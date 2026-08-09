<?php
/**
 * Continuidad visual final de portada, cabecera y total del carrito 0.10.107.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mantiene la información fiscal del total en una sola línea sin desplazar el
 * importe principal de su margen derecho. El orden semántico sigue siendo
 * importe + detalle fiscal; CSS invierte únicamente su presentación visual.
 */
add_filter(
	'woocommerce_cart_totals_order_total_html',
	static function ( string $html ): string {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() || false === stripos( $html, 'includes_tax' ) ) {
			return $html;
		}

		$pattern = '~(<strong\b[^>]*>.*?</strong>)\s*(<small\b[^>]*class=["\'][^"\']*\bincludes_tax\b[^"\']*["\'][^>]*>.*?</small>)~is';
		$wrapped = preg_replace( $pattern, '<span class="emo-cart-total-inline">$1$2</span>', $html, 1 );

		return is_string( $wrapped ) ? $wrapped : $html;
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-visual-continuity-final-010107">
			/*
			 * Portada: una sola superficie blanca en toda la sección. La banda que
			 * aparecía bajo el carrusel era la suma de sombras/superficies internas,
			 * no un cambio real de sección.
			 */
			body.home.elmercado-child-theme .emo-featured-products {
				background: #fff !important;
			}
			body.home.elmercado-child-theme .emo-featured-products :is(.emo-shell,.woocommerce,ul.products) {
				background: transparent !important;
				background-image: none !important;
				box-shadow: none !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products::before,
			body.home.elmercado-child-theme .emo-featured-products ul.products::after {
				display: none !important;
				content: none !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products > li.product,
			body.home.elmercado-child-theme .emo-featured-products ul.products > li.product:hover,
			body.home.elmercado-child-theme .emo-featured-products ul.products > li.product:focus-within {
				box-shadow: none !important;
			}

			/*
			 * Cabecera: el sticky nativo mantiene exactamente la misma geometría y
			 * apariencia antes y después de los primeros píxeles de scroll. También
			 * se neutralizan las dos huellas del antiguo controlador fijo, si algún
			 * recurso externo llegara a reinsertarlas.
			 */
			body.elmercado-child-theme .site-header,
			body.elmercado-child-theme .site-header.is-scrolled,
			body.elmercado-child-theme.is-scrolled .site-header {
				background: rgba(255,255,255,.98) !important;
				box-shadow: 0 1px 0 rgba(13,33,27,.06) !important;
				transition: none !important;
			}
			body.elmercado-child-theme .site-header-inner.fija,
			body.elmercado-child-theme .topbar.fija {
				position: static !important;
				top: auto !important;
			}
			body.elmercado-child-theme .site-header-inner + .bumper {
				display: none !important;
				height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			/*
			 * Carrito: el IVA comparte línea con el total y el importe fuerte sigue
			 * terminando exactamente en el margen derecho de la tabla.
			 */
			body.elmercado-child-theme.woocommerce-cart .cart_totals tr.order-total th {
				width: 32% !important;
				padding-right: 8px !important;
				vertical-align: baseline !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals tr.order-total td {
				width: 68% !important;
				vertical-align: baseline !important;
				text-align: right !important;
				white-space: nowrap !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline {
				display: inline-flex !important;
				max-width: 100% !important;
				align-items: baseline !important;
				justify-content: flex-end !important;
				gap: 6px !important;
				flex-direction: row-reverse !important;
				white-space: nowrap !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > strong,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > .includes_tax,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > small.includes_tax {
				display: inline !important;
				width: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				text-align: right !important;
				line-height: 1.15 !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > .includes_tax,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > small.includes_tax {
				color: rgba(255,255,255,.72) !important;
				font-size: .66rem !important;
				font-weight: 650 !important;
			}

			@media (max-width: 420px) {
				body.elmercado-child-theme.woocommerce-cart .cart_totals tr.order-total th {
					width: 28% !important;
				}
				body.elmercado-child-theme.woocommerce-cart .cart_totals tr.order-total td {
					width: 72% !important;
				}
				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline {
					gap: 4px !important;
				}
				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > .includes_tax,
				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-total-inline > small.includes_tax {
					font-size: .62rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
