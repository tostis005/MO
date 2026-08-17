<?php
/**
 * Ajustes finales de lectura del blog y copy del pie 0.10.251.
 *
 * Mantiene los productos relacionados dentro de sus contenedores y normaliza
 * el ritmo editorial, sin volver a estilizar las tarjetas WooCommerce. Las
 * tarjetas incrustadas heredan el mismo sistema global que la tienda.
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
