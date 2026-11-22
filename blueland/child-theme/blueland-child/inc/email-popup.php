<?php
/**
 * First-order email offer popup.
 */

defined( 'ABSPATH' ) || exit;

function bl_render_email_popup() {
	if (
		is_admin()
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() )
		|| isset( $_COOKIE['bl_popup_seen'] )
	) {
		return;
	}

	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	if ( ! $shop_url ) {
		$shop_url = home_url( '/shop/' );
	}
	?>
	<div id="bl-email-popup" class="bl-email-popup" role="dialog" aria-modal="true" aria-labelledby="bl-email-popup-title" hidden>
		<div class="bl-email-popup-backdrop" data-popup-dismiss></div>
		<section class="bl-email-popup-dialog" tabindex="-1">
			<button class="bl-email-popup-close" type="button" aria-label="Close offer" data-popup-dismiss>×</button>
			<div class="bl-email-popup-image">
				<img src="<?php echo esc_url( BL_CHILD_URL . '/assets/images/lifestyle.webp' ); ?>" alt="" loading="lazy" decoding="async">
			</div>
			<div class="bl-email-popup-content">
				<p class="bl-email-popup-eyebrow">A cleaner home starts here</p>
				<h2 id="bl-email-popup-title">Get 10% off your first order</h2>
				<form class="bl-popup-form">
					<label for="bl-popup-email">Email address</label>
					<input id="bl-popup-email" type="email" name="email" autocomplete="email" required>
					<label class="bl-popup-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="bl_newsletter_website" tabindex="-1" autocomplete="off"></label>
					<button class="button primary bl-email-popup-submit" type="submit">Get my 10% off</button>
					<p class="bl-email-popup-error" aria-live="polite"></p>
				</form>
				<div class="bl-popup-success" aria-live="polite" hidden>
					<p>Your code is ready:</p>
					<div class="bl-popup-code-row"><strong>WELCOME10</strong><button type="button" class="bl-popup-copy">Copy</button></div>
					<a class="button primary bl-popup-shop" href="<?php echo esc_url( $shop_url ); ?>">Shop now</a>
				</div>
				<p class="bl-email-popup-fine-print">One use per customer. Can't be combined with other offers.</p>
			</div>
		</section>
	</div>
	<?php
}
add_action( 'wp_footer', 'bl_render_email_popup', 9 );
