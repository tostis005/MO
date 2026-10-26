<?php
/**
 * Mentta-only Musee Gourmet curated packs (2026-10-26).
 * Run with WP-CLI: MDO_MENTTA_PACK_MODE=inspect|apply|verify wp eval-file SCRIPT.php
 * Composer uses source WooCommerce Media Library photographs, never synthetic meat images.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WC_Product_Simple')) { fwrite(STDERR,"WPCLI_WOO_REQUIRED\n"); exit(2); }
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) { fwrite(STDERR,"WRONG_HOST\n");exit(2); }
$mode=getenv('MDO_MENTTA_PACK_MODE')?:'inspect';
if (!in_array($mode,['inspect','apply','verify'],true)) { fwrite(STDERR,"INVALID_MODE\n");exit(2); }
$manifest=getenv('MDO_MENTTA_PACK_DATA')?:__DIR__.'/mentta-latitudes-packs-20261026.json';
$data=json_decode((string)file_get_contents($manifest),true);
if (!is_array($data) || count($data['packs']??[])!==7 || count($data['sources']??[])<35) {fwrite(STDERR,"INVALID_MANIFEST\n");exit(2);}
foreach(['emdo_mentta_exclusive_ids','emdo_mentta_exclusive_public_request','emdo_mj_is_store_api_request','emdo_mj_exclusive_or_variation'] as $fn) {
 if (!function_exists($fn)) {fwrite(STDERR,"SAFETY_GUARD_MISSING ".$fn."\n");exit(3);}
}
if (!file_exists(WP_CONTENT_DIR.'/mu-plugins/emdo-mentta-exclusive-products.php') ||
    !file_exists(WP_CONTENT_DIR.'/mu-plugins/emdo-mentta-storefront-reinforcement.php')) {
 fwrite(STDERR,"SAFETY_FILES_MISSING\n");exit(3);
}
$owner=get_userdata(4949);
if (!$owner || !in_array('wcfm_vendor',(array)$owner->roles,true)) {fwrite(STDERR,"INVALID_VENDOR_4949\n");exit(3);}
$root=get_term_by('slug','mentta','product_cat');
if (!$root) {fwrite(STDERR,"MENTTA_ROOT_MISSING\n");exit(3);}
$category=get_term_by('slug','mentta-carnes','product_cat');
if ($category && (int)$category->parent!==(int)$root->term_id) {fwrite(STDERR,"MENTTA_CHILD_WRONG_PARENT\n");exit(3);}
function mdo_mp_norm($s) {
 $s=strtolower(remove_accents(html_entity_decode((string)$s,ENT_QUOTES|ENT_HTML5,'UTF-8')));
 return preg_replace('/[^a-z0-9]+/','',$s);
}
$ids=get_posts(['post_type'=>'product','post_status'=>['publish'],'author'=>4949,
 'fields'=>'ids','posts_per_page'=>-1,'suppress_filters'=>true]);
$catalog=[];
foreach ($ids as $id) {
 $p=wc_get_product($id);
 if ($p && $p->is_type('simple')) $catalog[(int)$id]=['p'=>$p,'norm'=>mdo_mp_norm($p->get_name())];
}
$sources=[];$errors=[];
foreach($data['sources'] as $key=>$src) {
 $needle=mdo_mp_norm($src[0]);$matches=[];
 foreach($catalog as $id=>$entry) {if (str_starts_with($entry['norm'],$needle)) $matches[]=$id;}
 if(count($matches)!==1) { $hints=[]; $tokens=explode(" ",strtolower(remove_accents($src[0]))); $first=(stripos($key,"ostrich")!==false?"avestruz":(stripos($key,"wagyu")!==false||stripos($key,"aussie")!==false?"wagyu":"")); foreach($catalog as $candidateId=>$entry) { if($first && str_contains($entry["norm"],$first)) $hints[]=$candidateId.":".$entry["p"]->get_name(); } $errors[]="SOURCE_MATCH ".$key." count=".count($matches)." candidates=".implode(" | ",array_slice($hints,0,35));continue;}
 $id=$matches[0];$p=$catalog[$id]['p'];
 $image=(int)$p->get_image_id();
 if(!$image) { $gallery=$p->get_gallery_image_ids();$image=(int)($gallery[0]??0); }
 if(!$image || !wp_attachment_is_image($image)) {$errors[]="PHOTO_MISSING ".$key." #".$id;continue;}
 $in_stock=$p->is_in_stock() && (!$p->managing_stock() || $p->get_stock_quantity()===null || (int)$p->get_stock_quantity()>0);
 $sources[$key]=['id'=>$id,'image'=>$image,'p'=>$p,'qty_stock'=>$p->get_stock_quantity(),
   'available'=>$in_stock,'weight'=>(float)$src[1], 'cost'=>(float)$src[2],'name'=>$p->get_name()];
}
$resolved=[];$allIds=[];
foreach($data['packs'] as $pack) {
 $key=$pack['key'];$w=0.0;$cost=0.0;$lines=[];$gallery=[];$availability=true;
 foreach($pack['items'] as $entry) {
  [$srcKey,$qty]=$entry;
  if(!isset($sources[$srcKey]) || (int)$qty<1) { $errors[]="PACK_SOURCE_MISSING ".$key." ".$srcKey; continue; }
  $s=$sources[$srcKey];$w+=$s['weight']*(int)$qty;$cost+=$s['cost']*(int)$qty;
  $available=$s['available'] && (!$s['p']->managing_stock() || $s['qty_stock']===null || (int)$s['qty_stock']>=(int)$qty);
  $availability=$availability && $available;
  $lines[]=['key'=>$srcKey,'id'=>$s['id'],'qty'=>(int)$qty,'label'=>$data['sources'][$srcKey][3],
     'image'=>$s['image'],'available'=>$available,'photo_source_id'=>$s['image']];
  $gallery[]=$s['image'];
 }
 if(count($lines)!==count($pack['items'])){$errors[]="LINES_INCOMPLETE ".$key;continue;}
 $pvp=(float)$pack['price'];
 $profit=$pvp/1.10-$pvp*.15-18-$cost;
 if($profit/$pvp<.10) {$errors[]="MARGIN_BELOW_10_PERCENT ".$key;continue;}
 if(abs($w-(float)$pack['validation']['weight_max_kg'])>.005 ||
    abs($cost-(float)$pack['validation']['estimated_cost_ex_vat'])>.015) {$errors[]="MANIFEST_MATH_MISMATCH ".$key;continue;}
 $sku='MUSEE-MENTTA-PACK-'.strtoupper(str_replace('-','_',$key));
 $existing=(int)wc_get_product_id_by_sku($sku);
 if($existing) {
  $old=wc_get_product($existing);
  if(!$old || !$old->is_type('simple') || (string)get_post_meta($existing,'_emdo_mentta_pack_key',true)!==$key ||
    (int)get_post_field('post_author',$existing)!==4949) { $errors[]="SKU_COLLISION ".$sku;continue; }
 } else {
  $slug='pack-musee-mentta-'.$key;
  $post=get_page_by_path($slug,OBJECT,'product');
  if($post){$errors[]="SLUG_COLLISION ".$slug;continue;}
 }
 $resolved[]=['pack'=>$pack,'lines'=>$lines,'sku'=>$sku,'id'=>$existing,'weight'=>$w,'cost'=>$cost,
   'profit'=>$profit,'in_stock'=>$availability];
 if($existing)$allIds[]=$existing;
}
$report=['mode'=>$mode,'resolved_sources'=>count($sources),'requested_sources'=>count($data['sources']),
 'resolved_packs'=>count($resolved),'errors'=>$errors,'packs'=>array_map(static function($r){
  return ['key'=>$r['pack']['key'],'id'=>$r['id'],'pvp'=>$r['pack']['price'],
    'weight_max_kg'=>$r['weight'],'estimated_profit'=>$r['profit'],'available'=>$r['in_stock'],
    'component_ids'=>array_map(static fn($l)=>[$l['id'],$l['qty'],$l['image'],$l['available']],$r['lines'])];
 },$resolved)];
echo 'MUSEE_MENTTA_PREFLIGHT '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($errors || count($resolved)!==7 || count($sources)!==count($data['sources'])) exit(4);
if($mode==='inspect') {echo "PREFLIGHT_OK\n";exit(0);}
if(!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {fwrite(STDERR,"GD_AND_FREETYPE_REQUIRED\n");exit(5);}
function mdo_mp_font($bold=false) {
 $paths=$bold?[
 '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
 '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
 '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf'
 ]:[
 '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
 '/usr/share/fonts/dejavu/DejaVuSans.ttf',
 '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf'];
 foreach($paths as $file)if(is_readable($file))return $file;
 throw new RuntimeException("Missing TrueType system fonts");
}
function mdo_mp_col($im,$hex) {
 $hex=ltrim($hex,'#');return imagecolorallocate($im,hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2)));
}
function mdo_mp_text($im,$str,$font,$size,$y,$color,$center=600) {
 $b=imagettfbbox($size,0,$font,$str);$width=$b[2]-$b[0];
 imagettftext($im,$size,0,(int)($center-$width/2),$y,$color,$font);
}
function mdo_mp_wrap($str,$font,$size,$maxWidth,$maxLines=2) {
 $words=preg_split('/\s+/u',(string)$str);$lines=[];$cur='';
 foreach($words as $word) {
  $candidate=trim($cur.' '.$word);$b=imagettfbbox($size,0,$font,$candidate);
  if(($b[2]-$b[0])>$maxWidth && $cur!==''){$lines[]=$cur;$cur=$word;}
  else $cur=$candidate;
 }
 if($cur!=='')$lines[]=$cur;
 if(count($lines)>$maxLines) throw new RuntimeException('Photo tile label too long: '.$str);
 return $lines;
}
function mdo_mp_photo_path($imageId) {
 $path=(string)get_attached_file($imageId);
 $meta=wp_get_attachment_metadata($imageId);
 $uploads=wp_upload_dir();
 if(is_array($meta) && !empty($meta['sizes']['medium_large']['file'])) {
  $candidate=trailingslashit(dirname($path)).$meta['sizes']['medium_large']['file'];
  if(is_readable($candidate))return $candidate;
 }
 if(is_array($meta) && !empty($meta['sizes']['medium']['file'])) {
  $candidate=trailingslashit(dirname($path)).$meta['sizes']['medium']['file'];
  if(is_readable($candidate))return $candidate;
 }
 return is_readable($path)?$path:'';
}
function mdo_mp_crop_photo($out,$id,$left,$top,$width,$height) {
 $path=mdo_mp_photo_path($id);
 if(!$path)throw new RuntimeException("Original product photo inaccessible: attachment ".$id);
 $bytes=@file_get_contents($path);
 $src=$bytes?@imagecreatefromstring($bytes):false;
 if(!$src)throw new RuntimeException("Cannot decode product photo ".$id);
 $iw=imagesx($src);$ih=imagesy($src);
 if($iw<80 || $ih<80){imagedestroy($src);throw new RuntimeException("Low-resolution source photo ".$id);}
 // Fit full product photograph inside the tile, no synthetic replacement or misleading crop.
 $scale=min($width/$iw,$height/$ih);
 $dw=max(1,(int)round($iw*$scale));$dh=max(1,(int)round($ih*$scale));
 imagecopyresampled($out,$src,$left+(int)(($width-$dw)/2),$top+(int)(($height-$dh)/2),0,0,$dw,$dh,$iw,$ih);
 imagedestroy($src);
}
function mdo_mp_poster($pack,$lines,$pid) {
 $n=count($lines);$columns=4;$rows=(int)ceil($n/$columns);$width=1200;
 $height=330+$rows*211+30;
 $im=imagecreatetruecolor($width,$height);
 $cream=mdo_mp_col($im,'#FCF8F0');$burg=mdo_mp_col($im,'#2B211D');
 $gold=mdo_mp_col($im,'#B18B51');$white=mdo_mp_col($im,'#FFFFFF');
 $tile=mdo_mp_col($im,'#F2EEE7');$ink=mdo_mp_col($im,'#241E1B');
 imagefilledrectangle($im,0,0,$width,$height,$cream);
 imagefilledrectangle($im,0,0,$width,252,$burg);
 imagefilledrectangle($im,0,252,$width,257,$gold);
 // The header is designed graphically; every meat photograph in the product tiles is a real source media attachment.
 $font=mdo_mp_font(true);$normal=mdo_mp_font(false);
 $title=(string)$pack['name'];$parts=explode(' – ',$title,2);
 $headline=$parts[0];$tagline=$parts[1]??'';
 $titleLines=mdo_mp_wrap($headline,$font,35,1110,3);
 $top=count($titleLines)===3?55:75;
 foreach($titleLines as $i=>$line)mdo_mp_text($im,$line,$font,35,$top+52*$i,$white);
 mdo_mp_text($im,$tagline,$font,31,$top+52*count($titleLines)+14,$gold);
 mdo_mp_text($im,$pack['theme'],$normal,18,239,$gold);
 mdo_mp_text($im,'Incluye:',$font,27,306,$ink);
 imageline($im,45,302,445,302,$gold);imageline($im,755,302,1155,302,$gold);
 foreach($lines as $i=>$line) {
  $col=$i%4;$row=(int)floor($i/4);$x=22+$col*298;$y=322+$row*211;
  imagefilledrectangle($im,$x,$y,$x+282,$y+202,$tile);
  imagefilledrectangle($im,$x+10,$y+10,$x+272,$y+148,$white);
  mdo_mp_crop_photo($im,$line['image'],$x+15,$y+14,252,128);
  $label=$line['label'];
  if($line['qty']>1) {
   if($line['key']==='ostrich_triangles'&&$line['qty']===2)$label='4 triángulos avestruz (2 packs de 2)';
   else $label=$line['qty'].' × '.$label;
  }
  // Product formats and quantities are in the HTML description; short labels avoid illegible packaging text.
  $chunks=mdo_mp_wrap($label,$normal,13,260,3);
  foreach($chunks as $j=>$chunk)mdo_mp_text($im,$chunk,$normal,13,$y+163+$j*16,$ink,$x+141);
 }
 $uploads=wp_upload_dir();
 if(!empty($uploads['error']))throw new RuntimeException("Upload folder error");
 $dir=trailingslashit($uploads['basedir']).'mentta-musee-packs';
 if(!is_dir($dir)&&!wp_mkdir_p($dir))throw new RuntimeException('Cannot create pack media dir');
 $filename='mentta-musee-'.$pack['key'].'-fotos-reales-v1.jpg';
 $path=$dir.'/'.$filename;
 if(!imagejpeg($im,$path,88)){imagedestroy($im);throw new RuntimeException("Cannot write collage");}
 imagedestroy($im);
 $existing=get_post_meta($pid,'_emdo_mentta_poster_attachment_id',true);
 if($existing && get_post((int)$existing)) {
  $attachment=(int)$existing;
  update_attached_file($attachment,$path);
 } else {
  $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>$pack['name'].' · fotografías originales','post_status'=>'inherit',
    'post_content'=>'Composición de fotografías de los productos originales del productor, sin imágenes de carne generadas por IA.'],
    $path,$pid,true);
  if(is_wp_error($attachment))throw new RuntimeException('Media attachment create failed: '.$attachment->get_error_message());
  require_once ABSPATH.'wp-admin/includes/image.php';
  wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$path));
 }
 update_post_meta($pid,'_emdo_mentta_poster_attachment_id',(int)$attachment);
 update_post_meta($attachment,'_wp_attachment_image_alt',$pack['name'].' · productos originales incluidos');
 return (int)$attachment;
}
if($mode==='verify') {
 foreach($resolved as $r) {
  $id=$r['id'];$p=$id?wc_get_product($id):false;
  $gallery=$p?$p->get_gallery_image_ids():[];
  $wanted=array_values(array_unique(array_column($r['lines'],'image')));
  if(!$p || $p->get_status()!=='publish' || $p->get_catalog_visibility()!=='visible' ||
     !in_array($id,emdo_mentta_exclusive_ids(),true) || abs((float)$p->get_regular_price('edit')-(float)$r['pack']['price'])>.001 ||
     abs((float)$p->get_weight('edit')-$r['weight'])>.001 ||
     count($gallery)!==count($wanted) || count(array_diff($wanted,$gallery))!==0 ||
     !$p->get_image_id() || !get_attached_file($p->get_image_id()) ||
     (string)get_post_meta($id,'_emdo_mentta_pack_key',true)!==$r['pack']['key']) {
   fwrite(STDERR,'VERIFY_FAILED '.$r['pack']['key']."\n");exit(6);
  }
  $lines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
  if(!is_array($lines) || count($lines)!==count($r['lines']))exit(7);
  foreach($lines as $i=>$l)if((int)$l['id']!==$r['lines'][$i]['id'] || (int)$l['qty']!==$r['lines'][$i]['qty'] ||
    (int)$l['image']!==$r['lines'][$i]['image']) {fwrite(STDERR,'COMPONENT_IMAGE_COUNT_FAIL '.$id."\n");exit(8);}
  $cats=array_map('intval',$p->get_category_ids());
  $child=get_term_by('slug','mentta-carnes','product_cat');
  if(!$child || !in_array((int)$root->term_id,$cats,true) || !in_array((int)$child->term_id,$cats,true))exit(9);
  echo 'VERIFIED_PACK '.wp_json_encode(['id'=>$id,'sku'=>$r['sku'],'pvp'=>$p->get_price('edit'),'photo_tiles'=>count($lines),
    'actual_gallery_photos'=>count($gallery),'max_weight'=>$p->get_weight(),'estimated_profit'=>$r['profit']])."\n";
 }
 echo "ALL_SEVEN_PACKS_VERIFIED\n";exit(0);
}
if(!$category) {
 $term=wp_insert_term('Carnes','product_cat',['parent'=>(int)$root->term_id,'slug'=>'mentta-carnes',
   'description'=>'Categoría interna para carne y packs exclusivos de Mentta. No visible en el escaparate.']);
 if(is_wp_error($term))throw new RuntimeException('Cannot create Mentta carnes category: '.$term->get_error_message());
 $category=get_term((int)$term['term_id'],'product_cat');
}
$photoIds=[];$created=[];
try {
 foreach($resolved as $r) {
  $pack=$r['pack'];
  $pid=$r['id'];$fresh=$pid?wc_get_product($pid):new WC_Product_Simple();
  $fresh->set_status('draft'); // Never publish before exclusion guard list is updated.
  $fresh->set_name($pack['name']);
  $fresh->set_slug('pack-musee-mentta-'.$pack['key']);
  $fresh->set_sku($r['sku']);
  $fresh->set_regular_price((string)$pack['price']);
  $fresh->set_sale_price('');
  $fresh->set_weight((string)$r['weight']);
  $fresh->set_featured(false);
  $fresh->set_catalog_visibility('visible'); // For Mentta REST; public exclusion is enforced by MU guards.
  $fresh->set_virtual(false);
  $fresh->set_sold_individually(false);
  $fresh->set_manage_stock(false);
  $fresh->set_stock_status($r['in_stock']?'instock':'outofstock');
  $fresh->set_category_ids([(int)$root->term_id,(int)$category->term_id]);
  $short='Pack '.$pack['key'].' · '.count($r['lines']).' referencias · hasta '.str_replace('.',',',(string)$r['weight']).' kg. '.$pack['tag'].'.';
  $fresh->set_short_description('<p>'.esc_html($short).'</p>');
  $html='<p>'.esc_html($pack['description']).'</p><h2>¿Qué incluye este pack?</h2><ul>';
  foreach($r['lines'] as $line)$html.='<li>'.esc_html(($line['qty']>1?$line['qty'].' × ':'').$line['label']).'</li>';
  $html.='</ul><p><strong>Conservación:</strong> seguir siempre las indicaciones del etiquetado original. '
       .'Las piezas pueden entregarse congeladas y envasadas al vacío. Los productos Burger Meat de 500 g se entregan para moldear; '
       .'las referencias de 2 × 150 g se presentan como dos hamburguesas, salvo que el fabricante indique otra cosa. '
       .'Los pesos de los cortes son aproximados y los surtidos no se sustituyen sin validar composición y disponibilidad.</p>';
  $fresh->set_description(wp_kses_post($html));
  $fresh->update_meta_data('_emdo_mentta_only','yes');
  $fresh->update_meta_data('_emdo_mentta_pack_key',$pack['key']);
  $fresh->update_meta_data('_emdo_mentta_pack_lines',$r['lines']);
  $fresh->update_meta_data('_emdo_mentta_pack_profit_estimate',(string)round($r['profit'],2));
  $fresh->update_meta_data('_yoast_wpseo_meta-robots-noindex','1');
  $fresh->update_meta_data('_yoast_wpseo_title',$pack['name'].' | Musée Gourmet');
  $fresh->update_meta_data('_yoast_wpseo_metadesc',mb_substr($pack['tag'].'. '.$pack['description'],0,155));
  $fresh->set_gallery_image_ids(array_values(array_unique(array_column($r['lines'],'image'))));
  $pid=$fresh->save();
  if(!$pid)throw new RuntimeException("Save draft failed: ".$pack['key']);
  if(!$r['id'])$created[]=$pid;
  wp_update_post(['ID'=>$pid,'post_author'=>4949]);
  $sourceProducer=wp_get_object_terms($r['lines'][0]['id'],'pa_productor',['fields'=>'ids']);
  if(!is_wp_error($sourceProducer)&&$sourceProducer)wp_set_object_terms($pid,array_map('intval',$sourceProducer),'pa_productor',false);
  $hidden=emdo_mentta_exclusive_ids();
  if(!in_array($pid,$hidden,true)){$hidden[]=$pid;update_option('emdo_mentta_exclusive_ids',array_values(array_unique($hidden)),false);}
  $image=mdo_mp_poster($pack,$r['lines'],$pid);
  $product=wc_get_product($pid);
  $product->set_image_id($image);
  $product->set_status('publish');
  $product->save();
  if(!in_array($pid,emdo_mentta_exclusive_ids(),true))throw new RuntimeException('Cannot prove public privacy exclusion');
  wc_delete_product_transients($pid);
  $photoIds[]=['id'=>$pid,'poster_id'=>$image,'poster_path'=>get_attached_file($image),
     'original_photo_ids'=>array_values(array_unique(array_column($r['lines'],'image')))];
 }
 if(function_exists('wp_cache_flush'))wp_cache_flush();
 echo 'SEVEN_MUSEE_MENTTA_PRODUCTS_CREATED '.wp_json_encode($photoIds,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch(Throwable $e) {
 // Fail closed: newly created product records return to draft if any pack/photo fails.
 foreach($created as $id){$p=wc_get_product($id);if($p){$p->set_status('draft');$p->save();}}
 fwrite(STDERR,'MUSEE_MENTTA_INSTALL_ABORT '.$e->getMessage()."\n");
 exit(21);
}
