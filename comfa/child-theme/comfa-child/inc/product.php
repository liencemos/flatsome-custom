<?php
/**
 * Product page: price line, pack/colour/size pickers, bag button, info accordion and reviews.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		add_action( 'woocommerce_single_product_summary', 'cf_product_promo', 12 );
		add_action( 'woocommerce_single_product_summary', 'cf_product_info_card', 35 );
		add_action( 'woocommerce_after_single_product_summary', 'cf_product_story', 8 );
		add_action( 'woocommerce_after_single_product_summary', 'cf_product_reviews', 10 );
		add_action( 'woocommerce_before_single_product_summary', 'cf_product_gallery_badge', 15 );
		add_action( 'wp_footer', 'cf_size_guide_lightbox', 5 );
	}
);

// Quick view and the product page share the same price line.
add_filter(
	'woocommerce_get_price_html',
	function ( $html, $product ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $html;
		}
		if ( $product->is_type( 'variable' ) ) {
			return '<span class="cf-price">' . cf_price_line( $product ) . '</span>';
		}
		return $html;
	},
	20,
	2
);

add_filter(
	'woocommerce_available_variation',
	function ( $data, $product, $variation ) {
		$attributes = $variation->get_attributes();
		$count      = isset( $attributes['pa_pack'] ) && $attributes['pa_pack'] ? cf_pack_count( get_term_by( 'slug', $attributes['pa_pack'], 'pa_pack' ) ) : 1;
		$data['cf_pack_count'] = $count;
		$data['price_html']    = '<span class="price"><span class="cf-price">' . cf_variation_price_line( $variation, $count ) . '</span></span>';
		return $data;
	},
	20,
	3
);

function cf_variation_price_line( $variation, $count ) {
	$html = '<span class="cf-price__now">' . wc_price( wc_get_price_to_display( $variation ) ) . '</span>';
	if ( $variation->is_on_sale() ) {
		$html .= ' <del class="cf-price__was">' . wc_price( wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) ) ) . '</del>';
		$html .= ' <span class="cf-price__save">' . esc_html( cf_savings_label( $variation, $count ) ) . '</span>';
	}
	return $html;
}

// Quick view gets the giving line under the button as well.
add_action( 'flatsome_single_product_lightbox_summary', 'cf_product_give_line', 35 );

add_filter(
	'woocommerce_product_single_add_to_cart_text',
	function () {
		return __( 'Add to Bag', 'comfa' );
	}
);

function cf_product_promo() {
	$code = cf_setting( 'welcome_code' );
	if ( ! $code ) {
		return;
	}
	echo '<p class="cf-product-promo"><strong>' . esc_html( sprintf( /* translators: %d: discount percentage. */ __( 'Extra %d%% off your first order', 'comfa' ), (int) cf_setting( 'welcome_percent' ) ) ) . '</strong><span>' . esc_html( sprintf( /* translators: %s: coupon code. */ __( 'Enter code %s in your bag.', 'comfa' ), $code ) ) . '</span></p>';
}

function cf_product_gallery_badge() {
	global $product;
	$badge = $product instanceof WC_Product ? cf_badge_markup( $product ) : '';
	if ( $badge ) {
		echo '<div class="cf-gallery-badge">' . $badge . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

// Size label gets the selected value and a Size Guide link; other labels get the selected value.
add_action(
	'woocommerce_before_variations_form',
	function () {
		global $product;
		add_filter( 'woocommerce_attribute_label', 'cf_variation_label', 20, 2 );
		// Fit notes for the two-line size buttons (product meta: slug => note).
		$notes = $product instanceof WC_Product ? $product->get_meta( '_cf_size_notes' ) : array();
		if ( is_array( $notes ) && $notes ) {
			echo '<script type="application/json" class="cf-size-notes">' . wp_json_encode( $notes ) . '</script>';
		}
	}
);
add_action(
	'woocommerce_after_variations_form',
	function () {
		remove_filter( 'woocommerce_attribute_label', 'cf_variation_label', 20 );
	}
);

function cf_variation_label( $label, $name ) {
	$html = '<span class="cf-attr-name">' . esc_html( $label ) . ':</span> <span class="cf-attr-value" data-cf-attr="' . esc_attr( sanitize_title( $name ) ) . '"></span>';
	if ( 'pa_size' === $name && cf_size_guide_page() ) {
		$html .= '<a class="cf-size-guide-link" href="#cf-size-guide">' . esc_html__( 'Size Guide', 'comfa' ) . '</a>';
	}
	return $html;
}

/**
 * The Size Guide page, when it can be shown to every visitor.
 */
function cf_size_guide_page() {
	$page = get_page_by_path( 'size-guide' );
	// Password-protected pages are published too; never inline those.
	return $page && 'publish' === $page->post_status && '' === $page->post_password ? $page : null;
}

function cf_size_guide_lightbox() {
	$page = cf_size_guide_page();
	if ( ! $page ) {
		return;
	}
	echo do_shortcode( '[lightbox id="cf-size-guide" width="720px" padding="32px"]' . $page->post_content . '[/lightbox]' );
}

function cf_product_give_line() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$variation = cf_default_variation( $product );
	$count     = 1;
	if ( $variation ) {
		$attributes = $variation->get_attributes();
		$count      = ! empty( $attributes['pa_pack'] ) ? cf_pack_count( get_term_by( 'slug', $attributes['pa_pack'], 'pa_pack' ) ) : 1;
	}
	echo '<p class="cf-give-line" data-cf-give>' . esc_html( sprintf( /* translators: 1: items bought, 2: items given. */ __( '%1$d Bought = %2$d Given', 'comfa' ), $count, $count ) ) . '</p>';
}

/**
 * Giving line under the button plus the shipping / reviews / highlight card.
 */
function cf_product_info_card() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	cf_product_give_line();

	$threshold = (float) cf_setting( 'free_shipping_threshold' );
	$rating    = (float) $product->get_average_rating();
	$reviews   = (int) $product->get_review_count();
	$weight    = wc_get_product_terms( $product->get_id(), 'pa_weight', array( 'fields' => 'names' ) );
	$highlight = (string) $product->get_meta( '_cf_highlight' );
	$help_url  = get_permalink( get_page_by_path( 'shipping-returns' ) );

	$rows  = '[accordion-item title="' . esc_attr( sprintf( /* translators: %s: order value. */ __( 'Free Shipping Over %s + Free Returns', 'comfa' ), wp_strip_all_tags( wc_price( $threshold, array( 'decimals' => 0 ) ) ) ) ) . '"]';
	$rows .= '<p>' . esc_html__( 'Orders ship within one business day. Not quite right? Send it back within 60 days for a refund or exchange.', 'comfa' ) . '</p>';
	if ( $help_url ) {
		$rows .= '<p><a href="' . esc_url( $help_url ) . '">' . esc_html__( 'Shipping & Return Details', 'comfa' ) . '</a></p>';
	}
	$rows .= '[/accordion-item]';
	if ( $highlight || $weight ) {
		$rows .= '[accordion-item title="' . esc_attr__( 'Product Highlight', 'comfa' ) . '"]';
		$rows .= '<div class="cf-highlight"><span class="cf-highlight__icon" aria-hidden="true"></span><p><strong>' . esc_html( $weight && ! is_wp_error( $weight ) ? $weight[0] : __( 'Made to last', 'comfa' ) ) . '</strong>' . esc_html( $highlight ) . '</p></div>';
		$rows .= '[/accordion-item]';
	}
	if ( $product->get_short_description() ) {
		$rows .= '[accordion-item title="' . esc_attr__( 'Details & Care', 'comfa' ) . '"]' . wp_kses_post( wpautop( $product->get_short_description() ) ) . wp_kses_post( $product->get_description() ) . '[/accordion-item]';
	}
	echo '<div class="cf-info-card">';
	if ( $reviews ) {
		echo '<a class="cf-info-card__reviews" href="#reviews"><span class="cf-star" aria-hidden="true">★</span><strong>' . esc_html( number_format_i18n( $rating, 1 ) ) . '</strong><span>' . esc_html( sprintf( /* translators: %s: review count. */ _n( '%s Review', '%s Reviews', $reviews, 'comfa' ), number_format_i18n( $reviews ) ) ) . '</span></a>';
	}
	echo do_shortcode( '[accordion]' . $rows . '[/accordion]' );
	echo '</div>';
}

/**
 * Editable story band: UX Block "pdp-story" (Flatsome > UX Blocks).
 */
function cf_product_story() {
	$block = get_page_by_path( 'pdp-story', OBJECT, 'blocks' );
	if ( $block && 'publish' === $block->post_status ) {
		echo '<div class="cf-product-story">' . do_shortcode( '[block id="' . (int) $block->ID . '"]' ) . '</div>';
	}
}

function cf_product_reviews() {
	global $product;
	if ( ! $product instanceof WC_Product || ! comments_open() ) {
		return;
	}
	$rating  = (float) $product->get_average_rating();
	$reviews = (int) $product->get_review_count();
	echo '<section class="cf-reviews" id="cf-reviews">';
	echo '<header class="cf-reviews__summary"><h2>' . esc_html__( 'Reviews', 'comfa' ) . '</h2>';
	if ( $reviews ) {
		echo '<div class="cf-reviews__score"><strong>' . esc_html( number_format_i18n( $rating, 1 ) ) . '</strong><div>' . wc_get_rating_html( $rating, $reviews ) . '<span>' . esc_html( sprintf( /* translators: %s: review count. */ _n( '%s review', '%s reviews', $reviews, 'comfa' ), number_format_i18n( $reviews ) ) ) . '</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</header>';
	comments_template();
	echo '</section>';
}
