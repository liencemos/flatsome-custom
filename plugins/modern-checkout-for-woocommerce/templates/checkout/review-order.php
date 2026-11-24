<?php
/**
 * Review order table
 *
 * Override this template in yourtheme/modern-checkout-for-woocommerce/checkout/review-order.php.
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
	<?php echo mcwc_free_shipping_progress_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php do_action( 'mcwc_summary_before_items' ); ?>

	<ul class="mcwc-order-items">
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );
		foreach ( WC()->cart->get_cart() as $mcwc_cart_item_key => $mcwc_cart_item ) {
			$mcwc_product = apply_filters( 'woocommerce_cart_item_product', $mcwc_cart_item['data'], $mcwc_cart_item, $mcwc_cart_item_key );
			$mcwc_visible = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $mcwc_cart_item, $mcwc_cart_item_key );
			if ( ! $mcwc_product instanceof WC_Product || ! $mcwc_product->exists() || $mcwc_cart_item['quantity'] <= 0 || ! $mcwc_visible ) {
				continue;
			}
			$mcwc_item_name = $mcwc_product->get_name();
			$mcwc_name      = apply_filters( 'woocommerce_cart_item_name', $mcwc_item_name, $mcwc_cart_item, $mcwc_cart_item_key );
			$mcwc_quantity  = apply_filters( 'woocommerce_checkout_cart_item_quantity', sprintf( '&times;&nbsp;%s', esc_html( $mcwc_cart_item['quantity'] ) ), $mcwc_cart_item, $mcwc_cart_item_key );
			$mcwc_subtotal  = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $mcwc_product, $mcwc_cart_item['quantity'] ), $mcwc_cart_item, $mcwc_cart_item_key );
			?>
			<li class="mcwc-order-item">
				<div class="mcwc-item-image"><?php echo wp_kses_post( $mcwc_product->get_image( 'woocommerce_thumbnail' ) ); ?><span><?php echo esc_html( (string) $mcwc_cart_item['quantity'] ); ?></span></div>
				<div class="mcwc-item-description">
					<strong><?php echo wp_kses_post( $mcwc_name ); ?></strong>
					<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $mcwc_cart_item, false, $mcwc_item_name ) ); ?>
				</div>
				<div class="mcwc-item-total"><?php echo wp_kses_post( $mcwc_subtotal ); ?><span class="screen-reader-text"><?php echo wp_kses_post( $mcwc_quantity ); ?></span></div>
			</li>
			<?php
		}
		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</ul>

	<?php if ( 'yes' === mcwc_option( 'show_coupon', 'yes' ) ) : ?>
		<div class="mcwc-coupon">
			<button type="button" data-co-coupon-toggle aria-expanded="false"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M20.4 13.1 13 20.5 3 10.5V3h7.5z"></path><circle cx="7.5" cy="7.5" r="1"></circle></svg><span><?php esc_html_e( 'Have a coupon code?', 'modern-checkout-for-woocommerce' ); ?></span><strong><?php esc_html_e( 'Add', 'modern-checkout-for-woocommerce' ); ?></strong></button>
			<div class="mcwc-coupon-form" hidden>
				<label class="screen-reader-text" for="mcwc-coupon-code"><?php esc_html_e( 'Coupon code', 'modern-checkout-for-woocommerce' ); ?></label>
				<input id="mcwc-coupon-code" type="text" name="coupon_code" placeholder="<?php esc_attr_e( 'Coupon code', 'modern-checkout-for-woocommerce' ); ?>">
				<button type="button" class="button" data-co-coupon-apply><?php esc_html_e( 'Apply', 'modern-checkout-for-woocommerce' ); ?></button>
			</div>
			<div class="mcwc-coupon-notice" aria-live="polite"></div>
		</div>
	<?php endif; ?>

	<div class="mcwc-totals">
			<div class="mcwc-total-row cart-subtotal"><span><?php esc_html_e( 'Subtotal', 'modern-checkout-for-woocommerce' ); ?></span><span><?php wc_cart_totals_subtotal_html(); ?></span></div>
		<?php foreach ( WC()->cart->get_coupons() as $mcwc_code => $mcwc_coupon ) : ?>
			<div class="mcwc-total-row cart-discount coupon-<?php echo esc_attr( sanitize_title( $mcwc_code ) ); ?>"><span><?php wc_cart_totals_coupon_label( $mcwc_coupon ); ?></span><span><?php wc_cart_totals_coupon_html( $mcwc_coupon ); ?></span></div>
		<?php endforeach; ?>
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<div class="mcwc-total-row shipping"><span><?php esc_html_e( 'Shipping', 'modern-checkout-for-woocommerce' ); ?></span><span class="mcwc-shipping-value"><?php echo wp_kses_post( mcwc_checkout_shipping_value() ); ?></span></div>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
		<?php foreach ( WC()->cart->get_fees() as $mcwc_fee ) : ?>
			<div class="mcwc-total-row fee"><span><?php echo esc_html( $mcwc_fee->name ); ?></span><span><?php wc_cart_totals_fee_html( $mcwc_fee ); ?></span></div>
		<?php endforeach; ?>
		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $mcwc_code => $mcwc_tax ) : ?>
					<div class="mcwc-total-row tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $mcwc_code ) ); ?>"><span><?php echo esc_html( $mcwc_tax->label ); ?></span><span><?php echo wp_kses_post( $mcwc_tax->formatted_amount ); ?></span></div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="mcwc-total-row tax-total"><span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span><span><?php wc_cart_totals_taxes_total_html(); ?></span></div>
			<?php endif; ?>
		<?php endif; ?>
		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
		<div class="mcwc-total-row order-total"><strong><?php esc_html_e( 'Total', 'modern-checkout-for-woocommerce' ); ?></strong><strong><?php wc_cart_totals_order_total_html(); ?></strong></div>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</div>
	<?php do_action( 'mcwc_summary_after_totals' ); ?>

	<div class="mcwc-trust">
		<?php foreach ( mcwc_trust_items() as $mcwc_item ) : ?>
			<div><?php echo mcwc_trust_icon_svg( $mcwc_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $mcwc_item['text'] ); ?></span></div>
		<?php endforeach; ?>
	</div>
</div>
