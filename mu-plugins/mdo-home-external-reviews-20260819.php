<?php
/**
 * Plugin Name: MDO - Home External Reviews 2026-08-19
 * Description: Añade una franja ligera de prueba social con las valoraciones verificadas de Google y Trustpilot en la Home renovada.
 * Version: 1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_HOME_EXTERNAL_REVIEWS_VERSION = '1.0.3';

/**
 * Invalida la caché de Home una sola vez cuando cambia esta pieza.
 */
add_action(
	'init',
	static function (): void {
		$stored = (string) get_option( 'mdo_home_external_reviews_version', '' );
		if ( MDO_HOME_EXTERNAL_REVIEWS_VERSION === $stored ) {
			return;
		}

		if ( function_exists( 'elmercado_flush_home_cache' ) ) {
			elmercado_flush_home_cache();
		}

		update_option( 'mdo_home_external_reviews_version', MDO_HOME_EXTERNAL_REVIEWS_VERSION, false );
	},
	1
);

/**
 * Detecta la isla inglesa incluso si su helper todavía no se ha cargado.
 */
function mdo_home_external_reviews_is_english_20260820(): bool {
	if ( function_exists( 'mdo_island_en_request' ) ) {
		return mdo_island_en_request();
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

	return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

/** Los valores viven en opciones y se actualizan desde el sincronizador diario. */
function mdo_home_google_review_count_20260901(): int {
	if ( function_exists( 'mdo_google_review_count_010279' ) ) {
		return mdo_google_review_count_010279();
	}
	return max( 0, (int) get_option( 'mdo_google_review_count', 303 ) );
}

function mdo_home_trustpilot_review_count_20260901(): int {
	if ( function_exists( 'mdo_trustpilot_review_count_010279' ) ) {
		return mdo_trustpilot_review_count_010279();
	}
	return max( 0, (int) get_option( 'mdo_trustpilot_review_count', 169 ) );
}

/**
 * Devuelve la franja de valoraciones leyendo siempre los recuentos persistidos.
 * No se hace ninguna llamada externa durante el render de la portada.
 */
function mdo_home_external_reviews_markup_20260819(): string {
	$is_english = mdo_home_external_reviews_is_english_20260820();

	$google_count = mdo_home_google_review_count_20260901();
	$trust_count  = mdo_home_trustpilot_review_count_20260901();

	$google_url     = 'https://www.google.com/search?q=El+Mercado+de+Origen+Google+rese%C3%B1as';
	$trustpilot_url = $is_english
		? 'https://www.trustpilot.com/review/elmercadodeorigen.com'
		: 'https://es.trustpilot.com/review/elmercadodeorigen.com';

	$section_label = $is_english ? 'Customer ratings' : 'Valoraciones de clientes';
	$intro         = $is_english ? 'Trusted by customers who have already ordered' : 'La confianza de quienes ya han comprado';
	$review_word   = $is_english ? 'reviews' : 'reseñas';
	$opinion_word  = $is_english ? 'reviews' : 'opiniones';
	$google_score  = $is_english ? '4.9' : '4,9';
	$trust_score   = $is_english ? '4.6' : '4,6';
	$google_aria   = $is_english
		? sprintf( 'Google: 4.9 out of 5, %d reviews', $google_count )
		: sprintf( 'Google: 4,9 de 5, %d reseñas', $google_count );
	$trust_aria = $is_english
		? sprintf( 'Trustpilot: 4.6 out of 5, %d reviews', $trust_count )
		: sprintf( 'Trustpilot: 4,6 de 5, %d opiniones', $trust_count );

	return '<section class="mdo-review-proof" aria-label="' . esc_attr( $section_label ) . '" data-google-reviews="' . esc_attr( (string) $google_count ) . '" data-trustpilot-reviews="' . esc_attr( (string) $trust_count ) . '">'
		. '<div class="emo-shell mdo-review-proof__inner">'
		. '<span class="mdo-review-proof__intro">' . esc_html( $intro ) . '</span>'
		. '<div class="mdo-review-proof__sources">'
		. '<a class="mdo-review-proof__source" href="' . esc_url( $google_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $google_aria ) . '">'
		. '<span class="mdo-review-proof__brand">Google</span>'
		. '<span class="mdo-review-proof__score"><strong>' . esc_html( $google_score ) . '</strong><span class="mdo-review-proof__stars" aria-hidden="true">★★★★★</span></span>'
		. '<small>' . esc_html( (string) $google_count ) . ' ' . esc_html( $review_word ) . '</small>'
		. '</a>'
		. '<span class="mdo-review-proof__divider" aria-hidden="true"></span>'
		. '<a class="mdo-review-proof__source" href="' . esc_url( $trustpilot_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $trust_aria ) . '">'
		. '<span class="mdo-review-proof__brand">Trustpilot</span>'
		. '<span class="mdo-review-proof__score"><strong>' . esc_html( $trust_score ) . '</strong><span class="mdo-review-proof__stars" aria-hidden="true">★★★★★</span></span>'
		. '<small>' . esc_html( (string) $trust_count ) . ' ' . esc_html( $opinion_word ) . '</small>'
		. '</a>'
		. '</div></div></section>';
}

/**
 * Inserta la franja tras el bloque de confianza de la Home nueva y antes de
 * las categorías. Si ese punto cambia, cae de forma segura justo antes de las
 * categorías o, como último recurso, tras el hero.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( ! str_contains( $content, 'class="emo-home"' ) || str_contains( $content, 'class="mdo-review-proof"' ) ) {
			return $content;
		}

		$block = mdo_home_external_reviews_markup_20260819();
		$category_marker = '<section class="emo-section emo-categories">';
		$category_pos    = strpos( $content, $category_marker );

		if ( false !== $category_pos ) {
			return substr_replace( $content, $block, $category_pos, 0 );
		}

		$hero_end = strpos( $content, '</section>' );
		if ( false !== $hero_end ) {
			$hero_end += strlen( '</section>' );
			return substr_replace( $content, $block, $hero_end, 0 );
		}

		return $content;
	},
	55
);

/**
 * Estilos mínimos: una sola línea en escritorio y dos fuentes compactas en
 * móvil. Sin imágenes, fuentes, scripts ni recursos externos.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}
		?>
		<style id="mdo-home-external-reviews-20260819">
			body.elmercado-premium-home .mdo-review-proof {
				border-block: 1px solid rgba(34, 41, 34, .09);
				background: rgba(250, 248, 243, .72);
			}
			body.elmercado-premium-home .mdo-review-proof__inner {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: clamp(1rem, 3vw, 2.5rem);
				min-height: 62px;
				padding-block: 10px;
			}
			body.elmercado-premium-home .mdo-review-proof__intro {
				font-size: .76rem;
				font-weight: 650;
				letter-spacing: .04em;
				color: rgba(34, 41, 34, .66);
				white-space: nowrap;
			}
			body.elmercado-premium-home .mdo-review-proof__sources {
				display: flex;
				align-items: center;
				gap: 1rem;
			}
			body.elmercado-premium-home .mdo-review-proof__source {
				display: inline-flex;
				align-items: baseline;
				gap: .55rem;
				color: inherit;
				text-decoration: none;
				white-space: nowrap;
				transition: opacity .18s ease;
			}
			body.elmercado-premium-home .mdo-review-proof__source:hover,
			body.elmercado-premium-home .mdo-review-proof__source:focus-visible {
				opacity: .72;
			}
			body.elmercado-premium-home .mdo-review-proof__brand {
				font-size: .82rem;
				font-weight: 750;
				letter-spacing: -.01em;
			}
			body.elmercado-premium-home .mdo-review-proof__score {
				display: inline-flex;
				align-items: center;
				gap: .28rem;
				font-size: .82rem;
			}
			body.elmercado-premium-home .mdo-review-proof__score strong {
				font-size: .9rem;
			}
			body.elmercado-premium-home .mdo-review-proof__stars {
				font-size: .64rem;
				letter-spacing: .05em;
				color: #b9852f;
			}
			body.elmercado-premium-home .mdo-review-proof__source small {
				font-size: .72rem;
				color: #666;
			}
			body.elmercado-premium-home .mdo-review-proof__divider {
				width: 1px;
				height: 20px;
				background: rgba(34, 41, 34, .14);
			}
			@media (max-width: 767px) {
				body.elmercado-premium-home .mdo-review-proof__inner {
					align-items: stretch;
					gap: .5rem;
					padding-block: 9px 11px;
				}
				body.elmercado-premium-home .mdo-review-proof__intro {
					display: none;
				}
				body.elmercado-premium-home .mdo-review-proof__sources {
					width: 100%;
					justify-content: center;
					gap: .8rem;
				}
				body.elmercado-premium-home .mdo-review-proof__source {
					flex: 1 1 0;
					max-width: 170px;
					flex-wrap: wrap;
					justify-content: center;
					gap: .18rem .42rem;
					text-align: center;
				}
				body.elmercado-premium-home .mdo-review-proof__source small {
					flex-basis: 100%;
				}
				body.elmercado-premium-home .mdo-review-proof__divider {
					height: 34px;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
