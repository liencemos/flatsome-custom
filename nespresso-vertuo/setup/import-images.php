<?php

function vertuo_import_images( $rows, $product_ids ) {
	if ( ! current_user_can( 'upload_files' ) ) {
		WP_CLI::warning( 'Product images were skipped because the WordPress user cannot upload files.' );
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$downloaded = $reused = $kept = $failed = 0;
	foreach ( $rows as $row ) {
		$product = wc_get_product( $product_ids[ $row['sku'] ] );
		if ( wp_attachment_is_image( $product->get_image_id() ) ) {
			++$kept;
			continue;
		}

		$url = $row['image_url'];
		$matches = get_posts( array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'post_mime_type' => 'image',
			'meta_key' => '_source_url',
			'meta_value' => $url,
			'fields' => 'ids',
		) );
		$image_id = 0;
		foreach ( $matches as $match ) {
			if ( is_readable( get_attached_file( $match ) ) ) {
				$image_id = $match;
				++$reused;
				break;
			}
		}
		if ( ! $image_id ) {
			$image_id = vertuo_download_image( $url, $product );
			if ( is_wp_error( $image_id ) ) {
				WP_CLI::warning( sprintf( '%s image not imported: %s', $product->get_sku(), $image_id->get_error_message() ) );
				++$failed;
				continue;
			}
			++$downloaded;
		}
		$product->set_image_id( $image_id );
		$product->save();
	}

	WP_CLI::success( sprintf( 'Product images: downloaded %d, reused %d, kept %d, failed %d.', $downloaded, $reused, $kept, $failed ) );
}

function vertuo_download_image( $url, $product ) {
	$file = download_url( $url, 15 );
	if ( is_wp_error( $file ) ) {
		return $file;
	}

	$image_id = media_handle_sideload(
		array( 'name' => wp_basename( wp_parse_url( $url, PHP_URL_PATH ) ), 'tmp_name' => $file ),
		$product->get_id(),
		$product->get_name()
	);
	if ( is_file( $file ) ) {
		wp_delete_file( $file );
	}
	if ( ! is_wp_error( $image_id ) ) {
		update_post_meta( $image_id, '_source_url', esc_url_raw( $url ) );
		update_post_meta( $image_id, '_wp_attachment_image_alt', wp_slash( $product->get_name() ) );
	}
	return $image_id;
}
