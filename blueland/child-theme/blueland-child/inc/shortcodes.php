<?php
/**
 * Theme-specific UX Builder components and newsletter endpoint.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'bl_newsletter',
	function () {
		return '<form class="bl-newsletter-form"><label class="screen-reader-text" for="bl-newsletter-email">Email address</label><input id="bl-newsletter-email" type="email" name="email" placeholder="Email Address" required><label class="bl-newsletter-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="bl_newsletter_website" tabindex="-1" autocomplete="off"></label><button type="submit" aria-label="Subscribe">→</button><p class="bl-newsletter-message" aria-live="polite">By signing up, you consent to our Privacy Policy. You may unsubscribe at any time.</p></form>';
	}
);

add_action( 'wp_ajax_bl_newsletter', 'bl_newsletter_submit' );
add_action( 'wp_ajax_nopriv_bl_newsletter', 'bl_newsletter_submit' );
function bl_newsletter_submit() {
	check_ajax_referer( 'bl_storefront', 'nonce' );
	$honeypot = isset( $_POST['bl_newsletter_website'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_newsletter_website'] ) ) : '';
	if ( '' !== $honeypot ) {
		wp_send_json_error( array( 'message' => 'Unable to process this request.' ), 400 );
	}
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Enter a valid email address.' ), 400 );
	}
	$subscribers = get_option( 'bl_newsletter_emails', array() );
	$subscribers[] = $email;
	update_option( 'bl_newsletter_emails', array_values( array_unique( $subscribers ) ), false );
	$response = array( 'message' => 'Thanks for subscribing!' );
	if ( isset( $_POST['source'] ) && 'popup' === sanitize_key( wp_unslash( $_POST['source'] ) ) ) {
		$response['code'] = 'WELCOME10';
	}
	wp_send_json_success( $response );
}

add_shortcode(
	'bl_certifications',
	function () {
		$logos = array( 'Refill Ready', 'Thoughtful Design', 'Clear Information', 'Reusable Format', 'Everyday Use', 'Concentrated Formula', 'Low-Waste Choice', 'Made for Home' );
		$html = '<div class="bl-certifications-grid">';
		foreach ( $logos as $index => $label ) {
			$html .= '<div><img src="' . esc_url( BL_CHILD_URL . '/assets/images/cert-' . ( $index + 1 ) . '.png' ) . '" alt="" loading="lazy" decoding="async"><span>' . esc_html( $label ) . '</span></div>';
		}
		return $html . '</div>';
	}
);

add_shortcode(
	'bl_home_categories',
	function () {
		$slugs = array( 'starter-kits', 'refills', 'dish', 'laundry', 'toilet', 'hand-soap', 'spray-cleaners', 'extras' );
		$descriptions = array(
			'starter-kits' => 'Everything you need to make the switch.',
			'refills' => 'Plastic-free refills save money, space, and waste.',
			'dish' => 'Powerful, plastic-free scrubbing from dishwasher to sink.',
			'laundry' => 'Plastic-free essentials make laundry day a breeze.',
			'toilet' => 'Mess-free tablets make cleaning the toilet easier than ever.',
			'hand-soap' => 'Foaming hand soap in fresh, plant-based scents.',
			'spray-cleaners' => 'Streak-free cleaning for every surface.',
			'extras' => 'Reusable tools that complete your routine.',
		);
		$html = '<div class="bl-home-category-carousel"><button class="bl-category-prev" type="button" aria-label="Previous categories">‹</button><div class="bl-home-categories-track">';
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$image_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
			$image = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
			$html .= '<a class="bl-home-category-card" href="' . esc_url( get_term_link( $term ) ) . '"><img loading="lazy" decoding="async" src="' . esc_url( $image ) . '" alt=""><strong>' . esc_html( $term->name ) . ' →</strong><p>' . esc_html( $descriptions[ $slug ] ) . '</p></a>';
		}
		return $html . '</div><button class="bl-category-next" type="button" aria-label="Next categories">›</button></div>';
	}
);

add_shortcode(
	'bl_instagram_grid',
	function () {
		$html = '<div class="bl-instagram-track">';
		for ( $index = 1; $index <= 4; $index++ ) {
			$extension = file_exists( BL_CHILD_DIR . '/assets/images/instagram-' . $index . '.webp' ) ? 'webp' : 'jpg';
			$html .= '<img loading="lazy" decoding="async" src="' . esc_url( BL_CHILD_URL . '/assets/images/instagram-' . $index . '.' . $extension ) . '" alt="Pureland home care inspiration">';
		}
		return $html . '</div>';
	}
);

add_shortcode(
	'bl_press_logos',
	function () {
		$logos = array( 'The Daily Edit', 'HOME/GOOD', 'Green Review', 'MORNING' );
		$html = '<div class="bl-press-logos" role="group" aria-label="Press mentions">';
		foreach ( $logos as $index => $label ) {
			$quote = array(
				'Refill-ready essentials designed to make everyday routines feel easier.',
				'A considered approach to practical home care.',
				'Thoughtful details for the places we call home.',
				'Small, useful changes for a cleaner routine.',
			);
			$html .= '<button class="bl-press-logo' . ( 0 === $index ? ' is-active' : '' ) . '" type="button" data-quote="' . esc_attr( $quote[ $index ] ) . '" aria-pressed="' . ( 0 === $index ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $label ) . '"><img src="' . esc_url( BL_CHILD_URL . '/assets/images/press-' . ( $index + 1 ) . '.svg' ) . '" alt="' . esc_attr( $label ) . '"></button>';
		}
		return $html . '</div>';
	}
);

add_shortcode(
	'bl_image',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'name' => 'hero', 'class' => '' ), $atts, 'bl_image' );
		$allowed = array( 'hero', 'lifestyle', 'how-it-works', 'safe-home', 'instagram-1', 'instagram-2', 'instagram-3', 'instagram-4', 'kit-foam', 'kit-multi', 'kit-tablet', 'kit-spray' );
		$name = sanitize_key( $atts['name'] );
		if ( ! in_array( $name, $allowed, true ) ) {
			return '';
		}
		$photography = array( 'hero', 'lifestyle', 'how-it-works', 'safe-home', 'instagram-1', 'instagram-2', 'instagram-3', 'instagram-4' );
		$extension = in_array( $name, $photography, true ) ? 'jpg' : 'png';
		if ( file_exists( BL_CHILD_DIR . '/assets/images/' . $name . '.webp' ) ) {
			$extension = 'webp';
		}
		$alt = $name ? ucwords( str_replace( '-', ' ', $name ) ) : '';
		$loading = 'hero' === $name ? 'eager' : 'lazy';
		$priority = 'hero' === $name ? ' fetchpriority="high"' : '';
		return '<img loading="' . esc_attr( $loading ) . '"' . $priority . ' decoding="async" class="bl-content-image ' . esc_attr( sanitize_html_class( $atts['class'] ) ) . '" src="' . esc_url( BL_CHILD_URL . '/assets/images/' . $name . '.' . $extension ) . '" alt="' . esc_attr( $alt ) . '">';
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
