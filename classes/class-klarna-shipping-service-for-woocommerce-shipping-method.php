<?php
/**
 * Shipping method class file.
 *
 * @package KlarnaShippingService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping method class.
 */
class Klarna_Shipping_Serivce_For_WooCommerce_Shipping_Method extends WC_Shipping_Method {

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
		$label = 'Klarna Shipping Service';
		$cost  = 0;
		if ( null !== WC()->session->get( 'kco_wc_order_id' ) ) {
			$klarna_order = KCO_WC()->api->get_order();
			if ( isset( $klarna_order->selected_shipping_option ) ) {
				$label      = $klarna_order->selected_shipping_option->name;
				$cost       = floatval( $klarna_order->selected_shipping_option->price - $klarna_order->selected_shipping_option->tax_amount ) / 100;
				$tax_amount = floatval( $klarna_order->selected_shipping_option->tax_amount ) / 100;

				$rate = array(
					'id'    => $this->id,
					'label' => $label,
					'cost'  => $cost,
					'taxes' => $tax_amount,
				);
			}
			$this->add_rate( $rate );
		}
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
	$methods['klarna_kss'] = 'Klarna_Shipping_Serivce_For_WooCommerce_Shipping_Method';
	return $methods;
}
