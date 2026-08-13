<?php
/** Read-only inspection of ham reference metadata in production. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;

function emdo_matrix_terms( $id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $id, $taxonomy, array( 'fields'=>'names' ) );
    if ( is_wp_error( $names ) ) { return array(); }
    $names = array_values( array_unique( array_map( 'strval', (array) $names ) ) ); sort($names); return $names;
}

foreach ( array( 4508=>'Puente Robles', 4509=>'El Catedrático' ) as $author_id=>$vendor ) {
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT p.ID
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
         INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='product_cat'
         INNER JOIN {$wpdb->terms} t ON t.term_id=tt.term_id AND t.slug='jamones-paletas'
         WHERE p.post_type='product' AND p.post_author=%d AND p.post_status IN ('publish','archived')
         ORDER BY p.ID ASC",
        $author_id
    ), ARRAY_A );
    $matrix = array();
    foreach ( (array) $rows as $row ) {
        $id=(int)$row['ID'];
        $cats = wp_get_post_terms($id,'product_cat',array('fields'=>'slugs'));
        $cats = is_wp_error($cats)?array():array_values(array_map('strval',(array)$cats));
        if ( in_array('packs-y-lotes',$cats,true) ) { continue; }
        $key_data = array(
            'tipo-pieza'=>emdo_matrix_terms($id,'pa_tipo-pieza'),
            'calidad'=>emdo_matrix_terms($id,'pa_calidad'),
            'raza'=>emdo_matrix_terms($id,'pa_raza-iberica'),
            'alimentacion'=>emdo_matrix_terms($id,'pa_alimentacion'),
            'curacion'=>emdo_matrix_terms($id,'pa_curacion'),
            'origen'=>emdo_matrix_terms($id,'pa_origen'),
        );
        $key=wp_json_encode($key_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!isset($matrix[$key])){$matrix[$key]=array('count'=>0,'ids'=>array(),'data'=>$key_data);}
        ++$matrix[$key]['count']; $matrix[$key]['ids'][]=$id;
    }
    usort($matrix,function($a,$b){return $b['count']<=>$a['count'];});
    foreach($matrix as $entry){
        echo 'HAM_REFERENCE_MATRIX ' . wp_json_encode(array('vendor'=>$vendor,'count'=>$entry['count'],'sample_ids'=>array_slice($entry['ids'],0,8),'attrs'=>$entry['data']),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
    }
}

echo "FILTER_INSPECTION_DONE\n";
