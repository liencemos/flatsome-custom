<?php

$block = get_page_by_path( 'vertuo-footer', OBJECT, array( 'blocks' ) );
if ( ! $block ) {
	$links = array();
	foreach ( vertuo_storefront_links() as $label => $url ) {
		$links[ sanitize_title( $label ) ] = $url;
	}
	$links['my-account'] = wc_get_page_permalink( 'myaccount' );
	$links['cart'] = wc_get_cart_url();
	foreach ( array( 'contact-us', 'track-order', 'help-center', 'shipping', 'returns-and-warranty', 'about-vertuo', 'store-privacy', 'terms-of-use', 'accessibility' ) as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, array( 'page' ) );
		$links[ $slug ] = $page ? get_permalink( $page ) : '';
	}

	$replacements = array(
		'{{site_name}}'       => esc_html( get_bloginfo( 'name' ) ),
		'{{newsletter_form}}' => (string) (int) get_option( 'vertuo_form_newsletter' ),
		'{{contact_form}}'    => (string) (int) get_option( 'vertuo_form_contact' ),
	);
	foreach ( $links as $slug => $url ) {
		$replacements[ '{{' . $slug . '}}' ] = esc_url( wp_make_link_relative( $url ) );
	}

	$template = file_get_contents( dirname( __DIR__ ) . '/ux-builder/footer.txt' );
	if ( false === $template ) {
		WP_CLI::error( 'Could not read the footer UX Builder template.' );
	}
	$id = wp_insert_post( wp_slash( array(
		'post_type'    => 'blocks',
		'post_status'  => 'publish',
		'post_name'    => 'vertuo-footer',
		'post_title'   => 'Footer',
		'post_content' => strtr( $template, $replacements ),
	) ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	$block = get_post( $id );
}

if ( ! get_theme_mod( 'vertuo_footer_initialized' ) && 'publish' === $block->post_status ) {
	if ( ! get_theme_mod( 'footer_block' ) ) {
		set_theme_mod( 'footer_block', $block->post_name );
	}
	set_theme_mod( 'vertuo_footer_initialized', 1 );
}

WP_CLI::success( 'Footer UX Block ID: ' . $block->ID . '.' );
