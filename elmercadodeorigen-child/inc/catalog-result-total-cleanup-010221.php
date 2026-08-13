<?php
/**
 * Limpieza final de la barra de resultados del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-result-total-cleanup-010221">
			.emo-catalog-result-count-010218 { display:none !important; visibility:hidden !important; }
			.emo-catalog-result-count-010220 { display:block !important; visibility:visible !important; }
			.woocommerce-ordering { display:none !important; visibility:hidden !important; }
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<script id="elmercado-catalog-result-total-cleanup-script-010221">
			document.querySelectorAll('.emo-catalog-result-count-010218').forEach(function (node) {
				node.remove();
			});
		</script>
		<?php
	},
	PHP_INT_MAX
);

/* Desde 0.10.229 la única capa visual del catálogo es catalog-filter-unified-010229.php. */
add_action(
	'after_setup_theme',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/home-visible-categories-final-010226.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);

/* #view de WCFM debe dejar que sticky se ancle al viewport real en escritorio. */
add_action(
	'wp_loaded',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/vendor-sticky-root-fix-010228.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);

/**
 * Retira únicamente la capa visual/runtime anónima del módulo histórico del
 * productor. Sus funciones PHP, consultas, conteos y enqueue del slider siguen
 * activos; desaparecen el CSS paralelo y el MutationObserver que reejecutaba
 * el montaje con cada mutación del catálogo.
 */
function elmercado_catalog_remove_vendor_legacy_ui_010233( string $hook_name ): void {
	global $wp_filter;

	if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
		return;
	}

	$legacy_file = wp_normalize_path( ELMERCADO_THEME_PATH . '/inc/vendor-store-catalog-filters-010225.php' );
	$callbacks   = $wp_filter[ $hook_name ]->callbacks;

	foreach ( $callbacks as $priority => $items ) {
		foreach ( $items as $item ) {
			$callback = $item['function'] ?? null;
			if ( ! $callback instanceof Closure ) {
				continue;
			}
			try {
				$reflection = new ReflectionFunction( $callback );
				$filename   = $reflection->getFileName();
			} catch ( Throwable $throwable ) {
				continue;
			}
			if ( is_string( $filename ) && wp_normalize_path( $filename ) === $legacy_file ) {
				remove_action( $hook_name, $callback, (int) $priority );
			}
		}
	}
}

add_action(
	'after_setup_theme',
	static function (): void {
		elmercado_catalog_remove_vendor_legacy_ui_010233( 'wp_head' );
		elmercado_catalog_remove_vendor_legacy_ui_010233( 'wp_footer' );

		/* Montaje ligero, una sola vez, antes de que el contrato compartido normalice clases. */
		add_action(
			'wp_footer',
			static function (): void {
				if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
					return;
				}
				if ( ! function_exists( 'elmercado_vendor_store_state_010225' ) || ! function_exists( 'elmercado_vendor_store_filter_panel_010225' ) ) {
					return;
				}

				$state = elmercado_vendor_store_state_010225();
				if ( (int) ( $state['vendor_id'] ?? 0 ) <= 0 ) {
					return;
				}
				$panel = elmercado_vendor_store_filter_panel_010225( $state );
				$total = max( 0, (int) ( $state['total'] ?? 0 ) );
				$label = sprintf( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ), number_format_i18n( $total ) );
				?>
				<template id="emo-vendor-filter-template-010233"><?php echo $panel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
				<script id="elmercado-vendor-filter-lean-runtime-010233">
				(() => {
					'use strict';
					const store = document.querySelector('#wcfmmp-store');
					const template = document.getElementById('emo-vendor-filter-template-010233');
					if (!store || !template) return;
					const exactLabel = <?php echo wp_json_encode( $label ); ?>;
					let sliderInitialised = false;

					const productHost = () => store.querySelector('.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area');
					const sidebarHost = () => store.querySelector('.left_sidebar');
					const toolbarHost = () => store.querySelector('.elmercado-vendor-toolbar') || productHost();

					function syncCount() {
						let nodes = [...store.querySelectorAll('.woocommerce-result-count')];
						if (!nodes.length) {
							const host = toolbarHost();
							if (host) {
								const node = document.createElement('p');
								node.className = 'woocommerce-result-count emo-vendor-result-count-010225';
								host.prepend(node);
								nodes = [node];
							}
						}
						nodes.forEach((node) => {
							node.textContent = exactLabel;
							node.classList.add('emo-vendor-result-count-010225');
							node.removeAttribute('aria-hidden');
							node.setAttribute('role','status');
							node.setAttribute('aria-live','polite');
						});
					}

					function initPriceSliderOnce() {
						if (sliderInitialised || !window.jQuery) return;
						sliderInitialised = true;
						window.jQuery(document.body).trigger('init_price_filter');
					}

					function mount() {
						const body = store.querySelector('.body_area');
						const sidebar = sidebarHost();
						const products = productHost();
						if (!body || !sidebar || !products) return;
						if (!sidebar.querySelector('#emo-vendor-filters')) sidebar.innerHTML = template.innerHTML;
						sidebar.classList.add('emo-vendor-filter-rail-010225');
						if (products.parentElement === body && sidebar.parentElement === body && products.nextElementSibling !== sidebar) body.insertBefore(products, sidebar);
						store.querySelectorAll('.emo-vendor-filter-toggle-010225').forEach((node) => node.remove());
						document.querySelectorAll('.emo-vendor-filter-overlay-010225').forEach((node) => node.remove());
						sidebar.style.removeProperty('height');
						syncCount();
						initPriceSliderOnce();
					}

					mount();
					window.addEventListener('pageshow', mount, { passive:true });
				})();
				</script>
				<?php
			},
			PHP_INT_MAX - 100
		);
	},
	PHP_INT_MAX - 20
);

/*
 * Último cierre de especificidad para el contrato compartido. Se ancla a los
 * dos raíles reales, pero todas las reglas internas usan exactamente las mismas
 * clases compartidas y las mismas declaraciones en Tienda y productor.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_catalog_filter_unified_target_010229' ) || ! elmercado_catalog_filter_unified_target_010229() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-shared-interaction-010233">
			@media (min-width:1101px) {
				html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 {
					display:block !important; visibility:visible !important; opacity:1 !important; box-sizing:border-box !important;
					width:250px !important; min-width:250px !important; max-width:250px !important; height:auto !important;
					margin-bottom:0 !important; padding:18px !important; border:1px solid rgba(23,63,50,.11) !important;
					border-radius:18px !important; background:#fff !important; box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
					position:sticky !important; top:94px !important; bottom:auto !important; align-self:start !important;
					max-height:calc(100dvh - 112px) !important; overflow-x:hidden !important; overflow-y:auto !important;
					transform:none !important; transition:none !important; will-change:auto !important;
				}
			}

			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-title-shared-010229 {
				display:grid !important; grid-template-columns:max-content minmax(24px,1fr) !important; align-items:center !important; column-gap:10px !important;
				box-sizing:border-box !important; width:100% !important; min-height:0 !important; margin:0 0 8px !important; padding:1px 1px 7px !important;
				border:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important;
				color:#173f32 !important; font-size:10.5px !important; font-weight:800 !important; letter-spacing:.085em !important; line-height:1.25 !important; text-align:left !important; text-transform:uppercase !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-list-shared-010229 {
				display:grid !important; gap:3px !important; margin:0 !important; padding:0 !important; list-style:none !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 {
				display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; column-gap:8px !important;
				box-sizing:border-box !important; min-height:32px !important; margin:0 !important; padding:1px 4px !important; border:0 !important; border-radius:8px !important; background:transparent !important; box-shadow:none !important; list-style:none !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229 > .emo-filter-link-shared-010229 {
				display:block !important; min-width:0 !important; min-height:0 !important; margin:0 !important; padding:6px 4px !important; border:0 !important; background:transparent !important;
				color:#42584f !important; font-size:12px !important; font-weight:650 !important; line-height:1.3 !important; text-align:left !important; text-decoration:none !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-count-shared-010229 {
				display:inline-flex !important; align-items:center !important; justify-content:flex-end !important; min-width:22px !important; margin:0 1px 0 auto !important; padding:0 !important;
				border:0 !important; background:transparent !important; color:#809088 !important; font-size:10.5px !important; font-weight:650 !important; line-height:1 !important; text-align:right !important; white-space:nowrap !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) {
				background:#d9ede0 !important; box-shadow:inset 0 0 0 1px rgba(47,125,93,.18) !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:hover,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:focus-visible,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) > .emo-filter-link-shared-010229 {
				color:#155b42 !important; font-weight:650 !important; text-decoration-line:underline !important; text-decoration-style:solid !important; text-decoration-thickness:1px !important; text-underline-offset:3px !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:hover > span,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229 > span { text-decoration:inherit !important; }

			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove {
				display:inline-flex !important; flex:0 0 auto !important; align-items:center !important; gap:3px !important; min-width:max-content !important; margin:0 !important; padding:3px 2px !important;
				border:0 !important; background:transparent !important; color:#687b72 !important; font-size:10.5px !important; font-weight:700 !important; line-height:1 !important;
				text-decoration:none !important; white-space:nowrap !important; word-break:normal !important; overflow-wrap:normal !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove::before,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove::after { display:none !important; content:none !important; }
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove:hover > span:last-child,
			html body.elmercado-child-theme :is(#secondary#secondary, #wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove:focus-visible > span:last-child {
				color:#155b42 !important; text-decoration:underline !important; text-decoration-thickness:1px !important; text-underline-offset:3px !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
