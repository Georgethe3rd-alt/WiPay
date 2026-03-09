=== WiPay WooCommerce Payment Gateway ===
Contributors: wipaycaribbean
Tags: woocommerce, payment gateway, wipay, caribbean, credit card, trinidad, jamaica, barbados
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 5.0
WC tested up to: 8.8

Accept credit card payments across the Caribbean via WiPay's secure hosted checkout in your WooCommerce store.

== Description ==

**WiPay WooCommerce Payment Gateway** enables Caribbean merchants to accept credit card payments through WiPay's secure, PCI-compliant hosted checkout page.

= Features =

* **Multi-Country Support** — Trinidad & Tobago, Jamaica, Barbados, Saint Lucia, Grenada, Guyana
* **Automatic Currency Mapping** — Select your country, currency auto-populates
* **Sandbox & Live Modes** — Test thoroughly before going live
* **Flexible Fee Structure** — Customer pays or merchant absorbs processing fees
* **Secure Hosted Checkout** — Customers pay on WiPay's PCI-compliant page
* **Webhook Verification** — Server-to-server payment confirmation for reliability
* **Detailed Logging** — Full debug logging via WooCommerce's built-in logger
* **Order Notes** — Every payment event recorded
* **Developer Friendly** — Hooks, filters, and clean architecture
* **HPOS Compatible** — Works with High-Performance Order Storage
* **Translation Ready** — Full i18n support

= Supported Countries =

* Trinidad & Tobago (TTD)
* Jamaica (JMD)
* Barbados (BBD)
* Saint Lucia (XCD)
* Grenada (XCD)
* Guyana (GYD)
* USD accepted across all countries

= How It Works =

1. Customer selects WiPay at checkout
2. Redirected to WiPay's secure payment page
3. Enters card details on WiPay's PCI-compliant page
4. Redirected back to your store after payment
5. Order status updates automatically

Your store never handles raw card data.

== Installation ==

1. Upload the `wipay-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce → Settings → Payments → WiPay Caribbean
4. Enter your WiPay Account Number
5. Select your country and environment (Sandbox for testing)
6. Enable the gateway and save

== Frequently Asked Questions ==

= Where do I get my WiPay Account Number? =

Log in to your WiPay merchant dashboard at [wipaycaribbean.com](https://wipaycaribbean.com) and find your account number under Settings → API.

= Is this plugin PCI compliant? =

Yes. Card details are entered only on WiPay's hosted payment page. Your store never processes or stores card data.

= Can I accept USD payments? =

Yes. You can manually override the currency field to "USD" regardless of which country is selected.

= What happens if the customer closes their browser during payment? =

The server-to-server webhook ensures the order is updated even if the customer doesn't return to your site.

= Does this support recurring payments / subscriptions? =

Not currently. The plugin handles one-time payments only.

= How do I process refunds? =

Refunds must be processed manually through your WiPay merchant dashboard. Automated refunds via API are not yet supported by WiPay.

= Can I use this alongside other payment methods? =

Absolutely. WooCommerce supports multiple active payment gateways simultaneously.

== Screenshots ==

1. WiPay payment option at checkout
2. Admin settings page with country and environment configuration
3. WiPay hosted payment page (customer experience)
4. Order notes showing payment confirmation with transaction ID
5. WooCommerce debug logs for troubleshooting

== Changelog ==

= 2.0.0 =
* Complete rewrite of the WiPay WooCommerce payment gateway
* Multi-country support (TT, JM, BB, LC, GD, GY)
* Automatic currency mapping
* Sandbox and Live environment toggle
* Server-to-server webhook handler
* Comprehensive logging and order notes
* Developer hooks and filters
* HPOS compatibility
* Full i18n support
* User Guide and Developer Guide documentation

== Upgrade Notices ==

= 2.0.0 =
This is a complete rewrite. If upgrading from v1.x, deactivate the old plugin, install v2.0.0 fresh, and reconfigure your settings. Your WiPay Account Number and previous orders are not affected.
