<?php
/**
 * Recently viewed products. The browser keeps the list, so cached pages stay shared.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_recent_routes() {
	register_rest_route(
		'dewier/v1',
		'/products',
		array(
			'methods'             => 'GET',
			'callback'            => 'dewier_recent_products',
			'permission_callback' => '__return_true',
			'args'                => array(
				'ids' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'dewier_recent_routes' );

/**
 * Product cards for the given IDs, in the given order.
 */
function dewier_product_cards( $ids ) {
	$html = '';
	foreach ( $ids as $id ) {
		$item = wc_get_product( $id );
		if ( ! $item || 'publish' !== $item->get_status() || ! $item->is_visible() || $item->is_type( 'variation' ) ) {
			continue;
		}
		$GLOBALS['post']    = get_post( $item->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['product'] = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		setup_postdata( $GLOBALS['post'] );
		ob_start();
		wc_get_template_part( 'content', 'product' );
		$html .= ob_get_clean();
	}
	wp_reset_postdata();
	return $html;
}

function dewier_recent_products( WP_REST_Request $request ) {
	$ids   = array_slice( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) ) ) ), 0, 8 );
	$cards = $ids ? dewier_product_cards( $ids ) : '';
	if ( ! $cards ) {
		return array( 'html' => '' );
	}
	ob_start();
	dewier_carousel_open( 'products', __( 'Recently viewed', 'dewier' ) );
	echo '<ul class="products dw-carousel__list">' . $cards . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
	dewier_carousel_close();
	return array( 'html' => ob_get_clean() );
}

/**
 * Empty section that the script fills from the browser's list.
 */
function dewier_recent_slot( $exclude = 0 ) {
	?>
	<section class="dw-section dw-products dw-recent" data-endpoint="<?php echo esc_url( rest_url( 'dewier/v1/products' ) ); ?>" data-exclude="<?php echo esc_attr( (int) $exclude ); ?>" aria-labelledby="dw-recent-title" hidden>
		<div class="dw-section__head">
			<h2 id="dw-recent-title" class="dw-eyebrow"><?php esc_html_e( 'Recently viewed', 'dewier' ); ?></h2>
			<button type="button" class="dw-link dw-recent__clear" data-recent-clear><?php esc_html_e( 'Clear', 'dewier' ); ?></button>
		</div>
		<div class="dw-recent__body"></div>
	</section>
	<?php
}

function dewier_recent_cart() {
	dewier_recent_slot();
}
add_action( 'woocommerce_after_cart', 'dewier_recent_cart' );
add_action( 'woocommerce_cart_is_empty', 'dewier_recent_cart', 30 );
