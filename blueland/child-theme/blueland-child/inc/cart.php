<?php
/**
 * Mini-cart progress, quantity controls, and cross-sells.
 */

defined( 'ABSPATH' ) || exit;

function bl_free_shipping_threshold() {
	return (float) apply_filters( 'bl_free_shipping_threshold', 50 );
}

add_action(
	'woocommerce_before_mini_cart',
	function () {
		if ( ! WC()->cart ) {
			return;
		}
		$threshold = bl_free_shipping_threshold();
		$total = (float) WC()->cart->get_cart_contents_total();
		$remaining = max( 0, $threshold - $total );
		$progress = min( 100, ( $total / max( 1, $threshold ) ) * 100 );
		echo '<div class="bl-cart-promo">Free shipping on orders $50+</div><div class="bl-shipping-progress"><p>' . ( $remaining > 0 ? 'Add ' . wp_kses_post( wc_price( $remaining ) ) . ' to get free shipping' : 'You’ve unlocked free shipping!' ) . '</p><div role="progressbar" aria-valuenow="' . esc_attr( (string) round( $progress ) ) . '" aria-valuemin="0" aria-valuemax="100"><span style="width:' . esc_attr( $progress ) . '%"></span></div><small>$0 <span>Free shipping at $50</span></small></div>';
	}
);

add_filter(
	'woocommerce_widget_cart_item_quantity',
	function ( $quantity, $cart_item, $cart_item_key ) {
		return '<div class="bl-mini-quantity"><button type="button" data-cart-qty="-1" data-key="' . esc_attr( $cart_item_key ) . '" aria-label="Decrease quantity">−</button><span>' . esc_html( (string) $cart_item['quantity'] ) . '</span><button type="button" data-cart-qty="1" data-key="' . esc_attr( $cart_item_key ) . '" aria-label="Increase quantity">+</button></div><span class="bl-mini-price">' . wp_kses_post( WC()->cart->get_product_price( $cart_item['data'] ) ) . '</span>';
	},
	10,
	3
);

add_action(
	'woocommerce_widget_shopping_cart_before_buttons',
	function () {
		$products = wc_get_products( array( 'limit' => 4, 'status' => 'publish', 'orderby' => 'popularity', 'order' => 'DESC' ) );
		if ( empty( $products ) ) {
			return;
		}
		echo '<div class="bl-cart-recommendations"><h3>You may also like</h3><div class="bl-cart-recommendation-row">';
		foreach ( $products as $product ) {
			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}
			echo '<article><a href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . $product->get_image( 'woocommerce_thumbnail' ) . '<span>' . esc_html( $product->get_name() ) . '</span></a><span>' . wp_kses_post( $product->get_price_html() ) . '</span></article>';
		}
		echo '</div></div>';
	}
);

add_action(
	'woocommerce_widget_shopping_cart_after_buttons',
	function () {
		echo '<p class="bl-cart-tax-note">Taxes and shipping calculated at checkout.</p>';
	}
);

add_action(
	'wp_ajax_bl_update_cart_qty',
	'bl_update_cart_qty'
);
add_action(
	'wp_ajax_nopriv_bl_update_cart_qty',
	'bl_update_cart_qty'
);
function bl_update_cart_qty() {
	check_ajax_referer( 'bl_storefront', 'nonce' );
	$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	$delta = isset( $_POST['delta'] ) ? (int) $_POST['delta'] : 0;
	$cart = WC()->cart;
	if ( $cart && isset( $cart->get_cart()[ $key ] ) ) {
		$current = (int) $cart->get_cart()[ $key ]['quantity'];
		if ( $current + $delta > 0 ) {
			$cart->set_quantity( $key, $current + $delta );
		} else {
			$cart->remove_cart_item( $key );
		}
	}
	WC_AJAX::get_refreshed_fragments();
}
