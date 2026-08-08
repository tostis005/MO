<?php
/**
 * Ritmo superior transaccional sin compensaciones geométricas.
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
		<style id="elmercado-content-start-final-01079">
			/*
			 * Carrito y checkout se apoyan en el espaciado común de site-content.
			 * No usan márgenes negativos ni transformaciones para compensar el header.
			 */
			html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(
				.woocommerce,
				.emo-cart-intro,
				.emo-checkout-intro
			) {
				margin-top: 0 !important;
				padding-top: 0 !important;
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(
				.woocommerce,
				.emo-cart-intro,
				.emo-checkout-intro
			) {
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
