<?php
/**
 * Ajustes finales de lectura del blog y copy del pie 0.10.253.
 *
 * Mantiene los productos relacionados dentro de sus contenedores, normaliza
 * el ritmo editorial de todas las entradas y garantiza el bloque de embutidos
 * de la entrada de Jamón Ibérico sin reestilizar las tarjetas WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normaliza el título del enlace legal en menús de la versión inglesa.
 */
add_filter(
	'nav_menu_item_title',
	static function ( string $title, $item, $args, int $depth ): string {
		if ( ! function_exists( 'elmercado_is_english_request_010245' ) || ! elmercado_is_english_request_010245() ) {
			return $title;
		}

		$plain_title = trim( wp_strip_all_tags( $title ) );
		if ( 0 === strcasecmp( $plain_title, 'Terms and Conditions' ) ) {
			return 'Terms and Conditions';
		}

		return $title;
	},
	PHP_INT_MAX,
	4
);

/**
 * Clase contractual para que todas las entradas compartan el mismo sistema de
 * ritmo editorial sin depender de cómo fueron maquetadas históricamente.
 */
add_filter(
	'post_class',
	static function ( array $classes, array $css_class, int $post_id ): array {
		if ( is_singular( 'post' ) && (int) get_queried_object_id() === $post_id ) {
			$classes[] = 'emo-entry-standard';
		}

		return array_values( array_unique( $classes ) );
	},
	20,
	3
);

/**
 * Limpia separadores vacíos heredados del editor clásico y garantiza que el
 * epígrafe de embutidos de Jamón Ibérico tenga productos reales debajo.
 *
 * Se ejecuta después de do_shortcode para poder distinguir un contenedor Woo
 * vacío de un listado que contiene tarjetas de producto de verdad.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return $content;
		}

		$cleaned = preg_replace(
			'/<p\b[^>]*>(?:\s|&nbsp;|&#160;|&#x0*a0;|<br\b[^>]*>)*<\/p>/iu',
			'',
			$content
		);
		if ( is_string( $cleaned ) ) {
			$content = $cleaned;
		}

		/* Evita saltos manuales entre un titular y el texto que le sigue. */
		$cleaned = preg_replace(
			'/(<\/h[2-6]>)(?:\s*<br\b[^>]*>\s*)+/iu',
			'$1',
			$content
		);
		if ( is_string( $cleaned ) ) {
			$content = $cleaned;
		}

		if ( 'jamon-iberico' !== (string) get_post_field( 'post_name', get_queried_object_id() ) ) {
			return $content;
		}

		$heading_match = array();
		$has_heading   = 1 === preg_match(
			'/<h[1-6]\b[^>]*>(?:(?!<\/h[1-6]>)[\s\S])*?embutidos(?:(?!<\/h[1-6]>)[\s\S])*?<\/h[1-6]>/iu',
			$content,
			$heading_match,
			PREG_OFFSET_CAPTURE
		);

		if ( ! $has_heading ) {
			return $content;
		}

		$heading_start = (int) $heading_match[0][1];
		$heading_html  = (string) $heading_match[0][0];
		$heading_end   = $heading_start + strlen( $heading_html );
		$tail          = substr( $content, $heading_end );

		/* Un ul.products vacío no cuenta: exigimos al menos una tarjeta real. */
		$has_real_products = is_string( $tail ) && 1 === preg_match(
			'/<li\b[^>]*class=(?:"[^"]*\bproduct\b[^"]*"|\'[^\']*\bproduct\b[^\']*\')[^>]*>/iu',
			$tail
		);

		if ( $has_real_products || ! shortcode_exists( 'products' ) ) {
			return $content;
		}

		$term = get_term_by( 'slug', 'embutidos', 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			$term = get_term_by( 'name', 'Embutidos', 'product_cat' );
		}
		if ( ! $term instanceof WP_Term ) {
			return $content;
		}

		$embutidos_products = do_shortcode(
			sprintf(
				'[products category="%s" limit="8" columns="3" orderby="popularity" order="DESC"]',
				esc_attr( (string) $term->slug )
			)
		);

		if ( 1 !== preg_match( '/<li\b[^>]*class=(?:"[^"]*\bproduct\b[^"]*"|\'[^\']*\bproduct\b[^\']*\')[^>]*>/iu', $embutidos_products ) ) {
			return $content;
		}

		$fallback_block = '<div class="emo-jamon-embutidos-fallback emo-entry-product-section" data-emo-embutidos="010253">' . $embutidos_products . '</div>';

		return substr( $content, 0, $heading_end ) . $fallback_block . substr( $content, $heading_end );
	},
	PHP_INT_MAX
);

/**
 * Última capa visual histórica del blog. Sólo conserva lectura y geometría;
 * no modifica imágenes, botones, precio ni bloque de productor del catálogo.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_single() || is_singular( 'product' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-layout-polish-010246">
			body.single-post.elmercado-child-theme .emo-article-content :is(h4, h5, h6) {
				clear: both;
				margin: 2.15em 0 0.72em !important;
				color: var(--emo-charcoal, #0d211b);
				font-family: var(--emo-font-serif, Georgia, "Times New Roman", serif);
				font-weight: 300;
				line-height: 1.25;
				letter-spacing: -0.01em;
			}

			body.single-post.elmercado-child-theme .emo-article-content h4 {
				font-size: clamp(21px, 2.2vw, 28px);
			}

			body.single-post.elmercado-child-theme .emo-article-content h5 {
				font-size: clamp(19px, 1.9vw, 24px);
			}

			body.single-post.elmercado-child-theme .emo-article-content h6 {
				font-size: clamp(17px, 1.6vw, 21px);
			}

			body.single-post.elmercado-child-theme .emo-article-content > :is(h2, h3, h4, h5, h6):first-child {
				margin-top: 0 !important;
			}

			/* Sólo geometría del loop: el acabado visual lo aporta el catálogo global. */
			body.single-post.elmercado-child-theme .emo-article-content .woocommerce {
				width: 100%;
				max-width: 100%;
				margin: 2.5em 0;
				overflow: visible;
			}

			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products {
				display: grid !important;
				width: 100% !important;
				max-width: 100% !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				gap: clamp(0.9rem, 2.5vw, 1.5rem) !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
			}

			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products::before,
			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products::after {
				display: none !important;
				content: none !important;
			}

			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products > li.product {
				float: none !important;
				clear: none !important;
				width: auto !important;
				min-width: 0 !important;
				max-width: 100% !important;
				margin: 0 !important;
			}

			/*
			 * El shortcode no siempre conserva la misma profundidad de wrapper que la
			 * tienda. Igualamos únicamente la altura de línea que el contrato global
			 * aplica al título, manteniendo el mismo valor responsive del catálogo.
			 */
			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products > li.product .woocommerce-loop-product__title {
				line-height: 1.27 !important;
			}

			@media (max-width: 767px) {
				body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products > li.product .woocommerce-loop-product__title {
					line-height: 1.23 !important;
				}
			}

			/* Relacionados propios del blog: 3 → 2 → 1, sin tarjetas gigantes. */
			body.single-post.elmercado-child-theme .emo-related-grid {
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: clamp(1rem, 2.4vw, 1.75rem) !important;
			}

			body.single-post.elmercado-child-theme .emo-related-product {
				min-width: 0;
				max-width: 100%;
			}

			body.single-post.elmercado-child-theme .emo-related-product__media {
				width: 100%;
				max-width: 100%;
			}

			body.single-post.elmercado-child-theme .emo-related-product__media img {
				display: block;
				width: 100%;
				max-width: 100%;
				height: 100%;
				object-fit: cover;
			}

			body.single-post.elmercado-child-theme .emo-related-product h3 {
				overflow-wrap: anywhere;
			}

			@media (max-width: 900px) {
				body.single-post.elmercado-child-theme .emo-related-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 520px) {
				body.single-post.elmercado-child-theme .emo-related-grid {
					grid-template-columns: minmax(0, 1fr) !important;
				}

				body.single-post.elmercado-child-theme .emo-related-product {
					width: min(100%, 330px);
					margin-inline: auto;
				}
			}

			@media (max-width: 379px) {
				body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products {
					grid-template-columns: minmax(0, 280px) !important;
					justify-content: center;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Contrato de espaciado 0.10.253. Se imprime en el footer para quedar después
 * de todas las capas editoriales antiguas. Afecta a todas las entradas.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-entry-rhythm-010253">
			html body.single-post article.emo-entry-standard .emo-article-content > p {
				margin-top: 0 !important;
				margin-bottom: 1.05em !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > :is(ul, ol) {
				margin-top: 0.35em !important;
				margin-bottom: 1.2em !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > h2 {
				margin-top: clamp(32px, 3.2vw, 42px) !important;
				margin-bottom: 13px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > h3 {
				margin-top: clamp(28px, 2.8vw, 36px) !important;
				margin-bottom: 12px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > :is(h4, h5) {
				margin-top: clamp(25px, 2.5vw, 32px) !important;
				margin-bottom: 10px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > h6 {
				margin-top: clamp(20px, 2vw, 26px) !important;
				margin-bottom: 8px !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > :is(h2, h3, h4, h5, h6):first-child {
				margin-top: 0 !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > :is(h2, h3, h4, h5, h6) + :is(p, ul, ol),
			html body.single-post article.emo-entry-standard .emo-article-content > :is(h2, h3, h4, h5, h6) + br + :is(p, ul, ol) {
				margin-top: 0 !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > :is(h2, h3, h4, h5, h6) + br {
				display: none !important;
			}

			html body.single-post article.emo-entry-standard .emo-article-content > figure {
				margin-top: 1.4rem !important;
				margin-bottom: 1.6rem !important;
			}

			/* El producto empieza cerca de su titular, sin una franja blanca artificial. */
			html body.single-post article.emo-entry-standard .emo-article-content > :is(h2, h3, h4, h5, h6) + .woocommerce,
			html body.single-post article.emo-entry-standard .emo-article-content > :is(h2, h3, h4, h5, h6) + .emo-entry-product-section {
				margin-top: 16px !important;
			}

			html body.single-post article.emo-entry-standard .emo-jamon-embutidos-fallback {
				margin-top: 16px !important;
				margin-bottom: 0 !important;
			}

			html body.single-post article.emo-entry-standard .emo-jamon-embutidos-fallback > .woocommerce {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
			}

			/* Si una versión antigua dejó sólo la carcasa Woo vacía, no reserva espacio. */
			html body.single-post article.emo-entry-standard .emo-article-content .woocommerce:has(ul.products):not(:has(li.product)) {
				display: none !important;
			}

			@media (max-width: 767px) {
				html body.single-post article.emo-entry-standard .emo-article-content > h2 {
					margin-top: 30px !important;
				}

				html body.single-post article.emo-entry-standard .emo-article-content > h3 {
					margin-top: 26px !important;
				}

				html body.single-post article.emo-entry-standard .emo-article-content > :is(h4, h5) {
					margin-top: 23px !important;
				}

				html body.single-post article.emo-entry-standard .emo-article-content > h6 {
					margin-top: 20px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Salvaguarda para pies construidos con widgets/bloques y no con un menú
 * clásico: corrige únicamente el enlace legal dentro del footer inglés.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_is_english_request_010245' ) || ! elmercado_is_english_request_010245() ) {
			return;
		}
		?>
		<script id="elmercado-footer-terms-copy-010246">
		(() => {
			'use strict';
			const normalize = () => {
				const footer = document.querySelector('footer, #colophon, .site-footer');
				if (!footer) return;
				footer.querySelectorAll('a').forEach((link) => {
					const label = (link.textContent || '').trim();
					if (label.toLocaleLowerCase('en') === 'terms and conditions') {
						link.textContent = 'Terms and Conditions';
					}
				});
			};
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', normalize, {once: true});
			} else {
				normalize();
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

$elmercado_contact_footer_hotfix_010247 = __DIR__ . '/contact-footer-hotfix-010247.php';
if ( is_readable( $elmercado_contact_footer_hotfix_010247 ) ) {
	require_once $elmercado_contact_footer_hotfix_010247;
}

$elmercado_blog_design_system_010248 = __DIR__ . '/blog-design-system-010248.php';
if ( is_readable( $elmercado_blog_design_system_010248 ) ) {
	require_once $elmercado_blog_design_system_010248;
}
