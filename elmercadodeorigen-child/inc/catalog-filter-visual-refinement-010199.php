<?php
/**
 * Refinamiento visual final de filtros 0.10.199.
 *
 * - Los títulos se leen como encabezados editoriales, no como opciones activas.
 * - Las selecciones abandonan el fondo y la barra lateral.
 * - Se eliminan las flechas redundantes de categorías.
 * - En categoría se elimina el marco exterior del sidebar, manteniendo el
 *   borde propio del contexto de categoría activa.
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
		<style id="elmercado-catalog-filter-visual-refinement-010199">
			/*
			 * Encabezados de filtro: etiqueta editorial + regla horizontal.
			 * Sin caja, sin fondo y sin barra lateral para que nunca puedan
			 * confundirse con una opción seleccionada.
			 */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_price_filter > .widget-title,
				.widget_price_filter > .widgettitle,
				.widget_product_categories > .widget-title,
				.widget_product_categories > .widgettitle,
				#emo-global-vendor-filter > .emo-global-vendor-filter__title,
				#emo-category-attribute-filters h3.emo-category-filter-title
			) {
				display:grid !important;
				grid-template-columns:max-content minmax(24px,1fr) !important;
				align-items:center !important;
				column-gap:10px !important;
				width:100% !important;
				min-height:0 !important;
				margin:0 0 8px !important;
				padding:1px 1px 7px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				background-image:none !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:10.5px !important;
				font-weight:800 !important;
				letter-spacing:.085em !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:uppercase !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_price_filter > .widget-title,
				.widget_price_filter > .widgettitle,
				.widget_product_categories > .widget-title,
				.widget_product_categories > .widgettitle,
				#emo-global-vendor-filter > .emo-global-vendor-filter__title,
				#emo-category-attribute-filters h3.emo-category-filter-title
			)::after {
				content:"" !important;
				display:block !important;
				width:100% !important;
				height:1px !important;
				margin:0 !important;
				background:rgba(23,63,50,.16) !important;
			}

			/*
			 * Selección: sin tarjeta, sin fondo y sin línea lateral.
			 * El estado se comunica con tipografía/color y, cuando no existe
			 * casilla nativa, con un pequeño punto de selección.
			 */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(
				.widget_product_categories .current-cat,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen
			) {
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
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
				color:#1f684d !important;
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
				color:#1f684d !important;
				font-weight:750 !important;
			}

			/* Punto discreto solo para categorías/vendedor; los atributos ya usan checkbox. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			.widget_product_categories .current-cat > a::before,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > a::before {
				content:"" !important;
				display:inline-block !important;
				width:6px !important;
				height:6px !important;
				margin:0 8px 1px 0 !important;
				border:0 !important;
				border-radius:50% !important;
				background:#2f7d5d !important;
				box-shadow:none !important;
				transform:none !important;
				vertical-align:middle !important;
			}

			/*
			 * Las flechas de categorías son redundantes: ya existe nombre + contador.
			 * Cubrimos pseudo-elementos, SVG e iconos habituales del tema/widget.
			 */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(.widget_product_categories,.wc-block-product-categories,.wp-block-woocommerce-product-categories)
			li > a::after,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(.widget_product_categories,.wc-block-product-categories,.wp-block-woocommerce-product-categories)
			li > a > :is(svg,i,.arrow,.caret,.chevron,.woostify-svg-icon),
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat)
			:is(#secondary.widget-area,.shop-widget-area)
			:is(.widget_product_categories,.wc-block-product-categories,.wp-block-woocommerce-product-categories)
			li > :is(svg,i,.arrow,.caret,.chevron,.woostify-svg-icon,.cat-toggle,.category-toggle) {
				content:none !important;
				display:none !important;
				width:0 !important;
				height:0 !important;
				margin:0 !important;
				padding:0 !important;
			}

			/* Refuerzo de la Home: nunca mostrar una flecha junto al contador. */
			body.home.elmercado-child-theme .emo-home .emo-categories .emo-category-card > svg,
			body.home.elmercado-child-theme .emo-home .emo-categories .emo-category-card__content > svg,
			body.home.elmercado-child-theme .emo-home .emo-categories .emo-category-card :is(.arrow,.caret,.chevron) {
				display:none !important;
			}

			/*
			 * Categoría activa: conservar su propia tarjeta, pero eliminar el marco
			 * exterior del sidebar para evitar el efecto borde dentro de borde.
			 */
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) {
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}

			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-context {
				border:1px solid rgba(47,125,93,.16) !important;
				border-radius:9px !important;
				background:#f4f7f5 !important;
				box-shadow:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
