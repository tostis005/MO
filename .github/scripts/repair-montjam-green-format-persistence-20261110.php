<?php
/**
 * Montjam green shoulder #14305: restore YITH FORMAT associations for the
 * parent product and its ONLY public 6-6.5 kg variation.
 * This fixes the YITH async update removing the FORMAT options after initial paint.
 * No prices, product variants, inventory, copy, or addon settings are changed.
 * Usage MDO_GREEN_ASSOC_MODE=audit|apply|verify wp eval-file ...
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WC_Product_Variable')) {
  fwrite(STDERR,"ABORT_WPCLI_REQUIRED\n");exit(2);
}
$mode=getenv('MDO_GREEN_ASSOC_MODE')?:'audit';
if(!in_array($mode,['audit','apply','verify'],true)){fwrite(STDERR,"ABORT_BAD_MODE\n");exit(2);}
global $wpdb;
$pid=14305; $bid=110; $aid=195; $weight='6-65-kg';
$expected_name='Paleta de cebo de campo ibérica 50% raza ibérica Montjam (brida verde)';
$p=wc_get_product($pid);
if(!$p||!$p->is_type('variable')||$p->get_name()!==$expected_name||$p->get_status()!=='publish'){
  fwrite(STDERR,"ABORT_PRODUCT_MISMATCH\n");exit(3);
}
$producers=wp_get_object_terms($pid,'pa_productor',['fields'=>'slugs']);
if(is_wp_error($producers)||!in_array('montjam',$producers,true)){fwrite(STDERR,"ABORT_NOT_MONTJAM\n");exit(3);}
if(($p->get_default_attributes()['pa_tamano']??'')!==$weight){fwrite(STDERR,"ABORT_DEFAULT_WEIGHT_MISMATCH\n");exit(3);}
$sizeattr=$p->get_attributes()['pa_tamano']??null;
$term=get_term_by('slug',$weight,'pa_tamano');
if(!$term||!($sizeattr instanceof WC_Product_Attribute)||array_map('intval',$sizeattr->get_options())!==[(int)$term->term_id]){fwrite(STDERR,"ABORT_SIZE_SELECTOR_MISMATCH\n");exit(4);}
$public=[];$all=[];
foreach($p->get_children() as $vid){
  $v=wc_get_product($vid);
  if(!$v||!$v->is_type('variation')){fwrite(STDERR,"ABORT_BAD_CHILD\n");exit(4);}
  $all[]=$vid;
  if($v->get_status()==='publish')$public[]=$v;
}
if(count($public)!==1||($public[0]->get_attributes()['pa_tamano']??'')!==$weight||$public[0]->get_stock_status()!=='instock'||abs((float)$public[0]->get_regular_price('edit')-151.94)>0.0001||$public[0]->get_sale_price('edit')!==''){
  fwrite(STDERR,"ABORT_PUBLIC_VARIATION_MISMATCH\n");exit(4);
}
$vid=(int)$public[0]->get_id();
$assoc=$wpdb->prefix.'yith_wapo_blocks_assoc';$blocks=$wpdb->prefix.'yith_wapo_blocks';$addons=$wpdb->prefix.'yith_wapo_addons';
foreach([$assoc,$blocks,$addons] as $t)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t))!==$t){fwrite(STDERR,"ABORT_YITH_TABLE_MISSING\n");exit(5);}
$b=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$blocks` WHERE id=%d",$bid),ARRAY_A);
$a=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$addons` WHERE id=%d AND block_id=%d",$aid,$bid),ARRAY_A);
if(!$b||!$a||$b['name']!=='Montjam · Formato · 14305'||(int)$b['visibility']!==1||(int)$a['visibility']!==1){
  fwrite(STDERR,"ABORT_WRONG_YITH_BLOCK\n");exit(5);
}
$bs=maybe_unserialize($b['settings']);$as=maybe_unserialize($a['settings']);$opts=maybe_unserialize($a['options']);
if(($bs['rules']['show_in_products']??null)!==['14305'] || ($as['type']??null)!=='select'||($as['title']??null)!=='FORMATO'
 ||($opts['label']??null)!==['Pieza entera','Loncheado a cuchillo + huesos + taquitos','Deshuesado']
 ||($opts['price']??null)!==['0.00','46.20','17.325'] ||($opts['addon_enabled']??null)!==['yes','yes','yes']){
  fwrite(STDERR,"ABORT_YITH_OPTIONS_CHANGED\n");exit(5);
}
$current=$wpdb->get_results($wpdb->prepare("SELECT rule_id,object,type FROM `$assoc` WHERE rule_id=%d ORDER BY object",$bid),ARRAY_A);
if(!is_array($current)){fwrite(STDERR,"ABORT_ASSOC_QUERY\n");exit(6);}
foreach($current as $row)if($row['type']!=='product'){fwrite(STDERR,"ABORT_UNEXPECTED_ASSOC_TYPE\n");exit(6);}
$want=[(string)$pid,(string)$vid]; sort($want,SORT_STRING);
$have=array_map(fn($r)=>(string)$r['object'],$current);sort($have,SORT_STRING);
$correct=$have===$want;
echo "MONTJAM_FORMAT_ASSOC_PLAN ".wp_json_encode(['mode'=>$mode,'product'=>$pid,'variation'=>$vid,'weight'=>$weight,'price'=>'151.94','existing_objects'=>$have,'wanted_objects'=>$want,'already_fixed'=>$correct,'format_prices'=>['0.00','46.20','17.325']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($mode==='audit'){echo "MONTJAM_FORMAT_ASSOC_AUDIT_OK\n";exit(0);}
if($mode==='apply'&&!$correct){
  $started=$wpdb->query('START TRANSACTION');
  if($started===false){fwrite(STDERR,"ABORT_TRANSACTION_START\n");exit(7);}
  try{
    if($wpdb->delete($assoc,['rule_id'=>$bid])===false){throw new RuntimeException('Cannot clear old block association');}
    foreach([$pid,$vid] as $oid){
      if(!$wpdb->insert($assoc,['rule_id'=>$bid,'object'=>(string)$oid,'type'=>'product']))throw new RuntimeException('Cannot insert assoc '.$oid.' '.$wpdb->last_error);
    }
    if($wpdb->query('COMMIT')===false)throw new RuntimeException('Commit failed');
  }catch(Throwable $e){
    $wpdb->query('ROLLBACK');
    fwrite(STDERR,"MONTJAM_FORMAT_ASSOC_APPLY_FAILED ".$e->getMessage()."\n");exit(7);
  }
}
if(in_array($mode,['apply','verify'],true)){
  $got=$wpdb->get_results($wpdb->prepare("SELECT object,type FROM `$assoc` WHERE rule_id=%d ORDER BY object",$bid),ARRAY_A);
  if(count($got)!==2){fwrite(STDERR,"MONTJAM_FORMAT_ASSOC_VERIFY_BAD_COUNT\n");exit(8);}
  $values=array_map(fn($r)=>(string)$r['object'],$got);sort($values,SORT_STRING);
  if($values!==$want||count(array_filter($got,fn($r)=>$r['type']==='product'))!==2){fwrite(STDERR,"MONTJAM_FORMAT_ASSOC_VERIFY_BAD_ROWS\n");exit(8);}
  $q=wc_get_product($pid);
  $qpub=[];
  foreach($q->get_children() as $cid){$ch=wc_get_product($cid);if($ch&&$ch->get_status()==='publish')$qpub[]=$ch;}
  if(count($qpub)!==1||$qpub[0]->get_id()!==$vid||
     abs((float)$qpub[0]->get_regular_price('edit')-151.94)>0.0001||
     ($q->get_default_attributes()['pa_tamano']??'')!==$weight){
    fwrite(STDERR,"MONTJAM_FORMAT_ASSOC_VERIFY_PRODUCT_CHANGED\n");exit(8);
  }
  echo 'MONTJAM_FORMAT_ASSOC_'.strtoupper($mode).'_OK '.wp_json_encode(['product'=>$pid,'variation'=>$vid,'associated_count'=>2,'format_extra_unchanged'=>true,'whole_price_unchanged'=>true],JSON_UNESCAPED_SLASHES)."\n";
}
if($mode==='apply'){
  wp_cache_flush();
  wc_delete_product_transients($pid);
  if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
  if(function_exists('rocket_clean_domain'))rocket_clean_domain();
  if(function_exists('w3tc_flush_all'))w3tc_flush_all();
  do_action('litespeed_purge_all');
  echo "MONTJAM_FORMAT_ASSOC_CACHE_CLEARED\n";
}
