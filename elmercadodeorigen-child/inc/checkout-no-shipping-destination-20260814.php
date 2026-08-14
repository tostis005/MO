<?php
/**
 * Ajustes específicos de carrito y checkout.
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

    return '<div class="emo-no-shipping-destination" data-emo-no-shipping="1" role="alert" style="color:#27352f !important;background:#fff7e8 !important;">'
        . '<strong style="color:#0d211b !important;">No podemos enviar los productos de este productor al destino indicado.</strong> '
        . 'Actualmente no tiene configurada una opción de envío para esa zona. '
        . 'Si crees que debería estar disponible o se trata de un error, ponte en contacto con nosotros y lo revisaremos.'
        . '</div>';
}

add_filter( 'woocommerce_no_shipping_available_html', 'elmercado_checkout_no_shipping_message', 999 );
add_filter( 'woocommerce_cart_no_shipping_available_html', 'elmercado_checkout_no_shipping_message', 999 );

add_action(
    'wp_footer',
    static function (): void {
        $is_checkout_page = function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page();
        $is_cart_page     = function_exists( 'is_cart' ) && is_cart();

        if ( ! $is_checkout_page && ! $is_cart_page ) {
            return;
        }
        ?>
        <style id="elmercado-checkout-no-shipping-style">
            .emo-no-shipping-destination {
                display: block !important;
                margin: 0.75rem 0 !important;
                padding: 0.9rem 1rem !important;
                background: #fff7e8 !important;
                border: 1px solid rgba(178, 122, 33, 0.28) !important;
                border-left: 4px solid #b27a21 !important;
                border-radius: 12px !important;
                color: #27352f !important;
                font-size: 0.92rem !important;
                font-weight: 400 !important;
                line-height: 1.55 !important;
                text-align: left !important;
            }

            body.woocommerce-cart .emo-no-shipping-destination,
            body.woocommerce-cart .emo-no-shipping-destination *,
            body.woocommerce-checkout .emo-no-shipping-destination,
            body.woocommerce-checkout .emo-no-shipping-destination * {
                color: #27352f !important;
            }

            body.woocommerce-cart .emo-no-shipping-destination strong,
            body.woocommerce-checkout .emo-no-shipping-destination strong {
                color: #0d211b !important;
                font-weight: 700 !important;
            }

            body.woocommerce-checkout:has(.emo-no-shipping-destination[data-emo-no-shipping="1"]) #place_order,
            body.emo-checkout-no-shipping #place_order {
                display: none !important;
            }

            /* Métodos de pago: mantener el espaciado compacto ya validado. */
            body.woocommerce-checkout #payment ul.payment_methods {
                gap: 0 !important;
                row-gap: 0 !important;
                margin: 0 0 0.45rem !important;
                padding: 0 !important;
            }

            body.woocommerce-checkout #payment ul.payment_methods li.wc_payment_method {
                gap: 0 !important;
                row-gap: 0 !important;
                margin: 0 !important;
                padding: 0.28rem 0 !important;
            }

            body.woocommerce-checkout #payment .wc_payment_method > label {
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1.3 !important;
            }

            body.woocommerce-checkout #payment .wc_payment_method input[type="radio"] {
                margin-right: 0.55rem !important;
                vertical-align: middle !important;
            }

            body.woocommerce-checkout #payment .payment_box {
                margin: 0.18rem 0 0.28rem 1.65rem !important;
                padding: 0 !important;
            }

            body.woocommerce-checkout #payment .payment_box p {
                margin: 0 !important;
                padding: 0 !important;
            }

            body.woocommerce-checkout #payment .woocommerce-terms-and-conditions-wrapper {
                margin-top: 0.3rem !important;
                padding-top: 0 !important;
            }
        </style>
        <?php if ( $is_checkout_page ) : ?>
        <script id="elmercado-checkout-no-shipping-script">
            (function () {
                'use strict';

                function setImportant(el, property, value) {
                    if (el) {
                        el.style.setProperty(property, value, 'important');
                    }
                }

                function compactPaymentOptions() {
                    var paymentList = document.querySelector('#payment ul.payment_methods');
                    if (paymentList) {
                        setImportant(paymentList, 'gap', '0');
                        setImportant(paymentList, 'row-gap', '0');
                        setImportant(paymentList, 'margin-top', '0');
                        setImportant(paymentList, 'margin-bottom', '0.45rem');
                        setImportant(paymentList, 'padding-top', '0');
                        setImportant(paymentList, 'padding-bottom', '0');
                    }

                    document.querySelectorAll('#payment ul.payment_methods > li').forEach(function (method) {
                        setImportant(method, 'gap', '0');
                        setImportant(method, 'row-gap', '0');
                        setImportant(method, 'margin-top', '0');
                        setImportant(method, 'margin-bottom', '0');
                        setImportant(method, 'padding-top', '0.28rem');
                        setImportant(method, 'padding-bottom', '0.28rem');
                    });

                    document.querySelectorAll('#payment .payment_box').forEach(function (box) {
                        setImportant(box, 'margin-top', '0.18rem');
                        setImportant(box, 'margin-bottom', '0.28rem');
                        setImportant(box, 'padding-top', '0');
                        setImportant(box, 'padding-bottom', '0');
                    });
                }

                function syncCheckoutState() {
                    var blocked = document.querySelector('.emo-no-shipping-destination[data-emo-no-shipping="1"]') !== null;
                    document.body.classList.toggle('emo-checkout-no-shipping', blocked);

                    var button = document.getElementById('place_order');
                    if (button) {
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

                    compactPaymentOptions();
                }

                document.addEventListener('DOMContentLoaded', syncCheckoutState);

                if (window.jQuery) {
                    window.jQuery(document.body).on('updated_checkout updated_shipping_method payment_method_selected', syncCheckoutState);
                }

                var observer = new MutationObserver(syncCheckoutState);
                observer.observe(document.body, { childList: true, subtree: true });
            }());
        </script>
        <?php endif; ?>
        <?php
    },
    PHP_INT_MAX
);
