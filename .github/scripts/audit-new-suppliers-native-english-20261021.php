<?php
/** Read-only new-supplier SEO and English completeness inventory, EMDO production.
 * Re-audit after six Ikore Kofi and thirteen Isabel/Latitudes English releases; no writes.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('WordPress + supplier sync required');
}
$host=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)) throw new RuntimeException('Production only');
global $wpdb;
$su=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$codes=['chocolates-artesanos-isabel','ikore-kofi','tropitop','latitudes'];
$result=['site'=>'El Mercado de Origen','mode'=>'read_only','suppliers'=>[],'alerts'=>[]];
foreach($codes as $code){
  $supplier=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s LIMIT 1",$code),ARRAY_A);
  if(!$supplier){$result['suppliers'][$code]=['status'=>'supplier_not_found'];continue;}
  $rows=$wpdb->get_results($wpdb->prepare(
   "SELECT id,wc_product_id,status,title,source_url FROM {$sp} WHERE supplier_id=%d ORDER BY id",
   (int)$supplier['id']),ARRAY_A);
  $counts=['source_total'=>count($rows),'source_excluded'=>0,'linked_products'=>0,'published'=>0,
    'published_buyable'=>0,'missing_english_native'=>0,'missing_english_title'=>0,
    'missing_english_excerpt'=>0,'missing_english_content'=>0,'english_not_published'=>0,
    'source_nonphysical_suspects'=>0,'wrong_vendor'=>0,'missing_wp_product'=>0];
  $issues=[];$seen=[];
  foreach($rows as $r){
    $source_id=(int)$r['id'];$product_id=(int)$r['wc_product_id'];
    if($r['status']==='excluded')$counts['source_excluded']++;
    $title=(string)$r['title'];
    $suspicious=(bool)preg_match('~(?:visita(?:s)?|taller(?:es)?|curso(?:s)?|cata(?:s)?|apadrin(?:ar|a)|tarjeta.?regalo|entrada|reserva)~iu',$title);
    if($suspicious) {
      $counts['source_nonphysical_suspects']++;
      if(count($issues)<80)$issues[]=['id'=>$product_id,'source_id'=>$source_id,'type'=>'review_nonphysical','source_status'=>$r['status'],'title'=>$title];
    }
    if(!$product_id||isset($seen[$product_id]))continue;
    $seen[$product_id]=1;
    $p=wc_get_product($product_id);
    if(!$p){$counts['missing_wp_product']++;continue;}
    if((int)get_post_field('post_author',$product_id)!==(int)$supplier['vendor_user_id']){
      $counts['wrong_vendor']++;continue;
    }
    $counts['linked_products']++;
    if($p->get_status()!=='publish')continue;
    $counts['published']++;
    if(!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock())continue;
    $counts['published_buyable']++;
    $translated=(string)get_post_meta($product_id,'_en_US_published',true)==='1';
    $title_en=trim(wp_strip_all_tags((string)get_post_meta($product_id,'_en_US_post_title',true)));
    $slug_en=trim((string)get_post_meta($product_id,'_en_US_post_name',true));
    $excerpt_en=trim(wp_strip_all_tags((string)get_post_meta($product_id,'_en_US_post_excerpt',true)));
    $content_en=trim(wp_strip_all_tags((string)get_post_meta($product_id,'_en_US_post_content',true)));
    $need_excerpt=trim(wp_strip_all_tags((string)$p->get_short_description()))!=='';
    $need_content=trim(wp_strip_all_tags((string)$p->get_description()))!=='';
    $missing=[];
    if(!$translated){$counts['english_not_published']++;$missing[]='not_published';}
    if(!$title_en||!$slug_en){$counts['missing_english_title']++;$missing[]='title_or_slug';}
    if($need_excerpt&&!$excerpt_en){$counts['missing_english_excerpt']++;$missing[]='excerpt';}
    if($need_content&&!$content_en){$counts['missing_english_content']++;$missing[]='content';}
    if($missing){
      $counts['missing_english_native']++;
      if(count($issues)<80)$issues[]=['id'=>$product_id,'source_id'=>$source_id,'type'=>'native_english_missing',
        'title'=>$p->get_name(),'missing'=>$missing,'url'=>get_permalink($product_id)];
    }
  }
  $result['suppliers'][$code]=['counts'=>$counts,'issues'=>$issues];
}
// Ikore Kofi is deliberately a manual WooCommerce producer, not a supplier-sync record.
$ikore=get_user_by('login','ikore-kofi');
if($ikore){
    // Six audited, manually provisioned SKUs: do not use a broad vendor
    // author query; unrelated catalog products can share a user account.
    $official_skus=['46002/V0112','46002/V0116','46002/V0122','46002/V0123','46002/V0127','46002/V0124'];
    $ids=[];
    foreach($official_skus as $sku){
        $id=(int)wc_get_product_id_by_sku($sku);
        if(!$id)continue;
        $candidate=wc_get_product($id);
        if(!$candidate||$candidate->get_status()!=='publish'||
          (int)get_post_field('post_author',$id)!==(int)$ikore->ID||
          (string)get_post_meta($id,'_mdo_ikore_manual',true)!=='1')continue;
        $ids[]=$id;
    }
    $counts=['published'=>count($ids),'published_buyable'=>0,'missing_english_native'=>0,'missing_english_title'=>0,'missing_english_excerpt'=>0,'missing_english_content'=>0,'english_not_published'=>0];
    $issues=[];
    foreach($ids as $id){
        $p=wc_get_product($id);
        if(!$p||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock())continue;
        $counts['published_buyable']++;
        $translated=(string)get_post_meta($id,'_en_US_published',true)==='1';
        $name=trim((string)get_post_meta($id,'_en_US_post_title',true));
        $slug=trim((string)get_post_meta($id,'_en_US_post_name',true));
        $excerpt=trim(wp_strip_all_tags((string)get_post_meta($id,'_en_US_post_excerpt',true)));
        $body=trim(wp_strip_all_tags((string)get_post_meta($id,'_en_US_post_content',true)));
        $missing=[];
        if(!$translated){$counts['english_not_published']++;$missing[]='not_published';}
        if(!$name||!$slug){$counts['missing_english_title']++;$missing[]='title_or_slug';}
        if(trim(wp_strip_all_tags($p->get_short_description()))!==''&&!$excerpt){$counts['missing_english_excerpt']++;$missing[]='excerpt';}
        if(trim(wp_strip_all_tags($p->get_description()))!==''&&!$body){$counts['missing_english_content']++;$missing[]='content';}
        if($missing){
            $counts['missing_english_native']++;
            $issues[]=['id'=>(int)$id,'title'=>$p->get_name(),'missing'=>$missing,'url'=>get_permalink($id)];
        }
    }
    $result['suppliers']['ikore-kofi']=['mode'=>'six_verified_manual_skus','expected_skus'=>count($official_skus),'counts'=>$counts,'issues'=>$issues];
}
// Emit bounded targeted review rows separately: whole audit JSON may be
// clipped by job logs. Never publish the WordPress vendor user details.
foreach(['chocolates-artesanos-isabel','latitudes'] as $review_code){
    $issues=$result['suppliers'][$review_code]['issues']??[];
    foreach($issues as $issue){
        if(($issue['type']??'')!=='native_english_missing')continue;
        echo 'EMDO_EN_NATIVE_TARGET='.wp_json_encode([
            'supplier'=>$review_code,
            'product_id'=>(int)$issue['id'],
            'title'=>$issue['title'],
            'missing'=>$issue['missing'],
            'url'=>$issue['url'],
        ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}
echo 'EMDO_NEW_SUPPLIER_ENGLISH_QA='.wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
