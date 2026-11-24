<?php
/**
 * Seeds the Dewier demo storefront. Run with: wp eval-file seed.php
 * Safe to re-run: every object is looked up by slug or meta before it is created.
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WooCommerce' ) ) {
	fwrite( STDERR, "Run inside WordPress with WooCommerce active.\n" );
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dewier_dir     = get_stylesheet_directory();
$dewier_catalog = json_decode( file_get_contents( __DIR__ . '/catalog.json' ), true );
if ( ! $dewier_catalog ) {
	fwrite( STDERR, "catalog.json not found next to seed.php.\n" );
	exit( 1 );
}

function dewier_seed_log( $message ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::log( $message );
	}
}

/** Imports a child-theme image into the Media Library once and returns its ID. */
function dewier_seed_image( $file, $alt = '' ) {
	$existing = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_key'    => '_dewier_source',
			'meta_value'  => $file,
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	$source = get_stylesheet_directory() . '/assets/images/' . $file;
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$upload = wp_upload_bits( 'dewier-' . $file, null, file_get_contents( $source ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment(
		array(
			'post_mime_type' => $type['type'],
			'post_title'     => $alt ? $alt : pathinfo( $file, PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_dewier_source', $file );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

function dewier_seed_page( $slug, $title, $content = '', $template = '' ) {
	$page = get_page_by_path( $slug );
	$id   = $page ? $page->ID : wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		)
	);
	if ( $template ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return (int) $id;
}

function dewier_seed_attribute( $label, $slug ) {
	$taxonomy = wc_attribute_taxonomy_name( $slug );
	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
	}
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false ) );
	}
	return $taxonomy;
}

function dewier_seed_term( $name, $taxonomy, $args = array() ) {
	$slug = isset( $args['slug'] ) ? $args['slug'] : sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, $taxonomy, $args );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}

/* ---------- Store settings ---------- */

update_option( 'blogname', 'Dewier' );
update_option( 'blogdescription', 'Skin first. Makeup second.' );
update_option( 'woocommerce_default_country', 'US:NY' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_currency_pos', 'left' );
update_option( 'woocommerce_price_num_decimals', 0 );
update_option( 'woocommerce_weight_unit', 'oz' );
update_option( 'woocommerce_dimension_unit', 'in' );
update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
update_option( 'woocommerce_cart_redirect_after_add', 'no' );

// The child theme styles the shortcode cart, so swap out WooCommerce's default Cart block.
$dewier_cart_page = get_post( wc_get_page_id( 'cart' ) );
if ( $dewier_cart_page && has_block( 'woocommerce/cart', $dewier_cart_page ) ) {
	wp_update_post(
		array(
			'ID'           => $dewier_cart_page->ID,
			'post_content' => '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
		)
	);
}

update_option( 'woocommerce_enable_reviews', 'yes' );
update_option( 'woocommerce_enable_review_rating', 'yes' );
update_option( 'woocommerce_catalog_columns', 4 );
update_option( 'woocommerce_catalog_rows', 6 );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );
update_option( 'timezone_string', 'America/New_York' );
update_option( 'elementor_onboarded', true );
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );
if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
}

WC_Install::create_pages();

// Demo shipping, only on a store with no shipping methods in any zone: free
// from $40 (listed first so it is the default once it applies), else $5.
if ( ! WC_Shipping_Zones::get_zones() && ! WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods() ) {
	$dewier_zone = new WC_Shipping_Zone();
	$dewier_zone->set_zone_name( 'United States' );
	$dewier_zone->add_location( 'US', 'country' );
	$dewier_zone->save();
	$dewier_free = $dewier_zone->add_shipping_method( 'free_shipping' );
	update_option( 'woocommerce_free_shipping_' . $dewier_free . '_settings', array( 'title' => 'Free shipping', 'requires' => 'min_amount', 'min_amount' => '40', 'ignore_discounts' => 'no' ) );
	$dewier_flat = $dewier_zone->add_shipping_method( 'flat_rate' );
	update_option( 'woocommerce_flat_rate_' . $dewier_flat . '_settings', array( 'title' => 'Standard shipping', 'tax_status' => 'none', 'cost' => '5' ) );
}

/* ---------- Attributes and categories ---------- */

$dewier_shade_tax = dewier_seed_attribute( 'Shade', 'shade' );
$dewier_size_tax  = dewier_seed_attribute( 'Size', 'size' );

$dewier_cat_ids = array();
foreach ( $dewier_catalog['categories'] as $index => $cat ) {
	$args = array( 'slug' => $cat['slug'] );
	if ( ! empty( $cat['parent'] ) ) {
		$args['parent'] = $dewier_cat_ids[ $cat['parent'] ];
	}
	$dewier_cat_ids[ $cat['slug'] ] = dewier_seed_term( $cat['name'], 'product_cat', $args );
	update_term_meta( $dewier_cat_ids[ $cat['slug'] ], 'order', $index );
	$tile = dewier_seed_image( 'tile-' . $cat['slug'] . '.jpg', $cat['name'] );
	if ( $tile ) {
		update_term_meta( $dewier_cat_ids[ $cat['slug'] ], 'thumbnail_id', $tile );
	}
}

/* ---------- Products ---------- */

$dewier_reviews = array(
	array( 5, 'Maya R.', 'Exactly what I wanted. It looks like my skin on a really good day and it lasts through a full workday.' ),
	array( 5, 'Priya S.', 'I absolutely adore this. It gives me a natural, visible glow and I feel like I do not need to add anything else.' ),
	array( 4, 'Jordan T.', 'Lovely texture and easy to use. I wish the packaging were a little bigger, but I will buy it again.' ),
	array( 5, 'Elena K.', 'My third repurchase. Lightweight, comfortable, and it layers well with everything else I use.' ),
	array( 4, 'Sam W.', 'Great everyday staple. The finish is soft and it never feels heavy.' ),
);

$dewier_product_ids = array();
foreach ( $dewier_catalog['products'] as $index => $item ) {
	$existing_id = wc_get_product_id_by_sku( 'DEW-' . $item['slug'] );
	$variations  = array();
	$taxonomy    = '';
	if ( ! empty( $item['shades'] ) ) {
		$taxonomy = $dewier_shade_tax;
		foreach ( $item['shades'] as $name => $color ) {
			$variations[ $name ] = array( 'price' => $item['price'], 'color' => $color );
		}
	} elseif ( ! empty( $item['sizes'] ) ) {
		$taxonomy = $dewier_size_tax;
		foreach ( $item['sizes'] as $name => $price ) {
			$variations[ $name ] = array( 'price' => $price );
		}
	}

	$product = $existing_id ? wc_get_product( $existing_id ) : ( $variations ? new WC_Product_Variable() : new WC_Product_Simple() );
	$product->set_name( $item['name'] );
	$product->set_slug( $item['slug'] );
	$product->set_sku( 'DEW-' . $item['slug'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_menu_order( $index );
	$product->set_featured( ! empty( $item['featured'] ) );
	$product->set_reviews_allowed( true );
	$product->set_short_description( '<p><strong>' . esc_html( $item['subtitle'] ) . ', made for every day.</strong></p><p>A lightweight, skin-first formula that blends in seconds and wears comfortably from morning to night. Demo copy: replace it with your own product story.</p>' );
	$product->set_description( '<p>Dermatologist-tested and formulated without fragrance where it matters. Use it alone or layer it with the rest of your routine.</p>' );
	$product->set_category_ids( array_values( array_intersect_key( $dewier_cat_ids, array_flip( $item['cats'] ) ) ) );
	$product->set_image_id( dewier_seed_image( 'product-' . $item['slug'] . '.jpg', $item['name'] ) );
	$product->set_gallery_image_ids( array_filter( array( dewier_seed_image( 'texture-' . $item['slug'] . '.jpg', $item['name'] . ' texture' ) ) ) );

	if ( $variations ) {
		$term_ids = array();
		$position = 0;
		foreach ( $variations as $name => $data ) {
			$term_id    = dewier_seed_term( $name, $taxonomy );
			$term_ids[] = $term_id;
			update_term_meta( $term_id, 'order', $position++ );
			if ( ! empty( $data['color'] ) && ! get_term_meta( $term_id, 'dewier_color', true ) ) {
				update_term_meta( $term_id, 'dewier_color', $data['color'] );
			}
		}
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $term_ids );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
		$first = sanitize_title( array_key_first( $variations ) );
		$product->set_default_attributes( array( $taxonomy => $first ) );
	} else {
		$product->set_regular_price( (string) $item['price'] );
		$product->set_sale_price( isset( $item['sale'] ) ? (string) $item['sale'] : '' );
		if ( ! $existing_id ) {
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
		}
	}

	$product_id = $product->save();
	$dewier_product_ids[ $item['slug'] ] = $product_id;
	update_post_meta( $product_id, '_dewier_subtitle', $item['subtitle'] );
	update_post_meta( $product_id, '_dewier_badges', implode( "\n", isset( $item['badges'] ) ? $item['badges'] : array() ) );
	update_post_meta( $product_id, '_dewier_shade_note', isset( $item['shade_note'] ) ? $item['shade_note'] : '' );

	if ( $variations ) {
		$existing = array();
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child ) {
				$existing[ current( $child->get_attributes() ) ] = $child;
			}
		}
		$position = 0;
		foreach ( $variations as $name => $data ) {
			$slug      = sanitize_title( $name );
			$variation = isset( $existing[ $slug ] ) ? $existing[ $slug ] : new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( array( $taxonomy => $slug ) );
			$variation->set_regular_price( (string) $data['price'] );
			if ( ! isset( $existing[ $slug ] ) ) {
				$variation->set_manage_stock( false );
				$variation->set_stock_status( 'instock' );
			}
			$variation->set_menu_order( $position++ );
			$variation->set_status( 'publish' );
			$variation->save();
		}
		WC_Product_Variable::sync( $product_id );
	}

	if ( ! get_comments( array( 'post_id' => $product_id, 'count' => true ) ) ) {
		for ( $r = 0; $r < 3; $r++ ) {
			$review     = $dewier_reviews[ ( $index + $r ) % count( $dewier_reviews ) ];
			$comment_id = wp_insert_comment(
				array(
					'comment_post_ID'      => $product_id,
					'comment_author'       => $review[1],
					'comment_author_email' => sanitize_title( $review[1] ) . '@example.com',
					'comment_content'      => $review[2],
					'comment_type'         => 'review',
					'comment_approved'     => 1,
				)
			);
			update_comment_meta( $comment_id, 'rating', $review[0] );
			update_comment_meta( $comment_id, 'verified', 1 );
		}
		WC_Comments::clear_transients( $product_id );
	}
}
dewier_seed_log( 'Products: ' . count( $dewier_product_ids ) );

$dewier_links = array(
	'petal-flush'               => array( 'petal-flush-plush', 'everyday-balm', 'the-cheek-kit' ),
	'second-skin-eau-de-parfum' => array( 'second-skin-body-mist', 'second-skin-duo', 'mini-fragrance-wardrobe' ),
	'brow-lift'                 => array( 'feather-lash', 'brow-lift-duo' ),
	'everyday-balm'             => array( 'tinted-balm-stick', 'everyday-balm-trio' ),
	'milk-wash-cleanser'        => array( 'dew-drop-serum', 'barrier-cream', 'skincare-starter-set' ),
);
foreach ( $dewier_links as $slug => $targets ) {
	$product = wc_get_product( $dewier_product_ids[ $slug ] );
	$product->set_cross_sell_ids( array_values( array_intersect_key( $dewier_product_ids, array_flip( $targets ) ) ) );
	$product->save();
}

wc_delete_product_transients();
delete_transient( 'wc_attribute_taxonomies' );
flush_rewrite_rules();
dewier_seed_log( 'Seed base complete.' );
