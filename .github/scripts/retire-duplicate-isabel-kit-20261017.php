<?php
/**
 * Retire one verified duplicate chocolate kit without deletion.
 * The source URL redirects to the surviving official listing and both rows
 * share original SKU/price/name/weight. Keep existing order history intact.
 */
if(!defined('ABSPATH')||!class_exists('WooCommerce'))throw new RuntimeException('WordPress/Woo required');
global $wpdb;
$old_id=17526;$new_id=17647;$old=wc_get_product($old_id);$new=wc_get_product($new_id);
if(!$old||!$new||$old->get_status()!=='publish'||$new->get_status()!=='publish')throw new RuntimeException('Canonical or alias missing/not published');
if((int)get_post_field('post_author',$old_id)!==4964||(int)get_post_field('post_author',$new_id)!==4964)throw new RuntimeException('Unexpected product owner');
if($old->get_name()!==$new->get_name()||$old->get_price('edit')!==$new->get_price('edit')||$old->get_weight()!==$new->get_weight()||$old->get_stock_status()!==$new->get_stock_status())throw new RuntimeException('Kit variants differ; refusing to merge');
$source_table=MDO_Database::table('source_products');$sup_table=MDO_Database::table('suppliers');
$one=$wpdb->get_row("SELECT id,supplier_id,status,source_url,source_payload FROM {$source_table} WHERE id=656",ARRAY_A);
$two=$wpdb->get_row("SELECT id,supplier_id,status,source_url,source_payload FROM {$source_table} WHERE id=698",ARRAY_A);
if(!$one||!$two||$one['supplier_id']!==$two['supplier_id']||(int)$one['supplier_id']<1)throw new RuntimeException('Supplier source mismatch');
if((string)$one['source_url']!=='https://chocolatesartesanosisabel.com/producto/kit-elabora-propio-chocolate/' ||
 (string)$two['source_url']!=='https://chocolatesartesanosisabel.com/producto/kit-elabora-propio-chocolate-bean-to-bar/')
 throw new RuntimeException('Source canonicals changed; refusing duplicate merge');
$p1=json_decode((string)$one['source_payload'],true);$p2=json_decode((string)$two['source_payload'],true);
if(($p1['sku']??'')!=='PF19-000731'||($p2['sku']??'')!=='PF19-000731'||(float)($p1['price']??0)!==47.0||(float)($p2['price']??0)!==47.0)throw new RuntimeException('Original SKU/price mismatch');
if($old->get_sku()!=='PF19-000731'||$new->get_sku()!=='')throw new RuntimeException('Unexpected Woo SKU state');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT id,code,exclusion_url_fragments FROM {$sup_table} WHERE id=%d",(int)$one['supplier_id']),ARRAY_A);
if(!$supplier||(string)$supplier['code']!=='chocolates-artesanos-isabel')throw new RuntimeException('Not Isabel');
$fragments=json_decode((string)$supplier['exclusion_url_fragments'],true);if(!is_array($fragments))$fragments=[];
$needle='/kit-elabora-propio-chocolate/';
if(!in_array($needle,$fragments,true))$fragments[]=$needle;
$snapshot=[
 'old_status'=>$old->get_status(),'old_visibility'=>$old->get_catalog_visibility(),'old_sku'=>$old->get_sku(),
 'new_sku'=>$new->get_sku(),'source_status'=>$one['status'],'fragments'=>$supplier['exclusion_url_fragments'],
];
try{
 $old->set_sku('');$old->set_status('draft');$old->set_catalog_visibility('hidden');$old->save();
 $new->set_sku('PF19-000731');$new->save();
 $u1=$wpdb->update($sup_table,['exclusion_url_fragments'=>wp_json_encode($fragments,JSON_UNESCAPED_SLASHES)],['id'=>(int)$supplier['id']]);
 $u2=$wpdb->update($source_table,['status'=>'excluded','last_error'=>null],['id'=>656]);
 if(false===$u1||false===$u2)throw new RuntimeException('DB exclusion update failed');
 $old=wc_get_product($old_id);$new=wc_get_product($new_id);
 if($old->get_status()!=='draft'||$old->is_visible()||$new->get_status()!=='publish'||$new->get_sku()!=='PF19-000731'||(string)$wpdb->get_var("SELECT status FROM {$source_table} WHERE id=656")!=='excluded')throw new RuntimeException('Duplicate retire verification failed');
 $redirect_source=file_get_contents(WPMU_PLUGIN_DIR.'/mdo-chocolates-seo-cluster-20261017.php');
 if(!str_contains((string)$redirect_source,"kit-haz-tu-propio-chocolate-bean-to-bar-2/"))throw new RuntimeException('301 redirect plugin not present');
}catch(Throwable $e){
 $new=wc_get_product($new_id);if($new){$new->set_sku($snapshot['new_sku']);$new->save();}
 $old=wc_get_product($old_id);if($old){$old->set_sku($snapshot['old_sku']);$old->set_status($snapshot['old_status']);$old->set_catalog_visibility($snapshot['old_visibility']);$old->save();}
 $wpdb->update($source_table,['status'=>$snapshot['source_status']],['id'=>656]);
 $wpdb->update($sup_table,['exclusion_url_fragments'=>$snapshot['fragments']],['id'=>(int)$supplier['id']]);
 wp_cache_flush();throw $e;
}
wp_cache_flush();if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'ISABEL_KIT_DEDUP='.wp_json_encode(['verified'=>true,'archived_product'=>$old_id,'canonical_product'=>$new_id,'canonical_permalink'=>get_permalink($new_id),'sku'=>'PF19-000731','source_excluded'=>656,'supplier_url_exclusion'=>$needle],JSON_UNESCAPED_SLASHES)."\n";
