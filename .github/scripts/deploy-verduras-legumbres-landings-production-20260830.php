<?php
/**
 * Production deploy: curated Hortalizas/Verduras + Legumbres commercial SEO landings.
 * Spanish native pages + reviewed English aliases/content in _en_US_* metadata.
 * Dynamic product selection from the live catalogue; idempotent by _emdo_vl_landing_key.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

function emdo_vl_norm(string $s): string {
    $s = remove_accents(mb_strtolower($s));
    return preg_replace('/\s+/', ' ', trim($s));
}

function emdo_vl_find_term(array $needles): WP_Term {
    $terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
    if (is_wp_error($terms)) throw new Exception($terms->get_error_message());
    $matches = array();
    foreach ($terms as $t) {
        $hay = emdo_vl_norm($t->slug.' '.$t->name);
        foreach ($needles as $needle) {
            if (strpos($hay, emdo_vl_norm($needle)) !== false) { $matches[] = $t; break; }
        }
    }
    if (!$matches) throw new Exception('Product category not found for '.implode(',', $needles));
    usort($matches, static fn($a,$b) => ((int)$b->count <=> (int)$a->count));
    return $matches[0];
}

function emdo_vl_term_products(WP_Term $term): array {
    $q = new WP_Query(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','no_found_rows'=>true,
        'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$term->term_id),'include_children'=>true)),
        'orderby'=>'title','order'=>'ASC'
    ));
    $ids = array();
    foreach ($q->posts as $id) {
        $p = wc_get_product($id);
        if ($p && $p->is_in_stock()) $ids[] = (int)$id;
    }
    return array_values(array_unique($ids));
}

function emdo_vl_filter_products(array $ids, array $needles): array {
    $out = array();
    foreach ($ids as $id) {
        $p = get_post($id);
        if (!$p) continue;
        $hay = emdo_vl_norm($p->post_title.' '.$p->post_name);
        foreach ($needles as $needle) {
            if (strpos($hay, emdo_vl_norm($needle)) !== false) { $out[] = (int)$id; break; }
        }
    }
    return array_values(array_unique($out));
}

function emdo_vl_products_shortcode(array $ids): string {
    $ids = array_values(array_unique(array_filter(array_map('intval',$ids), static fn($id)=>get_post_status($id)==='publish')));
    if (!$ids) return '';
    return '[products ids="'.implode(',', $ids).'" columns="3" orderby="post__in"]';
}

function emdo_vl_aioseo(int $post_id, string $title, string $description): void {
    global $wpdb;
    $table = $wpdb->prefix.'aioseo_posts';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table)) !== $table) return;
    $row_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE post_id=%d LIMIT 1",$post_id));
    if ($row_id) $wpdb->update($table,array('title'=>$title,'description'=>$description),array('id'=>$row_id),array('%s','%s'),array('%d'));
}

function emdo_vl_page_id(string $key): int {
    $ids=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private'),'posts_per_page'=>2,'fields'=>'ids','no_found_rows'=>true,'meta_key'=>'_emdo_vl_landing_key','meta_value'=>$key));
    return $ids ? (int)$ids[0] : 0;
}

function emdo_vl_render(array $d, string $products): string {
    $html='';
    foreach($d['intro'] as $p) $html.='<p>'.$p.'</p>\n';
    $html.=$products."\n";
    foreach($d['sections'] as $heading=>$paragraphs){
        $html.='<h2>'.$heading.'</h2>\n';
        foreach($paragraphs as $p) $html.='<p>'.$p.'</p>\n';
    }
    if(!empty($d['bullets'])){
        foreach($d['bullets'] as $heading=>$items){
            $html.='<h2>'.$heading.'</h2><ul>';
            foreach($items as $item) $html.='<li>'.$item.'</li>';
            $html.='</ul>\n';
        }
    }
    $html.='<h2>'.$d['faq_title'].'</h2>\n';
    foreach($d['faq'] as $q=>$a) $html.='<h3>'.$q.'</h3><p>'.$a.'</p>\n';
    $html.=$products."\n";
    return $html;
}

function emdo_vl_upsert(array $cfg): array {
    $products=emdo_vl_products_shortcode($cfg['products']);
    if($products==='') throw new Exception('No live products for '.$cfg['key']);
    $id=emdo_vl_page_id($cfg['key']);
    if(!$id){ $existing=get_page_by_path($cfg['es_slug'],OBJECT,'page'); if($existing instanceof WP_Post) $id=(int)$existing->ID; }
    $args=array('post_type'=>'page','post_status'=>'publish','post_title'=>$cfg['es_title'],'post_name'=>$cfg['es_slug'],'post_content'=>emdo_vl_render($cfg['es'],$products),'post_excerpt'=>$cfg['es_meta'],'comment_status'=>'closed','ping_status'=>'closed');
    if($id) $args['ID']=$id;
    $saved=wp_insert_post(wp_slash($args),true);
    if(is_wp_error($saved)) throw new Exception($saved->get_error_message());
    $id=(int)$saved;
    update_post_meta($id,'_emdo_vl_landing_key',$cfg['key']);
    update_post_meta($id,'_en_US_post_title','<span data-no-translation>'.$cfg['en_title'].'</span>');
    update_post_meta($id,'_en_US_post_name',$cfg['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',$cfg['en_meta']);
    update_post_meta($id,'_en_US_post_content','<span data-no-translation>'.emdo_vl_render($cfg['en'],$products).'</span>');
    update_post_meta($id,'_en_US_published','1');
    emdo_vl_aioseo($id,$cfg['seo_title'],$cfg['es_meta']);
    return array('key'=>$cfg['key'],'group'=>$cfg['group'],'id'=>$id,'es_title'=>$cfg['es_title'],'en_title'=>$cfg['en_title'],'es_url'=>home_url('/'.$cfg['es_slug'].'/'),'en_url'=>home_url('/en/'.$cfg['en_slug'].'/'),'products'=>array_values($cfg['products']));
}

function emdo_vl_link_category(WP_Term $term, array $pages): void {
    if(!$pages) return;
    $start='<!-- emdo-vl-landings-start -->'; $end='<!-- emdo-vl-landings-end -->';
    $pattern='/\\s*'.preg_quote($start,'/').'.*?'.preg_quote($end,'/').'\\s*/s';
    $es=preg_replace($pattern,"\n",(string)$term->description);
    $en=(string)get_term_meta((int)$term->term_id,'_en_US_description',true);
    if($en!=='') $en=preg_replace($pattern,"\n",$en);
    $es_links=array(); $en_links=array();
    foreach($pages as $p){
        $es_links[]='<a href="'.esc_url($p['es_url']).'">'.esc_html($p['es_title']).'</a>';
        $en_links[]='<a href="'.esc_url($p['en_url']).'">'.esc_html($p['en_title']).'</a>';
    }
    $es_block=$start.'<div class="emdo-seo-landings"><p><strong>Explora también:</strong> '.implode(' · ',$es_links).'</p></div>'.$end;
    $r=wp_update_term((int)$term->term_id,'product_cat',array('description'=>trim($es)."\n\n".$es_block));
    if(is_wp_error($r)) throw new Exception($r->get_error_message());
    if($en!==''){
        $en_block=$start.'<div class="emdo-seo-landings"><p><strong>Explore also:</strong> '.implode(' · ',$en_links).'</p></div>'.$end;
        update_term_meta((int)$term->term_id,'_en_US_description',trim($en)."\n\n".$en_block);
    }
}

$veg_term=emdo_vl_find_term(array('hortal','verdur'));
$leg_term=emdo_vl_find_term(array('legumbr'));
$veg_ids=emdo_vl_term_products($veg_term);
$leg_ids=emdo_vl_term_products($leg_term);

$groups=array(
    'cestas'=>emdo_vl_filter_products($veg_ids,array('hortaliza','caja','cesta')),
    'patatas'=>emdo_vl_filter_products($veg_ids,array('patata')),
    'pimientos'=>emdo_vl_filter_products($veg_ids,array('pimiento')),
    'alubias'=>emdo_vl_filter_products($leg_ids,array('alubia')),
);

$configs=array();
if(count($groups['cestas'])>=2) $configs[]=array(
'key'=>'cestas-verduras','group'=>'verduras','es_slug'=>'cestas-verduras-hortalizas-domicilio','en_slug'=>'fresh-vegetable-boxes-delivered-spain','es_title'=>'Cestas de Verduras y Hortalizas a Domicilio','en_title'=>'Fresh Vegetable Boxes Delivered in Spain','seo_title'=>'Cestas de Verduras a Domicilio | Hortalizas de Temporada','es_meta'=>'Compra cestas de verduras y hortalizas frescas a domicilio directamente del agricultor. Compara cajas y formatos de La Huerta de Ana Mary.','en_meta'=>'Buy fresh seasonal vegetable boxes delivered in Spain from the grower. Compare box sizes and multi-delivery options from La Huerta de Ana Mary.','products'=>$groups['cestas'],
'es'=>array('intro'=>array('Las <strong>cestas de verduras y hortalizas a domicilio</strong> permiten recibir una selección de producto fresco sin tener que elegir cada referencia por separado. En El Mercado de Origen las cestas proceden de La Huerta de Ana Mary, productor de Fresno de la Vega, en León.','La composición puede variar con la temporada. Esa variación es parte de la propuesta: la cesta se adapta a lo que está disponible en la huerta en cada momento y puede combinar productos como patatas, pimientos, tomates, cebollas, calabacines, hojas y otras hortalizas según campaña.'),'sections'=>array('Cestas para una compra puntual o para varias entregas'=>array('Antes de elegir conviene distinguir entre una caja para una compra concreta y los formatos preparados para recibir varias cajas a lo largo de varias semanas o meses. El número de entregas, el peso de cada caja y las condiciones de envío se indican en cada producto.','Si buscas variedad sin planificar cada compra, una cesta mixta es una forma sencilla de abastecer la cocina. Para hogares que consumen verduras con frecuencia, los formatos de varias entregas ayudan a repartir la cantidad en el tiempo.'),'Qué puede incluir una cesta de temporada'=>array('No todas las cajas deben contener exactamente lo mismo. La disponibilidad depende de la época del año y de la cosecha. Por eso es más útil fijarse en el peso total, el ritmo de entrega y el tipo de surtido que en esperar una lista idéntica durante todo el año.','La Huerta de Ana Mary trabaja con hortalizas de Fresno de la Vega y adapta las combinaciones a los productos disponibles en cada campaña.'),'Cómo elegir el formato'=>array('Para una primera compra suele ser más fácil empezar por una caja o formato de menor compromiso y comprobar el ritmo real de consumo del hogar. Si la cesta se integra bien en la cocina semanal, los formatos de varias entregas pueden resultar más cómodos.','Revisa siempre la ficha concreta para confirmar peso, frecuencia, zona de envío y composición orientativa.')),'bullets'=>array('Qué comparar antes de comprar'=>array('Peso de cada caja.','Número y frecuencia de entregas.','Composición de temporada.','Condiciones y zona de envío.','Número de personas y consumo habitual del hogar.')),'faq_title'=>'Preguntas frecuentes','faq'=>array('¿Las cestas llevan siempre las mismas verduras?'=>'No necesariamente. La composición puede cambiar según la temporada y la disponibilidad de la huerta.','¿Hay opciones de varias entregas?'=>'Sí, el catálogo puede incluir formatos con varias cajas distribuidas a lo largo de semanas o meses.','¿De dónde proceden las hortalizas?'=>'Las cestas de esta selección proceden de La Huerta de Ana Mary, en Fresno de la Vega (León).')),
'en'=>array('intro'=>array('<strong>Fresh vegetable boxes delivered in Spain</strong> are a practical way to receive a mixed selection of produce without choosing every vegetable separately. El Mercado de Origen works with La Huerta de Ana Mary, a grower based in Fresno de la Vega, León.','The contents can change with the season. This is part of the idea: each box reflects what is available from the farm and may include potatoes, peppers, tomatoes, onions, courgettes, leafy vegetables and other produce depending on the crop calendar.'),'sections'=>array('One-off boxes and multi-delivery options'=>array('Before ordering, distinguish between a one-off box and formats designed to send several boxes over a number of weeks or months. The product page shows the box weight, delivery schedule and shipping conditions.','A mixed box works well for households that want variety without planning every individual item, while multi-delivery formats spread the quantity over time.'),'What a seasonal vegetable box can contain'=>array('The contents do not need to be identical throughout the year. Availability changes with season and harvest, so total weight, delivery frequency and type of selection are more useful than expecting the same list every month.','La Huerta de Ana Mary grows produce linked to Fresno de la Vega and adjusts its boxes to what is available during each season.'),'Choosing the right format'=>array('For a first order, a smaller commitment can help you understand your household consumption. If the box fits your weekly cooking habits, multi-delivery options can be convenient.','Always check the individual product page for weight, frequency, shipping area and indicative contents.')),'bullets'=>array('What to compare before ordering'=>array('Weight per box.','Number and frequency of deliveries.','Seasonal contents.','Shipping area and conditions.','Household size and normal consumption.')),'faq_title'=>'Frequently asked questions','faq'=>array('Do the boxes always contain the same vegetables?'=>'Not necessarily. Contents can change according to season and farm availability.','Are multi-delivery options available?'=>'Yes, the catalogue can include formats with several boxes delivered over weeks or months.','Where are the vegetables grown?'=>'This selection comes from La Huerta de Ana Mary in Fresno de la Vega, León, Spain.'))
));

if(count($groups['patatas'])>=2) $configs[]=array(
'key'=>'patatas-online','group'=>'verduras','es_slug'=>'comprar-patatas-online-directas-agricultor','en_slug'=>'buy-potatoes-online-direct-from-grower','es_title'=>'Comprar Patatas Online Directas del Agricultor','en_title'=>'Buy Potatoes Online Direct from the Grower','seo_title'=>'Comprar Patatas Online Directas del Agricultor','es_meta'=>'Compra patatas online directamente del agricultor. Compara variedades y formatos de La Huerta de Ana Mary, cultivadas en Fresno de la Vega.','en_meta'=>'Buy potatoes online direct from the grower. Compare varieties and pack sizes from La Huerta de Ana Mary in Fresno de la Vega, Spain.','products'=>$groups['patatas'],
'es'=>array('intro'=>array('Al <strong>comprar patatas online directamente del agricultor</strong> puedes elegir con más información sobre variedad, procedencia y formato. En El Mercado de Origen las patatas de esta selección proceden de La Huerta de Ana Mary, en Fresno de la Vega.','El catálogo puede incluir distintas variedades y tamaños de compra. La elección depende del uso culinario y, sobre todo en formatos grandes, de la cantidad que realmente vas a consumir y de cómo podrás conservarla en casa.'),'sections'=>array('La variedad importa'=>array('No todas las patatas se comportan igual en cocina. Algunas variedades son especialmente versátiles y otras resultan más adecuadas para cocción, asado, fritura o guisos. En cada ficha conviene revisar la variedad concreta y las indicaciones del productor.','Entre las referencias disponibles puede encontrarse Red Pontiac, una patata de piel roja y carne clara conocida por su versatilidad.'),'Formatos grandes: cuándo tienen sentido'=>array('Comprar varios kilos puede reducir la frecuencia de reposición, pero solo compensa si existe consumo suficiente y un lugar adecuado para guardarlas. Una despensa fresca, seca, ventilada y protegida de la luz ayuda a retrasar brotes y pérdidas de calidad.','Antes de elegir, compara kilos totales, precio, variedad y condiciones de envío.'),'Directas de una zona hortícola'=>array('Fresno de la Vega mantiene una fuerte tradición hortícola. Conocer el productor y la procedencia permite comprar la patata como un producto agrícola concreto, no solo como una referencia genérica de supermercado.')),'bullets'=>array('Qué mirar al elegir patatas online'=>array('Variedad.','Kilos del formato.','Uso culinario previsto.','Capacidad de almacenamiento en casa.','Condiciones de envío.')),'faq_title'=>'Preguntas frecuentes','faq'=>array('¿Qué es la patata Red Pontiac?'=>'Es una variedad de piel roja y carne clara, utilizada para diferentes preparaciones por su carácter polivalente.','¿Compensa comprar 20 kg de patatas?'=>'Puede compensar en hogares con consumo alto si se dispone de un lugar fresco, seco, ventilado y oscuro para almacenarlas.','¿Quién produce estas patatas?'=>'Las referencias de esta selección proceden de La Huerta de Ana Mary, en Fresno de la Vega.')),
'en'=>array('intro'=>array('When you <strong>buy potatoes online direct from the grower</strong>, you can compare variety, origin and pack size rather than treating every potato as the same product. This selection comes from La Huerta de Ana Mary in Fresno de la Vega, León.','The catalogue may include different varieties and quantities. The right choice depends on cooking use and, particularly for larger packs, how much your household will realistically consume and store well.'),'sections'=>array('Potato variety matters'=>array('Different potato varieties behave differently in the kitchen. Some are highly versatile, while others are better suited to boiling, roasting, frying or stews. Check the individual product page for the named variety and grower guidance.','Available references can include Red Pontiac, a red-skinned, light-fleshed variety known for its versatility.'),'When larger packs make sense'=>array('Buying several kilograms reduces how often you need to restock, but it only makes sense when consumption is high enough and storage conditions are suitable. Keep potatoes in a cool, dry, ventilated place away from light.','Compare total kilograms, price, variety and shipping conditions before choosing.'),'Direct from a vegetable-growing area'=>array('Fresno de la Vega has a strong horticultural tradition. Knowing the grower and origin turns the purchase into a choice between identifiable agricultural products rather than anonymous commodity potatoes.')),'bullets'=>array('What to check when buying potatoes online'=>array('Variety.','Pack weight.','Intended cooking use.','Storage space at home.','Shipping conditions.')),'faq_title'=>'Frequently asked questions','faq'=>array('What is Red Pontiac?'=>'It is a red-skinned, light-fleshed potato variety used for a range of cooking methods.','Is a 20 kg potato pack practical?'=>'It can be for high-consumption households with a cool, dry, dark and ventilated storage space.','Who grows these potatoes?'=>'The references in this selection come from La Huerta de Ana Mary in Fresno de la Vega, Spain.'))
));

if(count($groups['pimientos'])>=2) $configs[]=array(
'key'=>'pimientos-asar','group'=>'verduras','es_slug'=>'pimientos-para-asar-online','en_slug'=>'roasting-peppers-online','es_title'=>'Pimientos para Asar','en_title'=>'Roasting Peppers Online','seo_title'=>'Comprar Pimientos para Asar | Pimiento Lamuyo Rojo','es_meta'=>'Compra pimientos para asar directamente del agricultor. Compara pimiento Lamuyo rojo y formatos grandes de La Huerta de Ana Mary.','en_meta'=>'Buy roasting peppers direct from the grower. Compare red Lamuyo peppers and larger formats from La Huerta de Ana Mary.','products'=>$groups['pimientos'],
'es'=>array('intro'=>array('Los <strong>pimientos para asar</strong> funcionan mejor cuando tienen carne suficiente para soportar el calor, pelarse con facilidad y mantener una textura agradable después de la cocción. Por eso el tipo de pimiento importa tanto como el tamaño del formato.','En El Mercado de Origen puedes encontrar pimientos de La Huerta de Ana Mary, incluidos pimientos Lamuyo rojos y formatos pensados específicamente para quienes quieren asar una cantidad mayor.'),'sections'=>array('Por qué el Lamuyo encaja bien para asar'=>array('El pimiento Lamuyo se caracteriza por su tamaño grande y una carne gruesa. Estas cualidades lo hacen especialmente práctico para horno, parrilla, rellenos y preparaciones en las que después se quiere retirar la piel.','También puede consumirse crudo, pero su estructura hace que sea una de las opciones naturales cuando el objetivo principal es asar.'),'Comprar por unidades o en formato grande'=>array('Para consumo cotidiano puede bastar con cantidades pequeñas. Si vas a preparar conservas caseras, congelar pimiento asado o cocinar para varias personas, un formato de varios kilos reduce la necesidad de repetir la compra.','El formato grande solo tiene sentido si tienes previsto procesar o consumir el producto antes de que pierda calidad.'),'Del agricultor a casa'=>array('Conocer el productor permite saber de dónde procede el pimiento y elegir según variedad y uso. Las referencias de esta selección están vinculadas a La Huerta de Ana Mary y a Fresno de la Vega.')),'bullets'=>array('Qué comparar antes de comprar'=>array('Variedad del pimiento.','Cantidad total.','Uso: asar, rellenar, ensalada o conserva.','Madurez y estado al recibirlo.','Condiciones de envío.')),'faq_title'=>'Preguntas frecuentes','faq'=>array('¿El pimiento Lamuyo es bueno para asar?'=>'Sí. Su tamaño y carne gruesa lo hacen especialmente adecuado para asado, además de otros usos.','¿Puedo comprar una caja grande para asar?'=>'El catálogo incluye formatos de varios kilos cuando están disponibles; revisa la ficha para confirmar cantidad y condiciones.','¿Se pueden usar también en crudo?'=>'Sí. El Lamuyo puede utilizarse en ensaladas y otras preparaciones además del asado.')),
'en'=>array('intro'=>array('<strong>Roasting peppers</strong> benefit from enough flesh to handle high heat, peel easily and retain a pleasant texture after cooking. This makes pepper type just as important as pack size.','El Mercado de Origen offers peppers from La Huerta de Ana Mary, including red Lamuyo peppers and larger formats aimed at customers who want to roast a substantial quantity.'),'sections'=>array('Why Lamuyo works well for roasting'=>array('Lamuyo peppers are known for their large size and thick flesh. These features make them useful for oven roasting, grilling, stuffing and preparations where the skin is removed afterwards.','They can also be eaten raw, but their structure makes them a natural choice when roasting is the main purpose.'),'Small quantities or larger boxes'=>array('Small quantities suit normal household cooking. Larger boxes can make sense when preparing roasted peppers for several meals, freezing portions or cooking for a group.','A large pack is useful only when you can process or consume the peppers while quality is still high.'),'From the grower to your home'=>array('Knowing the grower makes it easier to choose by variety, origin and intended use. The references in this selection are linked to La Huerta de Ana Mary and Fresno de la Vega, León.')),'bullets'=>array('What to compare before ordering'=>array('Pepper variety.','Total quantity.','Use: roasting, stuffing, salads or preserves.','Ripeness and condition on arrival.','Shipping conditions.')),'faq_title'=>'Frequently asked questions','faq'=>array('Are Lamuyo peppers good for roasting?'=>'Yes. Their large size and thick flesh make them particularly suitable for roasting.','Can I buy a larger box for roasting?'=>'Larger multi-kilogram formats are available when listed in the catalogue; check the product page for current quantity and shipping details.','Can Lamuyo peppers be eaten raw?'=>'Yes. They also work in salads and other raw preparations.'))
));

if(count($groups['alubias'])>=2) $configs[]=array(
'key'=>'alubias-secas','group'=>'legumbres','es_slug'=>'comprar-alubias-secas-online','en_slug'=>'buy-dried-beans-online-spain','es_title'=>'Comprar Alubias Secas Online','en_title'=>'Buy Dried Beans Online from Spain','seo_title'=>'Comprar Alubias Secas Online | Blanca, Pinta y Canela','es_meta'=>'Compra alubias secas online directamente del productor. Compara alubia blanca, pinta y canela de La Huerta de Ana Mary.','en_meta'=>'Buy dried beans online from a Spanish grower. Compare white kidney, pinto and canela beans from La Huerta de Ana Mary.','products'=>$groups['alubias'],
'es'=>array('intro'=>array('Al <strong>comprar alubias secas online</strong> conviene elegir primero por variedad y por el tipo de plato que se quiere preparar. En el catálogo de El Mercado de Origen puedes comparar alubia blanca, alubia pinta y alubia canela de La Huerta de Ana Mary.','Las tres son legumbres secas, pero no ofrecen exactamente la misma experiencia en cocina. Tamaño, piel, textura interior y forma de ligar el caldo pueden hacer que una variedad resulte más apropiada para un guiso concreto.'),'sections'=>array('Alubia blanca, pinta o canela'=>array('La alubia blanca es una referencia muy versátil para guisos, potajes y platos en los que se busca un sabor suave. La pinta aporta una apariencia y carácter diferentes, mientras que la canela se reconoce por su color uniforme y una textura apreciada en cocina de cuchara.','No existe una variedad universalmente mejor. La elección depende de la receta y de la textura que quieras conseguir.'),'Legumbre seca: planificación y control de la cocción'=>array('Comprar la legumbre seca permite controlar el remojo, el punto de cocción, la sal y el caldo desde el principio. A cambio, requiere más planificación que un tarro ya cocido.','La antigüedad del grano, la dureza del agua y el método de cocción influyen en el tiempo final, por lo que las cifras de una receta deben entenderse como orientativas.'),'Directas del productor'=>array('Las alubias de esta selección proceden de La Huerta de Ana Mary. Identificar al productor y el origen ayuda a comparar productos más allá de una etiqueta genérica de “legumbres”.')),'bullets'=>array('Cómo elegir tus alubias'=>array('Variedad: blanca, pinta o canela.','Receta prevista.','Textura que buscas.','Cantidad que consumes habitualmente.','Tiempo disponible para remojo y cocción.')),'faq_title'=>'Preguntas frecuentes','faq'=>array('¿Las alubias secas necesitan remojo?'=>'Muchas variedades agradecen un remojo previo para hidratarse de forma uniforme y reducir el tiempo de cocción; sigue siempre las indicaciones del producto y de la receta.','¿Qué alubia es mejor para guisar?'=>'Depende del plato. Blanca, pinta y canela pueden funcionar en guisos, pero ofrecen matices de textura, aspecto y sabor diferentes.','¿Quién produce estas alubias?'=>'Las referencias de esta selección proceden de La Huerta de Ana Mary.')),
'en'=>array('intro'=>array('When you <strong>buy dried beans online</strong>, variety and intended dish are useful starting points. El Mercado de Origen lets you compare white kidney beans, pinto beans and canela beans from La Huerta de Ana Mary.','All are dried pulses, but they do not behave exactly the same in the kitchen. Size, skin, creamy texture and the way they thicken cooking liquid can make one variety better suited to a particular dish.'),'sections'=>array('White, pinto or canela beans'=>array('White beans are a versatile option for stews and dishes where a mild flavour is useful. Pinto beans bring a different appearance and character, while canela beans are known for their even brown-cinnamon colour and their place in traditional slow-cooked dishes.','There is no single best variety. Choose according to the recipe and the texture you want.'),'Dried beans give you control over cooking'=>array('Dried pulses let you control soaking, cooking point, seasoning and broth from the beginning. They require more planning than ready-cooked jars, but they give you more control over the final texture.','Bean age, water hardness and cooking method all affect timing, so recipe times should be treated as guidance rather than guarantees.'),'Direct from the producer'=>array('The beans in this selection come from La Huerta de Ana Mary. A visible producer and origin make it easier to compare real products rather than buying an anonymous generic pulse.')),'bullets'=>array('How to choose dried beans'=>array('Variety: white, pinto or canela.','Intended recipe.','Preferred texture.','Normal household consumption.','Time available for soaking and cooking.')),'faq_title'=>'Frequently asked questions','faq'=>array('Do dried beans need soaking?'=>'Many varieties benefit from soaking for more even hydration and shorter cooking; follow the product and recipe guidance.','Which beans are best for stews?'=>'It depends on the dish. White, pinto and canela beans can all work, with different textures, colours and flavour profiles.','Who produces these beans?'=>'The products in this selection come from La Huerta de Ana Mary in Spain.'))
));

$created=array(); $veg_pages=array(); $leg_pages=array();
foreach($configs as $cfg){
    $r=emdo_vl_upsert($cfg); $created[]=$r;
    if($cfg['group']==='verduras') $veg_pages[]=$r; else $leg_pages[]=$r;
}
emdo_vl_link_category($veg_term,$veg_pages);
emdo_vl_link_category($leg_term,$leg_pages);

$verified=count($created)>0;
foreach($created as $p){
    if(get_post_status($p['id'])!=='publish' || count($p['products'])<2) $verified=false;
    if((string)get_post_meta($p['id'],'_en_US_published',true)!=='1') $verified=false;
}

echo wp_json_encode(array(
    'verified'=>$verified,
    'count'=>count($created),
    'vegetables'=>count($veg_pages),
    'legumes'=>count($leg_pages),
    'category_terms'=>array('vegetables'=>array('id'=>(int)$veg_term->term_id,'slug'=>$veg_term->slug,'url'=>get_term_link($veg_term)),'legumes'=>array('id'=>(int)$leg_term->term_id,'slug'=>$leg_term->slug,'url'=>get_term_link($leg_term))),
    'groups'=>array_map('array_values',$groups),
    'pages'=>$created
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
