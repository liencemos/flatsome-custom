<?php
/**
 * CommerceKit modules wired into the Dewier templates.
 *
 * The Dewier card and product templates skip the WooCommerce hooks CommerceKit
 * attaches to, so its output is called from the Dewier action points instead.
 * Each module still switches on and off in CommerceKit's own settings.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_commercekit_setup() {
	if ( function_exists( 'commercekit_after_shop_loop_item_wishlist' ) ) {
		add_action( 'dewier_card_media', 'commercekit_after_shop_loop_item_wishlist' );
		add_action( 'dewier_pdp_after_buy', 'commercekit_single_product_wishlist', 20 );
		add_action( 'dewier_header_utils', 'dewier_commercekit_wishlist_link' );
		add_action( 'dewier_nav_after', 'dewier_commercekit_wishlist_link' );
	}
	if ( function_exists( 'commercekit_display_inventory_counter' ) ) {
		add_action( 'dewier_pdp_after_buy', 'commercekit_display_inventory_counter', 10 );
	}
	if ( function_exists( 'commercekit_sticky_atc_bar_wrappers' ) ) {
		add_action( 'dewier_pdp_before', 'dewier_commercekit_sticky_bar' );
		add_filter( 'commercekit_sticky_header_css_class', 'dewier_commercekit_sticky_header' );
	}
}
add_action( 'after_setup_theme', 'dewier_commercekit_setup' );

/**
 * CommerceKit's sticky bar, with the product name in place of the WooCommerce
 * tab links (the Dewier product page has no tabs) and the Dewier button label.
 */
function dewier_commercekit_sticky_bar( $product ) {
	add_filter( 'gettext_woocommerce', 'dewier_commercekit_bag_label', 10, 2 );
	ob_start();
	commercekit_sticky_atc_bar_wrappers();
	$bar = ob_get_clean();
	remove_filter( 'gettext_woocommerce', 'dewier_commercekit_bag_label', 10 );
	echo str_replace( '<ul class="commercekit-atc-tab-links">', '<span class="dw-sticky-name">' . esc_html( $product->get_name() ) . '</span><ul class="commercekit-atc-tab-links">', $bar ); // phpcs:ignore WordPress.Security.EscapeOutput
}

function dewier_commercekit_bag_label( $translation, $text ) {
	return 'Add to cart' === $text ? __( 'Add to bag', 'dewier' ) : $translation;
}

function dewier_commercekit_sticky_header() {
	return 'header.dw-header';
}

function dewier_commercekit_wishlist_link() {
	$options = function_exists( 'commercekit_get_options' ) ? commercekit_get_options() : array();
	$page    = isset( $options['wsl_page'] ) ? (int) $options['wsl_page'] : 0;
	if ( ! $page || 'publish' !== get_post_status( $page ) ) {
		return;
	}
	// The menu drawer copy replaces the header link on phones, where the header has no room for it.
	$class = doing_action( 'dewier_nav_after' ) ? 'dw-nav__extra' : 'dw-utils__link dw-utils__link--wishlist';
	printf( '<a class="%s" href="%s">%s</a>', esc_attr( $class ), esc_url( get_permalink( $page ) ), esc_html__( 'Wishlist', 'dewier' ) );
}
