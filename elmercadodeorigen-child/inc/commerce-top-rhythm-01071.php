<?php
/**
 * Ritmo superior definitivo para superficies WooCommerce sin page header.
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
		<style id="elmercado-commerce-top-rhythm-01071">
			/*
			 * Woostify reserva margen sobre el wrapper WooCommerce pensando en el
			 * page-header nativo. Al haberlo retirado, ese margen duplicaba el gap
			 * global. El único aire superior debe venir de --emo-content-start-gap.
			 */
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout,.woocommerce-account) .woocommerce {
				margin-top: 0 !important;
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				margin-top: 0 !important;
			}

			/* El primer scroll sólo cambia el acabado del header, nunca el flujo. */
			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout,.woocommerce-account) .woocommerce {
				margin-top: 0 !important;
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
