<?php
/** La Huerta de Ana Mary product copy batch 01 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_ha1_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_ha1_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_ha1_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_ha1_pair_html($es_html,$en_html,$label){
    $es=mo_ha1_segments($es_html); $en=mo_ha1_segments($en_html);
    if(count($es)!==count($en)) mo_ha1_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_ha1_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}

$producer_es=<<<'HTML'
<h2>Sobre La Huerta de Ana Mary</h2>
<p>La Huerta de Ana Mary trabaja desde Fresno de la Vega, en la provincia de León, una localidad situada en la Vega del Esla y especialmente vinculada al cultivo de verduras y hortalizas. La fertilidad de esta vega ha hecho que la agricultura forme parte de la vida del pueblo desde hace generaciones.</p>
<p>El proyecto está gestionado por Arsenio Pérez Crespo y cuenta como principal proveedor de hortalizas con Antonio Luis Morán García, agricultor de Fresno de la Vega y miembro de una familia dedicada al campo desde hace más de tres generaciones.</p>
<p>Según explica el propio productor, sus hortalizas se cultivan en parcelas de Fresno de la Vega combinando la experiencia agrícola de la zona con técnicas actuales orientadas a un cultivo sostenible. La recolección se realiza vinculada a los pedidos, evitando mantener las verduras almacenadas durante largos periodos en cámaras frigoríficas.</p>
<p>El objetivo es reducir al máximo el tiempo entre la huerta y el consumidor y conservar el carácter de un producto fresco, de temporada y directamente ligado a la Vega del Esla.</p>
HTML;
$producer_en=<<<'HTML'
<h2>About La Huerta de Ana Mary</h2>
<p>La Huerta de Ana Mary operates from Fresno de la Vega, in the province of León, a village in the fertile Vega del Esla with a long tradition of growing vegetables. Agriculture has been part of local life here for generations.</p>
<p>The project is managed by Arsenio Pérez Crespo and works with Antonio Luis Morán García as its main vegetable supplier, a farmer from Fresno de la Vega whose family has been involved in agriculture for more than three generations.</p>
<p>According to the producer, its vegetables are grown on plots in Fresno de la Vega, combining local farming experience with modern techniques aimed at sustainable cultivation. Harvesting is linked to orders, avoiding long periods of storage in refrigerated chambers.</p>
<p>The aim is to keep the time between field and customer as short as possible and preserve the character of fresh, seasonal produce closely connected to the Vega del Esla.</p>
HTML;

$products=[
'patatas'=>[
 'id'=>12699,'title'=>'Patatas blancas','title_en'=>'White Kennebec Potatoes','slug'=>'patatas-blancas','old_marker'=>'patatas blancas variedad kennebec',
 'es_excerpt'=>'<p>Patatas blancas variedad Kennebec, cultivadas en Fresno de la Vega y vendidas por kilo. Se presentan sin lavar, conservando el aspecto terroso característico de una patata recién salida del campo, con piel gruesa y carne blanca.</p>',
 'en_excerpt'=>'<p>White Kennebec potatoes grown in Fresno de la Vega and sold by the kilogram. They are supplied unwashed, retaining the earthy appearance of potatoes straight from the field, with thick skin and white flesh.</p>',
 'es_content'=><<<'HTML'
<h2>Patata Kennebec de Fresno de la Vega</h2>
<p>La Kennebec es una patata blanca muy versátil en cocina. Estas patatas se presentan sin lavar, por lo que mantienen tierra en la piel y un aspecto más rústico, propio de una patata que no ha pasado por procesos de lavado antes de su venta.</p>
<p>Su piel es gruesa y su carne blanca. Es una variedad que se adapta bien a muchas preparaciones domésticas y permite utilizar una misma patata para distintos platos.</p>
<h2>Cómo aprovecharla</h2>
<p>Puedes utilizarla para cocer, asar, freír, preparar purés o incorporar a guisos y platos de cuchara. Antes de cocinarla, lávala bien bajo el grifo para retirar los restos de tierra.</p>
<p>Para conservarla, guárdala en un lugar fresco, seco, oscuro y ventilado, alejada de fuentes de calor y de la luz directa.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Kennebec potatoes from Fresno de la Vega</h2>
<p>Kennebec is a versatile white potato for everyday cooking. These potatoes are supplied unwashed, so some soil remains on the skin and they keep the more rustic appearance of potatoes that have not been washed before sale.</p>
<p>They have thick skin and white flesh. The variety works well in a wide range of home-cooked dishes, making it useful for several different preparations.</p>
<h2>How to use them</h2>
<p>Use them for boiling, roasting, frying, mashing or adding to stews and slow-cooked dishes. Wash them thoroughly under running water before cooking to remove any remaining soil.</p>
<p>Store them in a cool, dry, dark and well-ventilated place, away from heat sources and direct light.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué variedad de patata es?</h3>
<p>Es patata blanca de la variedad Kennebec.</p>
<h3>¿Se entrega lavada?</h3>
<p>No. Se presenta sin lavar, por lo que puede conservar restos de tierra en la piel.</p>
<h3>¿Cómo se conserva mejor?</h3>
<p>En un lugar fresco, seco, oscuro y ventilado, evitando la luz directa y las temperaturas altas.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which potato variety is it?</h3>
<p>It is a white Kennebec potato.</p>
<h3>Are the potatoes supplied washed?</h3>
<p>No. They are supplied unwashed, so some soil may remain on the skin.</p>
<h3>How should they be stored?</h3>
<p>Keep them in a cool, dry, dark and well-ventilated place, away from direct light and high temperatures.</p>
HTML,
],
'calabacin'=>[
 'id'=>12702,'title'=>'Calabacín','title_en'=>'Courgette','slug'=>'calabacin','old_marker'=>'planta rastrera',
 'es_excerpt'=>'<p>Calabacín fresco cultivado en Fresno de la Vega. Una hortaliza de sabor suave y textura tierna, fácil de incorporar a platos de diario y adecuada para plancha, salteados, cremas, horno o rellenos.</p>',
 'en_excerpt'=>'<p>Fresh courgette grown in Fresno de la Vega. A mild, tender vegetable that is easy to use in everyday cooking and suitable for griddling, sautéing, soups, roasting or stuffing.</p>',
 'es_content'=><<<'HTML'
<h2>Una hortaliza suave y muy versátil</h2>
<p>El calabacín destaca por su textura tierna y por un sabor suave que combina fácilmente con otras verduras, carnes, pescados, arroces o pasta. Puede cocinarse de muchas formas sin necesidad de preparaciones complicadas.</p>
<p>La piel es comestible, por lo que basta con lavarlo bien antes de utilizarlo. Según la receta, puede cortarse en rodajas, dados, tiras o abrirse para rellenar.</p>
<h2>Cómo aprovecharlo</h2>
<p>A la plancha o salteado necesita una cocción relativamente corta. También funciona bien en cremas, tortillas, pistos, gratinados, verduras al horno o como base para rellenos.</p>
<p>Guárdalo en el frigorífico y evita mantenerlo húmedo durante el almacenamiento. Al ser una hortaliza fresca, conviene consumirlo en los días posteriores a recibirlo.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A mild and highly versatile vegetable</h2>
<p>Courgette has a tender texture and a mild flavour that pairs easily with other vegetables, meat, fish, rice or pasta. It can be cooked in many different ways without complicated preparation.</p>
<p>The skin is edible, so it only needs to be washed thoroughly before use. Depending on the recipe, it can be sliced, diced, cut into strips or opened for stuffing.</p>
<h2>How to use it</h2>
<p>It needs relatively little cooking time on a griddle or in a sauté pan. It also works well in soups, omelettes, vegetable stews, gratins, roasted vegetable dishes or stuffed preparations.</p>
<p>Keep it refrigerated and avoid excess moisture during storage. As a fresh vegetable, it is best eaten within the days following delivery.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Hay que pelar el calabacín?</h3>
<p>No es necesario. La piel es comestible; basta con lavarla bien antes de cocinar.</p>
<h3>¿Cómo se puede preparar?</h3>
<p>Puede cocinarse a la plancha, salteado, al horno, en crema, tortilla, pisto o relleno.</p>
<h3>¿Cómo se conserva?</h3>
<p>En el frigorífico, procurando mantenerlo seco y consumirlo mientras conserva su frescura.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Does courgette need to be peeled?</h3>
<p>No. The skin is edible; simply wash it thoroughly before cooking.</p>
<h3>How can it be prepared?</h3>
<p>It can be griddled, sautéed, roasted, made into soup, omelettes, vegetable stews or stuffed dishes.</p>
<h3>How should it be stored?</h3>
<p>Keep it refrigerated, avoid excess moisture and use it while it is still fresh.</p>
HTML,
],
'brocoli'=>[
 'id'=>12706,'title'=>'Brócoli','title_en'=>'Broccoli','slug'=>'brocoli','old_marker'=>'superfoods',
 'es_excerpt'=>'<p>Brócoli fresco cultivado en Fresno de la Vega. Sus ramilletes compactos y su sabor vegetal permiten prepararlo de forma sencilla al vapor, cocido, salteado, al horno o incorporado a arroces, pasta y otros platos.</p>',
 'en_excerpt'=>'<p>Fresh broccoli grown in Fresno de la Vega. Its compact florets and distinctive vegetable flavour make it easy to steam, boil, sauté, roast or add to rice, pasta and many other dishes.</p>',
 'es_content'=><<<'HTML'
<h2>Brócoli fresco para preparaciones sencillas</h2>
<p>El brócoli pertenece a la familia de las crucíferas y se aprovecha principalmente por sus ramilletes, aunque el tallo también puede cocinarse una vez retirada la parte exterior más dura.</p>
<p>Es una hortaliza que admite cocciones cortas y combina bien con aceite de oliva, ajo, especias, quesos, pasta, arroz y otras verduras.</p>
<h2>Cómo aprovecharlo</h2>
<p>Puedes cocinarlo al vapor o hervido procurando no prolongar demasiado la cocción si quieres mantener una textura más firme. También puede saltearse, gratinarse o asarse en el horno.</p>
<p>Consérvalo en el frigorífico y procura consumirlo mientras mantiene los ramilletes firmes y frescos.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Fresh broccoli for simple cooking</h2>
<p>Broccoli belongs to the cruciferous vegetable family and is mainly used for its florets, although the stalk can also be cooked once the tougher outer layer has been removed.</p>
<p>It works well with relatively short cooking times and pairs easily with olive oil, garlic, spices, cheese, pasta, rice and other vegetables.</p>
<h2>How to use it</h2>
<p>Steam or boil it without overcooking if you prefer a firmer texture. It can also be sautéed, baked with a topping or roasted in the oven.</p>
<p>Keep it refrigerated and use it while the florets remain firm and fresh.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Se puede comer el tallo del brócoli?</h3>
<p>Sí. Retirando la parte exterior más dura, el interior del tallo puede cocinarse junto con los ramilletes.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Al vapor, hervido, salteado, gratinado o asado en el horno.</p>
<h3>¿Cómo se conserva?</h3>
<p>En el frigorífico, procurando consumirlo mientras los ramilletes mantienen una textura firme.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Can the broccoli stalk be eaten?</h3>
<p>Yes. Once the tougher outer layer is removed, the inside of the stalk can be cooked together with the florets.</p>
<h3>How can it be cooked?</h3>
<p>It can be steamed, boiled, sautéed, baked or roasted.</p>
<h3>How should it be stored?</h3>
<p>Keep it refrigerated and use it while the florets remain firm.</p>
HTML,
],
'patatas20'=>[
 'id'=>12709,'title'=>'20 Kg de patatas blancas variedad kennebec','title_en'=>'20 kg White Kennebec Potatoes','slug'=>'20-kg-de-patatas-blancas-variedad-kennebec','old_marker'=>'20 kg DE PATATAS BLANCAS',
 'es_excerpt'=>'<p>Formato de 20 kg de patata blanca variedad Kennebec. Se presentan sin lavar y se envasan manualmente, conservando el aspecto terroso de la piel, con carne blanca y un uso muy versátil en cocina.</p>',
 'en_excerpt'=>'<p>20 kg format of white Kennebec potatoes. They are supplied unwashed and packed by hand, retaining the earthy appearance of the skin, with white flesh and a highly versatile range of culinary uses.</p>',
 'es_content'=><<<'HTML'
<h2>20 kg de patata Kennebec</h2>
<p>Este formato reúne 20 kg de patatas blancas variedad Kennebec. Se envasan manualmente y se presentan sin lavar, por lo que conservan tierra en la piel y el aspecto rústico característico de la patata recién manipulada desde el campo.</p>
<p>La Kennebec tiene piel gruesa y carne blanca y resulta práctica cuando se necesita una cantidad grande de patata para consumo habitual, familias numerosas o cocina frecuente.</p>
<h2>Cómo aprovecharlas y conservarlas</h2>
<p>Son aptas para cocer, asar, freír, preparar purés o utilizar en guisos. Antes de cocinar, lávalas bien para retirar los restos de tierra.</p>
<p>En un formato de este tamaño es especialmente importante guardarlas en un lugar fresco, seco, oscuro y ventilado. Evita bolsas cerradas, humedad, calor y exposición directa a la luz.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>20 kg of Kennebec potatoes</h2>
<p>This format contains 20 kg of white Kennebec potatoes. They are packed by hand and supplied unwashed, so some soil remains on the skin and they retain the rustic appearance of potatoes handled directly from the field.</p>
<p>Kennebec potatoes have thick skin and white flesh, making this format practical when a larger quantity is needed for regular household use or frequent cooking.</p>
<h2>How to use and store them</h2>
<p>They are suitable for boiling, roasting, frying, mashing or adding to stews. Wash them thoroughly before cooking to remove any remaining soil.</p>
<p>With a pack of this size, proper storage is particularly important. Keep the potatoes in a cool, dry, dark and well-ventilated place and avoid sealed bags, moisture, heat and direct light.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué cantidad incluye?</h3>
<p>El formato incluye 20 kg de patata blanca variedad Kennebec.</p>
<h3>¿Se entregan lavadas?</h3>
<p>No. Se presentan sin lavar y pueden conservar restos de tierra en la piel.</p>
<h3>¿Cómo conviene guardar 20 kg de patatas?</h3>
<p>En un lugar fresco, seco, oscuro y ventilado, evitando humedad, calor y recipientes completamente cerrados.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How much is included?</h3>
<p>The pack contains 20 kg of white Kennebec potatoes.</p>
<h3>Are they supplied washed?</h3>
<p>No. They are supplied unwashed and some soil may remain on the skin.</p>
<h3>How should 20 kg of potatoes be stored?</h3>
<p>Keep them in a cool, dry, dark and well-ventilated place, avoiding moisture, heat and fully sealed containers.</p>
HTML,
],
'flores'=>[
 'id'=>12711,'title'=>'Flores de calabacín 8 unidades','title_en'=>'Courgette Flowers – 8 pieces','slug'=>'flores-de-calabacin-8-unidades','old_marker'=>'8 delicadas flores de calabacín',
 'es_excerpt'=>'<p>Caja de 8 flores de calabacín frescas. Se recolectan cuando alcanzan el punto adecuado y, por su carácter delicado, son un producto especialmente indicado para rellenar, rebozar, freír o preparar en recetas de cocción breve.</p>',
 'en_excerpt'=>'<p>Box of 8 fresh courgette flowers. They are harvested when they reach the right stage and, because of their delicate nature, are particularly suitable for stuffing, battering, frying or other quick-cooking recipes.</p>',
 'es_content'=><<<'HTML'
<h2>Flores de calabacín frescas</h2>
<p>Las flores de calabacín son un producto delicado y de temporada que se recolecta cuando alcanza el punto adecuado. Esta caja incluye 8 unidades.</p>
<p>Su textura fina hace que necesiten poca manipulación y cocciones breves. Antes de utilizarlas, conviene revisarlas con cuidado y limpiarlas suavemente sin empaparlas.</p>
<h2>Cómo aprovecharlas</h2>
<p>Una de las preparaciones más habituales es rellenarlas —por ejemplo con queso u otros rellenos suaves— y después rebozarlas o freírlas. También pueden cocinarse de forma sencilla en sartén o incorporarse a otras elaboraciones donde se quiera conservar su forma.</p>
<p>Por su delicadeza, conviene mantenerlas refrigeradas y consumirlas lo antes posible después de recibirlas.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Fresh courgette flowers</h2>
<p>Courgette flowers are a delicate seasonal product harvested when they reach the right stage. This box contains 8 pieces.</p>
<p>Their fine texture means they need very little handling and only brief cooking. Before use, check them carefully and clean them gently without soaking them.</p>
<h2>How to use them</h2>
<p>One of the most common preparations is to stuff them —for example with cheese or another mild filling— and then batter or fry them. They can also be cooked briefly in a pan or used in other dishes where their shape is intended to remain visible.</p>
<p>Because they are delicate, keep them refrigerated and use them as soon as possible after delivery.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántas flores incluye la caja?</h3>
<p>Incluye 8 flores de calabacín.</p>
<h3>¿Las flores de calabacín se comen?</h3>
<p>Sí. Son comestibles y pueden prepararse rellenas, rebozadas, fritas o con cocciones breves.</p>
<h3>¿Cómo se conservan?</h3>
<p>En el frigorífico y durante el menor tiempo posible, ya que se trata de un producto especialmente delicado.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many flowers are included?</h3>
<p>The box contains 8 courgette flowers.</p>
<h3>Are courgette flowers edible?</h3>
<p>Yes. They are edible and can be stuffed, battered, fried or cooked briefly.</p>
<h3>How should they be stored?</h3>
<p>Keep them refrigerated and use them as soon as possible, as they are particularly delicate.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_ha1_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_ha1_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_ha1_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_ha1_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(stripos(mo_ha1_vendor($p),'La Huerta de Ana Mary')===false) mo_ha1_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_ha1_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre La Huerta de Ana Mary')===false) mo_ha1_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    $translations[$spec['title']]=$spec['title_en'];
    foreach(mo_ha1_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_ha1_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<45) mo_ha1_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_huerta_anamary_batch01_backup_20260831';
if(get_option($backup_key,null)===null){
    $backup=['created_at'=>current_time('mysql'),'posts'=>[],'trp'=>[]];
    foreach($resolved as $key=>$p){
        $backup['posts'][$key]=['ID'=>(int)$p->ID,'post_excerpt'=>$p->post_excerpt,'post_content'=>$p->post_content,'en_US_post_content'=>(string)get_post_meta($p->ID,'_en_US_post_content',true)];
    }
    foreach(array_keys($translations) as $original){
        $backup['trp'][$original]=$wpdb->get_results($wpdb->prepare("SELECT * FROM `{$trp}` WHERE original=%s",$original),ARRAY_A) ?: [];
    }
    if(!add_option($backup_key,$backup,'',false)) mo_ha1_fail('Could not create batch 01 backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($resolved as $key=>$p){
    $r=wp_update_post(['ID'=>(int)$p->ID,'post_excerpt'=>$payload[$key]['es_excerpt'],'post_content'=>$payload[$key]['es_content']],true);
    if(is_wp_error($r)) mo_ha1_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_ha1_fail("English long-content meta update failed {$key}");
    }
    clean_post_cache($p->ID);
    echo "UPDATED {$key}: ID {$p->ID}\n";
}

$has_original_id=in_array('original_id',$cols,true);
foreach($translations as $original=>$translated){
    $ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM `{$trp}` WHERE original=%s",$original));
    if($ids){
        foreach($ids as $id){
            $ok=$wpdb->update($trp,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);
            if($ok===false) mo_ha1_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_ha1_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre La Huerta de Ana Mary')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_ha1_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About La Huerta de Ana Mary')===false || stripos($en,'Frequently asked questions')===false) mo_ha1_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE huerta_batch01 products=5 translations=".count($translations)."\n";
