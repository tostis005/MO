<?php
/**
 * Línea de arranque compartida y separación definitiva del filtro 0.10.82.
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
		<style id="elmercado-visible-start-line-01082">
			/* Tienda marca la línea compacta de referencia en cada breakpoint. */
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

				/* Más específico que la regla genérica .widget: 20 px reales. */
				html body.elmercado-child-theme #emo-premium-filter-shell .emo-mobile-filter-content .widget.widget_price_filter {
					margin-bottom: 20px !important;
				}
			}

			@media (min-width: 768px) {
				html body.elmercado-child-theme.elmercado-about-page .emo-about-layout,
				html body.elmercado-child-theme.is-scrolled.elmercado-about-page .emo-about-layout {
					margin-top: -22.6px !important;
				}
				html body.elmercado-child-theme:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro),
				html body.elmercado-child-theme.is-scrolled:is(.woocommerce-cart,.woocommerce-checkout) :is(.emo-cart-intro,.emo-checkout-intro) {
					margin-top: -32.6px !important;
				}
			}

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
