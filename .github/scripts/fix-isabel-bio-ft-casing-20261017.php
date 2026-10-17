<?php
/** Isabel only: restore literal BIO&FT acronym in product titles, no product content or slugs changed. */
if(!defined('ABSPATH'))exit(1);
if(!class_exists('MDO_Isabel_Quality')||!method_exists('MDO_Isabel_Quality','canonical_title'))throw new RuntimeException('Primero despliegue EMDO 1.0.64');
global $wpdb;
if(!in_array(strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST)),array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('No es producción');
$table=MDO_Database::table('suppliers');$id=(int)$wpdb->get_var($wpdb->prepare("SELECT vendor_user_id FROM {$table} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'));
if($id!==4964)throw new RuntimeException('Vendedor inesperado');
$products=get_posts(array('post_type'=>'product','post_status'=>array('draft','publish','private'),'author'=>$id,'posts_per_page'=>-1,'fields'=>'ids'));
if(count($products)!==103)throw new RuntimeException('El catálogo de Isabel no es de 103 fichas: '.count($products));
$changed=0;$checked=0;$errors=array();$examples=array();
foreach($products as $pid){
 $p=wc_get_product((int)$pid);if(!$p)throw new RuntimeException('Producto ausente '.(int)$pid);
 $name=$p->get_name();
 $desired=MDO_Isabel_Quality::canonical_title($name);
 $checked++;
 if($desired===$name)continue;
 $slug=(string)get_post_field('post_name',$pid);
 $status=$p->get_status();$visibility=$p->get_catalog_visibility();
 $p->set_name($desired);$p->save();
 if((string)get_post_field('post_name',$pid)!==$slug)
  throw new RuntimeException('El slug SEO ha cambiado inesperadamente en '.$pid);
 $now=wc_get_product($pid);
 $visible=(string)$now->get_name();
 $human=html_entity_decode($visible,ENT_QUOTES|ENT_HTML5,'UTF-8');
 if(!str_contains($human,'BIO&FT') || $now->get_status()!==$status || $now->get_catalog_visibility()!==$visibility)
  throw new RuntimeException('Validación posterior fallida en '.$pid.': '.$visible);
 $changed++;
 if(count($examples)<12)$examples[]=array('id'=>$pid,'before'=>$name,'after'=>$visible);
}
echo 'ISABEL_BIOFT_FIXED='.wp_json_encode(array('checked'=>$checked,'changed'=>$changed,
 'errors'=>$errors,'examples'=>$examples,'only_titles'=>true,'other_vendors_untouched'=>true),
 JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
