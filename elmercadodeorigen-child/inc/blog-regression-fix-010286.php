<?php
/**
 * Correcciones finales y acotadas del artículo individual 0.10.286.
 *
 * Restaura cuatro lecturas relacionadas en escritorio y elimina el espacio
 * residual que las capas históricas vuelven a introducir antes del opt-in.
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
			/* El párrafo anterior no vuelve a sumar 1.05em encima del opt-in. */
			html body.single-post article.emo-entry-standard .emo-article-content > p:has(+ .emo-inline-special-anchor + .emo-inline-newsletter),
			html body.single-post article.emo-entry-standard .emo-article-content > p:has(+ .emo-inline-newsletter-anchor) {
				margin-bottom: 5px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content .emo-inline-newsletter-anchor {
				margin: 0 !important;
				padding: 0 !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content .emo-inline-newsletter {
				margin-top: 0 !important;
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
		<?php
	},
	PHP_INT_MAX
);
