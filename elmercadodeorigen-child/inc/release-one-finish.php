<?php
/**
 * Remate de la primera versión comercial.
 *
 * Woostify genera una segunda fila implícita al combinar su estructura con la
 * rejilla de cabecera. Se sustituye por flexbox en escritorio para mantener
 * marca, navegación y herramientas dentro de la franja blanca. También se
 * retira definitivamente el conmutador de filtros vacío de la tienda.
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
		<style id="elmercado-release-one-finish">
			body.elmercado-child-theme.woocommerce-shop .woostify-sorting .filter,
			body.elmercado-child-theme.woocommerce-shop .woostify-sorting .filter.show,
			body.elmercado-child-theme.woocommerce-shop .woostify-sorting .filter.emo-remove-filter-toggle {
				display: none !important;
				visibility: hidden !important;
				pointer-events: none !important;
			}

			@media (min-width: 992px) {
				body.elmercado-premium-home .site-header-inner,
				body.elmercado-premium-home .site-header-inner > .woostify-container {
					height: 62px !important;
					min-height: 62px !important;
					max-height: 62px !important;
					padding-block: 0 !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container {
					display: flex !important;
					align-items: center !important;
					align-content: center !important;
					justify-content: flex-start !important;
					gap: clamp(1.5rem, 3vw, 3.5rem) !important;
					grid-template-columns: none !important;
					grid-template-rows: none !important;
					grid-auto-flow: row !important;
					grid-auto-rows: auto !important;
					overflow: visible !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-branding,
				body.elmercado-premium-home .site-header-inner > .woostify-container > .main-navigation,
				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-tools {
					position: static !important;
					top: auto !important;
					right: auto !important;
					bottom: auto !important;
					left: auto !important;
					inset: auto !important;
					display: flex !important;
					height: 62px !important;
					min-height: 62px !important;
					max-height: 62px !important;
					align-items: center !important;
					align-self: center !important;
					margin: 0 !important;
					padding-block: 0 !important;
					transform: none !important;
					translate: none !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-branding {
					width: auto !important;
					min-width: 190px !important;
					flex: 0 0 auto !important;
					justify-content: flex-start !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container > .main-navigation {
					width: auto !important;
					min-width: 0 !important;
					flex: 1 1 auto !important;
					justify-content: center !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-tools {
					width: auto !important;
					min-width: max-content !important;
					flex: 0 0 auto !important;
					justify-content: flex-end !important;
					margin-left: auto !important;
				}

				body.elmercado-premium-home .site-header .site-branding > a,
				body.elmercado-premium-home .site-header .primary-navigation,
				body.elmercado-premium-home .site-header .primary-navigation > li,
				body.elmercado-premium-home .site-header .primary-navigation > li > a {
					align-items: center !important;
					align-self: center !important;
					margin-block: 0 !important;
					transform: none !important;
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
		if ( is_admin() || ! is_shop() ) {
			return;
		}
		?>
		<script id="elmercado-remove-empty-filter">
		(() => {
			const removeFilter = (root = document) => {
				root.querySelectorAll?.('.woostify-sorting .filter, .woostify-sorting button, .woostify-sorting a, .woostify-sorting [role="button"]').forEach((control) => {
					const label = `${control.textContent || ''} ${control.getAttribute('aria-label') || ''}`.trim().toLocaleLowerCase('es');
					if (control.matches('.filter') || /^(filtro|filter|filtrar productos)(\s|$)/i.test(label)) {
						control.remove();
					}
				});
			};

			removeFilter();
			new MutationObserver((records) => {
				records.forEach((record) => record.addedNodes.forEach((node) => {
					if (node.nodeType === 1) removeFilter(node);
				}));
			}).observe(document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
