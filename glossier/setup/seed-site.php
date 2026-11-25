<?php
/**
 * Seeds menus, content pages and the Elementor home and help pages.
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
	'About Meredithzone',
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

/**
 * Gives a page its Elementor layout. A page that already has one, or text of
 * its own, is left alone unless $reset is set. Returns whether it was written.
 */
function dewier_site_layout( $page_id, $elements, $reset, $placeholder = '' ) {
	$content = trim( (string) get_post_field( 'post_content', $page_id ) );
	if ( ! $reset && ( get_post_meta( $page_id, '_elementor_data', true ) || ( '' !== $content && $content !== $placeholder ) ) ) {
		return false;
	}
	update_post_meta( $page_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
	}
	return true;
}

/**
 * Writes a layout as a file for Elementor > Templates > Import.
 */
function dewier_site_export( $file, $title, $elements ) {
	$export = array(
		'version'       => '0.4',
		'title'         => $title,
		'type'          => 'page',
		'content'       => $elements,
		'page_settings' => array( 'template' => 'elementor_header_footer' ),
	);
	$export = str_replace( str_replace( '/', '\/', home_url() ), '', wp_json_encode( $export, JSON_PRETTY_PRINT ) );
	file_put_contents( __DIR__ . '/' . $file, $export ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

$dewier_help = array(
	dewier_site_element( 'dewier-help-search' ),
	dewier_site_element( 'dewier-faq' ),
	dewier_site_element( 'dewier-help-contact' ),
);

// Existing layouts are kept unless the script is run with "reset-home" or "reset-help".
$dewier_args     = isset( $args ) ? (array) $args : array();
$dewier_home_id  = dewier_site_page( 'home', 'Home' );
$dewier_help_id  = dewier_site_page( 'help', 'Help & FAQ', $dewier_copy );
$dewier_set_home = dewier_site_layout( $dewier_home_id, $dewier_home, in_array( 'reset-home', $dewier_args, true ) );
$dewier_set_help = dewier_site_layout( $dewier_help_id, $dewier_help, in_array( 'reset-help', $dewier_args, true ), $dewier_copy );
$dewier_quiz_id  = dewier_site_page( 'quiz', 'Find your routine' );
dewier_site_layout( $dewier_quiz_id, array( dewier_site_element( 'dewier-quiz' ) ), in_array( 'reset-quiz', $dewier_args, true ) );
if ( defined( 'ELEMENTOR_VERSION' ) ) {
	update_option( 'elementor_experiment-container', 'active' );
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $dewier_home_id );

// CommerceKit: a wishlist page, plus the modules the Meredithzone templates are wired to.
// The page is saved first: each activate command writes the option in its own process.
if ( function_exists( 'commercekit_get_options' ) ) {
	$dewier_kit = get_option( 'commercekit', array() );
	if ( empty( $dewier_kit['wsl_page'] ) || 'publish' !== get_post_status( (int) $dewier_kit['wsl_page'] ) ) {
		$dewier_kit['wsl_page'] = dewier_site_page( 'wishlist', 'Wishlist', '<!-- wp:shortcode -->[commercegurus_wishlist]<!-- /wp:shortcode -->' );
		update_option( 'commercekit', $dewier_kit );
	}
	foreach ( array( 'activate ajax-search', 'activate wishlist', 'activate waitlist', 'activate stock-meter', 'sticky-atc activate sticky-atc-desktop', 'sticky-atc activate sticky-atc-mobile' ) as $dewier_module ) {
		WP_CLI::runcommand( 'cg-commercekit ' . $dewier_module, array( 'exit_error' => false ) );
	}
}

// Importable copies of both pages.
dewier_site_export( 'home.json', 'Meredithzone Home', $dewier_home );
dewier_site_export( 'help.json', 'Meredithzone Help', $dewier_help );

flush_rewrite_rules();
WP_CLI::log( 'Site seed complete. Home page ID: ' . $dewier_home_id . ( $dewier_set_home ? '' : ' (existing layout kept)' ) . '. Help page ID: ' . $dewier_help_id . ( $dewier_set_help ? '' : ' (existing page kept)' ) );
