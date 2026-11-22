<?php
/**
 * Accessible mobile archive filter sheet shell.
 */

defined( 'ABSPATH' ) || exit;

function bl_render_mobile_filter_sheet() {
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}
	?>
	<div id="bl-mobile-filter-panel" class="bl-mobile-filter-panel" role="dialog" aria-modal="true" aria-labelledby="bl-mobile-filter-title" hidden>
		<div class="bl-mobile-filter-backdrop" data-mobile-filter-close></div>
		<section class="bl-mobile-filter-sheet" tabindex="-1">
			<div class="bl-mobile-filter-handle" aria-hidden="true"></div>
			<header class="bl-mobile-filter-header">
				<h2 id="bl-mobile-filter-title">Filter &amp; Sort</h2>
				<button type="button" class="bl-mobile-filter-close" aria-label="Close filters">×</button>
			</header>
			<div class="bl-mobile-filter-content"></div>
			<footer class="bl-mobile-filter-footer">
				<button type="button" class="bl-mobile-filter-clear">Clear all</button>
				<button type="button" class="button bl-mobile-filter-apply">Apply</button>
			</footer>
		</section>
	</div>
	<?php
}
add_action( 'wp_footer', 'bl_render_mobile_filter_sheet', 8 );
