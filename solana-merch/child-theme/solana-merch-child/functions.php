<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'solana-merch', get_stylesheet_directory_uri() . '/assets/storefront.css', array( 'flatsome-main' ), wp_get_theme()->get( 'Version' ) );
	if ( function_exists( 'is_product' ) && is_product() && get_post_meta( get_queried_object_id(), '_signal_demo', true ) ) {
		wp_enqueue_script( 'form-edit-swatches', get_stylesheet_directory_uri() . '/assets/swatches.js', array( 'wc-add-to-cart-variation' ), wp_get_theme()->get( 'Version' ), true );
	}
}, 100 );

add_action( 'woocommerce_single_product_summary', function () {
	$product = wc_get_product( get_the_ID() );
	if ( $product && get_post_meta( $product->get_id(), '_signal_demo', true ) ) {
		echo '<p class="edit-code edit-accent">FORM / EDIT &nbsp;—&nbsp; EDITION 001</p>';
	}
}, 4 );

add_filter( 'woocommerce_dropdown_variation_attribute_options_html', function ( $html, $args ) {
	$product = $args['product'];
	$name = sanitize_title( $args['attribute'] );
	if ( ! is_product() || ! $product || $product->get_id() !== get_queried_object_id() || ! $product->get_meta( '_signal_demo' ) || ! in_array( $name, array( 'size', 'color' ), true ) ) {
		return $html;
	}
	$options = $args['options'] ?: ( $product->get_variation_attributes()[ $name ] ?? array() );
	if ( ! $options ) {
		return $html;
	}
	$html .= '<div class="edit-swatches" data-attribute="' . esc_attr( $name ) . '" role="group" aria-label="' . esc_attr( wc_attribute_label( $name, $product ) ) . '" hidden>';
	foreach ( $options as $option ) {
		$html .= '<button type="button" class="edit-swatch" data-value="' . esc_attr( $option ) . '" aria-pressed="false">' . esc_html( $option ) . '</button>';
	}
	return $html . '</div>';
}, 20, 2 );

add_action( 'woocommerce_before_variations_form', function () {
	if ( is_product() && get_post_meta( get_queried_object_id(), '_form_edit_details', true ) ) {
		echo '<a class="edit-guide-link" href="#edit-size-guide">Size guide ↗</a>';
	}
} );

add_action( 'woocommerce_single_product_summary', function () {
	$details = get_post_meta( get_the_ID(), '_form_edit_details', true );
	if ( $details ) {
		echo do_shortcode( $details );
	}
}, 45 );

add_filter( 'woocommerce_product_upsells_products_heading', function ( $heading ) {
	return is_product() && get_post_meta( get_queried_object_id(), '_signal_demo', true ) ? 'Complete the look' : $heading;
} );

add_filter( 'flatsome_product_labels', function ( $html, $post, $product, $badge_style ) {
	if ( $product->is_in_stock() && $product->get_meta( '_form_edit_new' ) ) {
		$html .= '<div class="badge callout badge-' . esc_attr( $badge_style ) . '"><div class="badge-inner is-small edit-new-badge">New</div></div>';
	}
	return $html;
}, 15, 4 );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'id'            => 'form-edit-recent',
		'name'          => 'FORM / EDIT: Product discovery',
		'before_widget' => '<section id="%1$s" class="product-section edit-recent %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="product-section-title">',
		'after_title'   => '</h2>',
	) );
} );

add_action( 'woocommerce_after_single_product_summary', function () {
	dynamic_sidebar( 'form-edit-recent' );
}, 25 );

add_filter( 'woocommerce_recently_viewed_products_widget_query_args', function ( $args ) {
	if ( is_product() ) {
		$args['post__in'] = array_diff( $args['post__in'], array( get_queried_object_id() ) );
		$args['post__in'] = $args['post__in'] ?: array( 0 );
	}
	return $args;
} );

add_shortcode( 'form_edit_newsletter', function () {
	$status = isset( $_GET['studio_letter'] ) && is_string( $_GET['studio_letter'] ) ? sanitize_key( $_GET['studio_letter'] ) : '';
	ob_start();
	?>
	<form class="edit-letter-form" id="studio-letter" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="form_edit_newsletter">
		<?php wp_nonce_field( 'form_edit_newsletter', 'studio_letter_nonce', false ); ?>
		<label for="studio-letter-email">Email address</label>
		<div class="edit-letter-fields">
			<input id="studio-letter-email" type="email" name="email" required autocomplete="email" maxlength="254">
			<button class="button primary" type="submit">Request signup</button>
		</div>
		<p class="edit-letter-note">Submitting asks the store to add your email to the studio mailing list.</p>
		<?php if ( 'sent' === $status ) : ?>
			<p role="status">Thanks. Your request was sent to the studio.</p>
		<?php elseif ( 'error' === $status ) : ?>
			<p role="status">We couldn’t send your request. Please try again later.</p>
		<?php endif; ?>
	</form>
	<?php
	return ob_get_clean();
} );

function form_edit_request_newsletter() {
	check_admin_referer( 'form_edit_newsletter', 'studio_letter_nonce' );
	$email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$sent = is_email( $email ) && wp_mail( get_option( 'admin_email' ), 'FORM / EDIT: studio letter signup request', 'Requested signup: ' . $email );
	wp_safe_redirect( add_query_arg( 'studio_letter', $sent ? 'sent' : 'error', home_url( '/' ) ) . '#studio-letter' );
	exit;
}
add_action( 'admin_post_form_edit_newsletter', 'form_edit_request_newsletter' );
add_action( 'admin_post_nopriv_form_edit_newsletter', 'form_edit_request_newsletter' );
