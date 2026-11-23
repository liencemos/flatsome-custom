<?php
/**
 * Product card.
 *
 * @package dewier
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$dewier_options = dewier_get_options( $product );
$dewier_gallery = $product->get_gallery_image_ids();
$dewier_link    = $product->get_permalink();
?>
<li <?php wc_product_class( 'dw-card', $product ); ?>>
	<div class="dw-card__media">
		<?php dewier_badges( $product ); ?>
		<a class="dw-card__link" href="<?php echo esc_url( $dewier_link ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			echo $product->get_image( 'woocommerce_single', array( 'class' => 'dw-card__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( $dewier_gallery ) {
				echo wp_get_attachment_image( $dewier_gallery[0], 'woocommerce_single', false, array( 'class' => 'dw-card__img dw-card__img--hover', 'alt' => '', 'loading' => 'lazy' ) );
			}
			?>
		</a>
	</div>
	<div class="dw-card__info">
		<h3 class="dw-card__name"><a href="<?php echo esc_url( $dewier_link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<p class="dw-card__sub"><?php echo esc_html( $product->get_meta( '_dewier_subtitle' ) ); ?></p>
		<span class="price"><?php echo wp_kses_post( $dewier_options ? $dewier_options[0]['variation']->get_price_html() : $product->get_price_html() ); ?></span>
	</div>
	<?php dewier_card_options( $product, $dewier_options ); ?>
	<?php dewier_card_button( $product, $dewier_options ); ?>
</li>
