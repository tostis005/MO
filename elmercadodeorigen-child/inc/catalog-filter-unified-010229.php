<?php
/**
 * Contrato visual y de interacción único para los filtros de Tienda y productor.
 *
 * Esta capa sustituye las capas históricas de paridad visual que escribían
 * estilos y geometría de forma continua. Ambos catálogos reciben las mismas
 * clases finales, las mismas reglas CSS y un único montaje inicial.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_catalog_filter_unified_target_010229(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}
	return function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225();
}

/**
 * Categorías raíz que tienen productos realmente visibles.
 *
 * @return array<int,array{term:WP_Term,count:int}>
 */
function elmercado_catalog_filter_unified_root_categories_010229(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$exclude = array_filter( array( (int) get_option( 'default_product_cat' ) ) );
	$terms   = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => 0,
			'exclude'    => $exclude,
		)
	);
	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	$visible = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$count = function_exists( 'elmercado_catalog_visible_category_count_010217' )
			? elmercado_catalog_visible_category_count_010217( (int) $term->term_id )
			: max( 0, (int) $term->count );
		if ( $count <= 0 ) {
			continue;
		}
		$visible[] = array( 'term' => $term, 'count' => $count );
	}

	usort(
		$visible,
		static function ( array $left, array $right ): int {
			$by_count = (int) $right['count'] <=> (int) $left['count'];
			return 0 !== $by_count ? $by_count : strnatcasecmp( (string) $left['term']->name, (string) $right['term']->name );
		}
	);
	return $visible;
}

/**
 * Widget de categorías global con la verdad de visibilidad del catálogo.
 */
function elmercado_catalog_filter_unified_shop_categories_010229(): string {
	$categories = elmercado_catalog_filter_unified_root_categories_010229();
	if ( ! $categories ) {
		return '';
	}

	ob_start();
	?>
	<aside class="widget woocommerce widget_product_categories emo-global-category-filter-010229 emo-filter-widget-shared-010229" data-emo-category-truth="010229">
		<h2 class="widget-title emo-filter-title-shared-010229">Categorías</h2>
		<ul class="product-categories emo-filter-list-shared-010229">
			<?php foreach ( $categories as $data ) : ?>
				<?php
				$term  = $data['term'];
				$count = max( 0, (int) $data['count'] );
				$link  = get_term_link( $term );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				?>
				<li class="cat-item cat-item-<?php echo esc_attr( (string) $term->term_id ); ?> emo-filter-row-shared-010229">
					<a class="emo-filter-link-shared-010229" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $term->name ); ?></a>
					<span class="count emo-filter-count-shared-010229" aria-label="<?php echo esc_attr( sprintf( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ), number_format_i18n( $count ) ) ); ?>"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</aside>
	<?php
	return (string) ob_get_clean();
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! is_shop() || ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) ) {
			return;
		}
		$widget = elmercado_catalog_filter_unified_shop_categories_010229();
		if ( '' === $widget ) {
			return;
		}
		?>
		<template id="emo-global-category-filter-template-010229"><?php echo $widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
		<script id="elmercado-global-category-filter-010229">
		(() => {
			'use strict';
			const template = document.getElementById('emo-global-category-filter-template-010229');
			const sidebar = document.querySelector('#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area');
			if (!template || !sidebar) return;
			const legacy = [...sidebar.querySelectorAll(':scope > .widget_product_categories:not(.emo-global-category-filter-010229),:scope > .emo-global-category-filter-010226,:scope > .widget_block:has(.wc-block-product-categories),:scope > .widget_block:has(.wp-block-woocommerce-product-categories)')];
			const current = sidebar.querySelector(':scope > .emo-global-category-filter-010229');
			if (!current) {
				const widget = template.content.firstElementChild?.cloneNode(true);
				if (!widget) return;
				if (legacy.length) {
					legacy[0].replaceWith(widget);
					legacy.slice(1).forEach((node) => node.remove());
				} else {
					const price = sidebar.querySelector(':scope > .widget_price_filter');
					price ? price.insertAdjacentElement('afterend', widget) : sidebar.append(widget);
				}
			} else {
				legacy.forEach((node) => node.remove());
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX - 40
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_catalog_filter_unified_target_010229() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-unified-010229">
			/* Una única superficie de toolbar para Tienda y productor. */
			body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 {
				display:flex !important; box-sizing:border-box !important; width:100% !important; min-height:62px !important;
				align-items:center !important; justify-content:space-between !important; gap:14px !important;
				margin:0 0 14px !important; padding:11px 14px !important; border:1px solid rgba(23,63,50,.11) !important;
				border-radius:14px !important; background:#fff !important; box-shadow:0 10px 28px rgba(17,42,34,.06) !important;
			}
			body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225) {
				position:static !important; inset:auto !important; display:block !important; visibility:visible !important; float:none !important;
				flex:1 1 auto !important; min-width:0 !important; margin:0 !important; padding:0 !important;
				color:#42564e !important; font-size:12px !important; font-weight:700 !important; line-height:1.3 !important;
			}
			body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				position:static !important; inset:auto !important; display:block !important; visibility:visible !important; float:none !important;
				flex:0 0 min(250px,42vw) !important; width:min(250px,42vw) !important; margin:0 !important; padding:0 !important;
			}
			body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
				display:block !important; box-sizing:border-box !important; width:100% !important; height:40px !important; min-height:40px !important;
				margin:0 !important; padding:0 34px 0 13px !important; border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important; background-color:#f7f9f6 !important; color:#173f32 !important;
				font-size:12px !important; font-weight:700 !important; line-height:1 !important;
			}

			/* El mismo rail gobierna ambos catálogos. */
			@media (min-width:1101px) {
				html body.elmercado-child-theme .emo-filter-rail-shared-010229 {
					display:block !important; visibility:visible !important; opacity:1 !important; box-sizing:border-box !important;
					width:250px !important; min-width:250px !important; max-width:250px !important; height:auto !important;
					margin-bottom:0 !important; padding:18px !important; border:1px solid rgba(23,63,50,.11) !important;
					border-radius:18px !important; background:#fff !important; box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
					position:sticky !important; top:94px !important; bottom:auto !important; align-self:start !important;
					max-height:calc(100dvh - 112px) !important; overflow-x:hidden !important; overflow-y:auto !important;
					transform:none !important; transition:none !important; will-change:auto !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					display:grid !important; grid-template-columns:minmax(0,1fr) 250px !important; gap:0 34px !important; align-items:start !important; overflow:visible !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store :is(.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area) {
					grid-column:1 !important; grid-row:1 !important; display:block !important; width:100% !important; min-width:0 !important; max-width:none !important;
					float:none !important; position:static !important; margin:0 !important; transform:none !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-filter-rail-shared-010229 {
					grid-column:2 !important; grid-row:1 !important; float:none !important;
				}
			}

			/* Widgets, títulos, listas, filas, enlaces y contadores: un solo contrato. */
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-widget-shared-010229,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 > :is(.widget_price_filter,.widget_product_categories,#emo-global-vendor-filter,#emo-category-attribute-filters),
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-filter-rail-shared-010229 #emo-vendor-filters > .widget {
				float:none !important; box-sizing:border-box !important; width:100% !important; margin:0 0 12px !important; padding:0 !important;
				border:0 !important; border-bottom:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-title-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-filter-rail-shared-010229 #emo-vendor-filters .widget-title {
				display:grid !important; grid-template-columns:max-content minmax(24px,1fr) !important; align-items:center !important; column-gap:10px !important;
				box-sizing:border-box !important; width:100% !important; min-height:0 !important; margin:0 0 8px !important; padding:1px 1px 7px !important;
				border:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important;
				color:#173f32 !important; font-size:10.5px !important; font-weight:800 !important; letter-spacing:.085em !important;
				line-height:1.25 !important; text-align:left !important; text-transform:uppercase !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-title-shared-010229::after,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-filter-rail-shared-010229 #emo-vendor-filters .widget-title::after {
				content:"" !important; display:block !important; width:100% !important; height:1px !important; background:rgba(23,63,50,.16) !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-list-shared-010229 {
				display:grid !important; gap:3px !important; margin:0 !important; padding:0 !important; list-style:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 {
				display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:8px !important;
				box-sizing:border-box !important; min-height:32px !important; margin:0 !important; padding:1px 4px !important; border:0 !important;
				border-radius:8px !important; background:transparent !important; box-shadow:none !important; list-style:none !important;
				transition:background-color .14s ease,box-shadow .14s ease !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 > .emo-filter-link-shared-010229 {
				display:block !important; min-width:0 !important; min-height:0 !important; margin:0 !important; padding:6px 4px !important;
				border:0 !important; background:transparent !important; color:#42584f !important; font-size:12px !important; font-weight:650 !important;
				line-height:1.3 !important; text-align:left !important; text-decoration:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-count-shared-010229 {
				display:inline-flex !important; align-items:center !important; justify-content:flex-end !important; min-width:22px !important;
				margin:0 1px 0 auto !important; padding:0 !important; border:0 !important; background:transparent !important;
				color:#809088 !important; font-size:10.5px !important; font-weight:650 !important; line-height:1 !important; text-align:right !important; white-space:nowrap !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) {
				background:#d9ede0 !important; box-shadow:inset 0 0 0 1px rgba(47,125,93,.18) !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229 span,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) > .emo-filter-link-shared-010229 {
				color:#155b42 !important; font-weight:650 !important; text-decoration:underline !important; text-decoration-thickness:1px !important; text-underline-offset:3px !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 > .emo-filter-link-shared-010229::before,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 > .emo-filter-link-shared-010229::after { display:none !important; content:none !important; }

			/* Categoría activa usa literalmente las clases de Tienda. */
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context {
				box-sizing:border-box !important; width:100% !important; margin:0 0 14px !important; padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__row {
				display:flex !important; align-items:center !important; justify-content:space-between !important; gap:10px !important; min-height:38px !important;
				padding:8px 10px !important; border:1px solid rgba(23,63,50,.10) !important; border-radius:10px !important; background:#f3f7f4 !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__name {
				min-width:0 !important; overflow:hidden !important; text-overflow:ellipsis !important; white-space:nowrap !important;
				color:#173f32 !important; font-size:13px !important; font-weight:750 !important; line-height:1.25 !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove {
				display:inline-flex !important; flex:0 0 auto !important; align-items:center !important; gap:3px !important; min-width:max-content !important;
				margin:0 !important; padding:3px 2px !important; border:0 !important; background:transparent !important; color:#687b72 !important;
				font-size:10.5px !important; font-weight:700 !important; line-height:1 !important; text-decoration:none !important; white-space:nowrap !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove::before,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove::after { display:none !important; content:none !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove:hover span:last-child,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .emo-category-context__remove:focus-visible span:last-child {
				color:#155b42 !important; text-decoration:underline !important; text-underline-offset:3px !important;
			}

			/* Precio: exactamente las medidas del filtro final de Tienda 0.10.207. */
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter :is(form,.price_slider_wrapper) { margin:0 !important; padding:0 !important; font-family:inherit !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider {
				position:relative !important; height:4px !important; min-height:4px !important; margin:12px 9px 20px !important; padding:0 !important;
				border:0 !important; border-radius:999px !important; background:#dfe9e3 !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider .ui-slider-range {
				top:0 !important; height:4px !important; border:0 !important; border-radius:999px !important; background:#2f7d5d !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider .ui-slider-handle {
				top:50% !important; width:18px !important; height:18px !important; min-width:18px !important; min-height:18px !important;
				margin-top:-9px !important; margin-left:-9px !important; padding:0 !important; box-sizing:border-box !important;
				border:3px solid #2f7d5d !important; border-radius:50% !important; background:#fff !important; box-shadow:0 1px 5px rgba(17,42,34,.12) !important; transform:none !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider_amount {
				display:flex !important; align-items:center !important; justify-content:space-between !important; gap:10px !important; width:100% !important;
				min-height:40px !important; margin:0 !important; padding:0 !important; float:none !important; clear:both !important; font-family:inherit !important; text-align:left !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider_amount::before,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider_amount::after,
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider_amount .clear { display:none !important; content:none !important; }
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_slider_amount .button {
				flex:0 0 auto !important; min-height:38px !important; margin:0 !important; padding:0 14px !important; float:none !important;
				border-radius:999px !important; font-family:inherit !important; font-size:12px !important; font-weight:750 !important; line-height:1 !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter .price_label {
				position:static !important; display:block !important; flex:1 1 auto !important; min-width:0 !important; margin:0 0 0 auto !important; padding:0 !important; float:none !important;
				color:#42564e !important; font-family:inherit !important; font-size:11.5px !important; font-weight:700 !important; line-height:1.25 !important;
				letter-spacing:0 !important; text-align:right !important; text-transform:none !important; white-space:nowrap !important;
			}
			html body.elmercado-child-theme .emo-filter-rail-shared-010229 .widget_price_filter :is(#min_price,#max_price) { display:none !important; }

			/* Anula los controles móviles históricos del productor. */
			body.elmercado-child-theme .emo-vendor-filter-toggle-010225,
			body.elmercado-child-theme .emo-vendor-filter-overlay-010225,
			body.elmercado-child-theme .emo-vendor-filters__mobile-head { display:none !important; visibility:hidden !important; }

			@media (max-width:991px) {
				body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 {
					display:grid !important; grid-template-columns:minmax(0,1fr) 132px !important; align-items:center !important; gap:8px !important;
					min-height:60px !important; margin:0 0 10px !important; padding:9px 10px !important;
				}
				body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225) {
					grid-column:1 !important; grid-row:1 !important; display:flex !important; align-items:center !important; min-height:42px !important;
					font-size:11px !important; line-height:1.25 !important; white-space:normal !important;
				}
				body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					grid-column:2 !important; grid-row:1 !important; display:flex !important; align-items:center !important; width:132px !important; min-width:132px !important; min-height:42px !important;
				}
				body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select { height:42px !important; min-height:42px !important; font-size:11px !important; }
				body.elmercado-child-theme .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
					display:inline-flex !important; box-sizing:border-box !important; width:100% !important; height:44px !important; min-height:44px !important;
					align-items:center !important; justify-content:flex-start !important; gap:10px !important; margin:0 0 20px !important; padding:0 14px !important;
					border:1px solid rgba(23,63,50,.13) !important; border-radius:12px !important; background:#f7f9f6 !important; color:#173f32 !important;
					font-size:12px !important; font-weight:750 !important; letter-spacing:.01em !important; box-shadow:none !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 .emo-filter-chevron { margin-left:auto !important; font-size:16px !important; line-height:1 !important; }
				body.elmercado-child-theme .emo-mobile-filter-shell.emo-filter-shell-shared-010229 {
					position:fixed !important; inset:0 !important; display:block !important; background:rgba(8,27,22,.42) !important; z-index:10020 !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-shell.emo-filter-shell-shared-010229[hidden] { display:none !important; }
				body.elmercado-child-theme .emo-filter-shell-shared-010229 .emo-mobile-filter-panel {
					position:absolute !important; inset:0 auto 0 0 !important; box-sizing:border-box !important; width:min(88vw,350px) !important; max-width:350px !important; height:100% !important;
					padding:18px 16px calc(24px + env(safe-area-inset-bottom,0px)) !important; overflow-y:auto !important; background:#fff !important; box-shadow:16px 0 46px rgba(8,27,22,.18) !important;
				}
				body.elmercado-child-theme .emo-filter-shell-shared-010229 .emo-mobile-filter-head {
					display:flex !important; min-height:48px !important; align-items:center !important; justify-content:space-between !important; gap:12px !important;
					margin:0 0 16px !important; padding-bottom:12px !important; border-bottom:1px solid rgba(23,63,50,.12) !important;
				}
				body.elmercado-child-theme .emo-filter-shell-shared-010229 .emo-mobile-filter-title { margin:0 !important; color:#173f32 !important; font-size:18px !important; font-weight:800 !important; }
				body.elmercado-child-theme .emo-filter-shell-shared-010229 .emo-mobile-filter-close {
					display:grid !important; width:40px !important; height:40px !important; min-width:40px !important; padding:0 !important; place-items:center !important;
					border:0 !important; border-radius:50% !important; background:#173f32 !important; color:#fff !important; font-size:22px !important; line-height:1 !important;
				}
				body.elmercado-child-theme .emo-filter-shell-shared-010229 .emo-mobile-filter-content .emo-filter-rail-shared-010229 {
					display:block !important; position:static !important; inset:auto !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:none !important;
					height:auto !important; max-height:none !important; overflow:visible !important; margin:0 !important; padding:0 !important; border:0 !important; border-radius:0 !important;
					background:transparent !important; box-shadow:none !important; transform:none !important; visibility:visible !important; opacity:1 !important;
				}
				html.emo-filter-drawer-open-010229,body.emo-filter-drawer-open-010229 { overflow:hidden !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_catalog_filter_unified_target_010229() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-filter-unified-script-010229">
		(() => {
			'use strict';
			const body = document.body;
			const mobileQuery = window.matchMedia('(max-width:991px)');
			let resizeTimer = 0;

			const normalizeRows = (rail, vendor) => {
				const titleSelector = vendor
					? '#emo-vendor-filters .widget-title'
					: '.widget_price_filter > .widget-title,.widget_price_filter > .widgettitle,.widget_product_categories > .widget-title,.widget_product_categories > .widgettitle,#emo-global-vendor-filter > .emo-global-vendor-filter__title,#emo-category-attribute-filters .emo-category-filter-title';
				rail.querySelectorAll(titleSelector).forEach((node) => node.classList.add('emo-filter-title-shared-010229'));

				const listSelector = vendor
					? '.emo-vendor-category-filter > ul,.emo-vendor-attribute-filter > ul'
					: '.widget_product_categories > ul,#emo-global-vendor-filter .emo-global-vendor-filter__list,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list';
				rail.querySelectorAll(listSelector).forEach((node) => node.classList.add('emo-filter-list-shared-010229'));

				const rowSelector = vendor
					? '.emo-vendor-category-filter li,.emo-vendor-attribute-filter li'
					: '.widget_product_categories li,#emo-global-vendor-filter .emo-global-vendor-filter__item,#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item';
				rail.querySelectorAll(rowSelector).forEach((row) => {
					row.classList.add('emo-filter-row-shared-010229');
					const link = row.querySelector(':scope > a');
					if (link) link.classList.add('emo-filter-link-shared-010229');
					if (vendor && link) {
						const countInside = link.querySelector(':scope > small');
						if (countInside) row.append(countInside);
					}
					const count = row.querySelector(':scope > .count,:scope > small');
					if (count) {
						count.classList.add('count','emo-filter-count-shared-010229');
						count.textContent = (count.textContent || '').replace(/[()]/g,'').trim();
					}
				});
			};

			const normalizeRail = (rail, vendor) => {
				if (!rail) return;
				rail.classList.add('emo-filter-rail-shared-010229');
				[...rail.children].forEach((node) => {
					if (node.matches?.('.widget,.widget_product_categories,.widget_price_filter,#emo-global-vendor-filter,#emo-category-attribute-filters')) node.classList.add('emo-filter-widget-shared-010229');
				});
				if (vendor) rail.querySelectorAll('#emo-vendor-filters > .widget').forEach((node) => node.classList.add('emo-filter-widget-shared-010229'));
				normalizeRows(rail, vendor);
			};

			const normalizeContext = (rail) => {
				const panel = rail?.querySelector('#emo-vendor-filters');
				if (!panel) return;
				const context = panel.querySelector('#emo-vendor-category-context');
				const price = panel.querySelector('.emo-vendor-price-filter');
				const categories = panel.querySelector('#emo-vendor-category-filter');
				panel.querySelector('.emo-vendor-filters__mobile-head')?.remove();
				if (context) {
					context.classList.add('emo-category-context','emo-filter-widget-shared-010229');
					context.querySelector('.emo-vendor-category-context__eyebrow')?.remove();
					const row = context.querySelector('.emo-vendor-category-context__row');
					const name = row?.querySelector('strong');
					const remove = row?.querySelector('a');
					row?.classList.add('emo-category-context__row');
					name?.classList.add('emo-category-context__name');
					if (remove) {
						remove.className = 'emo-category-context__remove';
						remove.innerHTML = '<span aria-hidden="true">×</span><span>Quitar</span>';
					}
					if (price && context.nextElementSibling !== price) panel.insertBefore(context, price), panel.insertBefore(price, context.nextSibling);
				} else if (price && categories && price.nextElementSibling !== categories) {
					panel.insertBefore(categories, price.nextSibling);
				}
			};

			const alignOnce = (toolbar, rail) => {
				if (!toolbar || !rail || innerWidth < 1101) {
					rail?.style.removeProperty('margin-top');
					return;
				}
				rail.style.setProperty('margin-top','0px','important');
				requestAnimationFrame(() => {
					if (!document.contains(toolbar) || !document.contains(rail)) return;
					const offset = Math.max(0, Math.round(toolbar.getBoundingClientRect().top - rail.getBoundingClientRect().top));
					rail.style.setProperty('margin-top', `${offset}px`, 'important');
				});
			};

			const setupShop = () => {
				if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;
				const toolbar = document.querySelector('.woostify-sorting');
				const rail = document.querySelector('#secondary.widget-area,.shop-widget-area');
				if (!toolbar || !rail) return;
				toolbar.classList.add('emo-catalog-toolbar-shared-010229');
				normalizeRail(rail,false);
				alignOnce(toolbar,rail);
			};

			let vendorState = null;
			const setupVendor = () => {
				const store = document.querySelector('#wcfmmp-store');
				if (!store) return;
				const toolbar = store.querySelector('.elmercado-vendor-toolbar,.woostify-sorting');
				const rail = store.querySelector('.left_sidebar.emo-vendor-filter-rail-010225,.left_sidebar');
				if (!toolbar || !rail || !rail.querySelector('#emo-vendor-filters')) return;
				toolbar.classList.add('emo-catalog-toolbar-shared-010229');
				normalizeRail(rail,true);
				normalizeContext(rail);
				normalizeRail(rail,true);
				alignOnce(toolbar,rail);

				if (!vendorState) {
					const marker = document.createComment('emo-filter-rail-home-010229');
					rail.parentNode?.insertBefore(marker,rail);
					const toggle = document.createElement('button');
					toggle.type = 'button';
					toggle.className = 'emo-mobile-filter-toggle emo-filter-toggle-shared-010229';
					toggle.setAttribute('aria-expanded','false');
					toggle.setAttribute('aria-controls','emo-mobile-filter-panel-shared-010229');
					toggle.innerHTML = '<span class="emo-filter-label">Filtrar productos</span><span class="emo-filter-chevron" aria-hidden="true">⌄</span>';
					toolbar.insertAdjacentElement('afterend',toggle);

					const shell = document.createElement('div');
					shell.className = 'emo-mobile-filter-shell emo-filter-shell-shared-010229';
					shell.hidden = true;
					shell.innerHTML = '<aside class="emo-mobile-filter-panel" id="emo-mobile-filter-panel-shared-010229" aria-label="Filtros de productos"><div class="emo-mobile-filter-head"><h2 class="emo-mobile-filter-title">Filtrar productos</h2><button type="button" class="emo-mobile-filter-close" aria-label="Cerrar filtros">×</button></div><div class="emo-mobile-filter-content"></div></aside>';
					body.append(shell);
					vendorState = { store,toolbar,rail,marker,toggle,shell,content:shell.querySelector('.emo-mobile-filter-content'),close:shell.querySelector('.emo-mobile-filter-close') };

					const closeDrawer = (focus = true) => {
						document.documentElement.classList.remove('emo-filter-drawer-open-010229');
						body.classList.remove('emo-filter-drawer-open-010229');
						shell.hidden = true;
						toggle.setAttribute('aria-expanded','false');
						if (focus && mobileQuery.matches) toggle.focus();
					};
					const moveRail = () => {
						if (mobileQuery.matches) {
							if (rail.parentElement !== vendorState.content) vendorState.content.append(rail);
						} else if (marker.parentNode && rail.parentElement === vendorState.content) {
							marker.parentNode.insertBefore(rail,marker.nextSibling);
							closeDrawer(false);
							alignOnce(toolbar,rail);
						}
					};
					const openDrawer = () => {
						moveRail();
						shell.hidden = false;
						document.documentElement.classList.add('emo-filter-drawer-open-010229');
						body.classList.add('emo-filter-drawer-open-010229');
						toggle.setAttribute('aria-expanded','true');
						requestAnimationFrame(() => vendorState.close?.focus());
					};
					toggle.addEventListener('click',() => toggle.getAttribute('aria-expanded') === 'true' ? closeDrawer() : openDrawer());
					vendorState.close?.addEventListener('click',() => closeDrawer());
					shell.addEventListener('click',(event) => { if (event.target === shell) closeDrawer(); });
					document.addEventListener('keydown',(event) => { if (event.key === 'Escape' && !shell.hidden) closeDrawer(); });
					vendorState.moveRail = moveRail;
					moveRail();
				}
			};

			const setup = () => { setupShop(); setupVendor(); };
			setup();
			window.addEventListener('pageshow',setup,{passive:true});
			window.addEventListener('resize',() => {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(() => {
					vendorState?.moveRail?.();
					setup();
				},120);
			},{passive:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
