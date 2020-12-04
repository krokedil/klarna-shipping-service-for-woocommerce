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
		 * Class constructor.
		 *
		 * @param integer $instance_id The instance id.
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'klarna_kss';
			$this->instance_id        = absint( $instance_id );
			$this->title              = 'Klarna Shipping Service';
			$this->method_title       = __( 'Klarna Shipping Service', 'klarna-shipping-service-for-woocommerce' );
			$this->method_description = __( 'Enables Klarna Shipping Service for WooCommerce', 'klarna-shipping-service-for-woocommerce' );
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);
			$this->kss_tax_amount     = false;
			$this->init_form_fields();
			$this->init_settings();
			add_filter( 'woocommerce_shipping_packages', array( $this, 'kss_add_tax' ) );
		}
		/**
		 * Init form fields.
		 */
		public function init_form_fields() {
			$this->instance_form_fields = array(
				'title' => array(
					'title'       => __( 'Klarna Shipping Services', 'klarna-shipping-service-for-woocommerce' ),
					'type'        => 'title',
					'description' => __( 'There are currently no settings for Klarna Shipping Services since this is controlled by the TMS-provider. If other plugins adds settings, these are shown below.', 'klarna-shipping-service-for-woocommerce' ),
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
			$label           = 'Klarna Shipping Service';
			$cost            = 0;
			$klarna_order_id = WC()->session->get( 'kco_wc_order_id' );
			$shipping_data   = get_transient( 'kss_data_' . $klarna_order_id );
			$rate            = array();
			if ( ! empty( $shipping_data ) ) {
				$label                  = $shipping_data['name'];
				$cost                   = floatval( $shipping_data['price'] - $shipping_data['tax_amount'] ) / 100;
				$tax_amount             = floatval( $shipping_data['tax_amount'] ) / 100;
				$this->kss_tax_amount   = $tax_amount;
				$this->kss_total_amount = $cost;
				$rate                   = array(
					'id'    => $this->get_rate_id(),
					'label' => $label,
					'cost'  => $cost,
				);
			}
			$this->add_rate( $rate );
		}

		/**
		 * Add tax amount to shipping.
		 *
		 * @param array $packages packages.
		 * @return array
		 */
		public function kss_add_tax( $packages ) {
			if ( false !== $this->kss_tax_amount ) {
				$tax_rate_id = $this->find_tax_rate_id_from_price();
				foreach ( $packages as $i => $package ) {
					foreach ( $package['rates'] as $rate_key => $rate_values ) {
						if ( 'klarna_kss' === $rate_values->method_id ) { // check that the shipping is KSS.
							$taxes = array();
							foreach ( $package['rates'][ $rate_key ]->taxes as $key => $tax ) {
								// set the KSS tax amount in the taxes array.
								$taxes[ $tax_rate_id ] = $this->kss_tax_amount;
							}
							// Set the tax amount.
							$package['rates'][ $rate_key ]->taxes = $taxes;
						}
					}
				}
			}
			return $packages;
		}

		/**
		 * Returns the WC tax rate id for the price sent by Klarna.
		 *
		 * @return void
		 */
		public function find_tax_rate_id_from_price() {
			$rate        = ( 0 === $this->kss_total_amount ) ? ( $this->kss_tax_amount / $this->kss_total_amount ) * 100 : 0;
			$customer    = WC()->customer;
			$tax_classes = WC_Tax::get_tax_classes();
			$tax_rates   = array();

			foreach ( $tax_classes as $tax_class ) {
				$class_rates = WC_Tax::get_rates_for_tax_class( $tax_class );
				foreach ( $class_rates as $class_rate ) {
					if ( ( '' === $class_rate->tax_rate_country || WC()->customer->get_billing_country() === $class_rate->tax_rate_country )
						&& '1' === $class_rate->tax_rate_shipping
						&& $rate == $class_rate->tax_rate ) {
						break;
					}
				}
			}

			return $class_rate->tax_rate_id;
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
