<?php
/**
 * Normalize the vendor toolbar/product gap using static document flow.
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
		<style id="elmercado-vendor-flow-gap-final-01041">
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products {
				margin-top: -46px !important;
				transform: none !important;
				transition: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
