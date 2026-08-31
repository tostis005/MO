<?php
/** La Huerta de Ana Mary product copy batch 02 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha2_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha2_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha2_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha2_pair_html($es_html,$en_html,$label){
    $es=mo_ha2_segments($es_html); $en=mo_ha2_segments($en_html);
    if(count($es)!==count($en)) mo_ha2_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha2_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha2_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha2_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha2_fail("TranslatePress insert failed: {$original}");
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
'italiano'=>[
 'id'=>12715,'title'=>'Pimiento italiano','title_en'=>'Italian Sweet Pepper','slug'=>'pimiento-italiano',
 'es_excerpt'=>'<p>Pimiento italiano dulce, de forma estrecha y alargada y piel verde brillante. Una hortaliza muy versátil para freír, saltear, asar o incorporar a sofritos, guisos y otros platos cotidianos.</p>',
 'en_excerpt'=>'<p>Sweet Italian pepper with a long, narrow shape and bright green skin. A versatile vegetable for frying, sautéing, roasting or adding to sauces, stews and everyday dishes.</p>',
 'es_content'=><<<'HTML'
<h2>Un pimiento alargado y muy versátil</h2>
<p>El pimiento italiano se reconoce por su forma estrecha y alargada y por su piel verde brillante, que puede adquirir tonos rojizos a medida que madura. Tiene un sabor dulce y resulta fácil de incorporar a muchas preparaciones.</p>
<p>Su formato permite cocinarlo entero o cortarlo en tiras, rodajas o trozos según el plato.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede freírse, saltearse, asarse o cocinarse a la plancha. También funciona muy bien en sofritos, arroces, guisos, tortillas y como acompañamiento de carnes, pescados u otras verduras.</p>
<p>Guárdalo en el frigorífico y procura consumirlo mientras mantiene la piel firme y el aspecto fresco.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A long, versatile sweet pepper</h2>
<p>Italian sweet peppers are recognised by their long, narrow shape and bright green skin, which may develop reddish tones as they ripen. They have a sweet flavour and are easy to use in many different dishes.</p>
<p>Their shape makes them suitable for cooking whole or cutting into strips, slices or pieces depending on the recipe.</p>
<h2>How to use it</h2>
<p>It can be fried, sautéed, roasted or cooked on a griddle. It also works well in sofrito-style bases, rice dishes, stews, omelettes and as a side for meat, fish or other vegetables.</p>
<p>Keep it refrigerated and use it while the skin remains firm and fresh-looking.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Es un pimiento picante?</h3>
<p>No. El pimiento italiano de esta ficha es un pimiento dulce.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Se puede freír, saltear, asar, cocinar a la plancha o utilizar en sofritos y guisos.</p>
<h3>¿Cómo se conserva?</h3>
<p>En el frigorífico, procurando mantenerlo seco y consumirlo mientras conserva su firmeza.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Is it a hot pepper?</h3>
<p>No. The Italian pepper in this product is a sweet pepper.</p>
<h3>How can it be cooked?</h3>
<p>It can be fried, sautéed, roasted, griddled or used in sauces and stews.</p>
<h3>How should it be stored?</h3>
<p>Keep it refrigerated, avoid excess moisture and use it while it remains firm.</p>
HTML,
],
'cebollas'=>[
 'id'=>12718,'title'=>'Kg cebollas secas','title_en'=>'Dried Onions (kg)','slug'=>'kg-cebollas-secas',
 'es_excerpt'=>'<p>Cebollas secas vendidas por kilo, un básico de la cocina diaria para preparar sofritos, guisos, sopas, salsas, arroces o asados y para utilizar tanto cocinadas como en crudo.</p>',
 'en_excerpt'=>'<p>Dried onions sold by the kilogram, an everyday kitchen staple for sauces, stews, soups, rice dishes and roasts, and suitable for both cooked and raw preparations.</p>',
 'es_content'=><<<'HTML'
<h2>Un básico de la cocina diaria</h2>
<p>La cebolla es una de las hortalizas más utilizadas en la cocina mediterránea y sirve como base para innumerables preparaciones. Su sabor cambia notablemente con la cocción: en crudo resulta más intenso, mientras que cocinada se vuelve más suave y dulce.</p>
<p>Este formato se vende por kilo y permite disponer de una hortaliza muy versátil para platos de diario.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede utilizarse en sofritos, guisos, sopas, arroces, salsas, asados, tortillas y ensaladas. También puede cocinarse lentamente para potenciar su sabor dulce.</p>
<p>Consérvala en un lugar fresco, seco, oscuro y bien ventilado, evitando la humedad y la luz directa.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>An everyday kitchen staple</h2>
<p>Onion is one of the most widely used vegetables in Mediterranean cooking and forms the base of countless dishes. Its flavour changes considerably with cooking: raw onion is sharper, while cooked onion becomes milder and sweeter.</p>
<p>This product is sold by the kilogram and provides a highly versatile vegetable for everyday cooking.</p>
<h2>How to use it</h2>
<p>Use it in sauces, stews, soups, rice dishes, roasts, omelettes and salads. It can also be cooked slowly to bring out its sweeter flavour.</p>
<p>Store it in a cool, dry, dark and well-ventilated place, away from moisture and direct light.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿En qué formato se vende?</h3>
<p>Se vende por kilo.</p>
<h3>¿Cómo se puede utilizar?</h3>
<p>En sofritos, guisos, sopas, salsas, arroces, asados, tortillas o ensaladas.</p>
<h3>¿Cómo se conserva mejor?</h3>
<p>En un lugar fresco, seco, oscuro y ventilado, evitando la humedad.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How is it sold?</h3>
<p>It is sold by the kilogram.</p>
<h3>How can it be used?</h3>
<p>In sauces, stews, soups, rice dishes, roasts, omelettes or salads.</p>
<h3>How should it be stored?</h3>
<p>Keep it in a cool, dry, dark and well-ventilated place, away from moisture.</p>
HTML,
],
'lamuyo'=>[
 'id'=>12721,'title'=>'Pimientos lamuyos verdes','title_en'=>'Green Lamuyo Peppers','slug'=>'pimientos-lamuyos-verdes',
 'es_excerpt'=>'<p>Pimientos Lamuyo verdes cultivados en Fresno de la Vega, de tamaño grande, carne gruesa y sabor dulce. Una variedad especialmente adecuada para asar, rellenar, cocinar o utilizar en ensaladas.</p>',
 'en_excerpt'=>'<p>Green Lamuyo peppers grown in Fresno de la Vega, with a large size, thick flesh and sweet flavour. A variety particularly well suited to roasting, stuffing, cooking or using in salads.</p>',
 'es_content'=><<<'HTML'
<h2>Un pimiento grande y de carne gruesa</h2>
<p>El Lamuyo es una variedad apreciada por su tamaño, su carne gruesa y consistente y su sabor dulce. Su estructura permite cocinarlo de distintas formas y facilita el pelado después del asado si la receta lo requiere.</p>
<p>Estos pimientos proceden de Fresno de la Vega, en León.</p>
<h2>Cómo aprovecharlos</h2>
<p>Son especialmente adecuados para asar o rellenar, aunque también pueden utilizarse en ensaladas, sofritos, guisos, arroces o como acompañamiento de otros platos.</p>
<p>Consérvalos en el frigorífico y evita el exceso de humedad durante el almacenamiento.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A large pepper with thick flesh</h2>
<p>Lamuyo is a variety valued for its size, thick, firm flesh and sweet flavour. Its structure makes it suitable for different cooking methods and allows the skin to be removed easily after roasting when a recipe calls for it.</p>
<p>These peppers come from Fresno de la Vega, in León.</p>
<h2>How to use them</h2>
<p>They are particularly well suited to roasting or stuffing, although they can also be used in salads, sauces, stews, rice dishes or as a side for other foods.</p>
<p>Keep them refrigerated and avoid excess moisture during storage.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De dónde proceden?</h3>
<p>De Fresno de la Vega, en León.</p>
<h3>¿Para qué preparaciones son especialmente adecuados?</h3>
<p>Por su tamaño y carne gruesa funcionan especialmente bien asados y rellenos.</p>
<h3>¿Se pueden comer en crudo?</h3>
<p>Sí. También pueden utilizarse en ensaladas y otras preparaciones en crudo.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Where do they come from?</h3>
<p>From Fresno de la Vega, in León.</p>
<h3>Which preparations suit them particularly well?</h3>
<p>Their size and thick flesh make them especially suitable for roasting and stuffing.</p>
<h3>Can they be eaten raw?</h3>
<p>Yes. They can also be used in salads and other raw preparations.</p>
HTML,
],
'padron'=>[
 'id'=>12724,'title'=>'Bolsa 300 gr aprox pimientos de Padrón','title_en'=>'Padrón Peppers – Approx. 300 g Bag','slug'=>'bolsa-300-gr-aprox-pimientos-de-padron',
 'es_excerpt'=>'<p>Bolsa de aproximadamente 300 g de pimientos de Padrón, pequeños, verdes y de forma cónica. Algunos pueden resultar picantes, mientras que otros son suaves, una característica propia de este tipo de pimiento.</p>',
 'en_excerpt'=>'<p>Approx. 300 g bag of Padrón peppers, small, green and conical in shape. Some may be hot while others are mild, a characteristic feature of this type of pepper.</p>',
 'es_content'=><<<'HTML'
<h2>Unos pican y otros no</h2>
<p>Los pimientos de Padrón se caracterizan por su pequeño tamaño, su color verde y su forma ligeramente cónica. Su rasgo más conocido es que algunos ejemplares pueden resultar picantes mientras que otros mantienen un sabor suave.</p>
<p>Se presentan en bolsas de aproximadamente 300 gramos, un formato pensado para preparar una ración.</p>
<h2>Cómo prepararlos</h2>
<p>La preparación más habitual consiste en freírlos o saltearlos enteros con aceite y terminar con sal. También pueden cocinarse a la plancha.</p>
<p>Consérvalos en el frigorífico y procura consumirlos mientras mantienen la piel firme y fresca.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Some are hot and some are not</h2>
<p>Padrón peppers are known for their small size, green colour and slightly conical shape. Their best-known characteristic is that some peppers may be hot while others remain mild.</p>
<p>They are supplied in bags of approximately 300 grams, a convenient amount for preparing a serving.</p>
<h2>How to cook them</h2>
<p>The most common preparation is to fry or sauté them whole in oil and finish with salt. They can also be cooked on a griddle.</p>
<p>Keep them refrigerated and use them while the skin remains firm and fresh.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Todos los pimientos pican?</h3>
<p>No. Algunos pueden ser picantes y otros suaves, y no es posible distinguirlo simplemente por su aspecto.</p>
<h3>¿Cuánto contiene la bolsa?</h3>
<p>Aproximadamente 300 gramos.</p>
<h3>¿Cómo se preparan normalmente?</h3>
<p>Fritos o salteados enteros con aceite y sal, aunque también pueden hacerse a la plancha.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Are all the peppers hot?</h3>
<p>No. Some may be hot and others mild, and this cannot be determined simply from their appearance.</p>
<h3>How much is in the bag?</h3>
<p>Approximately 300 grams.</p>
<h3>How are they usually cooked?</h3>
<p>Fried or sautéed whole with oil and salt, although they can also be cooked on a griddle.</p>
HTML,
],
'picaguin'=>[
 'id'=>12727,'title'=>'Salsa muy picante picaguin hot sauce','title_en'=>'Picaguín Very Hot Sauce','slug'=>'salsa-muy-picate-picaguin-hot-sauce',
 'es_excerpt'=>'<p>Salsa muy picante Picaguín Hot Sauce de elaboración artesanal. Preparada con guindilla, vinagre de vino, aceite de oliva y orégano, sin conservantes ni colorantes, en formato de 100 ml.</p>',
 'en_excerpt'=>'<p>Very hot Picaguín Hot Sauce made using traditional methods. Prepared with chilli pepper, wine vinegar, olive oil and oregano, with no preservatives or colourings, in a 100 ml format.</p>',
 'es_content'=><<<'HTML'
<h2>Una salsa para quienes buscan picante intenso</h2>
<p>Picaguín Hot Sauce es una salsa muy picante elaborada artesanalmente en Fresno de la Vega. Su lista de ingredientes es sencilla: guindilla, vinagre de vino, aceite de oliva y orégano.</p>
<p>No contiene conservantes ni colorantes añadidos y se presenta en un envase de 100 ml.</p>
<h2>Cómo aprovecharla</h2>
<p>Por su intensidad conviene añadirla poco a poco y ajustar la cantidad al gusto. Puede utilizarse para dar un punto picante a carnes, verduras, arroces, bocadillos, salsas u otros platos.</p>
<p>Este producto cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor. Para su conservación y consumo una vez abierto, sigue las indicaciones del envase.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A sauce for those who enjoy intense heat</h2>
<p>Picaguín Hot Sauce is a very hot sauce made using traditional methods in Fresno de la Vega. Its ingredient list is simple: chilli pepper, wine vinegar, olive oil and oregano.</p>
<p>It contains no added preservatives or colourings and comes in a 100 ml container.</p>
<h2>How to use it</h2>
<p>Because of its intensity, add it gradually and adjust the amount to taste. It can be used to bring heat to meat, vegetables, rice dishes, sandwiches, sauces and many other foods.</p>
<p>This product carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality labels. Follow the instructions on the container for storage and use after opening.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contiene?</h3>
<p>Guindilla, vinagre de vino, aceite de oliva y orégano.</p>
<h3>¿Contiene conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que no contiene conservantes ni colorantes añadidos.</p>
<h3>¿Qué cantidad contiene el envase?</h3>
<p>100 ml.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What ingredients does it contain?</h3>
<p>Chilli pepper, wine vinegar, olive oil and oregano.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it contains no added preservatives or colourings.</p>
<h3>How much does the container hold?</h3>
<p>100 ml.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha2_fail("TranslatePress table missing: {$table}");

$backup_key='mo_huerta_anamary_batch02_backup_20260831';
if(get_option($backup_key,false)===false){
    $backup=[];
    foreach($products as $key=>$p){
        $post=get_post($p['id']);
        if(!$post) mo_ha2_fail("Backup missing post {$p['id']}");
        $backup[$p['id']]=[
            'post_excerpt'=>$post->post_excerpt,
            'post_content'=>$post->post_content,
            '_en_US_post_title'=>get_post_meta($p['id'],'_en_US_post_title',true),
            '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
            '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
        ];
    }
    add_option($backup_key,$backup,'','no');
    echo "BACKUP created {$backup_key}\n";
} else {
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($products as $key=>$p){
    $post=get_post($p['id']);
    if(!$post) mo_ha2_fail("Missing post {$p['id']}");
    if($post->post_type!=='product' || $post->post_status!=='publish') mo_ha2_fail("Unexpected product state {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha2_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha2_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha2_vendor($post),'Huerta de Ana Mary')===false) mo_ha2_fail("Vendor mismatch {$p['id']}: ".mo_ha2_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha2_fail("Product not instock {$p['id']}");

    $es_full=$p['es_content'].$producer_es.$p['es_faq'];
    $en_full=$p['en_content'].$producer_en.$p['en_faq'];
    $res=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$es_full],true);
    if(is_wp_error($res)) mo_ha2_fail("ES update failed {$p['id']}: ".$res->get_error_message());
    update_post_meta($p['id'],'_en_US_post_title',$p['title_en']);
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$en_full);
    update_post_meta($p['id'],'_en_US_ready','1');
    update_post_meta($p['id'],'_en_US_published','1');

    $pairs=[$p['title']=>$p['title_en']];
    $pairs[trim(wp_strip_all_tags($p['es_excerpt']))]=trim(wp_strip_all_tags($p['en_excerpt']));
    $pairs=array_merge($pairs,mo_ha2_pair_html($es_full,$en_full,$key));
    foreach($pairs as $orig=>$trans) mo_ha2_trp_upsert($table,$orig,$trans);

    clean_post_cache($p['id']);
    $check=get_post($p['id']);
    if(strpos($check->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($check->post_content,'Preguntas frecuentes')===false) mo_ha2_fail("ES verify failed {$p['id']}");
    $en_check=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($en_check,'About La Huerta de Ana Mary')===false || strpos($en_check,'Frequently asked questions')===false) mo_ha2_fail("EN verify failed {$p['id']}");
    if(trim((string)get_post_meta($p['id'],'_en_US_post_excerpt',true))==='') mo_ha2_fail("EN excerpt missing {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}

echo "DONE huerta_batch02_products=".count($products)."\n";
