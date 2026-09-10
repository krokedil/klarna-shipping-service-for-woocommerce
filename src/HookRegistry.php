<?php
namespace Krokedil\KustomShippingService;

use Krokedil\KustomShippingService\API\Controllers\ShippingOptionUpdateController;
use Krokedil\KustomShippingService\Services\ShippingCostRequiresAddress;

\defined( 'ABSPATH' ) || exit;

/**
 * Class HookRegistry.
 *
 * Registers the hooks for the Kustom Shipping Service plugin with WordPress.
 *
 * @package Krokedil\KustomShippingService
 */
class HookRegistry {
	/**
	 * The service that decides whether WooCommerce's shipping address gate applies.
	 *
	 * @var ShippingCostRequiresAddress
	 */
	private $shipping_cost_requires_address;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->shipping_cost_requires_address = new ShippingCostRequiresAddress();

		add_filter( 'kco_wc_gateway_settings', array( $this, 'add_callback_settings' ) );
		add_filter( 'kco_wc_merchant_urls', array( $this, 'maybe_add_shipping_option_change_callback_url' ) );
		add_filter( 'kco_wc_api_request_args', array( $this, 'maybe_add_subscription_free_trial_tag' ) );
		add_filter( 'option_woocommerce_shipping_cost_requires_address', array( $this->shipping_cost_requires_address, 'maybe_disable' ) );
	}

	/**
	 * Get the instance of the shipping cost requires address service.
	 *
	 * Exposed so the filter above can be removed with remove_filter() without
	 * having to drop every other callback on the option.
	 *
	 * @return ShippingCostRequiresAddress
	 */
	public function shipping_cost_requires_address() {
		return $this->shipping_cost_requires_address;
	}

	/**
	 * Add the settings for the Kustom Shipping Service plugin to the KCO settings page.
	 *
	 * @param array $settings The existing settings for the KCO plugin.
	 *
	 * @return array The modified settings for the KCO plugin, including the settings for the Kustom Shipping Service plugin.
	 */
	public function add_callback_settings( $settings ) {
		// Get the index for the 'shipping_section_end' setting, so we can add our setting as the last one before it.
		$shipping_section_end_index = array_search( 'shipping_section_end', array_keys( $settings ), true );

		// Insert our setting before the 'shipping_section_end' setting.
		$settings = array_slice( $settings, 0, $shipping_section_end_index, true ) +
			array(
				'ksa_enable_shipping_option_update_callback' => array(
					'title'       => __( 'Enable shipping option update callback', 'klarna-shipping-service-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable the shipping option update callback for WooCommerce to override shipping data from Kustom Shipping Assistant.', 'klarna-shipping-service-for-woocommerce' ),
					'default'     => 'no',
					'description' => __( 'Enabling this setting will allow WooCommerce to override shipping data from Kustom Shipping Assistant with data. For example if you need to override the tax rate used by shipping options in cases where the TMS does not provide the correct tax rate.', 'klarna-shipping-service-for-woocommerce' ),
				),
			) +
			array_slice( $settings, $shipping_section_end_index, null, true );

		return $settings;
	}

	/**
	 * Maybe add the shipping option change callback URL to the merchant URLs array.
	 *
	 * @param array $merchant_urls The merchant URLs array.
	 *
	 * @return array The modified merchant URLs array.
	 */
	public function maybe_add_shipping_option_change_callback_url( $merchant_urls ) {
		// Only if we have an actual cart that is not empty.
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return $merchant_urls;
		}

		// If the cart does not need shipping, we don't need to add the shipping option update callback URL.
		if ( ! WC()->cart->needs_shipping() ) {
			return $merchant_urls;
		}

		// If the cart only has free trial subscriptions, we will always need to add the callback handler.
		$is_free_trial_subscription = $this->cart_contains_only_free_trial_subscription();
		$setting_enabled            = $this->is_shipping_option_update_callback_enabled();

		// Only if the cart has a subscription and it's a free trial subscription. Otherwise only if the setting is enabled.
		if ( ! $is_free_trial_subscription && ! $setting_enabled ) {
			return $merchant_urls;
		}

		$merchant_urls['shipping_option_update'] = add_query_arg(
			array( 'kco_id' => '{checkout.order.id}' ),
			kustom_shipping_assistant()->api_registry()->get_request_path( ShippingOptionUpdateController::class, 'shipping-option-update'
		) );

		return $merchant_urls;
	}

	/**
	 * Maybe add the subscription free trial tag to the Kustom Checkout request args.
	 *
	 * This is needed to ensure that the shipping option update callback URL is added for free trial subscriptions, since Kustom only adds the shipping option update callback URL if the "ksa_free_shipping" tag is present in the order.
	 *
	 * @param array $request_args The request args for Kustom Checkout.
	 *
	 * @return array The modified request args for Kustom Checkout.
	 */
	public function maybe_add_subscription_free_trial_tag( $request_args ) {
		$tags = isset( $request_args['tags'] ) ? $request_args['tags'] : array();

		if ( $this->cart_contains_only_free_trial_subscription() ) {
			$tags[] = 'ksa_subscription_free_trial_shipping';
		}

		$request_args['tags'] = $tags;
		return $request_args;
	}

	/**
	 * Helper method to see if the current cart contains only a free trial subscription and the shipping should be zero for the initial purchase.
	 *
	 * @return bool True if the cart contains only a free trial subscription, false otherwise.
	 */
	private function cart_contains_only_free_trial_subscription() {
		// If there is no cart or the cart is empty, return false.
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return false;
		}

		// Check if the cart contains only free trial subscriptions.
		return class_exists( 'WC_Subscriptions_Cart' ) && \WC_Subscriptions_Cart::all_cart_items_have_free_trial();
	}

	/**
	 * Helper method to see if the setting to enable the callback is enabled or not.
	 *
	 * @return bool True if the setting is enabled, false otherwise.
	 */
	private function is_shipping_option_update_callback_enabled() {
		$settings = get_option( 'woocommerce_kco_settings', array() );
		return 'yes' === ( $settings['ksa_enable_shipping_option_update_callback'] ?? 'no' );
	}
}
