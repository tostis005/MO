<?php
/**
 * Metadatos editoriales visibles del Blog 0.10.88.
 *
 * El archivo y las entradas conservan únicamente el tiempo de lectura como
 * metadato visible. Las categorías y fechas siguen disponibles para WordPress,
 * SEO y consultas internas, pero no forman parte de la interfaz editorial.
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
		<style id="elmercado-blog-meta-visibility-final-01088">
			/* El Blog no muestra navegación/filtros por categoría ni categorías o
			 * fechas en tarjetas/entradas. Se conserva el tiempo de lectura. */
			body.elmercado-editorial-content .emo-journal-categories,
			body.elmercado-editorial-content .emo-article-card__category,
			body.elmercado-editorial-content .emo-article-card__meta > span:first-of-type,
			body.single-post .emo-article-hero__meta > a,
			body.single-post .emo-article-hero__meta > span:first-of-type {
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
