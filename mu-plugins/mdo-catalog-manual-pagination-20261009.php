<?php
/**
 * Plugin Name: EMDO - Paginacion segura del catalogo
 * Description: Recupera la navegacion real de WooCommerce cuando el cargador continuo del tema no llega al frontend.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) { exit; }

function mdo_catalog_manual_pager_surface_20261009() {
    if (is_admin()) { return false; }
    return (function_exists('is_shop') && is_shop()) ||
        (function_exists('is_product_taxonomy') && is_product_taxonomy());
}

/**
 * Never make a public product inaccessible because a scroll plugin hid links.
 * Retain working native pagination even if a loader fails or was deactivated.
 */
add_action('wp_head', static function () {
    if (!mdo_catalog_manual_pager_surface_20261009()) { return; }
    ?>
    <style id="mdo-catalog-manual-pagination-20261009">
    html body.elmercado-child-theme .mdo-catalog-manual-pagination {
        display:flex !important; flex-wrap:wrap; align-items:center;
        justify-content:center; gap:12px; clear:both;
        margin:24px auto 20px; padding:10px 0; min-height:48px;
        width:100%; visibility:visible !important; opacity:1 !important;
    }
    html body.elmercado-child-theme .mdo-catalog-manual-pagination a {
        display:inline-flex !important; align-items:center; justify-content:center;
        min-height:42px; padding:0 18px; border:1px solid #cbdcd1;
        border-radius:999px; background:#fff; color:#173f32;
        font-size:13px; font-weight:750; text-decoration:none;
    }
    html body.elmercado-child-theme .mdo-catalog-manual-pagination a:hover,
    html body.elmercado-child-theme .mdo-catalog-manual-pagination a:focus-visible {
        background:#eef6f0; outline:2px solid #a8c5b0; outline-offset:2px;
    }
    html body.elmercado-child-theme .mdo-catalog-manual-pagination .mdo-pager-current {
        font-size:12px; font-weight:700; color:#60766b; padding:8px 2px;
    }
    /* Restore native WooCommerce page controls hidden by old infinite-scroll CSS. */
    html body.elmercado-child-theme.emo-continuous-catalog nav.woocommerce-pagination,
    html body.elmercado-child-theme.emo-continuous-catalog .woocommerce-pagination.emo-catalog-native-pagination,
    html body.elmercado-child-theme.emo-continuous-catalog nav.navigation.pagination {
        display:block !important; visibility:visible !important; opacity:1 !important;
    }
    </style>
    <?php
}, 99999);

/** Server-rendered links work even when JavaScript is disabled or blocked. */
add_action('woocommerce_after_shop_loop', static function () {
    if (!mdo_catalog_manual_pager_surface_20261009()) { return; }
    global $wp_query;
    if (!$wp_query instanceof WP_Query) { return; }

    $current = max(1, (int)get_query_var('paged'), (int)get_query_var('page'));
    $max = (int)$wp_query->max_num_pages;
    if ($max < 1 && function_exists('elmercado_catalog_exact_result_total_010220')) {
        $total = max(0, (int)elmercado_catalog_exact_result_total_010220());
        $size = (int)$wp_query->get('posts_per_page');
        if ($size < 1) { $size = max(1, (int)get_option('posts_per_page', 12)); }
        $max = (int)ceil($total / $size);
    }
    if ($max < 2) { return; }

    echo '<nav class="mdo-catalog-manual-pagination" aria-label="Páginas del catálogo">';
    if ($current > 1) {
        echo '<a rel="prev" href="' . esc_url(get_pagenum_link($current - 1)) . '">← Productos anteriores</a>';
    }
    echo '<span class="mdo-pager-current">Página ' . esc_html((string)$current) .
         ' de ' . esc_html((string)$max) . '</span>';
    if ($current < $max) {
        echo '<a rel="next" href="' . esc_url(get_pagenum_link($current + 1)) . '">Ver más productos →</a>';
    }
    echo '</nav>';
}, 40);
