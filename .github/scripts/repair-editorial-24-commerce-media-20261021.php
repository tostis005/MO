<?php
/** Idempotent editorial copy and featured media repair. Backup first; no changes outside the 24 approved posts. */
if (!defined('ABSPATH')) throw new RuntimeException('Requires WordPress');
$labels=[
  1 => ['es' => 'Puedes contrastar los orígenes y formatos en <a href="%s">los cafés de especialidad disponibles</a>, sin depender de una descripción genérica.', 'en' => 'You can compare origins and formats in <a href="%s">the specialty coffees currently available</a> rather than relying on a generic description.'],
  2 => ['es' => 'Para comprobar qué formatos ofrece realmente cada productor, revisa <a href="%s">las referencias de café disponibles</a> antes de decidir si necesitas molinillo.', 'en' => 'Check <a href="%s">the current coffee selection</a> to see which formats are genuinely offered before deciding whether you need a grinder.'],
  3 => ['es' => 'En <a href="%s">nuestra selección de café de especialidad</a> puedes comparar orígenes y perfiles sin tratar la etiqueta como una garantía de sabor.', 'en' => 'You can compare origins and flavour profiles in <a href="%s">the specialty coffee selection</a> without treating the label as a promise of taste.'],
  4 => ['es' => 'Al elegir una bolsa, consulta <a href="%s">el tueste y el formato de los cafés disponibles</a> para comprobar qué encaja con tu italiana.', 'en' => 'Before ordering, inspect <a href="%s">the available coffees\' roast and format</a> to see what suits your moka pot.'],
  5 => ['es' => 'Las <a href="%s">fichas de los cafés que tenemos a la venta</a> indican qué perfil ofrece cada referencia; comprueba además que tu máquina admite ese grano.', 'en' => 'The <a href="%s">coffee listings currently on sale</a> describe each profile; also check that the beans suit your machine.'],
  6 => ['es' => 'Para comparar perfiles concretos, puedes explorar <a href="%s">los cafés de origen disponibles</a> y revisar su composición antes de comprar.', 'en' => 'To compare real flavour profiles, explore <a href="%s">the origin coffees on offer</a> and check their composition before ordering.'],
  7 => ['es' => 'Si consultas <a href="%s">nuestros cafés de especialidad</a>, verifica siempre la clase de tueste: no debes dar por hecho que contienen torrefacto.', 'en' => 'When looking through <a href="%s">our specialty coffees</a>, check the roast description rather than assuming any contain torrefacto.'],
  8 => ['es' => 'Puedes comenzar por <a href="%s">los cafés disponibles en la tienda</a> y elegir según sus notas sensoriales, no solo por un nombre comercial.', 'en' => 'Start with <a href="%s">the coffees available in the shop</a> and choose by their tasting notes rather than just a marketing name.'],
  9 => ['es' => 'Las <a href="%s">fichas de los cafés de origen disponibles</a> permiten contrastar cómo describe el tostador cada perfil.', 'en' => 'The <a href="%s">current origin-coffee listings</a> let you compare how the roaster describes each profile.'],
  10 => ['es' => 'Puedes contrastar esas diferencias en <a href="%s">las cremas de cacao que están disponibles</a>, comprobando sus ingredientes en cada ficha.', 'en' => 'You can compare those differences among <a href="%s">the chocolate spreads currently available</a> by reading each ingredient list.'],
  11 => ['es' => 'Si quieres comparar dos variedades de forma práctica, consulta <a href="%s">los aguacates disponibles esta temporada</a> antes de elegir la caja.', 'en' => 'If you want a practical variety comparison, check <a href="%s">the avocados currently in season</a> before choosing a box.'],
  15 => ['es' => 'Un ejemplo de formato mixto es <a href="%s">el surtido de chirimoyas y mangos españoles</a>; comprueba siempre las variedades efectivas del pedido.', 'en' => 'One mixed-format option is <a href="%s">the Spanish cherimoya and mango pack</a>; always check which varieties it contains.'],
  16 => ['es' => 'Si eliges <a href="%s">una caja que incluye mangos</a>, confirma con el vendedor su estado previsto a la entrega antes de planificar las comidas.', 'en' => 'When choosing <a href="%s">a box containing mangoes</a>, check their expected condition on arrival before planning meals.'],
  17 => ['es' => 'Las <a href="%s">cajas mixtas con mango disponibles</a> pueden cambiar con la cosecha: confirma el origen y la variedad que recibirás.', 'en' => '<a href="%s">Available mixed boxes containing mango</a> may change with the harvest; confirm the actual origin and cultivar.'],
  18 => ['es' => 'Una <a href="%s">caja mixta de naranjas y aguacates</a> no sustituye a una caja específica de zumo: revisa la composición para cada uso.', 'en' => 'A <a href="%s">mixed orange and avocado box</a> is not the same as a dedicated juicing-orange box; check what is included.'],
  19 => ['es' => 'También existen <a href="%s">cajas mixtas de naranjas y pomelos</a>; su utilidad depende de qué variedades quieras comer.', 'en' => 'You may also find <a href="%s">mixed boxes of oranges and grapefruit</a>; their value depends on which citrus you want to eat.'],
  20 => ['es' => 'Si te interesa combinar cítricos, revisa <a href="%s">las cajas de mandarinas y naranjas disponibles</a> y confirma siempre su origen.', 'en' => 'For a mixed citrus order, look at <a href="%s">the mandarin and orange boxes available</a> and confirm their actual origin.'],
  22 => ['es' => 'Para dar el primer paso con una cantidad razonable, puedes consultar <a href="%s">la selección actual de frutas frescas</a> y comparar sus formatos.', 'en' => 'To begin with a realistic quantity, explore <a href="%s">the current fresh-fruit selection</a> and compare pack sizes.'],
];
$image_candidates=[
  1 => [18028, 18041],
  2 => [18027, 18033],
  3 => [18032, 18073],
  4 => [18029, 18049],
  5 => [18030, 18057],
  6 => [18031, 18065],
  7 => [18028, 18041],
  8 => [18029, 18049],
  9 => [18026, 18073],
  15 => [20542, 20541],
  16 => [19049, 19048],
  17 => [18797, 18796],
  18 => [18753, 18752],
  19 => [18561, 18560],
  20 => [18637, 18636],
];
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,
 'meta_query'=>[['key'=>'_emdo_newprod_plan_id','compare'=>'EXISTS']],'suppress_filters'=>true]);
$found=[];
foreach ($posts as $post) {
 $plan=(int)get_post_meta($post->ID,'_emdo_newprod_plan_id',true);
 if ($plan>=1 && $plan<=24) $found[$plan]=$post;
}
if(count($found)!==24)throw new RuntimeException('24 approved posts not found: '.count($found));
ksort($found);
$category=get_term_by('slug','cafe-de-especialidad','product_cat');
if(!$category instanceof WP_Term)throw new RuntimeException('Coffee category missing');
$coffee_products=wc_get_products(['status'=>'publish','category'=>[$category->slug],'stock_status'=>'instock','limit'=>30]);
$goodCoffee=0;
foreach($coffee_products as $product)if($product->is_visible() && $product->is_purchasable() && $product->is_in_stock())$goodCoffee++;
if($goodCoffee<1)throw new RuntimeException('No purchasable specialty coffees');
$coffee_url=get_term_link($category);
if(is_wp_error($coffee_url))throw new RuntimeException('Coffee URL unavailable');
$prepared=[];
foreach($found as $plan=>$post){
  $id=(int)$post->ID;
  $shop='';
  $img=0;
  if($plan>=1 && $plan<=9){
    $shop=$coffee_url;
    if(isset($image_candidates[$plan])){
      [$candidate,$product_id]=$image_candidates[$plan];
      $product=wc_get_product($product_id);
      if($product && $product->is_visible() && $product->is_purchasable() && $product->is_in_stock() && wp_attachment_is_image($candidate))$img=$candidate;
    }
  } elseif(isset($image_candidates[$plan])){
    [$candidate,$product_id]=$image_candidates[$plan];
    $product=wc_get_product($product_id);
    if($product && $product->is_visible() && $product->is_purchasable() && $product->is_in_stock() && wp_attachment_is_image($candidate)){
      $img=$candidate;$shop=get_permalink($product_id);
    }
  } elseif(in_array($plan,[10,11,22],true)){
    $shop=(string)get_post_meta($id,'_emdo_newprod_product_category_url',true);
  }
  if(!is_string($shop) || !str_starts_with($shop,home_url('/')))$shop='';
  $es=(string)$post->post_content;
  $en=(string)get_post_meta($id,'_en_US_post_content',true);
  if($en==='')throw new RuntimeException('Missing English copy: '.$plan);
  $fix_es=$es;$fix_en=$en;
  $link_added=false;
  if($shop!=='' && isset($labels[$plan]) && !str_contains($es,$shop)){
    $texts=$labels[$plan];
    $es_sentence=sprintf($texts['es'],esc_url($shop));
    $en_sentence=sprintf($texts['en'],esc_url($shop));
    foreach(['es','en'] as $lang){
      $body=$lang==='es'?$fix_es:$fix_en;
      if(str_contains($body,$shop))continue;
      $pos=strrpos($body,'</p>');
      if($pos===false)throw new RuntimeException('No paragraph end '.$plan.'/'.$lang);
      $add=' '.($lang==='es'?$es_sentence:$en_sentence);
      $body=substr_replace($body,$add,$pos,0);
      if($lang==='es')$fix_es=$body;else $fix_en=$body;
      $link_added=true;
    }
  }
  $prepared[$plan]=['id'=>$id,'image'=>$img,'shop'=>$shop,'es'=>$fix_es,'en'=>$fix_en,'link_added'=>$link_added,'original_image'=>(int)get_post_thumbnail_id($id)];
}
if(getenv('EMDO_REPAIR_PREFLIGHT')==='1'){
 echo 'EMDO_REPAIR_PREFLIGHT '.wp_json_encode(['ok'=>true,'count'=>count($prepared),'changes'=>array_map(static fn($plan,$x)=>['plan'=>$plan,'image'=>$x['image'],'shop'=>$x['shop'],'link_added'=>$x['link_added']],array_keys($prepared),$prepared)],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
 return;
}
$changed=[];
foreach($prepared as $plan=>$p){
 $id=$p['id'];
 $orig=get_post($id);
 $old_en=(string)get_post_meta($id,'_en_US_post_content',true);
 if(!metadata_exists('post',$id,'_emdo_editorial_repair_20261021_backup_es'))update_post_meta($id,'_emdo_editorial_repair_20261021_backup_es',wp_slash($orig->post_content));
 if(!metadata_exists('post',$id,'_emdo_editorial_repair_20261021_backup_en'))update_post_meta($id,'_emdo_editorial_repair_20261021_backup_en',wp_slash($old_en));
 if(!metadata_exists('post',$id,'_emdo_editorial_repair_20261021_backup_image'))update_post_meta($id,'_emdo_editorial_repair_20261021_backup_image',(int)get_post_thumbnail_id($id));
 if($orig->post_content!==$p['es']){
   $result=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>wp_kses_post($p['es'])]),true);
   if(is_wp_error($result))throw new RuntimeException('Spanish save failure '.$plan.':'.$result->get_error_message());
 }
 if($old_en!==$p['en'])update_post_meta($id,'_en_US_post_content',wp_slash(wp_kses_post($p['en'])));
 if($p['image']>0 && get_post_thumbnail_id($id)!==$p['image']){
   if(!set_post_thumbnail($id,$p['image']))throw new RuntimeException('Image save failed '.$plan);
   delete_post_meta($id,'_emdo_uses_default_featured');
 }
 if($p['shop']!=='')update_post_meta($id,'_emdo_newprod_product_category_url',esc_url_raw($p['shop']));
 $changed[]=['plan'=>$plan,'post_id'=>$id,'image'=>(int)get_post_thumbnail_id($id),'shop'=>$p['shop'],'link'=>(bool)$p['link_added'],
  'es_word_count'=>str_word_count(wp_strip_all_tags((string)get_post_field('post_content',$id))),
  'english_published'=>(string)get_post_meta($id,'_en_US_published',true)==='1'];
}
wp_cache_flush();
if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'EMDO_REPAIR_APPLIED '.wp_json_encode(['ok'=>true,'count'=>count($changed),'posts'=>$changed],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
