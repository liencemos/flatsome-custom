=== Modern Checkout for WooCommerce ===
Contributors: pureland
Tags: woocommerce, checkout, ecommerce
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A theme-independent, three-step checkout for WooCommerce classic checkout pages.

== Description ==

Modern Checkout for WooCommerce presents the classic WooCommerce checkout in Information, Shipping, and Payment steps. It provides a responsive order summary, coupon field, optional free-shipping progress, configurable trust items, and a distraction-free page wrapper.

The plugin does not replace WooCommerce checkout processing, payment gateways, shipping calculations, or order creation. It requires WooCommerce and the classic [woocommerce_checkout] shortcode.

== Installation ==

1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin, or copy the plugin folder into wp-content/plugins/.
2. Activate Modern Checkout for WooCommerce.
3. Open WooCommerce > Settings > Modern Checkout and review the appearance and feature settings.
4. Use a page containing the classic [woocommerce_checkout] shortcode as the WooCommerce checkout page.

If the selected checkout page contains the WooCommerce Checkout block, enabling the plugin backs up the original content and replaces it with the classic shortcode. Deactivation restores that content only while the page still contains exactly the shortcode.

== Settings ==

Enable the checkout, choose a logo and colors, select a font and corner radii, customize the secure-checkout label and three trust items, and toggle the free-shipping bar, coupon field, and order notes. The free-shipping threshold can be set explicitly or detected from an enabled free-shipping method.

== Developer Hooks ==

Actions:

* mcwc_information_after_fields — after the Information address fields.
* mcwc_shipping_after_methods — after shipping methods.
* mcwc_shipping_step_end — at the end of the Shipping step.
* mcwc_payment_step_before — before the Payment section.
* mcwc_summary_before_items — before summary items.
* mcwc_summary_after_totals — after summary totals.
* mcwc_header_end — after the secure-checkout header text.

Filters:

* mcwc_is_active — enable or disable the plugin for a request.
* mcwc_trust_items — filter the summary trust items.
* mcwc_steps — filter the checkout step labels.
* mcwc_free_shipping_threshold — filter the detected/overridden threshold.
* mcwc_free_shipping_eligible_subtotal — filter the eligible cart subtotal.

Themes can override plugin checkout templates in modern-checkout-for-woocommerce/checkout/form-checkout.php and modern-checkout-for-woocommerce/checkout/review-order.php.

== Frequently Asked Questions ==

= Does this plugin process payments? =

No. Payment processing is handled by the WooCommerce gateways installed on the site.

= Can a theme override the templates? =

Yes. Place a copy in your theme's modern-checkout-for-woocommerce/checkout/ directory.

== Changelog ==

= 1.0.0 =
* Initial release.
