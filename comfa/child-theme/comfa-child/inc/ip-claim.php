<?php
/**
 * Intellectual property claim form: [cf_ip_claim_form].
 *
 * The form posts back to its own page (no JavaScript needed). Each report is
 * stored as a private `cf_ip_claim` post (listed under "IP Claims" in
 * wp-admin) and emailed to the site administrator; change the recipient with
 * the `cf_ip_claim_recipient` filter.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'cf_ip_claim',
			array(
				'labels'              => array(
					'name'          => __( 'IP Claims', 'comfa' ),
					'singular_name' => __( 'IP Claim', 'comfa' ),
					'edit_item'     => __( 'IP Claim', 'comfa' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'menu_icon'           => 'dashicons-shield',
				'supports'            => array( 'title' ),
				// Reports hold personal data: administrators only, and no manual entries.
				'capabilities'        => array(
					'edit_posts'             => 'manage_options',
					'edit_others_posts'      => 'manage_options',
					'edit_private_posts'     => 'manage_options',
					'edit_published_posts'   => 'manage_options',
					'delete_posts'           => 'manage_options',
					'delete_others_posts'    => 'manage_options',
					'delete_private_posts'   => 'manage_options',
					'delete_published_posts' => 'manage_options',
					'publish_posts'          => 'manage_options',
					'read_private_posts'     => 'manage_options',
					'create_posts'           => 'do_not_allow',
				),
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'register_meta_box_cb' => function () {
					add_meta_box( 'cf-ip-claim', __( 'Report', 'comfa' ), 'cf_ip_claim_meta_box', 'cf_ip_claim', 'normal', 'high' );
				},
			)
		);
	}
);

/**
 * Form fields: key => type, label, required, maximum length and options.
 */
function cf_ip_claim_fields() {
	return array(
		'role'         => array(
			'type'     => 'select',
			'label'    => __( 'I am', 'comfa' ),
			'required' => true,
			'options'  => array(
				'owner' => __( 'The rights owner', 'comfa' ),
				'agent' => __( 'Authorised to act for the rights owner', 'comfa' ),
			),
		),
		'owner'        => array( 'type' => 'text', 'label' => __( 'Rights owner (person or company)', 'comfa' ), 'required' => true, 'max' => 200, 'autocomplete' => 'organization' ),
		'name'         => array( 'type' => 'text', 'label' => __( 'Your full name', 'comfa' ), 'required' => true, 'max' => 200, 'autocomplete' => 'name' ),
		'email'        => array( 'type' => 'email', 'label' => __( 'Email address', 'comfa' ), 'required' => true, 'max' => 200, 'autocomplete' => 'email' ),
		'phone'        => array( 'type' => 'tel', 'label' => __( 'Telephone number', 'comfa' ), 'required' => true, 'max' => 60, 'autocomplete' => 'tel' ),
		'address'      => array( 'type' => 'textarea', 'label' => __( 'Mailing address', 'comfa' ), 'required' => true, 'max' => 600, 'rows' => 2, 'autocomplete' => 'street-address' ),
		'type'         => array(
			'type'     => 'select',
			'label'    => __( 'Type of right', 'comfa' ),
			'required' => true,
			'options'  => array(
				'copyright' => __( 'Copyright', 'comfa' ),
				'trademark' => __( 'Trademark', 'comfa' ),
				'other'     => __( 'Other intellectual property', 'comfa' ),
			),
		),
		'registration' => array( 'type' => 'text', 'label' => __( 'Registration number, if any', 'comfa' ), 'required' => false, 'max' => 200 ),
		'work'         => array( 'type' => 'textarea', 'label' => __( 'The work or mark you own', 'comfa' ), 'required' => true, 'max' => 3000, 'rows' => 3, 'help' => __( 'Describe it, or link to where we can see the original.', 'comfa' ) ),
		'urls'         => array( 'type' => 'textarea', 'label' => __( 'Where it appears on our website', 'comfa' ), 'required' => true, 'max' => 3000, 'rows' => 3, 'help' => __( 'One page address per line.', 'comfa' ) ),
		'details'      => array( 'type' => 'textarea', 'label' => __( 'How the content infringes your rights', 'comfa' ), 'required' => true, 'max' => 5000, 'rows' => 4 ),
		'good_faith'   => array( 'type' => 'checkbox', 'label' => __( 'I believe in good faith that the use described above is not authorised by the rights owner, its agents or the law.', 'comfa' ), 'required' => true ),
		'accuracy'     => array( 'type' => 'checkbox', 'label' => __( 'I state, under penalty of perjury, that this report is accurate and that I am the rights owner or am authorised to act for the rights owner.', 'comfa' ), 'required' => true ),
		'signature'    => array( 'type' => 'text', 'label' => __( 'Electronic signature', 'comfa' ), 'required' => true, 'max' => 200, 'help' => __( 'Type your full name.', 'comfa' ) ),
	);
}

/**
 * Plain text from a submitted value. Values are escaped on output, so page
 * addresses keep characters such as %20.
 */
function cf_ip_claim_clean( $value, $multiline, $max ) {
	$value = is_string( $value ) ? wp_check_invalid_utf8( wp_unslash( $value ) ) : '';
	$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
	$value = preg_replace( $multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/' : '/[\x00-\x1F\x7F]+/', $multiline ? '' : ' ', $value );
	$value = trim( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

/**
 * Result of the current request's submission: values, errors and a notice.
 */
function cf_ip_claim_state( $set = null ) {
	static $state = array( 'values' => array(), 'errors' => array(), 'notice' => '' );
	if ( is_array( $set ) ) {
		$state = array_merge( $state, $set );
	}
	return $state;
}

function cf_ip_claim_validate( $post ) {
	$values = array();
	$errors = array();
	foreach ( cf_ip_claim_fields() as $key => $field ) {
		$raw = isset( $post[ 'cf_claim_' . $key ] ) ? $post[ 'cf_claim_' . $key ] : '';
		if ( 'checkbox' === $field['type'] ) {
			$values[ $key ] = '1' === $raw ? '1' : '';
		} elseif ( 'select' === $field['type'] ) {
			$values[ $key ] = is_string( $raw ) && isset( $field['options'][ $raw ] ) ? $raw : '';
		} else {
			$values[ $key ] = cf_ip_claim_clean( $raw, 'textarea' === $field['type'], $field['max'] );
		}
		if ( $field['required'] && '' === $values[ $key ] ) {
			$errors[ $key ] = 'checkbox' === $field['type'] ? __( 'Please confirm this statement.', 'comfa' ) : __( 'This field is required.', 'comfa' );
		}
	}
	if ( '' !== $values['email'] && ! is_email( $values['email'] ) ) {
		$errors['email'] = __( 'Please enter a valid email address.', 'comfa' );
	}
	// Count digits in any script, for example Arabic-Indic numerals.
	if ( '' !== $values['phone'] && (int) preg_match_all( '/\p{Nd}/u', $values['phone'] ) < 6 ) {
		$errors['phone'] = __( 'Please enter a telephone number we can reach you on.', 'comfa' );
	}
	return array( $values, $errors );
}

/**
 * Report as label/value pairs, for the email and the admin screen.
 */
function cf_ip_claim_rows( $values ) {
	$rows = array();
	foreach ( cf_ip_claim_fields() as $key => $field ) {
		$value = isset( $values[ $key ] ) ? (string) $values[ $key ] : '';
		if ( 'checkbox' === $field['type'] ) {
			$value = '1' === $value ? __( 'Confirmed', 'comfa' ) : __( 'Not confirmed', 'comfa' );
		} elseif ( 'select' === $field['type'] ) {
			$value = isset( $field['options'][ $value ] ) ? $field['options'][ $value ] : '';
		}
		$rows[] = array( $field['label'], $value );
	}
	return $rows;
}

function cf_ip_claim_store( $values ) {
	$id = wp_insert_post(
		array(
			'post_type'   => 'cf_ip_claim',
			'post_status' => 'private',
			/* translators: 1: rights owner, 2: reporter name. */
			'post_title'  => wp_slash( sprintf( __( '%1$s (reported by %2$s)', 'comfa' ), $values['owner'], $values['name'] ) ),
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	// A report without its details is useless: do not keep it or count it as received.
	if ( ! update_post_meta( $id, '_cf_ip_claim', wp_slash( $values ) ) ) {
		wp_delete_post( $id, true );
		return 0;
	}
	return (int) $id;
}

function cf_ip_claim_mail( $values, $id ) {
	$recipient = apply_filters( 'cf_ip_claim_recipient', get_option( 'admin_email' ), $values );
	if ( ! $recipient ) {
		return false;
	}
	$body = '';
	foreach ( cf_ip_claim_rows( $values ) as $row ) {
		$body .= rtrim( $row[0], '.' ) . ":\n" . $row[1] . "\n\n";
	}
	if ( $id ) {
		$body .= admin_url( 'post.php?post=' . $id . '&action=edit' ) . "\n";
	}
	/* translators: 1: site name, 2: rights owner. */
	$subject = sprintf( __( '[%1$s] Intellectual property claim from %2$s', 'comfa' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $values['owner'] );
	return wp_mail( $recipient, $subject, $body, array( 'Reply-To: ' . $values['email'] ) );
}

add_action(
	'template_redirect',
	function () {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! isset( $_POST['cf_ip_claim_nonce'] ) ) {
			return;
		}
		nocache_headers();
		list( $values, $errors ) = cf_ip_claim_validate( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below; values are kept so an expired form can be resent.
		$done = is_singular() ? get_permalink( get_queried_object_id() ) : home_url( '/' );
		$done = add_query_arg( 'cf_claim', 'sent', $done ) . '#cf-ip-claim';

		// Hidden field that only bots fill in.
		if ( ! empty( $_POST['cf_claim_ref'] ) ) {
			wp_safe_redirect( $done );
			exit;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cf_ip_claim_nonce'] ) ), 'cf_ip_claim' ) ) {
			cf_ip_claim_state( array( 'values' => $values, 'notice' => __( 'This form had expired. Please check your details and send it again.', 'comfa' ) ) );
			return;
		}
		if ( $errors ) {
			cf_ip_claim_state( array( 'values' => $values, 'errors' => $errors, 'notice' => __( 'Please fix the highlighted fields and send the form again.', 'comfa' ) ) );
			return;
		}
		$ip_key = 'cf_claim_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown' );
		$tries  = (int) get_transient( $ip_key );
		if ( $tries >= 5 ) {
			cf_ip_claim_state( array( 'values' => $values, 'notice' => __( 'Too many reports from this connection. Please try again later.', 'comfa' ) ) );
			return;
		}
		set_transient( $ip_key, $tries + 1, HOUR_IN_SECONDS );

		$id   = cf_ip_claim_store( $values );
		$sent = cf_ip_claim_mail( $values, $id );
		if ( ! $id && ! $sent ) {
			cf_ip_claim_state( array( 'values' => $values, 'notice' => __( 'We could not send your report. Please try again, or email us instead.', 'comfa' ) ) );
			return;
		}
		do_action( 'cf_ip_claim_received', $values, $id );
		wp_safe_redirect( $done );
		exit;
	}
);

function cf_ip_claim_field_html( $key, $field, $value, $error ) {
	$id    = 'cf-claim-' . str_replace( '_', '-', $key );
	$name  = 'cf_claim_' . $key;
	$attrs = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"';
	$attrs .= $field['required'] ? ' required' : '';
	$attrs .= $error ? ' aria-invalid="true"' : '';
	$notes  = array();
	if ( ! empty( $field['help'] ) ) {
		$notes[] = $id . '-help';
	}
	if ( $error ) {
		$notes[] = $id . '-error';
	}
	$attrs .= $notes ? ' aria-describedby="' . esc_attr( implode( ' ', $notes ) ) . '"' : '';
	$attrs .= ! empty( $field['autocomplete'] ) ? ' autocomplete="' . esc_attr( $field['autocomplete'] ) . '"' : '';
	$attrs .= ! empty( $field['max'] ) ? ' maxlength="' . (int) $field['max'] . '"' : '';
	$mark   = $field['required'] ? ' <span class="cf-claim__req" aria-hidden="true">*</span>' : '';
	$html   = '<div class="cf-claim__field cf-claim__field--' . esc_attr( $field['type'] ) . ' cf-claim__field--' . esc_attr( str_replace( '_', '-', $key ) ) . ( $error ? ' has-error' : '' ) . '">';

	if ( 'checkbox' === $field['type'] ) {
		$html .= '<label for="' . esc_attr( $id ) . '"><input type="checkbox" value="1"' . $attrs . checked( $value, '1', false ) . '> <span>' . esc_html( $field['label'] ) . $mark . '</span></label>';
	} else {
		$html .= '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . $mark . '</label>';
		if ( 'select' === $field['type'] ) {
			$html .= '<select' . $attrs . '><option value="">' . esc_html__( 'Choose one', 'comfa' ) . '</option>';
			foreach ( $field['options'] as $option => $label ) {
				$html .= '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $label ) . '</option>';
			}
			$html .= '</select>';
		} elseif ( 'textarea' === $field['type'] ) {
			$html .= '<textarea rows="' . (int) $field['rows'] . '"' . $attrs . '>' . esc_textarea( $value ) . '</textarea>';
		} else {
			$html .= '<input type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $value ) . '"' . $attrs . '>';
		}
	}
	if ( ! empty( $field['help'] ) ) {
		$html .= '<small id="' . esc_attr( $id ) . '-help" class="cf-claim__help">' . esc_html( $field['help'] ) . '</small>';
	}
	if ( $error ) {
		$html .= '<small id="' . esc_attr( $id ) . '-error" class="cf-claim__error">' . esc_html( $error ) . '</small>';
	}
	return $html . '</div>';
}

function cf_ip_claim_form() {
	if ( isset( $_GET['cf_claim'] ) && 'sent' === $_GET['cf_claim'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		return '<div id="cf-ip-claim" class="cf-claim cf-claim--sent" role="status"><h2>' . esc_html__( 'Thank you. Your report has been sent.', 'comfa' ) . '</h2><p>' . esc_html__( 'Our team will review it and reply to the email address you gave. If we need more information we will ask for it there.', 'comfa' ) . '</p></div>';
	}
	$state  = cf_ip_claim_state();
	$fields = cf_ip_claim_fields();
	$groups = array(
		__( 'Your details', 'comfa' )           => array( 'role', 'owner', 'name', 'email', 'phone', 'address' ),
		__( 'The right you own', 'comfa' )       => array( 'type', 'registration', 'work' ),
		__( 'The content you are reporting', 'comfa' ) => array( 'urls', 'details' ),
		__( 'Statements and signature', 'comfa' ) => array( 'good_faith', 'accuracy', 'signature' ),
	);
	$action = get_permalink();
	$html   = '<form id="cf-ip-claim" class="cf-claim" method="post" action="' . esc_url( ( $action ? $action : '' ) . '#cf-ip-claim' ) . '">';
	if ( $state['notice'] ) {
		$html .= '<p class="cf-claim__notice" role="alert">' . esc_html( $state['notice'] ) . '</p>';
	}
	foreach ( $groups as $legend => $keys ) {
		$html .= '<fieldset class="cf-claim__group"><legend>' . esc_html( $legend ) . '</legend><div class="cf-claim__grid">';
		foreach ( $keys as $key ) {
			$html .= cf_ip_claim_field_html( $key, $fields[ $key ], isset( $state['values'][ $key ] ) ? $state['values'][ $key ] : '', isset( $state['errors'][ $key ] ) ? $state['errors'][ $key ] : '' );
		}
		$html .= '</div></fieldset>';
	}
	$html .= '<div class="cf-claim__hp" aria-hidden="true"><label for="cf-claim-ref">' . esc_html__( 'Leave this field empty', 'comfa' ) . '</label><input id="cf-claim-ref" type="text" name="cf_claim_ref" tabindex="-1" autocomplete="off"></div>';
	$html .= wp_nonce_field( 'cf_ip_claim', 'cf_ip_claim_nonce', false, false );
	$html .= '<button type="submit" class="button cf-claim__submit">' . esc_html__( 'Submit IP claim', 'comfa' ) . '</button>';
	$html .= '<p class="cf-claim__privacy">' . esc_html__( 'We use these details only to handle your report, and may share the report with the person who supplied the content.', 'comfa' ) . '</p>';
	return $html . '</form>';
}
add_shortcode( 'cf_ip_claim_form', 'cf_ip_claim_form' );

function cf_ip_claim_meta_box( $post ) {
	$values = get_post_meta( $post->ID, '_cf_ip_claim', true );
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( cf_ip_claim_rows( is_array( $values ) ? $values : array() ) as $row ) {
		echo '<tr><th scope="row">' . esc_html( $row[0] ) . '</th><td>' . nl2br( esc_html( $row[1] ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
}
