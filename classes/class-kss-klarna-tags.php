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
class KSS_Klarna_Tags {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'kco_wc_api_request_args', array( $this, 'maybe_add_free_shipping_tag' ) );
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
} new KSS_Klarna_Tags();
