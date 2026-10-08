<?php
/**
 * One-off, idempotent production task (2026-10-08):
 * Montjam 14264 non-DOP black label, option 2 prices + a Mentta-only two-weight duplicate.
 */
if ( ! defined('ABSPATH') || ! defined('WP_CLI') || ! WP_CLI || ! class_exists('WC_Product_Variable') ) {
    fwrite(STDERR, "ABORT: WP-CLI and WooCommerce required\n"); exit(2);
}

const EMDO_MJ_SOURCE = 14264;
const EMDO_MJ_SLUG = 'jamon-de-bellota-100-iberico-montjam-mentta';
$price_map = array(
    '6-65-kg' => '279.297',
    '65-7-kg' => '301.641',
    '7-75-kg' => '315.810',
    '75-8-kg' => '337.590',
    '8-85-kg' => '359.370',
    '85-9-kg' => '381.150',
    '9-95-kg' => '402.930',
);
$short_map = array('6-65-kg' => '600-650', '65-7-kg' => '650-700');
$source = wc_get_product(EMDO_MJ_SOURCE);
if (!$source || !$source->is_type('variable') || $source->get_status() !== 'publish'
    || stripos($source->get_name(),'Montjam') === false
    || stripos($source->get_name(),'D.O.P.') !== false) {
    fwrite(STDERR, "ABORT: source product identity/status mismatch\n"); exit(3);
}
$producer = wp_get_object_terms(EMDO_MJ_SOURCE,'pa_productor',array('fields'=>'slugs'));
if (is_wp_error($producer) || !in_array('montjam',$producer,true)) {
    fwrite(STDERR, "ABORT: product is not associated with Montjam\n"); exit(4);
}
$mentta = get_term_by('slug','mentta','product_cat');
if (!$mentta || is_wp_error($mentta)) {
    fwrite(STDERR, "ABORT: Mentta category missing\n"); exit(5);
}
$original = array();
foreach ($source->get_children() as $vid) {
    $v = wc_get_product($vid);
    if (!$v || !$v->is_type('variation') || $v->get_status() !== 'publish') { continue; }
    $slug = (string) ($v->get_attributes()['pa_tamano'] ?? '');
    if (!$slug || isset($original[$slug])) {
        fwrite(STDERR,"ABORT: ambiguous source variation sizes\n"); exit(6);
    }
    $original[$slug] = $v;
}
$expect = array_keys($price_map);
$found = array_keys($original);
sort($expect); sort($found);
if ($expect !== $found) {
    fwrite(STDERR,"ABORT: source weight set differs: ".wp_json_encode($found)."\n"); exit(7);
}
$attrs = $source->get_attributes();
if (!isset($attrs['pa_tamano']) || !$attrs['pa_tamano'] instanceof WC_Product_Attribute) {
    fwrite(STDERR,"ABORT: source missing global weight attribute\n"); exit(8);
}
$term_ids = array();
foreach (array_keys($short_map) as $weight) {
    $term = get_term_by('slug',$weight,'pa_tamano');
    if (!$term) { fwrite(STDERR,"ABORT: missing weight term $weight\n"); exit(9); }
    $term_ids[$weight] = (int)$term->term_id;
}
$existing = get_posts(array(
    'post_type'=>'product','post_status'=>array('publish','draft','private','pending'),
    'meta_key'=>'_emdo_mentta_source_id','meta_value'=>(string)EMDO_MJ_SOURCE,
    'fields'=>'ids','posts_per_page'=>10,'suppress_filters'=>true,
));
if (count($existing) > 1) {
    fwrite(STDERR,"ABORT: multiple Mentta-only duplicates already exist\n"); exit(10);
}
$copy_id = $existing ? (int)$existing[0] : 0;
if ($copy_id) {
    $prior = wc_get_product($copy_id);
    if (!$prior || !$prior->is_type('variable') || $prior->get_slug() !== EMDO_MJ_SLUG) {
        fwrite(STDERR,"ABORT: unexpected existing duplicate\n"); exit(11);
    }
} elseif (get_page_by_path(EMDO_MJ_SLUG,OBJECT,'product')) {
    fwrite(STDERR,"ABORT: Mentta-only slug already in use\n"); exit(12);
}
foreach (array('MONTJAM-JN-MENTTA','MONTJAM-JN-MENTTA-600-650','MONTJAM-JN-MENTTA-650-700') as $sku) {
    $owner = wc_get_product_id_by_sku($sku);
    if ($owner && !$copy_id) {
        fwrite(STDERR,"ABORT: existing SKU collision $sku\n"); exit(13);
    }
    if ($owner && $copy_id && $owner !== $copy_id && (int)get_post_field('post_parent',$owner) !== $copy_id) {
        fwrite(STDERR,"ABORT: unrelated SKU collision $sku\n"); exit(14);
    }
}
$source_author = (int)get_post_field('post_author',EMDO_MJ_SOURCE);
if (!$source_author) { fwrite(STDERR,"ABORT: source vendor author missing\n"); exit(15); }

/** Duplicate the source YITH Format select, including its unchanged options, for the new product. */
function emdo_mj_duplicate_format($from,$to) {
    global $wpdb;
    $bt=$wpdb->prefix.'yith_wapo_blocks';
    $at=$wpdb->prefix.'yith_wapo_addons';
    $jt=$wpdb->prefix.'yith_wapo_blocks_assoc';
    foreach (array($bt,$at,$jt) as $tbl) {
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$tbl)) !== $tbl) {
            throw new RuntimeException('YITH table missing: '.$tbl);
        }
    }
    $source_name='Montjam · Formato · '.$from;
    $copy_name='Montjam · Formato · '.$to;
    $origin=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$bt` WHERE name=%s LIMIT 1",$source_name),ARRAY_A);
    if (!$origin) { throw new RuntimeException('Source Format block missing'); }
    $old_id=(int)$origin['id'];
    $target_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `$bt` WHERE name=%s LIMIT 1",$copy_name));
    if (!$target_id) {
        unset($origin['id']);
        $origin['name']=$copy_name;
        $settings=maybe_unserialize($origin['settings']);
        if (!is_array($settings)) { throw new RuntimeException('Bad source YITH settings'); }
        $settings['name']=$copy_name;
        if (!isset($settings['rules']) || !is_array($settings['rules'])) { $settings['rules']=array(); }
        $settings['rules']['show_in_products']=array((string)$to);
        $origin['settings']=maybe_serialize($settings);
        $origin['creation_date']=current_time('mysql',true);
        $origin['last_update']=current_time('mysql',true);
        if (!$wpdb->insert($bt,$origin)) { throw new RuntimeException('YITH block duplication failed: '.$wpdb->last_error); }
        $target_id=(int)$wpdb->insert_id;
    }
    $association=$wpdb->get_row($wpdb->prepare("SELECT type FROM `$jt` WHERE rule_id=%d LIMIT 1",$old_id),ARRAY_A);
    $atype=(string)($association['type']??'product');
    $has_assoc=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$jt` WHERE rule_id=%d AND object=%s",$target_id,(string)$to));
    if (!$has_assoc && !$wpdb->insert($jt,array('rule_id'=>$target_id,'object'=>(string)$to,'type'=>$atype))) {
        throw new RuntimeException('YITH association duplication failed');
    }
    $origin_addons=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$at` WHERE block_id=%d ORDER BY id",$old_id),ARRAY_A);
    if (!$origin_addons) { throw new RuntimeException('Source Format addon missing'); }
    $target_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$at` WHERE block_id=%d",$target_id));
    if (!$target_count) {
        foreach ($origin_addons as $row) {
            unset($row['id']);
            $row['block_id']=$target_id;
            $row['creation_date']=current_time('mysql',true);
            $row['last_update']=current_time('mysql',true);
            if (!$wpdb->insert($at,$row)) { throw new RuntimeException('YITH addon duplication failed: '.$wpdb->last_error); }
        }
    } elseif ($target_count !== count($origin_addons)) {
        throw new RuntimeException('Existing duplicate Format block differs from source');
    }
    return $target_id;
}

$source_before=array();
foreach ($price_map as $size=>$p) {
    $v=$original[$size];
    $source_before[$v->get_id()]=array('regular'=>$v->get_regular_price('edit'),'sale'=>$v->get_sale_price('edit'));
}
$new_copy=false;
$copy_format_id=0;
try {
    // Update the original variation prices first, without changing descriptions, photos or stock.
    foreach ($price_map as $size=>$p) {
        $v=$original[$size];
        $v->set_regular_price($p);
        $v->set_sale_price('');
        $v->save();
    }
    WC_Product_Variable::sync(EMDO_MJ_SOURCE,true);

    if (!$copy_id) {
        require_once WC_ABSPATH.'includes/admin/class-wc-admin-duplicate-product.php';
        add_filter('woocommerce_duplicate_product_exclude_children','__return_true',100,2);
        try { $copy=(new WC_Admin_Duplicate_Product())->product_duplicate(wc_get_product(EMDO_MJ_SOURCE)); }
        finally { remove_filter('woocommerce_duplicate_product_exclude_children','__return_true',100); }
        $copy_id=(int)$copy->get_id();
        if (!$copy_id || !$copy->is_type('variable')) { throw new RuntimeException('Woo duplication failed'); }
        $new_copy=true;
        if ($copy->get_children()) { throw new RuntimeException('Woo duplicate unexpectedly copied children'); }
    } else {
        $copy=wc_get_product($copy_id);
    }

    // Keep a public REST-readable published product, but hide from all local product catalogs.
    $copy->set_name($source->get_name());
    $copy->set_slug(EMDO_MJ_SLUG);
    $copy->set_catalog_visibility('hidden');
    $copy->set_featured(false);
    $copy->set_category_ids(array((int)$mentta->term_id));
    $copy->set_sku('MONTJAM-JN-MENTTA');
    $copy->set_status('draft'); // never expose publicly until privacy guard is registered
    $copy->update_meta_data('_emdo_mentta_only','yes');
    $copy->update_meta_data('_emdo_mentta_source_id',(string)EMDO_MJ_SOURCE);
    $copy->update_meta_data('_yoast_wpseo_meta-robots-noindex','1');
    $copy->delete_meta_data('_yoast_wpseo_canonical');
    $cattrs=$copy->get_attributes();
    if (!isset($cattrs['pa_tamano']) || !$cattrs['pa_tamano'] instanceof WC_Product_Attribute) {
        throw new RuntimeException('Duplicated weight attribute missing');
    }
    $weight_attr=clone $cattrs['pa_tamano'];
    $weight_attr->set_options(array_values($term_ids));
    $weight_attr->set_variation(true);
    $cattrs['pa_tamano']=$weight_attr;
    $copy->set_attributes($cattrs);
    $copy->set_default_attributes(array('pa_tamano'=>'6-65-kg'));
    $copy->save();
    wp_update_post(array('ID'=>$copy_id,'post_author'=>$source_author));
    wp_set_object_terms($copy_id,array_values($term_ids),'pa_tamano',false);
    $source_producer=wp_get_object_terms(EMDO_MJ_SOURCE,'pa_productor',array('fields'=>'ids'));
    if (is_wp_error($source_producer) || !$source_producer) { throw new RuntimeException('Source vendor taxonomy missing'); }
    wp_set_object_terms($copy_id,array_map('intval',$source_producer),'pa_productor',false);

    $copied_sizes=array();
    foreach (wc_get_product($copy_id)->get_children() as $vid) {
        $child=wc_get_product($vid);
        $s=(string)($child->get_attributes()['pa_tamano']??'');
        if (!isset($short_map[$s]) || isset($copied_sizes[$s])) {
            throw new RuntimeException('Unexpected existing duplicate variation');
        }
        $copied_sizes[$s]=$child;
    }
    foreach ($short_map as $size=>$sku_part) {
        $v=$copied_sizes[$size]??null;
        if (!$v) {
            $v=clone $original[$size];
            $v->set_id(0);
            $v->set_parent_id($copy_id);
            $v->set_date_created(null);
            $v->set_slug('');
        }
        $v->set_sku('MONTJAM-JN-MENTTA-'.$sku_part);
        if (method_exists($v,'set_global_unique_id')) { $v->set_global_unique_id(''); }
        $v->set_status('publish');
        $v->set_regular_price($price_map[$size]);
        $v->set_sale_price('');
        $v->set_attributes(array('pa_tamano'=>$size));
        $v->set_menu_order(array_search($size,array_keys($short_map),true));
        if (!$v->save()) { throw new RuntimeException('Saving duplicated variation failed'); }
    }

    $copy_format_id=emdo_mj_duplicate_format(EMDO_MJ_SOURCE,$copy_id);
    $hidden_ids=emdo_mentta_exclusive_ids();
    if (!in_array($copy_id,$hidden_ids,true)) { $hidden_ids[]=$copy_id; }
    update_option('emdo_mentta_exclusive_ids',array_values(array_unique(array_map('intval',$hidden_ids))),'yes');
    $copy=wc_get_product($copy_id);
    $copy->set_status('publish');
    $copy->set_catalog_visibility('hidden');
    $copy->save();
    WC_Product_Variable::sync($copy_id,true);

    $checked=array();
    foreach (array($source->get_id()=>$price_map,$copy_id=>array_intersect_key($price_map,$short_map)) as $pid=>$desired) {
        $product=wc_get_product($pid);
        $seen=array();
        foreach ($product->get_children() as $vid) {
            $v=wc_get_product($vid);
            if (!$v || $v->get_status()!=='publish') { continue; }
            $s=(string)($v->get_attributes()['pa_tamano']??'');
            if (!isset($desired[$s])) { throw new RuntimeException("Unexpected active weight on #$pid: $s"); }
            if (abs((float)$v->get_regular_price('edit')-(float)$desired[$s]) > .00001
                || abs((float)$v->get_price('edit')-(float)$desired[$s]) > .00001
                || $v->get_sale_price('edit')!=='') { throw new RuntimeException("Price mismatch #$pid/$s"); }
            $seen[$s]=array('variation_id'=>$vid,'price'=>$v->get_regular_price('edit'));
        }
        $a=array_keys($seen); $b=array_keys($desired); sort($a); sort($b);
        if ($a!==$b) { throw new RuntimeException("Variation count/size mismatch #$pid"); }
        $checked[$pid]=$seen;
    }
    $copy=wc_get_product($copy_id);
    if ($copy->get_status()!=='publish' || $copy->get_catalog_visibility()!=='hidden'
        || $copy->get_category_ids()!==array((int)$mentta->term_id)
        || get_post_meta($copy_id,'_emdo_mentta_source_id',true)!==(string)EMDO_MJ_SOURCE
        || (int)get_post_field('post_author',$copy_id)!==$source_author
        || !in_array($copy_id,emdo_mentta_exclusive_ids(),true)
        || $copy->get_image_id()!==$source->get_image_id()
        || $copy->get_description()!==$source->get_description()) {
        throw new RuntimeException('Mentta-only product verification failed');
    }
    foreach (array(EMDO_MJ_SOURCE,$copy_id) as $pid) {
        wc_delete_product_transients($pid);
        clean_post_cache($pid);
        if (function_exists('rocket_clean_post')) { rocket_clean_post($pid); }
    }
    echo "EMDO_MONTJAM_MENTTA_OPTION2_OK ".wp_json_encode(array(
        'source_product_id'=>EMDO_MJ_SOURCE,
        'mentta_product_id'=>$copy_id,
        'mentta_product_url'=>get_permalink($copy_id),
        'source_prices'=>$checked[EMDO_MJ_SOURCE],
        'mentta_prices'=>$checked[$copy_id],
        'mentta_category_id'=>(int)$mentta->term_id,
        'mentta_catalog_visibility'=>$copy->get_catalog_visibility(),
        'mentta_public_guard_enabled'=>true,
        'yith_format_block_id'=>$copy_format_id,
        'vendor_author'=>$source_author,
    ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
    fwrite(STDERR,"FAILED: ".$e->getMessage()."\n");
    foreach ($source_before as $vid=>$old) {
        try {
            $v=wc_get_product($vid);
            if ($v) { $v->set_regular_price($old['regular']); $v->set_sale_price($old['sale']); $v->save(); }
        } catch (Throwable $ignored) {}
    }
    WC_Product_Variable::sync(EMDO_MJ_SOURCE,true);
    if ($new_copy && $copy_id) {
        global $wpdb;
        $b=$wpdb->prefix.'yith_wapo_blocks'; $a=$wpdb->prefix.'yith_wapo_addons'; $j=$wpdb->prefix.'yith_wapo_blocks_assoc';
        if ($copy_format_id) {
            $wpdb->delete($a,array('block_id'=>$copy_format_id));
            $wpdb->delete($j,array('rule_id'=>$copy_format_id));
            $wpdb->delete($b,array('id'=>$copy_format_id));
        }
        $copy=wc_get_product($copy_id);
        if ($copy) {
            foreach ($copy->get_children() as $vid) { $v=wc_get_product($vid); if ($v) { $v->delete(true); } }
            $copy->delete(true);
        }
        $hidden=array_values(array_diff(emdo_mentta_exclusive_ids(),array($copy_id)));
        update_option('emdo_mentta_exclusive_ids',$hidden,'yes');
    }
    exit(20);
}
