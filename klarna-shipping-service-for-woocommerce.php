<?php // phpcs:ignore
/**
 * Plugin Name: Kustom Shipping Assistant for WooCommerce
 * Plugin URI: https://krokedil.com/klarna/
 * Description: Kustom Shipping Assistant for WooCommerce.
 * Author: Krokedil
 * Author URI: https://krokedil.com/
 * Version: 1.3.3
 * Text Domain: klarna-shipping-service-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * WC requires at least: 3.8
 * WC tested up to: 11.1.0
 *
 * Copyright (c) 2017-2026 Krokedil
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
define( 'KLARNA_KSS_VERSION', '1.3.3' );
define( 'KLARNA_KSS_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'KLARNA_KSS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

/**
 * Plugin main class.
 */
class Klarna_Shipping_Service_For_WooCommerce {
	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var Klarna_Shipping_Service_For_WooCommerce $instance
	 */
	private static $instance = null;

	/**
	 * The hook registry instance.
	 *
	 * @var \Krokedil\KustomShippingService\HookRegistry
	 */
	protected $hook_registry;

	/**
	 * The API registry instance.
	 *
	 * @var \Krokedil\KustomShippingService\API\ApiRegistry
	 */
	protected $api_registry;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Klarna_Shipping_Service_For_WooCommerce The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Nope', 'klarna-shipping-service-for-woocommerce' ), '1.0' );
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Nope', 'klarna-shipping-service-for-woocommerce' ), '1.0' );
	}

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'check_version' ) );
		add_action( 'kco_wc_process_payment', array( $this, 'add_shipping_details_to_order' ), 10, 2 );
		add_action( 'kco_update_shipping_data', array( $this, 'clear_shipping_and_recalculate' ) );
		add_filter( 'kco_wc_chosen_shipping_method', array( $this, 'set_shipping_method' ) );
		add_filter( 'kco_check_if_needs_payment', array( $this, 'change_check_if_needs_payment' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
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
	 * Add plugin action links.
	 *
	 * @param array $links Plugin action link before filtering.
	 * @return array Filtered links.
	 */
	public function plugin_action_links( $links ) {
		$docs_url     = 'https://docs.krokedil.com/kustom-checkout-for-woocommerce/get-started/kustom-shipping-assistant/';
		$plugin_links = array(
			'<a target="_blank" href="' . $docs_url . '">' . __( 'Docs', 'klarna-shipping-service-for-woocommerce' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Include the plugin files.
	 *
	 * @return void
	 */
	public function include_files() {
		// Include the autoloader from composer. If it fails, we'll just return and not load the plugin. But an admin notice will show to the merchant.
		if ( ! self::init_composer() ) {
			return;
		}

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

		$this->hook_registry = new \Krokedil\KustomShippingService\HookRegistry();
		$this->api_registry  = new \Krokedil\KustomShippingService\API\ApiRegistry();
	}

	/**
	 * Returns the shipping method ID.
	 *
	 * @param array $chosen_shipping_methods WooCommerce shipping method ID.
	 * @return array The shipping method ID for this shipping method.
	 */
	public function set_shipping_method( $chosen_shipping_methods ) {
		$shipping_methods = WC()->shipping->get_shipping_methods();
		// Only do this if we have Kustom KSS active on the store, and the returned shipping method is NOT a real WooCommerce shipping method.
		if ( isset( $shipping_methods['klarna_kss'] ) && ! isset( $shipping_methods[ $chosen_shipping_methods[0] ] ) ) {
			return array( $this->get_kss_rate_id() );
		}
		return $chosen_shipping_methods;
	}

	/**
	 * Returns the rate ID of this store's Kustom Shipping Assistant shipping method.
	 *
	 * @return string The rate ID, or the bare method ID if no enabled instance was found.
	 */
	private function get_kss_rate_id() {
		if ( ! WC()->cart ) {
			return 'klarna_kss';
		}

		// The method is zone-instantiated, so WooCommerce matches it by rate ID ('klarna_kss:49') and falls back to the first rate in the zone on a miss.
		foreach ( WC()->cart->get_shipping_packages() as $package ) {
			$zone = WC_Shipping_Zones::get_zone_matching_package( $package );
			foreach ( $zone->get_shipping_methods( true ) as $shipping_method ) {
				if ( 'klarna_kss' === $shipping_method->id ) {
					return $shipping_method->get_rate_id();
				}
			}
		}

		return 'klarna_kss';
	}

	/**
	 * Adds the shipping details from KSS to the WooCommerce order.
	 *
	 * @param int   $order_id The WooCommerce order id.
	 * @param array $klarna_order The Kustom order.
	 * @return void
	 */
	public function add_shipping_details_to_order( $order_id, $klarna_order ) {
		if ( isset( $klarna_order['selected_shipping_option'] ) ) {
			$kco_id = $klarna_order['id'];
			$order  = wc_get_order( $order_id );

			$shipping_details = $klarna_order['selected_shipping_option'];
			if ( isset( $shipping_details['tms_reference'] ) ) {
				$order->update_meta_data( '_kco_kss_reference', $shipping_details['tms_reference'] );
			}

			// Update the shipping details with the override data if it exists, since we want to save the overridden shipping details to the order, not the original ones from KSS.
			$override_data = get_transient( "kss_override_data_$kco_id" );
			if ( $override_data ) {
				$shipping_details['price']      = $override_data['price'] ?? $shipping_details['price'];
				$shipping_details['name']       = $override_data['name'] ?? $shipping_details['name'];
				$shipping_details['tax_rate']   = $override_data['tax_rate'] ?? $shipping_details['tax_rate'];
				$shipping_details['tax_amount'] = $override_data['tax_amount'] ?? $shipping_details['tax_amount'];
			}

			$order->update_meta_data( '_kco_kss_data', wp_json_encode( $shipping_details, JSON_UNESCAPED_UNICODE ) );
			$order->save();
			WC()->session->__unset( 'kco_kss_enabled' );

			// Clear the kss_override_data_{order_id} transient since we have now saved the shipping data to the order.
			delete_transient( "kss_override_data_$kco_id" );
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
		} elseif ( null !== WC()->session->get( 'kco_kss_enabled' ) ) {
			WC()->session->__unset( 'kco_kss_enabled' );
		}

		// Clear this customer's cached shipping rates so WooCommerce re-runs shipping on the next
		// calculation. We unset every 'shipping_for_package_*' session key (not just the main-cart packages).
		foreach ( array_keys( WC()->session->get_session_data() ) as $session_key ) {
			if ( 0 === strpos( $session_key, 'shipping_for_package_' ) ) {
				WC()->session->__unset( $session_key );
			}
		}
	}

	/**
	 * Make sure that KCO iframe is displayed in checkout even if order total is 0.
	 * This is needed so we can save the tms data to the Woo order.
	 *
	 * @param bool $needs_payment Wether or not the plugin should check if KCO checkout should be displayed. Defaults to true.
	 *
	 * @return bool
	 */
	public function change_check_if_needs_payment( $needs_payment ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- We need to have the $needs_payment parameter to be able to use this as a filter for 'kco_check_if_needs_payment'.
		// Always return false. We want to display the KCO iframe even if order total is 0.
		return false;
	}

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

	/**
	 * Get the instance of the hook registry class.
	 *
	 * @return Krokedil\KustomShippingService\HookRegistry
	 */
	public function hook_registry() {
		return $this->hook_registry;
	}

	/**
	 * Get the instance of the API registry class.
	 *
	 * @return Krokedil\KustomShippingService\API\ApiRegistry
	 */
	public function api_registry() {
		return $this->api_registry;
	}

	/**
	 * Initialize composers autoloader. If it does not exist, bail and show an error.
	 *
	 * @return mixed
	 */
	private static function init_composer() {
		$autoloader = KLARNA_KSS_PATH . '/vendor/autoload.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader();
			return false;
		}

		$autoloader_result = require $autoloader;

		if ( ! $autoloader_result ) {
			return false;
		}

		return true;
	}

	/**
	 * Print error message for missing autoloader.
	 *
	 * @return void
	 */
	private static function missing_autoloader() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore
				esc_html__( 'Your installation of Kustom Shipping Assistant for WooCommerce is not complete. If you installed this plugin directly from Github please refer to the readme.dev.md file in the plugin.', 'klarna-shipping-service-for-woocommerce' )
			);
		}

		add_action(
			'admin_notices',
			function () {
				?>
					<div class="notice notice-error">
						<p>
							<?php echo esc_html__( 'Your installation of Kustom Shipping Assistant for WooCommerce is not complete. If you installed this plugin directly from Github please refer to the readme.dev.md file in the plugin.', 'klarna-shipping-service-for-woocommerce' ); ?>
						</p>
					</div>
				<?php
			}
		);
	}
}

/**
 * Main instance of Kustom Shipping Assistant.
 *
 * Returns the main instance of Kustom Shipping Assistant.
 *
 * @return Klarna_Shipping_Service_For_WooCommerce
 */
function kustom_shipping_assistant() { // phpcs:ignore
	return Klarna_Shipping_Service_For_WooCommerce::get_instance();
}

// Create a instance of the plugin to load it.
kustom_shipping_assistant();
