<?php
/**
 * Measured layout corrections after focused browser audit.
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
		<style id="elmercado-layout-consistency-098">
			@media (min-width: 992px) {
				body.elmercado-child-theme .site-header,
				body.elmercado-child-theme .site-header .site-header-inner,
				body.elmercado-child-theme .site-header .woostify-container {
					min-height: 62px !important;
					height: 62px !important;
				}
				body.elmercado-child-theme .site-header .site-branding {
					display: flex !important;
					align-items: center !important;
					width: 190px !important;
					height: 62px !important;
					flex: 0 0 190px !important;
				}
				body.elmercado-child-theme .site-header #site-navigation.main-navigation {
					display: flex !important;
					align-items: center !important;
					justify-content: flex-start !important;
					flex: 0 0 auto !important;
					width: max-content !important;
					max-width: max-content !important;
					height: 62px !important;
					margin: 0 0 0 58px !important;
				}
				body.elmercado-child-theme .site-header #site-navigation.main-navigation > ul,
				body.elmercado-child-theme .site-header #site-navigation.main-navigation .primary-navigation {
					width: max-content !important;
					max-width: max-content !important;
					justify-content: flex-start !important;
				}
				body.elmercado-child-theme .site-header .site-tools {
					height: 62px !important;
					margin-left: auto !important;
				}
			}

			/* Algunas plantillas móviles imprimen de nuevo el nombre dentro del
			 * propio título. Conservamos solo el título exterior. */
			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header .site-branding .site-title .site-title,
				body.elmercado-child-theme .site-header .site-branding > .site-title ~ .site-title,
				body.elmercado-child-theme .site-header .site-branding .site-description {
					display: none !important;
				}
			}

			/* WhatsApp queda disponible abajo a la derecha, pero con un z-index
			 * inferior al consentimiento para no tapar su texto ni sus controles. */
			@media (max-width: 767px) {
				body.elmercado-child-theme #ht-ctc-chat,
				body.elmercado-child-theme .ht-ctc-chat {
					left: auto !important;
					right: 12px !important;
					bottom: calc(18px + env(safe-area-inset-bottom, 0px)) !important;
					z-index: 20 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
