<?php

function vertuo_import_pack_sizes( $rows, $product_ids, $refresh ) {
	$taxonomy = 'pa_pack-size';
	$attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
	$settings = array( 'name' => 'Pack Size', 'slug' => 'pack-size', 'type' => 'ux_label', 'order_by' => 'name_num' );
	if ( ! $attribute_id || 'name_num' !== wc_attribute_orderby( $taxonomy ) || 'ux_label' !== wc_get_attribute( $attribute_id )->type ) {
		$attribute_id = $attribute_id ? wc_update_attribute( $attribute_id, $settings ) : wc_create_attribute( $settings );
		if ( is_wp_error( $attribute_id ) ) {
			WP_CLI::error( $attribute_id );
		}
	}
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, 'product' );
	}

	$terms = array();
	foreach ( array( 50, 100, 150, 200 ) as $size ) {
		$label = "$size Pods";
		$term = get_term_by( 'slug', (string) $size, $taxonomy );
		if ( ! $term ) {
			$saved = wp_insert_term( $label, $taxonomy, array( 'slug' => (string) $size ) );
		} elseif ( $label !== $term->name ) {
			$saved = wp_update_term( $term->term_id, $taxonomy, array( 'name' => $label ) );
		} else {
			$saved = array( 'term_id' => $term->term_id );
		}
		if ( is_wp_error( $saved ) ) {
			WP_CLI::error( $saved );
		}
		$terms[ $size ] = (int) $saved['term_id'];
	}

	$products = $created = 0;
	foreach ( $rows as $row ) {
		if ( 'coffee' !== $row['kind'] ) {
			continue;
		}
		$id = $product_ids[ $row['sku'] ];
		$product = new WC_Product_Variable( $id );
		$stock_status = $refresh
			? ( $row['availability']['listing_add_to_bag'] ? 'instock' : 'outofstock' )
			: $product->get_stock_status();
		$attributes = $product->get_attributes();
		$pack = isset( $attributes[ $taxonomy ] ) ? clone $attributes[ $taxonomy ] : new WC_Product_Attribute();
		if ( ! $pack->get_variation() || count( $terms ) !== count( $pack->get_options() ) || array_diff( $terms, $pack->get_options() ) ) {
			$pack->set_id( $attribute_id );
			$pack->set_name( $taxonomy );
			$pack->set_options( array_values( $terms ) );
			$pack->set_visible( true );
			$pack->set_variation( true );
			$attributes[ $taxonomy ] = $pack;
			$product->set_attributes( $attributes );
		}
		$defaults = $product->get_default_attributes();
		if ( $refresh || ! isset( $defaults[ $taxonomy ] ) ) {
			$defaults[ $taxonomy ] = '50';
			$product->set_default_attributes( $defaults );
		}
		$product->save();

		$existing = array();
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			$existing[ $variation->get_attributes()[ $taxonomy ] ?? '' ] = $variation;
		}

		foreach ( array_keys( $terms ) as $size ) {
			$variation = $existing[ $size ] ?? null;
			if ( $variation && ! $refresh ) {
				continue;
			}
			if ( ! $variation ) {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $id );
				$variation->set_attributes( array( $taxonomy => (string) $size ) );
				++$created;
			}
			$variation->set_stock_status( $stock_status );
			$variation->set_regular_price( wc_format_decimal( $row['pricing']['regular'] * $size / $row['pack_capsules'], 2 ) );
			if ( $refresh ) {
				$variation->set_sale_price( '' );
			}
			$variation->save();
		}
		WC_Product_Variable::sync( $id );
		++$products;
	}
	WP_CLI::success( sprintf( 'Configured Pack Size for %d pod products; created %d variations.', $products, $created ) );
}
