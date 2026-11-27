<?php
// Only add missing YITH product association rows to existing, unambiguous format blocks.
// Never rewrite prices, add-on options, stock, restrictions or product metadata.
if (!defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) exit(2);
if (!in_array(untrailingslashit(get_option('siteurl')),['https://www.elmercadodeorigen.com','https://elmercadodeorigen.com'],true)) exit(3);
global $wpdb;
$mode=getenv('EMDO_FORMAT_MODE') ?: 'audit';
if(!in_array($mode,['audit','apply','verify'],true)) exit(4);
$bt=$wpdb->prefix.'yith_wapo_blocks'; $at=$wpdb->prefix.'yith_wapo_addons'; $rt=$wpdb->prefix.'yith_wapo_blocks_assoc';
function emdo_fmt_query($sql){global $wpdb;$r=$wpdb->get_results($sql,ARRAY_A);if($wpdb->last_error)throw new RuntimeException('Database read failed');return $r;}
function emdo_fmt_ids($a){return array_values(array_unique(array_filter(array_map('intval',(array)$a))));}
function emdo_fmt_text($s){return strtolower(remove_accents((string)$s));}
function emdo_fmt_on($v){return in_array(strtolower((string)$v),['1','yes','true','on'],true);}
$known=['Hidalgo'=>[1350,1356,3948,3979,4160,4199,5045],'Montjam'=>[14264,14271,14275,14287,14294,14301,14305]];
$catalog=[];
foreach($known as $vendor=>$ids){
  $author=(int)get_post_field('post_author',$ids[0]);
  if(!$author)throw new RuntimeException('Missing reference vendor');
  $extra=get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>-1,'author'=>$author,'fields'=>'ids','suppress_filters'=>true]);
  foreach(array_unique(array_merge($ids,$extra)) as $id){
    $p=wc_get_product($id);if(!$p||$p->get_status()!=='publish'||!$p->is_type('variable'))continue;
    $n=emdo_fmt_text($p->get_name());
    if(!preg_match('/^(jamon|paleta)\b/',$n)||preg_match('/\b(sobres?|packs?|lonchead[oa]s?|deshuesad[oa]s?|mentta)\b/',$n))continue;
    if(!isset($p->get_attributes()['pa_tamano'])||!$p->get_attributes()['pa_tamano']->get_variation())continue;
    $catalog[(int)$id]=['vendor'=>$vendor,'product'=>$p,'kind'=>str_starts_with($n,'jamon')?'jamon':'paleta'];
  }
}
if(!isset($catalog[4160],$catalog[14275]))throw new RuntimeException('Required target missing');
$blocks=emdo_fmt_query("SELECT * FROM $bt WHERE visibility=1 AND product_association='products'");
$addons=emdo_fmt_query("SELECT * FROM $at WHERE visibility=1");
$rows=emdo_fmt_query("SELECT rule_id,object,type FROM $rt WHERE type='product'");
$assoc=[];$add=[];foreach($rows as $r)$assoc[(int)$r['rule_id']][]=(int)$r['object'];foreach($addons as $a)$add[(int)$a['block_id']][]=$a;
$report=[];$plans=[];$issues=[];$manifest=[];
foreach($catalog as $pid=>$entry){
  $p=$entry['product'];$vendor=$entry['vendor'];$kind=$entry['kind'];
  $children=get_posts(['post_type'=>'product_variation','post_parent'=>$pid,'post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','suppress_filters'=>true]);
  $children=emdo_fmt_ids($children);$variants=[];
  foreach($children as $vid){$v=wc_get_product($vid);if(!$v)continue;$variants[]=['id'=>$vid,'size'=>$v->get_attributes()['pa_tamano']??'','base'=>$v->get_price('edit'),'display_base'=>wc_get_price_to_display($v),'purchasable'=>$v->is_purchasable()&&$v->is_in_stock()];}
  $candidates=[];
  foreach($blocks as $b){
    $bid=(int)$b['id'];$bs=maybe_unserialize($b['settings']);$rules=$bs['rules']??[];
    $parents=emdo_fmt_ids($rules['show_in_products']??[]);$have=emdo_fmt_ids($assoc[$bid]??[]);
    if(!in_array($pid,$parents,true)&&!in_array($pid,$have,true)&&!array_intersect($children,$have)&&!array_intersect($children,$parents))continue;
    foreach($add[$bid]??[] as $a){
      $as=maybe_unserialize($a['settings']);$o=maybe_unserialize($a['options']);$labels=(array)($o['label']??[]);
      $txt=emdo_fmt_text(implode(' ',$labels));
      if(($as['type']??'')!=='select'||!str_contains($txt,'lonche')||!preg_match('/entera|entero|sin cortar/',$txt))continue;
      $candidates[]=[$b,$a,$as,$o,$rules,$parents,$have];
    }
  }
  $r=['id'=>$pid,'vendor'=>$vendor,'title'=>$p->get_name(),'url'=>get_permalink($pid),'variants'=>$variants,'candidates'=>array_map(fn($c)=>['block'=>$c[0]['id'],'name'=>$c[0]['name'],'addon'=>$c[1]['id'],'labels'=>$c[3]['label']??[],'prices'=>$c[3]['price']??[]],$candidates)];
  if(count($candidates)!==1){$r['issue']='no unique existing format block';$issues[]=$pid.': '.$r['issue'];$report[]=$r;continue;}
  [$b,$a,$as,$o,$rules,$parents,$have]=$candidates[0];$bid=(int)$b['id'];
  $options=[];
  foreach((array)$o['label'] as $i=>$label){$options[]=['label'=>$label,'enabled'=>($o['addon_enabled'][$i]??'yes'),'price'=>$o['price'][$i]??null,'sale'=>$o['price_sale'][$i]??'','method'=>$o['price_method'][$i]??'','type'=>$o['price_type'][$i]??''];}
  $r['options']=$options;$r['block']=$bid;
  $r['missing']=array_values(array_diff(array_merge([$pid],$children),$have));
  $r['rules']=['parents'=>$parents,'exclude'=>$rules['exclude_products']??null,'exclude_list'=>$rules['exclude_products_products']??null,'variation_rules'=>$as['enable_rules_variations']??null,'conditional_variations'=>$as['conditional_rule_variations']??null,'conditional_rules'=>$as['enable_rules']??null];
  $safe=(in_array($pid,$parents,true)||in_array($pid,$have,true))&&!emdo_fmt_on($b['exclude_products']??0)&&!emdo_fmt_on($rules['exclude_products']??'')&&empty($rules['exclude_products_products'])&&empty($rules['exclude_products_categories'])&&!emdo_fmt_on($as['enable_rules_variations']??'')&&empty($as['conditional_rule_variations'])&&!emdo_fmt_on($as['enable_rules']??'');
  if($r['missing']&&!$safe){$r['issue']='ambiguous or intentionally restricted block; not modified';$issues[]=$pid.': '.$r['issue'];}
  elseif($r['missing'])$plans[]=['pid'=>$pid,'block'=>$bid,'missing'=>$r['missing'],'option_hash'=>hash('sha256',$a['options']),'addon'=>(int)$a['id']];
  if($vendor==='Montjam'){
    foreach($options as $opt){$n=emdo_fmt_text($opt['label']);$expected=str_contains($n,'cuchillo')?($kind==='jamon'?57.75:46.20):(str_contains($n,'deshues')?17.325:null);
      if($expected!==null&&(!is_numeric($opt['price'])||abs((float)$opt['price']-$expected)>0.001))$issues[]=$pid.': Montjam historical tariff differs: '.$opt['label'].' '.$opt['price'];
    }
  }
  $manifest[]=['id'=>$pid,'vendor'=>$vendor,'url'=>get_permalink($pid),'variants'=>$variants,'options'=>$options];
  $report[]=$r;
}
echo 'IBERICO_REPORT '.wp_json_encode(['mode'=>$mode,'products'=>$report,'issues'=>$issues,'plans'=>$plans],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($mode==='apply'){
  if($wpdb->query('START TRANSACTION')===false)throw new RuntimeException('Transaction failed');
  try{
    foreach($plans as $plan){
      $raw=$wpdb->get_var($wpdb->prepare("SELECT options FROM $at WHERE id=%d",$plan['addon']));
      if(hash('sha256',(string)$raw)!==$plan['option_hash'])throw new RuntimeException('Options changed concurrently');
      foreach($plan['missing'] as $oid){
        $exists=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $rt WHERE rule_id=%d AND object=%s AND type='product'",$plan['block'],(string)$oid));
        if(!$exists&&!$wpdb->insert($rt,['rule_id'=>$plan['block'],'object'=>(string)$oid,'type'=>'product']))throw new RuntimeException('Insert failed');
      }
    }
    if($wpdb->query('COMMIT')===false)throw new RuntimeException('Commit failed');
  }catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
  foreach($plans as $plan){$pid=$plan['pid'];clean_post_cache($pid);wc_delete_product_transients($pid);if(function_exists('rocket_clean_post'))rocket_clean_post($pid);}
  if($plans)wp_cache_flush();
  echo 'IBERICO_APPLIED '.wp_json_encode($plans)."\n";
}
if($mode==='verify'){
  echo 'IBERICO_MANIFEST '.wp_json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
  if($plans||$issues){echo "IBERICO_REVIEW_REQUIRED\n";}else{echo "IBERICO_ASSOCIATIONS_VERIFIED\n";}
}
