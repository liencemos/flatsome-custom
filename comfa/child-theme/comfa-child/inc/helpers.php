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
	$html = '<span class="cf-price__now">' . wc_price( wc_get_price_to_display( $target ) ) . '</span>';
	if ( $target->is_on_sale() ) {
		$html .= ' <del class="cf-price__was">' . wc_price( wc_get_price_to_display( $target, array( 'price' => $target->get_regular_price() ) ) ) . '</del>';
		$html .= ' <span class="cf-price__save">' . esc_html( cf_savings_label( $target, $count ) ) . '</span>';
	}
	return $html;
}

/**
 * "Unisex · Midweight" style meta line.
 */
function cf_product_meta_line( $product ) {
	$parts = array();
	foreach ( array( 'pa_fit', 'pa_weight' ) as $taxonomy ) {
		$names = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $names ) && $names ) {
			$parts[] = $names[0];
		}
	}
	return implode( ' · ', $parts );
}
