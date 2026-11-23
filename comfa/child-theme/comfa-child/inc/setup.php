<?php
/**
 * Theme support, assets and store-wide settings.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		// Body, heading and serif accent fonts are set in Flatsome > Theme Options > Typography.
		// Only the poster display face is loaded here.
		wp_enqueue_style( 'cf-display-font', 'https://fonts.googleapis.com/css2?family=Bowlby+One&display=swap', array(), null );
		wp_enqueue_style( 'comfa-child', CF_URL . '/assets/css/comfa.css', array(), (string) filemtime( CF_DIR . '/assets/css/comfa.css' ) );
		wp_enqueue_script( 'comfa-child', CF_URL . '/assets/js/comfa.js', array( 'jquery' ), (string) filemtime( CF_DIR . '/assets/js/comfa.js' ), true );
		wp_localize_script(
			'comfa-child',
			'cfStore',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'cf_newsletter' ),
				'i18n'      => array(
					'error' => __( 'Something went wrong. Please try again.', 'comfa' ),
				),
			)
		);
	},
	20
);

add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'cf-store';
		return $classes;
	}
);

/**
 * Store settings that merchants can override with a filter or constant.
 */
function cf_setting( $key ) {
	$defaults = array(
		'free_shipping_threshold' => 75,
		'welcome_code'            => 'HELLO10',
		'welcome_percent'         => 10,
		'promo_line_1'            => 'Free shipping on orders over $75',
		'promo_line_2'            => 'Take 10% off your first order with code HELLO10',
	);
	$settings = apply_filters( 'cf_settings', $defaults );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
}

// "$34" instead of "$34.00"; prices with cents keep them.
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
