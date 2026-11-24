<?php
/**
 * Plugin bootstrap and shared helpers.
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class MCWC_Plugin {
	public static function init() {
		require_once MCWC_DIR . 'includes/class-mcwc-checkout.php';
		require_once MCWC_DIR . 'includes/admin-notices.php';

		if ( is_admin() ) {
			if ( ! class_exists( 'WC_Settings_Page' ) && defined( 'WC_ABSPATH' ) ) {
				$settings_page = WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
				if ( file_exists( $settings_page ) ) {
					require_once $settings_page;
				}
			}
			if ( class_exists( 'WC_Settings_Page' ) ) {
				require_once MCWC_DIR . 'includes/class-mcwc-settings.php';
				MCWC_Settings::init();
			}
		}

		MCWC_Checkout::init();
	}
}

function mcwc_option( $name, $default = '' ) {
	return get_option( 'mcwc_' . $name, $default );
}

function mcwc_is_enabled() {
	return 'yes' === mcwc_option( 'enabled', 'yes' );
}

function mcwc_active_font() {
	$fonts = array( 'inherit', 'DM Sans', 'Inter', 'Poppins', 'Roboto', 'Lato' );
	$font  = mcwc_option( 'font', 'DM Sans' );
	return in_array( $font, $fonts, true ) ? $font : 'DM Sans';
}

function mcwc_get_header_logo_html() {
	$logo_id = absint( mcwc_option( 'logo_id', 0 ) );
	if ( ! $logo_id ) {
		$logo_id = absint( get_theme_mod( 'custom_logo' ) );
	}

	if ( $logo_id ) {
		$image = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class' => 'mcwc-logo',
				'alt'   => get_bloginfo( 'name' ),
			)
		);
		if ( $image ) {
			return $image;
		}
	}

	return '<span class="mcwc-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
}
