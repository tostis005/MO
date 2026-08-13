<?php
/**
 * Guarded production catalog normalization for Hidalgo de la Jara.
 * The approved About copy is already live; this script only repairs/normalizes
 * published, catalog-visible products owned directly by user 6.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$user_id = 6;
$user = get_userdata( $user_id );
$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
if ( ! $user instanceof WP_User || ! is_array( $settings ) ||
     'Hidalgo de la Jara' !== (string) ( $settings['store_name'] ?? '' ) ||
     'hidalgo-de-la-jara' !== (string) ( $settings['store_slug'] ?? '' ) ) {
    fwrite( STDERR, "HIDALGO_CATALOG_ABORT: producer identity mismatch\n" );
    exit( 3 );
}

$required = array(
    'ham'   => 'jamones-paletas',
    'cured' => 'embutidos-y-curados',
    'packs' => 'packs-y-lotes',
);
$cat = array();
foreach ( $required as $key => $slug ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) {
        fwrite( STDERR, 'HIDALGO_CATALOG_ABORT: existing category missing: ' . $slug . "\n" );
        exit( 4 );
    }
    $cat[ $key ] = (int) $term->term_id;
}

if ( ! class_exists( 'MDO_Ham_Taxonomy' ) || ! class_exists( 'MDO_Cured_Catalog' ) || ! class_exists( 'MDO_Cured_Producer' ) ) {
    fwrite( STDERR, "HIDALGO_CATALOG_ABORT: required classifiers unavailable\n" );
    exit( 5 );
}

function emdo_hidalgo_norm( $text ) {
    $text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = remove_accents( strtolower( $text ) );
    return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}
function emdo_hidalgo_terms( $product_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields' => 'names' ) );
    return is_wp_error( $names ) ? array() : array_values( array_map( 'strval', $names ) );
}
function emdo_hidalgo_set_categories( $product_id, array $ids ) {
    $result = wp_set_object_terms( (int) $product_id, array_values( array_unique( array_map( 'intval', $ids ) ) ), 'product_cat', false );
    if ( is_wp_error( $result ) ) {
        throw new RuntimeException( $result->get_error_message() );
    }
    clean_post_cache( (int) $product_id );
    wc_delete_product_transients( (int) $product_id );
}

// Direct SQL is intentional: WCFM hooks altered WP_Query author filtering in a
// prior maintenance pass. post_author is the canonical ownership field here.
$product_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
     ORDER BY ID ASC",
    $user_id
) );

$processed = 0;
$hidden = 0;
$errors = array();
foreach ( array_map( 'intval', (array) $product_ids ) as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) { continue; }
    if ( 'hidden' === $product->get_catalog_visibility() ) { ++$hidden; continue; }

    $title = emdo_hidalgo_norm( $product->get_name( 'edit' ) );
    $family = '';
    try {
        if ( preg_match( '/\b(?:pack|surtido|lote|degustacion|cata)\b/u', $title ) ) {
            $family = 'pack';
            emdo_hidalgo_set_categories( $product_id, array( $cat['packs'] ) );
            $pack_result = MDO_Cured_Catalog::classify_product( $product_id );
            if ( empty( $pack_result['target'] ) || empty( $pack_result['pack'] ) ) {
                throw new RuntimeException( 'pack classifier did not recognize product' );
            }
            // If the pack contains ham/paleta, also enrich the ham filter layer.
            if ( preg_match( '/\b(?:jamon|paleta)\b/u', $title ) ) {
                MDO_Ham_Taxonomy::classify_product( $product_id );
            }
            if ( ! empty( $pack_result['pack_with_cured'] ) ) {
                MDO_Cured_Producer::sync_after_save( wc_get_product( $product_id ) );
            }
        } elseif ( preg_match( '/\b(?:jamon|paleta)\b/u', $title ) ) {
            $family = 'ham';
            emdo_hidalgo_set_categories( $product_id, array( $cat['ham'] ) );
            if ( ! MDO_Ham_Taxonomy::classify_product( $product_id ) ) {
                throw new RuntimeException( 'ham classifier did not recognize product' );
            }
            if ( class_exists( 'MDO_Ham_Catalog_Final' ) ) { MDO_Ham_Catalog_Final::finalize_product( $product_id ); }
            if ( class_exists( 'MDO_Ham_Catalog_Precision' ) ) { MDO_Ham_Catalog_Precision::precision_product( $product_id ); }
            if ( class_exists( 'MDO_Ham_Catalog_Canonical_Closure' ) ) { MDO_Ham_Catalog_Canonical_Closure::canonicalize_product( $product_id ); }
            if ( class_exists( 'MDO_Ham_Catalog_Direct_Closure' ) ) { MDO_Ham_Catalog_Direct_Closure::close_product( $product_id ); }
        } elseif ( preg_match( '/\b(?:lomo|lomito|salchichon|chorizo|morcon|sobrasada|cecina)\b/u', $title ) ) {
            $family = 'cured';
            emdo_hidalgo_set_categories( $product_id, array( $cat['cured'] ) );
            $cured_result = MDO_Cured_Catalog::classify_product( $product_id );
            if ( empty( $cured_result['target'] ) || empty( $cured_result['individual_cured'] ) ) {
                throw new RuntimeException( 'cured classifier did not recognize product' );
            }
            MDO_Cured_Producer::sync_after_save( wc_get_product( $product_id ) );
        } else {
            throw new RuntimeException( 'no safe family inferred from title' );
        }

        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
        if ( is_wp_error( $categories ) || ! $categories || in_array( 'aceites', (array) $categories, true ) || in_array( 'sin-categorizar', (array) $categories, true ) ) {
            throw new RuntimeException( 'invalid final category set' );
        }

        $filters = array(
            'tipo-pieza'    => emdo_hidalgo_terms( $product_id, 'pa_tipo-pieza' ),
            'calidad'       => emdo_hidalgo_terms( $product_id, 'pa_calidad' ),
            'raza-iberica'  => emdo_hidalgo_terms( $product_id, 'pa_raza-iberica' ),
            'alimentacion'  => emdo_hidalgo_terms( $product_id, 'pa_alimentacion' ),
            'con-dop'       => emdo_hidalgo_terms( $product_id, 'pa_con-dop' ),
            'dop'           => emdo_hidalgo_terms( $product_id, 'pa_dop' ),
            'origen'        => emdo_hidalgo_terms( $product_id, 'pa_origen' ),
            'preparacion'   => emdo_hidalgo_terms( $product_id, 'pa_preparacion' ),
            'rango-peso'    => emdo_hidalgo_terms( $product_id, 'pa_rango-peso' ),
            'curacion'      => emdo_hidalgo_terms( $product_id, 'pa_curacion' ),
            'productor'     => emdo_hidalgo_terms( $product_id, 'pa_productor' ),
            'tipo-producto' => emdo_hidalgo_terms( $product_id, 'pa_tipo-producto' ),
        );
        if ( 'ham' === $family && ( empty( $filters['tipo-pieza'] ) || empty( $filters['con-dop'] ) || ! in_array( 'Hidalgo de la Jara', $filters['productor'], true ) ) ) {
            throw new RuntimeException( 'core ham filters incomplete' );
        }
        if ( 'cured' === $family && ( empty( $filters['tipo-producto'] ) || empty( $filters['preparacion'] ) || ! in_array( 'Hidalgo de la Jara', $filters['productor'], true ) ) ) {
            throw new RuntimeException( 'core cured filters incomplete' );
        }

        ++$processed;
        echo 'HIDALGO_CATALOG_PRODUCT ' . wp_json_encode( array(
            'id' => $product_id,
            'name' => $product->get_name( 'edit' ),
            'family' => $family,
            'categories' => array_values( (array) $categories ),
            'filters' => array_filter( $filters ),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    } catch ( Throwable $error ) {
        $errors[] = array( 'id' => $product_id, 'name' => $product->get_name( 'edit' ), 'family' => $family, 'message' => $error->getMessage() );
    }
}

foreach ( $errors as $error ) {
    fwrite( STDERR, 'HIDALGO_CATALOG_ERROR ' . wp_json_encode( $error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
}
echo 'HIDALGO_CATALOG_SUMMARY ' . wp_json_encode( array(
    'owner_id' => $user_id,
    'direct_sql_products' => count( $product_ids ),
    'processed_visible' => $processed,
    'skipped_hidden' => $hidden,
    'errors' => count( $errors ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "__HIDALGO_UPDATE__=already_applied\n";

if ( $errors ) { exit( 6 ); }
