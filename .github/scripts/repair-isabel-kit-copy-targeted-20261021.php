<?php
/**
 * Targeted editorial correction for two source-verified Chocolates Isabel kits.
 * No other WooCommerce products, post types, URLs, SEO metas or commerce fields.
 * Execute with wp eval-file, once; idempotent and guarded by source ownership.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('WordPress, WooCommerce and supplier sync required');
}
global $wpdb;
$su=MDO_Database::table('suppliers');
$sp=MDO_Database::table('source_products');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'),ARRAY_A);
if (!$supplier) throw new RuntimeException('Approved producer not found');
$ids=[17526,17647];
$new_intro='<p>Este kit permite elaborar chocolate en casa a partir de habas de cacao, siguiendo las instrucciones incluidas. Reúne ingredientes y utensilios para preparar, moldear y envolver tabletas. El resultado puede tener una textura más rústica que la del chocolate refinado con maquinaria profesional.</p>';
$expected_fragments=['Una opción para acercarse al proceso de elaboración del chocolate','Este kit propone una experiencia más participativa que una tableta terminada'];
$report=['scope'=>'chocolates-artesanos-isabel','expected'=>2,'reviewed'=>0,'changed'=>0,'already_correct'=>0,'skipped'=>[],'ids_changed'=>[],'verified'=>true];
foreach ($ids as $id) {
    $row=$wpdb->get_row($wpdb->prepare("SELECT supplier_id,source_url FROM {$sp} WHERE wc_product_id=%d AND supplier_id=%d LIMIT 1",$id,(int)$supplier['id']),ARRAY_A);
    $p=wc_get_product($id);
    if (!$row || !$p || $p->get_status()!=='publish' || (int)get_post_field('post_author',$id)!==(int)$supplier['vendor_user_id'] || 
        !preg_match('/kit.*chocolate.*bean.*bar/iu',$p->get_name()) ||
        get_post_meta($id,'_emdo_isabel_editorial_approved',true)!=='1') {
        $report['skipped'][]=['id'=>$id,'reason'=>'identity_or_approval_changed'];
        continue;
    }
    $report['reviewed']++;
    $old_intro=(string)get_post_meta($id,'_emdo_isabel_editorial_intro',true);
    $old_body=(string)$p->get_description();
    if ($old_intro===$new_intro && str_contains($old_body,$new_intro)) {
        $report['already_correct']++;
        continue;
    }
    $matched=false;
    foreach ($expected_fragments as $fragment) {
        if (str_contains(wp_strip_all_tags($old_intro),$fragment)) $matched=true;
    }
    if (!$matched || !$old_intro || !str_contains($old_body,$old_intro)) {
        $report['skipped'][]=['id'=>$id,'reason'=>'editorial_intro_drifted'];
        continue;
    }
    $previous=['content'=>$old_body,'intro'=>$old_intro,'cats'=>$p->get_category_ids(),
      'title'=>$p->get_name(),'sku'=>$p->get_sku(),'price'=>$p->get_price('edit'),
      'regular'=>$p->get_regular_price('edit'),'sale'=>$p->get_sale_price('edit'),
      'status'=>$p->get_status('edit'),'stock'=>$p->get_stock_status('edit'),
      'weight'=>$p->get_weight('edit'),'images'=>$p->get_gallery_image_ids()];
    // Per-record revert information persisted before mutating product content.
    $backup_key='_emdo_targeted_seo_kit_20261021_backup';
    if (!metadata_exists('post',$id,$backup_key)) add_post_meta($id,$backup_key,$previous,true);
    $p->set_description(str_replace($old_intro,$new_intro,$old_body));
    $p->save();
    update_post_meta($id,'_emdo_isabel_editorial_intro',$new_intro);
    clean_post_cache($id);
    $check=wc_get_product($id);
    $invariants=['cats'=>$check->get_category_ids(),
      'title'=>$check->get_name(),'sku'=>$check->get_sku(),'price'=>$check->get_price('edit'),
      'regular'=>$check->get_regular_price('edit'),'sale'=>$check->get_sale_price('edit'),
      'status'=>$check->get_status('edit'),'stock'=>$check->get_stock_status('edit'),
      'weight'=>$check->get_weight('edit'),'images'=>$check->get_gallery_image_ids()];
    $original=$previous;
    unset($original['content'],$original['intro']);
    sort($invariants['cats']);sort($original['cats']);
    if ($invariants!==$original || !str_contains($check->get_description(),$new_intro) ||
        (string)get_post_meta($id,'_emdo_isabel_editorial_intro',true)!==$new_intro) {
        $check->set_description($old_body);$check->save();
        update_post_meta($id,'_emdo_isabel_editorial_intro',$old_intro);
        $report['verified']=false;
        $report['skipped'][]=['id'=>$id,'reason'=>'verification_failed_reverted'];
        continue;
    }
    $report['changed']++;$report['ids_changed'][]=$id;
}
$report['completed']=$report['reviewed']===$report['expected'] && $report['verified'] && count($report['skipped'])===0;
echo 'EMDO_TARGETED_KIT_SEO '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if (!$report['completed']) exit(17);
