<?php
/**
 * Improve empty chocolate category introductions and Falang labels only.
 * Does not change images, products, product prices or other taxonomies.
 */
if(!defined('ABSPATH'))throw new RuntimeException('WordPress required');
$catalog=[
'chocolates-artesanos'=>[
 'es'=>'El chocolate artesano ofrece muchas formas de disfrutar del cacao: tabletas de distintos porcentajes, bombones, elaboraciones para repostería y pequeños detalles para regalar. En esta selección de El Mercado de Origen reunimos productos de Chocolates Artesanos Isabel con ingredientes y presentaciones diferentes. Puedes explorar las familias por formato, comparar la composición de cada referencia y consultar nuestras guías sobre el origen del cacao, el chocolate Bean to Bar y sus usos en cocina. La disponibilidad se actualiza con la información del productor.',
 'en'=>'Artisan chocolate comes in many forms, from bars with different cocoa percentages to bonbons, baking ingredients and gifts. This El Mercado de Origen selection features Chocolates Artesanos Isabel, with individual products that differ in composition, format and intended use. Explore the ranges, check each product’s ingredients and discover our guides to cocoa origins, bean-to-bar chocolate and practical kitchen techniques. Product availability reflects the supplier’s latest updates.',
 'name'=>'Artisan chocolate'
],
'tabletas-de-chocolate'=>[
 'es'=>'Las tabletas son una buena forma de comparar el carácter de distintos chocolates. Aquí encontrarás variedades negras, con leche, blancas y combinaciones con frutas, frutos secos o especias, según las existencias del productor. Conviene mirar tanto el porcentaje de cacao como el resto de ingredientes: dos tabletas con el mismo porcentaje pueden resultar muy diferentes. Si no sabes por dónde empezar, consulta las guías sobre chocolate Bean to Bar y cómo elegir entre intensidades.',
 'en'=>'Chocolate bars are a practical way to compare cocoa intensity, texture and added ingredients. Browse dark, milk and white varieties, including recipes with fruit, nuts or spices, depending on stock. The cocoa percentage is useful, but it does not tell the whole story: bars with similar figures may taste quite different. Our guides to bean-to-bar chocolate and cocoa percentages offer more context before choosing.',
 'name'=>'Chocolate bars'
],
'bombones-y-chocolatinas'=>[
 'es'=>'Un bombón, una roca de chocolate o una chocolatina crujiente pueden ofrecer una experiencia muy distinta de una tableta. Esta categoría reúne pequeñas piezas, surtidos y elaboraciones con inclusiones como frutos secos, café o especias. Consulta el peso y los ingredientes de cada formato antes de elegir, especialmente cuando vas a regalar o compartir. También puedes explorar nuestras recomendaciones para organizar una cata casera y comparar chocolates diferentes.',
 'en'=>'Bonbons, crunchy chocolate bites and small confections offer a different experience from a full bar. This selection brings together individual pieces and assortments, some made with nuts, coffee or spices. Check the ingredients, allergens and pack size for each product, particularly when sharing or gifting. Our chocolate tasting guide can help you compare different styles at home.',
 'name'=>'Bonbons and chocolate bites'
],
'cremas-de-cacao'=>[
 'es'=>'Las cremas de cacao no son todas iguales. Algunas combinan chocolate con avellanas o almendras; otras incorporan aceite de oliva virgen extra, café o ingredientes vegetales. En esta sección puedes comparar sus propuestas de sabor y textura y encontrar la que mejor encaje con tostadas, postres o repostería. Revisa siempre la composición, los alérgenos y las indicaciones de conservación de la ficha concreta.',
 'en'=>'Cocoa spreads vary widely in their ingredients and flavour profiles. Some combine chocolate with hazelnuts or almonds, while others feature extra virgin olive oil, coffee or plant-based ingredients. Compare the available varieties for spreading, desserts and baking. Each product page provides the source composition, allergen information and storage instructions when supplied by the maker.',
 'name'=>'Cocoa spreads'
],
'cacao-para-beber-y-cocinar'=>[
 'es'=>'Para cocinar con cacao conviene diferenciar los ingredientes: nibs, habas, cascarilla, cacao en polvo, pasta y manteca tienen usos distintos. También hay chocolates para bebidas y coberturas en gotas o chips que facilitan la dosificación. Reunimos aquí las presentaciones pensadas para preparar recetas y bebidas, no tabletas de degustación. Si dudas entre formatos, nuestras guías explican cómo fundir chocolate, utilizar nibs o distinguir el cacao natural del alcalinizado.',
 'en'=>'Cocoa nibs, husks, beans, powder, cocoa mass and cocoa butter each behave differently in recipes. This category also includes chocolate for hot drinks, couverture drops and chips for easier measuring. These are ingredients chosen for preparing food and drinks rather than everyday tasting bars. Our guides cover melting chocolate, choosing cocoa products and making the most of their different formats.',
 'name'=>'Cocoa for drinks and baking'
],
'packs-y-regalos'=>[
 'es'=>'Regalar chocolate puede ser tan sencillo como elegir una selección de tabletas o tan personal como preparar un detalle especial. Esta categoría reúne lotes, surtidos y propuestas de regalo de Chocolates Artesanos Isabel. Compara el contenido exacto de cada pack, su peso, los ingredientes de las piezas y las condiciones de personalización cuando existan. Los artículos que requieren opciones especiales pueden mostrarse sin compra directa hasta que esas opciones estén verificadas.',
 'en'=>'Chocolate gifts can range from a selection of bars to a more personal treat. Explore assortments, gift packs and special-format products from Chocolates Artesanos Isabel. Compare the contents, pack size and ingredients before choosing. Products requiring unverified customisation options may be shown for reference without direct checkout until those options can be offered accurately.',
 'name'=>'Chocolate gifts and assortments'
],
'chocolates-de-temporada'=>[
 'es'=>'Algunas elaboraciones de chocolate aparecen solo en épocas concretas del año. Aquí agrupamos turrones artesanos, figuras y regalos relacionados con celebraciones como Navidad o Pascua. Los sabores pueden incluir pralinés, frutas, especias y combinaciones singulares, pero las referencias cambian según el calendario del productor. Cuando un artículo esté agotado, no aparecerá como opción de compra disponible; nuestras guías navideñas ayudan a comparar las alternativas.',
 'en'=>'Some chocolate products are seasonal, including artisan nougat-style bars, festive figures and celebration gifts. The range may feature pralines, fruit, spices and less familiar flavour combinations, depending on the maker’s seasonal production. Availability changes throughout the year. Unavailable products are not presented as ready-to-buy recommendations, and our seasonal guides help explain the different styles.',
 'name'=>'Seasonal chocolates'
],
'kits-y-experiencias'=>[
 'es'=>'Los kits de chocolate permiten conocer una parte del trabajo artesanal de una forma más participativa. En lugar de comprar solo una tableta terminada, puedes explorar propuestas para elaborar chocolate en casa. Antes de elegir conviene revisar qué incluye cada kit, para cuántas preparaciones sirve y si necesita utensilios adicionales. Para entender el proceso de elaboración, consulta nuestra guía sobre chocolate Bean to Bar.',
 'en'=>'Chocolate-making kits offer a hands-on introduction to some of the steps behind an artisan bar. Rather than buying a finished product, you can explore activities for making chocolate at home. Check exactly what each kit contains, the intended yield and whether additional equipment is needed. Our bean-to-bar guide explains the wider production process.',
 'name'=>'Chocolate kits and experiences'
]
];
$blog=[
'es'=>'El chocolate se puede elegir por su origen, porcentaje de cacao, ingredientes, elaboración o el uso que le daremos. En esta sección encontrarás artículos independientes sobre chocolate artesano, Bean to Bar, conservación, cata y técnicas de repostería. También explicamos las diferencias entre el cacao en polvo, los nibs, la cascarilla y otras formas de trabajar con cacao. Las guías se relacionan entre sí y con los productos pertinentes de la tienda cuando están disponibles, sin sustituir la información de composición y alérgenos de cada ficha.',
'en'=>'Chocolate can be explored through cocoa origins, ingredients, processing, tasting and practical cooking techniques. Our articles cover artisan and bean-to-bar chocolate, storage, cocoa nibs and husks, and how to choose ingredients for baking. Related guides connect the topics, while relevant available products offer a route from learning to shopping. Always refer to individual product labels for ingredient and allergen information.'
];
$updated=[];$snapshots=[];
try{
 foreach($catalog as $slug=>$copy){
  $t=get_term_by('slug',$slug,'product_cat');if(!$t instanceof WP_Term)throw new RuntimeException('Missing category '.$slug);
  $id=(int)$t->term_id;
  $old_es=(string)$t->description;$old_en=(string)get_term_meta($id,'_en_US_description',true);
  $snapshots[]=['id'=>$id,'tax'=>'product_cat','es'=>$old_es,'en'=>$old_en,'name'=>get_term_meta($id,'_en_US_name',true),'published'=>get_term_meta($id,'_en_US_published',true)];
  if(trim(wp_strip_all_tags($old_es))===''){
   $result=wp_update_term($id,'product_cat',['description'=>'<p>'.esc_html($copy['es']).'</p>']);
   if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
  }
  if(trim(wp_strip_all_tags($old_en))==='')update_term_meta($id,'_en_US_description','<p>'.esc_html($copy['en']).'</p>');
  if(!(string)get_term_meta($id,'_en_US_name',true))update_term_meta($id,'_en_US_name',$copy['name']);
  update_term_meta($id,'_en_US_published','1');
  $updated[]=['slug'=>$slug,'id'=>$id,'es_chars'=>mb_strlen(wp_strip_all_tags(term_description($id,'product_cat'))),'en_chars'=>mb_strlen(wp_strip_all_tags((string)get_term_meta($id,'_en_US_description',true)))];
 }
 $term=get_category_by_slug('chocolates-y-cacao');if(!$term instanceof WP_Term)throw new RuntimeException('Missing blog category');
 $id=(int)$term->term_id;
 $snapshots[]=['id'=>$id,'tax'=>'category','es'=>(string)$term->description,'en'=>get_term_meta($id,'_en_US_description',true),'name'=>get_term_meta($id,'_en_US_name',true),'published'=>get_term_meta($id,'_en_US_published',true)];
 if(trim(wp_strip_all_tags((string)$term->description))===''){
  $x=wp_update_term($id,'category',['description'=>'<p>'.esc_html($blog['es']).'</p>']);
  if(is_wp_error($x))throw new RuntimeException($x->get_error_message());
 }
 if(!(string)get_term_meta($id,'_en_US_description',true))update_term_meta($id,'_en_US_description','<p>'.esc_html($blog['en']).'</p>');
 update_term_meta($id,'_en_US_published','1');
 $updated[]=['slug'=>'blog/chocolates-y-cacao','id'=>$id,'es_chars'=>mb_strlen(wp_strip_all_tags(term_description($id,'category'))),'en_chars'=>mb_strlen(wp_strip_all_tags((string)get_term_meta($id,'_en_US_description',true)))];
 foreach($updated as $row)if($row['es_chars']<170||$row['en_chars']<170)throw new RuntimeException('Category description unexpectedly short '.$row['slug']);
}catch(Throwable $e){
 foreach(array_reverse($snapshots) as $snap){
  wp_update_term($snap['id'],$snap['tax'],['description'=>$snap['es']]);
  foreach(['_en_US_description'=>$snap['en'],'_en_US_name'=>$snap['name'],'_en_US_published'=>$snap['published']] as $key=>$v){
   if($v==='')delete_term_meta($snap['id'],$key);else update_term_meta($snap['id'],$key,$v);
  }
 }
 throw $e;
}
wp_cache_flush();if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'ISABEL_CATEGORY_EDITORIAL='.wp_json_encode(['verified'=>count($updated)===9,'categories'=>$updated],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
