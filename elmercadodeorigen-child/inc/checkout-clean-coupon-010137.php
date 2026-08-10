<?php
/**
 * Checkout limpio y tratamiento final de cupones 0.10.138.
 *
 * Reduce divisores y contornos redundantes del resumen/pago y da al flujo de
 * cupones un acabado coherente con el resto del sistema visual. No modifica la
 * lógica de WooCommerce ni aplica cupones automáticamente.
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
		<style id="elmercado-checkout-clean-coupon-010138">
			/* Una sola tarjeta de resumen: la profundidad la da la superficie, no el contorno. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review_heading,
			html body.elmercado-child-theme.woocommerce-checkout #order_review {
				border: 0 !important;
				outline: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review_heading {
				padding-bottom: 8px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review {
				padding-top: 7px !important;
				box-shadow: 0 20px 52px rgba(13,33,27,.18) !important;
			}

			/* Tabla del pedido: fuera líneas horizontales; jerarquía mediante espacio, peso y color. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table,
			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table :is(thead,tbody,tfoot,tr,th,td) {
				border: 0 !important;
				border-color: transparent !important;
				outline: 0 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table :is(th,td) {
				padding-top: 9px !important;
				padding-bottom: 9px !important;
				border-bottom: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table thead :is(th,td) {
				padding-bottom: 11px !important;
				color: rgba(255,253,248,.66) !important;
				font-size: 11px !important;
				font-weight: 850 !important;
				letter-spacing: .055em !important;
				text-transform: uppercase !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table tr.cart_item :is(th,td) {
				padding-top: 11px !important;
				padding-bottom: 13px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .shop_table tfoot tr.order-total :is(th,td) {
				padding-top: 15px !important;
				padding-bottom: 4px !important;
			}

			/* Envío: misma lógica limpia ya validada en carrito. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review ul#shipping_method > li,
			html body.elmercado-child-theme.woocommerce-checkout #order_review ul.woocommerce-shipping-methods > li {
				border: 0 !important;
				background: rgba(255,255,255,.045) !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping,
			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping > :is(th,td) {
				border: 0 !important;
				border-bottom: 0 !important;
			}

			/* Cupón aplicado: bloque cálido sin otra línea blanca dentro del resumen. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount > th,
			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount > td {
				padding-top: 9px !important;
				padding-bottom: 9px !important;
				border: 0 !important;
				background: rgba(241,213,156,.10) !important;
				color: #f1d59c !important;
				font-weight: 850 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount > th {
				padding-left: 11px !important;
				border-radius: 10px 0 0 10px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount > td {
				padding-right: 11px !important;
				border-radius: 0 10px 10px 0 !important;
				text-align: right !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount :is(.amount,strong) {
				color: #f1d59c !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review .woocommerce-remove-coupon {
				display: inline-flex !important;
				margin-left: 7px !important;
				color: rgba(255,253,248,.76) !important;
				font-size: 11px !important;
				font-weight: 750 !important;
				text-decoration: underline !important;
				text-decoration-thickness: 1px !important;
				text-underline-offset: 3px !important;
			}

			/* Pago: tarjetas suaves sin marcos blancos repetidos. */
			html body.elmercado-child-theme.woocommerce-checkout #payment,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.wc_payment_methods,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods,
			html body.elmercado-child-theme.woocommerce-checkout #payment .form-row.place-order {
				border: 0 !important;
				border-top: 0 !important;
				border-bottom: 0 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment li.wc_payment_method,
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods > li {
				padding: 12px 13px !important;
				border: 0 !important;
				background: rgba(255,255,255,.045) !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment li.wc_payment_method:has(> input[type="radio"]:checked),
			html body.elmercado-child-theme.woocommerce-checkout #payment ul.payment_methods > li:has(> input[type="radio"]:checked) {
				background: rgba(255,255,255,.085) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box {
				margin-top: 10px !important;
				padding: 11px 13px !important;
				border: 0 !important;
				background: rgba(255,255,255,.055) !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box::before,
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box::after {
				display: none !important;
				content: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .form-row.place-order {
				margin-top: 15px !important;
				padding-top: 5px !important;
			}

			/* El estado AJAX se distingue por fondo, no por otra caja con borde. */
			html body.elmercado-child-theme.woocommerce-checkout #order_review .emo-checkout-loading-note {
				border: 0 !important;
				outline: 0 !important;
				background: rgba(255,255,255,.065) !important;
				box-shadow: none !important;
			}

			/* Toggle, formulario y mensajes de cupón: superficie clara y compacta. */
			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-form-coupon-toggle {
				margin-bottom: 12px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-form-coupon-toggle .woocommerce-info,
			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-notices-wrapper :is(.woocommerce-message,.woocommerce-info,.woocommerce-error),
			html body.elmercado-child-theme.woocommerce-checkout .woocommerce > :is(.woocommerce-message,.woocommerce-info,.woocommerce-error) {
				box-sizing: border-box !important;
				border: 0 !important;
				border-left: 0 !important;
				outline: 0 !important;
				border-radius: 14px !important;
				background: #fffdf8 !important;
				box-shadow: 0 10px 28px rgba(13,33,27,.07) !important;
				color: #355047 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-notices-wrapper .woocommerce-message {
				background: #edf4ef !important;
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-notices-wrapper .woocommerce-error {
				background: #fff1ed !important;
				color: #7f2f2a !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-notices-wrapper :is(.woocommerce-message,.woocommerce-info,.woocommerce-error)::after {
				display: none !important;
				content: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-notices-wrapper .woocommerce-message::before,
			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-form-coupon-toggle .woocommerce-info::before {
				color: #2f6650 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-notices-wrapper .woocommerce-error::before {
				color: #a74f2d !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-form-coupon-toggle .woocommerce-info {
				margin: 0 !important;
				padding: 13px 15px !important;
				font-size: 13px !important;
				line-height: 1.45 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .woocommerce-form-coupon-toggle .showcoupon {
				color: #a74f2d !important;
				font-weight: 850 !important;
				text-decoration: underline !important;
				text-decoration-thickness: 1px !important;
				text-underline-offset: 3px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon {
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 0 0 20px !important;
				padding: 14px !important;
				border: 0 !important;
				border-radius: 16px !important;
				background: #fffdf8 !important;
				box-shadow: 0 12px 32px rgba(13,33,27,.07) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon :is(.form-row,.form-row-first,.form-row-last) {
				box-sizing: border-box !important;
				padding: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon input.input-text {
				box-sizing: border-box !important;
				width: 100% !important;
				min-height: 46px !important;
				margin: 0 !important;
				padding: 10px 13px !important;
				border: 1px solid rgba(23,63,50,.18) !important;
				border-radius: 999px !important;
				background: #fff !important;
				color: #173f32 !important;
				box-shadow: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon button.button,
			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon input.button {
				min-height: 46px !important;
				padding: 10px 18px !important;
				border-color: #c96d45 !important;
				background: #c96d45 !important;
				color: #fff !important;
				box-shadow: 0 8px 18px rgba(201,109,69,.18) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon button.button:hover,
			html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon button.button:focus {
				border-color: #e07c50 !important;
				background: #e07c50 !important;
			}

			@media (min-width: 768px) {
				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon .form-row-first {
					float: left !important;
					width: calc(100% - 166px) !important;
					margin: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon .form-row-last {
					float: right !important;
					width: 156px !important;
					margin: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon .form-row-last .button {
					width: 100% !important;
				}
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-checkout #order_review tr.woocommerce-shipping-totals.shipping {
					border: 0 !important;
					border-bottom: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount {
					display: grid !important;
					grid-template-columns: minmax(0,1fr) auto !important;
					align-items: center !important;
					gap: 8px !important;
					margin: 4px 0 !important;
					border-radius: 10px !important;
					background: rgba(241,213,156,.10) !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout #order_review tr.cart-discount > :is(th,td) {
					display: block !important;
					width: auto !important;
					margin: 0 !important;
					padding: 9px 11px !important;
					border-radius: 0 !important;
					background: transparent !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon .form-row-first,
				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon .form-row-last {
					float: none !important;
					width: 100% !important;
					margin: 0 0 10px !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon .form-row-last {
					margin-bottom: 0 !important;
				}

				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon button.button,
				html body.elmercado-child-theme.woocommerce-checkout form.checkout_coupon input.button {
					width: 100% !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
