<?php
/**
 * Corrección final de layout de filtros por categoría 0.10.189.
 *
 * La capa 0.10.188 contenía propiedades `dis:*` inválidas en los puntos que
 * debían anular el layout heredado. Esta capa deja explícitamente títulos,
 * opciones y contadores en el flujo correcto y sirve de guardia frente a los
 * estilos históricos del sidebar.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}
		?>
		<style id="elmercado-category-filter-display-fix-010189">
			body.elmercado-child-theme.tax-product_cat #secondary.widget-area #emo-category-attribute-filters h3.emo-category-filter-title,
			body.elmercado-child-theme.tax-product_cat .shop-widget-area #emo-category-attribute-filters h3.emo-category-filter-title,
			body.elmercado-child-theme.tax-product_cat .emo-mobile-filter-content #emo-category-attribute-filters h3.emo-category-filter-title {
				display:block !important;
				width:100% !important;
				min-height:0 !important;
				align-items:initial !important;
				justify-content:initial !important;
				margin:0 0 5px !important;
				padding:0 4px !important;
				background:transparent !important;
				color:#173f32 !important;
				font-size:12.5px !important;
				font-weight:750 !important;
				letter-spacing:0 !important;
				line-height:1.3 !important;
				text-align:left !important;
				text-transform:none !important;
			}

			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters ul.woocommerce-widget-layered-nav-list > li.woocommerce-widget-layered-nav-list__item {
				display:flex !important;
				flex-flow:row nowrap !important;
				align-items:center !important;
				justify-content:flex-start !important;
				gap:0 !important;
				width:100% !important;
				min-height:29px !important;
				margin:0 !important;
				padding:0 4px !important;
				border:0 !important;
				border-radius:7px !important;
				background:transparent !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters ul.woocommerce-widget-layered-nav-list > li.woocommerce-widget-layered-nav-list__item > a {
				display:block !important;
				flex:1 1 auto !important;
				width:auto !important;
				min-width:0 !important;
				min-height:0 !important;
				margin:0 !important;
				padding:6px 4px !important;
				text-align:left !important;
		}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters ul.woocommerce-widget-layered-nav-list > li.woocommerce-widget-layered-nav-list__item > span.count {
				display:block !important;
				flex:0 0 auto !important;
				float:none !important;
				position:static !important;
				width:auto !important;
				min-width:20px !important;
				margin:0 3px 0 10px !important;
				padding:0 !important;
				color:#809088 !important;
				font-size:10.5px !important;
				font-weight:650 !important;
				line-height:1 !important;
				text-align:right !important;
				white-space:nowrap !important;
			}

			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters ul.woocommerce-widget-layered-nav-list > li.woocommerce-widget-layered-nav-list__item:hover {
				background:#f6f8f6 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters ul.woocommerce-widget-layered-nav-list > li.woocommerce-widget-layered-nav-list__item--chosen,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters ul.woocommerce-widget-layered-nav-list > li.woocommerce-widget-layered-nav-list__item.chosen {
				background:#edf4ef !important;
				box-shadow:inset 3px 0 0 #2f7d5d !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}
		?>
		<script id="elmercado-category-filter-display-fix-controller-010189">
		(() => {
			'use strict';
			const normalize = () => {
				document.querySelectorAll('#emo-category-attribute-filters .emo-category-filter-title').forEach((title) => {
					title.style.setProperty('display', 'block', 'important');
					title.style.setProperty('width', '100%', 'important');
					title.style.setProperty('text-align', 'left', 'important');
					title.style.setProperty('justify-content', 'initial', 'important');
				});

				document.querySelectorAll('#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item').forEach((item) => {
					const link = item.querySelector(':scope > a');
					const count = item.querySelector(':scope > .count');
					item.style.setProperty('display', 'flex', 'important');
					item.style.setProperty('flex-flow', 'row nowrap', 'important');
					item.style.setProperty('align-items', 'center', 'important');
					if (link) {
						link.style.setProperty('display', 'block', 'important');
						link.style.setProperty('flex', '1 1 auto', 'important');
						link.style.setProperty('width', 'auto', 'important');
						link.style.setProperty('min-width', '0', 'important');
					}
					if (count) {
						count.textContent = count.textContent.replace(/[()]/g, '').trim();
						count.style.setProperty('display', 'block', 'important');
						count.style.setProperty('flex', '0 0 auto', 'important');
						count.style.setProperty('float', 'none', 'important');
						count.style.setProperty('position', 'static', 'important');
						count.style.setProperty('margin-left', 'auto', 'important');
					}
				});
			};

			normalize();
			requestAnimationFrame(normalize);
			setTimeout(normalize, 180);
			setTimeout(normalize, 700);
			window.addEventListener('pageshow', normalize, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
