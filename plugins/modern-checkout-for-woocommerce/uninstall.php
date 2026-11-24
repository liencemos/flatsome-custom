<?php
/**
 * Remove plugin settings on uninstall.
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mcwc_options = array(
	'mcwc_enabled',
	'mcwc_logo_id',
	'mcwc_logo_max_height',
	'mcwc_primary_color',
	'mcwc_text_color',
	'mcwc_muted_color',
	'mcwc_summary_background',
	'mcwc_border_color',
	'mcwc_font',
	'mcwc_input_radius',
	'mcwc_button_radius',
	'mcwc_header_right_text',
	'mcwc_trust_1_text',
	'mcwc_trust_1_icon',
	'mcwc_trust_2_text',
	'mcwc_trust_2_icon',
	'mcwc_trust_3_text',
	'mcwc_trust_3_icon',
	'mcwc_show_free_shipping',
	'mcwc_show_coupon',
	'mcwc_show_order_notes',
	'mcwc_free_shipping_threshold_override',
	'mcwc_exclude_virtual',
	'mcwc_previous_checkout_content',
);

foreach ( $mcwc_options as $mcwc_option ) {
	delete_option( $mcwc_option );
}
