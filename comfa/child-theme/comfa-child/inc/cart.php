<?php
/**
 * Bag: free-shipping progress and giving line in the mini cart and cart page.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Items a cart gives away: one per item bought, counting packs.
 */
function cf_cart_given_count() {
	$total = 0;
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		$pack = isset( $item['variation']['attribute_pa_pack'] ) ? $item['variation']['attribute_pa_pack'] : '';
		$total += (int) $item['quantity'] * ( $pack ? cf_pack_count( get_term_by( 'slug', $pack, 'pa_pack' ) ) : 1 );
	}
	return $total;
}

function cf_bag_progress() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$threshold = (float) cf_setting( 'free_shipping_threshold' );
	if ( $threshold <= 0 ) {
		return;
	}
	// Same basis WooCommerce uses for free-shipping minimums: discounted subtotal.
	$subtotal  = (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total();
	$remaining = max( 0, $threshold - $subtotal );
	$percent   = min( 100, $threshold > 0 ? $subtotal / $threshold * 100 : 100 );
	$message   = $remaining > 0
		/* translators: %s: amount left. */
		? sprintf( __( 'You are %s away from free shipping', 'comfa' ), wc_price( $remaining ) )
		: __( 'You have unlocked free shipping', 'comfa' );
	$given = cf_cart_given_count();
	echo '<div class="cf-bag-progress"><p>' . wp_kses_post( $message ) . '</p><div class="cf-bag-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) round( $percent ) ) . '"><span style="width:' . esc_attr( (string) round( $percent, 2 ) ) . '%"></span></div>';
	if ( $given ) {
		echo '<p class="cf-bag-progress__give">' . esc_html( sprintf( /* translators: %s: number of items. */ _n( 'This order gives %s item to someone in need', 'This order gives %s items to someone in need', $given, 'comfa' ), number_format_i18n( $given ) ) ) . '</p>';
	}
	echo '</div>';
}
add_action( 'woocommerce_before_mini_cart_contents', 'cf_bag_progress', 5 );
add_action( 'woocommerce_before_cart_table', 'cf_bag_progress', 5 );

add_filter(
	'woocommerce_product_add_to_cart_text',
	function ( $text, $product ) {
		return $product->is_type( 'variable' ) ? __( 'Choose Options', 'comfa' ) : $text;
	},
	10,
	2
);

add_filter(
	'gettext',
	function ( $translation, $text, $domain ) {
		if ( 'woocommerce' !== $domain ) {
			return $translation;
		}
		$map = array(
			'View cart'           => 'View Bag',
			'Cart totals'         => 'Order Summary',
			'Proceed to checkout' => 'Checkout',
			'Add to cart'         => 'Add to Bag',
		);
		return isset( $map[ $text ] ) && $translation === $text ? $map[ $text ] : $translation;
	},
	10,
	3
);
