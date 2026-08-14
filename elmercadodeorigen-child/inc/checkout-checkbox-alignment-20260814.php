<?php
/**
 * Alinea los checks del bloque de datos del checkout sin JavaScript ni saltos visuales.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'wp_footer',
    static function (): void {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
            return;
        }
        ?>
        <style id="elmercado-checkout-checkbox-alignment-style">
            /*
             * Las tres opciones usan wrappers distintos en WooCommerce. Se fuerza
             * la misma referencia izquierda y se elimina cualquier sangría heredada.
             */
            body.woocommerce-checkout #customer_details .woocommerce-account-fields,
            body.woocommerce-checkout #customer_details .woocommerce-shipping-fields {
                margin-left: 0 !important;
                padding-left: 0 !important;
                text-indent: 0 !important;
            }

            body.woocommerce-checkout #customer_details p.form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details p.create-account,
            body.woocommerce-checkout #customer_details #ship-to-different-address {
                box-sizing: border-box !important;
                width: 100% !important;
                min-height: 0 !important;
                height: auto !important;
                margin: 0.22rem 0 !important;
                padding: 0 !important;
                text-indent: 0 !important;
                transform: none !important;
            }

            body.woocommerce-checkout #customer_details p.form-row:has(input[type="checkbox"]) > label,
            body.woocommerce-checkout #customer_details .form-row:has(input[type="checkbox"]) > label,
            body.woocommerce-checkout #customer_details p.create-account > label,
            body.woocommerce-checkout #customer_details #ship-to-different-address > label,
            body.woocommerce-checkout #customer_details label:has(> input[type="checkbox"]) {
                box-sizing: border-box !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 0.5rem !important;
                margin: 0 !important;
                padding: 0 !important;
                text-indent: 0 !important;
                transform: none !important;
                line-height: 1.3 !important;
            }

            body.woocommerce-checkout #customer_details input[type="checkbox"] {
                position: static !important;
                flex: 0 0 auto !important;
                margin: 0 !important;
                padding: 0 !important;
                transform: none !important;
                vertical-align: middle !important;
            }

            /* Neutraliza sangrías del contenedor inmediato de cada check. */
            body.woocommerce-checkout #customer_details p.form-row:has(> label > input[type="checkbox"]),
            body.woocommerce-checkout #customer_details p.create-account:has(> label > input[type="checkbox"]),
            body.woocommerce-checkout #customer_details h3#ship-to-different-address:has(> label > input[type="checkbox"]) {
                margin-left: 0 !important;
                padding-left: 0 !important;
            }
        </style>
        <?php
    },
    PHP_INT_MAX
);
