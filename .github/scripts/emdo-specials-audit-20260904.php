<?php
if (!defined('ABSPATH')) { exit(1); }

global $wpdb, $wp_post_types;
function esa_out($label,$value=null){
    if (is_array($value)||is_object($value)) $value=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    echo $label . ($value===null?'':': '.(string)$value) . "\n";
}

$active=(array)get_option('active_plugins',[]);
$plugin_hits=[];
foreach($active as $p){ if (stripos($p,'emdo')!==false || stripos($p,'mercado')!==false || stripos($p,'special')!==false || stripos($p,'promo')!==false) $plugin_hits[]=$p; }
esa_out('ACTIVE_PLUGIN_HITS',$plugin_hits);

$pts=[];
foreach(get_post_types([], 'objects') as $name=>$obj){
    $label=$obj->labels->name ?? $obj->label ?? $name;
    $sing=$obj->labels->singular_name ?? '';
    if (preg_match('/especial|promo|oferta|destacad/i',$name.' '.$label.' '.$sing)) {
        $pts[$name]=['label'=>$label,'singular'=>$sing,'public'=>$obj->public,'show_ui'=>$obj->show_ui,'supports'=>get_all_post_type_supports($name)];
    }
}
esa_out('MATCHING_POST_TYPES',$pts);

// Also list non-core custom types to locate EMDO feature even if label differs.
$core=['post','page','attachment','revision','nav_menu_item','custom_css','customize_changeset','oembed_cache','user_request','wp_block','wp_template','wp_template_part','wp_global_styles','wp_navigation','wp_font_family','wp_font_face','product','product_variation','shop_order','shop_order_refund','shop_coupon','shop_order_placehold'];
$custom=[];
foreach(get_post_types([], 'objects') as $name=>$obj){
    if(!in_array($name,$core,true)){
      $custom[$name]=['label'=>$obj->labels->name ?? $obj->label ?? $name,'singular'=>$obj->labels->singular_name ?? '','public'=>$obj->public,'show_ui'=>$obj->show_ui];
    }
}
esa_out('CUSTOM_POST_TYPES',$custom);

// Recent records of likely types.
foreach(array_keys($pts) as $pt){
    $rows=get_posts(['post_type'=>$pt,'post_status'=>['publish','draft','private','pending'],'posts_per_page'=>20,'orderby'=>'ID','order'=>'DESC']);
    $out=[];
    foreach($rows as $r){
      $meta=[];
      foreach(get_post_meta($r->ID) as $k=>$vals){
        if(preg_match('/image|img|product|precio|price|link|url|button|cta|sub|text|desc|active|home|featured|special|promo/i',$k)) $meta[$k]=array_map('maybe_unserialize',$vals);
      }
      $out[]=['id'=>$r->ID,'title'=>$r->post_title,'status'=>$r->post_status,'excerpt'=>$r->post_excerpt,'content'=>$r->post_content,'meta'=>$meta,'thumb'=>(int)get_post_thumbnail_id($r->ID)];
    }
    esa_out('POSTS_'.$pt,$out);
}

$front_id=(int)get_option('page_on_front');
$front=$front_id?get_post($front_id):null;
esa_out('FRONT_PAGE',['id'=>$front_id,'title'=>$front?$front->post_title:'','content'=>$front?$front->post_content:'']);
if($front_id){
  $front_meta=[];
  foreach(get_post_meta($front_id) as $k=>$vals){
    if(preg_match('/special|promo|oferta|destacad|home|elementor|template/i',$k)) $front_meta[$k]=array_map('maybe_unserialize',$vals);
  }
  esa_out('FRONT_META',$front_meta);
}

// Scan plugin files for strings that strongly identify the feature; filenames only + matched line snippets.
$plugin_dir=WP_PLUGIN_DIR;
$matches=[];
$rii=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_dir,FilesystemIterator::SKIP_DOTS));
$count=0;
foreach($rii as $file){
  if($count>25000) break;
  $count++;
  if(!$file->isFile()) continue;
  $ext=strtolower($file->getExtension());
  if(!in_array($ext,['php','js','css'],true)) continue;
  $path=$file->getPathname();
  // Focus on custom-ish plugins to keep runtime reasonable.
  $rel=str_replace($plugin_dir.'/','',$path);
  if(!preg_match('/emdo|mercado|special|promo|home/i',$rel)) continue;
  $txt=@file_get_contents($path);
  if($txt===false) continue;
  if(preg_match('/especial|promoci[oó]n|special|promo/i',$txt)){
     preg_match_all('/^.*(?:especial|promoci[oó]n|special|promo).*$/mi',$txt,$mm);
     $matches[$rel]=array_slice($mm[0],0,8);
  }
}
esa_out('PLUGIN_CODE_MATCHES',$matches);
