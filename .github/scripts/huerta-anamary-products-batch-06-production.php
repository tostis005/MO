<?php
/** La Huerta de Ana Mary product copy batch 06 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha6_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha6_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha6_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha6_pair_html($es_html,$en_html,$label){
    $es=mo_ha6_segments($es_html); $en=mo_ha6_segments($en_html);
    if(count($es)!==count($en)) mo_ha6_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha6_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha6_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha6_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha6_fail("TranslatePress insert failed: {$original}");
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
'leek_jam_box'=>[
 'id'=>12775,'title'=>'12 Tarros de confitura de puerro 250ml','title_en'=>'12 Jars of Leek Jam 250 ml','slug'=>'12-tarros-de-confitura-de-puerro-250ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de confitura artesana de puerro de 250 ml cada uno. Elaborada únicamente con puerro y azúcar, sin conservantes ni colorantes, y pensada para acompañar carnes, patés, quesos, tostas o aperitivos.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of artisan leek jam, 250 ml each. Made only with leek and sugar, without preservatives or colourings, and suitable for pairing with meat, pâtés, cheese, toast or appetisers.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de confitura artesana de puerro</h2>
<p>Esta caja incluye 12 tarros de cristal de 250 ml de confitura de puerro. La receta es sencilla: puerro y azúcar, sin conservantes ni colorantes.</p>
<p>La confitura cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede servirse con carnes, patés y quesos, utilizarse en tostas y canapés o incorporarse a aperitivos en los que interese aportar un contraste dulce y vegetal.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of artisan leek jam</h2>
<p>This box contains 12 glass jars of leek jam, 250 ml each. The recipe is simple: leek and sugar, with no preservatives or colourings.</p>
<p>The jam carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Serve it with meat, pâtés and cheese, use it on toast or canapés, or add it to appetisers where a sweet, vegetable-based contrast works well.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye la caja?</h3><p>Incluye 12 tarros de 250 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Puerro y azúcar.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 250 ml each.</p>
<h3>What are the ingredients?</h3><p>Leek and sugar.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'tomato_jam_box'=>[
 'id'=>12779,'title'=>'12 Tarros de confitura de tomate 250 ml','title_en'=>'12 Jars of Tomato Jam 250 ml','slug'=>'12-tarros-de-confitura-de-tomate-250-ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de confitura artesana de tomate de 250 ml cada uno. Elaborada únicamente con tomate y azúcar, sin conservantes ni colorantes, para aperitivos, quesos, patés, carnes o tostas.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of artisan tomato jam, 250 ml each. Made only with tomato and sugar, without preservatives or colourings, for appetisers, cheese, pâtés, meat or toast.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de confitura artesana de tomate</h2>
<p>Esta caja incluye 12 tarros de cristal de 250 ml de confitura de tomate. Se elabora con tomate y azúcar, sin conservantes ni colorantes.</p>
<p>La confitura cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede acompañar quesos, patés y carnes, utilizarse en tostas y canapés o servirse como parte de un aperitivo. Su perfil dulce permite combinarla tanto con preparaciones saladas como con otras elaboraciones.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of artisan tomato jam</h2>
<p>This box contains 12 glass jars of tomato jam, 250 ml each. It is made with tomato and sugar, without preservatives or colourings.</p>
<p>The jam carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Pair it with cheese, pâtés and meat, use it on toast or canapés, or serve it as part of an appetiser. Its sweet profile works with both savoury dishes and other preparations.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye la caja?</h3><p>Incluye 12 tarros de 250 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Tomate y azúcar.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 250 ml each.</p>
<h3>What are the ingredients?</h3><p>Tomato and sugar.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'hot_roasted_box'=>[
 'id'=>12783,'title'=>'12 Tarros de pimiento asado picante 314 ml','title_en'=>'12 Jars of Hot Roasted Peppers 314 ml','slug'=>'12-tarros-de-pimiento-asado-picante-314-ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de pimiento asado picante de 314 ml cada uno. Los pimientos se asan con leña y se pelan a mano; la conserva se elabora sin conservantes ni colorantes. Gastos de envío incluidos.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of hot roasted peppers, 314 ml each. The peppers are roasted over wood and peeled by hand; the preserve is made without preservatives or colourings. Shipping costs included.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de pimiento asado picante</h2>
<p>La caja incluye 12 tarros de cristal de 314 ml de pimiento asado picante. Los pimientos se asan con leña y se pelan a mano antes de envasarse.</p>
<p>Los ingredientes indicados son pimientos en trozos asados y zumo de limón. La conserva no lleva conservantes ni colorantes y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor. Los gastos de envío están incluidos.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse como aperitivo o guarnición, incorporarse a tostas y bocadillos o acompañar carnes, pescados, huevos y otros platos a los que se quiera añadir un toque picante.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of hot roasted peppers</h2>
<p>The box contains 12 glass jars of hot roasted peppers, 314 ml each. The peppers are roasted over wood and peeled by hand before being packed.</p>
<p>The listed ingredients are roasted pepper pieces and lemon juice. The preserve contains no preservatives or colourings and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks. Shipping costs are included.</p>
<h2>How to use them</h2>
<p>Serve them as an appetiser or side dish, add them to toast and sandwiches, or pair them with meat, fish, eggs and other dishes where a hot pepper note works well.</p>
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
'leeks_box'=>[
 'id'=>12786,'title'=>'12 Tarros de puerros al natural 720ml','title_en'=>'12 Jars of Natural Leeks 720 ml','slug'=>'12-tarros-de-puerros-al-natural-720ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de puerros al natural de 720 ml cada uno. Elaboración artesanal sin conservantes ni colorantes, con puerros, agua y zumo de limón.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of natural leeks, 720 ml each. Traditionally prepared without preservatives or colourings, using leeks, water and lemon juice.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de puerros al natural</h2>
<p>Esta caja incluye 12 tarros de cristal de 720 ml de puerros en conserva al natural. Se trata de una elaboración artesanal sin conservantes ni colorantes.</p>
<p>Sus ingredientes son puerros, agua y zumo de limón. El producto cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharlos</h2>
<p>Están listos para servir como entrante o guarnición y también pueden incorporarse a ensaladas templadas, acompañarse con vinagretas y salsas suaves o combinarse con huevos, pescados y carnes.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of natural leeks</h2>
<p>This box contains 12 glass jars of naturally preserved leeks, 720 ml each. They are traditionally prepared without preservatives or colourings.</p>
<p>The ingredients are leeks, water and lemon juice. The product carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use them</h2>
<p>They are ready to serve as a starter or side dish and can also be added to warm salads, paired with vinaigrettes and mild sauces, or combined with eggs, fish and meat.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye?</h3><p>La caja incluye 12 tarros de 720 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Puerros, agua y zumo de limón.</p>
<h3>¿Lleva conservantes o colorantes?</h3><p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 720 ml each.</p>
<h3>What are the ingredients?</h3><p>Leeks, water and lemon juice.</p>
<h3>Does it contain preservatives or colourings?</h3><p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'hot_green_box'=>[
 'id'=>12789,'title'=>'12 Tarros de pimiento verde picante en vinagre 314 ml','title_en'=>'12 Jars of Hot Green Peppers in Vinegar 314 ml','slug'=>'12-tarros-de-pimiento-verde-picante-en-vinagre-314-ml',
 'es_excerpt'=>'<p>Caja con 12 tarros de pimiento verde picante en vinagre de 314 ml cada uno. Elaboración artesanal sin conservantes ni colorantes, con pimientos verdes, vinagre, aceite de oliva y sal. Gastos de envío incluidos.</p>',
 'en_excerpt'=>'<p>Box of 12 jars of hot green peppers in vinegar, 314 ml each. Traditionally prepared without preservatives or colourings, using green peppers, vinegar, olive oil and salt. Shipping costs included.</p>',
 'es_content'=><<<'HTML'
<h2>12 tarros de pimiento verde picante en vinagre</h2>
<p>La caja incluye 12 tarros de cristal de 314 ml de pimientos verdes picantes en vinagre. Es una conserva de elaboración artesanal y está lista para servir.</p>
<p>Sus ingredientes son pimientos verdes, vinagre, aceite de oliva y sal. No lleva conservantes ni colorantes y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor. Los gastos de envío están incluidos.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden servirse como aperitivo, acompañamiento de legumbres y carnes o utilizarse para añadir un punto ácido y picante a ensaladas, bocadillos y otros platos.</p>
<p>Antes de abrir, conserva los tarros siguiendo las indicaciones del envase. Una vez abiertos, mantenlos refrigerados y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>12 jars of hot green peppers in vinegar</h2>
<p>The box contains 12 glass jars of hot green peppers in vinegar, 314 ml each. This is a traditionally prepared preserve and is ready to serve.</p>
<p>The ingredients are green peppers, vinegar, olive oil and salt. It contains no preservatives or colourings and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks. Shipping costs are included.</p>
<h2>How to use them</h2>
<p>Serve them as an appetiser, with pulses and meat, or use them to add an acidic, hot note to salads, sandwiches and other dishes.</p>
<p>Before opening, store the jars according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántos tarros incluye?</h3><p>La caja incluye 12 tarros de 314 ml cada uno.</p>
<h3>¿Qué ingredientes contiene?</h3><p>Pimientos verdes, vinagre, aceite de oliva y sal.</p>
<h3>¿Los gastos de envío están incluidos?</h3><p>Sí. La ficha actual del producto indica que los gastos de envío están incluidos.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many jars are included?</h3><p>The box contains 12 jars of 314 ml each.</p>
<h3>What are the ingredients?</h3><p>Green peppers, vinegar, olive oil and salt.</p>
<h3>Are shipping costs included?</h3><p>Yes. The current product information states that shipping costs are included.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha6_fail("TranslatePress table missing: {$table}");

$backup=[]; $pairs=[];
foreach($products as $key=>$p){
    $post=get_post($p['id']);
    if(!$post || $post->post_type!=='product') mo_ha6_fail("Missing product {$p['id']}");
    if($post->post_status!=='publish') mo_ha6_fail("Not published {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha6_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha6_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha6_vendor($post),'Huerta de Ana Mary')===false) mo_ha6_fail("Vendor mismatch {$p['id']}: ".mo_ha6_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha6_fail("Not instock {$p['id']}");
    $backup[$p['id']]=[
      'post_excerpt'=>$post->post_excerpt,'post_content'=>$post->post_content,
      '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
      '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
    ];
    $full_es=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];
    $full_en=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];
    $products[$key]['full_es']=$full_es; $products[$key]['full_en']=$full_en;
    $pairs[$p['title']]=$p['title_en'];
    $pairs=array_merge($pairs,mo_ha6_pair_html($p['es_excerpt'],$p['en_excerpt'],$key.' excerpt'));
    $pairs=array_merge($pairs,mo_ha6_pair_html($full_es,$full_en,$key.' content'));
}
$backup_key='mo_huerta_anamary_batch06_backup_20260831';
if(get_option($backup_key,false)===false){ add_option($backup_key,$backup,'','no'); echo "BACKUP created {$backup_key}\n"; }
else { echo "BACKUP exists {$backup_key}\n"; }

foreach($products as $p){
    $r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);
    if(is_wp_error($r)) mo_ha6_fail("Update failed {$p['id']}: ".$r->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);
}
foreach($pairs as $original=>$translated){ mo_ha6_trp_upsert($table,$original,$translated); }

foreach($products as $p){
    $post=get_post($p['id']);
    $en_short=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);
    $en_long=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($post->post_content,'Preguntas frecuentes')===false) mo_ha6_fail("Spanish verification failed {$p['id']}");
    if(strpos($en_long,'About La Huerta de Ana Mary')===false || strpos($en_long,'Frequently asked questions')===false) mo_ha6_fail("English verification failed {$p['id']}");
    if(trim(wp_strip_all_tags($en_short))==='') mo_ha6_fail("English excerpt empty {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}
echo 'DONE huerta_batch06_products='.count($products).' translations='.count($pairs)."\n";
