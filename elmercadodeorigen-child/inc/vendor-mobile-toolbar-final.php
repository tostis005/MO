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
			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-sorting-normalized {
				display: block !important;
				width: 100% !important;
				max-width: none !important;
				min-height: 0 !important;
				margin: 0 0 18px !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				float: none !important;
				clear: both !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar-empty-shell {
				display: none !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
				display: flex !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 14px !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
				float: none !important;
				clear: both !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
				display: flex !important;
				flex: 1 1 auto !important;
				box-sizing: border-box !important;
				width: auto !important;
				max-width: none !important;
				min-width: 0 !important;
				min-height: 46px !important;
				align-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				float: none !important;
				clear: none !important;
				line-height: 1.35 !important;
			}

			body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
				display: flex !important;
				flex: 0 0 auto !important;
				box-sizing: border-box !important;
				width: auto !important;
				max-width: none !important;
				min-height: 46px !important;
				align-items: center !important;
				margin: 0 !important;
				padding: 0 !important;
				float: none !important;
				clear: none !important;
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
				body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links {
					grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
					gap: 0.4rem !important;
				}

				body.elmercado-child-theme #wcfmmp-store .tab_area .tab_links li a {
					min-height: 40px !important;
					padding: 0.55rem 0.35rem !important;
					font-size: 0.7rem !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-sorting-normalized {
					margin: 16px 0 14px !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) minmax(132px, 145px) !important;
					align-items: center !important;
					gap: 8px !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-result-count {
					width: 100% !important;
					min-height: 44px !important;
					font-size: 11px !important;
					line-height: 1.25 !important;
					white-space: normal !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering {
					width: 100% !important;
					min-height: 44px !important;
				}

				body.elmercado-child-theme #wcfmmp-store .elmercado-vendor-toolbar .woocommerce-ordering select {
					width: 100% !important;
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

			const commonAncestor = (first, second, boundary) => {
				let node = first?.parentElement || null;
				while (node && node !== boundary && node !== document.body) {
					if (node.contains(second)) return node;
					node = node.parentElement;
				}
				return null;
			};

			const mount = () => {
				const store = document.querySelector('#wcfmmp-store');
				if (!store) return;

				const result = store.querySelector('.woocommerce-result-count');
				const ordering = store.querySelector('.woocommerce-ordering');
				if (!result || !ordering) return;

				const host = result.closest('.woostify-sorting')
					|| ordering.closest('.woostify-sorting')
					|| commonAncestor(result, ordering, store)
					|| result.closest('.right_side,.products-wrapper,.wcfmmp-store-product')
					|| ordering.closest('.right_side,.products-wrapper,.wcfmmp-store-product');
				if (!host || host === store) return;

				host.classList.add('elmercado-vendor-sorting-normalized');

				let toolbar = host.querySelector(':scope > .elmercado-vendor-toolbar');
				if (!toolbar) {
					toolbar = document.createElement('div');
					toolbar.className = 'elmercado-vendor-toolbar';
					toolbar.setAttribute('role', 'group');
					toolbar.setAttribute('aria-label', 'Resultados y ordenación');
					host.insertBefore(toolbar, host.firstElementChild);
				}

				const previousParents = new Set([result.parentElement, ordering.parentElement]);
				if (result.parentElement !== toolbar) toolbar.appendChild(result);
				if (ordering.parentElement !== toolbar) toolbar.appendChild(ordering);

				previousParents.forEach((shell) => {
					if (!shell || shell === toolbar || shell === host) return;
					const hasMeaningfulContent = [...shell.children].some((child) => child !== toolbar && getComputedStyle(child).display !== 'none')
						|| (shell.textContent || '').trim() !== '';
					if (!hasMeaningfulContent) shell.classList.add('elmercado-vendor-toolbar-empty-shell');
				});

				host.querySelectorAll('.woostify-toolbar-left').forEach((shell) => {
					if (!shell.querySelector('.woocommerce-result-count') && !(shell.textContent || '').trim()) {
						shell.classList.add('elmercado-vendor-toolbar-empty-shell');
					}
				});
			};

			mount();
			document.addEventListener('DOMContentLoaded', mount, { once: true });
			window.addEventListener('pageshow', mount, { passive: true });
			window.setTimeout(mount, 250);
			window.setTimeout(mount, 1000);
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
