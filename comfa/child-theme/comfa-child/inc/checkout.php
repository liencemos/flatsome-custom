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
		<div class="cf-checkout-intro">
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( '← Back to bag', 'comfa' ); ?></a>
			<h1><?php esc_html_e( 'Checkout', 'comfa' ); ?></h1>
			<span><?php esc_html_e( 'Secure checkout', 'comfa' ); ?></span>
		</div>
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
				<?php foreach ( $stages as $stage => $label ) : ?>
					<li><button type="button" data-cf-stage="<?php echo esc_attr( $stage ); ?>"><?php echo esc_html( $label ); ?></button></li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<section id="cf-checkout-details" class="cf-checkout-panel" aria-labelledby="cf-checkout-details-title">
			<h2 id="cf-checkout-details-title"><?php esc_html_e( 'Contact & address', 'comfa' ); ?></h2>
		<?php
	},
	100
);

function cf_checkout_shipping_panel() {
	$shipping = WC()->cart->needs_shipping();
	cf_checkout_continue( $shipping ? __( 'Continue to shipping', 'comfa' ) : __( 'Continue to payment', 'comfa' ) );
	echo '</section>';
	if ( ! $shipping ) {
		return;
	}
	?>
	<section id="cf-checkout-delivery" class="cf-checkout-panel" aria-labelledby="cf-checkout-delivery-title" hidden>
		<h2 id="cf-checkout-delivery-title"><?php esc_html_e( 'Shipping method', 'comfa' ); ?></h2>
		<table class="cf-checkout-rates"><tbody></tbody></table>
		<?php cf_checkout_continue( __( 'Continue to payment', 'comfa' ) ); ?>
	</section>
	<?php
}

function cf_checkout_continue( $label ) {
	printf( '<button type="button" class="button cf-checkout-next" hidden>%s <span aria-hidden="true">→</span></button>', esc_html( $label ) );
}

function cf_checkout_payment_panel() {
	?>
	<section id="cf-checkout-payment" class="cf-checkout-panel" aria-labelledby="cf-checkout-payment-title">
		<h2 id="cf-checkout-payment-title"><?php esc_html_e( 'Payment', 'comfa' ); ?></h2>
		<?php woocommerce_checkout_payment(); ?>
	</section>
	<?php
}

add_action(
	'woocommerce_checkout_before_order_review_heading',
	function () {
		if ( cf_checkout_layout() ) {
			?>
			<button type="button" class="cf-checkout-summary-toggle" aria-controls="cf-checkout-summary" aria-expanded="true" hidden>
				<span><?php esc_html_e( 'Order summary', 'comfa' ); ?></span><strong></strong>
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
				<p class="cf-checkout-note"><?php esc_html_e( 'Need help with your order?', 'comfa' ); ?> <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'comfa' ); ?></a></p>
			</div>
			<?php
		}
	}
);

add_filter(
	'woocommerce_cart_item_name',
	function ( $name, $item ) {
		if ( ! doing_action( 'woocommerce_checkout_order_review' ) && ! doing_action( 'wc_ajax_update_order_review' ) ) {
			return $name;
		}
		return $item['data']->get_image( 'woocommerce_thumbnail', array( 'class' => 'cf-checkout-thumb', 'alt' => '' ) ) . $name;
	},
	10,
	2
);
