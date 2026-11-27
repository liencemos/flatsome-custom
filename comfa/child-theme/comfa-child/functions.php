<?php
/**
 * Comfa child theme bootstrap.
 */

defined( 'ABSPATH' ) || exit;

define( 'CF_VERSION', '1.0.0' );
define( 'CF_DIR', get_stylesheet_directory() );
define( 'CF_URL', get_stylesheet_directory_uri() );

foreach ( array( 'setup', 'helpers', 'loop', 'archive', 'product', 'pdp-studio', 'cart', 'checkout', 'newsletter', 'ip-claim', 'shortcodes' ) as $cf_file ) {
	require_once CF_DIR . '/inc/' . $cf_file . '.php';
}
