<?php
/**
 * Targeted final production metadata repair for the intended catalog.
 * Uses existing taxonomies/terms only. Does not create categories or terms,
 * does not change product categories, status, visibility, prices or content.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;

function emdo_repair_existing_term_ids( $taxonomy, array $names ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { throw new RuntimeException( 'Missing taxonomy ' . $taxonomy ); }
    $ids = array();
    foreach ( $names as $name ) {
        $term = get_term_by( 'name', (string) $name, $taxonomy );
        if ( ! $term instanceof WP_Term ) { throw new RuntimeException( 'Missing existing term ' . $taxonomy . ' / ' . $name ); }
        $ids[] = (int) $term->term_id;
    }
    return array_values( array_unique( $ids ) );
}
function emdo_repair_set_attr( $product_id, $taxonomy, array $names ) {
    $term_ids = emdo_repair_existing_term_ids( $taxonomy, $names );
    $result = wp_set_object_terms( (int) $product_id, $term_ids, $taxonomy, false );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    $attrs = get_post_meta( (int) $product_id, '_product_attributes', true );
    if ( ! is_array( $attrs ) ) { $attrs = array(); }
    $position = isset( $attrs[$taxonomy]['position'] ) ? (int) $attrs[$taxonomy]['position'] : count( $attrs );
    $attrs[$taxonomy] = array(
        'name'=>$taxonomy, 'value'=>'', 'position'=>$position,
        'is_visible'=>0, 'is_variation'=>0, 'is_taxonomy'=>1,
    );
    update_post_meta( (int) $product_id, '_product_attributes', $attrs );
}
function emdo_repair_clear_attr( $product_id, $taxonomy ) {
    if ( taxonomy_exists( $taxonomy ) ) {
        $result = wp_set_object_terms( (int) $product_id, array(), $taxonomy, false );
        if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    }
    $attrs = get_post_meta( (int) $product_id, '_product_attributes', true );
    if ( is_array( $attrs ) && isset( $attrs[$taxonomy] ) ) {
        unset( $attrs[$taxonomy] );
        update_post_meta( (int) $product_id, '_product_attributes', $attrs );
    }
}
function emdo_repair_terms( $product_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields'=>'names' ) );
    if ( is_wp_error( $names ) ) { return array(); }
    $names = array_values( array_unique( array_map( 'strval', (array) $names ) ) ); sort($names); return $names;
}
function emdo_repair_categories( $product_id ) {
    $slugs = wp_get_post_terms( (int) $product_id, 'product_cat', array( 'fields'=>'slugs' ) );
    if ( is_wp_error( $slugs ) ) { return array(); }
    $slugs = array_values( array_unique( array_map( 'strval', (array) $slugs ) ) ); sort($slugs); return $slugs;
}

$targets = array(
    1363 => array(
        'author'=>6,'status'=>'publish','title'=>'Jamón de bellota 100% Ibérico en sobres (brida negra)',
        'set'=>array( 'pa_con-dop'=>array('No') ), 'clear'=>array('pa_dop'),
    ),
    1370 => array(
        'author'=>6,'status'=>'publish','title'=>'Paleta de bellota 100% Ibérica en sobres de 10uds (brida negra)',
        'set'=>array( 'pa_con-dop'=>array('No') ), 'clear'=>array('pa_dop'),
    ),
    5080 => array(
        'author'=>6,'status'=>'publish','title'=>'Pack de cata de bellota 100% ibérico (2x jamón, 2x paleta, 2x lomo, 2x salchichón, 2x chorizo)',
        'set'=>array( 'pa_curacion'=>array('24–36 meses') ),
    ),
    11940 => array(
        'author'=>4508,'status'=>'archived','title'=>'Lote ibérico al corte',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Arribes del Duero'), 'pa_curacion'=>array('36–48 meses') ),
    ),
    11943 => array(
        'author'=>4508,'status'=>'archived','title'=>'Pack degustación puente robles',
        'set'=>array(
            'pa_tipo-pieza'=>array('Jamón','Paleta'),
            'pa_calidad'=>array('Bellota 100% ibérico','Cebo de campo ibérico'),
            'pa_raza-iberica'=>array('100% ibérico','50% ibérico'),
            'pa_alimentacion'=>array('Bellota','Cebo de campo'),
            'pa_origen'=>array('Arribes del Duero'),
            'pa_curacion'=>array('24–36 meses','36–48 meses'),
        ),
    ),
    11946 => array(
        'author'=>4508,'status'=>'archived','title'=>'Lote ibérico',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Arribes del Duero'), 'pa_curacion'=>array('36–48 meses') ),
    ),
    11958 => array(
        'author'=>4508,'status'=>'archived','title'=>'Lote bellota ibérica',
        'set'=>array( 'pa_tipo-pieza'=>array('Paleta'), 'pa_origen'=>array('Arribes del Duero'), 'pa_curacion'=>array('24–36 meses') ),
    ),
    11972 => array(
        'author'=>4508,'status'=>'archived','title'=>'Lote raza ibérica',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Arribes del Duero'), 'pa_curacion'=>array('36–48 meses') ),
    ),
    11991 => array(
        'author'=>4509,'status'=>'archived','title'=>'Loncheados degustación',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Salamanca'), 'pa_curacion'=>array('36–48 meses') ),
    ),
    12060 => array(
        'author'=>4509,'status'=>'archived','title'=>'Catedrático degustación',
        'set'=>array( 'pa_tipo-pieza'=>array('Paleta'), 'pa_origen'=>array('Salamanca'), 'pa_curacion'=>array('24–36 meses') ),
    ),
    12077 => array(
        'author'=>4509,'status'=>'archived','title'=>'Pack sabor artesano',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Salamanca'), 'pa_curacion'=>array('24–36 meses') ),
    ),
    12098 => array(
        'author'=>4509,'status'=>'archived','title'=>'El catedrático selección',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Salamanca'), 'pa_curacion'=>array('36–48 meses') ),
    ),
    12119 => array(
        'author'=>4509,'status'=>'archived','title'=>'Universidad ibérica',
        'set'=>array( 'pa_tipo-pieza'=>array('Jamón'), 'pa_origen'=>array('Salamanca'), 'pa_curacion'=>array('36–48 meses') ),
    ),
    12149 => array(
        'author'=>4509,'status'=>'archived','title'=>'El catedrático gourmet',
        'set'=>array( 'pa_tipo-pieza'=>array('Paleta'), 'pa_origen'=>array('Salamanca'), 'pa_curacion'=>array('24–36 meses') ),
    ),
);

$category_count_before = (int) wp_count_terms( array( 'taxonomy'=>'product_cat', 'hide_empty'=>false ) );
$taxonomy_counts_before = array();
foreach ( array('pa_tipo-pieza','pa_calidad','pa_raza-iberica','pa_alimentacion','pa_con-dop','pa_dop','pa_origen','pa_curacion') as $taxonomy ) {
    if ( taxonomy_exists($taxonomy) ) { $taxonomy_counts_before[$taxonomy]=(int)wp_count_terms(array('taxonomy'=>$taxonomy,'hide_empty'=>false)); }
}
$results = array();

foreach ( $targets as $product_id=>$cfg ) {
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT post_author,post_status,post_title FROM {$wpdb->posts} WHERE ID=%d AND post_type='product'", $product_id ), ARRAY_A );
    if ( ! is_array($row) || (int)$row['post_author'] !== (int)$cfg['author'] || (string)$row['post_status'] !== (string)$cfg['status'] || (string)$row['post_title'] !== (string)$cfg['title'] ) {
        throw new RuntimeException( 'Identity/status/title mismatch for product ' . $product_id );
    }
    $categories_before = emdo_repair_categories($product_id);
    foreach ( (array)($cfg['clear'] ?? array()) as $taxonomy ) { emdo_repair_clear_attr($product_id,$taxonomy); }
    foreach ( (array)($cfg['set'] ?? array()) as $taxonomy=>$names ) { emdo_repair_set_attr($product_id,$taxonomy,$names); }
    clean_post_cache($product_id); wc_delete_product_transients($product_id);
    if ( (string)get_post_status($product_id) !== (string)$cfg['status'] ) { throw new RuntimeException('Status changed for '.$product_id); }
    $categories_after = emdo_repair_categories($product_id);
    if ( $categories_after !== $categories_before ) { throw new RuntimeException('Categories changed for '.$product_id); }
    $verified=array();
    foreach ( (array)($cfg['set'] ?? array()) as $taxonomy=>$names ) {
        $actual=emdo_repair_terms($product_id,$taxonomy); $expected=array_values(array_unique(array_map('strval',$names))); sort($expected);
        if($actual!==$expected){throw new RuntimeException('Verification failed '.$product_id.' '.$taxonomy.' expected='.wp_json_encode($expected).' actual='.wp_json_encode($actual));}
        $verified[$taxonomy]=$actual;
    }
    foreach ( (array)($cfg['clear'] ?? array()) as $taxonomy ) {
        if(emdo_repair_terms($product_id,$taxonomy)){throw new RuntimeException('Clear verification failed '.$product_id.' '.$taxonomy);}
        $verified[$taxonomy]=array();
    }
    $results[]=array('id'=>$product_id,'title'=>$cfg['title'],'status'=>$cfg['status'],'categories'=>$categories_after,'verified'=>$verified);
}

$category_count_after = (int) wp_count_terms( array( 'taxonomy'=>'product_cat', 'hide_empty'=>false ) );
if($category_count_after!==$category_count_before){throw new RuntimeException('Product category count changed');}
foreach($taxonomy_counts_before as $taxonomy=>$before){
    $after=(int)wp_count_terms(array('taxonomy'=>$taxonomy,'hide_empty'=>false));
    if($after!==$before){throw new RuntimeException('Taxonomy term count changed for '.$taxonomy.' '.$before.' -> '.$after);}
}
foreach($results as $result){echo 'FILTER_REPAIR_PRODUCT '.wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";}
echo 'FILTER_REPAIR_SUMMARY '.wp_json_encode(array('updated'=>count($results),'category_count_before'=>$category_count_before,'category_count_after'=>$category_count_after,'created_categories'=>0,'created_terms'=>0),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
