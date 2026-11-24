<?php
/**
 * Header, promo bar and footer.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rotating messages in the bar above the header.
 */
function dewier_promo_messages() {
	return apply_filters(
		'dewier_promo_messages',
		array(
			array( __( 'Up to 30% off sitewide, plus a free gift on orders of $100+ after discount.', 'dewier' ), home_url( '/shop/' ) ),
			array( __( 'Free shipping on orders over $40.', 'dewier' ), home_url( '/shop/' ) ),
			array( __( 'Save 25% on all new subscriptions.', 'dewier' ), home_url( '/shop/' ) ),
		)
	);
}

function dewier_promo_bar() {
	$messages = dewier_promo_messages();
	if ( ! $messages ) {
		return;
	}
	?>
	<div class="dw-promo" role="region" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Announcements', 'dewier' ); ?>">
		<div class="dw-promo__slides" aria-live="off">
			<?php foreach ( $messages as $index => $message ) : ?>
				<a class="dw-promo__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" href="<?php echo esc_url( $message[1] ); ?>"<?php echo 0 === $index ? '' : ' tabindex="-1" aria-hidden="true"'; ?>><?php echo esc_html( $message[0] ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $messages ) > 1 ) : ?>
			<div class="dw-promo__controls">
				<button type="button" class="dw-promo__btn" data-promo="prev" aria-label="<?php esc_attr_e( 'Previous announcement', 'dewier' ); ?>"><?php echo dewier_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button type="button" class="dw-promo__btn" data-promo="next" aria-label="<?php esc_attr_e( 'Next announcement', 'dewier' ); ?>"><?php echo dewier_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button type="button" class="dw-promo__btn" data-promo="toggle" aria-pressed="false" aria-label="<?php esc_attr_e( 'Pause announcements', 'dewier' ); ?>"><?php echo dewier_icon( 'pause' ) . dewier_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

function dewier_nav_fallback() {
	echo '<ul class="dw-nav__list"><li><a href="' . esc_url( home_url( '/shop/' ) ) . '">' . esc_html__( 'Shop all', 'dewier' ) . '</a></li></ul>';
}

/**
 * Header bag link. Replaces Shoptimizer's pluggable cart link; the wrapper class
 * is kept so its cart fragment and drawer trigger keep working.
 */
function shoptimizer_cart_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$count = WC()->cart->get_cart_contents_count();
	?>
	<div class="shoptimizer-cart">
		<a class="cart-contents dw-utils__link" role="button" href="#" aria-controls="shoptimizerCartDrawer" aria-expanded="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: item count */ __( 'Bag, %d items', 'dewier' ), $count ) ); ?>">
			<?php esc_html_e( 'Bag', 'dewier' ); ?> (<span class="count"><?php echo esc_html( $count ); ?></span>)
		</a>
	</div>
	<?php
}

function dewier_cart_drawer_title() {
	return __( 'Your bag', 'dewier' );
}
add_filter( 'theme_mod_shoptimizer_cart_title', 'dewier_cart_drawer_title' );

/**
 * Free-shipping state of the bag for the shopper's shipping zone:
 * array( amount still to spend, percent of the minimum reached ), with 0 and
 * 100 once WooCommerce offers a free shipping rate. Null when the zone has no
 * free shipping an order amount can unlock.
 */
function dewier_free_shipping() {
	$packages = WC()->cart->get_shipping_packages();
	if ( ! $packages ) {
		return null;
	}
	$package = current( $packages );
	$state   = null;
	foreach ( WC_Shipping_Zones::get_zone_matching_package( $package )->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' !== $method->id ) {
			continue;
		}
		// WooCommerce decides whether the rate is offered: coupons, discounts and filters included.
		if ( $method->is_available( $package ) ) {
			return array( 0.0, 100 );
		}
		$minimum = (float) $method->min_amount;
		if ( $minimum <= 0 || ! in_array( $method->requires, array( 'min_amount', 'either' ), true ) ) {
			continue;
		}
		// Same order amount WC_Shipping_Free_Shipping::is_available() compares with the minimum.
		$total = (float) WC()->cart->get_displayed_subtotal();
		if ( 'no' === $method->ignore_discounts ) {
			$total -= (float) WC()->cart->get_discount_total();
			if ( WC()->cart->display_prices_including_tax() ) {
				$total -= (float) WC()->cart->get_discount_tax();
			}
		}
		$total = round( $total, wc_get_price_decimals() );
		if ( $total >= $minimum ) {
			continue;
		}
		if ( ! $state || $minimum - $total < $state[0] ) {
			$state = array( $minimum - $total, (int) round( max( 0, $total ) / $minimum * 100 ) );
		}
	}
	return $state;
}

/**
 * Free-shipping progress shown at the top of the bag drawer.
 */
function dewier_cart_progress() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$state = dewier_free_shipping();
	if ( ! $state ) {
		return;
	}
	list( $remaining, $percent ) = $state;
	?>
	<div class="dw-progress">
		<p class="dw-progress__text">
			<?php
			if ( $remaining > 0 ) {
				/* translators: %s: amount */
				echo wp_kses_post( sprintf( __( 'You are %s away from free shipping.', 'dewier' ), wc_price( $remaining ) ) );
			} else {
				esc_html_e( 'You have unlocked free shipping!', 'dewier' );
			}
			?>
		</p>
		<div class="dw-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $percent ); ?>">
			<span class="dw-progress__bar" style="width:<?php echo esc_attr( $percent ); ?>%"></span>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_mini_cart', 'dewier_cart_progress', 5 );

/**
 * The child header.php has no header-4 wrappers, so their closing tags must not print.
 */
function dewier_header_setup() {
	remove_action( 'shoptimizer_before_content', 'shoptimizer_header_wrapper_close', 0 );
}
add_action( 'wp', 'dewier_header_setup' );

/**
 * Footer.
 */
function dewier_footer_setup() {
	remove_action( 'shoptimizer_before_footer', 'shoptimizer_below_content', 10 );
	remove_action( 'shoptimizer_footer', 'shoptimizer_footer_widgets', 20 );
	remove_action( 'shoptimizer_footer', 'shoptimizer_footer_copyright', 30 );
	add_action( 'shoptimizer_footer', 'dewier_footer', 20 );
}
add_action( 'wp', 'dewier_footer_setup' );

function dewier_footer_column( $location, $fallback_title ) {
	if ( ! has_nav_menu( $location ) ) {
		return;
	}
	$locations = get_nav_menu_locations();
	$menu      = wp_get_nav_menu_object( $locations[ $location ] );
	?>
	<div class="dw-footer__col dw-footer__col--<?php echo esc_attr( str_replace( 'dewier_footer_', '', $location ) ); ?>">
		<h2 class="dw-eyebrow"><?php echo esc_html( $menu ? $menu->name : $fallback_title ); ?></h2>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'dw-footer__list',
				'depth'          => 1,
			)
		);
		?>
	</div>
	<?php
}

function dewier_footer() {
	$name = get_bloginfo( 'name' );
	if ( dewier_is_checkout_flow() ) {
		dewier_checkout_footer( $name );
		return;
	}
	?>
	<footer class="dw-footer">
		<div class="dw-footer__top">
			<p class="dw-footer__about">
				<?php
				/* translators: %s: site name */
				echo esc_html( apply_filters( 'dewier_footer_about', sprintf( __( 'At %s, we make products inspired by real life, with skin first and makeup second. We believe beauty is about having fun, celebrating freedom, and being present. Wherever you are in your routine, you look good.', 'dewier' ), $name ) ) );
				?>
			</p>
		</div>
		<div class="dw-footer__cols">
			<?php
			dewier_footer_column( 'dewier_footer_help', __( 'How can we help?', 'dewier' ) );
			dewier_footer_column( 'dewier_footer_about', __( 'About', 'dewier' ) );
			dewier_footer_column( 'dewier_footer_stores', __( 'Stores', 'dewier' ) );
			dewier_footer_column( 'dewier_footer_social', __( 'Social', 'dewier' ) );
			?>
		</div>
		<div class="dw-footer__bottom">
			<div class="dw-footer__legal">
				<span>&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . $name ); ?>. <?php esc_html_e( 'All rights reserved.', 'dewier' ); ?></span>
				<?php
				if ( has_nav_menu( 'dewier_footer_legal' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'dewier_footer_legal',
							'container'      => false,
							'menu_class'     => 'dw-footer__legal-list',
							'depth'          => 1,
						)
					);
				}
				?>
			</div>
			<svg class="dw-footer__doodle" viewBox="0 0 330 170" fill="none" stroke="currentColor" stroke-width="13" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<path d="M92 14C44 10 10 44 12 92c2 44 40 68 82 62 40-6 66-40 60-84-5-34-30-54-62-56Z"/>
				<path d="M62 70v2M96 66v2" stroke-width="12"/>
				<path d="M50 104c20 26 56 22 76-6"/>
				<path d="M200 96c-8-18 12-26 20-8l10 22-8-58c-2-16 18-18 22-2l8 42 2-56c0-16 20-16 22 0l2 58 12-44c4-14 22-10 20 6l-12 62c-6 32-22 42-50 40-26-2-38-22-48-62Z"/>
			</svg>
		</div>
	</footer>
	<?php
}

/**
 * Checkout keeps only the legal line so nothing pulls shoppers away from paying.
 */
function dewier_checkout_footer( $name ) {
	?>
	<footer class="dw-footer dw-footer--checkout">
		<div class="dw-footer__legal">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . $name ); ?>. <?php esc_html_e( 'All rights reserved.', 'dewier' ); ?></span>
			<?php
			if ( has_nav_menu( 'dewier_footer_legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'dewier_footer_legal',
						'container'      => false,
						'menu_class'     => 'dw-footer__legal-list',
						'depth'          => 1,
					)
				);
			}
			?>
		</div>
	</footer>
	<?php
}
