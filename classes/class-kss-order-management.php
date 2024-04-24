<?php
/**
 * Order management class.
 *
 * @package KlarnaShippingService/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order management class.
 */
class KSS_Order_Management {
	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $this, 'maybe_correct_taxes' ) );

		add_filter( 'kom_order_capture_args', array( $this, 'maybe_change_kss_shipping_line' ), 10, 2 );
		add_filter( 'kom_refund_order_args', array( $this, 'maybe_change_kss_shipping_line' ), 10, 2 );
		add_filter( 'kom_order_update_args', array( $this, 'maybe_change_kss_shipping_line' ), 10, 2 );
	}

	/**
	 * Maybe correct taxes.
	 *
	 * @param WC_Order_Item_Shipping $item Shipping item.
	 *
	 * @return void
	 */
	public function maybe_correct_taxes( $item ) {
		// If the method id is not klarna_kss, return.
		if ( 'klarna_kss' !== $item->get_method_id() ) {
			return;
		}

		$order = $item->get_order();

		if ( ! $order ) {
			return;
		}

		// Get the KSS data from the orders meta data.
		$kss_data = json_decode( $order->get_meta( '_kco_kss_data' ), true );

		// If the KSS data is not set, return.
		if ( ! $kss_data ) {
			return;
		}

		$taxes         = $item->get_taxes();
		$tax_rate      = $kss_data['tax_rate'] / 100;
		$price_inc_tax = round( $kss_data['price'] / 100, 2 );

		// Get the keys from the taxes['total'] array.
		$tax_keys = array_keys( $taxes['total'] );

		$rate = array(
			'compound' => false,
			'rate'     => $tax_rate,
		);

		$rates[ $tax_keys[0] ] = $rate;

		$taxes = WC_Tax::calc_inclusive_tax( $price_inc_tax, $rates );

		// Set the tax amount.
		$item->set_taxes( array( 'total' => $taxes ) );
	}

	/**
	 * Maybe change KSS shipping line.
	 *
	 * @param array $data Order data.
	 * @param int   $order_id Order ID.
	 *
	 * @return array
	 */
	public function maybe_change_kss_shipping_line( $data, $order_id ) {
		error_log( var_export( $data, true ) );
		$order    = wc_get_order( $order_id );
		$kss_data = json_decode( $order->get_meta( '_kco_kss_data' ), true );

		if ( ! $kss_data ) {
			return $data;
		}

		$tax_rate = $kss_data['tax_rate'];
		$id       = $kss_data['id'];

		// Loop the order lines to find the KSS shipping line.
		foreach ( $data['order_lines'] as $key => $order_line ) {
			if ( 'shipping_fee' === $order_line['type'] && ( $id === $order_line['reference'] || false !== strpos( $order_line['reference'], 'klarna_kss' ) ) ) {
				// Set the KSS tax rate to what we have in the KSS data.
				$order_line['tax_rate']      = $tax_rate;
				$data['order_lines'][ $key ] = $order_line;
				break;
			}
		}

		return $data;
	}
}
