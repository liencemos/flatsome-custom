<?php
/**
 * Shop and category pages: collection banner, sub-collection tiles and toolbar.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp',
	function () {
		if ( ! function_exists( 'is_woocommerce' ) || ! ( is_shop() || is_product_taxonomy() ) ) {
			return;
		}
		// Replace Flatsome's title bar with the collection banner; keep its filter button and controls.
		remove_action( 'flatsome_after_header', 'flatsome_category_header' );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		add_action( 'flatsome_after_header', 'cf_collection_banner', 20 );
		add_action( 'woocommerce_before_shop_loop', 'cf_archive_toolbar', 5 );
	}
);

function cf_collection_banner() {
	$term     = is_product_taxonomy() ? get_queried_object() : null;
	$title    = $term ? $term->name : ( is_search() ? sprintf( /* translators: %s: search term. */ __( 'Results for “%s”', 'comfa' ), get_search_query() ) : __( 'Shop All', 'comfa' ) );
	$children = array();
	$parent   = 0;
	if ( $term && 'product_cat' === $term->taxonomy ) {
		$parent = $term->term_id;
	}
	if ( ! $term || 'product_cat' === $term->taxonomy ) {
		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent,
				'hide_empty' => true,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				'number'     => 4,
			)
		);
		if ( is_wp_error( $children ) ) {
			$children = array();
		}
	}
	echo '<div class="cf-collection-banner"><div class="container">';
	echo '<nav class="cf-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'comfa' ) . '">';
	echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Shop', 'comfa' ) . '</a>';
	if ( $term && 'product_cat' === $term->taxonomy ) {
		foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				echo ' / <a href="' . esc_url( get_term_link( $ancestor ) ) . '">' . esc_html( $ancestor->name ) . '</a>';
			}
		}
	}
	echo '</nav>';
	echo '<h1 class="cf-collection-title">' . esc_html( $title ) . '</h1>';
	if ( $term && '' !== $term->description && ! $children ) {
		echo '<div class="cf-collection-intro">' . wp_kses_post( wpautop( $term->description ) ) . '</div>';
	}
	if ( $children ) {
		echo '<ul class="cf-subcollections">';
		foreach ( $children as $child ) {
			$image_id = (int) get_term_meta( $child->term_id, 'thumbnail_id', true );
			echo '<li><a href="' . esc_url( get_term_link( $child ) ) . '">';
			echo $image_id ? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'loading' => 'eager' ) ) : wc_placeholder_img( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<strong>' . esc_html( $child->name ) . '</strong>';
			if ( '' !== $child->description ) {
				echo '<span>' . esc_html( wp_strip_all_tags( $child->description ) ) . '</span>';
			}
			echo '</a></li>';
		}
		echo '</ul>';
	}
	echo '</div></div>';
}

function cf_archive_toolbar() {
	// Flatsome sets the loop up inside the title bar this theme replaces.
	wc_setup_loop();
	if ( ! wc_get_loop_prop( 'is_paginated' ) || ! woocommerce_products_will_display() ) {
		return;
	}
	$total = (int) wc_get_loop_prop( 'total' );
	echo '<div class="cf-toolbar">';
	/* translators: %s: number of products. */
	echo '<p class="cf-toolbar__count">' . esc_html( sprintf( _n( '%s Item', '%s Items', $total, 'comfa' ), number_format_i18n( $total ) ) ) . '</p>';
	wc_get_template_part( 'loop/filter-button' );
	echo '<div class="cf-toolbar__sort">';
	woocommerce_catalog_ordering();
	echo '</div></div>';
}
