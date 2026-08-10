<?php
/**
 * Legibilidad final del checkout 0.10.128.
 *
 * Ajusta únicamente contraste fino del resumen y muestra el aviso de recálculo
 * solo cuando existe un overlay AJAX real de WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		?>
		<style id="elmercado-checkout-legibility-final-010128">
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table .product-name a,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table .product-name a:visited,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table .product-name :is(.wcfmmp_sold_by_container,.wcfmmp_sold_by_label,.wcfmmp_sold_by_wrapper) a {
				color: #f1d59c !important;
				text-decoration-color: rgba(241,213,156,.55) !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table .product-name :is(.wcfmmp_sold_by_container,.wcfmmp_sold_by_label,.wcfmmp_sold_by_wrapper),
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table .product-name small {
				color: rgba(255,253,248,.78) !important;
				opacity: 1 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review > .emo-checkout-loading-note {
				display: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review:has(.blockUI.blockOverlay) > .emo-checkout-loading-note {
				display: block !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
