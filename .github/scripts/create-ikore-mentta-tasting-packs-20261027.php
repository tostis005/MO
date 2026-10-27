<?php
/**
 * Publish three Ikore Kofi Mentta-only tasting packs.
 * Production WP-CLI: MDO_IKORE_PACK_MODE=preflight|apply|verify wp eval-file FILE
 * Actual original source WooCommerce images are used; artwork is a deterministic collage.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WC_Product_Simple')) { fwrite(STDERR, "WPCLI_WOO_REQUIRED\n"); exit(2); }
if (!in_array(strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST)), ['elmercadodeorigen.com','www.elmercadodeorigen.com'], true)) { fwrite(STDERR,"PRODUCTION_ONLY\n"); exit(2); }
$mode = (string)(getenv('MDO_IKORE_PACK_MODE') ?: 'preflight');
if (!in_array($mode, ['preflight','apply','verify'], true)) exit(3);
foreach (['emdo_mentta_exclusive_ids','emdo_mentta_exclusive_public_request','emdo_mj_is_store_api_request'] as $guard) {
 if (!function_exists($guard)) { fwrite(STDERR, "MENTTA_GUARD_MISSING $guard\n"); exit(4); }
}
if (!class_exists('WC_Mentta_Plugin')) { fwrite(STDERR,"MENTTA_EXPORTER_MISSING\n");exit(4); }
$vendor = get_user_by('login', 'ikore-kofi');
if (!$vendor || !in_array('wcfm_vendor', (array)$vendor->roles, true)) { fwrite(STDERR, "IKORE_VENDOR_MISSING\n"); exit(4); }
$vid = (int)$vendor->ID;
$root = get_term_by('slug','mentta','product_cat');
if (!$root) { fwrite(STDERR,"MENTTA_ROOT_MISSING\n");exit(4); }
$cat = get_term_by('slug','mentta-cafes','product_cat');
if ($cat && (int)$cat->parent !== (int)$root->term_id) { fwrite(STDERR,"MENTTA_CHILD_WRONG_PARENT\n");exit(4); }
$sources = [
 'villa'=>['sku'=>'46002/V0122','name'=>'Costa Rica Villa Sarchí Miel','country'=>'Costa Rica','gross'=>12.90,'taste'=>'miel, nueces, vainilla y manzana verde'],
 'typica'=>['sku'=>'46002/V0123','name'=>'Costa Rica Typica Mejorado','country'=>'Costa Rica','gross'=>12.90,'taste'=>'caramelo, chocolate negro y naranja'],
 'geisha'=>['sku'=>'46002/V0124','name'=>'Costa Rica Geisha','country'=>'Costa Rica','gross'=>18.00,'taste'=>'jazmín, fruta de hueso y lima'],
 'colombia'=>['sku'=>'46002/V0116','name'=>'Colombia El Indio','country'=>'Colombia','gross'=>18.00,'taste'=>'fruta de la pasión, chocolate y frambuesa'],
 'kenia'=>['sku'=>'46002/V0127','name'=>'Kenia Washed Adumu','country'=>'Kenia','gross'=>18.00,'taste'=>'cereza, frambuesa y chocolate con leche'],
 'panama'=>['sku'=>'46002/V0112','name'=>'Panamá Chiriquí','country'=>'Panamá','gross'=>20.00,'taste'=>'fruta madura, chocolate y especias']
];
$packs = [
 'esencia-costa-rica'=>[
  'sku'=>'IKORE-MENTTA-PACK-COSTA-RICA','slug'=>'pack-cafe-especialidad-esencia-costa-rica-ikore',
  'name'=>'Pack Degustación de Café de Especialidad – Esencia de Costa Rica | Ikore Kofi',
  'headline'=>'ESENCIA DE COSTA RICA','subtitle'=>'Tres perfiles de una misma tierra','price'=>49.80,
  'items'=>['villa','typica','geisha'],
  'intro'=>'Una degustación de tres cafés 100 % arábica de Costa Rica tostados por Ikore Kofi. Explora el carácter dulce del Villa Sarchí Miel, el toque achocolatado y cítrico del Typica Mejorado y los aromas florales del Geisha. Tres variedades, tres matices y una forma de descubrir el café de especialidad.'
 ],
 'ruta-cuatro-origenes'=>[
  'sku'=>'IKORE-MENTTA-PACK-4-ORIGENES','slug'=>'pack-cafe-especialidad-ruta-cuatro-origenes-ikore',
  'name'=>'Pack Degustación de Café de Especialidad – Ruta de los 4 Orígenes | Ikore Kofi',
  'headline'=>'RUTA DE LOS 4 ORÍGENES','subtitle'=>'Costa Rica · Colombia · Kenia · Panamá','price'=>74.90,
  'items'=>['villa','colombia','kenia','panama'],
  'intro'=>'Viaja por cuatro países cafeteros a través de cuatro cafés de especialidad 100 % arábica, tostados por Ikore Kofi. Compara el dulzor de Costa Rica, la fruta tropical de Colombia, los frutos rojos de Kenia y la intensidad afrutada y especiada de Panamá.'
 ],
 'coleccion-completa'=>[
  'sku'=>'IKORE-MENTTA-PACK-6-CAFES','slug'=>'pack-cafe-especialidad-coleccion-completa-ikore',
  'name'=>'Pack Degustación de Café de Especialidad – Colección Completa Ikore | 6 Cafés',
  'headline'=>'COLECCIÓN COMPLETA IKORE','subtitle'=>'Seis cafés · cuatro países','price'=>105.80,
  'items'=>['villa','typica','geisha','colombia','kenia','panama'],
  'intro'=>'Descubre los seis cafés de especialidad Ikore Kofi en una sola selección: tres perfiles de Costa Rica más Colombia El Indio, Kenia Washed Adumu y Panamá Chiriquí. Una experiencia para comparar aromas dulces, florales, afrutados, cítricos y achocolatados.'
 ]
];
$errors = []; $resolved = [];
foreach ($sources as $key=>&$src) {
 $id = (int)wc_get_product_id_by_sku($src['sku']);
 $parent = $id ? wc_get_product($id) : false;
 $variation_id = (int)wc_get_product_id_by_sku($src['sku'].'-250-1');
 $variation = $variation_id ? wc_get_product($variation_id) : false;
 if (!$parent || !$parent->is_type('variable') || $parent->get_status()!=='publish' ||
  (int)get_post_field('post_author',$id)!==$vid || !$variation || !$variation->is_type('variation') ||
  (int)$variation->get_parent_id()!==$id || $variation->get_status()!=='publish') {
  $errors[]='SOURCE_OR_VARIATION_INVALID '.$key; continue;
 }
 if ($variation->get_attribute('peso')!=='250 g' || $variation->get_attribute('molienda')!=='En grano') {
  $errors[]='VARIATION_FORMAT_INVALID '.$key;continue;
 }
 $gross=(float)wc_get_price_including_tax($variation, ['qty'=>1]);
 if (abs($gross-$src['gross'])>.025) { $errors[]='SOURCE_PRICE_CHANGED '.$key.' '.round($gross,2);continue; }
 $photo=(int)$parent->get_image_id();
 if (!$photo || !wp_attachment_is_image($photo) || !is_readable((string)get_attached_file($photo))) {
  $errors[]='ORIGINAL_IMAGE_MISSING '.$key;continue;
 }
 $src['id']=$id;$src['variation_id']=$variation_id;$src['photo']=$photo;
 $src['in_stock']=$parent->is_in_stock() && $variation->is_in_stock();
}
unset($src);
foreach ($packs as $key=>&$pack) {
 $sum=0.0;$lines=[];$available=true;
 foreach ($pack['items'] as $item) {
  if (empty($sources[$item]['photo'])) { $errors[]='SOURCE_UNRESOLVED '.$key.' '.$item;continue; }
  $src=$sources[$item];$sum+=$src['gross'];$available=$available && $src['in_stock'];
  $lines[]=['id'=>$src['variation_id'],'source_product_id'=>$src['id'],'qty'=>1,'image'=>$src['photo'],
   'label'=>$src['name'].', 250 g, en grano','country'=>$src['country']];
 }
 if (abs($pack['price']-$sum-6)>.001 || count($lines)!==count($pack['items'])) {$errors[]='PACK_COMPOSITION_MATH '.$key;continue;}
 $pid=(int)wc_get_product_id_by_sku($pack['sku']);
 if ($pid) {
  $existing=wc_get_product($pid);
  if (!$existing || !$existing->is_type('simple') || (int)get_post_field('post_author',$pid)!==$vid ||
   (string)get_post_meta($pid,'_emdo_ikore_mentta_pack_key',true)!==$key) $errors[]='SKU_COLLISION '.$key;
 } elseif (get_page_by_path($pack['slug'], OBJECT, 'product')) $errors[]='SLUG_COLLISION '.$key;
 $pack['sum']=$sum;$pack['lines']=$lines;$pack['available']=$available;$pack['pid']=$pid;
}
unset($pack);
echo 'IKORE_PACK_PREFLIGHT '.wp_json_encode(['mode'=>$mode,'vendor_id'=>$vid,'source_count'=>count($sources),'errors'=>$errors,
 'packs'=>array_map(static fn($p)=>['id'=>$p['pid']??0,'name'=>$p['name'],'price'=>$p['price'],
  'sum_cafe'=>$p['sum']??null,'sources'=>array_column($p['lines']??[],'id'),
  'available'=>$p['available']??false],$packs)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if ($errors)exit(5);
if ($mode==='preflight')exit(0);
function ikore_pack_original_photo($image_id) {
 $original=(string)get_attached_file($image_id);
 if (is_readable($original)) return $original;
 throw new RuntimeException("Source image unavailable ".$image_id);
}
function ikore_pack_color($im,$rgb) {
 return imagecolorallocate($im,hexdec(substr($rgb,1,2)),hexdec(substr($rgb,3,2)),hexdec(substr($rgb,5,2)));
}
function ikore_pack_font($bold=false) {
 foreach (($bold?['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf','/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf']:
 ['/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf','/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf']) as $p) if(is_readable($p))return $p;
 throw new RuntimeException('FreeType fonts missing');
}
function ikore_pack_center($im,$text,$size,$baseline,$color,$bold=false) {
 $font=ikore_pack_font($bold);$box=imagettfbbox($size,0,$font,$text);
 imagettftext($im,$size,0,(int)((1200-($box[2]-$box[0]))/2),$baseline,$color,$font,$text);
}
function ikore_pack_cover($key,$pack,$pid,$sources) {
 if(!function_exists('imagecreatetruecolor') || !function_exists('imagecreatefromstring') || !function_exists('imagettftext'))throw new RuntimeException('GD/FreeType missing');
 $im=imagecreatetruecolor(1200,1200);
 $bg=ikore_pack_color($im,'#F5F1E9');$forest=ikore_pack_color($im,'#203A31');$bronze=ikore_pack_color($im,'#C3A47B');$white=ikore_pack_color($im,'#FFFFFF');$ink=ikore_pack_color($im,'#26372F');
 imagefilledrectangle($im,0,0,1200,1200,$bg);
 imagefilledrectangle($im,0,0,1200,218,$forest);
 ikore_pack_center($im,'CAFÉ DE ESPECIALIDAD',27,59,$bronze,true);
 ikore_pack_center($im,$pack['headline'],34,118,$white,true);
 ikore_pack_center($im,$pack['subtitle'],21,168,$white,false);
 ikore_pack_center($im,'IKORE KOFI · DEGUSTACIÓN EN GRANO',16,202,$bronze,false);
 $n=count($pack['items']);$cols=$n===3?3:($n===4?2:3);$rows=(int)ceil($n/$cols);
 $tileW=$cols===2?420:345;$tileH=$rows===1?708:398;
 $gapX=$cols===2?22:20;$gapY=18;
 $totalW=$cols*$tileW+($cols-1)*$gapX;
 $firstX=(int)((1200-$totalW)/2);
 $firstY=$rows===1?272:252;
 foreach ($pack['items'] as $i=>$srcKey) {
  $src=$sources[$srcKey];$row=(int)floor($i/$cols);$col=$i%$cols;
  $x=$firstX+$col*($tileW+$gapX);$y=$firstY+$row*($tileH+$gapY);
  imagefilledrectangle($im,$x,$y,$x+$tileW,$y+$tileH,$white);
  imagerectangle($im,$x,$y,$x+$tileW,$y+$tileH,$bronze);
  $padding=15;$usableW=$tileW-$padding*2;$picH=$rows===1?566:292;
  $bytes=file_get_contents(ikore_pack_original_photo($src['photo']));
  $photo=@imagecreatefromstring($bytes);
  if(!$photo)throw new RuntimeException('Decode original bag photo '.$src['photo']);
  $sw=imagesx($photo);$sh=imagesy($photo);
  // Full original photograph without removing the supplier's design or editing label text.
  $factor=min($usableW/$sw,$picH/$sh);
  $dw=(int)round($sw*$factor);$dh=(int)round($sh*$factor);
  imagecopyresampled($im,$photo,$x+$padding+(int)(($usableW-$dw)/2),$y+15+(int)(($picH-$dh)/2),0,0,$dw,$dh,$sw,$sh);
  imagedestroy($photo);
  $country=$src['country'];$short=str_replace(['Costa Rica ','Colombia ','Kenia ','Panamá '],'',$src['name']);
  $labelSize=($cols===3?13:17);
  $font=ikore_pack_font(true);
  imagettftext($im,$labelSize,0,$x+18,$y+$picH+50,$ink,$font,$country);
  imagettftext($im,$labelSize,0,$x+18,$y+$picH+76,$ink,$font,$short);
  imagettftext($im,13,0,$x+18,$y+$picH+101,$ink,ikore_pack_font(false),'250 g · en grano');
 }
 ikore_pack_center($im,$n.' CAFÉS · '.number_format($n*.25,($n===6?1:($n===4?0:2)),',','.').' KG · 100 % ARÁBICA',23,1163,$forest,true);
 $uploads=wp_upload_dir();
 if($uploads['error'])throw new RuntimeException('Uploads directory error');
 $dir=trailingslashit($uploads['basedir']).'mentta-ikore-packs';
 if(!is_dir($dir) && !wp_mkdir_p($dir))throw new RuntimeException('Cannot make upload dir');
 $path=$dir.'/pack-ikore-'.$key.'-originales-v1.jpg';
 if(!imagejpeg($im,$path,91)){imagedestroy($im);throw new RuntimeException('Cannot write jpg');}
 imagedestroy($im);
 $old=(int)get_post_meta($pid,'_emdo_ikore_cover_attachment',true);
 if($old && get_post($old)) { $attachment=$old; update_attached_file($attachment,$path); }
 else {
  $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>$pack['name'].' · composición de fotografías originales','post_status'=>'inherit',
   'post_content'=>'Collage con fotografías reales de las bolsas originales de café Ikore Kofi, sin alteración del texto ni del etiquetado.'],$path,$pid,true);
  if(is_wp_error($attachment))throw new RuntimeException('Attachment error '.$attachment->get_error_message());
 }
 require_once ABSPATH.'wp-admin/includes/image.php';
 wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$path));
 update_post_meta($attachment,'_wp_attachment_image_alt',$pack['name'].' · bolsas originales incluidas');
 update_post_meta($pid,'_emdo_ikore_cover_attachment',$attachment);
 return (int)$attachment;
}
if ($mode==='apply') {
 if (!$cat) {
  $insert=wp_insert_term('Cafés','product_cat',['parent'=>(int)$root->term_id,'slug'=>'mentta-cafes',
   'description'=>'Categoría interna de cafés y packs exclusivos para integración con Mentta.']);
  if(is_wp_error($insert))throw new RuntimeException($insert->get_error_message());
  $cat=get_term((int)$insert['term_id'],'product_cat');
 }
 $created=[];
 try {
  foreach ($packs as $key=>$pack) {
   $pid=$pack['pid'];
   $p=$pid?wc_get_product($pid):new WC_Product_Simple();
   $p->set_status('draft');
   $p->set_name($pack['name']);$p->set_slug($pack['slug']);$p->set_sku($pack['sku']);
   $stored_price=wc_prices_include_tax()?$pack['price']:$pack['price']/1.10;
   $p->set_regular_price(wc_format_decimal($stored_price,6));$p->set_sale_price('');
   $p->set_tax_status('taxable');$p->set_tax_class(wc_get_product($sources[$pack['items'][0]]['id'])->get_tax_class());
   $p->set_catalog_visibility('visible'); // Mentta importer requires true; public MU guards suppress product everywhere.
   $p->set_featured(false);$p->set_virtual(false);$p->set_sold_individually(false);
   $p->set_manage_stock(false);$p->set_stock_status($pack['available']?'instock':'outofstock');
   $p->set_weight((string)(count($pack['items'])*.25));
   $p->set_category_ids([(int)$root->term_id,(int)$cat->term_id]);
   $p->set_short_description('<p>'.esc_html(count($pack['items']).' cafés de especialidad 100 % arábica de 250 g cada uno, en grano. '.$pack['subtitle'].'. Tostados por Ikore Kofi.').' </p>');
   $html='<p>'.esc_html($pack['intro']).'</p><h2>Cafés incluidos en el pack de degustación</h2><ul>';
   foreach($pack['items'] as $item) $html.='<li><strong>'.esc_html($sources[$item]['name']).'</strong>: '.esc_html($sources[$item]['taste']).' (bolsa original de 250 g, en grano).</li>';
   $html.='</ul><h2>Cómo disfrutar tu degustación de café</h2><p>Prepara cada origen por separado, con la misma receta y cantidad de agua, para apreciar las diferencias de aroma, dulzor y acidez. El café llega en sus envases originales y no incluye molinillo, cafetera ni caja de regalo adicional.</p>';
   $html.='<p><strong>Formato:</strong> todas las bolsas son de 250 g y café en grano, para moler al preparar. <strong>Conservación:</strong> guarda los envases bien cerrados en lugar fresco y seco, sin luz directa ni olores intensos. Consulta lote y consumo preferente en cada bolsa. Las notas de cata no son ingredientes añadidos.</p>';
   $p->set_description(wp_kses_post($html));
   $p->set_gallery_image_ids(array_values(array_column($pack['lines'],'image')));
   $p->update_meta_data('_emdo_mentta_only','yes');
   $p->update_meta_data('_emdo_ikore_mentta_pack_key',$key);
   $p->update_meta_data('_emdo_mentta_pack_lines',$pack['lines']);
   $p->update_meta_data('_emdo_ikore_packs_component_gross_pvp',number_format($pack['sum'],2,'.',''));
   $p->update_meta_data('_emdo_ikore_packs_extra_gross_pvp','6.00');
   $p->update_meta_data('_emdo_ikore_packs_vendor_base_gross_estimate',number_format($pack['sum']*.85,2,'.',''));
   $p->update_meta_data('_yoast_wpseo_meta-robots-noindex','1');
   $p->update_meta_data('_yoast_wpseo_title',$pack['name']);
   $p->update_meta_data('_yoast_wpseo_metadesc',mb_substr($pack['intro'],0,155));
   $pid=$p->save();
   if(!$pid)throw new RuntimeException('Product save failed '.$key);
   if(!$pack['pid'])$created[]=$pid;
   wp_update_post(['ID'=>$pid,'post_author'=>$vid]);
   $ids=emdo_mentta_exclusive_ids();if(!in_array($pid,$ids,true)){$ids[]=$pid;update_option('emdo_mentta_exclusive_ids',array_values(array_unique($ids)),false);}
   if(!in_array($pid,emdo_mentta_exclusive_ids(),true))throw new RuntimeException('Mentta public guard save failed '.$pid);
   $cover=ikore_pack_cover($key,$pack,$pid,$sources);
   $product=wc_get_product($pid);$product->set_image_id($cover);$product->set_status('publish');$product->save();
   wc_delete_product_transients($pid);
   echo 'IKORE_PACK_CREATED '.wp_json_encode(['id'=>$pid,'sku'=>$pack['sku'],'slug'=>$pack['slug'],'gross_price'=>$pack['price'],'source_photo_count'=>count($pack['lines']),'cover_id'=>$cover],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
  }
  if(function_exists('wp_cache_flush'))wp_cache_flush();
 } catch(Throwable $e) {
  foreach($created as $id){$p=wc_get_product($id);if($p){$p->set_status('draft');$p->save();}}
  fwrite(STDERR,'IKORE_PACK_APPLY_FAILED '.$e->getMessage()."\n");exit(7);
 }
}
if ($mode==='apply' || $mode==='verify') {
 if(!$cat)$cat=get_term_by('slug','mentta-cafes','product_cat');
 if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
 foreach ($packs as $key=>$pack) {
  $id=(int)wc_get_product_id_by_sku($pack['sku']);
  $p=$id?wc_get_product($id):false;
  if(!$p || $p->get_status()!=='publish' || $p->get_catalog_visibility()!=='visible' ||
   (int)get_post_field('post_author',$id)!==$vid || !in_array($id,emdo_mentta_exclusive_ids(),true) ||
   !$cat || !in_array((int)$cat->term_id,$p->get_category_ids(),true) ||
   abs((float)wc_get_price_including_tax($p)-$pack['price'])>.025 ||
   count($p->get_gallery_image_ids())!==count($pack['items']) ||
   !is_readable((string)get_attached_file((int)$p->get_image_id()))) {
    fwrite(STDERR,'IKORE_PACK_VERIFY_PRODUCT_ERROR '.$key."\n");exit(8);
  }
  $lines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
  if(!is_array($lines)||count($lines)!==count($pack['items']))exit(9);
  $req=new WP_REST_Request('GET','/mentta_marketplace/products');$req->set_param('product_id',$id);
  $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
  $api=WC_Mentta_Plugin::get_products($req);
  if(!($api instanceof WP_REST_Response) || $api->get_status()!==200)exit(10);
  $payload=$api->get_data();$data=is_object($payload)?($payload->data??[]):(is_array($payload)?($payload['data']??[]):[]);
  $entry=is_array($data)?($data[0]??null):null;
  if(!is_array($entry) || (int)($entry['Id']??0)!==$id || ($entry['IsVisible']??null)!==true ||
   !in_array((int)$cat->term_id,array_map('intval',(array)($entry['Categories']??[])),true)) {
   fwrite(STDERR,'IKORE_PACK_MENTTA_API_ERROR '.$key.' '.wp_json_encode($entry)."\n");exit(11);
  }
  echo 'IKORE_PACK_VERIFIED '.wp_json_encode(['id'=>$id,'name'=>$p->get_name(),'pvp_inc_vat'=>round(wc_get_price_including_tax($p),2),
   'mentor_visible'=>$entry['IsVisible'],'public_guard'=>true,'photo_count'=>count($pack['items']),'category_ids'=>$entry['Categories'],
   'source_available'=>$pack['available']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }
 echo 'IKORE_THREE_MENTTA_PACKS_VALIDATED'."\n";
}
