<?php // phpcs:ignore
/**
 * Klarna tags class
 *
 * @package KlarnaShippingService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klarna tags class
 */
class KSS_Edit_Klarna_Order {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'kco_wc_api_request_args', array( $this, 'maybe_add_free_shipping_tag' ) );
		add_filter( 'kco_wc_api_request_args', array( $this, 'remove_shipping' ) );
		add_filter( 'kco_wc_api_request_args', array( $this, 'remove_shipping_callback_url' ) );
	}

	/**
	 * Maybe adds the free shipping tag.
	 *
	 * @param array $request_args The request args for Klarna Checkout.
	 * @return array
	 */
	public function maybe_add_free_shipping_tag( $request_args ) {
		// Get old tags if they exist.
		$tags = isset( $request_args['tags'] ) ? $request_args['tags'] : array();
		foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );
			if ( $coupon->get_free_shipping() ) {
				$tags[] = 'ksa_free_shipping';
			}
		}
		$request_args['tags'] = $tags;
		return $request_args;
	}

	/**
	 * Remove shipping from the Klarna order. Since we don't use the server side callback, Klarna adds this themselves.
	 *
	 * @param array $request_args The request args for Klarna Checkout.
	 * @return array
	 */
	public function remove_shipping( $request_args ) {
		foreach ( $request_args['order_lines'] as $key => $order_line ) {
			if ( 'shipping_fee' === $order_line['type'] ) {
				unset( $request_args['order_lines'][ $key ] );
				$request_args['order_amount']     = $request_args['order_amount'] - $order_line['unit_price'];
				$request_args['order_tax_amount'] = $request_args['order_tax_amount'] - $order_line['total_tax_amount'];
			}
		}
		return $request_args;
	}

	/**
	 * Removes the shipping callback url incase it is set.
	 *
	 * @param array $request_args The request args for Klarna Checkout.
	 * @return array
	 */
	public function remove_shipping_callback_url( $request_args ) {
		if ( isset( $request_args['merchant_urls']['shipping_option_update'] ) ) {
			unset( $request_args['merchant_urls']['shipping_option_update'] );
		}
		return $request_args;
	}
} new KSS_Edit_Klarna_Order();
