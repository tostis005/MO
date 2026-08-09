<?php
/**
 * Paleta final de portada alineada con el resto del sitio 0.10.117.
 *
 * Mantiene las superficies amplias en papel y blanco roto, y reserva los
 * verdes de marca para jerarquía, acentos y bloques de contraste.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<style id="elmercado-home-palette-site-final-010117">
			body.home.elmercado-child-theme .emo-home {
				--emo-home-categories-bg: #f7f3ea;
				--emo-home-products-bg: #fffdf8;
				--emo-home-story-bg: #f7f3ea;
				--emo-home-brand-green: #173f32;
				--emo-home-brand-green-soft: #e4eee8;
			}

			/* Superficies amplias: misma familia de papel cálido que el resto de la web. */
			body.home.elmercado-child-theme .emo-categories,
			body.home.elmercado-child-theme .emo-story {
				background: var(--emo-home-categories-bg) !important;
			}
			body.home.elmercado-child-theme .emo-featured-products {
				background: var(--emo-home-products-bg) !important;
			}
			body.home.elmercado-child-theme .emo-featured-products > .emo-shell,
			body.home.elmercado-child-theme .emo-featured-products .woocommerce,
			body.home.elmercado-child-theme .emo-featured-products ul.products {
				background: transparent !important;
				background-image: none !important;
				box-shadow: none !important;
			}

			/* Hero y bloque editorial: verdes de marca sin dominantes anaranjadas. */
			body.home.elmercado-child-theme .emo-hero {
				background:
					radial-gradient(circle at 12% 20%, rgba(228,238,232,.10), transparent 30%),
					radial-gradient(circle at 89% 12%, rgba(247,243,234,.08), transparent 28%),
					linear-gradient(135deg, #122a22, #21483a) !important;
			}
			body.home.elmercado-child-theme .emo-story__panel {
				background: linear-gradient(145deg, #173f32, #21483a) !important;
			}

			/* Los acentos claros pasan del melocotón a salvia/papel. */
			body.home.elmercado-child-theme .emo-hero h1 em,
			body.home.elmercado-child-theme .emo-hero-card figcaption span,
			body.home.elmercado-child-theme .emo-hero .emo-kicker--light,
			body.home.elmercado-child-theme .emo-story__panel .emo-kicker--light {
				color: var(--emo-home-brand-green-soft) !important;
			}
			body.home.elmercado-child-theme .emo-section :is(.emo-kicker,.emo-eyebrow):not(.emo-kicker--light),
			body.home.elmercado-child-theme .emo-story__values article > span {
				color: #2f6650 !important;
			}

			/* CTA principal del hero: papel cálido sobre verde, como el lenguaje del sitio. */
			body.home.elmercado-child-theme .emo-hero .emo-button--accent {
				background: #f7f3ea !important;
				border-color: #f7f3ea !important;
				color: #173f32 !important;
			}
			body.home.elmercado-child-theme .emo-hero .emo-button--accent:hover,
			body.home.elmercado-child-theme .emo-hero .emo-button--accent:focus-visible {
				background: #fffdf8 !important;
				border-color: #fffdf8 !important;
				color: #122a22 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
