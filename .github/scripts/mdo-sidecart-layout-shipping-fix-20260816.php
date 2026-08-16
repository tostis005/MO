<?php
/**
 * Production-safe side-cart layout hardening + final English shipping copy fix.
 * Applies visual layout to both languages. English copy changes are English-only.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_sidecart_fix_is_english_20260816(): bool {
    if ( function_exists( 'trp_get_current_language' ) ) {
        $lang = strtolower( str_replace( '_', '-', (string) trp_get_current_language() ) );
        if ( strpos( $lang, 'en' ) === 0 ) { return true; }
    }
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( $path && preg_match( '#^/en(?:/|$)#i', $path ) ) { return true; }
    $locale = strtolower( str_replace( '_', '-', (string) get_locale() ) );
    return strpos( $locale, 'en' ) === 0;
}

/**
 * Higher-specificity rules than the legacy inline mini-cart rules.
 * The thumbnail owns column 1 and all textual/product controls own column 2.
 */
add_action( 'wp_head', static function (): void {
    if ( is_admin() ) { return; }
    ?>
    <style id="mdo-sidecart-layout-fix-20260816">
    body .shop-cart-sidebar .woocommerce-mini-cart {
        overflow-x: hidden !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item {
        display: grid !important;
        grid-template-columns: 76px minmax(0, 1fr) !important;
        grid-template-rows: auto !important;
        column-gap: 14px !important;
        row-gap: 0 !important;
        align-items: start !important;
        position: relative !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        padding: 16px 40px 16px 14px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item > .woocommerce-mini-cart-item__thumb {
        grid-column: 1 !important;
        grid-row: 1 !important;
        align-self: start !important;
        position: relative !important;
        float: none !important;
        display: block !important;
        width: 76px !important;
        min-width: 76px !important;
        max-width: 76px !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item__thumb > a {
        display: block !important;
        width: 76px !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item__thumb img {
        position: static !important;
        float: none !important;
        display: block !important;
        width: 76px !important;
        min-width: 0 !important;
        max-width: 76px !important;
        height: 76px !important;
        margin: 0 !important;
        padding: 0 !important;
        object-fit: cover !important;
        transform: none !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item > .woocommerce-mini-cart-item__info {
        grid-column: 2 !important;
        grid-row: 1 !important;
        align-self: start !important;
        position: static !important;
        float: none !important;
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item__data,
    body .shop-cart-sidebar .woocommerce-mini-cart-item__footer {
        position: static !important;
        float: none !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item__data > a,
    body .shop-cart-sidebar .mini_cart_item__name {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-break: normal !important;
        line-height: 1.35 !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item .variation {
        display: grid !important;
        grid-template-columns: minmax(0, max-content) minmax(0, 1fr) !important;
        column-gap: 6px !important;
        row-gap: 3px !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 7px 0 0 !important;
        padding: 0 !important;
        font-size: 12px !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item .variation dt,
    body .shop-cart-sidebar .woocommerce-mini-cart-item .variation dd,
    body .shop-cart-sidebar .woocommerce-mini-cart-item .variation p {
        float: none !important;
        display: block !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-break: normal !important;
        box-sizing: border-box !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item__footer {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 8px 10px !important;
        margin-top: 10px !important;
    }

    body .shop-cart-sidebar .mini-cart-quantity {
        flex: 0 1 auto !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item__price {
        flex: 0 0 auto !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 0 0 0 auto !important;
        white-space: nowrap !important;
    }

    body .shop-cart-sidebar .woocommerce-mini-cart-item .remove {
        position: absolute !important;
        top: 14px !important;
        right: 11px !important;
        z-index: 3 !important;
        float: none !important;
        margin: 0 !important;
    }

    @media (max-width: 480px) {
        body .shop-cart-sidebar .woocommerce-mini-cart-item {
            grid-template-columns: 66px minmax(0, 1fr) !important;
            column-gap: 11px !important;
            padding: 14px 36px 14px 12px !important;
        }
        body .shop-cart-sidebar .woocommerce-mini-cart-item > .woocommerce-mini-cart-item__thumb,
        body .shop-cart-sidebar .woocommerce-mini-cart-item__thumb > a,
        body .shop-cart-sidebar .woocommerce-mini-cart-item__thumb img {
            width: 66px !important;
            max-width: 66px !important;
        }
        body .shop-cart-sidebar .woocommerce-mini-cart-item__thumb img {
            height: 66px !important;
        }
        body .shop-cart-sidebar .woocommerce-mini-cart-item .variation {
            grid-template-columns: 1fr !important;
            row-gap: 2px !important;
        }
        body .shop-cart-sidebar .woocommerce-mini-cart-item .variation dt:not(:first-child) {
            margin-top: 4px !important;
        }
    }
    </style>
    <?php
}, PHP_INT_MAX );

/** Exact dynamic strings that may come from custom fee/mini-cart code. */
function mdo_sidecart_fix_gettext_20260816( $translated, $text ) {
    if ( ! mdo_sidecart_fix_is_english_20260816() ) { return $translated; }
    $map = array(
        'Ahorro gastos de envío' => 'Shipping savings',
        'Reducir cantidad'       => 'Decrease quantity',
        'Aumentar cantidad'      => 'Increase quantity',
    );
    return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'mdo_sidecart_fix_gettext_20260816', PHP_INT_MAX, 2 );
add_filter( 'gettext_with_context', static function ( $translated, $text ) {
    return mdo_sidecart_fix_gettext_20260816( $translated, $text );
}, PHP_INT_MAX, 2 );

/**
 * Fee names are often stored dynamically and bypass gettext. Translate the exact
 * rendered label only on the English cart/checkout response.
 */
add_action( 'template_redirect', static function (): void {
    if ( is_admin() || ! mdo_sidecart_fix_is_english_20260816() ) { return; }
    $is_cart_or_checkout = ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() );
    if ( ! $is_cart_or_checkout ) { return; }
    ob_start( static function ( $html ) {
        return str_replace(
            array( 'Ahorro gastos de envío', 'Reducir cantidad', 'Aumentar cantidad' ),
            array( 'Shipping savings', 'Decrease quantity', 'Increase quantity' ),
            $html
        );
    } );
}, 0 );

/** AJAX/mutation fallback for English fragments inserted after initial render. */
add_action( 'wp_footer', static function (): void {
    if ( is_admin() || ! mdo_sidecart_fix_is_english_20260816() ) { return; }
    ?>
    <script id="mdo-sidecart-english-copy-fix-20260816">
    (function(){
      'use strict';
      function fix(root){
        root = root || document;
        root.querySelectorAll('.cart_totals tr.fee th').forEach(function(el){
          if ((el.textContent || '').trim() === 'Ahorro gastos de envío') el.textContent = 'Shipping savings';
        });
        root.querySelectorAll('.shop-cart-sidebar [aria-label]').forEach(function(el){
          var v = el.getAttribute('aria-label');
          if (v === 'Reducir cantidad') el.setAttribute('aria-label','Decrease quantity');
          if (v === 'Aumentar cantidad') el.setAttribute('aria-label','Increase quantity');
        });
      }
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ fix(document); });
      else fix(document);
      if (window.MutationObserver) new MutationObserver(function(ms){ ms.forEach(function(m){ m.addedNodes.forEach(function(n){ if(n.nodeType===1) fix(n); }); }); }).observe(document.body,{childList:true,subtree:true});
    })();
    </script>
    <?php
}, PHP_INT_MAX );
