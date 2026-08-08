<?php
/**
 * Ritmo vertical final de la portada 0.10.98.
 *
 * Reduce de forma coherente el aire previo a las secciones editoriales sin
 * compactar el hero ni la franja inicial de confianza.
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
		<style id="elmercado-home-rhythm-final-01098">
			/*
			 * La portada usaba el padding global de .emo-section (hasta 9rem),
			 * demasiado amplio para una secuencia de bloques editoriales. Dejamos
			 * una entrada común más corta y un poco más de salida para conservar
			 * la respiración entre contenidos.
			 */
			body.home.elmercado-child-theme .emo-home > section:not(.emo-hero):not(.emo-trust) {
				padding-top: clamp(3.25rem, 5.25vw, 5.25rem) !important;
			}

			body.home.elmercado-child-theme .emo-home > .emo-section {
				padding-bottom: clamp(3.75rem, 6vw, 6.25rem) !important;
			}

			/* Los dos bloques finales quedan ligeramente más próximos a lo anterior. */
			body.home.elmercado-child-theme .emo-home > :is(.emo-story, .emo-vendor-cta) {
				padding-top: clamp(3rem, 4.8vw, 4.75rem) !important;
			}

			@media (max-width: 767px) {
				body.home.elmercado-child-theme .emo-home > section:not(.emo-hero):not(.emo-trust) {
					padding-top: 2.625rem !important;
				}

				body.home.elmercado-child-theme .emo-home > .emo-section {
					padding-bottom: 3.25rem !important;
				}

				body.home.elmercado-child-theme .emo-home > :is(.emo-story, .emo-vendor-cta) {
					padding-top: 2.375rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
