<?php
/** Export products that need an English repair. Huerta is always refreshed from current cleaned Spanish copy. */
// Touch 2026-08-20: trigger the repair workflow after the workflow file exists on main.
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
function mdo_repair_plain( $v ): string { return trim( preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$v),ENT_QUOTES|ENT_HTML5,'UTF-8')) ); }
function mdo_repair_norm( $v ): string { $v=strtolower(remove_accents(mdo_repair_plain($v))); $v=preg_replace('/[^a-z0-9]+/u',' ',$v); return trim(preg_replace('/\s+/',' ',$v)); }
function mdo_repair_vendor( int $uid ): string {
  foreach(['wcfmmp_profile_settings','wcfm_profile_settings'] as $key){$v=get_user_meta($uid,$key,true);if(is_array($v)&&!empty($v['store_name']))return mdo_repair_plain($v['store_name']);}
  $u=get_userdata($uid);return $u?mdo_repair_plain($u->display_name?:$u->user_login):'author-'.$uid;
}
$huerta=[];
if(class_exists('MDO_Database')){$table=MDO_Database::table('source_products');$ids=$wpdb->get_col("SELECT DISTINCT wc_product_id FROM {$table} WHERE wc_product_id>0 AND source_url LIKE '%lahuertadeanamary.com%'");foreach((array)$ids as $id)$huerta[(int)$id]=true;}
$rows=$wpdb->get_results("SELECT ID,post_author,post_status,post_title,post_name,post_excerpt,post_content FROM {$wpdb->posts} WHERE post_type='product' AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID");
$out=[];$reasons=[];
foreach((array)$rows as $p){
  $id=(int)$p->ID;$is_huerta=isset($huerta[$id]);
  $en_title=(string)get_post_meta($id,'_en_US_post_title',true);$en_slug=(string)get_post_meta($id,'_en_US_post_name',true);$en_excerpt=(string)get_post_meta($id,'_en_US_post_excerpt',true);$en_content=(string)get_post_meta($id,'_en_US_post_content',true);
  $missing=[];if(mdo_repair_plain($en_title)==='')$missing[]='title';if(trim($en_slug)==='')$missing[]='slug';if(mdo_repair_plain($p->post_excerpt)!==''&&mdo_repair_plain($en_excerpt)==='')$missing[]='excerpt';if(mdo_repair_plain($p->post_content)!==''&&mdo_repair_plain($en_content)==='')$missing[]='content';
  $exact=[];foreach(['title'=>[$p->post_title,$en_title],'excerpt'=>[$p->post_excerpt,$en_excerpt],'content'=>[$p->post_content,$en_content]] as $field=>$pair){$a=mdo_repair_norm($pair[0]);$b=mdo_repair_norm($pair[1]);if($a!==''&&$a===$b){$words=count(array_filter(explode(' ',$a)));if(($field==='title'&&$words>=2)||($field!=='title'&&$words>=6))$exact[]=$field;}}
  if(!$is_huerta&&!$missing&&!$exact)continue;
  $reason=[];if($is_huerta)$reason[]='huerta_refresh';if($missing)$reason[]='missing:'.implode(',',$missing);if($exact)$reason[]='exact_copy:'.implode(',',$exact);
  $vendor=mdo_repair_vendor((int)$p->post_author);
  $out[]=['id'=>$id,'author_id'=>(int)$p->post_author,'vendor'=>$vendor,'status'=>(string)$p->post_status,'title'=>(string)$p->post_title,'slug'=>(string)$p->post_name,'excerpt'=>(string)$p->post_excerpt,'content'=>(string)$p->post_content,'reason'=>implode('|',$reason)];
  $reasons[$vendor]=($reasons[$vendor]??0)+1;
}
echo wp_json_encode(['site'=>get_option('siteurl'),'generated_at'=>gmdate('c'),'huerta_source_count'=>count($huerta),'count'=>count($out),'vendors'=>$reasons,'products'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
