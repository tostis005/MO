<?php
/** La Huerta de Ana Mary product copy batch 07 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha7_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha7_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha7_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha7_pair_html($es_html,$en_html,$label){
    $es=mo_ha7_segments($es_html); $en=mo_ha7_segments($en_html);
    if(count($es)!==count($en)) mo_ha7_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha7_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha7_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha7_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha7_fail("TranslatePress insert failed: {$original}");
    }
}

$producer_es=<<<'HTML'
<h2>Sobre La Huerta de Ana Mary</h2>
<p>En Fresno de la Vega, en la provincia de León, la agricultura y el cultivo de hortalizas forman parte de una tradición ligada a la Vega del Esla desde hace generaciones. La Huerta de Ana Mary continúa esa actividad familiar, con más de tres generaciones vinculadas al trabajo en el campo.</p>
<p>Las verduras y hortalizas se cultivan en parcelas de Fresno de la Vega, aprovechando la experiencia agrícola de la zona y adaptando el trabajo a los ciclos y temporadas de cada cultivo.</p>
<p>La recolección se organiza en función de los pedidos para reducir el tiempo de almacenamiento y acortar al máximo el recorrido entre la huerta y el consumidor, favoreciendo que el producto llegue fresco y en buenas condiciones.</p>
HTML;
$producer_en=<<<'HTML'
<h2>About La Huerta de Ana Mary</h2>
<p>In Fresno de la Vega, in the province of León, farming and vegetable growing are part of a tradition connected to the Vega del Esla that goes back generations. La Huerta de Ana Mary continues this family activity, with more than three generations linked to working the land.</p>
<p>The vegetables are grown on plots in Fresno de la Vega, drawing on the farming experience of the area and adapting the work to the cycles and seasons of each crop.</p>
<p>Harvesting is organised around orders to reduce storage time and keep the journey from the fields to the customer as short as possible, helping the produce arrive fresh and in good condition.</p>
HTML;

$products=[
'pepper_jam_box'=>[
 'id'=>12793,'title'=>'12 Tarros de confitura de pimiento 250ml','title_en'=>'12 Jars of Pepper Jam 250 ml','slug'=>'12-tarros-de-confitura-de-pimiento-250ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de confitura artesana de pimiento de 250 ml cada uno. Elaborada con pimiento rojo y azúcar, sin conservantes ni colorantes, para acompañar quesos, carnes, tostas y aperitivos.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of artisan pepper jam, 250 ml each. Made with red pepper and sugar, without preservatives or colourings, for pairing with cheese, meat, toast and appetisers.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de confitura artesana de pimiento</h2>
<p>Esta caja incluye 12 tarros de cristal de 250 ml de confitura de pimiento. Se elabora con pimiento rojo y azúcar, sin conservantes ni colorantes.</p>
<p>La confitura cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede combinarse con quesos, carnes y patés, utilizarse en tostas y canapés o incorporarse a aperitivos en los que interese un contraste dulce con el sabor del pimiento.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of artisan pepper jam</h2>
<p>This box contains 12 glass jars of pepper jam, 250 ml each. It is made with red pepper and sugar, without preservatives or colourings.</p>
<p>The jam carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Pair it with cheese, meat and pâtés, use it on toast or canapés, or add it to appetisers where a sweet contrast with the flavour of pepper works well.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye la caja?</h3><p>Incluye 12 tarros de 250 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Pimiento rojo y azúcar.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 250 ml each.</p>
<h3>What are the ingredients?</h3><p>Red pepper and sugar.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'fritada'=>[
 'id'=>12797,'title'=>'Fritada de tomate artesana 314ml','title_en'=>'Artisan Tomato Fritada 314 ml','slug'=>'fritada-de-tomate-artesana-314ml',
 'es_excerpt'=>'<p>Fritada de tomate artesana en tarro de cristal de 314 ml. Elaborada con tomate, pimiento rojo, pimiento verde, cebolla, aceite de oliva y sal, sin conservantes ni colorantes.</p>',
 'en_excerpt'=>'<p>Artisan tomato fritada in a 314 ml glass jar. Made with tomato, red pepper, green pepper, onion, olive oil and salt, without preservatives or colourings.</p>',
 'es_content'=><<<'HTML'
<h2>Fritada de tomate lista para servir o cocinar</h2>
<p>Esta fritada artesana se presenta en un tarro de cristal de 314 ml. Sus ingredientes son tomate, pimiento rojo, pimiento verde, cebolla, aceite de oliva y sal.</p>
<p>Se elabora sin conservantes ni colorantes y permite tener preparada una base vegetal para distintos platos sin tener que cocinarla desde cero.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede servirse como acompañamiento, utilizarse como base para huevos, carnes o pescados, incorporarse a arroces y guisos o calentarse directamente para completar una comida sencilla.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Tomato fritada ready to serve or cook with</h2>
<p>This artisan fritada comes in a 314 ml glass jar. Its ingredients are tomato, red pepper, green pepper, onion, olive oil and salt.</p>
<p>It is made without preservatives or colourings and provides a ready-made vegetable base for different dishes without having to cook it from scratch.</p>
<h2>How to use it</h2>
<p>Serve it as a side dish, use it as a base for eggs, meat or fish, add it to rice dishes and stews, or simply heat it to complete an easy meal.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contiene?</h3><p>Tomate, pimiento rojo, pimiento verde, cebolla, aceite de oliva y sal.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
<h3>¿Qué formato tiene?</h3><p>Se presenta en un tarro de cristal de 314 ml.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3><p>Tomato, red pepper, green pepper, onion, olive oil and salt.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
<h3>What is the format?</h3><p>It comes in a 314 ml glass jar.</p>
HTML,
],
'padron'=>[
 'id'=>12799,'title'=>'Kg pimientos de Padrón','title_en'=>'Padrón Peppers by the Kilogram','slug'=>'kg-pimientos-de-padron',
 'es_excerpt'=>'<p>Pimientos de Padrón frescos vendidos por kilo. Pequeños, verdes y de forma cónica, se preparan habitualmente fritos o a la plancha; como es característico de este tipo de pimiento, algunos pueden resultar picantes y otros no.</p>',
 'en_excerpt'=>'<p>Fresh Padrón peppers sold by the kilogram. Small, green and conical, they are usually pan-fried or cooked on a griddle; as is characteristic of this type of pepper, some may be hot while others are mild.</p>',
 'es_content'=><<<'HTML'
<h2>Pimientos de Padrón para freír o preparar a la plancha</h2>
<p>Estos pimientos se caracterizan por su pequeño tamaño, su color verde y su forma cónica. Se venden por kilo y son una opción clásica para preparar rápidamente en sartén o plancha.</p>
<p>Una de sus particularidades es que el grado de picante puede variar entre piezas: algunos pimientos pueden picar mientras que otros resultan suaves.</p>
<h2>Cómo prepararlos</h2>
<p>Una de las formas más habituales de servirlos es freírlos o cocinarlos a la plancha hasta que la piel se ablande y empiece a dorarse. Pueden servirse como aperitivo, guarnición o acompañamiento de otros platos.</p>
<p>Consérvalos refrigerados y evita el exceso de humedad durante el almacenamiento.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Padrón peppers for pan-frying or griddle cooking</h2>
<p>These peppers are characterised by their small size, green colour and conical shape. They are sold by the kilogram and are a classic choice for quick pan or griddle cooking.</p>
<p>One of their distinctive features is that heat can vary from pepper to pepper: some may be hot while others are mild.</p>
<h2>How to cook them</h2>
<p>A common way to serve them is to pan-fry or griddle them until the skin softens and begins to brown. They can be served as an appetiser, side dish or accompaniment to other foods.</p>
<p>Keep them refrigerated and avoid excess moisture during storage.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Se venden por unidad?</h3><p>No. Esta referencia se vende por kilo.</p>
<h3>¿Todos los pimientos de Padrón pican?</h3><p>No. El grado de picante puede variar y algunas piezas pueden resultar suaves.</p>
<h3>¿Cómo se preparan normalmente?</h3><p>Fritos o a la plancha, como aperitivo, guarnición o acompañamiento.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Are they sold individually?</h3><p>No. This product is sold by the kilogram.</p>
<h3>Are all Padrón peppers hot?</h3><p>No. The level of heat varies and some peppers may be mild.</p>
<h3>How are they usually cooked?</h3><p>Pan-fried or cooked on a griddle, as an appetiser, side dish or accompaniment.</p>
HTML,
],
'hot_roasted'=>[
 'id'=>12802,'title'=>'Pimiento asado picante 314 ml, artesano','title_en'=>'Artisan Hot Roasted Peppers 314 ml','slug'=>'pimiento-asado-picante-314-ml-artesano',
 'es_excerpt'=>'<p>Pimiento asado picante de elaboración artesanal en tarro de cristal de 314 ml. Asado con leña y pelado a mano, con pimiento asado y zumo de limón, sin conservantes ni colorantes.</p>',
 'en_excerpt'=>'<p>Traditionally prepared hot roasted peppers in a 314 ml glass jar. Roasted over wood and peeled by hand, with roasted pepper and lemon juice, without preservatives or colourings.</p>',
 'es_content'=><<<'HTML'
<h2>Pimiento picante asado con leña y pelado a mano</h2>
<p>Esta conserva se presenta en un tarro de cristal de 314 ml. Los pimientos se asan con leña y se pelan a mano antes de envasarse.</p>
<p>Los ingredientes indicados son pimientos asados y zumo de limón. No se añaden conservantes ni colorantes y el producto cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse como aperitivo o guarnición, incorporarse a tostas y bocadillos o acompañar carnes, pescados, huevos y otros platos en los que se quiera añadir un punto picante.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Hot peppers roasted over wood and peeled by hand</h2>
<p>This preserve comes in a 314 ml glass jar. The peppers are roasted over wood and peeled by hand before being packed.</p>
<p>The listed ingredients are roasted peppers and lemon juice. No preservatives or colourings are added, and the product carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Serve it as an appetiser or side dish, add it to toast and sandwiches, or pair it with meat, fish, eggs and other dishes where a hot pepper note works well.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo se elaboran los pimientos?</h3><p>Se asan con leña y se pelan a mano antes del envasado.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Pimientos asados y zumo de limón.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How are the peppers prepared?</h3><p>They are roasted over wood and peeled by hand before being packed.</p>
<h3>What are the ingredients?</h3><p>Roasted peppers and lemon juice.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'cucumber'=>[
 'id'=>12806,'title'=>'Pepino','title_en'=>'Cucumber','slug'=>'pepino',
 'es_excerpt'=>'<p>Pepino fresco de temporada de La Huerta de Ana Mary, vendido por kilo. Una hortaliza muy práctica para ensaladas, gazpachos, cremas frías y preparaciones frescas.</p>',
 'en_excerpt'=>'<p>Fresh seasonal cucumber from La Huerta de Ana Mary, sold by the kilogram. A practical vegetable for salads, gazpacho, chilled soups and other fresh dishes.</p>',
 'es_content'=><<<'HTML'
<h2>Pepino fresco de temporada</h2>
<p>El pepino es una hortaliza especialmente útil para preparaciones frescas y se vende por kilo. Su textura crujiente permite incorporarlo fácilmente a platos que se sirven en frío.</p>
<p>Esta referencia corresponde a producto fresco de temporada de La Huerta de Ana Mary.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede utilizarse en ensaladas, gazpachos, cremas frías, salsas y otros platos frescos, cortado en rodajas, dados o tiras según la preparación.</p>
<p>Consérvalo en el frigorífico. No es recomendable congelarlo en crudo, ya que al descongelarse pierde firmeza y su textura se ablanda.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Fresh seasonal cucumber</h2>
<p>Cucumber is particularly useful for fresh dishes and is sold by the kilogram. Its crisp texture makes it easy to add to foods served cold.</p>
<p>This product is fresh seasonal produce from La Huerta de Ana Mary.</p>
<h2>How to use it</h2>
<p>Use it in salads, gazpacho, chilled soups, sauces and other fresh dishes, sliced, diced or cut into strips depending on the preparation.</p>
<p>Keep it refrigerated. Freezing raw cucumber is not recommended because it loses firmness and becomes soft after thawing.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo se vende?</h3><p>Esta referencia se vende por kilo.</p>
<h3>¿Cómo se conserva?</h3><p>En el frigorífico, evitando periodos innecesariamente largos de almacenamiento.</p>
<h3>¿Se puede congelar?</h3><p>No es lo más recomendable en crudo, porque pierde firmeza y se ablanda al descongelarse.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How is it sold?</h3><p>This product is sold by the kilogram.</p>
<h3>How should it be stored?</h3><p>Keep it refrigerated and avoid unnecessarily long storage.</p>
<h3>Can it be frozen?</h3><p>Freezing it raw is not recommended because it loses firmness and becomes soft after thawing.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha7_fail("TranslatePress table missing: {$table}");

$backup=[]; $pairs=[];
foreach($products as $key=>$p){
    $post=get_post($p['id']);
    if(!$post || $post->post_type!=='product') mo_ha7_fail("Missing product {$p['id']}");
    if($post->post_status!=='publish') mo_ha7_fail("Not published {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha7_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha7_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha7_vendor($post),'Huerta de Ana Mary')===false) mo_ha7_fail("Vendor mismatch {$p['id']}: ".mo_ha7_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha7_fail("Not instock {$p['id']}");
    $backup[$p['id']]=[
      'post_excerpt'=>$post->post_excerpt,'post_content'=>$post->post_content,
      '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
      '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
    ];
    $full_es=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];
    $full_en=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];
    $products[$key]['full_es']=$full_es; $products[$key]['full_en']=$full_en;
    $pairs[$p['title']]=$p['title_en'];
    $pairs=array_merge($pairs,mo_ha7_pair_html($p['es_excerpt'],$p['en_excerpt'],$key.' excerpt'));
    $pairs=array_merge($pairs,mo_ha7_pair_html($full_es,$full_en,$key.' content'));
}
$backup_key='mo_huerta_anamary_batch07_backup_20260831';
if(get_option($backup_key,false)===false){ add_option($backup_key,$backup,'','no'); echo "BACKUP created {$backup_key}\n"; }
else { echo "BACKUP exists {$backup_key}\n"; }

foreach($products as $p){
    $r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);
    if(is_wp_error($r)) mo_ha7_fail("Update failed {$p['id']}: ".$r->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);
}
foreach($pairs as $original=>$translated){ mo_ha7_trp_upsert($table,$original,$translated); }

foreach($products as $p){
    $post=get_post($p['id']);
    $en_short=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);
    $en_long=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($post->post_content,'Preguntas frecuentes')===false) mo_ha7_fail("Spanish verification failed {$p['id']}");
    if(strpos($en_long,'About La Huerta de Ana Mary')===false || strpos($en_long,'Frequently asked questions')===false) mo_ha7_fail("English verification failed {$p['id']}");
    if(trim(wp_strip_all_tags($en_short))==='') mo_ha7_fail("English excerpt empty {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}
echo 'DONE huerta_batch07_products='.count($products).' translations='.count($pairs)."\n";
