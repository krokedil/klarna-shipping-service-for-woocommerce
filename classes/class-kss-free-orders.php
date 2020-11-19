<?php // phpcs:ignore
/**
 * Free orders class
 *
 * @package KlarnaShippingService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free Orders class
 */
class KSS_Free_Orders {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		$kco_settings   = get_option( 'woocommerce_kco_settings' );
		$this->testmode = 'yes' === $kco_settings['testmode'];

		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'handle_free_kco_orders' ), 20, 2 );
	}

	/**
	 * Set Klarna data in order even for free orders and finally reply to Klarnas JS validation event so order can proceed to confirmation step.
	 *
	 * @param string $redirect_url The redirect to thankyou page url.
	 * @param object $order The WooCommerce order.
	 * @return string the modified url, to be able to reply to Klarnas JS validation event.
	 */
	public function handle_free_kco_orders( $redirect_url, $order ) {

		if ( 'kco' === $order->get_payment_method() ) {
			$order_id = $order->get_id();

			if ( $this->process_payment_handler( $order_id ) ) {
				// Base64 encoded timestamp to always have a fresh URL for on hash change event.
				$redirect_url = '#klarna-success=' . base64_encode( microtime() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- Base64 used to give a unique nondescript string.
			} else {
				$redirect_url = '#error';
			}
		}

		return $redirect_url;
	}

	/**
	 * Process the payment with information from Klarna and return the result.
	 *
	 * @param  int $order_id WooCommerce order ID.
	 *
	 * @return mixed
	 */
	public function process_payment_handler( $order_id ) {
		// Get the Klarna order ID.
		$order = wc_get_order( $order_id );
		if ( is_object( $order ) && $order->get_transaction_id() ) {
			$klarna_order_id = $order->get_transaction_id();
		} else {
			$klarna_order_id = WC()->session->get( 'kco_wc_order_id' );
		}

		$klarna_order = KCO_WC()->api->get_klarna_order( $klarna_order_id );
		if ( ! $klarna_order ) {
			return false;
		}

		if ( $order_id && $klarna_order ) {

			// Set WC order transaction ID.
			update_post_meta( $order_id, '_wc_klarna_order_id', sanitize_key( $klarna_order['order_id'] ) );

			update_post_meta( $order_id, '_transaction_id', sanitize_key( $klarna_order['order_id'] ) );

			$environment = $this->testmode ? 'test' : 'live';
			update_post_meta( $order_id, '_wc_klarna_environment', $environment );

			$klarna_country = wc_get_base_location()['country'];
			update_post_meta( $order_id, '_wc_klarna_country', $klarna_country );

			// Set shipping phone and email.
			update_post_meta( $order_id, '_shipping_phone', sanitize_text_field( $klarna_order['shipping_address']['phone'] ) );
			update_post_meta( $order_id, '_shipping_email', sanitize_text_field( $klarna_order['shipping_address']['email'] ) );

			// Update the order with new confirmation page url.
			$klarna_order = KCO_WC()->api->update_klarna_order( $klarna_order_id, $order_id );

			$order->save();
			// Let other plugins hook into this sequence.
			do_action( 'kco_wc_process_payment', $order_id, $klarna_order );

			// Check that the transaction id got set correctly.
			if ( get_post_meta( $order_id, '_transaction_id', true ) === $klarna_order_id ) {
				return true;
			}
		}
		// Return false if we get here. Something went wrong.
		return false;
	}

} new KSS_Free_Orders();
