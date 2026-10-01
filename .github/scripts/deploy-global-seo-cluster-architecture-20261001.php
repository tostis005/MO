<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! taxonomy_exists( 'product_cat' ) ) { throw new Exception( 'WooCommerce unavailable.' ); }

function mdo_global_final_norm_20261001( string $text ): string {
    $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = remove_accents( mb_strtolower( $text, 'UTF-8' ) );
    $text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
    return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
}
function mdo_global_final_words_20261001( string $html ): int {
    $text=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));
    if(''===$text) return 0;
    preg_match_all('/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u',$text,$m);
    return count($m[0]);
}
function mdo_global_final_term_url_20261001( WP_Term $term ): string {
    $u=get_term_link($term);
    if(is_wp_error($u)) throw new Exception('Cannot resolve term URL: '.$term->taxonomy.'/'.$term->slug);
    return (string)$u;
}
function mdo_global_final_published_products_20261001( WP_Term $term ): int {
    return count(get_posts(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$term->term_id),'include_children'=>false))
    )));
}
function mdo_global_final_direct_posts_20261001( WP_Term $term ): array {
    return get_posts(array(
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,
        'tax_query'=>array(array('taxonomy'=>'category','field'=>'term_id','terms'=>array((int)$term->term_id),'include_children'=>false)),
        'orderby'=>'ID','order'=>'ASC'
    ));
}
function mdo_global_final_remove_boilerplate_20261001( string $content, array $phrases ): string {
    $targets=array_fill_keys(array_map('mdo_global_final_norm_20261001',$phrases),true);
    $cleaned=preg_replace_callback('/<p\b[^>]*>.*?<\/p>/isu',static function($m)use($targets){
        $norm=mdo_global_final_norm_20261001((string)$m[0]);
        return isset($targets[$norm]) ? '' : (string)$m[0];
    },$content);
    return is_string($cleaned)?$cleaned:$content;
}

$carnes_blog=get_term_by('slug','carnes','category');
$carnes_shop=get_term_by('slug','carnes','product_cat');
$wagyu_blog=get_term_by('slug','wagyu','category');
$wagyu_shop=get_term_by('slug','wagyu','product_cat');
if(!$carnes_blog instanceof WP_Term || !$carnes_shop instanceof WP_Term || !$wagyu_blog instanceof WP_Term || !$wagyu_shop instanceof WP_Term){
    throw new Exception('Carnes/Wagyu terms unavailable.');
}

/* ---------- Correct the Carnes -> Wagyu hierarchy and the 65-post cluster. ---------- */
$u=wp_update_term((int)$wagyu_blog->term_id,'category',array('name'=>'Wagyu','slug'=>'wagyu','parent'=>(int)$carnes_blog->term_id));
if(is_wp_error($u)) throw new Exception($u->get_error_message());
$u=wp_update_term((int)$wagyu_shop->term_id,'product_cat',array('name'=>'Wagyu','slug'=>'wagyu','parent'=>(int)$carnes_shop->term_id));
if(is_wp_error($u)) throw new Exception($u->get_error_message());
$wagyu_blog=get_term((int)$wagyu_blog->term_id,'category');
$wagyu_shop=get_term((int)$wagyu_shop->term_id,'product_cat');

$wagyu_ids=get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
    'meta_key'=>'_emdo_editorial_wagyu_65','meta_value'=>'2026-09-10.wagyu-65.v1'
));
if(65!==count($wagyu_ids)) throw new Exception('Expected 65 Wagyu posts, found '.count($wagyu_ids));
$wagyu_generic_blocks_removed=0;
foreach($wagyu_ids as $id){
    $id=(int)$id;
    wp_set_post_categories($id,array((int)$wagyu_blog->term_id),false);
    update_post_meta($id,'_emdo_global_cluster','wagyu');

    $content=(string)get_post_field('post_content',$id);
    $clean=preg_replace(
        '#\s*<h2>\s*Carnes relacionadas de nuestra tienda\s*</h2>\s*\[products\s+[^\]]*category=["\x{27}]carnes["\x{27}][^\]]*\]\s*#iu',
        "\n",
        $content
    );
    if(is_string($clean) && $clean!==$content){
        if(''===(string)get_post_meta($id,'_emdo_wagyu_pre_cluster_cleanup_20261001',true)){
            update_post_meta($id,'_emdo_wagyu_pre_cluster_cleanup_20261001',$content);
        }
        wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$clean)));
        $wagyu_generic_blocks_removed++;
    }

    $en=(string)get_post_meta($id,'_en_US_post_content',true);
    if(''!==$en){
        $en_clean=preg_replace(
            '#\s*<h2>\s*Related meats from our shop\s*</h2>\s*\[products\s+[^\]]*category=["\x{27}]carnes["\x{27}][^\]]*\]\s*#iu',
            "\n",
            $en
        );
        if(is_string($en_clean) && $en_clean!==$en){
            update_post_meta($id,'_en_US_post_content',$en_clean);
        }
    }
    clean_post_cache($id);
}

/* ---------- Build a cross-species editorial hub for Foie, patés y untables. ---------- */
$foie_blog=get_term_by('slug','foie-pates-untables','category');
if(!$foie_blog instanceof WP_Term){
    $created=wp_insert_term('Foie, patés y untables','category',array('slug'=>'foie-pates-untables'));
    if(is_wp_error($created)) throw new Exception($created->get_error_message());
    $foie_blog=get_term((int)$created['term_id'],'category');
}
$foie_shop=get_term_by('slug','foie-pates-untables','product_cat');
if(!$foie_blog instanceof WP_Term || !$foie_shop instanceof WP_Term) throw new Exception('Foie hub terms unavailable.');

$foie_desc='<p>Guías para entender las diferencias entre foie gras, paté, mousse, parfait, bloc, micuit y rillettes, cómo leer cada denominación y qué formato encaja mejor según el uso.</p>'
    . '<p>Esta categoría es transversal: reúne contenidos de producto y técnica aunque las elaboraciones puedan proceder de pato, oca u otras materias primas. Para contenidos específicamente de pato sigue disponible el subclúster de <a href="' . esc_url(mdo_global_final_term_url_20261001(get_term_by('slug','pato','category'))) . '">Pato</a>.</p>';
$u=wp_update_term((int)$foie_blog->term_id,'category',array('description'=>$foie_desc));
if(is_wp_error($u)) throw new Exception($u->get_error_message());

$duck_ids=get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
    'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck'
));
$foie_assigned=0;
foreach($duck_ids as $id){
    $identity=mdo_global_final_norm_20261001(
        (string)get_the_title((int)$id).' '.(string)get_post_field('post_name',(int)$id)
    );
    if(!preg_match('/\b(foie|pate|mousse|parfait|bloc|micuit|rillette|rillettes)\b/',$identity)) continue;
    $cats=wp_get_post_categories((int)$id);
    $cats[]= (int)$foie_blog->term_id;
    wp_set_post_categories((int)$id,array_values(array_unique(array_map('intval',$cats))),false);
    $foie_assigned++;
}

/* Pescados: create a blog hub only when enough real editorial content exists. */
$fish_matches=array();
$all_posts=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1));
foreach($all_posts as $post){
    if(!$post instanceof WP_Post) continue;
    $identity=mdo_global_final_norm_20261001($post->post_title.' '.$post->post_name);
    if(preg_match('/\b(pescado|pescados|marisco|mariscos|trucha|salmon|hueva|huevas)\b/',$identity)){
        $fish_matches[]=(int)$post->ID;
    }
}
$fish_blog=null;
$fish_assigned=0;
if(count($fish_matches)>=3){
    $fish_blog=get_term_by('slug','pescados-y-mariscos','category');
    if(!$fish_blog instanceof WP_Term){
        $created=wp_insert_term('Pescados y mariscos','category',array('slug'=>'pescados-y-mariscos'));
        if(is_wp_error($created)) throw new Exception($created->get_error_message());
        $fish_blog=get_term((int)$created['term_id'],'category');
    }
    $u=wp_update_term((int)$fish_blog->term_id,'category',array(
        'description'=>'<p>Guías sobre pescados, mariscos y elaboraciones del mar: conservación, formatos, servicio y criterios para elegir cada producto.</p>'
    ));
    if(is_wp_error($u)) throw new Exception($u->get_error_message());
    foreach($fish_matches as $id){
        $cats=wp_get_post_categories($id);
        $cats[]=(int)$fish_blog->term_id;
        wp_set_post_categories($id,array_values(array_unique(array_map('intval',$cats))),false);
        $fish_assigned++;
    }
}

/* ---------- Remove the remaining six public products from Sin categorizar. ---------- */
$uncat=get_term_by('slug','sin-categorizar','product_cat');
$uncat_moved=0;
if($uncat instanceof WP_Term){
    $uncat_ids=get_posts(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$uncat->term_id),'include_children'=>false))
    ));
    foreach($uncat_ids as $id){
        $id=(int)$id;
        if(1!==(int)get_post_meta($id,'_emdo_supplier_id',true)) continue;
        $terms=wp_get_post_terms($id,'product_cat',array('fields'=>'ids'));
        $terms=is_wp_error($terms)?array():array_map('intval',$terms);
        $terms=array_values(array_filter($terms,fn($tid)=>$tid!==(int)$uncat->term_id));
        $terms[]=(int)$carnes_shop->term_id;
        $set=wp_set_object_terms($id,array_values(array_unique($terms)),'product_cat',false);
        if(is_wp_error($set)) throw new Exception($set->get_error_message());
        $uncat_moved++;
    }
}

/* ---------- Bidirectional hub links for the clusters that actually sell. ---------- */
$pairs=array(
    'jamones'=>array('blog'=>'jamones-y-paletas','shop'=>'jamones-paletas','label'=>'jamones y paletas'),
    'aceites'=>array('blog'=>'aceites','shop'=>'aceites','label'=>'aceites'),
    'carnes'=>array('blog'=>'carnes','shop'=>'carnes','label'=>'carnes'),
    'hortalizas'=>array('blog'=>'hortalizas-y-verduras','shop'=>'hortalizas-verduras','label'=>'hortalizas y verduras'),
    'legumbres'=>array('blog'=>'legumbres','shop'=>'legumbres','label'=>'legumbres'),
    'embutidos'=>array('blog'=>'embutidos-y-curados','shop'=>'embutidos-y-curados','label'=>'embutidos y curados'),
    'conservas'=>array('blog'=>'conservas','shop'=>'conservas','label'=>'conservas'),
    'packs'=>array('blog'=>'packs-y-lotes','shop'=>'packs-y-lotes','label'=>'packs y lotes'),
    'foie'=>array('blog'=>'foie-pates-untables','shop'=>'foie-pates-untables','label'=>'foie, patés y untables'),
);
$paired=array();
foreach($pairs as $key=>$cfg){
    $bt=get_term_by('slug',$cfg['blog'],'category');
    $st=get_term_by('slug',$cfg['shop'],'product_cat');
    if(!$bt instanceof WP_Term || !$st instanceof WP_Term) continue;
    if(mdo_global_final_published_products_20261001($st)<=0 || count(mdo_global_final_direct_posts_20261001($bt))<=0) continue;

    $burl=mdo_global_final_term_url_20261001($bt);
    $surl=mdo_global_final_term_url_20261001($st);

    $bdesc=(string)$bt->description;
    if(false===strpos($bdesc,$surl)){
        $bdesc.='<p>Si quieres pasar de las guías a la compra, consulta la selección disponible de <a href="' . esc_url($surl) . '">' . esc_html($cfg['label']) . '</a>.</p>';
        $res=wp_update_term((int)$bt->term_id,'category',array('description'=>$bdesc));
        if(is_wp_error($res)) throw new Exception($res->get_error_message());
    }
    $sdesc=(string)$st->description;
    if(false===strpos($sdesc,$burl)){
        $sdesc.='<p>Para comparar formatos, conservación, uso y criterios de elección, consulta también nuestras <a href="' . esc_url($burl) . '">guías sobre ' . esc_html($cfg['label']) . '</a>.</p>';
        $res=wp_update_term((int)$st->term_id,'product_cat',array('description'=>$sdesc));
        if(is_wp_error($res)) throw new Exception($res->get_error_message());
    }
    $paired[]=$key;
}

/* ---------- Editorial cleanup: remove only clearly generic generator boilerplate. ---------- */
$boilerplate=array(
    'Esta guía está planteada para resolver la intención de búsqueda completa, no solo para dar una definición rápida. Veremos qué significa cada término, qué cambia realmente en el queso, cómo leer una etiqueta y cómo utilizar esa información al comprar, conservar o servir.',
    'También merece la pena separar tres preguntas que a menudo se mezclan: si el producto es seguro, si está bien elaborado y si encaja con nuestro gusto o nuestra receta. La seguridad depende de controles y de la integridad del producto; la calidad incluye materia prima y proceso; y la preferencia personal depende del uso. Separar esas preguntas evita conclusiones precipitadas y permite comprar con criterios más claros.',
    'No necesariamente. En alimentos transformados hay variaciones normales ligadas a la materia prima, al proceso y al almacenamiento. Lo importante es distinguir esas variaciones de señales claras de alteración y seguir las indicaciones del fabricante.',
    'No, pero aporta información esencial: ingredientes, origen cuando se declara, peso, responsable del producto, fechas e instrucciones de conservación. Es la primera referencia para interpretar correctamente una conserva, un embutido, una legumbre o un aceite.',
    'No. El precio puede reflejar origen, rendimiento, formato, selección, costes de elaboración o distribución. Para comparar hay que hacerlo entre productos equivalentes y valorar también rendimiento real y uso.',
    'Si hay pérdida de cierre, hinchado, fugas, olor claramente alterado, moho no esperado o cualquier indicio incompatible con el producto, no conviene probarlo para decidir. En productos comerciales puede conservarse el lote y contactar con vendedor o elaborador.',
    'Busca una descripción transparente, trazabilidad suficiente y un formato coherente con tu consumo. Después valora características sensoriales y culinarias. La mejor opción no es una categoría abstracta, sino la que combina buena elaboración con el uso que realmente vas a darle.',
);
$cleanup_categories=array('jamones-y-paletas','aceites','carnes','hortalizas-y-verduras','legumbres','embutidos-y-curados','conservas','packs-y-lotes','quesos');
$cleanup_ids=array();
foreach($cleanup_categories as $slug){
    $term=get_term_by('slug',$slug,'category');
    if(!$term instanceof WP_Term) continue;
    foreach(mdo_global_final_direct_posts_20261001($term) as $post){
        if($post instanceof WP_Post) $cleanup_ids[(int)$post->ID]=true;
    }
}
$boilerplate_posts_changed=0;
foreach(array_keys($cleanup_ids) as $id){
    $content=(string)get_post_field('post_content',$id);
    $clean=mdo_global_final_remove_boilerplate_20261001($content,$boilerplate);
    if($clean!==$content){
        if(''===(string)get_post_meta($id,'_emdo_pre_global_editorial_cleanup_20261001',true)){
            update_post_meta($id,'_emdo_pre_global_editorial_cleanup_20261001',$content);
        }
        $res=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$clean)),true);
        if(is_wp_error($res)) throw new Exception($res->get_error_message());
        $boilerplate_posts_changed++;
    }
}

/* Unique expansions for the few articles that were materially shorter. */
$expansions=array(
    13853=>'<h2>Cómo utilizar la DOP Guijuelo al comparar una compra</h2><p>La denominación ayuda a identificar un marco geográfico y de control, pero al comparar dos piezas conviene leerla junto con la denominación de venta, el porcentaje racial cuando corresponda, la alimentación declarada y el formato. Una pieza entera, una deshuesada y un loncheado pueden pertenecer al mismo entorno de elaboración y, aun así, ofrecer experiencias de compra distintas por rendimiento, conservación y momento de consumo.</p><p>Para decidir con criterio, separa por tanto la pregunta de origen de la pregunta de categoría. Después revisa productor, peso, curación indicada, presentación y condiciones de conservación. Esa lectura conjunta es más útil que elegir únicamente por el nombre de la zona y permite entender qué parte del precio procede de la materia prima, qué parte del proceso y qué parte del formato final.</p>',
    13854=>'<h2>Qué conviene comparar en un jamón amparado por Jabugo</h2><p>Cuando una pieza utiliza una denominación geográfica conocida, la comparación sigue necesitando contexto. La información más útil para el comprador está en la combinación entre certificación, categoría del ibérico, porcentaje racial cuando figure, formato y productor. Dos referencias pueden compartir territorio y no estar pensadas para el mismo presupuesto, rendimiento ni ocasión de consumo.</p><p>También merece la pena distinguir entre pieza completa, deshuesado y loncheado. El formato cambia la comodidad, la velocidad de consumo y la manera de conservar el jamón una vez abierto. Si la compra es para una casa con poco consumo, un formato más pequeño puede resultar más razonable; para una celebración o un consumo continuado, el rendimiento de una pieza adquiere más peso en la decisión.</p>',
    13855=>'<h2>Cómo leer Dehesa de Extremadura junto con la categoría del ibérico</h2><p>Una DOP y la categoría comercial del ibérico responden a preguntas diferentes. La primera sitúa el producto dentro de un territorio y un sistema de control; la segunda aporta información sobre raza y alimentación. Leer ambas capas por separado evita asumir que una sola palabra resume la calidad, el sabor o el precio de todas las piezas.</p><p>En la práctica, compara productos equivalentes: mismo tipo de pieza, formato parecido y una categoría legal comparable. A partir de ahí puedes valorar peso, tiempo de consumo previsto, productor y presentación. Este método es especialmente útil al comprar online, donde una fotografía atractiva no sustituye la información del producto y donde el rendimiento real de una pieza puede importar tanto como el precio de compra.</p>',
    13857=>'<h2>Por qué el clima no actúa solo durante la curación</h2><p>Temperatura, humedad y ventilación influyen en la velocidad a la que una pieza pierde agua, pero el resultado no depende de un único valor ambiental. El grosor de la pieza, su contenido de grasa, el salado previo y la gestión del secadero condicionan cómo responde el jamón a cada fase. Por eso dos piezas expuestas al mismo entorno pueden evolucionar de forma diferente.</p><p>La clave está en que el proceso sea gradual. Un secado demasiado rápido en superficie puede dificultar una evolución uniforme del interior, mientras que una humedad excesiva exige un control cuidadoso. En instalaciones actuales, la experiencia del maestro jamonero se combina con mediciones y ajustes de ventilación. Hablar de “clima” tiene sentido, pero siempre como parte de un sistema de curación más amplio.</p>',
    13859=>'<h2>Qué cambia cuando el jamón pasa del secadero a la bodega</h2><p>El secadero y la bodega no son simplemente dos habitaciones con nombres tradicionales. Representan etapas en las que cambian el ritmo de pérdida de humedad y la evolución interna de la pieza. Conforme avanza la curación, la grasa se vuelve más blanda y aromática y la textura de la carne se transforma de manera progresiva.</p><p>El control del proceso busca equilibrio, no velocidad. Una pieza necesita tiempo suficiente para que la evolución interior acompañe al secado exterior. De ahí que el peso inicial, la grasa, la temperatura, la humedad y la ventilación se valoren conjuntamente. Para el consumidor, entender estas fases ayuda a interpretar por qué el tiempo de curación es un dato útil pero no una garantía aislada: importa cómo se ha gestionado ese tiempo.</p>',
    13861=>'<h2>Cómo usar una DOP de aceite al elegir una botella</h2><p>La DOP aporta información sobre origen y cumplimiento de un pliego, pero no sustituye otros datos de compra. Para comparar AOVE conviene mirar también la variedad o coupage, el tamaño del envase, la campaña cuando se indique y el ritmo al que vas a consumirlo. Un formato grande puede ser económico por litro y, sin embargo, no ser la mejor elección si permanecerá abierto muchos meses.</p><p>El perfil sensorial tampoco debe deducirse únicamente del nombre geográfico. Variedad, momento de recolección, estado de la aceituna, elaboración y conservación influyen en el resultado. La denominación es una pieza del contexto; la elección final debería combinarla con el uso previsto —aliño, cocina, fritura o consumo en crudo— y con la intensidad que prefieras.</p>',
    13862=>'<h2>Cómo elegir un AOVE de Priego de Córdoba según el uso</h2><p>Cuando comparas aceites de una misma denominación, no busques una única “nota típica” que deba aparecer en todas las botellas. La variedad empleada, el momento de cosecha y la mezcla concreta pueden desplazar el equilibrio entre frutado, amargor y picor. Esa variación es útil porque permite elegir un aceite más delicado para unos platos y otro más expresivo para otros.</p><p>Lee la etiqueta junto con la ficha del productor y piensa en el consumo real. Si el aceite se va a usar principalmente en crudo, el perfil aromático cobra especial importancia; si se utilizará a diario en cocina, también pesan el formato y la rapidez de rotación. La mejor compra no es necesariamente la botella con más términos técnicos, sino la que ofrece información clara y encaja con tu forma de consumir AOVE.</p>',
    13863=>'<h2>Baena en la mesa: cómo pasar del origen a una elección concreta</h2><p>El origen ayuda a situar el aceite, pero dentro de una misma zona puede haber perfiles distintos. La composición varietal, la madurez del fruto y las decisiones de la almazara cambian la intensidad y el equilibrio del AOVE. Por eso conviene leer la botella concreta en lugar de comprar únicamente por la reputación general de la denominación.</p><p>Para una comparación práctica, revisa variedad o coupage, formato, fecha o campaña cuando estén disponibles y recomendaciones del elaborador. Después piensa en el plato: un aceite con personalidad puede funcionar muy bien sobre pan, tomate o verduras, mientras que un perfil más equilibrado puede ser más versátil para cocinar. Esa relación entre botella y uso es más útil que intentar ordenar todas las DOP en una escala única de calidad.</p>',
    13864=>'<h2>Picual y Royal: por qué la variedad cambia la experiencia</h2><p>En una zona donde conviven variedades diferentes, el nombre de la DOP no basta para anticipar el perfil. La genética de la aceituna condiciona aromas y estructura, pero también intervienen cosecha, madurez y elaboración. Comparar una botella elaborada principalmente con una variedad y otra con una composición distinta es una forma sencilla de entender hasta qué punto el cultivar modifica la experiencia.</p><p>Al comprar, busca la variedad declarada, la campaña si aparece y un envase que puedas consumir en un tiempo razonable. Después conserva el aceite protegido de luz y calor. De poco sirve elegir un AOVE interesante si permanece abierto demasiado tiempo o junto a una fuente de calor: la conservación posterior forma parte de la calidad que finalmente llega al plato.</p>',
    13865=>'<h2>Cómo interpretar un AOVE de Sierra Mágina sin reducirlo a una sola nota</h2><p>Las descripciones de una zona suelen resumir tendencias, pero una botella concreta responde a muchas variables. Parcela, momento de recolección, estado del fruto y trabajo de almazara pueden modificar intensidad y equilibrio. Por eso es preferible usar el origen como contexto y no como una promesa de que todos los aceites sabrán exactamente igual.</p><p>Si comparas varias referencias, observa variedad, formato y fecha de consumo prevista. Para uso diario puede pesar la versatilidad; para terminaciones en crudo quizá busques un perfil más marcado. También importa el envase: una botella pequeña se renueva antes, mientras que un formato grande exige buenas condiciones de almacenamiento y un consumo suficientemente rápido para mantener el perfil sensorial.</p>',
    13866=>'<h2>Cornicabra y origen: qué información ayuda de verdad al comprador</h2><p>La variedad aporta una base para entender un aceite, pero el resultado final no depende solo de ella. El punto de madurez, el tiempo hasta la molturación, la extracción y el almacenamiento condicionan el carácter de cada lote. Esa es la razón por la que dos AOVE elaborados con la misma aceituna pueden mostrar intensidades diferentes.</p><p>Para elegir una botella, combina la información de origen con el uso que vas a darle. Si buscas un aceite para terminar platos, presta atención al perfil descrito por el productor; si será un aceite de cocina habitual, añade al análisis el tamaño de envase y la rotación. En ambos casos, mantenerlo lejos de luz, calor y oxígeno ayuda a conservar mejor lo que se ha conseguido en la almazara.</p>',
    13867=>'<h2>Cómo separar el efecto del territorio del trabajo de la almazara</h2><p>Clima, suelo y altitud condicionan el desarrollo del olivo y de la aceituna, pero no actúan de manera aislada. La variedad puede responder de forma distinta a cada entorno y, después de la cosecha, el momento de recolección y la elaboración pueden acentuar o suavizar determinados rasgos. El territorio explica parte de la materia prima; la almazara decide cómo transformarla en aceite.</p><p>Por eso las comparaciones más útiles se hacen entre aceites bien identificados. Si conoces variedad, zona, campaña y perfil de cata puedes relacionar esos datos con el resultado sin atribuir todo a una sola causa. Esta lectura evita dos extremos: pensar que el origen no importa y creer que el origen determina por sí solo el sabor de cada botella.</p>',
    13694=>'<h2>Cómo aprovechar mejor el hierro de las legumbres en una comida</h2><p>El hierro de las legumbres es hierro no hemo y su aprovechamiento depende del conjunto de la comida. Combinar lentejas, garbanzos o alubias con alimentos ricos en vitamina C —por ejemplo pimiento, tomate, cítricos o una fruta de postre— puede favorecer su absorción. En cambio, comparar únicamente miligramos por 100 g no cuenta toda la historia.</p><p>También hay que distinguir peso seco y peso cocido. Al hidratarse, la legumbre incorpora agua y la concentración por 100 g cambia, aunque los nutrientes de la ración no desaparezcan de la misma forma. Para una elección práctica, piensa en variedad, ración y frecuencia de consumo y utiliza las tablas nutricionales como referencia comparable, no como una competición entre alimentos.</p>',
    14022=>'<h2>Cómo elegir entre los quesos murcianos según el momento de consumo</h2><p>Dos quesos de una misma región pueden responder a usos distintos. La textura, la humedad, la maduración y el tratamiento de la corteza condicionan cómo se comportan solos, en una tabla o dentro de una receta. Por eso conviene mirar la pieza concreta y no asumir que el origen regional convierte todos los quesos en equivalentes.</p><p>Para una tabla, combina intensidades y texturas y sirve cantidades que permitan probar más de un queso. Si el queso se compra para cocinar, valora fundido, humedad y salinidad. En cualquier caso, revisa tipo de leche, maduración, conservación y fecha de consumo preferente. Esa información práctica ayuda más que elegir únicamente por color exterior o por una descripción genérica de “suave” o “intenso”.</p>',
    13718=>'<h2>Hierro vegetal: por qué la ración y la combinación importan</h2><p>Las cifras por 100 g sirven para comparar verduras en una tabla, pero una ración habitual puede ser mayor o menor y el hierro vegetal no se absorbe igual que el hierro hemo de los alimentos animales. Por eso una clasificación numérica debe leerse con contexto.</p><p>En una comida real, combinar hortalizas con vitamina C puede favorecer el aprovechamiento del hierro no hemo. Pimientos, tomate, brócoli, cítricos u otras fuentes de vitamina C pueden formar parte del mismo menú. La variedad global de la dieta es más importante que perseguir una única verdura “ganadora”, y la cocción también puede modificar peso, agua y concentración por 100 g.</p>',
);
$expansions_added=0;
foreach($expansions as $id=>$html){
    $id=(int)$id;
    $content=(string)get_post_field('post_content',$id);
    if(''===$content || false!==strpos($content,'mdo-editorial-depth-20261001:'.$id)) continue;
    if(''===(string)get_post_meta($id,'_emdo_pre_global_depth_20261001',true)){
        update_post_meta($id,'_emdo_pre_global_depth_20261001',$content);
    }
    $new=rtrim($content)."\n<!-- mdo-editorial-depth-20261001:".$id." -->\n".$html."\n";
    $res=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$new)),true);
    if(is_wp_error($res)) throw new Exception($res->get_error_message());
    $expansions_added++;
}

/* ---------- Final quality and architecture checks. ---------- */
$quality_slugs=array('jamones-y-paletas','aceites','carnes','hortalizas-y-verduras','legumbres','embutidos-y-curados','conservas','packs-y-lotes','quesos','pato');
$quality=array();
$global_min=PHP_INT_MAX;
$boilerplate_hits=0;
$target_norms=array_map('mdo_global_final_norm_20261001',$boilerplate);
foreach($quality_slugs as $slug){
    $term=get_term_by('slug',$slug,'category');
    if(!$term instanceof WP_Term) continue;
    $posts=mdo_global_final_direct_posts_20261001($term);
    $min=PHP_INT_MAX;$max=0;$sum=0;
    foreach($posts as $post){
        if(!$post instanceof WP_Post) continue;
        $content=(string)get_post_field('post_content',$post->ID);
        $words=mdo_global_final_words_20261001($content);
        $min=min($min,$words);$max=max($max,$words);$sum+=$words;
        $norm=mdo_global_final_norm_20261001($content);
        foreach($target_norms as $phrase){ if(''!==$phrase) $boilerplate_hits+=substr_count($norm,$phrase); }
    }
    $quality[$slug]=array(
        'count'=>count($posts),'min_words'=>$min===PHP_INT_MAX?0:$min,
        'avg_words'=>$posts?round($sum/count($posts),1):0,'max_words'=>$max
    );
    if($posts) $global_min=min($global_min,$min);
}
if($global_min<650) throw new Exception('Editorial cluster minimum fell below 650 words: '.$global_min);
if($boilerplate_hits>0) throw new Exception('Generator boilerplate remains: '.$boilerplate_hits);

$wagyu_blog=get_term((int)$wagyu_blog->term_id,'category');
$wagyu_shop=get_term((int)$wagyu_shop->term_id,'product_cat');
if(!$wagyu_blog instanceof WP_Term || (int)$wagyu_blog->parent!==(int)$carnes_blog->term_id) throw new Exception('Wagyu blog hierarchy failed.');
if(!$wagyu_shop instanceof WP_Term || (int)$wagyu_shop->parent!==(int)$carnes_shop->term_id) throw new Exception('Wagyu shop hierarchy failed.');
$wagyu_category_count=count(mdo_global_final_direct_posts_20261001($wagyu_blog));
if(65!==$wagyu_category_count) throw new Exception('Wagyu category expected 65 posts, got '.$wagyu_category_count);

$uncat_after=0;
if($uncat instanceof WP_Term){
    $uncat_after=count(get_posts(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'tax_query'=>array(array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>array((int)$uncat->term_id),'include_children'=>false))
    )));
}

flush_rewrite_rules(false);
if(function_exists('wp_cache_flush')) wp_cache_flush();

echo wp_json_encode(array(
    'batch'=>'20261001-global-seo-cluster-architecture',
    'paired_clusters'=>$paired,
    'wagyu'=>array(
        'blog_term_id'=>(int)$wagyu_blog->term_id,
        'blog_parent'=>(int)$wagyu_blog->parent,
        'posts'=>$wagyu_category_count,
        'shop_term_id'=>(int)$wagyu_shop->term_id,
        'shop_parent'=>(int)$wagyu_shop->parent,
        'published_products'=>mdo_global_final_published_products_20261001($wagyu_shop),
        'generic_carnes_blocks_removed'=>$wagyu_generic_blocks_removed,
    ),
    'foie'=>array(
        'blog_term_id'=>(int)$foie_blog->term_id,
        'posts_assigned'=>$foie_assigned,
        'shop_products'=>mdo_global_final_published_products_20261001($foie_shop),
        'blog_url'=>mdo_global_final_term_url_20261001($foie_blog),
        'shop_url'=>mdo_global_final_term_url_20261001($foie_shop),
    ),
    'pescados'=>array(
        'matching_posts'=>count($fish_matches),
        'category_created'=>$fish_blog instanceof WP_Term,
        'posts_assigned'=>$fish_assigned,
    ),
    'uncategorized_moved'=>$uncat_moved,
    'uncategorized_after'=>$uncat_after,
    'boilerplate_posts_changed'=>$boilerplate_posts_changed,
    'expansions_added'=>$expansions_added,
    'boilerplate_hits_after'=>$boilerplate_hits,
    'quality'=>$quality,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
