<?php
/**
 * Igualación final de la cabecera móvil de portada con el resto del sitio.
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
		<style id="elmercado-home-mobile-header-final">
			@media (max-width: 991px) {
				body.elmercado-premium-home .site-header,
				body.elmercado-premium-home .site-header-inner {
					height: auto !important;
					min-height: 63px !important;
					overflow: visible !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container {
					display: grid !important;
					grid-template-columns: 44px minmax(0, 1fr) 132px !important;
					align-items: center !important;
					column-gap: 6px !important;
					height: 63px !important;
					min-height: 63px !important;
					padding: 0 12px !important;
				}

				body.elmercado-premium-home .site-header .toggle-sidebar-menu-btn,
				body.elmercado-premium-home .site-header .site-branding,
				body.elmercado-premium-home .site-header .site-tools {
					position: static !important;
					top: auto !important;
					right: auto !important;
					bottom: auto !important;
					left: auto !important;
					transform: none !important;
					align-self: center !important;
				}

				body.elmercado-premium-home .site-header .toggle-sidebar-menu-btn {
					grid-column: 1 !important;
					grid-row: 1 !important;
				}

				body.elmercado-premium-home .site-header .site-branding {
					grid-column: 2 !important;
					grid-row: 1 !important;
					margin: 0 !important;
				}

				body.elmercado-premium-home .site-header .site-tools {
					grid-column: 3 !important;
					grid-row: 1 !important;
					display: flex !important;
					width: 132px !important;
					height: 44px !important;
					align-items: center !important;
					justify-content: flex-end !important;
					gap: 4px !important;
					margin: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
