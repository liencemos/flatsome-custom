<?php

$catalog = json_decode( file_get_contents( dirname( __DIR__ ) . '/data/catalog.json' ), true, 512, JSON_THROW_ON_ERROR );

function vertuo_check( $condition, $message ) {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

$product_ids = array();
foreach ( $catalog['products'] as $row ) {
	$sku = 'VERTUO-' . $row['sku'];
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	vertuo_check( $product && 'publish' === $product->get_status(), "$sku is not published." );
	$product_ids[ $row['sku'] ] = $product->get_id();
	vertuo_check( $row['pricing']['regular'] === wc_format_decimal( $product->get_regular_price(), 2 ), "$sku has the wrong price." );
	$specs = $row['source_specs'];
	vertuo_check( $specs['headline'] === $product->get_short_description(), "$sku has the wrong source headline." );
	vertuo_check( $specs['product_description_html'] === $product->get_description(), "$sku has the wrong source description." );
	vertuo_check( $specs['listing_cup'] === $product->get_meta( 'vertuo_listing_cup' ), "$sku has the wrong listing cup size." );
	vertuo_check( ( $row['compatibility'] ?? '' ) === $product->get_meta( 'vertuo_machine_compatibility' ), "$sku has the wrong machine compatibility." );
	foreach ( $specs['attributes'] as $name => $expected ) {
		vertuo_check( $expected === $product->get_attribute( $name ), "$sku has the wrong $name attribute." );
	}
	$pack_size = $product->get_attributes()['pa_pack-size'] ?? null;
	vertuo_check( 'coffee' === $row['kind']
		? $pack_size && $pack_size->is_taxonomy() && '50, 100, 150, 200' === $product->get_attribute( 'pa_pack-size' )
		: ! $pack_size && $product->is_type( 'simple' ), "$sku has incorrect Pack Size attributes or product type." );
	vertuo_check( ! $product->get_attribute( 'Source Pack Price' ), "$sku still displays an archived listing price." );
	vertuo_check( ! $product->get_attribute( 'Hương vị' ), "$sku still has a translated flavor attribute." );
	vertuo_check( $row['image_url'] === $product->get_meta( 'vertuo_image_source' ) && ! $product->get_image_id(), "$sku has incorrect image handling." );

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
	vertuo_check( ! is_wp_error( $terms ), "$sku collections could not be read." );
	vertuo_check( ! array_diff( array_merge( array( 'Vertuo' ), $row['collections'] ), array_map( 'html_entity_decode', $terms ) ), "$sku is missing collections." );
}

WP_CLI::success( sprintf( 'Validated %d published products: source descriptions, USD prices, attributes, image metadata and collections.', count( $catalog['products'] ) ) );

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
