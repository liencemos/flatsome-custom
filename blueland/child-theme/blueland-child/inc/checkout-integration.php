<?php
/**
 * Pureland integration with Modern Checkout for WooCommerce.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'mcwc_free_shipping_threshold', 'bl_free_shipping_threshold' );
add_filter( 'mcwc_free_shipping_eligible_subtotal', 'bl_free_shipping_eligible_subtotal' );

add_action(
	'mcwc_shipping_step_end',
	function () {
		if ( function_exists( 'mcwc_is_active' ) && mcwc_is_active() && function_exists( 'bl_render_gift_wrap_control' ) ) {
			bl_render_gift_wrap_control();
		}
	}
);

function bl_mcwc_remove_duplicate_checkout_components() {
	if ( ! function_exists( 'mcwc_is_active' ) || ! mcwc_is_active() ) {
		return;
	}
	remove_action( 'woocommerce_checkout_before_order_review_heading', 'bl_render_free_shipping_progress', 5 );
	remove_action( 'woocommerce_review_order_before_payment', 'bl_render_gift_wrap_control', 5 );
	remove_action( 'wp_footer', 'bl_render_email_popup', 9 );
}
add_action( 'wp', 'bl_mcwc_remove_duplicate_checkout_components', 30 );
add_action( 'woocommerce_checkout_update_order_review', 'bl_mcwc_remove_duplicate_checkout_components', 30 );
