<?php
/**
 * Plugin Name: MDO Global SEO Cluster Architecture 2026-10-01
 * Description: Connects editorial and commercial taxonomy clusters without forcing producer-specific silos.
 * Version: 2026.10.01.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_global_cluster_is_english_20261001(): bool {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

function mdo_global_cluster_registry_20261001(): array {
    return array(
        'jamones' => array(
            'blog' => 'jamones-y-paletas',
            'shop' => 'jamones-paletas',
            'label' => 'jamones y paletas',
            'shop_label' => 'Jamones y paletas',
        ),
        'aceites' => array(
            'blog' => 'aceites',
            'shop' => 'aceites',
            'label' => 'aceites',
            'shop_label' => 'Aceites',
        ),
        'carnes' => array(
            'blog' => 'carnes',
            'shop' => 'carnes',
            'label' => 'carnes',
            'shop_label' => 'Carnes',
        ),
        'wagyu' => array(
            'blog' => 'wagyu',
            'shop' => 'wagyu',
            'label' => 'Wagyu',
            'shop_label' => 'Wagyu',
            'parent' => 'carnes',
        ),
        'pato' => array(
            'blog' => 'pato',
            'shop' => 'pato',
            'label' => 'pato',
            'shop_label' => 'Productos de pato',
            'parent' => 'carnes',
            'specialized' => true,
        ),
        'hortalizas' => array(
            'blog' => 'hortalizas-y-verduras',
            'shop' => 'hortalizas-verduras',
            'label' => 'hortalizas y verduras',
            'shop_label' => 'Hortalizas y verduras',
        ),
        'legumbres' => array(
            'blog' => 'legumbres',
            'shop' => 'legumbres',
            'label' => 'legumbres',
            'shop_label' => 'Legumbres',
        ),
        'embutidos' => array(
            'blog' => 'embutidos-y-curados',
            'shop' => 'embutidos-y-curados',
            'label' => 'embutidos y curados',
            'shop_label' => 'Embutidos y curados',
        ),
        'conservas' => array(
            'blog' => 'conservas',
            'shop' => 'conservas',
            'label' => 'conservas',
            'shop_label' => 'Conservas',
        ),
        'packs' => array(
            'blog' => 'packs-y-lotes',
            'shop' => 'packs-y-lotes',
            'label' => 'packs y lotes',
            'shop_label' => 'Packs y lotes',
        ),
        'quesos' => array(
            'blog' => 'quesos',
            'shop' => 'quesos',
            'label' => 'quesos',
            'shop_label' => 'Quesos',
        ),
        'foie' => array(
            'blog' => 'foie-pates-untables',
            'shop' => 'foie-pates-untables',
            'label' => 'foie, patés y untables',
            'shop_label' => 'Foie, patés y untables',
        ),
        'pescados' => array(
            'blog' => 'pescados-y-mariscos',
            'shop' => 'pescados-mariscos',
            'label' => 'pescados y mariscos',
            'shop_label' => 'Pescados y mariscos',
        ),
    );
}

function mdo_global_cluster_get_term_20261001( string $taxonomy, string $slug ): ?WP_Term {
    $term = get_term_by( 'slug', $slug, $taxonomy );
    return $term instanceof WP_Term ? $term : null;
}

function mdo_global_cluster_term_url_20261001( string $taxonomy, string $slug ): string {
    $term = mdo_global_cluster_get_term_20261001( $taxonomy, $slug );
    if ( ! $term ) { return ''; }
    $url = get_term_link( $term );
    return is_wp_error( $url ) ? '' : (string) $url;
}

function mdo_global_cluster_has_products_20261001( string $shop_slug ): bool {
    static $cache = array();
    if ( isset( $cache[ $shop_slug ] ) ) { return $cache[ $shop_slug ]; }

    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy'         => 'product_cat',
                'field'            => 'slug',
                'terms'            => array( $shop_slug ),
                'include_children' => false,
            ),
        ),
    ) );
    $cache[ $shop_slug ] = ! empty( $ids );
    return $cache[ $shop_slug ];
}

function mdo_global_cluster_has_posts_20261001( string $blog_slug ): bool {
    static $cache = array();
    if ( isset( $cache[ $blog_slug ] ) ) { return $cache[ $blog_slug ]; }

    $term = mdo_global_cluster_get_term_20261001( 'category', $blog_slug );
    if ( ! $term ) {
        $cache[ $blog_slug ] = false;
        return false;
    }
    $ids = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => array( (int) $term->term_id ),
                'include_children' => false,
            ),
        ),
    ) );
    $cache[ $blog_slug ] = ! empty( $ids );
    return $cache[ $blog_slug ];
}

function mdo_global_cluster_active_20261001( string $key ): bool {
    $registry = mdo_global_cluster_registry_20261001();
    if ( empty( $registry[ $key ] ) ) { return false; }
    $cfg = $registry[ $key ];
    return mdo_global_cluster_has_posts_20261001( (string) $cfg['blog'] )
        && mdo_global_cluster_has_products_20261001( (string) $cfg['shop'] );
}

function mdo_global_cluster_for_post_20261001( int $post_id ): string {
    if ( $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) { return ''; }

    // Pato has a richer dedicated implementation and must win first.
    if ( 'duck' === (string) get_post_meta( $post_id, '_emdo_blog_cluster', true ) ) {
        return 'pato';
    }

    $terms = wp_get_post_terms( $post_id, 'category', array( 'fields'=>'slugs' ) );
    if ( is_wp_error( $terms ) ) { return ''; }
    $slugs = array_values( array_map( 'strval', $terms ) );

    $priority = array(
        'wagyu','foie','pescados','jamones','aceites','hortalizas',
        'legumbres','embutidos','conservas','packs','quesos','carnes'
    );
    $registry = mdo_global_cluster_registry_20261001();
    foreach ( $priority as $key ) {
        if ( ! empty( $registry[ $key ] ) && in_array( (string) $registry[ $key ]['blog'], $slugs, true ) ) {
            return $key;
        }
    }
    return '';
}

function mdo_global_cluster_for_product_20261001( int $product_id ): string {
    if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) { return ''; }

    if ( function_exists( 'mdo_duck_commerce_is_product_20261001' )
        && mdo_duck_commerce_is_product_20261001( $product_id ) ) {
        return 'pato';
    }

    $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields'=>'slugs' ) );
    if ( is_wp_error( $terms ) ) { return ''; }
    $slugs = array_values( array_map( 'strval', $terms ) );

    $priority = array(
        'wagyu','foie','pescados','jamones','aceites','hortalizas',
        'legumbres','embutidos','conservas','packs','quesos','carnes'
    );
    $registry = mdo_global_cluster_registry_20261001();
    foreach ( $priority as $key ) {
        if ( ! empty( $registry[ $key ] ) && in_array( (string) $registry[ $key ]['shop'], $slugs, true ) ) {
            return $key;
        }
    }
    return '';
}

function mdo_global_cluster_product_ids_20261001( string $shop_slug, int $seed = 0, int $limit = 3 ): array {
    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'fields'         => 'ids',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => array(
            array(
                'taxonomy'         => 'product_cat',
                'field'            => 'slug',
                'terms'            => array( $shop_slug ),
                'include_children' => false,
            ),
        ),
        'meta_query'     => array(
            array( 'key'=>'_stock_status', 'value'=>'instock', 'compare'=>'=' ),
        ),
    ) );
    $ids = array_values( array_map( 'intval', $ids ) );
    if ( empty( $ids ) ) { return array(); }

    $out = array();
    $count = count( $ids );
    $start = $count > 0 ? abs( $seed ) % $count : 0;
    for ( $i = 0; $i < min( $limit, $count ); $i++ ) {
        $out[] = $ids[ ( $start + $i ) % $count ];
    }
    return $out;
}

function mdo_global_cluster_post_ids_20261001( string $blog_slug, int $seed = 0, int $limit = 3 ): array {
    $term = mdo_global_cluster_get_term_20261001( 'category', $blog_slug );
    if ( ! $term ) { return array(); }
    $ids = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'fields'         => 'ids',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => array(
            array(
                'taxonomy'         => 'category',
                'field'            => 'term_id',
                'terms'            => array( (int) $term->term_id ),
                'include_children' => false,
            ),
        ),
    ) );
    $ids = array_values( array_map( 'intval', $ids ) );
    if ( empty( $ids ) ) { return array(); }
    $out = array();
    $count = count( $ids );
    $start = abs( $seed ) % $count;
    for ( $i = 0; $i < min( $limit, $count ); $i++ ) {
        $out[] = $ids[ ( $start + $i ) % $count ];
    }
    return $out;
}

function mdo_global_cluster_render_product_links_20261001( string $key, int $seed ): string {
    $registry = mdo_global_cluster_registry_20261001();
    if ( empty( $registry[ $key ] ) || ! mdo_global_cluster_active_20261001( $key ) ) { return ''; }
    $cfg = $registry[ $key ];
    $url = mdo_global_cluster_term_url_20261001( 'product_cat', (string) $cfg['shop'] );
    if ( '' === $url ) { return ''; }

    $ids = mdo_global_cluster_product_ids_20261001( (string) $cfg['shop'], $seed, 3 );
    if ( empty( $ids ) ) { return ''; }

    $html = '<aside class="mdo-global-cluster mdo-global-cluster--products" data-mdo-global-cluster="' . esc_attr( $key ) . '">';
    $html .= '<h2>Productos relacionados</h2>';
    $html .= '<p>Si quieres pasar de la guía a la compra, estas son opciones disponibles dentro de ' . esc_html( (string) $cfg['shop_label'] ) . '.</p><ul>';
    foreach ( $ids as $id ) {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
        if ( ! $product ) { continue; }
        $html .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $product->get_name() ) . '</a>';
        $price = trim( wp_strip_all_tags( $product->get_price_html() ) );
        if ( '' !== $price ) { $html .= ' <span>· ' . esc_html( $price ) . '</span>'; }
        $html .= '</li>';
    }
    $html .= '</ul><p><a href="' . esc_url( $url ) . '">Ver todos los productos de ' . esc_html( strtolower( (string) $cfg['shop_label'] ) ) . ' →</a></p></aside>';
    return $html;
}

function mdo_global_cluster_render_guide_links_20261001( string $key, int $seed ): string {
    $registry = mdo_global_cluster_registry_20261001();
    if ( empty( $registry[ $key ] ) ) { return ''; }
    $cfg = $registry[ $key ];
    if ( ! mdo_global_cluster_has_posts_20261001( (string) $cfg['blog'] ) ) { return ''; }

    $url = mdo_global_cluster_term_url_20261001( 'category', (string) $cfg['blog'] );
    if ( '' === $url ) { return ''; }
    $ids = mdo_global_cluster_post_ids_20261001( (string) $cfg['blog'], $seed, 3 );
    if ( empty( $ids ) ) { return ''; }

    $html = '<section class="mdo-global-cluster mdo-global-cluster--guides" data-mdo-global-cluster-guides="' . esc_attr( $key ) . '">';
    $html .= '<h2>Guías para elegir, conservar y preparar</h2><ul>';
    foreach ( $ids as $id ) {
        $html .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></li>';
    }
    $html .= '</ul><p><a href="' . esc_url( $url ) . '">Ver todas las guías de ' . esc_html( (string) $cfg['label'] ) . ' →</a></p></section>';
    return $html;
}

/**
 * Standard article -> commerce bridge. Existing GSC bridges and Pato's richer
 * implementation take precedence, so the site never renders two equivalent
 * modules on the same article.
 */
add_filter( 'the_content', static function( string $content ): string {
    if ( mdo_global_cluster_is_english_20261001() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) { return $content; }
    if ( false !== strpos( $content, 'data-mdo-global-cluster=' )
        || false !== strpos( $content, 'data-emdo-duck-commerce=' )
        || false !== strpos( $content, 'mdo-seo-cluster-shop' ) ) {
        return $content;
    }

    $post_id = (int) get_the_ID();
    $key = mdo_global_cluster_for_post_20261001( $post_id );
    if ( '' === $key || 'pato' === $key ) { return $content; }

    $block = mdo_global_cluster_render_product_links_20261001( $key, $post_id );
    return '' !== $block ? $content . "\n" . $block : $content;
}, PHP_INT_MAX );

/** Product -> editorial bridge. */
add_action( 'woocommerce_after_single_product_summary', static function(): void {
    if ( mdo_global_cluster_is_english_20261001() || ! function_exists( 'is_product' ) || ! is_product() ) { return; }
    $product_id = (int) get_queried_object_id();
    $key = mdo_global_cluster_for_product_20261001( $product_id );
    if ( '' === $key || 'pato' === $key ) { return; }

    $block = mdo_global_cluster_render_guide_links_20261001( $key, $product_id );
    if ( '' !== $block ) { echo $block; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 13 );

/** Product-category hub -> editorial hub. */
add_action( 'woocommerce_after_shop_loop', static function(): void {
    if ( mdo_global_cluster_is_english_20261001() || ! function_exists( 'is_product_category' ) || ! is_product_category() || is_paged() ) { return; }
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term ) { return; }

    $registry = mdo_global_cluster_registry_20261001();
    foreach ( $registry as $key => $cfg ) {
        if ( (string) $cfg['shop'] !== (string) $term->slug || 'pato' === $key ) { continue; }
        $block = mdo_global_cluster_render_guide_links_20261001( $key, (int) $term->term_id );
        if ( '' !== $block ) { echo $block; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        break;
    }
}, 35 );

/** Blog-category hub -> commercial hub and a few currently available products. */
add_action( 'loop_start', static function( WP_Query $query ): void {
    static $rendered = false;
    if ( $rendered || is_admin() || mdo_global_cluster_is_english_20261001() || ! $query->is_main_query() || ! is_category() ) { return; }

    $term = get_queried_object();
    if ( ! $term instanceof WP_Term ) { return; }
    $registry = mdo_global_cluster_registry_20261001();

    foreach ( $registry as $key => $cfg ) {
        if ( (string) $cfg['blog'] !== (string) $term->slug || 'pato' === $key ) { continue; }
        if ( ! mdo_global_cluster_active_20261001( $key ) ) { return; }

        $block = mdo_global_cluster_render_product_links_20261001( $key, (int) $term->term_id );
        if ( '' !== $block ) {
            $rendered = true;
            echo $block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        return;
    }
}, 2 );

/** Keep internal/empty category archives out of the index even if products drift. */
function mdo_global_cluster_is_junk_product_category_20261001(): bool {
    if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) { return false; }
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term ) { return false; }

    if ( in_array( (string) $term->slug, array( 'sin-categorizar','uncategorized' ), true ) ) {
        return true;
    }

    foreach ( mdo_global_cluster_registry_20261001() as $cfg ) {
        if ( (string) $cfg['shop'] !== (string) $term->slug ) { continue; }
        return ! mdo_global_cluster_has_products_20261001( (string) $term->slug );
    }

    return false;
}
add_filter( 'aioseo_robots_meta', static function( $attributes ) {
    if ( ! is_array( $attributes ) || ! mdo_global_cluster_is_junk_product_category_20261001() ) { return $attributes; }
    $attributes['noindex'] = 'noindex';
    $attributes['nofollow'] = '';
    return $attributes;
}, PHP_INT_MAX );
add_filter( 'wp_robots', static function( array $robots ): array {
    if ( mdo_global_cluster_is_junk_product_category_20261001() ) { $robots['noindex'] = true; }
    return $robots;
}, PHP_INT_MAX );

add_action( 'wp_head', static function(): void {
    if ( ! is_singular( array( 'post','product' ) ) && ! is_category() && ( ! function_exists('is_product_category') || ! is_product_category() ) ) {
        return;
    }
    ?>
    <style id="mdo-global-cluster-css">
        .mdo-global-cluster{margin:30px 0;padding:22px;border:1px solid rgba(13,33,27,.13);border-radius:14px;background:#f8f5ef}
        .mdo-global-cluster h2{margin-top:0;font-size:1.25em}
        .mdo-global-cluster ul{margin:12px 0;padding-left:20px}
        .mdo-global-cluster li{margin:7px 0}
        .mdo-global-cluster a{font-weight:650;text-decoration:underline;text-underline-offset:3px}
    </style>
    <?php
}, 122 );
