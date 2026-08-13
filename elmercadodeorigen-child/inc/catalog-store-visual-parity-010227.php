<?php
/**
 * Paridad visual definitiva entre Tienda y las tiendas de productor.
 *
 * - elimina la entradilla redundante bajo Productos;
 * - alinea el rail de filtros con la barra de resultados/ordenación;
 * - unifica títulos, filas, hover, categoría activa y precio;
 * - reutiliza en productor el mismo patrón móvil de Tienda: toolbar, botón
 *   Filtrar productos y drawer lateral con el mismo cierre.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_catalog_store_visual_parity_target_010227(): bool {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}

	return function_exists( 'elmercado_vendor_store_is_request_010225' )
		&& elmercado_vendor_store_is_request_010225();
}

/**
 * Retira del HTML final la frase editorial que no debe existir bajo Productos.
 */
function elmercado_catalog_store_strip_tagline_010227( string $html ): string {
	$needle = 'una seleccion de productos con procedencia clara para acercar el origen a tu mesa de una forma mas directa';

	$updated = preg_replace_callback(
		'~<p\b[^>]*>.*?</p>~is',
		static function ( array $matches ) use ( $needle ): string {
			$text = html_entity_decode( wp_strip_all_tags( (string) $matches[0] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
			$text = is_string( $text ) ? rtrim( $text, " .!?…\t\n\r\0\x0B" ) : '';
			$key  = strtolower( remove_accents( $text ) );

			return $needle === $key ? '' : (string) $matches[0];
		},
		$html
	);

	return is_string( $updated ) ? $updated : $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_catalog_store_visual_parity_target_010227() ) {
			return;
		}
		ob_start( 'elmercado_catalog_store_strip_tagline_010227' );
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_catalog_store_visual_parity_target_010227() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-store-visual-parity-010227">
			/* No queda ninguna entradilla entre Productos y los controles del catálogo. */
			body.elmercado-child-theme.woocommerce-shop .emo-shop-lead {
				display:none !important;
				visibility:hidden !important;
				margin:0 !important;
				padding:0 !important;
			}

			/* Barra común de resultados + ordenación. Se aplica a Tienda y productor. */
			body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 {
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-height:62px !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:14px !important;
				margin:0 0 14px !important;
				padding:11px 14px !important;
				border:1px solid rgba(23,63,50,.11) !important;
				border-radius:14px !important;
				background:#fff !important;
				box-shadow:0 10px 28px rgba(17,42,34,.06) !important;
			}
			body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225) {
				position:static !important;
				inset:auto !important;
				float:none !important;
				flex:1 1 auto !important;
				min-width:0 !important;
				margin:0 !important;
				padding:0 !important;
				color:#42564e !important;
				font-size:12px !important;
				font-weight:700 !important;
				line-height:1.3 !important;
			}
			body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 .woocommerce-ordering {
				position:static !important;
				inset:auto !important;
				display:block !important;
				visibility:visible !important;
				float:none !important;
				flex:0 0 min(250px,42vw) !important;
				width:min(250px,42vw) !important;
				margin:0 !important;
				padding:0 !important;
			}
			body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 .woocommerce-ordering select {
				display:block !important;
				box-sizing:border-box !important;
				width:100% !important;
				height:40px !important;
				min-height:40px !important;
				margin:0 !important;
				padding:0 34px 0 13px !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				background-color:#f7f9f6 !important;
				color:#173f32 !important;
				font-size:12px !important;
				font-weight:700 !important;
				line-height:1 !important;
			}

			/* Caja exterior de filtros: exactamente la misma superficie en ambos catálogos. */
			@media (min-width:1101px) {
				body.elmercado-child-theme .emo-filter-rail-parity-010227 {
					box-sizing:border-box !important;
					width:250px !important;
					min-width:250px !important;
					max-width:250px !important;
					padding:18px !important;
					border:1px solid rgba(23,63,50,.11) !important;
					border-radius:18px !important;
					background:#fff !important;
					box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page .emo-filter-rail-parity-010227 > #emo-vendor-filters {
					box-sizing:border-box !important;
					width:100% !important;
					margin:0 !important;
					padding:0 !important;
				}
			}

			/* Títulos de los filtros: mismo peso, caja alta, tracking y línea. */
			body.elmercado-child-theme .emo-filter-title-parity-010227 {
				display:grid !important;
				grid-template-columns:max-content minmax(24px,1fr) !important;
				align-items:center !important;
				column-gap:10px !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-height:0 !important;
				margin:0 0 8px !important;
				padding:1px 1px 7px !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				color:#173f32 !important;
				font-size:10.5px !important;
				font-weight:800 !important;
				letter-spacing:.085em !important;
				line-height:1.25 !important;
				text-align:left !important;
				text-transform:uppercase !important;
			}
			body.elmercado-child-theme .emo-filter-title-parity-010227::after {
				content:"" !important;
				display:block !important;
				width:100% !important;
				height:1px !important;
				background:rgba(23,63,50,.16) !important;
			}

			/* Filas, contadores y hover verdes iguales a Tienda. */
			body.elmercado-child-theme .emo-filter-row-parity-010227 {
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
				transition:background-color .14s ease, box-shadow .14s ease !important;
			}
			body.elmercado-child-theme .emo-filter-row-parity-010227 > a {
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
			body.elmercado-child-theme .emo-filter-row-parity-010227 > a.emo-vendor-filter-link-parity-010227 {
				grid-column:1 / -1 !important;
				display:grid !important;
				grid-template-columns:minmax(0,1fr) auto !important;
				align-items:center !important;
				gap:8px !important;
				width:100% !important;
			}
			body.elmercado-child-theme .emo-filter-row-parity-010227 :is(.count,small) {
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
			body.elmercado-child-theme .emo-filter-row-parity-010227:hover,
			body.elmercado-child-theme .emo-filter-row-parity-010227:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) {
				background:#d9ede0 !important;
				box-shadow:inset 0 0 0 1px rgba(47,125,93,.18) !important;
			}
			body.elmercado-child-theme .emo-filter-row-parity-010227:hover > a,
			body.elmercado-child-theme .emo-filter-row-parity-010227:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) > a {
				color:#155b42 !important;
				font-weight:650 !important;
				text-decoration:none !important;
			}

			/* Widgets del productor heredan la misma densidad que los widgets de Tienda. */
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 #emo-vendor-filters > .widget {
				float:none !important;
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 12px !important;
				padding:0 !important;
				border:0 !important;
				border-radius:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 #emo-vendor-filters > .widget:last-child {
				margin-bottom:0 !important;
			}

			/* Categoría activa: misma tarjeta de Tienda y siempre antes del precio. */
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-category-context {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 14px !important;
				padding:0 !important;
				border:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-category-context__eyebrow {
				display:none !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-category-context__row {
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
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-category-context__row strong {
				min-width:0 !important;
				color:#173f32 !important;
				font-size:13px !important;
				font-weight:750 !important;
				line-height:1.25 !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-category-context__row a {
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

			/* Precio del productor: mismas medidas que el precio de Tienda. */
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter :is(form,.price_slider_wrapper) {
				margin:0 !important;
				padding:0 !important;
				font-family:inherit !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter .price_slider {
				position:relative !important;
				height:4px !important;
				min-height:4px !important;
				margin:12px 9px 20px !important;
				padding:0 !important;
				border:0 !important;
				border-radius:999px !important;
				background:#dfe9e3 !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter .price_slider .ui-slider-range {
				top:0 !important;
				height:4px !important;
				border:0 !important;
				border-radius:999px !important;
				background:#2f7d5d !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter .price_slider .ui-slider-handle {
				top:50% !important;
				width:18px !important;
				height:18px !important;
				min-width:18px !important;
				min-height:18px !important;
				margin-top:0 !important;
				margin-left:-9px !important;
				padding:0 !important;
				box-sizing:border-box !important;
				border:3px solid #2f7d5d !important;
				border-radius:50% !important;
				background:#fff !important;
				box-shadow:0 1px 5px rgba(17,42,34,.12) !important;
				transform:translateY(-50%) !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter .price_slider_amount {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:10px !important;
				width:100% !important;
				min-height:40px !important;
				margin:0 !important;
				padding:0 !important;
				font-family:inherit !important;
				text-align:left !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter .price_slider_amount .button {
				flex:0 0 auto !important;
				min-height:38px !important;
				margin:0 !important;
				padding:0 14px !important;
				border-radius:999px !important;
				font-family:inherit !important;
				font-size:12px !important;
				font-weight:750 !important;
				line-height:1 !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter .price_label {
				min-width:0 !important;
				margin:0 0 0 auto !important;
				color:#42564e !important;
				font-size:11.5px !important;
				font-weight:700 !important;
				line-height:1.25 !important;
				text-align:right !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme .emo-vendor-filter-rail-010225 .emo-vendor-price-filter :is(#min_price,#max_price) {
				display:none !important;
			}

			/* El control móvil propio anterior del productor desaparece: se usa el de Tienda. */
			body.elmercado-child-theme .emo-vendor-filter-toggle-010225,
			body.elmercado-child-theme .emo-vendor-filter-overlay-010225,
			body.elmercado-child-theme .emo-vendor-filters__mobile-head {
				display:none !important;
				visibility:hidden !important;
			}

			/* Tablet estrecha: no se deja el antiguo drawer del productor a la derecha. */
			@media (min-width:992px) and (max-width:1100px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) 250px !important;
					column-gap:34px !important;
					align-items:start !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store :is(.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area) {
					grid-column:1 !important;
					width:100% !important;
					max-width:none !important;
					float:none !important;
					margin:0 !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					grid-column:2 !important;
					display:block !important;
					box-sizing:border-box !important;
					position:sticky !important;
					top:94px !important;
					right:auto !important;
					bottom:auto !important;
					left:auto !important;
					width:250px !important;
					min-width:250px !important;
					max-width:250px !important;
					height:auto !important;
					overflow:visible !important;
					margin:0 !important;
					padding:18px !important;
					border:1px solid rgba(23,63,50,.11) !important;
					border-radius:18px !important;
					background:#fff !important;
					box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
					transform:none !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 > #emo-vendor-filters {
					padding:0 !important;
				}
			}

			/* Móvil: toolbar y trigger en dos cajas sucesivas, igual que Tienda. */
			@media (max-width:991px) {
				body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) 132px !important;
					align-items:center !important;
					gap:8px !important;
					min-height:60px !important;
					margin:0 0 10px !important;
					padding:9px 10px !important;
				}
				body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225) {
					grid-column:1 !important;
					grid-row:1 !important;
					display:flex !important;
					align-items:center !important;
					min-height:42px !important;
					font-size:11px !important;
					line-height:1.25 !important;
					white-space:normal !important;
				}
				body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 .woocommerce-ordering {
					grid-column:2 !important;
					grid-row:1 !important;
					display:flex !important;
					align-items:center !important;
					width:132px !important;
					min-width:132px !important;
					min-height:42px !important;
				}
				body.elmercado-child-theme .emo-catalog-toolbar-parity-010227 .woocommerce-ordering select {
					height:42px !important;
					min-height:42px !important;
					font-size:11px !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle {
					display:inline-flex !important;
					box-sizing:border-box !important;
					width:100% !important;
					height:44px !important;
					min-height:44px !important;
					align-items:center !important;
					justify-content:flex-start !important;
					gap:10px !important;
					margin:0 0 20px !important;
					padding:0 14px !important;
					border:1px solid rgba(23,63,50,.13) !important;
					border-radius:12px !important;
					background:#f7f9f6 !important;
					color:#173f32 !important;
					font-size:12px !important;
					font-weight:750 !important;
					letter-spacing:.01em !important;
					box-shadow:none !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle::before {
					content:"" !important;
					width:16px !important;
					height:16px !important;
					flex:0 0 16px !important;
					background:
						linear-gradient(#2f7d5d,#2f7d5d) 0 3px/16px 1px no-repeat,
						linear-gradient(#2f7d5d,#2f7d5d) 0 8px/16px 1px no-repeat,
						linear-gradient(#2f7d5d,#2f7d5d) 0 13px/16px 1px no-repeat !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-label {
					margin-right:auto !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-chevron {
					display:none !important;
				}

				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 {
					position:fixed !important;
					inset:0 !important;
					display:block !important;
					background:rgba(8,27,22,.42) !important;
					z-index:10020 !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227[hidden] {
					display:none !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-panel {
					position:absolute !important;
					inset:0 auto 0 0 !important;
					box-sizing:border-box !important;
					width:min(88vw,350px) !important;
					max-width:350px !important;
					height:100% !important;
					padding:18px 16px calc(24px + env(safe-area-inset-bottom,0px)) !important;
					overflow-y:auto !important;
					background:#fff !important;
					box-shadow:16px 0 46px rgba(8,27,22,.18) !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-head {
					display:flex !important;
					min-height:48px !important;
					align-items:center !important;
					justify-content:space-between !important;
					gap:12px !important;
					margin:0 0 16px !important;
					padding-bottom:12px !important;
					border-bottom:1px solid rgba(23,63,50,.12) !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-title {
					margin:0 !important;
					color:#173f32 !important;
					font-size:18px !important;
					font-weight:800 !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-close {
					display:grid !important;
					width:40px !important;
					height:40px !important;
					min-width:40px !important;
					padding:0 !important;
					place-items:center !important;
					border:0 !important;
					border-radius:50% !important;
					background:#173f32 !important;
					color:#fff !important;
					font-size:22px !important;
					line-height:1 !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-content .emo-vendor-filter-rail-010225 {
					display:block !important;
					position:static !important;
					inset:auto !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:none !important;
					height:auto !important;
					overflow:visible !important;
					margin:0 !important;
					padding:0 !important;
					border:0 !important;
					border-radius:0 !important;
					background:transparent !important;
					box-shadow:none !important;
					transform:none !important;
					visibility:visible !important;
					opacity:1 !important;
				}
				body.elmercado-child-theme .emo-vendor-mobile-filter-shell-010227 .emo-mobile-filter-content #emo-vendor-filters {
					box-sizing:border-box !important;
					width:100% !important;
					margin:0 !important;
					padding:0 !important;
				}
				html.emo-vendor-shop-filter-open-010227,
				body.emo-vendor-shop-filter-open-010227 {
					overflow:hidden !important;
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
		if ( ! elmercado_catalog_store_visual_parity_target_010227() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-store-visual-parity-script-010227">
		(() => {
			'use strict';
			const body = document.body;
			const sentence = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa';
			const normalize = (value) => (value || '').replace(/\s+/g, ' ').trim().replace(/[.!?…]+$/u, '');
			const visible = (node) => {
				if (!node) return false;
				const style = getComputedStyle(node);
				const rect = node.getBoundingClientRect();
				return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
			};

			function cleanTagline(root = document) {
				root.querySelectorAll?.('p').forEach((node) => {
					if (normalize(node.textContent) === sentence) node.remove();
				});
			}

			function normalizeToolbar(toolbar) {
				if (!toolbar) return;
				toolbar.classList.add('emo-catalog-toolbar-parity-010227');
			}

			function normalizeRail(sidebar, isVendor = false) {
				if (!sidebar) return;
				sidebar.classList.add('emo-filter-rail-parity-010227');

				const titleSelector = isVendor
					? '#emo-vendor-filters .widget-title'
					: '.widget_price_filter > .widget-title,.widget_price_filter > .widgettitle,.widget_product_categories > .widget-title,.widget_product_categories > .widgettitle,#emo-global-vendor-filter > .emo-global-vendor-filter__title,#emo-category-attribute-filters .emo-category-filter-title';
				sidebar.querySelectorAll(titleSelector).forEach((node) => node.classList.add('emo-filter-title-parity-010227'));

				const rowSelector = isVendor
					? '.emo-vendor-category-filter li,.emo-vendor-attribute-filter li'
					: '.widget_product_categories li,#emo-global-vendor-filter .emo-global-vendor-filter__item,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item';
				sidebar.querySelectorAll(rowSelector).forEach((row) => {
					row.classList.add('emo-filter-row-parity-010227');
					if (isVendor) row.querySelector(':scope > a')?.classList.add('emo-vendor-filter-link-parity-010227');
				});

				if (isVendor) {
					const panel = sidebar.querySelector('#emo-vendor-filters');
					const context = panel?.querySelector('#emo-vendor-category-context');
					const price = panel?.querySelector('.emo-vendor-price-filter');
					const categories = panel?.querySelector('#emo-vendor-category-filter');
					const mobileHead = panel?.querySelector('.emo-vendor-filters__mobile-head');
					if (context && price) {
						panel.insertBefore(context, mobileHead ? mobileHead.nextSibling : panel.firstChild);
						panel.insertBefore(price, context.nextSibling);
					} else if (!context && price && categories && price.nextElementSibling !== categories) {
						panel.insertBefore(categories, price.nextSibling);
					}
					context?.querySelector('.emo-vendor-category-context__eyebrow')?.remove();
					const remove = context?.querySelector('.emo-vendor-category-context__row a');
					if (remove && !remove.querySelector('[aria-hidden="true"]')) {
						remove.innerHTML = '<span aria-hidden="true">×</span><span>Quitar</span>';
					}
				}
			}

			function alignRail(toolbar, sidebar, minWidth = 1101) {
				if (!toolbar || !sidebar) return;
				if (innerWidth < minWidth) {
					sidebar.style.removeProperty('margin-top');
					return;
				}
				sidebar.style.setProperty('margin-top', '0px', 'important');
				requestAnimationFrame(() => {
					if (!document.contains(toolbar) || !document.contains(sidebar)) return;
					const toolbarTop = toolbar.getBoundingClientRect().top;
					const railTop = sidebar.getBoundingClientRect().top;
					const offset = Math.max(0, Math.round(toolbarTop - railTop));
					sidebar.style.setProperty('margin-top', `${offset}px`, 'important');
				});
			}

			function setupShop() {
				if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;
				const toolbar = document.querySelector('.woostify-sorting');
				const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area');
				if (!toolbar || !sidebar) return;
				normalizeToolbar(toolbar);
				normalizeRail(sidebar, false);
				alignRail(toolbar, sidebar, 1101);
			}

			let vendorDrawerBooted = false;
			function setupVendor() {
				const store = document.querySelector('#wcfmmp-store');
				if (!store) return;
				cleanTagline(store);
				const toolbar = store.querySelector('.elmercado-vendor-toolbar,.woostify-sorting');
				const sidebar = store.querySelector('.left_sidebar.emo-vendor-filter-rail-010225,.left_sidebar');
				if (!toolbar || !sidebar || !sidebar.querySelector('#emo-vendor-filters')) return;
				normalizeToolbar(toolbar);
				normalizeRail(sidebar, true);
				alignRail(toolbar, sidebar, 992);
				if (vendorDrawerBooted) return;
				vendorDrawerBooted = true;

				const marker = document.createComment('emo-vendor-filter-sidebar-home-010227');
				sidebar.parentNode?.insertBefore(marker, sidebar);
				const toggle = document.createElement('button');
				toggle.type = 'button';
				toggle.className = 'emo-mobile-filter-toggle emo-vendor-mobile-filter-toggle-010227';
				toggle.setAttribute('aria-expanded', 'false');
				toggle.setAttribute('aria-controls', 'emo-vendor-mobile-filter-panel-010227');
				toggle.innerHTML = '<span class="emo-filter-label">Filtrar productos</span><span class="emo-filter-chevron" aria-hidden="true">⌄</span>';
				toolbar.insertAdjacentElement('afterend', toggle);

				const shell = document.createElement('div');
				shell.className = 'emo-mobile-filter-shell emo-vendor-mobile-filter-shell-010227';
				shell.hidden = true;
				shell.innerHTML = '<aside class="emo-mobile-filter-panel" id="emo-vendor-mobile-filter-panel-010227" aria-label="Filtros de productos"><div class="emo-mobile-filter-head"><h2 class="emo-mobile-filter-title">Filtrar productos</h2><button type="button" class="emo-mobile-filter-close" aria-label="Cerrar filtros">×</button></div><div class="emo-mobile-filter-content"></div></aside>';
				body.append(shell);

				const content = shell.querySelector('.emo-mobile-filter-content');
				const close = shell.querySelector('.emo-mobile-filter-close');
				const mobile = () => matchMedia('(max-width:991px)').matches;
				const moveSidebar = () => {
					if (mobile()) {
						if (sidebar.parentElement !== content) content.append(sidebar);
						normalizeRail(sidebar, true);
					} else if (marker.parentNode && sidebar.parentElement === content) {
						marker.parentNode.insertBefore(sidebar, marker.nextSibling);
						normalizeRail(sidebar, true);
						alignRail(toolbar, sidebar, 992);
					}
				};
				const closeDrawer = (restoreFocus = true) => {
					document.documentElement.classList.remove('emo-vendor-shop-filter-open-010227');
					body.classList.remove('emo-vendor-shop-filter-open-010227');
					shell.hidden = true;
					toggle.setAttribute('aria-expanded', 'false');
					if (restoreFocus && mobile()) toggle.focus();
				};
				const openDrawer = () => {
					moveSidebar();
					shell.hidden = false;
					document.documentElement.classList.add('emo-vendor-shop-filter-open-010227');
					body.classList.add('emo-vendor-shop-filter-open-010227');
					toggle.setAttribute('aria-expanded', 'true');
					requestAnimationFrame(() => close?.focus());
				};

				toggle.addEventListener('click', () => toggle.getAttribute('aria-expanded') === 'true' ? closeDrawer() : openDrawer());
				close?.addEventListener('click', () => closeDrawer());
				shell.addEventListener('click', (event) => { if (event.target === shell) closeDrawer(); });
				document.addEventListener('keydown', (event) => {
					if (event.key === 'Escape' && !shell.hidden) {
						event.preventDefault();
						closeDrawer();
					}
				});
				window.addEventListener('resize', () => {
					if (!mobile()) closeDrawer(false);
					moveSidebar();
				}, { passive:true });
				moveSidebar();
			}

			function sync() {
				cleanTagline();
				setupShop();
				setupVendor();
			}

			sync();
			requestAnimationFrame(sync);
			setTimeout(sync, 120);
			setTimeout(sync, 450);
			setTimeout(sync, 1200);
			window.addEventListener('pageshow', sync, { passive:true });
			window.addEventListener('resize', () => setTimeout(sync, 30), { passive:true });
			new MutationObserver(() => requestAnimationFrame(sync)).observe(document.body, { childList:true, subtree:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
