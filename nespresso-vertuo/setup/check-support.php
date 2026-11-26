<?php

$key = $args[0] ?? 'contact';
if ( ! in_array( $key, array( 'contact', 'newsletter' ), true ) ) {
	WP_CLI::error( 'Expected contact or newsletter.' );
}

$id = (int) get_option( 'vertuo_form_' . $key );
$page = get_page_by_path( 'contact-us', OBJECT, array( 'page' ) );
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
