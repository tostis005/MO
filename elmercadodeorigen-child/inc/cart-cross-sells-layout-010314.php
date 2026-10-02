<?php
/**
 * Carrito: recomendaciones bajo el listado y totales en la columna derecha.
 *
 * En escritorio, WooCommerce coloca por defecto los cross-sells dentro de
 * .cart-collaterals, lo que los comprime junto al resumen. Con display: contents
 * mantenemos el DOM y los hooks nativos, pero permitimos que cross-sells y
 * totales participen directamente en la rejilla principal del carrito.
 *
 * 0.10.315: en escritorio las tarjetas recomendadas usan tres columnas en
 * lugar de dos, reduciendo cada producto a aproximadamente dos tercios del
 * ancho anterior sin estrechar el bloque completo de recomendaciones.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<style id="elmercado-cart-cross-sells-layout-010314">
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout {
				grid-template-areas:
					"cart totals"
					"cross totals";
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .woocommerce-cart-form {
				grid-area: cart !important;
				min-width: 0 !important;
			}

			/*
			 * El contenedor nativo deja de crear una segunda rejilla. Sus hijos
			 * pasan a ser items de .emo-cart-layout sin moverlos en el DOM.
			 */
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals {
				display: contents !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals::before,
			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals::after {
				display: none !important;
				content: none !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells {
				grid-area: cross !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				float: none !important;
				clear: both !important;
				margin: clamp(1.35rem, 2.5vw, 2rem) 0 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells > h2 {
				margin: 0 0 1rem !important;
				color: #0d211b !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(1.65rem, 2.7vw, 2.35rem) !important;
				font-weight: 700 !important;
				line-height: 1.08 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells > ul.products {
				display: grid !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: clamp(.85rem, 1.8vw, 1.25rem) !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells > ul.products > li.product {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				float: none !important;
				clear: none !important;
				margin: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cart_totals {
				grid-area: totals !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				float: none !important;
				clear: none !important;
				align-self: start !important;
			}

			@media (max-width: 991px) {
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout {
					grid-template-areas:
						"cart"
						"totals"
						"cross";
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells {
					margin-top: .4rem !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells > ul.products {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 640px) {
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals > .cross-sells > ul.products {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
