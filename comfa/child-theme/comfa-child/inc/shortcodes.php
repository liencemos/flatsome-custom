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
