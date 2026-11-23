<?php
/**
 * Small shortcodes used in the header and UX Builder content.
 */

defined( 'ABSPATH' ) || exit;

// Two-line promo for the top bar (Theme Options > Header > Top Bar > HTML).
add_shortcode(
	'cf_promo',
	function () {
		return '<div class="cf-promo"><strong>' . esc_html( cf_setting( 'promo_line_1' ) ) . '</strong><span>' . esc_html( cf_setting( 'promo_line_2' ) ) . '</span></div>';
	}
);

add_shortcode(
	'cf_year',
	function () {
		return esc_html( wp_date( 'Y' ) );
	}
);

// Footer link to the page chosen in Settings > Privacy; hidden until that page is published.
add_shortcode(
	'cf_privacy_link',
	function () {
		$url = get_privacy_policy_url();
		return $url ? ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Privacy', 'comfa' ) . '</a>' : '';
	}
);

// Product names and totals for the giving counter can be edited in UX Builder; this keeps one number in sync.
add_shortcode(
	'cf_given_total',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'base' => 0 ), $atts, 'cf_given_total' );
		return esc_html( number_format_i18n( (int) $atts['base'] + (int) get_option( 'cf_given_total', 0 ) ) );
	}
);

add_action(
	'woocommerce_order_status_completed',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_meta( '_cf_given_counted' ) ) {
			return;
		}
		$given = 0;
		foreach ( $order->get_items() as $item ) {
			$pack   = $item->get_meta( 'pa_pack' );
			$given += (int) $item->get_quantity() * ( $pack ? cf_pack_count( get_term_by( 'slug', $pack, 'pa_pack' ) ) : 1 );
		}
		update_option( 'cf_given_total', (int) get_option( 'cf_given_total', 0 ) + $given, false );
		$order->update_meta_data( '_cf_given_counted', $given );
		$order->save();
	}
);

// Scrolling strip: [cf_ticker text="First phrase|Second phrase"]. The second copy only fills the loop.
add_shortcode(
	'cf_ticker',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'text' => '' ), $atts, 'cf_ticker' );
		$items = array_filter( array_map( 'trim', explode( '|', $atts['text'] ) ) );
		if ( ! $items ) {
			return '';
		}
		$group = '';
		foreach ( $items as $item ) {
			$group .= '<span class="cf-ticker__item">' . esc_html( $item ) . '</span>';
		}
		return '<div class="cf-ticker__track"><div class="cf-ticker__group">' . $group . '</div><div class="cf-ticker__group" aria-hidden="true">' . $group . '</div></div>';
	}
);

// Colour circles linking to the shop filtered by that colour: [cf_color_nav].
add_shortcode(
	'cf_color_nav',
	function () {
		// Term counts include products the shop hides (catalog visibility, out of stock), so list by visible products.
		$ids   = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'tax_query'      => WC()->query->get_tax_query(),
			)
		);
		$terms = $ids ? get_terms( array( 'taxonomy' => 'pa_color', 'object_ids' => $ids ) ) : array();
		if ( is_wp_error( $terms ) || ! $terms ) {
			return '';
		}
		$html = '';
		foreach ( $terms as $term ) {
			$url   = add_query_arg( 'filter_color', $term->slug, wc_get_page_permalink( 'shop' ) );
			$html .= '<li><a href="' . esc_url( $url ) . '"><span class="cf-color-nav__dot" style="background-color:' . esc_attr( sanitize_hex_color( get_term_meta( $term->term_id, 'ux_color', true ) ) ) . '"></span>' . esc_html( $term->name ) . '</a></li>';
		}
		return '<ul class="cf-color-nav">' . $html . '</ul>';
	}
);
