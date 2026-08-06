<?php
/**
 * Final edge fixes for product media and minicart quantity controls.
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
		<style id="elmercado-storefront-edge-fix">
			body.elmercado-child-theme #shop-cart-sidebar .quantity,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity {
				display: grid !important;
				grid-template-columns: 38px 46px 38px !important;
				width: 122px !important;
				min-width: 122px !important;
				height: 40px !important;
				align-items: center !important;
				justify-items: center !important;
				gap: 0 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .quantity > *::before,
			body.elmercado-child-theme #shop-cart-sidebar .quantity > *::after,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > *::before,
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-quantity > *::after {
				content: none !important;
				display: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty {
				position: static !important;
				display: flex !important;
				width: 38px !important;
				height: 38px !important;
				margin: 0 !important;
				padding: 0 !important;
				align-items: center !important;
				justify-content: center !important;
				font-family: Arial,sans-serif !important;
				font-size: 21px !important;
				font-weight: 700 !important;
				line-height: 38px !important;
				color: #173f32 !important;
				text-indent: 0 !important;
				transform: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty:first-child {
				grid-column: 1 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar input.qty {
				position: static !important;
				grid-column: 2 !important;
				display: block !important;
				width: 46px !important;
				height: 36px !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				border-inline: 1px solid rgba(23,63,50,.12) !important;
				font-family: Arial,sans-serif !important;
				font-size: 16px !important;
				font-weight: 700 !important;
				font-variant-numeric: tabular-nums !important;
				line-height: 36px !important;
				text-align: center !important;
				text-indent: 0 !important;
				background: #fff !important;
				background-image: none !important;
				color: #173f32 !important;
				-webkit-appearance: none !important;
				-moz-appearance: textfield !important;
				appearance: none !important;
				transform: none !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar .mini-cart-product-qty:last-child {
				grid-column: 3 !important;
			}
			body.elmercado-child-theme #shop-cart-sidebar input.qty::-webkit-inner-spin-button,
			body.elmercado-child-theme #shop-cart-sidebar input.qty::-webkit-outer-spin-button {
				-webkit-appearance: none !important;
				display: none !important;
				margin: 0 !important;
			}

			body.elmercado-child-theme ul.products li.product {
				border: 0 !important;
				background: #fff !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-wrapper,
			body.elmercado-child-theme ul.products li.product .woocommerce-LoopProduct-link,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link,
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				border: 0 !important;
				box-shadow: none !important;
				background: transparent !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper {
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper img,
			body.elmercado-child-theme ul.products li.product img.product-loop-image,
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__link img {
				width: calc(100% + 2px) !important;
				height: calc(100% + 2px) !important;
				max-width: none !important;
				margin: -1px !important;
				padding: 0 !important;
				border: 0 !important;
				outline: 0 !important;
				border-radius: 0 !important;
				object-fit: contain !important;
			}
			body.elmercado-child-theme ul.products li.product .product-loop-image-wrapper::after {
				height: 15% !important;
				background: linear-gradient(180deg,rgba(255,255,255,0),#fff 94%) !important;
			}

			@media (min-width: 1280px) {
				body.home .emo-featured-products ul.products,
				body.home .emo-products ul.products {
					display: grid !important;
					grid-template-columns: repeat(6,minmax(0,1fr)) !important;
					gap: 1rem !important;
				}
				body.home .emo-featured-products ul.products li.product,
				body.home .emo-products ul.products li.product {
					width: auto !important;
					margin: 0 !important;
				}
			}
			@media (min-width: 980px) and (max-width: 1279px) {
				body.home .emo-featured-products ul.products,
				body.home .emo-products ul.products {
					grid-template-columns: repeat(3,minmax(0,1fr)) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-normalize-minicart-quantity">
		(() => {
			'use strict';
			let frame = 0;
			const normalize = () => {
				document.querySelectorAll('#shop-cart-sidebar .quantity, #shop-cart-sidebar .mini-cart-quantity').forEach((control) => {
					const input = control.querySelector('input.qty');
					if (!input) return;
					const children = [...control.children];
					const buttons = children.filter((child) => child !== input && !child.contains(input));
					if (buttons.length < 2) return;
					const minus = buttons[0];
					const plus = buttons[buttons.length - 1];
					buttons.slice(1, -1).forEach((extra) => extra.remove());
					minus.textContent = '−';
					plus.textContent = '+';
					minus.setAttribute('aria-label', 'Reducir cantidad');
					plus.setAttribute('aria-label', 'Aumentar cantidad');
					input.type = 'text';
					input.inputMode = 'numeric';
					input.setAttribute('pattern', '[0-9]*');
					input.setAttribute('aria-label', 'Cantidad');
				});
				frame = 0;
			};
			const requestNormalize = () => {
				if (frame) return;
				frame = requestAnimationFrame(normalize);
			};
			normalize();
			new MutationObserver(requestNormalize).observe(document.body, { childList: true, subtree: true });
			document.body.addEventListener('wc_fragments_refreshed', requestNormalize);
			document.body.addEventListener('updated_wc_div', requestNormalize);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
