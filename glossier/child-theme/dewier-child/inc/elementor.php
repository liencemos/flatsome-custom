<?php
/**
 * Elementor widgets for the storefront sections.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_elementor_category( $manager ) {
	$manager->add_category( 'dewier', array( 'title' => __( 'Meredithzone', 'dewier' ) ) );
}
add_action( 'elementor/elements/categories_registered', 'dewier_elementor_category' );

function dewier_elementor_widgets( $manager ) {
	require_once DEWIER_DIR . '/inc/elementor/widgets.php';
	foreach ( array( 'Hero', 'Gift', 'Products', 'Promo_Cards', 'Story', 'Carousel', 'Help_Search', 'Faq', 'Help_Contact' ) as $name ) {
		$class = 'Dewier_Widget_' . $name;
		$manager->register( new $class() );
	}
}
add_action( 'elementor/widgets/register', 'dewier_elementor_widgets' );

/**
 * Elementor containers hold full-bleed sections, so they get no default padding.
 */
function dewier_elementor_editor_styles() {
	wp_enqueue_style( 'dewier', DEWIER_URI . '/assets/css/dewier.css', array(), dewier_asset_version( '/assets/css/dewier.css' ) );
}
add_action( 'elementor/preview/enqueue_styles', 'dewier_elementor_editor_styles' );

/**
 * Carousel shell shared by the product and image carousels.
 */
function dewier_carousel_open( $modifier, $label ) {
	printf( '<div class="dw-carousel dw-carousel--%s" role="region" aria-roledescription="carousel" aria-label="%s"><div class="dw-carousel__track" tabindex="0">', esc_attr( $modifier ), esc_attr( $label ) );
}

function dewier_carousel_close() {
	echo '</div>';
	printf( '<button type="button" class="dw-btn dw-btn--icon dw-carousel__arrow dw-carousel__arrow--prev" data-dir="-1" aria-label="%s" hidden>%s</button>', esc_attr__( 'Previous', 'dewier' ), dewier_icon( 'prev' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	printf( '<button type="button" class="dw-btn dw-btn--icon dw-carousel__arrow dw-carousel__arrow--next" data-dir="1" aria-label="%s" hidden>%s</button>', esc_attr__( 'Next', 'dewier' ), dewier_icon( 'next' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="dw-carousel__progress" aria-hidden="true"><span></span></div></div>';
}
