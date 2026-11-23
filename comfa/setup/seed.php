<?php
/**
 * Comfa demo seed. Run with: wp eval-file seed.php
 *
 * Expects catalog.json next to this file, the demo images in
 * wp-content/uploads/comfa-demo/ and the UX Builder exports in
 * wp-content/uploads/comfa-demo/ux-builder/. Safe to re-run: records are
 * matched by slug, and pages or UX Blocks edited after seeding are left alone.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce must be active before seeding.' );
}
if ( 'flatsome' !== get_template() ) {
	WP_CLI::error( 'Activate the Comfa child theme (parent: Flatsome) before seeding.' );
}

global $wpdb;
$cf_uploads = wp_upload_dir();
define( 'CF_SEED_DIR', trailingslashit( $cf_uploads['basedir'] ) . 'comfa-demo/' );
$cf_catalog = json_decode( (string) file_get_contents( __DIR__ . '/catalog.json' ), true );
if ( ! $cf_catalog ) {
	WP_CLI::error( 'catalog.json is missing or invalid.' );
}

function cf_seed_asset( $filename ) {
	static $cache = array();
	if ( isset( $cache[ $filename ] ) ) {
		return $cache[ $filename ];
	}
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'meta_key' => '_cf_seed_asset', 'meta_value' => $filename, 'fields' => 'ids' ) );
	if ( $existing ) {
		return $cache[ $filename ] = (int) $existing[0];
	}
	$file = CF_SEED_DIR . $filename;
	if ( ! file_exists( $file ) ) {
		WP_CLI::warning( 'Missing demo image: ' . $filename );
		return $cache[ $filename ] = 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = wp_insert_attachment(
		array(
			'post_title'     => ucwords( str_replace( '-', ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) ),
			// Prefixed so an image such as mission.webp cannot take the slug of the Mission page.
			'post_name'      => 'comfa-image-' . sanitize_title( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_status'    => 'inherit',
			'post_mime_type' => wp_check_filetype( $file )['type'],
		),
		$file
	);
	if ( is_wp_error( $id ) ) {
		return $cache[ $filename ] = 0;
	}
	update_post_meta( $id, '_cf_seed_asset', $filename );
	update_post_meta( $id, '_wp_attachment_image_alt', ucwords( str_replace( '-', ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) ) );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	return $cache[ $filename ] = (int) $id;
}

function cf_seed_attribute( $label, $slug, $type ) {
	$taxonomy = wc_attribute_taxonomy_name( $slug );
	$id       = wc_attribute_taxonomy_id_by_name( $taxonomy );
	$args     = array( 'name' => $label, 'slug' => $slug, 'type' => $type, 'order_by' => 'menu_order', 'has_archives' => false );
	$result   = $id ? wc_update_attribute( $id, $args ) : wc_create_attribute( $args );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( 'Attribute ' . $label . ': ' . $result->get_error_message() );
	}
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false, 'public' => true, 'label' => $label ) );
	}
	return $taxonomy;
}

function cf_seed_term( $taxonomy, $name, $slug = '', $args = array() ) {
	$slug = $slug ? $slug : sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		wp_update_term( $term->term_id, $taxonomy, array_merge( array( 'name' => $name ), $args ) );
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, $taxonomy, array_merge( array( 'slug' => $slug ), $args ) );
	if ( is_wp_error( $created ) ) {
		WP_CLI::error( "Term $name ($taxonomy): " . $created->get_error_message() );
	}
	return (int) $created['term_id'];
}

/**
 * Create or refresh a page / UX Block unless the merchant has edited it.
 */
function cf_seed_post( $type, $slug, $title, $content, $extra = array() ) {
	// get_page_by_path() also matches attachments, so look the slug up for this post type only.
	$found    = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'posts_per_page' => 1 ) );
	$existing = $found ? $found[0] : null;
	if ( $existing ) {
		$seeded = get_post_meta( $existing->ID, '_cf_seed_hash', true );
		if ( $seeded && md5( $existing->post_content ) !== $seeded ) {
			WP_CLI::log( "Kept edited $type: $slug" );
			return (int) $existing->ID;
		}
		if ( ! $seeded && '' !== trim( $existing->post_content ) && empty( $extra['force'] ) ) {
			WP_CLI::log( "Kept existing $type: $slug" );
			return (int) $existing->ID;
		}
		// Only the body is refreshed; a renamed title, status or template stays as the merchant left it.
		wp_update_post( wp_slash( array( 'ID' => $existing->ID, 'post_content' => $content ) ) );
		$id = (int) $existing->ID;
	} else {
		// Stop before inserting when other content (e.g. a media file) owns the slug; WordPress would publish "slug-2".
		if ( wp_unique_post_slug( $slug, 0, 'publish', $type, 0 ) !== $slug ) {
			WP_CLI::error( "$type $slug: the slug is already used by other content." );
		}
		$id = wp_insert_post( wp_slash( array( 'post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_status' => 'publish' ) ), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "$type $slug: " . $id->get_error_message() );
		}
		if ( 'page' === $type ) {
			update_post_meta( $id, '_wp_page_template', isset( $extra['template'] ) ? $extra['template'] : 'page-blank.php' );
		}
	}
	update_post_meta( $id, '_cf_seed_hash', md5( get_post_field( 'post_content', $id ) ) );
	return (int) $id;
}

function cf_seed_render( $text, $context ) {
	return preg_replace_callback(
		'/\{\{(img|src|url|block|ids):([^}]+)\}\}/',
		function ( $match ) use ( $context ) {
			list( , $kind, $value ) = $match;
			switch ( $kind ) {
				case 'img':
					return (string) cf_seed_asset( $value );
				case 'src':
					return esc_url( (string) wp_get_attachment_url( cf_seed_asset( $value ) ) );
				case 'url':
					return esc_url( home_url( $value ) );
				case 'block':
					return isset( $context['blocks'][ $value ] ) ? (string) $context['blocks'][ $value ] : '0';
				case 'ids':
					return isset( $context['ids'][ $value ] ) ? implode( ',', $context['ids'][ $value ] ) : '';
			}
			return '';
		},
		$text
	);
}

// ---------------------------------------------------------------- Store basics.
update_option( 'blogname', 'Comfa' );
update_option( 'blogdescription', 'Comfort basics that give back' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_default_country', 'US:NY' );
update_option( 'woocommerce_price_num_decimals', 2 );
update_option( 'woocommerce_enable_reviews', 'yes' );
update_option( 'woocommerce_enable_review_rating', 'yes' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_cart_redirect_after_add', 'no' );
update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
foreach ( array( 'woocommerce_thumbnail_cropping' => 'custom', 'woocommerce_thumbnail_cropping_custom_width' => 3, 'woocommerce_thumbnail_cropping_custom_height' => 4, 'woocommerce_thumbnail_image_width' => 600, 'woocommerce_single_image_width' => 1000 ) as $cf_key => $cf_value ) {
	update_option( $cf_key, $cf_value );
}

// ---------------------------------------------------------------- Attributes.
// Flatsome only registers its swatch attribute types once swatches are switched on, and WooCommerce
// falls back to "select" for unknown types. Enable them for this run before creating attributes.
set_theme_mod( 'swatches', 1 );
add_filter(
	'product_attributes_type_selector',
	function ( $types ) {
		return array_merge( array( 'ux_color' => 'Color', 'ux_image' => 'Image', 'ux_label' => 'Label' ), $types );
	}
);
$cf_tax = array(
	'pack'   => cf_seed_attribute( 'Pack', 'pack', 'ux_label' ),
	'color'  => cf_seed_attribute( 'Color', 'color', 'ux_color' ),
	'size'   => cf_seed_attribute( 'Size', 'size', 'ux_label' ),
	'fit'    => cf_seed_attribute( 'Fit', 'fit', 'select' ),
	'weight' => cf_seed_attribute( 'Weight', 'weight', 'select' ),
);
delete_transient( 'wc_attribute_taxonomies' );
WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

$cf_order = 0;
foreach ( $cf_catalog['colors'] as $cf_slug => $cf_color ) {
	$cf_id = cf_seed_term( $cf_tax['color'], $cf_color[0], $cf_slug );
	update_term_meta( $cf_id, 'ux_color', $cf_color[1] );
	update_term_meta( $cf_id, 'order', $cf_order++ );
}
$cf_order = 0;
foreach ( $cf_catalog['pack_sets'] as $cf_set ) {
	foreach ( $cf_set as $cf_pack ) {
		$cf_id = cf_seed_term( $cf_tax['pack'], $cf_pack[0] );
		update_term_meta( $cf_id, 'cf_pack_count', (int) $cf_pack[1] );
		update_term_meta( $cf_id, 'order', (int) $cf_pack[1] );
	}
}
foreach ( $cf_catalog['size_sets'] as $cf_set ) {
	foreach ( $cf_set as $cf_size ) {
		$cf_id = cf_seed_term( $cf_tax['size'], $cf_size[0], '', array( 'description' => '' ) );
		update_term_meta( $cf_id, 'order', $cf_order++ );
	}
}
cf_seed_term( $cf_tax['fit'], 'Unisex' );

// ---------------------------------------------------------------- Categories and tags.
$cf_cats = array();
foreach ( $cf_catalog['categories'] as $cf_slug => $cf_cat ) {
	$cf_cats[ $cf_slug ] = cf_seed_term( 'product_cat', $cf_cat['name'], $cf_slug, array( 'description' => $cf_cat['blurb'] ) );
	update_term_meta( $cf_cats[ $cf_slug ], 'thumbnail_id', cf_seed_asset( $cf_cat['image'] ) );
	foreach ( $cf_cat['children'] as $cf_child_slug => $cf_child ) {
		$cf_cats[ $cf_child_slug ] = cf_seed_term( 'product_cat', $cf_child[0], $cf_child_slug, array( 'parent' => $cf_cats[ $cf_slug ], 'description' => $cf_child[1] ) );
		update_term_meta( $cf_cats[ $cf_child_slug ], 'thumbnail_id', cf_seed_asset( 'sub-' . $cf_child_slug . '.webp' ) );
	}
}
$cf_styles = array( 'casual' => 'Casual', 'sport' => 'Sport', 'graphic' => 'Graphic', 'outdoor' => 'Outdoor' );
foreach ( $cf_styles as $cf_slug => $cf_name ) {
	cf_seed_term( 'product_tag', $cf_name, $cf_slug );
}

// ---------------------------------------------------------------- Products.
$cf_reviews = array(
	array( 'Maya R.', 5, 'Softest thing I own', 'Washed it a dozen times and it still looks new. Ordering more colours.' ),
	array( 'Daniel K.', 5, 'Fits exactly as described', 'True to size and the fabric has a really nice weight to it.' ),
	array( 'Priya S.', 4, 'Great quality', 'Lovely colour and very comfortable. Shipping took a day longer than expected.' ),
	array( 'Tom H.', 5, 'Bought the pack, glad I did', 'Good value in the pack and I like that every one bought means one given.' ),
);
$cf_ids = array( 'all' => array(), 'featured' => array() );
foreach ( $cf_catalog['products'] as $cf_index => $cf_item ) {
	$cf_id      = wc_get_product_id_by_sku( 'CF-' . strtoupper( $cf_item['slug'] ) );
	$cf_product = $cf_id ? wc_get_product( $cf_id ) : new WC_Product_Variable();
	if ( ! $cf_product instanceof WC_Product_Variable ) {
		WP_CLI::error( 'SKU CF-' . strtoupper( $cf_item['slug'] ) . ' belongs to a non-variable product.' );
	}
	$cf_packs = $cf_catalog['pack_sets'][ $cf_item['packs'] ];
	$cf_sizes = wp_list_pluck( $cf_catalog['size_sets'][ $cf_item['sizes'] ], 0 );
	cf_seed_term( $cf_tax['weight'], $cf_item['weight'] );

	$cf_attributes = array();
	$cf_defs       = array(
		array( 'pack', wp_list_pluck( $cf_packs, 0 ), true, true ),
		array( 'color', array_map( function ( $slug ) use ( $cf_catalog ) { return $cf_catalog['colors'][ $slug ][0]; }, $cf_item['colors'] ), true, true ),
		array( 'size', $cf_sizes, true, true ),
		array( 'fit', array( 'Unisex' ), false, false ),
		array( 'weight', array( $cf_item['weight'] ), false, true ),
	);
	foreach ( $cf_defs as $cf_position => $cf_def ) {
		$cf_attribute = new WC_Product_Attribute();
		$cf_attribute->set_id( wc_attribute_taxonomy_id_by_name( $cf_tax[ $cf_def[0] ] ) );
		$cf_attribute->set_name( $cf_tax[ $cf_def[0] ] );
		$cf_attribute->set_options( array_map( function ( $name ) use ( $cf_tax, $cf_def ) { return (int) get_term_by( 'name', $name, $cf_tax[ $cf_def[0] ] )->term_id; }, $cf_def[1] ) );
		$cf_attribute->set_position( $cf_position );
		$cf_attribute->set_visible( $cf_def[3] );
		$cf_attribute->set_variation( $cf_def[2] );
		$cf_attributes[] = $cf_attribute;
	}
	$cf_gallery = array( cf_seed_asset( $cf_item['slug'] . '-detail.webp' ), cf_seed_asset( 'tees' === str_replace( 't-shirts', 'tees', $cf_item['cat'] ) ? 'pdp-wear.webp' : $cf_catalog['categories'][ $cf_item['cat'] ]['image'] ), cf_seed_asset( 'fabric.webp' ) );
	$cf_product->set_name( $cf_item['name'] );
	$cf_product->set_slug( $cf_item['slug'] );
	$cf_product->set_sku( 'CF-' . strtoupper( $cf_item['slug'] ) );
	$cf_product->set_status( 'publish' );
	$cf_product->set_catalog_visibility( 'visible' );
	$cf_product->set_featured( ! empty( $cf_item['featured'] ) );
	$cf_product->set_short_description( $cf_item['short'] );
	$cf_product->set_description( '<ul><li>Combed ringspun cotton, pre-shrunk</li><li>Machine wash cold with like colours, tumble dry low</li><li>Made in small batches in certified workshops</li></ul>' );
	$cf_product->set_attributes( $cf_attributes );
	$cf_product->set_default_attributes( array( $cf_tax['pack'] => sanitize_title( $cf_item['default_pack'] ), $cf_tax['color'] => $cf_item['colors'][0] ) );
	$cf_product->set_category_ids( array_values( array_filter( array( $cf_cats[ $cf_item['cat'] ], $cf_item['sub'] ? $cf_cats[ $cf_item['sub'] ] : 0 ) ) ) );
	$cf_product->set_tag_ids( array( (int) get_term_by( 'slug', $cf_item['style'], 'product_tag' )->term_id ) );
	$cf_product->set_image_id( cf_seed_asset( $cf_item['slug'] . '-' . $cf_item['colors'][0] . '.webp' ) );
	$cf_product->set_gallery_image_ids( array_values( array_filter( $cf_gallery ) ) );
	$cf_product->set_menu_order( $cf_index );
	$cf_product->set_reviews_allowed( true );
	$cf_product->update_meta_data( '_cf_badge', $cf_item['badge'] );
	$cf_product->update_meta_data( '_cf_highlight', $cf_item['highlight'] );
	$cf_notes = array();
	foreach ( $cf_catalog['size_sets'][ $cf_item['sizes'] ] as $cf_size ) {
		$cf_notes[ sanitize_title( $cf_size[0] ) ] = $cf_size[1];
	}
	$cf_product->update_meta_data( '_cf_size_notes', $cf_notes );
	$cf_product->update_meta_data( '_cf_seed', 1 );
	$cf_id = $cf_product->save();

	$cf_existing = array();
	foreach ( $cf_product->get_children() as $cf_child_id ) {
		$cf_child = wc_get_product( $cf_child_id );
		if ( $cf_child ) {
			$cf_existing[ $cf_child->get_sku() ] = $cf_child;
		}
	}
	foreach ( $cf_packs as $cf_pack ) {
		foreach ( $cf_item['colors'] as $cf_color_index => $cf_color ) {
			$cf_sku       = 'CF-' . strtoupper( $cf_item['slug'] . '-' . sanitize_title( $cf_pack[0] ) . '-' . $cf_color );
			$cf_variation = isset( $cf_existing[ $cf_sku ] ) ? $cf_existing[ $cf_sku ] : new WC_Product_Variation();
			$cf_regular   = $cf_item['price'] * $cf_pack[1];
			$cf_variation->set_parent_id( $cf_id );
			$cf_variation->set_sku( $cf_sku );
			$cf_variation->set_attributes( array( $cf_tax['pack'] => sanitize_title( $cf_pack[0] ), $cf_tax['color'] => $cf_color, $cf_tax['size'] => '' ) );
			$cf_variation->set_regular_price( wc_format_decimal( $cf_regular, 2 ) );
			$cf_variation->set_sale_price( $cf_pack[2] ? wc_format_decimal( $cf_regular * ( 100 - $cf_pack[2] ) / 100, 2 ) : '' );
			$cf_variation->set_image_id( cf_seed_asset( $cf_item['slug'] . '-' . $cf_color . '.webp' ) );
			$cf_variation->set_manage_stock( false );
			$cf_variation->set_stock_status( 'instock' );
			$cf_variation->set_menu_order( $cf_pack[1] * 100 + $cf_color_index );
			$cf_variation->save();
			unset( $cf_existing[ $cf_sku ] );
		}
	}
	WC_Product_Variable::sync( $cf_id );

	if ( ! get_comments( array( 'post_id' => $cf_id, 'count' => true, 'meta_key' => '_cf_seed', 'meta_value' => 1 ) ) ) {
		foreach ( $cf_reviews as $cf_review_index => $cf_review ) {
			if ( ( $cf_index + $cf_review_index ) % 4 === 3 ) {
				continue;
			}
			$cf_comment = wp_insert_comment(
				array(
					'comment_post_ID'      => $cf_id,
					'comment_author'       => $cf_review[0],
					'comment_author_email' => sanitize_title( $cf_review[0] ) . '@example.com',
					'comment_content'      => '<strong>' . $cf_review[2] . '</strong> ' . $cf_review[3],
					'comment_type'         => 'review',
					'comment_approved'     => 1,
					'comment_date'         => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * ( 3 + $cf_review_index * 9 + $cf_index ) ),
				)
			);
			update_comment_meta( $cf_comment, 'rating', $cf_review[1] );
			update_comment_meta( $cf_comment, 'verified', 1 );
			update_comment_meta( $cf_comment, '_cf_seed', 1 );
		}
		WC_Comments::clear_transients( $cf_id );
	}
	$cf_ids['all'][]                 = $cf_id;
	$cf_ids[ $cf_item['cat'] ][]     = $cf_id;
	if ( ! empty( $cf_item['featured'] ) ) {
		$cf_ids['featured'][] = $cf_id;
	}
}
$cf_ids['tees'] = array_slice( $cf_ids['t-shirts'], 0, 4 );
$cf_ids['caps'] = array_slice( $cf_ids['caps'], 0, 4 );
wc_delete_product_transients();
if ( function_exists( 'swatches' ) ) {
	swatches()->cache_clear();
}

// ---------------------------------------------------------------- UX Blocks and pages.
$cf_ux      = CF_SEED_DIR . 'ux-builder/';
$cf_context = array( 'ids' => $cf_ids, 'blocks' => array() );
$cf_blocks  = array( 'footer' => 'Footer', 'mega-tees' => 'Mega Menu: T-Shirts', 'mega-caps' => 'Mega Menu: Caps', 'pdp-story' => 'Product Story', 'page-404' => '404 Page' );
foreach ( $cf_blocks as $cf_slug => $cf_title ) {
	$cf_context['blocks'][ $cf_slug ] = cf_seed_post( 'blocks', $cf_slug, $cf_title, cf_seed_render( (string) file_get_contents( $cf_ux . 'blocks/' . $cf_slug . '.txt' ), $cf_context ) );
}
$cf_pages = array( 'home' => 'Home', 'mission' => 'Our Mission', 'help' => 'Help & FAQ', 'shipping-returns' => 'Shipping & Returns', 'size-guide' => 'Size Guide', 'contact' => 'Contact' );
$cf_page_ids = array();
foreach ( $cf_pages as $cf_slug => $cf_title ) {
	$cf_file = 'home' === $cf_slug ? $cf_ux . 'home.txt' : $cf_ux . 'pages/' . $cf_slug . '.txt';
	$cf_page_ids[ $cf_slug ] = cf_seed_post( 'page', $cf_slug, $cf_title, cf_seed_render( (string) file_get_contents( $cf_file ), $cf_context ), array( 'template' => 'home' === $cf_slug ? 'page-blank.php' : 'page-blank.php' ) );
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $cf_page_ids['home'] );

// Classic cart and checkout so Flatsome's cart/checkout styling and hooks apply.
foreach ( array( 'cart' => '[woocommerce_cart]', 'checkout' => '[woocommerce_checkout]' ) as $cf_slug => $cf_shortcode ) {
	$cf_page_id = wc_get_page_id( $cf_slug );
	$cf_page    = $cf_page_id > 0 ? get_post( $cf_page_id ) : null;
	if ( $cf_page && false !== strpos( $cf_page->post_content, 'wp:woocommerce/' . $cf_slug ) ) {
		update_post_meta( $cf_page_id, '_cf_original_content', wp_slash( $cf_page->post_content ) );
		wp_update_post( array( 'ID' => $cf_page_id, 'post_content' => $cf_shortcode ) );
	}
}

// ---------------------------------------------------------------- Menus.
$cf_menu = wp_get_nav_menu_object( 'Comfa Main' );
$cf_menu_id = $cf_menu ? (int) $cf_menu->term_id : (int) wp_create_nav_menu( 'Comfa Main' );
if ( ! wp_get_nav_menu_items( $cf_menu_id ) ) {
	$cf_links = array(
		array( 'New In', home_url( '/shop/?orderby=date' ), '' ),
		array( 'T-Shirts', get_term_link( $cf_cats['t-shirts'], 'product_cat' ), 'mega-tees' ),
		array( 'Caps', get_term_link( $cf_cats['caps'], 'product_cat' ), 'mega-caps' ),
		array( 'Hoodies', get_term_link( $cf_cats['hoodies'], 'product_cat' ), '' ),
		array( 'Socks', get_term_link( $cf_cats['socks'], 'product_cat' ), '' ),
		array( 'Mission', get_permalink( $cf_page_ids['mission'] ), '' ),
		array( 'Help', get_permalink( $cf_page_ids['help'] ), '' ),
	);
	foreach ( $cf_links as $cf_position => $cf_link ) {
		$cf_item_id = wp_update_nav_menu_item( $cf_menu_id, 0, array( 'menu-item-title' => $cf_link[0], 'menu-item-url' => $cf_link[1], 'menu-item-status' => 'publish', 'menu-item-type' => 'custom', 'menu-item-position' => $cf_position + 1 ) );
		if ( $cf_link[2] && ! is_wp_error( $cf_item_id ) ) {
			update_post_meta( $cf_item_id, '_menu_item_block', $cf_context['blocks'][ $cf_link[2] ] );
			update_post_meta( $cf_item_id, '_menu_item_design', 'full-width' );
		}
	}
}
$cf_locations = get_theme_mod( 'nav_menu_locations', array() );
$cf_locations['primary']        = $cf_menu_id;
$cf_locations['primary_mobile'] = $cf_menu_id;
set_theme_mod( 'nav_menu_locations', $cf_locations );

// ---------------------------------------------------------------- Flatsome Theme Options.
$cf_mods = array(
	'site_logo'                   => cf_seed_asset( 'logo.png' ),
	'site_logo_sticky'            => cf_seed_asset( 'logo.png' ),
	'color_primary'               => '#1f3557',
	'color_secondary'             => '#1b1b1b',
	'color_success'               => '#3d7a52',
	'color_alert'                 => '#d4240d',
	'color_texts'                 => '#030302',
	'type_headings_color'         => '#030302',
	'color_links'                 => '#030302',
	'color_links_hover'           => '#3860be',
	'body_bg'                     => '#fdfcf8',
	'body_layout'                 => 'full-width',
	'site_width'                  => '1330',
	'type_texts'                  => array( 'font-family' => 'Outfit', 'variant' => 'regular' ),
	'type_headings'               => array( 'font-family' => 'Outfit', 'variant' => '700' ),
	'type_nav'                    => array( 'font-family' => 'Outfit', 'variant' => '800' ),
	'type_alt'                    => array( 'font-family' => 'Fraunces', 'variant' => '300' ),
	'type_size'                   => 100,
	'type_size_mobile'            => 100,
	'text_transform_headings'     => 'none',
	'text_transform_buttons'      => 'none',
	'text_transform_navigation'   => 'none',
	'text_transform_section_titles' => 'none',
	'text_transform_widget_titles' => 'none',
	'topbar_show'                 => 1,
	'topbar_bg'                   => '#213858',
	'topbar_color'                => 'dark',
	'header_top_height'           => '52',
	'topbar_elements_left'        => array(),
	'topbar_elements_center'      => array( 'html' ),
	'topbar_elements_right'       => array(),
	'topbar_left'                 => '[cf_promo]',
	'header_elements_left'        => array( 'nav' ),
	'header_elements_right'       => array( 'account', 'search', 'cart' ),
	'header_mobile_elements_left' => array( 'menu-icon', 'search' ),
	'header_mobile_elements_right' => array( 'account', 'cart' ),
	'header_mobile_elements_top'  => array( 'html' ),
	'logo_position'               => 'left',
	'logo_position_mobile'        => 'center',
	'logo_width'                  => 132,
	'header_height'               => '64',
	'header_height_mobile'        => '56',
	'header_bg'                   => '#fdfcf8',
	'header_color'                => 'light',
	'header_sticky'               => 1,
	'header_sticky_show'          => 'always',
	'nav_size'                    => 'medium',
	'nav_spacing'                 => 'xlarge',
	'nav_uppercase'               => 0,
	'type_nav_color'              => '#030302',
	'type_nav_color_hover'        => '#3860be',
	'dropdown_style'              => 'simple',
	'dropdown_border_enabled'     => 0,
	'header_search_style'         => 'lightbox',
	'search_icon_style'           => '',
	'header_account_title'        => 1,
	'account_icon_style'          => 'plain',
	'header_account_username'     => 0,
	'account_login_style'         => 'lightbox',
	'header_cart_style'           => 'off-canvas',
	'header_cart_total'           => 0,
	'header_cart_title'           => 0,
	'cart_icon_style'             => 'plain',
	'cart_icon'                   => 'basket',
	'header_cart_sticky_footer'   => 1,
	'mobile_overlay'              => 'left',
	'footer_block'                => $cf_context['blocks']['footer'],
	'footer_1'                    => 0,
	'footer_2'                    => 0,
	'footer_left_text'            => '',
	'footer_right_text'           => '',
	'back_to_top'                 => 0,
	'swatches'                    => 1,
	'swatches_layout'             => '',
	'swatches_box_attribute'      => (string) wc_attribute_taxonomy_id_by_name( $cf_tax['color'] ),
	'swatches_box_limit'          => 6,
	'swatches_box_shape'          => 'circle',
	'swatches_box_size'           => '',
	'swatches_box_select_event'   => 'click',
	'swatches_tooltip'            => 0,
	'disable_quick_view'          => 0,
	'category_sidebar'            => 'left-sidebar',
	'category_row_count'          => 3,
	'category_row_count_tablet'   => 2,
	'category_row_count_mobile'   => 2,
	'products_pr_page'            => 12,
	'grid_style'                  => 'grid1',
	'category_grid_style'         => 'grid',
	'equalize_product_box'        => 0,
	'product_box_category'        => 0,
	'product_box_rating'          => 0,
	'add_to_cart_icon'            => 'disabled',
	'product_hover'               => 'none',
	'category_show_title'         => 0,
	'breadcrumb_home'             => 0,
	'product_layout'              => 'stacked-right',
	'product_image_width'         => '8',
	'product_gallery_grid_layout' => '2',
	'product_next_prev_nav'       => 0,
	'product_header'              => '',
	'product_zoom'                => 0,
	'product_sticky_cart'         => 1,
	'related_products'            => 'slider',
	'related_products_pr_row'     => 4,
	'max_related_products'        => 8,
	'error_404_block'             => $cf_context['blocks']['page-404'],
	'html_scripts_header'         => '',
	'flatsome_lightbox'           => 1,
	'lazy_load_images'            => 0,
);
foreach ( $cf_mods as $cf_key => $cf_value ) {
	set_theme_mod( $cf_key, $cf_value );
}

// ---------------------------------------------------------------- Shop sidebar filters.
$cf_layered = array();
$cf_number  = 2;
foreach ( array( 'weight' => array( 'Weight', 'list' ), 'size' => array( 'Size', 'list' ), 'color' => array( 'Color', 'list' ), 'pack' => array( 'Pack Size', 'list' ) ) as $cf_slug => $cf_widget ) {
	$cf_layered[ $cf_number++ ] = array( 'title' => $cf_widget[0], 'attribute' => $cf_slug, 'display_type' => $cf_widget[1], 'query_type' => 'or' );
}
$cf_layered['_multiwidget'] = 1;
update_option( 'widget_woocommerce_layered_nav', $cf_layered );
update_option( 'widget_woocommerce_price_filter', array( 2 => array( 'title' => 'Price' ), '_multiwidget' => 1 ) );
$cf_sidebars = get_option( 'sidebars_widgets', array() );
$cf_sidebars['shop-sidebar'] = array( 'woocommerce_layered_nav-2', 'woocommerce_layered_nav-3', 'woocommerce_layered_nav-4', 'woocommerce_layered_nav-5', 'woocommerce_price_filter-2' );
update_option( 'sidebars_widgets', $cf_sidebars );

// ---------------------------------------------------------------- Shipping, coupon, demo payment.
$cf_zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $cf_existing_zone ) {
	if ( 'Comfa Demo (US)' === $cf_existing_zone['zone_name'] ) {
		$cf_zone = new WC_Shipping_Zone( $cf_existing_zone['id'] );
	}
}
if ( ! $cf_zone ) {
	$cf_zone = new WC_Shipping_Zone();
	$cf_zone->set_zone_name( 'Comfa Demo (US)' );
	$cf_zone->add_location( 'US', 'country' );
	$cf_zone->save();
	$cf_flat = $cf_zone->add_shipping_method( 'flat_rate' );
	$cf_free = $cf_zone->add_shipping_method( 'free_shipping' );
	update_option( 'woocommerce_flat_rate_' . $cf_flat . '_settings', array( 'title' => 'Standard Shipping', 'tax_status' => 'none', 'cost' => '6' ) );
	update_option( 'woocommerce_free_shipping_' . $cf_free . '_settings', array( 'title' => 'Free Shipping', 'requires' => 'min_amount', 'min_amount' => '75', 'ignore_discounts' => 'no' ) );
}
// Free shipping first, so it is preselected once the bag reaches the minimum.
foreach ( $cf_zone->get_shipping_methods() as $cf_method ) {
	$wpdb->update( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array( 'method_order' => 'free_shipping' === $cf_method->id ? 1 : 2 ), array( 'instance_id' => $cf_method->instance_id ) );
}
WC_Cache_Helper::get_transient_version( 'shipping', true );
$cf_coupon_id = wc_get_coupon_id_by_code( 'HELLO10' );
$cf_coupon    = new WC_Coupon( $cf_coupon_id ? $cf_coupon_id : 0 );
$cf_coupon->set_code( 'HELLO10' );
$cf_coupon->set_discount_type( 'percent' );
$cf_coupon->set_amount( 10 );
$cf_coupon->set_usage_limit_per_user( 1 );
$cf_coupon->save();
update_option( 'woocommerce_enable_coupons', 'yes' );
$cf_cod = get_option( 'woocommerce_cod_settings', array() );
update_option( 'woocommerce_cod_settings', array_merge( is_array( $cf_cod ) ? $cf_cod : array(), array( 'enabled' => 'yes', 'title' => 'Cash on delivery (demo)' ) ) );

flush_rewrite_rules();
WP_CLI::success( sprintf( 'Comfa demo seeded: %d products, %d UX Blocks, %d pages.', count( $cf_ids['all'] ), count( $cf_context['blocks'] ), count( $cf_page_ids ) ) );
