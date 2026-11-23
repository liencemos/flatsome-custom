<?php
/**
 * Run via WP-CLI after seeding: wp eval-file /path/to/solana-merch/setup/check-demo.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

function signal_check( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

signal_check( 'flatsome' === get_template() && 'solana-merch-child' === get_stylesheet() && class_exists( 'WooCommerce' ), 'Expected WooCommerce, Flatsome and its FORM / EDIT child theme.' );
foreach ( array( 'header_color', 'content_color', 'footer_bottom_text' ) as $setting ) {
	signal_check( 'light' === get_theme_mod( $setting ), "Expected Flatsome light text mode for {$setting}." );
}
$logo = get_theme_mod( 'site_logo' );
signal_check( $logo && hash_file( 'sha256', get_stylesheet_directory() . '/assets/art/wordmark.png' ) === get_post_meta( $logo, '_signal_art_hash', true ), 'Expected the current light wordmark version in the media library.' );
$home = get_post( get_option( 'page_on_front' ) );
signal_check( $home && has_shortcode( $home->post_content, 'ux_products' ) && false === strpos( $home->post_content, '{{' ), 'Home must contain the seeded Flatsome product grid without unresolved placeholders.' );
signal_check( 'stacked-right' === get_theme_mod( 'product_layout' ) && 'fade_in_back' === get_theme_mod( 'product_hover' ), 'Expected the native stacked product gallery and second-image hover.' );
signal_check( has_shortcode( get_theme_mod( 'html_shop_page', '' ), 'block' ) && 'blocks' === get_post_type( get_theme_mod( 'footer_block' ) ), 'Expected Flatsome UX Blocks on the shop header and footer.' );
$cart = get_post( wc_get_page_id( 'cart' ) );
signal_check( $cart && has_shortcode( $cart->post_content, 'woocommerce_cart' ) && ! has_block( 'woocommerce/cart', $cart ), 'Expected the classic WooCommerce cart so Flatsome can refresh its header bag count.' );
if ( metadata_exists( 'post', $cart->ID, '_signal_original_cart_content' ) ) {
	signal_check( has_block( 'woocommerce/cart', get_post_meta( $cart->ID, '_signal_original_cart_content', true ) ), 'Expected the original Cart block in the conversion backup.' );
}

$products = get_posts( array(
	'post_type'      => 'product',
	'post_status'    => 'publish',
	'meta_key'       => '_signal_demo',
	'meta_value'     => 1,
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );
signal_check( 12 === count( $products ), 'Expected 12 demo products.' );

$counts = array( 't-shirts' => 0, 'hats' => 0 );
$total = 0;
foreach ( $products as $product_id ) {
	$product = wc_get_product( $product_id );
	$sku = 'FE-' . strtoupper( sanitize_title( $product->get_name() ) );
	signal_check( $sku === $product->get_sku() && $product_id === wc_get_product_id_by_sku( $sku ), "Expected the FORM / EDIT SKU {$sku} for product {$product_id}." );
	$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
	signal_check( is_array( $terms ) && 1 === count( $terms ) && isset( $counts[ $terms[0] ] ), "Invalid demo category for product {$product_id}." );
	++$counts[ $terms[0] ];
	$header = get_term_meta( $product->get_category_ids()[0], 'cat_meta', true );
	signal_check( has_shortcode( $header['cat_header'] ?? '', 'block' ), "Missing native category header for {$terms[0]}." );
	signal_check( $product->is_type( 'variable' ) && $product->get_image_id() && 2 === count( $product->get_gallery_image_ids() ), "Expected variable product {$product_id} with three catalog images." );

	$sizes = 't-shirts' === $terms[0] ? array( 'S', 'M', 'L', 'XL' ) : array( 'One size' );
	$expected = array();
	foreach ( $sizes as $size ) {
		foreach ( array( 'Ink', 'Stone' ) as $color ) {
			$expected[] = $size . '|' . $color;
		}
	}
	$actual = array();
	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		$attributes = $variation->get_attributes();
		$actual[] = ( $attributes['size'] ?? '' ) . '|' . ( $attributes['color'] ?? '' );
		signal_check( $variation->is_in_stock() && $variation->is_purchasable() && $variation->get_image_id( 'edit' ), "Variation {$variation_id} must be purchasable and have color artwork." );
	}
	sort( $actual );
	sort( $expected );
	signal_check( $actual === $expected, "Unexpected or duplicated variation for product {$product_id}." );
	$total += count( $actual );
}
signal_check( array( 't-shirts' => 6, 'hats' => 6 ) === $counts, 'Expected six tees and six hats.' );
WP_CLI::success( "Native demo structure verified: 12 variable products, {$total} purchasable variations, two categories and seeded UX Blocks." );
