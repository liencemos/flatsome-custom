<?php
/**
 * Checkout: stepped Information / Shipping / Payment layout with a summary column.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The checkout form itself, not the order-received or pay-for-order screens.
 */
function dewier_is_checkout_flow() {
	return function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' );
}

function dewier_checkout_setup() {
	if ( ! dewier_is_checkout_flow() ) {
		return;
	}
	remove_action( 'woocommerce_before_checkout_form', 'shoptimizer_cart_progress', 5 );
	// Payment renders inside the Payment step instead of under the order summary.
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
}

// The summary template prints its own thumbnail with a quantity badge; this also covers its AJAX refreshes.
function dewier_checkout_thumbnails() {
	remove_filter( 'woocommerce_cart_item_name', 'shoptimizer_product_thumbnail_in_checkout', 20 );
}
add_action( 'woocommerce_review_order_before_cart_contents', 'dewier_checkout_thumbnails' );
add_action( 'wp', 'dewier_checkout_setup' );

function dewier_checkout_body_class( $classes ) {
	if ( dewier_is_checkout_flow() ) {
		$classes[] = 'dw-checkout';
	}
	return $classes;
}
add_filter( 'body_class', 'dewier_checkout_body_class' );

function dewier_checkout_assets() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	wp_enqueue_style( 'dewier-checkout', DEWIER_URI . '/assets/css/checkout.css', array( 'dewier' ), dewier_asset_version( '/assets/css/checkout.css' ) );
	if ( dewier_is_checkout_flow() ) {
		// WooPayments' card iframe can only load fonts from a public font host such as Bunny Fonts.
		wp_enqueue_style( 'dewier-checkout-font', 'https://fonts.bunny.net/css?family=dm-sans:400,500', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'dewier-checkout', DEWIER_URI . '/assets/js/checkout.js', array( 'jquery', 'wc-checkout' ), dewier_asset_version( '/assets/js/checkout.js' ), true );
		wp_localize_script(
			'dewier-checkout',
			'dewierCheckout',
			array(
				'contact' => __( 'Contact', 'dewier' ),
				'shipTo'  => __( 'Ship to', 'dewier' ),
				'method'  => __( 'Method', 'dewier' ),
				'change'  => __( 'Change', 'dewier' ),
				'applied' => __( 'Discount applied.', 'dewier' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'dewier_checkout_assets', 110 );

/**
 * Packages that have at least one rate for the current address.
 */
function dewier_checkout_has_rates() {
	foreach ( WC()->shipping()->get_packages() as $package ) {
		if ( ! empty( $package['rates'] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Shipping method choices for the Shipping step. Uses WooCommerce's input names so checkout.js picks them up.
 */
function dewier_checkout_shipping_methods() {
	echo '<div class="dw-co-methods dw-co-shipping-methods">';
	if ( ! dewier_checkout_has_rates() ) {
		echo '<p class="dw-co-methods__empty">' . esc_html__( 'Enter a complete delivery address to see shipping options.', 'dewier' ) . '</p></div>';
		return;
	}
	$chosen   = (array) WC()->session->get( 'chosen_shipping_methods' );
	$packages = WC()->shipping()->get_packages();
	foreach ( $packages as $i => $package ) {
		$rates = $package['rates'];
		if ( ! $rates ) {
			continue;
		}
		$current = isset( $chosen[ $i ] ) && isset( $rates[ $chosen[ $i ] ] ) ? $chosen[ $i ] : current( array_keys( $rates ) );
		if ( count( $packages ) > 1 ) {
			/* translators: %d: shipment number */
			echo '<p class="dw-co-methods__package">' . esc_html( sprintf( __( 'Shipment %d', 'dewier' ), $i + 1 ) ) . '</p>';
		}
		echo '<ul class="dw-co-methods__list" id="shipping_method">';
		foreach ( $rates as $rate ) {
			$id = 'shipping_method_' . $i . '_' . sanitize_title( $rate->id );
			echo '<li class="dw-co-methods__item">';
			printf(
				'<input type="%1$s" name="shipping_method[%2$d]" data-index="%2$d" id="%3$s" value="%4$s" class="shipping_method" %5$s>',
				count( $rates ) > 1 ? 'radio' : 'hidden',
				(int) $i,
				esc_attr( $id ),
				esc_attr( $rate->id ),
				checked( $rate->id, $current, false )
			);
			printf( '<label for="%s">%s</label>', esc_attr( $id ), wp_kses_post( wc_cart_totals_shipping_method_label( $rate ) ) );
			do_action( 'woocommerce_after_shipping_rate', $rate, $i );
			echo '</li>';
		}
		echo '</ul>';
	}
	echo '</div>';
}

/**
 * Shipping total line for the order summary, without the rate choices.
 */
function dewier_checkout_shipping_total() {
	if ( ! dewier_checkout_has_rates() ) {
		return '<span class="dw-co-muted">' . esc_html__( 'Calculated at next step', 'dewier' ) . '</span>';
	}
	return WC()->cart->get_cart_shipping_total();
}

function dewier_checkout_mobile_total() {
	return '<span class="dw-co-toggle__total">' . wp_kses_post( WC()->cart->get_total() ) . '</span>';
}

function dewier_checkout_progress_html() {
	ob_start();
	dewier_cart_progress();
	return '<div class="dw-co-progress">' . ob_get_clean() . '</div>';
}

function dewier_checkout_fragments( $fragments ) {
	ob_start();
	dewier_checkout_shipping_methods();
	$fragments['.dw-co-shipping-methods'] = ob_get_clean();
	$fragments['.dw-co-toggle__total']    = dewier_checkout_mobile_total();
	$fragments['.dw-co-progress']         = dewier_checkout_progress_html();
	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'dewier_checkout_fragments' );

/**
 * Store-styled Place order button; gateways still swap its text through data-value.
 */
function dewier_order_button_html( $html ) {
	return str_replace( 'class="button alt', 'class="button alt dw-btn dw-btn--dark dw-co-place', $html );
}
add_filter( 'woocommerce_order_button_html', 'dewier_order_button_html' );

/**
 * Contact block: the email field moves out of the address block.
 */
function dewier_checkout_contact( $checkout ) {
	$fields = $checkout->get_checkout_fields( 'billing' );
	if ( isset( $fields['billing_email'] ) ) {
		$field          = $fields['billing_email'];
		$field['class'] = array( 'form-row-wide' );
		woocommerce_form_field( 'billing_email', $field, $checkout->get_value( 'billing_email' ) );
	}
}
