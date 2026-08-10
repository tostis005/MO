<?php
/**
 * Cierre de cascada transaccional 0.10.127.
 *
 * Recupera únicamente la densidad compacta del bloque de garantías del carrito
 * en la última posición de la cascada. No interviene en estados AJAX ni en el
 * resumen o los métodos de pago del checkout.
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
		<style id="elmercado-transaction-cascade-final-010127">
			html body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance {
				display: flex !important;
				flex-direction: column !important;
				flex-wrap: nowrap !important;
				align-items: stretch !important;
				align-content: flex-start !important;
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

			html body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-assurance > span {
				position: static !important;
				display: flex !important;
				flex: 0 0 auto !important;
				align-self: auto !important;
				align-items: flex-start !important;
				justify-content: flex-start !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
				line-height: 1.35 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<script id="elmercado-cart-assurance-cleanup-010127">
		(() => {
			'use strict';

			const cleanAssurance = () => {
				const box = document.querySelector('.cart_totals .emo-cart-assurance');
				if (!box) return;
				[...box.childNodes].forEach((node) => {
					const keep = node.nodeType === Node.ELEMENT_NODE && node.nodeName === 'SPAN';
					if (!keep) node.remove();
				});
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', cleanAssurance, { once: true });
			} else {
				cleanAssurance();
			}

			if (window.jQuery) {
				window.jQuery(document.body).on('updated_wc_div updated_cart_totals', cleanAssurance);
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
