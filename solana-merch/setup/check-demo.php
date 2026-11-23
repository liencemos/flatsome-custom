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
$us_zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'US', 'state' => 'CA', 'postcode' => '90012' ) ) );
signal_check( $shipping->get_id() && $shipping->get_id() === $us_zone->get_id(), 'Expected the demo zone to handle US shipping.' );
$free = array_filter( $shipping->get_shipping_methods( true ), static function ( $method ) {
	return 'free_shipping' === $method->id && 'min_amount' === $method->requires && 75.0 === (float) $method->min_amount && 'no' === $method->ignore_discounts;
} );
signal_check( 1 === count( $free ) && 'yes' === ( get_option( 'woocommerce_cod_settings' )['enabled'] ?? '' ), 'Expected $75 after discounts for free shipping and demo COD.' );
signal_check( 'Jan 7' === form_edit_delivery_date( new DateTimeImmutable( '2026-01-02T12:00:00Z' ), 3 ), 'Delivery estimate must skip weekends.' );
foreach ( array( 'woocommerce_layered_nav', 'woocommerce_price_filter' ) as $widget ) {
	signal_check( is_active_widget( false, false, $widget ), "Missing WooCommerce catalog widget {$widget}." );
}
signal_check( has_shortcode( get_theme_mod( 'html_shop_page', '' ), 'block' ) && 'blocks' === get_post_type( get_theme_mod( 'footer_block' ) ), 'Expected Flatsome UX Blocks on the shop header and footer.' );
$footer = get_post( get_theme_mod( 'footer_block' ) );
$locations = get_nav_menu_locations();
signal_check( ! empty( $locations['primary'] ) && $locations['primary'] === ( $locations['primary_mobile'] ?? null ), 'Expected desktop and mobile demo navigation.' );
$menu_items = wp_get_nav_menu_items( $locations['primary'] );
foreach ( array( 'about', 'lookbook', 'contact', 'faq', 'shipping-returns', 'size-guide', 'care-guide' ) as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	signal_check( $page && 'publish' === $page->post_status, "Missing published page: {$slug}." );
	signal_check( false !== strpos( $footer->post_content, esc_url( get_permalink( $page ) ) ), "Missing footer link: {$slug}." );
	$matches = array_filter( $menu_items ?: array(), static function ( $item ) use ( $page ) {
		return 'page' === $item->object && $page->ID === (int) $item->object_id;
	} );
	signal_check( 1 === count( $matches ), "Expected exactly one header menu entry: {$slug}." );
	signal_check( 'page-blank.php' === get_page_template_slug( $page ) && has_shortcode( $page->post_content, 'section' ) && false === strpos( $page->post_content, '{{' ), "Expected seeded Flatsome layout: {$slug}." );
}
foreach ( array( 'privacy-policy', 'terms-of-service', 'return-refund-policy' ) as $slug ) {
	$policy = get_page_by_path( $slug, OBJECT, array( 'page' ) );
	signal_check( $policy && 'publish' === $policy->post_status && 'page-blank.php' === get_page_template_slug( $policy ), "Missing published Flatsome policy: {$slug}." );
	signal_check( has_shortcode( $policy->post_content, 'form_edit_policy_toc' ) && false === strpos( $policy->post_content, '{{' ), "Missing policy TOC: {$slug}." );
	preg_match_all( '/href="#([^"]+)"/', $policy->post_content, $anchors );
	signal_check( count( $anchors[1] ) >= 7, "Expected policy section links: {$slug}." );
	foreach ( $anchors[1] as $anchor ) {
		signal_check( 1 === substr_count( $policy->post_content, 'id="' . $anchor . '"' ), "Expected one target for {$slug}#{$anchor}." );
	}
	if ( 'privacy-policy' !== $slug ) {
		signal_check( false !== strpos( $footer->post_content, esc_url( get_permalink( $policy ) ) ), "Missing footer policy link: {$slug}." );
	}
}
$privacy = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) );
signal_check( $privacy && 'page' === $privacy->post_type && 'publish' === $privacy->post_status, 'Expected a published Privacy Policy selected in WordPress.' );
signal_check( false !== strpos( $footer->post_content, esc_url( get_permalink( $privacy ) ) ), 'Expected the footer to link to the selected Privacy Policy.' );
$shipping_page = get_page_by_path( 'shipping-returns', OBJECT, array( 'page' ) );
signal_check( false !== strpos( $shipping_page->post_content, esc_url( get_permalink( get_page_by_path( 'return-refund-policy', OBJECT, array( 'page' ) ) ) ) ), 'Expected Shipping & Returns to link to the Return & Refund Policy.' );
$faq = get_page_by_path( 'faq' );
signal_check( has_shortcode( $faq->post_content, 'accordion' ) && 17 === substr_count( $faq->post_content, '[accordion-item ' ) && 5 === substr_count( $faq->post_content, 'class="edit-help-section"' ), 'Expected 17 native accordion questions in five FAQ categories.' );
$contact = get_page_by_path( 'contact', OBJECT, array( 'page' ) );
signal_check( has_shortcode( $contact->post_content, 'form_edit_contact' ) && shortcode_exists( 'form_edit_contact' ) && has_action( 'admin_post_nopriv_form_edit_contact' ), 'Expected the protected contact form shortcode and handler.' );
$contact_message = "Order link:\nhttps://example.com/printed%20tee";
signal_check( $contact_message === form_edit_contact_message( $contact_message ) && 'Plain text' === form_edit_contact_message( '<b>Plain text</b>' ), 'Expected plain-text contact messages to preserve URL escapes and line breaks.' );
$size_page = get_page_by_path( 'size-guide' );
$tee = get_page_by_path( 'carrier-wave', OBJECT, 'product' );
preg_match( '~<table>.*?</table>~s', $size_page->post_content, $size_table );
signal_check( $tee && ! empty( $size_table[0] ) && false !== strpos( get_post_meta( $tee->ID, '_form_edit_details', true ), $size_table[0] ), 'Expected the same tee size table on the Size Guide and product lightbox.' );
$not_found = get_post( get_theme_mod( '404_block' ) );
signal_check( $not_found && 'blocks' === $not_found->post_type && has_shortcode( $not_found->post_content, 'section' ) && false !== strpos( $not_found->post_content, 'edit-page-404' ), 'Expected Flatsome to use the demo 404 UX Block.' );
WP_CLI::log( 'Editorial demo verified: ten Flatsome pages, policy anchors and footer links, FAQ accordion, contact form, shared size table and 404 UX Block.' );
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
