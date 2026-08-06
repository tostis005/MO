<?php
/**
 * Correcciones de composición de cabecera que deben imprimirse después de los
 * estilos tardíos del tema padre y de los plugins de búsqueda.
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
		<style id="elmercado-header-finish">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				body.elmercado-child-theme .site-header .sidebar-menu {
					display: none !important;
				}

				body.elmercado-child-theme .site-header-inner > .woostify-container {
					display: grid !important;
					grid-template-columns: minmax(190px, auto) minmax(0, 1fr) auto !important;
					align-items: center !important;
					gap: clamp(1.5rem, 3vw, 3.5rem) !important;
					min-height: 80px !important;
				}

				body.elmercado-child-theme .site-header .site-branding {
					grid-column: 1 !important;
					justify-self: start !important;
					margin: 0 !important;
				}

				body.elmercado-child-theme .site-header .main-navigation {
					display: flex !important;
					grid-column: 2 !important;
					width: 100% !important;
					align-items: center !important;
					justify-content: center !important;
					margin: 0 !important;
				}

				body.elmercado-child-theme .site-header .main-navigation > .primary-navigation,
				body.elmercado-child-theme .site-header .main-navigation .primary-navigation {
					display: flex !important;
					width: auto !important;
					align-items: center !important;
					justify-content: center !important;
					gap: clamp(0.35rem, 1vw, 1rem) !important;
					margin: 0 !important;
					padding: 0 !important;
					list-style: none !important;
				}

				body.elmercado-child-theme .site-header .primary-navigation > li {
					display: flex !important;
					align-items: center !important;
					margin: 0 !important;
					padding: 0 !important;
					list-style: none !important;
				}

				body.elmercado-child-theme .site-header .primary-navigation > li::marker {
					content: "" !important;
				}

				body.elmercado-child-theme .site-header .primary-navigation > li > a {
					display: flex !important;
					min-height: 44px !important;
					align-items: center !important;
					margin: 0 !important;
					padding: 0.65rem 0.55rem !important;
					white-space: nowrap !important;
				}

				body.elmercado-child-theme .site-header .primary-navigation > li:has(> .dgwt-wcas-search-wrapp),
				body.elmercado-child-theme .site-header .primary-navigation > li.emo-duplicate-search-item {
					display: none !important;
				}

				body.elmercado-child-theme .site-header .site-tools {
					display: flex !important;
					grid-column: 3 !important;
					align-items: center !important;
					justify-content: flex-end !important;
					gap: 0.5rem !important;
					margin: 0 !important;
					overflow: visible !important;
				}

				body.elmercado-child-theme .site-header .site-tools .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools .search-icon,
				body.elmercado-child-theme .site-header .site-tools .site-search-toggle,
				body.elmercado-child-theme .site-header .site-tools > a.tools-icon,
				body.elmercado-child-theme .site-header .site-tools .my-account > a.tools-icon {
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					width: 44px !important;
					height: 44px !important;
					min-width: 44px !important;
					margin: 0 !important;
					padding: 0 !important;
					place-items: center !important;
					line-height: 1 !important;
					pointer-events: auto !important;
				}
			}

			/* El contador debe permanecer visible incluso cuando WooCommerce lo deja a cero. */
			body.elmercado-child-theme .site-header .site-tools :is(.shopping-cart,.shopping-bag-button,.cart-contents) {
				position: relative !important;
				overflow: visible !important;
			}

			body.elmercado-child-theme .site-header .site-tools :is(.shopping-cart,.shopping-bag-button,.cart-contents) :is(.shop-cart-count,.cart-count,.count),
			body.elmercado-child-theme .site-header .site-tools > :is(.shop-cart-count,.cart-count) {
				position: absolute !important;
				top: 1px !important;
				right: 0 !important;
				display: inline-flex !important;
				visibility: visible !important;
				opacity: 1 !important;
				min-width: 18px !important;
				height: 18px !important;
				align-items: center !important;
				justify-content: center !important;
				padding: 0 4px !important;
				border: 2px solid #fffdf8 !important;
				border-radius: 999px !important;
				background: #1f674b !important;
				color: #fff !important;
				font-size: 10px !important;
				font-weight: 800 !important;
				line-height: 14px !important;
				transform: none !important;
				z-index: 5 !important;
			}

			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header .main-navigation {
					display: none !important;
				}

				body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn {
					display: flex !important;
				}

				/* El buscador debe estar disponible también en la portada móvil. */
				body.elmercado-child-theme .site-header .site-tools .header-search-icon,
				body.elmercado-child-theme .site-header .site-tools .search-icon,
				body.elmercado-child-theme .site-header .site-tools .site-search-toggle {
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					width: 40px !important;
					height: 40px !important;
					min-width: 40px !important;
					place-items: center !important;
					pointer-events: auto !important;
				}
			}

			body.elmercado-premium-home .emo-featured-products .yith-wcwl-add-to-wishlist {
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
