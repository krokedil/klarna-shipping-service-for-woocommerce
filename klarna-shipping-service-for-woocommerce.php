<?php // phpcs:ignore
/**
 * Plugin Name: Klarna Shipping Assistant for WooCommerce
 * Plugin URI: https://krokedil.com/klarna/
 * Description: Klarna Shipping Assistant for WooCommerce.
 * Author: Krokedil
 * Author URI: https://krokedil.com/
 * Version: 1.2.0
 * Text Domain: klarna-shipping-service-for-woocommerce
 * Domain Path: /languages
 *
 * WC requires at least: 3.8
 * WC tested up to: 8.2.2
 *
 * Copyright (c) 2017-2023 Krokedil
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
define( 'KLARNA_KSS_VERSION', '1.2.0' );
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
		add_action( 'kco_update_shipping_data', array( $this, 'clear_shipping_and_recalculate' ) );
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

		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatability' ) );
	}

	/**
	 * Declare compatibility with WooCommerce features.
	 *
	 * @return void
	 */
	public function declare_wc_compatability() {
		// Declare HPOS compatibility.
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Include the plugin files.
	 *
	 * @return void
	 */
	public function include_files() {
		// Include classes.
		if ( is_admin() ) {
			include_once KLARNA_KSS_PATH . '/classes/class-kss-admin-notices.php';
		}
		include_once KLARNA_KSS_PATH . '/classes/class-kss-cart-page.php';
		include_once KLARNA_KSS_PATH . '/classes/class-kss-shipping-method.php';
		include_once KLARNA_KSS_PATH . '/classes/class-kss-order-lines.php';
		include_once KLARNA_KSS_PATH . '/classes/class-kss-free-orders.php';
		include_once KLARNA_KSS_PATH . '/classes/class-kss-edit-klarna-order.php';
		include_once KLARNA_KSS_PATH . '/classes/class-kss-compare-totals.php';
	}

	/**
	 * Returns the shipping method ID.
	 *
	 * @param array $chosen_shipping_methods WooCommerce shipping method ID.
	 * @return array The shipping method ID for this shipping method.
	 */
	public function set_shipping_method( $chosen_shipping_methods ) {
		$shipping_methods = WC()->shipping->get_shipping_methods();
		// Only do this if we have Klarna KSS active on the store, and the returned shipping method is NOT a real WooCommerce shipping method.
		if ( isset( $shipping_methods['klarna_kss'] ) && ! isset( $shipping_methods[ $chosen_shipping_methods[0] ] ) ) {
			return array( 'klarna_kss' );
		}
		return $chosen_shipping_methods;
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
			$order = wc_get_order( $order_id );

			$shipping_details = $klarna_order['selected_shipping_option'];
			$order->update_meta_data( '_kco_kss_data', wp_json_encode( $shipping_details, JSON_UNESCAPED_UNICODE ) );
			$order->update_meta_data( '_kco_kss_reference', $shipping_details['tms_reference'] );
			$order->save();
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
			$packages = WC()->cart->get_shipping_packages();
			foreach ( $packages as $package_key => $package ) {
				$session_key = 'shipping_for_package_' . $package_key;
				WC()->session->__unset( $session_key );
			}
		} else {
			if ( null !== WC()->session->get( 'kco_kss_enabled' ) ) {
				WC()->session->__unset( 'kco_kss_enabled' );
				$packages = WC()->cart->get_shipping_packages();
				foreach ( $packages as $package_key => $package ) {
					$session_key = 'shipping_for_package_' . $package_key;
					WC()->session->__unset( $session_key );
				}
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

	//Declare HPOS compatibility
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );
	

	/**
	 * Checks the plugin version.
	 *
	 * @return void
	 */
	public function check_version() {
		require KLARNA_KSS_PATH . '/kernl-update-checker/kernl-update-checker.php';

		$update_checker = Puc_v4_Factory::buildUpdateChecker(
			'https://kernl.us/api/v1/updates/5d55892b8e5ece2071af8e83/',
			__FILE__,
			'klarna-shipping-service-for-woocommerce'
		);
	}
}
new Klarna_Shipping_Service_For_WooCommerce();
