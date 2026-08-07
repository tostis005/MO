<?php
/**
 * Contraste final del checkout 0.10.35.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		?>
		<style id="elmercado-checkout-contrast-final-01035">
			/* Mantener todo el resumen y los métodos de pago sobre un fondo oscuro estable. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table tbody,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table tfoot,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table tr,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table th,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table td,
			html body.elmercado-child-theme.woocommerce-checkout #payment,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods > li,
			html body.elmercado-child-theme.woocommerce-checkout #payment .form-row.place-order {
				background: #173f32 !important;
				border-color: rgba(255, 255, 255, .18) !important;
				color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review :is(
				th,
				td,
				.product-name,
				.product-total,
				.amount,
				strong,
				small
			),
			html body.elmercado-child-theme.woocommerce-checkout #payment :is(
				label,
				p,
				span,
				strong,
				small,
				.woocommerce-terms-and-conditions-checkbox-text,
				.woocommerce-privacy-policy-text
			) {
				color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment a {
				color: #f2ce83 !important;
				font-weight: 800 !important;
			}

			/* Las cajas explicativas de cada pasarela deben seguir siendo claras. */
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box,
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box :is(p,span,label,strong,small,a) {
				color: #173f32 !important;
			}
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box {
				background: #fff !important;
				border: 1px solid rgba(255, 255, 255, .35) !important;
			}
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box::before {
				border-bottom-color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment input[type="radio"],
			html body.elmercado-child-theme.woocommerce-checkout #payment input[type="checkbox"] {
				accent-color: #d7a84f !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #place_order {
				background: #f1d59c !important;
				border-color: #f1d59c !important;
				color: #0d211b !important;
				font-weight: 900 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
