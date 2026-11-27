<?php
/**
 * Product cards: badge, swatches (Flatsome), title, price line, meta and Quick View.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'flatsome_product_box_actions', 'flatsome_lightbox_button', 50 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		add_action( 'flatsome_woocommerce_shop_loop_images', 'cf_loop_badge', 30 );
		add_action( 'woocommerce_after_shop_loop_item_title', 'cf_loop_details', 10 );
	}
);

// The card shows a text savings label instead of the sale bubble.
add_filter( 'woocommerce_sale_flash', '__return_empty_string', 20 );
add_filter( 'flatsome_product_labels', '__return_empty_string', 20 );

add_action(
	'wp',
	function () {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			remove_action( 'flatsome_woocommerce_shop_loop_images', 'woocommerce_template_loop_product_thumbnail', 10 );
			add_action( 'flatsome_woocommerce_shop_loop_images', 'cf_archive_thumbnail', 10 );
		}
	}
);

function cf_archive_thumbnail() {
	if ( ! in_the_loop() || wc_get_loop_prop( 'is_shortcode' ) ) {
		woocommerce_template_loop_product_thumbnail();
		return;
	}

	$columns = max( 1, (int) wc_get_loop_prop( 'columns', 3 ) );
	$tablet  = max( 1, (int) get_theme_mod( 'category_row_count_tablet', 3 ) );
	$mobile  = max( 1, (int) get_theme_mod( 'category_row_count_mobile', 2 ) );
	$width   = (float) get_theme_mod( 'site_width', 1080 );
	$width   = $width > 0 ? $width : 1080;
	$index   = (int) wc_get_loop_prop( 'loop', 1 );
	$sidebar = ! in_array( get_theme_mod( 'category_sidebar', 'left-sidebar' ), array( 'none', 'off-canvas' ), true );
	$share   = ( $sidebar ? 0.75 : 1 ) / $columns;
	$gutter  = $sidebar ? 30 : 20;
	$attrs   = array(
		'sizes' => sprintf(
			'(max-width: 549px) calc(%svw - 20px), (max-width: 849px) calc(%svw - 20px), (max-width: %dpx) calc(%svw - %dpx), %dpx',
			100 / $mobile,
			100 / $tablet,
			(int) $width,
			$share * 100,
			$gutter,
			max( 1, (int) ( $width * $share - $gutter ) )
		),
	);
	if ( $index <= max( $columns, $tablet, $mobile ) ) {
		$attrs['loading'] = 'eager';
		$attrs['class']   = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail ux-skip-lazy skip-lazy';
	}
	if ( 1 === $index ) {
		$attrs['fetchpriority'] = 'high';
	}
	echo woocommerce_get_product_thumbnail( 'woocommerce_thumbnail', $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function cf_badge_markup( $product ) {
	$badge = trim( (string) $product->get_meta( '_cf_badge' ) );
	if ( '' === $badge ) {
		return '';
	}
	return '<span class="cf-badge"><svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path fill="currentColor" d="M6 0l1.5 4.5L12 6 7.5 7.5 6 12 4.5 7.5 0 6l4.5-1.5z"/></svg>' . esc_html( $badge ) . '</span>';
}

function cf_loop_badge() {
	global $product;
	if ( $product instanceof WC_Product ) {
		echo cf_badge_markup( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

function cf_loop_details() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	echo '<div class="cf-card-price">' . wp_kses_post( cf_price_line( $product ) ) . '</div>';
	$meta = cf_product_meta_line( $product );
	if ( $meta ) {
		echo '<div class="cf-card-meta">' . esc_html( $meta ) . '</div>';
	}
	if ( get_theme_mod( 'disable_quick_view', 0 ) ) {
		return;
	}
	// Same markup and script Flatsome's own quick view button uses.
	wp_enqueue_script( 'wc-add-to-cart-variation' );
	printf(
		'<a href="#quick-view" class="quick-view cf-quick-view" role="button" aria-haspopup="dialog" aria-expanded="false" data-prod="%1$d" aria-label="%2$s">%3$s</a>',
		(int) $product->get_id(),
		/* translators: %s: product name. */
		esc_attr( sprintf( __( 'Quick view %s', 'comfa' ), $product->get_name() ) ),
		esc_html__( 'Quick View', 'comfa' )
	);
}
