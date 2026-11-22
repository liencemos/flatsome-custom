<?php
/**
 * Classic checkout block conversion and notices.
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

function mcwc_get_checkout_page() {
	$page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
	return $page_id > 0 ? get_post( $page_id ) : null;
}

function mcwc_checkout_page_uses_block() {
	$page = mcwc_get_checkout_page();
	return $page && function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $page );
}

function mcwc_switch_checkout_block_to_shortcode() {
	if ( ! mcwc_is_enabled() ) {
		return;
	}
	$page = mcwc_get_checkout_page();
	if ( ! $page || ! has_block( 'woocommerce/checkout', $page ) ) {
		return;
	}
	if ( false === get_option( 'mcwc_previous_checkout_content', false ) ) {
		update_option( 'mcwc_previous_checkout_content', $page->post_content, false );
	}
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => '[woocommerce_checkout]',
		)
	);
}
add_action( 'woocommerce_update_options_modern_checkout', 'mcwc_switch_checkout_block_to_shortcode' );

function mcwc_switch_checkout_block_action() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to update checkout settings.', 'modern-checkout-for-woocommerce' ) );
	}
	check_admin_referer( 'mcwc_switch_checkout' );
	mcwc_switch_checkout_block_to_shortcode();
	wp_safe_redirect( add_query_arg( 'mcwc_switched', '1', admin_url( 'admin.php?page=wc-settings&tab=modern_checkout' ) ) );
	exit;
}
add_action( 'admin_post_mcwc_switch_checkout', 'mcwc_switch_checkout_block_action' );

function mcwc_checkout_block_admin_notice() {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! mcwc_is_enabled() || ! mcwc_checkout_page_uses_block() ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=mcwc_switch_checkout' ), 'mcwc_switch_checkout' );
	?>
	<div class="notice notice-warning">
		<p><?php esc_html_e( 'Modern Checkout requires the classic WooCommerce checkout shortcode. The checkout page still contains the Checkout block.', 'modern-checkout-for-woocommerce' ); ?>
			<a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Switch to classic checkout', 'modern-checkout-for-woocommerce' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'mcwc_checkout_block_admin_notice' );
