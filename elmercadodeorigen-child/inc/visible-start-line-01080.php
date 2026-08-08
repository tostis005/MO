<?php
/**
 * Ajuste final de la línea de arranque visible en páginas sobre papel 0.10.80.
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
		<style id="elmercado-visible-start-line-01080">
			/*
			 * Las cuatro plantillas tienen wrappers internos distintos. Ajustamos sólo
			 * el margen estático del primer bloque útil para que su primer kicker quede
			 * en la misma línea. El valor no cambia con scroll, resize ni JavaScript.
			 */
			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-shop .emo-shop-lead {
					margin-top: 6px !important;
				}
				html body.elmercado-child-theme.elmercado-about-page .emo-about-layout {
					margin-top: -10px !important;
				}
				html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
					margin-top: -20px !important;
				}
			}

			/* El estado visual del header nunca modifica estos valores. */
			html body.elmercado-child-theme.is-scrolled.woocommerce-shop .emo-shop-lead,
			html body.elmercado-child-theme.is-scrolled.elmercado-about-page .emo-about-layout,
			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
