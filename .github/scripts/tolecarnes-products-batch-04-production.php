<?php
/**
 * Tolecarnes product copy batch 04 (ES + EN).
 * Products: 11105, 11107, 11110, 11112, 11114.
 * Scope: Spanish WooCommerce excerpt/content, TranslatePress ES→EN strings,
 *        and the custom _en_US_post_content field used by the English description tab.
 */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b4_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b4_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b4_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b4_pair_html($es_html,$en_html,$label){
    $es=mo_b4_segments($es_html); $en=mo_b4_segments($en_html);
    if(count($es)!==count($en)) mo_b4_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b4_fail("Tag mismatch {$label} at {$i}");
        $pairs[$seg['text']]=$en[$i]['text'];
    }
    return $pairs;
}

$producer_es=<<<'HTML'
<h2>Sobre Tolecarnes</h2>
<p>Tolecarnes es una ganadería familiar de Menasalbas, situada a los pies de los Montes de Toledo, con tres generaciones dedicadas a la cría de ganado vacuno.</p>
<p>Sus terneras se crían desde el nacimiento en la propia ganadería y pastan libremente durante buena parte del año en su dehesa de los Montes de Toledo. La zona, especialmente húmeda en la cara norte de los Montes, permite disponer de pastos naturales durante gran parte del año y mantener a los animales en movimiento en un entorno abierto.</p>
<p>Durante el invierno, cuando el pasto natural es menos abundante, su alimentación se complementa con pasto recogido en los meses de mayor disponibilidad y con piensos elaborados en la Cooperativa Valle de Mena, de la que Tolecarnes forma parte. Estos piensos se producen a partir de ingredientes de origen vegetal, lo que permite a la ganadería tener un mayor control sobre la alimentación de sus animales.</p>
<p>Tolecarnes controla el proceso de cría desde el nacimiento de las terneras hasta la comercialización de la carne, manteniendo un modelo de ganadería tradicional muy vinculado a los Montes de Toledo.</p>
HTML;
$producer_en=<<<'HTML'
<h2>About Tolecarnes</h2>
<p>Tolecarnes is a family-run cattle farm based in Menasalbas, at the foot of the Montes de Toledo, with three generations of experience raising cattle.</p>
<p>Its calves are raised by the farm from birth and spend much of the year grazing freely on its dehesa in the Montes de Toledo. The wetter conditions on the northern side of the mountains provide natural pasture for a large part of the year, allowing the cattle to remain outdoors and active in an open environment.</p>
<p>During winter, when fresh pasture is less abundant, their diet is supplemented with forage collected during the more productive months and with feed produced by Cooperativa Valle de Mena, of which Tolecarnes is a member. This feed is made from plant-based ingredients, giving the farm greater control over the animals' diet.</p>
<p>Tolecarnes oversees the farming process from the birth of its calves through to the sale of the meat, maintaining a traditional approach to cattle farming closely connected to the Montes de Toledo.</p>
HTML;

$products=[
'osobuco_ternera'=>[
 'id'=>11105,'title'=>'Osobuco de ternera','slug'=>'osobuco-de-ternera','sku'=>'/osobuco-ternera-vacuno',
 'old_marker'=>'El osobuco es el morcillo de ternera pero con hueso',
 'es_excerpt'=>'<p>Osobuco de ternera obtenido del morcillo y cortado transversalmente con el hueso en el centro. Es una pieza especialmente adecuada para guisos y cocciones largas, donde la carne puede cocinarse lentamente junto con la salsa y el resto de ingredientes. Precio por kilogramo.</p>',
 'en_excerpt'=>'<p>Beef osso buco cut crosswise from the shin with the bone in the centre. It is particularly well suited to stews and long, slow cooking, allowing the meat to cook gently together with the sauce and the other ingredients. Price per kilogram.</p>',
 'es_content'=><<<'HTML'
<h2>Morcillo de ternera cortado con hueso</h2>
<p>El osobuco es una forma de presentar el morcillo de ternera mediante cortes horizontales que mantienen el hueso en el centro de cada pieza. Este formato permite cocinar conjuntamente la carne y el hueso durante preparaciones largas y húmedas.</p>
<p>Es un corte pensado para platos que necesitan tiempo. En un guiso o estofado, la cocción suave permite que la carne vaya adquiriendo una textura más tierna mientras sus jugos se integran con la salsa.</p>
<h2>Cómo prepararlo</h2>
<p>Una forma sencilla de empezar es dorar ligeramente las piezas por ambos lados antes de añadir verduras, caldo, vino o los ingredientes elegidos para el guiso.</p>
<p>Después conviene continuar con una cocción suave y con suficiente líquido hasta alcanzar la textura deseada. También puede prepararse en olla a presión cuando se quiere reducir el tiempo de cocción.</p>
<p>El precio de este producto se indica por kilogramo.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Cross-cut beef shin with the bone</h2>
<p>Osso buco is beef shin presented in cross-cut slices that keep the bone in the centre of each piece. This format allows the meat and bone to cook together during long, moist preparations.</p>
<p>It is a cut made for dishes that benefit from time. In a stew or braise, gentle cooking gradually tenderises the meat while its juices become part of the sauce.</p>
<h2>How to cook it</h2>
<p>A simple way to begin is to brown the pieces lightly on both sides before adding vegetables, stock, wine or the other ingredients chosen for the dish.</p>
<p>Then continue with gentle cooking and enough liquid until the meat reaches the desired tenderness. A pressure cooker can also be used when you want to reduce the cooking time.</p>
<p>The price of this product is shown per kilogram.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué parte de la ternera es el osobuco?</h3>
<p>Es morcillo de ternera cortado transversalmente y presentado con el hueso en el centro.</p>
<h3>¿Para qué tipo de cocina es adecuado?</h3>
<p>Es especialmente apropiado para guisos, estofados y otras preparaciones de cocción prolongada con líquido.</p>
<h3>¿Se puede preparar en olla a presión?</h3>
<p>Sí. La olla a presión permite reducir el tiempo necesario para conseguir una textura tierna.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which part of the animal is osso buco?</h3>
<p>It is beef shin cut crosswise and presented with the bone in the centre.</p>
<h3>What type of cooking is it suitable for?</h3>
<p>It is particularly suitable for stews, braises and other long, moist cooking methods.</p>
<h3>Can I cook it in a pressure cooker?</h3>
<p>Yes. A pressure cooker can reduce the time needed to achieve a tender texture.</p>
HTML,
],
'carne_piedra_vaca'=>[
 'id'=>11107,'title'=>'Carne a la piedra de vaca madurada','slug'=>'carne-a-la-piedra-de-vaca-madurada','sku'=>'/carne-a-la-piedra-de-vaca-madurada-adh',
 'old_marker'=>'Carne de vaca madurada preparada para cocinar a la piedra',
 'es_excerpt'=>'<p>Carne de vaca madurada durante más de 20 días, con grasa infiltrada y sabor intenso. Se entrega ya fileteada en bandejas envasadas al vacío y está preparada para cocinar a la piedra, en plancha o en sartén.</p>',
 'en_excerpt'=>'<p>Beef matured for more than 20 days, with intramuscular fat and a pronounced flavour. It is supplied already sliced in vacuum-packed trays and prepared for cooking on a hot stone, griddle or frying pan.</p>',
 'es_content'=><<<'HTML'
<h2>Carne de vaca madurada y lista para cocinar</h2>
<p>Esta carne de vaca procede de piezas con un periodo de maduración superior a 20 días. Presenta grasa infiltrada y un sabor intenso, y se entrega ya fileteada para facilitar una preparación rápida en la mesa o en la cocina.</p>
<p>El formato está especialmente pensado para cocinar la carne a la piedra, aunque también puede utilizarse una plancha o una sartén bien caliente.</p>
<h2>Cómo prepararla</h2>
<p>Si utilizas una piedra, deja que alcance una temperatura alta antes de empezar a cocinar. Coloca pequeñas porciones de carne sobre la superficie y cocina cada una al punto que prefieras.</p>
<p>En plancha o sartén, utiliza igualmente una superficie bien caliente y evita sobrecargarla para que la temperatura no caiga de forma brusca.</p>
<p>La carne se entrega fileteada en bandejas envasadas al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Matured beef ready for quick cooking</h2>
<p>This beef comes from cuts matured for more than 20 days. It has intramuscular fat and a pronounced flavour and is supplied already sliced for quick preparation either at the table or in the kitchen.</p>
<p>The format is particularly suited to hot-stone cooking, although it can also be prepared on a properly heated griddle or in a frying pan.</p>
<h2>How to cook it</h2>
<p>If using a hot stone, allow it to reach a high temperature before cooking. Place small portions of beef on the surface and cook each piece to your preferred doneness.</p>
<p>For a griddle or frying pan, use a properly heated surface and avoid overcrowding it so the temperature does not drop sharply.</p>
<p>The beef is supplied sliced in vacuum-packed trays.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuánto tiempo de maduración tiene?</h3>
<p>Las piezas utilizadas cuentan con más de 20 días de maduración.</p>
<h3>¿Solo se puede cocinar a la piedra?</h3>
<p>No. También puede prepararse en plancha o sartén bien calientes.</p>
<h3>¿Cómo se presenta?</h3>
<p>Se entrega fileteada en bandejas envasadas al vacío.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How long is the beef matured?</h3>
<p>The cuts used are matured for more than 20 days.</p>
<h3>Can it only be cooked on a hot stone?</h3>
<p>No. It can also be cooked on a properly heated griddle or in a frying pan.</p>
<h3>How is it supplied?</h3>
<p>It is supplied sliced in vacuum-packed trays.</p>
HTML,
],
'rabo_ternera'=>[
 'id'=>11110,'title'=>'Rabo de ternera','slug'=>'rabo-de-ternera','sku'=>'/rabo-de-ternera-montes-de-toledo-awd',
 'old_marker'=>'Rabo de ternera de los Montes de Toledo, cortado en medallones por sus coyunturas',
 'es_excerpt'=>'<p>Rabo de ternera de los Montes de Toledo, preparado en medallones mediante cortes por las articulaciones, como en una carnicería tradicional. Es un corte especialmente indicado para guisos y cocciones lentas. Se presenta en bandejas de aproximadamente 0,7 a 1,2 kg.</p>',
 'en_excerpt'=>'<p>Beef tail from the Montes de Toledo, prepared as medallions by cutting through the joints, as in a traditional butcher shop. It is particularly well suited to stews and slow cooking. Supplied in trays of approximately 0.7 to 1.2 kg.</p>',
 'es_content'=><<<'HTML'
<h2>Rabo de ternera preparado para guisar</h2>
<p>El rabo de ternera se entrega ya cortado en medallones por sus articulaciones, evitando tener que porcionar la pieza en casa. Procede de los Montes de Toledo y su formato está pensado especialmente para recetas de cocción lenta.</p>
<p>Es un corte que necesita tiempo y humedad para cocinarse. Por eso se utiliza habitualmente en guisos, donde la carne se cocina junto con verduras, caldo, vino y otros ingredientes hasta alcanzar una textura tierna.</p>
<h2>Cómo prepararlo</h2>
<p>Puedes comenzar dorando los medallones por varias caras para aportar un punto tostado antes de incorporar el resto de ingredientes.</p>
<p>Después, continúa con una cocción suave y prolongada con suficiente líquido. También puede utilizarse una olla a presión si se quiere acortar el tiempo necesario.</p>
<p>El producto se presenta en una bandeja de aproximadamente 0,7 a 1,2 kg.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Beef tail prepared for stewing</h2>
<p>The beef tail is supplied already cut into medallions through the joints, so there is no need to portion the piece at home. It comes from the Montes de Toledo and is prepared particularly for slow-cooked dishes.</p>
<p>This is a cut that benefits from time and moisture. It is therefore commonly used in stews, where the meat cooks together with vegetables, stock, wine and other ingredients until tender.</p>
<h2>How to cook it</h2>
<p>You can begin by browning the medallions on several sides to develop a roasted note before adding the other ingredients.</p>
<p>Then continue with gentle, prolonged cooking with enough liquid. A pressure cooker can also be used when you want to shorten the cooking time.</p>
<p>The product is supplied in a tray weighing approximately 0.7 to 1.2 kg.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo viene cortado el rabo de ternera?</h3>
<p>Se entrega cortado en medallones por las articulaciones, listo para incorporarlo al guiso.</p>
<h3>¿Qué peso tiene la bandeja?</h3>
<p>El peso aproximado de cada bandeja se sitúa entre 0,7 y 1,2 kg.</p>
<h3>¿Cuál es la mejor forma de cocinarlo?</h3>
<p>Es un corte especialmente adecuado para guisos y cocciones lentas con líquido, aunque también puede prepararse en olla a presión.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How is the beef tail cut?</h3>
<p>It is supplied as medallions cut through the joints, ready to add to a stew.</p>
<h3>How much does the tray weigh?</h3>
<p>Each tray weighs approximately 0.7 to 1.2 kg.</p>
<h3>What is the best way to cook it?</h3>
<p>It is particularly suitable for stews and slow cooking with liquid, although it can also be prepared in a pressure cooker.</p>
HTML,
],
'lote_familiar'=>[
 'id'=>11112,'title'=>'Lote familiar','slug'=>'lote-familiar','sku'=>'/lote-familiar-adv',
 'old_marker'=>'Lote de 3 kg de ternera, compuesto por 1kg de filetes, 1 kg de carne magra, 1 kg de picada y de regalo huesos',
 'es_excerpt'=>'<p>Lote familiar de 3 kg de ternera compuesto por 1 kg de filetes, 1 kg de carne magra y 1 kg de carne picada, con huesos de regalo. Una selección pensada para disponer de distintos cortes y resolver varias comidas con preparaciones muy diferentes. Precio por lote.</p>',
 'en_excerpt'=>'<p>Family beef box containing 3 kg in total: 1 kg of steaks, 1 kg of lean diced beef and 1 kg of minced beef, plus complimentary beef bones. A selection designed to provide different cuts for several different meals. Price per box.</p>',
 'es_content'=><<<'HTML'
<h2>Tres kilos de ternera para distintas preparaciones</h2>
<p>El lote familiar reúne tres formatos diferentes de carne de ternera: 1 kg de filetes, 1 kg de carne magra y 1 kg de carne picada. Además, incluye huesos de regalo.</p>
<p>La combinación permite repartir el lote entre comidas muy distintas sin comprar cada corte por separado. Los filetes están pensados para preparaciones rápidas, la carne magra funciona especialmente bien en guisos y la carne picada puede utilizarse en múltiples platos cotidianos.</p>
<h2>Cómo aprovechar el lote</h2>
<p>Los filetes pueden prepararse a la plancha o en sartén; la carne magra puede cocinarse lentamente en guisos, estofados o salsas; y la carne picada sirve para hamburguesas caseras, albóndigas, rellenos, pasta y otras elaboraciones.</p>
<p>Los huesos incluidos pueden utilizarse para preparar caldo o fondo y aprovecharlos como base de otros platos.</p>
<p>El precio se indica por el lote completo de 3 kg de carne, más los huesos de regalo.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Three kilograms of beef for different meals</h2>
<p>The family box brings together three different beef formats: 1 kg of steaks, 1 kg of lean diced beef and 1 kg of minced beef. Complimentary beef bones are also included.</p>
<p>The combination makes it possible to spread the box across very different meals without buying each cut separately. The steaks suit quick cooking, the lean diced beef works particularly well in stews, and the minced beef can be used in many everyday dishes.</p>
<h2>How to use the box</h2>
<p>The steaks can be cooked on a griddle or in a frying pan; the lean diced beef can be cooked slowly in stews, casseroles or sauces; and the minced beef can be used for homemade burgers, meatballs, fillings, pasta and other dishes.</p>
<p>The included bones can be used to make stock or broth as a base for other meals.</p>
<p>The price shown is for the complete box containing 3 kg of beef, plus the complimentary bones.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué incluye el lote familiar?</h3>
<p>Incluye 1 kg de filetes de ternera, 1 kg de carne magra, 1 kg de carne picada y huesos de regalo.</p>
<h3>¿Cuánta carne contiene en total?</h3>
<p>El lote contiene 3 kg de carne de ternera, además de los huesos incluidos como regalo.</p>
<h3>¿El precio es por kilogramo?</h3>
<p>No. El precio indicado corresponde al lote completo.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What does the family box include?</h3>
<p>It includes 1 kg of beef steaks, 1 kg of lean diced beef, 1 kg of minced beef and complimentary beef bones.</p>
<h3>How much beef does it contain in total?</h3>
<p>The box contains 3 kg of beef, plus the complimentary bones.</p>
<h3>Is the price per kilogram?</h3>
<p>No. The displayed price is for the complete box.</p>
HTML,
],
'filetes_cachopos'=>[
 'id'=>11114,'title'=>'Filetes para cachopos','slug'=>'filetes-para-cachopos','sku'=>'/filetes-para-cachopos-adr',
 'old_marker'=>'Filetes de ternera preparados especialmente para cachopos, obtenidos de cortes como tapa o contra',
 'es_excerpt'=>'<p>Filetes de ternera preparados especialmente para elaborar cachopos, obtenidos de cortes como tapa o contra. Se presentan finos y en un formato adecuado para rellenar, empanar y freír, evitando tener que preparar o abrir la pieza en casa.</p>',
 'en_excerpt'=>'<p>Beef steaks prepared specifically for making cachopos, using cuts such as topside or silverside. They are supplied thinly sliced in a format suitable for filling, breading and frying, avoiding the need to open out the cut at home.</p>',
 'es_content'=><<<'HTML'
<h2>Filetes preparados para hacer cachopos</h2>
<p>Estos filetes se obtienen de piezas como la tapa o la contra y se preparan específicamente con un corte fino y un formato adecuado para elaborar cachopos.</p>
<p>Al venir ya preparados, no es necesario abrir o trabajar la pieza en casa para conseguir una superficie adecuada para rellenar y empanar.</p>
<h2>Cómo utilizarlos</h2>
<p>Coloca el relleno elegido sobre la carne, cierra la preparación con el filete correspondiente y empana antes de freír. El corte fino facilita que el conjunto pueda cocinarse de manera uniforme.</p>
<p>Conviene controlar la temperatura del aceite para que el empanado se dore sin quemarse antes de que la carne y el relleno estén bien cocinados.</p>
<p>También pueden utilizarse para otras preparaciones de filete fino en las que interese una pieza amplia y manejable.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Steaks prepared for making cachopos</h2>
<p>These steaks are taken from cuts such as topside or silverside and prepared specifically as thin, suitably shaped pieces for making cachopos.</p>
<p>Because they are supplied already prepared, there is no need to open out or work the cut at home to create a suitable surface for filling and breading.</p>
<h2>How to use them</h2>
<p>Place your chosen filling on the meat, close the preparation with the corresponding steak and bread it before frying. The thin cut helps the complete dish cook evenly.</p>
<p>Control the oil temperature so the coating browns without burning before the meat and filling are properly cooked.</p>
<p>They can also be used for other thin-steak preparations where a broad, manageable piece of beef is useful.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué cortes proceden estos filetes?</h3>
<p>Se obtienen de piezas como la tapa o la contra de ternera.</p>
<h3>¿Hay que abrir los filetes en casa?</h3>
<p>No. Se presentan ya finos y preparados en un formato adecuado para rellenar, empanar y freír.</p>
<h3>¿Solo sirven para cachopos?</h3>
<p>Están preparados especialmente para cachopos, aunque también pueden utilizarse en otras elaboraciones que necesiten filetes finos y amplios.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which cuts are used for these steaks?</h3>
<p>They are taken from cuts such as beef topside or silverside.</p>
<h3>Do I need to open out the steaks at home?</h3>
<p>No. They are supplied already thinly sliced and prepared in a format suitable for filling, breading and frying.</p>
<h3>Are they only suitable for cachopos?</h3>
<p>They are prepared specifically for cachopos, although they can also be used in other dishes that call for thin, broad steaks.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b4_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_b4_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_b4_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b4_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b4_fail("SKU mismatch {$key}");
    if(stripos(mo_b4_vendor($p),'tolecarnes')===false) mo_b4_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b4_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre Tolecarnes')===false) mo_b4_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    foreach(mo_b4_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b4_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<35) mo_b4_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_tolecarnes_batch04_backup_20260831';
if(get_option($backup_key,null)===null){
    $backup=['created_at'=>current_time('mysql'),'posts'=>[],'trp'=>[]];
    foreach($resolved as $key=>$p){
        $backup['posts'][$key]=[
            'ID'=>(int)$p->ID,
            'post_excerpt'=>$p->post_excerpt,
            'post_content'=>$p->post_content,
            'en_US_post_content'=>(string)get_post_meta($p->ID,'_en_US_post_content',true),
        ];
    }
    foreach(array_keys($translations) as $original){
        $backup['trp'][$original]=$wpdb->get_results($wpdb->prepare("SELECT * FROM `{$trp}` WHERE original=%s",$original),ARRAY_A) ?: [];
    }
    if(!add_option($backup_key,$backup,'',false)) mo_b4_fail('Could not create batch 04 backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($resolved as $key=>$p){
    $r=wp_update_post([
        'ID'=>(int)$p->ID,
        'post_excerpt'=>$payload[$key]['es_excerpt'],
        'post_content'=>$payload[$key]['es_content'],
    ],true);
    if(is_wp_error($r)) mo_b4_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_b4_fail("English long-content meta update failed {$key}");
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
            if($ok===false) mo_b4_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_b4_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre Tolecarnes')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_b4_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About Tolecarnes')===false || stripos($en,'Frequently asked questions')===false) mo_b4_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE batch04 products=5 translations=".count($translations)."\n";
