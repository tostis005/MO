<?php
/**
 * Imagen cuadrada para las fichas de productores 0.10.242.
 *
 * Afecta únicamente al directorio /productores/. El banner de cada productor
 * se muestra completo y centrado dentro de un lienzo cuadrado, sin recorte ni
 * deformación y sin alterar las fichas de producto de WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-producer-list-image-square-010242">
			/*
			 * .store-info es un fondo inline vacío: la información del productor
			 * vive en .store-footer, así que podemos cuadrar el área de imagen sin
			 * interferir con nombre, botón o valoración.
			 */
			html body.elmercado-producers-page #wcfmmp-stores-wrap .wcfmmp-single-store .store-content {
				position: relative !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				aspect-ratio: 1 / 1 !important;
				overflow: hidden !important;
			}

			html body.elmercado-producers-page #wcfmmp-stores-wrap .wcfmmp-single-store .store-content .store-info {
				position: absolute !important;
				inset: 0 !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: 100% !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 0 !important;
				background-color: #fff !important;
				background-size: contain !important;
				background-position: center center !important;
				background-repeat: no-repeat !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
