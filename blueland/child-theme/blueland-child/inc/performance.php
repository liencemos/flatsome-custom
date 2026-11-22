<?php
/**
 * Front-end loading and image format optimizations.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'image_editor_output_format',
	function ( $formats ) {
		if ( function_exists( 'wp_image_editor_supports' ) && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formats['image/jpeg'] = 'image/webp';
			$formats['image/png']  = 'image/webp';
		}
		return $formats;
	}
);

add_action(
	'wp_head',
	function () {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
		if ( is_front_page() ) {
			echo '<link rel="preload" as="image" href="' . esc_url( BL_CHILD_URL . '/assets/images/hero.webp' ) . '" fetchpriority="high">';
		}
	},
	1
);

add_filter(
	'script_loader_tag',
	function ( $tag, $handle ) {
		if ( 'blueland-child' !== $handle || false !== strpos( $tag, ' defer' ) ) {
			return $tag;
		}
		return preg_replace( '/<script\b/', '<script defer', $tag, 1 );
	},
	10,
	2
);

add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter(
			'tiny_mce_plugins',
			function ( $plugins ) {
				return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
			}
		);
	}
);

add_filter(
	'wp_resource_hints',
	function ( $urls, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $urls;
		}
		return array_values(
			array_filter(
				$urls,
				function ( $url ) {
					return ! is_string( $url ) || false === strpos( $url, 's.w.org' );
				}
			)
		);
	},
	10,
	2
);
