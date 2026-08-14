<?php
/**
 * Corrige el contraste de las opciones resaltadas de SelectWoo en checkout.
 *
 * Algunos estilos del tema dejan el fondo oscuro al pasar por una opción,
 * pero conservan el texto oscuro, haciendo que el país/provincia desaparezca.
 * El parche queda limitado al checkout.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'wp_head',
    static function (): void {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
            return;
        }
        ?>
        <style id="elmercado-checkout-select-contrast-fix">
            body.woocommerce-checkout .select2-container--default .select2-results__option--highlighted,
            body.woocommerce-checkout .select2-container--default .select2-results__option--highlighted[aria-selected],
            body.woocommerce-checkout .select2-container--default .select2-results__option--highlighted[data-selected],
            body.woocommerce-checkout .selectWoo-container .select2-results__option--highlighted {
                color: #fff !important;
            }
        </style>
        <?php
    },
    PHP_INT_MAX
);
