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

			/* Evita la segunda línea duplicada de marca que aparece en algunas
			 * plantillas móviles de Woostify/WCFM. */
			@media (max-width: 991px) {
				body.elmercado-child-theme .site-header .site-branding > .site-title ~ .site-title,
				body.elmercado-child-theme .site-header .site-branding .site-description {
					display: none !important;
				}
			}

			/* El acceso flotante a WhatsApp no debe invadir buscadores, filtros o
			 * controles del directorio en pantallas estrechas. */
			@media (max-width: 767px) {
				body.elmercado-child-theme #ht-ctc-chat,
				body.elmercado-child-theme .ht-ctc-chat {
					left: auto !important;
					right: 12px !important;
					bottom: calc(76px + env(safe-area-inset-bottom, 0px)) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
