<?php
/**
 * Mensaje claro cuando un productor no tiene envío configurado para el destino
 * y bloqueo visual del botón de realizar pedido mientras exista ese estado.
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
        . '<strong>No podemos enviar los productos de este productor al destino indicado.</strong> '
        . 'Actualmente no tiene configurada una opción de envío para esa zona. '
        . 'Si crees que debería estar disponible o se trata de un error, ponte en contacto con nosotros y lo revisaremos.'
        . '</div>';
}

add_filter( 'woocommerce_no_shipping_available_html', 'elmercado_checkout_no_shipping_message', 999 );
add_filter( 'woocommerce_cart_no_shipping_available_html', 'elmercado_checkout_no_shipping_message', 999 );

/**
 * Oculta el botón de finalizar pedido mientras WooCommerce muestre el aviso
 * anterior. El checkout se recalcula por AJAX, por eso se sincroniza tras cada
 * actualización y también ante cambios del DOM.
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

            body.emo-checkout-no-shipping #place_order {
                display: none !important;
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
                    } else {
                        button.disabled = false;
                        button.removeAttribute('aria-hidden');
                        button.removeAttribute('tabindex');
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
