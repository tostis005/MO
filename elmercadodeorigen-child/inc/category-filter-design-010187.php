<?php
/**
 * Acabado contextual y compacto de filtros por categoría 0.10.187.
 *
 * Separa la categoría activa de los filtros refinables, elimina navegación
 * redundante de categorías/etiquetas dentro de archivos de categoría y corrige
 * la alineación visual de términos y contadores de WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cabecera contextual de la categoría actual.
 */
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
			<span class="emo-category-context__label">Categoría</span>
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

/**
 * CSS crítico y capa final del rail de filtros en categorías.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}
		?>
		<style id="elmercado-category-filter-design-010187">
			/* No tiene sentido navegar categorías o etiquetas una vez dentro de una categoría. */
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(
				.widget_product_categories,
				.widget_product_tag_cloud,
				.widget_tag_cloud,
				.wc-block-product-categories,
				.wp-block-woocommerce-product-categories,
				.wp-block-tag-cloud
			) {
				display:none !important;
				visibility:hidden !important;
			}

			#emo-category-context[hidden],
			#emo-category-attribute-filters[hidden] {
				display:none !important;
			}

			/* Categoría activa: contexto claro, compacto y separable del resto de filtros. */
			body.elmercado-child-theme.tax-product_cat #emo-category-context {
				box-sizing:border-box !important;
				display:block !important;
				width:100% !important;
				margin:0 0 15px !important;
				padding:12px 13px !important;
				border:1px solid rgba(23,63,50,.12) !important;
				border-radius:13px !important;
				background:#f4f7f3 !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__label {
				display:block !important;
				margin:0 0 5px !important;
				color:#6c7c75 !important;
				font-size:10px !important;
				font-weight:800 !important;
				letter-spacing:.08em !important;
				line-height:1.2 !important;
				text-transform:uppercase !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row {
				dis:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__name {
				min-width:0 !important;
				color:#173f32 !important;
				font-size:14px !important;
				font-weight:800 !important;
				letter-spacing:0 !important;
				line-height:1.25 !important;
				text-transform:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove {
				dis:inline-flex !important;
				flex:0 0 auto !important;
				min-height:28px !important;
				align-items:center !important;
				justify-content:center !important;
				gap:4px !important;
				margin:0 !important;
				padding:4px 8px !important;
				border:1px solid rgba(23,63,50,.15) !important;
				border-radius:999px !important;
				background:#fff !important;
				color:#496158 !important;
				font-size:11px !important;
				font-weight:750 !important;
				line-height:1 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove:hover,
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove:focus-visible {
				border-color:rgba(47,125,93,.34) !important;
				background:#eaf2ed !important;
				color:#173f32 !important;
			}

			/* La antigua cabecera del bloque específico queda sustituida por el contexto anterior. */
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-attribute-filters__head {
				dis:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters {
				margin:0 0 12px !important;
				padding:0 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions {
				dis:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
				margin:0 !important;
				padding:0 0 8px !important;
				border-bottom:1px solid rgba(23,63,50,.10) !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions__label {
				color:#6c7c75 !important;
				font-size:10px !important;
				font-weight:800 !important;
				letter-spacing:.08em !important;
				line-height:1.2 !important;
				text-transform:uppercase !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions__clear {
				color:#496158 !important;
				font-size:11px !important;
				font-weight:700 !important;
				line-height:1.2 !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}

			/* Todas las cabeceras del rail se vuelven editoriales y compactas. */
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) :is(
				.widget-title,
				.widgettitle,
				.sidebar-heading,
				.widget-heading,
				.wp-block-heading
			) {
				dis:block !important;
				min-height:0 !important;
				margin:0 0 7px !important;
				padding:0 !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				color:#173f32 !important;
				box-shadow:none !important;
				font-family:inherit !important;
				font-size:13px !important;
				font-weight:800 !important;
				letter-spacing:0 !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group {
				margin:0 !important;
				padding:11px 0 !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.09) !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group:last-child {
				border-bottom:0 !important;
			}

			/* Una fila = control + texto a la izquierda, contador estable a la derecha. */
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list {
				dis:block !important;
				margin:0 !important;
				padding:0 !important;
				list-style:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item {
				dis:grid !important;
				grid-template-columns:minmax(0,1fr) auto !important;
				align-items:center !important;
				column-gap:9px !important;
				min-height:31px !important;
				margin:0 !important;
				padding:0 !important;
				border:0 !important;
				list-style:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item::before,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item::after {
				dis:none !important;
				content:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a {
				position:relative !important;
				dis:flex !important;
				min-width:0 !important;
				min-height:31px !important;
				align-items:center !important;
				margin:0 !important;
				padding:4px 3px 4px 24px !important;
				border:0 !important;
				background:transparent !important;
				color:#334c43 !important;
				font-size:12.5px !important;
				font-weight:600 !important;
				line-height:1.3 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a::before {
				content:"" !important;
				dis:block !important;
				position:absolute !important;
				left:1px !important;
				top:50% !important;
				width:14px !important;
				height:14px !important;
				margin:0 !important;
				border:1.5px solid #9eaaa4 !important;
				border-radius:4px !important;
				background:#fff !important;
				box-shadow:none !important;
				font-family:inherit !important;
				font-size:0 !important;
				line-height:0 !important;
				transform:translateY(-50%) !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a::after {
				dis:none !important;
				content:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > a,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > a {
				color:#173f32 !important;
				font-weight:800 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > a::before,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > a::before {
				border-color:#2f7d5d !important;
				background:#2f7d5d !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover > a {
				color:#173f32 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .woocommerce-widget-layered-nav-list .count {
				dis:inline-flex !important;
				float:none !important;
				position:static !important;
				min-width:25px !important;
				height:20px !important;
				align-items:center !important;
				justify-content:center !important;
				justify-self:end !important;
				margin:0 !important;
				padding:0 6px !important;
				border:0 !important;
				border-radius:999px !important;
				background:#f1f4f2 !important;
				color:#75857e !important;
				font-size:10.5px !important;
				font-weight:700 !important;
				line-height:20px !important;
				text-align:center !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-productor .woocommerce-widget-layered-nav-list {
				max-height:245px !important;
				overflow:auto !important;
				padding-right:4px !important;
			}

			/* El rail gana aire sin comprometer la parrilla de producto. */
			@media (min-width:1101px) {
				body.elmercado-child-theme.tax-product_cat:not(.wcfmmp-store-page) #content.site-content > .woostify-container {
					gap:30px !important;
				}
				body.elmercado-child-theme.tax-product_cat:not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					flex:0 0 282px !important;
					width:282px !important;
					min-width:282px !important;
					max-width:282px !important;
					padding:16px !important;
				}
			}

			@media (max-width:1100px) {
				body.elmercado-child-theme.tax-product_cat #emo-category-context {
					margin-bottom:13px !important;
				}
				body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group {
					padding:10px 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Mueve el contexto al sidebar canónico, retira widgets redundantes y neutraliza
 * la cabecera antigua del bloque específico sin provocar parpadeos.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}
		?>
		<script id="elmercado-category-filter-design-controller-010187">
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
				const direct = sidebar.querySelectorAll(
					'.widget_product_categories,.widget_product_tag_cloud,.widget_tag_cloud,' +
					'.wc-block-product-categories,.wp-block-woocommerce-product-categories,.wp-block-tag-cloud'
				);
				direct.forEach((node) => {
					const wrapper = node.closest('.widget,.widget_block');
					if (wrapper && wrapper !== sidebar) wrapper.remove();
					else node.remove();
				});
			};

			const normalizeSpecificPanel = () => {
				if (!panel) return;
				const oldHead = panel.querySelector('.emo-category-attribute-filters__head');
				if (!oldHead) return;
				const clear = oldHead.querySelector('.emo-category-attribute-filters__clear');
				if (clear) {
					let actions = panel.querySelector('.emo-category-filter-actions');
					if (!actions) {
						actions = document.createElement('div');
						actions.className = 'emo-category-filter-actions';
						const label = document.createElement('span');
						label.className = 'emo-category-filter-actions__label';
						label.textContent = 'Filtros';
						actions.append(label);
						panel.prepend(actions);
					}
					clear.className = 'emo-category-filter-actions__clear';
					clear.textContent = 'Limpiar';
					actions.append(clear);
				}
				oldHead.remove();
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
			setTimeout(place, 180);
			setTimeout(place, 720);
			setTimeout(place, 1100);
			window.addEventListener('pageshow', place, { passive:true });
			window.addEventListener('resize', () => requestAnimationFrame(place), { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
