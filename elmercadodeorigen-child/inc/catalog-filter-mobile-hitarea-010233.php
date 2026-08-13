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
		<style id="elmercado-catalog-filter-mobile-hitarea-010233-v2">
			@media (max-width:991px) {
				html body.elmercado-child-theme.wcfmmp-store-page .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
					position:relative !important;
					z-index:100 !important;
					isolation:isolate !important;
					pointer-events:auto !important;
					touch-action:manipulation !important;
				}

				html body.elmercado-child-theme .emo-mobile-filter-shell.emo-filter-shell-shared-010229 .emo-mobile-filter-close {
					display:grid !important;
					box-sizing:border-box !important;
					flex:0 0 40px !important;
					width:40px !important;
					min-width:40px !important;
					max-width:40px !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					margin:0 !important;
					padding:0 !important;
					border:0 !important;
					border-radius:50% !important;
					place-items:center !important;
					background:#173f32 !important;
					color:#fff !important;
					font-size:22px !important;
					line-height:1 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
