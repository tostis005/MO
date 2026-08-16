<?php
/** Final mobile sizing correction for the Woostify side-cart thumbnail column. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'wp_head', static function (): void {
    if ( is_admin() ) { return; }
    ?>
    <style id="mdo-sidecart-mobile-width-final-20260816">
    @media (max-width:480px) {
        body .shop-cart-sidebar .woocommerce-mini-cart-item > .woocommerce-mini-cart-item__thumb {
            width:66px!important;
            min-width:66px!important;
            max-width:66px!important;
        }
    }
    </style>
    <?php
}, PHP_INT_MAX );
