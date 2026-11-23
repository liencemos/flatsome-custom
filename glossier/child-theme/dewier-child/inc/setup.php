<?php
/**
 * Assets, theme options and menus.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_asset_version( $path ) {
	$file = DEWIER_DIR . $path;
	return file_exists( $file ) ? (string) filemtime( $file ) : DEWIER_VERSION;
}

function dewier_enqueue_assets() {
	wp_enqueue_style( 'dewier', DEWIER_URI . '/assets/css/dewier.css', array(), dewier_asset_version( '/assets/css/dewier.css' ) );
	wp_enqueue_script( 'dewier', DEWIER_URI . '/assets/js/dewier.js', array(), dewier_asset_version( '/assets/js/dewier.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'dewier_enqueue_assets', 100 );

function dewier_preload_fonts() {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( DEWIER_URI . '/assets/fonts/dm-sans-latin-wght-normal.woff2' ) );
}
add_action( 'wp_head', 'dewier_preload_fonts', 1 );

function dewier_register_menus() {
	register_nav_menus(
		array(
			'dewier_footer_help'   => __( 'Footer: Help', 'dewier' ),
			'dewier_footer_about'  => __( 'Footer: About', 'dewier' ),
			'dewier_footer_stores' => __( 'Footer: Stores', 'dewier' ),
			'dewier_footer_social' => __( 'Footer: Social', 'dewier' ),
			'dewier_footer_legal'  => __( 'Footer: Legal', 'dewier' ),
		)
	);
}
add_action( 'after_setup_theme', 'dewier_register_menus', 20 );

/**
 * Shoptimizer options the design depends on.
 */
function dewier_theme_options() {
	return array(
		'shoptimizer_layout_woocommerce_enable_sidebar_cart'  => true,
		'shoptimizer_layout_floating_button_display'          => false,
		'shoptimizer_layout_woocommerce_single_product_ajax'  => false,
		'shoptimizer_layout_woocommerce_sticky_cart_display'  => false,
		'shoptimizer_layout_woocommerce_prev_next_display'    => false,
		'shoptimizer_layout_woocommerce_display_breadcrumbs'  => false,
		'shoptimizer_layout_woocommerce_sidebar'              => 'no-woocommerce-sidebar',
		'shoptimizer_layout_shop_title'                       => false,
	);
}

function dewier_apply_theme_options() {
	foreach ( dewier_theme_options() as $key => $value ) {
		add_filter(
			'theme_mod_' . $key,
			static function () use ( $value ) {
				return $value;
			}
		);
	}
}
dewier_apply_theme_options();

function dewier_body_class( $classes ) {
	$classes[] = 'dewier';
	return $classes;
}
add_filter( 'body_class', 'dewier_body_class' );

/**
 * Inline SVG icons.
 */
function dewier_icon( $name ) {
	$icons = array(
		'search' => '<circle cx="7" cy="7" r="5.2"/><path d="m11 11 4 4"/>',
		'prev'   => '<path d="M10 3.5 5.5 8l4.5 4.5"/>',
		'next'   => '<path d="M6 3.5 10.5 8 6 12.5"/>',
		'pause'  => '<path d="M5.5 3v10M10.5 3v10"/>',
		'play'   => '<path d="M5 3v10l8-5z"/>',
		'close'  => '<path d="m3.5 3.5 9 9m0-9-9 9"/>',
		'plus'   => '<path d="M8 3v10M3 8h10"/>',
		'minus'  => '<path d="M3 8h10"/>',
		'menu'   => '<path d="M2 4.5h12M2 8h12M2 11.5h12"/>',
		'globe'  => '<circle cx="8" cy="8" r="6"/><path d="M2 8h12M8 2c2 2 2 10 0 12M8 2c-2 2-2 10 0 12"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return '<svg class="dw-icon" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
}

/**
 * Button markup shared by templates and Elementor widgets.
 */
function dewier_button( $label, $url, $variant = 'primary', $attrs = '' ) {
	return sprintf( '<a class="dw-btn dw-btn--%s" href="%s"%s><span>%s</span></a>', esc_attr( $variant ), esc_url( $url ), $attrs ? ' ' . $attrs : '', esc_html( $label ) );
}
