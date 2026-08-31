<?php
/** La Huerta de Ana Mary product copy batch 11 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha11_fail($m){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($m); }
    throw new Exception($m);
}
function mo_ha11_vendor($p){
    $u = get_userdata((int)$p->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha11_segments($html){
    $out=[];
    if (preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)) {
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $out[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $out;
}
function mo_ha11_pair($es,$en,$label){
    $a=mo_ha11_segments($es); $b=mo_ha11_segments($en);
    if(count($a)!==count($b)) mo_ha11_fail("Segment mismatch {$label}: ES=".count($a)." EN=".count($b));
    $pairs=[];
    foreach($a as $i=>$row){
        if($row['tag']!==$b[$i]['tag']) mo_ha11_fail("Tag mismatch {$label} at {$i}");
        $pairs[$row['text']]=$b[$i]['text'];
    }
    return $pairs;
}
function mo_ha11_trp($table,$original,$translated){
    global $wpdb;
    $id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$original));
    if($id){
        $ok=$wpdb->update($table,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
        if($ok===false) mo_ha11_fail("TranslatePress update failed: {$original}");
    } else {
        $ok=$wpdb->insert($table,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);
        if($ok===false) mo_ha11_fail("TranslatePress insert failed: {$original}");
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
[
 'id'=>13569,
 'title'=>'Pimiento lamuyo rojo',
 'title_en'=>'Red Lamuyo Pepper',
 'slug'=>'pimiento-lamuyo-rojo',
 'es_excerpt'=>'<p>Pimiento lamuyo rojo fresco, de gran tamaño, carne gruesa y sabor dulce. Una variedad especialmente adecuada para asar, rellenar, preparar en ensalada o utilizar como guarnición.</p>',
 'en_excerpt'=>'<p>Fresh red Lamuyo pepper, large in size with thick flesh and a sweet flavour. A variety particularly well suited to roasting, stuffing, salads and side dishes.</p>',
 'es_content'=><<<'HTML'
<h2>Pimiento lamuyo rojo de carne gruesa</h2>
<p>El pimiento lamuyo rojo se caracteriza por su tamaño grande, su color rojo vivo y una carne gruesa y consistente. Su sabor es dulce y presenta una acidez suave, por lo que funciona bien tanto en preparaciones en crudo como cocinadas.</p>
<h2>Cómo aprovecharlo</h2>
<p>Es especialmente adecuado para asar. También puede utilizarse relleno, en ensaladas, salteados, guarniciones o como parte de platos de verduras.</p>
<p>Antes de consumirlo, lávalo bien y retira el pedúnculo y las semillas según la preparación que vayas a realizar.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Thick-fleshed red Lamuyo pepper</h2>
<p>Red Lamuyo peppers are known for their large size, bright red colour and thick, firm flesh. Their flavour is sweet with mild acidity, making them suitable for both raw and cooked preparations.</p>
<h2>How to use it</h2>
<p>It is particularly well suited to roasting. It can also be stuffed, used in salads, sautéed, served as a side dish or added to vegetable-based recipes.</p>
<p>Wash thoroughly before use and remove the stalk and seeds as required for the recipe.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo es el pimiento lamuyo rojo?</h3><p>Es un pimiento de gran tamaño, color rojo vivo y carne gruesa y consistente.</p>
<h3>¿Es adecuado para asar?</h3><p>Sí. Es una de las preparaciones para las que esta variedad resulta especialmente adecuada.</p>
<h3>¿Cómo puede utilizarse además de asado?</h3><p>Puede prepararse relleno, en ensaladas, salteados, guarniciones y otros platos de verduras.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is a red Lamuyo pepper like?</h3><p>It is a large pepper with a bright red colour and thick, firm flesh.</p>
<h3>Is it suitable for roasting?</h3><p>Yes. Roasting is one of the preparations for which this variety is particularly well suited.</p>
<h3>How else can it be used?</h3><p>It can be stuffed, used in salads, sautéed, served as a side dish or added to other vegetable dishes.</p>
HTML,
],
[
 'id'=>13765,
 'title'=>'Pimientos morrones 10 kg especiales para asar',
 'title_en'=>'10 kg Bell Peppers for Roasting',
 'slug'=>'pimientos-morrones-10-kg-especiales-para-asar',
 'es_excerpt'=>'<p>Formato de 10 kg de pimientos morrones frescos, pensado especialmente para asar. Una cantidad adecuada para preparar varias bandejas, cocinar para grupos o elaborar distintas recetas a base de pimiento asado.</p>',
 'en_excerpt'=>'<p>10 kg format of fresh bell peppers, intended especially for roasting. A practical quantity for several trays, cooking for groups or preparing different roasted-pepper recipes.</p>',
 'es_content'=><<<'HTML'
<h2>10 kg de pimientos morrones para asar</h2>
<p>Este formato incluye 10 kg de pimientos morrones y está pensado especialmente para preparaciones en las que se necesita una cantidad mayor de pimiento para asar.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden asarse enteros en horno o parrilla y, una vez cocinados, pelarse y utilizarse como guarnición, en ensaladas, tostas, rellenos o como base para otras elaboraciones.</p>
<p>Antes de cocinarlos, lávalos bien. Si vas a asarlos enteros, puedes retirar el pedúnculo y las semillas antes o después de la cocción según el método que prefieras.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>10 kg of bell peppers for roasting</h2>
<p>This format contains 10 kg of bell peppers and is intended especially for preparations where a larger quantity of peppers is needed for roasting.</p>
<h2>How to use them</h2>
<p>They can be roasted whole in the oven or on a grill and, once cooked, peeled and used as a side dish, in salads, on toast, in fillings or as the base for other recipes.</p>
<p>Wash them thoroughly before cooking. If roasting them whole, the stalk and seeds can be removed either before or after cooking depending on the method you prefer.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué cantidad incluye?</h3><p>Este formato incluye 10 kg de pimientos morrones.</p>
<h3>¿Para qué preparación está pensado?</h3><p>Está pensado especialmente para asar.</p>
<h3>¿Cómo pueden utilizarse después de asarlos?</h3><p>Como guarnición, en ensaladas, tostas, rellenos o como base para otras elaboraciones.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How much is included?</h3><p>This format contains 10 kg of bell peppers.</p>
<h3>What preparation is it intended for?</h3><p>It is intended especially for roasting.</p>
<h3>How can the peppers be used after roasting?</h3><p>As a side dish, in salads, on toast, in fillings or as the base for other recipes.</p>
HTML,
],
[
 'id'=>13768,
 'title'=>'Pimientos morrones',
 'title_en'=>'Bell Peppers',
 'slug'=>'pimientos-morrones',
 'es_excerpt'=>'<p>Pimientos morrones frescos cultivados en Fresno de la Vega. Una hortaliza especialmente adecuada para asar y muy versátil también para rellenos, ensaladas, guarniciones y otras preparaciones de cocina.</p>',
 'en_excerpt'=>'<p>Fresh bell peppers grown in Fresno de la Vega. Particularly well suited to roasting and also versatile for stuffing, salads, side dishes and other everyday recipes.</p>',
 'es_content'=><<<'HTML'
<h2>Pimientos morrones de Fresno de la Vega</h2>
<p>El pimiento morrón forma parte de la tradición hortícola de Fresno de la Vega y es una referencia especialmente apreciada para preparaciones en las que se busca un pimiento adecuado para asar.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden asarse enteros en horno o parrilla y servirse después pelados como guarnición o parte de ensaladas y tostas. También pueden utilizarse rellenos, salteados o incorporados a distintos platos de verduras.</p>
<p>Antes de utilizarlos, lávalos bien y retira el pedúnculo y las semillas según la preparación que vayas a realizar.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Bell peppers from Fresno de la Vega</h2>
<p>Bell peppers are part of the horticultural tradition of Fresno de la Vega and are particularly appreciated for recipes where a pepper suitable for roasting is wanted.</p>
<h2>How to use them</h2>
<p>They can be roasted whole in the oven or on a grill, then peeled and served as a side dish or used in salads and on toast. They can also be stuffed, sautéed or added to a range of vegetable dishes.</p>
<p>Wash them thoroughly before use and remove the stalk and seeds as required for the recipe.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Dónde se cultivan?</h3><p>Se cultivan en Fresno de la Vega, en la provincia de León.</p>
<h3>¿Son adecuados para asar?</h3><p>Sí. El asado es una de las preparaciones más habituales para este tipo de pimiento.</p>
<h3>¿Qué otros usos tienen?</h3><p>Pueden utilizarse rellenos, en ensaladas, salteados, guarniciones y otras preparaciones con verduras.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Where are they grown?</h3><p>They are grown in Fresno de la Vega, in the province of León.</p>
<h3>Are they suitable for roasting?</h3><p>Yes. Roasting is one of the most common preparations for this type of pepper.</p>
<h3>What other uses do they have?</h3><p>They can be stuffed, used in salads, sautéed, served as a side dish or added to other vegetable recipes.</p>
HTML,
],
];

$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table) mo_ha11_fail('TranslatePress table missing');
$backup=[]; $pairs=[];
foreach($products as $i=>$p){
    $post=get_post($p['id']);
    if(!$post || $post->post_type!=='product' || $post->post_status!=='publish') mo_ha11_fail("Missing/unpublished {$p['id']}");
    if($post->post_title!==$p['title']) mo_ha11_fail("Title mismatch {$p['id']}: {$post->post_title}");
    if($post->post_name!==$p['slug']) mo_ha11_fail("Slug mismatch {$p['id']}: {$post->post_name}");
    if(stripos(mo_ha11_vendor($post),'Huerta de Ana Mary')===false) mo_ha11_fail("Vendor mismatch {$p['id']}");
    if(get_post_meta($p['id'],'_stock_status',true)!=='instock') mo_ha11_fail("Not instock {$p['id']}");
    $backup[$p['id']]=[
        'post_excerpt'=>$post->post_excerpt,
        'post_content'=>$post->post_content,
        '_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),
        '_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true),
    ];
    $products[$i]['full_es']=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];
    $products[$i]['full_en']=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];
    $pairs[$p['title']]=$p['title_en'];
    $pairs=array_merge($pairs,mo_ha11_pair($p['es_excerpt'],$p['en_excerpt'],"excerpt {$p['id']}"));
    $pairs=array_merge($pairs,mo_ha11_pair($products[$i]['full_es'],$products[$i]['full_en'],"content {$p['id']}"));
}

$key='mo_huerta_anamary_batch11_backup_20260831';
if(get_option($key,false)===false){
    add_option($key,$backup,'','no');
    echo "BACKUP created {$key}\n";
} else {
    echo "BACKUP exists {$key}\n";
}

foreach($products as $p){
    $r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);
    if(is_wp_error($r)) mo_ha11_fail("Update failed {$p['id']}: ".$r->get_error_message());
    update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);
    update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);
}
foreach($pairs as $o=>$t) mo_ha11_trp($table,$o,$t);

foreach($products as $p){
    $post=get_post($p['id']);
    $en=(string)get_post_meta($p['id'],'_en_US_post_content',true);
    $ens=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);
    if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false || strpos($post->post_content,'Preguntas frecuentes')===false) mo_ha11_fail("ES verify failed {$p['id']}");
    if(strpos($en,'About La Huerta de Ana Mary')===false || strpos($en,'Frequently asked questions')===false) mo_ha11_fail("EN verify failed {$p['id']}");
    if(trim(wp_strip_all_tags($ens))==='') mo_ha11_fail("EN excerpt empty {$p['id']}");
    echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";
}
echo "DONE huerta_batch11_products=".count($products)." translations=".count($pairs)."\n";
