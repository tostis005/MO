<?php
/**
 * 2026-10-21 focused policy: Chocolates Isabel physical shippable goods only.
 * Gift vouchers and on-site services are excluded, while incomplete physical
 * customisations are temporarily drafted (never deleted). The physical DIY
 * bean-to-bar kit is specifically exempt. Run under WP-CLI in production.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) throw new RuntimeException('WordPress/WooCommerce/supplier sync required');
$host=strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST));
if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) throw new RuntimeException('Production only');
global $wpdb;
$su=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s",'chocolates-artesanos-isabel'),ARRAY_A);
if (!$s || !get_user_by('id',(int)$s['vendor_user_id'])) throw new RuntimeException('Wrong/missing Isabel supplier');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,wc_product_id,source_url,title,status,source_payload FROM {$sp} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
if(count($rows)<90 || count($rows)>160) throw new RuntimeException('Supplier inventory outside expected bound');
$preflight=getenv('EMDO_SCOPE_PREFLIGHT')==='1';
$report=['mode'=>$preflight?'preflight':'apply','supplier'=>'chocolates-artesanos-isabel',
 'source_reviewed'=>count($rows),'eligible_for_hide'=>0,'gift_and_service_excluded'=>0,
 'physical_custom_pending_hidden'=>0,'already_hidden'=>0,'kit_kept'=>0,'targets'=>[],
 'skipped'=>[],'verified'=>true];
$targets=[];
foreach($rows as $r) {
 $id=(int)$r['wc_product_id'];$p=$id?wc_get_product($id):false;
 $payload=json_decode((string)$r['source_payload'],true)?:[];
 $title=(string)$r['title'];
 $sku=$p?(string)$p->get_sku():(string)($payload['sku']??'');
 $source=(string)$r['source_url'];
 $physicalKit=(bool)preg_match('~kit.*(?:haz.*propio|elabora.*propio).*chocolate|kit.*chocolate.*bean.*bar~iu',$title);
 if ($physicalKit){$report['kit_kept']++;continue;}
 $gift=in_array($sku,['PF19-0000655','PF19-0000656'],true) &&
  (bool)preg_match('~tarjeta\s*[- ]\s*regalo~iu',$title);
 $service=(bool)preg_match('~^\s*(?:visita(?:s)?\b|taller(?:es)?\b|entrada(?:s)?\s+(?:a|para)\b|experiencia(?:s)?\s+(?:en|de)\b|cata(?:s)?\s+(?:guiada|presencial|en el obrador))~iu',$title);
 $protected=$p && get_post_meta($id,'_emdo_isabel_customization_review_required',true)==='1';
 if(!$gift && !$service && !$protected)continue;
 $reason=$gift?'voucher':($service?'onsite_service':'physical_custom_unavailable');
 if (!$p && !$gift && !$service)continue;
 if($p && (int)get_post_field('post_author',$id)!==(int)$s['vendor_user_id']){
    $report['skipped'][]=['id'=>$id,'reason'=>'wrong_vendor'];continue;
 }
 if($p && $source!==(string)get_post_meta($id,'_emdo_source_url',true)){
    $report['skipped'][]=['id'=>$id,'reason'=>'source_mismatch'];continue;
 }
 $targets[]=['source_id'=>(int)$r['id'],'product_id'=>$id,'sku'=>$sku,
   'name'=>$title,'reason'=>$reason,'source_status'=>$r['status'],
   'product_status'=>$p?$p->get_status():'none','visible'=>$p?$p->get_catalog_visibility():'none',
   'url'=>$p?get_permalink($id):''];
}
if(count($targets)>25)throw new RuntimeException('Too many product exclusions: manual review required');
$report['eligible_for_hide']=count($targets);
$report['targets']=$targets;
if($preflight){echo 'EMDO_PHYSICAL_ONLY '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;exit;}
if($report['skipped'])throw new RuntimeException('Preflight encountered mismatched ownership/source, refusing writes');
foreach($targets as $target){
 $sourceId=$target['source_id'];$id=$target['product_id'];$reason=$target['reason'];$p=$id?wc_get_product($id):false;
 $permanent=in_array($reason,['voucher','onsite_service'],true);
 $row=$wpdb->get_row($wpdb->prepare("SELECT status FROM {$sp} WHERE id=%d AND supplier_id=%d",$sourceId,(int)$s['id']),ARRAY_A);
 if(!$row)throw new RuntimeException('Source row disappeared');
 if($p){
    $was=['status'=>$p->get_status(),'visibility'=>$p->get_catalog_visibility(),
      'sku'=>$p->get_sku(),'price'=>$p->get_price('edit'),'stock'=>$p->get_stock_status('edit'),
      'type'=>$p->get_type(),'images'=>$p->get_gallery_image_ids(),
      'source_status'=>$row['status'],'reason'=>$reason];
    $backupKey='_emdo_physical_only_20261021_backup';
    if(!metadata_exists('post',$id,$backupKey))add_post_meta($id,$backupKey,$was,true);
    if($p->get_status()==='draft' && $p->get_catalog_visibility()==='hidden'){
       $report['already_hidden']++;
    }else{
       $p->set_status('draft');$p->set_catalog_visibility('hidden');$p->save();
    }
    $after=wc_get_product($id);
    if(!$after || $after->get_status()!=='draft' || $after->get_catalog_visibility()!=='hidden' ||
       $after->get_sku()!==$was['sku'] || $after->get_price('edit')!==$was['price'] ||
       $after->get_stock_status('edit')!==$was['stock'] || $after->get_type()!==$was['type'] ||
       $after->get_gallery_image_ids()!==$was['images']){
       $report['verified']=false;
       $report['skipped'][]=['id'=>$id,'reason'=>'postwrite_verification_failed'];
       continue;
    }
 }
 if($permanent){
   if($row['status']!=='excluded'){
     $ok=$wpdb->update($sp,['status'=>'excluded'],['id'=>$sourceId,'supplier_id'=>(int)$s['id']],['%s'],['%d','%d']);
     if($ok===false)throw new RuntimeException('Source exclusion failed '.$sourceId);
   }
   if((string)$wpdb->get_var($wpdb->prepare("SELECT status FROM {$sp} WHERE id=%d",$sourceId))!=='excluded')throw new RuntimeException('Exclusion verification failed '.$sourceId);
   $report['gift_and_service_excluded']++;
 }else{
   $report['physical_custom_pending_hidden']++;
 }
}
echo 'EMDO_PHYSICAL_ONLY '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$report['verified'] || $report['skipped'])exit(12);
