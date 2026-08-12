<?php
/**
 * Sistema definitivo y consolidado de filtros de catálogo 0.10.202.
 *
 * Mantiene la lógica funcional existente y concentra aquí el acabado visual,
 * el contexto de categoría, los contadores y la jerarquía del sidebar. No
 * aplica estilos desde JavaScript: toda la presentación se resuelve en <head>
 * para evitar cambios visuales posteriores al primer render.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuenta productos de un vendedor en el contexto actual.
 */
function elmercado_catalog_vendor_count_010202( int $vendor_id ): int {
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'author'                 => $vendor_id,
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => array( (int) $term->term_id ),
					'include_children' => true,
				),
			);
		}
	}

	$query = new WP_Query( $args );
	return (int) $query->found_posts;
}

/**
 * Contadores de vendedor que el marcado histórico todavía no imprime.
 *
 * @return array<string,int>
 */
function elmercado_catalog_vendor_counts_010202(): array {
	$counts = array();
	if ( ! function_exists( 'elmercado_core_filter_vendors' ) ) {
		return $counts;
	}

	foreach ( elmercado_core_filter_vendors() as $vendor_id => $vendor_name ) {
		$count = elmercado_catalog_vendor_count_010202( (int) $vendor_id );
		if ( $count > 0 ) {
			$counts[ (string) $vendor_id ] = $count;
		}
	}

	return $counts;
}

/**
 * Contexto de categoría: una única tarjeta interior con acción Quitar.
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
			<div class="emo-category-context__row">
				<strong class="emo-category-context__name"><?php echo esc_html( $term->name ); ?></strong>
				<a class="emo-category-context__remove" href="<?php echo esc_url( $shop_url ); ?>" aria-label="<?php echo esc_attr( 'Quitar categoría ' . $term->name ); ?>">
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
 * Única hoja final del sistema de filtros.
 */
add_action(
	'wp_head',
	static function (): void {
		$is_catalog = function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && ( is_shop() || is_product_category() );
		$is_home    = function_exists( 'is_front_page' ) && is_front_page();
		if ( is_admin() || ( ! $is_catalog && ! $is_home ) ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-system-010202">
			/* Home: contador sí, flecha no. */
			body.home.elmercado-child-theme .emo-category-card > svg,
			body.home.elmercado-child-theme .emo-category-card::after,
			body.home.elmercado-child-theme .emo-category-card__content::after {
				display:none !important;
				content:none !important;
			}
			body.home.elmercado-child-theme .emo-category-card > .emo-category-card__content {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) auto !important;
				align-items:center !important;
				gap:10px !important;
				padding-top:12px !important;
				padding-bottom:12px !important;
			}
			body.home.elmercado-child-theme .emo-category-card__content small {
				margin:0 !important;
				white-space:nowrap !important;
				text-align:right !important;
			}

			/* Panel general: se conserva la tarjeta exterior que ya funcionaba. */
			@media (min-width:1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) {
					box-sizing:border-box !important;
					display:flex !important;
					flex-direction:column !important;
					width:250px !important;
					min-width:250px !important;
					max-width:250px !important;
					padding:18px !important;
					border:1px solid rgba(23,63,50,.11) !important;
					border-radius:18px !important;
					background:#fff !important;
					box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
				}
			}

			/* Orden final. El DOM puede moverse sin cambiar lo que ve el usuario. */
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) {
				display:flex !important;
				flex-direction:column !important;
			}
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-context { order:0 !important; }
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > .emo-active-filter-chips[data-emo-global-active-filters="true"] { order:1 !important; }
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > :is(.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter) { order:2 !important; }
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-global-vendor-filter { order:3 !important; }
			body.elmercado-child-theme.tax-product_cat :is(#secondary.widget-area,.shop-widget-area) > #emo-category-attribute-filters { order:4 !important; }

			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) {
				display:flex !important;
				flex-direction:column !important;
			}
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) > .emo-active-filter-chips[data-emo-global-active-filters="true"] { order:0 !important; }
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) > :is(.widget_price_filter,.wc-block-price-filter,.wp-block-woocommerce-price-filter) { order:1 !important; }
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) > .widget_product_categories { order:2 !important; }
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area) > #emo-global-vendor-filter { order:3 !important; }

			#emo-category-context[hidden],
			#emo-global-vendor-filter[hidden],
			#emo-category-attribute-filters[hidden] { display:none !important; }

			/* Categoría activa: solo la tarjeta interior, nunca una caja exterior duplicada. */
			body.elmercado-child-theme.tax-product_cat #emo-category-context {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 14px !important;
				padding:0 !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__row {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
				min-height:38px !important;
				padding:8px 10px !important;
				border:1px solid rgba(23,63,50,.10) !important;
				border-radius:10px !important;
				background:#f3f7f4 !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__name {
				min-width:0 !important;
				color:#173f32 !important;
				font-size:13px !important;
				font-weight:750 !important;
				line-height:1.25 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove {
				display:inline-flex !important;
				align-items:center !important;
				gap:3px !important;
				padding:3px 2px !important;
				border:0 !important;
				background:transparent !important;
				color:#687b72 !important;
				font-size:10.5px !important;
				font-weight:700 !important;
				line-height:1 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove:hover,
			body.elmercado-child-theme.tax-product_cat #emo-category-context .emo-category-context__remove:focus-visible {
				color:#173f32 !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}

			/* Secciones: aire entre filtros, sin rayas completas entre grupos. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) > :is(
				.widget_price_filter,
				.widget_product_categories,
				#emo-global-vendor-filter,
				#emo-category-attribute-filters
			) {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 12px !important;
				padding:0 !important;
				border:0 !important;
				border-bottom:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-group {
				margin:0 0 5px !important;
				padding:7px 0 8px !important;
				border:0 !important;
				border-bottom:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}

			/* El antiguo "Limpiar filtros" desaparece: solo queda "Limpiar todo". */
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-attribute-filters__head,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-attribute-filters__clear,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions__clear {
				display:none !important;
			}

			/* Titulares: texto editorial + regla corta, sin caja ni fondo. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_price_filter > .widget-title,
				.widget_price_filter > .widgettitle,
				.widget_product_categories > .widget-title,
				.widget_product_categories > .widgettitle,
				#emo-global-vendor-filter > .emo-global-vendor-filter__title,
				#emo-category-attribute-filters .emo-category-filter-title
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
				border-left:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				background-image:none !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-size:10.5px !important;
				font-weight:800 !important;
				letter-spacing:.085em !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:uppercase !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_price_filter > .widget-title,
				.widget_price_filter > .widgettitle,
				.widget_product_categories > .widget-title,
				.widget_product_categories > .widgettitle,
				#emo-global-vendor-filter > .emo-global-vendor-filter__title,
				#emo-category-attribute-filters .emo-category-filter-title
			)::after {
				content:"" !important;
				display:block !important;
				width:100% !important;
				height:1px !important;
				background:rgba(23,63,50,.16) !important;
			}

			/* Listas con gap real para que dos seleccionados nunca se fusionen. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories > ul,
				.widget_product_categories ul.product-categories,
				#emo-global-vendor-filter .emo-global-vendor-filter__list,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list
			) {
				display:grid !important;
				gap:3px !important;
				margin:0 !important;
				padding:0 !important;
				list-style:none !important;
			}

			/* Misma métrica para Categorías, Vendedor y atributos. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories li,
				#emo-global-vendor-filter .emo-global-vendor-filter__item,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item
			) {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) auto !important;
				align-items:center !important;
				column-gap:8px !important;
				box-sizing:border-box !important;
				min-height:32px !important;
				margin:0 !important;
				padding:1px 4px !important;
				border:0 !important;
				border-radius:8px !important;
				background:transparent !important;
				box-shadow:none !important;
				list-style:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .widget_product_categories ul.children {
				grid-column:1 / -1 !important;
				width:100% !important;
				margin:2px 0 !important;
				padding-left:12px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories li > a,
				#emo-global-vendor-filter .emo-global-vendor-filter__item > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item > a
			) {
				display:block !important;
				min-width:0 !important;
				min-height:0 !important;
				margin:0 !important;
				padding:6px 4px !important;
				border:0 !important;
				background:transparent !important;
				color:#42584f !important;
				font-size:12px !important;
				font-weight:650 !important;
				line-height:1.3 !important;
				text-align:left !important;
				text-decoration:none !important;
			}

			/* Hover y seleccionado son visualmente iguales y nunca cambian de peso. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories li:hover,
				.widget_product_categories .current-cat,
				#emo-global-vendor-filter .emo-global-vendor-filter__item:hover,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen
			) {
				background:#d9ede0 !important;
				box-shadow:inset 0 0 0 1px rgba(47,125,93,.18) !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories li:hover > a,
				.widget_product_categories .current-cat > a,
				#emo-global-vendor-filter .emo-global-vendor-filter__item:hover > a,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > a,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > a
			) {
				color:#155b42 !important;
				font-weight:650 !important;
			}

			/* Contadores iguales y estables. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories .count,
				#emo-global-vendor-filter .count,
				#emo-category-attribute-filters .count
			) {
				display:inline-flex !important;
				align-items:center !important;
				justify-content:flex-end !important;
				min-width:22px !important;
				margin:0 1px 0 auto !important;
				padding:0 !important;
				border:0 !important;
				background:transparent !important;
				color:#809088 !important;
				font-size:10.5px !important;
				font-weight:650 !important;
				line-height:1 !important;
				text-align:right !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories li:hover > .count,
				.widget_product_categories .current-cat > .count,
				#emo-global-vendor-filter .emo-global-vendor-filter__item:hover > .count,
				#emo-global-vendor-filter .emo-global-vendor-filter__item.is-active > .count,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item:hover > .count,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item.chosen > .count,
				#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen > .count
			) {
				color:#155b42 !important;
			}

			/* Sin flechas decorativas ni pseudo-elementos que compitan con el contador. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) :is(#secondary.widget-area,.shop-widget-area) :is(
				.widget_product_categories li > a,
				#emo-global-vendor-filter .emo-global-vendor-filter__item > a
			)::after,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .widget_product_categories li > :is(svg,i,.arrow,.caret,.chevron,.woostify-svg-icon,.cat-toggle,.category-toggle),
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) #emo-global-vendor-filter .emo-global-vendor-filter__item > :is(svg,i,.arrow,.caret,.chevron,.woostify-svg-icon) {
				display:none !important;
				content:none !important;
			}

			/* Filtros aplicados: bloque único, sin segundo limpiar ni separador inferior. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 14px !important;
				padding:0 !important;
				border:0 !important;
				border-bottom:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__head {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:8px !important;
				margin:0 0 8px !important;
				padding:0 1px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__head strong {
				color:#173f32 !important;
				font-size:11px !important;
				font-weight:800 !important;
				letter-spacing:.04em !important;
				text-transform:uppercase !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__clear {
				display:inline-flex !important;
				align-items:center !important;
				justify-content:center !important;
				min-height:28px !important;
				padding:6px 10px !important;
				border:1px solid #173f32 !important;
				border-radius:999px !important;
				background:#173f32 !important;
				color:#fff !important;
				font-size:10.5px !important;
				font-weight:800 !important;
				line-height:1 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips__list {
				display:flex !important;
				flex-wrap:wrap !important;
				gap:6px !important;
				margin:0 !important;
				padding:0 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip {
				display:inline-flex !important;
				max-width:100% !important;
				align-items:center !important;
				gap:4px !important;
				min-height:27px !important;
				padding:5px 8px !important;
				border:1px solid rgba(47,125,93,.18) !important;
				border-radius:999px !important;
				background:#edf4ef !important;
				color:#294c3f !important;
				font-size:10.5px !important;
				font-weight:650 !important;
				line-height:1.15 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip:hover,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chip:focus-visible {
				background:#d9ede0 !important;
				border-color:rgba(47,125,93,.30) !important;
				color:#155b42 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Solo estructura y contadores; nunca estilos. Se ejecuta después de los
 * controladores funcionales históricos y deja el DOM en su posición final.
 */
add_action(
	'wp_footer',
	static function (): void {
		$is_catalog = function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && ( is_shop() || is_product_category() );
		if ( is_admin() || ! $is_catalog ) {
			return;
		}

		$vendor_counts = elmercado_catalog_vendor_counts_010202();
		?>
		<script id="elmercado-catalog-filter-system-controller-010202">
		(() => {
			'use strict';
			const vendorCounts = <?php echo wp_json_encode( $vendor_counts ); ?>;
			const sidebar = document.querySelector('.emo-mobile-filter-content #secondary.widget-area,.emo-mobile-filter-content .shop-widget-area,.emo-mobile-filter-content .widget-area,#secondary.widget-area,.shop-widget-area,.content-area + .widget-area');
			if (!sidebar) return;

			const context = document.getElementById('emo-category-context');
			const vendor = document.getElementById('emo-global-vendor-filter');
			const specific = document.getElementById('emo-category-attribute-filters');
			const active = document.querySelector('.emo-active-filter-chips[data-emo-global-active-filters="true"]');

			if (context) {
				if (context.parentElement !== sidebar) sidebar.prepend(context);
				context.hidden = false;
				context.removeAttribute('aria-hidden');
			}
			if (vendor) {
				if (vendor.parentElement !== sidebar) sidebar.append(vendor);
				vendor.hidden = false;
				vendor.removeAttribute('aria-hidden');
			}
			if (specific) {
				if (specific.parentElement !== sidebar) sidebar.append(specific);
				specific.hidden = false;
				specific.removeAttribute('aria-hidden');
			}
			if (active && active.parentElement !== sidebar) sidebar.append(active);

			/* Eliminamos físicamente la acción redundante; "Limpiar todo" permanece. */
			document.querySelectorAll('.emo-category-attribute-filters__clear,.emo-category-filter-actions__clear').forEach((link) => {
				if ((link.textContent || '').toLowerCase().includes('limpiar')) link.remove();
			});

			/* Vendedor recibe su contador sin recrear filas ni estilos. */
			document.querySelectorAll('#emo-global-vendor-filter .emo-global-vendor-filter__item').forEach((item) => {
				const value = vendorCounts[String(item.dataset.vendorId || '')];
				if (value === undefined) return;
				let badge = item.querySelector(':scope > .count');
				if (!badge) {
					badge = document.createElement('span');
					badge.className = 'count';
					item.append(badge);
				}
				badge.textContent = String(value);
				badge.setAttribute('aria-label', `${value} productos`);
			});

			/* WooCommerce imprime algunos contadores con paréntesis; se normalizan. */
			document.querySelectorAll('.widget_product_categories .count,#emo-category-attribute-filters .count').forEach((badge) => {
				const clean = (badge.textContent || '').replace(/[()]/g, '').trim();
				if (clean) badge.textContent = clean;
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
