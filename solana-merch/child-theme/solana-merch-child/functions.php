<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'solana-merch', get_stylesheet_directory_uri() . '/assets/storefront.css', array( 'flatsome-main' ), wp_get_theme()->get( 'Version' ) );
	if ( function_exists( 'is_product' ) && is_product() && get_post_meta( get_queried_object_id(), '_signal_demo', true ) ) {
		wp_enqueue_script( 'form-edit-swatches', get_stylesheet_directory_uri() . '/assets/swatches.js', array( 'wc-add-to-cart-variation' ), wp_get_theme()->get( 'Version' ), true );
	}
}, 100 );

add_action( 'woocommerce_single_product_summary', function () {
	$product = wc_get_product( get_the_ID() );
	if ( $product && get_post_meta( $product->get_id(), '_signal_demo', true ) ) {
		echo '<p class="edit-code edit-accent">FORM / EDIT &nbsp;—&nbsp; EDITION 001</p>';
	}
}, 4 );

function form_edit_shipping_progress() {
	$zone_id = (int) get_option( 'form_edit_demo_shipping_zone' );
	$cart = WC()->cart;
	if ( ! $zone_id || ! $cart || $cart->is_empty() || ! $cart->needs_shipping() ) {
		return;
	}
	$packages = $cart->get_shipping_packages();
	if ( ! $packages ) {
		return;
	}
	foreach ( $packages as $package ) {
		$zone = WC_Shipping_Zones::get_zone_matching_package( $package );
		if ( $zone_id !== $zone->get_id() ) {
			return;
		}
	}
	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' !== $method->id || 'min_amount' !== $method->requires ) {
			continue;
		}
		$minimum = (float) $method->min_amount;
		$subtotal = $cart->get_displayed_subtotal();
		if ( 'no' === $method->ignore_discounts ) {
			$subtotal -= $cart->get_discount_total();
			if ( $cart->display_prices_including_tax() ) {
				$subtotal -= $cart->get_discount_tax();
			}
		}
		$subtotal = round( $subtotal, wc_get_price_decimals() );
		$left = max( 0, $minimum - $subtotal );
		$label = $left > 0 ? sprintf( '%s away from free US shipping', wc_price( $left ) ) : 'You qualify for free US shipping';
		echo '<div class="edit-shipping-progress"><p>' . wp_kses_post( $label ) . '</p><progress aria-label="Progress toward free shipping" max="100" value="' . esc_attr( $minimum > 0 ? min( 100, max( 0, $subtotal / $minimum * 100 ) ) : 100 ) . '"></progress></div>';
		break;
	}
}
add_action( 'woocommerce_before_cart_table', 'form_edit_shipping_progress' );
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'form_edit_shipping_progress' );

function form_edit_checkout_notes() {
	$policy = get_privacy_policy_url();
	return '<p class="edit-checkout-notes"><span>' . esc_html( is_ssl() ? 'Secure checkout' : 'Checkout details' ) . '</span>' .
		( $policy ? ' <a href="' . esc_url( $policy ) . '">Privacy policy</a>' : '' ) .
		'<span>For returns, keep your order number.</span></p>';
}

add_filter( 'render_block_woocommerce/checkout-actions-block', function ( $content ) {
	return $content . form_edit_checkout_notes();
} );
add_action( 'woocommerce_review_order_after_submit', function () {
	echo form_edit_checkout_notes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the renderer.
} );

function form_edit_delivery_date( $date, $days ) {
	while ( $days > 0 ) {
		$date = $date->modify( '+1 day' );
		if ( (int) $date->format( 'N' ) <= 5 ) {
			--$days;
		}
	}
	return wp_date( 'M j', $date->getTimestamp(), $date->getTimezone() );
}

add_action( 'woocommerce_single_product_summary', function () {
	$product = wc_get_product( get_the_ID() );
	if ( ! $product || ! $product->get_meta( '_signal_demo' ) || ! $product->needs_shipping() || ! $product->is_in_stock() ) {
		return;
	}
	list( $first, $last ) = apply_filters( 'form_edit_delivery_business_days', array( 3, 5 ), $product );
	$first = max( 0, (int) $first );
	$last = max( $first, (int) $last );
	$today = current_datetime();
	$start = form_edit_delivery_date( $today, $first );
	$end = form_edit_delivery_date( $today, $last );
	echo '<p class="edit-delivery"><strong>Estimated US delivery</strong> ' . esc_html( $start === $end ? $start : "$start – $end" ) . '</p>';
}, 27 );

add_filter( 'woocommerce_dropdown_variation_attribute_options_html', function ( $html, $args ) {
	$product = $args['product'];
	$name = sanitize_title( $args['attribute'] );
	if ( ! is_product() || ! $product || $product->get_id() !== get_queried_object_id() || ! $product->get_meta( '_signal_demo' ) || ! in_array( $name, array( 'size', 'color' ), true ) ) {
		return $html;
	}
	$options = $args['options'] ?: ( $product->get_variation_attributes()[ $name ] ?? array() );
	if ( ! $options ) {
		return $html;
	}
	$html .= '<div class="edit-swatches" data-attribute="' . esc_attr( $name ) . '" role="group" aria-label="' . esc_attr( wc_attribute_label( $name, $product ) ) . '" hidden>';
	foreach ( $options as $option ) {
		$html .= '<button type="button" class="edit-swatch" data-value="' . esc_attr( $option ) . '" aria-pressed="false">' . esc_html( $option ) . '</button>';
	}
	return $html . '</div>';
}, 20, 2 );

add_action( 'woocommerce_before_variations_form', function () {
	if ( is_product() && get_post_meta( get_queried_object_id(), '_form_edit_details', true ) ) {
		echo '<a class="edit-guide-link" href="#edit-size-guide">Size guide ↗</a>';
	}
} );

add_action( 'woocommerce_single_product_summary', function () {
	$details = get_post_meta( get_the_ID(), '_form_edit_details', true );
	if ( $details ) {
		echo do_shortcode( $details );
	}
}, 45 );

add_filter( 'woocommerce_product_upsells_products_heading', function ( $heading ) {
	return is_product() && get_post_meta( get_queried_object_id(), '_signal_demo', true ) ? 'Complete the look' : $heading;
} );

add_filter( 'flatsome_product_labels', function ( $html, $post, $product, $badge_style ) {
	if ( $product->is_in_stock() && $product->get_meta( '_form_edit_new' ) ) {
		$html .= '<div class="badge callout badge-' . esc_attr( $badge_style ) . '"><div class="badge-inner is-small edit-new-badge">New</div></div>';
	}
	return $html;
}, 15, 4 );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'id'            => 'form-edit-recent',
		'name'          => 'FORM / EDIT: Product discovery',
		'before_widget' => '<section id="%1$s" class="product-section edit-recent %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="product-section-title">',
		'after_title'   => '</h2>',
	) );
} );

add_action( 'woocommerce_after_single_product_summary', function () {
	dynamic_sidebar( 'form-edit-recent' );
}, 25 );

add_filter( 'woocommerce_recently_viewed_products_widget_query_args', function ( $args ) {
	if ( is_product() ) {
		$args['post__in'] = array_diff( $args['post__in'], array( get_queried_object_id() ) );
		$args['post__in'] = $args['post__in'] ?: array( 0 );
	}
	return $args;
} );

add_shortcode( 'form_edit_newsletter', function () {
	$status = isset( $_GET['studio_letter'] ) && is_string( $_GET['studio_letter'] ) ? sanitize_key( $_GET['studio_letter'] ) : '';
	ob_start();
	?>
	<form class="edit-letter-form" id="studio-letter" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="form_edit_newsletter">
		<?php wp_nonce_field( 'form_edit_newsletter', 'studio_letter_nonce', false ); ?>
		<div hidden>
			<label for="studio-letter-website">Leave this field empty</label>
			<input id="studio-letter-website" type="text" name="studio_letter_website" tabindex="-1" autocomplete="off">
		</div>
		<label for="studio-letter-email">Email address</label>
		<div class="edit-letter-fields">
			<input id="studio-letter-email" type="email" name="email" required autocomplete="email" maxlength="254">
			<button class="button primary" type="submit">Request signup</button>
		</div>
		<p class="edit-letter-note">Submitting asks the store to add your email to the studio mailing list.</p>
		<?php if ( 'sent' === $status ) : ?>
			<p role="status">Thanks. Your request was sent to the studio.</p>
		<?php elseif ( 'error' === $status ) : ?>
			<p role="status">We couldn’t send your request. Please try again later.</p>
		<?php endif; ?>
	</form>
	<?php
	return ob_get_clean();
} );

function form_edit_reserve_mail_request( $channel, $signature ) {
	global $wpdb;

	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	$prefix = 'form_edit_' . $channel . '_';
	$ip_key = $prefix . 'ip_' . wp_hash( $ip );
	$email_key = $prefix . 'email_' . wp_hash( $signature );
	$hour_key = $prefix . 'hour_' . intdiv( time(), HOUR_IN_SECONDS );
	$lock = $prefix . md5( DB_NAME . ':' . $wpdb->prefix );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) {
		return false;
	}

	try {
		$count = (int) get_transient( $hour_key );
		if ( $count >= 20 || get_transient( $ip_key ) || get_transient( $email_key ) ) {
			return false;
		}
		return set_transient( $hour_key, $count + 1, HOUR_IN_SECONDS )
			&& set_transient( $ip_key, 1, 10 * MINUTE_IN_SECONDS )
			&& set_transient( $email_key, 1, DAY_IN_SECONDS );
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

function form_edit_request_newsletter() {
	check_admin_referer( 'form_edit_newsletter', 'studio_letter_nonce' );
	$email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$sent = '' === ( $_POST['studio_letter_website'] ?? '' )
		&& strlen( $email ) <= 254
		&& is_email( $email )
		&& form_edit_reserve_mail_request( 'letter', strtolower( $email ) )
		&& wp_mail( get_option( 'admin_email' ), 'FORM / EDIT: studio letter signup request', 'Requested signup: ' . $email );
	wp_safe_redirect( add_query_arg( 'studio_letter', $sent ? 'sent' : 'error', home_url( '/' ) ) . '#studio-letter' );
	exit;
}
add_action( 'admin_post_form_edit_newsletter', 'form_edit_request_newsletter' );
add_action( 'admin_post_nopriv_form_edit_newsletter', 'form_edit_request_newsletter' );

add_shortcode( 'form_edit_policy_toc', function ( $atts, $content = '' ) {
	$list = wp_kses_post( trim( $content ?? '' ) );
	return '<nav class="edit-policy-toc edit-policy-toc-desktop" aria-label="On this page"><p class="edit-label">On this page</p>' . $list . '</nav>'
		. '<details class="edit-policy-toc edit-policy-toc-mobile"><summary>On this page</summary><nav aria-label="On this page">' . $list . '</nav></details>';
} );

add_shortcode( 'form_edit_contact', function () {
	$status = isset( $_GET['studio_contact'] ) && is_string( $_GET['studio_contact'] ) ? sanitize_key( wp_unslash( $_GET['studio_contact'] ) ) : '';
	$fields = get_query_var( 'form_edit_contact_invalid_phone', array() );
	$privacy = get_privacy_policy_url();
	ob_start();
	?>
	<form id="studio-contact" class="edit-contact-form" method="post" action="<?php echo esc_url( get_permalink( get_queried_object_id() ) . '#studio-contact' ); ?>">
		<input type="hidden" name="studio_contact_action" value="send">
		<?php wp_nonce_field( 'form_edit_contact', 'studio_contact_nonce', false ); ?>
		<div hidden>
			<label for="studio-contact-website">Leave this field empty</label>
			<input id="studio-contact-website" type="text" name="studio_contact_website" tabindex="-1" autocomplete="off">
		</div>
		<?php if ( ! $fields && 'sent' === $status ) : ?>
			<p class="edit-contact-status" role="status">Thanks. Your enquiry was sent to the studio.</p>
		<?php elseif ( ! $fields && 'error' === $status ) : ?>
			<p class="edit-contact-status" role="status">We couldn’t send your enquiry. Try again later or use the studio email.</p>
		<?php endif; ?>
		<div class="edit-contact-fields">
			<label for="studio-contact-name">Name <span aria-hidden="true">*</span>
				<input id="studio-contact-name" type="text" name="studio_contact_fields[name]" value="<?php echo esc_attr( $fields['name'] ?? '' ); ?>" autocomplete="name" maxlength="100" required>
			</label>
			<div>
				<label for="studio-contact-phone">Phone (optional)
					<input id="studio-contact-phone" type="tel" name="studio_contact_fields[phone]" value="<?php echo esc_attr( $fields['phone'] ?? '' ); ?>" autocomplete="tel" maxlength="40"<?php if ( $fields ) : ?> aria-invalid="true" aria-describedby="studio-contact-phone-error" autofocus<?php endif; ?>>
				</label>
				<?php if ( $fields ) : ?>
					<p id="studio-contact-phone-error" class="edit-page-caption" role="alert">Enter a phone number such as +1 (555) 012-3456 ext. 42, or leave it blank.</p>
				<?php endif; ?>
			</div>
			<label class="edit-contact-wide" for="studio-contact-email">Email address <span aria-hidden="true">*</span>
				<input id="studio-contact-email" type="email" name="studio_contact_fields[email]" value="<?php echo esc_attr( $fields['email'] ?? '' ); ?>" autocomplete="email" maxlength="254" required>
			</label>
			<label class="edit-contact-wide" for="studio-contact-message">Message <span aria-hidden="true">*</span>
				<textarea id="studio-contact-message" name="studio_contact_fields[message]" rows="5" maxlength="3000" required><?php echo esc_textarea( $fields['message'] ?? '' ); ?></textarea>
			</label>
		</div>
		<p class="edit-page-caption">Include an order number if relevant. Do not send payment details.<?php if ( $privacy ) : ?> <a href="<?php echo esc_url( $privacy ); ?>">Privacy Policy</a><?php endif; ?></p>
		<button class="button primary" type="submit">Send enquiry</button>
	</form>
	<?php
	return ob_get_clean();
} );

function form_edit_contact_message( $message ) {
	return trim( wp_strip_all_tags( wp_check_invalid_utf8( $message ) ) );
}

function form_edit_contact_valid_phone( $phone ) {
	return '' === $phone || ( strlen( $phone ) <= 40 && preg_match( '~^[0-9+()./ -]*[0-9][0-9+()./ -]*(?:(?:x|ext\.?) *[0-9]+)?$~iD', $phone ) );
}

function form_edit_request_contact() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || 'send' !== ( $_POST['studio_contact_action'] ?? '' )
		|| ! is_page() || ! has_shortcode( get_post_field( 'post_content', get_queried_object_id() ), 'form_edit_contact' ) ) {
		return;
	}
	check_admin_referer( 'form_edit_contact', 'studio_contact_nonce' );
	$submitted = $_POST['studio_contact_fields'] ?? array();
	$fields = array();
	foreach ( array( 'name', 'phone', 'email', 'message' ) as $key ) {
		$fields[ $key ] = is_array( $submitted ) && isset( $submitted[ $key ] ) && is_string( $submitted[ $key ] ) ? wp_unslash( $submitted[ $key ] ) : '';
	}
	$phone = trim( $fields['phone'], ' ' );
	$honeypot_empty = '' === ( $_POST['studio_contact_website'] ?? '' );
	if ( $honeypot_empty && ! form_edit_contact_valid_phone( $phone ) ) {
		set_query_var( 'form_edit_contact_invalid_phone', $fields );
		nocache_headers();
		status_header( 422 );
		return;
	}
	$name = sanitize_text_field( $fields['name'] );
	$email = sanitize_email( $fields['email'] );
	$message = form_edit_contact_message( $fields['message'] );
	$sent = $honeypot_empty
		&& '' !== $name && mb_strlen( $name ) <= 100
		&& strlen( $email ) <= 254 && is_email( $email )
		&& '' !== $message && mb_strlen( $message ) <= 3000
		&& form_edit_reserve_mail_request( 'contact', strtolower( $email ) . '|' . $message )
		&& wp_mail( get_option( 'admin_email' ), 'FORM / EDIT: studio enquiry', "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\n\n{$message}", array( 'Reply-To: ' . $email ) );
	wp_safe_redirect( add_query_arg( 'studio_contact', $sent ? 'sent' : 'error', get_permalink( get_queried_object_id() ) ) . '#studio-contact', 303 );
	exit;
}
add_action( 'template_redirect', 'form_edit_request_contact', 9 );
