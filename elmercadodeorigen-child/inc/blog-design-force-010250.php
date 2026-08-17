<?php
/**
 * Capa geométrica final del blog 0.10.251.
 *
 * Se imprime en el footer para quedar después de las capas históricas del tema.
 * Sólo corrige geometría editorial; las tarjetas de producto conservan sin
 * alteraciones el mismo acabado global que tienen en la tienda.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! ( is_home() || is_archive() || is_singular( 'post' ) ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-design-force-010250">
			html body main#primary.emo-journal,
			html body main#primary.emo-article-page {
				display: block !important;
				float: none !important;
				clear: both !important;
				position: relative !important;
				left: auto !important;
				right: auto !important;
				width: min(1180px, calc(100% - 48px)) !important;
				min-width: 0 !important;
				max-width: 1180px !important;
				flex: 0 1 1180px !important;
				margin: clamp(22px, 3vw, 38px) auto clamp(38px, 6vw, 76px) !important;
				padding: 0 !important;
				transform: none !important;
				box-sizing: border-box !important;
				overflow: visible !important;
			}

			html body main#primary.emo-journal > .emo-journal-hero,
			html body main#primary.emo-journal > .emo-journal-listing,
			html body main#primary.emo-article-page .emo-article-hero,
			html body main#primary.emo-article-page .emo-article-main,
			html body main#primary.emo-article-page > .emo-related-reading {
				width: 100% !important;
				max-width: 100% !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal > .emo-journal-hero,
			html body main#primary.emo-article-page .emo-article-hero {
				margin-inline: auto !important;
				border-radius: 26px !important;
				overflow: hidden !important;
				box-shadow: 0 20px 55px rgba(13, 33, 27, 0.13) !important;
			}

			html body main#primary.emo-journal .emo-journal-hero__inner,
			html body main#primary.emo-article-page .emo-article-hero__inner,
			html body main#primary.emo-journal .emo-journal-listing > .emo-shell,
			html body main#primary.emo-article-page .emo-article-main > .emo-shell,
			html body main#primary.emo-article-page .emo-related-reading > .emo-shell {
				width: 100% !important;
				max-width: 100% !important;
				margin-inline: auto !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal .emo-journal-listing > .emo-shell,
			html body main#primary.emo-article-page .emo-article-main > .emo-shell {
				padding-inline: 0 !important;
			}

			html body main#primary.emo-journal > .emo-journal-listing {
				margin: 0 !important;
				padding: clamp(46px, 6vw, 76px) 0 0 !important;
			}

			html body main#primary.emo-journal .emo-journal-grid {
				display: grid !important;
				width: 100% !important;
				max-width: 100% !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				gap: 26px !important;
				margin: 0 !important;
				padding: 0 !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal .emo-article-card {
				min-width: 0 !important;
				border-radius: 20px !important;
				overflow: hidden !important;
				box-shadow: 0 10px 30px rgba(13, 33, 27, 0.055) !important;
			}

			html body main#primary.emo-journal .emo-article-card--featured {
				grid-column: 1 / -1 !important;
				display: grid !important;
				grid-template-columns: minmax(0, 1.1fr) minmax(340px, 0.9fr) !important;
				min-height: 430px !important;
			}

			html body main#primary.emo-article-page .emo-article-featured {
				width: min(100%, 1080px) !important;
				max-width: 1080px !important;
				margin: 0 auto clamp(32px, 5vw, 54px) !important;
				border-radius: 22px !important;
				overflow: hidden !important;
			}

			html body main#primary.emo-article-page .emo-article-featured img {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				height: auto !important;
				max-height: 610px !important;
				aspect-ratio: 16 / 9 !important;
				object-fit: cover !important;
			}

			html body main#primary.emo-article-page .emo-article-content {
				width: min(100%, 800px) !important;
				min-width: 0 !important;
				max-width: 800px !important;
				margin: 0 auto !important;
				padding: clamp(28px, 4.2vw, 52px) !important;
				background: #fff !important;
				border-radius: 22px !important;
				box-shadow: 0 12px 34px rgba(13, 33, 27, 0.06) !important;
				box-sizing: border-box !important;
				overflow: visible !important;
			}

			/*
			 * Los productos abren el ancho de lectura hasta 1040 px, pero su tarjeta
			 * no recibe estilos propios del blog: hereda WooCommerce/Woostify/WCFM.
			 */
			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
				width: min(1040px, calc(100vw - 80px)) !important;
				max-width: 1040px !important;
				margin: clamp(34px, 5vw, 52px) 50% !important;
				padding: 0 !important;
				transform: translateX(-50%) !important;
				box-sizing: border-box !important;
				overflow: visible !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
				display: grid !important;
				float: none !important;
				width: 100% !important;
				max-width: 100% !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template)::before,
			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template)::after {
				display: none !important;
				content: none !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products > li.product, ul.products > li.product, .wc-block-grid__product, .wc-block-product) {
				float: none !important;
				clear: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				margin: 0 !important;
			}

			html body main#primary.emo-article-page > .emo-related-reading {
				margin: clamp(48px, 7vw, 84px) 0 0 !important;
				padding: clamp(40px, 5vw, 62px) clamp(28px, 4vw, 46px) !important;
				background: #efe8dc !important;
				border-radius: 24px !important;
			}

			html body main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
				display: grid !important;
				width: min(100%, 1040px) !important;
				max-width: 1040px !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin: 0 auto !important;
			}

			@media (max-width: 1100px) {
				html body main#primary.emo-article-page .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
					width: 100% !important;
					max-width: 100% !important;
					margin: 34px 0 !important;
					transform: none !important;
				}

				html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template),
				html body main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 820px) {
				html body main#primary.emo-journal,
				html body main#primary.emo-article-page {
					width: calc(100% - 32px) !important;
					max-width: calc(100% - 32px) !important;
					flex-basis: auto !important;
					margin-top: 16px !important;
				}

				html body main#primary.emo-journal .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 18px !important;
				}

				html body main#primary.emo-journal .emo-article-card--featured {
					grid-column: auto !important;
					display: flex !important;
					min-height: 0 !important;
					flex-direction: column !important;
				}

				html body main#primary.emo-article-page .emo-article-content {
					padding: 26px 22px !important;
					border-radius: 18px !important;
				}
			}

			@media (max-width: 620px) {
				html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template),
				html body main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}

			@media (max-width: 420px) {
				html body main#primary.emo-journal,
				html body main#primary.emo-article-page {
					width: calc(100% - 20px) !important;
					max-width: calc(100% - 20px) !important;
				}

				html body main#primary.emo-article-page .emo-article-content {
					padding: 24px 18px !important;
				}
			}
		</style>
		<script id="elmercado-blog-geometry-guard-010250">
		(() => {
			'use strict';
			const apply = () => {
				const main = document.querySelector('main#primary.emo-journal, main#primary.emo-article-page');
				if (!main) return;
				const mobile = window.matchMedia('(max-width: 820px)').matches;
				const verySmall = window.matchMedia('(max-width: 420px)').matches;
				const width = verySmall ? 'calc(100% - 20px)' : (mobile ? 'calc(100% - 32px)' : 'min(1180px, calc(100% - 48px))');
				main.style.setProperty('display', 'block', 'important');
				main.style.setProperty('float', 'none', 'important');
				main.style.setProperty('clear', 'both', 'important');
				main.style.setProperty('position', 'relative', 'important');
				main.style.setProperty('left', 'auto', 'important');
				main.style.setProperty('right', 'auto', 'important');
				main.style.setProperty('width', width, 'important');
				main.style.setProperty('max-width', verySmall || mobile ? width : '1180px', 'important');
				main.style.setProperty('min-width', '0', 'important');
				main.style.setProperty('flex', mobile ? '0 1 auto' : '0 1 1180px', 'important');
				main.style.setProperty('margin-left', 'auto', 'important');
				main.style.setProperty('margin-right', 'auto', 'important');
				main.style.setProperty('padding', '0', 'important');
				main.style.setProperty('transform', 'none', 'important');
			};
			apply();
			requestAnimationFrame(apply);
			window.addEventListener('resize', apply, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
