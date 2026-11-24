<?php
/**
 * Renames the visible "Dewier" brand to "Meredithzone" on a site seeded before the rename.
 * Run with: wp eval-file rebrand.php [dry-run]
 *
 * Only display text changes: titles, content, Elementor data, term names and options.
 * Slugs, file names and the dewier-child theme folder stay as they are.
 *
 * @package dewier
 */

defined( 'ABSPATH' ) || exit;

$dewier_dry = in_array( 'dry-run', isset( $args ) ? (array) $args : array(), true );

function dewier_rebrand_value( $value ) {
	if ( is_string( $value ) ) {
		return preg_replace( '/Dewier(?![_A-Za-z])/', 'Meredithzone', $value );
	}
	if ( is_array( $value ) ) {
		return array_map( 'dewier_rebrand_value', $value );
	}
	if ( is_object( $value ) ) {
		foreach ( get_object_vars( $value ) as $key => $item ) {
			$value->$key = dewier_rebrand_value( $item );
		}
	}
	return $value;
}

function dewier_rebrand_log( $what ) {
	WP_CLI::log( $what );
}

global $wpdb;

$dewier_posts = $wpdb->get_results( "SELECT ID, post_type, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_type <> 'revision' AND ( BINARY post_title LIKE '%Dewier%' OR BINARY post_content LIKE '%Dewier%' OR BINARY post_excerpt LIKE '%Dewier%' )" );
foreach ( $dewier_posts as $dewier_post ) {
	dewier_rebrand_log( "post {$dewier_post->ID} ({$dewier_post->post_type}): {$dewier_post->post_title}" );
	if ( ! $dewier_dry ) {
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_title'   => dewier_rebrand_value( $dewier_post->post_title ),
				'post_content' => dewier_rebrand_value( $dewier_post->post_content ),
				'post_excerpt' => dewier_rebrand_value( $dewier_post->post_excerpt ),
			),
			array( 'ID' => $dewier_post->ID )
		);
		clean_post_cache( $dewier_post->ID );
	}
}

$dewier_meta = $wpdb->get_results( "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE BINARY meta_value LIKE '%Dewier%' AND meta_key NOT IN ( '_wp_attached_file', '_wp_attachment_metadata' )" );
foreach ( $dewier_meta as $dewier_row ) {
	dewier_rebrand_log( "meta {$dewier_row->post_id} {$dewier_row->meta_key}" );
	if ( ! $dewier_dry ) {
		$dewier_value = maybe_unserialize( $dewier_row->meta_value );
		$wpdb->update( $wpdb->postmeta, array( 'meta_value' => maybe_serialize( dewier_rebrand_value( $dewier_value ) ) ), array( 'meta_id' => $dewier_row->meta_id ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		wp_cache_delete( $dewier_row->post_id, 'post_meta' );
	}
}

$dewier_terms = $wpdb->get_results( "SELECT t.term_id, t.name, tt.taxonomy FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE BINARY t.name LIKE '%Dewier%' OR BINARY tt.description LIKE '%Dewier%'" );
foreach ( $dewier_terms as $dewier_term ) {
	dewier_rebrand_log( "term {$dewier_term->term_id} ({$dewier_term->taxonomy}): {$dewier_term->name}" );
	if ( ! $dewier_dry ) {
		$dewier_full = get_term( $dewier_term->term_id, $dewier_term->taxonomy );
		wp_update_term(
			$dewier_term->term_id,
			$dewier_term->taxonomy,
			array(
				'name'        => dewier_rebrand_value( $dewier_full->name ),
				'description' => dewier_rebrand_value( $dewier_full->description ),
			)
		);
	}
}

$dewier_options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE BINARY option_value LIKE '%Dewier%' AND option_name NOT LIKE '%transient%' AND option_name NOT IN ( 'stylesheet', 'template', 'recently_edited' )" );
foreach ( $dewier_options as $dewier_option ) {
	dewier_rebrand_log( "option {$dewier_option}" );
	if ( ! $dewier_dry ) {
		update_option( $dewier_option, dewier_rebrand_value( get_option( $dewier_option ) ) );
	}
}

if ( $dewier_dry ) {
	WP_CLI::success( 'Dry run: nothing changed.' );
	return;
}

// Product saves refresh WooCommerce and CommerceKit search data for the renamed products.
foreach ( $dewier_posts as $dewier_post ) {
	if ( 'product' === $dewier_post->post_type && function_exists( 'wc_get_product' ) ) {
		$dewier_product = wc_get_product( $dewier_post->ID );
		if ( $dewier_product ) {
			$dewier_product->save();
		}
	}
}
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_cgkit\\_%' OR option_name LIKE '\\_transient\\_timeout\\_cgkit\\_%'" );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
wp_cache_flush();
WP_CLI::success( 'Brand renamed to Meredithzone.' );
