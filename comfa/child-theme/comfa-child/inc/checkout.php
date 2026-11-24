<?php
/**
 * Comfa's classic Checkout presentation.
 */

defined( 'ABSPATH' ) || exit;

function cf_checkout_layout() {
	return function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url()
		&& has_shortcode( get_post_field( 'post_content', get_queried_object_id() ), 'woocommerce_checkout' );
}

add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		if ( cf_checkout_layout() && isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['priority'] = 5;
			$fields['billing']['billing_email']['class'] = array_unique( array_merge( $fields['billing']['billing_email']['class'] ?? array(), array( 'form-row-wide' ) ) );
		}
		return $fields;
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! cf_checkout_layout() ) {
			return;
		}
		wp_enqueue_style( 'cf-checkout', CF_URL . '/assets/css/checkout.css', array( 'comfa-child' ), (string) filemtime( CF_DIR . '/assets/css/checkout.css' ) );
		wp_enqueue_script( 'cf-checkout', CF_URL . '/assets/js/checkout.js', array( 'wc-checkout' ), (string) filemtime( CF_DIR . '/assets/js/checkout.js' ), true );
		wp_localize_script(
			'cf-checkout',
		'cfCheckout',
			array(
				'required' => __( 'Please complete this field.', 'comfa' ),
				'invalid'  => __( 'Please check this field.', 'comfa' ),
				'contact'  => __( 'Contact', 'comfa' ),
				'shipTo'   => __( 'Ship to', 'comfa' ),
				'method'   => __( 'Method', 'comfa' ),
				'change'   => __( 'Change', 'comfa' ),
				'applied'  => __( 'Discount applied.', 'comfa' ),
				'couponError' => __( 'Unable to apply this discount code.', 'comfa' ),
			)
		);
	},
	30
);

add_action(
	'woocommerce_before_checkout_form',
	function ( $checkout ) {
		if ( ! cf_checkout_layout() ) {
			return;
		}
		if ( $checkout->get_checkout_fields() ) {
			remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
			add_action( 'woocommerce_checkout_after_customer_details', 'cf_checkout_shipping_panel', 10 );
			add_action( 'woocommerce_checkout_after_customer_details', 'cf_checkout_payment_panel', 20 );
		}
		?>
		<header class="cf-checkout-intro">
			<h1><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?><span class="screen-reader-text"> — <?php esc_html_e( 'Checkout', 'comfa' ); ?></span></a></h1>
			<span><?php esc_html_e( 'Secure checkout', 'comfa' ); ?></span>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Bag', 'comfa' ); ?></a>
		</header>
		<?php
	},
	5
);

add_action(
	'woocommerce_checkout_before_customer_details',
	function () {
		if ( ! cf_checkout_layout() ) {
			return;
		}
		$stages = array( 'details' => __( 'Information', 'comfa' ) );
		if ( WC()->cart->needs_shipping() ) {
			$stages['delivery'] = __( 'Shipping', 'comfa' );
		}
		$stages['payment'] = __( 'Payment', 'comfa' );
		?>
		<nav class="cf-checkout-stages" aria-label="<?php esc_attr_e( 'Checkout sections', 'comfa' ); ?>" hidden>
			<ol>
				<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Bag', 'comfa' ); ?></a></li>
				<?php foreach ( $stages as $stage => $label ) : ?>
					<li><button type="button" data-cf-stage="<?php echo esc_attr( $stage ); ?>"><?php echo esc_html( $label ); ?></button></li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<section id="cf-checkout-details" class="cf-checkout-panel" aria-labelledby="cf-checkout-details-title">
			<h2 id="cf-checkout-details-title"><?php esc_html_e( 'Contact', 'comfa' ); ?></h2>
		<?php
	},
	100
);

function cf_checkout_shipping_panel() {
	$shipping = WC()->cart->needs_shipping();
	?>
	<div class="cf-checkout-actions">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Return to bag', 'comfa' ); ?></a>
		<?php cf_checkout_continue( $shipping ? __( 'Continue to shipping', 'comfa' ) : __( 'Continue to payment', 'comfa' ) ); ?>
	</div>
	<?php
	echo '</section>';
	if ( ! $shipping ) {
		return;
	}
	?>
	<section id="cf-checkout-delivery" class="cf-checkout-panel" aria-labelledby="cf-checkout-delivery-title" hidden>
		<div class="cf-checkout-recap"></div>
		<h2 id="cf-checkout-delivery-title"><?php esc_html_e( 'Shipping method', 'comfa' ); ?></h2>
		<table class="cf-checkout-rates"><tbody></tbody></table>
		<p class="cf-checkout-shipping-error" role="alert" tabindex="-1" hidden><?php esc_html_e( 'Choose an available shipping method before continuing. If none is shown, check your delivery address under Information.', 'comfa' ); ?></p>
		<div class="cf-checkout-actions">
			<?php cf_checkout_back( 'details', __( 'Return to information', 'comfa' ) ); ?>
			<?php cf_checkout_continue( __( 'Continue to payment', 'comfa' ) ); ?>
		</div>
	</section>
	<?php
}

function cf_checkout_continue( $label ) {
	printf( '<button type="button" class="button cf-checkout-next" hidden>%s <span aria-hidden="true">→</span></button>', esc_html( $label ) );
}

function cf_checkout_back( $stage, $label ) {
	printf( '<button type="button" class="cf-checkout-back" data-cf-go="%1$s">%2$s</button>', esc_attr( $stage ), esc_html( $label ) );
}

function cf_checkout_payment_panel() {
	$shipping = WC()->cart->needs_shipping();
	?>
	<section id="cf-checkout-payment" class="cf-checkout-panel" aria-labelledby="cf-checkout-payment-title">
		<div class="cf-checkout-recap"></div>
		<h2 id="cf-checkout-payment-title"><?php esc_html_e( 'Payment', 'comfa' ); ?></h2>
		<p class="cf-checkout-muted"><?php esc_html_e( 'All transactions are secure and encrypted.', 'comfa' ); ?></p>
		<?php woocommerce_checkout_payment(); ?>
		<div class="cf-checkout-actions">
			<?php cf_checkout_back( $shipping ? 'delivery' : 'details', $shipping ? __( 'Return to shipping', 'comfa' ) : __( 'Return to information', 'comfa' ) ); ?>
		</div>
	</section>
	<?php
}

add_action(
	'woocommerce_checkout_before_order_review_heading',
	function () {
		if ( cf_checkout_layout() ) {
			?>
			<button type="button" class="cf-checkout-summary-toggle" aria-controls="cf-checkout-summary" aria-expanded="true" hidden>
				<span><span class="cf-summary-show"><?php esc_html_e( 'Show order summary', 'comfa' ); ?></span><span class="cf-summary-hide"><?php esc_html_e( 'Hide order summary', 'comfa' ); ?></span></span><strong></strong>
			</button>
			<div id="cf-checkout-summary">
			<?php
		}
	}
);

add_action(
	'woocommerce_checkout_after_order_review',
	function () {
		if ( cf_checkout_layout() ) {
			?>
				<div class="cf-checkout-coupon" hidden>
					<label class="screen-reader-text" for="cf-checkout-code"><?php esc_html_e( 'Discount code', 'comfa' ); ?></label>
					<input id="cf-checkout-code" class="input-text" type="text" placeholder="<?php esc_attr_e( 'Discount code', 'comfa' ); ?>" autocomplete="off">
					<button type="button" class="button"><?php esc_html_e( 'Apply', 'comfa' ); ?></button>
				</div>
				<p class="cf-checkout-coupon-note" role="status" hidden></p>
				<?php echo cf_checkout_progress_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="cf-checkout-note"><?php esc_html_e( 'Need help with your order?', 'comfa' ); ?> <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'comfa' ); ?></a></p>
			</div>
			<?php
		}
	}
);

function cf_checkout_progress_html() {
	ob_start();
	cf_bag_progress();
	return '<div class="cf-checkout-progress">' . ob_get_clean() . '</div>';
}

add_filter(
	'woocommerce_update_order_review_fragments',
	function ( $fragments ) {
		$fragments['.cf-checkout-progress'] = cf_checkout_progress_html();
		return $fragments;
	}
);

add_action(
	'woocommerce_after_checkout_form',
	function () {
		if ( ! cf_checkout_layout() ) {
			return;
		}
		$links = array(
			get_privacy_policy_url() => __( 'Privacy Policy', 'comfa' ),
			wc_get_page_permalink( 'terms', '' ) => __( 'Terms of Service', 'comfa' ),
			home_url( '/contact/' ) => __( 'Contact', 'comfa' ),
		);
		echo '<nav class="cf-checkout-legal" aria-label="' . esc_attr__( 'Checkout policies', 'comfa' ) . '">';
		foreach ( $links as $url => $label ) {
			if ( $url ) {
				printf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $label ) );
			}
		}
		echo '</nav>';
	}
);

add_filter(
	'woocommerce_cart_item_name',
	function ( $name, $item ) {
		if ( ! doing_action( 'woocommerce_checkout_order_review' ) && ! doing_action( 'wc_ajax_update_order_review' ) ) {
			return $name;
		}
		return '<span class="cf-checkout-media">' . $item['data']->get_image( 'woocommerce_thumbnail', array( 'class' => 'cf-checkout-thumb', 'alt' => '' ) )
			. '<span class="cf-checkout-qty" aria-hidden="true">' . esc_html( $item['quantity'] ) . '</span></span>' . $name;
	},
	10,
	2
);
