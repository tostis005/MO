<?php
/**
 * Final alignment for vendor result count and ordering controls.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-mobile-toolbar-final">
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
				display: flex !important;
				width: 100% !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 14px !important;
				margin: 0 0 18px !important;
				padding: 0 !important;
				clear: both !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				display: flex !important;
				flex: 1 1 auto !important;
				width: auto !important;
				min-width: 0 !important;
				min-height: 46px !important;
				align-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				float: none !important;
				line-height: 1.35 !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				display: flex !important;
				flex: 0 0 auto !important;
				width: auto !important;
				min-height: 46px !important;
				align-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				float: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
				box-sizing: border-box !important;
				height: 46px !important;
				min-height: 46px !important;
				margin: 0 !important;
				border-top-width: 1px !important;
				border-bottom-width: 1px !important;
			}

			@media (max-width: 600px) {
				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
					flex-wrap: nowrap !important;
					gap: 8px !important;
					margin-bottom: 12px !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
					min-height: 44px !important;
					font-size: 11px !important;
					line-height: 1.25 !important;
					white-space: nowrap !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
					min-height: 44px !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					width: min(40vw, 145px) !important;
					height: 44px !important;
					min-height: 44px !important;
					padding: 0 28px 0 9px !important;
					font-size: 11px !important;
					line-height: 1.2 !important;
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
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-toolbar-layout-final">
		(() => {
			'use strict';

			const mount = () => {
				const store = document.querySelector('#wcfmmp-store');
				if (!store) return;

				const result = store.querySelector('.woocommerce-result-count');
				const ordering = store.querySelector('.woocommerce-ordering');
				if (!result || !ordering) return;

				let toolbar = store.querySelector('.elmercado-vendor-toolbar');
				if (!toolbar) {
					toolbar = document.createElement('div');
					toolbar.className = 'elmercado-vendor-toolbar';
					toolbar.setAttribute('role', 'group');
					toolbar.setAttribute('aria-label', 'Resultados y ordenación');
					result.parentNode.insertBefore(toolbar, result);
				}

				if (result.parentElement !== toolbar) toolbar.appendChild(result);
				if (ordering.parentElement !== toolbar) toolbar.appendChild(ordering);
			};

			mount();
			window.addEventListener('pageshow', mount, { passive: true });
			new MutationObserver(() => requestAnimationFrame(mount)).observe(document.body, {
				childList: true,
				subtree: true,
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
