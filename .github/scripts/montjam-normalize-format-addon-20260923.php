<?php
if(!defined('ABSPATH')){fwrite(STDERR,"ABORT\n");exit(2);}
global $wpdb;
$blocks=$wpdb->prefix.'yith_wapo_blocks'; $addons=$wpdb->prefix.'yith_wapo_addons';
$products=[14264=>'jamon',14271=>'jamon',14287=>'jamon',14294=>'jamon',14275=>'paleta',14301=>'paleta',14305=>'paleta'];
foreach([$blocks,$addons] as $t){if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t))!==$t){fwrite(STDERR,"Missing $t\n");exit(3);}}
$out=[];
foreach($products as $pid=>$kind){
  $p=wc_get_product($pid); if(!$p||!$p->is_type('variable')){fwrite(STDERR,"Bad product $pid\n");exit(4);}
  $prod=wp_get_object_terms($pid,'pa_productor',['fields'=>'slugs']);
  if(is_wp_error($prod)||!in_array('montjam',$prod,true)){fwrite(STDERR,"Not Montjam $pid\n");exit(5);}
  $isj=$kind==='jamon'; $knife=$isj?'57.75':'46.20'; $boneless='17.325';
  $name='Montjam · Formato · '.$pid;
  $bids=$wpdb->get_col($wpdb->prepare("SELECT id FROM `$blocks` WHERE name=%s ORDER BY id",$name));
  if(count($bids)>1){fwrite(STDERR,"Duplicate block $pid\n");exit(6);}
  $bsettings=['name'=>$name,'priority'=>'1','rules'=>['show_in'=>'products','show_in_products'=>[(string)$pid],'show_in_categories'=>'','exclude_products'=>'','exclude_products_products'=>'','exclude_products_categories'=>'','show_to'=>'all','show_to_user_roles'=>'','show_to_membership'=>'']];
  $bdata=['user_id'=>null,'vendor_id'=>'0','settings'=>maybe_serialize($bsettings),'priority'=>'1.00000','visibility'=>1,'last_update'=>current_time('mysql',true),'name'=>$name,'product_association'=>'products','exclude_products'=>0,'user_association'=>'all','exclude_users'=>0];
  if($bids){$bid=(int)$bids[0]; if($wpdb->update($blocks,$bdata,['id'=>$bid])===false){fwrite(STDERR,"Block update failed $pid\n");exit(7);}}
  else{$bdata['creation_date']=current_time('mysql',true); if(!$wpdb->insert($blocks,$bdata)){fwrite(STDERR,"Block insert failed $pid\n");exit(8);} $bid=(int)$wpdb->insert_id;}
  $settings=['type'=>'select','title'=>'FORMATO','title_in_cart'=>'no','title_in_cart_opt'=>'','description'=>'','required'=>'','show_image'=>'','image'=>'','image_replacement'=>'','options_images_position'=>'','show_as_toggle'=>'','hide_options_images'=>'','hide_options_label'=>'','hide_options_prices'=>'','hide_products_prices'=>'','show_add_to_cart'=>'','show_sku'=>'','show_stock'=>'','show_quantity'=>'','show_in_a_grid'=>'','options_per_row'=>'','options_width'=>'','select_width'=>'','image_position'=>'','label_content_align'=>'','image_equal_height'=>'','images_height'=>'','label_position'=>'','label_padding'=>'','description_position'=>'','product_out_of_stock'=>'','enable_rules'=>'','enable_rules_variations'=>'','conditional_logic_display'=>'show','conditional_rule_variations'=>'','conditional_set_conditions'=>'','conditional_logic_display_if'=>'all','conditional_rule_addon'=>['empty'],'conditional_rule_addon_is'=>[''],'first_options_selected'=>'','first_free_options'=>'','selection_type'=>'','enable_min_max'=>'','min_max_rule'=>'','min_max_value'=>'','sell_individually'=>'no','enable_min_max_numbers'=>'','numbers_min'=>'','numbers_max'=>'','text_content'=>'','heading_text'=>'','heading_type'=>'','heading_color'=>'','separator_style'=>'','separator_width'=>'','separator_size'=>'','separator_color'=>'','conditional_logic'=>[]];
  $labels=['Pieza entera','Loncheado a cuchillo + huesos + taquitos','Deshuesado'];
  $desc=['',$isj?'Loncheado a cuchillo por un profesional. Se entrega en sobres al vacío junto con los huesos y los taquitos aprovechables de la pieza.':'Paleta loncheada a cuchillo por un profesional. Se entrega en sobres al vacío junto con los huesos y los taquitos aprovechables de la pieza.','La pieza se entrega deshuesada y envasada al vacío para facilitar su almacenamiento y corte en casa.'];
  $options=['default'=>['yes','no','no'],'addon_enabled'=>['yes','yes','yes'],'label'=>$labels,'description'=>$desc,'image'=>['','',''],'price_method'=>['free','increase','increase'],'price'=>['0.00',$knife,$boneless],'price_sale'=>['','',''],'price_type'=>['fixed','fixed','fixed'],'show_image'=>['no','no','no'],'label_in_cart'=>['no','no','no']];
  $aids=$wpdb->get_col($wpdb->prepare("SELECT id FROM `$addons` WHERE block_id=%d ORDER BY priority,id",$bid));
  if(count($aids)>1){fwrite(STDERR,"Multiple addons $pid\n");exit(9);}
  $adata=['block_id'=>$bid,'settings'=>maybe_serialize($settings),'options'=>maybe_serialize($options),'priority'=>'1.00000','visibility'=>1,'last_update'=>current_time('mysql',true)];
  if($aids){$aid=(int)$aids[0]; if($wpdb->update($addons,$adata,['id'=>$aid])===false){fwrite(STDERR,"Addon update failed $pid\n");exit(10);}}
  else{$adata['creation_date']=current_time('mysql',true);if(!$wpdb->insert($addons,$adata)){fwrite(STDERR,"Addon insert failed $pid\n");exit(11);} $aid=(int)$wpdb->insert_id;}
  $b=maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT settings FROM `$blocks` WHERE id=%d",$bid)));
  $a=maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT settings FROM `$addons` WHERE id=%d",$aid)));
  $o=maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT options FROM `$addons` WHERE id=%d",$aid)));
  if(($b['rules']['show_in_products']??[])!==[(string)$pid]||($a['enable_rules_variations']??null)!==''||($a['conditional_rule_variations']??null)!==''||($o['label']??[])!==$labels||($o['price']??[])!==['0.00',$knife,$boneless]){fwrite(STDERR,"Verify failed $pid\n");exit(12);}
  $out[]=['product_id'=>$pid,'title'=>$p->get_name(),'kind'=>$kind,'published_variations'=>count(array_filter($p->get_children(),fn($vid)=>($v=wc_get_product($vid))&&$v->get_status()==='publish')),'block_id'=>$bid,'addon_id'=>$aid,'labels'=>$labels,'prices'=>['piece'=>'0.00','knife'=>$knife,'boneless'=>$boneless],'url'=>get_permalink($pid)];
}
wp_cache_flush();
if(class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
if(function_exists('rocket_clean_domain')) rocket_clean_domain();
if(function_exists('w3tc_flush_all')) w3tc_flush_all();
do_action('litespeed_purge_all');
echo "MONTJAM_FORMAT_FIXED: ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
