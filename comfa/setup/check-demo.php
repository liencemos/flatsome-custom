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
// Print-to-order designs: exactly one variation per product (style) x colour x size the style comes in, priced style + size surcharge.
global $wpdb;
$cf_design_variations = 0;
foreach ( $cf_catalog['designs'] as $cf_item ) {
	$cf_product = wc_get_product( wc_get_product_id_by_sku( 'CF-' . strtoupper( $cf_item['slug'] ) ) );
	if ( ! $cf_product instanceof WC_Product_Variable ) {
		$cf_failures[] = 'Missing variable product: ' . $cf_item['slug'];
		continue;
	}
	$cf_expected = array();
	$cf_styles   = 'all' === $cf_item['styles'] ? array_keys( $cf_catalog['styles'] ) : $cf_item['styles'];
	foreach ( $cf_styles as $cf_style_slug ) {
		$cf_style  = $cf_catalog['styles'][ $cf_style_slug ];
		$cf_colors = isset( $cf_item['colors'] ) ? array_intersect( $cf_style['colors'], $cf_item['colors'] ) : $cf_style['colors'];
		foreach ( $cf_colors as $cf_color ) {
			foreach ( $cf_style['sizes'] as $cf_size ) {
				$cf_extra = isset( $cf_catalog['size_extra'][ $cf_size ] ) ? $cf_catalog['size_extra'][ $cf_size ] : 0;
				$cf_expected[ $cf_style_slug . '/' . $cf_color . '/' . sanitize_title( $cf_size ) ] = $cf_style['price'] + $cf_extra;
			}
		}
	}
	$cf_found = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, MAX( CASE WHEN m.meta_key = 'attribute_pa_style' THEN m.meta_value END ) AS style, MAX( CASE WHEN m.meta_key = 'attribute_pa_color' THEN m.meta_value END ) AS color, MAX( CASE WHEN m.meta_key = 'attribute_pa_size' THEN m.meta_value END ) AS size, MAX( CASE WHEN m.meta_key = '_price' THEN m.meta_value END ) AS price, MAX( CASE WHEN m.meta_key = '_thumbnail_id' THEN m.meta_value END ) AS image, MAX( CASE WHEN m.meta_key = '_stock_status' THEN m.meta_value END ) AS stock FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_parent = %d AND p.post_type = 'product_variation' AND p.post_status = 'publish' GROUP BY p.ID", $cf_product->get_id() ) ) as $cf_row ) {
		$cf_key = $cf_row->style . '/' . $cf_row->color . '/' . $cf_row->size;
		$cf_expect( ! isset( $cf_found[ $cf_key ] ), $cf_item['slug'] . ': duplicate variation ' . $cf_key );
		$cf_found[ $cf_key ] = (float) $cf_row->price;
		$cf_expect( (int) $cf_row->image > 0 && 'instock' === $cf_row->stock, $cf_item['slug'] . ': variation ' . $cf_key . ' has no image or is out of stock.' );
	}
	$cf_design_variations += count( $cf_found );
	$cf_missing = array_diff_key( $cf_expected, $cf_found );
	$cf_extra   = array_diff_key( $cf_found, $cf_expected );
	$cf_expect( ! $cf_missing, $cf_item['slug'] . ': ' . count( $cf_missing ) . ' variations missing, e.g. ' . (string) key( $cf_missing ) );
	$cf_expect( ! $cf_extra, $cf_item['slug'] . ': ' . count( $cf_extra ) . ' unexpected variations, e.g. ' . (string) key( $cf_extra ) );
	foreach ( array_intersect_key( $cf_expected, $cf_found ) as $cf_key => $cf_price ) {
		$cf_expect( abs( $cf_found[ $cf_key ] - $cf_price ) < 0.005, $cf_item['slug'] . ': ' . $cf_key . ' costs ' . $cf_found[ $cf_key ] . ', expected ' . $cf_price );
	}
	// The pickers and the card read this map; it must list each style with exactly its own colours and sizes.
	$cf_map = function_exists( 'cf_style_map' ) ? cf_style_map( $cf_product ) : array();
	$cf_expect( array_keys( $cf_map ) === array_values( $cf_styles ), $cf_item['slug'] . ': style map does not list the expected products.' );
	foreach ( $cf_map as $cf_style_slug => $cf_style ) {
		$cf_spec   = $cf_catalog['styles'][ $cf_style_slug ];
		$cf_colors = isset( $cf_item['colors'] ) ? array_values( array_intersect( $cf_spec['colors'], $cf_item['colors'] ) ) : $cf_spec['colors'];
		$cf_expect( $cf_style['colors'] === $cf_colors && $cf_style['sizes'] === array_map( 'sanitize_title', $cf_spec['sizes'] ) && abs( $cf_style['price'] - $cf_spec['price'] ) < 0.005, $cf_item['slug'] . ': style map is wrong for ' . $cf_style_slug );
	}
	$cf_default = $cf_product->get_default_attributes();
	$cf_expect( isset( $cf_default['pa_style'], $cf_default['pa_color'], $cf_map[ $cf_default['pa_style'] ] ) && in_array( $cf_default['pa_color'], $cf_map[ $cf_default['pa_style'] ]['colors'], true ), $cf_item['slug'] . ': default product and colour are not a real combination.' );
	$cf_expect( $cf_product->get_image_id() && $cf_product->get_gallery_image_ids(), $cf_item['slug'] . ': images missing.' );
	$cf_expect( abs( (float) $cf_product->get_variation_price( 'min' ) - min( $cf_expected ) ) < 0.005 && abs( (float) $cf_product->get_variation_price( 'max' ) - max( $cf_expected ) ) < 0.005, $cf_item['slug'] . ': price range is wrong.' );
}
foreach ( $cf_catalog['styles'] as $cf_slug => $cf_style ) {
	$cf_term = get_term_by( 'slug', $cf_slug, 'pa_style' );
	$cf_expect( $cf_term && $cf_term->name === $cf_style['name'] && wp_attachment_is_image( (int) get_term_meta( $cf_term->term_id, 'ux_image', true ) ), 'Product term missing or has no thumbnail: ' . $cf_slug );
	foreach ( $cf_style['sizes'] as $cf_size ) {
		$cf_expect( (bool) get_term_by( 'name', $cf_size, 'pa_size' ), 'Size term missing: ' . $cf_size );
	}
	foreach ( $cf_style['colors'] as $cf_color ) {
		$cf_term = get_term_by( 'slug', $cf_color, 'pa_color' );
		$cf_expect( $cf_term && '' !== (string) get_term_meta( $cf_term->term_id, 'ux_color', true ), 'Colour term missing or has no swatch: ' . $cf_color );
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
foreach ( array( 'pa_pack' => 'ux_label', 'pa_color' => 'ux_color', 'pa_size' => 'ux_label', 'pa_style' => 'ux_image' ) as $cf_taxonomy => $cf_type ) {
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
WP_CLI::success( sprintf( 'Comfa demo verified: %d products with %d variations, %d print-to-order designs with %d variations, 5 UX Blocks, 11 pages, swatches, coupon and classic cart.', count( $cf_catalog['products'] ), $cf_variations, count( $cf_catalog['designs'] ), $cf_design_variations ) );
