=== Klarna Shipping Assistant for WooCommerce ===
Contributors: krokedil
Tags: woocommerce, klarna, ecommerce, e-commerce, instant shopping
Requires at least: 5.0.0
Tested up to: 6.1
Requires PHP: 7.0
WC requires at least: 4.0.0
WC tested up to: 7.1.0
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== DESCRIPTION ==
Klarna Shipping Assistant for WooCommerce is an add-on plugin to Klarna Checkout for WooCommerce. This add-on plugin makes it possible to display shipping options in the KCO iframe provided by your TMS.

== Installation ==
To install this plugin you first need to have Klarna Checkout for WooCommerce installed. You install this plugin just like any other WordPress plugin:

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the ‘Plugins’ menu in WordPress Administration.

More information regarding configuration of this plugin can be found in the documentation here: https://docs.krokedil.com/article/298-klarna-shipping-service-for-woocommerce.

== Changelog ==
= 2022.10.26    - version 1.1.3 =
* Fix           - Fixed an issue where shipping method changes were detected incorrectly.

= 2022.10.26    - version 1.1.2 =
* Enhancement   - The merchant will be notified about detected discrepancies in the total amount in reference to Klarna Checkout.
* Enhancement   - Modified how we calculate shipping cost and tax to prevent rounding issues.

= 2021.06.02    - version 1.1.1 =
* Fix           - Fixed an issue that could cause the wrong tax class name to be displayed to the customer.

= 2021.02.15    - version 1.1.0 =
* Enhancement   - General improvements to the plugin to support the new version of KCO.

= 2021.02.15    - version 1.0.1 =
* Fix           - Fixed a bug caused by removing the shipping line from the Klarna order object when having a fee.

= 2020.12.16    - version 1.0.0 =
* Changed the name of the plugin to Klarna Shipping Assistant.
* Feature       - Added a feature that adds a tag to the Klarna order when a free shipping coupon is used. This tag is called "ksa_free_shipping" and can be read by the TMS to enable free shipping alternatives.
* Feature       - Added product shipping classes as tags to the Klarna order items. These tags can be read by the TMS system to change what shipping methods should be available.
* Feature       - Replace the shipping calculation on the cart page with a standard display for Klarna Shipping Assistant. It will either show the KSA shipping information if it exists, or it will tell the customer that shipping is calculated on the checkout page.
* Enhancement   - Improved the speed and reliability of the checkout process when using Klarna Shipping Assistant. These changes requires you to use this plugin along with Klarna Checkout for WooCommerce version 2.4.1 or newer. Older versions will no longer work.

= 2020.08.17    - version 0.2.1 =
* Fix           - Save Klarna data in Woo order and reply to JS validation event even for 0 value orders.

= 2020.08.12    - version 0.2.0 =
* Enhancement   - Display KCO checkout even if order total is 0. This is needed to save tms data correctly in Woo order.

= 2020.04.09    - version 0.1.6 =
* Enhancement   - Added support for special signs (åäö) get formatted correctly to the order.
* Enhancement   - Allow plugins to add setting to KSS shipping method.

= 2020.03.10	- version 0.1.5 =
* Enhancement   - Added support for Klarna Checkout For WooCommerce 2.x.

= 2020.02.26	- version 0.1.4 =
* Fix           - Wrapped our functions in method exists to prevent error if WooCommerce is disabled.
* Enhancement   - Added tax amount to the shipping method for KSS orders.

= 2019.11.12	- version 0.1.3 =
* Fix           - Reverted the way shipping tax is added to order.

= 2019.11.11	- version 0.1.2 =
* Fix           - Correctly add shipping tax to order.
* Fix           - Default to 0 for missing product dimensions.

= 2019.08.15	- version 0.1.1 =
* Tweak         - Updated readme.

= 2019.08.15	- version 0.1 =
* Initial release
