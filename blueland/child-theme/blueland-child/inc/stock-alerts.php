<?php
/**
 * Stock badges and back-in-stock notifications.
 */

defined( 'ABSPATH' ) || exit;

function bl_stock_status_badges_markup( $product ) {
	if ( ! $product instanceof WC_Product || $product->is_virtual() || 'yes' === get_post_meta( $product->get_id(), '_bl_is_gift_card', true ) ) {
		return '';
	}

	$badges = array();
	if ( ! $product->is_in_stock() ) {
		$badges[] = '<span class="bl-badge bl-badge-sold-out">Sold out</span>';
	} else {
		$restocked_at = absint( get_post_meta( $product->get_id(), '_bl_restocked_at', true ) );
		if ( $restocked_at && $restocked_at <= time() && time() - $restocked_at <= 14 * DAY_IN_SECONDS ) {
			$badges[] = '<span class="bl-badge bl-badge-back-in-stock">Back in Stock</span>';
		}

		$low_stock_amount = max( 0, (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ) );
		$stock_quantity   = $product->get_stock_quantity();
		if ( $product->managing_stock() && null !== $stock_quantity && $stock_quantity > 0 && $stock_quantity <= $low_stock_amount ) {
			$badges[] = '<span class="bl-badge bl-badge-low-stock">' . esc_html( sprintf( 'Only %d left', (int) $stock_quantity ) ) . '</span>';
		}
	}

	return $badges ? '<div class="bl-product-stock-badges">' . implode( '', $badges ) . '</div>' : '';
}

function bl_stock_notify_form( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$product_id = $product->get_id();
	return '<form class="bl-stock-notify-form"><h3>Notify me when it’s back</h3><label for="bl-stock-email-' . esc_attr( (string) $product_id ) . '">Email address</label><input id="bl-stock-email-' . esc_attr( (string) $product_id ) . '" type="email" name="email" autocomplete="email" required><input type="hidden" name="product_id" value="' . esc_attr( (string) $product_id ) . '"><input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( 'bl_stock_notify_' . $product_id ) ) . '"><label class="bl-stock-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="bl_stock_website" tabindex="-1" autocomplete="off"></label><button class="button primary" type="submit">Notify me when it’s back</button><p class="bl-stock-notify-message" aria-live="polite"></p></form>';
}

function bl_stock_notify_pdp_shortcode() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	if ( ! $product->is_in_stock() ) {
		return bl_stock_notify_form( $product );
	}
	ob_start();
	woocommerce_template_single_add_to_cart();
	return ob_get_clean();
}
add_shortcode( 'bl_stock_notify_pdp', 'bl_stock_notify_pdp_shortcode' );

function bl_stock_notify_submit() {
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : false;
	$nonce      = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	$honeypot   = isset( $_POST['bl_stock_website'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_stock_website'] ) ) : '';
	$email      = isset( $_POST['email'] ) ? strtolower( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : '';

	if ( ! $product || ! wp_verify_nonce( $nonce, 'bl_stock_notify_' . $product_id ) ) {
		wp_send_json_error( array( 'message' => 'Please refresh and try again.' ), 403 );
	}
	if ( '' !== $honeypot || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Enter a valid email address.' ), 400 );
	}
	if ( $product->is_in_stock() ) {
		wp_send_json_error( array( 'message' => 'This product is available now.' ), 400 );
	}

	update_post_meta( $product_id, '_bl_stock_last_status', 'outofstock' );
	$subscribers = get_post_meta( $product_id, '_bl_stock_notify', true );
	$subscribers = is_array( $subscribers ) ? $subscribers : array();
	$subscribers = array_values( array_unique( array_filter( array_map( 'strtolower', array_map( 'sanitize_email', $subscribers ) ), 'is_email' ) ) );
	$already_subscribed = in_array( $email, $subscribers, true );
	if ( ! $already_subscribed ) {
		$subscribers[] = $email;
		update_post_meta( $product_id, '_bl_stock_notify', $subscribers );
	}
	wp_send_json_success( array( 'message' => $already_subscribed ? 'You’re already on the list.' : 'We’ll email you when it’s back.' ) );
}
add_action( 'wp_ajax_bl_stock_notify', 'bl_stock_notify_submit' );
add_action( 'wp_ajax_nopriv_bl_stock_notify', 'bl_stock_notify_submit' );

function bl_send_restock_notifications( $product_id, $stock_status, $product = null ) {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}
	$target_ids = array( absint( $product_id ) );
	if ( $product instanceof WC_Product_Variation && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent && $parent->is_in_stock() ) {
			$target_ids[] = $parent->get_id();
		}
	}

	foreach ( array_unique( $target_ids ) as $target_id ) {
		$target = wc_get_product( $target_id );
		if ( ! $target ) {
			continue;
		}
		$current_status = $target->get_stock_status();
		$previous_status = get_post_meta( $target_id, '_bl_stock_last_status', true );
		update_post_meta( $target_id, '_bl_stock_last_status', $current_status );
		if ( 'instock' !== $current_status || 'outofstock' !== $previous_status ) {
			continue;
		}
		update_post_meta( $target_id, '_bl_restocked_at', time() );
		$subscribers = get_post_meta( $target_id, '_bl_stock_notify', true );
		if ( ! is_array( $subscribers ) || empty( $subscribers ) ) {
			continue;
		}
		$name    = $target->get_name();
		$link    = get_permalink( $target_id );
		$subject = sprintf( '%s is back in stock', $name );
		$message = sprintf( "Good news! %1\$s is back in stock.\n\nShop now: %2\$s", $name, $link );
		foreach ( array_unique( array_map( 'strtolower', array_map( 'sanitize_email', $subscribers ) ) ) as $subscriber ) {
			if ( is_email( $subscriber ) ) {
				wp_mail( $subscriber, $subject, $message );
			}
		}
		delete_post_meta( $target_id, '_bl_stock_notify' );
	}
}
add_action( 'woocommerce_product_set_stock_status', 'bl_send_restock_notifications', 10, 3 );
add_action( 'woocommerce_variation_set_stock_status', 'bl_send_restock_notifications', 10, 3 );
