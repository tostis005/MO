<?php
/**
 * Update only commercial copy on three private Musée Gourmet / Mentta review packs.
 * No image, price, permalink, stock, shipping, category or publication changes.
 */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')){fwrite(STDERR,"WC_CLI_REQUIRED\n");exit(2);}
if(!function_exists('emdo_musee_review_ids') || !function_exists('emdo_mentta_exclusive_ids') || !get_option('emdo_musee_mentta_review_hold')){fwrite(STDERR,"MENTTA_HOLD_NOT_ACTIVE\n");exit(3);}
$packs=[
 21814=>[
 'key'=>'pasaporte-salvaje','price'=>'153.49','count'=>9,'weight'=>1.8,
 'short'=>'Descubre 9 carnes exóticas y de caza en un solo pack de degustación: cebra, bisonte, alce, jabalí, venado, avestruz, canguro, cocodrilo y búfalo. Nueve cortes de unos 200 g, perfectos para organizar una cata a la plancha o a la parrilla. Peso aproximado: 1,8 kg.',
 'intro'=>'¿Te apetece probar algo diferente? Pasaporte Salvaje reúne nueve cortes de carnes exóticas y de caza para descubrir nuevos sabores y comparar sus matices. Desde la intensidad del venado y el jabalí hasta la delicadeza de la carne de cocodrilo, pasando por cebra, bisonte y avestruz, este pack ofrece una degustación variada en raciones fáciles de cocinar y compartir.',
 'experience'=>'Una selección pensada para los amantes de la carne, las cenas especiales y las barbacoas en las que apetece sorprender. Puedes preparar las piezas por separado, probar diferentes puntos de cocción y disfrutar del contraste de texturas de cada especie.',
 'items'=>[
 ['zebra_filet','Filete de cebra — 200 g aprox.'],
 ['bison_filet','Filete de bisonte — 200 g aprox.'],
 ['elk_cadera','Cadera/babilla de alce — 200 g aprox.'],
 ['boar_tender','Solomillo de jabalí — 200 g aprox.'],
 ['deer_loin','Lomo de venado — 200 g aprox.'],
 ['ostrich_filet','Filete de avestruz — 200 g aprox.'],
 ['kangaroo_200','Lomo de canguro — 200 g aprox.'],
 ['croc_filet','Solomillo de cocodrilo — 200 g aprox.'],
 ['buffalo_loin','Lomo de búfalo — 200 g aprox.']
 ],
 'seo'=>'Pack de carnes exóticas Pasaporte Salvaje | 9 cortes gourmet',
 'meta'=>'Prueba 9 carnes exóticas y de caza: cebra, bisonte, alce, jabalí, venado, avestruz, canguro, cocodrilo y búfalo. Pack degustación de 1,8 kg.'
 ],
 21818=>[
 'key'=>'safari-africano','price'=>'154.99','count'=>8,'weight'=>1.9,
 'short'=>'Pack degustación de cebra, avestruz y cocodrilo con 5 cortes de carne y 3 packs de hamburguesas gourmet. Incluye filetes, lomos y solomillos, además de 6 hamburguesas de 150 g. Peso aproximado: 1,9 kg.',
 'intro'=>'Safari Africano te invita a explorar tres carnes poco habituales en una sola experiencia gastronómica: cebra, avestruz y cocodrilo. Una combinación de cortes enteros y hamburguesas gourmet para disfrutar de diferentes texturas y sabores, tanto a la plancha como en la parrilla.',
 'experience'=>'Es una propuesta ideal para una cena de degustación original o una barbacoa distinta: comienza con los filetes y solomillos para apreciar el carácter de cada carne y continúa con las hamburguesas de cebra, avestruz y cocodrilo. El nombre del pack es temático y no indica el origen geográfico de todas las piezas.',
 'items'=>[
 ['zebra_filet','Filete de cebra — 200 g aprox.'],
 ['zebra_loin','Lomo de cebra — 200 g aprox.'],
 ['zebra_burgers','2 hamburguesas Burger Meat de cebra — 150 g cada una'],
 ['ostrich_filet','Filete extra de avestruz — 200 g aprox.'],
 ['ostrich_tender','Solomillo de avestruz — 200 g aprox.'],
 ['ostrich_burgers','2 hamburguesas Burger Meat de avestruz — 150 g cada una'],
 ['croc_tender','Solomillo de cocodrilo — 200 g aprox.'],
 ['croc_burgers','2 hamburguesas Burger Meat de cocodrilo — 150 g cada una']
 ],
 'seo'=>'Pack de cebra, avestruz y cocodrilo | Safari Africano',
 'meta'=>'Descubre cebra, avestruz y cocodrilo en 5 cortes selectos y 6 hamburguesas gourmet. Pack Safari Africano de 1,9 kg para plancha o parrilla.'
 ],
 21820=>[
 'key'=>'vuelta-al-mundo','price'=>'158.99','count'=>10,'weight'=>1.95,
 'short'=>'Una degustación de 10 carnes diferentes en un mismo pack: avestruz, wagyu japonés A5, Black Angus, cebra, bisonte, venado, canguro, búfalo, alce y jabalí. Cortes individuales seleccionados, con un peso aproximado total de 1,95 kg.',
 'intro'=>'La Vuelta al Mundo es una propuesta para viajar a través de los sabores sin salir de la mesa. Este pack combina diez carnes diferentes, desde los medallones de wagyu japonés A5 y el entrecot Black Angus hasta cortes de avestruz, cebra, bisonte y varias especialidades de caza. Una selección creada para descubrir contrastes de sabor, jugosidad y textura en una sola degustación.',
 'experience'=>'Cada ración permite cocinar y probar una carne distinta, lo que convierte este surtido en una gran opción para compartir con amigos, celebrar una ocasión especial o sorprender a quien disfruta descubriendo nuevos cortes. Ideal para preparar en pequeñas tandas a la plancha o a la parrilla y comparar los resultados.',
 'items'=>[
 ['ostrich_filet','Filete extra de avestruz — 200 g aprox.'],
 ['japan_wagyu','Medallones de wagyu japonés A5 — 150 g aprox.'],
 ['black_angus','Entrecot de Black Angus — 200 g aprox.'],
 ['zebra_filet','Filete de cebra — 200 g aprox.'],
 ['bison_filet','Filete de bisonte — 200 g aprox.'],
 ['deer_loin','Lomo de venado — 200 g aprox.'],
 ['kangaroo_200','Lomo de canguro — 200 g aprox.'],
 ['buffalo_tender','Solomillo de búfalo — 200 g aprox.'],
 ['elk_loin','Lomo de alce — 200 g aprox.'],
 ['boar_loin','Lomo de jabalí — 200 g aprox.']
 ],
 'seo'=>'Pack degustación de 10 carnes del mundo | La Vuelta al Mundo',
 'meta'=>'Prueba 10 carnes: wagyu japonés A5, Black Angus, avestruz, cebra, bisonte, venado, canguro, búfalo, alce y jabalí. Pack de 1,95 kg.'
 ]
];
$go=getenv('MDO_MUSEE_COPY_APPLY')==='1';
$verified=[];
foreach($packs as $id=>$d){
 $p=wc_get_product($id);
 $lines=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 if(!$p || $p->get_status()!=='draft' || get_post_meta($id,'_emdo_mentta_pack_key',true)!==$d['key'] ||
 !in_array($id,emdo_musee_review_ids(),true) || !in_array($id,emdo_mentta_exclusive_ids(),true) ||
 !is_array($lines) || count($lines)!==$d['count'] ||
 abs((float)$p->get_regular_price('edit')-(float)$d['price'])>.001 ||
 abs((float)$p->get_weight('edit')-$d['weight'])>.001 ||
 !$p->get_image_id()) {fwrite(STDERR,"COPY_PREFLIGHT_FAILED ".$id."\n");exit(4);}
 foreach($d['items'] as $i=>[$key,$display]){
  if(($lines[$i]['key']??null)!==$key || (int)($lines[$i]['qty']??0)!==1) {
   fwrite(STDERR,"COMPONENTS_DONT_MATCH_COPY $id $key\n");exit(5);
  }
 }
 $before=[
  'price'=>$p->get_regular_price('edit'),'weight'=>$p->get_weight('edit'),
  'status'=>$p->get_status(),'featured'=>$p->get_image_id(),
  'gallery'=>$p->get_gallery_image_ids(),'sku'=>$p->get_sku(),
  'lines'=>$lines
 ];
 $html='<h2>'.esc_html($p->get_name()).'</h2>';
 $html.='<p>'.esc_html($d['intro']).'</p>';
 $html.='<h3>¿Qué incluye este pack?</h3><ul>';
 foreach($d['items'] as $item){$html.='<li>'.esc_html($item[1]).'</li>';}
 $html.='</ul>';
 $html.='<h3>Una degustación para disfrutar y compartir</h3>';
 $html.='<p>'.esc_html($d['experience']).'</p>';
 $html.='<p><strong>Peso total aproximado: '.esc_html(str_replace('.',',',number_format($d['weight'],2,'.',''))).' kg.</strong> Las piezas se suministran en los formatos indicados; el peso individual puede variar ligeramente.</p>';
 $html.='<p><strong>Conservación:</strong> producto de carne que requiere mantener la cadena de frío y conservarse conforme a la temperatura indicada en cada envase.</p>';
 if($go){
  $p->set_short_description('<p>'.esc_html($d['short']).'</p>');
  $p->set_description($html);
  $p->update_meta_data('_yoast_wpseo_title',$d['seo']);
  $p->update_meta_data('_yoast_wpseo_metadesc',$d['meta']);
  $p->save();
  wc_delete_product_transients($id);clean_post_cache($id);
  $check=wc_get_product($id);
  if($check->get_regular_price('edit')!==$before['price'] || $check->get_weight('edit')!==$before['weight'] ||
     $check->get_status()!=='draft' || $check->get_image_id()!==$before['featured'] ||
     $check->get_gallery_image_ids()!==$before['gallery'] || $check->get_sku()!==$before['sku'] ||
     get_post_meta($id,'_emdo_mentta_pack_lines',true)!==$before['lines'] ||
     !str_contains($check->get_description('edit'),'¿Qué incluye este pack?')) {
    fwrite(STDERR,"COPY_POSTVERIFY_FAILED $id\n");exit(6);
  }
 }
 $verified[]=['id'=>$id,'pack'=>$d['key'],'pieces'=>count($d['items']),'price'=>$d['price'],'new_description_chars'=>strlen($html),'mode'=>$go?'updated':'preflight',
  'seo_title'=>$d['seo'],'seo_meta'=>$d['meta']];
}
echo 'MUSEE_THREE_COMMERCE_COPY_VERIFIED '.wp_json_encode($verified,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
