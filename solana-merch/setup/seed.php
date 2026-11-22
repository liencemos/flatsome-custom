<?php
/**
 * Run via WP-CLI on a demo site: wp eval-file /path/to/solana-merch/setup/seed.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) || 'flatsome' !== get_template() || 'solana-merch-child' !== get_stylesheet() ) {
	WP_CLI::error( 'Activate WooCommerce and the Signal Supply Flatsome child theme before seeding.' );
}

if ( ! get_option( 'permalink_structure' ) ) {
	WP_CLI::error( 'Set a non-plain permalink structure in Settings → Permalinks before seeding.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function signal_seed_result( $result ) {
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	return $result;
}

function signal_seed_existing_post( $type, $slug ) {
	$post = get_page_by_path( $slug, OBJECT, $type );
	if ( $post && ! get_post_meta( $post->ID, '_signal_demo', true ) ) {
		WP_CLI::error( "The {$type} slug {$slug} already belongs to non-demo content." );
	}
	return $post ? $post->ID : 0;
}

function signal_seed_post( $type, $slug, $title, $content ) {
	$id = signal_seed_result( wp_insert_post( array(
		'ID'           => signal_seed_existing_post( $type, $slug ),
		'post_type'    => $type,
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => wp_slash( $content ),
		'post_status'  => 'publish',
		'comment_status' => 'closed',
		'meta_input'   => array( '_signal_demo' => 1 ),
	), true ) );
	return $id;
}

function signal_seed_media( $file, $alt ) {
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'meta_key'       => '_signal_art',
		'meta_value'     => $file,
		'fields'         => 'ids',
		'posts_per_page' => 1,
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}
	$temp = wp_tempnam( $file );
	if ( ! $temp || ! copy( get_stylesheet_directory() . '/assets/art/' . $file, $temp ) ) {
		WP_CLI::error( "Could not stage {$file} for media import." );
	}
	$id = media_handle_sideload( array( 'name' => 'signal-' . $file, 'tmp_name' => $temp ), 0, $alt );
	if ( is_wp_error( $id ) && file_exists( $temp ) ) {
		unlink( $temp );
	}
	signal_seed_result( $id );
	update_post_meta( $id, '_signal_art', $file );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return $id;
}

function signal_seed_existing_category( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! get_term_meta( $term->term_id, '_signal_demo', true ) ) {
		WP_CLI::error( "The product category {$slug} already belongs to non-demo content." );
	}
	return $term;
}

function signal_seed_category( $slug, $name, $image_id ) {
	$term = signal_seed_existing_category( $slug );
	if ( ! $term ) {
		$term = signal_seed_result( wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) ) );
		$id   = (int) $term['term_id'];
		update_term_meta( $id, '_signal_demo', 1 );
	} else {
		$id = $term->term_id;
	}
	update_term_meta( $id, 'thumbnail_id', $image_id );
	return $id;
}

function signal_seed_attribute( $name, $values ) {
	$attribute = new WC_Product_Attribute();
	$attribute->set_name( $name );
	$attribute->set_options( $values );
	$attribute->set_position( 'Size' === $name ? 0 : 1 );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	return $attribute;
}

function signal_seed_product( $category, $hat, $record, $index ) {
	list( $slug, $title, $price, $intro ) = $record;
	$sku = 'SIG-' . strtoupper( $slug );
	$id  = wc_get_product_id_by_sku( $sku );
	if ( $id && ! get_post_meta( $id, '_signal_demo', true ) ) {
		WP_CLI::error( "SKU {$sku} already belongs to non-demo content." );
	}

	$ink    = signal_seed_media( $slug . '-ink.webp', $title . ' in Ink, front view' );
	$navy   = signal_seed_media( $slug . '-navy.webp', $title . ' in Navy, front view' );
	$detail = signal_seed_media( $slug . '-detail.webp', $title . ' print detail' );
	$sizes  = $hat ? array( 'One size' ) : array( 'S', 'M', 'L', 'XL' );
	$colors = array( 'Ink', 'Navy' );
	$specs  = $hat
		? '<ul><li>Six-panel cotton twill</li><li>Curved brim and adjustable strap</li><li>Front embroidery in Gold</li></ul>'
		: '<ul><li>Heavyweight 100% cotton jersey</li><li>Relaxed unisex fit with a ribbed crew neck</li><li>Front screen print in Gold</li></ul>';
	$care   = $hat
		? 'Spot clean with a damp cloth; air dry.'
		: 'Wash cold, inside out, with like colors. Hang dry; do not iron the print.';

	$product = new WC_Product_Variable( $id );
	$product->set_name( $title );
	$product->set_slug( $slug );
	$product->set_sku( $sku );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_featured( $index < 2 );
	$product->set_menu_order( $index * 2 + (int) $hat );
	$product->set_category_ids( array( $category ) );
	$product->set_short_description( $intro );
	$product->set_description( '<p>' . esc_html( $intro ) . '</p><h3>Field specs</h3>' . $specs . '<h3>Care</h3><p>' . esc_html( $care ) . '</p>' );
	$product->set_image_id( $ink );
	$product->set_gallery_image_ids( array( $detail, $navy ) );
	$product->set_attributes( array( signal_seed_attribute( 'Size', $sizes ), signal_seed_attribute( 'Color', $colors ) ) );
	$product->update_meta_data( '_signal_demo', 1 );
	$id = $product->save();

	$existing = array();
	foreach ( $product->get_children() as $child ) {
		$variation = wc_get_product( $child );
		if ( $variation && $variation->get_meta( '_signal_demo' ) ) {
			$attributes = $variation->get_attributes();
			$existing[ ( $attributes['size'] ?? '' ) . '|' . ( $attributes['color'] ?? '' ) ] = $variation;
		}
	}

	foreach ( $sizes as $size ) {
		foreach ( $colors as $color ) {
			$key       = $size . '|' . $color;
			$variation = $existing[ $key ] ?? new WC_Product_Variation();
			$variation->set_parent_id( $id );
			$variation->set_status( 'publish' );
			$variation->set_attributes( array( 'size' => $size, 'color' => $color ) );
			$variation->set_regular_price( $price );
			$variation->set_stock_status( 'instock' );
			$variation->set_image_id( 'Ink' === $color ? $ink : $navy );
			$variation->update_meta_data( '_signal_demo', 1 );
			$variation->save();
		}
	}
	WC_Product_Variable::sync( $id );
	return $id;
}

function signal_seed_render( $file, $values ) {
	$content = file_get_contents( dirname( __DIR__ ) . '/ux-builder/' . $file . '.txt' );
	if ( false === $content ) {
		WP_CLI::error( "Could not read UX Builder export {$file}." );
	}
	$tokens = array();
	foreach ( $values as $key => $value ) {
		$tokens[ '{{' . $key . '}}' ] = $value;
	}
	$content = strtr( $content, $tokens );
	if ( preg_match( '/\{\{\w+\}\}/', $content, $unknown ) ) {
		WP_CLI::error( "Unresolved placeholder {$unknown[0]} in {$file}." );
	}
	return $content;
}

function signal_seed_menu( $items ) {
	$name = 'Signal Supply Demo Navigation';
	$menu = wp_get_nav_menu_object( $name );
	$id   = $menu ? (int) $menu->term_id : signal_seed_result( wp_create_nav_menu( $name ) );
	$existing = array();
	foreach ( wp_get_nav_menu_items( $id ) ?: array() as $entry ) {
		$existing[ $entry->object . ':' . $entry->object_id ] = $entry->ID;
	}
	foreach ( $items as $position => list( $label, $object, $object_id ) ) {
		signal_seed_result( wp_update_nav_menu_item( $id, $existing[ $object . ':' . $object_id ] ?? 0, array(
			'menu-item-title'     => $label,
			'menu-item-object'    => $object,
			'menu-item-object-id' => $object_id,
			'menu-item-type'      => 'page' === $object ? 'post_type' : 'taxonomy',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $position + 1,
		) ) );
	}
	$locations                   = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary']        = $id;
	$locations['primary_mobile'] = $id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

$tees = array(
	array( 'carrier-wave', 'Carrier Wave Tee', '34.00', 'A pulse trace in high-contrast Gold, printed on heavyweight cotton. The anchor piece of Drop 001.' ),
	array( 'night-grid', 'Night Grid Tee', '32.00', 'A gridded coordinate mark for navigating after dark, printed on a relaxed crew neck.' ),
	array( 'orbital-index', 'Orbital Index Tee', '36.00', 'A tilted orbit and a single tracking point, screen printed in Gold.' ),
	array( 'relay-station', 'Relay Station Tee', '34.00', 'An antenna emblem from an imaginary relay station, drawn in a single line.' ),
	array( 'static-field', 'Static Field Tee', '32.00', 'Four oscillating lines turn a moment of interference into a front graphic.' ),
	array( 'after-hours', 'After Hours Tee', '36.00', 'A clean radial mark for late trains, long sets, and the hours in between.' ),
);
$hats = array(
	array( 'receiver-cap', 'Receiver Cap', '28.00', 'Our signature pulse trace in Gold thread on an adjustable six-panel cap.' ),
	array( 'sideband-cap', 'Sideband Cap', '28.00', 'Gold grid embroidery on a cotton twill cap with a curved brim.' ),
	array( 'orbit-cap', 'Orbit Cap', '30.00', 'A compact orbital diagram embroidered on the front of an everyday cap.' ),
	array( 'uplink-cap', 'Uplink Cap', '30.00', 'A small antenna mark embroidered in Gold, finished with an adjustable back strap.' ),
	array( 'field-cap', 'Static Field Cap', '28.00', 'An embroidered wave pattern with a low-profile fit for everyday wear.' ),
	array( 'nightshift-cap', 'Nightshift Cap', '30.00', 'A radial Gold mark for late starts and early finishes.' ),
);

foreach ( array( 't-shirts', 'hats' ) as $slug ) {
	signal_seed_existing_category( $slug );
}

WC_Install::create_pages();
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'blogname', 'Signal Supply' );
update_option( 'blogdescription', 'Independent graphic tees and caps' );
$tees_image = signal_seed_media( 'collection-tees.webp', 'Orbital Index Tee, graphic tees collection' );
$hats_image = signal_seed_media( 'collection-hats.webp', 'Receiver Cap, field caps collection' );
$tees_id    = signal_seed_category( 't-shirts', 'T-shirts', $tees_image );
$hats_id    = signal_seed_category( 'hats', 'Hats', $hats_image );
$products   = array();
foreach ( array( array( $tees, $tees_id, false ), array( $hats, $hats_id, true ) ) as list( $records, $category, $hat ) ) {
	foreach ( $records as $index => $record ) {
		$products[ $record[0] ] = signal_seed_product( $category, $hat, $record, $index );
	}
}

$product_url = get_permalink( $products['carrier-wave'] );
$links = array(
	'shop_url'    => esc_url( wc_get_page_permalink( 'shop' ) ),
	'tees_url'    => esc_url( signal_seed_result( get_term_link( $tees_id, 'product_cat' ) ) ),
	'hats_url'    => esc_url( signal_seed_result( get_term_link( $hats_id, 'product_cat' ) ) ),
	'product_url' => esc_url( $product_url ),
	'account_url' => esc_url( wc_get_page_permalink( 'myaccount' ) ),
	'cart_url'    => esc_url( wc_get_cart_url() ),
);
$home = signal_seed_post( 'page', 'signal-home', 'Home', signal_seed_render( 'homepage', $links + array(
	'hero_image'   => signal_seed_media( 'hero.webp', 'Carrier Wave Tee on a gold technical grid' ),
	'tees_image'   => $tees_image,
	'hats_image'   => $hats_image,
	'featured_ids' => implode( ',', array_intersect_key( $products, array_flip( array( 'carrier-wave', 'night-grid', 'receiver-cap', 'sideband-cap' ) ) ) ),
) ) );
update_post_meta( $home, '_wp_page_template', 'page-blank.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );
$footer = signal_seed_post( 'blocks', 'signal-footer', 'Signal Supply Footer', signal_seed_render( 'footer', $links ) );

foreach ( array(
	$tees_id => array( '01 / DROP_001', 'Graphic tees', 'Heavyweight cotton, clean lines, and prints made for the night shift.', 'Explore field caps →', $links['hats_url'] ),
	$hats_id => array( '02 / DROP_001', 'Field caps', 'Six-panel caps with embroidered signals and an adjustable fit.', 'Explore graphic tees →', $links['tees_url'] ),
	0        => array( '00 / DROP_001', 'The collection', 'Two collections. Twelve designs. Find your frequency.', 'Explore graphic tees →', $links['tees_url'] ),
) as $term_id => list( $index, $title, $intro, $cta, $url ) ) {
	$header = signal_seed_render( 'archive-header', array(
		'archive_index' => $index,
		'archive_title' => $title,
		'archive_intro' => $intro,
		'archive_cta'   => $cta,
		'archive_url'   => $url,
	) );
	$block_id = signal_seed_post( 'blocks', 'signal-archive-' . sanitize_title( $title ), 'Signal Supply — ' . $title, $header );
	$block = '[block id="' . $block_id . '"]';
	if ( $term_id ) {
		$meta = (array) get_term_meta( $term_id, 'cat_meta', true );
		$meta['cat_header'] = $block;
		update_term_meta( $term_id, 'cat_meta', $meta );
	} else {
		set_theme_mod( 'html_shop_page', $block );
	}
}

signal_seed_menu( array(
	array( 'Home', 'page', $home ),
	array( 'Shop', 'page', wc_get_page_id( 'shop' ) ),
	array( 'T-shirts', 'product_cat', $tees_id ),
	array( 'Hats', 'product_cat', $hats_id ),
) );

$logo = signal_seed_media( 'wordmark.png', 'Signal Supply' );
$mods = array(
	'site_logo'                      => $logo,
	'site_logo_dark'                 => $logo,
	'logo_width'                     => 168,
	'logo_position'                  => 'left',
	'logo_position_mobile'           => 'center',
	'header_bg'                      => '#000814',
	'header_color'                   => 'dark',
	'header_height'                  => 72,
	'header_elements_left'           => array( 'nav' ),
	'header_elements_right'          => array( 'search', 'account', 'cart' ),
	'header_mobile_elements_left'    => array( 'menu-icon' ),
	'header_mobile_elements_right'   => array( 'cart' ),
	'mobile_sidebar'                 => array( 'search-form', 'nav', 'account' ),
	'header_cart_style'              => 'off-canvas',
	'header_cart_total'              => 0,
	'header_cart_title'              => 0,
	'header_cart_qty'                => 1,
	'header_account_title'           => 0,
	'cart_icon'                      => 'bag',
	'topbar_show'                    => 0,
	'header_sticky'                  => 1,
	'body_layout'                    => 'full-width',
	'site_width'                     => 1280,
	'body_bg'                        => '#000814',
	'content_bg'                     => '#000814',
	'content_color'                  => 'dark',
	'color_primary'                  => '#FFC300',
	'color_secondary'                => '#FFD60A',
	'color_texts'                    => '#FFD60A',
	'color_links'                    => '#FFD60A',
	'color_links_hover'              => '#FFC300',
	'type_headings_color'            => '#FFD60A',
	'color_regular_price'            => '#FFD60A',
	'color_divider'                  => '#003566',
	'type_headings'                  => array( 'font-family' => 'Space Grotesk', 'variant' => '700' ),
	'type_texts'                     => array( 'font-family' => 'Manrope', 'variant' => '400' ),
	'type_nav'                       => array( 'font-family' => 'Manrope', 'variant' => '700' ),
	'type_alt'                       => array( 'font-family' => 'IBM Plex Mono', 'variant' => '500' ),
	'google_fonts_cdn'               => 1,
	'category_title_style'           => '',
	'category_show_title'            => 0,
	'category_sidebar'               => 'none',
	'category_row_count'             => 4,
	'category_row_count_tablet'      => 3,
	'category_row_count_mobile'      => 2,
	'products_pr_page'               => 12,
	'product_box_category'           => 1,
	'disable_quick_view'             => 0,
	'product_next_prev_nav'          => 0,
	'product_layout'                 => 'no-sidebar',
	'product_image_width'            => 6,
	'footer_block'                   => $footer,
	'footer_bottom_color'            => '#000814',
	'footer_bottom_text'             => 'dark',
	'footer_left_text'               => '© ' . gmdate( 'Y' ) . ' SIGNAL SUPPLY / STOREFRONT DEMO',
	'payment_icons_placement'        => array(),
);
foreach ( $mods as $setting => $value ) {
	set_theme_mod( $setting, $value );
}

WP_CLI::success( sprintf( 'Signal Supply: %d variable products seeded.', count( $products ) ) );
WP_CLI::log( 'Homepage: ' . home_url( '/' ) );
WP_CLI::log( 'Category: ' . $links['tees_url'] );
WP_CLI::log( 'Product: ' . $product_url );
