<?php

function vertuo_apply_storefront_design() {
	if ( get_theme_mod( 'vertuo_storefront_design' ) ) {
		return;
	}
	$font = 'DM Sans';
	$mods = array(
		'header_elements_left'        => array( 'search-form' ),
		'header_elements_right'       => array( 'account', 'cart' ),
		'header_elements_bottom_left' => array( 'vertuo-nav' ),
		'header_mobile_elements_right' => array( 'account', 'cart' ),
		'header_mobile_elements_bottom' => array( 'search-form' ),
		'mobile_sidebar'              => array( 'vertuo-nav', 'account' ),
		'topbar_elements_left'        => array(),
		'topbar_elements_center'      => array( 'vertuo-announcement' ),
		'topbar_elements_right'       => array(),
		'header_mobile_elements_top'  => array( 'vertuo-announcement' ),
		'logo_position'               => 'left',
		'topbar_show'                 => true,
		'topbar_bg'                   => '#ffc62c',
		'header_bg'                   => '#ebe8e3',
		'nav_position_bg'             => '#ebe8e3',
		'header_height'               => 82,
		'header_mobile_height'        => 56,
		'header_account_title'        => false,
		'header_cart_total'           => false,
		'header_cart_title'           => false,
		'cart_icon'                   => 'cart',
		'color_primary'               => '#3e2d30',
		'color_secondary'             => '#ffc62c',
		'type_headings'               => array( 'font-family' => $font, 'variant' => '700' ),
		'type_texts'                  => array( 'font-family' => $font, 'variant' => 'regular' ),
		'type_nav'                    => array( 'font-family' => $font, 'variant' => '700' ),
		'product_layout'              => 'no-sidebar',
		'product_image_width'         => 7,
		'product_share'               => false,
		'product_next_prev_nav'       => false,
		'footer_1'                    => false,
		'footer_2'                    => false,
		'footer_bottom_color'         => '#f5f2ee',
		'footer_bottom_text'          => 'light',
		'vertuo_storefront_design'    => 1,
	);
	foreach ( $mods as $name => $value ) {
		set_theme_mod( $name, $value );
	}
}

function vertuo_storefront_catalog_url() {
	$page = get_page_by_path( 'vertuo-coffee-pods' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

add_filter( 'theme_mod_logo_link', function ( $link ) {
	return $link ?: vertuo_storefront_catalog_url();
} );

function vertuo_storefront_collection_url( $collection = '' ) {
	$args = array( 'vertuo_shop' => '1' );
	if ( $collection ) {
		$args['vertuo_collection'] = $collection;
	}
	return add_query_arg( $args, vertuo_storefront_catalog_url() );
}

function vertuo_storefront_is_shop() {
	return is_page( 'vertuo-coffee-pods' ) && '1' === vertuo_catalog_input( 'vertuo_shop' );
}

add_filter( 'body_class', function ( $classes ) {
	if ( vertuo_storefront_is_shop() ) {
		$classes[] = 'vertuo-shop-view';
	}
	return $classes;
} );

function vertuo_storefront_links() {
	return array(
		'Coffee Pods'     => vertuo_storefront_collection_url(),
		'Espresso'        => vertuo_storefront_collection_url( 'ristretto-espresso-0-85-fl-oz-1-35-fl-oz' ),
		'Double Espresso' => vertuo_storefront_collection_url( 'double-espresso-2-7-fl-oz' ),
		'Coffee'          => vertuo_storefront_collection_url( 'coffee-7-77-fl-oz' ),
		'Coffee Packs'    => vertuo_storefront_collection_url( 'specialty-coffee-packs' ),
		'Fall Favorites'   => vertuo_storefront_collection_url( 'fall-favorites' ),
	);
}

add_action( 'flatsome_header_elements', function ( $element ) {
	if ( 'vertuo-nav' === $element ) {
		foreach ( vertuo_storefront_links() as $label => $url ) {
			printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
		}
	} elseif ( 'vertuo-announcement' === $element ) {
		printf( '<li class="vertuo-announcement">Find your next favorite cup. <a href="%s">Shop coffee pods</a></li>', esc_url( vertuo_storefront_collection_url() ) );
	}
} );

add_action( 'flatsome_after_header', function () {
	if ( ( ! is_page( 'vertuo-coffee-pods' ) && ! is_singular( 'product' ) ) || vertuo_storefront_is_shop() ) {
		return;
	}
	echo '<nav class="vertuo-collection-strip" aria-label="Featured collections">';
	foreach ( array_slice( vertuo_storefront_links(), 2 ) as $label => $url ) {
		printf( '<a href="%s">%s <span>Explore collection</span></a>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</nav>';
} );

add_action( 'woocommerce_before_single_product_summary', function () {
	if ( 'no-sidebar' === get_theme_mod( 'product_layout' ) ) {
		the_title( '<h1 class="vertuo-mobile-product-title">', '</h1>' );
	}
}, 15 );

add_shortcode( 'vertuo_hero_products', function () {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	ob_start();
	?>
	<div class="vertuo-featured">
	<?php foreach ( array( '169604', '7070.10', '7076.10' ) as $sku ) : ?>
		<?php $product = wc_get_product( wc_get_product_id_by_sku( 'VERTUO-' . $sku ) ); ?>
		<?php if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) { continue; } ?>
		<a class="vertuo-featured-card" href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<div><span class="vertuo-featured-label">Shop Vertuo</span><strong><?php echo esc_html( $product->get_name() ); ?></strong><span class="vertuo-featured-cta">View product</span></div>
			<?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>
		</a>
	<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
} );

add_action( 'flatsome_footer', function () {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	?>
	<div class="vertuo-footer">
		<div class="vertuo-footer-band"><div class="container"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> <a href="<?php echo esc_url( vertuo_storefront_collection_url() ); ?>">Explore the coffee collection</a></div></div>
		<div class="container vertuo-footer-links">
			<nav aria-label="Shop"><strong>Shop</strong>
				<?php foreach ( vertuo_storefront_links() as $label => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<nav aria-label="Orders"><strong>Orders</strong>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">My Account</a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Shopping Cart</a>
			</nav>
			<div><strong>About Vertuo</strong><p>Explore coffee pods, coffee packs, and accessories for the way you brew.</p></div>
		</div>
	</div>
	<?php
}, 9 );
