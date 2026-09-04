<?php
/**
 * Correcciones finales y acotadas del artículo individual 0.10.286.
 *
 * Restaura cuatro lecturas relacionadas en escritorio, mantiene su rejilla
 * responsive y controla el espacio residual antes del opt-in.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-regression-fix-010286">
			/* El párrafo anterior conserva una separación breve y controlada antes del opt-in. */
			html body.single-post article.emo-entry-standard .emo-article-content > p:has(+ .emo-inline-special-anchor + .emo-inline-newsletter),
			html body.single-post article.emo-entry-standard .emo-article-content > p:has(+ .emo-inline-newsletter-anchor) {
				margin-bottom: 11px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content .emo-inline-newsletter-anchor {
				margin: 0 !important;
				padding: 0 !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content .emo-inline-newsletter {
				margin-top: 4px !important;
				padding-top: 9px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content .emo-inline-newsletter h3 {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/* El bloque de lecturas vuelve al ancho que permite cuatro tarjetas. */
			html body.single-post main#primary.emo-article-page > .emo-related-reading > .emo-shell {
				width: min(100%, 1180px) !important;
				max-width: 1180px !important;
				margin-left: auto !important;
				margin-right: auto !important;
			}

			html body.single-post main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
				display: grid !important;
				width: min(100%, 1040px) !important;
				max-width: 1040px !important;
				grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin-left: auto !important;
				margin-right: auto !important;
			}

			@media (max-width: 1100px) {
				html body.single-post main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 560px) {
				html body.single-post main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}
		</style>
		<script id="elmercado-related-reading-responsive-010287">
		(() => {
			'use strict';
			/*
			 * single.php heredó un grid-template-columns inline con !important.
			 * Se elimina únicamente esa propiedad para que las media queries finales
			 * puedan gobernar 4 -> 2 -> 1 columnas según el ancho de pantalla.
			 */
			const grid = document.querySelector('main#primary.emo-article-page .emo-related-reading .emo-journal-grid');
			if (grid && grid.style) {
				grid.style.removeProperty('grid-template-columns');
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
