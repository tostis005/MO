<?php
/** La Huerta de Ana Mary product copy batch 08 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha8_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha8_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha8_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha8_pair_html($es_html,$en_html,$label){
    $es=mo_ha8_segments($es_html); $en=mo_ha8_segments($en_html);
    if(count($es)!==count($en)) mo_ha8_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha8_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha8_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha8_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha8_fail("TranslatePress insert failed: {$original}");
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
'fritada_box'=>[
 'id'=>12809,'title'=>'12 Tarros de fritada de tomate artesana 314ml','title_en'=>'12 Jars of Artisan Tomato Fritada 314 ml','slug'=>'12-tarros-de-fritada-de-tomate-artesana-314ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de fritada de tomate artesana de 314 ml cada uno. Elaborada con tomate, pimiento rojo, pimiento verde, cebolla, aceite de oliva y sal, sin conservantes ni colorantes.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of artisan tomato fritada, 314 ml each. Made with tomato, red pepper, green pepper, onion, olive oil and salt, without preservatives or colourings.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de fritada de tomate artesana</h2>
<p>Esta caja incluye 12 tarros de cristal de 314 ml de fritada de tomate. Se elabora de forma artesanal con tomate, pimiento rojo, pimiento verde, cebolla, aceite de oliva y sal.</p>
<p>La conserva no lleva conservantes ni colorantes y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede servirse directamente como acompañamiento o utilizarse como base para arroces, pasta, huevos, carnes, pescados y otros platos en los que interese incorporar una mezcla de tomate y hortalizas ya preparada.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of artisan tomato fritada</h2>
<p>This box contains 12 glass jars of tomato fritada, 314 ml each. It is traditionally prepared with tomato, red pepper, green pepper, onion, olive oil and salt.</p>
<p>The preserve contains no preservatives or colourings and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Serve it directly as an accompaniment or use it as a base for rice dishes, pasta, eggs, meat, fish and other recipes where a ready-prepared mix of tomato and vegetables is useful.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye la caja?</h3><p>Incluye 12 tarros de 314 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Tomate, pimiento rojo, pimiento verde, cebolla, aceite de oliva y sal.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 314 ml each.</p>
<h3>What are the ingredients?</h3><p>Tomato, red pepper, green pepper, onion, olive oil and salt.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'sweet_sour_box'=>[
 'id'=>12813,'title'=>'12 Tarros de pimiento agridulce 720ml','title_en'=>'12 Jars of Sweet and Sour Peppers 720 ml','slug'=>'12-tarros-de-pimiento-agridulce-720ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de pimiento agridulce de 720 ml cada uno. Elaboración artesanal sin conservantes ni colorantes, con pimientos, vinagre, azúcar, agua y zumo de limón.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of sweet and sour peppers, 720 ml each. Traditionally prepared without preservatives or colourings, using peppers, vinegar, sugar, water and lemon juice.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de pimiento agridulce</h2>
<p>La caja incluye 12 tarros de cristal de 720 ml de pimiento agridulce. La elaboración combina pimientos, vinagre, azúcar, agua y zumo de limón.</p>
<p>Se trata de una conserva artesanal sin conservantes ni colorantes y con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse como aperitivo o guarnición, incorporarse a ensaladas, tostas y bocadillos o acompañar carnes, quesos y otros platos en los que funcione bien el contraste entre dulce y ácido.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of sweet and sour peppers</h2>
<p>The box contains 12 glass jars of sweet and sour peppers, 720 ml each. They are prepared with peppers, vinegar, sugar, water and lemon juice.</p>
<p>This traditionally made preserve contains no preservatives or colourings and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use them</h2>
<p>Serve them as an appetiser or side dish, add them to salads, toast and sandwiches, or pair them with meat, cheese and other dishes where a sweet-and-sour contrast works well.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye la caja?</h3><p>Incluye 12 tarros de 720 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Pimientos, vinagre, azúcar, agua y zumo de limón.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 720 ml each.</p>
<h3>What are the ingredients?</h3><p>Peppers, vinegar, sugar, water and lemon juice.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'sweet_roasted_box'=>[
 'id'=>12816,'title'=>'12 Tarros de pimientos asados dulces','title_en'=>'12 Jars of Sweet Roasted Peppers','slug'=>'12-tarros-de-pimientos-asados-dulces',
 'es_excerpt'=>'<p>Caja con 12 tarros de pimiento asado dulce de 314 ml cada uno. Los pimientos morrones se asan con leña y se pelan a mano; la conserva se elabora sin conservantes ni colorantes. Gastos de envío incluidos.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of sweet roasted peppers, 314 ml each. The red peppers are roasted over wood and peeled by hand; the preserve is made without preservatives or colourings. Shipping costs included.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de pimiento asado dulce</h2>
<p>La caja incluye 12 tarros de cristal de 314 ml de pimiento morrón dulce. Los pimientos se asan con leña y se pelan a mano antes de envasarse.</p>
<p>Los ingredientes indicados son pimientos morrones asados y zumo de limón. La conserva no lleva conservantes ni colorantes y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor. Los gastos de envío están incluidos.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse como entrante o guarnición, incorporarse a ensaladas, tostas y bocadillos o acompañar carnes, pescados, huevos y otros platos.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of sweet roasted peppers</h2>
<p>The box contains 12 glass jars of sweet red peppers, 314 ml each. The peppers are roasted over wood and peeled by hand before being packed.</p>
<p>The listed ingredients are roasted red peppers and lemon juice. The preserve contains no preservatives or colourings and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks. Shipping costs are included.</p>
<h2>How to use them</h2>
<p>Serve them as a starter or side dish, add them to salads, toast and sandwiches, or pair them with meat, fish, eggs and other dishes.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye?</h3><p>La caja incluye 12 tarros de 314 ml cada uno.</p>
<h3>¿Cómo se elaboran los pimientos?</h3><p>Se asan con leña y se pelan a mano antes de su envasado.</p>
<h3>¿Los gastos de envío están incluidos?</h3><p>Sí. La ficha actual del producto indica que los gastos de envío están incluidos.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 314 ml each.</p>
<h3>How are the peppers prepared?</h3><p>They are roasted over wood and peeled by hand before being packed.</p>
<h3>Are shipping costs included?</h3><p>Yes. The current product information states that shipping costs are included.</p>
HTML,
],
'fried_pepper_box'=>[
 'id'=>12819,'title'=>'12 Tarros de pimiento frito en aceite de oliva 314 ml','title_en'=>'12 Jars of Fried Peppers in Olive Oil 314 ml','slug'=>'12-tarros-de-pimiento-frito-en-aceite-de-oliva-314-ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de pimiento rojo frito en aceite de oliva de 314 ml cada uno. Elaboración artesanal sin conservantes ni colorantes, con pimientos rojos, aceite de oliva y zumo de limón. Gastos de envío incluidos.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of fried red peppers in olive oil, 314 ml each. Traditionally prepared without preservatives or colourings, using red peppers, olive oil and lemon juice. Shipping costs included.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de pimiento frito en aceite de oliva</h2>
<p>La caja incluye 12 tarros de cristal de 314 ml de pimiento rojo frito en aceite de oliva. Sus ingredientes son pimientos rojos, aceite de oliva y zumo de limón.</p>
<p>La conserva es de elaboración artesanal, no lleva conservantes ni colorantes y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor. Los gastos de envío están incluidos.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse directamente como guarnición o aperitivo, utilizarse en tostas y bocadillos o acompañar carnes, pescados, huevos y otros platos.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of fried peppers in olive oil</h2>
<p>The box contains 12 glass jars of fried red peppers in olive oil, 314 ml each. The ingredients are red peppers, olive oil and lemon juice.</p>
<p>The preserve is traditionally prepared without preservatives or colourings and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks. Shipping costs are included.</p>
<h2>How to use them</h2>
<p>Serve them directly as a side dish or appetiser, use them on toast or in sandwiches, or pair them with meat, fish, eggs and other dishes.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye?</h3><p>La caja incluye 12 tarros de 314 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Pimientos rojos, aceite de oliva y zumo de limón.</p>
<h3>¿Los gastos de envío están incluidos?</h3><p>Sí. La ficha actual del producto indica que los gastos de envío están incluidos.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 314 ml each.</p>
<h3>What are the ingredients?</h3><p>Red peppers, olive oil and lemon juice.</p>
<h3>Are shipping costs included?</h3><p>Yes. The current product information states that shipping costs are included.</p>
HTML,
],
'vegetable_box'=>[
 'id'=>12825,'title'=>'10 Kg de hortalizas frescas','title_en'=>'10 kg Box of Fresh Vegetables','slug'=>'10-kg-de-hortalizas-frescas',
 'es_excerpt'=>'<p>Caja de 10 kg con una selección de hortalizas frescas y de temporada de Fresno de la Vega. La composición varía según los productos disponibles en cada momento. Gastos de envío incluidos a destinos de la Península.</p>',
 'en_excerpt'=>'<p>10 kg box with a selection of fresh, seasonal vegetables from Fresno de la Vega. The contents vary according to what is available at the time. Shipping costs included to mainland Spain.</p>',
 'es_content'=><<<'HTML'
<h2>10 kg de hortalizas frescas y de temporada</h2>
<p>Esta caja reúne 10 kg de hortalizas de Fresno de la Vega seleccionadas entre los productos disponibles en cada momento. Al tratarse de una cesta de temporada, la composición puede cambiar a lo largo del año.</p>
<p>Las hortalizas se preparan en función de la disponibilidad de la huerta y del pedido. Los gastos de envío están incluidos para destinos de la Península.</p>
<h2>Cómo aprovechar la caja</h2>
<p>La variedad de la selección permite utilizar las hortalizas en ensaladas, guisos, potajes, cremas, salteados, asados y otras preparaciones cotidianas, adaptando cada receta a los productos recibidos.</p>
<p>Al recibir la caja, conviene revisar cada hortaliza y conservarla según sus necesidades: algunas se mantienen mejor en frigorífico y otras en un lugar fresco, seco y ventilado.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>10 kg of fresh seasonal vegetables</h2>
<p>This box contains 10 kg of vegetables from Fresno de la Vega selected from the produce available at the time. Because it is a seasonal box, the contents may change throughout the year.</p>
<p>The vegetables are prepared according to availability in the fields and the order. Shipping costs are included for destinations in mainland Spain.</p>
<h2>How to use the box</h2>
<p>The variety in the selection can be used for salads, stews, soups, purées, sautés, roasting and other everyday dishes, adapting each recipe to the vegetables received.</p>
<p>When the box arrives, check each type of vegetable and store it according to its needs: some keep best refrigerated, while others are better in a cool, dry and well-ventilated place.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuánta cantidad incluye la caja?</h3><p>Incluye 10 kg de hortalizas frescas.</p>
<h3>¿Qué hortalizas contiene?</h3><p>La composición depende de los productos de temporada disponibles en Fresno de la Vega en cada momento.</p>
<h3>¿Los gastos de envío están incluidos?</h3><p>Sí. La ficha actual indica que los gastos de envío están incluidos para destinos de la Península.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How much does the box contain?</h3><p>It contains 10 kg of fresh vegetables.</p>
<h3>Which vegetables are included?</h3><p>The contents depend on the seasonal produce available in Fresno de la Vega at the time.</p>
<h3>Are shipping costs included?</h3><p>Yes. The current product information states that shipping costs are included for destinations in mainland Spain.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha8_fail("TranslatePress table missing: {$table}");

$backup=[]; $pairs=[];
foreach($products as $key=>$p){
    $post=get_post($p['id']);
    if(!$post || $post->post_type!=='product') mo_ha8_fail("Missing product {$p['id']}");
    if($post->post_status!=='publish') mo_ha8_fail("Not published {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha8_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha8_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha8_vendor($post),'Huerta de Ana Mary')===false) mo_ha8_fail("Vendor mismatch {$p['id']}: ".mo_ha8_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha8_fail("Not instock {$p['id']}");
    $backup[$p['id']]=[
      'post_excerpt'=>$post->post_excerpt,'post_content'=>$post->post_content,
      '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
      '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
    ];
    $full_es=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];
    $full_en=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];
    $products[$key]['full_es']=$full_es; $products[$key]['full_en']=$full_en;
    $pairs[$p['title']]=$p['title_en'];
    $pairs=array_merge($pairs,mo_ha8_pair_html($p['es_excerpt'],$p['en_excerpt'],$key.' excerpt'));
    $pairs=array_merge($pairs,mo_ha8_pair_html($full_es,$full_en,$key.' content'));
}
$backup_key='mo_huerta_anamary_batch08_backup_20260831';
if(get_option($backup_key,false)===false){ add_option($backup_key,$backup,'','no'); echo "BACKUP created {$backup_key}\n"; }
else { echo "BACKUP exists {$backup_key}\n"; }

foreach($products as $p){
    $r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);
    if(is_wp_error($r)) mo_ha8_fail("Update failed {$p['id']}: ".$r->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);
}
foreach($pairs as $original=>$translated){ mo_ha8_trp_upsert($table,$original,$translated); }

foreach($products as $p){
    $post=get_post($p['id']);
    $en_short=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);
    $en_long=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($post->post_content,'Preguntas frecuentes')===false) mo_ha8_fail("Spanish verification failed {$p['id']}");
    if(strpos($en_long,'About La Huerta de Ana Mary')===false || strpos($en_long,'Frequently asked questions')===false) mo_ha8_fail("English verification failed {$p['id']}");
    if(trim(wp_strip_all_tags($en_short))==='') mo_ha8_fail("English excerpt empty {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}
echo 'DONE huerta_batch08_products='.count($products).' translations='.count($pairs)."\n";
