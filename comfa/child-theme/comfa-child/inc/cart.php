<?php
/**
 * Bag: page heading, free-shipping progress and giving line, empty state, cross-sells.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Items a cart gives away: one per item bought, counting packs.
 */
function cf_cart_given_count() {
	$total = 0;
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		$pack = isset( $item['variation']['attribute_pa_pack'] ) ? $item['variation']['attribute_pa_pack'] : '';
		$total += (int) $item['quantity'] * ( $pack ? cf_pack_count( get_term_by( 'slug', $pack, 'pa_pack' ) ) : 1 );
	}
	return $total;
}

/**
 * Free-shipping rule for the customer's shipping zone, read from WooCommerce so the
 * bag never promises what checkout does not offer.
 *
 * @return array|null { minimum, ignore_discounts } or null when the zone has no minimum-based free shipping.
 */
function cf_free_shipping_rule() {
	$packages = WC()->cart->get_shipping_packages();
	if ( ! $packages ) {
		return null;
	}
	$zone = WC_Shipping_Zones::get_zone_matching_package( reset( $packages ) );
	$rule = null;
	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' !== $method->id ) {
			continue;
		}
		$requires = (string) $method->get_option( 'requires' );
		if ( '' === $requires ) {
			return array( 'minimum' => 0.0, 'ignore_discounts' => false );
		}
		if ( in_array( $requires, array( 'min_amount', 'either' ), true ) ) {
			$minimum = (float) $method->get_option( 'min_amount' );
			if ( null === $rule || $minimum < $rule['minimum'] ) {
				$rule = array( 'minimum' => $minimum, 'ignore_discounts' => 'yes' === $method->get_option( 'ignore_discounts' ) );
			}
		}
	}
	return $rule;
}

function cf_bag_progress() {
	if ( ! WC()->cart || WC()->cart->is_empty() || ! WC()->cart->needs_shipping() ) {
		return;
	}
	$rule = cf_free_shipping_rule();
	if ( ! $rule ) {
		return;
	}
	$threshold = $rule['minimum'];
	$subtotal  = (float) WC()->cart->get_displayed_subtotal() - ( $rule['ignore_discounts'] ? 0 : (float) WC()->cart->get_discount_total() );
	$remaining = max( 0, $threshold - $subtotal );
	$percent   = $threshold > 0 ? min( 100, max( 0, $subtotal / $threshold * 100 ) ) : 100;
	$message   = $remaining > 0
		/* translators: %s: amount left. */
		? sprintf( __( 'You are %s away from free shipping', 'comfa' ), wc_price( $remaining ) )
		: __( 'You have unlocked free shipping', 'comfa' );
	$given = cf_cart_given_count();
	echo '<div class="cf-bag-progress"><p>' . wp_kses_post( $message ) . '</p><div class="cf-bag-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) round( $percent ) ) . '"><span style="width:' . esc_attr( (string) round( $percent, 2 ) ) . '%"></span></div>';
	if ( $given ) {
		echo '<p class="cf-bag-progress__give">' . esc_html( sprintf( /* translators: %s: number of items. */ _n( 'This order gives %s item to someone in need', 'This order gives %s items to someone in need', $given, 'comfa' ), number_format_i18n( $given ) ) ) . '</p>';
	}
	echo '</div>';
}
add_action( 'woocommerce_before_mini_cart_contents', 'cf_bag_progress', 5 );
add_action( 'woocommerce_before_cart_table', 'cf_bag_progress', 5 );

/**
 * Bag page heading with the item count. It sits inside the cart form, the part WooCommerce re-renders after every update.
 */
function cf_bag_heading() {
	$count = WC()->cart->get_cart_contents_count();
	printf(
		'<header class="cf-bag-head"><h1 class="cf-bag-head__title">%1$s</h1><p class="cf-bag-head__count">%2$s</p></header>',
		esc_html__( 'Your Bag', 'comfa' ),
		/* translators: %s: number of items. */
		esc_html( sprintf( _n( '%s item', '%s items', $count, 'comfa' ), number_format_i18n( $count ) ) )
	);
}
add_action( 'woocommerce_before_cart_table', 'cf_bag_heading', 1 );

/**
 * Editable empty-bag content: UX Block "bag-empty" (Flatsome > UX Blocks), shown under WooCommerce's own message.
 */
function cf_bag_empty() {
	$block = get_page_by_path( 'bag-empty', OBJECT, 'blocks' );
	if ( $block && 'publish' === $block->post_status ) {
		echo '<div class="cf-bag-empty">' . do_shortcode( '[block id="' . (int) $block->ID . '"]' ) . '</div>';
	}
}
add_action( 'woocommerce_cart_is_empty', 'cf_bag_empty', 20 );

// Cross-sells stay where Flatsome prints them, under the items; three fit that column.
add_filter( 'woocommerce_cross_sells_columns', function () { return 3; } );
add_filter( 'woocommerce_cross_sells_total', function () { return 3; } );

add_filter(
	'woocommerce_shipping_package_name',
	function ( $name, $index ) {
		return 0 === (int) $index && count( WC()->shipping()->get_packages() ) < 2 ? __( 'Shipping', 'woocommerce' ) : $name;
	},
	10,
	2
);

add_filter(
	'woocommerce_product_add_to_cart_text',
	function ( $text, $product ) {
		return $product->is_type( 'variable' ) ? __( 'Choose Options', 'comfa' ) : $text;
	},
	10,
	2
);

add_filter(
	'gettext',
	function ( $translation, $text, $domain ) {
		if ( 'flatsome' === $domain ) {
			return 'Shopping Cart' === $text && $translation === $text ? 'Bag' : $translation;
		}
		if ( 'woocommerce' !== $domain ) {
			return $translation;
		}
		$map = array(
			'View cart'                         => 'View Bag',
			'Cart totals'                       => 'Order Summary',
			'Proceed to checkout'               => 'Checkout',
			'Add to cart'                       => 'Add to Bag',
			'Update cart'                       => 'Update Bag',
			'Your cart is currently empty.'     => 'Your bag is empty.',
			'You may be interested in&hellip;'  => 'Goes well with',
		);
		return isset( $map[ $text ] ) && $translation === $text ? $map[ $text ] : $translation;
	},
	10,
	3
);

/**
 * The welcome code is for first orders only. Eligibility is checked against the
 * account that owns the bag or order; guest emails are not looked up, because
 * anyone can type any address (WooCommerce's own per-customer limit still applies).
 */
function cf_customer_has_ordered( $user_id, $exclude_order_id = 0 ) {
	if ( ! $user_id ) {
		return false;
	}
	$statuses = array_diff( array_keys( wc_get_order_statuses() ), array( 'wc-cancelled', 'wc-failed', 'wc-refunded', 'wc-checkout-draft' ) );
	return (bool) wc_get_orders(
		array(
			'customer_id' => $user_id,
			'status'      => $statuses,
			'limit'       => 1,
			'return'      => 'ids',
			'exclude'     => array_filter( array( (int) $exclude_order_id ) ),
		)
	);
}

function cf_is_welcome_coupon( $code ) {
	$welcome = (string) cf_setting( 'welcome_code' );
	return '' !== $welcome && wc_format_coupon_code( $welcome ) === wc_format_coupon_code( $code );
}

add_filter(
	'woocommerce_coupon_is_valid',
	function ( $valid, $coupon, $discounts = null ) {
		if ( ! $valid || ! cf_is_welcome_coupon( $coupon->get_code() ) ) {
			return $valid;
		}
		// In the order editor the coupon belongs to the order's customer, not the staff member.
		$object  = $discounts instanceof WC_Discounts ? $discounts->get_object() : null;
		$ordered = $object instanceof WC_Order
			? cf_customer_has_ordered( $object->get_customer_id(), $object->get_id() )
			: cf_customer_has_ordered( get_current_user_id() );
		if ( $ordered ) {
			throw new Exception( esc_html__( 'This code is for first orders only.', 'comfa' ), 100 );
		}
		return $valid;
	},
	10,
	3
);

/**
 * WooCommerce links [privacy_policy] to the Settings > Privacy page even while that page is a draft.
 */
function cf_unlink_unpublished_privacy_policy( $text ) {
	$page_id = wc_privacy_policy_page_id();
	if ( $page_id && 'publish' !== get_post_status( $page_id ) ) {
		$text = str_replace( '[privacy_policy]', __( 'privacy policy', 'woocommerce' ), $text );
	}
	return $text;
}
add_filter( 'woocommerce_get_privacy_policy_text', 'cf_unlink_unpublished_privacy_policy' );
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'cf_unlink_unpublished_privacy_policy' );
