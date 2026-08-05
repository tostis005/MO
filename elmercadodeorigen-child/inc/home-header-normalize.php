<?php
/**
 * Normalización final de la altura de cabecera en portada.
 *
 * El marcado de Woostify añade altura al contenedor interior aunque la rejilla
 * de la cabecera ya esté limitada. Fijamos la misma altura visual que en las
 * plantillas interiores y eliminamos el padding residual.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_is_optimized_home() ) {
			return;
		}
		?>
		<style id="elmercado-home-header-normalize">
			@media (min-width: 992px) {
				body.elmercado-premium-home .site-header-inner {
					height: auto !important;
					min-height: 0 !important;
					padding-block: 0 !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container {
					height: 62px !important;
					min-height: 62px !important;
					padding-block: 0 !important;
				}

				body.elmercado-premium-home .site-branding img,
				body.elmercado-premium-home .custom-logo {
					max-height: 44px !important;
				}
			}

			@media (max-width: 991px) {
				body.elmercado-premium-home .site-header-inner {
					height: auto !important;
					min-height: 0 !important;
					padding-block: 0 !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container {
					height: 60px !important;
					min-height: 60px !important;
					padding-block: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
