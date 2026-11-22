<?php
/**
 * Mobile sidebar category tiles and utility links.
 */

defined( 'ABSPATH' ) || exit;

function bl_mobile_category_tiles() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'orderby'    => 'term_order',
			'order'      => 'ASC',
			'number'     => 8,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	echo '<nav class="bl-mobile-category-nav" aria-label="Shop by category"><h2>Shop by Category</h2><div class="bl-mobile-category-tiles">';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$image_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		$image    = $image_id
			? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'alt' => $term->name, 'loading' => 'lazy', 'decoding' => 'async' ) )
			: '<img src="' . esc_url( wc_placeholder_img_src( 'woocommerce_thumbnail' ) ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy" decoding="async">';
		echo '<a class="bl-mobile-category-tile" href="' . esc_url( $link ) . '">' . $image . '<span>' . esc_html( $term->name ) . '</span></a>';
	}
	echo '</div></nav>';
}
add_action( 'flatsome_before_sidebar_menu_elements', 'bl_mobile_category_tiles', 5 );

function bl_mobile_sidebar_quick_links() {
	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$wishlist    = get_page_by_path( 'wishlist' );
	$contact     = get_page_by_path( 'contact' );
	$wishlist_url = $wishlist ? get_permalink( $wishlist ) : home_url( '/wishlist/' );
	$contact_url  = $contact ? get_permalink( $contact ) : home_url( '/contact/' );

	echo '<nav class="bl-mobile-quick-links" aria-label="Quick links"><a href="' . esc_url( $account_url ) . '">Account</a><a href="' . esc_url( $wishlist_url ) . '">Wishlist</a><a href="' . esc_url( $contact_url ) . '">Contact</a></nav>';
}
add_action( 'flatsome_after_sidebar_menu_elements', 'bl_mobile_sidebar_quick_links', 20 );
