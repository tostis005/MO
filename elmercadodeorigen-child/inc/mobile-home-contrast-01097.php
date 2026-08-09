<?php
/**
 * Contraste final del bloque editorial claro de la portada móvil 0.10.114.
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
		<style id="elmercado-mobile-home-contrast-010114">
			@media (max-width: 767px) {
				body.home.elmercado-child-theme .emo-story__panel p {
					color: #4f5d56 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
