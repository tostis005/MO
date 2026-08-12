<?php
/**
 * Acabado visual de filtros por categoría 0.10.188.
 *
 * Mantiene la categoría activa como contexto discreto y deja los refinamientos
 * en una lista compacta, sin falsos checkboxes ni contadores desalineados.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
			return;
		}

		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
		?>
		<aside id="emo-category-context" class="emo-category-context" hidden aria-label="Categoría activa">
			<div class="emo-category-context__row">
				<strong class="emo-category-context__name"><?php echo esc_html( $term->name ); ?></strong>
				<a
					class="emo-category-context__remove"
					href="<?php echo esc_url( $shop_url ); ?>"
					aria-label="<?php echo esc_attr( 'Quitar categoría ' . $term->name ); ?>"
				>
					<span aria-hidden="true">×</span>
					<span>Quitar</span>
				</a>
			</div>
		</aside>
		<?php
	},
	38
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}
		?>
		<style id="elmercado-category-filter-design-010188">
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(
				.widget_product_categories,
				.widget_product_tag_cloud,
				.widget_tag_cloud,
				.wc-block-product-categories,
				.wp-block-woocommerce-product-categories,
				.wp-block-tag-cloud
			) {
				dis:none !important;
				visibility:hidden !important;
			}

			#emo-category-context[hidden],
			#emo-category-attribute-filters[hidden] {
				display:none !important;
			}

			/* Categoría activa: una sola línea, sin tarjeta dentro de tarjeta. */
			body.elmercado-child-theme.tax-product_cat #emo-category-context {
				dis:block !important;
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 4px !important;
				padding:0 0 13px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.11) !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row {
				dis:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
				min-height:38px !important;
				padding:8px 10px !important;
				border:1px solid rgba(23,63,50,.10) !important;
				border-radius:10px !important;
				background:#f3f7f4 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__name {
				min-width:0 !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:13px !important;
				font-weight:750 !important;
				letter-spacing:0 !important;
				line-height:1.25 !important;
				text-transform:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove {
				dis:inline-flex !important;
				flex:0 0 auto !important;
				align-items:center !important;
				gap:3px !important;
				margin:0 !important;
				padding:3px 2px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				color:#687b72 !important;
				font-size:10.5px !important;
				font-weight:700 !important;
				line-height:1 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove span[aria-hidden="true"] {
				font-size:15px !important;
				font-weight:500 !important;
				line-height:.8 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove:hover,
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove:focus-visible {
				color:#173f32 !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}

			/* Cabecera antigua del perfil: sólo conservamos Limpiar cuando proceda. */
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-attribute-filters__head {
				dis:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters {
				margin:0 !important;
				padding:0 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions {
				dis:flex !important;
				align-items:center !important;
				justify-content:flex-end !important;
				min-height:25px !important;
				margin:0 !important;
				padding:3px 0 0 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions__clear {
				color:#687b72 !important;
				font-size:10.5px !important;
				font-weight:700 !important;
				line-height:1.2 !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}

			/* Títulos compactos y siempre alineados a la izquierda. */
			body.elmercado-child-theme.tax-product_cat #secondary #emo-category-attribute-filters .emo-category-filter-group,
			body.elmercado-child-theme.tax-product_cat .shop-widget-area #emo-category-attribute-filters .emo-category-filter-group,
			body.elmercado-child-theme.tax-product_cat .emo-mobile-filter-content #emo-category-attribute-filters .emo-category-filter-group {
				margin:0 !important;
				padding:11px 0 10px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.085) !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #secondary #emo-category-attribute-filters .emo-category-filter-title,
			body.elmercado-child-theme.tax-product_cat .shop-widget-area #emo-category-attribute-filters .emo-category-filter-title,
			body.elmercado-child-theme.tax-product_cat .emo-mobile-filter-content #emo-category-attribute-filters .emo-category-filter-title {
				dis:block !important;
				min-height:0 !important;
				margin:0 0 5px !important;
				padding:0 2px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:12.5px !important;
				font-weight:750 !important;
				letter-spacing:0 !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:none !important;
			}

			/* Opciones: texto y contador en la misma línea. Sin checkboxes artificiales. */
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list {
				dis:block !important;
				margin:0 !important;
				padding:0 !important;
				list-style:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item {
				dis:flex !important;
				float:none !important;
				position:relative !important;
				align-items:center !important;
				width:100% !important;
				min-height:28px !important;
				margin:0 !important;
				padding:0 4px !important;
				border:0 !important;
				border-radius:7px !important;
				background:transparent !important;
				box-shadow:none !important;
				list-style:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item::before,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item::after,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a::before,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a::after {
				dis:none !important;
				content:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a {
				dis:block !important;
				float:none !important;
				flex:1 1 auto !important;
				min-width:0 !important;
				width:auto !important;
				min-height:0 !important;
				margin:0 !important;
				padding:5px 4px !important;
				border:0 !important;
				background:transparent !important;
				color:#42584f !important;
				font-size:12px !important;
				font-weight:600 !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list .count {
				dis:block !important;
				float:none !important;
				flex:0 0 auto !important;
				position:static !important;
				min-width:18px !important;
				height:auto !important;
				margin:0 2px 0 8px !important;
				padding:0 !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				color:#809088 !important;
				font-size:10.5px !important;
				font-weight:650 !important;
				line-height:1 !important;
				text-align:right !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover {
				background:#f6f8f6 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen {
				background:#edf4ef !important;
				box-shadow:inset 3px 0 0 #2f7d5d !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > a,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > a {
				color:#173f32 !important;
				font-weight:750 !important;
			}

			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-productor .woocommerce-widget-layered-nav-list {
				max-height:230px !important;
				overflow:auto !important;
				padding-right:3px !important;
			}

			/* Precio pertenece al mismo sistema visual y no recupera la antigua cabecera verde. */
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > .widget_price_filter {
				margin:0 !important;
				padding:12px 0 0 !important;
				border:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > .widget_price_filter > :is(.widget-title,.widgettitle) {
				dis:block !important;
				min-height:0 !important;
				margin:0 0 9px !important;
				padding:0 2px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				color:#173f32 !important;
				font-size:12.5px !important;
				font-weight:750 !important;
				letter-spacing:0 !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:none !important;
			}

			@media (min-width:1101px) {
				body.elmercado-child-theme.tax-product_cat:not(.wcfmmp-store-page) #content.site-content > .woostify-container {
					gap:30px !important;
				}
				body.elmercado-child-theme.tax-product_cat:not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					flex:0 0 280px !important;
					width:280px !important;
					min-width:280px !important;
					max-width:280px !important;
					padding:17px !important;
				}
			}

			@media (max-width:1100px) {
				body.elmercado-child-theme.tax-product_cat #emo-category-context {
					margin-bottom:2px !important;
				}
				body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group {
					padding:10px 0 9px !important;
				}
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
		<script id="elmercado-category-filter-design-controller-010188">
		(() => {
			'use strict';
			const context = document.getElementById('emo-category-context');
			const panel = document.getElementById('emo-category-attribute-filters');
			if (!context) return;

			const findSidebar = () => document.querySelector(
				'.emo-mobile-filter-content #secondary.widget-area,' +
				'.emo-mobile-filter-content .shop-widget-area,' +
				'.emo-mobile-filter-content .widget-area,' +
				'#secondary.widget-area,' +
				'.shop-widget-area,' +
				'.content-area + .widget-area'
			);

			const removeLegacyTaxonomyWidgets = (sidebar) => {
				if (!sidebar) return;
				sidebar.querySelectorAll(
					'.widget_product_categories,.widget_product_tag_cloud,.widget_tag_cloud,' +
					'.wc-block-product-categories,.wp-block-woocommerce-product-categories,.wp-block-tag-cloud'
				).forEach((node) => {
					const wrapper = node.closest('.widget,.widget_block');
					if (wrapper && wrapper !== sidebar) wrapper.remove();
					else node.remove();
				});
			};

			const normalizeSpecificPanel = () => {
				if (!panel) return;
				const oldHead = panel.querySelector('.emo-category-attribute-filters__head');
				if (oldHead) {
					const clear = oldHead.querySelector('.emo-category-attribute-filters__clear');
					if (clear) {
						let actions = panel.querySelector('.emo-category-filter-actions');
						if (!actions) {
							actions = document.createElement('div');
							actions.className = 'emo-category-filter-actions';
							panel.prepend(actions);
						}
						clear.className = 'emo-category-filter-actions__clear';
						clear.textContent = 'Limpiar filtros';
						actions.append(clear);
					}
					oldHead.remove();
				}

				panel.querySelectorAll('.woocommerce-widget-layered-nav-list .count').forEach((count) => {
					count.textContent = count.textContent.replace(/[()]/g, '').trim();
				});
			};

			const place = () => {
				const sidebar = findSidebar();
				if (!sidebar) return;
				removeLegacyTaxonomyWidgets(sidebar);
				normalizeSpecificPanel();

				if (context.parentElement !== sidebar || sidebar.firstElementChild !== context) {
					sidebar.prepend(context);
				}
				context.hidden = false;
				context.removeAttribute('aria-hidden');

				if (panel) {
					if (context.nextElementSibling !== panel) context.insertAdjacentElement('afterend', panel);
					panel.hidden = false;
					panel.removeAttribute('aria-hidden');
				}
			};

			place();
			requestAnimationFrame(place);
			setTimeout(place, 160);
			setTimeout(place, 620);
			window.addEventListener('pageshow', place, { passive:true });
			window.addEventListener('resize', () => requestAnimationFrame(place), { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
