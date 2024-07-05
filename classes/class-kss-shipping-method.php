<?php // phpcs:ignore
/**
 * Shipping method class file.
 *
 * @package KlarnaShippingService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Shipping_Method' ) ) {

	/**
	 * Shipping method class.
	 */
	class KSS_Shipping_Method extends WC_Shipping_Method {

		/**
		 * Total amount for the shipping from Klarna.
		 *
		 * @var float
		 */
		public $kss_total_amount;

		/**
		 * Total tax amount for the shipping from Klarna.
		 *
		 * @var float
		 */
		public $kss_tax_amount;

		/**
		 * Tax rate for the shipping from Klarna.
		 *
		 * @var float
		 */
		public $kss_tax_rate;

		/**
		 * Class constructor.
		 *
		 * @param integer $instance_id The instance id.
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'klarna_kss';
			$this->instance_id        = absint( $instance_id );
			$this->title              = 'Klarna Shipping Assistant';
			$this->method_title       = __( 'Klarna Shipping Assistant', 'klarna-shipping-service-for-woocommerce' );
			$this->method_description = __( 'Enables Klarna Shipping Assistant for WooCommerce', 'klarna-shipping-service-for-woocommerce' );
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);
			$this->kss_tax_amount     = false;
			$this->init_form_fields();
			$this->init_settings();

			add_filter( 'woocommerce_calc_shipping_tax', array( $this, 'maybe_override_shipping_tax' ), 999, 3 );
		}
		/**
		 * Init form fields.
		 */
		public function init_form_fields() {
			$this->instance_form_fields = array(
				'title' => array(
					'title'       => __( 'Klarna Shipping Assistant', 'klarna-shipping-service-for-woocommerce' ),
					'type'        => 'title',
					'description' => __( 'There are currently no settings for Klarna Shipping Assistant since this is controlled by the TMS-provider. If other plugins adds settings, these are shown below.', 'klarna-shipping-service-for-woocommerce' ),
				),
			);
		}

		/**
		 * Check if shipping method should be available.
		 *
		 * @param array $package The shipping package.
		 * @return boolean
		 */
		public function is_available( $package ) {
			if ( null !== WC()->session->get( 'kco_kss_enabled' ) && WC()->session->get( 'kco_kss_enabled' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Calculate shipping cost.
		 *
		 * @param array $package The shipping package.
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			$label           = 'Klarna Shipping Assistant';
			$cost            = 0;
			$klarna_order_id = WC()->session->get( 'kco_wc_order_id' );
			$shipping_data   = get_transient( 'kss_data_' . $klarna_order_id );
			$rate            = array();
			if ( ! empty( $shipping_data ) ) {
				if ( isset( $shipping_data['shipping_method'] ) && 'digital' === strtolower( $shipping_data['shipping_method'] ) ) {
					add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
					return;
				}

				$label = $shipping_data['name'] ?? $label;

				// To prevent rounding issues from Klarna sending us a max of 2 decimals, we need to calculate the actual tax cost and subtract that from the total.
				$this->kss_tax_amount   = round( $shipping_data['tax_amount'] / 100, 2 );
				$this->kss_tax_rate     = $shipping_data['tax_rate'] / 100;
				$this->kss_total_amount = round( $shipping_data['price'] / 100, 2 );

				$shipping_tax = WC_Tax::calc_shipping_tax( $this->kss_total_amount, WC_Tax::get_shipping_tax_rates() );
				$cost         = $this->kss_total_amount - array_sum( $shipping_tax );

				$rate = array(
					'id'       => $this->get_rate_id(),
					'label'    => $label,
					'cost'     => $cost,
					'taxes'    => $shipping_tax,
					'calc_tax' => 'per_order',
				);

				/* Klarna already converts the shipping cost to the purchase currency. To avoid double-conversion, we must pass the currency onto the currency switchers. */
				if ( isset( $shipping_data['currency'] ) ) {
					$rate['meta_data'] = array(
						'currency' => $shipping_data['currency'],
					);

					/* WPML do not respect the meta data currency property. */
					global $woocommerce_wpml;
					if ( isset( $woocommerce_wpml ) && $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
						$rate['cost'] = $woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $rate['cost'], $shipping_data['currency'] );
					}
				}
			}
			$this->add_rate( apply_filters( 'klarna_kss_shipping_method_add_rate', $rate ) );
		}

		/**
		 * Maybe override the shipping tax from the WooCommerce calculations.
		 *
		 * @param array $taxes The calculated taxes.
		 * @param float $price The price.
		 * @param array $rates The tax rates.
		 *
		 * @return array
		 */
		public function maybe_override_shipping_tax( $taxes, $price, $rates ) {
			// Replace the value of the first tax with the value from Klarna, and keep the same key.
			if ( $this->kss_tax_amount ) {
				$taxes[ key( $taxes ) ] = $this->kss_tax_amount;
			}

			// Return an array with the first tax rate as key and the tax amount as value.
			return $taxes;
		}
	}

	add_filter( 'woocommerce_shipping_methods', 'add_kss_shipping_method' );
	/**
	 * Registers the shipping method.
	 *
	 * @param array $methods WooCommerce shipping methods.
	 * @return array
	 */
	function add_kss_shipping_method( $methods ) {
		$methods['klarna_kss'] = 'KSS_Shipping_Method';
		return $methods;
	}
}