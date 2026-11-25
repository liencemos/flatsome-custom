<?php
/**
 * Meredithzone child theme for Shoptimizer.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DEWIER_VERSION', '1.0.0' );
define( 'DEWIER_DIR', get_stylesheet_directory() );
define( 'DEWIER_URI', get_stylesheet_directory_uri() );

require_once DEWIER_DIR . '/inc/setup.php';
require_once DEWIER_DIR . '/inc/layout.php';
require_once DEWIER_DIR . '/inc/woocommerce.php';
require_once DEWIER_DIR . '/inc/checkout.php';
require_once DEWIER_DIR . '/inc/commercekit.php';
require_once DEWIER_DIR . '/inc/elementor.php';
require_once DEWIER_DIR . '/inc/newsletter.php';
require_once DEWIER_DIR . '/inc/reviews.php';
require_once DEWIER_DIR . '/inc/recent.php';
require_once DEWIER_DIR . '/inc/quiz.php';
