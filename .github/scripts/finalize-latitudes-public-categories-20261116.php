<?php
/**
 * Targeted cleanup for the seven published public Musée / Latitudes BF packs.
 * Removes only an unrelated Embutidos y curados auto-assignment, never
 * modifies the seven Mentta source products or other catalog products.
 * MDO_BF17_CATEGORY_MODE=inspect|apply|verify
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) exit(2);
$host = strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if (!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)) exit(2);
$mode = getenv('MDO_BF17_CATEGORY_MODE') ?: 'inspect';
if (!in_array($mode,['inspect','apply','verify'],true)) exit(2);
$expected = [
    21814=>['pasaporte-salvaje','carnes-exoticas'],
    21816=>['reserva-de-caza','carnes-exoticas'],
    21818=>['safari-africano','carnes-exoticas'],
    21820=>['vuelta-al-mundo','carnes-exoticas'],
    21822=>['parrilla-sin-fronteras-xl','carnes-exoticas'],
    21824=>['universo-wagyu','wagyu'],
    21826=>['leyendas-del-vacuno','wagyu']
];
$terms = [];
foreach (['carnes','packs-y-lotes','carnes-exoticas','wagyu','embutidos-y-curados','mentta','mentta-carnes'] as $slug) {
    $term = get_term_by('slug',$slug,'product_cat');
    if (!$term || is_wp_error($term)) {fwrite(STDERR,"MISSING_CATEGORY $slug\n");exit(3);}
    $terms[$slug] = (int)$term->term_id;
}
if ($terms['embutidos-y-curados'] !== 105) {fwrite(STDERR,"CATEGORY_ID_CHANGED\n"); exit(3);}
$rows = [];
foreach ($expected as $src => [$key,$extra]) {
    $sku='MDO-WEB-BF17-'.strtoupper(str_replace('-','_', $key));
    $id=(int)wc_get_product_id_by_sku($sku);
    $p=$id?wc_get_product($id):false;
    if (!$p || $p->get_status()!=='publish' || !$p->is_type('simple') ||
        get_post_meta($id,'_emdo_web_pack_key',true)!==$key ||
        (int)get_post_meta($id,'_emdo_web_source_mentta_pack_id',true)!==$src ||
        (int)get_post_field('post_author',$id)!==4949 ||
        get_post_meta($id,'_emdo_mentta_only',true)==='yes' ||
        ($p->get_catalog_visibility('edit')!=='visible')) {
        fwrite(STDERR,"PUBLIC_PACK_BASELINE_INVALID $key\n");exit(4);
    }
    $want=[$terms['carnes'],$terms['packs-y-lotes'],$terms[$extra]];
    $cats=array_map('intval',$p->get_category_ids('edit'));
    if (array_diff($want,$cats) || array_intersect($cats,[$terms['mentta'],$terms['mentta-carnes']])) {
        fwrite(STDERR,"OWN_CATEGORY_OR_MENTTA_MISMATCH $key\n");exit(5);
    }
    $rows[]=['id'=>$id,'src'=>$src,'key'=>$key,'p'=>$p,'want'=>$want,'extra'=>array_values(array_diff($cats,$want))];
}
foreach ($rows as $row) {
    $id=$row['id'];
    if ($mode==='apply') {
        // Do not call save() on the WooCommerce product; it can trigger other
        // vendor/category synchronizers. Only touch the one wrong relationship.
        if (has_term($terms['embutidos-y-curados'],'product_cat',$id)) {
            $removed=wp_remove_object_terms($id,$terms['embutidos-y-curados'],'product_cat');
            if (is_wp_error($removed) || !$removed) {fwrite(STDERR,"REMOVE_FAILED $id\n");exit(6);}
            clean_object_term_cache($id,'product');
            wc_delete_product_transients($id);
        }
    }
    $raw=wp_get_object_terms($id,'product_cat',['fields'=>'ids']);
    $raw=is_wp_error($raw)?[]:array_map('intval',$raw);
    sort($raw);
    $want=$row['want'];sort($want);
    echo 'BF17_CAT_'.strtoupper($mode).' '.wp_json_encode(['key'=>$row['key'],'id'=>$id,
      'categories'=>$raw,'wanted'=>$want,'unexpected'=>array_values(array_diff($raw,$want))],
      JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
    if ($mode==='verify' && $raw!==$want) {
        fwrite(STDERR,"BF17_CATEGORY_VERIFY_FAILED $id\n");exit(7);
    }
}
if ($mode==='apply' && function_exists('rocket_clean_domain')) rocket_clean_domain();
if ($mode==='apply') wp_cache_flush();
echo 'SEVEN_PUBLIC_PACK_CATEGORIES_'.strtoupper($mode).'_OK'."\n";
