<?php
/**
 * Refuerzo visual de "Limpiar todo" 0.10.191.
 *
 * Garantiza que la acción global se diferencie de los chips individuales pese
 * a estilos heredados del sidebar.
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
		<style id="elmercado-active-filter-clear-guard-010191">
			body.elmercado-child-theme.tax-product_cat #secondary.widget-area #emo-category-attribute-filters .emo-active-filter-chips .emo-active-filter-chips__clear,
			body.elmercado-child-theme.tax-product_cat .shop-widget-area #emo-category-attribute-filters .emo-active-filter-chips .emo-active-filter-chips__clear,
			body.elmercado-child-theme.tax-product_cat .emo-mobile-filter-content #emo-category-attribute-filters .emo-active-filter-chips .emo-active-filter-chips__clear {
				display:inline-flex !important;
				align-items:center !important;
				justify-content:center !important;
				min-height:28px !important;
				padding:6px 10px !important;
				border:1px solid #173f32 !important;
				border-radius:999px !important;
				background:#173f32 !important;
				background-image:none !important;
				box-shadow:none !important;
				color:#fff !important;
				font-size:10.5px !important;
				font-weight:800 !important;
				line-height:1 !important;
				text-decoration:none !important;
				opacity:1 !important;
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
		<script id="elmercado-active-filter-clear-guard-controller-010191">
		(() => {
			'use strict';
			const emphasize = () => {
				document.querySelectorAll('#emo-category-attribute-filters .emo-active-filter-chips__clear').forEach((button) => {
					button.style.setProperty('display', 'inline-flex', 'important');
					button.style.setProperty('align-items', 'center', 'important');
					button.style.setProperty('justify-content', 'center', 'important');
					button.style.setProperty('min-height', '28px', 'important');
					button.style.setProperty('padding', '6px 10px', 'important');
					button.style.setProperty('border', '1px solid #173f32', 'important');
					button.style.setProperty('border-radius', '999px', 'important');
					button.style.setProperty('background', '#173f32', 'important');
					button.style.setProperty('background-image', 'none', 'important');
					button.style.setProperty('color', '#fff', 'important');
					button.style.setProperty('font-weight', '800', 'important');
					button.style.setProperty('opacity', '1', 'important');
				});
			};
			emphasize();
			requestAnimationFrame(emphasize);
			setTimeout(emphasize, 220);
			setTimeout(emphasize, 800);
			window.addEventListener('pageshow', emphasize, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
