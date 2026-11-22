<?php
/**
 * Product review photos and moderated product questions.
 */

defined( 'ABSPATH' ) || exit;

function bl_review_photos_markup( $comment_id ) {
	$photo_ids = get_comment_meta( $comment_id, '_bl_review_photos', true );
	if ( ! is_array( $photo_ids ) || empty( $photo_ids ) ) {
		return '';
	}

	$photos = '';
	foreach ( array_slice( array_map( 'absint', $photo_ids ), 0, 3 ) as $attachment_id ) {
		$thumbnail = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		$full      = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $thumbnail || ! $full ) {
			continue;
		}
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$photos .= '<a class="bl-review-photo" href="' . esc_url( $full ) . '" aria-label="' . esc_attr( 'View review photo' ) . '"><img src="' . esc_url( $thumbnail ) . '" alt="' . esc_attr( $alt ) . '" width="72" height="72" loading="lazy" decoding="async"></a>';
	}

	return $photos ? '<div class="bl-review-photos">' . $photos . '</div>' : '';
}

function bl_hold_review_comment( $comment_id ) {
	wp_set_comment_status( $comment_id, 'hold' );
}

function bl_handle_review_photo_uploads( $comment_id, $comment_approved, $commentdata ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment || 'product' !== get_post_type( $comment->comment_post_ID ) || 'review' !== $comment->comment_type ) {
		return;
	}

	$nonce = isset( $_POST['bl_review_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_review_nonce'] ) ) : '';
	$website = isset( $_POST['bl_review_website'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_review_website'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'bl_review_' . $comment->comment_post_ID ) || '' !== $website ) {
		bl_hold_review_comment( $comment_id );
		return;
	}

	if ( empty( $_FILES['bl_review_photos'] ) || ! is_array( $_FILES['bl_review_photos']['name'] ) ) {
		return;
	}

	$allowed_mimes = array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
	);
	$uploads      = array();
	$file_set     = $_FILES['bl_review_photos'];
	foreach ( array_keys( $file_set['name'] ) as $index ) {
		$error = isset( $file_set['error'][ $index ] ) ? (int) $file_set['error'][ $index ] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_NO_FILE === $error ) {
			continue;
		}
		if (
			UPLOAD_ERR_OK !== $error
			|| empty( $file_set['tmp_name'][ $index ] )
			|| (int) $file_set['size'][ $index ] > 5 * MB_IN_BYTES
		) {
			bl_hold_review_comment( $comment_id );
			return;
		}

		$file_name = sanitize_file_name( wp_unslash( $file_set['name'][ $index ] ) );
		$file_type = wp_check_filetype_and_ext( $file_set['tmp_name'][ $index ], $file_name, $allowed_mimes );
		if ( empty( $file_type['ext'] ) || empty( $file_type['type'] ) || ! in_array( $file_type['type'], array_values( $allowed_mimes ), true ) ) {
			bl_hold_review_comment( $comment_id );
			return;
		}
		$uploads[] = array(
			'name'     => $file_name,
			'type'     => $file_type['type'],
			'tmp_name' => $file_set['tmp_name'][ $index ],
			'error'    => $error,
			'size'     => (int) $file_set['size'][ $index ],
		);
	}

	if ( count( $uploads ) > 3 ) {
		bl_hold_review_comment( $comment_id );
		return;
	}
	if ( empty( $uploads ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attachment_ids = array();
	foreach ( $uploads as $index => $upload ) {
		$field_name = 'bl_review_photo_' . $index;
		$_FILES[ $field_name ] = $upload;
		$attachment_id = media_handle_upload(
			$field_name,
			$comment->comment_post_ID,
			array(),
			array(
				'test_form' => false,
				'mimes'     => $allowed_mimes,
			)
		);
		unset( $_FILES[ $field_name ] );
		if ( is_wp_error( $attachment_id ) ) {
			foreach ( $attachment_ids as $saved_id ) {
				wp_delete_attachment( $saved_id, true );
			}
			bl_hold_review_comment( $comment_id );
			return;
		}
		$attachment_ids[] = (int) $attachment_id;
	}
	update_comment_meta( $comment_id, '_bl_review_photos', $attachment_ids );
}
add_action( 'comment_post', 'bl_handle_review_photo_uploads', 20, 3 );

function bl_product_qa_shortcode() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$product_id = $product->get_id();
	$questions  = get_comments(
		array(
			'post_id' => $product_id,
			'type'    => 'bl_question',
			'status'  => 'approve',
			'number'  => 0,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		)
	);
	$html = '<section id="questions-answers" class="bl-product-qa"><h2>Questions &amp; Answers</h2>';
	if ( $questions ) {
		$html .= '<div class="bl-qa-list">';
		foreach ( $questions as $question ) {
			$html .= '<article class="bl-qa-item"><h3>' . esc_html( $question->comment_content ) . '</h3><p class="bl-qa-author">Asked by ' . esc_html( $question->comment_author ) . '</p>';
			$answers = get_comments(
				array(
					'post_id' => $product_id,
					'parent'  => $question->comment_ID,
					'status'  => 'approve',
					'number'  => 0,
					'orderby' => 'comment_date_gmt',
					'order'   => 'ASC',
				)
			);
			foreach ( $answers as $answer ) {
				$author = $answer->user_id && user_can( (int) $answer->user_id, 'manage_woocommerce' ) ? 'Pureland Team' : $answer->comment_author;
				$html .= '<div class="bl-qa-answer"><strong>' . esc_html( $author ) . '</strong>' . wp_kses_post( wpautop( $answer->comment_content ) ) . '</div>';
			}
			$html .= '</article>';
		}
		$html .= '</div>';
	}

	if ( comments_open( $product_id ) ) {
		$html .= '<form class="bl-qa-form"><h3>Ask a question</h3><label>Name<input type="text" name="name" maxlength="100" required></label><label>Email<input type="email" name="email" autocomplete="email" required></label><label>Your question<textarea name="question" maxlength="500" rows="4" required></textarea></label><input type="hidden" name="product_id" value="' . esc_attr( (string) $product_id ) . '"><input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( 'bl_storefront' ) ) . '"><label class="bl-qa-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="bl_qa_website" tabindex="-1" autocomplete="off"></label><button class="button primary" type="submit">Submit Question</button><p class="bl-qa-message" aria-live="polite"></p></form>';
	}

	return $html . '</section>';
}
add_shortcode( 'bl_product_qa', 'bl_product_qa_shortcode' );

function bl_submit_product_question() {
	check_ajax_referer( 'bl_storefront', 'nonce' );
	$honeypot = isset( $_POST['bl_qa_website'] ) ? sanitize_text_field( wp_unslash( $_POST['bl_qa_website'] ) ) : '';
	$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$question = isset( $_POST['question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question'] ) ) : '';
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $question ) : strlen( $question );

	if ( '' !== $honeypot || ! $product_id || 'product' !== get_post_type( $product_id ) || ! comments_open( $product_id ) || '' === $name || ! is_email( $email ) || '' === $question || $length > 500 ) {
		wp_send_json_error( array( 'message' => 'Please check your details and try again.' ), 400 );
	}

	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => $name,
			'comment_author_email' => $email,
			'comment_content'      => $question,
			'comment_type'         => 'bl_question',
			'comment_approved'     => 0,
		)
	);
	if ( ! $comment_id || is_wp_error( $comment_id ) ) {
		wp_send_json_error( array( 'message' => 'We could not submit your question. Please try again.' ), 500 );
	}

	wp_send_json_success( array( 'message' => "Thanks! We'll answer soon." ) );
}
add_action( 'wp_ajax_bl_submit_product_question', 'bl_submit_product_question' );
add_action( 'wp_ajax_nopriv_bl_submit_product_question', 'bl_submit_product_question' );

function bl_render_review_lightbox() {
	if ( ! is_product() ) {
		return;
	}
	echo '<div id="bl-review-lightbox" class="bl-review-lightbox" role="dialog" aria-modal="true" aria-label="Review photo" hidden><button class="bl-review-lightbox-close" type="button" aria-label="Close photo">×</button><img src="" alt=""></div>';
}
add_action( 'wp_footer', 'bl_render_review_lightbox', 10 );
