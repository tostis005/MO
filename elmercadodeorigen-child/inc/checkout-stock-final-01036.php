<?php
/**
 * Checkout y agotados: pasada final 0.10.36.
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
		<style id="elmercado-checkout-stock-final-01036">
			/*
			 * Algunas pasarelas vuelven a pintar fondos grises dentro de #order_review.
			 * Forzamos una superficie oscura homogénea y reservamos el blanco sólo para
			 * las cajas explicativas de cada método.
			 */
			html body.elmercado-child-theme.woocommerce-checkout #order_review :is(
				.shop_table,
				.shop_table > *,
				.shop_table > * > *,
				.shop_table > * > * > *,
				#payment,
				#payment > *,
				.payment_methods,
				.payment_methods > *,
				.payment_methods > * > *
			) {
				background-color: #173f32 !important;
				border-color: rgba(255, 255, 255, .18) !important;
				color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #order_review :is(
				.shop_table th,
				.shop_table td,
				.shop_table .amount,
				.shop_table strong,
				#payment label,
				#payment p,
				#payment span,
				#payment strong,
				#payment small
			) {
				color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box,
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box *,
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box > * {
				background-color: #fff !important;
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box {
				border: 1px solid rgba(255, 255, 255, .35) !important;
				border-radius: 12px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box::before {
				border-bottom-color: #fff !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment a:not(.button) {
				color: #f2ce83 !important;
				font-weight: 800 !important;
			}

			/* Las reseñas externas no deben competir con el formulario de pago. */
			html body.elmercado-child-theme.woocommerce-checkout:not(.woocommerce-order-received) .woocommerce > form.checkout ~ *,
			html body.elmercado-child-theme.woocommerce-checkout:not(.woocommerce-order-received) :is(
				.ti-widget,
				.ti-widget-container,
				[id*="trustindex" i],
				[class*="trustindex" i],
				[id*="trustpilot" i],
				[class*="trustpilot" i]
			) {
				display: none !important;
			}

			/* Estado de stock inequívoco también en la ficha individual. */
			html body.elmercado-child-theme.single-product div.product.outofstock .summary::before {
				content: "Agotado";
				display: inline-flex;
				align-items: center;
				min-height: 30px;
				margin: 0 0 12px;
				padding: 6px 10px;
				background: #7f2f2a;
				border-radius: 999px;
				color: #fff;
				font-size: .74rem;
				font-weight: 850;
				letter-spacing: .04em;
				line-height: 1;
				text-transform: uppercase;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
