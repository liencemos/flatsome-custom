<?php
/**
 * Product reviews: photo uploads, rating summary and a customer photo strip.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DEWIER_REVIEW_PHOTOS', 3 );

/**
 * Per-photo limit: 5 MB, or less when PHP accepts less.
 */
function dewier_review_photo_bytes() {
	return min( 5 * MB_IN_BYTES, wp_max_upload_size() );
}

/**
 * Bytes PHP accepts for the whole form; above it PHP drops the review text too.
 */
function dewier_review_post_bytes() {
	$post = wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
	return $post > 0 ? (int) ( $post * 0.9 ) : 0;
}

function dewier_review_photo_types() {
	return array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
	);
}

/**
 * The field stays hidden until the script switches the form to multipart.
 */
function dewier_review_photo_field( $args ) {
	$args['comment_field'] .= sprintf(
		'<p class="comment-form-photos dw-review-upload" hidden><label for="dw-review-photos">%1$s <span class="dw-review-upload__hint">%2$s</span></label><input type="file" id="dw-review-photos" name="dw_review_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-max="%3$d" data-bytes="%4$d" data-total="%7$d" data-error-count="%5$s" data-error-file="%6$s"><span class="dw-review-upload__preview"></span><span class="dw-review-upload__error" role="alert"></span></p>',
		esc_html__( 'Add photos', 'dewier' ),
		/* translators: 1: number of photos, 2: size in MB */
		esc_html( sprintf( __( '(optional, up to %1$d JPG, PNG or WebP, %2$d MB each)', 'dewier' ), DEWIER_REVIEW_PHOTOS, max( 1, floor( dewier_review_photo_bytes() / MB_IN_BYTES ) ) ) ),
		DEWIER_REVIEW_PHOTOS,
		dewier_review_photo_bytes(),
		/* translators: %d: number of photos */
		esc_attr( sprintf( __( 'You can add up to %d photos.', 'dewier' ), DEWIER_REVIEW_PHOTOS ) ),
		/* translators: %d: size in MB */
		esc_attr( sprintf( __( 'Photos must be JPG, PNG or WebP and under %d MB.', 'dewier' ), max( 1, floor( dewier_review_photo_bytes() / MB_IN_BYTES ) ) ) ),
		dewier_review_post_bytes()
	);
	return $args;
}
add_filter( 'woocommerce_product_review_comment_form_args', 'dewier_review_photo_field' );

function dewier_review_save_photos( $comment_id, $approved, $data ) {
	// phpcs:disable WordPress.Security.NonceVerification -- core comment form, which has no nonce for guests.
	if ( 'spam' === $approved || empty( $_FILES['dw_review_photos']['name'] ) || ! is_array( $_FILES['dw_review_photos']['name'] ) ) {
		return;
	}
	$post_id = (int) $data['comment_post_ID'];
	if ( 'product' !== get_post_type( $post_id ) || ! empty( $data['comment_parent'] ) ) {
		return;
	}
	$files = $_FILES['dw_review_photos']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	// phpcs:enable

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$saved = array();
	$count = min( DEWIER_REVIEW_PHOTOS, count( $files['name'] ) );
	for ( $i = 0; $i < $count; $i++ ) {
		$file = array(
			'name'     => sanitize_file_name( (string) $files['name'][ $i ] ),
			'type'     => (string) $files['type'][ $i ],
			'tmp_name' => (string) $files['tmp_name'][ $i ],
			'error'    => (int) $files['error'][ $i ],
			'size'     => (int) $files['size'][ $i ],
		);
		if ( UPLOAD_ERR_OK !== $file['error'] || $file['size'] > dewier_review_photo_bytes() || ! is_uploaded_file( $file['tmp_name'] ) ) {
			continue;
		}
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], dewier_review_photo_types() );
		if ( ! $check['type'] || ! wp_getimagesize( $file['tmp_name'] ) ) {
			continue;
		}
		$file['type'] = $check['type'];
		if ( $check['proper_filename'] ) {
			$file['name'] = $check['proper_filename'];
		}
		$id = dewier_review_store_photo( $file, $post_id, $data['comment_author'] );
		if ( $id ) {
			update_post_meta( $id, '_dw_review', $comment_id );
			$saved[] = $id;
		}
	}
	if ( $saved ) {
		update_comment_meta( $comment_id, 'dw_review_photos', $saved );
		if ( 1 === (int) $approved ) {
			dewier_review_photo_status( 'approved', '', get_comment( $comment_id ) );
		}
	}
}
add_action( 'comment_post', 'dewier_review_save_photos', 10, 3 );

/**
 * Stores one checked upload in the media library.
 */
function dewier_review_store_photo( $file, $post_id, $author ) {
	$mimes = dewier_review_photo_types();
	$moved = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => $mimes,
		)
	);
	if ( empty( $moved['file'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => $moved['type'],
			/* translators: %s: reviewer name */
			'post_title'     => sprintf( __( 'Review photo by %s', 'dewier' ), $author ),
			'post_status'    => 'private',
		),
		$moved['file'],
		$post_id,
		true
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $moved['file'] );
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $moved['file'] ) );
	return (int) $id;
}

/**
 * Photos stay private, out of the public media list, until their review is approved.
 */
function dewier_review_photo_status( $new_status, $old_status, $comment ) {
	foreach ( dewier_review_photo_ids( $comment->comment_ID ) as $id ) {
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'approved' === $new_status ? 'inherit' : 'private',
			)
		);
	}
}
add_action( 'transition_comment_status', 'dewier_review_photo_status', 10, 3 );

function dewier_review_delete_photos( $comment_id ) {
	foreach ( dewier_review_photo_ids( $comment_id ) as $id ) {
		if ( (int) get_post_meta( $id, '_dw_review', true ) === (int) $comment_id ) {
			wp_delete_attachment( $id, true );
		}
	}
}
add_action( 'delete_comment', 'dewier_review_delete_photos' );

function dewier_review_photo_ids( $comment_id ) {
	return array_filter( array_map( 'absint', (array) get_comment_meta( $comment_id, 'dw_review_photos', true ) ) );
}

function dewier_review_photo_link( $id, $group, $label ) {
	$full = wp_get_attachment_image_url( $id, 'large' );
	if ( ! $full ) {
		return '';
	}
	return sprintf(
		'<a class="dw-review-photo" href="%1$s" data-dw-lightbox="%2$s" aria-label="%3$s">%4$s</a>',
		esc_url( $full ),
		esc_attr( $group ),
		esc_attr( $label ),
		wp_get_attachment_image( $id, 'woocommerce_gallery_thumbnail', false, array( 'loading' => 'lazy', 'alt' => '' ) )
	);
}

function dewier_review_photos_in_review( $comment ) {
	$ids = '1' === (string) $comment->comment_approved ? dewier_review_photo_ids( $comment->comment_ID ) : array();
	if ( ! $ids ) {
		return;
	}
	echo '<ul class="dw-review-photos">';
	foreach ( $ids as $index => $id ) {
		/* translators: 1: photo number, 2: reviewer name */
		echo '<li>' . dewier_review_photo_link( $id, 'review-' . $comment->comment_ID, sprintf( __( 'Photo %1$d from %2$s', 'dewier' ), $index + 1, $comment->comment_author ) ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}
add_action( 'woocommerce_review_after_comment_text', 'dewier_review_photos_in_review' );

/**
 * Average, star breakdown and customer photos above the review list.
 */
function dewier_review_summary( $product ) {
	if ( ! wc_review_ratings_enabled() ) {
		return;
	}
	$counts = $product->get_rating_counts();
	$total  = array_sum( $counts );
	if ( ! $total ) {
		return;
	}
	$average = (float) $product->get_average_rating();
	$photos  = array();
	$reviews = get_comments(
		array(
			'post_id'  => $product->get_id(),
			'status'   => 'approve',
			'type'     => 'review',
			'meta_key' => 'dw_review_photos', // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'   => 12,
		)
	);
	foreach ( $reviews as $review ) {
		foreach ( dewier_review_photo_ids( $review->comment_ID ) as $id ) {
			$photos[] = array( $id, $review->comment_author );
		}
	}
	$photos = array_slice( $photos, 0, 12 );
	?>
	<section class="dw-review-summary" aria-labelledby="dw-review-summary-title">
		<h2 id="dw-review-summary-title" class="screen-reader-text"><?php esc_html_e( 'Rating summary', 'dewier' ); ?></h2>
		<div class="dw-review-summary__score">
			<span class="dw-review-summary__avg"><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?></span>
			<span class="dw-pdp__rating"><?php echo wc_get_rating_html( $average ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php /* translators: %s: number of reviews */ ?>
			<p><?php echo esc_html( sprintf( _n( 'Based on %s review', 'Based on %s reviews', $total, 'dewier' ), number_format_i18n( $total ) ) ); ?></p>
			<?php if ( comments_open( $product->get_id() ) && ( 'yes' !== get_option( 'woocommerce_review_rating_verification_required' ) || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) ) : ?>
				<?php echo dewier_button( __( 'Write a review', 'dewier' ), '#review_form_wrapper', 'secondary', 'data-dw-write-review' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</div>
		<ul class="dw-review-summary__bars">
			<?php for ( $stars = 5; $stars >= 1; $stars-- ) : ?>
				<?php $count = isset( $counts[ $stars ] ) ? (int) $counts[ $stars ] : 0; ?>
				<?php /* translators: 1: star count, 2: number of reviews */ ?>
				<li aria-label="<?php echo esc_attr( sprintf( _n( '%1$d star: %2$s review', '%1$d stars: %2$s reviews', $count, 'dewier' ), $stars, number_format_i18n( $count ) ) ); ?>">
					<span aria-hidden="true"><?php echo esc_html( $stars ); ?>&#9733;</span>
					<span class="dw-review-summary__bar" aria-hidden="true"><span style="width:<?php echo esc_attr( round( $count / $total * 100 ) ); ?>%"></span></span>
					<span aria-hidden="true"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
				</li>
			<?php endfor; ?>
		</ul>
		<?php if ( $photos ) : ?>
			<div class="dw-review-summary__photos">
				<h3 class="dw-eyebrow"><?php esc_html_e( 'Customer photos', 'dewier' ); ?></h3>
				<ul class="dw-review-photos">
					<?php foreach ( $photos as $index => $photo ) : ?>
						<?php /* translators: 1: photo number, 2: reviewer name */ ?>
						<li><?php echo dewier_review_photo_link( $photo[0], 'summary', sprintf( __( 'Customer photo %1$d from %2$s', 'dewier' ), $index + 1, $photo[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</section>
	<?php
}
