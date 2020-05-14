=== Klarna Shipping Service for WooCommerce ===
Contributors: krokedil
Tags: woocommerce, klarna, ecommerce, e-commerce, instant shopping
Requires at least: 5.0.0
Tested up to: 5.4.0
Requires PHP: 5.6
WC requires at least: 3.5.0
WC tested up to: 4.1.0
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== DESCRIPTION ==
Klarna Shipping Service for WooCommerce is an add-on plugin to Klarna Checkout for WooCommerce. This add-on plugin makes it possible to display shipping options in the KCO iframe provided by your TMS.

== Installation ==
To install this plugin you first need to have Klarna Checkout for WooCommerce installed. You install this plugin just like any other WordPress plugin:

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the ‘Plugins’ menu in WordPress Administration.

More information regarding configuration of this plugin can be found in the documentation here: https://docs.krokedil.com/article/298-klarna-shipping-service-for-woocommerce.

== Changelog ==
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