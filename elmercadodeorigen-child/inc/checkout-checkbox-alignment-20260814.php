<?php
/**
 * Alinea los checks del bloque de datos del checkout sin desplazamientos visuales.
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
            body.woocommerce-checkout #customer_details p.form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .form-row:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details p.create-account,
            body.woocommerce-checkout #customer_details #ship-to-different-address,
            body.woocommerce-checkout #customer_details .woocommerce-account-fields,
            body.woocommerce-checkout #customer_details .woocommerce-shipping-fields {
                margin-left: 0 !important;
                padding-left: 0 !important;
                text-indent: 0 !important;
            }

            body.woocommerce-checkout #customer_details label:has(input[type="checkbox"]),
            body.woocommerce-checkout #customer_details .emo-compact-check-label {
                margin-left: 0 !important;
                transform: none !important;
            }

            body.woocommerce-checkout #customer_details input[type="checkbox"] {
                margin-left: 0 !important;
                transform: none !important;
            }
        </style>
        <script id="elmercado-checkout-checkbox-alignment">
            (function () {
                'use strict';

                function setImportant(el, property, value) {
                    if (el) {
                        el.style.setProperty(property, value, 'important');
                    }
                }

                function normalizeCheckoutCheckboxes() {
                    document.querySelectorAll('#customer_details input[type="checkbox"]').forEach(function (checkbox) {
                        var label = checkbox.closest('label');
                        var row = checkbox.closest('p.form-row, p.create-account, h3#ship-to-different-address, p, h3, .form-row, .woocommerce-form-row, .form-group, li');
                        var section = checkbox.closest('.woocommerce-account-fields, .woocommerce-shipping-fields');

                        setImportant(checkbox, 'margin-left', '0');
                        setImportant(checkbox, 'transform', 'none');

                        if (label) {
                            setImportant(label, 'margin-left', '0');
                            setImportant(label, 'transform', 'none');
                        }

                        if (row) {
                            setImportant(row, 'margin-left', '0');
                            setImportant(row, 'padding-left', '0');
                            setImportant(row, 'text-indent', '0');
                            setImportant(row, 'transform', 'none');
                        }

                        if (section) {
                            setImportant(section, 'margin-left', '0');
                            setImportant(section, 'padding-left', '0');
                            setImportant(section, 'text-indent', '0');
                            setImportant(section, 'transform', 'none');
                        }
                    });
                }

                document.addEventListener('DOMContentLoaded', normalizeCheckoutCheckboxes);

                if (window.jQuery) {
                    window.jQuery(document.body).on(
                        'updated_checkout updated_shipping_method',
                        normalizeCheckoutCheckboxes
                    );
                }

                new MutationObserver(normalizeCheckoutCheckboxes).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }());
        </script>
        <?php
    },
    PHP_INT_MAX
);
