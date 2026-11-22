<?php
/**
 * Shared free-shipping progress component.
 */

defined( 'ABSPATH' ) || exit;

function bl_free_shipping_threshold() {
	return (float) apply_filters( 'bl_free_shipping_threshold', 50 );
}

function bl_free_shipping_eligible_subtotal() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$subtotal = 0.0;
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( $product instanceof WC_Product && $product->is_virtual() ) {
			continue;
		}
		$subtotal += isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0.0;
	}

	return max( 0.0, $subtotal );
}

function bl_free_shipping_progress_html() {
	$threshold = bl_free_shipping_threshold();
	$total     = bl_free_shipping_eligible_subtotal();
	$remaining = max( 0.0, $threshold - $total );
	$progress  = $threshold > 0 ? min( 100, ( $total / $threshold ) * 100 ) : 100;
	$message   = $remaining > 0
       ? 'You\'re ' . wp_kses_post( wc_price( $remaining ) ) . ' away from free shipping'
       : 'You\'ve unlocked free shipping!';

	return '<div class="bl-shipping-progress"><p>' . $message . '</p><div role="progressbar" aria-label="Free shipping progress" aria-valuenow="' . esc_attr( (string) round( $progress ) ) . '" aria-valuemin="0" aria-valuemax="100"><span style="width:' . esc_attr( (string) $progress ) . '%"></span></div><small>' . wp_kses_post( wc_price( 0 ) ) . '<span>Free shipping at ' . wp_kses_post( wc_price( $threshold ) ) . '</span></small></div>';
}

function bl_render_free_shipping_progress() {
	echo bl_free_shipping_progress_html();
}

add_action( 'woocommerce_before_mini_cart', 'bl_render_free_shipping_progress', 5 );
add_action( 'woocommerce_before_cart_table', 'bl_render_free_shipping_progress', 5 );
add_action( 'woocommerce_checkout_before_order_review_heading', 'bl_render_free_shipping_progress', 5 );

add_shortcode(
	'bl_free_shipping_progress',
	function () {
		return bl_free_shipping_progress_html();
	}
);

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['.bl-shipping-progress'] = bl_free_shipping_progress_html();
		return $fragments;
	}
);
