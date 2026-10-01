<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
if ( ! taxonomy_exists( 'product_cat' ) || ! function_exists( 'wc_get_product' ) ) {
    throw new Exception('WooCommerce product categories unavailable.');
}

$supplier_id = 0;
if ( class_exists( 'MDO_Supplier_Repository' ) ) {
    foreach ( MDO_Supplier_Repository::all() as $supplier ) {
        if ( 'selectos-de-castilla' === (string)($supplier['connector'] ?? '')
            || 'selectos-de-castilla' === (string)($supplier['code'] ?? '')
            || 'Selectos de Castilla' === (string)($supplier['name'] ?? '') ) {
            $supplier_id = (int)$supplier['id'];
            break;
        }
    }
}
if ( $supplier_id <= 0 ) { $supplier_id = 5; }

$published = get_posts(array(
    'post_type'=>'product',
    'post_status'=>'publish',
    'posts_per_page'=>-1,
    'fields'=>'ids',
    'meta_key'=>'_emdo_supplier_id',
    'meta_value'=>$supplier_id,
    'orderby'=>'ID',
    'order'=>'ASC',
));
if ( count($published) < 70 ) {
    throw new Exception('Selectos published catalog unexpectedly small: '.count($published));
}

$ensure_term = static function(string $name,string $slug,int $parent=0,string $description=''): WP_Term {
    $term=get_term_by('slug',$slug,'product_cat');
    $args=array('name'=>$name,'slug'=>$slug,'parent'=>$parent);
    if($description!=='') $args['description']=$description;
    if($term instanceof WP_Term){
        $r=wp_update_term((int)$term->term_id,'product_cat',$args);
        if(is_wp_error($r)) throw new Exception($r->get_error_message());
        $term=get_term((int)$term->term_id,'product_cat');
        if(!$term instanceof WP_Term) throw new Exception('Could not reload category '.$slug);
        return $term;
    }
    $r=wp_insert_term($name,'product_cat',$args);
    if(is_wp_error($r)) throw new Exception($r->get_error_message());
    $term=get_term((int)$r['term_id'],'product_cat');
    if(!$term instanceof WP_Term) throw new Exception('Could not create category '.$slug);
    return $term;
};

$carnes=get_term_by('slug','carnes','product_cat');
$packs=get_term_by('slug','packs-y-lotes','product_cat');
$pato=get_term_by('slug','pato','product_cat');
$confit_pato=get_term_by('slug','confit-de-pato','product_cat');
if(!$carnes instanceof WP_Term || !$packs instanceof WP_Term || !$pato instanceof WP_Term || !$confit_pato instanceof WP_Term){
    throw new Exception('Required existing categories are missing.');
}

$cochinillo=$ensure_term(
    'Cochinillo','cochinillo',(int)$carnes->term_id,
    '<p>Productos de cochinillo de Selectos de Castilla, con patés y elaboraciones confitadas listas para servir o terminar en casa.</p>'
);
$lechazo=$ensure_term(
    'Lechazo','lechazo',(int)$carnes->term_id,
    '<p>Elaboraciones de lechazo de Selectos de Castilla, como patés y rillettes de lechazo churro.</p>'
);
$otras_aves=$ensure_term(
    'Otras aves','otras-aves',(int)$carnes->term_id,
    '<p>Especialidades de otras aves de Selectos de Castilla, incluyendo avestruz, oca y codorniz.</p>'
);
$pescados=$ensure_term(
    'Pescados y mariscos','pescados-mariscos',0,
    '<p>Selección de productos de pescado y marisco de productores especializados.</p>'
);
$trucha=$ensure_term(
    'Trucha','trucha',(int)$pescados->term_id,
    '<p>Productos de trucha de Selectos de Castilla, con huevas y mousses de trucha de la Montaña Palentina.</p>'
);

$new_terms=array($cochinillo,$lechazo,$otras_aves,$pescados,$trucha);
foreach($new_terms as $term){
    update_term_meta((int)$term->term_id,'display_type','products');
    update_term_meta((int)$term->term_id,'_emdo_selectos_category_setup','20261001');
}

$map=array(
    16197=>array('pescados-mariscos','trucha'),
    16203=>array('packs-y-lotes'),
    16288=>array('otras-aves'),
    16340=>array('packs-y-lotes'),
    16342=>array('pescados-mariscos','trucha'),
    16360=>array('otras-aves'),
    16384=>array('packs-y-lotes'),
    16463=>array('packs-y-lotes'),
    16467=>array('cochinillo'),
    16499=>array('otras-aves'),
    16524=>array('otras-aves'),
    16548=>array('cochinillo'),
    16554=>array('cochinillo'),
    16586=>array('cochinillo'),
    16591=>array('lechazo'),
    16624=>array('otras-aves'),
    16628=>array('otras-aves'),
    16665=>array('lechazo'),
    16669=>array('packs-y-lotes'),
    16678=>array('packs-y-lotes'),
    16726=>array('packs-y-lotes'),
    16739=>array('pescados-mariscos','trucha'),
    16756=>array('packs-y-lotes'),
    16763=>array('packs-y-lotes'),
    16769=>array('pescados-mariscos','trucha'),
    16782=>array('packs-y-lotes'),
    16816=>array('pato','confit-de-pato'),
    16828=>array('packs-y-lotes'),
    16836=>array('packs-y-lotes'),
);

$term_by_slug=array();
$all_terms=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
if(is_wp_error($all_terms)) throw new Exception($all_terms->get_error_message());
foreach($all_terms as $term){
    if($term instanceof WP_Term) $term_by_slug[$term->slug]=$term;
}
foreach(array('cochinillo','lechazo','otras-aves','pescados-mariscos','trucha','packs-y-lotes','pato','confit-de-pato') as $slug){
    if(!isset($term_by_slug[$slug])) throw new Exception('Missing target category '.$slug);
}

$mapped=0;
$mentta_removed=0;
$uncategorized_removed=0;
$rows=array();

foreach($published as $id){
    $id=(int)$id;
    $current=wp_get_post_terms($id,'product_cat');
    if(is_wp_error($current)) throw new Exception($current->get_error_message());

    if(''===(string)get_post_meta($id,'_emdo_selectos_categories_backup_20261001',true)){
        update_post_meta($id,'_emdo_selectos_categories_backup_20261001',wp_json_encode(array_map(
            static fn(WP_Term $t): int => (int)$t->term_id,
            array_filter($current,static fn($t)=>$t instanceof WP_Term)
        )));
    }

    if(isset($map[$id])){
        $target_ids=array();
        foreach($map[$id] as $slug) $target_ids[]=(int)$term_by_slug[$slug]->term_id;
        $r=wp_set_object_terms($id,array_values(array_unique($target_ids)),'product_cat',false);
        if(is_wp_error($r)) throw new Exception('Failed mapping product '.$id.': '.$r->get_error_message());
        $mapped++;
    } else {
        $keep=array();
        foreach($current as $term){
            if(!$term instanceof WP_Term) continue;
            if(in_array($term->slug,array('sin-categorizar','uncategorized'),true)){
                $uncategorized_removed++;
                continue;
            }
            if('mentta'===$term->slug || str_starts_with($term->slug,'mentta-')){
                $mentta_removed++;
                continue;
            }
            $keep[]=(int)$term->term_id;
        }
        if(!$keep){
            throw new Exception('Published Selectos product would be left without a category: '.$id.' '.get_the_title($id));
        }
        $r=wp_set_object_terms($id,array_values(array_unique($keep)),'product_cat',false);
        if(is_wp_error($r)) throw new Exception('Failed cleaning product '.$id.': '.$r->get_error_message());
    }

    delete_post_meta($id,'_emdo_auto_category_attempted');
    delete_post_meta($id,'_emdo_auto_category_reason');
    delete_post_meta($id,'_emdo_auto_category_ids');
    delete_post_meta($id,'_emdo_auto_category_score');
    clean_post_cache($id);
}

$thumb_sources=array(
    'cochinillo'=>16467,
    'lechazo'=>16591,
    'otras-aves'=>16288,
    'pescados-mariscos'=>16197,
    'trucha'=>16197,
);
foreach($thumb_sources as $slug=>$pid){
    $thumb=(int)get_post_thumbnail_id((int)$pid);
    if($thumb>0 && isset($term_by_slug[$slug])){
        update_term_meta((int)$term_by_slug[$slug]->term_id,'thumbnail_id',$thumb);
    }
}

$uncategorized_after=0;
$mentta_after=0;
$missing_after=0;
$wrong_map=array();
$category_counts=array();

foreach($published as $id){
    $id=(int)$id;
    $terms=wp_get_post_terms($id,'product_cat');
    if(is_wp_error($terms)) throw new Exception($terms->get_error_message());
    $slugs=array_values(array_map(static fn(WP_Term $t): string => $t->slug,$terms));
    if(!$slugs) $missing_after++;
    foreach($slugs as $slug){
        if(in_array($slug,array('sin-categorizar','uncategorized'),true)) $uncategorized_after++;
        if('mentta'===$slug || str_starts_with($slug,'mentta-')) $mentta_after++;
        $category_counts[$slug]=($category_counts[$slug]??0)+1;
    }
    if(isset($map[$id])){
        $expected=$map[$id];
        $a=$slugs; $b=$expected; sort($a); sort($b);
        if($a!==$b) $wrong_map[]=array('id'=>$id,'title'=>get_the_title($id),'expected'=>$expected,'actual'=>$slugs);
    }
    $rows[]=array(
        'id'=>$id,
        'title'=>get_the_title($id),
        'categories'=>$slugs,
        'url'=>get_permalink($id),
    );
}

ksort($category_counts);
if($mapped!==count($map)) throw new Exception('Mapped product count mismatch: '.$mapped);
if($uncategorized_after!==0) throw new Exception('Uncategorized assignments remain: '.$uncategorized_after);
if($mentta_after!==0) throw new Exception('MENTTA assignments remain on Selectos products: '.$mentta_after);
if($missing_after!==0) throw new Exception('Published Selectos products without categories: '.$missing_after);
if($wrong_map) throw new Exception('Explicit category mapping verification failed.');

clean_term_cache(array_map(static fn(WP_Term $t): int => (int)$t->term_id,$new_terms),'product_cat');
flush_rewrite_rules(false);

echo wp_json_encode(array(
    'batch'=>'20261001-selectos-categories',
    'supplier_id'=>$supplier_id,
    'published_products'=>count($published),
    'explicitly_mapped'=>$mapped,
    'mentta_removed'=>$mentta_removed,
    'uncategorized_removed'=>$uncategorized_removed,
    'uncategorized_after'=>$uncategorized_after,
    'mentta_after'=>$mentta_after,
    'missing_after'=>$missing_after,
    'new_categories'=>array_map(static fn(WP_Term $t): array => array(
        'id'=>(int)$t->term_id,
        'name'=>$t->name,
        'slug'=>$t->slug,
        'parent'=>(int)$t->parent,
        'url'=>is_wp_error(get_term_link($t))?'':get_term_link($t),
    ),$new_terms),
    'category_counts'=>$category_counts,
    'rows'=>$rows,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
