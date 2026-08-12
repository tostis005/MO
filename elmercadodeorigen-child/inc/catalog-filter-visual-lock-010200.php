<?php
/**
 * Bloqueo final del lenguaje visual del sistema de filtros 0.10.201.
 *
 * Conserva la tarjeta general del sidebar, elimina la doble tarjeta del
 * contexto de categoría, homogeneiza el ritmo de todas las opciones y elimina
 * las flechas decorativas tanto de Categorías como de Vendedor.
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
		<style id="elmercado-catalog-filter-visual-lock-010201">
			/* El panel general recupera la tarjeta blanca original en escritorio. */
			@media (min-width:1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) {
					border:1px solid rgba(23,63,50,.11) !important;
					border-radius:18px !important;
					background:#fff !important;
					box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
				}
			}

			/*
			 * La categoría activa conserva únicamente su tarjeta interior.
			 * El aside exterior no vuelve a generar una segunda caja/fondo.
			 */
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-context {
				margin:0 0 4px !important;
				padding:0 0 13px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}

			/* Titulares editoriales ya aprobados: texto + regla, nunca tarjeta. */
			.emo-filter-heading-refined-010200::after {
				content:"" !important;
				display:block !important;
				width:100% !important;
				height:1px !important;
				margin:0 !important;
				background:rgba(23,63,50,.16) !important;
			}

			/* Ritmo común de opciones: Categorías, Vendedor y atributos. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories li,
				#emo-global-vendor-filter .emo-global-vendor-filter__item,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item
			) {
				box-sizing:border-box !important;
				min-height:32px !important;
				margin:0 !important;
				padding:1px 4px !important;
				border:0 !important;
				border-radius:8px !important;
				background:transparent !important;
				box-shadow:none !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories li > a,
				#emo-global-vendor-filter .emo-global-vendor-filter__item > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a
			) {
				box-sizing:border-box !important;
				min-height:0 !important;
				margin:0 !important;
				padding:6px 4px !important;
				border:0 !important;
				background:transparent !important;
				color:#42584f !important;
				font-size:12px !important;
				font-weight:600 !important;
				line-height:1.3 !important;
				text-decoration:none !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories li,
				#emo-global-vendor-filter .emo-global-vendor-filter__item,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item
			):hover {
				background:#f4f7f5 !important;
			}

			/* Selección común, más visible y sin barra lateral ni indicador especial. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories .current-cat,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen
			) {
				border:0 !important;
				border-radius:8px !important;
				background:#e2efe7 !important;
				box-shadow:none !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories .current-cat > a,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > a
			) {
				color:#155b42 !important;
				font-weight:800 !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories .current-cat > .count,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > .count,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > .count,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > .count
			) {
				color:#155b42 !important;
				font-weight:750 !important;
			}

			/* El estado activo ya no necesita punto/barra: todos usan el mismo fondo. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			.widget_product_categories .current-cat > a::before,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > a::before {
				content:none !important;
				display:none !important;
			}

			/* Ninguna flecha decorativa en Categorías ni Vendedor. */
			a.emo-category-link-no-arrow-010200::after,
			a.emo-vendor-link-no-arrow-010201::after,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			#emo-global-vendor-filter .emo-global-vendor-filter__item > a::after {
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
		<script id="elmercado-catalog-filter-visual-lock-controller-010201">
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

			const styleOptionRow = (row) => {
				if (!(row instanceof HTMLElement)) return;
				[
					['min-height', '32px'],
					['margin', '0px'],
					['padding', '1px 4px'],
					['border', '0px'],
					['border-radius', '8px'],
					['background', 'transparent'],
					['box-shadow', 'none'],
				].forEach(([property, value]) => setImportant(row, property, value));

				const link = row.querySelector(':scope > a');
				if (link instanceof HTMLElement) {
					setImportant(link, 'min-height', '0px');
					setImportant(link, 'margin', '0px');
					setImportant(link, 'padding', '6px 4px');
					setImportant(link, 'color', 'rgb(66, 88, 79)');
					setImportant(link, 'font-size', '12px');
					setImportant(link, 'font-weight', '600');
					setImportant(link, 'line-height', '1.3');
				}
			};

			const styleSelection = (row) => {
				if (!(row instanceof HTMLElement)) return;
				setImportant(row, 'border', '0px');
				setImportant(row, 'border-radius', '8px');
				setImportant(row, 'background', 'rgb(226, 239, 231)');
				setImportant(row, 'box-shadow', 'none');

				const link = row.querySelector(':scope > a');
				if (link instanceof HTMLElement) {
					setImportant(link, 'color', 'rgb(21, 91, 66)');
					setImportant(link, 'font-weight', '800');
				}

				const count = row.querySelector(':scope > .count');
				if (count instanceof HTMLElement) {
					setImportant(count, 'color', 'rgb(21, 91, 66)');
					setImportant(count, 'font-weight', '750');
				}
			};

			const removeArrows = () => {
				document.querySelectorAll(
					'.widget_product_categories li > a,' +
					'.wc-block-product-categories li > a,' +
					'.wp-block-woocommerce-product-categories li > a'
				).forEach((link) => {
					if (!(link instanceof HTMLElement)) return;
					link.classList.add('emo-category-link-no-arrow-010200');
					link.querySelectorAll(':scope > svg,:scope > i,:scope > .arrow,:scope > .caret,:scope > .chevron,:scope > .woostify-svg-icon').forEach((icon) => icon.remove());
				});

				document.querySelectorAll('#emo-global-vendor-filter .emo-global-vendor-filter__item > a').forEach((link) => {
					if (!(link instanceof HTMLElement)) return;
					link.classList.add('emo-vendor-link-no-arrow-010201');
					link.querySelectorAll(':scope > svg,:scope > i,:scope > .arrow,:scope > .caret,:scope > .chevron,:scope > .woostify-svg-icon').forEach((icon) => icon.remove());
				});

				document.querySelectorAll(
					'.widget_product_categories li > svg,.widget_product_categories li > i,.widget_product_categories li > .arrow,.widget_product_categories li > .caret,.widget_product_categories li > .chevron,.widget_product_categories li > .woostify-svg-icon,.widget_product_categories li > .cat-toggle,.widget_product_categories li > .category-toggle,' +
					'#emo-global-vendor-filter .emo-global-vendor-filter__item > svg,#emo-global-vendor-filter .emo-global-vendor-filter__item > i,#emo-global-vendor-filter .emo-global-vendor-filter__item > .arrow,#emo-global-vendor-filter .emo-global-vendor-filter__item > .caret,#emo-global-vendor-filter .emo-global-vendor-filter__item > .chevron,#emo-global-vendor-filter .emo-global-vendor-filter__item > .woostify-svg-icon'
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
					'.widget_product_categories li,' +
					'#emo-global-vendor-filter .emo-global-vendor-filter__item,' +
					'#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item'
				).forEach(styleOptionRow);

				document.querySelectorAll(
					'.widget_product_categories .current-cat,' +
					'#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,' +
					'#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,' +
					'#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen'
				).forEach(styleSelection);

				removeArrows();
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
