<?php
/**
 * Ajustes finales de lectura del blog y copy del pie 0.10.246.
 *
 * Mantiene los productos relacionados dentro de sus contenedores, evita
 * tarjetas sobredimensionadas en tablet/móvil, normaliza el ritmo de los
 * encabezados editoriales y corrige el título inglés de Terms and Conditions.
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
 * Última capa visual del blog. Se imprime al final del head para ganar a los
 * estilos históricos sin alterar el catálogo ni las fichas de producto.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_single() || is_singular( 'product' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-layout-polish-010246">
			/* Los niveles editoriales menores deben respirar igual que H2/H3. */
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

			body.single-post.elmercado-child-theme .emo-article-content :is(h2, h3, h4, h5, h6):first-child {
				margin-top: 0 !important;
			}

			/* Un loop de WooCommerce incrustado en una entrada nunca sale del texto. */
			body.single-post.elmercado-child-theme .emo-article-content .woocommerce {
				width: 100%;
				max-width: 100%;
				margin: 2.5em 0;
				overflow: hidden;
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

			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products > li.product img {
				display: block;
				width: 100% !important;
				max-width: 100% !important;
				height: auto;
				margin-inline: auto;
			}

			body.single-post.elmercado-child-theme .emo-article-content .woocommerce ul.products > li.product :is(h2, h3, .woocommerce-loop-product__title) {
				overflow-wrap: anywhere;
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

/*
 * El hotfix 0.10.247 se mantiene separado para no mezclar la corrección del
 * formulario de contacto con la capa editorial anterior.
 */
$elmercado_contact_footer_hotfix_010247 = __DIR__ . '/contact-footer-hotfix-010247.php';
if ( is_readable( $elmercado_contact_footer_hotfix_010247 ) ) {
	require_once $elmercado_contact_footer_hotfix_010247;
}

/*
 * 0.10.248 unifica definitivamente el lienzo del blog y las rejillas de
 * producto. Se carga la última para sustituir solo las reglas editoriales
 * históricas que necesitaban un ancho diferente.
 */
$elmercado_blog_design_system_010248 = __DIR__ . '/blog-design-system-010248.php';
if ( is_readable( $elmercado_blog_design_system_010248 ) ) {
	require_once $elmercado_blog_design_system_010248;
}
