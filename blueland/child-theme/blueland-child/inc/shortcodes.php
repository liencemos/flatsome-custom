<?php
/**
 * Theme-specific UX Builder components and newsletter endpoint.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'bl_newsletter',
	function () {
		return '<form class="bl-newsletter-form"><label class="screen-reader-text" for="bl-newsletter-email">Email address</label><input id="bl-newsletter-email" type="email" name="email" placeholder="Email Address" required><button type="submit" aria-label="Subscribe">→</button><p class="bl-newsletter-message" aria-live="polite">By signing up, you consent to our Privacy Policy. You may unsubscribe at any time.</p></form>';
	}
);

add_action( 'wp_ajax_bl_newsletter', 'bl_newsletter_submit' );
add_action( 'wp_ajax_nopriv_bl_newsletter', 'bl_newsletter_submit' );
function bl_newsletter_submit() {
	check_ajax_referer( 'bl_storefront', 'nonce' );
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Enter a valid email address.' ), 400 );
	}
	$subscribers = get_option( 'bl_newsletter_emails', array() );
	$subscribers[] = $email;
	update_option( 'bl_newsletter_emails', array_values( array_unique( $subscribers ) ), false );
	wp_send_json_success( array( 'message' => 'Thanks for subscribing!' ) );
}

add_shortcode(
	'bl_certifications',
	function () {
		$logos = array( 'Refill Ready', 'Thoughtful Design', 'Clear Information', 'Reusable Format', 'Everyday Use', 'Concentrated Formula', 'Low-Waste Choice', 'Made for Home' );
		$html = '<div class="bl-certifications-grid">';
		foreach ( $logos as $index => $label ) {
			$html .= '<div><img src="' . esc_url( BL_CHILD_URL . '/assets/images/cert-' . ( $index + 1 ) . '.png' ) . '" alt=""><span>' . esc_html( $label ) . '</span></div>';
		}
		return $html . '</div>';
	}
);

add_shortcode(
	'bl_image',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'name' => 'hero', 'class' => '' ), $atts, 'bl_image' );
		$allowed = array( 'hero', 'lifestyle', 'how-it-works', 'autoship', 'instagram-1', 'instagram-2', 'instagram-3', 'instagram-4' );
		$name = sanitize_key( $atts['name'] );
		if ( ! in_array( $name, $allowed, true ) ) {
			return '';
		}
		return '<img class="bl-content-image ' . esc_attr( sanitize_html_class( $atts['class'] ) ) . '" src="' . esc_url( BL_CHILD_URL . '/assets/images/' . $name . '.png' ) . '" alt="">';
	}
);

add_shortcode(
	'bl_usp_grid',
	function () {
		$items = array(
			array( 'Powerfully Effective', 'Small, mighty formulas made for everyday messes.' ),
			array( 'Concentrated Formulas', 'Refill formats designed for everyday routines.' ),
			array( 'Thoughtful Ingredients', 'Clear product information for everyday home care.' ),
			array( 'Better Value', 'Convenient routines that help you save over time.' ),
		);
		$html = '<div class="bl-usp-grid">';
		foreach ( $items as $index => $item ) {
			$html .= '<article><img src="' . esc_url( BL_CHILD_URL . '/assets/images/usp-' . ( $index + 1 ) . '.svg' ) . '" alt=""><h3>' . esc_html( $item[0] ) . '</h3><p>' . esc_html( $item[1] ) . '</p></article>';
		}
		return $html . '</div>';
	}
);
