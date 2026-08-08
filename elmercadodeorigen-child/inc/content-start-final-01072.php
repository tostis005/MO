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
		<style id="elmercado-content-start-final-01072">
			/*
			 * Los intros transaccionales tienen un pequeño aire tipográfico propio.
			 * Evitamos sumarle además el gutter de .site-content para que su primer
			 * elemento visible quede al mismo nivel que Tienda y el resto de páginas.
			 */
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) .site-content {
				padding-top: 0 !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				margin-top: -6px !important;
			}

			/* El estado de scroll no puede modificar este ritmo. */
			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) .site-content {
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				margin-top: -6px !important;
				translate: none !important;
				transform: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
