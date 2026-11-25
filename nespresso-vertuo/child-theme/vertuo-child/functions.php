<?php

add_filter( 'theme_mod_swatches', '__return_true' );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'vertuo-child', get_stylesheet_uri(), array( 'flatsome-main' ), wp_get_theme()->get( 'Version' ) );
}, 20 );

add_filter( 'theme_mod_site_logo', function ( $logo ) {
	return get_template_directory_uri() . '/assets/img/logo.png' === $logo ? '' : $logo;
} );

add_filter( 'theme_mod_topbar_left', function ( $text ) {
	return '<strong class="uppercase">Add anything here or just remove it...</strong>' === $text ? '' : $text;
} );

add_filter( 'theme_mod_footer_left_text', function ( $text ) {
	return 'Copyright [ux_current_year] &copy; <strong>Flatsome Theme</strong>' === $text
		? '&copy; [ux_current_year] ' . esc_html( get_bloginfo( 'name' ) )
		: $text;
} );

function vertuo_catalog_input( $key ) {
	return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : '';
}

function vertuo_card_spec() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$specs = array();
	foreach ( array( 'Aromatic Notes', 'Cup Size', 'Intensity', 'Pods per Pack' ) as $name ) {
		$value = 'Cup Size' === $name && $product->meta_exists( 'vertuo_listing_cup' )
			? $product->get_meta( 'vertuo_listing_cup' )
			: $product->get_attribute( $name );
		if ( '' !== $value ) {
			if ( 'Intensity' === $name ) {
				$value = 'Intensity ' . $value;
			} elseif ( 'Pods per Pack' === $name ) {
				$value .= ' pods per pack';
			}
			$specs[] = $value;
		}
	}
	if ( $specs ) {
		echo '<p class="vertuo-card-spec">' . esc_html( implode( ' · ', $specs ) ) . '</p>';
	}
	$compatibility = $product->get_meta( 'vertuo_machine_compatibility' );
	if ( $compatibility ) {
		echo '<p class="vertuo-card-spec">' . esc_html( $compatibility ) . '</p>';
	}
}

function vertuo_catalog_shortcode_limit( $attributes ) {
	$attributes['limit'] = '-1';
	return $attributes;
}

function vertuo_catalog_cart_button() {
	if ( 'button' !== get_theme_mod( 'add_to_cart_icon' ) ) {
		woocommerce_template_loop_add_to_cart();
	}
}

function vertuo_catalog_pack_sizes() {
	global $product;
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return;
	}
	$options = $product->get_variation_attributes()['pa_pack-size'] ?? array();
	if ( ! $options ) {
		return;
	}

	echo '<div class="vertuo-card-packs"><span>Pack Size</span><div class="vertuo-card-pack-options">';
	foreach ( wc_get_product_terms( $product->get_id(), 'pa_pack-size', array( 'fields' => 'all' ) ) as $term ) {
		if ( in_array( $term->slug, $options, true ) ) {
			$url = add_query_arg( 'attribute_pa_pack-size', $term->slug, $product->get_permalink() );
			printf( '<a href="%s" aria-label="%s">%s</a>', esc_url( $url ), esc_attr( $product->get_name() . ' – ' . $term->name ), esc_html( $term->name ) );
		}
	}
	echo '</div></div>';
}

function vertuo_collection_product_ids( $collection ) {
	$ordered_ids = get_term_meta( $collection->term_id, 'vertuo_product_order', true );
	if ( ! is_array( $ordered_ids ) || ! $ordered_ids ) {
		return array();
	}
	$current_ids = wc_get_products( array(
		'category' => array( $collection->slug ),
		'status'   => 'publish',
		'limit'    => -1,
		'orderby'  => 'menu_order',
		'order'    => 'ASC',
		'return'   => 'ids',
	) );
	return array_merge( array_intersect( $ordered_ids, $current_ids ), array_diff( $current_ids, $ordered_ids ) );
}

function vertuo_catalog_shortcode() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}

	$root = get_term_by( 'slug', 'vertuo', 'product_cat' );
	if ( ! $root ) {
		return '';
	}
	$collections = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $root->term_id, 'hide_empty' => true, 'orderby' => 'term_id' ) );
	if ( is_wp_error( $collections ) || ! $collections ) {
		return '';
	}

	$by_slug = array_column( $collections, null, 'slug' );
	$category = vertuo_catalog_input( 'vertuo_collection' );
	$category = isset( $by_slug[ $category ] ) ? $category : '';
	$orders = array(
		'featured' => array( 'menu_order', 'ASC', 'Featured' ),
		'price-asc' => array( 'price', 'ASC', 'Price: Low to high' ),
		'price-desc' => array( 'price', 'DESC', 'Price: High to low' ),
		'name' => array( 'title', 'ASC', 'Name: A–Z' ),
	);
	$sort = vertuo_catalog_input( 'vertuo_sort' );
	$sort = isset( $orders[ $sort ] ) ? $sort : 'featured';
	$groups = $category ? array( $by_slug[ $category ] ) : ( 'featured' === $sort ? $collections : array( $root ) );
	$page_url = get_permalink();
	$form_args = array();
	parse_str( (string) wp_parse_url( $page_url, PHP_URL_QUERY ), $form_args );

	ob_start();
	?>
	<div class="vertuo-catalog" id="vertuo-catalog">
		<h2>Shop Vertuo</h2>
		<form class="vertuo-catalog-form" method="get" action="<?php echo esc_url( $page_url . '#vertuo-catalog' ); ?>">
			<?php foreach ( $form_args as $key => $value ) : ?>
				<?php if ( is_scalar( $value ) ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<label>Collection
				<select name="vertuo_collection">
					<option value="">All collections</option>
					<?php foreach ( $collections as $collection ) : ?>
						<option value="<?php echo esc_attr( $collection->slug ); ?>" <?php selected( $category, $collection->slug ); ?>><?php echo esc_html( $collection->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Sort by
				<select name="vertuo_sort">
					<?php foreach ( $orders as $key => $options ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $options[2] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button class="button" type="submit">Apply</button>
		</form>
		<?php
		add_action( 'woocommerce_after_shop_loop_item_title', 'vertuo_card_spec', 7 );
		add_action( 'woocommerce_after_shop_loop_item_title', 'vertuo_catalog_pack_sizes', 8 );
		add_action( 'flatsome_product_box_after', 'vertuo_catalog_cart_button', 100 );
		add_filter( 'shortcode_atts_products', 'vertuo_catalog_shortcode_limit', 20 );
		foreach ( $groups as $group ) {
			$ids = 'featured' === $sort ? vertuo_collection_product_ids( $group ) : array();
			echo '<h3>' . esc_html( $group->name ) . '</h3>';
			echo do_shortcode( sprintf(
				'[products category="%s" columns="5" limit="-1" ids="%s" orderby="%s" order="%s"]',
				sanitize_title( $group->slug ),
				implode( ',', array_map( 'absint', $ids ) ),
				$ids ? 'post__in' : $orders[ $sort ][0],
				$orders[ $sort ][1]
			) );
		}
		remove_action( 'woocommerce_after_shop_loop_item_title', 'vertuo_card_spec', 7 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'vertuo_catalog_pack_sizes', 8 );
		remove_action( 'flatsome_product_box_after', 'vertuo_catalog_cart_button', 100 );
		remove_filter( 'shortcode_atts_products', 'vertuo_catalog_shortcode_limit', 20 );
		?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'vertuo_catalog', 'vertuo_catalog_shortcode' );
