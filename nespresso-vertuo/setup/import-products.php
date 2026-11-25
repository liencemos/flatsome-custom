<?php

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce must be active.' );
}
if ( ! current_user_can( 'manage_product_terms' ) ) {
	WP_CLI::error( 'Run the import with --user=<WordPress administrator>.' );
}

require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

$data_dir = dirname( __DIR__ ) . '/data';
$catalog = json_decode( file_get_contents( $data_dir . '/catalog.json' ), true, 512, JSON_THROW_ON_ERROR );
$parent = term_exists( 'Vertuo', 'product_cat', 0 ) ?: wp_insert_term( 'Vertuo', 'product_cat' );
if ( is_wp_error( $parent ) ) {
	WP_CLI::error( $parent );
}
$parent_id = (int) $parent['term_id'];
$collection_terms = array();
foreach ( $catalog['collections'] as $collection ) {
	$term = term_exists( $collection['name'], 'product_cat', $parent_id )
		?: wp_insert_term( $collection['name'], 'product_cat', array( 'parent' => $parent_id ) );
	if ( is_wp_error( $term ) ) {
		WP_CLI::error( $term );
	}
	$collection_terms[ $term['term_id'] ] = $collection['skus'];
}

$mapping = array(
	'Type' => 'type',
	'SKU' => 'sku',
	'Name' => 'name',
	'Published' => 'published',
	'Visibility in catalog' => 'catalog_visibility',
	'Short description' => 'short_description',
	'Description' => 'description',
	'In stock?' => 'stock_status',
	'Regular price' => 'regular_price',
	'Categories' => 'category_ids',
	'Position' => 'menu_order',
	'Meta: vertuo_source_url' => 'meta:vertuo_source_url',
	'Meta: vertuo_image_source' => 'meta:vertuo_image_source',
	'Meta: vertuo_listing_current_usd' => 'meta:vertuo_listing_current_usd',
);

for ( $i = 1; $i <= 7; $i++ ) {
	$mapping[ "Attribute $i name" ] = "attributes:name$i";
	$mapping[ "Attribute $i value(s)" ] = "attributes:value$i";
	$mapping[ "Attribute $i visible" ] = "attributes:visible$i";
	$mapping[ "Attribute $i global" ] = "attributes:taxonomy$i";
}

$importer = new WC_Product_CSV_Importer(
	$data_dir . '/woocommerce-products.csv',
	array( 'mapping' => $mapping, 'parse' => true, 'prevent_timeouts' => false )
);
$results = $importer->import();
foreach ( $results['failed'] as $error ) {
	WP_CLI::warning( $error->get_error_message() );
}
if ( $results['failed'] ) {
	WP_CLI::error( 'One or more rows failed to import.' );
}

$product_ids = array();
foreach ( $catalog['products'] as $row ) {
	$id = wc_get_product_id_by_sku( 'VERTUO-' . $row['sku'] );
	if ( ! $id ) {
		WP_CLI::error( 'Missing imported SKU: ' . $row['sku'] );
	}
	$product_ids[ $row['sku'] ] = $id;
}
foreach ( $collection_terms as $term_id => $skus ) {
	$ordered_ids = array_map( static function ( $sku ) use ( $product_ids ) {
		return $product_ids[ $sku ];
	}, $skus );
	$saved = update_term_meta( $term_id, 'vertuo_product_order', $ordered_ids );
	if ( is_wp_error( $saved ) ) {
		WP_CLI::error( $saved );
	}
}

WP_CLI::success( sprintf( 'Imported %d products; skipped %d existing products.', count( $results['imported'] ), count( $results['skipped'] ) ) );
