<?php
/**
 * Legibilidad final del checkout 0.10.131.
 *
 * Ajusta únicamente contraste fino del resumen y muestra el aviso de recálculo
 * solo cuando existe un overlay AJAX real de WooCommerce. En sesiones de QA
 * user-visual fija una dirección peninsular en carrito/checkout para auditar
 * métodos de envío reales sin afectar a usuarios normales.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp',
	static function (): void {
		if (
			is_admin()
			|| ! isset( $_GET['user-visual'] )
			|| ! function_exists( 'is_cart' )
			|| ! function_exists( 'is_checkout' )
			|| ( ! is_cart() && ! is_checkout() )
		) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->customer || ! WC()->cart ) {
			return;
		}

		$customer = WC()->customer;
		$customer->set_billing_country( 'ES' );
		$customer->set_billing_state( 'M' );
		$customer->set_billing_postcode( '28001' );
		$customer->set_billing_city( 'Madrid' );
		$customer->set_billing_address_1( 'Calle de Alcalá 1' );
		$customer->set_shipping_country( 'ES' );
		$customer->set_shipping_state( 'M' );
		$customer->set_shipping_postcode( '28001' );
		$customer->set_shipping_city( 'Madrid' );
		$customer->set_shipping_address_1( 'Calle de Alcalá 1' );
		$customer->set_calculated_shipping( true );
		$customer->save();

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
	},
	5
);

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
