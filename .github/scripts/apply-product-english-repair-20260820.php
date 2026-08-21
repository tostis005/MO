<?php
/** Safely apply English product translations; publish English routes for published Huerta products. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$payload_path=getenv('MDO_PAYLOAD_PATH')?:'/tmp/product-english-repair-output.json';
$payload=json_decode((string)file_get_contents($payload_path),true);
if(!is_array($payload)||!isset($payload['products'])||!is_array($payload['products'])){fwrite(STDERR,"Invalid payload\n");exit(20);}
function mdo_apply_plain($v):string{return trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$v),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
function mdo_apply_num_canon($x):string{$x=str_replace(',','.',(string)$x);if(strpos($x,'.')!==false)$x=rtrim(rtrim($x,'0'),'.');if($x==='')$x='0';if(strpos($x,'.')===0)$x='0'.$x;return $x;}
function mdo_apply_nums($s):array{$s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');preg_match_all('/\d+(?:[.,]\d+)?/u',$s,$m);$o=[];foreach($m[0] as $x){$x=mdo_apply_num_canon($x);$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
function mdo_apply_huerta_clean($v):string{return preg_replace('/\bconservas\b/iu','preserves',(string)$v);}
$huerta=[];
if(class_exists('MDO_Database')){$table=MDO_Database::table('source_products');$ids=$wpdb->get_col("SELECT DISTINCT wc_product_id FROM {$table} WHERE wc_product_id>0 AND source_url LIKE '%lahuertadeanamary.com%'");foreach((array)$ids as $id)$huerta[(int)$id]=true;}
$used=[];$existing=$wpdb->get_results("SELECT post_id,meta_value FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value<>''",ARRAY_A);foreach($existing as $e)$used[sanitize_title($e['meta_value'])]=(int)$e['post_id'];
$validated=[];$problems=[];
foreach($payload['products'] as $r){
  $id=(int)($r['id']??0);$p=get_post($id);if(!$p||$p->post_type!=='product'){$problems[]=['id'=>$id,'reason'=>'missing_product'];continue;}
  if((string)$p->post_status!==(string)($r['source_status']??'')){$problems[]=['id'=>$id,'reason'=>'status_changed'];continue;}
  $title=(string)($r['title']??'');$slug=sanitize_title((string)($r['slug']??''));$excerpt=(string)($r['excerpt']??'');$content=(string)($r['content']??'');
  if(isset($huerta[$id])){
    $title=mdo_apply_huerta_clean($title);$excerpt=mdo_apply_huerta_clean($excerpt);$content=mdo_apply_huerta_clean($content);
    $title_fixes=[
      12740=>['Young garlic, 314 ml','young-garlic-314-ml'],
      12748=>['Artisan canned leeks, 720 ml','artisan-canned-leeks-720-ml'],
      12797=>['Artisan tomato sauté, 314 ml','artisan-tomato-saute-314-ml'],
      12809=>['12 jars of artisan tomato sauté, 314 ml','12-jars-of-artisan-tomato-saute-314-ml'],
    ];
    if(isset($title_fixes[$id])){$title=$title_fixes[$id][0];$slug=$title_fixes[$id][1];}
  }
  if(mdo_apply_plain($title)===''||$slug===''){$problems[]=['id'=>$id,'reason'=>'missing_title_or_slug'];continue;}
  if(mdo_apply_plain($p->post_excerpt)!==''&&mdo_apply_plain($excerpt)===''){$problems[]=['id'=>$id,'reason'=>'missing_excerpt'];continue;}
  if(mdo_apply_plain($p->post_content)!==''&&mdo_apply_plain($content)===''){$problems[]=['id'=>$id,'reason'=>'missing_content'];continue;}
  $src=$p->post_title.' '.$p->post_excerpt.' '.$p->post_content;$dst=$title.' '.$excerpt.' '.$content;
  if(mdo_apply_nums($src)!==mdo_apply_nums($dst)){$problems[]=['id'=>$id,'reason'=>'number_mismatch'];continue;}
  if(isset($used[$slug])&&$used[$slug]!==$id){$slug=sanitize_title($slug.'-'.$id);}if(isset($used[$slug])&&$used[$slug]!==$id){$problems[]=['id'=>$id,'reason'=>'slug_collision'];continue;}$used[$slug]=$id;
  $r['title']=$title;$r['slug']=$slug;$r['excerpt']=$excerpt;$r['content']=$content;$validated[]=$r;
}
if($problems){echo 'PRECHECK_BAD '.wp_json_encode(array_slice($problems,0,80),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";exit(21);}
$up=wp_upload_dir();$dir=trailingslashit($up['basedir']).'mdo-translation-backups';wp_mkdir_p($dir);$backup=['created'=>gmdate('c'),'products'=>[]];
foreach($validated as $r){$id=(int)$r['id'];$backup['products'][]=['id'=>$id,'published'=>(string)get_post_meta($id,'_en_US_published',true),'ready'=>(string)get_post_meta($id,'_en_US_ready',true),'title'=>(string)get_post_meta($id,'_en_US_post_title',true),'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),'content'=>(string)get_post_meta($id,'_en_US_post_content',true)];}
$backup_path=trailingslashit($dir).'product-en-repair-20260820-'.gmdate('Ymd-His').'.json';file_put_contents($backup_path,wp_json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$counts=[];$huerta_published=0;
foreach($validated as $r){
  $id=(int)$r['id'];$pub=(string)get_post_meta($id,'_en_US_published',true);$ready=(string)get_post_meta($id,'_en_US_ready',true);
  update_post_meta($id,'_en_US_post_title',(string)$r['title']);update_post_meta($id,'_en_US_post_name',(string)$r['slug']);update_post_meta($id,'_en_US_post_excerpt',(string)$r['excerpt']);update_post_meta($id,'_en_US_post_content',(string)$r['content']);
  if(isset($huerta[$id])&&'publish'===get_post_status($id)){
    update_post_meta($id,'_en_US_published','1');update_post_meta($id,'_en_US_ready','1');$huerta_published++;
  }elseif($pub!=='1'){
    update_post_meta($id,'_en_US_ready','1');
  }elseif($ready!==''){
    update_post_meta($id,'_en_US_ready',$ready);
  }
  $vendor=(string)($r['vendor']??'');$counts[$vendor]=($counts[$vendor]??0)+1;
}
wp_cache_flush();
$bad=[];foreach($validated as $r){$id=(int)$r['id'];foreach(['post_title'=>'title','post_name'=>'slug','post_excerpt'=>'excerpt','post_content'=>'content'] as $meta=>$key){$actual=(string)get_post_meta($id,'_en_US_'.$meta,true);$expected=(string)$r[$key];if($meta==='post_name'){$actual=sanitize_title($actual);$expected=sanitize_title($expected);}if($actual!==$expected)$bad[]=['id'=>$id,'field'=>$meta];}if(isset($huerta[$id])&&'publish'===get_post_status($id)&&(string)get_post_meta($id,'_en_US_published',true)!=='1')$bad[]=['id'=>$id,'field'=>'published'];}
if($bad){echo 'VERIFY_BAD '.wp_json_encode($bad,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";exit(22);}
echo 'APPLIED_OK '.wp_json_encode(['count'=>count($validated),'vendors'=>$counts,'huerta_published'=>$huerta_published,'backup'=>$backup_path],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
