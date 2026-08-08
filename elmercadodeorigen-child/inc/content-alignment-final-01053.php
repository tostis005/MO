<?php
/**
 * Alineación editorial final, gutters y limpieza visual de Home 0.10.53.
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
		<style id="elmercado-content-alignment-final-01053">
			body.elmercado-child-theme {
				--emo-inner-top-gap: 20px;
			}

			/* Blog: la cabecera oscura sigue siendo la referencia de gutter y altura. */
			body.elmercado-child-theme .emo-journal-hero {
				padding-top: var(--emo-inner-top-gap) !important;
			}
			body.elmercado-child-theme :is(
				.emo-journal-hero__inner,
				.emo-journal-listing > .emo-shell,
				.emo-article-hero__inner,
				.emo-article-main > .emo-shell,
				.emo-related-reading > .emo-shell
			) {
				width: min(var(--emo-content-max, 1180px), calc(100% - (2 * var(--emo-page-gutter, 16px)))) !important;
				max-width: var(--emo-content-max, 1180px) !important;
				margin-inline: auto !important;
				box-sizing: border-box !important;
			}

			/* La ficha de una entrada usa todo el ancho útil del mismo shell editorial. */
			body.elmercado-child-theme.elmercado-editorial-content .emo-article-content,
			body.elmercado-child-theme.elmercado-editorial-content .emo-article-footer {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
				box-sizing: border-box !important;
			}

			/* Productores y Contacto ya están dentro del gutter exterior: no duplicarlo. */
			body.elmercado-child-theme .emo-producers-intro,
			body.elmercado-child-theme .emo-contact-layout {
				width: 100% !important;
				max-width: var(--emo-content-max, 1180px) !important;
				margin-inline: auto !important;
				box-sizing: border-box !important;
			}
			body.elmercado-child-theme .emo-producers-intro,
			body.elmercado-child-theme .emo-contact-layout {
				margin-top: var(--emo-inner-top-gap) !important;
			}

			/* El contenido de Contacto comparte exactamente los bordes de su cabecera. */
			body.elmercado-child-theme .emo-contact-form {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
				box-sizing: border-box !important;
			}

			/* Home: sin micro-etiquetas sobre las imágenes del hero ni flechas en el carrusel. */
			body.home.elmercado-child-theme .emo-hero__visual .emo-hero-card figcaption > span,
			body.home.elmercado-child-theme .emo-featured-products .emo-carousel-controls,
			body.home.elmercado-child-theme .emo-featured-products .emo-carousel-control {
				display: none !important;
				visibility: hidden !important;
				opacity: 0 !important;
				pointer-events: none !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme {
					--emo-page-gutter: 16px;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
