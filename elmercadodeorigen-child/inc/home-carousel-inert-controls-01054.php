<?php
/**
 * Mantiene el carrusel móvil de Home completamente limpio de flechas visibles.
 *
 * El módulo histórico del carrusel sigue creando sus controles. Se conservan
 * como nodos inertes para compatibilidad, pero se recortan visualmente y se
 * eliminan del orden de foco sin observar ni modificar globalmente el DOM.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<style id="elmercado-home-carousel-inert-controls-01054">
			@media (max-width: 991px) {
				body.home.elmercado-child-theme .emo-featured-products .emo-carousel-controls {
					display: block !important;
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: none !important;
				}

				body.home.elmercado-child-theme .emo-featured-products .emo-carousel-control {
					display: grid !important;
					visibility: visible !important;
					opacity: 1 !important;
					clip-path: inset(50%) !important;
					background: transparent !important;
					border-color: transparent !important;
					box-shadow: none !important;
					backdrop-filter: none !important;
					color: transparent !important;
					pointer-events: none !important;
				}

				body.home.elmercado-child-theme .emo-featured-products .emo-carousel-control svg {
					display: none !important;
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
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<script id="elmercado-home-carousel-inert-controls-script-01054">
		(() => {
			'use strict';
			document.addEventListener('DOMContentLoaded', () => {
				document.querySelectorAll('.emo-featured-products .emo-carousel-controls, .emo-featured-products .emo-carousel-control').forEach((node) => {
					node.setAttribute('aria-hidden', 'true');
					if (node.matches('button')) node.tabIndex = -1;
				});
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
