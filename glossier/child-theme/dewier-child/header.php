<?php
/**
 * Site header: promo bar, logo, navigation, utilities.
 *
 * @package dewier
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php do_action( 'shoptimizer_before' ); ?>
<div id="page" class="hfeed site">
	<?php do_action( 'shoptimizer_before_site' ); ?>

	<?php if ( dewier_is_checkout_flow() ) : ?>
	<header id="masthead" class="dw-header dw-header--checkout">
		<div class="dw-header__inner">
			<a class="dw-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?><span aria-hidden="true">.</span>
			</a>
			<p class="dw-header__secure"><?php echo dewier_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Secure checkout', 'dewier' ); ?></span></p>
			<a class="dw-header__bag" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Bag', 'dewier' ); ?></a>
		</div>
	</header>
	<?php else : ?>
	<?php dewier_promo_bar(); ?>

	<header id="masthead" class="dw-header">
		<div class="dw-header__inner">
			<button class="dw-header__toggle" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menu', 'dewier' ); ?>">
				<?php echo dewier_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
			<a class="dw-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?><span aria-hidden="true">.</span>
			</a>
			<nav id="site-navigation" class="dw-nav" aria-label="<?php esc_attr_e( 'Primary', 'dewier' ); ?>">
				<button class="dw-nav__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'dewier' ); ?>"><?php echo dewier_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'dw-nav__list',
						'depth'          => 2,
						'fallback_cb'    => 'dewier_nav_fallback',
					)
				);
				do_action( 'dewier_nav_after' );
				?>
			</nav>
			<div class="dw-utils">
				<button class="dw-utils__search" type="button" aria-controls="dw-search" aria-expanded="false" aria-label="<?php esc_attr_e( 'Search', 'dewier' ); ?>">
					<?php echo dewier_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<?php do_action( 'dewier_header_utils' ); ?>
				<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<a class="dw-utils__link dw-utils__link--account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
						<?php echo is_user_logged_in() ? esc_html__( 'Account', 'dewier' ) : esc_html__( 'Log in', 'dewier' ); ?>
					</a>
					<?php shoptimizer_cart_link(); ?>
				<?php endif; ?>
			</div>
		</div>
		<div id="dw-search" class="dw-search" hidden>
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="dw-search-field"><?php esc_html_e( 'Search products', 'dewier' ); ?></label>
				<input id="dw-search-field" type="search" name="s" placeholder="<?php esc_attr_e( 'Search', 'dewier' ); ?>" autocomplete="off">
				<input type="hidden" name="post_type" value="product">
				<button class="dw-btn dw-btn--primary" type="submit"><span><?php esc_html_e( 'Search', 'dewier' ); ?></span></button>
			</form>
		</div>
	</header>
	<div class="dw-overlay" hidden></div>
	<?php endif; ?>

	<?php do_action( 'shoptimizer_before_content' ); ?>

	<div id="content" class="site-content" tabindex="-1">
		<div class="shoptimizer-archive">
		<?php do_action( 'shoptimizer_before_col_full' ); ?>
		<div class="col-full">
		<?php do_action( 'shoptimizer_after_col_full_open' ); ?>
