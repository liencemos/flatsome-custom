<?php

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce must be active.' );
}
if ( ! current_user_can( 'manage_product_terms' ) ) {
	WP_CLI::error( 'Run the import with --user=<WordPress administrator>.' );
}

require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

class Vertuo_Product_CSV_Importer extends WC_Product_CSV_Importer {
	public function keep_skus( $skus ) {
		$this->parsed_data = array_filter( $this->parsed_data, static function ( $row ) use ( $skus ) {
			return isset( $skus[ $row['sku'] ?? '' ] );
		} );
	}
}

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
	'Meta: vertuo_listing_cup' => 'meta:vertuo_listing_cup',
);

for ( $i = 1; $i <= 7; $i++ ) {
	$mapping[ "Attribute $i name" ] = "attributes:name$i";
	$mapping[ "Attribute $i value(s)" ] = "attributes:value$i";
	$mapping[ "Attribute $i visible" ] = "attributes:visible$i";
	$mapping[ "Attribute $i global" ] = "attributes:taxonomy$i";
}

$refresh = '1' === ( $args[0] ?? '' );
$legacy_attributes = array_flip( array_map( 'sanitize_title', array( 'Dung tích', 'Cường độ', 'Hương vị', 'Quy cách', 'Mức rang', 'Xuất xứ', 'Ghi chú', 'Source Pack Price' ) ) );
$legacy_skus = array();
foreach ( $catalog['products'] as $row ) {
	$sku = 'VERTUO-' . $row['sku'];
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	if ( $product ) {
		$attributes = $product->get_attributes( 'edit' );
		if ( array_intersect_key( $attributes, $legacy_attributes ) ) {
			$legacy_skus[ $sku ] = array_diff_key( $attributes, $legacy_attributes );
		}
	}
}
$preserve_attributes = static function ( $product ) use ( $legacy_skus ) {
	$retained = $legacy_skus[ $product->get_sku() ] ?? array();
	$product->set_attributes( array_filter( $product->get_attributes( 'edit' ) ) + $retained );
	return $product;
};
foreach ( $refresh || $legacy_skus ? array( false, true ) : array( false ) as $update_existing ) {
	$import_mapping = $mapping;
	if ( $update_existing && ! $refresh ) {
		foreach ( array( 'Type', 'Name', 'Published', 'Visibility in catalog', 'In stock?', 'Regular price', 'Categories', 'Position' ) as $column ) {
			$import_mapping[ $column ] = '';
		}
	}
	$importer = new Vertuo_Product_CSV_Importer(
		$data_dir . '/woocommerce-products.csv',
		array( 'mapping' => $import_mapping, 'parse' => true, 'prevent_timeouts' => false, 'update_existing' => $update_existing )
	);
	if ( $update_existing && ! $refresh ) {
		$importer->keep_skus( $legacy_skus );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', $preserve_attributes );
	}
	$results = $importer->import();
	remove_filter( 'woocommerce_product_import_pre_insert_product_object', $preserve_attributes );
	foreach ( $results['failed'] as $error ) {
		WP_CLI::warning( $error->get_error_message() );
	}
	if ( $results['failed'] ) {
		WP_CLI::error( 'One or more rows failed to import.' );
	}
	WP_CLI::success( $update_existing
		? sprintf( 'Updated %d products from the English storefront CSV.', count( $results['updated'] ) )
		: sprintf( 'Imported %d products; skipped %d existing products.', count( $results['imported'] ), count( $results['skipped'] ) )
	);
}

$pack_size_id = wc_attribute_taxonomy_id_by_name( 'pack-size' );
if ( ! $pack_size_id ) {
	WP_CLI::error( 'The Pack Size attribute was not imported.' );
}
if ( 'name_num' !== wc_attribute_orderby( 'pa_pack-size' ) ) {
	$saved = wc_update_attribute( $pack_size_id, array( 'order_by' => 'name_num' ) );
	if ( is_wp_error( $saved ) ) {
		WP_CLI::error( $saved );
	}
}

$product_ids = array();
foreach ( $catalog['products'] as $row ) {
	$id = wc_get_product_id_by_sku( 'VERTUO-' . $row['sku'] );
	if ( ! $id ) {
		WP_CLI::error( 'Missing imported SKU: ' . $row['sku'] );
	}
	$product_ids[ $row['sku'] ] = $id;
	if ( $refresh || isset( $legacy_skus[ 'VERTUO-' . $row['sku'] ] ) ) {
		$product = wc_get_product( $id );
		$attributes = $product->get_attributes( 'edit' );
		$remaining = array_diff_key( $attributes, $legacy_attributes );
		if ( $attributes !== $remaining ) {
			$product->set_attributes( $remaining );
			$product->save();
		}
	}
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

WP_CLI::success( sprintf( 'Saved source order for %d collections.', count( $collection_terms ) ) );
