<?php
/**
 * Ritmo uniforme de las tarjetas de criterio de la Home 0.10.167.
 *
 * En móvil, la etiqueta lateral abarca las dos filas de contenido para que su
 * altura no altere la separación entre el titular y la descripción.
 * No modifica estructura, textos, tamaños ni distribución general.
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
		<style id="elmercado-home-story-card-rhythm-010167">
			@media (max-width: 767px) {
				body.home.elmercado-child-theme .emo-story__values article {
					row-gap: 0.9rem !important;
				}

				body.home.elmercado-child-theme .emo-story__values article > span {
					grid-row: 1 / span 2 !important;
				}

				body.home.elmercado-child-theme .emo-story__values article h3 {
					grid-column: 2 !important;
					grid-row: 1 !important;
					margin: 0 !important;
					padding: 0 !important;
				}

				body.home.elmercado-child-theme .emo-story__values article h3 + p {
					grid-column: 2 !important;
					grid-row: 2 !important;
					margin: 0 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
