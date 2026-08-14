<?php
/**
 * Ajustes específicos del checkout:
 * - Mensaje claro cuando un productor no tiene envío para el destino.
 * - Oculta de forma robusta el botón de realizar pedido en ese estado.
 * - Compacta la separación vertical de checks/radios del checkout.
 *
 * Se despliega como MU-plugin para no depender del orden de carga del tema.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sustituye el mensaje genérico de WooCommerce cuando no hay métodos de envío.
 *
 * @param string $message Mensaje original.
 * @return string
 */
function elmercado_checkout_no_shipping_message( string $message ): string {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $message;
    }

    return '<div class="emo-no-shipping-destination" data-emo-no-shipping="1" role="alert">'
        . '<strong style="color:#0d211b !important;">No podemos enviar los productos de este productor al destino indicado.</strong> '
        . 'Actualmente no tiene configurada una opción de envío para esa zona. '
        . 'Si crees que debería estar disponible o se trata de un error, ponte en contacto con nosotros y lo revisaremos.'
        . '</div>';
}

add_filter( 'woocommerce_no_shipping_available_html', 'elmercado_checkout_no_shipping_message', 999 );
add_filter( 'woocommerce_cart_no_shipping_available_html', 'elmercado_checkout_no_shipping_message', 999 );

/**
 * Refuerza el estado sin envío y compacta los controles del checkout.
 * WooCommerce actualiza el resumen por AJAX, por lo que el estado se sincroniza
 * también después de cada actualización del checkout.
 */
add_action(
    'wp_footer',
    static function (): void {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
            return;
        }
        ?>
        <style id="elmercado-checkout-no-shipping-style">
            .emo-no-shipping-destination {
                margin: 0.75rem 0;
                padding: 0.9rem 1rem;
                background: #fff7e8;
                border: 1px solid rgba(178, 122, 33, 0.28);
                border-left: 4px solid #b27a21;
                border-radius: 12px;
                color: #27352f !important;
                font-size: 0.92rem;
                line-height: 1.55;
            }

            body.woocommerce-checkout #order_review .emo-no-shipping-destination strong,
            body.woocommerce-checkout .emo-no-shipping-destination strong {
                color: #0d211b !important;
            }

            body.woocommerce-checkout:has(.emo-no-shipping-destination[data-emo-no-shipping="1"]) #place_order,
            body.emo-checkout-no-shipping #place_order {
                display: none !important;
            }

            /* Columna de datos: compactar únicamente filas que contienen checks. */
            body.woocommerce-checkout #customer_details p.form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .form-row:has(input[type="checkbox"]) {
                margin-top: 0.35rem !important;
                margin-bottom: 0.35rem !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            body.woocommerce-checkout #customer_details .woocommerce-account-fields {
                margin-top: 0.35rem !important;
                margin-bottom: 0.35rem !important;
            }

            body.woocommerce-checkout #customer_details #ship-to-different-address {
                margin-top: 0.55rem !important;
                margin-bottom: 0.65rem !important;
                padding: 0 !important;
            }

            body.woocommerce-checkout #customer_details label:has(input[type="checkbox"]) {
                margin-top: 0 !important;
                margin-bottom: 0 !important;
                line-height: 1.35 !important;
            }

            body.woocommerce-checkout #customer_details input[type="checkbox"] {
                margin-right: 0.5rem !important;
                vertical-align: middle !important;
            }

            /* Métodos de pago: eliminar los grandes saltos entre radios/opciones. */
            body.woocommerce-checkout #payment ul.payment_methods {
                margin: 0 0 0.65rem !important;
                padding: 0 !important;
            }

            body.woocommerce-checkout #payment ul.payment_methods li.wc_payment_method {
                margin: 0 !important;
                padding: 0.45rem 0 !important;
            }

            body.woocommerce-checkout #payment .wc_payment_method > label {
                margin: 0 !important;
                line-height: 1.35 !important;
            }

            body.woocommerce-checkout #payment .wc_payment_method input[type="radio"] {
                margin-right: 0.55rem !important;
                vertical-align: middle !important;
            }

            body.woocommerce-checkout #payment .payment_box {
                margin: 0.35rem 0 0.45rem !important;
                padding: 0.35rem 0 0.15rem !important;
            }

            body.woocommerce-checkout #payment .payment_box p {
                margin: 0 !important;
            }

            body.woocommerce-checkout #payment .woocommerce-terms-and-conditions-wrapper {
                margin-top: 0.45rem !important;
            }
        </style>
        <script id="elmercado-checkout-no-shipping-script">
            (function () {
                'use strict';

                function syncNoShippingState() {
                    var blocked = document.querySelector('.emo-no-shipping-destination[data-emo-no-shipping="1"]') !== null;
                    document.body.classList.toggle('emo-checkout-no-shipping', blocked);

                    var button = document.getElementById('place_order');
                    if (!button) {
                        return;
                    }

                    if (blocked) {
                        button.disabled = true;
                        button.setAttribute('aria-hidden', 'true');
                        button.setAttribute('tabindex', '-1');
                        button.style.setProperty('display', 'none', 'important');
                    } else {
                        button.disabled = false;
                        button.removeAttribute('aria-hidden');
                        button.removeAttribute('tabindex');
                        button.style.removeProperty('display');
                    }
                }

                document.addEventListener('DOMContentLoaded', syncNoShippingState);

                if (window.jQuery) {
                    window.jQuery(document.body).on('updated_checkout updated_shipping_method', syncNoShippingState);
                }

                var observer = new MutationObserver(syncNoShippingState);
                observer.observe(document.body, { childList: true, subtree: true });
            }());
        </script>
        <?php
    },
    PHP_INT_MAX
);
