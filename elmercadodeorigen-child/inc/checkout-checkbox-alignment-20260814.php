<?php
/**
 * Alinea horizontalmente los checks visibles del bloque de datos del checkout.
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
        <script id="elmercado-checkout-checkbox-alignment">
            (function () {
                'use strict';

                var rafId = 0;

                function alignCheckoutCheckboxes() {
                    window.cancelAnimationFrame(rafId);

                    var items = Array.prototype.slice.call(
                        document.querySelectorAll('#customer_details input[type="checkbox"]')
                    ).map(function (checkbox) {
                        return {
                            checkbox: checkbox,
                            label: checkbox.closest('label')
                        };
                    }).filter(function (item) {
                        return item.label && item.checkbox.getClientRects().length && item.label.getClientRects().length;
                    });

                    if (items.length < 2) {
                        return;
                    }

                    items.forEach(function (item) {
                        item.label.style.setProperty('transform', 'none', 'important');
                    });

                    rafId = window.requestAnimationFrame(function () {
                        var targetLeft = Math.max.apply(null, items.map(function (item) {
                            return item.checkbox.getBoundingClientRect().left;
                        }));

                        items.forEach(function (item) {
                            var currentLeft = item.checkbox.getBoundingClientRect().left;
                            var delta = Math.max(0, Math.round((targetLeft - currentLeft) * 10) / 10);
                            item.label.style.setProperty(
                                'transform',
                                delta > 0.5 ? 'translateX(' + delta + 'px)' : 'none',
                                'important'
                            );
                        });
                    });
                }

                document.addEventListener('DOMContentLoaded', alignCheckoutCheckboxes);
                window.addEventListener('resize', alignCheckoutCheckboxes);

                if (window.jQuery) {
                    window.jQuery(document.body).on(
                        'updated_checkout updated_shipping_method',
                        alignCheckoutCheckboxes
                    );
                }

                new MutationObserver(alignCheckoutCheckboxes).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }());
        </script>
        <?php
    },
    PHP_INT_MAX
);
