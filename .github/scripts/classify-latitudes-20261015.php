<?php
/** One-off idempotent taxonomy reconciliation for 138 disabled Latitudes products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$supplier_table = MDO_Database::table( 'suppliers' );
$table = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $supplier || '1' !== (string) get_user_meta( (int) $supplier['vendor_user_id'], '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Se exige que el vendedor Latitudes permanezca deshabilitado.' );
}
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT id,wc_product_id,title,source_url,status FROM {$table} WHERE supplier_id=%d ORDER BY id", (int) $supplier['id']
), ARRAY_A );
if ( count( $rows ) !== 138 ) { throw new RuntimeException( 'La clasificación exige las 138 referencias conocidas.' ); }
$existing = array( 'carnes','pato','foie-gras-de-pato','magret-de-pato','foie-pates-untables','packs-y-lotes','wagyu' );
$ids = array();
foreach ( $existing as $slug ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) { throw new RuntimeException( 'Falta una categoría existente: ' . $slug ); }
    $ids[ $slug ] = (int) $term->term_id;
}
$descriptions = array(
    'carnes-exoticas' => array( 'name' => 'Carnes exóticas', 'parent' => $ids['carnes'],
        'description' => 'Cortes de carnes menos habituales y especialidades de distintas especies. La ficha de cada producto identifica su formato y procedencia cuando el proveedor la facilita.' ),
    'elaborados-carnicos' => array( 'name' => 'Elaborados cárnicos', 'parent' => $ids['carnes'],
        'description' => 'Hamburguesas, fingers, croquetas y otras elaboraciones a base de carne, organizadas por tipo de preparación, sin crear una categoría para cada especie o productor.' ),
    'huevos' => array( 'name' => 'Huevos', 'parent' => 0,
        'description' => 'Huevos de distintas aves en los formatos disponibles de cada productor.' ),
);
foreach ( $descriptions as $slug => $config ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term ) {
        $made = wp_insert_term( $config['name'], 'product_cat', array(
            'slug' => $slug, 'parent' => $config['parent'], 'description' => $config['description'],
        ) );
        if ( is_wp_error( $made ) ) { throw new RuntimeException( $made->get_error_message() ); }
        $term = get_term( (int) $made['term_id'], 'product_cat' );
    }
    if ( ! $term instanceof WP_Term || (int) $term->parent !== (int) $config['parent'] ) {
        throw new RuntimeException( 'La categoría existe con una jerarquía incompatible: ' . $slug );
    }
    $ids[ $slug ] = (int) $term->term_id;
}
$out = array();
$counts = array();
foreach ( $rows as $r ) {
    $url = (string) $r['source_url'];
    $title = mb_strtolower( remove_accents( (string) $r['title'] ) );
    $url_path = (string) wp_parse_url( $url, PHP_URL_PATH );
    $product_id = (int) $r['wc_product_id'];
    $wc = $product_id ? wc_get_product( $product_id ) : false;
    if ( ! $wc || $wc->get_status() !== 'draft' || $wc->get_catalog_visibility() !== 'hidden' ||
        (int) get_post_field( 'post_author', $product_id ) !== (int) $supplier['vendor_user_id'] ) {
        throw new RuntimeException( 'La ficha no está correctamente oculta/asignada: ' . $r['id'] );
    }
    $slugs = array();
    if ( str_starts_with( $url_path, '/exoticos/pato/' ) ) {
        $slugs = str_contains( $title, 'foie' )
            ? array( 'carnes', 'pato', 'foie-gras-de-pato', 'foie-pates-untables' )
            : array( 'carnes', 'pato', 'magret-de-pato' );
    } elseif ( preg_match( '~/exoticos/(?:avestruz|oca)/huevo-|/exoticos/avestruz/huevo-~', $url_path ) ) {
        $slugs = array( 'huevos' );
    } elseif ( str_starts_with( $url_path, '/exoticos/' ) ) {
        if ( str_contains( $url_path, '/pack-medallones-exoticos-' ) ) {
            $slugs = array( 'packs-y-lotes' );
        } elseif ( preg_match( '/burger meat|croqueta|finger|hamburguesa/u', $title ) ) {
            $slugs = array( 'carnes', 'elaborados-carnicos' );
        } else {
            $slugs = array( 'carnes', 'carnes-exoticas' );
        }
    } elseif ( str_starts_with( $url_path, '/premium/' ) ) {
        if ( preg_match( '/burger meat|hamburguesa/u', $title ) ) {
            $slugs = array( 'carnes', 'elaborados-carnicos' );
            if ( str_contains( $title, 'wagyu' ) ) { $slugs[] = 'wagyu'; }
        } elseif ( str_contains( $title, 'wagyu' ) ) {
            $slugs = array( 'carnes', 'wagyu' );
        } elseif ( preg_match( '/black angus|cebon/u', $title ) ) {
            $slugs = array( 'carnes' );
        }
    }
    if ( ! $slugs ) { throw new RuntimeException( 'Producto sin clasificación inequívoca: ' . $url ); }
    $category_ids = array_map( fn( $slug ) => $ids[ $slug ], $slugs );
    $result = wp_set_post_terms( $product_id, $category_ids, 'product_cat', false );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    update_post_meta( $product_id, '_emdo_latitudes_category_v1', '20261015' );
    foreach ( $slugs as $slug ) { $counts[ $slug ] = ( $counts[ $slug ] ?? 0 ) + 1; }
    $out[] = array( 'source_id' => (int) $r['id'], 'wc_id' => $product_id, 'category' => $slugs );
}
$verified = 0;
foreach ( $out as $r ) {
    $p = wc_get_product( $r['wc_id'] );
    $actual = array_map( 'intval', $p->get_category_ids() );
    $expected = array_map( fn( $slug ) => $ids[$slug], $r['category'] );
    sort( $actual ); sort( $expected );
    if ( $actual !== $expected || $p->get_status() !== 'draft' || $p->get_catalog_visibility() !== 'hidden' ) {
        throw new RuntimeException( 'No se conserva la clasificación/visibilidad del producto ' . $r['wc_id'] );
    }
    $verified++;
}
echo 'LATITUDES_CATEGORY_RESULTS=' . wp_json_encode( array(
    'total' => count( $rows ), 'verified' => $verified, 'created_at_most' => 3,
    'categories' => $counts, 'ids' => $ids, 'published' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
