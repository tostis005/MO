<?php
/** La Huerta de Ana Mary product copy batch 05 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha5_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha5_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha5_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha5_pair_html($es_html,$en_html,$label){
    $es=mo_ha5_segments($es_html); $en=mo_ha5_segments($en_html);
    if(count($es)!==count($en)) mo_ha5_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha5_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}
function mo_ha5_trp_upsert($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha5_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha5_fail("TranslatePress insert failed: {$original}");
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
'repollo'=>[
 'id'=>12761,'title'=>'Repollo','title_en'=>'Cabbage','slug'=>'repollo',
 'es_excerpt'=>'<p>Repollo fresco vendido por unidad, de hojas verdes, carnosas y con nervaduras marcadas. Cada pieza puede oscilar aproximadamente entre 0,9 y 2,2 kg. Una verdura muy versátil para cocer, guisar, saltear o utilizar en ensaladas.</p>',
 'en_excerpt'=>'<p>Fresh cabbage sold by the unit, with fleshy green leaves and pronounced veins. Each head may weigh approximately between 0.9 and 2.2 kg. A versatile vegetable for boiling, stewing, sautéing or using in salads.</p>',
 'es_content'=><<<'HTML'
<h2>Un repollo para platos de cuchara, salteados y ensaladas</h2>
<p>El repollo forma una cabeza compacta de hojas verdes, carnosas y con nervaduras marcadas. Su textura es firme en crudo y se vuelve más tierna a medida que se cocina.</p>
<p>Se vende por unidades y el peso de cada pieza puede oscilar aproximadamente entre 0,9 y 2,2 kg.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puede cocerse, guisarse, saltearse o incorporarse a sopas y platos de cuchara. También puede cortarse fino y utilizarse en ensaladas y otras preparaciones en crudo.</p>
<p>Consérvalo en el frigorífico y, una vez cortado, protégelo bien para evitar que se reseque.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A cabbage for stews, sautés and salads</h2>
<p>Cabbage forms a compact head of fleshy green leaves with pronounced veins. Its texture is firm when raw and becomes more tender as it cooks.</p>
<p>It is sold by the unit and each head may weigh approximately between 0.9 and 2.2 kg.</p>
<h2>How to use it</h2>
<p>It can be boiled, stewed, sautéed or added to soups and slow-cooked dishes. It can also be finely sliced and used in salads and other raw preparations.</p>
<p>Keep it refrigerated and, once cut, wrap or cover it well to prevent it from drying out.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo se vende?</h3>
<p>Se vende por unidades.</p>
<h3>¿Cuánto pesa cada pieza?</h3>
<p>El peso puede oscilar aproximadamente entre 0,9 y 2,2 kg.</p>
<h3>¿Se puede comer en crudo?</h3>
<p>Sí. Puede cortarse fino y utilizarse en ensaladas y otras preparaciones en crudo.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How is it sold?</h3>
<p>It is sold by the unit.</p>
<h3>How much does each cabbage weigh?</h3>
<p>Each head may weigh approximately between 0.9 and 2.2 kg.</p>
<h3>Can it be eaten raw?</h3>
<p>Yes. It can be finely sliced and used in salads and other raw preparations.</p>
HTML,
],
'leekjam'=>[
 'id'=>12764,'title'=>'Mermelada artesana de puerro 250 ml','title_en'=>'Artisan Leek Jam 250 ml','slug'=>'mermelada-artesana-de-puerro-250-ml',
 'es_excerpt'=>'<p>Mermelada artesana de puerro en tarro de cristal de 250 ml, elaborada únicamente con puerro y azúcar. Sin conservantes ni colorantes, resulta adecuada para acompañar carnes, patés, aperitivos y tostas.</p>',
 'en_excerpt'=>'<p>Artisan leek jam in a 250 ml glass jar, made only with leeks and sugar. Free from preservatives and colourings, it works well with meat, pâtés, appetisers and toast.</p>',
 'es_content'=><<<'HTML'
<h2>Una mermelada salada de puerro con solo dos ingredientes</h2>
<p>Esta mermelada artesana se elabora con puerro y azúcar y se presenta en un tarro de cristal de 250 ml. No contiene conservantes ni colorantes añadidos.</p>
<p>Se produce en Fresno de la Vega y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede utilizarse como acompañamiento de carnes y patés, servirse en aperitivos o extenderse sobre pan y tostas. Su perfil dulce permite utilizarla en pequeñas cantidades para aportar contraste a preparaciones saladas.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A savoury leek jam made with just two ingredients</h2>
<p>This artisan jam is made with leeks and sugar and comes in a 250 ml glass jar. It contains no added preservatives or colourings.</p>
<p>It is produced in Fresno de la Vega and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Use it alongside meat and pâtés, serve it with appetisers or spread it on bread and toast. Its sweetness makes it useful in small amounts to add contrast to savoury dishes.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contiene?</h3>
<p>Puerro y azúcar.</p>
<h3>¿Lleva conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
<h3>¿Qué formato tiene?</h3>
<p>Se presenta en un tarro de cristal de 250 ml.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Leeks and sugar.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
<h3>What is the format?</h3>
<p>It comes in a 250 ml glass jar.</p>
HTML,
],
'pepperjam'=>[
 'id'=>12767,'title'=>'Mermelada artesana de pimiento 250 ml','title_en'=>'Artisan Pepper Jam 250 ml','slug'=>'mermelada-artesana-de-pimiento-250-ml',
 'es_excerpt'=>'<p>Mermelada artesana de pimiento en tarro de cristal de 250 ml, elaborada únicamente con pimiento rojo y azúcar. Sin conservantes ni colorantes, es una opción práctica para aperitivos, carnes, quesos y tostas.</p>',
 'en_excerpt'=>'<p>Artisan pepper jam in a 250 ml glass jar, made only with red pepper and sugar. Free from preservatives and colourings, it is a practical choice for appetisers, meat, cheese and toast.</p>',
 'es_content'=><<<'HTML'
<h2>Mermelada de pimiento rojo de elaboración artesanal</h2>
<p>Esta confitura se prepara únicamente con pimiento rojo y azúcar y se presenta en un tarro de cristal de 250 ml. No contiene conservantes ni colorantes añadidos.</p>
<p>Se produce en Fresno de la Vega y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede servirse en aperitivos, utilizarse como acompañamiento de carnes o combinarse con queso sobre pan, tostas y pequeños bocados. Su sabor dulce permite crear contrastes sencillos con ingredientes salados.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Traditionally prepared red pepper jam</h2>
<p>This preserve is made only with red pepper and sugar and comes in a 250 ml glass jar. It contains no added preservatives or colourings.</p>
<p>It is produced in Fresno de la Vega and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Serve it with appetisers, use it alongside meat or combine it with cheese on bread, toast and small bites. Its sweetness creates an easy contrast with savoury ingredients.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contiene?</h3>
<p>Pimiento rojo y azúcar.</p>
<h3>¿Lleva conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
<h3>¿Qué formato tiene?</h3>
<p>Se presenta en un tarro de cristal de 250 ml.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Red pepper and sugar.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
<h3>What is the format?</h3>
<p>It comes in a 250 ml glass jar.</p>
HTML,
],
'tomatojam'=>[
 'id'=>12770,'title'=>'Mermelada artesana de tomate 250 ml','title_en'=>'Artisan Tomato Jam 250 ml','slug'=>'mermelada-artesana-de-tomate-250-ml',
 'es_excerpt'=>'<p>Mermelada artesana de tomate en tarro de cristal de 250 ml, elaborada únicamente con tomate y azúcar. Sin conservantes ni colorantes, puede utilizarse en aperitivos, con carnes y patés o sobre pan y tostas.</p>',
 'en_excerpt'=>'<p>Artisan tomato jam in a 250 ml glass jar, made only with tomato and sugar. Free from preservatives and colourings, it can be served with appetisers, meat and pâtés or spread on bread and toast.</p>',
 'es_content'=><<<'HTML'
<h2>Mermelada de tomate con una receta sencilla</h2>
<p>Esta mermelada artesana se elabora únicamente con tomate y azúcar y se presenta en un tarro de cristal de 250 ml. No contiene conservantes ni colorantes añadidos.</p>
<p>Se produce en Fresno de la Vega y cuenta con los sellos de Alimentos Artesanales de Castilla y León y Tierra de Sabor.</p>
<h2>Cómo aprovecharla</h2>
<p>Puede utilizarse en aperitivos, como acompañamiento de carnes y patés o extendida sobre pan y tostas. También permite aportar un punto dulce a combinaciones saladas sin necesidad de añadir otros ingredientes.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Tomato jam with a simple recipe</h2>
<p>This artisan jam is made only with tomato and sugar and comes in a 250 ml glass jar. It contains no added preservatives or colourings.</p>
<p>It is produced in Fresno de la Vega and carries the Alimentos Artesanales de Castilla y León and Tierra de Sabor quality marks.</p>
<h2>How to use it</h2>
<p>Use it with appetisers, serve it alongside meat and pâtés or spread it on bread and toast. It can also add a sweet note to savoury combinations without the need for extra ingredients.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contiene?</h3>
<p>Tomate y azúcar.</p>
<h3>¿Lleva conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elabora sin conservantes ni colorantes.</p>
<h3>¿Qué formato tiene?</h3>
<p>Se presenta en un tarro de cristal de 250 ml.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Tomato and sugar.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
<h3>What is the format?</h3>
<p>It comes in a 250 ml glass jar.</p>
HTML,
],
'guindillas'=>[
 'id'=>12773,'title'=>'Guindillas en vinagre 720 ml','title_en'=>'Pickled Guindilla Peppers 720 ml','slug'=>'guindillas-en-vinagre-720-ml',
 'es_excerpt'=>'<p>Guindillas verdes en vinagre de elaboración artesanal, presentadas en tarro de cristal de 720 ml. Elaboradas con guindillas, agua, vinagre y sal, sin conservantes ni colorantes. Peso neto 600 g y peso escurrido 400 g.</p>',
 'en_excerpt'=>'<p>Traditionally prepared green guindilla peppers in vinegar, packed in a 720 ml glass jar. Made with guindilla peppers, water, vinegar and salt, without preservatives or colourings. Net weight 600 g and drained weight 400 g.</p>',
 'es_content'=><<<'HTML'
<h2>Guindillas verdes en vinagre listas para servir</h2>
<p>Estas guindillas verdes se conservan en vinagre y se presentan en un tarro de cristal de 720 ml. Sus ingredientes son guindillas, agua, vinagre y sal, sin conservantes ni colorantes añadidos.</p>
<p>El formato tiene un peso neto de 600 g y un peso escurrido de 400 g. El producto cuenta con los sellos de Tierra de Sabor y Alimentos Artesanales de Castilla y León.</p>
<h2>Cómo aprovecharlas</h2>
<p>Pueden servirse directamente como aperitivo o acompañamiento y funcionan especialmente bien junto a legumbres, ensaladas y platos en los que se busque un punto ácido y vegetal.</p>
<p>Antes de abrir, conserva el tarro siguiendo las indicaciones del envase. Una vez abierto, mantenlo refrigerado y respeta las instrucciones de conservación de la etiqueta.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Green guindilla peppers in vinegar, ready to serve</h2>
<p>These green guindilla peppers are preserved in vinegar and packed in a 720 ml glass jar. The ingredients are guindilla peppers, water, vinegar and salt, with no added preservatives or colourings.</p>
<p>The jar has a net weight of 600 g and a drained weight of 400 g. The product carries the Tierra de Sabor and Alimentos Artesanales de Castilla y León quality marks.</p>
<h2>How to use them</h2>
<p>Serve them directly as an appetiser or accompaniment. They work particularly well with pulses, salads and dishes where a fresh, vinegary note is wanted.</p>
<p>Before opening, store the jar according to the instructions on the packaging. Once opened, keep refrigerated and follow the storage directions on the label.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué ingredientes contienen?</h3>
<p>Guindillas, agua, vinagre y sal.</p>
<h3>¿Qué cantidad incluye el tarro?</h3>
<p>El tarro es de 720 ml, con 600 g de peso neto y 400 g de peso escurrido.</p>
<h3>¿Llevan conservantes o colorantes?</h3>
<p>No. La ficha del producto indica que se elaboran sin conservantes ni colorantes.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are the ingredients?</h3>
<p>Guindilla peppers, water, vinegar and salt.</p>
<h3>How much does the jar contain?</h3>
<p>The jar is 720 ml, with a net weight of 600 g and a drained weight of 400 g.</p>
<h3>Does it contain preservatives or colourings?</h3>
<p>No. The product information states that it is made without preservatives or colourings.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha5_fail("TranslatePress table missing: {$table}");

$backup=[]; $pairs=[];
foreach($products as $key=>$p){
    $post=get_post($p['id']);
    if(!$post || $post->post_type!=='product') mo_ha5_fail("Missing product {$p['id']}");
    if($post->post_status!=='publish') mo_ha5_fail("Not published {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha5_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha5_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha5_vendor($post),'Huerta de Ana Mary')===false) mo_ha5_fail("Vendor mismatch {$p['id']}: ".mo_ha5_vendor($post));
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha5_fail("Not instock {$p['id']}");
    $backup[$p['id']]=[
      'post_excerpt'=>$post->post_excerpt,'post_content'=>$post->post_content,
      '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
      '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
    ];
    $full_es=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];
    $full_en=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];
    $products[$key]['full_es']=$full_es; $products[$key]['full_en']=$full_en;
    $pairs[$p['title']]=$p['title_en'];
    $pairs=array_merge($pairs,mo_ha5_pair_html($p['es_excerpt'],$p['en_excerpt'],$key.' excerpt'));
    $pairs=array_merge($pairs,mo_ha5_pair_html($full_es,$full_en,$key.' content'));
}
$backup_key='mo_huerta_anamary_batch05_backup_20260831';
if(get_option($backup_key,false)===false){ add_option($backup_key,$backup,'','no'); echo "BACKUP created {$backup_key}\n"; }
else { echo "BACKUP exists {$backup_key}\n"; }

foreach($products as $p){
    $r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);
    if(is_wp_error($r)) mo_ha5_fail("Update failed {$p['id']}: ".$r->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);
}
foreach($pairs as $original=>$translated){ mo_ha5_trp_upsert($table,$original,$translated); }

foreach($products as $p){
    $post=get_post($p['id']);
    $en_short=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);
    $en_long=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($post->post_content,'Preguntas frecuentes')===false) mo_ha5_fail("Spanish verification failed {$p['id']}");
    if(strpos($en_long,'About La Huerta de Ana Mary')===false || strpos($en_long,'Frequently asked questions')===false) mo_ha5_fail("English verification failed {$p['id']}");
    if(trim(wp_strip_all_tags($en_short))==='') mo_ha5_fail("English excerpt empty {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}
echo 'DONE huerta_batch05_products='.count($products).' translations='.count($pairs)."\n";
