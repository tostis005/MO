<?php
/**
 * Plugin Name: EMDO - Packs Latitudes Black Friday de tienda
 * Description: Disponibilidad de packs publicos vinculada a sus componentes originales, sin modificar los packs exclusivos Mentta.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

/** True only when every source item can cover the requested number of packs. */
function emdo_web_pack_components_available($lines, $packsQty = 1) {
    if (!is_array($lines) || !$lines || (int)$packsQty < 1) return false;
    foreach ($lines as $line) {
        $id = (int)($line['id'] ?? 0);
        $units = (int)($line['qty'] ?? 0) * (int)$packsQty;
        $part = $id ? wc_get_product($id) : false;
        if (!$part || !$part->is_type('simple') || $units < 1 ||
            $part->get_status() !== 'publish' || !$part->is_in_stock()) return false;
        if ($part->managing_stock() && $part->get_stock_quantity() !== null &&
            (float)$part->get_stock_quantity() < $units) return false;
    }
    return true;
}

function emdo_web_pack_lines_for($productId) {
    $productId = absint($productId);
    if (!$productId || !get_post_meta($productId, '_emdo_web_source_mentta_pack_id', true)) return null;
    $lines = get_post_meta($productId, '_emdo_web_pack_lines', true);
    return is_array($lines) && $lines ? $lines : [];
}

/** Dynamic stock, independent of the Mentta-only pack stock integration. */
add_filter('woocommerce_product_get_stock_status', static function($status, $p) {
    if (!($p instanceof WC_Product)) return $status;
    $lines = emdo_web_pack_lines_for($p->get_id());
    if ($lines === null) return $status;
    return emdo_web_pack_components_available($lines, 1) ? 'instock' : 'outofstock';
}, 200, 2);
add_filter('woocommerce_product_is_in_stock', static function($stock, $p) {
    if (!($p instanceof WC_Product)) return $stock;
    $lines = emdo_web_pack_lines_for($p->get_id());
    return $lines === null ? $stock : emdo_web_pack_components_available($lines, 1);
}, 200, 2);

/** Aggregate stock demands across distinct packs sharing the same meat cuts. */
function emdo_web_pack_cart_stock_ok($additionId = 0, $additionQty = 0) {
    if (!function_exists('WC') || !WC()->cart) return true;
    $need = [];
    foreach (WC()->cart->get_cart() as $item) {
        $id = (int)($item['product_id'] ?? 0);
        $lines = emdo_web_pack_lines_for($id);
        if ($lines === null) continue;
        $qty = (int)($item['quantity'] ?? 0);
        foreach ($lines as $line) {
            $componentId = (int)($line['id'] ?? 0);
            $need[$componentId] = ($need[$componentId] ?? 0) + (int)($line['qty'] ?? 0) * $qty;
        }
    }
    $extra = emdo_web_pack_lines_for($additionId);
    if ($extra !== null && $additionQty > 0) {
        foreach ($extra as $line) {
            $componentId = (int)($line['id'] ?? 0);
            $need[$componentId] = ($need[$componentId] ?? 0) + (int)($line['qty'] ?? 0) * (int)$additionQty;
        }
    }
    foreach ($need as $id => $qty) {
        $p = $id ? wc_get_product($id) : false;
        if (!$p || $qty <= 0 || $p->get_status() !== 'publish' || !$p->is_in_stock() ||
            ($p->managing_stock() && $p->get_stock_quantity() !== null &&
              (float)$p->get_stock_quantity() < $qty)) return false;
    }
    return true;
}
add_filter('woocommerce_add_to_cart_validation', static function($valid, $productId, $quantity) {
    if (!$valid || emdo_web_pack_lines_for($productId) === null) return $valid;
    if (!emdo_web_pack_cart_stock_ok($productId, $quantity)) {
        wc_add_notice('Este pack no tiene suficientes unidades disponibles para la cantidad seleccionada.', 'error');
        return false;
    }
    return $valid;
}, 200, 3);
add_action('woocommerce_check_cart_items', static function() {
    if (!emdo_web_pack_cart_stock_ok()) {
        wc_add_notice('Algunos packs de carne ya no tienen suficientes unidades disponibles. Revisa las cantidades del carrito.', 'error');
    }
}, 200);
