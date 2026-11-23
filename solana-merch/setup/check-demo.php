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
signal_check( has_shortcode( $home->post_content, 'form_edit_newsletter' ) && false !== strpos( $home->post_content, 'edit-process' ), 'Expected the editorial process and newsletter modules.' );
signal_check( get_theme_mod( 'product_sticky_cart' ) && 'bottom' === get_theme_mod( 'product_upsell' ), 'Expected native sticky cart and below-description upsells.' );
signal_check( class_exists( 'YITH_WCWL' ) && in_array( 'wishlist', get_theme_mod( 'header_elements_right', array() ), true ), 'Expected YITH Wishlist and its native Flatsome header element.' );
signal_check( (int) get_option( 'yith_wcwl_wishlist_page_id' ) > 0 && get_post( (int) get_option( 'yith_wcwl_wishlist_page_id' ) ), 'Expected a configured YITH Wishlist page.' );
signal_check( get_theme_mod( 'live_search' ) && ! get_theme_mod( 'disable_quick_view' ) && get_theme_mod( 'product_buy_now' ), 'Expected native live search, Quick View, and Buy Now.' );
$coupon = new WC_Coupon( 'first10' );
signal_check( 'percent' === $coupon->get_discount_type() && 10.0 === (float) $coupon->get_amount() && false !== strpos( get_theme_mod( 'topbar_left' ), 'FIRST10' ), 'Expected working FIRST10 top bar promotion.' );
$shipping = new WC_Shipping_Zone( (int) get_option( 'form_edit_demo_shipping_zone' ) );
$free = array_filter( $shipping->get_shipping_methods( true ), static function ( $method ) {
	return 'free_shipping' === $method->id && 'min_amount' === $method->requires && 75.0 === (float) $method->min_amount && 'no' === $method->ignore_discounts;
} );
signal_check( 1 === count( $free ) && 'yes' === ( get_option( 'woocommerce_cod_settings' )['enabled'] ?? '' ), 'Expected $75 after discounts for free shipping and demo COD.' );
signal_check( 'Jan 7' === form_edit_delivery_date( new DateTimeImmutable( '2026-01-02T12:00:00Z' ), 3 ), 'Delivery estimate must skip weekends.' );
foreach ( array( 'woocommerce_layered_nav', 'woocommerce_price_filter' ) as $widget ) {
	signal_check( is_active_widget( false, false, $widget ), "Missing WooCommerce catalog widget {$widget}." );
}
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
$low_stock = 0;
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
	$sample_reviews = get_comments( array( 'post_id' => $product_id, 'type' => 'review', 'meta_key' => '_form_edit_demo_review', 'count' => true ) );
	signal_check( 1 === (int) $sample_reviews && $product->get_average_rating() > 0, "Missing or duplicate sample rating for {$product_id}." );

	$sizes = 't-shirts' === $terms[0] ? array( 'S', 'M', 'L', 'XL' ) : array( 'One size' );
	$filter_sizes = wp_get_post_terms( $product_id, 'pa_size', array( 'fields' => 'names' ) );
	sort( $filter_sizes );
	$expected_sizes = $sizes;
	sort( $expected_sizes );
	signal_check( $expected_sizes === $filter_sizes && array( 'Ink', 'Stone' ) === wp_get_post_terms( $product_id, 'pa_color', array( 'fields' => 'names' ) ), "Missing native filter terms for {$product_id}." );
	signal_check( 1 === count( $product->get_upsell_ids() ) && has_shortcode( get_post_meta( $product_id, '_form_edit_details', true ), 'lightbox' ), "Missing paired product or fit guide for {$product_id}." );
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
		if ( $variation->managing_stock() && 2 === $variation->get_stock_quantity() ) {
			++$low_stock;
		}
	}
	sort( $actual );
	sort( $expected );
	signal_check( $actual === $expected, "Unexpected or duplicated variation for product {$product_id}." );
	$total += count( $actual );
}
signal_check( array( 't-shirts' => 6, 'hats' => 6 ) === $counts, 'Expected six tees and six hats.' );
signal_check( 4 === $low_stock, 'Expected four low-stock demo variations.' );
WP_CLI::success( "Native demo structure verified: 12 variable products, {$total} purchasable variations, two categories and seeded UX Blocks." );
