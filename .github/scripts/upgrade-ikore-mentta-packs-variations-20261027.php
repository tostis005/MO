<?php
/**
 * Safe in-place migration of THREE existing Ikore Mentta-only packs to variable products.
 * Modes: MDO_IKORE_VAR_MODE=preflight|apply|verify wp eval-file SCRIPT.php
 * Product IDs, SKUs, fixed Ikore vendor commission, and Mentta exclusions stay unchanged.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WC_Product_Variable')){fwrite(STDERR,"WOOCOMMERCE_WPCLI_REQUIRED\n");exit(2);}
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)){fwrite(STDERR,"PRODUCTION_ONLY\n");exit(2);}
$mode=(string)(getenv('MDO_IKORE_VAR_MODE')?:'preflight');
if(!in_array($mode,['preflight','apply','verify'],true))exit(2);
foreach(['emdo_mentta_exclusive_ids','emdo_musee_pack_stock_ok'] as $f)if(!function_exists($f)){fwrite(STDERR,"MENTTA_EXCLUSION_OR_STOCK_GUARD_MISSING $f\n");exit(3);}
if(!class_exists('WC_Mentta_Plugin')){fwrite(STDERR,"MENTTA_EXPORTER_MISSING\n");exit(3);}
$vendor=get_user_by('login','ikore-kofi');
if(!$vendor||(int)$vendor->ID!==4970){fwrite(STDERR,"SOURCE_VENDOR_MISMATCH\n");exit(3);}
$vid=(int)$vendor->ID;
$pricesIncludeTax=wc_prices_include_tax();
$baseSKU=[
 'villa'=>'46002/V0122','typica'=>'46002/V0123','geisha'=>'46002/V0124',
 'colombia'=>'46002/V0116','kenia'=>'46002/V0127','panama'=>'46002/V0112'
];
$sourceNames=[
 'villa'=>'Costa Rica Villa Sarchí Miel','typica'=>'Costa Rica Typica Mejorado',
 'geisha'=>'Costa Rica Geisha','colombia'=>'Colombia El Indio',
 'kenia'=>'Kenia Washed Adumu','panama'=>'Panamá Chiriquí'
];
$packs=[
 'esencia-costa-rica'=>['id'=>21904,'sku'=>'IKORE-MENTTA-PACK-COSTA-RICA','gross'=>49.80,'title'=>'ESENCIA DE COSTA RICA','subtitle'=>'Tres perfiles de una misma tierra','items'=>['villa','typica','geisha']],
 'ruta-cuatro-origenes'=>['id'=>21906,'sku'=>'IKORE-MENTTA-PACK-4-ORIGENES','gross'=>74.90,'title'=>'RUTA DE LOS 4 ORÍGENES','subtitle'=>'Costa Rica · Colombia · Kenia · Panamá','items'=>['villa','colombia','kenia','panama']],
 'coleccion-completa'=>['id'=>21908,'sku'=>'IKORE-MENTTA-PACK-6-CAFES','gross'=>105.80,'title'=>'COLECCIÓN COMPLETA','subtitle'=>'Seis cafés · cuatro países','items'=>['villa','typica','geisha','colombia','kenia','panama']]
];
$errors=[];$src=[];$referenceAttr=null;$grinds=[];
foreach($baseSKU as $key=>$sku){
 $pid=wc_get_product_id_by_sku($sku);$p=$pid?wc_get_product($pid):false;
 if(!$p||!$p->is_type('variable')||$p->get_status()!=='publish'||(int)get_post_field('post_author',$pid)!==$vid){$errors[]="SOURCE_PARENT_BAD $key";continue;}
 $attr=null;foreach($p->get_attributes() as $a)if($a instanceof WC_Product_Attribute && strtolower($a->get_name())==='molienda')$attr=$a;
 if(!$attr||!$attr->get_variation()||$attr->is_taxonomy()){$errors[]="SOURCE_GRIND_ATTR_MISSING $key";continue;}
 $opts=array_values($attr->get_options());
 if(!$referenceAttr){$referenceAttr=$attr;$grinds=$opts;}
 if($opts!==$grinds||count($opts)!==7){$errors[]="GRIND_OPTIONS_MISMATCH $key";continue;}
 $photo=(int)$p->get_image_id();
 if(!$photo||!is_readable((string)get_attached_file($photo))){$errors[]="SOURCE_PHOTO_MISSING $key";continue;}
 $versions=[];foreach($grinds as $n=>$grind){
  $varid=(int)wc_get_product_id_by_sku($sku.'-250-'.($n+1));$v=$varid?wc_get_product($varid):false;
  if(!$v||!$v->is_type('variation')||(int)$v->get_parent_id()!==(int)$pid||$v->get_status()!=='publish'||
   $v->get_attribute('peso')!=='250 g'||$v->get_attribute('molienda')!==$grind){$errors[]="SOURCE_GRIND_VARIATION_BAD $key ".$grind;continue;}
  if(!$v->is_in_stock())$errors[]="SOURCE_VARIATION_OUT_OF_STOCK $key ".$grind;
  $versions[$grind]=$varid;
 }
 $src[$key]=['id'=>(int)$pid,'image'=>$photo,'versions'=>$versions,'grind_attr'=>$attr];
}
$root=get_term_by('slug','mentta','product_cat');
$child=get_term_by('slug','mentta-cafes','product_cat');
if(!$root||!$child||$child->parent!==$root->term_id)$errors[]='MENTTA_CATEGORY_MISSING';
$hidden=emdo_mentta_exclusive_ids();
foreach($packs as $key=>&$pack){
 $id=$pack['id'];$p=wc_get_product($id);
 if(!$p||!in_array($p->get_type(),['simple','variable'],true) ||
   (int)wc_get_product_id_by_sku($pack['sku'])!==$id ||
   (int)get_post_field('post_author',$id)!==$vid ||
   !in_array($id,$hidden,true) ||
   (string)get_post_meta($id,'_emdo_mentta_only',true)!=='yes'||
   (string)get_post_meta($id,'_emdo_ikore_mentta_pack_key',true)!==$key ||
   (string)get_post_meta($id,'_emdo_mentta_pack_key',true)!=='ikore-'.$key ||
   $p->get_status()!=='publish' || !$p->get_image_id() ||
   !in_array((int)$root->term_id,array_map('intval',$p->get_category_ids()),true) ||
   !in_array((int)$child->term_id,array_map('intval',$p->get_category_ids()),true)){$errors[]="PACK_PARENT_BAD $key";continue;}
 $oldLines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 if(!is_array($oldLines)||count($oldLines)!==count($pack['items'])){$errors[]="PACK_LINES_BAD $key";continue;}
 $commission=get_post_meta($id,'_wcfmmp_commission',true);
 if(!is_array($commission)||($commission['commission_mode']??'')!=='fixed'){$errors[]="PACK_VENDOR_COMMISSION_BAD $key";continue;}
 $pack['previousType']=$p->get_type();
 $pack['commission']=$commission;
}
unset($pack);
if($grinds!==['En grano','Italiana','Espresso','Prensa francesa','Cold brew','Aeropress','Filtro y V60'])$errors[]='UNEXPECTED_SOURCE_MOLIENDA_VALUES';
echo 'IKORE_VAR_PREFLIGHT '.wp_json_encode([
 'mode'=>$mode,'sources'=>count($src),'grinds'=>$grinds,'pack_ids'=>array_column($packs,'id'),
 'types'=>array_map(static fn($p)=>$p['previousType']??'not_verified',$packs),
 'errors'=>$errors
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($errors)exit(4);
// Read-only inspect source variable serialization without logging customer data.
if($mode==='preflight'){
 $req=new WP_REST_Request('GET','/mentta_marketplace/products');
 $req->set_param('product_id',$src['villa']['id']);
 $resp=WC_Mentta_Plugin::get_products($req);
 $payload=$resp instanceof WP_REST_Response?$resp->get_data():[];
 $data=is_object($payload)?($payload->data??[]):(is_array($payload)?($payload['data']??[]):[]);
 $first=is_array($data)?($data[0]??[]):[];
 $shape=[];foreach((array)$first as $k=>$v){$lk=strtolower((string)$k);
  if(preg_match('/attrib|variat|option|sku|price|visible|type|stock/i',$lk))
   $shape[$k]=is_array($v)?['type'=>'array','count'=>count($v),'first_keys'=>isset($v[0])&&is_array($v[0])?array_keys($v[0]):[]]:gettype($v);
 }
 echo 'IKORE_EXISTING_VARIABLE_MENTTA_FIELDS '.wp_json_encode($shape,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 exit(0);
}
function ikore_var_lines($items,$src,$grind){
 $lines=[];foreach($items as $key)$lines[]=[
  'id'=>(int)$src[$key]['versions'][$grind],
  'source_product_id'=>(int)$src[$key]['id'],
  'qty'=>1,'image'=>(int)$src[$key]['image'],
  'label'=>$GLOBALS['sourceNames'][$key].', 250 g, '.$grind
 ];return $lines;
}
function ikore_var_center($canvas,$text,$size,$y,$c,$font){
 $bb=imagettfbbox($size,0,$font,$text);
 $x=(int)round((1200-($bb[2]-$bb[0]))/2);
 imagettftext($canvas,$size,0,$x,$y,$c,$font,$text);
}
function ikore_var_cover($key,$conf,$src,$pid){
 if(!function_exists('imagecreatetruecolor')||!function_exists('imagettftext'))throw new RuntimeException('GD missing');
 $font='/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';$bold='/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
 if(!is_readable($font)||!is_readable($bold))throw new RuntimeException('Font unavailable');
 $im=imagecreatetruecolor(1200,1200);
 $cream=imagecolorallocate($im,249,245,235);$green=imagecolorallocate($im,24,56,45);$gold=imagecolorallocate($im,196,156,94);
 $ink=imagecolorallocate($im,29,47,39);$white=imagecolorallocate($im,255,255,255);
 imagefilledrectangle($im,0,0,1199,1199,$cream);
 // The same elegant header and lower accent on all three packs. Bag photos are copied pixel-for-pixel from WP attachments.
 imagefilledrectangle($im,0,0,1199,235,$green);
 imagefilledrectangle($im,0,230,1199,235,$gold);
 ikore_var_center($im,'CAFÉ DE ESPECIALIDAD',28,62,$gold,$bold);
 ikore_var_center($im,$conf['title'],33,124,$white,$bold);
 ikore_var_center($im,$conf['subtitle'],19,172,$white,$font);
 $n=count($conf['items']);$cols=$n===4?2:3;$rows=(int)ceil($n/$cols);
 $margin=$cols===2?105:38;$gap=18;
 $w=(int)floor((1200-2*$margin-($cols-1)*$gap)/$cols);
 $mainTop=$rows===1?296:263;$h=$rows===1?690:393;
 $labelSize=$cols===2?18:14;
 foreach($conf['items'] as $i=>$item){
  $row=intdiv($i,$cols);$col=$i%$cols;
  $x=$margin+$col*($w+$gap);$y=$mainTop+$row*($h+15);
  imagefilledrectangle($im,$x,$y,$x+$w,$y+$h,$white);
  imagerectangle($im,$x,$y,$x+$w,$y+$h,$gold);
  $sourcePath=get_attached_file($src[$item]['image']);
  $photo=@imagecreatefromstring((string)file_get_contents($sourcePath));
  if(!$photo)throw new RuntimeException('Cannot decode source photo '.$item);
  $sw=imagesx($photo);$sh=imagesy($photo);
  $photoWidth=$w-24;$photoHeight=$rows===1?543:275;
  $scale=min($photoWidth/$sw,$photoHeight/$sh);
  $tw=max(1,(int)floor($scale*$sw));$th=max(1,(int)floor($scale*$sh));
  imagecopyresampled($im,$photo,$x+12+(int)(($photoWidth-$tw)/2),$y+12+(int)(($photoHeight-$th)/2),0,0,$tw,$th,$sw,$sh);
  imagedestroy($photo);
  $country=str_starts_with($item,'villa')||in_array($item,['typica','geisha'])?'Costa Rica':($item==='colombia'?'Colombia':($item==='kenia'?'Kenia':'Panamá'));
  $display=str_replace(['Costa Rica ','Colombia ','Kenia ','Panamá '],'',$GLOBALS['sourceNames'][$item]);
  $textY=$y+$photoHeight+48;
  imagettftext($im,$labelSize,0,$x+16,$textY,$ink,$bold,$country);
  imagettftext($im,$labelSize,0,$x+16,$textY+26,$ink,$bold,$display);
  imagettftext($im,15,0,$x+16,$textY+50,$ink,$font,'250 g');
 }
 imagefilledrectangle($im,0,1099,1199,1199,$green);
 imagefilledrectangle($im,0,1098,1199,1102,$gold);
 $weight=count($conf['items'])===3?'0,75':(count($conf['items'])===4?'1':'1,5');
 ikore_var_center($im,$n.' CAFÉS · '.$weight.' KG · 100 % ARÁBICA',24,1162,$white,$bold);
 $uploads=wp_upload_dir();$folder=trailingslashit($uploads['basedir']).'mentta-ikore-packs';
 if(!is_dir($folder)&&!wp_mkdir_p($folder))throw new RuntimeException('Cannot create cover folder');
 $path="$folder/pack-ikore-$key-fotos-originales-sin-molienda-v2.jpg";
 if(!imagejpeg($im,$path,92))throw new RuntimeException('Cannot save cover '.$key);
 imagedestroy($im);
 require_once ABSPATH.'wp-admin/includes/image.php';
 $old=(int)get_post_meta($pid,'_emdo_ikore_clean_cover_attachment',true);
 if($old && get_post($old)){$attachment=$old;update_attached_file($old,$path);}
 else{
  $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>'Pack café de especialidad '.$conf['title'].' — fotografías reales','post_status'=>'inherit'],$path,$pid,true);
  if(is_wp_error($attachment))throw new RuntimeException('Cannot create attachment '.$attachment->get_error_message());
 }
 wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$path));
 update_post_meta($attachment,'_wp_attachment_image_alt','Pack de degustación '.$conf['title'].', fotografías originales de cada bolsa');
 update_post_meta($pid,'_emdo_ikore_clean_cover_attachment',(int)$attachment);
 return (int)$attachment;
}
if($mode==='apply'){
 foreach($packs as $key=>$pack){
  $pid=$pack['id'];
  try{
   // Keep original post ID/SKU and exclusion. Convert only the Woo product_type taxonomy.
   if($pack['previousType']==='simple'){
    $updated=wp_set_object_terms($pid,'variable','product_type',false);
    if(is_wp_error($updated))throw new RuntimeException('Cannot set variable type');
    clean_post_cache($pid);wc_delete_product_transients($pid);
   }
   $p=new WC_Product_Variable($pid);
   if(!$p->exists())throw new RuntimeException('Parent invalid after conversion');
   $attr=new WC_Product_Attribute();
   $attr->set_id(0);$attr->set_name('Molienda');$attr->set_options($grinds);
   $attr->set_position(0);$attr->set_visible(true);$attr->set_variation(true);
   $p->set_attributes([$attr]);
   $p->set_default_attributes(['molienda'=>'En grano']);
   $p->set_status('publish');$p->set_catalog_visibility('visible');$p->set_featured(false);
   $p->set_tax_status('taxable');$p->set_tax_class(wc_get_product($src['villa']['id'])->get_tax_class());
   $p->set_weight((string)wc_get_weight(count($pack['items'])*.25,get_option('woocommerce_weight_unit'),'kg'));
   $p->set_manage_stock(false);$p->set_stock_status('instock');
   $p->set_short_description('<p>Pack de degustación de '.count($pack['items']).' cafés de especialidad de 250 g cada uno. <strong>Elige cómo quieres recibirlos: en grano o molidos para tu cafetera.</strong> Una única molienda para todas las bolsas, sin cambio de precio.</p>');
   $description='<p>Descubre una selección de cafés 100 % arábica de especialidad tostados por Ikore Kofi. '.$pack['subtitle'].'.</p>';
   $description.='<h2>¿Qué cafés incluye este pack?</h2><ul>';
   foreach($pack['items'] as $item)$description.='<li>'.esc_html($GLOBALS['sourceNames'][$item]).' — 250 g.</li>';
   $description.='</ul><h2>Elige la molienda para tu cafetera</h2><p><strong>Selecciona cómo quieres recibir tus cafés: en grano o molidos para tu cafetera.</strong> Puedes elegir En grano, Italiana, Espresso, Prensa francesa, Cold brew, Aeropress o Filtro y V60. La opción elegida se aplica a todas las bolsas del pack y <strong>no modifica el precio</strong>.</p>';
   $description.='<p>Se entregan '.count($pack['items']).' bolsas originales de 250 g de Ikore Kofi, sin molinillo ni cafetera. Para comparar los perfiles, utiliza una misma receta y temperatura en cada café. Guarda los envases cerrados, protegidos de la luz, humedad y olores. Consulta fecha y lote en cada bolsa. Las notas de cata son descriptores sensoriales, no ingredientes añadidos.</p>';
   $p->set_description(wp_kses_post($description));
   $p->set_gallery_image_ids(array_values(array_map(static fn($k)=>(int)$src[$k]['image'],$pack['items'])));
   $p->update_meta_data('_emdo_mentta_only','yes');$p->update_meta_data('_yoast_wpseo_meta-robots-noindex','1');
   $p->update_meta_data('_emdo_mentta_pack_lines',ikore_var_lines($pack['items'],$src,'En grano'));
   $p->save();
   foreach($grinds as $n=>$grind){
    $sku=$pack['sku'].'-M'.($n+1);
    $variationExistingId=(int)wc_get_product_id_by_sku($sku);
    if($variationExistingId){
     $v=wc_get_product($variationExistingId);
     if(!$v||!$v->is_type('variation')||(int)$v->get_parent_id()!==$pid)throw new RuntimeException('Variation SKU collision '.$sku);
    }else{$v=new WC_Product_Variation();}
    $v->set_parent_id($pid);$v->set_sku($sku);
    $v->set_attributes(['molienda'=>$grind]);
    $v->set_tax_status('taxable');$v->set_tax_class('parent');
    $stored=$pricesIncludeTax?$pack['gross']:$pack['gross']/1.10;
    $v->set_regular_price(wc_format_decimal($stored,6));
    $v->set_sale_price('');$v->set_price(wc_format_decimal($stored,6));
    $v->set_status('publish');
    $v->set_weight((string)wc_get_weight(count($pack['items'])*.25,get_option('woocommerce_weight_unit'),'kg'));
    $v->set_manage_stock(false);$v->set_stock_status('instock');
    $v->update_meta_data('_emdo_mentta_only','yes');
    $v->update_meta_data('_emdo_mentta_pack_key','ikore-'.$key);
    $v->update_meta_data('_emdo_mentta_pack_lines',ikore_var_lines($pack['items'],$src,$grind));
    $v->update_meta_data('_emdo_ikore_grind',$grind);
    $variationId=$v->save();
    wp_update_post(['ID'=>$variationId,'post_author'=>$GLOBALS['vid']]);
   }
   WC_Product_Variable::sync($pid);
   $cover=ikore_var_cover($key,$pack,$pid,$src);
   $p=wc_get_product($pid);$p->set_image_id($cover);$p->save();
   wc_delete_product_transients($pid);clean_post_cache($pid);
   echo 'IKORE_VAR_APPLIED '.wp_json_encode(['id'=>$pid,'price'=>$pack['gross'],'variations'=>count($grinds),'cover_id'=>$cover],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
  }catch(Throwable $e){
   $p=wc_get_product($pid);if($p){$p->set_status('draft');$p->save();}
   fwrite(STDERR,'IKORE_VAR_ABORTED_UNPUBLISHED '.$pid.' '.$e->getMessage()."\n");exit(7);
  }
 }
 if(function_exists('wp_cache_flush'))wp_cache_flush();
}
if($mode==='verify'||$mode==='apply'){
 if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
 foreach($packs as $key=>$pack){
  $p=wc_get_product($pack['id']);
  if(!$p||!$p->is_type('variable')||$p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible'
   ||!in_array($pack['id'],emdo_mentta_exclusive_ids(),true)||count($p->get_children())!==7
   ||count($p->get_gallery_image_ids())!==count($pack['items'])
   ||!is_readable((string)get_attached_file($p->get_image_id())))throw new RuntimeException('PACK_VERIFY_BAD '.$key);
  $varresult=[];
  foreach($grinds as $n=>$grind){
   $var=wc_get_product((int)wc_get_product_id_by_sku($pack['sku'].'-M'.($n+1)));
   if(!$var||$var->get_parent_id()!==$pack['id']||$var->get_attribute('molienda')!==$grind
    ||abs((float)wc_get_price_including_tax($var)-$pack['gross'])>.025 || !$var->is_in_stock())throw new RuntimeException('VARIATION_VERIFY_BAD '.$key.' '.$grind);
   $lines=get_post_meta($var->get_id(),'_emdo_mentta_pack_lines',true);
   if(!is_array($lines)||count($lines)!==count($pack['items']))throw new RuntimeException('STOCK_MAPPING_VERIFY_BAD '.$key.' '.$grind);
   foreach($lines as $line){
    $source=wc_get_product((int)$line['id']);
    if(!$source||$source->get_attribute('molienda')!==$grind)throw new RuntimeException('COMPONENT_GRIND_VERIFY_BAD '.$key.' '.$grind);
   }
   $varresult[]=['id'=>$var->get_id(),'molienda'=>$grind,'price'=>round(wc_get_price_including_tax($var),2)];
  }
  $request=new WP_REST_Request('GET','/mentta_marketplace/products');$request->set_param('product_id',$pack['id']);
  $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$pack['id'];
  $response=WC_Mentta_Plugin::get_products($request);
  $payload=$response instanceof WP_REST_Response?$response->get_data():[];
  $data=is_object($payload)?($payload->data??[]):(is_array($payload)?($payload['data']??[]):[]);
  $entry=is_array($data)?($data[0]??[]):[];
  if(!is_array($entry)||(int)($entry['Id']??0)!==$pack['id']||($entry['IsVisible']??null)!==true)throw new RuntimeException('MENTTA_PARENT_NOT_EXPORTED '.$key);
  $variableExport=[];foreach($entry as $k=>$value)if(preg_match('/attrib|variat|option|price|sku|type/i',(string)$k))
   $variableExport[$k]=is_array($value)?['count'=>count($value),'keys'=>isset($value[0])&&is_array($value[0])?array_keys($value[0]):[]]:gettype($value);
  echo 'IKORE_VAR_VERIFIED '.wp_json_encode(['id'=>$pack['id'],'price_including_tax'=>$pack['gross'],
    'status'=>'publish','public_excluded'=>true,'mentta_visible'=>true,'variations'=>$varresult,
    'mentta_fields'=>$variableExport,'cover_id'=>(int)$p->get_image_id()
   ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }
 echo "IKORE_THREE_VARIABLE_PACKS_VERIFIED\n";
}
