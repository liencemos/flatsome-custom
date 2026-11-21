<?php
/**
 * Product cards, archive controls, and product taxonomy presentation.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'bl_loop_quick_add', 20 );
		remove_action( 'flatsome_product_box_actions', 'flatsome_lightbox_button', 50 );
		add_action( 'woocommerce_shop_loop_item_title', 'bl_loop_title', 10 );
		add_action( 'woocommerce_after_shop_loop_item_title', 'bl_loop_card_details', 5 );
		add_action( 'flatsome_product_box_actions', 'bl_loop_quick_add', 20 );
		add_action( 'flatsome_woocommerce_shop_loop_images', 'bl_loop_badges', 20 );
		add_action( 'woocommerce_before_shop_loop', 'bl_archive_toolbar', 5 );
		add_action( 'woocommerce_after_shop_loop', 'bl_archive_seo_text', 20 );
	}
);

function bl_loop_badges() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	echo bl_product_badges_markup( $product );
}

function bl_product_badges_markup( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$badges = array_filter( array_map( 'trim', explode( ',', (string) $product->get_meta( '_bl_badges' ) ) ) );
	if ( empty( $badges ) ) {
		return '';
	}
	$priority = array( 'giftable' => 0, 'new' => 1, 'bestseller' => 2 );
	usort(
		$badges,
		function ( $left, $right ) use ( $priority ) {
			$left_type  = false !== strpos( $left, ':' ) ? 'savings' : $left;
			$right_type = false !== strpos( $right, ':' ) ? 'savings' : $right;
			$left_order  = isset( $priority[ $left_type ] ) ? $priority[ $left_type ] : ( 'savings' === $left_type ? 9 : 5 );
			$right_order = isset( $priority[ $right_type ] ) ? $priority[ $right_type ] : ( 'savings' === $right_type ? 9 : 5 );
			return $left_order <=> $right_order;
		}
	);
	$badges = array_slice( $badges, 0, 2 );
	$html = '<div class="bl-product-badges">';
	foreach ( $badges as $badge ) {
		$label = $badge;
		$class = 'bl-badge';
		if ( false !== strpos( $badge, ':' ) ) {
			list( $type, $value ) = array_map( 'trim', explode( ':', $badge, 2 ) );
			$label = $value . ' KIT SAVINGS';
			$class .= ' bl-badge-savings';
		} else {
			$class .= ' bl-badge-' . sanitize_html_class( strtolower( $badge ) );
		}
		$html .= '<span class="' . esc_attr( $class ) . '">' . esc_html( strtoupper( $label ) ) . '</span>';
	}
	return $html . '</div>';
}

function bl_loop_title() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	echo '<h2 class="woocommerce-loop-product__title"><a href="' . esc_url( get_the_permalink() ) . '">' . esc_html( get_the_title() ) . '</a><span class="bl-card-title-price">' . wp_kses_post( $product->get_price_html() ) . '</span></h2>';
}

function bl_loop_card_details() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$rating = $product->get_average_rating();
	$count  = $product->get_review_count();
	$scents = wc_get_product_terms( $product->get_id(), 'pa_scent', array( 'fields' => 'names' ) );
	echo '<div class="bl-card-rating"><span class="star-rating" role="img" aria-label="' . esc_attr( sprintf( 'Rated %s out of 5', $rating ) ) . '"><span style="width:' . esc_attr( (float) $rating * 20 ) . '%"></span></span><strong>' . esc_html( number_format_i18n( (float) $rating, 1 ) ) . '</strong><span class="bl-review-count">(' . esc_html( number_format_i18n( $count ) ) . ')</span></div>';
	if ( $product->is_on_sale() ) {
		echo '<div class="bl-card-savings">with 20% Autoship savings</div>';
	}
	if ( ! empty( $scents ) ) {
		$swatches = '';
		foreach ( array_slice( $scents, 0, 1 ) as $index => $scent ) {
			$swatches .= '<span class="bl-swatch bl-swatch-' . esc_attr( (string) $index ) . '" aria-label="' . esc_attr( $scent ) . '"></span>';
		}
		echo '<div class="bl-card-scents">' . $swatches . '<span>| Scent: ' . esc_html( reset( $scents ) ) . '</span></div>';
	}
}

function bl_loop_quick_add() {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
		return;
	}
	if ( $product->is_type( 'simple' ) ) {
		echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" data-quantity="1" class="button bl-quick-add add_to_cart_button ajax_add_to_cart product_type_simple" data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" rel="nofollow">Quick Add</a>';
		return;
	}
	echo '<a href="#quick-view" role="button" aria-haspopup="dialog" aria-expanded="false" class="button bl-quick-add bl-quick-view quick-view" data-prod="' . esc_attr( $product->get_id() ) . '" rel="nofollow">Choose Options</a>';
}

function bl_archive_toolbar() {
	if ( ! is_main_query() || ( ! is_shop() && ! is_product_taxonomy() ) ) {
		return;
	}
	$count = wc_get_loop_prop( 'total' );
	echo '<div class="bl-archive-heading"><h1>' . esc_html( woocommerce_page_title( false ) ) . '</h1>';
	if ( is_product_category() ) {
		$description = term_description();
		if ( $description ) {
			echo '<div class="bl-archive-description">' . wp_kses_post( $description ) . '</div>';
		}
	}
	echo '</div><div class="bl-archive-toolbar"><span class="bl-item-count">' . esc_html( sprintf( _n( '%s Item', '%s Items', $count, 'blueland-child' ), number_format_i18n( $count ) ) ) . '</span><div class="bl-archive-filters">';
	$category = get_queried_object();
	if ( $category instanceof WP_Term ) {
		$parent = $category->parent;
		$terms  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $parent ) );
	} else {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) );
	}
	echo '<label class="bl-filter-select bl-category-filter-wrap"><span class="screen-reader-text">Product Category</span><select class="bl-category-filter"><option value="">Product Category</option>';
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			echo '<option value="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</option>';
		}
	}
	echo '</select></label>';
	$types = taxonomy_exists( 'pa_product-type' ) ? get_terms( array( 'taxonomy' => 'pa_product-type', 'hide_empty' => true ) ) : array();
	$current_type = isset( $_GET['filter_product-type'] ) ? sanitize_title( wp_unslash( $_GET['filter_product-type'] ) ) : '';
	echo '<label class="bl-filter-select bl-type-filter-wrap"><span class="screen-reader-text">Product Type</span><select class="bl-type-filter"><option value="">Product Type</option><option value="all">All types</option>';
	if ( ! is_wp_error( $types ) ) {
		foreach ( $types as $type ) {
			echo '<option value="' . esc_attr( $type->slug ) . '"' . selected( $current_type, $type->slug, false ) . '>' . esc_html( $type->name ) . '</option>';
		}
	}
	echo '</select></label>';
	echo '<label class="bl-filter-select bl-order-filter"><span class="screen-reader-text">Sort products</span>' . bl_catalog_ordering_markup() . '</label>';
	echo '</div><button type="button" class="bl-mobile-filter-toggle" aria-expanded="false">Filter &amp; Sort</button></div><div class="bl-mobile-filter-panel" role="dialog" aria-modal="true" aria-label="Filter and sort products" hidden><div class="bl-mobile-filter-header"><strong>Filter &amp; Sort</strong><button type="button" class="bl-mobile-filter-close" aria-label="Close filters">×</button></div></div>';
}

function bl_catalog_ordering_markup() {
	ob_start();
	woocommerce_catalog_ordering();
	$ordering = ob_get_clean();
	return $ordering;
}

add_filter(
	'woocommerce_catalog_orderby',
	function ( $options ) {
		return array(
			'menu_order' => 'Featured',
			'price'      => 'Price Low to High',
			'price-desc' => 'Price High to Low',
			'rating'     => 'Rating High to Low',
			'rating_asc' => 'Rating Low to High',
		);
	}
);

add_filter(
	'woocommerce_get_catalog_ordering_args',
	function ( $args, $orderby ) {
		if ( 'rating_asc' === $orderby ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_wc_average_rating';
			$args['order']   = 'ASC';
		}
		return $args;
	},
	10,
	2
);

add_filter(
	'woocommerce_product_loop_start',
	function ( $markup ) {
		if ( ! is_main_query() || ( ! is_shop() && ! is_product_taxonomy() ) || is_search() || max( 1, absint( get_query_var( 'paged' ) ) ) > 1 ) {
			return $markup;
		}
		$promo = do_shortcode( '[block id="plp-promo"]' );
		return preg_replace_callback(
			'/(<(ul|div)[^>]*class="[^"]*\bproducts\b[^"]*"[^>]*>)/',
			function ( $matches ) use ( $promo ) {
				$tag = 'ul' === $matches[2] ? 'li' : 'div';
				return $matches[1] . '<' . $tag . ' class="bl-promo-cell">' . $promo . '</' . $tag . '>';
			},
			$markup,
			1
		);
	}
);

function bl_archive_seo_text() {
	if ( ! is_main_query() || ! is_product_taxonomy() ) {
		return;
	}
	$term = get_queried_object();
	if ( $term instanceof WP_Term ) {
		$text = get_term_meta( $term->term_id, '_bl_seo_text', true );
		if ( $text ) {
			echo '<div class="bl-archive-seo">' . wp_kses_post( wpautop( $text ) ) . '</div>';
		}
	}
}

add_filter( 'woocommerce_show_page_title', '__return_false' );
