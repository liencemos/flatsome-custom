<?php
/**
 * Product page: details column, gallery and editorial image.
 *
 * @package dewier
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

$dewier_images = array_values( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
$dewier_story  = count( $dewier_images ) > 1 ? $dewier_images[1] : 0;
$dewier_rating = (float) $product->get_average_rating();
$dewier_count  = (int) $product->get_review_count();
$dewier_facts  = apply_filters( 'dewier_product_facts', array( __( 'Buildable', 'dewier' ), __( 'Cruelty free', 'dewier' ), __( 'Easy', 'dewier' ) ), $product );
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'dw-pdp', $product ); ?> data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-price="<?php echo esc_attr( wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ) ); ?>">
	<?php do_action( 'dewier_pdp_before', $product ); ?>
	<div class="dw-pdp__grid">
		<div class="dw-pdp__info summary entry-summary">
			<h1 class="dw-pdp__title product_title entry-title"><?php the_title(); ?></h1>
			<div class="dw-pdp__meta">
				<span class="dw-pdp__sub"><?php echo esc_html( $product->get_meta( '_dewier_subtitle' ) ); ?></span>
				<?php if ( $dewier_count > 0 ) : ?>
					<a class="dw-pdp__rating" href="#reviews">
						<?php echo wc_get_rating_html( $dewier_rating, $dewier_count ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span>(<?php echo esc_html( number_format_i18n( $dewier_count ) ); ?>)</span>
					</a>
				<?php endif; ?>
			</div>
			<?php dewier_badges( $product ); ?>

			<div class="dw-pdp__buy">
				<?php woocommerce_template_single_add_to_cart(); ?>
				<?php do_action( 'dewier_pdp_after_buy', $product ); ?>
			</div>

			<?php if ( $product->get_short_description() ) : ?>
				<div class="dw-pdp__desc" data-collapsed="true">
					<div class="dw-pdp__desc-body" id="dw-desc"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
					<button type="button" class="dw-link" aria-expanded="false" aria-controls="dw-desc" data-more="<?php esc_attr_e( 'Show more', 'dewier' ); ?>" data-less="<?php esc_attr_e( 'Show less', 'dewier' ); ?>"><?php esc_html_e( 'Show more', 'dewier' ); ?></button>
				</div>
			<?php endif; ?>

			<?php if ( $dewier_facts ) : ?>
				<ul class="dw-pdp__facts">
					<?php foreach ( $dewier_facts as $dewier_fact ) : ?>
						<li><?php echo esc_html( $dewier_fact ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p class="dw-pdp__fine"><?php esc_html_e( 'Dermatologist tested, formulated without fragrance', 'dewier' ); ?></p>
			<?php endif; ?>

			<?php dewier_addons( $product ); ?>
		</div>

		<div class="dw-pdp__gallery">
			<div class="dw-pdp__stage">
				<?php foreach ( $dewier_images as $dewier_index => $dewier_image ) : ?>
					<figure class="dw-pdp__slide<?php echo 0 === $dewier_index ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr( $dewier_index ); ?>">
						<?php echo wp_get_attachment_image( $dewier_image, 'woocommerce_single', false, array( 'loading' => 0 === $dewier_index ? 'eager' : 'lazy' ) ); ?>
					</figure>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $dewier_images ) > 1 ) : ?>
				<div class="dw-pdp__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'dewier' ); ?>">
					<?php foreach ( $dewier_images as $dewier_index => $dewier_image ) : ?>
						<button type="button" role="tab" class="dw-pdp__thumb<?php echo 0 === $dewier_index ? ' is-active' : ''; ?>" aria-selected="<?php echo 0 === $dewier_index ? 'true' : 'false'; ?>" data-index="<?php echo esc_attr( $dewier_index ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Image %d', 'dewier' ), $dewier_index + 1 ) ); ?>">
							<?php echo wp_get_attachment_image( $dewier_image, 'woocommerce_gallery_thumbnail' ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="dw-pdp__story">
			<?php if ( $dewier_story ) : ?>
				<figure>
					<?php echo wp_get_attachment_image( $dewier_story, 'large' ); ?>
					<figcaption><?php echo esc_html( sprintf( /* translators: %s: product name */ __( '%s, up close.', 'dewier' ), $product->get_name() ) ); ?></figcaption>
				</figure>
			<?php endif; ?>
		</div>
	</div>

	<div class="dw-pdp__more">
		<?php dewier_accordions( $product ); ?>
		<div id="reviews-wrap" class="dw-pdp__reviews">
			<?php dewier_review_summary( $product ); ?>
			<?php comments_template(); ?>
		</div>
	</div>

	<?php
	woocommerce_upsell_display( 4, 4 );
	woocommerce_output_related_products();
	dewier_recent_slot( $product->get_id() );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
