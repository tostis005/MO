<?php
/**
 * Plugin Name: MDO Vendor Desktop Products Layout Fix
 * Description: Prevents the vendor product grid from overlapping result/shipping/ordering controls on desktop.
 * Version: 1.0.1
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MDO_VENDOR_DESKTOP_LAYOUT_VERSION = '1.0.1';

/**
 * The WCFM vendor page currently inherits a -46px top margin on ul.products.
 * On desktop this pulls the first product row into the result/shipping/ordering bar.
 * Scope the correction to vendor pages and desktop widths only; mobile is untouched.
 *
 * Print this at the end of the document because part of the vendor styling is emitted
 * after wp_head and otherwise wins the cascade even against an earlier !important.
 */
function mdo_vendor_desktop_products_layout_fix() {
    if ( is_admin() ) {
        return;
    }
    ?>
    <style id="mdo-vendor-desktop-products-layout-fix">
    @media (min-width: 1024px) {
        html body.wcfm-store-page #products-wrapper ul.products.products {
            margin-top: 0 !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_footer', 'mdo_vendor_desktop_products_layout_fix', 99999 );
