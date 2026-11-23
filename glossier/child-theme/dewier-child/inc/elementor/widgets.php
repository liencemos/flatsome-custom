<?php
/**
 * Elementor widget classes.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * Widgets declare their controls as a list of field definitions.
 */
abstract class Dewier_Widget extends Widget_Base {

	abstract protected function fields();

	public function get_categories() {
		return array( 'dewier' );
	}

	public function get_icon() {
		return 'eicon-woocommerce';
	}

	protected function add_field( $target, $field ) {
		$types = array(
			'text'     => Controls_Manager::TEXT,
			'textarea' => Controls_Manager::TEXTAREA,
			'url'      => Controls_Manager::URL,
			'image'    => Controls_Manager::MEDIA,
			'color'    => Controls_Manager::COLOR,
			'number'   => Controls_Manager::NUMBER,
			'select'   => Controls_Manager::SELECT,
			'wysiwyg'  => Controls_Manager::WYSIWYG,
		);
		$args  = array(
			'label'       => $field['label'],
			'type'        => $types[ $field['type'] ],
			'label_block' => in_array( $field['type'], array( 'text', 'textarea', 'url', 'wysiwyg' ), true ),
		);
		if ( isset( $field['default'] ) ) {
			$args['default'] = 'url' === $field['type'] ? array( 'url' => $field['default'] ) : ( 'image' === $field['type'] ? array( 'url' => $field['default'] ) : $field['default'] );
		}
		if ( isset( $field['options'] ) ) {
			$args['options'] = $field['options'];
		}
		$target->add_control( $field['id'], $args );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'dewier' ) ) );
		foreach ( $this->fields() as $field ) {
			if ( 'repeater' === $field['type'] ) {
				$repeater = new Repeater();
				foreach ( $field['fields'] as $sub ) {
					$this->add_field( $repeater, $sub );
				}
				$this->add_control(
					$field['id'],
					array(
						'label'       => $field['label'],
						'type'        => Controls_Manager::REPEATER,
						'fields'      => $repeater->get_controls(),
						'default'     => $field['default'],
						'title_field' => '{{{ ' . $field['title'] . ' }}}',
					)
				);
				continue;
			}
			$this->add_field( $this, $field );
		}
		$this->end_controls_section();
	}

	protected static function url( $value, $fallback = '#' ) {
		$url = is_array( $value ) ? ( isset( $value['url'] ) ? $value['url'] : '' ) : (string) $value;
		return $url ? $url : $fallback;
	}

	protected static function image( $value, $alt = '' ) {
		$url = self::url( $value, '' );
		if ( ! $url ) {
			return '';
		}
		if ( is_array( $value ) && ! empty( $value['id'] ) ) {
			return wp_get_attachment_image( $value['id'], 'large', false, array( 'alt' => $alt, 'loading' => 'lazy' ) );
		}
		return sprintf( '<img src="%s" alt="%s" loading="lazy">', esc_url( $url ), esc_attr( $alt ) );
	}

	protected static function asset( $file ) {
		return DEWIER_URI . '/assets/images/' . $file;
	}

	protected static function shop_url() {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}
}

class Dewier_Widget_Hero extends Dewier_Widget {

	public function get_name() {
		return 'dewier-hero';
	}

	public function get_title() {
		return __( 'Dewier Hero', 'dewier' );
	}

	protected function fields() {
		return array(
			array( 'id' => 'eyebrow', 'label' => __( 'Eyebrow', 'dewier' ), 'type' => 'text', 'default' => __( 'Black Friday sale', 'dewier' ) ),
			array( 'id' => 'title', 'label' => __( 'Title', 'dewier' ), 'type' => 'text', 'default' => __( 'Biggest sale of the year!', 'dewier' ) ),
			array( 'id' => 'text', 'label' => __( 'Text', 'dewier' ), 'type' => 'textarea', 'default' => __( 'Plus, unlock 30% off and a free Barrier Cream with your $100+ order.', 'dewier' ) ),
			array( 'id' => 'button', 'label' => __( 'Button label', 'dewier' ), 'type' => 'text', 'default' => __( 'Shop now', 'dewier' ) ),
			array( 'id' => 'link', 'label' => __( 'Link', 'dewier' ), 'type' => 'url', 'default' => self::shop_url() ),
			array( 'id' => 'big', 'label' => __( 'Large figure', 'dewier' ), 'type' => 'text', 'default' => '25%' ),
			array( 'id' => 'suffix', 'label' => __( 'Figure suffix', 'dewier' ), 'type' => 'text', 'default' => __( 'off', 'dewier' ) ),
			array( 'id' => 'sub', 'label' => __( 'Line under figure', 'dewier' ), 'type' => 'text', 'default' => __( 'Everything*', 'dewier' ) ),
			array( 'id' => 'badge', 'label' => __( 'Round badge', 'dewier' ), 'type' => 'text', 'default' => __( '30% off', 'dewier' ) ),
			array( 'id' => 'badge_note', 'label' => __( 'Badge note', 'dewier' ), 'type' => 'text', 'default' => '$100+*' ),
			array( 'id' => 'wordmark', 'label' => __( 'Wordmark', 'dewier' ), 'type' => 'text', 'default' => get_bloginfo( 'name' ) . '.' ),
		);
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<section class="dw-hero">
			<div class="dw-hero__card">
				<p class="dw-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
				<h2 class="dw-hero__title"><?php echo esc_html( $s['title'] ); ?></h2>
				<p class="dw-hero__text"><?php echo esc_html( $s['text'] ); ?></p>
				<?php
				if ( $s['button'] ) {
					echo dewier_button( $s['button'], self::url( $s['link'] ), 'conversion' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<div class="dw-hero__art" aria-hidden="true">
				<?php if ( $s['badge'] ) : ?>
					<span class="dw-hero__badge"><b><?php echo esc_html( $s['badge'] ); ?></b><i><?php echo esc_html( $s['badge_note'] ); ?></i></span>
				<?php endif; ?>
				<span class="dw-hero__big"><?php echo esc_html( $s['big'] ); ?><sup><?php echo esc_html( $s['suffix'] ); ?></sup></span>
				<span class="dw-hero__sub"><?php echo esc_html( $s['sub'] ); ?></span>
				<span class="dw-hero__wordmark"><?php echo esc_html( $s['wordmark'] ); ?></span>
			</div>
		</section>
		<?php
	}
}

class Dewier_Widget_Gift extends Dewier_Widget {

	public function get_name() {
		return 'dewier-gift';
	}

	public function get_title() {
		return __( 'Dewier Gift Strip', 'dewier' );
	}

	protected function fields() {
		return array(
			array( 'id' => 'image', 'label' => __( 'Image', 'dewier' ), 'type' => 'image', 'default' => self::asset( 'gift.jpg' ) ),
			array( 'id' => 'title', 'label' => __( 'Title', 'dewier' ), 'type' => 'text', 'default' => __( 'Free Barrier Cream!', 'dewier' ) ),
			array( 'id' => 'text', 'label' => __( 'Text', 'dewier' ), 'type' => 'text', 'default' => __( 'with a purchase of $100+.', 'dewier' ) ),
			array( 'id' => 'link_label', 'label' => __( 'Link label', 'dewier' ), 'type' => 'text', 'default' => __( 'Terms apply', 'dewier' ) ),
			array( 'id' => 'link', 'label' => __( 'Link', 'dewier' ), 'type' => 'url', 'default' => self::shop_url() ),
		);
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<section class="dw-gift">
			<div class="dw-gift__img"><?php echo self::image( $s['image'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div>
				<h2 class="dw-gift__title"><?php echo esc_html( $s['title'] ); ?></h2>
				<p class="dw-gift__text"><?php echo esc_html( $s['text'] ); ?> <?php if ( $s['link_label'] ) : ?><a class="dw-link" href="<?php echo esc_url( self::url( $s['link'] ) ); ?>"><?php echo esc_html( $s['link_label'] ); ?></a>.<?php endif; ?></p>
			</div>
		</section>
		<?php
	}
}

class Dewier_Widget_Products extends Dewier_Widget {

	public function get_name() {
		return 'dewier-products';
	}

	public function get_title() {
		return __( 'Dewier Product Carousel', 'dewier' );
	}

	protected function fields() {
		$cats = array( '' => __( 'All categories', 'dewier' ) );
		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
			foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
				$cats[ $term->slug ] = $term->name;
			}
		}
		return array(
			array( 'id' => 'eyebrow', 'label' => __( 'Heading', 'dewier' ), 'type' => 'text', 'default' => __( 'Shop all', 'dewier' ) ),
			array( 'id' => 'button', 'label' => __( 'Button label', 'dewier' ), 'type' => 'text', 'default' => __( 'Shop now', 'dewier' ) ),
			array( 'id' => 'link', 'label' => __( 'Link', 'dewier' ), 'type' => 'url', 'default' => self::shop_url() ),
			array(
				'id'      => 'source',
				'label'   => __( 'Products', 'dewier' ),
				'type'    => 'select',
				'default' => 'featured',
				'options' => array(
					'featured' => __( 'Featured', 'dewier' ),
					'latest'   => __( 'Latest', 'dewier' ),
					'sale'     => __( 'On sale', 'dewier' ),
				),
			),
			array( 'id' => 'category', 'label' => __( 'Category', 'dewier' ), 'type' => 'select', 'default' => '', 'options' => $cats ),
			array( 'id' => 'count', 'label' => __( 'Number of products', 'dewier' ), 'type' => 'number', 'default' => 10 ),
		);
	}

	protected function render() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return;
		}
		$s    = $this->get_settings_for_display();
		$args = array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'limit'      => max( 1, (int) $s['count'] ),
			'orderby'    => 'latest' === $s['source'] ? 'date' : 'menu_order',
			'order'      => 'latest' === $s['source'] ? 'DESC' : 'ASC',
		);
		if ( 'featured' === $s['source'] ) {
			$args['featured'] = true;
		}
		if ( 'sale' === $s['source'] ) {
			$args['include'] = wc_get_product_ids_on_sale() ? wc_get_product_ids_on_sale() : array( 0 );
		}
		if ( $s['category'] ) {
			$args['category'] = array( $s['category'] );
		}
		$products = wc_get_products( $args );
		if ( ! $products ) {
			return;
		}
		?>
		<section class="dw-section dw-products">
			<div class="dw-section__head">
				<h2 class="dw-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></h2>
				<?php
				if ( $s['button'] ) {
					echo dewier_button( $s['button'], self::url( $s['link'] ), 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<?php
			dewier_carousel_open( 'products', $s['eyebrow'] );
			echo '<ul class="products dw-carousel__list">';
			foreach ( $products as $item ) {
				$GLOBALS['post']    = get_post( $item->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				$GLOBALS['product'] = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $GLOBALS['post'] );
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			echo '</ul>';
			dewier_carousel_close();
			?>
		</section>
		<?php
	}
}

class Dewier_Widget_Promo_Cards extends Dewier_Widget {

	public function get_name() {
		return 'dewier-promo-cards';
	}

	public function get_title() {
		return __( 'Dewier Promo Cards', 'dewier' );
	}

	protected function fields() {
		return array(
			array(
				'id'      => 'cards',
				'label'   => __( 'Cards', 'dewier' ),
				'type'    => 'repeater',
				'title'   => 'big + " " + caption',
				'fields'  => array(
					array( 'id' => 'big', 'label' => __( 'Large figure', 'dewier' ), 'type' => 'text', 'default' => '30%' ),
					array( 'id' => 'suffix', 'label' => __( 'Figure suffix', 'dewier' ), 'type' => 'text', 'default' => __( 'off', 'dewier' ) ),
					array( 'id' => 'caption', 'label' => __( 'Caption', 'dewier' ), 'type' => 'text', 'default' => __( 'All sets', 'dewier' ) ),
					array( 'id' => 'button', 'label' => __( 'Button label', 'dewier' ), 'type' => 'text', 'default' => __( 'Save now', 'dewier' ) ),
					array( 'id' => 'link', 'label' => __( 'Link', 'dewier' ), 'type' => 'url', 'default' => self::shop_url() ),
				),
				'default' => array(
					array( 'big' => '30%', 'suffix' => __( 'off', 'dewier' ), 'caption' => __( 'All sets', 'dewier' ), 'button' => __( 'Save now', 'dewier' ), 'link' => array( 'url' => home_url( '/product-category/sets/' ) ) ),
					array( 'big' => '25%', 'suffix' => __( 'off', 'dewier' ), 'caption' => __( 'All new + future subscriptions', 'dewier' ), 'button' => __( 'Subscribe now', 'dewier' ), 'link' => array( 'url' => self::shop_url() ) ),
				),
			),
		);
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo '<section class="dw-section dw-promos">';
		foreach ( (array) $s['cards'] as $card ) {
			?>
			<a class="dw-promos__card" href="<?php echo esc_url( self::url( $card['link'] ) ); ?>">
				<span class="dw-promos__big" aria-hidden="true"><?php echo esc_html( $card['big'] ); ?><sup><?php echo esc_html( $card['suffix'] ); ?></sup></span>
				<span class="dw-promos__caption"><span class="screen-reader-text"><?php echo esc_html( $card['big'] . ' ' . $card['suffix'] ); ?> </span><?php echo esc_html( $card['caption'] ); ?></span>
				<?php if ( $card['button'] ) : ?>
					<span class="dw-btn dw-btn--primary"><span><?php echo esc_html( $card['button'] ); ?></span></span>
				<?php endif; ?>
			</a>
			<?php
		}
		echo '</section>';
	}
}

class Dewier_Widget_Story extends Dewier_Widget {

	public function get_name() {
		return 'dewier-story';
	}

	public function get_title() {
		return __( 'Dewier Story', 'dewier' );
	}

	protected function fields() {
		return array(
			array( 'id' => 'eyebrow', 'label' => __( 'Eyebrow', 'dewier' ), 'type' => 'text', 'default' => __( 'Feeling cheerful', 'dewier' ) ),
			array( 'id' => 'text', 'label' => __( 'Text', 'dewier' ), 'type' => 'textarea', 'default' => __( 'Get inspired, cross everyone off your list and treat yourself while you are at it with our brand new holiday must-haves!', 'dewier' ) ),
			array( 'id' => 'button', 'label' => __( 'Button label', 'dewier' ), 'type' => 'text', 'default' => __( 'Shop now', 'dewier' ) ),
			array( 'id' => 'link', 'label' => __( 'Link', 'dewier' ), 'type' => 'url', 'default' => self::shop_url() ),
			array( 'id' => 'image', 'label' => __( 'Image', 'dewier' ), 'type' => 'image', 'default' => self::asset( 'story.jpg' ) ),
			array( 'id' => 'wordmark', 'label' => __( 'Text over image', 'dewier' ), 'type' => 'text', 'default' => get_bloginfo( 'name' ) . '.' ),
		);
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<section class="dw-section dw-story">
			<div class="dw-story__copy">
				<p class="dw-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
				<p class="dw-story__text"><?php echo esc_html( $s['text'] ); ?></p>
				<?php
				if ( $s['button'] ) {
					echo dewier_button( $s['button'], self::url( $s['link'] ), 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<a class="dw-story__media" href="<?php echo esc_url( self::url( $s['link'] ) ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo self::image( $s['image'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( $s['wordmark'] ) : ?>
					<span class="dw-story__wordmark"><?php echo esc_html( $s['wordmark'] ); ?></span>
				<?php endif; ?>
			</a>
		</section>
		<?php
	}
}

class Dewier_Widget_Carousel extends Dewier_Widget {

	public function get_name() {
		return 'dewier-carousel';
	}

	public function get_title() {
		return __( 'Dewier Image Carousel', 'dewier' );
	}

	protected function fields() {
		$tiles = array();
		foreach ( array( 'fragrance' => 'Fragrance', 'makeup' => 'Makeup', 'lip' => 'Lip', 'skincare' => 'Skincare', 'sets' => 'Sets', 'balms' => 'Balms', 'body' => 'Body' ) as $slug => $label ) {
			$tiles[] = array( 'image' => array( 'url' => self::asset( 'tile-' . $slug . '.jpg' ) ), 'label' => $label, 'link' => array( 'url' => home_url( '/product-category/' . $slug . '/' ) ) );
		}
		return array(
			array( 'id' => 'eyebrow', 'label' => __( 'Heading', 'dewier' ), 'type' => 'text', 'default' => '' ),
			array(
				'id'      => 'size',
				'label'   => __( 'Tile size', 'dewier' ),
				'type'    => 'select',
				'default' => 'tiles',
				'options' => array(
					'tiles' => __( 'Five across, with labels', 'dewier' ),
					'large' => __( 'Three across', 'dewier' ),
				),
			),
			array(
				'id'      => 'items',
				'label'   => __( 'Images', 'dewier' ),
				'type'    => 'repeater',
				'title'   => 'label',
				'fields'  => array(
					array( 'id' => 'image', 'label' => __( 'Image', 'dewier' ), 'type' => 'image', 'default' => self::asset( 'tile-makeup.jpg' ) ),
					array( 'id' => 'label', 'label' => __( 'Label', 'dewier' ), 'type' => 'text', 'default' => '' ),
					array( 'id' => 'link', 'label' => __( 'Link', 'dewier' ), 'type' => 'url', 'default' => self::shop_url() ),
				),
				'default' => $tiles,
			),
		);
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$large = 'large' === $s['size'];
		echo '<section class="dw-section dw-gallery">';
		if ( $s['eyebrow'] ) {
			echo '<div class="dw-section__head"><h2 class="dw-eyebrow">' . esc_html( $s['eyebrow'] ) . '</h2></div>';
		}
		dewier_carousel_open( $large ? 'large' : 'tiles', $s['eyebrow'] ? $s['eyebrow'] : __( 'Categories', 'dewier' ) );
		echo '<ul class="dw-carousel__list">';
		foreach ( (array) $s['items'] as $item ) {
			printf(
				'<li class="dw-gallery__item"><a href="%s"><span class="dw-gallery__img">%s</span>%s</a></li>',
				esc_url( self::url( $item['link'] ) ),
				self::image( $item['image'], $item['label'] ), // phpcs:ignore WordPress.Security.EscapeOutput
				$item['label'] && ! $large ? '<span class="dw-eyebrow">' . esc_html( $item['label'] ) . '</span>' : ''
			);
		}
		echo '</ul>';
		dewier_carousel_close();
		echo '</section>';
	}
}

class Dewier_Widget_Help_Search extends Dewier_Widget {

	public function get_name() {
		return 'dewier-help-search';
	}

	public function get_title() {
		return __( 'Dewier Help Search', 'dewier' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	protected function fields() {
		return array(
			array( 'id' => 'title', 'label' => __( 'Title', 'dewier' ), 'type' => 'text', 'default' => __( 'How can we help?', 'dewier' ) ),
			array( 'id' => 'placeholder', 'label' => __( 'Search placeholder', 'dewier' ), 'type' => 'text', 'default' => __( 'Search', 'dewier' ) ),
			array( 'id' => 'empty', 'label' => __( 'No results text', 'dewier' ), 'type' => 'text', 'default' => __( 'No results found', 'dewier' ) ),
			array( 'id' => 'background', 'label' => __( 'Background', 'dewier' ), 'type' => 'color', 'default' => '#f5dbdf' ),
		);
	}

	/**
	 * The search suggests the questions of the Dewier FAQ widgets on the same page.
	 */
	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = 'dw-help-' . $this->get_id();
		?>
		<section class="dw-help" style="background-color:<?php echo esc_attr( $s['background'] ); ?>">
			<h1 class="dw-help__title"><?php echo esc_html( $s['title'] ); ?></h1>
			<?php /* translators: %d: number of questions */ ?>
			<form class="dw-help__search" role="search" data-empty="<?php echo esc_attr( $s['empty'] ); ?>" data-count="<?php esc_attr_e( '%d matching questions', 'dewier' ); ?>">
				<span class="dw-help__icon"><?php echo dewier_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<input class="dw-help__input" id="<?php echo esc_attr( $id ); ?>" type="text" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="<?php echo esc_attr( $id ); ?>-results" aria-label="<?php echo esc_attr( $s['placeholder'] ); ?>" placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>">
				<button type="button" class="dw-help__clear" aria-label="<?php esc_attr_e( 'Clear search', 'dewier' ); ?>" hidden><?php echo dewier_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<ul class="dw-help__results" id="<?php echo esc_attr( $id ); ?>-results" role="listbox" aria-label="<?php esc_attr_e( 'Matching questions', 'dewier' ); ?>" hidden></ul>
				<span class="screen-reader-text dw-help__status" role="status"></span>
			</form>
		</section>
		<?php
	}
}

class Dewier_Widget_Faq extends Dewier_Widget {

	public function get_name() {
		return 'dewier-faq';
	}

	public function get_title() {
		return __( 'Dewier FAQ', 'dewier' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	protected function fields() {
		$items = array();
		foreach ( require DEWIER_DIR . '/inc/elementor/faq-defaults.php' as $item ) {
			$items[] = array( 'topic' => $item[0], 'question' => $item[1], 'answer' => $item[2] );
		}
		return array(
			array( 'id' => 'heading', 'label' => __( 'Heading', 'dewier' ), 'type' => 'text', 'default' => __( 'FAQs', 'dewier' ) ),
			array( 'id' => 'jump', 'label' => __( 'Topic menu label (mobile)', 'dewier' ), 'type' => 'text', 'default' => __( 'Go to category...', 'dewier' ) ),
			array(
				'id'      => 'items',
				'label'   => __( 'Questions', 'dewier' ),
				'type'    => 'repeater',
				'title'   => 'question',
				'fields'  => array(
					array( 'id' => 'topic', 'label' => __( 'Topic (questions with the same topic are grouped)', 'dewier' ), 'type' => 'text', 'default' => __( 'Shipping', 'dewier' ) ),
					array( 'id' => 'question', 'label' => __( 'Question', 'dewier' ), 'type' => 'text', 'default' => '' ),
					array( 'id' => 'answer', 'label' => __( 'Answer', 'dewier' ), 'type' => 'wysiwyg', 'default' => '' ),
				),
				'default' => $items,
			),
		);
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$topics = array();
		foreach ( (array) $s['items'] as $item ) {
			if ( '' === trim( (string) $item['question'] ) ) {
				continue;
			}
			$topics[ trim( (string) $item['topic'] ) ][] = $item;
		}
		if ( ! $topics ) {
			return;
		}
		$anchors = array();
		foreach ( array_keys( $topics ) as $topic ) {
			$anchors[ $topic ] = 'topic-' . ( sanitize_title( $topic ) ? sanitize_title( $topic ) : count( $anchors ) + 1 );
		}
		$named = array_filter( array_keys( $topics ), 'strlen' );
		$jump  = 'dw-faq-jump-' . $this->get_id();
		?>
		<section class="dw-faq">
			<?php if ( $s['heading'] ) : ?>
				<h2 class="dw-faq__heading"><?php echo esc_html( $s['heading'] ); ?></h2>
			<?php endif; ?>
			<div class="dw-faq__layout<?php echo $named ? '' : ' dw-faq__layout--plain'; ?>">
				<?php if ( $named ) : ?>
					<nav class="dw-faq__nav" aria-label="<?php esc_attr_e( 'FAQ topics', 'dewier' ); ?>">
						<ul>
							<?php foreach ( $named as $topic ) : ?>
								<li><a href="#<?php echo esc_attr( $anchors[ $topic ] ); ?>"><?php echo esc_html( $topic ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
					<div class="dw-faq__jump">
						<select class="dw-faq__select" id="<?php echo esc_attr( $jump ); ?>" aria-label="<?php echo esc_attr( $s['jump'] ); ?>">
							<option value=""><?php echo esc_html( $s['jump'] ); ?></option>
							<?php foreach ( $named as $topic ) : ?>
								<option value="<?php echo esc_attr( $anchors[ $topic ] ); ?>"><?php echo esc_html( $topic ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="dw-faq__list">
					<?php foreach ( $topics as $topic => $items ) : ?>
						<section class="dw-faq__topic" id="<?php echo esc_attr( $anchors[ $topic ] ); ?>">
							<?php if ( '' !== $topic ) : ?>
								<h3 class="dw-faq__topic-title"><?php echo esc_html( $topic ); ?></h3>
							<?php endif; ?>
							<div class="dw-faq__items">
								<?php
								foreach ( $items as $item ) :
									$id = 'faq-' . ( empty( $item['_id'] ) ? substr( md5( $topic . $item['question'] ), 0, 7 ) : $item['_id'] );
									?>
									<div class="dw-faq__item" id="<?php echo esc_attr( $id ); ?>">
										<h4 class="dw-faq__question">
											<button type="button" class="dw-faq__q" id="<?php echo esc_attr( $id ); ?>-q" aria-expanded="false" aria-controls="<?php echo esc_attr( $id ); ?>-a">
												<span><?php echo esc_html( $item['question'] ); ?></span>
												<?php echo dewier_icon( 'down' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											</button>
										</h4>
										<div class="dw-faq__a" id="<?php echo esc_attr( $id ); ?>-a">
											<div class="dw-faq__answer"><?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
			<noscript><style>.dw-faq .dw-faq__a{grid-template-rows:1fr;padding-top:1rem;visibility:visible}</style></noscript>
		</section>
		<?php
	}
}
