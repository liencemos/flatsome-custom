<?php
/**
 * Seeds menus, content pages and the Elementor home page.
 *
 * Run after seed.php: wp eval-file seed-site.php
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_site_page( $slug, $title, $content = '' ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return (int) $page->ID;
	}
	return (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		)
	);
}

/**
 * Rebuilds a menu from a list of items: array( title, url, children ).
 */
function dewier_site_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		wp_delete_nav_menu( $menu->term_id );
	}
	$menu_id = wp_create_nav_menu( $name );
	foreach ( $items as $item ) {
		$parent = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $item[0],
				'menu-item-url'    => $item[1],
				'menu-item-status' => 'publish',
			)
		);
		foreach ( isset( $item[2] ) ? $item[2] : array() as $child ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $child[0],
					'menu-item-url'       => $child[1],
					'menu-item-parent-id' => $parent,
					'menu-item-status'    => 'publish',
				)
			);
		}
	}
	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

function dewier_site_cat( $slug ) {
	$link = get_term_link( $slug, 'product_cat' );
	return is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link;
}

function dewier_site_cats( $slugs ) {
	$items = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term ) {
			$items[] = array( $term->name, dewier_site_cat( $slug ) );
		}
	}
	return $items;
}

// Content pages.
$dewier_copy  = '<!-- wp:paragraph --><p>This page is a placeholder. Replace this text with your own content.</p><!-- /wp:paragraph -->';
$dewier_pages = array();
foreach ( array(
	'contact'          => 'Contact',
	'help'             => 'Help & FAQ',
	'shipping-returns' => 'Returns & exchanges',
	'about'            => 'About us',
	'careers'          => 'Careers',
	'stores'           => 'Find your store',
	'terms'            => 'Terms of use',
	'accessibility'    => 'Accessibility',
) as $dewier_slug => $dewier_title ) {
	$dewier_pages[ $dewier_slug ] = get_permalink( dewier_site_page( $dewier_slug, $dewier_title, $dewier_copy ) );
}

// Menus.
$dewier_primary = array();
foreach ( array(
	'fragrance' => array(),
	'makeup'    => array( 'face', 'lip', 'brow', 'eye', 'cheek' ),
	'skincare'  => array( 'cleansers', 'moisturizers', 'treatments' ),
	'balms'     => array(),
	'body'      => array(),
	'goods'     => array(),
	'sets'      => array(),
) as $dewier_slug => $dewier_children ) {
	$dewier_term = get_term_by( 'slug', $dewier_slug, 'product_cat' );
	if ( $dewier_term ) {
		$dewier_primary[] = array( $dewier_term->name, dewier_site_cat( $dewier_slug ), dewier_site_cats( $dewier_children ) );
	}
}
$dewier_primary[] = array( 'Shop all', wc_get_page_permalink( 'shop' ) );

dewier_site_menu( 'Primary', 'primary', $dewier_primary );
dewier_site_menu(
	'How can we help?',
	'dewier_footer_help',
	array(
		array( 'Contact', $dewier_pages['contact'] ),
		array( 'Track my package', wc_get_page_permalink( 'myaccount' ) ),
		array( 'Returns & exchanges', $dewier_pages['shipping-returns'] ),
		array( 'Help & FAQ', $dewier_pages['help'] ),
	)
);
dewier_site_menu(
	'About Dewier',
	'dewier_footer_about',
	array(
		array( 'About us', $dewier_pages['about'] ),
		array( 'Careers', $dewier_pages['careers'] ),
	)
);
dewier_site_menu( 'Stores', 'dewier_footer_stores', array( array( 'Find your store', $dewier_pages['stores'] ) ) );
dewier_site_menu(
	'Social',
	'dewier_footer_social',
	array(
		array( 'Instagram', 'https://www.instagram.com/' ),
		array( 'Pinterest', 'https://www.pinterest.com/' ),
		array( 'YouTube', 'https://www.youtube.com/' ),
		array( 'TikTok', 'https://www.tiktok.com/' ),
	)
);
dewier_site_menu(
	'Legal',
	'dewier_footer_legal',
	array(
		array( 'Privacy policy', get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) ),
		array( 'Terms of use', $dewier_pages['terms'] ),
		array( 'Accessibility', $dewier_pages['accessibility'] ),
	)
);

// Elementor home page.
function dewier_site_element( $widget, $settings = array() ) {
	return array(
		'id'       => substr( md5( $widget . wp_json_encode( $settings ) ), 0, 7 ),
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array(
			'content_width' => 'full',
			'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
		),
		'elements' => array(
			array(
				'id'         => substr( md5( 'w' . $widget . wp_json_encode( $settings ) ), 0, 7 ),
				'elType'     => 'widget',
				'widgetType' => $widget,
				'settings'   => (object) $settings,
				'elements'   => array(),
			),
		),
	);
}

$dewier_images = get_stylesheet_directory_uri() . '/assets/images/';
$dewier_social = array();
for ( $dewier_i = 1; $dewier_i <= 6; $dewier_i++ ) {
	$dewier_social[] = array(
		'_id'   => 'social' . $dewier_i,
		'image' => array( 'url' => $dewier_images . 'social-' . $dewier_i . '.jpg', 'id' => '' ),
		'label' => 'Community photo ' . $dewier_i,
		'link'  => array( 'url' => wc_get_page_permalink( 'shop' ) ),
	);
}

$dewier_home = array(
	dewier_site_element( 'dewier-hero' ),
	dewier_site_element( 'dewier-gift' ),
	dewier_site_element( 'dewier-products' ),
	dewier_site_element( 'dewier-promo-cards' ),
	dewier_site_element( 'dewier-story' ),
	dewier_site_element( 'dewier-carousel' ),
	dewier_site_element( 'dewier-carousel', array( 'eyebrow' => 'Get festive', 'size' => 'large', 'items' => $dewier_social ) ),
);

// An existing home layout is kept unless the script is run with "reset-home".
$dewier_home_id  = dewier_site_page( 'home', 'Home' );
$dewier_reset    = isset( $args ) && in_array( 'reset-home', (array) $args, true );
$dewier_set_home = $dewier_reset || ! get_post_meta( $dewier_home_id, '_elementor_data', true );
if ( $dewier_set_home ) {
	update_post_meta( $dewier_home_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $dewier_home_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $dewier_home_id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $dewier_home_id, '_elementor_data', wp_slash( wp_json_encode( $dewier_home ) ) );
}
if ( defined( 'ELEMENTOR_VERSION' ) ) {
	if ( $dewier_set_home ) {
		update_post_meta( $dewier_home_id, '_elementor_version', ELEMENTOR_VERSION );
	}
	update_option( 'elementor_experiment-container', 'active' );
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $dewier_home_id );

// Importable copy of the home page for Elementor > Templates > Import.
$dewier_export = array(
	'version'       => '0.4',
	'title'         => 'Dewier Home',
	'type'          => 'page',
	'content'       => $dewier_home,
	'page_settings' => array( 'template' => 'elementor_header_footer' ),
);
$dewier_export = str_replace( str_replace( '/', '\/', home_url() ), '', wp_json_encode( $dewier_export, JSON_PRETTY_PRINT ) );
file_put_contents( __DIR__ . '/home.json', $dewier_export ); // phpcs:ignore WordPress.WP.AlternativeFunctions

flush_rewrite_rules();
WP_CLI::log( 'Site seed complete. Home page ID: ' . $dewier_home_id . ( $dewier_set_home ? '' : ' (existing layout kept)' ) );
