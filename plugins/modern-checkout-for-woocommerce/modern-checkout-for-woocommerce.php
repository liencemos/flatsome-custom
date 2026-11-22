<?php
/**
 * Plugin Name: Modern Checkout for WooCommerce
 * Description: A theme-independent, three-step WooCommerce checkout.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 11.3.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: modern-checkout-for-woocommerce
 * Domain Path: /languages
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'MCWC_VERSION', '1.0.0' );
define( 'MCWC_FILE', __FILE__ );
define( 'MCWC_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCWC_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MCWC_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', MCWC_FILE, false );
		}
	}
);

function mcwc_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once MCWC_DIR . 'includes/class-mcwc-plugin.php';
	MCWC_Plugin::init();
}

function mcwc_load_textdomain() {
	load_plugin_textdomain( 'modern-checkout-for-woocommerce', false, dirname( plugin_basename( MCWC_FILE ) ) . '/languages' );
}

add_action( 'init', 'mcwc_load_textdomain', 0 );
add_action( 'plugins_loaded', 'mcwc_bootstrap', 20 );

function mcwc_restore_checkout_content() {
	$previous = get_option( 'mcwc_previous_checkout_content', false );
	if ( is_array( $previous ) && ! empty( $previous['page_id'] ) && isset( $previous['content'] ) ) {
		$page = get_post( absint( $previous['page_id'] ) );
		if ( $page && '[woocommerce_checkout]' === $page->post_content && is_string( $previous['content'] ) ) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => wp_slash( $previous['content'] ),
				)
			);
		}
	}
	delete_option( 'mcwc_previous_checkout_content' );
}
register_deactivation_hook( MCWC_FILE, 'mcwc_restore_checkout_content' );
