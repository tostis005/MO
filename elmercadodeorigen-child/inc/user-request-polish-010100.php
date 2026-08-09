<?php
/**
 * Pulido solicitado para portada, carrito y feedback de filtros 0.10.101.
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
		<style id="elmercado-user-request-polish-010101">
			/* Portada: la fotografía es la única superficie de la ficha; texto y acciones respiran sobre el fondo de sección. */
			body.home.elmercado-child-theme .emo-featured-products {
				background: #eaf1eb !important;
			}
			body.home.elmercado-child-theme .emo-story {
				background: #f3eadf !important;
			}
			body.home.elmercado-child-theme .emo-featured-products .woocommerce,
			body.home.elmercado-child-theme .emo-featured-products ul.products,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product:hover,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product > a,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .woocommerce-loop-product__link {
				background: transparent !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product:hover {
				border-color: transparent !important;
				box-shadow: none !important;
				overflow: visible !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper {
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				background-image: none !important;
				border: 0 !important;
				border-radius: 18px !important;
				overflow: hidden !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper::after {
				display: none !important;
				content: none !important;
				background: none !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product :is(.product-loop-content,.product-content) {
				margin-top: 0 !important;
				padding: 12px 2px 0 !important;
				background: transparent !important;
				background-image: none !important;
				border: 0 !important;
				box-shadow: none !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .product-loop-image-wrapper img,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product img.product-loop-image,
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .woocommerce-loop-product__link img {
				border-radius: 18px !important;
				box-shadow: 0 9px 26px rgba(13,33,27,.08);
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product :is(.woocommerce-loop-product__title,.product-title,h2,h3) {
				padding-inline: 2px !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .star-rating {
				margin-inline: 2px !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .price {
				padding-inline: 2px !important;
			}
			body.home.elmercado-child-theme .emo-featured-products ul.products li.product .button {
				width: 100% !important;
				margin-inline: 0 !important;
			}

			/* Carrito: el importe total queda en la misma vertical que el subtotal; el IVA pasa a una segunda línea legible. */
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total th,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td {
				vertical-align: middle !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td {
				text-align: right !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td > strong,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td > .includes_tax,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td > small.includes_tax {
				display: block !important;
				width: 100% !important;
				margin: 0 !important;
				text-align: right !important;
			}
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td > .includes_tax,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .order-total td > small.includes_tax {
				margin-top: 3px !important;
				color: rgba(255,255,255,.74) !important;
				font-size: .76rem !important;
				font-weight: 650 !important;
				line-height: 1.25 !important;
			}

			/* Feedback inmediato al aplicar un filtro mediante navegación o formulario. */
			.emo-catalog-filter-progress {
				position: fixed;
				inset: 0;
				z-index: 10080;
				display: grid;
				place-items: center;
				padding: 24px;
				background: rgba(247,243,234,.76);
				backdrop-filter: blur(3px);
			}
			.emo-catalog-filter-progress[hidden] {
				display: none !important;
			}
			.emo-catalog-filter-progress__status {
				display: inline-flex;
				min-height: 54px;
				align-items: center;
				gap: 12px;
				padding: 13px 18px;
				background: #173f32;
				border: 1px solid rgba(255,255,255,.12);
				border-radius: 999px;
				box-shadow: 0 18px 48px rgba(13,33,27,.18);
				color: #fffdf8;
				font-size: 13px;
				font-weight: 800;
				letter-spacing: .01em;
			}
			.emo-catalog-filter-progress__dots {
				display: inline-flex;
				align-items: center;
				gap: 4px;
			}
			.emo-catalog-filter-progress__dots i {
				display: block;
				width: 6px;
				height: 6px;
				background: #d7a84f;
				border-radius: 50%;
				animation: emo-filter-pulse 720ms ease-in-out infinite alternate;
			}
			.emo-catalog-filter-progress__dots i:nth-child(2) { animation-delay: 120ms; }
			.emo-catalog-filter-progress__dots i:nth-child(3) { animation-delay: 240ms; }
			@keyframes emo-filter-pulse {
				from { opacity: .28; }
				to { opacity: 1; }
			}
			@media (prefers-reduced-motion: reduce) {
				.emo-catalog-filter-progress__dots i { animation: none; opacity: 1; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}
		?>
		<div class="emo-catalog-filter-progress" id="emo-catalog-filter-progress" role="status" aria-live="polite" hidden>
			<div class="emo-catalog-filter-progress__status">
				<span class="emo-catalog-filter-progress__dots" aria-hidden="true"><i></i><i></i><i></i></span>
				<span><?php esc_html_e( 'Actualizando productos…', 'elmercadodeorigen' ); ?></span>
			</div>
		</div>
		<script id="elmercado-filter-progress-controller-010101">
		(() => {
			'use strict';
			const overlay = document.querySelector('#emo-catalog-filter-progress');
			if (!overlay) return;

			let fallback = 0;
			const hide = () => {
				window.clearTimeout(fallback);
				overlay.hidden = true;
				document.body.classList.remove('emo-catalog-filter-pending');
				document.body.removeAttribute('aria-busy');
			};
			const show = () => {
				window.clearTimeout(fallback);
				overlay.hidden = false;
				document.body.classList.add('emo-catalog-filter-pending');
				document.body.setAttribute('aria-busy', 'true');
				fallback = window.setTimeout(hide, 10000);
			};
			const validLink = (event, link) => {
				if (!link || link.target === '_blank' || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
				if (typeof event.button === 'number' && event.button !== 0) return false;
				const href = link.getAttribute('href') || '';
				return href !== '' && href.charAt(0) !== '#' && !/^javascript:/i.test(href);
			};
			const roots = [
				document.querySelector('#secondary.widget-area,.shop-widget-area'),
				document.querySelector('#emo-premium-filter-shell .emo-mobile-filter-content')
			].filter((node, index, all) => node && all.indexOf(node) === index);

			roots.forEach((root) => {
				root.addEventListener('click', (event) => {
					const link = event.target.closest?.('a[href]');
					if (link && root.contains(link) && validLink(event, link)) show();
				});
				root.addEventListener('submit', (event) => {
					if (event.target instanceof HTMLFormElement) show();
				});
			});

			window.addEventListener('pageshow', hide, { passive: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
