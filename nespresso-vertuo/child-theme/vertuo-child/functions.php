<?php

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'vertuo-child', get_stylesheet_uri(), array( 'flatsome-main' ), wp_get_theme()->get( 'Version' ) );
}, 20 );

function vertuo_catalog_input( $key ) {
	return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : '';
}

function vertuo_card_spec() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$specs = array();
	foreach ( array( 'Dung tích', 'Cường độ', 'Hương vị', 'Quy cách' ) as $name ) {
		$value = $product->get_attribute( $name );
		if ( '' !== $value ) {
			$specs[] = $value;
		}
	}
	if ( $specs ) {
		echo '<p class="vertuo-card-spec">' . esc_html( implode( ' · ', $specs ) ) . '</p>';
	}
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
		'featured' => array( 'menu_order', 'ASC', 'Thứ tự danh mục' ),
		'price-asc' => array( 'price', 'ASC', 'Giá tăng dần' ),
		'price-desc' => array( 'price', 'DESC', 'Giá giảm dần' ),
		'name' => array( 'title', 'ASC', 'Tên A–Z' ),
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
		<h2>Danh mục Vertuo</h2>
		<form class="vertuo-catalog-form" method="get" action="<?php echo esc_url( $page_url . '#vertuo-catalog' ); ?>">
			<?php foreach ( $form_args as $key => $value ) : ?>
				<?php if ( is_scalar( $value ) ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<label>Bộ sưu tập
				<select name="vertuo_collection">
					<option value="">Tất cả</option>
					<?php foreach ( $collections as $collection ) : ?>
						<option value="<?php echo esc_attr( $collection->slug ); ?>" <?php selected( $category, $collection->slug ); ?>><?php echo esc_html( $collection->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Sắp xếp
				<select name="vertuo_sort">
					<?php foreach ( $orders as $key => $options ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $options[2] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button class="button" type="submit">Áp dụng</button>
		</form>
		<?php
		add_action( 'woocommerce_after_shop_loop_item_title', 'vertuo_card_spec', 7 );
		foreach ( $groups as $group ) {
			echo '<h3>' . esc_html( $group->name ) . '</h3>';
			echo do_shortcode( sprintf(
				'[products category="%s" columns="4" limit="-1" orderby="%s" order="%s"]',
				sanitize_title( $group->slug ),
				$orders[ $sort ][0],
				$orders[ $sort ][1]
			) );
		}
		remove_action( 'woocommerce_after_shop_loop_item_title', 'vertuo_card_spec', 7 );
		?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'vertuo_catalog', 'vertuo_catalog_shortcode' );
