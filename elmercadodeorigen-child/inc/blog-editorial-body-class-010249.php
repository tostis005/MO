<?php
/**
 * Garantiza el scope editorial en todas las vistas reales del blog.
 *
 * Algunas rutas del índice no heredaban la clase histórica
 * `elmercado-editorial-content`, por lo que la capa visual 0.10.248 se
 * imprimía pero sus reglas de ancho no llegaban a aplicarse.
 *
 * Incluye también el cierre responsive solicitado para los avisos del carrito
 * y la paridad de anchura entre titular y cuerpo de los artículos en escritorio.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_home() || is_archive() || is_singular( 'post' ) ) {
			$classes[] = 'elmercado-editorial-content';
			$classes   = array_values( array_unique( $classes ) );
		}

		return $classes;
	},
	PHP_INT_MAX
);

/**
 * Cierre geométrico para carrito y lectura editorial.
 *
 * Se usa una especificidad mayor que las capas históricas para no depender del
 * orden de carga y se mantienen intactas las reglas de móvil del blog.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		$is_cart = function_exists( 'is_cart' ) && is_cart();
		$is_post = function_exists( 'is_singular' ) && is_singular( 'post' );

		if ( ! $is_cart && ! $is_post ) {
			return;
		}
		?>
		<style id="elmercado-cart-blog-responsive-polish-010252">
			<?php if ( $is_cart ) : ?>
			/*
			 * Los avisos superiores pueden aparecer sólo para determinados
			 * productores. Con width:100% + padding en content-box desbordaban el
			 * viewport, algo especialmente visible alrededor de 767/768px.
			 */
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-notices-wrapper,
			html body.elmercado-child-theme.woocommerce-cart .woocommerce-notices-wrapper > :is(.woocommerce-message,.woocommerce-info,.woocommerce-error),
			html body.elmercado-child-theme.woocommerce-cart > :is(.woocommerce-message,.woocommerce-info,.woocommerce-error) {
				box-sizing: border-box !important;
				min-width: 0 !important;
				max-width: 100% !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .woocommerce-notices-wrapper > :is(.woocommerce-message,.woocommerce-info,.woocommerce-error),
			html body.elmercado-child-theme.woocommerce-cart .woocommerce > :is(.woocommerce-message,.woocommerce-info,.woocommerce-error) {
				width: min(100%, 1180px) !important;
				overflow-wrap: anywhere;
				word-break: normal;
			}

			html body.elmercado-child-theme.woocommerce-cart :is(.emo-cart-intro,.emo-cart-layout,.woocommerce-cart-form,.cart-collaterals,.cart_totals) {
				box-sizing: border-box !important;
				min-width: 0 !important;
				max-width: 100% !important;
			}

			html body.elmercado-child-theme.woocommerce-cart table.shop_table_responsive tr.woocommerce-cart-form__cart-item,
			html body.elmercado-child-theme.woocommerce-cart table.shop_table_responsive tr.cart_item,
			html body.elmercado-child-theme.woocommerce-cart table.shop_table_responsive td,
			html body.elmercado-child-theme.woocommerce-cart .product-name {
				box-sizing: border-box !important;
				min-width: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-cart .product-name,
			html body.elmercado-child-theme.woocommerce-cart .product-name :is(a,.variation,.wcfmmp_sold_by_container) {
				overflow-wrap: anywhere;
				word-break: normal;
			}

			/*
			 * Tablet: a partir de 768px Woostify recupera reglas de escritorio
			 * para formulario/totales. Forzamos una única columna hasta 991px para
			 * que el primer track no quede reducido a unas decenas de píxeles.
			 */
			@media (min-width: 768px) and (max-width: 991px) {
				html body.elmercado-child-theme.woocommerce-cart .woocommerce-notices-wrapper {
					width: min(100%, 1180px) !important;
					margin-right: auto !important;
					margin-left: auto !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout {
					display: grid !important;
					width: min(100%, 1180px) !important;
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 1.25rem !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .woocommerce-cart-form,
				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals {
					display: block !important;
					grid-column: 1 !important;
					box-sizing: border-box !important;
					width: 100% !important;
					min-width: 0 !important;
					max-width: 100% !important;
					margin-right: 0 !important;
					margin-left: 0 !important;
					float: none !important;
				}

				html body.elmercado-child-theme.woocommerce-cart .emo-cart-layout > .cart-collaterals .cart_totals {
					display: block !important;
					box-sizing: border-box !important;
					width: 100% !important;
					min-width: 0 !important;
					max-width: 100% !important;
					margin: 0 !important;
					float: none !important;
				}

				html body.elmercado-child-theme.woocommerce-cart :is(.woocommerce-message,.woocommerce-info,.woocommerce-error) .button {
					display: flex !important;
					float: none !important;
					box-sizing: border-box !important;
					width: 100% !important;
					max-width: 100% !important;
					min-height: 40px !important;
					align-items: center !important;
					justify-content: center !important;
					margin: .8rem 0 0 !important;
				}
			}

			@media (max-width: 767px) {
				html body.elmercado-child-theme.woocommerce-cart table.shop_table_responsive tr.woocommerce-cart-form__cart-item,
				html body.elmercado-child-theme.woocommerce-cart table.shop_table_responsive tr.cart_item {
					width: 100% !important;
					max-width: 100% !important;
				}

				html body.elmercado-child-theme.woocommerce-cart :is(.woocommerce-message,.woocommerce-info,.woocommerce-error) {
					padding-right: 1rem !important;
					padding-left: 1.15rem !important;
				}
			}
			<?php endif; ?>

			<?php if ( $is_post ) : ?>
			/* En PC, el cuerpo recupera la misma anchura máxima (900px) que el H1. */
			@media (min-width: 821px) {
				html body.single-post.elmercado-child-theme.elmercado-editorial-content main#primary.emo-article-page .emo-article-main .emo-article-content {
					width: min(100%, 900px) !important;
					max-width: 900px !important;
				}
			}
			<?php endif; ?>
		</style>
		<?php
	},
	PHP_INT_MAX
);
