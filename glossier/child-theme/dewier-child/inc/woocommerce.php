<?php
/**
 * WooCommerce: product cards, collection pages and the product page.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Badges are stored one per line as "promo:Label" or "value:Label".
 */
function dewier_get_badges( $product ) {
	$badges = array();
	$lines  = array_filter( array_map( 'trim', explode( "\n", (string) $product->get_meta( '_dewier_badges' ) ) ) );
	foreach ( $lines as $line ) {
		$parts    = explode( ':', $line, 2 );
		$badges[] = 2 === count( $parts ) ? array( $parts[0], $parts[1] ) : array( 'value', $line );
	}
	if ( ! $badges && $product->is_on_sale() ) {
		$badges[] = array( 'promo', __( 'Sale', 'dewier' ) );
	}
	return $badges;
}

function dewier_badges( $product ) {
	$badges = dewier_get_badges( $product );
	if ( ! $badges ) {
		return;
	}
	echo '<div class="dw-badges">';
	foreach ( $badges as $badge ) {
		printf( '<span class="dw-badge dw-badge--%s">%s</span>', esc_attr( $badge[0] ), esc_html( $badge[1] ) );
	}
	echo '</div>';
}

/**
 * In-stock variations of a product that varies by a single attribute, one per term.
 * Products with several variation attributes return nothing: one control cannot
 * identify a variation, so those are chosen on the product page.
 */
function dewier_get_options( $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return array();
	}
	$attributes = $product->get_variation_attributes();
	$taxonomy   = (string) key( $attributes );
	if ( 1 !== count( $attributes ) || ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$by_slug = array();
	foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
		$values = $variation->get_attributes();
		if ( ! empty( $values[ $taxonomy ] ) && $variation->is_purchasable() && $variation->is_in_stock() ) {
			$by_slug[ $values[ $taxonomy ] ] = $variation;
		}
	}
	$options = array();
	$terms   = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );
	foreach ( $terms as $term ) {
		if ( ! isset( $by_slug[ $term->slug ] ) ) {
			continue;
		}
		$options[] = array(
			'taxonomy'  => $taxonomy,
			'slug'      => $term->slug,
			'name'      => $term->name,
			'color'     => (string) get_term_meta( $term->term_id, 'dewier_color', true ),
			'variation' => $by_slug[ $term->slug ],
		);
	}
	return $options;
}

/**
 * How many options a product card shows before linking to the product page.
 */
function dewier_card_limit( $options ) {
	return $options && '' !== $options[0]['color'] ? 8 : 3;
}

/**
 * The option a card or add-on row starts on: the product default when it is
 * among the given options, otherwise the first one.
 */
function dewier_selected_option( $product, $options ) {
	if ( ! $options ) {
		return null;
	}
	$default = $product->get_default_attributes();
	foreach ( $options as $option ) {
		if ( isset( $default[ $option['taxonomy'] ] ) && $default[ $option['taxonomy'] ] === $option['slug'] ) {
			return $option;
		}
	}
	return $options[0];
}

/**
 * Shade swatches / size pills on product cards. The selected option sets the
 * variation the "Add to bag" button adds.
 */
function dewier_card_options( $product, $options, $selected ) {
	if ( ! $options ) {
		return;
	}
	$is_color = '' !== $options[0]['color'];
	$selected = $selected ? $selected['slug'] : '';
	$limit    = dewier_card_limit( $options );
	$label    = wc_attribute_label( $options[0]['taxonomy'] );

	printf( '<div class="dw-options dw-options--%s" role="radiogroup" aria-label="%s">', $is_color ? 'color' : 'size', esc_attr( $label ) );
	foreach ( array_slice( $options, 0, $limit ) as $option ) {
		$variation = $option['variation'];
		printf(
			'<button type="button" class="dw-option%1$s" role="radio" aria-checked="%2$s" data-variation="%3$d" data-price="%4$s"%5$s%6$s>%7$s</button>',
			$option['slug'] === $selected ? ' is-selected' : '',
			$option['slug'] === $selected ? 'true' : 'false',
			absint( $variation->get_id() ),
			esc_attr( $variation->get_price_html() ),
			$is_color ? ' style="--dw-swatch:' . esc_attr( $option['color'] ) . '"' : '',
			$is_color ? ' aria-label="' . esc_attr( $option['name'] ) . '" title="' . esc_attr( $option['name'] ) . '"' : '',
			$is_color ? '' : esc_html( $option['name'] )
		);
	}
	if ( count( $options ) > $limit ) {
		printf( '<a class="dw-option dw-option--more" href="%s" aria-label="%s">+</a>', esc_url( $product->get_permalink() ), esc_attr__( 'See all options', 'dewier' ) );
	}
	echo '</div>';
}

function dewier_card_button( $product, $options, $selected ) {
	if ( ! $product->is_purchasable() && ! $product->is_type( 'variable' ) ) {
		echo dewier_button( __( 'View product', 'dewier' ), $product->get_permalink(), 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	if ( ! $product->is_in_stock() ) {
		echo '<span class="dw-btn dw-btn--add is-disabled" aria-disabled="true"><span>' . esc_html__( 'Out of stock', 'dewier' ) . '</span></span>';
		return;
	}
	if ( $product->is_type( 'variable' ) && ! $options ) {
		echo dewier_button( __( 'Choose options', 'dewier' ), $product->get_permalink(), 'add' ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	$id = $selected ? $selected['variation']->get_id() : $product->get_id();
	printf(
		'<a href="%1$s" class="dw-btn dw-btn--add add_to_cart_button ajax_add_to_cart" data-product_id="%2$d" data-quantity="1" rel="nofollow" aria-label="%3$s"><span>%4$s</span></a>',
		esc_url( $product->get_permalink() ),
		absint( $id ),
		/* translators: %s: product name */
		esc_attr( sprintf( __( 'Add %s to bag', 'dewier' ), $product->get_name() ) ),
		esc_html__( 'Add to bag', 'dewier' )
	);
}

/**
 * Collection pages.
 */
function dewier_loop_setup() {
	remove_action( 'woocommerce_before_shop_loop', 'shoptimizer_sorting_wrapper', 9 );
	remove_action( 'woocommerce_before_shop_loop', 'shoptimizer_sorting_wrapper_close', 31 );
	remove_action( 'woocommerce_before_shop_loop', 'shoptimizer_product_columns_wrapper', 40 );
	remove_action( 'woocommerce_before_shop_loop', 'shoptimizer_woocommerce_pagination', 30 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_after_shop_loop', 'shoptimizer_sorting_wrapper_end', 9 );
	remove_action( 'woocommerce_after_shop_loop', 'shoptimizer_sorting_wrapper_close', 31 );
	remove_action( 'woocommerce_after_shop_loop', 'shoptimizer_product_columns_wrapper_close', 40 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'shoptimizer_before_content', 'shoptimizer_product_cat_banner', 15 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
	add_filter( 'woocommerce_show_page_title', '__return_false' );
	add_action( 'woocommerce_before_shop_loop', 'dewier_collection_bar', 10 );
}
add_action( 'wp', 'dewier_loop_setup' );

add_filter(
	'loop_shop_columns',
	static function () {
		return 4;
	},
	99
);

add_filter(
	'loop_shop_per_page',
	static function () {
		return 22;
	},
	99
);

add_filter(
	'woocommerce_catalog_orderby',
	static function ( $options ) {
		if ( isset( $options['menu_order'] ) ) {
			$options['menu_order'] = __( 'Featured', 'dewier' );
		}
		return $options;
	}
);

/**
 * Sub-category links and sorting above the product grid.
 */
function dewier_collection_bar() {
	$current = is_product_category() ? get_queried_object() : null;
	$parent  = $current ? ( $current->parent ? get_term( $current->parent, 'product_cat' ) : $current ) : null;
	$links   = array();
	$terms   = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent ? $parent->term_id : 0,
			'hide_empty' => true,
			'orderby'    => 'meta_value_num',
			'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		$links[] = array( $term->name, get_term_link( $term ), $current && $current->term_id === $term->term_id );
	}
	if ( $parent ) {
		/* translators: %s: category name */
		$links[] = array( sprintf( __( 'All %s', 'dewier' ), $parent->name ), get_term_link( $parent ), $current->term_id === $parent->term_id );
	} else {
		$links[] = array( __( 'Shop all', 'dewier' ), wc_get_page_permalink( 'shop' ), is_shop() && ! is_search() );
	}
	?>
	<div class="dw-collection-bar">
		<h1 class="screen-reader-text"><?php woocommerce_page_title(); ?></h1>
		<nav class="dw-collection-bar__nav" aria-label="<?php esc_attr_e( 'Categories', 'dewier' ); ?>">
			<?php foreach ( $links as $link ) : ?>
				<a href="<?php echo esc_url( $link[1] ); ?>"<?php echo $link[2] ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo esc_html( $link[0] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="dw-collection-bar__sort">
			<?php woocommerce_catalog_ordering(); ?>
		</div>
	</div>
	<?php
}

/**
 * Promotional tiles placed inside the first page of the grid.
 */
function dewier_grid_tiles() {
	return apply_filters(
		'dewier_grid_tiles',
		array(
			0 => array(
				'type'  => 'promo',
				'big'   => '25%',
				'small' => __( 'Everything*', 'dewier' ),
				'text'  => __( 'Save big with our biggest sale of the year!', 'dewier' ),
				'url'   => wc_get_page_permalink( 'shop' ),
			),
			6 => array(
				'type'  => 'image',
				'image' => DEWIER_URI . '/assets/images/gift.jpg',
				'text'  => __( 'Get a free Barrier Cream with your $100+ order!', 'dewier' ),
				'url'   => wc_get_page_permalink( 'shop' ),
			),
		)
	);
}

function dewier_grid_tile() {
	static $index = 0;
	if ( ! wc_get_loop_prop( 'is_paginated' ) || wc_get_loop_prop( 'current_page' ) > 1 || is_search() ) {
		return;
	}
	$tiles = dewier_grid_tiles();
	if ( isset( $tiles[ $index ] ) ) {
		$tile = $tiles[ $index ];
		echo '<li class="dw-tile dw-tile--' . esc_attr( $tile['type'] ) . '"><a href="' . esc_url( $tile['url'] ) . '">';
		if ( 'promo' === $tile['type'] ) {
			echo '<span class="dw-tile__art" aria-hidden="true"><span class="dw-tile__big">' . esc_html( $tile['big'] ) . '<sup>off</sup></span><span class="dw-tile__small">' . esc_html( $tile['small'] ) . '</span></span>';
		} else {
			echo '<img src="' . esc_url( $tile['image'] ) . '" alt="" loading="lazy">';
		}
		echo '<span class="dw-tile__text">' . esc_html( $tile['text'] ) . '</span></a></li>';
	}
	++$index;
}
add_action( 'woocommerce_shop_loop', 'dewier_grid_tile' );

/**
 * Product page.
 */
function dewier_single_setup() {
	if ( ! is_product() ) {
		return;
	}
	remove_action( 'woocommerce_before_single_product_summary', 'shoptimizer_sticky_single_add_to_cart', 30 );
	add_filter( 'woocommerce_product_single_add_to_cart_text', 'dewier_add_to_bag_text' );
	add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'dewier_variation_swatches', 10, 2 );
	add_filter( 'woocommerce_reset_variations_link', '__return_empty_string' );
	add_filter( 'woocommerce_product_related_products_heading', 'dewier_related_heading' );
	add_filter( 'woocommerce_output_related_products_args', 'dewier_related_args', 99 );
}
add_action( 'wp', 'dewier_single_setup' );

function dewier_add_to_bag_text() {
	return __( 'Add to bag', 'dewier' );
}

function dewier_related_heading() {
	return __( 'You may also like', 'dewier' );
}

function dewier_related_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
}

/**
 * Swatch buttons next to each variation select; the select stays the source of truth.
 */
function dewier_variation_swatches( $html, $args ) {
	$taxonomy = $args['attribute'];
	$product  = $args['product'];
	if ( ! $product || ! taxonomy_exists( $taxonomy ) ) {
		return $html;
	}
	$terms = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );
	if ( ! $terms ) {
		return $html;
	}
	$is_color = '' !== (string) get_term_meta( $terms[0]->term_id, 'dewier_color', true );
	$note     = (string) $product->get_meta( '_dewier_shade_note' );
	$out      = sprintf( '<div class="dw-options dw-options--%s dw-options--pdp" role="radiogroup" aria-label="%s" data-attribute="%s">', $is_color ? 'color' : 'size', esc_attr( wc_attribute_label( $taxonomy ) ), esc_attr( 'attribute_' . sanitize_title( $taxonomy ) ) );
	foreach ( $terms as $term ) {
		if ( ! in_array( $term->slug, $args['options'], true ) ) {
			continue;
		}
		$color = (string) get_term_meta( $term->term_id, 'dewier_color', true );
		$out  .= sprintf(
			'<button type="button" class="dw-option" role="radio" aria-checked="false" data-value="%1$s" data-name="%2$s"%3$s>%4$s</button>',
			esc_attr( $term->slug ),
			esc_attr( $term->name ),
			$is_color ? ' style="--dw-swatch:' . esc_attr( $color ) . '" aria-label="' . esc_attr( $term->name ) . '" title="' . esc_attr( $term->name ) . '"' : '',
			$is_color ? '' : esc_html( $term->name )
		);
	}
	$out .= '</div>';
	if ( $is_color ) {
		$out .= '<p class="dw-option-note" aria-live="polite" data-note="' . esc_attr( $note ) . '"></p>';
	}
	return '<div class="dw-select-hidden">' . $html . '</div>' . $out;
}

/**
 * Add-on rows (cross-sells) on the product page.
 */
function dewier_addons( $product ) {
	$ids = array_slice( $product->get_cross_sell_ids(), 0, 4 );
	if ( ! $ids ) {
		return;
	}
	echo '<section class="dw-addons" aria-labelledby="dw-addons-title"><h2 id="dw-addons-title" class="dw-eyebrow">' . esc_html__( 'Add on', 'dewier' ) . '</h2><ul>';
	foreach ( $ids as $id ) {
		$addon = wc_get_product( $id );
		if ( ! $addon || ! $addon->is_visible() ) {
			continue;
		}
		$option = dewier_selected_option( $addon, dewier_get_options( $addon ) );
		$target = $option ? $option['variation'] : $addon;
		$simple = $option || $addon->is_type( 'simple' );
		echo '<li class="dw-addon">';
		echo '<a class="dw-addon__img" href="' . esc_url( $addon->get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . $addon->get_image( 'woocommerce_thumbnail' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<div class="dw-addon__body"><a class="dw-addon__name" href="' . esc_url( $addon->get_permalink() ) . '">' . esc_html( $addon->get_name() ) . '</a>';
		echo '<span class="dw-addon__sub">' . esc_html( $addon->get_meta( '_dewier_subtitle' ) ) . '</span>';
		if ( $option ) {
			echo '<span class="dw-addon__sub">' . esc_html( wc_attribute_label( $option['taxonomy'] ) . ': ' . $option['name'] ) . '</span>';
		}
		echo '<span class="price">' . wp_kses_post( $target->get_price_html() ) . '</span></div>';
		if ( $simple && $addon->is_in_stock() ) {
			/* translators: %s: product name */
			printf( '<a href="%s" class="dw-addon__add add_to_cart_button ajax_add_to_cart" data-product_id="%d" data-quantity="1" rel="nofollow" aria-label="%s">%s</a>', esc_url( $addon->get_permalink() ), absint( $target->get_id() ), esc_attr( sprintf( __( 'Add %s to bag', 'dewier' ), $option ? $addon->get_name() . ' - ' . $option['name'] : $addon->get_name() ) ), dewier_icon( 'plus' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</li>';
	}
	echo '</ul></section>';
}

/**
 * Accordion content under the product page columns.
 */
function dewier_accordions( $product ) {
	$sections = apply_filters(
		'dewier_product_accordions',
		array(
			__( 'What it is', 'dewier' )       => wpautop( $product->get_description() ),
			__( 'How to use', 'dewier' )       => '<p>' . esc_html__( 'Start with a little, then build. Apply to clean skin with fingertips and blend outward. Layer for more payoff.', 'dewier' ) . '</p>',
			__( 'Ingredients', 'dewier' )      => '<p>' . esc_html__( 'Formulated without parabens, mineral oil or synthetic fragrance. Dermatologist tested. Vegan and cruelty free. See packaging for the full ingredient list.', 'dewier' ) . '</p>',
			__( 'Shipping + returns', 'dewier' ) => '<p>' . esc_html__( 'Free standard shipping on orders over $40. Not quite right? Return it within 30 days for a full refund.', 'dewier' ) . '</p>',
		),
		$product
	);
	echo '<div class="dw-accordions">';
	foreach ( $sections as $title => $content ) {
		if ( ! trim( wp_strip_all_tags( $content ) ) ) {
			continue;
		}
		echo '<details class="dw-accordion"><summary><span>' . esc_html( $title ) . '</span>' . dewier_icon( 'plus' ) . dewier_icon( 'minus' ) . '</summary><div class="dw-accordion__body">' . wp_kses_post( $content ) . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
}

/**
 * Cart page: Dewier heading and free-shipping progress instead of Shoptimizer's step bar.
 */
function dewier_cart_page_setup() {
	remove_action( 'woocommerce_before_cart', 'shoptimizer_cart_progress' );
}
add_action( 'wp', 'dewier_cart_page_setup' );

function dewier_cart_heading() {
	echo '<h1 class="dw-cart__title">' . esc_html__( 'Your bag', 'dewier' ) . '</h1>';
}
add_action( 'woocommerce_before_cart', 'dewier_cart_heading', 5 );
add_action( 'woocommerce_before_cart_totals', 'dewier_cart_progress', 5 );

