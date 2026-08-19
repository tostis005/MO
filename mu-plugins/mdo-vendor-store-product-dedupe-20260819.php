<?php
/**
 * Plugin Name: MDO Vendor Store Product Deduplication
 * Description: Stabilises WCFM vendor-store product pagination and prevents duplicate product cards during continuous loading.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Detect the public WCFM producer-store surface without depending on the child theme.
 */
function mdo_vspd_is_vendor_store_request(): bool {
    if ( is_admin() ) {
        return false;
    }

    if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) ) {
        return (bool) elmercado_vendor_store_is_request_010225();
    }

    if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
        return true;
    }
    if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
        return true;
    }
    if ( function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page() ) {
        return true;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $parts       = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
    $endpoint    = trim( (string) get_option( 'wcfm_store_url', 'tienda' ), '/' );
    $position    = array_search( $endpoint, $parts, true );

    return false !== $position && isset( $parts[ $position + 1 ] );
}

/**
 * Limit SQL changes to product queries used while rendering a producer store.
 */
function mdo_vspd_is_vendor_product_query( WP_Query $query ): bool {
    if ( ! mdo_vspd_is_vendor_store_request() || $query->get( 'emo_vendor_store_truth_010225' ) ) {
        return false;
    }

    $post_type = $query->get( 'post_type' );

    return 'product' === $post_type
        || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
        || $query->is_post_type_archive( 'product' )
        || $query->is_tax( 'dc_vendor_shop' );
}

/**
 * Taxonomy/meta joins can otherwise return the same product row more than once.
 */
add_filter(
    'posts_distinct',
    static function ( string $distinct, WP_Query $query ): string {
        return mdo_vspd_is_vendor_product_query( $query ) ? 'DISTINCT' : $distinct;
    },
    PHP_INT_MAX,
    2
);

/**
 * Keep offset pagination deterministic when the selected WooCommerce sort value
 * is shared by several products. The existing ordering remains authoritative;
 * product ID is only a final tie-breaker.
 */
add_filter(
    'posts_orderby',
    static function ( string $orderby, WP_Query $query ): string {
        if ( ! mdo_vspd_is_vendor_product_query( $query ) ) {
            return $orderby;
        }

        global $wpdb;

        if ( preg_match( '/\bRAND\s*\(/i', $orderby ) ) {
            return $orderby;
        }

        $id_pattern = '/(?:`?' . preg_quote( $wpdb->posts, '/' ) . '`?\.)?`?ID`?\s+(?:ASC|DESC)\b/i';
        if ( preg_match( $id_pattern, $orderby ) ) {
            return $orderby;
        }

        $tie_breaker = "{$wpdb->posts}.ID DESC";
        return '' !== trim( $orderby ) ? rtrim( trim( $orderby ), ',' ) . ', ' . $tie_breaker : $tie_breaker;
    },
    PHP_INT_MAX,
    2
);

/**
 * Last-line browser guard. It removes any duplicate already present in the
 * first response and watches the grid for cards appended by any loader.
 */
add_action(
    'wp_footer',
    static function (): void {
        if ( ! mdo_vspd_is_vendor_store_request() ) {
            return;
        }
        ?>
        <script id="mdo-vendor-store-product-dedupe-20260819">
        (() => {
            'use strict';

            const grid = document.querySelector('#wcfmmp-store ul.products');
            if (!grid) return;

            const seenProductKeys = new Set();
            const registeredNodes = new WeakSet();

            const normaliseProductUrl = (href) => {
                if (!href) return '';
                try {
                    const url = new URL(href, window.location.href);
                    url.hash = '';
                    url.search = '';
                    url.pathname = url.pathname.replace(/\/+$/, '/');
                    return `${url.origin.toLowerCase()}${url.pathname.toLowerCase()}`;
                } catch (_) {
                    return String(href).split('#')[0].split('?')[0].replace(/\/+$/, '/').toLowerCase();
                }
            };

            const productKey = (item) => {
                if (!(item instanceof Element)) return '';

                const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
                if (postClass) return `id:${postClass.slice(5)}`;

                const elementId = item.getAttribute('id') || '';
                const idMatch = elementId.match(/^product-(\d+)$/i);
                if (idMatch) return `id:${idMatch[1]}`;

                const directId = item.getAttribute('data-product_id') || item.getAttribute('data-product-id') || '';
                if (/^\d+$/.test(directId)) return `id:${directId}`;

                const idCarrier = item.querySelector('[data-product_id], [data-product-id]');
                const nestedId = idCarrier?.getAttribute('data-product_id') || idCarrier?.getAttribute('data-product-id') || '';
                if (/^\d+$/.test(nestedId)) return `id:${nestedId}`;

                const link = item.querySelector('a.woocommerce-LoopProduct-link[href], a[href*="/producto/"], a[href*="/product/"]');
                const canonical = normaliseProductUrl(link?.href || '');
                return canonical ? `url:${canonical}` : '';
            };

            const registerProduct = (item, removeDuplicate = true) => {
                if (!(item instanceof Element) || !item.matches('li.product')) return true;
                if (registeredNodes.has(item)) return true;

                const key = productKey(item);
                if (key && seenProductKeys.has(key)) {
                    if (removeDuplicate && item.isConnected) item.remove();
                    return false;
                }

                if (key) seenProductKeys.add(key);
                registeredNodes.add(item);
                return true;
            };

            [...grid.querySelectorAll(':scope > li.product')].forEach((item) => registerProduct(item, true));

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (!(node instanceof Element)) return;

                        const candidates = node.matches('li.product')
                            ? [node]
                            : [...node.querySelectorAll('li.product')];

                        candidates.forEach((item) => {
                            if (item.parentElement === grid) registerProduct(item, true);
                        });
                    });
                });
            });

            observer.observe(grid, { childList: true });

            window.__mdoVendorProductDedupe = {
                version: '2026-08-19.1',
                count: () => seenProductKeys.size
            };
        })();
        </script>
        <?php
    },
    PHP_INT_MAX
);
