<?php
/**
 * Bloqueo del lenguaje visual refinado de filtros 0.10.200.
 *
 * Algunas capas históricas mantienen estilos de titular con !important. Esta
 * capa asigna el nuevo lenguaje directamente a los nodos finales para evitar
 * que Precio, Categorías o Vendedor recuperen el diseño de tarjeta anterior.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) || ( ! is_shop() && ! is_product_category() ) ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-visual-lock-010200">
			.emo-filter-heading-refined-010200::after {
				content:"" !important;
				display:block !important;
				width:100% !important;
				height:1px !important;
				margin:0 !important;
				background:rgba(23,63,50,.16) !important;
			}

			a.emo-category-link-no-arrow-010200::after {
				content:none !important;
				display:none !important;
				width:0 !important;
				height:0 !important;
				margin:0 !important;
				padding:0 !important;
			}

			body.home.elmercado-child-theme .emo-category-card::after,
			body.home.elmercado-child-theme .emo-category-card__content::after {
				content:none !important;
				display:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) || ( ! is_shop() && ! is_product_category() ) ) {
			return;
		}
		?>
		<script id="elmercado-catalog-filter-visual-lock-controller-010200">
		(() => {
			'use strict';

			const setImportant = (node, property, value) => {
				if (!(node instanceof HTMLElement)) return;
				if (node.style.getPropertyValue(property) === value && node.style.getPropertyPriority(property) === 'important') return;
				node.style.setProperty(property, value, 'important');
			};

			const styleHeading = (title) => {
				if (!(title instanceof HTMLElement)) return;
				title.classList.add('emo-filter-heading-refined-010200');
				[
					['display', 'grid'],
					['grid-template-columns', 'max-content minmax(24px, 1fr)'],
					['align-items', 'center'],
					['justify-content', 'initial'],
					['column-gap', '10px'],
					['width', '100%'],
					['min-height', '0px'],
					['margin', '0px 0px 8px'],
					['padding', '1px 1px 7px'],
					['border', '0px'],
					['border-left', '0px'],
					['border-radius', '0px'],
					['background', 'transparent'],
					['background-image', 'none'],
					['box-shadow', 'none'],
					['color', 'rgb(23, 63, 50)'],
					['font-size', '10.5px'],
					['font-weight', '800'],
					['letter-spacing', '0.085em'],
					['line-height', '1.25'],
					['text-align', 'left'],
					['text-transform', 'uppercase'],
				].forEach(([property, value]) => setImportant(title, property, value));
			};

			const styleSelection = (row) => {
				if (!(row instanceof HTMLElement)) return;
				setImportant(row, 'border', '0px');
				setImportant(row, 'border-radius', '0px');
				setImportant(row, 'background', 'transparent');
				setImportant(row, 'box-shadow', 'none');

				const link = row.querySelector(':scope > a');
				if (link instanceof HTMLElement) {
					setImportant(link, 'color', 'rgb(31, 104, 77)');
					setImportant(link, 'font-weight', '800');
				}

				const count = row.querySelector(':scope > .count');
				if (count instanceof HTMLElement) {
					setImportant(count, 'color', 'rgb(31, 104, 77)');
					setImportant(count, 'font-weight', '750');
				}
			};

			const removeCategoryArrows = () => {
				document.querySelectorAll(
					'.widget_product_categories li > a,' +
					'.wc-block-product-categories li > a,' +
					'.wp-block-woocommerce-product-categories li > a'
				).forEach((link) => {
					if (!(link instanceof HTMLElement)) return;
					link.classList.add('emo-category-link-no-arrow-010200');
					link.querySelectorAll(':scope > svg,:scope > i,:scope > .arrow,:scope > .caret,:scope > .chevron,:scope > .woostify-svg-icon').forEach((icon) => icon.remove());
				});

				document.querySelectorAll(
					'.widget_product_categories li > svg,.widget_product_categories li > i,.widget_product_categories li > .arrow,.widget_product_categories li > .caret,.widget_product_categories li > .chevron,.widget_product_categories li > .woostify-svg-icon,.widget_product_categories li > .cat-toggle,.widget_product_categories li > .category-toggle'
				).forEach((icon) => icon.remove());
			};

			const refine = () => {
				document.querySelectorAll(
					'.widget_price_filter > .widget-title,' +
					'.widget_price_filter > .widgettitle,' +
					'.widget_product_categories > .widget-title,' +
					'.widget_product_categories > .widgettitle,' +
					'#emo-global-vendor-filter > .emo-global-vendor-filter__title,' +
					'#emo-category-attribute-filters h3.emo-category-filter-title'
				).forEach(styleHeading);

				document.querySelectorAll(
					'.widget_product_categories .current-cat,' +
					'#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,' +
					'#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,' +
					'#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen'
				).forEach(styleSelection);

				removeCategoryArrows();

				if (document.body.classList.contains('tax-product_cat')) {
					const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
					if (sidebar instanceof HTMLElement) {
						setImportant(sidebar, 'border', '0px');
						setImportant(sidebar, 'border-radius', '0px');
						setImportant(sidebar, 'background', 'transparent');
						setImportant(sidebar, 'box-shadow', 'none');
					}
				}
			};

			let scheduled = false;
			const schedule = () => {
				if (scheduled) return;
				scheduled = true;
				requestAnimationFrame(() => {
					scheduled = false;
					refine();
				});
			};

			refine();
			requestAnimationFrame(refine);
			setTimeout(refine, 250);
			setTimeout(refine, 900);
			setTimeout(refine, 2100);
			setTimeout(refine, 3600);
			window.addEventListener('pageshow', refine, { passive:true });
			window.addEventListener('resize', schedule, { passive:true });

			const observer = new MutationObserver(schedule);
			observer.observe(document.body, { childList:true, subtree:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
