<?php

$catalog = json_decode( file_get_contents( dirname( __DIR__ ) . '/data/catalog.json' ), true, 512, JSON_THROW_ON_ERROR );

function vertuo_check( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

foreach ( $catalog['products'] as $row ) {
	$sku = 'VERTUO-' . $row['sku'];
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	vertuo_check( $product && 'publish' === $product->get_status(), "$sku is not published." );
	vertuo_check( $row['pricing']['regular'] === wc_format_decimal( $product->get_regular_price(), 2 ), "$sku has the wrong price." );
	vertuo_check( $row['profile_vi'] === $product->get_attribute( 'Hương vị' ), "$sku has the wrong flavor attribute." );
	vertuo_check( $row['image_url'] === $product->get_meta( 'vertuo_image_source' ) && ! $product->get_image_id(), "$sku has incorrect image handling." );

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
	vertuo_check( ! is_wp_error( $terms ), "$sku collections could not be read." );
	vertuo_check( ! array_diff( array_merge( array( 'Vertuo' ), $row['collections'] ), array_map( 'html_entity_decode', $terms ) ), "$sku is missing collections." );
}

WP_CLI::success( sprintf( 'Validated %d published products: USD prices, flavor attributes, image metadata and collections.', count( $catalog['products'] ) ) );
