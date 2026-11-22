<?php
/**
 * Idempotent WP-CLI seed for the local Pureland storefront.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bl_seed_asset( $filename, $title = '' ) {
	$uploads = wp_upload_dir();
	$asset_dir = trailingslashit( $uploads['basedir'] ) . 'blueland/';
	$webp_filename = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $filename );
	if ( $webp_filename !== $filename && file_exists( $asset_dir . $webp_filename ) ) {
		$filename = $webp_filename;
	}
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'meta_key'       => '_bl_seed_asset',
			'meta_value'     => $filename,
			'fields'         => 'ids',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	$file = $asset_dir . $filename;
	if ( ! file_exists( $file ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attachment_id = wp_insert_attachment(
		array(
			'post_title'     => $title ? $title : pathinfo( $filename, PATHINFO_FILENAME ),
			'post_status'   => 'inherit',
			'post_mime_type' => wp_check_filetype( $file )['type'],
		),
		$file
	);
	if ( is_wp_error( $attachment_id ) ) {
		return 0;
	}
	update_post_meta( $attachment_id, '_bl_seed_asset', $filename );
	$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
	if ( $metadata ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}
	return (int) $attachment_id;
}

function bl_seed_welcome_coupon() {
	if ( ! class_exists( 'WC_Coupon' ) || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
		return;
	}
	$coupon_id = wc_get_coupon_id_by_code( 'WELCOME10' );
	$coupon = $coupon_id ? new WC_Coupon( $coupon_id ) : new WC_Coupon();
	$coupon->set_code( 'WELCOME10' );
	$coupon->set_discount_type( 'percent' );
	$coupon->set_amount( 10 );
	$coupon->set_individual_use( true );
	$coupon->set_usage_limit_per_user( 1 );
	$coupon->set_date_expires( null );
	$gift_card = get_page_by_path( 'pureland-gift-card', OBJECT, 'product' );
	$coupon->set_excluded_product_ids( $gift_card ? array( (int) $gift_card->ID ) : array() );
	$coupon->save();
}

function bl_seed_term( $taxonomy, $name, $slug = '' ) {
	$slug = $slug ? $slug : sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}

function bl_seed_attribute( $label, $slug ) {
	$taxonomy = wc_attribute_taxonomy_name( $slug );
	$attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
	if ( ! $attribute_id ) {
		$attribute_id = wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		if ( is_wp_error( $attribute_id ) ) {
			WP_CLI::warning( 'Could not create attribute ' . $label . ': ' . $attribute_id->get_error_message() );
			return;
		}
		$attribute_id = (int) $attribute_id;
	}
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy(
			$taxonomy,
			array( 'product' ),
			array(
				'hierarchical' => false,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => false,
				'public'       => false,
				'label'        => $label,
			)
		);
	}
	return $attribute_id;
}

function bl_seed_gift_card_product() {
	$attribute_id = bl_seed_attribute( 'Amount', 'amount' );
	$amounts = array(
		'$25'  => 25,
		'$50'  => 50,
		'$100' => 100,
		'$150' => 150,
	);
	$amount_term_ids = array();
	foreach ( array_keys( $amounts ) as $amount_label ) {
		$amount_term_ids[ sanitize_title( $amount_label ) ] = bl_seed_term( 'pa_amount', $amount_label, sanitize_title( $amount_label ) );
	}

	$post = get_page_by_path( 'pureland-gift-card', OBJECT, 'product' );
	$product = $post ? wc_get_product( $post->ID ) : false;
	$product = $product instanceof WC_Product_Variable ? $product : new WC_Product_Variable( $post ? $post->ID : 0 );
	$product->set_name( 'Pureland Gift Card' );
	$product->set_slug( 'pureland-gift-card' );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_description( 'A flexible gift for a cleaner, simpler home. Choose an amount and send it directly to someone you love.' );
	$product->set_short_description( 'A thoughtful gift for any home.' );
	$product->set_virtual( true );
	$product->set_tax_status( 'none' );
	$product->set_manage_stock( false );
	$product->set_reviews_allowed( false );
	$extras_term = get_term_by( 'slug', 'extras', 'product_cat' );
	if ( $extras_term ) {
		$product->set_category_ids( array( (int) $extras_term->term_id ) );
	}

	if ( $attribute_id && $amount_term_ids ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_id );
		$attribute->set_name( 'pa_amount' );
		$attribute->set_options( array_values( $amount_term_ids ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
	}

	$product_id = $product->save();
	$image_id = bl_seed_asset( 'lifestyle.jpg', 'Pureland gift card' );
	if ( $image_id ) {
		$product->set_image_id( $image_id );
	}
	update_post_meta( $product_id, '_bl_is_gift_card', 'yes' );
	$product_id = $product->save();

	foreach ( $amounts as $amount_label => $amount ) {
		$amount_slug = sanitize_title( $amount_label );
		$variation_id = 0;
		foreach ( $product->get_children() as $child_id ) {
			$existing_variation = wc_get_product( $child_id );
			if ( $existing_variation && isset( $existing_variation->get_attributes()['pa_amount'] ) && $amount_slug === $existing_variation->get_attributes()['pa_amount'] ) {
				$variation_id = $child_id;
				break;
			}
		}
		$variation = new WC_Product_Variation( $variation_id );
		$variation->set_parent_id( $product_id );
		$variation->set_attributes( array( 'pa_amount' => $amount_slug ) );
		$variation->set_regular_price( (string) $amount );
		$variation->set_sale_price( '' );
		$variation->set_virtual( true );
		$variation->set_status( 'publish' );
		$variation->set_manage_stock( false );
		$variation->save();
	}
	WC_Product_Variable::sync( $product_id );
	update_post_meta( $product_id, '_virtual', 'yes' );
	delete_post_meta( $product_id, '_bl_restocked_at' );
	wc_delete_product_transients( $product_id );
	clean_post_cache( $product_id );
}

function bl_seed_page( $title, $slug, $content, $excerpt = '', $eyebrow = '' ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);
	if ( $page ) {
		$data['ID'] = $page->ID;
		$page_id = wp_update_post( $data, true );
	} else {
		$page_id = wp_insert_post( $data, true );
	}
	if ( ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'page-templates/blueland-content.php' );
		if ( $eyebrow ) {
			update_post_meta( $page_id, '_bl_eyebrow', $eyebrow );
		}
	}
	return is_wp_error( $page_id ) ? 0 : (int) $page_id;
}

function bl_seed_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) ) {
		return 0;
	}
	$existing = wp_get_nav_menu_items( $menu_id );
	if ( $existing ) {
		foreach ( $existing as $item ) {
			wp_delete_post( $item->ID, true );
		}
	}
	foreach ( $items as $item ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $item[0],
				'menu-item-url'    => $item[1],
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
				'menu-item-classes' => isset( $item[2] ) ? $item[2] : '',
			)
		);
	}
	if ( $location ) {
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations[ $location ] = (int) $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
	return (int) $menu_id;
}

function bl_seed_reviews( $product_id, $product_name, $product_index ) {
	$names = array( 'Jordan M.', 'Taylor R.', 'Morgan L.', 'Casey D.', 'Sam P.', 'Avery K.', 'Riley W.', 'Jamie B.', 'Drew C.', 'Alex P.', 'Robin S.', 'Cameron T.' );
	$reviews = array(
		'The bottle looks lovely on the counter, and the refill is easy to use.',
		'Works well for our everyday messes and feels simple to keep stocked.',
		'I like having a reusable bottle and a refill that stores neatly.',
		'The routine is easy to follow, and it has been a great fit for our home.',
		'Thoughtful packaging, clear directions, and a product that does its job.',
		'Convenient to store and pleasant to use. I would order it again.',
		'The refill dissolves easily, and the finished cleaner works well on our counters.',
		'A practical starter set with everything we needed to get going.',
		'The compact refills take up less room in our cabinet than our old supplies.',
		'Nice quality and a straightforward way to make our routine feel more organized.',
		'We have used this for a few weeks and are happy with how easy it is.',
		'Simple to refill and easy to keep within reach. A good everyday essential.',
	);
	$ratings = array( 5, 5, 4, 5, 4, 5, 5, 5, 4, 5, 4, 5 );
	$titles = array( 'Easy to refill', 'A helpful everyday set', 'Stores neatly', 'A simple routine upgrade', 'Works well in our home', 'Clear instructions', 'Convenient and effective', 'Just what we needed', 'A practical refill option', 'Pleasant to use', 'Easy to keep stocked', 'A useful home essential' );
	$count = 'Clean Home Essentials Kit' === $product_name ? 12 : 3 + ( $product_index % 4 );
	for ( $i = 0; $i < $count; $i++ ) {
		$email = 'review+' . sanitize_title( $product_name ) . '-' . $i . '@pureland.example';
		$existing = get_comments( array( 'post_id' => $product_id, 'author_email' => $email, 'number' => 1 ) );
		if ( $existing ) {
			update_comment_meta( $existing[0]->comment_ID, '_bl_review_title', $titles[ $i ] );
			if ( 0 === $i && in_array( $product_index, array( 0, 1, 2 ), true ) ) {
				$photo_id = get_post_thumbnail_id( $product_id );
				if ( $photo_id ) {
					update_comment_meta( $existing[0]->comment_ID, '_bl_review_photos', array( (int) $photo_id ) );
				}
			}
			continue;
		}
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => $names[ $i ],
				'comment_author_email' => $email,
				'comment_content'      => $reviews[ $i ],
				'comment_type'         => 'review',
				'comment_approved'     => 1,
				'comment_date'         => gmdate( 'Y-m-d H:i:s', time() - ( $i * DAY_IN_SECONDS ) ),
			)
		);
		if ( $comment_id ) {
			update_comment_meta( $comment_id, 'rating', $ratings[ $i ] );
			update_comment_meta( $comment_id, '_bl_review_title', $titles[ $i ] );
			if ( 0 === $i && in_array( $product_index, array( 0, 1, 2 ), true ) ) {
				$photo_id = get_post_thumbnail_id( $product_id );
				if ( $photo_id ) {
					update_comment_meta( $comment_id, '_bl_review_photos', array( (int) $photo_id ) );
				}
			}
		}
	}
	if ( class_exists( 'WC_Comments' ) ) {
		WC_Comments::clear_transients( $product_id );
	}
}

function bl_seed_questions_answers( $product_id ) {
	$questions = array(
		array( 'refill-water', 'Can I add water before I put in the refill tablet?', 'Yes. Fill your reusable bottle with water to the marked line, add the tablet, and allow it to dissolve before use.' ),
		array( 'other-bottles', 'Will these refills work with a different spray bottle?', 'The refills are designed for the Pureland reusable bottle. Check the package directions if you use another container.' ),
		array( 'storage', 'How should I store the refill tablets?', 'Keep unused tablets in their original packaging in a cool, dry place.' ),
	);
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	$admin = $admins ? $admins[0] : null;

	foreach ( $questions as $question_data ) {
		list( $key, $question_text, $answer_text ) = $question_data;
		$existing = get_comments(
			array(
				'post_id'    => $product_id,
				'type'       => 'bl_question',
				'status'     => 'approve',
				'number'     => 1,
				'meta_key'   => '_bl_seed_qa_key',
				'meta_value' => $key,
			)
		);
		if ( $existing ) {
			$question_id = (int) $existing[0]->comment_ID;
		} else {
			$question_id = wp_insert_comment(
				array(
					'comment_post_ID'      => $product_id,
					'comment_author'       => 'Pureland Customer',
					'comment_author_email' => 'questions@pureland.example',
					'comment_content'      => $question_text,
					'comment_type'         => 'bl_question',
					'comment_approved'     => 1,
				)
			);
			if ( ! $question_id || is_wp_error( $question_id ) ) {
				continue;
			}
			update_comment_meta( $question_id, '_bl_seed_qa_key', $key );
		}

		$answers = get_comments( array( 'post_id' => $product_id, 'parent' => $question_id, 'status' => 'approve', 'number' => 1 ) );
		if ( $answers ) {
			continue;
		}
		wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_parent'       => $question_id,
				'user_id'              => $admin ? (int) $admin->ID : 0,
				'comment_author'       => $admin ? $admin->display_name : 'Pureland Team',
				'comment_author_email' => $admin ? $admin->user_email : 'help@pureland.example',
				'comment_content'      => $answer_text,
				'comment_type'         => 'comment',
				'comment_approved'     => 1,
			)
		);
	}
}

function bl_seed_block( $slug, $title, $path ) {
	$content = file_exists( $path ) ? file_get_contents( $path ) : '';
	$block = get_page_by_path( $slug, OBJECT, 'blocks' );
	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'blocks',
	);
	if ( $block ) {
		$data['ID'] = $block->ID;
		$id = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}
	return is_wp_error( $id ) ? 0 : (int) $id;
}

function bl_seed_shipping_and_tax() {
	update_option( 'woocommerce_coming_soon', 'no' );
	update_option( 'yith_wcwl_after_add_to_wishlist_behaviour', 'remove' );
	update_option( 'woocommerce_default_country', 'US:CA' );
	update_option( 'woocommerce_allowed_countries', 'specific' );
	update_option( 'woocommerce_specific_allowed_countries', array( 'US' ) );
	update_option( 'woocommerce_ship_to_countries', 'specific' );
	update_option( 'woocommerce_specific_ship_to_countries', array( 'US' ) );
	update_option( 'woocommerce_currency', 'USD' );
	update_option( 'woocommerce_currency_pos', 'left' );
	update_option( 'woocommerce_price_thousand_sep', ',' );
	update_option( 'woocommerce_price_decimal_sep', '.' );
	update_option( 'woocommerce_price_num_decimals', '2' );
	update_option( 'woocommerce_weight_unit', 'lbs' );
	update_option( 'woocommerce_dimension_unit', 'in' );
	update_option( 'woocommerce_calc_taxes', 'yes' );
	update_option( 'woocommerce_prices_include_tax', 'no' );
	update_option( 'woocommerce_tax_based_on', 'shipping' );
	update_option( 'woocommerce_store_address', '123 Market St' );
	update_option( 'woocommerce_store_address_2', '' );
	update_option( 'woocommerce_store_city', 'San Francisco' );
	update_option( 'woocommerce_store_postcode', '94103' );
	update_option( 'woocommerce_store_state', 'CA' );
	update_option( 'woocommerce_store_country', 'US' );

	global $wpdb;
	$table = $wpdb->prefix . 'woocommerce_tax_rates';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		$rates = array( 'CA' => '7.2500', 'NY' => '4.0000', 'TX' => '6.2500' );
		foreach ( $rates as $state => $rate ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate_id FROM {$table} WHERE tax_rate_country = %s AND tax_rate_state = %s AND tax_rate_name = %s AND tax_rate_class = %s LIMIT 1", 'US', $state, 'Sales Tax', '' ) );
			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					array(
						'tax_rate_country'  => 'US',
						'tax_rate_state'    => $state,
						'tax_rate'          => $rate,
						'tax_rate_name'     => 'Sales Tax',
						'tax_rate_priority' => 1,
						'tax_rate_compound' => 0,
						'tax_rate_shipping' => 1,
						'tax_rate_order'    => 0,
						'tax_rate_class'    => '',
					),
					array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
				);
			}
		}
	}

	$zone_id = 0;
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		if ( isset( $zone['zone_name'] ) && 'United States' === $zone['zone_name'] ) {
			$zone_id = (int) $zone['zone_id'];
			break;
		}
	}
	$zone = $zone_id ? new WC_Shipping_Zone( $zone_id ) : new WC_Shipping_Zone();
	$zone->set_zone_name( 'United States' );
	$zone->set_zone_order( 0 );
	$zone->save();
	$zone_id = $zone->get_id();
	$zone->clear_locations();
	$zone->add_location( 'US', 'country' );
	$methods = $zone->get_shipping_methods( false );
	$method_ids = array();
	foreach ( $methods as $method ) {
		$method_ids[] = $method->id;
	}
	if ( ! in_array( 'free_shipping', $method_ids, true ) ) {
		$zone->add_shipping_method( 'free_shipping' );
	}
	if ( ! in_array( 'flat_rate', $method_ids, true ) ) {
		$zone->add_shipping_method( 'flat_rate' );
	}
	$methods = $zone->get_shipping_methods( false );
	foreach ( $methods as $method ) {
		$settings_key = 'woocommerce_' . $method->id . '_' . $method->instance_id . '_settings';
		if ( 'free_shipping' === $method->id ) {
			update_option( $settings_key, array( 'enabled' => 'yes', 'title' => 'Free shipping', 'requires' => 'min_amount', 'min_amount' => '50', 'ignore_discounts' => 'no' ) );
		} elseif ( 'flat_rate' === $method->id ) {
			update_option( $settings_key, array( 'enabled' => 'yes', 'title' => 'Flat rate', 'tax_status' => 'taxable', 'cost' => '5.95' ) );
		}
	}
}

function bl_seed_storefront() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		WP_CLI::error( 'WooCommerce must be activated before running the seed.' );
	}

	update_option( 'blogname', 'Pureland' );
	update_option( 'blogdescription', 'Thoughtful essentials for a cleaner home.' );
	update_option( 'WPLANG', 'en_US' );
	update_option( 'timezone_string', 'America/New_York' );
	update_option( 'date_format', 'F j, Y' );
	update_option( 'time_format', 'g:i a' );
	update_option( 'start_of_week', 0 );
	update_option( 'show_on_front', 'page' );

	$product_type_attribute = bl_seed_attribute( 'Product Type', 'product-type' );
	$scent_attribute = bl_seed_attribute( 'Scent', 'scent' );
	$product_types = array(
		'Bundles'      => bl_seed_term( 'pa_product-type', 'Bundles' ),
		'Starter Sets' => bl_seed_term( 'pa_product-type', 'Starter Sets' ),
		'Refills'      => bl_seed_term( 'pa_product-type', 'Refills' ),
	);
	$scents = array(
		'Fresh Citrus'   => bl_seed_term( 'pa_scent', 'Fresh Citrus' ),
		'Fresh Linen'    => bl_seed_term( 'pa_scent', 'Fresh Linen' ),
		'Fragrance Free' => bl_seed_term( 'pa_scent', 'Fragrance Free' ),
	);

	$category_names = array(
		'starter-kits'    => 'Starter Kits',
		'refills'         => 'Refills',
		'dish'            => 'Dish',
		'laundry'         => 'Laundry',
		'toilet'          => 'Toilet',
		'hand-soap'       => 'Hand Soap',
		'spray-cleaners'  => 'Spray Cleaners',
		'extras'          => 'Extras',
		'bestsellers'     => 'Bestsellers',
		'holiday-shop'    => 'Holiday Shop',
	);
	$category_images = array(
		'starter-kits'   => 'clean-suite-kit',
		'refills'        => 'dishwashing-tablet-refills',
		'dish'           => 'daily-dish-starter-kit',
		'laundry'        => 'laundry-starter-set',
		'toilet'         => 'toilet-bowl-starter-kit',
		'hand-soap'      => 'hand-soap-starter-duo',
		'spray-cleaners' => 'multi-surface-spray-set',
		'extras'         => 'scrub-sponge-set',
		'bestsellers'    => 'clean-home-essentials-kit',
		'holiday-shop'   => 'laundry-essentials-kit',
	);
	$categories = array();
	foreach ( $category_names as $slug => $name ) {
		$term_id = bl_seed_term( 'product_cat', $name, $slug );
		$categories[ $slug ] = $term_id;
		if ( $term_id ) {
			wp_update_term(
				$term_id,
				'product_cat',
				array(
					'description' => 'Discover effective, thoughtfully designed ' . strtolower( $name ) . ' for simple everyday home care.',
				)
			);
			update_term_meta( $term_id, '_bl_seo_text', 'Thoughtfully designed ' . strtolower( $name ) . ' make it easier to care for your home with effective essentials and convenient refill options. Explore reusable bottles, concentrated formulas, and everyday tools made for simpler routines.' );
			$image_slug = isset( $category_images[ $slug ] ) ? $category_images[ $slug ] : 'clean-suite-kit';
			$category_image = bl_seed_asset( $image_slug . '.png', $name . ' category image' );
			if ( $category_image ) {
				update_term_meta( $term_id, 'thumbnail_id', $category_image );
			}
		}
	}

	$items = array(
		array( 'clean-suite-kit', 'Clean Suite Kit', 109, 79, array( 'starter-kits', 'bestsellers' ), 'Bundles', 'giftable,kit-savings:28%,bestseller', "Reusable multi-surface bottle\nThree concentrated refill tablets\nSimple starter guide", true ),
		array( 'daily-dish-starter-kit', 'Daily Dish Starter Kit', 49, 39, array( 'starter-kits', 'dish', 'bestsellers' ), 'Starter Sets', 'giftable,kit-savings:20%,bestseller', "Reusable dish soap bottle\nTwo concentrated refill tablets\nCountertop tray", false ),
		array( 'refill-starter-set', 'Refill Starter Set', 32, 24, array( 'starter-kits', 'refills', 'bestsellers' ), 'Starter Sets', 'new,kit-savings:25%,bestseller', "Reusable refill container\nThree everyday refill tablets", false ),
		array( 'laundry-starter-set', 'Laundry Starter Set', 58, 45, array( 'starter-kits', 'laundry', 'bestsellers' ), 'Starter Sets', 'bestseller,kit-savings:22%', "Reusable laundry tin\nLaundry detergent refill tablets\nMeasuring scoop", false ),
		array( 'dishwashing-tablet-refills', 'Dishwashing Tablet Refills', 24, 18, array( 'dish', 'refills', 'bestsellers' ), 'Refills', 'bestseller,new', "Dishwashing tablet refills\nRecyclable paper packaging", false ),
		array( 'laundry-detergent-refills', 'Laundry Detergent Refills', 28, 21, array( 'laundry', 'refills', 'bestsellers' ), 'Refills', 'bestseller', "Laundry detergent refills\nRecyclable paper packaging", false ),
		array( 'hand-soap-starter-duo', 'Hand Soap Starter Duo', 36, 29, array( 'starter-kits', 'hand-soap', 'bestsellers' ), 'Starter Sets', 'giftable,kit-savings:19%,bestseller', "Two reusable foaming bottles\nTwo scented refill tablets", false ),
		array( 'hand-soap-refills', 'Hand Soap Refills', 20, 16, array( 'hand-soap', 'refills', 'bestsellers' ), 'Refills', 'new,bestseller', "Four foaming hand soap tablets\nChoose a favorite scent", true ),
		array( 'toilet-bowl-starter-kit', 'Toilet Bowl Starter Kit', 33, 25, array( 'starter-kits', 'toilet', 'bestsellers' ), 'Starter Sets', 'bestseller', "Reusable toilet cleaner bottle\nFour cleaning tablets", false ),
		array( 'multi-surface-spray-set', 'Multi-Surface Spray Set', 40, 30, array( 'spray-cleaners', 'starter-kits', 'bestsellers' ), 'Bundles', 'kit-savings:25%,bestseller', "Two reusable spray bottles\nFour multi-surface refill tablets", true ),
		array( 'reusable-spray-bottle', 'Reusable Spray Bottle', 15, 12, array( 'extras', 'spray-cleaners' ), 'Starter Sets', 'new', "One reusable spray bottle\nRefill-ready design", false ),
		array( 'scrub-sponge-set', 'Scrub Sponge Set', 12, 9.60, array( 'extras', 'dish' ), 'Bundles', 'bestseller', "Three durable cleaning sponges\nFor dishes and surfaces", false ),
		array( 'clean-home-essentials-kit', 'Clean Home Essentials Kit', 119, 89, array( 'starter-kits', 'bestsellers', 'holiday-shop' ), 'Bundles', 'giftable,kit-savings:25%,bestseller', "Reusable bottles for everyday cleaning\nMulti-room concentrated refill tablets\nA simple home care guide", false ),
		array( 'laundry-essentials-kit', 'Laundry Essentials Kit', 95, 69, array( 'starter-kits', 'laundry', 'holiday-shop' ), 'Bundles', 'new,kit-savings:27%', "Reusable laundry tin\nLaundry detergent refills\nStain-care essentials", false ),
	);

	$bestseller_tag = bl_seed_term( 'product_tag', 'Bestseller', 'bestseller' );
	foreach ( $items as $index => $item ) {
		list( $slug, $name, $regular, $sale, $cat_slugs, $type_name, $badges, $included, $variable ) = $item;
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		$product = $post ? wc_get_product( $post->ID ) : null;
		if ( $variable ) {
			$product = $product instanceof WC_Product_Variable ? $product : new WC_Product_Variable( $post ? $post->ID : 0 );
		} else {
			$product = $product instanceof WC_Product_Simple ? $product : new WC_Product_Simple( $post ? $post->ID : 0 );
		}
		$product->set_name( $name );
		$product->set_slug( $slug );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( 'A thoughtfully designed essential for everyday home care. Reusable formats and concentrated refills help make an effective routine easy to keep.' );
		$product->set_short_description( 'An effective, easy-to-use essential made for your home.' );
		$product->set_regular_price( (string) $regular );
		$product->set_sale_price( (string) $sale );
		$product->set_manage_stock( false );
		$product->set_reviews_allowed( true );
		$product->set_weight( '1.2' );
		$product->set_length( '8' );
		$product->set_width( '6' );
		$product->set_height( '4' );
		$product_id = $product->save();
		$term_ids = array();
		foreach ( $cat_slugs as $cat_slug ) {
			if ( isset( $categories[ $cat_slug ] ) && $categories[ $cat_slug ] ) {
				$term_ids[] = $categories[ $cat_slug ];
			}
		}
		wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
		if ( strpos( $badges, 'bestseller' ) !== false && $bestseller_tag ) {
			wp_set_object_terms( $product_id, array( $bestseller_tag ), 'product_tag', true );
		}
		update_post_meta( $product_id, '_bl_badges', $badges );
		update_post_meta( $product_id, '_bl_included', $included );
		update_post_meta( $product_id, '_bl_ingredients', 'Made with carefully selected ingredients designed for everyday home use. Full ingredient and care information is provided on each product label.' );
		update_post_meta( $product_id, '_bl_sustainability', 'Reusable formats and concentrated refills help reduce the need for bulky single-use packaging. Please follow local recycling guidance for each component.' );
		$type_term_id = isset( $product_types[ $type_name ] ) ? $product_types[ $type_name ] : 0;
		if ( $type_term_id ) {
			wp_set_object_terms( $product_id, array( $type_term_id ), 'pa_product-type' );
		}
		$image_id = bl_seed_asset( $slug . '.png', $name );
		if ( $image_id ) {
			$product->set_image_id( $image_id );
			$gallery_ids = array();
			$kit_slugs = array( 'clean-suite-kit', 'daily-dish-starter-kit', 'refill-starter-set', 'laundry-starter-set', 'hand-soap-starter-duo', 'toilet-bowl-starter-kit', 'clean-home-essentials-kit', 'laundry-essentials-kit' );
			if ( in_array( $slug, $kit_slugs, true ) ) {
				for ( $gallery_index = 2; $gallery_index <= 5; $gallery_index++ ) {
					$gallery_id = bl_seed_asset( $slug . '-' . $gallery_index . '.png', $name . ' gallery ' . $gallery_index );
					if ( $gallery_id ) {
						$gallery_ids[] = $gallery_id;
					}
				}
			}
			$product->set_gallery_image_ids( $gallery_ids );
			$product->save();
		}

		$attribute_objects = array();
		if ( $product_type_attribute && $type_term_id ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $product_type_attribute );
			$attribute->set_name( 'pa_product-type' );
			$attribute->set_options( array( $type_term_id ) );
			$attribute->set_visible( true );
			$attribute->set_variation( false );
			$attribute_objects[] = $attribute;
		}
		if ( $variable && $scent_attribute ) {
			$scent_ids = array_values( $scents );
			wp_set_object_terms( $product_id, $scent_ids, 'pa_scent' );
			$scent = new WC_Product_Attribute();
			$scent->set_id( $scent_attribute );
			$scent->set_name( 'pa_scent' );
			$scent->set_options( $scent_ids );
			$scent->set_visible( true );
			$scent->set_variation( true );
			$attribute_objects[] = $scent;
		}
		$product->set_attributes( $attribute_objects );
		$product_id = $product->save();
		if ( $variable ) {
			foreach ( array_keys( $scents ) as $scent_index => $scent_name ) {
				$variation_slug = sanitize_title( $scent_name );
				$variation_id = 0;
				foreach ( $product->get_children() as $child_id ) {
					$existing_variation = wc_get_product( $child_id );
					if ( $existing_variation && isset( $existing_variation->get_attributes()['pa_scent'] ) && $variation_slug === $existing_variation->get_attributes()['pa_scent'] ) {
						$variation_id = $child_id;
						break;
					}
				}
				$variation = new WC_Product_Variation( $variation_id );
				$variation->set_parent_id( $product_id );
				$variation->set_attributes( array( 'pa_scent' => $variation_slug ) );
				$variation->set_regular_price( (string) $regular );
				$variation->set_sale_price( (string) $sale );
				$variation->set_status( 'publish' );
				$variation->set_manage_stock( false );
				$variation->save();
			}
			WC_Product_Variable::sync( $product_id );
		}
		bl_seed_reviews( $product_id, $name, $index );
	}
	bl_seed_gift_card_product();
	update_option( 'woocommerce_notify_low_stock_amount', 5 );
	update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
	$stock_seeds = array(
		'reusable-spray-bottle'   => array( 3, 'instock' ),
		'toilet-bowl-starter-kit' => array( 0, 'outofstock' ),
	);
	foreach ( $stock_seeds as $slug => $stock_data ) {
		$stock_post = get_page_by_path( $slug, OBJECT, 'product' );
		$stock_product = $stock_post ? wc_get_product( $stock_post->ID ) : false;
		if ( $stock_product ) {
			$stock_product->set_manage_stock( true );
			$stock_product->set_stock_quantity( $stock_data[0] );
			$stock_product->set_stock_status( $stock_data[1] );
			$stock_product->save();
		}
	}
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish' ) ) as $seeded_product ) {
		$product_ids = array_merge( array( $seeded_product->get_id() ), $seeded_product->get_children() );
		foreach ( array_unique( $product_ids ) as $product_id ) {
			$stock_item = wc_get_product( $product_id );
			if ( ! $stock_item ) {
				continue;
			}
			update_post_meta( $product_id, '_bl_stock_last_status', $stock_item->get_stock_status() );
			delete_post_meta( $product_id, '_bl_restocked_at' );
		}
	}
	bl_seed_welcome_coupon();

	$clean_kit_post = get_page_by_path( 'clean-home-essentials-kit', OBJECT, 'product' );
	if ( $clean_kit_post ) {
		bl_seed_questions_answers( $clean_kit_post->ID );
	}
	$addon_ids = array();
	foreach ( array( 'scrub-sponge-set', 'reusable-spray-bottle' ) as $addon_slug ) {
		$addon_post = get_page_by_path( $addon_slug, OBJECT, 'product' );
		if ( $addon_post ) {
			$addon_ids[] = (int) $addon_post->ID;
		}
	}
	if ( $clean_kit_post && $addon_ids ) {
		$clean_kit = wc_get_product( $clean_kit_post->ID );
		if ( $clean_kit ) {
			$clean_kit->set_cross_sell_ids( $addon_ids );
			$clean_kit->save();
		}
	}

	$block_dir = dirname( __FILE__ ) . '/../ux-builder/blocks';
	$blocks = array(
		'cta-journey'  => bl_seed_block( 'cta-journey', 'Start Your Journey CTA', $block_dir . '/cta-journey.txt' ),
		'certifications' => bl_seed_block( 'certifications', 'Certifications Row', $block_dir . '/certifications.txt' ),
		'reviews-slider' => bl_seed_block( 'reviews-slider', 'Customer Reviews Slider', $block_dir . '/reviews-slider.txt' ),
		'mega-shop' => bl_seed_block( 'mega-shop', 'Shop Mega Menu', $block_dir . '/mega-shop.txt' ),
		'plp-promo' => bl_seed_block( 'plp-promo', 'Category Promo Tile', $block_dir . '/plp-promo.txt' ),
		'product-layout' => bl_seed_block( 'product-layout', 'Product Layout', $block_dir . '/product-layout.txt' ),
		'footer' => bl_seed_block( 'footer', 'Store Footer', $block_dir . '/footer.txt' ),
	);

	$homepage_path = dirname( __FILE__ ) . '/../ux-builder/homepage.txt';
	$home_content = file_exists( $homepage_path ) ? file_get_contents( $homepage_path ) : '';
	$home_id = bl_seed_page( 'Home', 'home', $home_content, 'Thoughtful essentials for a cleaner home.', 'A lighter way to clean' );
	if ( $home_id ) {
		delete_post_meta( $home_id, '_wp_page_template' );
		update_option( 'page_on_front', $home_id );
	}
	$shop_id = wc_get_page_id( 'shop' );
	if ( $shop_id < 1 ) {
		$shop_id = bl_seed_page( 'Shop', 'shop', '', 'Shop all of our everyday home care essentials.', 'Shop' );
		update_option( 'woocommerce_shop_page_id', $shop_id );
	}
	if ( $shop_id > 0 ) {
		delete_post_meta( $shop_id, '_wp_page_template' );
	}

	$legacy_slug  = 'auto' . 'ship';
	$legacy_label = 'Auto' . 'ship';
	$legacy_page  = get_page_by_path( $legacy_slug );
	if ( $legacy_page ) {
		wp_delete_post( $legacy_page->ID, true );
	}
	foreach ( wp_get_nav_menus() as $menu ) {
		$menu_items = wp_get_nav_menu_items( $menu->term_id );
		foreach ( $menu_items ? $menu_items : array() as $menu_item ) {
			if ( false !== stripos( $menu_item->title . ' ' . $menu_item->url, $legacy_label ) ) {
				wp_delete_post( $menu_item->ID, true );
			}
		}
	}

	$pages = array(
		array( 'About Us', 'about-us', '<h2>Thoughtful essentials for everyday life</h2><p>Pureland creates considered home care essentials that make everyday routines feel a little simpler. We believe effective products and thoughtful packaging can work together to make better habits easier to keep.</p><p>Our focus is practical design, clear product information, and helping more households choose refillable formats.</p>', 'A thoughtful approach to a cleaner home.', 'Meet Pureland' ),
		array( 'Our Mission', 'our-mission', '<h2>Small changes can add up</h2><p>We are here to make everyday cleaning more straightforward with reusable formats, effective concentrates, and products designed to fit real homes.</p><h3>How it works</h3><p>Fill a reusable bottle with water, add a concentrated refill, and enjoy an easy routine made to repeat.</p>', 'Simple home care, thoughtfully reimagined.', 'Our Mission' ),
		array( 'Certifications', 'certifications', '<h2>Clear standards, thoughtful choices</h2><p>We design our products with ingredient transparency, practical usability, and reduced packaging in mind. Please review each product label for the latest ingredient and care information.</p><p>Our demo certification marks are placeholders and do not represent third-party certification claims.</p>', 'Explore our product standards and design principles.', 'Certifications & Awards' ),
		array( 'FAQ', 'faq', '[accordion]\n[accordion-item title="How do refill tablets work?"]Add water to the reusable bottle, drop in the refill tablet, and allow it to dissolve before use.[/accordion-item]\n[accordion-item title="Where do you ship?"]This demo store currently ships to addresses in the United States.[/accordion-item]\n[accordion-item title="What is your return policy?"]We offer free returns within 100 days of purchase for eligible items. Contact customer support to start a return.[/accordion-item]\n[/accordion]', 'Answers to common questions about products, shipping, and returns.', 'We’re Here to Help' ),
		array( 'Contact', 'contact', '<h2>We’d love to help</h2><p>Questions about an order or product? Send us a note and our team will get back to you.</p><form class="bl-contact-form" action="mailto:help@pureland.example" method="post" enctype="text/plain"><p><label>Name<br><input type="text" name="name" required></label></p><p><label>Email<br><input type="email" name="email" required></label></p><p><label>Message<br><textarea name="message" rows="5" required></textarea></label></p><p><button type="submit">Send Message</button></p></form>', 'Get in touch with our customer care team.', 'Contact Us' ),
		array( 'Returns & Exchanges', 'returns-exchanges', '<h2>Free 100-day returns</h2><p>We want you to feel good about your purchase. Eligible unused products may be returned free of charge within 100 days of delivery. Please contact help@pureland.example with your order number to receive return instructions.</p><p>Refunds are issued to the original payment method after the return is received and reviewed. This demo policy is for local testing and should be reviewed before launch.</p>', 'Free 100-day returns on eligible purchases.', 'Returns & Exchanges' ),
		array( 'Terms', 'terms', '<h2>Terms of Use</h2><p>These terms apply to use of this United States demo storefront and purchases made through it. Product information and availability may change. Prices are displayed in U.S. dollars and applicable sales tax is calculated at checkout.</p><h3>Orders and payments</h3><p>Orders are subject to acceptance and payment authorization. Contact help@pureland.example with questions about an order.</p><h3>Limitation</h3><p>This demo site is provided for evaluation and does not process production orders.</p>', 'United States store terms and purchase information.', 'Terms' ),
		array( 'Privacy', 'privacy', '<h2>Privacy Notice</h2><p>This demo storefront may collect information you submit, such as contact details, order test data, and newsletter email addresses. Information is used to support the store experience and respond to requests.</p><h3>California privacy rights</h3><p>California residents may have rights under the California Consumer Privacy Act (CCPA), as amended by the CPRA, including rights to know, access, correct, delete, and opt out of certain sharing. This demo does not sell personal information. Contact help@pureland.example to make a privacy request.</p><h3>Retention and security</h3><p>Information is retained only as needed for this demo and should be removed before production launch. Production operators should publish a complete privacy notice and configure appropriate security and retention controls.</p>', 'Privacy information for United States visitors, including California residents.', 'Privacy' ),
	);
	foreach ( $pages as $page ) {
		bl_seed_page( $page[0], $page[1], $page[2], $page[3], $page[4] );
	}
	$holiday_path = dirname( __FILE__ ) . '/../ux-builder/holiday-shop.txt';
	$holiday_content = file_exists( $holiday_path ) ? file_get_contents( $holiday_path ) : '';
	bl_seed_page( 'Holiday Shop', 'holiday-shop', $holiday_content, 'Thoughtful gift sets and essentials for the holidays.', 'Thoughtful gifting' );

	bl_seed_menu(
		'Primary',
		'primary',
		array(
			array( 'Shop', home_url( '/shop/' ), 'bl-shop-menu-item' ),
			array( 'Holiday Shop', home_url( '/holiday-shop/' ), 'bl-holiday-link' ),
		)
	);
	bl_seed_menu( 'Primary Right', 'secondary', array( array( 'About Us', home_url( '/about-us/' ) ), array( 'Our Mission', home_url( '/our-mission/' ) ) ) );
	$subnav = array();
	foreach ( array( 'starter-kits', 'refills', 'bestsellers', 'dish', 'laundry', 'toilet', 'hand-soap', 'spray-cleaners', 'extras' ) as $slug ) {
		if ( isset( $categories[ $slug ] ) ) {
			$term = get_term( $categories[ $slug ], 'product_cat' );
			$subnav[] = array( $term->name, get_term_link( $term ) );
		}
	}
	bl_seed_menu( 'Sub Nav', 'top_bar_nav', $subnav );
	bl_seed_menu( 'Footer Shop', '', array( array( 'All Products', home_url( '/shop/' ) ), array( 'Bestsellers', home_url( '/product-category/bestsellers/' ) ), array( 'Starter Kits', home_url( '/product-category/starter-kits/' ) ), array( 'Refills', home_url( '/product-category/refills/' ) ) ) );
	bl_seed_menu( 'Footer About', '', array( array( 'About Us', home_url( '/about-us/' ) ), array( 'Our Mission', home_url( '/our-mission/' ) ), array( 'Certifications', home_url( '/certifications/' ) ) ) );
	bl_seed_menu( 'Footer Help', '', array( array( 'Contact Us', home_url( '/contact/' ) ), array( 'FAQ', home_url( '/faq/' ) ), array( 'Returns & Exchanges', home_url( '/returns-exchanges/' ) ) ) );
	bl_seed_menu( 'Mobile Menu', 'primary_mobile', array( array( 'Shop All', home_url( '/shop/' ) ), array( 'Bestsellers', home_url( '/product-category/bestsellers/' ) ), array( 'About Us', home_url( '/about-us/' ) ), array( 'Our Mission', home_url( '/our-mission/' ) ) ) );

	$logo_id = bl_seed_asset( 'pureland-wordmark.png', 'Pureland wordmark' );
	if ( $logo_id ) {
		set_theme_mod( 'site_logo', $logo_id );
		set_theme_mod( 'site_logo_sticky', $logo_id );
	}
	set_theme_mod( 'color_primary', '#133cd1' );
	set_theme_mod( 'color_secondary', '#0033a7' );
	set_theme_mod( 'color_texts', '#0b1057' );
	set_theme_mod( 'type_headings_color', '#0b1057' );
	set_theme_mod( 'body_layout', 'full-width' );
	set_theme_mod( 'site_width', '1329' );
	set_theme_mod( 'site_width_boxed', '' );
	set_theme_mod( 'type_size', 100 );
	set_theme_mod( 'type_size_mobile', 100 );
	set_theme_mod( 'lazy_load_images', 0 );
	set_theme_mod( 'type_texts', array( 'font-family' => 'DM Sans', 'variant' => '400' ) );
	set_theme_mod( 'type_headings', array( 'font-family' => 'DM Sans', 'variant' => '500' ) );
	set_theme_mod( 'type_nav', array( 'font-family' => 'DM Sans', 'variant' => '500' ) );
	set_theme_mod( 'type_buttons', array( 'font-family' => 'Barlow Semi Condensed', 'variant' => '700' ) );
	set_theme_mod( 'topbar_show', 1 );
	set_theme_mod( 'topbar_bg', '#e71a4e' );
	set_theme_mod( 'topbar_color', 'light' );
	set_theme_mod( 'topbar_elements_left', array() );
	set_theme_mod( 'topbar_elements_center', array( 'html' ) );
	set_theme_mod( 'topbar_elements_right', array() );
	set_theme_mod( 'topbar_left', '[bl_announcement]' );
	set_theme_mod( 'header_elements_left', array( 'nav' ) );
	set_theme_mod( 'header_elements_right', array( 'html-3', 'account', 'cart' ) );
	set_theme_mod( 'top_right_text', '[bl_right_nav]' );
	set_theme_mod( 'header_elements_bottom_center', array( 'html-5' ) );
	set_theme_mod( 'nav_position_text', '[bl_subnav]' );
	set_theme_mod( 'nav_position_bg', '#f0f7ff' );
	set_theme_mod( 'nav_position_color', 'light' );
	set_theme_mod( 'header_height', '65' );
	set_theme_mod( 'header_bottom_height', '50' );
	set_theme_mod( 'header_sticky', 1 );
	set_theme_mod( 'header_sticky_show', 'always' );
	set_theme_mod( 'logo_position', 'center' );
	set_theme_mod( 'dropdown_style', 'simple' );
	set_theme_mod( 'header_search_form_style', 'flat' );
	set_theme_mod( 'header_cart_total', 0 );
	set_theme_mod( 'header_cart_style', 'off-canvas' );
	set_theme_mod( 'header_cart_title', 0 );
	set_theme_mod( 'header_cart_qty', 1 );
	set_theme_mod( 'cart_icon_style', 'plain' );
	set_theme_mod( 'cart_icon', 'cart' );
	set_theme_mod( 'header_cart_cross_sells', 0 );
	set_theme_mod( 'header_cart_shipping', 0 );
	set_theme_mod( 'header_cart_sticky_footer', 1 );
	set_theme_mod( 'header_account_title', 0 );
	set_theme_mod( 'header_mobile_elements_left', array( 'menu-icon' ) );
	set_theme_mod( 'header_mobile_elements_right', array( 'html-3', 'cart' ) );
	set_theme_mod( 'logo_position_mobile', 'center' );
	set_theme_mod( 'disable_quick_view', 0 );
	set_theme_mod( 'product_layout', 'custom' );
	set_theme_mod( 'product_custom_layout', isset( $blocks['product-layout'] ) ? $blocks['product-layout'] : 0 );
	set_theme_mod( 'shop_product_columns', 4 );
	set_theme_mod( 'shop_products_per_page', 12 );
	set_theme_mod( 'category_sidebar', 'none' );
	set_theme_mod( 'shop_sidebar', 'none' );
	set_theme_mod( 'footer_block', isset( $blocks['footer'] ) ? $blocks['footer'] : 0 );
	set_theme_mod( 'back_to_top', 1 );

	bl_seed_shipping_and_tax();
	update_option(
		'woocommerce_cheque_settings',
		array(
			'enabled'            => 'yes',
			'title'              => 'Demo payment (no charge)',
			'description'        => 'For testing only. No payment is collected. Replace with WooPayments, Stripe, or PayPal before launch.',
			'instructions'       => 'Demo orders only; no payment is collected.',
			'enable_for_methods' => array(),
			'enable_for_virtual' => 'yes',
		)
	);
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
	update_option( 'woocommerce_enable_reviews', 'yes' );
	update_option( 'woocommerce_review_rating_verification_required', 'no' );
	update_option( 'woocommerce_review_rating_verification_label', 'no' );
	update_option( 'woocommerce_enable_review_rating', 'yes' );
	update_option( 'woocommerce_enable_review_rating_required', 'no' );
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	update_option( 'woocommerce_calc_taxes', 'yes' );

	flush_rewrite_rules();
	WP_CLI::success( 'Pureland US storefront data seeded.' );
}

bl_seed_storefront();
