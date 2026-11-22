<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy it to your theme to maintain
 * compatibility. We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the
 * readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.2
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-checkout-review-order-table">
	<?php if ( function_exists( 'bl_free_shipping_progress_html' ) ) : ?>
		<?php echo bl_free_shipping_progress_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<ul class="bl-co-order-items">
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$visible = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );
			if ( ! $product instanceof WC_Product || ! $product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}
			$item_name = WC()->cart->get_item_product_name( $cart_item, $product );
			$name      = apply_filters( 'woocommerce_cart_item_name', $item_name, $cart_item, $cart_item_key );
			$quantity  = apply_filters( 'woocommerce_checkout_cart_item_quantity', sprintf( '&times;&nbsp;%s', esc_html( $cart_item['quantity'] ) ), $cart_item, $cart_item_key );
			$subtotal  = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
			?>
			<li class="bl-co-order-item">
				<div class="bl-co-item-image"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?><span><?php echo esc_html( (string) $cart_item['quantity'] ); ?></span></div>
				<div class="bl-co-item-description">
					<strong><?php echo wp_kses_post( $name ); ?></strong>
					<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item, false, $item_name ) ); ?>
				</div>
				<div class="bl-co-item-total"><?php echo wp_kses_post( $subtotal ); ?><span class="screen-reader-text"><?php echo wp_kses_post( $quantity ); ?></span></div>
			</li>
			<?php
		}
		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</ul>

	<div class="bl-co-coupon">
		<button type="button" data-co-coupon-toggle aria-expanded="false"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M20.4 13.1 13 20.5 3 10.5V3h7.5z"></path><circle cx="7.5" cy="7.5" r="1"></circle></svg><span>Have a coupon code?</span><strong>Add</strong></button>
		<div class="bl-co-coupon-form" hidden>
			<label class="screen-reader-text" for="bl-co-coupon-code"><?php esc_html_e( 'Coupon code', 'woocommerce' ); ?></label>
			<input id="bl-co-coupon-code" type="text" name="coupon_code" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>">
			<button type="button" class="button" data-co-coupon-apply><?php esc_html_e( 'Apply', 'woocommerce' ); ?></button>
		</div>
		<div class="bl-co-coupon-notice" aria-live="polite"></div>
	</div>

	<div class="bl-co-totals">
		<div class="bl-co-total-row cart-subtotal"><span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span><span><?php wc_cart_totals_subtotal_html(); ?></span></div>
		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<div class="bl-co-total-row cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>"><span><?php wc_cart_totals_coupon_label( $coupon ); ?></span><span><?php wc_cart_totals_coupon_html( $coupon ); ?></span></div>
		<?php endforeach; ?>
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<div class="bl-co-total-row shipping"><span><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span><span class="bl-co-shipping-value"><?php echo wp_kses_post( bl_checkout_shipping_value() ); ?></span></div>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="bl-co-total-row fee"><span><?php echo esc_html( $fee->name ); ?></span><span><?php wc_cart_totals_fee_html( $fee ); ?></span></div>
		<?php endforeach; ?>
		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<div class="bl-co-total-row tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>"><span><?php echo esc_html( $tax->label ); ?></span><span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span></div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="bl-co-total-row tax-total"><span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span><span><?php wc_cart_totals_taxes_total_html(); ?></span></div>
			<?php endif; ?>
		<?php endif; ?>
		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
		<div class="bl-co-total-row order-total"><strong><?php esc_html_e( 'Total', 'woocommerce' ); ?></strong><strong><?php wc_cart_totals_order_total_html(); ?></strong></div>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</div>

	<div class="bl-co-trust">
		<div><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h11v11H3zM14 10h4l3 3v4h-7zM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM18 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path></svg><span>Free shipping on $50+</span></div>
		<div><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.5-8 11-8 11z"></path><path d="m8.5 12 2.2 2.2 4.8-5"></path></svg><span>100-day returns</span></div>
		<div><svg aria-hidden="true" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg><span>Secure checkout</span></div>
	</div>
</div>
