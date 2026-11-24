<?php
/**
 * Checkout Form
 *
 * Override this template in yourtheme/modern-checkout-for-woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'modern-checkout-for-woocommerce' ) ) );
	return;
}

$mcwc_needs_shipping = WC()->cart->needs_shipping();
$mcwc_steps          = array( 'information' => __( 'Information', 'modern-checkout-for-woocommerce' ) );
if ( $mcwc_needs_shipping ) {
	$mcwc_steps['shipping'] = __( 'Shipping', 'modern-checkout-for-woocommerce' );
}
$mcwc_steps['payment'] = __( 'Payment', 'modern-checkout-for-woocommerce' );
$mcwc_steps            = apply_filters( 'mcwc_steps', $mcwc_steps );
?>
<div class="mcwc-shell mcwc-checkout">
	<form name="checkout" method="post" class="checkout woocommerce-checkout mcwc-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'modern-checkout-for-woocommerce' ); ?>">
		<input type="hidden" name="mcwc_checkout" value="1">
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="mcwc-layout">
			<main class="mcwc-main">
				<div class="mcwc-main-inner">
					<nav class="mcwc-steps" aria-label="<?php esc_attr_e( 'Checkout progress', 'modern-checkout-for-woocommerce' ); ?>">
						<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Cart', 'modern-checkout-for-woocommerce' ); ?></a>
						<?php foreach ( $mcwc_steps as $mcwc_step => $mcwc_label ) : ?>
							<span class="mcwc-step-separator" aria-hidden="true"><svg viewBox="0 0 12 12" focusable="false"><path d="m4 2 4 4-4 4"></path></svg></span>
							<button type="button" data-co-step="<?php echo esc_attr( $mcwc_step ); ?>" <?php echo 'information' === $mcwc_step ? 'aria-current="step"' : 'aria-disabled="true"'; ?>><?php echo esc_html( $mcwc_label ); ?></button>
						<?php endforeach; ?>
					</nav>

					<section class="mcwc-panel" data-co-panel="information">
						<h1 tabindex="-1"><?php esc_html_e( 'Information', 'modern-checkout-for-woocommerce' ); ?></h1>
						<div id="customer_details">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>
							<?php do_action( 'mcwc_information_after_fields', WC()->checkout() ); ?>
						</div>
						<?php if ( ! $mcwc_needs_shipping ) : ?>
							<?php mcwc_checkout_render_order_notes(); ?>
						<?php endif; ?>
						<div class="mcwc-step-footer">
							<button type="button" class="button mcwc-continue" data-co-continue><?php echo esc_html( $mcwc_needs_shipping ? __( 'Continue to shipping', 'modern-checkout-for-woocommerce' ) : __( 'Continue to payment', 'modern-checkout-for-woocommerce' ) ); ?></button>
							<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="mcwc-return"><?php esc_html_e( '‹ Return to cart', 'modern-checkout-for-woocommerce' ); ?></a>
						</div>
					</section>

					<?php if ( $mcwc_needs_shipping ) : ?>
						<section class="mcwc-panel" data-co-panel="shipping" hidden>
							<h1 tabindex="-1"><?php esc_html_e( 'Shipping', 'modern-checkout-for-woocommerce' ); ?></h1>
							<?php echo mcwc_checkout_render_review_summary(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h2 class="mcwc-section-title"><?php esc_html_e( 'Shipping method', 'modern-checkout-for-woocommerce' ); ?></h2>
							<?php echo mcwc_checkout_shipping_methods_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php do_action( 'mcwc_shipping_after_methods' ); ?>
							<?php mcwc_checkout_render_order_notes(); ?>
							<?php do_action( 'mcwc_shipping_step_end' ); ?>
							<div class="mcwc-step-footer">
								<button type="button" class="button mcwc-continue" data-co-continue><?php esc_html_e( 'Continue to payment', 'modern-checkout-for-woocommerce' ); ?></button>
								<button type="button" class="mcwc-return" data-co-change="information"><?php esc_html_e( '‹ Return to information', 'modern-checkout-for-woocommerce' ); ?></button>
							</div>
						</section>
					<?php endif; ?>

					<section class="mcwc-panel" data-co-panel="payment" hidden>
						<?php do_action( 'mcwc_payment_step_before' ); ?>
						<h1 tabindex="-1"><?php esc_html_e( 'Payment', 'modern-checkout-for-woocommerce' ); ?></h1>
						<?php echo mcwc_checkout_render_review_summary( $mcwc_needs_shipping ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h2 class="mcwc-section-title"><?php esc_html_e( 'Payment', 'modern-checkout-for-woocommerce' ); ?></h2>
						<p class="mcwc-secure-copy"><?php esc_html_e( 'All transactions are secure and encrypted.', 'modern-checkout-for-woocommerce' ); ?></p>
						<?php woocommerce_checkout_payment(); ?>
						<div class="mcwc-step-footer">
							<button type="button" class="mcwc-return" data-co-change="<?php echo esc_attr( $mcwc_needs_shipping ? 'shipping' : 'information' ); ?>"><?php echo esc_html( $mcwc_needs_shipping ? __( '‹ Return to shipping', 'modern-checkout-for-woocommerce' ) : __( '‹ Return to information', 'modern-checkout-for-woocommerce' ) ); ?></button>
						</div>
					</section>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				</div>
			</main>

			<aside class="mcwc-summary" aria-label="<?php esc_attr_e( 'Order summary', 'modern-checkout-for-woocommerce' ); ?>">
				<div class="mcwc-summary-inner">
					<div class="mcwc-summary-mobile-head"><strong><?php esc_html_e( 'Your Order', 'modern-checkout-for-woocommerce' ); ?></strong><button type="button" data-co-summary-close aria-label="<?php esc_attr_e( 'Close order summary', 'modern-checkout-for-woocommerce' ); ?>">×</button></div>
					<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
					<h2 id="order_review_heading" class="screen-reader-text"><?php esc_html_e( 'Your order', 'modern-checkout-for-woocommerce' ); ?></h2>
					<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action( 'woocommerce_checkout_order_review' ); ?>
					</div>
					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>
			</aside>
		</div>
		<button type="button" class="mcwc-mobile-order-toggle" data-co-summary-toggle aria-expanded="false">
			<span><?php esc_html_e( 'Your Order', 'modern-checkout-for-woocommerce' ); ?> <span aria-hidden="true">⌄</span></span><strong class="mcwc-mobile-total"></strong>
		</button>
	</form>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
