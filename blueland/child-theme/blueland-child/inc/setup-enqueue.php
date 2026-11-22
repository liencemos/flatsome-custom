<?php
/**
 * Theme support and front-end assets.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'bl-google-fonts',
			'https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=DM+Sans:wght@400;500;700&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'blueland-child', BL_CHILD_URL . '/assets/css/blueland.css', array(), (string) filemtime( BL_CHILD_DIR . '/assets/css/blueland.css' ) );
		wp_enqueue_script( 'blueland-child', BL_CHILD_URL . '/assets/js/blueland.js', array(), (string) filemtime( BL_CHILD_DIR . '/assets/js/blueland.js' ), true );
		wp_localize_script(
			'blueland-child',
			'blStore',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bl_storefront' ),
				'cartUrl' => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
			)
		);
	}
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_product() ) {
			$classes[] = 'bl-product-page';
		}
		return $classes;
	}
);

add_filter(
	'default_page_template',
	function () {
		return 'page-templates/blueland-content.php';
	}
);
