<?php
/**
 * Fix the real Woostify hydrated mini-cart DOM.
 * Woostify rebuilds this fragment after page load, so runtime inline !important
 * styles are used to prevent theme/customizer rules from re-breaking the layout.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', static function (): void {
    if ( is_admin() ) { return; }
    ?>
    <style id="mdo-sidecart-actual-dom-fix-20260816">
    body .cart-sidebar-content .woocommerce-mini-cart-item {
        display:block!important; position:relative!important; width:100%!important;
        min-width:0!important; max-width:100%!important; padding:12px 0 18px!important;
        box-sizing:border-box!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) {
        display:block!important; position:relative!important; width:100%!important;
        min-width:0!important; max-width:100%!important; min-height:76px!important;
        margin:0!important; padding:0 46px 0 88px!important; box-sizing:border-box!important;
        white-space:normal!important; overflow-wrap:anywhere!important; word-break:normal!important;
        line-height:1.35!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) > img {
        position:absolute!important; left:0!important; top:0!important; display:block!important;
        float:none!important; width:72px!important; min-width:72px!important; max-width:72px!important;
        height:72px!important; margin:0!important; padding:0!important; object-fit:cover!important;
        box-sizing:border-box!important; transform:none!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > a.remove {
        position:absolute!important; top:8px!important; right:0!important; z-index:5!important;
        float:none!important; margin:0!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation {
        display:grid!important; grid-template-columns:max-content minmax(0,1fr)!important;
        column-gap:5px!important; row-gap:3px!important; width:calc(100% - 88px)!important;
        min-width:0!important; max-width:calc(100% - 88px)!important; margin:8px 0 0 88px!important;
        padding:0!important; box-sizing:border-box!important; clear:both!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation dt,
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation dd,
    body .cart-sidebar-content .woocommerce-mini-cart-item > .variation p {
        float:none!important; display:block!important; min-width:0!important; max-width:100%!important;
        margin:0!important; padding:0!important; white-space:normal!important;
        overflow-wrap:anywhere!important; word-break:normal!important; box-sizing:border-box!important;
    }
    body .cart-sidebar-content .woocommerce-mini-cart-item > .mini-cart-product-infor {
        display:flex!important; flex-wrap:wrap!important; align-items:center!important;
        justify-content:space-between!important; gap:8px 12px!important;
        width:calc(100% - 88px)!important; min-width:0!important;
        max-width:calc(100% - 88px)!important; margin:12px 0 0 88px!important;
        padding:0!important; box-sizing:border-box!important;
    }
    @media (max-width:480px) {
        body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) {
            min-height:66px!important; padding-left:78px!important; padding-right:42px!important;
        }
        body .cart-sidebar-content .woocommerce-mini-cart-item > a:not(.remove) > img {
            width:64px!important; min-width:64px!important; max-width:64px!important; height:64px!important;
        }
        body .cart-sidebar-content .woocommerce-mini-cart-item > .variation,
        body .cart-sidebar-content .woocommerce-mini-cart-item > .mini-cart-product-infor {
            width:calc(100% - 78px)!important; max-width:calc(100% - 78px)!important; margin-left:78px!important;
        }
    }
    </style>
    <?php
}, PHP_INT_MAX );

/*
 * Woostify/cart-fragments can inject stronger !important rules after wp_head.
 * Apply the intended geometry directly to the hydrated nodes, and repeat after
 * every fragment replacement. Inline !important wins that cascade reliably.
 */
add_action( 'wp_footer', static function (): void {
    if ( is_admin() ) { return; }
    ?>
    <script id="mdo-sidecart-actual-dom-runtime-20260816">
    (function(){'use strict';
      function imp(el, prop, value){ if(el) el.style.setProperty(prop, value, 'important'); }
      function fixItem(li){
        if(!li) return;
        var kids=Array.prototype.slice.call(li.children||[]);
        var product=kids.find(function(e){return e.tagName==='A'&&!e.classList.contains('remove');});
        var remove=kids.find(function(e){return e.classList&&e.classList.contains('remove');});
        var variation=kids.find(function(e){return e.classList&&e.classList.contains('variation');});
        var info=kids.find(function(e){return e.classList&&e.classList.contains('mini-cart-product-infor');});
        var img=product ? product.querySelector(':scope > img') : null;
        var mobile=window.matchMedia && window.matchMedia('(max-width:480px)').matches;
        var image=mobile?64:72, indent=mobile?78:88, right=mobile?42:46;

        imp(li,'display','block'); imp(li,'position','relative'); imp(li,'width','100%');
        imp(li,'min-width','0'); imp(li,'max-width','100%'); imp(li,'padding','12px 0 18px'); imp(li,'box-sizing','border-box');

        if(product){
          imp(product,'display','block'); imp(product,'position','relative'); imp(product,'width','100%');
          imp(product,'min-width','0'); imp(product,'max-width','100%'); imp(product,'min-height',image+'px');
          imp(product,'margin','0'); imp(product,'padding','0 '+right+'px 0 '+indent+'px');
          imp(product,'box-sizing','border-box'); imp(product,'white-space','normal');
          imp(product,'overflow-wrap','anywhere'); imp(product,'word-break','normal'); imp(product,'line-height','1.35');
        }
        if(img){
          imp(img,'position','absolute'); imp(img,'left','0'); imp(img,'top','0'); imp(img,'display','block');
          imp(img,'float','none'); imp(img,'width',image+'px'); imp(img,'min-width',image+'px'); imp(img,'max-width',image+'px');
          imp(img,'height',image+'px'); imp(img,'margin','0'); imp(img,'padding','0'); imp(img,'object-fit','cover');
          imp(img,'box-sizing','border-box'); imp(img,'transform','none');
        }
        if(remove){
          imp(remove,'position','absolute'); imp(remove,'top','8px'); imp(remove,'right','0'); imp(remove,'z-index','5');
          imp(remove,'float','none'); imp(remove,'margin','0');
        }
        if(variation){
          imp(variation,'display','grid'); imp(variation,'grid-template-columns','max-content minmax(0, 1fr)');
          imp(variation,'column-gap','5px'); imp(variation,'row-gap','3px');
          imp(variation,'width','calc(100% - '+indent+'px)'); imp(variation,'min-width','0');
          imp(variation,'max-width','calc(100% - '+indent+'px)'); imp(variation,'margin','8px 0 0 '+indent+'px');
          imp(variation,'padding','0'); imp(variation,'box-sizing','border-box'); imp(variation,'clear','both');
          variation.querySelectorAll('dt,dd,p').forEach(function(el){
            imp(el,'float','none'); imp(el,'display','block'); imp(el,'min-width','0'); imp(el,'max-width','100%');
            imp(el,'margin','0'); imp(el,'padding','0'); imp(el,'white-space','normal'); imp(el,'overflow-wrap','anywhere');
            imp(el,'word-break','normal'); imp(el,'box-sizing','border-box');
          });
        }
        if(info){
          imp(info,'display','flex'); imp(info,'flex-wrap','wrap'); imp(info,'align-items','center');
          imp(info,'justify-content','space-between'); imp(info,'gap','8px 12px');
          imp(info,'width','calc(100% - '+indent+'px)'); imp(info,'min-width','0');
          imp(info,'max-width','calc(100% - '+indent+'px)'); imp(info,'margin','12px 0 0 '+indent+'px');
          imp(info,'padding','0'); imp(info,'box-sizing','border-box');
          var qty=info.querySelector('.mini-cart-quantity'), price=info.querySelector('.mini-cart-product-price');
          if(qty){imp(qty,'flex','0 0 auto');imp(qty,'margin','0');}
          if(price){imp(price,'flex','0 0 auto');imp(price,'margin','0 0 0 auto');imp(price,'white-space','nowrap');}
        }
      }
      function fixAll(){ document.querySelectorAll('.cart-sidebar-content .woocommerce-mini-cart-item').forEach(fixItem); }
      if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',fixAll); else fixAll();
      if(window.MutationObserver) new MutationObserver(function(ms){
        var relevant=false; ms.forEach(function(m){if(m.addedNodes&&m.addedNodes.length) relevant=true;});
        if(relevant) fixAll();
      }).observe(document.body,{childList:true,subtree:true});
      window.addEventListener('resize',fixAll,{passive:true});
      if(window.jQuery){jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart',fixAll);}
    })();
    </script>
    <?php
}, PHP_INT_MAX - 1 );

/* English labels inside hydrated cart fragments. */
add_action( 'wp_footer', static function (): void {
    if ( is_admin() ) { return; }
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( ! preg_match( '#^/en(?:/|$)#i', $path ) ) { return; }
    ?>
    <script id="mdo-sidecart-actual-dom-en-copy-20260816">
    (function(){'use strict';
      function fix(){document.querySelectorAll('.cart-sidebar-content [aria-label]').forEach(function(el){
        var v=el.getAttribute('aria-label');
        if(v==='Reducir cantidad')el.setAttribute('aria-label','Decrease quantity');
        if(v==='Aumentar cantidad')el.setAttribute('aria-label','Increase quantity');
        if(v==='Cantidad')el.setAttribute('aria-label','Quantity');
      });}
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',fix);else fix();
      if(window.MutationObserver)new MutationObserver(fix).observe(document.body,{childList:true,subtree:true});
    })();
    </script>
    <?php
}, PHP_INT_MAX );
