<?php
/**
 * Gift cards, recipient delivery, and gift wrapping.
 */

defined( 'ABSPATH' ) || exit;

function bl_is_gift_card_product( $product_id ) {
	return 'yes' === get_post_meta( $product_id, '_bl_is_gift_card', true );
}

function bl_gift_card_fields() {
	global $product;
	if ( ! $product instanceof WC_Product || ! bl_is_gift_card_product( $product->get_id() ) ) {
		return;
	}
	$product_id = $product->get_id();
	echo '<div class="bl-gift-card-fields"><h3>Gift card details</h3><label>Recipient name<input type="text" name="bl_gift_recipient_name" maxlength="100" required></label><label>Recipient email<input type="email" name="bl_gift_recipient_email" autocomplete="email" required></label><label>Sender name<input type="text" name="bl_gift_sender_name" maxlength="100" required></label><label>Message <span>(optional, up to 200 characters)</span><textarea name="bl_gift_message" maxlength="200" rows="3"></textarea></label><input type="hidden" name="bl_gift_card_nonce" value="' . esc_attr( wp_create_nonce( 'bl_gift_card_' . $product_id ) ) . '"><label class="bl-gift-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="bl_gift_website" tabindex="-1" autocomplete="off"></label></div>';
}
add_action( 'woocommerce_before_add_to_cart_button', 'bl_gift_card_fields' );

function bl_validate_gift_card_add_to_cart( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
	if ( ! bl_is_gift_card_product( $product_id ) ) {
		return $passed;
	}
	$nonce = isset( $_POST['bl_gift_card_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_card_nonce'] ) ) : '';
	$honeypot = isset( $_POST['bl_gift_website'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_website'] ) ) : '';
	$name = isset( $_POST['bl_gift_recipient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_recipient_name'] ) ) : '';
	$email = isset( $_POST['bl_gift_recipient_email'] ) ? sanitize_email( wp_unslash( $_POST['bl_gift_recipient_email'] ) ) : '';
	$sender = isset( $_POST['bl_gift_sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_sender_name'] ) ) : '';
	$message = isset( $_POST['bl_gift_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bl_gift_message'] ) ) : '';
	$message_length = function_exists( 'mb_strlen' ) ? mb_strlen( $message ) : strlen( $message );

	if ( ! wp_verify_nonce( $nonce, 'bl_gift_card_' . $product_id ) || '' !== $honeypot || '' === $name || ! is_email( $email ) || '' === $sender || $message_length > 200 ) {
		wc_add_notice( 'Please complete the gift card details with a valid recipient email.', 'error' );
		return false;
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'bl_validate_gift_card_add_to_cart', 10, 5 );

function bl_add_gift_card_cart_data( $cart_item_data, $product_id, $variation_id ) {
	if ( ! bl_is_gift_card_product( $product_id ) ) {
		return $cart_item_data;
	}
	$cart_item_data['bl_gift_recipient_name'] = isset( $_POST['bl_gift_recipient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_recipient_name'] ) ) : '';
	$cart_item_data['bl_gift_recipient_email'] = isset( $_POST['bl_gift_recipient_email'] ) ? strtolower( sanitize_email( wp_unslash( $_POST['bl_gift_recipient_email'] ) ) ) : '';
	$cart_item_data['bl_gift_sender_name'] = isset( $_POST['bl_gift_sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_sender_name'] ) ) : '';
	$cart_item_data['bl_gift_message'] = isset( $_POST['bl_gift_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bl_gift_message'] ) ) : '';
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'bl_add_gift_card_cart_data', 10, 3 );

function bl_display_gift_card_cart_data( $item_data, $cart_item ) {
	$fields = array(
		'bl_gift_recipient_name'  => 'Recipient',
		'bl_gift_recipient_email' => 'Recipient email',
		'bl_gift_sender_name'     => 'From',
		'bl_gift_message'         => 'Message',
	);
	foreach ( $fields as $key => $label ) {
		if ( ! empty( $cart_item[ $key ] ) ) {
			$item_data[] = array(
				'key'   => $label,
				'value' => esc_html( $cart_item[ $key ] ),
			);
		}
	}
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'bl_display_gift_card_cart_data', 10, 2 );

function bl_save_gift_card_order_item_meta( $item, $cart_item_key, $values, $order ) {
	$fields = array(
		'bl_gift_recipient_name'  => 'Recipient',
		'bl_gift_recipient_email' => 'Recipient email',
		'bl_gift_sender_name'     => 'From',
		'bl_gift_message'         => 'Message',
	);
	foreach ( $fields as $key => $label ) {
		if ( ! empty( $values[ $key ] ) ) {
			$item->add_meta_data( $label, sanitize_textarea_field( $values[ $key ] ), true );
			$item->add_meta_data( '_' . $key, sanitize_textarea_field( $values[ $key ] ), true );
		}
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'bl_save_gift_card_order_item_meta', 10, 4 );

function bl_generate_gift_coupon_code() {
	do {
		$code = 'PL-' . strtoupper( wp_generate_password( 4, false, false ) ) . '-' . strtoupper( wp_generate_password( 4, false, false ) );
	} while ( wc_get_coupon_id_by_code( $code ) );
	return $code;
}

function bl_issue_gift_card_coupons( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$gift_card_post = get_page_by_path( 'pureland-gift-card', OBJECT, 'product' );
	$gift_card_id = $gift_card_post ? (int) $gift_card_post->ID : 0;
	foreach ( $order->get_items() as $item ) {
		if ( ! $gift_card_id || $gift_card_id !== (int) $item->get_product_id() || $item->get_meta( '_bl_gift_codes', true ) ) {
			continue;
		}
		$quantity = max( 1, (int) $item->get_quantity() );
		$unit_amount = round( (float) $item->get_total() / $quantity, 2 );
		$codes = array();
		for ( $unit = 0; $unit < $quantity; $unit++ ) {
			$coupon = new WC_Coupon();
			$code = bl_generate_gift_coupon_code();
			$coupon->set_code( $code );
			$coupon->set_discount_type( 'fixed_cart' );
			$coupon->set_amount( $unit_amount );
			$coupon->set_individual_use( true );
			$coupon->set_usage_limit( 1 );
			$coupon->set_usage_limit_per_user( 1 );
			$coupon->set_date_expires( null );
			$coupon->set_excluded_product_ids( array( $gift_card_id ) );
			if ( $coupon->save() ) {
				$codes[] = $code;
			}
		}
		if ( empty( $codes ) ) {
			continue;
		}

		$item->update_meta_data( '_bl_gift_codes', implode( "\n", $codes ) );
		$item->save();

		$recipient_email = strtolower( sanitize_email( $item->get_meta( '_bl_gift_recipient_email', true ) ) );
		if ( ! is_email( $recipient_email ) ) {
			continue;
		}
		$recipient_name = sanitize_text_field( $item->get_meta( '_bl_gift_recipient_name', true ) );
		$sender_name = sanitize_text_field( $item->get_meta( '_bl_gift_sender_name', true ) );
		$gift_message = sanitize_textarea_field( $item->get_meta( '_bl_gift_message', true ) );
		$code_list = '<ul>';
		foreach ( $codes as $code ) {
			$code_list .= '<li><strong>' . esc_html( $code ) . '</strong></li>';
		}
		$code_list .= '</ul>';
		$content = '<p>Hello ' . esc_html( $recipient_name ) . ',</p><p>' . esc_html( $sender_name ) . ' sent you a Pureland Gift Card.</p>';
		if ( $gift_message ) {
			$content .= '<blockquote>' . nl2br( esc_html( $gift_message ) ) . '</blockquote>';
		}
		$content .= '<p>Use this single-use code at checkout:</p>' . $code_list;
		$wrapped = WC()->mailer()->wrap_message( 'Your Pureland gift card', $content );
		wp_mail( $recipient_email, 'Your Pureland gift card', $wrapped, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}
add_action( 'woocommerce_order_status_processing', 'bl_issue_gift_card_coupons' );
add_action( 'woocommerce_order_status_completed', 'bl_issue_gift_card_coupons' );

add_action(
	'woocommerce_after_order_itemmeta',
	function ( $item_id, $item, $product ) {
		if ( current_user_can( 'manage_woocommerce' ) && $item instanceof WC_Order_Item_Product ) {
			$codes = $item->get_meta( '_bl_gift_codes', true );
			if ( $codes ) {
				echo '<p><strong>Gift card codes:</strong><br>' . nl2br( esc_html( $codes ) ) . '</p>';
			}
		}
	},
	10,
	3
);

function bl_cart_has_physical_items() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product && ! $cart_item['data']->is_virtual() ) {
			return true;
		}
	}
	return false;
}

function bl_gift_wrap_control_html() {
	if ( ! bl_cart_has_physical_items() ) {
		return '';
	}
	$enabled = WC()->session->get( 'bl_gift_wrap', false );
	$note = sanitize_textarea_field( (string) WC()->session->get( 'bl_gift_wrap_note', '' ) );
	return '<div class="bl-gift-wrap-control"><label class="bl-gift-wrap-check"><input type="checkbox" data-gift-wrap-toggle value="1" ' . checked( $enabled, true, false ) . '> Add gift wrapping &amp; a note (+$4.95)</label><textarea data-gift-wrap-note rows="3" maxlength="200" placeholder="Gift note (optional)"' . ( $enabled ? '' : ' hidden' ) . '>' . esc_textarea( $note ) . '</textarea><input type="hidden" data-gift-wrap-nonce value="' . esc_attr( wp_create_nonce( 'bl_gift_wrap' ) ) . '"><label class="bl-gift-honeypot" aria-hidden="true">Leave this field empty<input type="text" data-gift-wrap-website tabindex="-1" autocomplete="off"></label><p class="bl-gift-wrap-message" aria-live="polite"></p></div>';
}

function bl_render_gift_wrap_control() {
	echo bl_gift_wrap_control_html();
}
add_action( 'woocommerce_before_cart_totals', 'bl_render_gift_wrap_control', 5 );
add_action( 'woocommerce_review_order_before_payment', 'bl_render_gift_wrap_control', 5 );

function bl_render_gift_wrap_block_template() {
	if ( ( ! is_cart() && ! is_checkout() ) || is_order_received_page() || is_checkout_pay_page() || ! bl_cart_has_physical_items() ) {
		return;
	}
	echo '<template id="bl-gift-wrap-block-template">' . bl_gift_wrap_control_html() . '</template>';
}
add_action( 'wp_footer', 'bl_render_gift_wrap_block_template', 20 );

function bl_update_gift_wrap_session() {
	check_ajax_referer( 'bl_gift_wrap', 'nonce' );
	$honeypot = isset( $_POST['bl_gift_website'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_gift_website'] ) ) : '';
	if ( '' !== $honeypot ) {
		wp_send_json_error( array( 'message' => 'Unable to process this request.' ), 400 );
	}
	$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) && bl_cart_has_physical_items();
	$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $note ) : strlen( $note );
	if ( $length > 200 ) {
		$note = function_exists( 'mb_substr' ) ? mb_substr( $note, 0, 200 ) : substr( $note, 0, 200 );
	}
	WC()->session->set( 'bl_gift_wrap', $enabled );
	WC()->session->set( 'bl_gift_wrap_note', $enabled ? $note : '' );
	wp_send_json_success( array( 'enabled' => $enabled ) );
}
add_action( 'wp_ajax_bl_update_gift_wrap', 'bl_update_gift_wrap_session' );
add_action( 'wp_ajax_nopriv_bl_update_gift_wrap', 'bl_update_gift_wrap_session' );

add_action(
	'woocommerce_cart_calculate_fees',
	function ( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! bl_cart_has_physical_items() ) {
			if ( WC()->session ) {
				WC()->session->set( 'bl_gift_wrap', false );
				WC()->session->set( 'bl_gift_wrap_note', '' );
			}
			return;
		}
		if ( WC()->session && WC()->session->get( 'bl_gift_wrap', false ) ) {
			$cart->add_fee( 'Gift wrapping', 4.95, false );
		}
	},
	20
);

function bl_save_gift_wrap_order_meta( $order, $data = array() ) {
	if ( ! WC()->session || ! WC()->session->get( 'bl_gift_wrap', false ) ) {
		return;
	}
	$note = sanitize_textarea_field( (string) WC()->session->get( 'bl_gift_wrap_note', '' ) );
	$order->update_meta_data( '_bl_gift_wrap', 'yes' );
	$order->update_meta_data( '_bl_gift_wrap_note', $note );
	$order->update_meta_data( 'Gift wrap note', $note );
}
add_action( 'woocommerce_checkout_create_order', 'bl_save_gift_wrap_order_meta', 10, 2 );
add_action( 'woocommerce_store_api_checkout_update_order_meta', 'bl_save_gift_wrap_order_meta', 10, 1 );

function bl_clear_gift_wrap_session() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	WC()->session->set( 'bl_gift_wrap', false );
	WC()->session->set( 'bl_gift_wrap_note', '' );
}

add_action( 'woocommerce_cart_emptied', 'bl_clear_gift_wrap_session' );

function bl_display_gift_wrap_email_meta( $order, $sent_to_admin, $plain_text, $email ) {
	$note = $order->get_meta( '_bl_gift_wrap_note', true );
	if ( ! $note ) {
		return;
	}
	if ( $plain_text ) {
		echo "\nGift wrapping requested. Gift note: " . esc_html( $note ) . "\n";
	} else {
		echo '<p><strong>Gift wrapping requested</strong><br>' . nl2br( esc_html( $note ) ) . '</p>';
	}
}
add_action( 'woocommerce_email_order_meta', 'bl_display_gift_wrap_email_meta', 10, 4 );

add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( $order ) {
		$note = $order->get_meta( '_bl_gift_wrap_note', true );
		if ( $note ) {
			echo '<p><strong>Gift wrap note:</strong><br>' . nl2br( esc_html( $note ) ) . '</p>';
		}
	}
);

function bl_gift_card_feature_shortcode() {
	$post = get_page_by_path( 'pureland-gift-card', OBJECT, 'product' );
	$product = $post ? wc_get_product( $post->ID ) : false;
	if ( ! $product ) {
		return '';
	}
	return '<div class="bl-gift-card-feature"><div>' . $product->get_image( 'woocommerce_thumbnail' ) . '</div><div><p class="bl-eyebrow">A thoughtful gift</p><h2>Pureland Gift Card</h2><p>Let them choose the home essentials they’ll love.</p><p>' . wp_kses_post( $product->get_price_html() ) . '</p><a class="button primary" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">Choose an amount</a></div></div>';
}
add_shortcode( 'bl_gift_card_feature', 'bl_gift_card_feature_shortcode' );
