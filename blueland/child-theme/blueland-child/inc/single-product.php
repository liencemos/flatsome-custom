<?php
/**
 * Custom product layout data, plan pricing, tabs, and review components.
 */

defined( 'ABSPATH' ) || exit;

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
		return bl_product_badges_markup( $product ) . ( function_exists( 'bl_stock_status_badges_markup' ) ? bl_stock_status_badges_markup( $product ) : '' );
	}
);

add_shortcode(
	'bl_product_price_summary',
	function () {
		global $product;
		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			return '';
		}
		return '<div class="bl-todays-total"><strong>Today’s Total</strong><span class="bl-today-price">' . wp_kses_post( wc_price( (float) $product->get_price() ) ) . '</span></div>';
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
				'number'  => 0,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);
		$html = '<div class="bl-review-controls"><div class="bl-review-chips"><button class="is-active" data-review-rating="all" type="button">All Reviews</button><button data-review-rating="5" type="button">5 Stars</button><button data-review-rating="4" type="button">4 Stars</button><button data-review-rating="3" type="button">3 Stars &amp; Below</button><button data-review-rating="photos" type="button">With Photos</button></div><div><button class="bl-write-review" type="button">Write a Review</button></div></div><div class="bl-review-list" data-visible-count="6">';
		foreach ( $reviews as $index => $review ) {
			$rating = absint( get_comment_meta( $review->comment_ID, 'rating', true ) );
			$title = get_comment_meta( $review->comment_ID, '_bl_review_title', true );
			$hidden = $index >= 6 ? ' hidden' : '';
			$filled_stars = min( 5, max( 0, $rating ) );
			$stars = '<span class="bl-review-stars-filled">' . str_repeat( '★', $filled_stars ) . '</span><span class="bl-review-stars-empty">' . str_repeat( '★', 5 - $filled_stars ) . '</span>';
			$photos = function_exists( 'bl_review_photos_markup' ) ? bl_review_photos_markup( $review->comment_ID ) : '';
			$html .= '<article class="bl-review-card" data-rating="' . esc_attr( (string) $rating ) . '"' . $hidden . '><div class="bl-review-author"><strong>' . esc_html( $review->comment_author ) . '</strong><span>Verified Buyer</span><small>Reviewing ' . esc_html( $product->get_name() ) . '</small></div><div><p class="bl-review-stars" aria-label="' . esc_attr( $filled_stars . ' out of 5 stars' ) . '">' . $stars . '</p><h3>' . esc_html( $title ? $title : 'A simple everyday favorite' ) . '</h3><p>' . esc_html( $review->comment_content ) . '</p>' . $photos . '<small class="bl-review-date">' . esc_html( get_comment_date( 'F j, Y', $review ) ) . '</small></div></article>';
		}
		$html .= '</div>';
		if ( count( $reviews ) > 6 ) {
			$html .= '<button class="bl-review-more" type="button">Show More</button>';
		}
		if ( comments_open( $product->get_id() ) ) {
			$post = get_post( $product->get_id() );
			setup_postdata( $post );
			ob_start();
			$rating_field = '<p class="comment-form-rating"><label for="rating">Your rating</label><span class="stars"><select name="rating" id="rating" required><option value="">Rate…</option><option value="5">Perfect</option><option value="4">Good</option><option value="3">Average</option><option value="2">Not that bad</option><option value="1">Very poor</option></select></span></p>';
			$photo_field = '<p class="comment-form-attachments"><label for="bl-review-photos">Add photos (JPEG, PNG, or WebP; up to 3)</label><input id="bl-review-photos" type="file" name="bl_review_photos[]" accept="image/jpeg,image/png,image/webp" multiple></p>';
			$nonce_field = '<input type="hidden" name="bl_review_nonce" value="' . esc_attr( wp_create_nonce( 'bl_review_' . $product->get_id() ) ) . '"><label class="bl-review-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="bl_review_website" tabindex="-1" autocomplete="off"></label>';
			$comment_form = array(
				'title_reply'   => 'Write a Review',
				'label_submit'  => 'Submit Review',
				'comment_field' => $rating_field . '<p class="comment-form-comment"><label for="comment">Your review</label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>' . $photo_field . $nonce_field,
			);
			comment_form( $comment_form, $product->get_id() );
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
