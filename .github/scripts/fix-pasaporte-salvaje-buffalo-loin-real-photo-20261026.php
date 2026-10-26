<?php
/**
 * Generate pack posters by COMPOSITING ONLY original vendor catalog photographs.
 * Photos: WooCommerce attachment media bytes (no diffusion / AI / retouch).
 * Important: all products remain DRAFT and Mentta is held by emdo-musee-mentta-review-hold.
 *
 * WP-CLI: MDO_MUSEE_REVISED_APPLY=1 wp eval-file THIS_FILE
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product')){fwrite(STDERR,"WORDPRESS_WOO_REQUIRED\n");exit(2);}
if(!function_exists('emdo_musee_review_ids')||!get_option('emdo_musee_mentta_review_hold')){fwrite(STDERR,"MENTTA_HOLD_NOT_ACTIVE\n");exit(3);}
function mdo_rnorm($s){return preg_replace('/[^a-z0-9]+/','',strtolower(remove_accents(html_entity_decode((string)$s,ENT_QUOTES|ENT_HTML5,'UTF-8'))));}
$sourceIds=get_posts(['post_type'=>'product','post_status'=>'publish','author'=>4949,'fields'=>'ids','posts_per_page'=>-1,'suppress_filters'=>true]);
$byName=[];
foreach($sourceIds as $pid){$product=wc_get_product($pid);if($product && $product->is_type('simple'))$byName[(int)$pid]=$product;}
$names=[
 'zebra_filet'=>'filete de 1ª cebra (ración de 200',
 'zebra_loin'=>'lomo de cebra (ración de 200',
 'zebra_burgers'=>'pack burger meat cebra 2×150',
 'ostrich_filet'=>'filete 1ª extra avestruz ración',
 'ostrich_tender'=>'solomillo de avestruz extra (ración de 200',
 'ostrich_burgers'=>'pack burger meat avestruz 2',
 'croc_tender'=>'solomillo de cocodrilo (ración de 200',
 'croc_burgers'=>'pack burger meat cocodrilo 2×150',
 'japan_wagyu'=>'medallones wagyu japonés a5 (150',
 'black_angus'=>'entrecot de black angus (ración de 200',
 'bison_filet'=>'filete de bisonte (200 gr',
 'deer_loin'=>'lomo de venado (ración de 200',
 'kangaroo_200'=>'lomo de canguro (ración de 200',
 'buffalo_tender'=>'solomillo de búfalo (ración de 200',
 'elk_loin'=>'lomo de alce (ración de 200',
 'boar_loin'=>'lomo de jabalí (ración de 200',
 'elk_cadera'=>'filete 1ª de alce cadera / babilla',
 'boar_tender'=>'solomillo de jabalí (ración de 200',
 'croc_filet'=>'solomillo de cocodrilo (ración de 200',
 'buffalo_loin'=>'lomo de búfalo (ración de 200
];
$source=[];$errors=[];
foreach($names as $k=>$needle){
 $matched=[];
 foreach($byName as $id=>$p)if(str_starts_with(mdo_rnorm($p->get_name()),mdo_rnorm($needle)))$matched[]=$id;
 if(count($matched)!==1){$errors[$k]='source-count-'.count($matched);continue;}
 $p=$byName[$matched[0]];
 $photo=(int)$p->get_image_id();
 if(!$photo || !is_readable((string)get_attached_file($photo))) {$errors[$k]='source-photo-missing-'.$p->get_id();continue;}
 $source[$k]=['id'=>$p->get_id(),'photo'=>$photo,'label'=>$p->get_name(),'file'=>get_attached_file($photo),'product'=>$p];
}
$manifest=[
 'safari-africano'=>[
   'id'=>21818,'name'=>'Pack de Cebra, Avestruz y Cocodrilo – Safari Africano',
   'subtitle'=>'Safari Africano','headline'=>'Pack de Cebra, Avestruz y Cocodrilo',
   'theme'=>'SABANA AFRICANA', 'price'=>154.99,'weight'=>1.90,
   'teaser'=>'Degustación de cebra, avestruz y cocodrilo en ocho formatos originales de 200 g y hamburguesas gourmet de 2 × 150 g.',
   'intro'=>'Un auténtico recorrido por tres carnes exóticas: cebra, avestruz y cocodrilo. Ocho referencias distintas, con filetes, lomos, solomillos y hamburguesas, para preparar a la plancha o parrilla. El nombre Safari Africano es temático y no garantiza que todas las piezas tengan el mismo país de origen.',
   'contents'=>[
    ['zebra_filet',1,'Filete de cebra — 200 g'],['zebra_loin',1,'Lomo de cebra — 200 g'],
    ['zebra_burgers',1,'2 hamburguesas de cebra — 150 g cada una'],
    ['ostrich_filet',1,'Filete extra de avestruz — 200 g'],
    ['ostrich_tender',1,'Solomillo de avestruz — 200 g'],
    ['ostrich_burgers',1,'2 hamburguesas de avestruz — 150 g cada una'],
    ['croc_tender',1,'Solomillo de cocodrilo — 200 g'],
    ['croc_burgers',1,'2 hamburguesas de cocodrilo — 150 g cada una']
   ]
 ],
 'vuelta-al-mundo'=>[
   'id'=>21820,'name'=>'Pack Degustación de 10 Carnes del Mundo – La Vuelta al Mundo',
   'subtitle'=>'La Vuelta al Mundo','headline'=>'Pack Degustación de 10 Carnes del Mundo',
   'theme'=>'DIEZ ESPECIES · DIEZ SABORES', 'price'=>158.99,'weight'=>1.95,
   'teaser'=>'Diez carnes del mundo en raciones seleccionadas: avestruz, wagyu japonés, Black Angus, cebra, bisonte, venado, canguro, búfalo, alce y jabalí.',
   'intro'=>'Un itinerario gastronómico para descubrir diez especies y cortes distintos. Incluye medallones japoneses wagyu A5, entrecot Black Angus, carnes de caza y especialidades exóticas. Ideal para una cata comparativa a la parrilla. Los países de origen exactos se confirman en el etiquetado original de cada pieza.',
   'contents'=>[
    ['ostrich_filet',1,'Filete extra de avestruz — 200 g'],
    ['japan_wagyu',1,'Medallones wagyu japonés A5 — 150 g'],
    ['black_angus',1,'Entrecot Black Angus — 200 g'],
    ['zebra_filet',1,'Filete de cebra — 200 g'],
    ['bison_filet',1,'Filete de bisonte — 200 g'],
    ['deer_loin',1,'Lomo de venado — 200 g'],
    ['kangaroo_200',1,'Lomo de canguro — 200 g'],
    ['buffalo_tender',1,'Solomillo de búfalo — 200 g'],
    ['elk_loin',1,'Lomo de alce — 200 g'],
    ['boar_loin',1,'Lomo de jabalí — 200 g']
   ]
 ],
 'pasaporte-salvaje'=>[
   'id'=>21814,'name'=>'Pack Degustación de Carnes Exóticas – Pasaporte Salvaje',
   'subtitle'=>'Pasaporte Salvaje','headline'=>'Pack Degustación de Carnes Exóticas',
   'theme'=>'NUEVE CORTES DEL MUNDO','price'=>151.99,'weight'=>1.80,
   'teaser'=>'Nueve cortes de carnes exóticas, incluyendo cebra, bisonte, alce, jabalí, venado, avestruz, canguro, cocodrilo y búfalo.',
   'intro'=>'Una ruta por nueve especies y cortes seleccionados para cocinar a la plancha o parrilla. Combina carnes de caza con especialidades exóticas para una degustación variada. Las piezas son raciones individuales y requieren transporte refrigerado.',
   'contents'=>[
    ['zebra_filet',1,'Filete de cebra — 200 g'],
    ['bison_filet',1,'Filete de bisonte — 200 g'],
    ['elk_cadera',1,'Cadera/babilla de alce — 200 g'],
    ['boar_tender',1,'Solomillo de jabalí — 200 g'],
    ['deer_loin',1,'Lomo de venado — 200 g'],
    ['ostrich_filet',1,'Filete de avestruz — 200 g'],
    ['kangaroo_200',1,'Lomo de canguro — 200 g'],
    ['croc_filet',1,'Solomillo de cocodrilo — 200 g'],
    ['buffalo_loin',1,'Lomo de búfalo — 200 g']
   ]
 ]
 ];
// This one-off fix MUST update only Pasaporte Salvaje, never the other six paused packs.
$manifest=array_intersect_key($manifest,['pasaporte-salvaje'=>true]);
function mdo_drawphoto($im,$path,$x,$y,$w,$h){
 $bytes=@file_get_contents($path);$src=$bytes?@imagecreatefromstring($bytes):false;
 if(!$src)throw new RuntimeException('Original photo cannot be decoded');
 $iw=imagesx($src);$ih=imagesy($src);
 if($iw<75||$ih<75)throw new RuntimeException('Source photo too small');
 // Minimal non-generative retouch: remove only 4% of the original photo's bottom edge
 // where supplier footer URLs may be printed. All remaining pixels are actual catalog photography.
 $ih=max(75,(int)floor($ih*0.96));
 $z=min($w/$iw,$h/$ih);$dw=(int)round($iw*$z);$dh=(int)round($ih*$z);
 imagecopyresampled($im,$src,$x+(int)(($w-$dw)/2),$y+(int)(($h-$dh)/2),0,0,$dw,$dh,$iw,$ih);
 imagedestroy($src);
}
function mdo_fcolor($im,$rgb){return imagecolorallocate($im,hexdec(substr($rgb,1,2)),hexdec(substr($rgb,3,2)),hexdec(substr($rgb,5,2)));}
function mdo_font($bold=true){
 $files=$bold?['/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf','/usr/share/fonts/dejavu/DejaVuSerif-Bold.ttf','/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']:
 ['/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf','/usr/share/fonts/dejavu/DejaVuSerif.ttf','/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];
 foreach($files as $f)if(file_exists($f))return $f;
 throw new RuntimeException('TTF font missing');
}
function mdo_fittext($im,$str,$font,$maxSize,$limit,$y,$color,$cx=700){
 for($s=$maxSize;$s>=14;$s--){
 $bbox=imagettfbbox($s,0,$font,$str);
 if($bbox[2]-$bbox[0]<=$limit)break;
 }
 $bbox=imagettfbbox($s,0,$font,$str);
 imagettftext($im,$s,0,(int)($cx-($bbox[2]-$bbox[0])/2),$y,$color,$font,$str);
}
function mdo_paint($pack,$lines,$pid) {
 // Only actual original media-library photos are painted in the tiles.
 $n=count($lines);$cols=$n===10?5:($n===9?3:4);$rows=(int)ceil($n/$cols);
 $width=1400;$header=478;$rowH=$cols===5?315:($cols===3?325:332);
 $total=$header+85+$rows*$rowH+40;
 $im=imagecreatetruecolor($width,$total);
 $cream=mdo_fcolor($im,'#FCF9F1');$brown=mdo_fcolor($im,'#332920');
 $gold=mdo_fcolor($im,'#B38C4F');$ivory=mdo_fcolor($im,'#F6EFE4');
 $muted=mdo_fcolor($im,'#E9DFCD');$black=mdo_fcolor($im,'#130F0B');
 imagefilledrectangle($im,0,0,$width,$total,$cream);
 // Painterly landscape header uses geometric original drawing, not fake meat photography.
 for($y=0;$y<$header;$y++){
  $t=$y/$header;
  $r=(int)(245-(55*$t));$g=(int)(237-(73*$t));$b=(int)(224-(95*$t));
  imageline($im,0,$y,$width,$y,imagecolorallocate($im,max(0,$r),max(0,$g),max(0,$b)));
 }
 $mount=mdo_fcolor($im,'#BEAF92');$hill=mdo_fcolor($im,'#A08B6A');
 imagefilledpolygon($im,[0,315,100,280,214,328,365,254,491,335,614,289,758,341,881,267,1056,310,1196,253,1400,310,1400,478,0,478],13,$mount);
 imagefilledpolygon($im,[0,390,170,338,322,399,500,337,610,386,772,333,984,377,1170,350,1400,406,1400,478,0,478],11,$hill);
 // Minimal botanical silhouettes, part of header decoration only.
 $tree=mdo_fcolor($im,'#5D5141');
 foreach([165,1160] as $x){
  imageline($im,$x,400,$x+7,350,$tree);imagesetthickness($im,8);
  imageline($im,$x+5,365,$x-45,346,$tree);
  imageline($im,$x+5,365,$x+51,343,$tree);
  imagesetthickness($im,1);
  imagefilledellipse($im,$x,340,190,39,$tree);
 }
 // Cream header title area improves readability.
 imagefilledrectangle($im,90,18,1310,177,$ivory);
 $bold=mdo_font();$regular=mdo_font(false);
 $words=explode(' ',$pack['headline']);$line1='';$line2='';
 foreach($words as $word) {
  $try=trim($line1.' '.$word);$bb=imagettfbbox(56,0,$bold,$try);
  if(($bb[2]-$bb[0])>1160 && $line1!==''){$line2=trim($line2.' '.$word);}else $line1=$try;
 }
 if($line2==='')mdo_fittext($im,$line1,$bold,61,1160,118,$black);
 else{mdo_fittext($im,$line1,$bold,51,1160,86,$black);mdo_fittext($im,$line2,$bold,51,1160,151,$black);}
 imagefilledrectangle($im,345,185,1055,255,$brown);
 mdo_fittext($im,$pack['subtitle'],$bold,44,665,236,$gold);
 mdo_fittext($im,$pack['theme'],$regular,20,1000,449,$cream);
 imagefilledrectangle($im,0,$header-4,$width,$header,$gold);
 imageline($im,44,$header+52,535,$header+52,$gold);
 imageline($im,865,$header+52,1356,$header+52,$gold);
 mdo_fittext($im,'Incluye:',$bold,49,260,$header+68,$brown);
 $left=18;$gap=13;$usable=$width-2*$left;
 $tileW=(int)(($usable-$gap*($cols-1))/$cols);
 foreach($lines as $i=>$line){
  $col=$i%$cols;$row=intdiv($i,$cols);
  $x=$left+$col*($tileW+$gap);$y=$header+88+$row*$rowH;
  imagefilledrectangle($im,$x,$y,$x+$tileW,$y+$rowH-12,$muted);
  imagefilledrectangle($im,$x+6,$y+7,$x+$tileW-6,$y+$rowH-88,$cream);
  if($line['file']) mdo_drawphoto($im,$line['file'],$x+12,$y+12,$tileW-24,$rowH-115);
  else { mdo_fittext($im,'FOTO ORIGINAL',$bold,22,$tileW-24,$y+125,$brown,$x+$tileW/2);
         mdo_fittext($im,'PENDIENTE',$bold,22,$tileW-24,$y+163,$brown,$x+$tileW/2); }
  $short=preg_replace('/\s+[—–]\s*.*$/u','',$line['label']);
  mdo_fittext($im,$short,$bold,$cols===5?19:25,$tileW-22,$y+$rowH-38,$black,$x+$tileW/2);
 }
 $base=wp_upload_dir();if(!empty($base['error']))throw new RuntimeException('Upload dir fails');
 $dir=trailingslashit($base['basedir']).'mentta-musee-packs';
 if(!is_dir($dir)&&!wp_mkdir_p($dir))throw new RuntimeException('Upload folder creation failed');
 $file=$dir.'/mentta-musee-'.$pack['key'].'-fotos-ORIGINALES-20261026.jpg';
 if(!imagejpeg($im,$file,88))throw new RuntimeException('Poster jpeg save fails');
 imagedestroy($im);
 require_once ABSPATH.'wp-admin/includes/image.php';
 $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>$pack['name'].' · fotos reales del catálogo','post_status'=>'inherit',
 'post_content'=>'Composición de imágenes originales de cada referencia. Sin imágenes de carne generadas por IA.'],$file,$pid,true);
 if(is_wp_error($attachment)||!$attachment)throw new RuntimeException('Media create failed');
 wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$file));
 update_post_meta($attachment,'_wp_attachment_image_alt',$pack['name'].', surtido real de carnes');
 return (int)$attachment;
}
$mode=getenv('MDO_MUSEE_REVISED_APPLY')==='1'?'apply':'inspect';
$report=[];
foreach($manifest as $key=>$pack) {
 $id=$pack['id'];$p=wc_get_product($id);
 if(!$p||$p->get_status()!=='draft'||get_post_meta($id,'_emdo_mentta_pack_key',true)!==$key ||
    !in_array($id,emdo_musee_review_ids(),true)) {
   fwrite(STDERR,"REVIEW_DRAFT_MISSING ".$id."\n");exit(4);
 }
 $resolved=[];$missing=[];
 foreach($pack['contents'] as [$src,$qty,$label]){
  if(!isset($source[$src])){
    $missing[]=['source'=>$src,'why'=>$errors[$src]??'unknown'];
    if($src==='buffalo_entrecot' && $key==='pasaporte-salvaje'){
       $resolved[]=['key'=>$src,'id'=>0,'qty'=>$qty,'label'=>$label,'image'=>0,'file'=>'','available'=>false];
    }
    continue;
  }
  $s=$source[$src];$resolved[]=['key'=>$src,'id'=>$s['id'],'qty'=>$qty,'label'=>$label,'image'=>$s['photo'],
   'file'=>$s['file'],'available'=>$s['product']->is_in_stock()];
 }
 $entry=['id'=>$id,'key'=>$key,'price'=>$pack['price'],'weight_kg'=>$pack['weight'],
   'original_images'=>count(array_filter(array_column($resolved,'image'))),'total_items'=>count($pack['contents']),'missing'=>$missing,
   'matched_photo_ids'=>array_column($resolved,'image')];
 $report[]=$entry;
 if($missing && !($key==='pasaporte-salvaje' && count($missing)===1 && $missing[0]['source']==='buffalo_entrecot')) {
  echo 'PACK_NOT_MODIFIED_ORIGINAL_IMAGE_NOT_AVAILABLE '.wp_json_encode($entry,JSON_UNESCAPED_UNICODE)."\n";
  continue;
 }
 if($missing) echo 'PASAPORTE_BUFFALO_PHOTO_PENDING_SOURCE_MISSING '.wp_json_encode($entry,JSON_UNESCAPED_UNICODE)."\n";
 if($mode!=='apply') continue;
 $product=wc_get_product($id);
 $product->set_name($pack['name']);
 $product->set_regular_price((string)$pack['price']);$product->set_sale_price('');
 $product->set_weight((string)$pack['weight']);
 $product->set_short_description('<p>'.esc_html($pack['teaser']).($missing?' Pendiente de confirmar la referencia Entrecot de búfalo y su fotografía original.':'').'</p>');
 $html='<p>'.esc_html($pack['intro']).'</p><h2>Contenido exacto del pack</h2><ul>';
 foreach($resolved as $row)$html.='<li>'.esc_html($row['label']).'</li>';
 $html.='</ul><p>Producto refrigerado. Conservar según las indicaciones del fabricante. Las fotografías disponibles muestran los artículos originales incluidos. Cantidades y pesos indicados en cada referencia.</p>';
 if($missing)$html.='<p><strong>Revisión pendiente:</strong> no se ha localizado el entrecot de búfalo de 200 g ni su fotografía original en el catálogo de este proveedor. Esta ficha continúa como borrador, no disponible para Menta.</p>';
 $product->set_description($html);
 $product->set_gallery_image_ids(array_values(array_filter(array_column($resolved,'image'))));
 if($missing)$product->set_stock_status('outofstock');
 else if(!in_array(false,array_column($resolved,'available'),true))$product->set_stock_status('instock');
 $product->update_meta_data('_emdo_mentta_pack_lines',array_map(static function($x){unset($x['file']);return $x;},$resolved));
 $product->update_meta_data('_emdo_mentta_photos_100_percent_real',$missing?'8_real_1_pending':'yes');
 $product->update_meta_data('_yoast_wpseo_title',$pack['name'].' | Musée Gourmet');
 $product->update_meta_data('_yoast_wpseo_metadesc',mb_substr($pack['teaser'],0,155));
 $product->set_status('draft');
 $product->save();
 $poster=mdo_paint($pack,$resolved,$id);
 $product->set_image_id($poster);
 $product->set_status('draft');
 $product->save();
 clean_post_cache($id);wc_delete_product_transients($id);
 $verify=wc_get_product($id);
 if($verify->get_status()!=='draft'||abs((float)$verify->get_regular_price('edit')-$pack['price'])>0.001||
   count($verify->get_gallery_image_ids())!==(count($pack['contents'])-count($missing))||
   (int)$verify->get_image_id()!==(int)$poster||
   !get_attached_file($poster)) {fwrite(STDERR,"POST_UPDATE_VERIFICATION_FAILED $id\n");exit(6);}
 echo 'PACK_REAL_PHOTOGRAPHS_UPDATED '.wp_json_encode(['id'=>$id,'name'=>$pack['name'],'price'=>$verify->get_regular_price('edit'),
 'original_photos'=>count($resolved)-count($missing),'pending_photo_count'=>count($missing),'hero_attachment_id'=>$poster,'mentta_blocked'=>true,'status'=>'draft'],JSON_UNESCAPED_UNICODE)."\n";
}
echo 'REVISION_SUMMARY '.wp_json_encode(['mode'=>$mode,'packs'=>$report],JSON_UNESCAPED_UNICODE)."\n";
