<?php
/**
 * Ajuste final de la línea de arranque visible en páginas sobre papel 0.10.81.
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
		<style id="elmercado-visible-start-line-01081">
			/*
			 * Tienda marca la línea compacta de referencia. Los márgenes son estáticos
			 * y se mantienen idénticos al entrar en el estado is-scrolled.
			 */
			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-shop .emo-shop-lead {
					margin-top: 0 !important;
				}
				html body.elmercado-child-theme.elmercado-about-page .emo-about-layout,
				html body.elmercado-child-theme.is-scrolled.elmercado-about-page .emo-about-layout {
					margin-top: -16.6px !important;
				}
				html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro),
				html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
					margin-top: -26.6px !important;
				}
			}

			/* En escritorio carrito y checkout comparten la línea de Quiénes somos. */
			@media (min-width: 768px) {
				html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro),
				html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
					margin-top: -10px !important;
				}
			}

			html body.elmercado-child-theme.is-scrolled.woocommerce-shop .emo-shop-lead,
			html body.elmercado-child-theme.is-scrolled.elmercado-about-page .emo-about-layout,
			html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
				top: auto !important;
				translate: none !important;
				transform: none !important;
			}

			@media (max-width: 767px) {
				/* Separación inequívoca entre Precio y Categorías en el drawer. */
				html body.elmercado-child-theme #emo-premium-filter-shell .widget_price_filter {
					margin-bottom: 20px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
