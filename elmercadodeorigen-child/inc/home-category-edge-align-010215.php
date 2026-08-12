<?php
/**
 * Ajuste final de alineacion del contador de categorias en la Home.
 *
 * El layout historico deja un padding derecho en el bloque de contenido de la
 * tarjeta. El contador debe llegar al borde derecho util de la tarjeta, sin
 * cambiar la posicion del nombre ni la logica de conteo.
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
		<style id="elmercado-home-category-edge-align-010215">
			html body.home.elmercado-child-theme .emo-home .emo-category-card .emo-category-card__content {
				padding-right: 0 !important;
				padding-inline-end: 0 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
