<?php
/** La Huerta de Ana Mary product copy batch 04 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha4_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha4_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha4_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha4_pair_html($es_html,$en_html,$label){
    $es=mo_ha4_segments($es_html); $en=mo_ha4_segments($en_html);
    if(count($es)!==count($en)) mo_ha4_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha4_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha4_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha4_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha4_fail("TranslatePress insert failed: {$original}");
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
'potato20'=>[
 'id'=>12746,'title'=>'20 Kg de patatas rojas, red pontiac','title_en'=>'20 kg Red Pontiac Red Potatoes','slug'=>'20-kg-de-patatas-rojas-red-pontiac',
 'es_excerpt'=>'<p>20 kg de patatas rojas Red Pontiac, de carne blanca y ojos marcados. Una variedad multiusos para cocer, freír, asar, preparar purés o utilizar en guisos. El precio incluye el envío a la Península; esta referencia no se envía a las islas.</p>',
 'en_excerpt'=>'<p>20 kg of Red Pontiac red potatoes with white flesh and marked eyes. A versatile variety for boiling, frying, roasting, mashing or adding to stews. The price includes delivery to mainland Spain; this product is not shipped to the islands.</p>',
 'es_content'=><<<'HTML'
<h2>Patata roja Red Pontiac en formato de 20 kg</h2>
<p>La Red Pontiac es una patata de piel roja, carne blanca y ojos marcados. Es una variedad multiusos que permite resolver preparaciones muy distintas con una misma patata.</p>
<p>Este formato reúne 20 kg en un solo pedido. El precio incluye los gastos de envío a destinos de la Península. Esta referencia no se envía a las islas.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede utilizarse para cocer, freír, asar, preparar purés o incorporar a guisos y platos de cuchara. Su versatilidad la convierte en una opción práctica para hogares con un consumo habitual de patata.</p>
<p>Para conservarla, mantenla en un lugar fresco, seco, oscuro y ventilado, alejada de fuentes de calor y de la luz directa.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Red Pontiac potatoes in a 20 kg format</h2>
<p>Red Pontiac is a red-skinned potato with white flesh and marked eyes. It is a versatile variety that can be used for many different preparations.</p>
<p>This format contains 20 kg in a single order. The price includes delivery to destinations in mainland Spain. This product is not shipped to the islands.</p>
<h2>How to use them</h2>
<p>They can be boiled, fried, roasted, mashed or added to stews and other slow-cooked dishes. Their versatility makes them a practical choice for households that use potatoes regularly.</p>
<p>Store them in a cool, dry, dark and well-ventilated place, away from heat sources and direct light.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué variedad de patata es?</h3>
<p>Es patata roja de la variedad Red Pontiac, con carne blanca y ojos marcados.</p>
<h3>¿Cuánta cantidad incluye?</h3>
<p>El formato incluye 20 kg de patatas.</p>
<h3>¿El envío está incluido?</h3>
<p>Sí, el precio incluye el envío a la Península. Esta referencia no se envía a las islas.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which potato variety is it?</h3>
<p>It is the Red Pontiac variety, with red skin, white flesh and marked eyes.</p>
<h3>How much is included?</h3>
<p>The format includes 20 kg of potatoes.</p>
<h3>Is delivery included?</h3>
<p>Yes. The price includes delivery to mainland Spain. This product is not shipped to the islands.</p>
HTML,
],
'leeks'=>[
 'id'=>12748,'title'=>'Puerros en conserva 720 ml, artesanos','title_en'=>'Artisan Natural Leeks 720 ml','slug'=>'puerros-en-conserva-720-ml-artesanos',
 'es_excerpt'=>'<p>Puerros al natural de elaboración artesanal en tarro de cristal de 720 ml. Elaborados sin conservantes ni colorantes, con puerros, agua y zumo de limón.</p>',
 'en_excerpt'=>'<p>Traditionally prepared natural leeks in a 720 ml glass jar. Made without preservatives or colourings, using leeks, water and lemon juice.</p>',
 'es_content'=><<<'HTML'
<h2>Puerros al natural en conserva</h2>
<p>Estos puerros se presentan al natural en un tarro de cristal de 720 ml. La conserva es de elaboración artesanal y no incorpora conservantes ni colorantes.</p>
<p>Sus ingredientes son puerros, agua y zumo de limón. El producto cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharlos</h2>
<p>Están listos para utilizar y pueden servirse como entrante o guarnición, incorporarse a ensaladas templadas o acompañarse con vinagretas, salsas suaves, huevos, pescados o carnes.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Natural preserved leeks</h2>
<p>These leeks are preserved naturally in a 720 ml glass jar. They are traditionally prepared without preservatives or colourings.</p>
<p>The ingredients are leeks, water and lemon juice. The product carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use them</h2>
<p>They are ready to use and can be served as a starter or side dish, added to warm salads or paired with vinaigrettes, mild sauces, eggs, fish or meat.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contiene?</h3>
<p>Puerros, agua y zumo de limón.</p>
<h3>¿Lleva conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
<h3>¿Qué formato tiene?</h3>
<p>Se presenta en un tarro de cristal de 720 ml.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Leeks, water and lemon juice.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
<h3>What is the format?</h3>
<p>It comes in a 720 ml glass jar.</p>
HTML,
],
'roasted'=>[
 'id'=>12751,'title'=>'Pimiento artesano asado dulce, 314 ml','title_en'=>'Artisan Sweet Roasted Peppers 314 ml','slug'=>'pimiento-artesano-asado-dulce-314-ml',
 'es_excerpt'=>'<p>Pimiento morrón dulce asado con leña, pelado a mano y envasado en tarro de cristal de 314 ml. Elaboración artesanal, sin conservantes ni colorantes, con pimiento asado y zumo de limón.</p>',
 'en_excerpt'=>'<p>Sweet red peppers roasted over wood, peeled by hand and packed in a 314 ml glass jar. Traditionally prepared without preservatives or colourings, using roasted peppers and lemon juice.</p>',
 'es_content'=><<<'HTML'
<h2>Pimiento dulce asado con leña y pelado a mano</h2>
<p>Esta conserva se elabora con pimientos morrones dulces asados con leña y pelados a mano antes de su envasado en tarro de cristal de 314 ml.</p>
<p>Sus ingredientes son pimientos morrones asados y zumo de limón. No se añaden conservantes ni colorantes y el producto cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse directamente como entrante o guarnición, utilizarse en ensaladas, tostas y bocadillos o acompañar carnes, pescados, huevos y otros platos.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Sweet peppers roasted over wood and peeled by hand</h2>
<p>This preserve is made with sweet red peppers roasted over wood and peeled by hand before being packed in a 314 ml glass jar.</p>
<p>The ingredients are roasted red peppers and lemon juice. No preservatives or colourings are added, and the product carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Serve directly as a starter or side dish, use in salads, toast or sandwiches, or pair with meat, fish, eggs and other dishes.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo se asan los pimientos?</h3>
<p>Se asan con leña y se pelan a mano antes del envasado.</p>
<h3>¿Qué ingredientes contiene?</h3>
<p>Pimientos morrones asados y zumo de limón.</p>
<h3>¿Lleva conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How are the peppers roasted?</h3>
<p>They are roasted over wood and peeled by hand before being packed.</p>
<h3>What are the ingredients?</h3>
<p>Roasted red peppers and lemon juice.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
'potato1'=>[
 'id'=>12754,'title'=>'Patatas rojas red pontiac','title_en'=>'Red Pontiac Red Potatoes','slug'=>'patatas-rojas-red-pontiac',
 'es_excerpt'=>'<p>Patatas rojas variedad Red Pontiac, de carne blanca y ojos marcados, vendidas por kilo. Una patata multiusos adecuada para cocer, freír, asar, preparar purés o incorporar a guisos.</p>',
 'en_excerpt'=>'<p>Red Pontiac red potatoes with white flesh and marked eyes, sold by the kilogram. A versatile potato for boiling, frying, roasting, mashing or adding to stews.</p>',
 'es_content'=><<<'HTML'
<h2>Patata roja Red Pontiac para distintos usos</h2>
<p>La Red Pontiac se reconoce por su piel roja, su carne blanca y sus ojos marcados. Es una variedad multiusos que se adapta a preparaciones muy diferentes en la cocina diaria.</p>
<p>Esta referencia se vende por kilo.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede utilizarse para cocer, freír, asar, preparar purés o incorporar a guisos y platos de cuchara. Elegir el tipo de cocción según el plato permite sacar partido a su versatilidad.</p>
<p>Para conservarla, mantenla en un lugar fresco, seco, oscuro y ventilado, alejada de fuentes de calor y de la luz directa.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Red Pontiac potatoes for different uses</h2>
<p>Red Pontiac potatoes are recognised by their red skin, white flesh and marked eyes. They are a versatile variety suited to many different everyday preparations.</p>
<p>This product is sold by the kilogram.</p>
<h2>How to use them</h2>
<p>They can be boiled, fried, roasted, mashed or added to stews and other slow-cooked dishes. Choosing the cooking method according to the recipe makes the most of their versatility.</p>
<p>Store them in a cool, dry, dark and well-ventilated place, away from heat sources and direct light.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué variedad de patata es?</h3>
<p>Es patata roja de la variedad Red Pontiac, con carne blanca y ojos marcados.</p>
<h3>¿En qué formato se vende?</h3>
<p>Esta referencia se vende por kilo.</p>
<h3>¿Para qué preparaciones sirve?</h3>
<p>Es una variedad multiusos adecuada para cocer, freír, asar, preparar purés y utilizar en guisos.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which potato variety is it?</h3>
<p>It is the Red Pontiac variety, with red skin, white flesh and marked eyes.</p>
<h3>How is it sold?</h3>
<p>This product is sold by the kilogram.</p>
<h3>What can it be used for?</h3>
<p>It is a versatile variety suitable for boiling, frying, roasting, mashing and using in stews.</p>
HTML,
],
'hotpeppers'=>[
 'id'=>12757,'title'=>'Pimientos picantes','title_en'=>'Hot Peppers','slug'=>'pimientos-picantes',
 'es_excerpt'=>'<p>Pimientos frescos picantes, verdes o rojos, vendidos por kilo. Indicados para quienes buscan sabores intensos y para utilizar en crudo, fritos, como acompañamiento o escaldados y aliñados al estilo tradicional leonés.</p>',
 'en_excerpt'=>'<p>Fresh hot peppers, green or red, sold by the kilogram. For those who enjoy intense flavours, suitable for eating raw, frying, serving as an accompaniment or blanching and dressing in the traditional León style.</p>',
 'es_content'=><<<'HTML'
<h2>Pimientos para quienes buscan un picante intenso</h2>
<p>Esta variedad puede presentarse en verde o en rojo y destaca por su carácter picante. Se vende por kilo y permite utilizar el pimiento de distintas formas según el plato.</p>
<p>Su sabor intenso hace que funcione tanto como ingrediente principal de una preparación como en pequeñas cantidades para aportar picante.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden consumirse en crudo, incorporarse a ensaladas y otros platos, freírse o utilizarse como acompañamiento. En León también es tradicional prepararlos escaldados y aliñados con aceite, sal y vinagre.</p>
<p>Consérvalos en el frigorífico y evita el exceso de humedad durante el almacenamiento.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Peppers for those who enjoy intense heat</h2>
<p>This variety may be green or red and is characterised by its hot flavour. It is sold by the kilogram and can be used in different ways depending on the dish.</p>
<p>Its intensity means it can be used as a main ingredient or in smaller amounts to add heat to a preparation.</p>
<h2>How to use them</h2>
<p>They can be eaten raw, added to salads and other dishes, fried or served as an accompaniment. In León, they are also traditionally blanched and dressed with oil, salt and vinegar.</p>
<p>Keep them refrigerated and avoid excess moisture during storage.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Son picantes?</h3>
<p>Sí. Esta referencia corresponde a una variedad de pimiento picante.</p>
<h3>¿Pueden ser verdes o rojos?</h3>
<p>Sí. La ficha del producto contempla pimientos verdes o rojos.</p>
<h3>¿Cómo se pueden preparar?</h3>
<p>En crudo, fritos, como acompañamiento o escaldados y aliñados con aceite, sal y vinagre.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Are they hot?</h3>
<p>Yes. This product is a hot pepper variety.</p>
<h3>Can they be green or red?</h3>
<p>Yes. The product information covers both green and red peppers.</p>
<h3>How can they be prepared?</h3>
<p>Raw, fried, as an accompaniment or blanched and dressed with oil, salt and vinegar.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha4_fail("TranslatePress table missing: {$table}");

$backup=[]; $pairs=[];
foreach($products as $key=>$p){
    $post=get_post($p['id']);
    if(!$post || $post->post_type!=='product') mo_ha4_fail("Missing product {$p['id']}");
    if($post->post_status!=='publish') mo_ha4_fail("Not published {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha4_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha4_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha4_vendor($post),'Huerta de Ana Mary')===false) mo_ha4_fail("Vendor mismatch {$p['id']}: ".mo_ha4_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha4_fail("Not instock {$p['id']}");
    $backup[$p['id']]=[
      'post_excerpt'=>$post->post_excerpt,'post_content'=>$post->post_content,
      '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
      '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
    ];
    $full_es=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];
    $full_en=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];
    $products[$key]['full_es']=$full_es; $products[$key]['full_en']=$full_en;
    $pairs[$p['title']]=$p['title_en'];
    $pairs=array_merge($pairs,mo_ha4_pair_html($p['es_excerpt'],$p['en_excerpt'],$key.' excerpt'));
    $pairs=array_merge($pairs,mo_ha4_pair_html($full_es,$full_en,$key.' content'));
}
$backup_key='mo_huerta_anamary_batch04_backup_20260831';
if(get_option($backup_key,false)===false){ add_option($backup_key,$backup,'','no'); echo "BACKUP created {$backup_key}\n"; }
else { echo "BACKUP exists {$backup_key}\n"; }

foreach($products as $p){
    $r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);
    if(is_wp_error($r)) mo_ha4_fail("Update failed {$p['id']}: ".$r->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);
}
foreach($pairs as $original=>$translated){ mo_ha4_trp_upsert($table,$original,$translated); }

foreach($products as $p){
    $post=get_post($p['id']);
    $en_short=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);
    $en_long=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($post->post_content,'Preguntas frecuentes')===false) mo_ha4_fail("Spanish verification failed {$p['id']}");
    if(strpos($en_long,'About La Huerta de Ana Mary')===false || strpos($en_long,'Frequently asked questions')===false) mo_ha4_fail("English verification failed {$p['id']}");
    if(trim(wp_strip_all_tags($en_short))==='') mo_ha4_fail("English excerpt empty {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}
echo 'DONE huerta_batch04_products='.count($products).' translations='.count($pairs)."\n";
