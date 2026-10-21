<?php
/** One-off idempotent taxonomy repair for the 24 published original bilingual guides and available fruit bundles. */
if(!defined('ABSPATH'))throw new RuntimeException('Requires WordPress CLI');
if(!function_exists('wc_get_product'))throw new RuntimeException('WooCommerce unavailable');
$found=[];
foreach(get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>60,
 'meta_query'=>[['key'=>'_emdo_newprod_plan_id','compare'=>'EXISTS']],'suppress_filters'=>true]) as $p){
 $n=(int)get_post_meta($p->ID,'_emdo_newprod_plan_id',true);
 if($n>=1&&$n<=24)$found[$n]=$p;
}
if(count($found)!==24)throw new RuntimeException('Expected 24 approved articles, got '.count($found));
$parent=get_term_by('slug','frutas','category');
if(!$parent instanceof WP_Term)throw new RuntimeException('Existing frutas editorial category missing');
$tree=[
 'aguacates'=>['name'=>'Aguacates','posts'=>[11,12,13,14],
   'description'=>'Guías para distinguir los aguacates Hass, Bacon y Fuerte, reconocer su madurez, conocer la campaña española y conservar cada pieza. Compra según la variedad y la fecha en que quieres consumirla, contrastando origen, peso y disponibilidad real del vendedor.'],
 'mangos'=>['name'=>'Mangos','posts'=>[15,16,17],
   'description'=>'Información para elegir mangos según sus variedades y textura: Osteen, Kent, Keitt y otras opciones. Aprende a reconocer un mango maduro, a organizar el consumo de una caja y a identificar cuándo se cosecha en España sin confundir productos nacionales con importados.'],
 'citricos'=>['name'=>'Cítricos','posts'=>[18,19,20],
   'description'=>'Consejos para escoger naranjas de mesa, de zumo y mandarinas, comparar variedades y aprovechar su calendario de recolección. Las guías explican qué preguntar al comprar, cómo conservar el pedido y por qué la procedencia y el nombre de la variedad importan más que una foto genérica.'],
 'frutas-tropicales'=>['name'=>'Frutas tropicales','posts'=>[23],
   'description'=>'Conoce el sabor, la textura y los puntos de maduración de mango, papaya, chirimoya, pitahaya, guayaba y maracuyá. Aprende qué variedades probar y cómo recibirlas, conservarlas y disfrutarlas sin dar por supuesto que toda fruta tropical es importada.']
];
$catCopy=[
 'cafe-de-especialidad'=>'Descubre cafés de especialidad de distintos orígenes y perfiles. Para elegir, compara el tueste, las notas de sabor, la fecha de tostado cuando esté indicada y el formato disponible. No todas las cafeteras necesitan la misma molienda: antes de comprar, decide si usarás espresso, italiana, filtro o superautomática. Los productos mostrados corresponden al catálogo real y su stock puede cambiar. Las guías que aparecen después del catálogo te ayudan a entender estas diferencias sin tener que conocer el vocabulario de un barista.',
 'aguacates'=>'Elige aguacates según su variedad, la madurez prevista a la entrega y el peso del pedido. Hass, Bacon y Fuerte no tienen exactamente la misma piel ni la misma evolución al madurar. En esta categoría puedes comparar las cajas y combinaciones disponibles, incluidas mezclas con otras frutas cuando se indique en la ficha. Las guías enlazadas explican cómo saber si un aguacate está en su punto, conservarlo y reconocer el periodo habitual de cosecha española. Confirma siempre las variedades reales de cada lote.',
 'frutas'=>'Descubre frutas frescas, cajas y combinaciones para consumo diario. Para comparar opciones, revisa la procedencia, las variedades incluidas, el peso neto, el estado de maduración y los gastos de entrega del vendedor. Algunas cajas reúnen distintas frutas y no se componen siempre de las mismas piezas: la ficha de cada producto debe explicarlo. Consulta nuestras guías para elegir frutas de temporada, conservarlas al recibirlas y pedir cantidades que puedas consumir sin desperdicio.',
 'frutas-tropicales'=>'Explora frutas tropicales y sus diferentes perfiles de sabor: desde mangos jugosos hasta papayas, chirimoyas, pitahayas y otras variedades. Algunas se producen en España y otras proceden de otros países; comprueba el origen en cada ficha. Si eliges una caja mixta, revisa qué incluye y cómo llegará de madura. Las guías de esta categoría explican cómo reconocer la madurez del mango, qué textura esperar de distintas frutas y cómo escoger productos que encajen con tu hogar.'
];
$review=[];
foreach($tree as $slug=>$cfg){
 $existing=get_term_by('slug',$slug,'category');
 if($existing instanceof WP_Term && (int)$existing->parent!==(int)$parent->term_id)throw new RuntimeException('Conflicting category hierarchy '.$slug);
 $review['category_'.$slug]=['existing'=>$existing instanceof WP_Term,'posts'=>$cfg['posts']];
}
$frutaTerm=get_term_by('slug','frutas','product_cat');
$tropicalTerm=get_term_by('slug','frutas-tropicales','product_cat');
if(!($frutaTerm instanceof WP_Term)||!($tropicalTerm instanceof WP_Term))throw new RuntimeException('Commercial fruit categories unavailable');
$toCat=['tropical'=>[],'frutas'=>[]];$products=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>250,'fields'=>'ids',
 'tax_query'=>[['taxonomy'=>'product_cat','field'=>'slug','terms'=>['packs-y-lotes'],'include_children'=>false]]]);
foreach($products as $id){
 $p=wc_get_product((int)$id);if(!$p||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock())continue;
 $name=remove_accents(mb_strtolower($p->get_name()));
 $termSlugs=wp_get_post_terms($id,'product_cat',['fields'=>'slugs']);
 if(is_wp_error($termSlugs))continue;
 if(preg_match('~(?<![a-z])mangos?(?![a-z])~u',$name) && !in_array('frutas-tropicales',$termSlugs,true))$toCat['tropical'][]=(int)$id;
 if(preg_match('~(?<![a-z])(naranjas?|mandarinas?|pomelos?)(?![a-z])~u',$name)
    && !in_array('frutas',$termSlugs,true) && !in_array('aguacates',$termSlugs,true))$toCat['frutas'][]=(int)$id;
}
if(count($toCat['tropical'])>25 || count($toCat['frutas'])>25)throw new RuntimeException('Unexpected large catalog update');
$review['products_to_assign']=$toCat;
foreach($catCopy as $slug=>$copy){
 $term=get_term_by('slug',$slug,'product_cat');
 $review['copy_'.$slug]=['exists'=>$term instanceof WP_Term,
  'words'=>$term instanceof WP_Term?count(preg_split('/\s+/u',trim(wp_strip_all_tags($term->description)))):0];
}
if(getenv('EMDO_CLUSTER_PREFLIGHT')==='1'){
 echo 'EMDO_CLUSTER_PREFLIGHT '.wp_json_encode(['ok'=>true,'articles'=>count($found),'review'=>$review],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
 return;
}
$created=[];$changed=[];
foreach($tree as $slug=>$cfg){
 $term=get_term_by('slug',$slug,'category');
 if(!$term instanceof WP_Term){
   $result=wp_insert_term($cfg['name'],'category',['slug'=>$slug,'parent'=>$parent->term_id,'description'=>$cfg['description']]);
   if(is_wp_error($result))throw new RuntimeException('Create category '.$slug.': '.$result->get_error_message());
   $term=get_term((int)$result['term_id'],'category');
   $created[]=$slug;
 }
 if(!$term instanceof WP_Term)throw new RuntimeException('Create category missing '.$slug);
 foreach($cfg['posts'] as $num){
   $id=$found[$num]->ID;
   if(!has_term((int)$term->term_id,'category',$id)){
     $old=wp_get_post_terms($id,'category',['fields'=>'ids']);
     if(is_wp_error($old))throw new RuntimeException('Terms unavailable '.$num);
     if(!metadata_exists('post',$id,'_emdo_cluster_20261021_original_blog_terms'))update_post_meta($id,'_emdo_cluster_20261021_original_blog_terms',$old);
     $res=wp_set_post_terms($id,[(int)$term->term_id],'category',true);
     if(is_wp_error($res))throw new RuntimeException('Assign category '.$num);
     $changed[]=['post'=>$num,'category'=>$slug];
   }
 }
}
$descChanged=[];
foreach($catCopy as $slug=>$copy){
 $term=get_term_by('slug',$slug,'product_cat');if(!$term instanceof WP_Term)continue;
 $wordCount=count(preg_split('/\s+/u',trim(wp_strip_all_tags($term->description))));
 if($wordCount>=45)continue;
 if(!metadata_exists('term',$term->term_id,'_emdo_cluster_20261021_original_product_cat_description')){
   update_term_meta($term->term_id,'_emdo_cluster_20261021_original_product_cat_description',(string)$term->description);
 }
 $ret=wp_update_term($term->term_id,'product_cat',['description'=>$copy]);
 if(is_wp_error($ret))throw new RuntimeException('Description save '.$slug);
 $descChanged[]=$slug;
}
$recategorised=[];
foreach($toCat as $kind=>$ids){
 $termID=$kind==='tropical'?$tropicalTerm->term_id:$frutaTerm->term_id;
 foreach($ids as $pid){
    $old=wp_get_post_terms($pid,'product_cat',['fields'=>'ids']);
    if(is_wp_error($old))throw new RuntimeException('Product terms error '.$pid);
    if(!metadata_exists('post',$pid,'_emdo_cluster_20261021_original_product_terms'))update_post_meta($pid,'_emdo_cluster_20261021_original_product_terms',$old);
    $done=wp_set_object_terms($pid,[(int)$termID],'product_cat',true);
    if(is_wp_error($done))throw new RuntimeException('Product taxonomy save failed '.$pid);
    $recategorised[]=['id'=>$pid,'group'=>$kind];
 }
}
wp_cache_flush();
if(function_exists('rocket_clean_domain'))rocket_clean_domain();
echo 'EMDO_CLUSTER_APPLIED '.wp_json_encode(['ok'=>true,'created_categories'=>$created,'post_categories'=>$changed,
 'product_categories'=>$recategorised,'commercial_copy'=>$descChanged],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
