<?php
/**
 * Smoke check for the seeded Comfa demo. Run with: wp eval-file check-demo.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cf_catalog  = json_decode( (string) file_get_contents( __DIR__ . '/catalog.json' ), true );
$cf_failures = array();
$cf_expect   = function ( $condition, $message ) use ( &$cf_failures ) {
	if ( ! $condition ) {
		$cf_failures[] = $message;
	}
};

$cf_expect( 'comfa-child' === get_stylesheet() && 'flatsome' === get_template(), 'Comfa Child (parent Flatsome) is not the active theme.' );
$cf_variations = 0;
foreach ( $cf_catalog['products'] as $cf_item ) {
	$cf_product = wc_get_product( wc_get_product_id_by_sku( 'CF-' . strtoupper( $cf_item['slug'] ) ) );
	if ( ! $cf_product instanceof WC_Product_Variable ) {
		$cf_failures[] = 'Missing variable product: ' . $cf_item['slug'];
		continue;
	}
	$cf_expected = count( $cf_item['colors'] ) * count( $cf_catalog['pack_sets'][ $cf_item['packs'] ] );
	$cf_children = $cf_product->get_children();
	$cf_variations += count( $cf_children );
	$cf_expect( count( $cf_children ) === $cf_expected, $cf_item['slug'] . ': expected ' . $cf_expected . ' variations, found ' . count( $cf_children ) );
	$cf_expect( $cf_product->get_image_id() && count( $cf_product->get_gallery_image_ids() ) >= 2, $cf_item['slug'] . ': images missing.' );
	$cf_default = function_exists( 'cf_default_variation' ) ? cf_default_variation( $cf_product ) : null;
	$cf_expect( $cf_default && $cf_default->is_purchasable() && $cf_default->is_in_stock(), $cf_item['slug'] . ': default variation is not purchasable.' );
	// Cards show the default variation's price; WooCommerce sorts and filters by the lowest one.
	$cf_expect( $cf_default && (float) $cf_default->get_price() === (float) $cf_product->get_variation_price( 'min' ), $cf_item['slug'] . ': default variation is not the lowest-priced one.' );
	foreach ( $cf_children as $cf_child_id ) {
		$cf_child = wc_get_product( $cf_child_id );
		$cf_pack  = get_term_by( 'slug', $cf_child->get_attributes()['pa_pack'], 'pa_pack' );
		$cf_count = cf_pack_count( $cf_pack );
		$cf_expect( abs( (float) $cf_child->get_regular_price() - $cf_item['price'] * $cf_count ) < 0.01, $cf_child->get_sku() . ': regular price does not match pack size.' );
		$cf_expect( $cf_count > 1 ? $cf_child->is_on_sale() : ! $cf_child->is_on_sale(), $cf_child->get_sku() . ': pack saving is wrong.' );
	}
}
foreach ( array( 'footer', 'mega-tees', 'mega-caps', 'pdp-story', 'page-404' ) as $cf_slug ) {
	$cf_block = get_page_by_path( $cf_slug, OBJECT, 'blocks' );
	$cf_expect( $cf_block && 'publish' === $cf_block->post_status && false === strpos( $cf_block->post_content, '{{' ), 'UX Block missing or has unresolved placeholders: ' . $cf_slug );
}
foreach ( array( 'home', 'mission', 'help', 'shipping-returns', 'size-guide', 'contact', 'privacy-policy', 'terms-of-service', 'return-refund-policy', 'dmca', 'ip-claim' ) as $cf_slug ) {
	$cf_page = get_page_by_path( $cf_slug );
	$cf_expect( $cf_page && 'publish' === $cf_page->post_status && false === strpos( $cf_page->post_content, '{{' ), 'Page missing or has unresolved placeholders: ' . $cf_slug );
}
$cf_expect( (int) get_option( 'page_on_front' ) === (int) get_page_by_path( 'home' )->ID, 'Home is not the front page.' );
$cf_expect( (int) get_theme_mod( 'footer_block' ) === (int) get_page_by_path( 'footer', OBJECT, 'blocks' )->ID, 'Footer UX Block is not assigned.' );
$cf_expect( (int) get_theme_mod( '404_block' ) === (int) get_page_by_path( 'page-404', OBJECT, 'blocks' )->ID, '404 UX Block is not assigned.' );
$cf_expect( (bool) get_theme_mod( 'swatches' ) && wc_get_attribute( get_theme_mod( 'swatches_box_attribute' ) ), 'Flatsome swatches are not configured.' );
foreach ( array( 'pa_pack' => 'ux_label', 'pa_color' => 'ux_color', 'pa_size' => 'ux_label' ) as $cf_taxonomy => $cf_type ) {
	$cf_attribute = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $cf_taxonomy ) );
	$cf_expect( $cf_attribute && $cf_type === $cf_attribute->type, $cf_taxonomy . ' is not a ' . $cf_type . ' swatch attribute.' );
}
$cf_expect( (bool) wc_get_coupon_id_by_code( 'HELLO10' ), 'Coupon HELLO10 is missing.' );
$cf_expect( false !== strpos( get_post_field( 'post_content', wc_get_page_id( 'cart' ) ), '[woocommerce_cart]' ), 'Cart page does not use the classic shortcode.' );

if ( $cf_failures ) {
	foreach ( $cf_failures as $cf_failure ) {
		WP_CLI::warning( $cf_failure );
	}
	WP_CLI::error( count( $cf_failures ) . ' check(s) failed.' );
}
WP_CLI::success( sprintf( 'Comfa demo verified: %d products, %d variations, 5 UX Blocks, 11 pages, swatches, coupon and classic cart.', count( $cf_catalog['products'] ), $cf_variations ) );
