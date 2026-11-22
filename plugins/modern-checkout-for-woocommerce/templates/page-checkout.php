<?php
/**
 * Distraction-free document wrapper for the classic checkout.
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'mcwc-active' ); ?>>
<?php wp_body_open(); ?>
<div class="mcwc-page">
	<header class="mcwc-topbar">
		<a class="mcwc-topbar-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo wp_kses_post( mcwc_get_header_logo_html() ); ?>
		</a>
		<div class="mcwc-topbar-right">
			<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg>
			<span><?php echo esc_html( mcwc_option( 'header_right_text', __( 'Secure Checkout', 'modern-checkout-for-woocommerce' ) ) ); ?></span>
		</div>
		<?php do_action( 'mcwc_header_end' ); ?>
	</header>
	<main class="mcwc-content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
