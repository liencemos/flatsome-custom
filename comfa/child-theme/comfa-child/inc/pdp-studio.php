<?php

defined( 'ABSPATH' ) || exit;

function cf_studio_active( $product_id ) {
	if ( ! function_exists( 'flatsome_product_block' ) ) {
		return false;
	}
	$layout = flatsome_product_block( $product_id );
	return ! empty( $layout['id'] ) && has_shortcode( (string) get_post_field( 'post_content', $layout['id'] ), 'cf_studio_deal' );
}

add_action( 'wp_enqueue_scripts', function () {
	$id = get_queried_object_id();
	if ( ! ( ( is_singular( 'product' ) && cf_studio_active( $id ) ) || ( is_singular( 'blocks' ) && has_shortcode( (string) get_post_field( 'post_content', $id ), 'cf_studio_deal' ) ) ) ) {
		return;
	}
	foreach ( array( 'css', 'js' ) as $type ) {
		$path = "/assets/$type/pdp-studio.$type";
		if ( 'css' === $type ) {
			wp_enqueue_style( 'cf-pdp-studio', CF_URL . $path, array( 'comfa-child' ), filemtime( CF_DIR . $path ) );
		} else {
			wp_enqueue_script( 'cf-pdp-studio', CF_URL . $path, array( 'jquery' ), filemtime( CF_DIR . $path ), true );
		}
	}
}, 30 );

add_action( 'woocommerce_after_single_product', function () {
	if ( is_product() && cf_studio_active( get_queried_object_id() ) ) {
		cf_product_reviews();
	}
} );

function cf_studio_product() {
	global $product;
	return $product instanceof WC_Product ? $product : null;
}

function cf_studio_deal_markup( $product ) {
	$price   = wc_get_price_to_display( $product );
	$regular = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
	$sale    = $product->is_on_sale() && $regular > $price;
	$html    = '<p class="price cf-studio-deal__main">' . wc_price( $price ) . $product->get_price_suffix();
	if ( $sale ) {
		$html .= ' <small>' . esc_html( sprintf( __( '%s%% OFF', 'comfa' ), round( 100 * ( $regular - $price ) / $regular ) ) ) . '</small>';
	}
	$html .= '</p>';
	if ( $sale ) {
		$html .= '<p>' . esc_html__( 'Was', 'comfa' ) . ' <del>' . wc_price( $regular ) . '</del> <span aria-hidden="true">·</span> <strong>' . esc_html__( 'You save', 'comfa' ) . ' ' . wc_price( $regular - $price ) . '</strong> <small>' . esc_html__( '* Compared with the regular price', 'comfa' ) . '</small></p>';
	}
	return $html;
}

function cf_studio_deal() {
	$product = cf_studio_product();
	if ( ! $product ) {
		return '';
	}
	$item = $product->is_type( 'variable' ) ? cf_default_variation( $product ) : $product;
	return '<div class="price-wrapper cf-studio-deal" aria-live="polite">' . ( $item ? cf_studio_deal_markup( $item ) : wp_kses_post( $product->get_price_html() ) ) . '</div>';
}

add_filter( 'woocommerce_available_variation', function ( $data, $product, $variation ) {
	$on_product = is_product() && $product->get_id() === get_queried_object_id();
	$ajax_match = wp_doing_ajax() && isset( $_GET['wc-ajax'] ) && 'get_variation' === $_GET['wc-ajax']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ( $on_product || $ajax_match ) && cf_studio_active( $product->get_id() ) ) {
		$data['cf_studio_deal'] = cf_studio_deal_markup( $variation );
	}
	return $data;
}, 25, 3 );

function cf_studio_artist( $atts ) {
	$product = cf_studio_product();
	if ( ! $product ) {
		return '';
	}
	$atts = shortcode_atts( array( 'byline' => 'Designed by', 'artist' => '', 'artist_url' => '', 'follow' => 'Follow', 'follow_url' => '' ), $atts );
	$artist = esc_html( $atts['artist'] ?: get_bloginfo( 'name' ) );
	$url    = $atts['artist_url'] ?: home_url( '/mission/' );
	$rating = (float) $product->get_average_rating();
	$count  = $product->get_review_count();
	$html   = '<div><p>' . esc_html( $atts['byline'] ) . ' <a href="' . esc_url( $url ) . '">' . $artist . '</a>';
	if ( $atts['follow_url'] && $atts['follow'] ) {
		$html .= ' · <a class="cf-studio-follow" href="' . esc_url( $atts['follow_url'] ) . '">' . esc_html( $atts['follow'] ) . '</a>';
	}
	$html .= '</p>';
	if ( $count && comments_open( $product->get_id() ) ) {
		$html .= '<a href="#cf-reviews">' . wc_get_rating_html( $rating, $count ) . ' ' . esc_html( sprintf( _n( '%1$s (%2$s review)', '%1$s (%2$s reviews)', $count, 'comfa' ), number_format_i18n( $rating, 1 ), number_format_i18n( $count ) ) ) . '</a>';
	}
	return $html . '</div>';
}

function cf_studio_wishlist() {
	$product = cf_studio_product();
	if ( ! $product || ! shortcode_exists( 'yith_wcwl_add_to_wishlist' ) ) {
		return '';
	}
	return '<div class="cf-studio-wishlist">' . do_shortcode( '[yith_wcwl_add_to_wishlist product_id="' . (int) $product->get_id() . '"]' ) . '</div>';
}

function cf_studio_features() {
	$product = cf_studio_product();
	return $product ? wp_kses_post( wpautop( $product->get_description() ) ) : '';
}

function cf_studio_region( $atts ) {
	$atts = shortcode_atts( array( 'label' => 'Shipping destination:' ), $atts );
	$country = WC()->customer ? WC()->customer->get_shipping_country() : '';
	$countries = WC()->countries->get_countries();
	return isset( $countries[ $country ] ) ? '<p class="cf-studio-region">' . esc_html( $atts['label'] . ' ' . $countries[ $country ] ) . '</p>' : '';
}

function cf_studio_elements() {
	return array(
		'deal'     => array( 'Studio Price & Savings', array() ),
		'artist'   => array( 'Studio Artist & Rating', array( 'byline' => 'Designed by', 'artist' => '', 'artist_url' => '', 'follow' => 'Follow', 'follow_url' => '' ) ),
		'wishlist' => array( 'Studio Wishlist', array() ),
		'features' => array( 'Studio Product Features', array() ),
		'region'   => array( 'Studio Shipping Region', array( 'label' => 'Shipping destination:' ) ),
	);
}

foreach ( cf_studio_elements() as $cf_studio_key => $cf_studio_element ) {
	add_shortcode( 'cf_studio_' . $cf_studio_key, 'cf_studio_' . $cf_studio_key );
}

add_action( 'ux_builder_setup', function () {
	foreach ( cf_studio_elements() as $key => list( $name, $fields ) ) {
		$options = array();
		foreach ( $fields as $field => $default ) {
			$options[ $field ] = array( 'type' => 'textfield', 'heading' => ucwords( str_replace( '_', ' ', $field ) ), 'default' => $default );
		}
		add_ux_builder_shortcode( 'cf_studio_' . $key, array( 'name' => $name, 'category' => 'Product Page', 'options' => $options ) );
	}
} );
