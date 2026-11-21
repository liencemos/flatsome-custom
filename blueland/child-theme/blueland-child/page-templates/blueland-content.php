<?php
/**
 * Template Name: Blueland Content
 *
 * @package Pureland_Child
 */

get_header();
$eyebrow = get_post_meta( get_the_ID(), '_bl_eyebrow', true );
?>
<main id="main" class="bl-content-page">
	<section class="bl-content-hero">
		<div class="bl-content-width">
			<?php if ( $eyebrow ) : ?>
				<p class="bl-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="bl-content-subtitle"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<div class="bl-content-body">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php the_content(); ?>
		<?php endwhile; ?>
	</div>
	<?php echo do_shortcode( '[block id="cta-journey"]' ); ?>
</main>
<?php
get_footer();
