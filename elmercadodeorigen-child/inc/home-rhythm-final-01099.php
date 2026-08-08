<?php
/**
 * Ritmo vertical final de la portada 0.10.99.
 *
 * Homogeneiza la entrada de las secciones de Home y acerca también el primer
 * mensaje del hero a la cabecera, sin tocar la estructura ni las alturas del
 * contenido interno.
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
		<style id="elmercado-home-rhythm-final-01099">
			/* El primer mensaje del hero queda más próximo a la cabecera. */
			body.home.elmercado-child-theme .emo-home > .emo-hero {
				padding-top: clamp(2.75rem, 4vw, 4rem) !important;
			}

			/* Entrada común y más corta para todas las secciones editoriales. */
			body.home.elmercado-child-theme .emo-home > section.emo-section {
				padding-top: clamp(3rem, 4.15vw, 4.25rem) !important;
				padding-bottom: clamp(3.75rem, 6vw, 6.25rem) !important;
			}

			@media (max-width: 767px) {
				body.home.elmercado-child-theme .emo-home > .emo-hero {
					padding-top: 2.5rem !important;
				}

				body.home.elmercado-child-theme .emo-home > section.emo-section {
					padding-top: 2.25rem !important;
					padding-bottom: 3.25rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
