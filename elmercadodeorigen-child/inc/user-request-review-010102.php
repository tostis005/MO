<?php
/**
 * Revisión visual solicitada: carrito, fichas de producto y continuidad del catálogo 0.10.103.
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
		<style id="elmercado-user-request-review-010103">
			/*
			 * Nombres de producto: dos líneas completas tanto en portada como en tienda.
			 * Si el nombre necesita más espacio, line-clamp muestra la elipsis al final.
			 */
			body.elmercado-child-theme ul.products li.product :is(
				.woocommerce-loop-product__title,
				.product-title,
				h2,
				h3
			) {
				display: -webkit-box !important;
				box-sizing: border-box !important;
				height: 2.7em !important;
				min-height: 2.7em !important;
				max-height: 2.7em !important;
				margin-bottom: 8px !important;
				overflow: hidden !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				line-clamp: 2 !important;
				line-height: 1.35 !important;
				text-overflow: ellipsis !important;
				text-wrap: wrap !important;
				white-space: normal !important;
				overflow-wrap: break-word !important;
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
