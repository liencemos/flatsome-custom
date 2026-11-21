<?php
/**
 * Custom product layout data, plan pricing, tabs, and review components.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woocommerce_add_cart_item_data',
	function ( $cart_item_data, $product_id, $variation_id ) {
		if ( empty( $_POST['bl_plan'] ) || 'autoship' !== sanitize_key( wp_unslash( $_POST['bl_plan'] ) ) ) {
			return $cart_item_data;
		}
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $product ) {
			return $cart_item_data;
		}
		$cart_item_data['bl_plan']         = 'autoship';
		$cart_item_data['bl_interval']     = isset( $_POST['bl_interval'] ) ? absint( wp_unslash( $_POST['bl_interval'] ) ) : 3;
		$cart_item_data['bl_base_price']   = (float) $product->get_price();
		$cart_item_data['bl_plan_unique']  = md5( wp_rand() . microtime( true ) );
		return $cart_item_data;
	},
	10,
	3
);

add_action(
	'woocommerce_before_calculate_totals',
	function ( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['bl_plan'], $cart_item['bl_base_price'] ) && 'autoship' === $cart_item['bl_plan'] ) {
				$cart_item['data']->set_price( (float) $cart_item['bl_base_price'] * 0.8 );
			}
		}
	},
	20
);

add_filter(
	'woocommerce_get_item_data',
	function ( $item_data, $cart_item ) {
		if ( isset( $cart_item['bl_plan'] ) && 'autoship' === $cart_item['bl_plan'] ) {
			$item_data[] = array( 'key' => 'Plan', 'value' => 'Autoship — every ' . absint( $cart_item['bl_interval'] ) . ' months (20% off)' );
		}
		return $item_data;
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $item, $cart_item_key, $values ) {
		if ( isset( $values['bl_plan'] ) && 'autoship' === $values['bl_plan'] ) {
			$item->add_meta_data( 'Plan', 'Autoship', true );
			$item->add_meta_data( 'Refill interval', absint( $values['bl_interval'] ) . ' months', true );
		}
	},
	10,
	3
);

add_shortcode(
	'bl_product_included',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $product->get_meta( '_bl_included' ) ) ) );
		if ( empty( $lines ) ) {
			return '';
		}
		$output = '<div class="bl-included"><h3>What’s Included In Your First Order</h3><ul>';
		foreach ( $lines as $line ) {
			$output .= '<li>' . esc_html( $line ) . '</li>';
		}
		return $output . '</ul></div>';
	}
);

add_shortcode(
	'bl_product_eyebrow',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		$label = 'Everyday Essentials';
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! in_array( $term->slug, array( 'bestsellers', 'holiday-shop' ), true ) ) {
					$label = $term->name;
					break;
				}
			}
		}
		return '<p class="bl-pdp-eyebrow">' . esc_html( $label ) . '</p>';
	}
);

add_shortcode(
	'bl_product_badges',
	function () {
		global $product;
		return bl_product_badges_markup( $product );
	}
);

add_shortcode(
	'bl_plan_selector',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			return '';
		}
		$price = (float) $product->get_price();
		$plan_price = wc_price( $price * 0.8 );
		$regular    = wc_price( $price );
		$intervals  = '<select name="bl_interval" aria-label="Autoship delivery interval"><option value="1">Every 1 month</option><option value="2">Every 2 months</option><option value="3" selected>Every 3 months</option></select>';
		return '<div class="bl-plan-selector"><h3>Your plan</h3><label class="bl-plan-card is-selected"><input type="radio" name="bl_plan" value="autoship" checked><span class="bl-plan-content"><strong>Save 20% with Autoship</strong><span class="bl-plan-line"><s>' . wp_kses_post( $regular ) . '</s><b class="bl-plan-price">' . wp_kses_post( $plan_price ) . '</b></span><small class="bl-plan-interval">Recurring refills (' . $intervals . ')</small></span><em>5% BACK IN REWARDS</em></label><label class="bl-plan-card"><input type="radio" name="bl_plan" value="one-time"><span class="bl-plan-content"><strong>Buy One Time</strong><span class="bl-plan-line"><b class="bl-plan-price">' . wp_kses_post( $regular ) . '</b></span></span></label><div class="bl-todays-total is-autoship"><strong>Today’s Total</strong><span class="bl-plan-regular">' . wp_kses_post( $regular ) . '</span><span class="bl-plan-total">' . wp_kses_post( $plan_price ) . '</span></div></div>';
	}
);

add_shortcode(
	'bl_product_addon',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$ids = $product->get_cross_sell_ids();
		$products = $ids ? wc_get_products( array( 'include' => $ids, 'limit' => 4, 'status' => 'publish' ) ) : wc_get_products( array( 'limit' => 4, 'status' => 'publish', 'orderby' => 'popularity', 'order' => 'DESC', 'exclude' => array( $product->get_id() ) ) );
		if ( empty( $products ) ) {
			return '';
		}
		$html = '<div class="bl-product-addon"><h3>Popular Add-On</h3><div class="bl-product-addon-track">';
		foreach ( $products as $index => $addon ) {
			$button = $addon->is_type( 'simple' ) && $addon->is_purchasable()
				? '<a href="' . esc_url( $addon->add_to_cart_url() ) . '" data-quantity="1" class="button add_to_cart_button ajax_add_to_cart product_type_simple" data-product_id="' . esc_attr( $addon->get_id() ) . '" rel="nofollow">ADD | ' . wp_kses_post( $addon->get_price_html() ) . '</a>'
				: '<a class="button" href="' . esc_url( get_permalink( $addon->get_id() ) ) . '">View</a>';
			$html .= '<article data-addon-slide="' . esc_attr( (string) $index ) . '">' . $addon->get_image( 'woocommerce_thumbnail' ) . '<span class="bl-addon-name"><span>' . esc_html( $addon->get_name() ) . '</span><small>Refill-ready everyday essential</small></span>' . $button . '</article>';
		}
		$html .= '</div><div class="bl-addon-controls"><button type="button" data-addon-direction="-1" aria-label="Previous add-on">‹</button><div class="bl-addon-dots">';
		foreach ( $products as $index => $addon ) {
			$html .= '<button type="button" data-addon-index="' . esc_attr( (string) $index ) . '" aria-label="Add-on ' . esc_attr( (string) ( $index + 1 ) ) . '"' . ( 0 === $index ? ' class="is-active"' : '' ) . '></button>';
		}
		return $html . '</div><button type="button" data-addon-direction="1" aria-label="Next add-on">›</button></div></div>';
	}
);

add_shortcode(
	'bl_product_rewards',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		return '<div class="bl-rewards-box"><strong>Rewards</strong><span>Earn ' . esc_html( (string) ( (int) floor( (float) $product->get_price() ) * 5 ) ) . ' points with this purchase.</span></div><p class="bl-shipping-note">Free 100-Day Returns · Free Shipping over $50</p>';
	}
);

add_shortcode(
	'bl_product_related',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$ids = wc_get_related_products( $product->get_id(), 4 );
		if ( empty( $ids ) ) {
			$popular = wc_get_products( array( 'limit' => 4, 'status' => 'publish', 'orderby' => 'popularity', 'order' => 'DESC', 'exclude' => array( $product->get_id() ) ) );
			$ids = array_map(
				function ( $related_product ) {
					return $related_product->get_id();
				},
				$popular
			);
		}
		return $ids ? do_shortcode( '[products ids="' . esc_attr( implode( ',', array_map( 'absint', $ids ) ) ) . '" limit="4" columns="4"]' ) : '';
	}
);

add_shortcode(
	'bl_reviews_summary',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$counts = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		$reviews = get_comments( array( 'post_id' => $product->get_id(), 'status' => 'approve', 'type' => 'review', 'number' => 0 ) );
		foreach ( $reviews as $review ) {
			$rating = absint( get_comment_meta( $review->comment_ID, 'rating', true ) );
			if ( isset( $counts[ $rating ] ) ) {
				++$counts[ $rating ];
			}
		}
		$total = array_sum( $counts );
		$average = $total ? $product->get_average_rating() : 0;
		$html = '<div class="bl-review-summary"><div class="bl-review-score"><strong>' . esc_html( number_format_i18n( (float) $average, 1 ) ) . '</strong><span>★★★★★</span><small>Based on ' . esc_html( number_format_i18n( $total ) ) . ' reviews</small><b>94% would recommend</b></div><div class="bl-review-summary-details"><h3>Reviews Summary</h3><p>Customers appreciate an easy-to-use routine and thoughtful refill formats.</p><div class="bl-rating-bars">';
		foreach ( $counts as $rating => $count ) {
			$percent = $total ? ( $count / $total ) * 100 : 0;
			$html .= '<div><span>' . esc_html( (string) $rating ) . ' ★</span><span class="bl-rating-track"><i style="width:' . esc_attr( $percent ) . '%"></i></span><small>' . esc_html( (string) $count ) . '</small></div>';
		}
		return $html . '</div></div></div>';
	}
);

add_shortcode(
	'bl_reviews_list',
	function () {
		global $product, $post;
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$reviews = get_comments(
			array(
				'post_id' => $product->get_id(),
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => 6,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);
		$html = '<div class="bl-review-controls"><div class="bl-review-chips"><button class="is-active" type="button">All Reviews</button><button type="button">With Photos</button><button type="button">Autoship</button></div><div><button class="bl-review-filter" type="button">Filters</button><button class="bl-write-review" type="button">Write a Review</button></div></div><div class="bl-review-list">';
		foreach ( $reviews as $review ) {
			$rating = absint( get_comment_meta( $review->comment_ID, 'rating', true ) );
			$title = get_comment_meta( $review->comment_ID, '_bl_review_title', true );
			$html .= '<article class="bl-review-card"><div class="bl-review-author"><strong>' . esc_html( $review->comment_author ) . '</strong><span>Verified Buyer</span><small>Reviewing ' . esc_html( $product->get_name() ) . '</small></div><div><p class="bl-review-stars">' . esc_html( str_repeat( '★', min( 5, $rating ) ) ) . '</p><h3>' . esc_html( $title ? $title : 'A simple everyday favorite' ) . '</h3><p>' . esc_html( $review->comment_content ) . '</p><small class="bl-review-date">' . esc_html( get_comment_date( 'F j, Y', $review ) ) . '</small></div></article>';
		}
		$html .= '</div>';
		if ( comments_open( $product->get_id() ) ) {
			$post = get_post( $product->get_id() );
			setup_postdata( $post );
			ob_start();
			comment_form( array( 'title_reply' => 'Write a Review', 'label_submit' => 'Submit Review' ), $product->get_id() );
			$html .= '<div class="bl-review-form">' . ob_get_clean() . '</div>';
			wp_reset_postdata();
		}
		return $html;
	}
);

add_filter(
	'woocommerce_product_tabs',
	function ( $tabs ) {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return $tabs;
		}
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['title'] = 'Overview';
		}
		unset( $tabs['additional_information'], $tabs['reviews'] );
		$ingredients = $product->get_meta( '_bl_ingredients' );
		$sustainability = $product->get_meta( '_bl_sustainability' );
		if ( $ingredients ) {
			$tabs['ingredients'] = array(
				'title'    => 'Ingredients',
				'priority' => 25,
				'callback' => function () use ( $ingredients ) {
					echo wp_kses_post( wpautop( $ingredients ) );
				},
			);
		}
		if ( $sustainability ) {
			$tabs['sustainability'] = array(
				'title'    => 'Sustainability',
				'priority' => 30,
				'callback' => function () use ( $sustainability ) {
					echo wp_kses_post( wpautop( $sustainability ) );
				},
			);
		}
		return $tabs;
	}
);

add_action(
	'wp_footer',
	function () {
		if ( is_product() ) {
			echo '<div class="bl-mobile-sticky-cart"><button type="button" class="button" data-bl-scroll-add>Add to Cart</button></div>';
		}
	}
);
