<?php
/**
 * WooCommerce settings page.
 *
 * @package Modern_Checkout_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

class MCWC_Settings extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'modern_checkout';
		$this->label = __( 'Modern Checkout', 'modern-checkout-for-woocommerce' );
		parent::__construct();
	}

	public static function init() {
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'woocommerce_admin_field_mcwc_media', array( __CLASS__, 'render_media_field' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'sanitize_option' ), 10, 3 );
	}

	public static function register_page( $pages ) {
		$pages[] = new self();
		return $pages;
	}

	public function get_settings_for_default_section() {
		$icons = array(
			'truck'  => __( 'Truck', 'modern-checkout-for-woocommerce' ),
			'return' => __( 'Return', 'modern-checkout-for-woocommerce' ),
			'lock'   => __( 'Lock', 'modern-checkout-for-woocommerce' ),
			'shield' => __( 'Shield', 'modern-checkout-for-woocommerce' ),
			'gift'   => __( 'Gift', 'modern-checkout-for-woocommerce' ),
			'leaf'   => __( 'Leaf', 'modern-checkout-for-woocommerce' ),
			'star'   => __( 'Star', 'modern-checkout-for-woocommerce' ),
		);

		$settings = array(
			array(
				'title' => __( 'Modern Checkout', 'modern-checkout-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'mcwc_general_options',
			),
			array(
				'title'   => __( 'Enable modern checkout', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_enabled',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Logo', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_logo_id',
				'type'    => 'mcwc_media',
				'default' => '',
			),
			array(
				'title'             => __( 'Logo max height (px)', 'modern-checkout-for-woocommerce' ),
				'id'                => 'mcwc_logo_max_height',
				'type'              => 'number',
				'default'           => 32,
				'custom_attributes' => array( 'min' => 8, 'max' => 160, 'step' => 1 ),
			),
			array(
				'title'   => __( 'Primary color', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_primary_color',
				'type'    => 'color',
				'default' => '#133cd1',
			),
			array(
				'title'   => __( 'Text color', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_text_color',
				'type'    => 'color',
				'default' => '#0b1057',
			),
			array(
				'title'   => __( 'Muted color', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_muted_color',
				'type'    => 'color',
				'default' => '#5b6190',
			),
			array(
				'title'   => __( 'Summary background', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_summary_background',
				'type'    => 'color',
				'default' => '#f0f7ff',
			),
			array(
				'title'   => __( 'Border color', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_border_color',
				'type'    => 'color',
				'default' => '#dfe3ef',
			),
			array(
				'title'   => __( 'Font', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_font',
				'type'    => 'select',
				'default' => 'DM Sans',
				'options' => array(
					'inherit' => __( 'Inherit', 'modern-checkout-for-woocommerce' ),
					'DM Sans' => 'DM Sans',
					'Inter'   => 'Inter',
					'Poppins' => 'Poppins',
					'Roboto'  => 'Roboto',
					'Lato'    => 'Lato',
				),
			),
			array(
				'title'             => __( 'Input radius (px)', 'modern-checkout-for-woocommerce' ),
				'id'                => 'mcwc_input_radius',
				'type'              => 'number',
				'default'           => 8,
				'custom_attributes' => array( 'min' => 0, 'max' => 48, 'step' => 1 ),
			),
			array(
				'title'             => __( 'Button radius (px)', 'modern-checkout-for-woocommerce' ),
				'id'                => 'mcwc_button_radius',
				'type'              => 'number',
				'default'           => 999,
				'custom_attributes' => array( 'min' => 0, 'max' => 999, 'step' => 1 ),
			),
			array(
				'title'   => __( 'Header right text', 'modern-checkout-for-woocommerce' ),
				'id'      => 'mcwc_header_right_text',
				'type'    => 'text',
				'default' => __( 'Secure Checkout', 'modern-checkout-for-woocommerce' ),
			),
			array(
				'title' => __( 'Trust item 1', 'modern-checkout-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'mcwc_trust_items',
			),
		);

		$defaults = array(
			array( __( 'Free shipping on $50+', 'modern-checkout-for-woocommerce' ), 'truck' ),
			array( __( '100-day returns', 'modern-checkout-for-woocommerce' ), 'return' ),
			array( __( 'Secure checkout', 'modern-checkout-for-woocommerce' ), 'lock' ),
		);
		foreach ( $defaults as $index => $default ) {
			$number     = $index + 1;
			$settings[] = array(
				/* translators: %d: Trust item number. */
				'title'   => sprintf( __( 'Trust item %d text', 'modern-checkout-for-woocommerce' ), $number ),
				'id'      => 'mcwc_trust_' . $number . '_text',
				'type'    => 'text',
				'default' => $default[0],
			);
			$settings[] = array(
				/* translators: %d: Trust item number. */
				'title'   => sprintf( __( 'Trust item %d icon', 'modern-checkout-for-woocommerce' ), $number ),
				'id'      => 'mcwc_trust_' . $number . '_icon',
				'type'    => 'select',
				'default' => $default[1],
				'options' => $icons,
			);
		}

		$settings[] = array(
			'title' => __( 'Checkout features', 'modern-checkout-for-woocommerce' ),
			'type'  => 'title',
			'id'    => 'mcwc_feature_options',
		);
		foreach (
			array(
				'mcwc_show_free_shipping' => __( 'Show free-shipping progress bar', 'modern-checkout-for-woocommerce' ),
				'mcwc_show_coupon'        => __( 'Show coupon field', 'modern-checkout-for-woocommerce' ),
				'mcwc_show_order_notes'   => __( 'Show order notes', 'modern-checkout-for-woocommerce' ),
				'mcwc_exclude_virtual'    => __( 'Exclude virtual products from the free-shipping bar', 'modern-checkout-for-woocommerce' ),
			) as $id => $label
		) {
			$settings[] = array(
				'title'   => $label,
				'id'      => $id,
				'type'    => 'checkbox',
				'default' => 'yes',
			);
		}
		$settings[] = array(
			'title'             => __( 'Free-shipping threshold override', 'modern-checkout-for-woocommerce' ),
			'desc'              => __( 'Leave blank to detect the threshold from an enabled free-shipping method.', 'modern-checkout-for-woocommerce' ),
			'id'                => 'mcwc_free_shipping_threshold_override',
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array( 'min' => 0, 'step' => '0.01' ),
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'mcwc_options_end',
		);

		return $settings;
	}

	public static function render_media_field( $field ) {
		$value = absint( get_option( $field['id'], $field['default'] ?? 0 ) );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label></th>
			<td class="forminp">
				<div class="mcwc-logo-preview"><?php echo $value ? wp_kses_post( wp_get_attachment_image( $value, 'thumbnail' ) ) : ''; ?></div>
				<input type="hidden" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
				<button type="button" class="button mcwc-select-logo"><?php esc_html_e( 'Choose image', 'modern-checkout-for-woocommerce' ); ?></button>
				<button type="button" class="button mcwc-remove-logo"><?php esc_html_e( 'Remove', 'modern-checkout-for-woocommerce' ); ?></button>
			</td>
		</tr>
		<?php
	}

	public static function sanitize_option( $value, $option, $raw_value ) {
		$id = is_array( $option ) && isset( $option['id'] ) ? $option['id'] : ( is_string( $option ) ? $option : '' );
		if ( 0 !== strpos( (string) $id, 'mcwc_' ) ) {
			return $value;
		}
		if ( 'mcwc_enabled' === $id || in_array( $id, array( 'mcwc_show_free_shipping', 'mcwc_show_coupon', 'mcwc_show_order_notes', 'mcwc_exclude_virtual' ), true ) ) {
			return 'yes' === $value ? 'yes' : 'no';
		}
		if ( in_array( $id, array( 'mcwc_primary_color', 'mcwc_text_color', 'mcwc_muted_color', 'mcwc_summary_background', 'mcwc_border_color' ), true ) ) {
			$color = sanitize_hex_color( $value );
			$defaults = array(
				'mcwc_primary_color'      => '#133cd1',
				'mcwc_text_color'         => '#0b1057',
				'mcwc_muted_color'        => '#5b6190',
				'mcwc_summary_background' => '#f0f7ff',
				'mcwc_border_color'       => '#dfe3ef',
			);
			$default  = is_array( $option ) && isset( $option['default'] ) ? $option['default'] : ( $defaults[ $id ] ?? '#000000' );
			return $color ? $color : $default;
		}
		if ( 'mcwc_logo_id' === $id ) {
			return absint( $value );
		}
		if ( 'mcwc_logo_max_height' === $id ) {
			return min( 160, max( 8, absint( $value ) ) );
		}
		if ( 'mcwc_input_radius' === $id ) {
			return min( 48, max( 0, absint( $value ) ) );
		}
		if ( 'mcwc_button_radius' === $id ) {
			return min( 999, max( 0, absint( $value ) ) );
		}
		if ( 'mcwc_free_shipping_threshold_override' === $id ) {
			if ( '' === trim( (string) $raw_value ) ) {
				return '';
			}
			return min( 100000, max( 0, (float) wc_format_decimal( $value ) ) );
		}
		if ( 'mcwc_font' === $id ) {
			return in_array( $value, array( 'inherit', 'DM Sans', 'Inter', 'Poppins', 'Roboto', 'Lato' ), true ) ? $value : 'DM Sans';
		}
		if ( preg_match( '/^mcwc_trust_[1-3]_icon$/', $id ) ) {
			return in_array( $value, array( 'truck', 'return', 'lock', 'shield', 'gift', 'leaf', 'star' ), true ) ? $value : 'lock';
		}
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		return $value;
	}

	public static function enqueue_admin_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of the current settings tab.
		if ( 'woocommerce_page_wc-settings' !== $hook || empty( $_GET['tab'] ) || 'modern_checkout' !== sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'mcwc-admin', MCWC_URL . 'assets/js/admin.js', array( 'jquery', 'media-editor', 'media-views' ), MCWC_VERSION, true );
		wp_localize_script(
			'mcwc-admin',
			'mcwcAdmin',
			array(
				'mediaTitle'  => __( 'Select checkout logo', 'modern-checkout-for-woocommerce' ),
				'mediaButton' => __( 'Use this logo', 'modern-checkout-for-woocommerce' ),
			)
		);
	}
}
