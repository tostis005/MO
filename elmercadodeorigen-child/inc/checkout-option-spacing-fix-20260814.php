<?php
/**
 * Compacta el espaciado de checks y opciones del checkout.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'wp_head',
    static function (): void {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
            return;
        }
        ?>
        <style id="elmercado-checkout-option-spacing-fix">
            /* Checks de factura, creación de cuenta y dirección alternativa. */
            body.woocommerce-checkout #customer_details p.form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .woocommerce-account-fields .create-account,
            body.woocommerce-checkout #customer_details #ship-to-different-address {
                margin-top: .35rem !important;
                margin-bottom: .65rem !important;
            }

            body.woocommerce-checkout #customer_details .woocommerce-account-fields,
            body.woocommerce-checkout #customer_details .woocommerce-shipping-fields,
            body.woocommerce-checkout #customer_details .woocommerce-additional-fields {
                margin-top: .45rem !important;
                margin-bottom: .45rem !important;
            }

            body.woocommerce-checkout #customer_details label.checkbox,
            body.woocommerce-checkout #customer_details #ship-to-different-address label {
                margin-bottom: 0 !important;
            }

            /* Opciones de pago y condiciones, sin huecos verticales excesivos. */
            body.woocommerce-checkout #payment ul.payment_methods {
                margin: 0 !important;
                padding: .45rem 0 !important;
            }

            body.woocommerce-checkout #payment ul.payment_methods li.wc_payment_method {
                margin: 0 !important;
                padding: .32rem 0 !important;
            }

            body.woocommerce-checkout #payment .payment_box {
                margin: .3rem 0 .45rem !important;
                padding: .65rem .85rem !important;
            }

            body.woocommerce-checkout #payment .payment_box p {
                margin: 0 !important;
            }

            body.woocommerce-checkout #payment .woocommerce-terms-and-conditions-wrapper,
            body.woocommerce-checkout #payment .form-row.place-order {
                margin-top: .45rem !important;
                margin-bottom: .45rem !important;
            }
        </style>
        <?php
    },
    PHP_INT_MAX
);
