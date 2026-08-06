<?php
/**
 * Exact desktop header coordinates across full-width and normal containers.
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
		<style id="elmercado-header-exact-consistency">
			@media (min-width: 992px) {
				body.elmercado-child-theme:not(.home) .site-header .site-branding {
					margin-left: -15px !important;
				}
				body.elmercado-child-theme:not(.home) .site-header .site-tools {
					margin-right: -15px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
