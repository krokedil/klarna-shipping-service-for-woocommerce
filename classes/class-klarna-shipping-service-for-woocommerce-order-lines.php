<?php
/**
 * Order lines class
 *
 * @package KlarnaShippingService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order lines class
 */
class Klarna_Shipping_Service_Order_Lines {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'kco_wc_api_request_args', array( $this, 'add_product_dimensions' ) );
	}

	/**
	 * Adds the product dimensions to the object.
	 *
	 * @param array $request_args The request args to Klarna.
	 * @return array
	 */
	public function add_product_dimensions( $request_args ) {
		$order_lines     = $request_args['order_lines'];
		$new_order_lines = array();
		foreach ( $order_lines as $order_line ) {
			$shipping_attributes = $this->get_shipping_attributes_for_product( $order_line['reference'] );
			if ( false !== $shipping_attributes ) {
				$order_line['shipping_attributes'] = $shipping_attributes;
			}
			$new_order_lines[] = $order_line;
		}
		$request_args['order_lines'] = $new_order_lines;
		error_log( var_export( $request_args, true ) );
		return $request_args;
	}

	/**
	 * Gets the shipping attributes for the product.
	 *
	 * @param string $sku The product SKU.
	 * @return array
	 */
	public function get_shipping_attributes_for_product( $sku ) {
		if ( 0 !== wc_get_product_id_by_sku( $sku ) ) {
			$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
		} else {
			$product = wc_get_product( $sku );
		}
		if ( ! $product ) {
			return false;
		}
		$shipping_attributes = array(
			'weight'     => round( wc_get_weight( $product->get_weight(), 'g' ) ),
			'dimensions' => array(
				'height' => round( wc_get_dimension( $product->get_height(), 'mm' ) ),
				'width'  => round( wc_get_dimension( $product->get_width(), 'mm' ) ),
				'length' => round( wc_get_dimension( $product->get_length(), 'mm' ) ),
			),
		);

		// Return array without any empty values.
		return array_filter(
			$shipping_attributes, function( $value ) {
				return ! is_null( $value ) && $value !== '';
			}
		);
	}

} new Klarna_Shipping_Service_Order_Lines();
