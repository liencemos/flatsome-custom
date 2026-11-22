<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
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
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$needs_shipping = WC()->cart->needs_shipping();
?>
<div class="bl-co-shell">
	<header class="bl-co-topbar">
		<div class="bl-co-topbar-left"><a class="bl-co-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">PURELAND</a></div>
		<div class="bl-co-topbar-right"><svg aria-hidden="true" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg><span>Secure Checkout</span></div>
	</header>

	<form name="checkout" method="post" class="checkout woocommerce-checkout bl-co-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
		<input type="hidden" name="bl_checkout_modern" value="1">
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="bl-co-layout">
			<main class="bl-co-main">
				<div class="bl-co-main-inner">
					<nav class="bl-co-steps" aria-label="Checkout progress">
						<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Cart</a>
						<span class="bl-co-step-separator" aria-hidden="true"><svg viewBox="0 0 12 12" focusable="false"><path d="m4 2 4 4-4 4"></path></svg></span>
						<button type="button" data-co-step="information" aria-current="step">Information</button>
						<?php if ( $needs_shipping ) : ?>
							<span class="bl-co-step-separator" aria-hidden="true"><svg viewBox="0 0 12 12" focusable="false"><path d="m4 2 4 4-4 4"></path></svg></span>
							<button type="button" data-co-step="shipping" aria-disabled="true">Shipping</button>
						<?php endif; ?>
						<span class="bl-co-step-separator" aria-hidden="true"><svg viewBox="0 0 12 12" focusable="false"><path d="m4 2 4 4-4 4"></path></svg></span>
						<button type="button" data-co-step="payment" aria-disabled="true">Payment</button>
					</nav>

					<section class="bl-co-panel" data-co-panel="information">
						<h1 tabindex="-1">Information</h1>
						<div id="customer_details">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>
						</div>
						<?php if ( ! $needs_shipping ) : ?>
							<?php bl_checkout_render_order_notes(); ?>
						<?php endif; ?>
						<div class="bl-co-step-footer">
							<button type="button" class="button bl-co-continue" data-co-continue><?php echo esc_html( $needs_shipping ? 'Continue to shipping' : 'Continue to payment' ); ?></button>
							<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="bl-co-return">‹ Return to cart</a>
						</div>
					</section>

					<?php if ( $needs_shipping ) : ?>
						<section class="bl-co-panel" data-co-panel="shipping" hidden>
							<h1 tabindex="-1">Shipping</h1>
							<?php echo bl_checkout_render_review_summary(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h2 class="bl-co-section-title">Shipping method</h2>
							<?php echo bl_checkout_shipping_methods_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php bl_checkout_render_order_notes(); ?>
							<?php bl_render_gift_wrap_control(); ?>
							<div class="bl-co-step-footer">
								<button type="button" class="button bl-co-continue" data-co-continue>Continue to payment</button>
								<button type="button" class="bl-co-return" data-co-change="information">‹ Return to information</button>
							</div>
						</section>
					<?php endif; ?>

					<section class="bl-co-panel" data-co-panel="payment" hidden>
						<h1 tabindex="-1">Payment</h1>
						<?php echo bl_checkout_render_review_summary( $needs_shipping ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h2 class="bl-co-section-title">Payment method</h2>
						<p class="bl-co-secure-copy">All transactions are secure and encrypted.</p>
						<?php woocommerce_checkout_payment(); ?>
						<div class="bl-co-step-footer">
							<button type="button" class="bl-co-return" data-co-change="<?php echo esc_attr( $needs_shipping ? 'shipping' : 'information' ); ?>">‹ Return to <?php echo esc_html( $needs_shipping ? 'shipping' : 'information' ); ?></button>
						</div>
					</section>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				</div>
			</main>

			<aside class="bl-co-summary" aria-label="Order summary">
				<div class="bl-co-summary-inner">
					<div class="bl-co-summary-mobile-head"><strong>Your Order</strong><button type="button" data-co-summary-close aria-label="Close order summary">×</button></div>
					<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
					<h2 id="order_review_heading" class="screen-reader-text"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h2>
					<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action( 'woocommerce_checkout_order_review' ); ?>
					</div>
					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>
			</aside>
		</div>
		<button type="button" class="bl-co-mobile-order-toggle" data-co-summary-toggle aria-expanded="false">
			<span>Your Order <span aria-hidden="true">⌄</span></span><strong class="bl-co-mobile-total"></strong>
		</button>
	</form>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
