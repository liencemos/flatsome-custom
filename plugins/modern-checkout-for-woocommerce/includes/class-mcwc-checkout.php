<?php
/**
 * Checkout rendering, templates, assets, and public hooks.
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class MCWC_Checkout {
	public static function init() {
		add_filter( 'woocommerce_checkout_fields', 'mcwc_checkout_fields_priority' );
		add_action( 'wp', 'mcwc_prepare_checkout', 20 );
		add_action( 'woocommerce_checkout_update_order_review', 'mcwc_ajax_setup', 1 );
		add_action( 'template_include', 'mcwc_template_include', 99 );
		add_filter( 'woocommerce_locate_template', 'mcwc_locate_template', 999, 3 );
		add_action( 'wp_enqueue_scripts', 'mcwc_enqueue_assets', 50 );
		add_action( 'wp_enqueue_scripts', 'mcwc_print_css_variables', 51 );
		add_filter( 'wp_resource_hints', 'mcwc_resource_hints', 10, 2 );
		add_filter( 'woocommerce_update_order_review_fragments', 'mcwc_update_order_review_fragments' );
	}
}

function mcwc_is_active() {
	$is_checkout = ! empty( $GLOBALS['mcwc_checkout_ajax'] );
	if ( ! $is_checkout && function_exists( 'is_checkout' ) ) {
		$is_checkout = is_checkout()
			&& ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() )
			&& ( ! function_exists( 'is_checkout_pay_page' ) || ! is_checkout_pay_page() );
	}

	return (bool) apply_filters( 'mcwc_is_active', mcwc_is_enabled() && $is_checkout );
}

function mcwc_ajax_setup( $post_data ) {
	parse_str( (string) $post_data, $posted_data );
	if ( isset( $posted_data['mcwc_checkout'] ) && '1' === $posted_data['mcwc_checkout'] ) {
		$GLOBALS['mcwc_checkout_ajax'] = true;
		mcwc_prepare_checkout();
	}
}

function mcwc_checkout_fields_priority( $fields ) {
	if ( mcwc_is_active() && isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['priority'] = 5;
	}
	return $fields;
}

function mcwc_prepare_checkout() {
	if ( ! mcwc_is_active() || ! function_exists( 'WC' ) || ! WC()->checkout() ) {
		return;
	}

	$checkout = WC()->checkout();
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	remove_action( 'woocommerce_checkout_after_order_review', 'wc_checkout_privacy_policy_text', 1 );
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20 );
	add_action( 'woocommerce_review_order_before_submit', 'wc_checkout_privacy_policy_text', 5 );
	if ( function_exists( 'flatsome_html_checkout_sidebar' ) ) {
		remove_action( 'woocommerce_checkout_after_order_review', 'flatsome_html_checkout_sidebar', 10 );
	}
	remove_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_billing' ), 10 );
	remove_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_shipping' ), 10 );
	remove_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ), 10 );
	add_action( 'woocommerce_checkout_billing', 'mcwc_checkout_render_billing_fields', 10 );
	add_action( 'woocommerce_checkout_shipping', 'mcwc_checkout_render_shipping_fields', 10 );
}

function mcwc_template_include( $template ) {
	return mcwc_is_active() ? MCWC_DIR . 'templates/page-checkout.php' : $template;
}

function mcwc_locate_template( $template, $template_name, $template_path ) {
	if ( ! mcwc_is_active() || ! in_array( $template_name, array( 'checkout/form-checkout.php', 'checkout/review-order.php' ), true ) ) {
		return $template;
	}
	$theme_override = trailingslashit( get_stylesheet_directory() ) . 'modern-checkout-for-woocommerce/' . $template_name;
	if ( file_exists( $theme_override ) ) {
		return $theme_override;
	}
	$plugin_template = MCWC_DIR . 'templates/' . $template_name;
	return file_exists( $plugin_template ) ? $plugin_template : $template;
}

function mcwc_enqueue_assets() {
	if ( ! mcwc_is_active() ) {
		return;
	}
	wp_enqueue_style( 'mcwc-checkout', MCWC_URL . 'assets/css/checkout.css', array(), MCWC_VERSION );
	wp_enqueue_script( 'mcwc-checkout', MCWC_URL . 'assets/js/checkout.js', array( 'jquery', 'wc-checkout' ), MCWC_VERSION, true );
	wp_localize_script(
		'mcwc-checkout',
		'mcwcCheckoutI18n',
		array(
			'addEmail'        => __( 'Add your email address', 'modern-checkout-for-woocommerce' ),
			'addAddress'      => __( 'Add your shipping address', 'modern-checkout-for-woocommerce' ),
			'calculated'      => __( 'Calculated at next step', 'modern-checkout-for-woocommerce' ),
			'required'        => __( 'This field is required.', 'modern-checkout-for-woocommerce' ),
			'validEmail'      => __( 'Please enter a valid email address.', 'modern-checkout-for-woocommerce' ),
			'validZip'        => __( 'Enter a valid 5-digit ZIP code.', 'modern-checkout-for-woocommerce' ),
			'couponError'     => __( 'We could not apply that code. Please try again.', 'modern-checkout-for-woocommerce' ),
		)
	);
	$font = mcwc_active_font();
	if ( 'inherit' !== $font ) {
		$font_families = array(
			'DM Sans' => 'DM+Sans:wght@400;500;600;700',
			'Inter'   => 'Inter:wght@400;500;600;700',
			'Poppins' => 'Poppins:wght@400;500;600;700',
			'Roboto'  => 'Roboto:wght@400;500;600;700',
			'Lato'    => 'Lato:wght@400;700',
		);
		wp_enqueue_style( 'mcwc-google-font', 'https://fonts.googleapis.com/css2?family=' . rawurlencode( $font_families[ $font ] ) . '&display=swap', array(), MCWC_VERSION );
	}
}

function mcwc_resource_hints( $urls, $relation_type ) {
	if ( mcwc_is_active() && 'preconnect' === $relation_type && 'inherit' !== mcwc_active_font() ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}

function mcwc_print_css_variables() {
	if ( ! mcwc_is_active() ) {
		return;
	}
	$colors = array(
		'--mcwc-primary'     => sanitize_hex_color( mcwc_option( 'primary_color', '#133cd1' ) ) ?: '#133cd1',
		'--mcwc-text'        => sanitize_hex_color( mcwc_option( 'text_color', '#0b1057' ) ) ?: '#0b1057',
		'--mcwc-muted'       => sanitize_hex_color( mcwc_option( 'muted_color', '#5b6190' ) ) ?: '#5b6190',
		'--mcwc-summary-bg'  => sanitize_hex_color( mcwc_option( 'summary_background', '#f0f7ff' ) ) ?: '#f0f7ff',
		'--mcwc-border'      => sanitize_hex_color( mcwc_option( 'border_color', '#dfe3ef' ) ) ?: '#dfe3ef',
	);
	$font          = 'inherit' === mcwc_active_font() ? 'inherit' : '"' . mcwc_active_font() . '", sans-serif';
	$input_radius  = min( 48, max( 0, absint( mcwc_option( 'input_radius', 8 ) ) ) );
	$button_radius = min( 999, max( 0, absint( mcwc_option( 'button_radius', 999 ) ) ) );
	$logo_height   = min( 160, max( 8, absint( mcwc_option( 'logo_max_height', 32 ) ) ) );
	$variables     = '';
	foreach ( $colors as $name => $value ) {
		$variables .= $name . ':' . esc_attr( $value ) . ';';
	}
	$variables .= '--mcwc-font:' . $font . ';--mcwc-radius:' . $input_radius . 'px;--mcwc-btn-radius:' . $button_radius . 'px;--mcwc-logo-height:' . $logo_height . 'px;';
	wp_add_inline_style( 'mcwc-checkout', 'body.mcwc-active{' . $variables . '}' );
}

function mcwc_checkout_render_billing_fields( $checkout = null ) {
	$checkout = $checkout instanceof WC_Checkout ? $checkout : WC()->checkout();
	$fields   = $checkout->get_checkout_fields( 'billing' );
	echo '<div class="woocommerce-billing-fields">';
	echo '<h2 class="mcwc-section-title">' . esc_html__( 'Contact', 'modern-checkout-for-woocommerce' ) . '</h2>';
	do_action( 'woocommerce_before_checkout_billing_form', $checkout );
	echo '<div class="woocommerce-billing-fields__field-wrapper mcwc-contact-fields">';
	if ( isset( $fields['billing_email'] ) ) {
		woocommerce_form_field( 'billing_email', $fields['billing_email'], $checkout->get_value( 'billing_email' ) );
	}
	echo '</div><h2 class="mcwc-section-title">' . esc_html__( 'Shipping address', 'modern-checkout-for-woocommerce' ) . '</h2><div class="woocommerce-billing-fields__field-wrapper mcwc-billing-fields">';
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
			echo '<p class="form-row form-row-wide create-account"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" ' . checked( ( true === $checkout->get_value( 'createaccount' ) || true === apply_filters( 'woocommerce_create_account_default_checked', false ) ), true, false ) . ' type="checkbox" name="createaccount" value="1" /> <span>' . esc_html__( 'Create an account?', 'modern-checkout-for-woocommerce' ) . '</span></label></p>';
		}
		do_action( 'woocommerce_before_checkout_registration_form', $checkout );
		if ( $checkout->get_checkout_fields( 'account' ) ) {
			echo '<div class="create-account">';
			foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) {
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
			}
			echo '<div class="clear"></div></div>';
		}
		do_action( 'woocommerce_after_checkout_registration_form', $checkout );
		echo '</div>';
	}
	echo '</div>';
}

function mcwc_checkout_render_shipping_fields( $checkout = null ) {
	if ( ! WC()->cart->needs_shipping_address() ) {
		return;
	}
	$checkout = $checkout instanceof WC_Checkout ? $checkout : WC()->checkout();
	$checked  = $checkout->get_value( 'ship_to_different_address' );
	if ( '' === (string) $checked ) {
		$checked = 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0;
	}
	echo '<div class="woocommerce-shipping-fields"><h3 id="ship-to-different-address"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" ' . checked( apply_filters( 'woocommerce_ship_to_different_address_checked', $checked ), 1, false ) . ' type="checkbox" name="ship_to_different_address" value="1" /> <span>' . esc_html__( 'Ship to a different address?', 'modern-checkout-for-woocommerce' ) . '</span></label></h3><div class="shipping_address">';
	do_action( 'woocommerce_before_checkout_shipping_form', $checkout );
	echo '<div class="woocommerce-shipping-fields__field-wrapper">';
	foreach ( $checkout->get_checkout_fields( 'shipping' ) as $key => $field ) {
		woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
	}
	echo '</div>';
	do_action( 'woocommerce_after_checkout_shipping_form', $checkout );
	echo '</div></div>';
}

function mcwc_checkout_render_order_notes() {
	if ( 'yes' !== mcwc_option( 'show_order_notes', 'yes' ) ) {
		return;
	}
	$checkout = WC()->checkout();
	$fields   = $checkout->get_checkout_fields( 'order' );
	do_action( 'woocommerce_before_order_notes', $checkout );
	if ( $fields ) {
		echo '<div class="woocommerce-additional-fields"><h2 class="mcwc-section-title">' . esc_html__( 'Order notes (optional)', 'modern-checkout-for-woocommerce' ) . '</h2><div class="woocommerce-additional-fields__field-wrapper">';
		foreach ( $fields as $key => $field ) {
			if ( 'order_comments' === $key ) {
				$field['label_class'] = array_unique( array_merge( (array) ( $field['label_class'] ?? array() ), array( 'screen-reader-text' ) ) );
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		echo '</div></div>';
	}
	do_action( 'woocommerce_after_order_notes', $checkout );
}

function mcwc_checkout_address_summary() {
	$checkout     = WC()->checkout();
	$use_shipping = WC()->cart->needs_shipping_address() && ( '1' === (string) $checkout->get_value( 'ship_to_different_address' ) || ( '' === (string) $checkout->get_value( 'ship_to_different_address' ) && 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ) );
	$prefix       = $use_shipping ? 'shipping_' : 'billing_';
	$country      = (string) $checkout->get_value( $prefix . 'country' );
	$state        = (string) $checkout->get_value( $prefix . 'state' );
	$states       = WC()->countries->get_states( $country );
	$parts        = array(
		$checkout->get_value( $prefix . 'address_1' ),
		$checkout->get_value( $prefix . 'address_2' ),
		$checkout->get_value( $prefix . 'city' ),
		isset( $states[ $state ] ) ? $states[ $state ] : $state,
		$checkout->get_value( $prefix . 'postcode' ),
		WC()->countries->countries[ $country ] ?? $country,
	);
	$parts = array_filter( array_map( 'trim', $parts ) );
	return implode( ', ', array_map( 'sanitize_text_field', $parts ) );
}

function mcwc_checkout_shipping_method_summary() {
	if ( ! WC()->cart->needs_shipping() ) {
		return '';
	}
	$chosen   = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$labels   = array();
	$packages = WC()->shipping()->get_packages();
	foreach ( $packages as $index => $package ) {
		$rates = $package['rates'] ?? array();
		$rate  = isset( $chosen[ $index ], $rates[ $chosen[ $index ] ] ) ? $rates[ $chosen[ $index ] ] : false;
		if ( $rate ) {
			$labels[] = wp_strip_all_tags( wc_cart_totals_shipping_method_label( $rate ) );
		}
	}
	return $labels ? implode( ', ', $labels ) : __( 'Calculated at next step', 'modern-checkout-for-woocommerce' );
}

function mcwc_checkout_shipping_value() {
	if ( ! WC()->cart->needs_shipping() ) {
		return '';
	}
	if ( ! WC()->customer->has_calculated_shipping() || ( ! WC()->cart->get_shipping_total() && ! WC()->session->get( 'chosen_shipping_methods' ) ) ) {
		return __( 'Calculated at next step', 'modern-checkout-for-woocommerce' );
	}
	$total = (float) WC()->cart->get_shipping_total();
	if ( WC()->cart->display_prices_including_tax() ) {
		$total += (float) WC()->cart->get_shipping_tax();
	}
	return $total <= 0 ? __( 'Free', 'modern-checkout-for-woocommerce' ) : wc_price( $total );
}

function mcwc_checkout_render_review_summary( $include_method = false ) {
	$checkout       = WC()->checkout();
	$email          = $checkout->get_value( 'billing_email' );
	$use_shipping   = WC()->cart->needs_shipping_address() && ( '1' === (string) $checkout->get_value( 'ship_to_different_address' ) || ( '' === (string) $checkout->get_value( 'ship_to_different_address' ) && 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ) );
	$address        = mcwc_checkout_address_summary();
	$address_target = $use_shipping ? 'shipping_address_1' : 'billing_address_1';
	ob_start();
	?>
	<div class="mcwc-step-review">
		<div><strong><?php esc_html_e( 'Contact', 'modern-checkout-for-woocommerce' ); ?></strong><span class="mcwc-contact-value"><?php echo esc_html( $email ? $email : __( 'Add your email address', 'modern-checkout-for-woocommerce' ) ); ?></span><button type="button" data-co-change="information" data-co-focus="billing_email"><?php esc_html_e( 'Change', 'modern-checkout-for-woocommerce' ); ?></button></div>
		<?php if ( WC()->cart->needs_shipping() ) : ?>
			<div><strong><?php esc_html_e( 'Ship to', 'modern-checkout-for-woocommerce' ); ?></strong><span class="mcwc-address-value"><?php echo esc_html( $address ? $address : __( 'Add your shipping address', 'modern-checkout-for-woocommerce' ) ); ?></span><button type="button" data-co-change="information" data-co-focus="<?php echo esc_attr( $address_target ); ?>"><?php esc_html_e( 'Change', 'modern-checkout-for-woocommerce' ); ?></button></div>
			<?php if ( $include_method ) : ?>
				<div><strong><?php esc_html_e( 'Method', 'modern-checkout-for-woocommerce' ); ?></strong><span class="mcwc-method-value"><?php echo esc_html( mcwc_checkout_shipping_method_summary() ); ?></span><button type="button" data-co-change="shipping" data-co-focus="mcwc-shipping-methods"><?php esc_html_e( 'Change', 'modern-checkout-for-woocommerce' ); ?></button></div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function mcwc_checkout_shipping_methods_html() {
	if ( ! WC()->cart->needs_shipping() ) {
		return '<div class="mcwc-shipping-methods" hidden></div>';
	}
	$packages = WC()->shipping()->get_packages();
	if ( empty( $packages ) ) {
		WC()->cart->calculate_shipping();
		$packages = WC()->shipping()->get_packages();
	}
	$chosen           = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$has_destination  = WC()->customer->has_calculated_shipping();
	ob_start();
	?>
	<div class="mcwc-shipping-methods">
		<?php if ( empty( $packages ) ) : ?>
			<p><?php echo esc_html( $has_destination ? __( 'There are no shipping options available for this address.', 'modern-checkout-for-woocommerce' ) : __( 'Enter your address to view shipping options.', 'modern-checkout-for-woocommerce' ) ); ?></p>
		<?php else : ?>
			<?php foreach ( $packages as $index => $package ) : ?>
				<?php $rates = $package['rates'] ?? array(); ?>
				<?php if ( $rates ) : ?>
					<ul class="woocommerce-shipping-methods">
						<?php foreach ( $rates as $rate ) : ?>
							<?php $rate_id = $rate->get_id(); $input_id = 'mcwc_shipping_method_' . $index . '_' . sanitize_title( $rate_id ); ?>
							<li>
								<input type="radio" name="shipping_method[<?php echo esc_attr( (string) $index ); ?>]" data-index="<?php echo esc_attr( (string) $index ); ?>" id="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $rate_id ); ?>" class="shipping_method" <?php checked( $chosen[ $index ] ?? '', $rate_id ); ?>>
								<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo wp_kses_post( wc_cart_totals_shipping_method_label( $rate ) ); ?></label>
								<?php do_action( 'woocommerce_after_shipping_rate', $rate, $index ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! $has_destination ) : ?>
					<p><?php esc_html_e( 'Enter your address to view shipping options.', 'modern-checkout-for-woocommerce' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'There are no shipping options available for this address.', 'modern-checkout-for-woocommerce' ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function mcwc_find_free_shipping_method() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	$packages = WC()->cart->get_shipping_packages();
	$customer = WC()->customer;
	$has_destination = $customer && $customer->has_calculated_shipping();
	if ( ! $has_destination ) {
		$base = wc_get_base_location();
		$destination = array(
			'country'   => $base['country'],
			'state'     => $base['state'],
			'postcode'  => get_option( 'woocommerce_store_postcode' ),
			'city'      => get_option( 'woocommerce_store_city' ),
			'address'   => get_option( 'woocommerce_store_address' ),
			'address_1' => get_option( 'woocommerce_store_address' ),
			'address_2' => get_option( 'woocommerce_store_address_2' ),
		);
	} else {
		$destination = array(
			'country'   => $customer->get_shipping_country(),
			'state'     => $customer->get_shipping_state(),
			'postcode'  => $customer->get_shipping_postcode(),
			'city'      => $customer->get_shipping_city(),
			'address'   => $customer->get_shipping_address(),
			'address_1' => $customer->get_shipping_address_1(),
			'address_2' => $customer->get_shipping_address_2(),
		);
	}

	if ( empty( $packages ) ) {
		$packages = array( array( 'destination' => $destination ) );
	} elseif ( ! $has_destination ) {
		foreach ( $packages as &$package ) {
			$package['destination'] = $destination;
		}
		unset( $package );
	}

	foreach ( $packages as $package ) {
		$zone = WC_Shipping_Zones::get_zone_matching_package( $package );
		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			$minimum = (float) $method->get_option( 'min_amount', 0 );
			if ( 'free_shipping' === $method->id && 'yes' === $method->enabled && $minimum > 0 ) {
				return $method;
			}
		}
	}

	return false;
}

function mcwc_find_shipping_threshold() {
	$method = mcwc_find_free_shipping_method();
	return $method ? (float) $method->get_option( 'min_amount', 0 ) : null;
}

function mcwc_free_shipping_threshold() {
	$override  = mcwc_option( 'free_shipping_threshold_override', '' );
	$base      = '' !== (string) $override ? (float) $override : mcwc_find_shipping_threshold();
	$threshold = apply_filters( 'mcwc_free_shipping_threshold', $base );
	return is_numeric( $threshold ) && (float) $threshold > 0 ? (float) $threshold : null;
}

function mcwc_free_shipping_eligible_subtotal() {
	$subtotal = 0.0;
	if ( function_exists( 'WC' ) && WC()->cart ) {
		$cart             = WC()->cart;
		$method           = mcwc_find_free_shipping_method();
		$exclude_virtual  = 'yes' === mcwc_option( 'exclude_virtual', 'yes' );
		$has_override     = '' !== (string) mcwc_option( 'free_shipping_threshold_override', '' );
		if ( ! $method && $has_override ) {
			foreach ( $cart->get_cart() as $cart_item ) {
				$product = $cart_item['data'] ?? null;
				if ( $exclude_virtual && $product instanceof WC_Product && $product->is_virtual() ) {
					continue;
				}
				$subtotal += isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0.0;
			}
		} elseif ( $method ) {
			$includes_tax = $cart->display_prices_including_tax();
			$subtotal     = (float) $cart->get_displayed_subtotal();
			if ( $includes_tax ) {
				$subtotal -= (float) $cart->get_discount_tax();
			}

			$apply_discounts = 'no' === $method->get_option( 'ignore_discounts', 'no' );
			if ( $apply_discounts ) {
				$subtotal -= (float) $cart->get_discount_total();
			}

			if ( $exclude_virtual ) {
				$virtual_subtotal = 0.0;
				$virtual_discount = 0.0;
				foreach ( $cart->get_cart() as $cart_item ) {
					$product = $cart_item['data'] ?? null;
					if ( ! $product instanceof WC_Product || ! $product->is_virtual() ) {
						continue;
					}
					$line_subtotal = (float) ( $cart_item['line_subtotal'] ?? 0 );
					$line_tax      = (float) ( $cart_item['line_subtotal_tax'] ?? 0 );
					$virtual_subtotal += $line_subtotal + ( $includes_tax ? $line_tax : 0 );
					if ( $apply_discounts ) {
						$virtual_discount += max( 0, $line_subtotal - (float) ( $cart_item['line_total'] ?? 0 ) );
						if ( $includes_tax ) {
							$virtual_discount += max( 0, $line_tax - (float) ( $cart_item['line_tax'] ?? 0 ) );
						}
					}
				}
				$subtotal -= $virtual_subtotal;
				if ( $apply_discounts ) {
					$subtotal += $virtual_discount;
				}
			}
		}
	}
	$subtotal = round( max( 0, $subtotal ), wc_get_price_decimals() );
	return (float) apply_filters( 'mcwc_free_shipping_eligible_subtotal', $subtotal );
}

function mcwc_free_shipping_progress_html() {
	if ( 'yes' !== mcwc_option( 'show_free_shipping', 'yes' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$threshold = mcwc_free_shipping_threshold();
	if ( null === $threshold ) {
		return '';
	}
	$total     = mcwc_free_shipping_eligible_subtotal();
	$remaining = max( 0, $threshold - $total );
	$progress  = min( 100, ( $total / $threshold ) * 100 );
	if ( $remaining > 0 ) {
		/* translators: %s: Amount remaining until free shipping. */
		$message = sprintf( __( 'You’re %s away from free shipping', 'modern-checkout-for-woocommerce' ), wc_price( $remaining ) );
	} else {
		$message = __( 'You’ve unlocked free shipping!', 'modern-checkout-for-woocommerce' );
	}
	ob_start();
	?>
	<div class="mcwc-shipping-progress">
		<p><?php echo wp_kses_post( $message ); ?></p>
		<div role="progressbar" aria-label="<?php esc_attr_e( 'Free shipping progress', 'modern-checkout-for-woocommerce' ); ?>" aria-valuenow="<?php echo esc_attr( (string) round( $progress ) ); ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?php echo esc_attr( (string) $progress ); ?>%"></span></div>
		<small><?php echo wp_kses_post( wc_price( 0 ) ); ?><span>
			<?php /* translators: %s: Free-shipping threshold amount. */ ?>
			<?php printf( esc_html__( 'Free shipping at %s', 'modern-checkout-for-woocommerce' ), wp_kses_post( wc_price( $threshold ) ) ); ?>
		</span></small>
	</div>
	<?php
	return ob_get_clean();
}

function mcwc_trust_items() {
	$defaults = array(
		array( __( 'Free shipping on $50+', 'modern-checkout-for-woocommerce' ), 'truck' ),
		array( __( '100-day returns', 'modern-checkout-for-woocommerce' ), 'return' ),
		array( __( 'Secure checkout', 'modern-checkout-for-woocommerce' ), 'lock' ),
	);
	$items = array();
	foreach ( $defaults as $index => $default ) {
		$number = $index + 1;
		$text   = sanitize_text_field( mcwc_option( 'trust_' . $number . '_text', $default[0] ) );
		$icon   = mcwc_option( 'trust_' . $number . '_icon', $default[1] );
		if ( $text && in_array( $icon, array( 'truck', 'return', 'lock', 'shield', 'gift', 'leaf', 'star' ), true ) ) {
			$items[] = array(
				'text' => $text,
				'icon' => $icon,
			);
		}
	}
	return apply_filters( 'mcwc_trust_items', $items );
}

function mcwc_trust_icon_svg( $icon ) {
	$icons = array(
		'truck'  => '<path d="M3 6h11v11H3zM14 10h4l3 3v4h-7zM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM18 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
		'return' => '<path d="M4 7h11a5 5 0 0 1 0 10h-3"/><path d="m7 4-3 3 3 3"/><path d="m12 14-3 3 3 3"/>',
		'lock'   => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		'shield' => '<path d="M12 3 20 6v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
		'gift'   => '<rect x="3" y="9" width="18" height="12" rx="1"/><path d="M12 9v12M3 13h18M12 9H7.5a2.5 2.5 0 1 1 2.5-2.5V9zm0 0h4.5a2.5 2.5 0 1 0-2.5-2.5V9z"/>',
		'leaf'   => '<path d="M20 4c-8 0-14 3-14 10a6 6 0 0 0 6 6c7 0 8-8 8-16z"/><path d="M4 21c2-5 6-8 12-11"/>',
		'star'   => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>',
	);
	if ( ! isset( $icons[ $icon ] ) ) {
		return '';
	}
	return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">' . $icons[ $icon ] . '</svg>';
}

function mcwc_update_order_review_fragments( $fragments ) {
	if ( mcwc_is_active() || ! empty( $GLOBALS['mcwc_checkout_ajax'] ) ) {
		$fragments['.mcwc-shipping-methods'] = mcwc_checkout_shipping_methods_html();
		if ( 'yes' === mcwc_option( 'show_free_shipping', 'yes' ) ) {
			$fragments['.mcwc-shipping-progress'] = mcwc_free_shipping_progress_html();
		}
	}
	return $fragments;
}
