<?php
/**
 * Pureland child theme bootstrap.
 */

defined( 'ABSPATH' ) || exit;

define( 'BL_CHILD_VERSION', '1.0.0' );
define( 'BL_CHILD_DIR', get_stylesheet_directory() );
define( 'BL_CHILD_URL', get_stylesheet_directory_uri() );

foreach ( array( 'setup-enqueue', 'performance', 'header', 'woocommerce-loop', 'single-product', 'review-media-qa', 'stock-alerts', 'gift-card-wrap', 'cart', 'free-shipping-progress', 'mobile-navigation', 'mobile-filter-sheet', 'email-popup', 'shortcodes' ) as $bl_file ) {
	require_once BL_CHILD_DIR . '/inc/' . $bl_file . '.php';
}
