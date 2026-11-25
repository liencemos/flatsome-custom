<?php
/**
 * Checkout: Information, Shipping and Payment steps beside the order summary.
 *
 * @package dewier
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dewier_ships = WC()->cart->needs_shipping();
$dewier_steps = array( 'information' => __( 'Information', 'dewier' ) );
if ( $dewier_ships ) {
	$dewier_steps['shipping'] = __( 'Shipping', 'dewier' );
}
$dewier_steps['payment'] = __( 'Payment', 'dewier' );
$dewier_step_keys        = array_keys( $dewier_steps );
?>
<div class="dw-co" data-steps="<?php echo esc_attr( implode( ' ', $dewier_step_keys ) ); ?>">
	<div class="dw-co__main">
		<nav class="dw-co__crumbs" aria-label="<?php esc_attr_e( 'Checkout steps', 'dewier' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Bag', 'dewier' ); ?></a></li>
				<?php foreach ( $dewier_steps as $dewier_key => $dewier_label ) : ?>
					<li><button type="button" class="dw-co__crumb" data-go="<?php echo esc_attr( $dewier_key ); ?>"><?php echo esc_html( $dewier_label ); ?></button></li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<div class="dw-co__pre">
			<?php
			do_action( 'woocommerce_before_checkout_form', $checkout );

			// If checkout registration is disabled and not logged in, the user cannot checkout.
			if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
				echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
				echo '</div></div></div>';
				return;
			}
			?>
		</div>

		<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
			<section class="dw-co__step" data-step="information" aria-label="<?php esc_attr_e( 'Information', 'dewier' ); ?>">
				<?php if ( $checkout->get_checkout_fields() ) : ?>
					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

					<div class="dw-co__group dw-co__contact">
						<h2 class="dw-co__heading"><?php esc_html_e( 'Contact', 'dewier' ); ?></h2>
						<?php dewier_checkout_contact( $checkout ); ?>
					</div>

					<div class="dw-co__group" id="customer_details">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
				<?php endif; ?>

				<div class="dw-co__nav">
					<a class="dw-co__back" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Return to bag', 'dewier' ); ?></a>
					<button type="button" class="dw-btn dw-btn--dark dw-co__next" data-go="<?php echo esc_attr( $dewier_step_keys[1] ); ?>">
						<span><?php echo esc_html( $dewier_ships ? __( 'Continue to shipping', 'dewier' ) : __( 'Continue to payment', 'dewier' ) ); ?></span>
					</button>
				</div>
			</section>

			<?php if ( $dewier_ships ) : ?>
				<section class="dw-co__step" data-step="shipping" aria-label="<?php esc_attr_e( 'Shipping', 'dewier' ); ?>">
					<div class="dw-co__recap" data-recap="shipping"></div>
					<div class="dw-co__group">
						<h2 class="dw-co__heading"><?php esc_html_e( 'Shipping method', 'dewier' ); ?></h2>
						<?php dewier_checkout_shipping_methods(); ?>
					</div>
					<div class="dw-co__nav">
						<button type="button" class="dw-co__back" data-go="information"><?php esc_html_e( 'Return to information', 'dewier' ); ?></button>
						<button type="button" class="dw-btn dw-btn--dark dw-co__next" data-go="payment"><span><?php esc_html_e( 'Continue to payment', 'dewier' ); ?></span></button>
					</div>
				</section>
			<?php endif; ?>

			<section class="dw-co__step" data-step="payment" aria-label="<?php esc_attr_e( 'Payment', 'dewier' ); ?>">
				<div class="dw-co__recap" data-recap="payment"></div>
				<div class="dw-co__group">
					<h2 class="dw-co__heading"><?php esc_html_e( 'Payment', 'dewier' ); ?></h2>
					<p class="dw-co-muted"><?php esc_html_e( 'All transactions are secure and encrypted.', 'dewier' ); ?></p>
					<?php woocommerce_checkout_payment(); ?>
				</div>
				<div class="dw-co__nav dw-co__nav--end">
					<button type="button" class="dw-co__back" data-go="<?php echo esc_attr( $dewier_step_keys[ count( $dewier_step_keys ) - 2 ] ); ?>">
						<?php echo esc_html( $dewier_ships ? __( 'Return to shipping', 'dewier' ) : __( 'Return to information', 'dewier' ) ); ?>
					</button>
				</div>
			</section>
		</form>

		<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
	</div>

	<aside class="dw-co__aside" aria-label="<?php esc_attr_e( 'Order summary', 'dewier' ); ?>">
		<button type="button" class="dw-co-toggle" aria-expanded="false" aria-controls="dw-co-summary">
			<span class="dw-co-toggle__label">
				<span class="dw-co-toggle__show"><?php esc_html_e( 'Show order summary', 'dewier' ); ?></span>
				<span class="dw-co-toggle__hide"><?php esc_html_e( 'Hide order summary', 'dewier' ); ?></span>
			</span>
			<?php echo dewier_checkout_mobile_total(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>

		<div class="dw-co-summary" id="dw-co-summary">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<h2 id="order_review_heading" class="dw-co__heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h2>
			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="dw-co-coupon">
					<label class="screen-reader-text" for="dw-co-coupon"><?php esc_html_e( 'Discount code', 'dewier' ); ?></label>
					<input id="dw-co-coupon" class="input-text" type="text" placeholder="<?php esc_attr_e( 'Discount code', 'dewier' ); ?>" autocomplete="off">
					<button type="button" class="dw-btn dw-co-coupon__apply"><span><?php esc_html_e( 'Apply', 'dewier' ); ?></span></button>
				</div>
			<?php endif; ?>

			<?php echo dewier_checkout_progress_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</aside>
</div>
