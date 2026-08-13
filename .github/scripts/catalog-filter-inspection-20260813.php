<?php
/** Read-only inspection of remaining filter anomalies in production. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$target_ids = array( 1363,1370,11707,11940,11943,11946,11958,11972,11991,12060,12077,12098,12119,12149 );

function emdo_inspect_terms( $id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $id, $taxonomy, array( 'fields'=>'names' ) );
    return is_wp_error( $names ) ? array() : array_values( array_map( 'strval', (array) $names ) );
}
function emdo_inspect_text( $text, $limit = 2400 ) {
    $text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
    return mb_substr( $text, 0, $limit );
}
function emdo_inspect_product( $id, $reason ) {
    $product = wc_get_product( (int) $id );
    if ( ! $product instanceof WC_Product ) { return; }
    $meta = get_post_meta( (int) $id );
    $source_meta = array();
    foreach ( (array) $meta as $key => $values ) {
        $lk = strtolower( (string) $key );
        if ( str_contains( $lk, 'source' ) || str_contains( $lk, 'supplier' ) || str_contains( $lk, 'url' ) ) {
            $vals = array();
            foreach ( (array) $values as $value ) {
                if ( is_string( $value ) && strlen( $value ) < 1200 ) { $vals[] = $value; }
            }
            if ( $vals ) { $source_meta[$key] = array_slice( $vals, 0, 3 ); }
        }
    }
    $categories = wp_get_post_terms( (int) $id, 'product_cat', array( 'fields'=>'slugs' ) );
    $categories = is_wp_error( $categories ) ? array() : array_values( array_map( 'strval', (array) $categories ) );
    echo 'FILTER_INSPECTION ' . wp_json_encode( array(
        'id'=>(int)$id,
        'reason'=>$reason,
        'author'=>(int)get_post_field( 'post_author', (int)$id ),
        'status'=>(string)get_post_status( (int)$id ),
        'title'=>(string)$product->get_name( 'edit' ),
        'categories'=>$categories,
        'short_description'=>emdo_inspect_text( $product->get_short_description( 'edit' ), 1800 ),
        'description'=>emdo_inspect_text( $product->get_description( 'edit' ), 3200 ),
        'attrs'=>array_filter( array(
            'tipo-pieza'=>emdo_inspect_terms($id,'pa_tipo-pieza'),
            'calidad'=>emdo_inspect_terms($id,'pa_calidad'),
            'raza'=>emdo_inspect_terms($id,'pa_raza-iberica'),
            'alimentacion'=>emdo_inspect_terms($id,'pa_alimentacion'),
            'con-dop'=>emdo_inspect_terms($id,'pa_con-dop'),
            'dop'=>emdo_inspect_terms($id,'pa_dop'),
            'origen'=>emdo_inspect_terms($id,'pa_origen'),
            'preparacion'=>emdo_inspect_terms($id,'pa_preparacion'),
            'curacion'=>emdo_inspect_terms($id,'pa_curacion'),
            'productor'=>emdo_inspect_terms($id,'pa_productor'),
            'tipo-producto'=>emdo_inspect_terms($id,'pa_tipo-producto'),
        ) ),
        'source_meta'=>$source_meta,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}

foreach ( $target_ids as $id ) { emdo_inspect_product( $id, 'hard-error' ); }

// Also inspect all intended ham products with missing Curación.
$rows = $wpdb->get_results(
    "SELECT p.ID, p.post_author, p.post_status
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
     INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='product_cat'
     INNER JOIN {$wpdb->terms} t ON t.term_id=tt.term_id AND t.slug='jamones-paletas'
     WHERE p.post_type='product'
       AND ((p.post_author IN (3,6,4507) AND p.post_status='publish') OR (p.post_author IN (4508,4509) AND p.post_status IN ('publish','archived')))
     GROUP BY p.ID
     ORDER BY p.ID ASC",
    ARRAY_A
);
foreach ( (array) $rows as $row ) {
    $id = (int) $row['ID'];
    if ( ! emdo_inspect_terms( $id, 'pa_curacion' ) ) {
        if ( ! in_array( $id, $target_ids, true ) ) { emdo_inspect_product( $id, 'missing-curacion' ); }
    }
}

echo "FILTER_INSPECTION_DONE\n";
