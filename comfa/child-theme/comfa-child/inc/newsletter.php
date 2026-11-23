<?php
/**
 * Newsletter sign-up (footer form and welcome offer).
 *
 * Each address is stored as a private `cf_subscriber` post (listed under
 * "Subscribers" in wp-admin) and passed to the `cf_newsletter_signup` action
 * so a mailing service can be connected.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'cf_subscriber',
			array(
				'labels'              => array(
					'name'          => __( 'Subscribers', 'comfa' ),
					'singular_name' => __( 'Subscriber', 'comfa' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'menu_icon'           => 'dashicons-email',
				'supports'            => array( 'title' ),
				// Addresses are personal data: administrators only, and no manual entries.
				'capabilities'        => array(
					'edit_posts'              => 'manage_options',
					'edit_others_posts'       => 'manage_options',
					'edit_private_posts'      => 'manage_options',
					'edit_published_posts'    => 'manage_options',
					'delete_posts'            => 'manage_options',
					'delete_others_posts'     => 'manage_options',
					'delete_private_posts'    => 'manage_options',
					'delete_published_posts'  => 'manage_options',
					'publish_posts'           => 'manage_options',
					'read_private_posts'      => 'manage_options',
					'create_posts'            => 'do_not_allow',
				),
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'rewrite'             => false,
			)
		);
	}
);

/**
 * Store one sign-up. Returns true when the address is new.
 */
function cf_newsletter_store( $email ) {
	$email = strtolower( $email );
	$slug  = md5( $email );
	if ( get_posts( array( 'post_type' => 'cf_subscriber', 'name' => $slug, 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids' ) ) ) {
		return false;
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'cf_subscriber',
			'post_status' => 'private',
			'post_title'  => $email,
			'post_name'   => $slug,
		),
		true
	);
	return ! is_wp_error( $id );
}

function cf_newsletter_form( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'button'      => __( 'Sign Up', 'comfa' ),
			'placeholder' => __( 'Email address', 'comfa' ),
		),
		$atts,
		'cf_newsletter'
	);
	$id = wp_unique_id( 'cf-news-' );
	ob_start();
	?>
	<form class="cf-newsletter" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" novalidate>
		<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Email address', 'comfa' ); ?></label>
		<div class="cf-newsletter__row">
			<input id="<?php echo esc_attr( $id ); ?>" type="email" name="email" autocomplete="email" required placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>">
			<input type="text" name="company" tabindex="-1" autocomplete="off" class="cf-newsletter__hp" aria-hidden="true">
			<button type="submit" class="button cf-newsletter__button"><?php echo esc_html( $atts['button'] ); ?> <span aria-hidden="true">→</span></button>
		</div>
		<p class="cf-newsletter__message" role="status" aria-live="polite"></p>
	</form>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cf_newsletter', 'cf_newsletter_form' );

function cf_newsletter_submit() {
	check_ajax_referer( 'cf_newsletter', 'nonce' );
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$trap  = isset( $_POST['company'] ) ? trim( (string) wp_unslash( $_POST['company'] ) ) : '';
	$code  = (string) cf_setting( 'welcome_code' );
	/* translators: %s: coupon code. */
	$success = $code ? sprintf( __( 'You are in. Use code %s for your first order.', 'comfa' ), $code ) : __( 'You are in. Thanks for signing up.', 'comfa' );
	if ( '' !== $trap ) {
		wp_send_json_success( array( 'message' => $success ) );
	}
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'comfa' ) ), 400 );
	}
	$ip_key = 'cf_news_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown' );
	$tries  = (int) get_transient( $ip_key );
	if ( $tries >= 5 ) {
		wp_send_json_error( array( 'message' => __( 'Too many attempts. Please try again later.', 'comfa' ) ), 429 );
	}
	set_transient( $ip_key, $tries + 1, 10 * MINUTE_IN_SECONDS );

	if ( cf_newsletter_store( $email ) ) {
		do_action( 'cf_newsletter_signup', $email );
	}
	wp_send_json_success( array( 'message' => $success ) );
}
add_action( 'wp_ajax_cf_newsletter', 'cf_newsletter_submit' );
add_action( 'wp_ajax_nopriv_cf_newsletter', 'cf_newsletter_submit' );

/**
 * Floating welcome-offer pill that opens a Flatsome lightbox.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! cf_setting( 'welcome_code' ) || ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) ) {
			return;
		}
		$percent = (int) cf_setting( 'welcome_percent' );
		echo '<div class="cf-offer-pill" hidden><a href="#cf-offer">' . esc_html( sprintf( /* translators: %d: discount percentage. */ __( 'Get %d%% Off', 'comfa' ), $percent ) ) . '</a><button type="button" aria-label="' . esc_attr__( 'Dismiss offer', 'comfa' ) . '">×</button></div>';
		$body  = '<div class="cf-offer"><p class="cf-offer__eyebrow">' . esc_html__( 'New here?', 'comfa' ) . '</p><h3>' . esc_html( sprintf( /* translators: %d: discount percentage. */ __( 'Get %d%% Off', 'comfa' ), $percent ) ) . '</h3><p>' . esc_html__( 'Join the list for your welcome code, new drops and giving updates.', 'comfa' ) . '</p>';
		$body .= cf_newsletter_form( array( 'button' => sprintf( /* translators: %d: discount percentage. */ __( 'Get My %d%% Off', 'comfa' ), $percent ) ) ) . '</div>';
		echo do_shortcode( '[lightbox id="cf-offer" width="460px" padding="36px"]' . $body . '[/lightbox]' );
	},
	6
);
