<?php
/**
 * English-only commerce/UI translation bridge for El Mercado de Origen.
 * Keeps native Spanish data untouched and reuses reviewed _en_US_* term metadata.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_commerce_en_request_20260816(): bool {
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

/** @return array<string,string> */
function mdo_commerce_en_ui_map_20260816(): array {
    return array(
        'Buscar' => 'Search',
        'Búsqueda' => 'Search',
        'Buscar productos...' => 'Search products...',
        'Buscar productos…' => 'Search products…',
        'Escribe para buscar' => 'Type to search',
        'Filtros' => 'Filters',
        'Filtrar productos' => 'Filter products',
        'Cerrar filtros' => 'Close filters',
        'Filtros activos' => 'Active filters',
        'Filtros aplicados' => 'Applied filters',
        'Limpiar todo' => 'Clear all',
        'Categorías' => 'Categories',
        'Vendedor' => 'Seller',
        'Precio' => 'Price',
        'Precio mínimo' => 'Minimum price',
        'Precio máximo' => 'Maximum price',
        'Recomendados' => 'Recommended',
        'Más populares' => 'Most popular',
        'Mejor valorados' => 'Top rated',
        'Más recientes' => 'Newest',
        'Menor precio' => 'Lowest price',
        'Mayor precio' => 'Highest price',
        'Ordenar por' => 'Sort by',
        'Mostrar más' => 'Show more',
        'VISITAR' => 'VISIT',
        'Visitar' => 'Visit',
        'TU SELECCIÓN' => 'YOUR SELECTION',
        'Tu selección' => 'Your selection',
        'Revisa tu carrito' => 'Review your cart',
        'Comprueba cantidades y productos antes de continuar.' => 'Check quantities and products before continuing.',
        'Comprueba cantidades y productos antes de continuar. Verás el coste final y las opciones disponibles en el siguiente paso.' => 'Check quantities and products before continuing. You’ll see the final cost and available options in the next step.',
        'Pago protegido durante todo el proceso' => 'Secure payment throughout the process',
        'Información clara antes de confirmar' => 'Clear information before you confirm',
        'Atención cercana si necesitas ayuda' => 'Personal support if you need help',
        'Tu selección se ha guardado correctamente.' => 'Your selection has been saved successfully.',
        'Tamaño' => 'Size',
        'Tamaño:' => 'Size:',
        'Variedad' => 'Variety',
        'Variedad:' => 'Variety:',
        'Alimentación' => 'Feeding',
        'Calidad' => 'Quality',
        'Con DOP' => 'PDO',
        'Curación' => 'Curing',
        'Denominación de origen' => 'Designation of origin',
        'Origen' => 'Origin',
        'Peso' => 'Weight',
        'Preparación' => 'Preparation',
        'Productor' => 'Producer',
        'Raza ibérica' => 'Iberian breed',
        'Tipo de pieza' => 'Piece type',
        'Tipo de producto' => 'Product type',
        'IVA incl.' => 'VAT incl.',
        'IVA' => 'VAT',
    );
}

add_filter( 'gettext', static function ( $translated, $text, $domain ) {
    if ( ! mdo_commerce_en_request_20260816() ) { return $translated; }
    $map = mdo_commerce_en_ui_map_20260816();
    return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}, PHP_INT_MAX, 3 );

// Reuse the reviewed English names already stored for WooCommerce attribute terms.
add_filter( 'get_term', static function ( $term, $taxonomy ) {
    if ( ! mdo_commerce_en_request_20260816() || ! $term instanceof WP_Term || ! is_string( $taxonomy ) || strpos( $taxonomy, 'pa_' ) !== 0 ) {
        return $term;
    }
    $english = (string) get_term_meta( $term->term_id, '_en_US_name', true );
    if ( $english === '' ) { return $term; }
    $clean = trim( html_entity_decode( wp_strip_all_tags( $english ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
    if ( $clean === '' ) { return $term; }
    $copy = clone $term;
    $copy->name = $clean;
    return $copy;
}, PHP_INT_MAX, 2 );

add_filter( 'woocommerce_attribute_label', static function ( $label, $name, $product = null ) {
    if ( ! mdo_commerce_en_request_20260816() ) { return $label; }
    $map = array(
        'pa_alimentacion' => 'Feeding',
        'pa_calidad' => 'Quality',
        'pa_con-dop' => 'PDO',
        'pa_curacion' => 'Curing',
        'pa_dop' => 'Designation of origin',
        'pa_origen' => 'Origin',
        'pa_peso' => 'Weight',
        'pa_preparacion' => 'Preparation',
        'pa_productor' => 'Producer',
        'pa_rango-peso' => 'Weight',
        'pa_raza-iberica' => 'Iberian breed',
        'pa_tamano' => 'Size',
        'pa_tipo-pieza' => 'Piece type',
        'pa_tipo-producto' => 'Product type',
        'pa_variedad' => 'Variety',
    );
    return isset( $map[ $name ] ) ? $map[ $name ] : $label;
}, PHP_INT_MAX, 3 );

// Attributes are shown separately in cart/checkout. Avoid duplicating raw Spanish
// variation attributes inside the variation product title itself.
add_filter( 'woocommerce_product_variation_title_include_attributes', static function ( $include ) {
    return mdo_commerce_en_request_20260816() ? false : $include;
}, PHP_INT_MAX );

// Translate exact late-injected text nodes/attributes without touching scripts, URLs or Spanish pages.
add_action( 'wp_footer', static function (): void {
    if ( is_admin() || ! mdo_commerce_en_request_20260816() ) { return; }
    $map = mdo_commerce_en_ui_map_20260816();
    ?>
    <script id="mdo-english-commerce-ui-bridge-20260816">
    (() => {
        'use strict';
        const map = <?php echo wp_json_encode( $map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
        const skip = new Set(['SCRIPT','STYLE','NOSCRIPT','TEXTAREA','CODE','PRE']);
        const translate = (value) => {
            if (typeof value !== 'string') return value;
            const core = value.trim();
            if (Object.prototype.hasOwnProperty.call(map, core)) return map[core];
            const filterCount = core.match(/^Filtros\s*\((\d+)\)$/u);
            if (filterCount) return `Filters (${filterCount[1]})`;
            return core;
        };
        const textNode = (node) => {
            if (!node || node.nodeType !== Node.TEXT_NODE || !node.parentElement || skip.has(node.parentElement.tagName)) return;
            const raw = node.nodeValue || '';
            const core = raw.trim();
            if (!core) return;
            const replacement = translate(core);
            if (replacement === core) return;
            const lead = raw.match(/^\s*/u)?.[0] || '';
            const trail = raw.match(/\s*$/u)?.[0] || '';
            node.nodeValue = lead + replacement + trail;
        };
        const element = (el) => {
            if (!(el instanceof Element) || skip.has(el.tagName)) return;
            for (const attr of ['aria-label','title','placeholder','alt']) {
                if (!el.hasAttribute(attr)) continue;
                const raw = el.getAttribute(attr) || '';
                const replacement = translate(raw);
                if (replacement !== raw.trim()) el.setAttribute(attr, replacement);
            }
            for (const child of el.childNodes) if (child.nodeType === Node.TEXT_NODE) textNode(child);
        };
        const scan = (root) => {
            if (!root) return;
            if (root.nodeType === Node.TEXT_NODE) { textNode(root); return; }
            if (!(root instanceof Element || root instanceof Document || root instanceof DocumentFragment)) return;
            if (root instanceof Element) element(root);
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
            let node;
            while ((node = walker.nextNode())) node.nodeType === Node.TEXT_NODE ? textNode(node) : element(node);
        };
        const start = () => {
            scan(document.body);
            const observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    if (mutation.type === 'characterData') textNode(mutation.target);
                    for (const node of mutation.addedNodes) scan(node);
                }
            });
            observer.observe(document.body, {subtree:true, childList:true, characterData:true});
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true});
        else start();
    })();
    </script>
    <?php
}, PHP_INT_MAX );
