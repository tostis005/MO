<?php
/**
 * Alineación exacta del slider de precio en móvil 0.10.209.
 *
 * Centra geométricamente los tiradores sobre la pista para evitar que el borde
 * o reglas heredadas desplacen visualmente las bolitas por encima de la raya.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) ) {
			return;
		}
		if ( ! is_shop() && ! is_product_category() ) {
			return;
		}
		?>
		<style id="elmercado-mobile-price-slider-alignment-010209">
			@media (max-width: 1100px) {
				html body.elmercado-child-theme .emo-mobile-filter-content .widget_price_filter .price_slider {
					position: relative !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-content .widget_price_filter .price_slider .ui-slider-handle {
					top: 50% !important;
					margin-top: 0 !important;
					box-sizing: border-box !important;
					transform: translateY(-50%) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
