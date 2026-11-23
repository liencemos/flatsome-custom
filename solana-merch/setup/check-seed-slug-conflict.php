<?php
/**
 * Run against a disposable seeded demo with WP-CLI: wp eval-file /path/to/setup/check-seed-slug-conflict.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$home = get_page_by_path( 'signal-home', OBJECT, array( 'page' ) );
if ( ! $home || ! get_post_meta( $home->ID, '_signal_demo', true ) || $home->ID !== (int) get_option( 'page_on_front' ) ) {
	WP_CLI::error( 'This regression test requires a disposable seeded demo with signal-home as the front page.' );
}

$site_title = get_option( 'blogname' );
$sentinel = 'FORM / EDIT slug conflict test';
$attachment = 0;
try {
	$moved = wp_update_post( array( 'ID' => $home->ID, 'post_name' => 'form-edit-slug-conflict-test-home' ), true );
	if ( is_wp_error( $moved ) ) {
		throw new RuntimeException( $moved->get_error_message() );
	}
	$attachment = wp_insert_attachment( array(
		'post_name'   => 'signal-home',
		'post_title'  => 'FORM / EDIT slug conflict test',
		'post_parent' => 0,
	), false, 0, true );
	if ( is_wp_error( $attachment ) || 'signal-home' !== get_post_field( 'post_name', $attachment ) ) {
		throw new RuntimeException( 'Could not create the same-slug attachment fixture.' );
	}
	update_option( 'blogname', $sentinel );
	$before = wp_count_posts( 'page' );
	$result = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __DIR__ . '/seed.php' ), array(
		'return'     => 'all',
		'exit_error' => false,
		'launch'     => true,
	) );
	if ( ! $result->return_code || false === strpos( $result->stderr, 'page slug signal-home is unavailable' ) ) {
		throw new RuntimeException( 'Seed did not report the attachment conflict: ' . $result->stderr );
	}
	wp_cache_delete( _count_posts_cache_key( 'page' ), 'counts' );
	foreach ( array( 'alloptions', 'page_on_front', 'blogname' ) as $option_cache_key ) {
		wp_cache_delete( $option_cache_key, 'options' );
	}
	clean_post_cache( $attachment );
	if ( $before != wp_count_posts( 'page' ) || $home->ID !== (int) get_option( 'page_on_front' ) || $sentinel !== get_option( 'blogname' ) ||
		'signal-home' !== get_post_field( 'post_name', $attachment ) || 'attachment' !== get_post_type( $attachment ) ) {
		throw new RuntimeException( 'Seed changed a page, the attachment, or site settings despite the slug conflict.' );
	}
} finally {
	if ( $attachment && ! is_wp_error( $attachment ) ) {
		wp_delete_attachment( $attachment, true );
	}
	wp_update_post( array( 'ID' => $home->ID, 'post_name' => 'signal-home' ) );
	update_option( 'page_on_front', $home->ID );
	update_option( 'blogname', $site_title );
}
WP_CLI::success( 'The unattached signal-home attachment blocked the seed before creating a page or changing the front page or site title.' );

$about = get_page_by_path( 'about', OBJECT, array( 'page' ) );
$menu_id = get_nav_menu_locations()['primary'] ?? 0;
$about_item = null;
foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
	if ( $about && 'page' === $item->object && $about->ID === (int) $item->object_id ) {
		$about_item = get_post( $item->ID );
		break;
	}
}
if ( ! $about_item ) {
	WP_CLI::error( 'This regression test requires an About page in the demo’s primary menu.' );
}

try {
	$updated_page = wp_update_post( array( 'ID' => $about->ID, 'post_title' => 'About Us' ), true );
	$updated_item = wp_update_post( array( 'ID' => $about_item->ID, 'post_title' => 'About', 'post_name' => 'about' ), true );
	if ( is_wp_error( $updated_page ) || is_wp_error( $updated_item ) || 'About Us' !== get_post_field( 'post_title', $about->ID ) ||
		'about' !== get_post_field( 'post_name', $about_item->ID ) || 'about' !== get_post_field( 'post_name', $about->ID ) ) {
		throw new RuntimeException( 'Could not create the matching About page and menu item slugs.' );
	}
	for ( $attempt = 1; $attempt <= 2; ++$attempt ) {
		$result = WP_CLI::runcommand( 'eval-file ' . escapeshellarg( __DIR__ . '/seed.php' ), array(
			'return'     => 'all',
			'exit_error' => false,
			'launch'     => true,
		) );
		if ( $result->return_code ) {
			throw new RuntimeException( "Reseed {$attempt} rejected the matching menu item slug: " . $result->stderr );
		}
		clean_post_cache( $about->ID );
		clean_post_cache( $about_item->ID );
		if ( 'About Us' !== get_post_field( 'post_title', $about->ID ) || 'about' !== get_post_field( 'post_name', $about->ID ) ||
			'about' !== get_post_field( 'post_name', $about_item->ID ) ) {
			throw new RuntimeException( "Reseed {$attempt} changed the About page or matching menu item slug." );
		}
		WP_CLI::log( "Reseed {$attempt}: " . strtok( $result->stdout, "\n" ) );
	}
} finally {
	wp_update_post( array( 'ID' => $about->ID, 'post_title' => wp_slash( $about->post_title ) ) );
	wp_update_post( array(
		'ID'         => $about_item->ID,
		'post_title' => wp_slash( $about_item->post_title ),
		'post_name'  => $about_item->post_name,
	) );
}
WP_CLI::success( 'The About Us page and the About menu item kept the same slug across two reseeds.' );
