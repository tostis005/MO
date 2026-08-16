<?php
/**
 * Fix the real Woostify hydrated mini-cart DOM.
 * The product image and product title live inside the same direct <a> child.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', static function (): void {
    if ( is_admin() ) { return; }
    ?>
    <style id="mdo-sidecart-actual-dom-fix-20260816">
    body .cart-sidebar-content .woocommerce-mini-cart-item {
        display:block!important;
        position:relative!important;
        width:100%!important;
        min-width:0!important;
        max-width:100%!important;
        padding:12px 0 18px!important;
        box-sizing:border-box!important;
    }

    /* Product link: image on the left, ALL title text in a clean right column. */
    body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) {
        display:block!important;
        position:relative!important;
        width:100%!important;
        min-width:0!important;
        max-width:100%!important;
        min-height:76px!important;
        margin:0!important;
        padding:0 46px 0 88px!important;
        box-sizing:border-box!important;
        white-space:normal!important;
        overflow-wrap:anywhere!important;
        word-break:normal!important;
        line-height:1.35!important;
    }

    body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) > img {
        position:absolute!important;
        left:0!important;
        top:0!important;
        display:block!important;
        float:none!important;
        width:72px!important;
        min-width:72px!important;
        max-width:72px!important;
        height:72px!important;
        margin:0!important;
        padding:0!important;
        object-fit:cover!important;
        box-sizing:border-box!important;
        transform:none!important;
    }

    /* Remove button must never consume a grid row or push title/image apart. */
    body .cart-sidebar-content .woocommerce-mini-cart-item > a.remove {
        position:absolute!important;
        top:8px!important;
        right:0!important;
        z-index:5!important;
        float:none!important;
        margin:0!important;
    }

    /* Product attributes/vendor follow exactly the same right-hand column. */
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation {
        display:grid!important;
        grid-template-columns:max-content minmax(0,1fr)!important;
        column-gap:5px!important;
        row-gap:3px!important;
        width:calc(100% - 88px)!important;
        min-width:0!important;
        max-width:calc(100% - 88px)!important;
        margin:8px 0 0 88px!important;
        padding:0!important;
        box-sizing:border-box!important;
        clear:both!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation dt,
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation dd,
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation p {
        float:none!important;
        display:block!important;
        min-width:0!important;
        max-width:100%!important;
        margin:0!important;
        padding:0!important;
        white-space:normal!important;
        overflow-wrap:anywhere!important;
        word-break:normal!important;
        box-sizing:border-box!important;
    }

    /* Quantity and price align with attributes/vendor, never below the image column. */
    body .cart-sidebar-content .woocommerce-mini-cart-item > .mini-cart-product-infor {
        display:flex!important;
        flex-wrap:wrap!important;
        align-items:center!important;
        justify-content:space-between!important;
        gap:8px 12px!important;
        width:calc(100% - 88px)!important;
        min-width:0!important;
        max-width:calc(100% - 88px)!important;
        margin:12px 0 0 88px!important;
        padding:0!important;
        box-sizing:border-box!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item .mini-cart-quantity {
        flex:0 0 auto!important;
        margin:0!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item .mini-cart-product-price {
        flex:0 0 auto!important;
        margin:0 0 0 auto!important;
        white-space:nowrap!important;
    }

    @media (max-width:480px) {
        body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) {
            min-height:66px!important;
            padding-left:78px!important;
            padding-right:42px!important;
        }
        body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) > img {
            width:64px!important;
            min-width:64px!important;
            max-width:64px!important;
            height:64px!important;
        }
        body .cart-sidebar-content .woocommerce-mini-cart-item > .variation,
        body .cart-sidebar-content .woocommerce-mini-cart-item > .mini-cart-product-infor {
            width:calc(100% - 78px)!important;
            max-width:calc(100% - 78px)!important;
            margin-left:78px!important;
        }
        body .cart-sidebar-content .woocommerce-mini-cart-item > .variation {
            grid-template-columns:max-content minmax(0,1fr)!important;
        }
    }
    </style>
    <?php
}, PHP_INT_MAX );

/* English cart fragments are hydrated after page load; fix the actual live selector too. */
add_action( 'wp_footer', static function (): void {
    if ( is_admin() ) { return; }
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( ! preg_match( '#^/en(?:/|$)#i', $path ) ) { return; }
    ?>
    <script id="mdo-sidecart-actual-dom-en-copy-20260816">
    (function(){'use strict';
      function fix(root){root=root||document;
        root.querySelectorAll('.cart-sidebar-content [aria-label]').forEach(function(el){
          var v=el.getAttribute('aria-label');
          if(v==='Reducir cantidad')el.setAttribute('aria-label','Decrease quantity');
          if(v==='Aumentar cantidad')el.setAttribute('aria-label','Increase quantity');
          if(v==='Cantidad')el.setAttribute('aria-label','Quantity');
        });
      }
      function run(){fix(document);}
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
      if(window.MutationObserver)new MutationObserver(run).observe(document.body,{childList:true,subtree:true});
    })();
    </script>
    <?php
}, PHP_INT_MAX );
