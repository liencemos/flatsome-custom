<?php

$key = $args[0] ?? 'contact';
$page = get_page_by_path( 'contact-us', OBJECT, array( 'page' ) );
if ( 'footer' === $key ) {
	$contact = WPCF7_ContactForm::get_instance( (int) get_option( 'vertuo_form_contact' ) );
	$newsletter = WPCF7_ContactForm::get_instance( (int) get_option( 'vertuo_form_newsletter' ) );
	$block = get_page_by_path( 'vertuo-footer', OBJECT, array( 'blocks' ) );
	if ( ! $page || ! $contact || ! $newsletter || ! $block || $block->post_name !== get_theme_mod( 'footer_block' ) ) {
		WP_CLI::error( 'Contact page, forms, or selected Footer UX Block are missing.' );
	}
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $page->ID ) );
	$queried_page = get_queried_object();
	$content_cases = array(
		'[vertuo_contact_form]' => false,
		$contact->shortcode() => false,
		'<!-- wp:contact-form-7/contact-form-selector --><div>' . $contact->shortcode() . '</div><!-- /wp:contact-form-7/contact-form-selector -->' => false,
		'[contact-form-7 id="' . $contact->id() . '"]' => false,
		'<p>An edited Contact Us page without a form.</p>' => true,
		'[[vertuo_contact_form]]' => true,
		'[contact-form-7 id="' . $contact->id() . '" output="raw_form"]' => true,
		$newsletter->shortcode() => true,
	);
	foreach ( $content_cases as $content => $expect_footer_form ) {
		$queried_page->post_content = $content;
		ob_start();
		do_action( 'flatsome_footer' );
		$footer = ob_get_clean();
		if ( ! str_contains( $footer, 'vertuo-footer-links' )
			|| ! str_contains( $footer, 'wpcf7-f' . $newsletter->id() . '-' )
			|| $expect_footer_form !== str_contains( $footer, 'wpcf7-f' . $contact->id() . '-' ) ) {
			WP_CLI::error( 'Unexpected footer form visibility on Contact Us.' );
		}
	}
	WP_CLI::success( 'Flatsome renders the selected Footer UX Block with the newsletter and conditional contact form.' );
	return;
}
if ( ! in_array( $key, array( 'contact', 'newsletter' ), true ) ) {
	WP_CLI::error( 'Expected contact, newsletter, or footer.' );
}

$id = (int) get_option( 'vertuo_form_' . $key );
if ( ! $page || ! $id || ! str_contains( vertuo_render_support_form( $key ), '_wpcf7_unit_tag' ) ) {
	WP_CLI::error( 'Support page or form is missing.' );
}

$unit = 'wpcf7-f' . $id . '-p' . $page->ID . '-o1';
$email = 'vertuo-test@example.com';
$message = 'Local support form check.';
$_POST = array(
	'_wpcf7'                   => (string) $id,
	'_wpcf7_unit_tag'          => $unit,
	'_wpcf7_container_post'    => (string) $page->ID,
	'your-email'               => $email,
);
if ( 'contact' === $key ) {
	$_POST += array(
		'your-name'    => 'Example Customer',
		'topic'        => 'Orders and delivery',
		'your-message' => $message,
	);
}

$mail_request = null;
add_filter( 'pre_wp_mail', function ( $return, $mail ) use ( &$mail_request ) {
	$mail_request = $mail;
	return true;
}, 10, 2 );

$request = new WP_REST_Request( 'POST', '/contact-form-7/v1/contact-forms/' . $id . '/feedback' );
$request->set_header( 'Content-Type', 'multipart/form-data' );
$request->set_body_params( $_POST );
$response = rest_do_request( $request );
$data = $response->get_data();
$expected = 'contact' === $key ? $message : $email;
if ( 200 !== $response->get_status() || 'mail_sent' !== ( $data['status'] ?? '' ) || ! str_contains( $mail_request['message'] ?? '', $expected ) ) {
	WP_CLI::error( 'Unexpected CF7 response: HTTP ' . $response->get_status() . ' / ' . ( $data['status'] ?? $data['code'] ?? 'unknown' ) );
}

WP_CLI::success( "$key: CF7 returned HTTP 200 / mail_sent; test mail intercepted locally." );
