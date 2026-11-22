<?php
/**
 * Announcement, search, and header UX.
 */

defined( 'ABSPATH' ) || exit;

function bl_header_cart_icon_markup() {
	$count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	$badge = $count > 0 ? '<span class="bl-cart-count" aria-hidden="true">' . esc_html( (string) $count ) . '</span>' : '';
	return '<span class="cart-icon image-icon" data-cart-count="' . esc_attr( (string) $count ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 4h2l2.4 11.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6"></path><circle cx="9.5" cy="20" r="1.5"></circle><circle cx="18.5" cy="20" r="1.5"></circle></svg>' . $badge . '</span>';
}

add_filter(
	'flatsome_icon',
	function ( $icon_html, $name ) {
		if ( 'icon-user' === $name ) {
			return '<i class="icon-user bl-outline-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="7.5" r="3.5"></circle><path d="M5 21v-1a7 7 0 0 1 14 0v1"></path></svg></i>';
		}
		if ( 0 === strpos( $name, 'icon-shopping-' ) ) {
			return bl_header_cart_icon_markup();
		}
		return $icon_html;
	},
	20,
	2
);

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$fragments['.header .cart-icon'] = bl_header_cart_icon_markup();
		return $fragments;
	},
	20
);

add_shortcode(
	'bl_announcement',
	function () {
		$announcements = array(
			'Free shipping on orders $50+',
			'Refill your routine with less waste',
			'Simple swaps for a cleaner home',
		);
		$items = '';
		foreach ( $announcements as $index => $announcement ) {
			$shop_link = 0 === $index ? ' <a href="' . esc_url( home_url( '/shop/' ) ) . '">Shop now</a>' : '';
			$items .= '<span class="bl-announcement-slide' . ( 0 === $index ? ' is-active' : '' ) . '">' . esc_html( $announcement ) . $shop_link . '</span>';
		}
		return '<div class="bl-announcement" aria-label="Store announcements"><button class="bl-announcement-prev" type="button" aria-label="Previous announcement">‹</button><div class="bl-announcement-slides">' . $items . '</div><button class="bl-announcement-next" type="button" aria-label="Next announcement">›</button></div>';
	}
);

add_shortcode(
	'bl_search',
	function () {
		$form = '<form role="search" method="get" class="bl-search-form" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="bl-search-input">Search products</label><input id="bl-search-input" type="search" name="s" placeholder="Search products…" /><input type="hidden" name="post_type" value="product" /><button type="submit" aria-label="Search">→</button></form>';
		return '<div class="bl-search-wrap"><button class="bl-search-toggle" type="button" aria-expanded="false" aria-label="Open search"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.8" cy="10.8" r="7.3"></circle><path d="m16.2 16.2 5.3 5.3"></path></svg></button><div class="bl-search-panel" hidden><div class="bl-search-panel-inner"><div class="bl-search-heading"><strong>What are you searching for?</strong><button type="button" class="bl-search-close" aria-label="Close search">×</button></div>' . $form . '<h3>Popular Products</h3><div class="bl-search-popular">' . do_shortcode( '[products limit="4" columns="4" orderby="popularity"]' ) . '</div></div></div></div>';
	}
);

add_shortcode(
	'bl_mega_menu',
	function () {
		return do_shortcode( '[block id="mega-shop"]' );
	}
);

add_shortcode(
	'bl_right_nav',
	function () {
		$search = do_shortcode( '[bl_search]' );
		return '<div class="bl-right-nav"><div class="bl-right-dropdown"><a href="' . esc_url( home_url( '/about-us/' ) ) . '">About Us<svg class="bl-chevron" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M3 4.5 6 7.5 9 4.5"></path></svg></a><ul><li><a href="' . esc_url( home_url( '/about-us/' ) ) . '">Who We Are</a></li><li><a href="' . esc_url( home_url( '/certifications/' ) ) . '">Certifications</a></li></ul></div><div class="bl-right-dropdown"><a href="' . esc_url( home_url( '/our-mission/' ) ) . '">Our Mission<svg class="bl-chevron" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M3 4.5 6 7.5 9 4.5"></path></svg></a><ul><li><a href="' . esc_url( home_url( '/our-mission/' ) ) . '">Our Approach</a></li><li><a href="' . esc_url( home_url( '/faq/' ) ) . '">FAQs</a></li></ul></div>' . $search . '</div>';
	}
);

add_shortcode(
	'bl_subnav',
	function () {
		$links = array(
			'Starter Kits' => '/product-category/starter-kits/',
			'Refills'      => '/product-category/refills/',
			'Bestsellers'  => '/product-category/bestsellers/',
			'|Dish'        => '/product-category/dish/',
			'Laundry'      => '/product-category/laundry/',
			'Toilet'       => '/product-category/toilet/',
			'Hand Soap'    => '/product-category/hand-soap/',
			'Spray Cleaners' => '/product-category/spray-cleaners/',
			'Extras'       => '/product-category/extras/',
		);
		$html = '<nav class="bl-subnav" aria-label="Shop categories">';
		foreach ( $links as $label => $url ) {
			$separator = '|' === substr( $label, 0, 1 );
			$text = $separator ? substr( $label, 1 ) : $label;
			$active = is_product_category() && ( stripos( (string) get_queried_object()->slug, sanitize_title( $text ) ) !== false ) ? ' is-active' : '';
			$html .= ( $separator ? '<span class="bl-subnav-divider" aria-hidden="true"></span>' : '' ) . '<a class="' . esc_attr( trim( $active ) ) . '" href="' . esc_url( home_url( $url ) ) . '">' . esc_html( $text ) . '</a>';
		}
		return $html . '</nav>';
	}
);

add_filter(
	'wp_nav_menu_objects',
	function ( $items, $args ) {
		foreach ( $items as $item ) {
			if ( 'Shop' === $item->title ) {
				$item->classes[] = 'bl-shop-menu-item';
			}
		}
		return $items;
	},
	20,
	2
);

add_filter(
	'walker_nav_menu_start_el',
	function ( $item_output, $item, $depth, $args ) {
		if ( 0 === (int) $depth && 'Shop' === $item->title && isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
			$item_output .= '<div class="bl-mega-dropdown">' . do_shortcode( '[block id="mega-shop"]' ) . '</div>';
		}
		return $item_output;
	},
	20,
	4
);
