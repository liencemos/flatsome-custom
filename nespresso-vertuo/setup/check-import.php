<?php

$catalog = json_decode( file_get_contents( dirname( __DIR__ ) . '/data/catalog.json' ), true, 512, JSON_THROW_ON_ERROR );

function vertuo_check( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

$product_ids = array();
$source_image_count = 0;
$pod_count = $variation_count = $in_stock_count = $simple_count = 0;
foreach ( $catalog['products'] as $row ) {
	$sku = 'VERTUO-' . $row['sku'];
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	vertuo_check( $product && 'publish' === $product->get_status(), "$sku is not published." );
	$product_ids[ $row['sku'] ] = $product->get_id();
	if ( 'coffee' !== $row['kind'] ) {
		vertuo_check( $row['pricing']['regular'] === wc_format_decimal( $product->get_regular_price(), 2 ), "$sku has the wrong price." );
	}
	$specs = $row['source_specs'];
	vertuo_check( $specs['headline'] === $product->get_short_description(), "$sku has the wrong source headline." );
	vertuo_check( $specs['product_description_html'] === $product->get_description(), "$sku has the wrong source description." );
	vertuo_check( $specs['listing_cup'] === $product->get_meta( 'vertuo_listing_cup' ), "$sku has the wrong listing cup size." );
	vertuo_check( ( $row['compatibility'] ?? '' ) === html_entity_decode( $product->get_meta( 'vertuo_machine_compatibility' ) ), "$sku has the wrong machine compatibility." );
	foreach ( $specs['attributes'] as $name => $expected ) {
		vertuo_check( $expected === $product->get_attribute( $name ), "$sku has the wrong $name attribute." );
	}
	$pack_size = $product->get_attributes()['pa_pack-size'] ?? null;
	if ( 'coffee' === $row['kind'] ) {
		vertuo_check( $product->is_type( 'variable' ) && $pack_size && $pack_size->is_taxonomy() && $pack_size->get_variation(), "$sku is missing its Pack Size variation attribute." );
		vertuo_check( '50 Pods, 100 Pods, 150 Pods, 200 Pods' === $product->get_attribute( 'pa_pack-size' ), "$sku has incorrect Pack Size options." );
		vertuo_check( '50' === $product->get_default_attributes()['pa_pack-size'], "$sku has the wrong default Pack Size." );
		$expected_sizes = array( 50, 100, 150, 200 );
		foreach ( $product->get_children() as $id ) {
			$variation = wc_get_product( $id );
			$size = (int) ( $variation->get_attributes()['pa_pack-size'] ?? 0 );
			vertuo_check( in_array( $size, $expected_sizes, true ), "$sku has a duplicate or incorrect variation size: $size." );
			$expected_sizes = array_diff( $expected_sizes, array( $size ) );
			$price = wc_format_decimal( round( $row['pricing']['regular'] * $size / $row['pack_capsules'], 2 ), 2 );
			vertuo_check( 'publish' === $variation->get_status() && $price === $variation->get_regular_price() && $price === $variation->get_price(), "$sku has an inactive or incorrectly priced $size-pod variation." );
			vertuo_check( $row['availability']['listing_add_to_bag'] === $variation->is_in_stock(), "$sku has the wrong stock status for $size Pods." );
			$in_stock_count += (int) $variation->is_in_stock();
			++$variation_count;
		}
		vertuo_check( ! $expected_sizes, "$sku does not have all four Pack Size variations." );
		++$pod_count;
	} else {
		vertuo_check( ! $pack_size && $product->is_type( 'simple' ), "$sku should remain a simple product without Pack Size." );
		++$simple_count;
	}
	vertuo_check( ! $product->get_attribute( 'Source Pack Price' ), "$sku still displays an archived listing price." );
	vertuo_check( ! $product->get_attribute( 'Hương vị' ), "$sku still has a translated flavor attribute." );
	vertuo_check( $row['image_url'] === $product->get_meta( 'vertuo_image_source' ), "$sku has the wrong image source URL." );
	$image_id = $product->get_image_id();
	if ( $image_id ) {
		vertuo_check( wp_attachment_is_image( $image_id ), "$sku has an invalid featured image." );
		if ( $row['image_url'] === get_post_meta( $image_id, '_source_url', true ) ) {
			vertuo_check( is_readable( get_attached_file( $image_id ) ), "$sku's downloaded image is missing from the Media Library." );
			++$source_image_count;
		}
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
	vertuo_check( ! is_wp_error( $terms ), "$sku collections could not be read." );
	vertuo_check( ! array_diff( array_merge( array( 'Vertuo' ), $row['collections'] ), array_map( 'html_entity_decode', $terms ) ), "$sku is missing collections." );
}

WP_CLI::success( sprintf( 'Validated %d published products: source descriptions, USD prices, attributes, image metadata and collections.', count( $catalog['products'] ) ) );
WP_CLI::success( sprintf( 'Validated %d variable pod products, %d priced Pack Size variations (%d in stock) and %d simple bundles/accessories.', $pod_count, $variation_count, $in_stock_count, $simple_count ) );
if ( '1' === ( $args[0] ?? '' ) ) {
	vertuo_check( count( $catalog['products'] ) === $source_image_count, 'Some catalog products are missing their local source image.' );
}
WP_CLI::success( sprintf( '%d of %d products use a local image from their source URL.', $source_image_count, count( $catalog['products'] ) ) );

$root = get_term_by( 'slug', 'vertuo', 'product_cat' );
vertuo_check( $root, 'Vertuo catalog category is missing.' );
foreach ( $catalog['collections'] as $collection ) {
	$term = term_exists( $collection['name'], 'product_cat', $root->term_id );
	vertuo_check( $term && ! is_wp_error( $term ), "{$collection['name']} is missing." );
	$expected = array_map( static function ( $sku ) use ( $product_ids ) {
		return $product_ids[ $sku ];
	}, $collection['skus'] );
	$actual = get_term_meta( (int) $term['term_id'], 'vertuo_product_order', true );
	vertuo_check( $expected === $actual, "{$collection['name']} has incorrect product order." );
}
WP_CLI::success( sprintf( 'Validated source product order for %d collections.', count( $catalog['collections'] ) ) );
