<?php
/**
 * Limpieza final de estados heredados de checkout y control del envío 0.10.123.
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
		<style id="elmercado-cart-checkout-state-cleanup-010123">
			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-button {
				display: flex !important;
				float: none !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				align-items: center !important;
				justify-content: flex-start !important;
				text-align: left !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-status-card {
				display: none !important;
				visibility: hidden !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		?>
		<script id="elmercado-cart-checkout-state-cleanup-js-010123">
		(() => {
			'use strict';

			const removeLegacyStatusCard = () => {
				document.querySelectorAll('.emo-checkout-status-card').forEach((card) => card.remove());
			};

			document.addEventListener('DOMContentLoaded', () => {
				removeLegacyStatusCard();
				if (window.jQuery) {
					jQuery(document.body).on('updated_checkout checkout_error', removeLegacyStatusCard);
				}
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
