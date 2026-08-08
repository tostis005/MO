<?php
/**
 * Cierre visual solicitado: ritmo del carrito y fallback legible del checkout.
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
		<style id="elmercado-visual-coherence-01065">
			/* El bloque de confianza es una lista vertical compacta, nunca una rejilla estirada. */
			html body.woocommerce-cart .emo-cart-assurance {
				display: flex !important;
				flex-direction: column !important;
				flex-wrap: nowrap !important;
				align-items: stretch !important;
				justify-content: flex-start !important;
				gap: 7px !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 10px 0 0 !important;
				padding: 10px 0 0 !important;
			}

			html body.woocommerce-cart .emo-cart-assurance > span {
				position: static !important;
				display: flex !important;
				flex: 0 0 auto !important;
				align-items: flex-start !important;
				justify-content: flex-start !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 0 !important;
				padding: 0 !important;
				line-height: 1.35 !important;
			}

			/* Checkout: si WooCommerce aún no ha pintado una fila de producto, mostramos
			 * el estado real de preparación en lugar de una tarjeta verde vacía. */
			html body.woocommerce-checkout .emo-checkout-summary-column > .emo-checkout-status-card {
				display: flex !important;
				height: auto !important;
				min-height: 116px !important;
				max-height: none !important;
				flex-direction: column !important;
				justify-content: center !important;
				gap: 8px !important;
				box-sizing: border-box !important;
				padding: 20px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-radius: 18px !important;
				background: #173f32 !important;
				color: #fffdf8 !important;
			}

			html body.woocommerce-checkout .emo-checkout-summary-column:has(> #order_review tr.cart_item) > .emo-checkout-status-card {
				display: none !important;
			}

			html body.woocommerce-checkout .emo-checkout-summary-column:not(:has(> #order_review tr.cart_item)) > :is(#order_review_heading,#order_review) {
				display: none !important;
			}

			/* Fallback sin JavaScript: incluso si el nodo de estado aún no existe, nunca
			 * dejamos una superficie vacía. */
			html body.woocommerce-checkout .emo-checkout-summary-column:not(:has(> #order_review tr.cart_item)):not(:has(> .emo-checkout-status-card))::before {
				display: block !important;
				box-sizing: border-box !important;
				min-height: 116px !important;
				padding: 22px !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-radius: 18px !important;
				background: #173f32 !important;
				color: #fffdf8 !important;
				content: "Preparando tu resumen\A Estamos actualizando el pedido y las opciones de pago con tus datos." !important;
				font-size: 13px !important;
				font-weight: 650 !important;
				line-height: 1.55 !important;
				white-space: pre-line !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
