<?php
/**
 * Limpieza de estados de checkout y destino único de envío, refinado en 0.10.178.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* QA temporal: fuerza un carrito multivendedor con destino en la sesión headless de staging. */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() || empty( $_GET['qa'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		if ( false === stripos( $user_agent, 'HeadlessChrome' ) || ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->customer ) {
			return;
		}

		$authors = array();
		$ids     = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$product_id = (int) ( $item['product_id'] ?? 0 );
			if ( $product_id <= 0 ) {
				continue;
			}
			$ids[] = $product_id;
			$author = (int) get_post_field( 'post_author', $product_id );
			if ( $author > 0 ) {
				$authors[] = $author;
			}
		}
		$authors = array_values( array_unique( $authors ) );

		if ( function_exists( 'wc_get_products' ) && count( $authors ) < 3 ) {
			foreach ( wc_get_products( array( 'limit' => 100, 'status' => 'publish', 'type' => 'simple', 'stock_status' => 'instock', 'orderby' => 'date', 'order' => 'DESC' ) ) as $product ) {
				if ( count( $authors ) >= 3 ) {
					break;
				}
				if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
					continue;
				}
				$product_id = (int) $product->get_id();
				$author     = (int) get_post_field( 'post_author', $product_id );
				$price      = (float) $product->get_price();
				if ( $product_id <= 0 || $author <= 0 || $price <= 0 || $price >= 60 || in_array( $product_id, $ids, true ) || in_array( $author, $authors, true ) ) {
					continue;
				}
				if ( WC()->cart->add_to_cart( $product_id, 1 ) ) {
					$ids[]     = $product_id;
					$authors[] = $author;
				}
			}
		}

		WC()->customer->set_shipping_country( 'ES' );
		WC()->customer->set_shipping_state( 'M' );
		WC()->customer->set_shipping_postcode( '28001' );
		WC()->customer->set_shipping_city( 'Madrid' );
		WC()->customer->set_shipping_address_1( 'Calle de Alcalá 1' );
		if ( method_exists( WC()->customer, 'set_calculated_shipping' ) ) {
			WC()->customer->set_calculated_shipping( true );
		}
		WC()->customer->save();
		WC()->cart->calculate_totals();
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
		<style id="elmercado-cart-checkout-state-cleanup-010178">
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

			/* El destino pertenece al pedido: se muestra una sola vez, no una por paquete/productor. */
			body.elmercado-child-theme.woocommerce-cart.emo-cart-shipping-destination-unified .cart_totals tr.woocommerce-shipping-totals.shipping :is(.woocommerce-shipping-destination,.woocommerce-shipping-calculator) {
				display: none !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary {
				display: grid !important;
				grid-template-columns: minmax(0,1fr) auto !important;
				gap: 4px 14px !important;
				box-sizing: border-box !important;
				width: 100% !important;
				margin: 0 0 14px !important;
				padding: 11px 0 14px !important;
				border-bottom: 1px solid rgba(255,255,255,.12) !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__eyebrow,
			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__address {
				grid-column: 1 !important;
				margin: 0 !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__eyebrow {
				color: rgba(255,253,248,.68) !important;
				font-size: 10px !important;
				font-weight: 850 !important;
				letter-spacing: .055em !important;
				line-height: 1.35 !important;
				text-transform: uppercase !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__address {
				color: #fffdf8 !important;
				font-size: 13px !important;
				font-weight: 750 !important;
				line-height: 1.45 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator {
				grid-column: 2 !important;
				grid-row: 1 / span 2 !important;
				align-self: center !important;
				min-width: 0 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator .woocommerce-shipping-calculator {
				display: block !important;
				width: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				text-align: right !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator .shipping-calculator-button {
				display: inline-flex !important;
				width: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #f1d59c !important;
				font-size: 13px !important;
				font-weight: 850 !important;
				line-height: 1.35 !important;
				text-align: right !important;
				text-decoration: underline !important;
				text-underline-offset: 3px !important;
				white-space: nowrap !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator .shipping-calculator-form {
				box-sizing: border-box !important;
				width: min(460px, calc(100vw - 48px)) !important;
				max-width: 100% !important;
				margin: 12px 0 0 auto !important;
				padding: 13px !important;
				border-radius: 12px !important;
				background: rgba(255,255,255,.045) !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator .shipping-calculator-form :is(.form-row,input.input-text,select,.select2-container,.select2-selection,.button) {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary {
					grid-template-columns: minmax(0,1fr) !important;
					gap: 5px !important;
				}
				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator {
					grid-column: 1 !important;
					grid-row: auto !important;
					justify-self: start !important;
					margin-top: 3px !important;
				}
				body.elmercado-child-theme.woocommerce-cart .cart_totals .emo-cart-shipping-summary__calculator .shipping-calculator-form {
					width: 100% !important;
					margin-left: 0 !important;
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
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
			?>
			<script id="elmercado-cart-checkout-state-cleanup-js-010178">
			(() => {
				'use strict';
				const clean = () => document.querySelectorAll('.emo-checkout-status-card').forEach((card) => card.remove());
				document.addEventListener('DOMContentLoaded', () => {
					clean();
					if (window.jQuery) jQuery(document.body).on('updated_checkout checkout_error', clean);
				});
			})();
			</script>
			<?php
		}
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}
		?>
		<script id="elmercado-cart-shipping-destination-010178">
		(() => {
			'use strict';
			const body = document.body;
			const normalizeDestination = (node) => {
				if (!node) return '';
				const strong = node.querySelector('strong');
				if (strong?.textContent.trim()) return strong.textContent.replace(/\s+/g, ' ').trim();
				return (node.textContent || '').replace(/\s+/g, ' ').replace(/^(?:enviando|enviar|envía|enviará)\s+a\s*/i, '').trim().replace(/\.$/, '');
			};

			const sync = () => {
				const totals = document.querySelector('.cart_totals');
				if (!totals) return;
				const destinations = [...totals.querySelectorAll('tr.woocommerce-shipping-totals.shipping .woocommerce-shipping-destination')];
				const source = destinations.find((node) => normalizeDestination(node));
				let summary = totals.querySelector('.emo-cart-shipping-summary');
				if (!source) {
					summary?.remove();
					body.classList.remove('emo-cart-shipping-destination-unified');
					return;
				}

				body.classList.add('emo-cart-shipping-destination-unified');
				if (!summary) {
					summary = document.createElement('div');
					summary.className = 'emo-cart-shipping-summary';
					summary.innerHTML = '<p class="emo-cart-shipping-summary__eyebrow">Enviará a</p><p class="emo-cart-shipping-summary__address"></p><div class="emo-cart-shipping-summary__calculator"></div>';
					const table = totals.querySelector('table.shop_table');
					if (table) table.insertAdjacentElement('beforebegin', summary);
					else totals.prepend(summary);
				}

				const address = summary.querySelector('.emo-cart-shipping-summary__address');
				if (address) address.textContent = normalizeDestination(source);
				const slot = summary.querySelector('.emo-cart-shipping-summary__calculator');
				const calculator = totals.querySelector('tr.woocommerce-shipping-totals.shipping .woocommerce-shipping-calculator');
				if (slot && calculator) {
					slot.replaceChildren(calculator);
					const button = calculator.querySelector('.shipping-calculator-button');
					if (button) button.textContent = 'Cambiar dirección';
				}
			};

			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', sync, { once: true });
			else sync();
			if (window.jQuery) jQuery(document.body).on('updated_wc_div updated_cart_totals', sync);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
