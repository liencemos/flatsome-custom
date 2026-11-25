<?php
/**
 * Routine quiz: four questions, then product picks with a matched shade.
 *
 * @package dewier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dewier_quiz_questions() {
	return apply_filters(
		'dewier_quiz_questions',
		array(
			'goal' => array(
				'title'   => __( 'What are you shopping for?', 'dewier' ),
				'options' => array(
					'skincare'  => __( 'Skincare', 'dewier' ),
					'makeup'    => __( 'Makeup', 'dewier' ),
					'lip'       => __( 'Lips + balms', 'dewier' ),
					'fragrance' => __( 'Fragrance + body', 'dewier' ),
					'all'       => __( 'A bit of everything', 'dewier' ),
				),
			),
			'skin' => array(
				'title'   => __( 'How does your skin usually feel?', 'dewier' ),
				'options' => array(
					'dry'       => __( 'Dry or tight', 'dewier' ),
					'oily'      => __( 'Shiny by midday', 'dewier' ),
					'combo'     => __( 'Oily in places, dry in others', 'dewier' ),
					'normal'    => __( 'Comfortable most days', 'dewier' ),
					'sensitive' => __( 'Easily irritated', 'dewier' ),
				),
			),
			'tone' => array(
				'title'   => __( 'Which is closest to your skin tone?', 'dewier' ),
				'options' => array(
					'fair'   => __( 'Fair', 'dewier' ),
					'light'  => __( 'Light', 'dewier' ),
					'medium' => __( 'Medium', 'dewier' ),
					'tan'    => __( 'Tan', 'dewier' ),
					'deep'   => __( 'Deep', 'dewier' ),
					'rich'   => __( 'Rich', 'dewier' ),
				),
				'colors'  => array(
					'fair'   => '#f4dcc9',
					'light'  => '#e9c3a1',
					'medium' => '#d09a6e',
					'tan'    => '#b0774c',
					'deep'   => '#835232',
					'rich'   => '#55331f',
				),
			),
			'look' => array(
				'title'   => __( 'What is your everyday look?', 'dewier' ),
				'options' => array(
					'bare'    => __( 'Barely there', 'dewier' ),
					'glow'    => __( 'Fresh and dewy', 'dewier' ),
					'defined' => __( 'Defined and polished', 'dewier' ),
				),
			),
		)
	);
}

/**
 * Product slug => array( goal, skin types, looks, shade follows skin tone ). "*" matches any answer.
 */
function dewier_quiz_rules() {
	return apply_filters(
		'dewier_quiz_rules',
		array(
			'milk-wash-cleanser'        => array( 'skincare', '*', '*' ),
			'dew-drop-serum'            => array( 'skincare', 'dry normal combo', 'glow' ),
			'barrier-cream'             => array( 'skincare', 'dry sensitive normal', 'bare glow' ),
			'daily-shield-spf'          => array( 'skincare', '*', '*' ),
			'soft-peel-exfoliator'      => array( 'skincare', 'oily combo normal', 'bare defined' ),
			'skincare-starter-set'      => array( 'skincare', 'normal combo dry', 'bare glow' ),
			'sheer-veil-tint'           => array( 'makeup', '*', 'bare glow', true ),
			'stretch-concealer'         => array( 'makeup', '*', 'defined bare', true ),
			'halo-highlighter'          => array( 'makeup', 'dry normal', 'glow' ),
			'petal-flush'               => array( 'makeup', '*', 'glow bare' ),
			'petal-flush-plush'         => array( 'makeup', 'oily combo', 'defined' ),
			'brow-lift'                 => array( 'makeup', '*', 'bare defined' ),
			'feather-lash'              => array( 'makeup', '*', 'defined' ),
			'lid-wash'                  => array( 'makeup', '*', 'defined glow' ),
			'satin-lip'                 => array( 'lip', '*', 'defined' ),
			'lip-sketch'                => array( 'lip', '*', 'defined' ),
			'glass-gloss'               => array( 'lip', '*', 'glow' ),
			'everyday-balm'             => array( 'lip', 'dry sensitive', 'bare' ),
			'tinted-balm-stick'         => array( 'lip', '*', 'bare glow' ),
			'second-skin-eau-de-parfum' => array( 'fragrance', '*', '*' ),
			'second-skin-body-mist'     => array( 'fragrance', '*', 'bare glow' ),
			'body-dew-oil'              => array( 'fragrance', 'dry normal', 'glow' ),
			'soft-hands-cream'          => array( 'fragrance', 'dry sensitive', 'bare' ),
		)
	);
}

/**
 * Valid answers only; unknown keys or values are dropped.
 */
function dewier_quiz_answers( $input ) {
	$answers = array();
	foreach ( dewier_quiz_questions() as $key => $question ) {
		$value = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
		if ( isset( $question['options'][ $value ] ) ) {
			$answers[ $key ] = $value;
		}
	}
	return $answers;
}

/**
 * Up to four products, best match first: array( product_id => follows skin tone ).
 */
function dewier_quiz_pick( $answers ) {
	$goal   = isset( $answers['goal'] ) ? $answers['goal'] : 'all';
	$scored = array();
	foreach ( dewier_quiz_rules() as $slug => $rule ) {
		if ( 'all' !== $goal && $rule[0] !== $goal ) {
			continue;
		}
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( ! $post || 'publish' !== $post->post_status ) {
			continue;
		}
		$score = 0;
		foreach ( array( 1 => 'skin', 2 => 'look' ) as $index => $key ) {
			if ( '*' === $rule[ $index ] ) {
				++$score;
			} elseif ( isset( $answers[ $key ] ) && in_array( $answers[ $key ], explode( ' ', $rule[ $index ] ), true ) ) {
				$score += 2;
			}
		}
		$scored[] = array( $post->ID, $score, $rule[0], ! empty( $rule[3] ) );
	}
	usort(
		$scored,
		static function ( $a, $b ) {
			return $b[1] - $a[1];
		}
	);
	$picks = array();
	// "A bit of everything" takes the best product from each goal first.
	if ( 'all' === $goal ) {
		$seen = array();
		foreach ( $scored as $item ) {
			if ( ! isset( $seen[ $item[2] ] ) ) {
				$seen[ $item[2] ]   = true;
				$picks[ $item[0] ] = $item[3];
			}
		}
	}
	foreach ( $scored as $item ) {
		if ( count( $picks ) >= 4 ) {
			break;
		}
		if ( ! isset( $picks[ $item[0] ] ) ) {
			$picks[ $item[0] ] = $item[3];
		}
	}
	if ( ! $picks ) {
		foreach ( wc_get_products( array( 'status' => 'publish', 'featured' => true, 'limit' => 4, 'return' => 'ids' ) ) as $id ) {
			$picks[ $id ] = false;
		}
	}
	return array_slice( $picks, 0, 4, true );
}

function dewier_quiz_routes() {
	register_rest_route(
		'dewier/v1',
		'/quiz',
		array(
			'methods'             => 'GET',
			'callback'            => 'dewier_quiz_result',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'dewier_quiz_routes' );

function dewier_quiz_result( WP_REST_Request $request ) {
	$questions = dewier_quiz_questions();
	$answers   = dewier_quiz_answers( $request->get_query_params() );
	$picks     = dewier_quiz_pick( $answers );
	$tones     = array_keys( $questions['tone']['options'] );
	$tone      = isset( $answers['tone'] ) ? array_search( $answers['tone'], $tones, true ) : false;
	$shades    = array();

	// Shade names sort light to dark (T1, T2 ...), so the tone's position picks the shade.
	$choose = static function ( $option, $product, $options ) use ( $picks, $tone, $tones, &$shades ) {
		if ( false === $tone || empty( $picks[ $product->get_id() ] ) || count( $options ) < 2 ) {
			return $option;
		}
		usort(
			$options,
			static function ( $a, $b ) {
				return strnatcasecmp( $a['name'], $b['name'] );
			}
		);
		$match = $options[ (int) round( $tone * ( count( $options ) - 1 ) / ( count( $tones ) - 1 ) ) ];
		$shades[ $product->get_id() ] = $product->get_name() . ': ' . $match['name'];
		return $match;
	};
	add_filter( 'dewier_selected_option', $choose, 10, 3 );
	$cards = dewier_product_cards( array_keys( $picks ) );
	remove_filter( 'dewier_selected_option', $choose, 10 );

	$skin = isset( $answers['skin'] ) ? $questions['skin']['options'][ $answers['skin'] ] : '';
	$look = isset( $answers['look'] ) ? $questions['look']['options'][ $answers['look'] ] : '';
	$text = $skin && $look
		/* translators: 1: skin answer, 2: look answer */
		? sprintf( __( 'Picked for skin that feels "%1$s" and a "%2$s" look.', 'dewier' ), $skin, $look )
		: __( 'Picked from our best sellers.', 'dewier' );
	if ( $shades ) {
		/* translators: %s: list of products and shades */
		$text .= ' ' . sprintf( __( 'Your shade match: %s.', 'dewier' ), implode( ', ', array_unique( $shades ) ) );
	}
	return array(
		'text' => $text,
		'html' => '<ul class="products dw-quiz__grid">' . $cards . '</ul>',
	);
}

/**
 * Quiz markup for the Elementor widget and the [dewier_quiz] shortcode.
 */
function dewier_quiz_render( $s = array() ) {
	$s = wp_parse_args(
		array_filter( (array) $s, 'strlen' ),
		array(
			'eyebrow' => __( 'Find your routine', 'dewier' ),
			'title'   => __( 'Not sure where to start?', 'dewier' ),
			'text'    => __( 'Answer four quick questions and we will pick the products for your skin, your shade and your everyday look.', 'dewier' ),
			'button'  => __( 'Start the quiz', 'dewier' ),
		)
	);
	$questions = dewier_quiz_questions();
	$total     = count( $questions );
	ob_start();
	?>
	<section class="dw-quiz" data-endpoint="<?php echo esc_url( rest_url( 'dewier/v1/quiz' ) ); ?>">
		<div class="dw-quiz__intro">
			<p class="dw-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></p>
			<h1 class="dw-quiz__title"><?php echo esc_html( $s['title'] ); ?></h1>
			<p class="dw-quiz__text"><?php echo esc_html( $s['text'] ); ?></p>
			<button type="button" class="dw-btn dw-btn--dark" data-quiz-start><span><?php echo esc_html( $s['button'] ); ?></span></button>
		</div>
		<form class="dw-quiz__form" hidden>
			<div class="dw-quiz__progress" aria-hidden="true"><span></span></div>
			<?php
			$number = 0;
			foreach ( $questions as $key => $question ) :
				++$number;
				?>
				<fieldset class="dw-quiz__step" data-step="<?php echo esc_attr( $key ); ?>" hidden>
					<?php /* translators: 1: question number, 2: number of questions */ ?>
					<p class="dw-quiz__count"><?php echo esc_html( sprintf( __( 'Question %1$d of %2$d', 'dewier' ), $number, $total ) ); ?></p>
					<legend class="dw-quiz__q" tabindex="-1"><?php echo esc_html( $question['title'] ); ?></legend>
					<div class="dw-quiz__options">
						<?php foreach ( $question['options'] as $value => $label ) : ?>
							<label class="dw-quiz__option">
								<input type="radio" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
								<?php if ( isset( $question['colors'][ $value ] ) ) : ?>
									<span class="dw-quiz__swatch" style="--dw-swatch:<?php echo esc_attr( $question['colors'][ $value ] ); ?>" aria-hidden="true"></span>
								<?php endif; ?>
								<span><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>
			<div class="dw-quiz__nav">
				<button type="button" class="dw-btn dw-btn--secondary" data-quiz-back><span><?php esc_html_e( 'Back', 'dewier' ); ?></span></button>
				<button type="submit" class="dw-btn dw-btn--dark" data-quiz-next disabled data-next="<?php esc_attr_e( 'Next', 'dewier' ); ?>" data-finish="<?php esc_attr_e( 'See my picks', 'dewier' ); ?>"><span><?php esc_html_e( 'Next', 'dewier' ); ?></span></button>
			</div>
		</form>
		<div class="dw-quiz__result" hidden>
			<div class="dw-quiz__result-head">
				<p class="dw-eyebrow"><?php esc_html_e( 'Your picks', 'dewier' ); ?></p>
				<h2 class="dw-quiz__title" tabindex="-1"><?php esc_html_e( 'Your Meredithzone routine', 'dewier' ); ?></h2>
				<p class="dw-quiz__text" data-quiz-summary></p>
				<button type="button" class="dw-link" data-quiz-restart><?php esc_html_e( 'Retake the quiz', 'dewier' ); ?></button>
			</div>
			<div class="dw-quiz__products" aria-live="polite" aria-busy="false"></div>
			<p class="dw-quiz__error" role="alert" hidden><?php esc_html_e( 'We could not load your picks. Please try again.', 'dewier' ); ?></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'dewier_quiz', 'dewier_quiz_render' );
