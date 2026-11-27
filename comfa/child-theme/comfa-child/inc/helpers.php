<?php
/**
 * Shared catalog helpers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Number of items in a pack term ("3-Pack" => 3, "Single" => 1).
 */
function cf_pack_count( $term ) {
	if ( ! $term ) {
		return 1;
	}
	$count = is_object( $term ) ? (int) get_term_meta( $term->term_id, 'cf_pack_count', true ) : 0;
	if ( $count > 0 ) {
		return $count;
	}
	$label = is_object( $term ) ? $term->name : (string) $term;
	return preg_match( '/(\d+)/', $label, $match ) ? max( 1, (int) $match[1] ) : 1;
}

/**
 * The variation matching the product's default attributes, or the cheapest one.
 */
function cf_default_variation( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return null;
	}
	$cache_key = 'cf_default_variation_' . $product->get_id();
	$cached    = wp_cache_get( $cache_key, 'comfa' );
	if ( false !== $cached ) {
		return $cached ? wc_get_product( $cached ) : null;
	}
	$defaults = $product->get_default_attributes();
	$match    = 0;
	if ( $defaults ) {
		$attributes = array();
		foreach ( $defaults as $name => $value ) {
			$attributes[ 'attribute_' . $name ] = $value;
		}
		foreach ( $product->get_attributes() as $name => $attribute ) {
			if ( $attribute->get_variation() && ! isset( $attributes[ 'attribute_' . $name ] ) ) {
				$attributes[ 'attribute_' . $name ] = '';
			}
		}
		$match = ( new WC_Product_Data_Store_CPT() )->find_matching_product_variation( $product, $attributes );
	}
	if ( ! $match ) {
		$prices = $product->get_variation_prices();
		$match  = $prices['price'] ? (int) key( $prices['price'] ) : 0;
	}
	wp_cache_set( $cache_key, (int) $match, 'comfa' );
	return $match ? wc_get_product( $match ) : null;
}

/**
 * Savings label for a sale price, e.g. "10% Pack Savings".
 */
function cf_savings_label( $product, $pack_count = 1 ) {
	if ( ! $product || ! $product->is_on_sale() ) {
		return '';
	}
	$regular = (float) $product->get_regular_price();
	$price   = (float) $product->get_price();
	if ( $regular <= 0 || $price >= $regular ) {
		return '';
	}
	$percent = (int) round( ( $regular - $price ) / $regular * 100 );
	/* translators: %d: discount percentage. */
	return sprintf( $pack_count > 1 ? __( '%d%% Pack Savings', 'comfa' ) : __( '%d%% Savings', 'comfa' ), $percent );
}

/**
 * Price line for cards and the product page: price, struck regular price and savings.
 */
function cf_price_line( $product ) {
	$target = $product;
	$count  = 1;
	if ( $product->is_type( 'variable' ) ) {
		$variation = cf_default_variation( $product );
		if ( $variation ) {
			$target = $variation;
			$attributes = $variation->get_attributes();
			$count      = ! empty( $attributes['pa_pack'] ) ? cf_pack_count( get_term_by( 'slug', $attributes['pa_pack'], 'pa_pack' ) ) : 1;
		}
	}
	$html = '';
	// A design sold on several garments has no single price until a product and size are picked.
	if ( cf_style_count( $product ) > 1 && $product->get_variation_price( 'min' ) !== $product->get_variation_price( 'max' ) ) {
		return '<span class="cf-price__from">' . esc_html__( 'From', 'comfa' ) . '</span> <span class="cf-price__now">' . wc_price( $product->get_variation_price( 'min', true ) ) . '</span>';
	}
	$html .= '<span class="cf-price__now">' . wc_price( wc_get_price_to_display( $target ) ) . '</span>';
	if ( $target->is_on_sale() ) {
		$html .= ' <del class="cf-price__was">' . wc_price( wc_get_price_to_display( $target, array( 'price' => $target->get_regular_price() ) ) ) . '</del>';
		$html .= ' <span class="cf-price__save">' . esc_html( cf_savings_label( $target, $count ) ) . '</span>';
	}
	return $html;
}

/**
 * Number of products (Product attribute terms) a design is sold on; 0 when Product is not one of its variation attributes.
 */
function cf_style_count( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return 0;
	}
	$attributes = $product->get_variation_attributes();
	return isset( $attributes['pa_style'] ) ? count( $attributes['pa_style'] ) : 0;
}
add_action(
	'woocommerce_delete_product_transients',
	function ( $product_id ) {
		if ( $product_id ) {
			wp_cache_delete( 'cf_default_variation_' . $product_id, 'comfa' );
		}
	}
);

/**
 * "Unisex · Midweight" style meta line.
 */
function cf_product_meta_line( $product ) {
	$parts  = array();
	$styles = cf_style_count( $product );
	if ( $styles > 1 ) {
		/* translators: %s: number of garments a design is printed on. */
		$parts[] = sprintf( _n( '%s product', '%s products', $styles, 'comfa' ), number_format_i18n( $styles ) );
		$options = $product->get_variation_attributes();
		$colors  = isset( $options['pa_color'] ) ? $options['pa_color'] : array();
		if ( count( $colors ) > 1 ) {
			/* translators: %s: number of colours. */
			$parts[] = sprintf( _n( '%s colour', '%s colours', count( $colors ), 'comfa' ), number_format_i18n( count( $colors ) ) );
		}
	}
	foreach ( array( 'pa_fit', 'pa_weight' ) as $taxonomy ) {
		$names = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $names ) && $names ) {
			$parts[] = $names[0];
		}
	}
	return implode( ' · ', $parts );
}
