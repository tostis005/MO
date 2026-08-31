<?php
/** La Huerta de Ana Mary product copy batch 03 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha3_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha3_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha3_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha3_pair_html($es_html,$en_html,$label){
    $es=mo_ha3_segments($es_html); $en=mo_ha3_segments($en_html);
    if(count($es)!==count($en)) mo_ha3_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha3_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha3_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha3_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha3_fail("TranslatePress insert failed: {$original}");
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
'agridulce'=>[
 'id'=>12730,'title'=>'Pimiento agridulce 720 ml, artesano','title_en'=>'Artisan Sweet-and-Sour Peppers 720 ml','slug'=>'pimiento-agridulce-720-ml-artesano',
 'es_excerpt'=>'<p>Pimientos agridulces de elaboración artesanal en tarro de cristal de 720 ml, preparados con pimientos, vinagre, azúcar, agua y zumo de limón. Sin conservantes ni colorantes.</p>',
 'en_excerpt'=>'<p>Artisan sweet-and-sour peppers in a 720 ml glass jar, made with peppers, vinegar, sugar, water and lemon juice. No preservatives or colourings.</p>',
 'es_content'=><<<'HTML'
<h2>Pimientos agridulces artesanos</h2>
<p>Una conserva de pimiento con un equilibrio entre dulzor y acidez, pensada para servir directamente o incorporar a platos fríos y calientes. Se presenta en tarro de cristal de 720 ml.</p>
<p>Los ingredientes indicados para esta conserva son pimientos, vinagre, azúcar, agua y zumo de limón. No se añaden conservantes ni colorantes.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden servirse como aperitivo, guarnición o acompañamiento de carnes, quesos, ensaladas y bocadillos. También funcionan bien para aportar un contraste agridulce a platos preparados.</p>
<p>El tarro tiene un peso neto de 660 g y un peso escurrido de 400 g.</p>
<h2>Elaboración y sellos de calidad</h2>
<p>Esta conserva se elabora de forma artesanal en Fresno de la Vega, León, y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Artisan sweet-and-sour peppers</h2>
<p>A preserved pepper with a balance of sweetness and acidity, suitable for serving directly or adding to both cold and hot dishes. It is supplied in a 720 ml glass jar.</p>
<p>The listed ingredients are peppers, vinegar, sugar, water and lemon juice. No preservatives or colourings are added.</p>
<h2>How to use them</h2>
<p>Serve them as an appetiser, side dish or accompaniment to meat, cheese, salads and sandwiches. They also work well for adding a sweet-and-sour contrast to prepared dishes.</p>
<p>The jar has a net weight of 660 g and a drained weight of 400 g.</p>
<h2>Production and quality seals</h2>
<p>This preserve is made using traditional methods in Fresno de la Vega, León, and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality seals.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes lleva?</h3>
<p>Pimientos, vinagre, azúcar, agua y zumo de limón.</p>
<h3>¿Lleva conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
<h3>¿Qué cantidad contiene?</h3>
<p>El envase es de 720 ml, con 660 g de peso neto y 400 g de peso escurrido.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Peppers, vinegar, sugar, water and lemon juice.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
<h3>How much does the jar contain?</h3>
<p>The jar is 720 ml, with a net weight of 660 g and a drained weight of 400 g.</p>
HTML,
],
'frito'=>[
 'id'=>12733,'title'=>'Pimiento frito en aceite de oliva 314 ml','title_en'=>'Fried Peppers in Olive Oil 314 ml','slug'=>'pimiento-frito-en-aceite-de-oliva-314-ml',
 'es_excerpt'=>'<p>Pimientos rojos fritos en aceite de oliva, elaborados artesanalmente y presentados en tarro de cristal de 314 ml. Preparados con pimientos rojos, aceite de oliva y zumo de limón, sin conservantes ni colorantes.</p>',
 'en_excerpt'=>'<p>Red peppers fried in olive oil, traditionally prepared and supplied in a 314 ml glass jar. Made with red peppers, olive oil and lemon juice, with no preservatives or colourings.</p>',
 'es_content'=><<<'HTML'
<h2>Pimiento rojo frito en aceite de oliva</h2>
<p>Una conserva lista para servir elaborada a partir de pimientos rojos fritos en aceite de oliva. El formato de 314 ml permite tener un acompañamiento preparado para utilizar directamente en comidas y cenas.</p>
<p>Sus ingredientes son pimientos rojos, aceite de oliva y zumo de limón. No se añaden conservantes ni colorantes.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse como aperitivo o guarnición y combinarse con carnes, pescados, huevos, quesos, ensaladas, tostas o bocadillos.</p>
<p>El tarro tiene un peso neto de 320 g y un peso escurrido de 300 g.</p>
<h2>Elaboración y sellos de calidad</h2>
<p>Esta conserva se elabora de forma artesanal en Fresno de la Vega, León, y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Red peppers fried in olive oil</h2>
<p>A ready-to-serve preserve made from red peppers fried in olive oil. The 314 ml format provides a prepared accompaniment that can be used directly for everyday meals.</p>
<p>The ingredients are red peppers, olive oil and lemon juice. No preservatives or colourings are added.</p>
<h2>How to use it</h2>
<p>Serve it as an appetiser or side dish and pair it with meat, fish, eggs, cheese, salads, toast or sandwiches.</p>
<p>The jar has a net weight of 320 g and a drained weight of 300 g.</p>
<h2>Production and quality seals</h2>
<p>This preserve is made using traditional methods in Fresno de la Vega, León, and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality seals.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes lleva?</h3>
<p>Pimientos rojos, aceite de oliva y zumo de limón.</p>
<h3>¿Está listo para consumir?</h3>
<p>Sí. Puede servirse directamente como aperitivo, guarnición o acompañamiento.</p>
<h3>¿Qué cantidad contiene?</h3>
<p>El envase es de 314 ml, con 320 g de peso neto y 300 g de peso escurrido.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Red peppers, olive oil and lemon juice.</p>
<h3>Is it ready to eat?</h3>
<p>Yes. It can be served directly as an appetiser, side dish or accompaniment.</p>
<h3>How much does the jar contain?</h3>
<p>The jar is 314 ml, with a net weight of 320 g and a drained weight of 300 g.</p>
HTML,
],
'berenjena'=>[
 'id'=>12735,'title'=>'Berenjena','title_en'=>'Aubergine','slug'=>'berenjena',
 'es_excerpt'=>'<p>Berenjena fresca de temporada, una hortaliza muy versátil para asar, hornear, saltear, rellenar o incorporar a guisos y platos de verduras. Se vende por kilo.</p>',
 'en_excerpt'=>'<p>Fresh seasonal aubergine, a versatile vegetable for roasting, baking, sautéing, stuffing or adding to stews and vegetable dishes. Sold by the kilogram.</p>',
 'es_content'=><<<'HTML'
<h2>Una hortaliza de verano muy versátil</h2>
<p>La berenjena es una hortaliza propia de los meses cálidos y especialmente habitual en la cocina mediterránea. Su pulpa absorbe bien los sabores de salsas, especias y otros ingredientes, por lo que admite preparaciones muy diferentes.</p>
<p>Se vende por kilo y puede cocinarse con piel una vez bien lavada.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede asarse, hornearse, saltearse, cocinarse a la plancha, rellenarse o utilizarse en guisos, pistos y platos de verduras. Para algunas recetas puede cortarse y salarse brevemente antes de cocinar, aunque no es imprescindible.</p>
<p>Guárdala en el frigorífico y procura consumirla mientras la piel se mantiene firme, lisa y sin zonas blandas.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A versatile summer vegetable</h2>
<p>Aubergine is a warm-season vegetable widely used in Mediterranean cooking. Its flesh takes on the flavours of sauces, spices and other ingredients very well, making it suitable for many different preparations.</p>
<p>It is sold by the kilogram and can be cooked with the skin on after washing thoroughly.</p>
<h2>How to use it</h2>
<p>It can be roasted, baked, sautéed, griddled, stuffed or added to stews and vegetable dishes. For some recipes it can be sliced and lightly salted before cooking, although this is not essential.</p>
<p>Keep it refrigerated and use it while the skin remains firm, smooth and free from soft areas.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿En qué formato se vende?</h3>
<p>Se vende por kilo.</p>
<h3>¿Hay que pelarla?</h3>
<p>No es necesario. La piel puede cocinarse y consumirse después de lavar bien la berenjena.</p>
<h3>¿Cómo se puede preparar?</h3>
<p>Asada, al horno, salteada, a la plancha, rellena o incorporada a guisos y platos de verduras.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How is it sold?</h3>
<p>It is sold by the kilogram.</p>
<h3>Does it need to be peeled?</h3>
<p>No. The skin can be cooked and eaten after washing the aubergine thoroughly.</p>
<h3>How can it be prepared?</h3>
<p>Roasted, baked, sautéed, griddled, stuffed or added to stews and vegetable dishes.</p>
HTML,
],
'ajetes'=>[
 'id'=>12740,'title'=>'Ajetes al natural 314 ml','title_en'=>'Natural Garlic Shoots 314 ml','slug'=>'ajetes-al-natural-314-ml',
 'es_excerpt'=>'<p>Ajetes al natural de elaboración artesanal, presentados en tarro de cristal de 314 ml. Una conserva de sabor marcado y lista para incorporar a revueltos, salteados, tortillas y otras preparaciones.</p>',
 'en_excerpt'=>'<p>Traditionally prepared natural garlic shoots in a 314 ml glass jar. A full-flavoured preserve ready to add to scrambled eggs, sautés, omelettes and other dishes.</p>',
 'es_content'=><<<'HTML'
<h2>Ajetes al natural listos para cocinar</h2>
<p>Los ajetes o ajos tiernos tienen un sabor reconocible pero más suave que el ajo seco. En esta conserva se presentan al natural en un tarro de cristal de 314 ml, preparados para facilitar su uso en cocina.</p>
<p>La ficha del producto indica una elaboración artesanal y sin conservantes ni colorantes.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden utilizarse en revueltos, tortillas, salteados, arroces, pasta o como acompañamiento de carnes, pescados y otras verduras. Al estar ya en conserva, resultan especialmente prácticos para preparaciones rápidas.</p>
<p>Una vez abierto el envase, conviene conservarlo refrigerado y seguir las indicaciones de conservación que figuren en el tarro.</p>
<h2>Elaboración en Fresno de la Vega</h2>
<p>Esta conserva procede de una elaboración artesanal vinculada a Fresno de la Vega, en León, una zona con una larga tradición en el cultivo y transformación de hortalizas.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Natural garlic shoots ready for cooking</h2>
<p>Garlic shoots, or young garlic, have a recognisable flavour that is milder than mature garlic. In this preserve they are supplied natural in a 314 ml glass jar, prepared for convenient use in the kitchen.</p>
<p>The product information states that they are traditionally prepared without preservatives or colourings.</p>
<h2>How to use them</h2>
<p>Use them in scrambled eggs, omelettes, sautés, rice dishes, pasta or as an accompaniment to meat, fish and other vegetables. As they are already preserved, they are particularly convenient for quick meals.</p>
<p>Once opened, keep the jar refrigerated and follow the storage instructions shown on the packaging.</p>
<h2>Made in Fresno de la Vega</h2>
<p>This preserve comes from traditional production linked to Fresno de la Vega, in León, an area with a long history of growing and preserving vegetables.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué son los ajetes?</h3>
<p>Son ajos tiernos, con un sabor más suave que el ajo seco y muy utilizados en revueltos y salteados.</p>
<h3>¿En qué formato se presentan?</h3>
<p>En tarro de cristal de 314 ml.</p>
<h3>¿Cómo se pueden utilizar?</h3>
<p>En revueltos, tortillas, salteados, arroces, pasta o como acompañamiento de otros platos.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are garlic shoots?</h3>
<p>They are young garlic, milder than mature garlic and commonly used in scrambled eggs and sautés.</p>
<h3>How are they supplied?</h3>
<p>In a 314 ml glass jar.</p>
<h3>How can they be used?</h3>
<p>In scrambled eggs, omelettes, sautés, rice dishes, pasta or as an accompaniment to other foods.</p>
HTML,
],
'picante'=>[
 'id'=>12743,'title'=>'Pimiento verde picante en vinagre, 314 ml','title_en'=>'Hot Green Peppers in Vinegar 314 ml','slug'=>'pimiento-verde-picante-en-vinagre-314-ml',
 'es_excerpt'=>'<p>Pimientos verdes picantes en vinagre, de elaboración artesanal y presentados en tarro de cristal de 314 ml. Preparados con pimientos verdes, vinagre, aceite de oliva y sal, y listos para consumir.</p>',
 'en_excerpt'=>'<p>Hot green peppers in vinegar, traditionally prepared and supplied in a 314 ml glass jar. Made with green peppers, vinegar, olive oil and salt, ready to eat.</p>',
 'es_content'=><<<'HTML'
<h2>Pimiento verde picante en vinagre</h2>
<p>Una conserva de sabor intenso pensada para quienes buscan un acompañamiento picante ya preparado. Los pimientos se presentan en un tarro de cristal de 314 ml y están listos para consumir.</p>
<p>Sus ingredientes son pimientos verdes, vinagre, aceite de oliva y sal. La ficha del producto indica que se elabora artesanalmente y sin conservantes ni colorantes.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede servirse como aperitivo, acompañamiento de legumbres, carnes, bocadillos y platos de cuchara, o utilizarse para añadir un punto picante a otras preparaciones.</p>
<p>El tarro tiene un peso neto de 300 g y un peso escurrido de 230 g.</p>
<h2>Elaboración y sellos de calidad</h2>
<p>Esta conserva se elabora en Fresno de la Vega, León, y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Hot green peppers in vinegar</h2>
<p>A full-flavoured preserve for anyone looking for a ready-to-serve spicy accompaniment. The peppers are supplied in a 314 ml glass jar and are ready to eat.</p>
<p>The ingredients are green peppers, vinegar, olive oil and salt. The product information states that it is traditionally prepared without preservatives or colourings.</p>
<h2>How to use it</h2>
<p>Serve it as an appetiser, alongside pulses, meat, sandwiches and slow-cooked dishes, or use it to add heat to other preparations.</p>
<p>The jar has a net weight of 300 g and a drained weight of 230 g.</p>
<h2>Production and quality seals</h2>
<p>This preserve is made in Fresno de la Vega, León, and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality seals.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes lleva?</h3>
<p>Pimientos verdes, vinagre, aceite de oliva y sal.</p>
<h3>¿Está listo para consumir?</h3>
<p>Sí. Puede servirse directamente como aperitivo o acompañamiento.</p>
<h3>¿Qué cantidad contiene?</h3>
<p>El envase es de 314 ml, con 300 g de peso neto y 230 g de peso escurrido.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Green peppers, vinegar, olive oil and salt.</p>
<h3>Is it ready to eat?</h3>
<p>Yes. It can be served directly as an appetiser or accompaniment.</p>
<h3>How much does the jar contain?</h3>
<p>The jar is 314 ml, with a net weight of 300 g and a drained weight of 230 g.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha3_fail("TranslatePress table missing: {$table}");

foreach($products as $key=>$p){
    $post=get_post((int)$p['id']);
    if(!$post || $post->post_type!=='product') mo_ha3_fail("Invalid product {$p['id']}");
    if($post->post_status!=='publish') mo_ha3_fail("Not published {$p['id']} {$post->post_status}");
    if($post->post_name!==$p['slug']) mo_ha3_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if($post->post_title!==$p['title']) mo_ha3_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if(stripos(mo_ha3_vendor($post),'Huerta de Ana Mary')===false) mo_ha3_fail("Vendor mismatch {$p['id']}: ".mo_ha3_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha3_fail("Not instock {$p['id']}");
}

$backup_key='mo_huerta_anamary_batch03_backup_20260831';
if(get_option($backup_key,null)===null){
    $backup=[];
    foreach($products as $p){
        $post=get_post((int)$p['id']);
        $backup[$p['id']]=[
            'post_excerpt'=>$post->post_excerpt,
            'post_content'=>$post->post_content,
            '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
            '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
        ];
    }
    add_option($backup_key,$backup,'','no');
    echo "BACKUP created {$backup_key}\n";
} else {
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

$translation_pairs=[];
$translation_pairs += mo_ha3_pair_html($producer_es,$producer_en,'producer');

foreach($products as $key=>$p){
    $es_long=$p['es_content'].$producer_es.$p['es_faq'];
    $en_long=$p['en_content'].$producer_en.$p['en_faq'];
    $updated=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$es_long],true);
    if(is_wp_error($updated)) mo_ha3_fail("Update failed {$p['id']}: ".$updated->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$en_long);

    $translation_pairs[$p['title']]=$p['title_en'];
    $translation_pairs += mo_ha3_pair_html($p['es_excerpt'],$p['en_excerpt'],"excerpt {$p['id']}");
    $translation_pairs += mo_ha3_pair_html($p['es_content'],$p['en_content'],"content {$p['id']}");
    $translation_pairs += mo_ha3_pair_html($p['es_faq'],$p['en_faq'],"faq {$p['id']}");

    $fresh=get_post($p['id']);
    if(strpos($fresh->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($fresh->post_content,'Preguntas frecuentes')===false) mo_ha3_fail("ES verification failed {$p['id']}");
    $enmeta=get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($enmeta,'About La Huerta de Ana Mary')===false || strpos($enmeta,'Frequently asked questions')===false) mo_ha3_fail("EN verification failed {$p['id']}");
    if(get_post_meta($p['id'],'_en_US_post_excerpt',true)!==$p['en_excerpt']) mo_ha3_fail("EN excerpt verification failed {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}

foreach($translation_pairs as $original=>$translated){ mo_ha3_trp_upsert($table,$original,$translated); }
wp_cache_flush();
echo 'DONE huerta_batch03_products='.count($products).' translations='.count($translation_pairs)."\n";
