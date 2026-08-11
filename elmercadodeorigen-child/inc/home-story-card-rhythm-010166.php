<?php
/**
 * Ritmo uniforme de las tarjetas de criterio de la Home 0.10.166.
 *
 * Homogeneiza exclusivamente la distancia entre el titular y su descripción
 * en las tres tarjetas, sin alterar estructura, textos, tamaños ni distribución.
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
		<style id="elmercado-home-story-card-rhythm-010166">
			body.home.elmercado-child-theme .emo-story__values article {
				row-gap: 0 !important;
			}

			body.home.elmercado-child-theme .emo-story__values article h3 {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
				padding-bottom: 0 !important;
			}

			body.home.elmercado-child-theme .emo-story__values article h3 + p {
				margin-top: 0.9rem !important;
				margin-bottom: 0 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
