# Klarna Shipping Service for WooCommerce

Klarna Shipping Service for WooCommerce is an add-on plugin to Klarna Checkout for WooCommerce. This add-on plugin makes it possible to display shipping options in the KCO iframe provided by your TMS.

---
### Requirements
* Your Klarna account needs to be activated for Klarna Shipping Service with TMS before using this plugin.
* Requires [WooCommerce](https://wordpress.org/plugins/woocommerce/) together with [Klarna Checkout for WooCommerce](https://wordpress.org/plugins/klarna-checkout-for-woocommerce/) to be used.

---
### Installation

To install this plugin you first need to have Klarna Checkout for WooCommerce installed. You install this plugin just like any other WordPress plugin:

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the ‘Plugins’ menu in WordPress Administration.

---
### Configuration

Read more about how to configure the plugin [in the documentation](https://docs.krokedil.com/article/298-klarna-shipping-service-for-woocommerce).

---
### No booking of shipment & no tracking ID reporting to Klarna
This plugin enables Klarna Shipping Service into Klarna Checkout and your WooCommerce order. However, the actual booking of the shipment in your transport administration system is not handled in this plugin. There are a lot of TMS services available and every system has their own API. That's why we don't to connect to a specific TMS in this plugin.

---
### Feature roadmap
The Klarna Shipping Service plugin is in active development. New features are introduced on an ongoing basis. The following features are currently planned: 

* More detailed display of shipping meta data (pickup location etc) in WooCommerce order.
* Creating endpoint for external services to update the WooCommerce order with the tracking ID for the shipment created in the TMS (for those TMS'es that doesn't report this directly to Klarna).
* Develop plugin to Unifaun and other TMS platforms to offer a better and more complete integration experience.