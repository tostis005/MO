<?php
/**
 * Protege el disparador móvil compartido de overlays de tarjetas de producto.
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
		<style id="elmercado-catalog-filter-mobile-hitarea-010233">
			@media (max-width:991px) {
				html body.elmercado-child-theme.wcfmmp-store-page .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
					position:relative !important;
					z-index:100 !important;
					isolation:isolate !important;
					pointer-events:auto !important;
					touch-action:manipulation !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
