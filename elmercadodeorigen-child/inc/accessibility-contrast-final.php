<?php
/**
 * Correcciones de contraste verificadas mediante Lighthouse.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-accessibility-contrast-final">
			/* Hero de portada: título, introducción y acciones siempre claros sobre fotografía oscura. */
			body.home.elmercado-child-theme .emo-hero,
			body.home.elmercado-child-theme .emo-hero h1,
			body.home.elmercado-child-theme .emo-hero h2,
			body.home.elmercado-child-theme .emo-hero p,
			body.home.elmercado-child-theme .emo-hero span,
			body.home.elmercado-child-theme .emo-hero a,
			body.home.elmercado-child-theme [class*="hero"] .emo-hero__content,
			body.home.elmercado-child-theme [class*="hero"] .emo-hero__content * {
				color: #fffdf8 !important;
			}

			/* Tarjetas claras de valores: Lighthouse detectó texto claro sobre fondo claro. */
			body.home.elmercado-child-theme .emo-story__values article.emo-reveal,
			body.home.elmercado-child-theme .emo-story__values article.emo-reveal span,
			body.home.elmercado-child-theme .emo-story__values article.emo-reveal h3,
			body.home.elmercado-child-theme .emo-story__values article.emo-reveal p,
			body.home.elmercado-child-theme .emo-story__values article.emo-reveal a {
				color: #173f32 !important;
				opacity: 1 !important;
			}

			body.home.elmercado-child-theme .emo-story__values article.emo-reveal > span:first-child {
				color: #1f674b !important;
			}

			/* CTA de productores: botón oscuro con texto blanco real. */
			body.home.elmercado-child-theme .emo-vendor-cta__inner .emo-button--dark,
			body.home.elmercado-child-theme .emo-vendor-cta__inner .emo-button--dark:visited {
				border-color: #173f32 !important;
				background: #173f32 !important;
				color: #fffdf8 !important;
			}

			body.home.elmercado-child-theme .emo-vendor-cta__inner .emo-button--dark:hover,
			body.home.elmercado-child-theme .emo-vendor-cta__inner .emo-button--dark:focus-visible {
				border-color: #0d2b22 !important;
				background: #0d2b22 !important;
				color: #fffdf8 !important;
			}

			/* Estado agotado legible en catálogo global y tienda del productor. */
			body.elmercado-child-theme ul.products .woostify-out-of-stock-label {
				border: 1px solid rgba(255, 255, 255, 0.3) !important;
				background: #173f32 !important;
				color: #fffdf8 !important;
				font-weight: 800 !important;
				text-shadow: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
