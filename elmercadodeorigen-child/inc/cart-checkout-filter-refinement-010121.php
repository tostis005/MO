<?php
/**
 * Carrito, checkout y filtros: ajuste de anchura y densidad 0.10.122.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'widget_title',
	static function ( $title ): string {
		$title = (string) $title;
		if ( is_admin() ) {
			return $title;
		}

		$is_catalog = ( function_exists( 'is_shop' ) && is_shop() )
			|| ( function_exists( 'is_product_category' ) && is_product_category() )
			|| ( function_exists( 'is_product_tag' ) && is_product_tag() );
		if ( ! $is_catalog ) {
			return $title;
		}

		$plain_title = trim( wp_strip_all_tags( $title ) );
		if ( 'Categorías del producto' === $plain_title ) {
			return 'Categorías';
		}
		if ( 'Etiquetas del producto' === $plain_title ) {
			return 'Etiquetas';
		}

		return $title;
	},
	40,
	1
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-cart-checkout-filter-refinement-010121">
			/* Carrito: la fila de envío usa el ancho real de la tarjeta de totales. */
			body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping {
				display: block !important;
				width: 100% !important;
				border-bottom: 1px solid rgba(255,255,255,.12) !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > th,
			body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td {
				display: block !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				border: 0 !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > th {
				padding: .85rem 0 .3rem !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td {
				padding: .2rem 0 .85rem !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals table.shop_table tr.woocommerce-shipping-totals.shipping > td::before {
				display: none !important;
				content: none !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals :is(
				.woocommerce-shipping-destination,
				.woocommerce-shipping-calculator,
				.shipping-calculator-form
			) {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-button {
				display: inline-flex !important;
				align-items: center !important;
				margin-top: .25rem !important;
				color: #fffdf8 !important;
				font-weight: 800 !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form {
				margin-top: .7rem !important;
				padding: .8rem !important;
				border: 1px solid rgba(255,255,255,.12) !important;
				border-radius: 12px !important;
				background: rgba(255,255,255,.05) !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .form-row {
				display: block !important;
				float: none !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				margin: 0 0 .6rem !important;
				padding: 0 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(input.input-text,select),
			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-container {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
				min-height: 44px !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form :is(input.input-text,select),
			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-selection {
				border: 1px solid rgba(255,255,255,.18) !important;
				border-radius: 10px !important;
				background: rgba(255,255,255,.08) !important;
				color: #fffdf8 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form input.input-text {
				padding: 10px 12px !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-selection {
				display: flex !important;
				min-height: 44px !important;
				align-items: center !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .select2-selection__rendered {
				width: 100% !important;
				padding: 0 38px 0 12px !important;
				color: #fffdf8 !important;
				line-height: 42px !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals .shipping-calculator-form .button {
				display: flex !important;
				width: 100% !important;
				min-height: 44px !important;
				align-items: center !important;
				justify-content: center !important;
				margin: .1rem 0 0 !important;
				padding: .7rem 1rem !important;
				border-radius: 999px !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals ul#shipping_method {
				display: grid !important;
				width: 100% !important;
				max-width: none !important;
				gap: .45rem !important;
				margin: .25rem 0 .65rem !important;
				padding: 0 !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals ul#shipping_method > li {
				display: grid !important;
				box-sizing: border-box !important;
				width: 100% !important;
				grid-template-columns: 18px minmax(0,1fr) !important;
				align-items: start !important;
				gap: .55rem !important;
				margin: 0 !important;
				padding: .55rem .65rem !important;
				border-radius: 10px !important;
				background: rgba(255,255,255,.055) !important;
				text-align: left !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals ul#shipping_method > li > input[type="radio"] {
				margin: .2rem 0 0 !important;
			}

			body.elmercado-child-theme.woocommerce-cart .cart_totals ul#shipping_method > li > label {
				width: 100% !important;
				margin: 0 !important;
				color: #fffdf8 !important;
				font-weight: 650 !important;
				line-height: 1.4 !important;
				text-align: left !important;
			}

			/* Checkout: las opciones de pago conservan su geometría durante AJAX. */
			html body.elmercado-child-theme.woocommerce-checkout :is(
				#order_review,
				#payment,
				.wc_payment_methods,
				.wc_payment_method,
				.payment_box
			) {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: none !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .wc_payment_methods {
				margin: 0 !important;
				padding: 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .wc_payment_method {
				margin: 0 !important;
				padding: 12px 0 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .wc_payment_method > label {
				display: inline !important;
				margin: 0 !important;
				line-height: 1.45 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box {
				margin: 10px 0 0 !important;
				padding: 12px 14px !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout .emo-checkout-summary-column > .emo-checkout-status-card {
				display: none !important;
			}

			@media (min-width: 1101px) {
				/* Filtros: mismo rail, menos altura desperdiciada. */
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					padding: 14px !important;
					border-radius: 16px !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .widget {
					margin-bottom: 14px !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) :is(.widget-title,.widgettitle,.sidebar-heading,.widget-heading,.wp-block-heading) {
					min-height: 38px !important;
					margin-bottom: 10px !important;
					padding: 7px 10px !important;
					font-size: 11.5px !important;
					white-space: nowrap !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .widget_product_categories li > a {
					min-height: 34px !important;
					margin: 1px 0 !important;
					padding: 6px 8px !important;
					font-size: 12.5px !important;
					line-height: 1.25 !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .tagcloud {
					gap: 6px !important;
				}

				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .tagcloud a {
					min-height: 30px !important;
					padding: 5px 9px !important;
					font-size: 10.8px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
