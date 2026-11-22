<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'solana-merch', get_stylesheet_directory_uri() . '/assets/storefront.css', array( 'flatsome-main' ), wp_get_theme()->get( 'Version' ) );
	$heading_color = sanitize_hex_color( get_theme_mod( 'type_headings_color', '' ) );
	if ( $heading_color ) {
		wp_add_inline_style( 'solana-merch', ':root{--signal-heading:' . $heading_color . '}' );
	}
}, 100 );

add_action( 'woocommerce_single_product_summary', function () {
	$product = wc_get_product( get_the_ID() );
	if ( $product && get_post_meta( $product->get_id(), '_signal_demo', true ) ) {
		echo '<p class="signal-eyebrow signal-product-edition">● &nbsp; DROP 001 / FIELD EDITION</p>';
	}
}, 4 );
