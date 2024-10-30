=== HubOn Local Pickup ===
Contributors: HubOn
Tags: woocommerce, pickup, local pickup, shipping, delivery
Requires at least: 5.2
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.0
Version: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Short Description: Enhance WooCommerce with local pickup options, custom pickup dates, and transport management with the HubOn Local Pickup plugin.

== Description ==

HubOn Local Pickup enhances WooCommerce by providing local pickup options at checkout, managing transport statuses, and including a custom pickup date.

HubOn is a third-party service that allows customers to pick up their orders at nearby stores. To use the HubOn Local Pickup plugin, you must first register at [letshubon.com](https://letshubon.com). HubOn partners with local stores to offer a cost-effective, eco-friendly, and secure alternative to traditional shipping. HubOn also provides flexibility for customers to collect their orders at a convenient time. Ensure HubOn operates in your area by checking our service area and hub locations [here](https://letshubon.com/hubs).

For each transaction, a transport will be automatically created. Log in to [letshubon.com](https://letshubon.com) to pay for the transports and print the transport labels.

== Features ==

– Display the nearest HubOn pickup locations on the checkout page.
– Add a customizable pickup date selector on the checkout page.
– Automatically create a transport associated with your customer order on letshubon.com.
– List transport statuses including to be paid, paid, and failed transports.

HubOn's terms of use and privacy policies can be viewed [here](https://letshubon.com/legal).

== Installation ==

1. Upload the `hubon-local-pickup` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce settings, then the Shipping tab, and configure the settings under HubOn Local Pickup.
4. Enter the HubOn secret key. If you don't have a secret key, you can obtain one by visiting your account settings page [here](https://letshubon.com/accounts/integration).

== Frequently Asked Questions ==

=== Can we change the local pickup price? ===

We recommend offering local pickup for free with a minimum order, but you can adjust the prices of the hub options through your account settings on [letshubon.com](https://letshubon.com/accounts/integration).

=== Does this plugin work with any theme? ===

The plugin is designed to work with most WooCommerce-compatible themes. Styling adjustments may be necessary for specific themes.

=== Can I add a cut-off date for orders? ===

Yes, you can adjust the cut-off date through your account settings on [letshubon.com](https://letshubon.com/accounts/integration).

=== Can I customize the pickup days for my customers? ===

Yes, you can customize pickup days through your account settings on [letshubon.com](https://letshubon.com/accounts/integration).

More FAQs can be viewed [here](https://letshubon.com/faqs).

== Screenshots ==

1. The hub options displayed on the checkout page.
   ![Hub options](./docs/assets/images/hub-options.png)

2. Settings page for HubOn Local Pickup.
   ![Settings page](./docs/assets/images/setting-page.png)

3. The transport status lists within the WordPress admin.
   ![Transport status lists](./docs/assets/images/transport-status-lists.png)

== Changelog ==

= 1.0.0 =
* Initial release: Introduced nearest hub option, transport lists, and pickup date feature.

== Upgrade Notice ==

= 1.0.0 =
* First version released. Please provide feedback and report any bugs via the support forum.

== License ==

This plugin is released under the GNU General Public License v3.0. It is compatible with WordPress's GPL v3 licensing. More details can be found in the `license.txt` file.