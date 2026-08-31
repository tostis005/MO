<?php
/** Tolecarnes product copy batch 06 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b6_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b6_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b6_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b6_pair_html($es_html,$en_html,$label){
    $es=mo_b6_segments($es_html); $en=mo_b6_segments($en_html);
    if(count($es)!==count($en)) mo_b6_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b6_fail("Tag mismatch {$label} at {$i}");
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
'ragout_pito'=>[
 'id'=>11129,'title'=>'Ragout de pito de vacuno','title_en'=>'Beef pito ragout','slug'=>'ragout-de-pito-de-vacuno','sku'=>'/ragout-de-pito-de-vacuno',
 'old_marker'=>'Ragout de pito de vacuno preparado a partir de una pieza situada próxima a la entraña.',
 'es_excerpt'=>'<p>Ragout de pito de vacuno, preparado a partir de una pieza situada próxima a la entraña. Se presenta troceado y está especialmente pensado para guisos, estofados y platos con salsa, donde puede cocinarse con tiempo hasta alcanzar una textura agradable.</p>',
 'en_excerpt'=>'<p>Beef pito ragout, prepared from a cut located close to the skirt area. Supplied diced and particularly suited to stews, casseroles and dishes with sauce, where it can cook gradually until it reaches a pleasant texture.</p>',
 'es_content'=><<<'HTML'
<h2>Carne troceada para guisos con sabor</h2>
<p>El pito de vacuno es una pieza situada próxima a la entraña y se caracteriza por un sabor marcado. En este formato se prepara ya troceado para utilizarlo directamente en recetas de cocción en salsa.</p>
<p>Es una opción especialmente cómoda para estofados, guisos con verduras y otras preparaciones en las que la carne se cocina junto al resto de ingredientes.</p>
<h2>Cómo prepararlo</h2>
<p>Puedes comenzar dorando ligeramente los trozos de carne antes de añadir verduras, caldo, vino u otros ingredientes del guiso. Después, continúa con una cocción suave hasta que la carne alcance la textura que buscas.</p>
<p>También puede prepararse en olla a presión si quieres reducir el tiempo de cocción.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Diced beef for flavourful stews</h2>
<p>Beef pito is a cut located close to the skirt area and is known for its pronounced flavour. In this format it is supplied already diced so it can go straight into dishes cooked in sauce.</p>
<p>It is a particularly convenient choice for casseroles, vegetable stews and other dishes in which the meat cooks together with the rest of the ingredients.</p>
<h2>How to cook it</h2>
<p>You can start by lightly browning the pieces of beef before adding vegetables, stock, wine or the other ingredients in the dish. Then continue with gentle cooking until the meat reaches the texture you prefer.</p>
<p>It can also be prepared in a pressure cooker when you want to reduce the cooking time.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es el pito de vacuno?</h3>
<p>Es una pieza de vacuno situada próxima a la entraña.</p>
<h3>¿Para qué recetas está pensado este ragout?</h3>
<p>Está especialmente indicado para guisos, estofados y otras preparaciones con salsa.</p>
<h3>¿Viene ya troceado?</h3>
<p>Sí. Se presenta troceado para facilitar su incorporación directa a la receta.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is beef pito?</h3>
<p>It is a beef cut located close to the skirt area.</p>
<h3>What dishes is this ragout intended for?</h3>
<p>It is particularly suited to stews, casseroles and other dishes cooked with sauce.</p>
<h3>Is it supplied already diced?</h3>
<p>Yes. It is supplied diced so it can be added directly to the chosen recipe.</p>
HTML,
],
'lote_filetes_magro'=>[
 'id'=>11131,'title'=>'Lote filetes + magro','title_en'=>'Steak + diced beef family box','slug'=>'lote-filetes-magro','sku'=>'/filetes-magro-awr',
 'old_marker'=>'Lote compuesto por aproximadamente 1,5 kg de filetes de primera de ternera y 1,5 kg de carne magra de ternera',
 'es_excerpt'=>'<p>Lote de ternera de aproximadamente 3 kg en total, compuesto por unos 1,5 kg de filetes de primera y 1,5 kg de carne magra. Dos formatos diferentes para combinar preparaciones rápidas con guisos y platos de cocción más pausada. Se preparan por separado y se presentan envasados al vacío.</p>',
 'en_excerpt'=>'<p>Beef box weighing approximately 3 kg in total, containing around 1.5 kg of first-category steaks and 1.5 kg of lean diced beef. Two different formats for combining quick meals with stews and slower-cooked dishes. They are prepared separately and supplied vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Dos tipos de carne en un mismo lote</h2>
<p>Este lote reúne aproximadamente 1,5 kg de filetes de primera de ternera y 1,5 kg de carne magra, con un peso total aproximado de 3 kg.</p>
<p>La combinación permite tener en casa carne para preparaciones muy diferentes: los filetes están pensados para cocciones rápidas, mientras que el magro puede utilizarse en guisos, estofados y platos con salsa.</p>
<h2>Cómo aprovechar el lote</h2>
<p>Los filetes pueden prepararse en sartén, plancha o brasa y acompañarse con una guarnición sencilla. La carne magra funciona especialmente bien cuando se cocina con algo más de tiempo junto a verduras, caldo u otros ingredientes.</p>
<p>Ambos productos se preparan por separado y se presentan envasados al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Two types of beef in one box</h2>
<p>This box contains approximately 1.5 kg of first-category beef steaks and 1.5 kg of lean diced beef, for a total weight of around 3 kg.</p>
<p>The combination gives you beef for very different meals: the steaks are intended for quick cooking, while the lean diced beef can be used for stews, casseroles and dishes with sauce.</p>
<h2>How to make the most of the box</h2>
<p>The steaks can be cooked in a frying pan, on a griddle or over charcoal and served with a simple side dish. The lean diced beef works particularly well when cooked more slowly with vegetables, stock or other ingredients.</p>
<p>Both products are prepared separately and supplied vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué incluye el lote?</h3>
<p>Incluye aproximadamente 1,5 kg de filetes de primera de ternera y 1,5 kg de carne magra de ternera.</p>
<h3>¿Cuál es el peso total?</h3>
<p>El peso total es de aproximadamente 3 kg.</p>
<h3>¿Los dos productos vienen juntos?</h3>
<p>Se preparan por separado y se presentan envasados al vacío.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What does the box contain?</h3>
<p>It contains approximately 1.5 kg of first-category beef steaks and 1.5 kg of lean diced beef.</p>
<h3>What is the total weight?</h3>
<p>The total weight is approximately 3 kg.</p>
<h3>Are both products packed together?</h3>
<p>They are prepared separately and supplied vacuum packed.</p>
HTML,
],
'tomahawk'=>[
 'id'=>11134,'title'=>'Tomahawk de ternera','title_en'=>'Beef Tomahawk steak','slug'=>'tomahawk-de-ternera','sku'=>'/tomahawk-ternera',
 'old_marker'=>'Chuletón de Ternera en formato Tomahawk',
 'es_excerpt'=>'<p>Chuletón de ternera en formato Tomahawk, con el medallón del lomo unido al hueso largo de la costilla. Cada pieza pesa aproximadamente entre 0,8 y 1 kg. Un corte especialmente llamativo para preparar a la parrilla, barbacoa o al horno.</p>',
 'en_excerpt'=>'<p>Beef Tomahawk steak with the loin medallion attached to the long rib bone. Each piece weighs approximately 0.8 to 1 kg. A particularly striking cut for grilling, barbecuing or roasting in the oven.</p>',
 'es_content'=><<<'HTML'
<h2>Un chuletón con todo el hueso de la costilla</h2>
<p>El Tomahawk es un chuletón de ternera en el que se mantiene el hueso largo de la costilla unido al medallón del lomo. Su formato hace que sea una pieza especialmente vistosa para llevar entera a la mesa.</p>
<p>Cada unidad tiene un peso aproximado de entre 0,8 y 1 kg, por lo que conviene tener en cuenta el tamaño de la pieza al elegir el método y el tiempo de cocción.</p>
<h2>Cómo prepararlo</h2>
<p>Puede cocinarse a la parrilla o en barbacoa, marcando primero la superficie y continuando la cocción de forma más moderada para que el calor llegue al interior de una pieza de este grosor.</p>
<p>También puede terminarse en el horno. Tras cocinarlo, dejarlo reposar unos minutos antes de cortarlo ayuda a que los jugos se redistribuyan por la carne.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A rib steak with the full rib bone</h2>
<p>A Tomahawk is a beef rib steak in which the long rib bone remains attached to the loin medallion. Its distinctive format makes it an especially impressive piece to bring whole to the table.</p>
<p>Each steak weighs approximately 0.8 to 1 kg, so the size of the piece should be taken into account when choosing the cooking method and timing.</p>
<h2>How to cook it</h2>
<p>It can be cooked on a grill or barbecue, first searing the surface and then continuing over more moderate heat so that the centre of this thick cut cooks evenly.</p>
<p>It can also be finished in the oven. After cooking, allowing it to rest for a few minutes before slicing helps the juices redistribute through the meat.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es un Tomahawk de ternera?</h3>
<p>Es un chuletón en el que el medallón del lomo conserva unido el hueso largo de la costilla.</p>
<h3>¿Cuánto pesa cada pieza?</h3>
<p>El peso aproximado de cada unidad está entre 0,8 y 1 kg.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Es especialmente adecuado para parrilla o barbacoa y también puede terminarse o prepararse en el horno.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is a beef Tomahawk steak?</h3>
<p>It is a rib steak in which the loin medallion remains attached to the long rib bone.</p>
<h3>How much does each steak weigh?</h3>
<p>Each piece weighs approximately 0.8 to 1 kg.</p>
<h3>How can it be cooked?</h3>
<p>It is particularly well suited to grilling or barbecuing and can also be finished or cooked in the oven.</p>
HTML,
],
'aleta'=>[
 'id'=>11136,'title'=>'Aleta de ternera para rellenar','title_en'=>'Beef flank for stuffing','slug'=>'aleta-de-ternera-para-rellenar','sku'=>'/aleta-de-ternera',
 'old_marker'=>'Aleta de ternera preparada ya abierta para rellenar en casa.',
 'es_excerpt'=>'<p>Aleta de ternera preparada ya abierta para rellenar en casa. Un corte pensado para extender, añadir el relleno que prefieras, enrollar y cocinar después al horno o en olla. Se entrega preparada para facilitar este tipo de elaboración.</p>',
 'en_excerpt'=>'<p>Beef flank prepared already opened out for stuffing at home. A cut designed to be spread out, filled with your chosen ingredients, rolled and then cooked in the oven or in a pot. Supplied prepared to make this type of dish easier.</p>',
 'es_content'=><<<'HTML'
<h2>Preparada para rellenar en casa</h2>
<p>La aleta de ternera es una pieza que puede abrirse para formar una superficie amplia sobre la que repartir distintos rellenos. En este caso se entrega ya abierta, evitando tener que preparar el corte antes de empezar la receta.</p>
<p>Permite adaptar el relleno al gusto de cada casa y preparar una pieza completa para cocinar después lentamente.</p>
<h2>Cómo prepararla</h2>
<p>Extiende la carne, reparte el relleno dejando algo de margen en los bordes y enrolla la pieza. Después conviene atarla o sujetarla para que mantenga la forma durante la cocción.</p>
<p>Puede cocinarse al horno o en una olla con verduras y líquido de cocción. Una vez hecha, déjala reposar unos minutos antes de cortarla en rodajas.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Prepared for stuffing at home</h2>
<p>Beef flank can be opened out to create a broad surface over which different fillings can be spread. In this case it is supplied already opened, so there is no need to prepare the cut before starting the recipe.</p>
<p>This allows you to adapt the filling to your own taste and prepare a complete rolled piece for slower cooking.</p>
<h2>How to prepare it</h2>
<p>Lay the meat flat, spread the filling while leaving a little space around the edges, and roll the piece. It is then best tied or secured so that it keeps its shape during cooking.</p>
<p>It can be cooked in the oven or in a pot with vegetables and cooking liquid. Once cooked, allow it to rest for a few minutes before slicing.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿La aleta viene ya abierta?</h3>
<p>Sí. Se entrega ya abierta para facilitar la preparación de recetas rellenas.</p>
<h3>¿Qué tipo de relleno se puede utilizar?</h3>
<p>Puede adaptarse al gusto de cada receta con verduras, huevo, frutos secos u otros ingredientes que quieras incorporar.</p>
<h3>¿Cómo se cocina una vez rellena?</h3>
<p>Puede cocinarse al horno o en olla, procurando sujetar previamente la pieza para que conserve su forma.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Is the beef supplied already opened out?</h3>
<p>Yes. It is supplied already opened to make stuffed recipes easier to prepare.</p>
<h3>What kind of filling can be used?</h3>
<p>The filling can be adapted to the recipe with vegetables, egg, nuts or other ingredients you would like to include.</p>
<h3>How is it cooked once stuffed?</h3>
<p>It can be cooked in the oven or in a pot, ideally securing the rolled piece first so that it keeps its shape.</p>
HTML,
],
'lote_barbacoa'=>[
 'id'=>11139,'title'=>'Lote barbacoa','title_en'=>'Barbecue beef box','slug'=>'lote-barbacoa','sku'=>'/lote-barbacoa-aww',
 'old_marker'=>'Lote barbacoa compuesto por 2 kg de churrasco de ternera, cuatro hamburguesas Classic y 1 kg de entraña.',
 'es_excerpt'=>'<p>Lote preparado para barbacoa con 2 kg de churrasco de ternera, cuatro hamburguesas Classic y 1 kg de entraña. Tres opciones diferentes para cocinar a la parrilla en una misma compra. Los cortes principales se presentan envasados al vacío.</p>',
 'en_excerpt'=>'<p>Barbecue box containing 2 kg of beef churrasco, four Classic burgers and 1 kg of skirt steak. Three different options for grilling in a single order. The main cuts are supplied vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Un lote pensado para la barbacoa</h2>
<p>Este lote reúne 2 kg de churrasco de ternera, cuatro hamburguesas Classic y 1 kg de entraña. La combinación permite preparar una barbacoa con cortes y formatos diferentes sin tener que elegir cada producto por separado.</p>
<p>El churrasco y la entraña aportan dos formas distintas de disfrutar la ternera a la parrilla, mientras que las hamburguesas añaden una opción sencilla que puede cocinarse al mismo tiempo.</p>
<h2>Cómo organizar la parrilla</h2>
<p>Empieza por tener la parrilla bien caliente y adapta la zona de cocción a cada producto. La entraña, al ser una pieza relativamente fina, suele necesitar menos tiempo que cortes más gruesos. Las hamburguesas deben cocinarse completamente en el interior.</p>
<p>Los cortes principales del lote se preparan y se presentan envasados al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A box designed for the barbecue</h2>
<p>This box contains 2 kg of beef churrasco, four Classic burgers and 1 kg of skirt steak. The combination gives you different cuts and formats for the grill without having to choose each product separately.</p>
<p>The churrasco and skirt steak offer two different ways to enjoy beef on the grill, while the burgers add an easy option that can be cooked at the same time.</p>
<h2>How to organise the grill</h2>
<p>Start with a properly heated grill and adapt the cooking area to each product. Skirt steak is a relatively thin cut and will generally need less time than thicker pieces. The burgers should be cooked thoroughly in the centre.</p>
<p>The main cuts in the box are prepared and supplied vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué incluye el lote barbacoa?</h3>
<p>Incluye 2 kg de churrasco de ternera, cuatro hamburguesas Classic y 1 kg de entraña.</p>
<h3>¿Está pensado todo para cocinar a la parrilla?</h3>
<p>Sí. Los tres productos pueden prepararse en parrilla o barbacoa.</p>
<h3>¿Cómo se presentan los cortes principales?</h3>
<p>Los cortes principales se presentan envasados al vacío.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What does the barbecue box contain?</h3>
<p>It contains 2 kg of beef churrasco, four Classic burgers and 1 kg of skirt steak.</p>
<h3>Is everything intended for grilling?</h3>
<p>Yes. All three products can be cooked on a grill or barbecue.</p>
<h3>How are the main cuts supplied?</h3>
<p>The main cuts are supplied vacuum packed.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b6_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_b6_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_b6_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b6_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b6_fail("SKU mismatch {$key}");
    if(stripos(mo_b6_vendor($p),'tolecarnes')===false) mo_b6_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b6_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre Tolecarnes')===false) mo_b6_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    $translations[$spec['title']]=$spec['title_en'];
    foreach(mo_b6_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b6_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<40) mo_b6_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_tolecarnes_batch06_backup_20260831';
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
    if(!add_option($backup_key,$backup,'',false)) mo_b6_fail('Could not create batch 06 backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($resolved as $key=>$p){
    $r=wp_update_post(['ID'=>(int)$p->ID,'post_excerpt'=>$payload[$key]['es_excerpt'],'post_content'=>$payload[$key]['es_content']],true);
    if(is_wp_error($r)) mo_b6_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_b6_fail("English long-content meta update failed {$key}");
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
            if($ok===false) mo_b6_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_b6_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre Tolecarnes')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_b6_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About Tolecarnes')===false || stripos($en,'Frequently asked questions')===false) mo_b6_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE batch06 products=5 translations=".count($translations)."\n";
