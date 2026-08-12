<?php
/**
 * Cierre de jerarquía y titulares del sistema de filtros 0.10.197.
 *
 * Evita que controladores históricos alteren visualmente el orden solicitado
 * y aplica el mismo titular destacado a los filtros específicos.
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
		<style id="elmercado-catalog-filter-final-010197">
			/*
			 * La cascada histórica reordena nodos del sidebar con JavaScript.
			 * El orden visual queda definido aquí y ya no depende de ese DOM order.
			 */
			body.elmercado-child-theme.tax-product_cat:not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
				display:flex !important;
				flex-direction:column !important;
			}
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-context {
				order:0 !important;
			}
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > :is(.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter) {
				order:1 !important;
			}
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-global-vendor-filter {
				order:2 !important;
			}
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-attribute-filters,
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-applied-filters-slot-010196 {
				order:3 !important;
			}

			/* Misma cabecera para Tipo, Por peso, Alimentación... que para Vendedor/Precio. */
			body.elmercado-child-theme.tax-product_cat #secondary.widget-area #emo-category-attribute-filters h3.emo-category-filter-title,
			body.elmercado-child-theme.tax-product_cat .shop-widget-area #emo-category-attribute-filters h3.emo-category-filter-title,
			body.elmercado-child-theme.tax-product_cat .emo-mobile-filter-content #emo-category-attribute-filters h3.emo-category-filter-title {
				display:flex !important;
				width:100% !important;
				min-height:32px !important;
				align-items:center !important;
				justify-content:flex-start !important;
				margin:0 0 7px !important;
				padding:6px 9px !important;
				border:1px solid rgba(47,125,93,.10) !important;
				border-left:3px solid #2f7d5d !important;
				border-radius:8px !important;
				background:#f2f6f3 !important;
				background-image:none !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:800 !important;
				letter-spacing:.015em !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:none !important;
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
		<script id="elmercado-catalog-filter-final-controller-010197">
		(() => {
			'use strict';
			const normalizeSpecificTitles = () => {
				document.querySelectorAll('#emo-category-attribute-filters h3.emo-category-filter-title').forEach((title) => {
					title.style.setProperty('display', 'flex', 'important');
					title.style.setProperty('width', '100%', 'important');
					title.style.setProperty('min-height', '32px', 'important');
					title.style.setProperty('align-items', 'center', 'important');
					title.style.setProperty('justify-content', 'flex-start', 'important');
					title.style.setProperty('margin', '0 0 7px', 'important');
					title.style.setProperty('padding', '6px 9px', 'important');
					title.style.setProperty('border', '1px solid rgba(47,125,93,.10)', 'important');
					title.style.setProperty('border-left', '3px solid #2f7d5d', 'important');
					title.style.setProperty('border-radius', '8px', 'important');
					title.style.setProperty('background', '#f2f6f3', 'important');
					title.style.setProperty('color', '#173f32', 'important');
					title.style.setProperty('font-size', '12px', 'important');
					title.style.setProperty('font-weight', '800', 'important');
					title.style.setProperty('line-height', '1.25', 'important');
					title.style.setProperty('text-align', 'left', 'important');
			});
			};

			normalizeSpecificTitles();
			requestAnimationFrame(normalizeSpecificTitles);
			setTimeout(normalizeSpecificTitles, 760);
			setTimeout(normalizeSpecificTitles, 1800);
			window.addEventListener('pageshow', normalizeSpecificTitles, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
