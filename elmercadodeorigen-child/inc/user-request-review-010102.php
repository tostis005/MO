<?php
/**
 * Revisión visual solicitada: carrito, productos de portada y continuidad del catálogo 0.10.102.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * En archivos de categoría/etiqueta mantenemos el mismo contexto introductorio
 * de la tienda para que filtrar no parezca un salto a otra página.
 */
add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product_category' ) || ! function_exists( 'is_product_tag' ) ) {
			return;
		}

		if ( ! is_product_category() && ! is_product_tag() ) {
			return;
		}
		?>
		<div class="emo-shop-lead emo-shop-lead--filtered">
			<p><?php esc_html_e( 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa.', 'elmercadodeorigen' ); ?></p>
		</div>
		<?php
	},
	1
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-user-request-review-010102">
			/*
			 * Portada: ningún contenedor interior de producto vuelve a pintar
			 * una tarjeta blanca. La única superficie es la propia sección.
			 */
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product:hover,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-wrapper,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product :is(
				.woocommerce-LoopProduct-link,
				.woocommerce-loop-product__link,
				.product-loop-content,
				.product-content,
				.product-thumbnail,
				.product-image,
				.product-loop-image
			) {
				background: transparent !important;
				background-color: transparent !important;
				background-image: none !important;
				border-color: transparent !important;
				box-shadow: none !important;
			}

			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-wrapper {
				border: 0 !important;
				border-radius: 0 !important;
				overflow: visible !important;
			}

			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper {
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-radius: 18px !important;
				overflow: hidden !important;
				isolation: isolate !important;
			}

			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper::before,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper::after {
				display: none !important;
				content: none !important;
				background: none !important;
			}

			body.home.elmercado-child-theme .emo-featured-products ul.products li.product :is(
				.product-loop-image-wrapper img,
				img.product-loop-image,
				.woocommerce-LoopProduct-link img,
				.woocommerce-loop-product__link img
			) {
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				background-color: transparent !important;
				background-image: none !important;
				border-radius: 18px !important;
			}

			body.home.elmercado-child-theme .emo-featured-products ul.products li.product :is(
				.product-loop-content,
				.product-content
			) {
				margin-top: 0 !important;
				padding: 12px 2px 0 !important;
				border: 0 !important;
				overflow: visible !important;
			}

			/*
			 * Móvil en horizontal: misma lógica de densidad que categorías.
			 * A partir del ancho horizontal del teléfono hay tres productos por fila.
			 */
			@media (orientation: landscape) and (max-width: 991px) and (max-height: 600px) {
				body.home.elmercado-child-theme .emo-featured-products .woocommerce,
				body.home.elmercado-child-theme .emo-featured-products ul.products {
					width: 100% !important;
					max-width: none !important;
					margin-inline: 0 !important;
					padding-inline: 0 !important;
				}

				body.home.elmercado-child-theme .emo-featured-products ul.products {
					display: grid !important;
					grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
					gap: 14px !important;
					overflow: visible !important;
					scroll-snap-type: none !important;
				}

				body.home.elmercado-child-theme .emo-featured-products ul.products li.product {
					width: auto !important;
					min-width: 0 !important;
					max-width: none !important;
					margin: 0 !important;
					scroll-snap-align: none !important;
				}
			}

			/*
			 * Carrito: la tabla de totales ocupa todo el ancho útil de la tarjeta.
			 * Etiquetas al margen izquierdo interior e importes al margen derecho interior.
			 */
			body.elmercado-child-theme.woocommerce-cart .cart_totals :is(
				table.shop_table,
				table.shop_table_responsive
			) {
				display: table !important;
				width: 100% !important;
				max-width: none !important;
				min-width: 0 !important;
				margin: 0 !important;
				table-layout: fixed !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals :is(tbody, tr) {
				width: 100% !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals th,
			body.elmercado-child-theme.woocommerce-cart .cart_totals td {
				box-sizing: border-box !important;
				width: 50% !important;
				max-width: none !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals th {
				padding-right: 12px !important;
				padding-left: 0 !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals td {
				padding-right: 0 !important;
				padding-left: 12px !important;
				text-align: right !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals td :is(
				.amount,
				strong,
				.includes_tax
			) {
				margin-right: 0 !important;
				margin-left: auto !important;
				text-align: right !important;
			}

			/*
			 * Catálogo filtrado: conserva el bloque de contexto y el aire que
			 * tiene la tienda antes de la barra "Mostrando resultados".
			 */
			body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag) .emo-shop-lead {
				display: block !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 0 clamp(20px, 3vw, 30px) !important;
				padding: 0 !important;
				visibility: visible !important;
				opacity: 1 !important;
			}

			body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag) .emo-shop-lead p {
				max-width: 760px !important;
				margin: 0 !important;
				color: #596860 !important;
				font-size: clamp(14px, 1.35vw, 16px) !important;
				line-height: 1.7 !important;
			}

			body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag) .emo-shop-lead + .woostify-sorting,
			body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag) .emo-shop-lead ~ .woostify-sorting {
				margin-top: 0 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
