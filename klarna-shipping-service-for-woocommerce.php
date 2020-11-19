<?php // phpcs:ignore
/**
 * Plugin Name: Klarna Shipping Service for WooCommerce
 * Plugin URI: https://krokedil.com/klarna/
 * Description: Klarna Shipping Service for WooCommerce.
 * Author: Krokedil
 * Author URI: https://krokedil.com/
 * Version: 0.2.1
 * Text Domain: klarna-shipping-service-for-woocommerce
 * Domain Path: /languages
 *
 * WC requires at least: 3.8
 * WC tested up to: 4.3.2
 *
 * Copyright (c) 2017-2019 Krokedil
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Define plugin constants.
define( 'KLARNA_KSS_VERSION', '0.2.1' );
define( 'KLARNA_KSS_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'KLARNA_KSS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

/**
 * Plugin main class.
 */
class Klarna_Shipping_Service_For_WooCommerce {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'check_version' ) );
		add_action( 'kco_wc_process_payment', array( $this, 'add_shipping_details_to_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'clear_shipping_and_recalculate' ) );
		add_filter( 'kco_wc_chosen_shipping_method', array( $this, 'set_shipping_method' ) );
		add_filter( 'kco_check_if_needs_payment', array( $this, 'change_check_if_needs_payment' ) );
	}

	/**
	 * Initiates the plugin.
	 *
	 * @return void
	 */
	public function init() {
		$this->include_files();
	}

	/**
	 * Include the plugin files.
	 *
	 * @return void
	 */
	public function include_files() {
		// Include classes.
		include_once KLARNA_KSS_PATH . '/classes/class-klarna-shipping-service-for-woocommerce-shipping-method.php';
		include_once KLARNA_KSS_PATH . '/classes/class-klarna-shipping-service-for-woocommerce-order-lines.php';
		include_once KLARNA_KSS_PATH . '/classes/class-klarna-shipping-service-for-woocommerce-free-orders.php';
	}

	/**
	 * Returns the shipping method ID.
	 *
	 * @param string $shipping_method WooCommerce shipping method ID.
	 * @return string The shipping method ID for this shipping method.
	 */
	public function set_shipping_method( $shipping_method ) {
		$shipping_methods = WC()->shipping->get_shipping_methods();
		// Only do this if we have Klarna KSS active on the store, and the returned shipping method is NOT a real WooCommerce shipping method.
		if ( isset( $shipping_methods['klarna_kss'] ) && ! isset( $shipping_methods[ $shipping_method[0] ] ) ) {
			$chosen_shipping_methods[] = 'klarna_kss';
			return $chosen_shipping_methods;
		}
		return $shipping_method;
	}

	/**
	 * Adds the shipping details from KSS to the WooCommerce order.
	 *
	 * @param int   $order_id The WooCommerce order id.
	 * @param array $klarna_order The Klarna order.
	 * @return void
	 */
	public function add_shipping_details_to_order( $order_id, $klarna_order ) {
		if ( isset( $klarna_order['selected_shipping_option'] ) ) {
			$shipping_details = $klarna_order['selected_shipping_option'];
			update_post_meta( $order_id, '_kco_kss_data', wp_json_encode( $shipping_details, JSON_UNESCAPED_UNICODE ) );
			update_post_meta( $order_id, '_kco_kss_reference', $shipping_details['tms_reference'] );
			WC()->session->__unset( 'kco_kss_enabled' );
		}
	}

	/**
	 * Clears the shipping calculations to prevent errors.
	 *
	 * @return void
	 */
	public function clear_shipping_and_recalculate() {
		if ( 'kco' === WC()->session->get( 'chosen_payment_method' ) ) {
			WC()->session->set( 'kco_kss_enabled', true );
			WC()->session->__unset( 'shipping_for_package_0' );
			WC()->cart->calculate_shipping();
		} else {
			if ( null !== WC()->session->get( 'kco_kss_enabled' ) ) {
				WC()->session->__unset( 'shipping_for_package_0' );
				WC()->session->__unset( 'kco_kss_enabled' );
			}
		}
	}

	/**
	 * Make sure that KCO iframe is displayed in checkout even if order total is 0.
	 * This is needed so we can save the tms data to the Woo order.
	 *
	 * @param bool $bool Wether or not the plugin should check if KCO checkout should be displayed. Defaults to true.
	 *
	 * @return bool
	 */
	public function change_check_if_needs_payment( $bool ) {
		// Allways return false. We want to display the KCO iframe even if order total is 0.
		return false;
	}

	/**
	 * Checks the plugin version.
	 *
	 * @return void
	 */
	public function check_version() {
		require KLARNA_KSS_PATH . '/includes/plugin_update_check.php';
		$KernlUpdater = new PluginUpdateChecker_2_0( // phpcs:ignore
			'https://kernl.us/api/v1/updates/5d55892b8e5ece2071af8e83/',
			__FILE__,
			'klarna-shipping-service-for-woocommerce',
			1
		);
	}
}
new Klarna_Shipping_Service_For_WooCommerce();
