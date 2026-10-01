<?php
/**
 * Plugin Name: MDO Duck Commerce SEO 2026-10-01
 * Description: Connects the duck editorial cluster with Selectos de Castilla's duck catalog through commercial taxonomy, metadata, internal links and product schema.
 * Version: 2026.10.01.2
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const EMDO_DUCK_COMMERCE_SUPPLIER_ID_20261001 = 5;

function mdo_duck_commerce_category_meta_20261001(): array {
    return array(
        'pato' => array(
            'title' => 'Comprar productos de pato online | Foie, magret y confit',
            'description' => 'Compra productos de pato de Selectos de Castilla: foie gras, magret, confit, jamón de pato, patés, mousses, rillettes y cortes frescos.',
        ),
        'magret-de-pato' => array(
            'title' => 'Comprar magret de pato online | Selectos de Castilla',
            'description' => 'Compra magret de pato de Selectos de Castilla. Consulta formatos, precio, conservación y disponibilidad, y aprende a cocinarlo con nuestras guías.',
        ),
        'foie-gras-de-pato' => array(
            'title' => 'Comprar foie gras de pato online | Selectos de Castilla',
            'description' => 'Compra foie gras de pato de Selectos de Castilla: fresco, micuit, bloc y otros formatos. Compara precio, conservación, textura y disponibilidad.',
        ),
        'confit-de-pato' => array(
            'title' => 'Comprar confit de pato online | Selectos de Castilla',
            'description' => 'Compra confit de pato de Selectos de Castilla en distintos formatos. Consulta precio y disponibilidad y descubre cómo calentarlo y servirlo.',
        ),
        'jamon-de-pato' => array(
            'title' => 'Comprar jamón de pato online | Selectos de Castilla',
            'description' => 'Compra jamón de pato de Selectos de Castilla, en pieza o loncheado según disponibilidad. Consulta formatos, precio, conservación y cómo servirlo.',
        ),
        'pate-mousse-rillettes-de-pato' => array(
            'title' => 'Comprar paté, mousse y rillettes de pato online',
            'description' => 'Compra paté, mousse, parfait y rillettes de pato de Selectos de Castilla. Compara formatos, composición, precio y disponibilidad antes de elegir.',
        ),
        'pato-fresco' => array(
            'title' => 'Comprar pato fresco online | Cortes de pato',
            'description' => 'Compra pato fresco y cortes de pato de Selectos de Castilla: muslo, solomillo, corazones, mollejas y otras piezas según disponibilidad.',
        ),
    );
}

function mdo_duck_commerce_strlen_20261001( string $value ): int {
    return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
}

function mdo_duck_commerce_trim_title_20261001( string $value, int $max = 60 ): string {
    $value = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $value ) ) );
    if ( mdo_duck_commerce_strlen_20261001( $value ) <= $max ) { return $value; }
    if ( function_exists( 'mb_substr' ) ) {
        $cut = mb_substr( $value, 0, $max + 1, 'UTF-8' );
        $cut = preg_replace( '/\s+\S*$/u', '', $cut );
        return trim( (string) $cut );
    }
    $cut = substr( $value, 0, $max + 1 );
    $cut = preg_replace( '/\s+\S*$/', '', $cut );
    return trim( (string) $cut );
}

function mdo_duck_commerce_is_product_20261001( int $product_id = 0 ): bool {
    if ( $product_id <= 0 ) { $product_id = (int) get_queried_object_id(); }
    return $product_id > 0
        && 'product' === get_post_type( $product_id )
        && '1' === (string) get_post_meta( $product_id, '_emdo_duck_commercial_cluster', true )
        && EMDO_DUCK_COMMERCE_SUPPLIER_ID_20261001 === (int) get_post_meta( $product_id, '_emdo_supplier_id', true );
}

function mdo_duck_commerce_product_title_for_id_20261001( int $product_id ): string {
    $name = trim( wp_strip_all_tags( (string) get_the_title( $product_id ) ) );
    if ( '' === $name ) { return ''; }
    $options = array(
        'Comprar ' . $name . ' | Selectos de Castilla',
        $name . ' | Selectos de Castilla',
        'Comprar ' . $name,
        $name,
    );
    foreach ( $options as $option ) {
        if ( mdo_duck_commerce_strlen_20261001( $option ) <= 60 ) { return $option; }
    }
    return mdo_duck_commerce_trim_title_20261001( $name, 60 );
}

function mdo_duck_commerce_product_description_for_id_20261001( int $product_id ): string {
    $name = trim( wp_strip_all_tags( (string) get_the_title( $product_id ) ) );
    if ( '' === $name ) { return ''; }
    $description = 'Compra ' . $name . ' de Selectos de Castilla en El Mercado de Origen. Consulta precio, formato, ingredientes, conservación y disponibilidad actual.';
    if ( mdo_duck_commerce_strlen_20261001( $description ) > 165 ) {
        $description = 'Compra ' . $name . ' de Selectos de Castilla. Consulta precio, formato, conservación y disponibilidad actual en El Mercado de Origen.';
    }
    return $description;
}

function mdo_duck_commerce_current_term_slug_20261001(): string {
    if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) { return ''; }
    $term = get_queried_object();
    return $term instanceof WP_Term ? (string) $term->slug : '';
}

function mdo_duck_commerce_seo_title_20261001( $current ): string {
    if ( is_admin() ) { return (string) $current; }

    $slug = mdo_duck_commerce_current_term_slug_20261001();
    $map  = mdo_duck_commerce_category_meta_20261001();
    if ( '' !== $slug && isset( $map[ $slug ] ) ) { return (string) $map[ $slug ]['title']; }

    if ( function_exists( 'is_product' ) && is_product() && mdo_duck_commerce_is_product_20261001() ) {
        $title = mdo_duck_commerce_product_title_for_id_20261001( (int) get_queried_object_id() );
        return '' !== $title ? $title : (string) $current;
    }

    return (string) $current;
}

function mdo_duck_commerce_seo_description_20261001( $current ): string {
    if ( is_admin() ) { return (string) $current; }

    $slug = mdo_duck_commerce_current_term_slug_20261001();
    $map  = mdo_duck_commerce_category_meta_20261001();
    if ( '' !== $slug && isset( $map[ $slug ] ) ) { return (string) $map[ $slug ]['description']; }

    if ( function_exists( 'is_product' ) && is_product() && mdo_duck_commerce_is_product_20261001() ) {
        $description = mdo_duck_commerce_product_description_for_id_20261001( (int) get_queried_object_id() );
        return '' !== $description ? $description : (string) $current;
    }

    return (string) $current;
}

function mdo_duck_commerce_register_seo_filters_20261001(): void {
    if ( is_admin() ) { return; }
    $priority = PHP_INT_MAX;
    add_filter( 'aioseo_title', 'mdo_duck_commerce_seo_title_20261001', $priority );
    add_filter( 'aioseo_description', 'mdo_duck_commerce_seo_description_20261001', $priority );
    add_filter( 'wpseo_title', 'mdo_duck_commerce_seo_title_20261001', $priority );
    add_filter( 'wpseo_metadesc', 'mdo_duck_commerce_seo_description_20261001', $priority );
    add_filter( 'rank_math/frontend/title', 'mdo_duck_commerce_seo_title_20261001', $priority );
    add_filter( 'rank_math/frontend/description', 'mdo_duck_commerce_seo_description_20261001', $priority );
    add_filter( 'seopress_titles_title', 'mdo_duck_commerce_seo_title_20261001', $priority );
    add_filter( 'seopress_titles_desc', 'mdo_duck_commerce_seo_description_20261001', $priority );
    add_filter( 'pre_get_document_title', 'mdo_duck_commerce_seo_title_20261001', $priority );
}
/*
 * Register on wp, after MU/plugins have finished installing their generic
 * commerce/category filters. At the same maximum filter priority this narrow
 * duck override is therefore appended last and wins only on this cluster.
 */
add_action( 'wp', 'mdo_duck_commerce_register_seo_filters_20261001', PHP_INT_MAX );

function mdo_duck_commerce_blog_target_slug_20261001( int $post_id ): string {
    $key = (string) get_post_meta( $post_id, '_emdo_seo_landing_key', true );
    if ( in_array( $key, array( 'duck-02','duck-06','duck-11','duck-14','duck-15','duck-18','duck-20','duck-21','duck-58' ), true ) ) {
        return 'foie-gras-de-pato';
    }
    if ( in_array( $key, array( 'duck-08','duck-12','duck-13','duck-19','duck-39','duck-40','duck-41','duck-42','duck-43' ), true ) ) {
        return 'pate-mousse-rillettes-de-pato';
    }
    $group = (string) get_post_meta( $post_id, '_emdo_duck_primary_subtopic', true );
    $map = array(
        'carne-cortes' => 'pato-fresco',
        'magret'       => 'magret-de-pato',
        'confit'       => 'confit-de-pato',
        'jamon'        => 'jamon-de-pato',
        'salsas-menus' => 'pato',
    );
    return isset( $map[ $group ] ) ? $map[ $group ] : 'pato';
}

function mdo_duck_commerce_term_url_20261001( string $slug ): string {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) { return ''; }
    $url = get_term_link( $term );
    return is_wp_error( $url ) ? '' : (string) $url;
}

function mdo_duck_commerce_product_ids_for_category_20261001( string $slug ): array {
    static $cache = array();
    if ( isset( $cache[ $slug ] ) ) { return $cache[ $slug ]; }

    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'fields'         => 'ids',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => array(
            array( 'taxonomy'=>'product_cat', 'field'=>'slug', 'terms'=>array( $slug ), 'include_children'=>false ),
        ),
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key'=>'_emdo_supplier_id', 'value'=>EMDO_DUCK_COMMERCE_SUPPLIER_ID_20261001, 'compare'=>'=' ),
            array( 'key'=>'_emdo_duck_commercial_cluster', 'value'=>'1', 'compare'=>'=' ),
            array( 'key'=>'_stock_status', 'value'=>'instock', 'compare'=>'=' ),
        ),
    ) );
    $cache[ $slug ] = array_values( array_map( 'intval', $ids ) );
    return $cache[ $slug ];
}

/** Add a stable commerce bridge to every duck article without hard-coding product IDs. */
add_filter( 'the_content', static function ( string $content ): string {
    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) { return $content; }
    $post_id = (int) get_the_ID();
    if ( 'duck' !== (string) get_post_meta( $post_id, '_emdo_blog_cluster', true ) ) { return $content; }
    if ( false !== strpos( $content, 'data-emdo-duck-commerce' ) ) { return $content; }

    $slug = mdo_duck_commerce_blog_target_slug_20261001( $post_id );
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) { return $content; }
    $term_url = get_term_link( $term );
    if ( is_wp_error( $term_url ) ) { return $content; }

    $ids = mdo_duck_commerce_product_ids_for_category_20261001( $slug );
    if ( empty( $ids ) && 'pato' !== $slug ) {
        $slug = 'pato';
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term instanceof WP_Term ) { return $content; }
        $term_url = get_term_link( $term );
        if ( is_wp_error( $term_url ) ) { return $content; }
        $ids = mdo_duck_commerce_product_ids_for_category_20261001( $slug );
    }

    $picked = array();
    $count = count( $ids );
    if ( $count > 0 ) {
        $start = $post_id % $count;
        for ( $i = 0; $i < min( 3, $count ); $i++ ) {
            $picked[] = $ids[ ( $start + $i ) % $count ];
        }
    }

    $html  = '<aside class="emdo-duck-commerce" data-emdo-duck-commerce="' . esc_attr( $slug ) . '">';
    $html .= '<h2>Productos de pato relacionados</h2>';
    $html .= '<p>Si quieres pasar de la guía a la compra, aquí tienes opciones de Selectos de Castilla relacionadas con este tema.</p>';
    if ( $picked ) {
        $html .= '<ul>';
        foreach ( $picked as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) { continue; }
            $html .= '<li><a href="' . esc_url( get_permalink( $product_id ) ) . '">' . esc_html( $product->get_name() ) . '</a>';
            $price = trim( wp_strip_all_tags( $product->get_price_html() ) );
            if ( '' !== $price ) { $html .= ' <span class="emdo-duck-commerce-price">· ' . esc_html( $price ) . '</span>'; }
            $html .= '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '<p><a class="emdo-duck-commerce-category" href="' . esc_url( $term_url ) . '">Ver ' . esc_html( strtolower( $term->name ) ) . ' →</a></p>';
    $html .= '</aside>';

    return $content . "\n" . $html;
}, 44 );

function mdo_duck_commerce_guide_map_20261001(): array {
    static $map = null;
    if ( is_array( $map ) ) { return $map; }
    $map = array();
    $ids = get_posts( array(
        'post_type'=>'post',
        'post_status'=>'publish',
        'posts_per_page'=>100,
        'fields'=>'ids',
        'meta_key'=>'_emdo_blog_cluster',
        'meta_value'=>'duck',
    ) );
    foreach ( $ids as $id ) {
        $key = (string) get_post_meta( (int) $id, '_emdo_seo_landing_key', true );
        if ( '' !== $key ) {
            $map[ $key ] = array(
                'url' => (string) get_permalink( (int) $id ),
                'title' => (string) get_the_title( (int) $id ),
            );
        }
    }
    return $map;
}

function mdo_duck_commerce_guides_for_group_20261001( string $group ): array {
    $groups = array(
        'fresh'   => array( 'duck-01','duck-07','duck-53' ),
        'magret'  => array( 'duck-03','duck-09','duck-24' ),
        'foie'    => array( 'duck-02','duck-11','duck-15' ),
        'confit'  => array( 'duck-04','duck-28','duck-29' ),
        'jamon'   => array( 'duck-05','duck-34','duck-35' ),
        'prepared'=> array( 'duck-06','duck-41','duck-39' ),
        'root'    => array( 'duck-01','duck-54','duck-55' ),
    );
    $keys = isset( $groups[ $group ] ) ? $groups[ $group ] : $groups['root'];
    $map = mdo_duck_commerce_guide_map_20261001();
    $out = array();
    foreach ( $keys as $key ) {
        if ( isset( $map[ $key ] ) ) { $out[] = $map[ $key ]; }
    }
    return $out;
}

/** Send product-page authority back into the editorial cluster and commercial hub. */
add_action( 'woocommerce_after_single_product_summary', static function (): void {
    if ( ! function_exists( 'is_product' ) || ! is_product() || ! mdo_duck_commerce_is_product_20261001() ) { return; }
    $product_id = (int) get_queried_object_id();
    $group = (string) get_post_meta( $product_id, '_emdo_duck_product_cluster', true );
    $guides = mdo_duck_commerce_guides_for_group_20261001( $group );
    $root_url = mdo_duck_commerce_term_url_20261001( 'pato' );
    if ( empty( $guides ) && '' === $root_url ) { return; }

    echo '<section class="emdo-duck-product-guides" data-emdo-duck-product-guides="' . esc_attr( $group ?: 'root' ) . '">';
    echo '<h2>Guías para elegir y preparar productos de pato</h2>';
    echo '<p>Completa la información de la ficha con nuestras guías sobre cortes, conservación, cocción y servicio.</p>';
    if ( $guides ) {
        echo '<ul>';
        foreach ( $guides as $guide ) {
            echo '<li><a href="' . esc_url( $guide['url'] ) . '">' . esc_html( $guide['title'] ) . '</a></li>';
        }
        echo '</ul>';
    }
    if ( '' !== $root_url ) {
        echo '<p><a class="emdo-duck-product-hub" href="' . esc_url( $root_url ) . '">Ver todos los productos de pato →</a></p>';
    }
    echo '</section>';
}, 12 );

add_filter( 'woocommerce_structured_data_product', static function ( $markup, $product ) {
    if ( ! is_array( $markup ) || ! $product instanceof WC_Product ) { return $markup; }
    $product_id = (int) $product->get_id();
    if ( ! mdo_duck_commerce_is_product_20261001( $product_id ) ) { return $markup; }
    $markup['brand'] = array(
        '@type' => 'Brand',
        'name'  => 'Selectos de Castilla',
    );
    $group = (string) get_post_meta( $product_id, '_emdo_duck_product_cluster', true );
    $labels = array(
        'fresh'=>'Pato fresco',
        'magret'=>'Magret de pato',
        'foie'=>'Foie gras de pato',
        'confit'=>'Confit de pato',
        'jamon'=>'Jamón de pato',
        'prepared'=>'Paté, mousse y rillettes de pato',
        'root'=>'Productos de pato',
    );
    $markup['category'] = isset( $labels[ $group ] ) ? $labels[ $group ] : 'Productos de pato';
    return $markup;
}, 20, 2 );

add_action( 'wp_head', static function (): void {
    $is_duck_post = is_singular( 'post' ) && 'duck' === (string) get_post_meta( get_queried_object_id(), '_emdo_blog_cluster', true );
    $is_duck_product = function_exists( 'is_product' ) && is_product() && mdo_duck_commerce_is_product_20261001();
    if ( ! $is_duck_post && ! $is_duck_product ) { return; }
    ?>
    <style id="emdo-duck-commerce-css">
        .emdo-duck-commerce,.emdo-duck-product-guides{margin:30px 0;padding:22px;border:1px solid rgba(13,33,27,.13);border-radius:14px;background:#f8f5ef}
        .emdo-duck-commerce h2,.emdo-duck-product-guides h2{margin-top:0;font-size:1.25em}
        .emdo-duck-commerce ul,.emdo-duck-product-guides ul{margin:12px 0;padding-left:20px}
        .emdo-duck-commerce li,.emdo-duck-product-guides li{margin:7px 0}
        .emdo-duck-commerce a,.emdo-duck-product-guides a{font-weight:650;text-decoration:underline;text-underline-offset:3px}
        .emdo-duck-commerce-price{white-space:nowrap}
    </style>
    <?php
}, 121 );
