<?php
/**
 * Pureland classic multi-step checkout.
 */

defined( 'ABSPATH' ) || exit;

function bl_checkout_modern_is_active() {
	if ( ! empty( $GLOBALS['bl_checkout_modern_ajax'] ) ) {
		return true;
	}
	return function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page();
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( bl_checkout_modern_is_active() ) {
			$classes[] = 'bl-checkout-modern';
		}
		return $classes;
	}
);

add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		if ( bl_checkout_modern_is_active() && isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['priority'] = 5;
		}
		return $fields;
	}
);

function bl_checkout_modern_setup() {
	if ( ! bl_checkout_modern_is_active() || ! function_exists( 'WC' ) || ! WC()->checkout() ) {
		return;
	}

	$checkout = WC()->checkout();
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	remove_action( 'woocommerce_checkout_after_order_review', 'wc_checkout_privacy_policy_text', 1 );
	remove_action( 'woocommerce_checkout_after_order_review', 'flatsome_html_checkout_sidebar', 10 );
	if ( function_exists( 'wc_checkout_privacy_policy_text' ) ) {
		remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
		add_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
	}
	remove_action( 'woocommerce_review_order_before_payment', 'bl_render_gift_wrap_control', 5 );
	remove_action( 'woocommerce_checkout_before_order_review_heading', 'bl_render_free_shipping_progress', 5 );
	remove_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_billing' ), 10 );
	remove_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ), 10 );
	add_action( 'woocommerce_checkout_billing', 'bl_checkout_render_billing_fields', 10 );
	add_action( 'woocommerce_checkout_shipping', 'bl_checkout_render_shipping_fields', 10 );
}
add_action( 'wp', 'bl_checkout_modern_setup', 20 );

function bl_checkout_modern_ajax_setup( $post_data ) {
	parse_str( (string) $post_data, $posted_data );
	if ( isset( $posted_data['bl_checkout_modern'] ) && '1' === $posted_data['bl_checkout_modern'] ) {
		$GLOBALS['bl_checkout_modern_ajax'] = true;
		bl_checkout_modern_setup();
	}
}
add_action( 'woocommerce_checkout_update_order_review', 'bl_checkout_modern_ajax_setup', 1 );

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! bl_checkout_modern_is_active() ) {
			return;
		}

		wp_enqueue_script(
			'bl-checkout-modern',
			BL_CHILD_URL . '/assets/js/checkout-modern.js',
			array( 'jquery', 'wc-checkout' ),
			(string) filemtime( BL_CHILD_DIR . '/assets/js/checkout-modern.js' ),
			true
		);
		wp_enqueue_style(
			'bl-checkout-modern',
			BL_CHILD_URL . '/assets/css/checkout-modern.css',
			array( 'blueland-child' ),
			(string) filemtime( BL_CHILD_DIR . '/assets/css/checkout-modern.css' )
		);
	},
	50
);

function bl_checkout_render_billing_fields( $checkout = null ) {
	$checkout = $checkout instanceof WC_Checkout ? $checkout : WC()->checkout();
	$fields   = $checkout->get_checkout_fields( 'billing' );

	echo '<div class="woocommerce-billing-fields">';
	echo '<h2 class="bl-co-section-title">Contact</h2>';
	do_action( 'woocommerce_before_checkout_billing_form', $checkout );
	echo '<div class="woocommerce-billing-fields__field-wrapper bl-co-contact-fields">';
	if ( isset( $fields['billing_email'] ) ) {
		woocommerce_form_field( 'billing_email', $fields['billing_email'], $checkout->get_value( 'billing_email' ) );
	}
	echo '</div><h2 class="bl-co-section-title">Shipping address</h2><div class="woocommerce-billing-fields__field-wrapper bl-co-billing-fields">';
	foreach ( $fields as $key => $field ) {
		if ( 'billing_email' !== $key ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
	}
	echo '</div>';
	do_action( 'woocommerce_after_checkout_billing_form', $checkout );

	if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) {
		echo '<div class="woocommerce-account-fields">';
		if ( ! $checkout->is_registration_required() ) {
			echo '<p class="form-row form-row-wide create-account"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" ' . checked( ( true === $checkout->get_value( 'createaccount' ) || true === apply_filters( 'woocommerce_create_account_default_checked', false ) ), true, false ) . ' type="checkbox" name="createaccount" value="1" /> <span>' . esc_html__( 'Create an account?', 'woocommerce' ) . '</span></label></p>';
		}
		do_action( 'woocommerce_before_checkout_registration_form', $checkout );
		$account_fields = $checkout->get_checkout_fields( 'account' );
		if ( $account_fields ) {
			echo '<div class="create-account">';
			foreach ( $account_fields as $key => $field ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
			echo '</div>';
		}
		do_action( 'woocommerce_after_checkout_registration_form', $checkout );
		echo '</div>';
	}

	echo '</div>';
}

function bl_checkout_render_shipping_fields( $checkout = null ) {
	if ( ! WC()->cart->needs_shipping_address() ) {
		return;
	}
	$checkout = $checkout instanceof WC_Checkout ? $checkout : WC()->checkout();
	echo '<div class="woocommerce-shipping-fields"><h3 id="ship-to-different-address"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" ' . checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1, false ) . ' type="checkbox" name="ship_to_different_address" value="1" /> <span>' . esc_html__( 'Ship to a different address?', 'woocommerce' ) . '</span></label></h3><div class="shipping_address">';
	do_action( 'woocommerce_before_checkout_shipping_form', $checkout );
	echo '<div class="woocommerce-shipping-fields__field-wrapper">';
	foreach ( $checkout->get_checkout_fields( 'shipping' ) as $key => $field ) {
		woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
	}
	echo '</div>';
	do_action( 'woocommerce_after_checkout_shipping_form', $checkout );
	echo '</div></div>';
}

function bl_checkout_render_order_notes() {
	$checkout     = WC()->checkout();
	$order_fields = $checkout->get_checkout_fields( 'order' );
	do_action( 'woocommerce_before_order_notes', $checkout );
	if ( $order_fields ) {
		echo '<div class="woocommerce-additional-fields"><h2 class="bl-co-section-title">' . esc_html__( 'Order notes (optional)', 'woocommerce' ) . '</h2><div class="woocommerce-additional-fields__field-wrapper">';
		foreach ( $order_fields as $key => $field ) {
			if ( 'order_comments' === $key ) {
				$field['label_class'] = array_unique( array_merge( (array) ( $field['label_class'] ?? array() ), array( 'screen-reader-text' ) ) );
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		echo '</div></div>';
	}
	do_action( 'woocommerce_after_order_notes', $checkout );
}

function bl_checkout_address_summary() {
	$checkout         = WC()->checkout();
	$use_shipping     = WC()->cart->needs_shipping_address() && ( '1' === (string) $checkout->get_value( 'ship_to_different_address' ) || ( '' === (string) $checkout->get_value( 'ship_to_different_address' ) && 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ) );
	$prefix           = $use_shipping ? 'shipping_' : 'billing_';
	$country          = (string) $checkout->get_value( $prefix . 'country' );
	$state             = (string) $checkout->get_value( $prefix . 'state' );
	$state_options     = WC()->countries->get_states( $country );
	$state_name        = isset( $state_options[ $state ] ) ? $state_options[ $state ] : $state;
	$country_name      = isset( WC()->countries->countries[ $country ] ) ? WC()->countries->countries[ $country ] : $country;
	$parts             = array(
		$checkout->get_value( $prefix . 'address_1' ),
		$checkout->get_value( $prefix . 'address_2' ),
		$checkout->get_value( $prefix . 'city' ),
		$state_name,
		$checkout->get_value( $prefix . 'postcode' ),
		$country_name,
	);
	$parts             = array_filter( array_map( 'trim', $parts ) );
	return implode( ', ', array_map( 'sanitize_text_field', $parts ) );
}

function bl_checkout_shipping_method_summary() {
	if ( ! WC()->cart->needs_shipping() ) {
		return '';
	}
	$chosen  = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$labels  = array();
	$packages = WC()->shipping()->get_packages();
	foreach ( $packages as $index => $package ) {
		$rates = isset( $package['rates'] ) ? $package['rates'] : array();
		$rate  = isset( $chosen[ $index ], $rates[ $chosen[ $index ] ] ) ? $rates[ $chosen[ $index ] ] : false;
		if ( $rate ) {
			$labels[] = wp_strip_all_tags( wc_cart_totals_shipping_method_label( $rate ) );
		}
	}
	return $labels ? implode( ', ', $labels ) : 'Calculated at next step';
}

function bl_checkout_shipping_value() {
	if ( ! WC()->cart->needs_shipping() ) {
		return '';
	}
	if ( ! WC()->customer->has_calculated_shipping() || ! WC()->cart->get_shipping_total() && ! WC()->session->get( 'chosen_shipping_methods' ) ) {
		return 'Calculated at next step';
	}
	$total = (float) WC()->cart->get_shipping_total();
	if ( WC()->cart->display_prices_including_tax() ) {
		$total += (float) WC()->cart->get_shipping_tax();
	}
	return $total <= 0 ? 'Free' : wc_price( $total );
}

function bl_checkout_render_review_summary( $include_method = false ) {
	$checkout       = WC()->checkout();
	$email          = $checkout->get_value( 'billing_email' );
	$use_shipping   = WC()->cart->needs_shipping_address() && ( '1' === (string) $checkout->get_value( 'ship_to_different_address' ) || ( '' === (string) $checkout->get_value( 'ship_to_different_address' ) && 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ) );
	$address        = bl_checkout_address_summary();
	$address_target = $use_shipping ? 'shipping_address_1' : 'billing_address_1';
	ob_start();
	?>
	<div class="bl-co-step-review">
		<div><strong>Contact</strong><span class="bl-co-contact-value"><?php echo esc_html( $email ? $email : 'Add your email address' ); ?></span><button type="button" data-co-change="information" data-co-focus="billing_email">Change</button></div>
		<?php if ( WC()->cart->needs_shipping() ) : ?>
			<div><strong>Ship to</strong><span class="bl-co-address-value"><?php echo esc_html( $address ? $address : 'Add your shipping address' ); ?></span><button type="button" data-co-change="information" data-co-focus="<?php echo esc_attr( $address_target ); ?>">Change</button></div>
			<?php if ( $include_method ) : ?>
				<div><strong>Method</strong><span class="bl-co-method-value"><?php echo esc_html( bl_checkout_shipping_method_summary() ); ?></span><button type="button" data-co-change="shipping" data-co-focus="bl-co-shipping-methods">Change</button></div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function bl_checkout_shipping_methods_html() {
	if ( ! WC()->cart->needs_shipping() ) {
		return '<div class="bl-co-shipping-methods" hidden></div>';
	}
	$packages = WC()->shipping()->get_packages();
	if ( empty( $packages ) ) {
		WC()->cart->calculate_shipping();
		$packages = WC()->shipping()->get_packages();
	}
	$chosen   = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$has_destination = WC()->customer->has_calculated_shipping();
	ob_start();
	?>
	<div class="bl-co-shipping-methods">
		<?php if ( empty( $packages ) ) : ?>
			<p><?php echo esc_html( $has_destination ? __( 'There are no shipping options available for this address.', 'woocommerce' ) : __( 'Enter your address to view shipping options.', 'woocommerce' ) ); ?></p>
		<?php else : ?>
			<?php foreach ( $packages as $index => $package ) : ?>
				<?php $rates = isset( $package['rates'] ) ? $package['rates'] : array(); ?>
				<?php if ( $rates ) : ?>
					<ul class="woocommerce-shipping-methods">
						<?php foreach ( $rates as $rate ) : ?>
							<?php $rate_id = $rate->get_id(); $input_id = 'shipping_method_' . $index . '_' . sanitize_title( $rate_id ); ?>
							<li>
								<input type="radio" name="shipping_method[<?php echo esc_attr( (string) $index ); ?>]" data-index="<?php echo esc_attr( (string) $index ); ?>" id="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $rate_id ); ?>" class="shipping_method" <?php checked( isset( $chosen[ $index ] ) ? $chosen[ $index ] : '', $rate_id ); ?>>
								<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo wp_kses_post( wc_cart_totals_shipping_method_label( $rate ) ); ?></label>
								<?php do_action( 'woocommerce_after_shipping_rate', $rate, $index ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! $has_destination ) : ?>
					<p><?php esc_html_e( 'Enter your address to view shipping options.', 'woocommerce' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'There are no shipping options available for this address.', 'woocommerce' ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

add_filter(
	'woocommerce_update_order_review_fragments',
	function ( $fragments ) {
		$fragments['.bl-co-shipping-methods'] = bl_checkout_shipping_methods_html();
		return $fragments;
	}
);
