<?php
/**
 * Email sign-up popup. Each new subscriber gets a single-use percentage coupon.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_newsletter_discount() {
	return (int) apply_filters( 'dewier_newsletter_discount', 10 );
}

function dewier_newsletter_register() {
	register_post_type(
		'dw_subscriber',
		array(
			'labels'          => array(
				'name'          => __( 'Subscribers', 'dewier' ),
				'singular_name' => __( 'Subscriber', 'dewier' ),
				'all_items'     => __( 'Subscribers', 'dewier' ),
				'search_items'  => __( 'Search subscribers', 'dewier' ),
				'not_found'     => __( 'No subscribers yet.', 'dewier' ),
				'edit_item'     => __( 'Subscriber', 'dewier' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'woocommerce',
			'capability_type' => 'shop_coupon',
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'supports'        => array( 'title' ),
			'rewrite'         => false,
			'query_var'       => false,
		)
	);
}
add_action( 'init', 'dewier_newsletter_register' );

function dewier_newsletter_routes() {
	register_rest_route(
		'dewier/v1',
		'/subscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'dewier_newsletter_subscribe',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'dewier_newsletter_routes' );

function dewier_newsletter_find( $email ) {
	$ids = get_posts(
		array(
			'post_type'   => 'dw_subscriber',
			'post_status' => 'any',
			'name'        => md5( $email ),
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Whether a sign-up coupon can still be used.
 */
function dewier_newsletter_code_open( $code ) {
	$coupon = new WC_Coupon( $code );
	return $coupon->get_id() && ( ! $coupon->get_usage_limit() || $coupon->get_usage_count() < $coupon->get_usage_limit() );
}

function dewier_newsletter_subscribe( WP_REST_Request $request ) {
	if ( ! class_exists( 'WC_Coupon' ) ) {
		return new WP_Error( 'dewier_unavailable', __( 'Sign-up is not available right now.', 'dewier' ), array( 'status' => 503 ) );
	}
	// Bots fill the hidden field; people never see it.
	if ( '' !== (string) $request->get_param( 'company' ) ) {
		return new WP_Error( 'dewier_invalid', __( 'Please try again.', 'dewier' ), array( 'status' => 400 ) );
	}
	$email = strtolower( sanitize_email( (string) $request->get_param( 'email' ) ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'dewier_email', __( 'Please enter a valid email address.', 'dewier' ), array( 'status' => 400 ) );
	}

	$ip    = class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : '';
	$limit = 'dewier_nl_' . md5( $ip );
	$tries = (int) get_transient( $limit );
	if ( $tries >= 5 ) {
		return new WP_Error( 'dewier_limit', __( 'Too many sign-ups from this connection. Please try again later.', 'dewier' ), array( 'status' => 429 ) );
	}
	set_transient( $limit, $tries + 1, HOUR_IN_SECONDS );

	$existing = dewier_newsletter_find( $email );
	if ( $existing ) {
		$code = (string) get_post_meta( $existing, '_dw_coupon', true );
		if ( $code && dewier_newsletter_code_open( $code ) ) {
			return array( 'code' => $code, 'message' => __( 'You are already on the list. Here is your code again.', 'dewier' ) );
		}
		return new WP_Error( 'dewier_used', __( 'You are already on the list, and your welcome code has been used.', 'dewier' ), array( 'status' => 409 ) );
	}

	$percent = dewier_newsletter_discount();
	$code    = 'MZ' . $percent . '-' . strtoupper( wp_generate_password( 6, false ) );
	$coupon  = new WC_Coupon();
	$coupon->set_code( $code );
	$coupon->set_discount_type( 'percent' );
	$coupon->set_amount( $percent );
	$coupon->set_usage_limit( 1 );
	$coupon->set_individual_use( true );
	/* translators: %s: email address */
	$coupon->set_description( sprintf( __( 'Email sign-up: %s', 'dewier' ), $email ) );
	$coupon->save();

	$id = wp_insert_post(
		array(
			'post_type'   => 'dw_subscriber',
			'post_status' => 'publish',
			'post_title'  => $email,
			'post_name'   => md5( $email ),
		)
	);
	update_post_meta( $id, '_dw_coupon', $code );

	dewier_newsletter_mail( $email, $code, $percent );

	return array( 'code' => $code, 'message' => __( 'You are in. Here is your code.', 'dewier' ) );
}

function dewier_newsletter_mail( $email, $code, $percent ) {
	$mailer = WC()->mailer();
	/* translators: %d: discount percentage */
	$heading = sprintf( __( 'Here is %d%% off', 'dewier' ), $percent );
	/* translators: 1: coupon code, 2: shop URL */
	$body = sprintf( __( '<p>Thanks for joining. Use this code at checkout:</p><p style="font-size:20px;letter-spacing:2px"><strong>%1$s</strong></p><p>It works once, on any order. <a href="%2$s">Start shopping</a>.</p>', 'dewier' ), esc_html( $code ), esc_url( wc_get_page_permalink( 'shop' ) ) );
	$mailer->send( $email, $heading, $mailer->wrap_message( $heading, $body ) );
}

/**
 * Subscribers list: coupon column and CSV export.
 */
function dewier_newsletter_columns( $columns ) {
	return array(
		'cb'        => $columns['cb'],
		'title'     => __( 'Email', 'dewier' ),
		'dw_coupon' => __( 'Coupon', 'dewier' ),
		'dw_used'   => __( 'Used', 'dewier' ),
		'date'      => $columns['date'],
	);
}
add_filter( 'manage_dw_subscriber_posts_columns', 'dewier_newsletter_columns' );

function dewier_newsletter_column( $column, $post_id ) {
	$code = (string) get_post_meta( $post_id, '_dw_coupon', true );
	if ( 'dw_coupon' === $column ) {
		echo esc_html( $code );
	} elseif ( 'dw_used' === $column && $code ) {
		echo esc_html( dewier_newsletter_code_open( $code ) ? __( 'No', 'dewier' ) : __( 'Yes', 'dewier' ) );
	}
}
add_action( 'manage_dw_subscriber_posts_custom_column', 'dewier_newsletter_column', 10, 2 );

function dewier_newsletter_views( $views ) {
	$views['dw_export'] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=dewier_subscribers_csv' ), 'dewier_subscribers_csv' ) ), esc_html__( 'Export CSV', 'dewier' ) );
	return $views;
}
add_filter( 'views_edit-dw_subscriber', 'dewier_newsletter_views' );

function dewier_newsletter_export() {
	if ( ! current_user_can( 'edit_shop_coupons' ) ) {
		wp_die( esc_html__( 'You are not allowed to export subscribers.', 'dewier' ), 403 );
	}
	check_admin_referer( 'dewier_subscribers_csv' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, array( 'email', 'coupon', 'used', 'date' ) );
	$ids = get_posts( array( 'post_type' => 'dw_subscriber', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $id ) {
		$code = (string) get_post_meta( $id, '_dw_coupon', true );
		// A leading "=", "+", "-" or "@" would run as a formula in spreadsheet apps.
		$row = array( get_the_title( $id ), $code, $code && ! dewier_newsletter_code_open( $code ) ? 'yes' : 'no', get_the_date( 'Y-m-d H:i', $id ) );
		fputcsv( $out, array_map( static function ( $value ) {
			return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
		}, $row ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_dewier_subscribers_csv', 'dewier_newsletter_export' );

/**
 * Popup markup. It stays hidden until the script decides to show it.
 */
function dewier_newsletter_popup() {
	if ( ! function_exists( 'is_cart' ) || is_cart() || is_checkout() || is_account_page() || ! apply_filters( 'dewier_newsletter_popup', true ) ) {
		return;
	}
	$percent = dewier_newsletter_discount();
	$privacy = get_privacy_policy_url();
	?>
	<div class="dw-nl" id="dw-nl" data-endpoint="<?php echo esc_url( rest_url( 'dewier/v1/subscribe' ) ); ?>" hidden>
		<div class="dw-nl__backdrop" data-nl-close></div>
		<div class="dw-nl__dialog" role="dialog" aria-modal="true" aria-labelledby="dw-nl-title" tabindex="-1">
			<button type="button" class="dw-nl__close" data-nl-close aria-label="<?php esc_attr_e( 'Close', 'dewier' ); ?>"><?php echo dewier_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="dw-nl__media"><img src="<?php echo esc_url( DEWIER_URI . '/assets/images/gift.jpg' ); ?>" alt="" loading="lazy" width="600" height="750"></div>
			<div class="dw-nl__body">
				<p class="dw-eyebrow"><?php esc_html_e( 'Join the list', 'dewier' ); ?></p>
				<?php /* translators: %d: discount percentage */ ?>
				<h2 id="dw-nl-title" class="dw-nl__title"><?php echo esc_html( sprintf( __( 'Take %d%% off your first order', 'dewier' ), $percent ) ); ?></h2>
				<form class="dw-nl__form" novalidate>
					<p class="dw-nl__text"><?php esc_html_e( 'Be first to hear about launches, restocks and new routines. Your code shows up right away.', 'dewier' ); ?></p>
					<label class="screen-reader-text" for="dw-nl-email"><?php esc_html_e( 'Email address', 'dewier' ); ?></label>
					<div class="dw-nl__row">
						<input type="email" id="dw-nl-email" name="email" class="dw-nl__input" placeholder="<?php esc_attr_e( 'Email address', 'dewier' ); ?>" autocomplete="email" required>
						<?php /* translators: %d: discount percentage */ ?>
						<button type="submit" class="dw-btn dw-btn--dark"><span><?php echo esc_html( sprintf( __( 'Get %d%% off', 'dewier' ), $percent ) ); ?></span></button>
					</div>
					<input type="text" name="company" class="dw-nl__hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					<p class="dw-nl__msg" role="alert"></p>
					<p class="dw-nl__fine">
						<?php esc_html_e( 'By signing up you agree to receive emails from us. Unsubscribe any time.', 'dewier' ); ?>
						<?php if ( $privacy ) : ?>
							<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', 'dewier' ); ?></a>
						<?php endif; ?>
					</p>
				</form>
				<div class="dw-nl__done" hidden>
					<p class="dw-nl__text dw-nl__status" role="status"></p>
					<div class="dw-nl__code"><code></code><button type="button" class="dw-link" data-nl-copy data-copied="<?php esc_attr_e( 'Copied', 'dewier' ); ?>"><?php esc_html_e( 'Copy', 'dewier' ); ?></button></div>
					<p class="dw-nl__fine"><?php esc_html_e( 'Enter it at checkout. It works once and does not combine with other codes. We also sent it to your inbox.', 'dewier' ); ?></p>
					<?php echo dewier_button( __( 'Start shopping', 'dewier' ), wc_get_page_permalink( 'shop' ), 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<button type="button" class="dw-link dw-nl__skip" data-nl-close><?php esc_html_e( 'No thanks', 'dewier' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'dewier_newsletter_popup' );
