<?php
/**
 * Continuidad visual final de portada, cabecera y total del carrito 0.10.115.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		<style id="elmercado-visual-continuity-final-010115">
			/* Portada: las secciones centrales reutilizan directamente la paleta base del sitio. */
			body.home.elmercado-child-theme .emo-home {
				--emo-home-categories-bg: #f7f3ea;
				--emo-home-products-bg: #e4eee8;
				--emo-home-story-bg: #eee7da;
				--emo-home-muted: #4f5d56;
			}
			body.home.elmercado-child-theme .emo-categories {
				background: var(--emo-home-categories-bg) !important;
			}
			body.home.elmercado-child-theme .emo-featured-products {
				background: var(--emo-home-products-bg) !important;
			}
			body.home.elmercado-child-theme .emo-story {
				background: var(--emo-home-story-bg) !important;
			}
			body.home.elmercado-child-theme .emo-categories > .emo-shell,
			body.home.elmercado-child-theme .emo-featured-products > .emo-shell,
			body.home.elmercado-child-theme .emo-featured-products .woocommerce,
			body.home.elmercado-child-theme .emo-featured-products ul.products,
			body.home.elmercado-child-theme .emo-story > .emo-shell {
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

			/* El texto secundario mantiene contraste AA sobre las tres superficies claras. */
			body.home.elmercado-child-theme .emo-categories .emo-section-heading > p,
			body.home.elmercado-child-theme .emo-featured-products .emo-section-heading p,
			body.home.elmercado-child-theme .emo-story__values p {
				color: var(--emo-home-muted) !important;
			}

			/* Patrón editorial común: kicker, título y descripción con el mismo ritmo vertical. */
			body.elmercado-child-theme :is(.emo-kicker,.emo-eyebrow) + :is(h1,h2,h3) {
				margin-bottom: 0 !important;
			}
			body.elmercado-child-theme :is(.emo-kicker,.emo-eyebrow) + :is(h1,h2,h3) + p {
				margin-top: clamp(1rem, 1.4vw, 1.25rem) !important;
			}

			/* Cabecera: neutraliza el margen heredado que provocaba el salto al comenzar a hacer scroll. */
			body.elmercado-child-theme .site-header,
			body.elmercado-child-theme .site-header.is-scrolled,
			body.elmercado-child-theme.is-scrolled .site-header {
				background: rgba(255,255,255,.98) !important;
				box-shadow: 0 1px 0 rgba(13,33,27,.06) !important;
				transition: none !important;
			}
			body.elmercado-child-theme #content.site-content {
				margin-top: 0 !important;
			}
			body.elmercado-child-theme .site-header-inner.fija,
			body.elmercado-child-theme .topbar.fija {
				position: static !important;
				top: auto !important;
			}
			body.elmercado-child-theme .site-header .bumper,
			body.elmercado-child-theme .site-header + .bumper,
			body.elmercado-child-theme .site-header-inner + .bumper,
			body.elmercado-child-theme .site-header-inner ~ .bumper {
				display: none !important;
				height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			/* Carrito: primero el total y, después, el detalle de IVA entre paréntesis. */
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
				flex-direction: row !important;
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
				color: rgba(255,255,255,.8) !important;
				font-size: .74rem !important;
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
					font-size: .75rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
