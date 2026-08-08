<?php
/**
 * Alineación visual definitiva de carrito y checkout con el resto del sitio.
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
		<style id="elmercado-content-start-final-01073">
			/*
			 * El primer elemento visible de carrito/checkout se alinea con el inicio
			 * de Tienda. Es un ajuste estático de flujo: sin transform ni mediciones
			 * en runtime, y con valor específico para cada ritmo de cabecera.
			 */
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) .site-content {
				padding-top: 0 !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				margin-top: -30px !important;
			}

			/* El estado de scroll sólo cambia el acabado del header, no el flujo. */
			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) .site-content {
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				margin-top: -30px !important;
				translate: none !important;
				transform: none !important;
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro),
				html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
					margin-top: -24px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
