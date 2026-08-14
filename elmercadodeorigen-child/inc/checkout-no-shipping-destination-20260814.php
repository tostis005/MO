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

    return '<div class="emo-no-shipping-destination" data-emo-no-shipping="1" role="alert">'
        . '<strong>No podemos enviar los productos de este productor al destino indicado.</strong> '
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
            /* Aviso sin envío: contraste correcto tanto en carrito como en checkout. */
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

            body.woocommerce-cart .cart_totals .emo-no-shipping-destination,
            body.woocommerce-cart .cart_totals .emo-no-shipping-destination *,
            body.woocommerce-checkout #order_review .emo-no-shipping-destination,
            body.woocommerce-checkout #order_review .emo-no-shipping-destination * {
                color: #27352f !important;
            }

            body.woocommerce-cart .cart_totals .emo-no-shipping-destination strong,
            body.woocommerce-checkout #order_review .emo-no-shipping-destination strong,
            body.woocommerce-checkout .emo-no-shipping-destination strong {
                color: #0d211b !important;
                font-weight: 700 !important;
            }

            body.woocommerce-checkout:has(.emo-no-shipping-destination[data-emo-no-shipping="1"]) #place_order,
            body.emo-checkout-no-shipping #place_order {
                display: none !important;
            }

            /*
             * Checks del checkout. Woostify/WooCommerce añaden espacio en varios
             * niveles (fila + contenedor), por eso se neutralizan todos los wrappers
             * específicos de estas tres opciones sin tocar los campos normales.
             */
            body.woocommerce-checkout #customer_details p.form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details p.create-account,
            body.woocommerce-checkout #customer_details #ship-to-different-address,
            body.woocommerce-checkout #customer_details .emo-compact-check-row {
                min-height: 0 !important;
                height: auto !important;
                margin: 0.18rem 0 !important;
                padding: 0 !important;
            }

            body.woocommerce-checkout #customer_details .woocommerce-account-fields,
            body.woocommerce-checkout #customer_details .woocommerce-shipping-fields {
                min-height: 0 !important;
                height: auto !important;
                margin: 0.18rem 0 !important;
                padding: 0 !important;
                gap: 0 !important;
                row-gap: 0 !important;
            }

            body.woocommerce-checkout #customer_details .woocommerce-account-fields > p,
            body.woocommerce-checkout #customer_details .woocommerce-shipping-fields > h3 {
                margin-top: 0.18rem !important;
                margin-bottom: 0.18rem !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            body.woocommerce-checkout #customer_details label:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .emo-compact-check-label {
                display: inline-flex !important;
                align-items: center !important;
                gap: 0.5rem !important;
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1.3 !important;
            }

            body.woocommerce-checkout #customer_details input[type="checkbox"] {
                flex: 0 0 auto !important;
                margin: 0 !important;
                vertical-align: middle !important;
            }

            /* Métodos de pago: mantener el espaciado compacto que ya funciona. */
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

                function compactCheckoutChecks() {
                    document.querySelectorAll('#customer_details input[type="checkbox"]').forEach(function (checkbox) {
                        var label = checkbox.closest('label');
                        var row = checkbox.closest('p.form-row, p.create-account, h3#ship-to-different-address, p, h3, .form-row, .woocommerce-form-row, .form-group, li');
                        var section = checkbox.closest('.woocommerce-account-fields, .woocommerce-shipping-fields');

                        if (label) {
                            label.classList.add('emo-compact-check-label');
                            setImportant(label, 'margin', '0');
                            setImportant(label, 'padding', '0');
                            setImportant(label, 'line-height', '1.3');
                            setImportant(label, 'gap', '0.5rem');
                        }

                        if (row) {
                            row.classList.add('emo-compact-check-row');
                            setImportant(row, 'min-height', '0');
                            setImportant(row, 'height', 'auto');
                            setImportant(row, 'margin-top', '0.18rem');
                            setImportant(row, 'margin-bottom', '0.18rem');
                            setImportant(row, 'padding-top', '0');
                            setImportant(row, 'padding-bottom', '0');
                        }

                        if (section) {
                            setImportant(section, 'min-height', '0');
                            setImportant(section, 'height', 'auto');
                            setImportant(section, 'margin-top', '0.18rem');
                            setImportant(section, 'margin-bottom', '0.18rem');
                            setImportant(section, 'padding-top', '0');
                            setImportant(section, 'padding-bottom', '0');
                            setImportant(section, 'gap', '0');
                            setImportant(section, 'row-gap', '0');
                        }

                        setImportant(checkbox, 'margin', '0');
                    });
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

                    compactCheckoutChecks();
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
