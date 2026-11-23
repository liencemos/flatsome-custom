<?php
/**
 * Run via WP-CLI on a demo site: wp eval-file /path/to/solana-merch/setup/seed.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) || 'flatsome' !== get_template() || 'solana-merch-child' !== get_stylesheet() ) {
	WP_CLI::error( 'Activate WooCommerce and the FORM / EDIT Flatsome child theme before seeding.' );
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
	$source = get_stylesheet_directory() . '/assets/art/' . $file;
	$hash   = is_readable( $source ) ? hash_file( 'sha256', $source ) : false;
	if ( ! $hash ) {
		WP_CLI::error( "Could not read {$file} for media import." );
	}
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'meta_query'     => array(
			array( 'key' => '_signal_art', 'value' => $file ),
			array( 'key' => '_signal_art_hash', 'value' => $hash ),
		),
		'fields'         => 'ids',
		'posts_per_page' => 1,
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}
	$temp = wp_tempnam( $file );
	if ( ! $temp || ! copy( $source, $temp ) ) {
		WP_CLI::error( "Could not stage {$file} for media import." );
	}
	$id = media_handle_sideload( array( 'name' => 'form-edit-' . $file, 'tmp_name' => $temp ), 0, $alt );
	if ( is_wp_error( $id ) && file_exists( $temp ) ) {
		unlink( $temp );
	}
	signal_seed_result( $id );
	update_post_meta( $id, '_signal_art', $file );
	update_post_meta( $id, '_signal_art_hash', $hash );
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

function form_edit_seed_product_id( $slug, $sku ) {
	$id         = wc_get_product_id_by_sku( $sku );
	$legacy_sku = 'SIG-' . strtoupper( $slug );
	$legacy_id  = wc_get_product_id_by_sku( $legacy_sku );

	if ( $id && ( 'product' !== get_post_type( $id ) || ! get_post_meta( $id, '_signal_demo', true ) ) ) {
		WP_CLI::error( "SKU {$sku} already belongs to non-demo content." );
	}
	if ( $legacy_id && ! $id && ( 'product' !== get_post_type( $legacy_id ) || ! get_post_meta( $legacy_id, '_signal_demo', true ) ) ) {
		WP_CLI::error( "SKU {$legacy_sku} already belongs to non-demo content." );
	}
	if ( $id && $legacy_id && $id !== $legacy_id && get_post_meta( $legacy_id, '_signal_demo', true ) ) {
		WP_CLI::error( "SKUs {$sku} and {$legacy_sku} belong to different demo records." );
	}
	return $id ?: $legacy_id;
}

function signal_seed_product( $id, $category, $hat, $record, $index ) {
	list( $slug, $sku, $title, $price, $intro ) = $record;
	$ink    = signal_seed_media( $slug . '-ink.webp', $title . ' in Ink, front view' );
	$stone  = signal_seed_media( $slug . '-stone.webp', $title . ' in Stone, front view' );
	$detail = signal_seed_media( $slug . '-detail.webp', $title . ' print detail' );
	$sizes  = $hat ? array( 'One size' ) : array( 'S', 'M', 'L', 'XL' );
	$colors = array( 'Ink', 'Stone' );
	$specs  = $hat
		? '<ul><li>Six-panel cotton twill</li><li>Curved brim and adjustable strap</li><li>Embroidered front graphic</li></ul>'
		: '<ul><li>Heavyweight 100% cotton jersey</li><li>Relaxed unisex fit with a ribbed crew neck</li><li>Screen-printed front graphic</li></ul>';
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
	$product->set_description( '<h3>Material &amp; construction</h3>' . $specs . '<h3>Care</h3><p>' . esc_html( $care ) . '</p>' );
	$product->set_image_id( $ink );
	$product->set_gallery_image_ids( array( $detail, $stone ) );
	$product->set_attributes( array( signal_seed_attribute( 'Size', $sizes ), signal_seed_attribute( 'Color', $colors ) ) );
	$product->update_meta_data( '_signal_demo', 1 );
	$id = $product->save();

	$expected = array();
	foreach ( $sizes as $size ) {
		foreach ( $colors as $color ) {
			$expected[ $size . '|' . $color ] = array( 'color' => $color, 'size' => $size );
		}
	}

	$existing = array();
	foreach ( $product->get_children() as $child ) {
		$variation = wc_get_product( $child );
		if ( $variation && $variation->get_meta( '_signal_demo' ) ) {
			$attributes = $variation->get_attributes();
			ksort( $attributes );
			$key = ( $attributes['size'] ?? '' ) . '|' . ( $attributes['color'] ?? '' );
			if ( ( $expected[ $key ] ?? null ) === $attributes && ! isset( $existing[ $key ] ) ) {
				$existing[ $key ] = $variation;
			} else {
				$variation->delete();
			}
		}
	}

	foreach ( $expected as $key => $attributes ) {
		$variation = $existing[ $key ] ?? new WC_Product_Variation();
		if ( ! $variation->get_id() ) {
			$variation->set_parent_id( $id );
			$variation->set_attributes( $attributes );
			$variation->update_meta_data( '_signal_demo', 1 );
		}
		$variation->set_status( 'publish' );
		$variation->set_regular_price( $price );
		$variation->set_stock_status( 'instock' );
		$variation->set_image_id( 'Ink' === $attributes['color'] ? $ink : $stone );
		$variation->save();
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
	$name = 'FORM / EDIT Demo Navigation';
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
	array( 'carrier-wave', 'FE-BLOCK-01-TEE', 'Block 01 Tee', '34.00', 'A solid form and its missing square. Screen printed just off center on heavyweight cotton jersey.' ),
	array( 'night-grid', 'FE-GRID-02-TEE', 'Grid 02 Tee', '32.00', 'Four squares in two weights. A modular print on a relaxed crew neck.' ),
	array( 'orbital-index', 'FE-ORBIT-03-TEE', 'Orbit 03 Tee', '36.00', 'A bisected circle, screen printed on heavyweight cotton jersey.' ),
	array( 'relay-station', 'FE-ANGLE-04-TEE', 'Angle 04 Tee', '34.00', 'A sharp triangular form interrupted by a smaller notch.' ),
	array( 'static-field', 'FE-LINE-05-TEE', 'Line 05 Tee', '32.00', 'Four horizontal forms, each shorter than the last.' ),
	array( 'after-hours', 'FE-FACET-06-TEE', 'Facet 06 Tee', '36.00', 'A diamond cut from a diamond, with a single point at its center.' ),
);
$hats = array(
	array( 'receiver-cap', 'FE-BLOCK-01-CAP', 'Block 01 Cap', '28.00', 'A compact block embroidered on an adjustable six-panel cap.' ),
	array( 'sideband-cap', 'FE-GRID-02-CAP', 'Grid 02 Cap', '28.00', 'A four-square study in raised thread on soft cotton twill.' ),
	array( 'orbit-cap', 'FE-ORBIT-03-CAP', 'Orbit 03 Cap', '30.00', 'A bisected circle embroidered above the curved brim.' ),
	array( 'uplink-cap', 'FE-ANGLE-04-CAP', 'Angle 04 Cap', '30.00', 'An embroidered triangular mark and an adjustable back strap.' ),
	array( 'field-cap', 'FE-LINE-05-CAP', 'Line 05 Cap', '28.00', 'Four stepped bars embroidered on a low-profile cap.' ),
	array( 'nightshift-cap', 'FE-FACET-06-CAP', 'Facet 06 Cap', '30.00', 'Diamond-shaped embroidery on a six-panel cotton twill cap.' ),
);

foreach ( array( 't-shirts', 'hats' ) as $slug ) {
	signal_seed_existing_category( $slug );
}
$existing_products = array();
foreach ( array_merge( $tees, $hats ) as list( $slug, $sku ) ) {
	$existing_products[ $slug ] = form_edit_seed_product_id( $slug, $sku );
}

WC_Install::create_pages();
$cart = get_post( wc_get_page_id( 'cart' ) );
if ( $cart && has_block( 'woocommerce/cart', $cart ) ) {
	$blocks  = parse_blocks( $cart->post_content );
	$classic = parse_blocks( '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->' );
	$updated = false;
	foreach ( $blocks as $index => $block ) {
		if ( 'woocommerce/cart' === $block['blockName'] ) {
			$blocks[ $index ] = $classic[0];
			$updated = true;
		}
	}
	if ( $updated ) {
		if ( ! metadata_exists( 'post', $cart->ID, '_signal_original_cart_content' ) && ! add_post_meta( $cart->ID, '_signal_original_cart_content', wp_slash( $cart->post_content ), true ) ) {
			WP_CLI::error( 'Could not back up the original cart content before conversion.' );
		}
		signal_seed_result( wp_update_post( array( 'ID' => $cart->ID, 'post_content' => wp_slash( serialize_blocks( $blocks ) ) ), true ) );
	}
}
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'blogname', 'FORM / EDIT' );
update_option( 'blogdescription', 'Independent clothing editions' );
$tees_image = signal_seed_media( 'collection-tees.webp', 'Orbit 03 Tee in Stone' );
$hats_image = signal_seed_media( 'collection-hats.webp', 'Angle 04 Cap in Ink' );
$tees_id    = signal_seed_category( 't-shirts', 'T-shirts', $tees_image );
$hats_id    = signal_seed_category( 'hats', 'Hats', $hats_image );
$products   = array();
foreach ( array( array( $tees, $tees_id, false ), array( $hats, $hats_id, true ) ) as list( $records, $category, $hat ) ) {
	foreach ( $records as $index => $record ) {
		$products[ $record[0] ] = signal_seed_product( $existing_products[ $record[0] ], $category, $hat, $record, $index );
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
	'hero_image'   => signal_seed_media( 'hero.webp', 'Block 01 Tee and Orbit 03 Cap arranged on a studio surface' ),
	'featured_ids' => implode( ',', array( $products['carrier-wave'], $products['receiver-cap'], $products['night-grid'] ) ),
) ) );
update_post_meta( $home, '_wp_page_template', 'page-blank.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );
$footer = signal_seed_post( 'blocks', 'signal-footer', 'FORM / EDIT Footer', signal_seed_render( 'footer', $links ) );

foreach ( array(
	$tees_id => array( 'graphic-tees', '01 / EDITION 001', 'Printed tees.', 'Six graphic studies on heavyweight cotton jersey. Cut with room to move.' ),
	$hats_id => array( 'field-caps', '02 / EDITION 001', 'Embroidered caps.', 'Six embroidered studies on soft cotton twill. One adjustable fit.' ),
	0        => array( 'the-collection', '00 / COLLECTION INDEX', 'Edition 01.', 'Twelve graphic pieces, split between two everyday forms.' ),
) as $term_id => list( $slug, $index, $title, $intro ) ) {
	$header = signal_seed_render( 'archive-header', $links + array(
		'archive_index' => $index,
		'archive_title' => $title,
		'archive_intro' => $intro,
	) );
	$block_id = signal_seed_post( 'blocks', 'signal-archive-' . $slug, 'FORM / EDIT — ' . $title, $header );
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
	array( 'Edition 01', 'page', wc_get_page_id( 'shop' ) ),
	array( 'Tees', 'product_cat', $tees_id ),
	array( 'Caps', 'product_cat', $hats_id ),
) );

$logo = signal_seed_media( 'wordmark.png', 'FORM / EDIT' );
$mods = array(
	'site_logo'                      => $logo,
	'site_logo_dark'                 => '',
	'logo_width'                     => 144,
	'logo_position'                  => 'left',
	'logo_position_mobile'           => 'center',
	'header_bg'                      => '#FFFFFF',
	'header_color'                   => 'light',
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
	'site_width'                     => 1440,
	'body_bg'                        => '#FFFFFF',
	'content_bg'                     => '#FFFFFF',
	'content_color'                  => 'light',
	'color_primary'                  => '#1B1B1B',
	'color_secondary'                => '#5C5B58',
	'color_texts'                    => '#1B1B1B',
	'color_links'                    => '#1B1B1B',
	'color_links_hover'              => '#000814',
	'type_headings_color'            => '#1B1B1B',
	'color_regular_price'            => '#1B1B1B',
	'color_divider'                  => '#D5D3CF',
	'type_headings'                  => array( 'font-family' => 'Inter Tight', 'variant' => '500' ),
	'type_texts'                     => array( 'font-family' => 'Inter', 'variant' => '400' ),
	'type_nav'                       => array( 'font-family' => 'Inter', 'variant' => '600' ),
	'type_alt'                       => array( 'font-family' => 'Bodoni Moda', 'variant' => '400italic' ),
	'google_fonts_cdn'               => 1,
	'category_title_style'           => '',
	'category_show_title'            => 0,
	'category_sidebar'               => 'none',
	'category_row_count'             => 3,
	'category_row_count_tablet'      => 3,
	'category_row_count_mobile'      => 2,
	'products_pr_page'               => 12,
	'product_box_category'           => 1,
	'product_hover'                  => 'fade_in_back',
	'disable_quick_view'             => 0,
	'product_next_prev_nav'          => 0,
	'product_layout'                 => 'stacked-right',
	'product_image_width'            => 7,
	'footer_block'                   => $footer,
	'footer_bottom_color'            => '#FFFFFF',
	'footer_bottom_text'             => 'light',
	'footer_left_text'               => '© ' . gmdate( 'Y' ) . ' FORM / EDIT',
	'payment_icons_placement'        => array(),
);
foreach ( $mods as $setting => $value ) {
	set_theme_mod( $setting, $value );
}

WP_CLI::success( sprintf( 'FORM / EDIT: %d variable products seeded.', count( $products ) ) );
WP_CLI::log( 'Homepage: ' . home_url( '/' ) );
WP_CLI::log( 'Category: ' . $links['tees_url'] );
WP_CLI::log( 'Product: ' . $product_url );
