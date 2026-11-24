<?php
/**
 * Theme support, assets and store-wide settings.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		// Body, heading and serif accent fonts are set in Flatsome > Theme Options > Typography.
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

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attributes ) {
		if ( 'ux_image' !== $tag || ! is_front_page() || ! in_array( 'cf-hero__image', explode( ' ', $attributes['class'] ?? '' ), true ) ) {
			return $output;
		}
		$image = new WP_HTML_Tag_Processor( $output );
		if ( $image->next_tag( 'IMG' ) ) {
			$image->set_attribute( 'loading', 'eager' );
			$image->set_attribute( 'fetchpriority', 'high' );
			$image->set_attribute( 'sizes', '(max-width: 549px) calc(100vw - 14px), calc(50vw - 7px)' );
			$image->add_class( 'ux-skip-lazy' );
			$image->add_class( 'skip-lazy' );
		}
		return $image->get_updated_html();
	},
	10,
	3
);

function cf_product_main_image_sizes() {
	$columns = max( 1, min( 12, (int) get_theme_mod( 'product_image_width', 6 ) ) );
	$width   = (float) get_theme_mod( 'site_width', 1080 );
	$width   = $width > 0 ? $width : 1080;
	$layout  = get_theme_mod( 'product_layout' );
	$grid    = ! get_theme_mod( 'product_gallery_woocommerce' ) && 'stacked-right' === $layout ? get_theme_mod( 'product_gallery_grid_layout' ) : '';
	$share   = 'gallery-wide' === $layout ? 1 : $columns / 12;
	if ( '2' === (string) $grid ) {
		$share /= 2;
	}
	if ( in_array( $layout, array( 'left-sidebar', 'right-sidebar', 'left-sidebar-full', 'right-sidebar-full', 'left-sidebar-small' ), true ) ) {
		$share *= 0.75;
	}
	return sprintf( '(max-width: 849px) calc(100vw - 30px), (max-width: %dpx) calc(%svw - 30px), %dpx', (int) $width, $share * 100, max( 1, (int) ( $width * $share - 30 ) ) );
}

add_filter(
	'woocommerce_gallery_image_html_attachment_image_params',
	function ( $attributes, $attachment_id, $size, $main ) {
		if ( $main && is_product() && (int) $attachment_id === (int) get_post_thumbnail_id( get_queried_object_id() ) ) {
			$attributes['class']         = trim( ( $attributes['class'] ?? '' ) . ' ux-skip-lazy skip-lazy' );
			$attributes['loading']       = 'eager';
			$attributes['fetchpriority'] = 'high';
			$attributes['sizes']         = cf_product_main_image_sizes();
		}
		return $attributes;
	},
	20,
	4
);
