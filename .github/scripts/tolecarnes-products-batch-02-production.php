<?php
/**
 * Tolecarnes product copy batch 02 (ES + EN via TranslatePress).
 * Scope: WooCommerce post_excerpt + post_content and TranslatePress ES→EN strings only.
 * Products: 11077, 11079, 11082, 11087, 11090.
 */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b2_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b2_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b2_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b2_pair_html($es_html,$en_html,$label){
    $es=mo_b2_segments($es_html); $en=mo_b2_segments($en_html);
    if(count($es)!==count($en)) mo_b2_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b2_fail("Tag mismatch {$label} at {$i}");
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
'entrecot_lomo_bajo'=>[
 'id'=>11077,'title'=>'Entrecot de lomo bajo','slug'=>'entrecot-de-lomo-bajo','sku'=>'/entrecot-de-ternera-extra-ad','en_title'=>'Beef Striploin Steak',
 'es_excerpt'=>'<p>Entrecot de ternera seleccionado del lomo bajo y presentado sin hueso. Es una pieza tierna y sabrosa, especialmente adecuada para preparaciones rápidas a la plancha, en sartén o a la parrilla. El precio del producto se indica por kilogramo.</p>',
 'en_excerpt'=>'<p>Beef steak selected from the striploin and presented boneless. It is a tender, flavourful cut particularly well suited to quick cooking on a griddle, in a frying pan or on the grill. The product price is shown per kilogram.</p>',
 'es_content'=><<<'HTML'
<h2>Un corte tierno del lomo bajo</h2>
<p>Este entrecot procede del lomo bajo de la ternera y se presenta sin hueso. Es una zona especialmente apreciada para obtener filetes tiernos que pueden cocinarse en pocos minutos.</p>
<p>Su preparación no necesita ser complicada: una superficie bien caliente y un tiempo de cocción ajustado al grosor de la pieza permiten mantener el protagonismo de la carne.</p>
<h2>Cómo prepararlo</h2>
<p>Puede cocinarse en sartén, plancha, parrilla o barbacoa. Antes de ponerlo al fuego, conviene retirar el exceso de humedad de la superficie.</p>
<p>Cocina la pieza por ambos lados y evita mantenerla sobre el fuego más tiempo del necesario. Después de cocinarla, un breve reposo antes de cortarla ayuda a conservar mejor sus jugos.</p>
<p>Puede servirse simplemente con sal y una guarnición de verduras, patatas o ensalada.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A tender cut from the striploin</h2>
<p>This steak comes from the beef striploin and is presented boneless. This area is particularly valued for tender steaks that can be cooked in just a few minutes.</p>
<p>It does not require a complicated preparation: a properly heated cooking surface and a cooking time suited to the thickness of the steak allow the meat itself to remain the focus.</p>
<h2>How to cook it</h2>
<p>It can be cooked in a frying pan, on a griddle, on the grill or on a barbecue. Pat away excess surface moisture before placing it over the heat.</p>
<p>Cook the steak on both sides and avoid leaving it over the heat longer than necessary. A short rest after cooking helps retain its juices before slicing.</p>
<p>It can be served simply with salt and a side of vegetables, potatoes or salad.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿El entrecot de lomo bajo lleva hueso?</h3>
<p>No. Esta pieza se presenta sin hueso.</p>
<h3>¿Cómo se recomienda cocinarlo?</h3>
<p>Es especialmente adecuado para sartén, plancha, parrilla o barbacoa, utilizando una cocción relativamente rápida.</p>
<h3>¿Cómo se indica el precio?</h3>
<p>El precio de este producto se muestra por kilogramo.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Does this striploin steak contain a bone?</h3>
<p>No. This cut is presented boneless.</p>
<h3>How should I cook it?</h3>
<p>It is particularly suitable for a frying pan, griddle, grill or barbecue, using a relatively quick cooking method.</p>
<h3>How is the price shown?</h3>
<p>The price of this product is shown per kilogram.</p>
HTML,
],
'filetes_aguja'=>[
 'id'=>11079,'title'=>'Filetes aguja de ternera','slug'=>'filetes-aguja-de-ternera','sku'=>'/filetes-de-aguja-de-ternera-awf','en_title'=>'Beef Chuck Steaks',
 'es_excerpt'=>'<p>Filetes de aguja de ternera jugosos y sabrosos, con infiltración de grasa que ayuda a mantener una textura tierna durante la cocción. Se presentan en bandeja de 1 kg y envasados al vacío.</p>',
 'en_excerpt'=>'<p>Juicy, flavourful beef chuck steaks with intramuscular fat that helps them remain tender during cooking. They are supplied in a 1 kg tray and vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>La jugosidad de la aguja</h2>
<p>La aguja es una pieza del cuarto delantero de la ternera. En estos filetes destaca la presencia de grasa infiltrada, que aporta jugosidad y ayuda a conseguir una textura tierna cuando se cocinan correctamente.</p>
<p>Son una alternativa muy versátil para comidas del día a día y permiten preparar un plato de carne sin elaboraciones complicadas.</p>
<h2>Cómo prepararlos</h2>
<p>Pueden cocinarse en sartén o plancha bien calientes, controlando el tiempo para no secar innecesariamente la carne.</p>
<p>También funcionan bien a la parrilla. Puedes acompañarlos con verduras, patatas, ensalada o utilizarlos en bocadillos y otras preparaciones sencillas.</p>
<p>El producto se presenta en una bandeja de 1 kg envasada al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>The juiciness of beef chuck</h2>
<p>The chuck is a forequarter cut. These steaks contain intramuscular fat, which contributes juiciness and helps produce a tender texture when they are cooked correctly.</p>
<p>They are a versatile option for everyday meals and make it easy to prepare a meat dish without complicated cooking.</p>
<h2>How to cook them</h2>
<p>They can be cooked in a hot frying pan or on a griddle, controlling the cooking time so the meat does not dry out unnecessarily.</p>
<p>They also work well on the grill. Serve them with vegetables, potatoes or salad, or use them in sandwiches and other simple dishes.</p>
<p>The product is supplied in a 1 kg tray and vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Por qué son jugosos los filetes de aguja?</h3>
<p>La aguja presenta infiltración de grasa, una característica que contribuye a la jugosidad y al sabor durante la cocción.</p>
<h3>¿Qué formato tiene el producto?</h3>
<p>Se presenta en una bandeja de 1 kg envasada al vacío.</p>
<h3>¿Se pueden preparar a la plancha?</h3>
<p>Sí. Son adecuados para sartén, plancha o parrilla, procurando no prolongar la cocción más de lo necesario.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Why are chuck steaks juicy?</h3>
<p>The chuck contains intramuscular fat, which contributes juiciness and flavour during cooking.</p>
<h3>What is the pack format?</h3>
<p>They are supplied in a 1 kg tray and vacuum packed.</p>
<h3>Can I cook them on a griddle?</h3>
<p>Yes. They are suitable for a frying pan, griddle or grill, without extending the cooking time more than necessary.</p>
HTML,
],
'chuleton_vaca_vieja'=>[
 'id'=>11082,'title'=>'Chuletón de vaca vieja madurado','slug'=>'chuleton-de-vaca-vieja-madurado','sku'=>'29325','en_title'=>'Matured Old Cow Rib Steak',
 'es_excerpt'=>'<p>Chuletón de lomo alto procedente de vacas seleccionadas y presentado como carne madurada. Se vende por pieza, con opciones aproximadas de 500 g u 800 g, y es un corte especialmente indicado para parrilla o barbacoa.</p>',
 'en_excerpt'=>'<p>Rib steak from the high loin of selected mature cows, presented as matured beef. It is sold by the piece, with approximate 500 g or 800 g options, and is particularly well suited to grilling or barbecuing.</p>',
 'es_content'=><<<'HTML'
<h2>Chuletón de lomo alto</h2>
<p>Este chuletón procede del lomo alto de vacas seleccionadas. Es una pieza pensada para quienes buscan un corte con presencia y una preparación sencilla en la que la carne sea la protagonista.</p>
<p>El producto se ofrece como carne madurada, sin indicar una duración concreta de maduración en la ficha, y se comercializa por pieza.</p>
<h2>Cómo prepararlo</h2>
<p>La parrilla y la barbacoa son dos de las preparaciones más habituales para un chuletón. También puede cocinarse en una plancha o sartén de tamaño suficiente para que la pieza tenga buen contacto con la superficie.</p>
<p>Utiliza una temperatura alta para marcar el exterior y adapta el tiempo al grosor de la pieza y al punto de cocción que busques. Después, deja reposar brevemente antes de cortar.</p>
<p>Hay opciones de aproximadamente 500 g y 800 g por pieza.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A rib steak from the high loin</h2>
<p>This rib steak comes from the high loin of selected mature cows. It is designed for those looking for a substantial cut and a simple preparation in which the meat itself is the main focus.</p>
<p>The product is offered as matured beef. The product information does not specify a particular maturation period, and it is sold by the piece.</p>
<h2>How to cook it</h2>
<p>Grilling and barbecuing are two of the most common ways to prepare a rib steak. It can also be cooked on a sufficiently large griddle or frying pan so the meat has good contact with the cooking surface.</p>
<p>Use a high temperature to sear the outside and adjust the cooking time to the thickness of the steak and your preferred doneness. Allow it to rest briefly before slicing.</p>
<p>Approximate 500 g and 800 g piece options are available.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué parte procede este chuletón?</h3>
<p>Procede del lomo alto de vacas seleccionadas.</p>
<h3>¿Qué pesos hay disponibles?</h3>
<p>Se vende por pieza con opciones aproximadas de 500 g y 800 g.</p>
<h3>¿Cuánto tiempo de maduración tiene?</h3>
<p>La ficha del producto lo identifica como carne madurada, pero no especifica una duración concreta de maduración.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which part does this rib steak come from?</h3>
<p>It comes from the high loin of selected mature cows.</p>
<h3>What weights are available?</h3>
<p>It is sold by the piece with approximate 500 g and 800 g options.</p>
<h3>How long is it matured for?</h3>
<p>The product is identified as matured beef, but the product information does not specify a particular maturation period.</p>
HTML,
],
'solomillo_ternera'=>[
 'id'=>11087,'title'=>'Solomillo de ternera','slug'=>'solomillo-de-ternera','sku'=>'/solomillo-de-ternera','en_title'=>'Beef Tenderloin',
 'es_excerpt'=>'<p>Solomillo de ternera, una de las piezas más apreciadas por su ternura y textura suave. Se encuentra en la zona interior junto al lomo y permite preparaciones rápidas en medallones, sartén o plancha, además de elaboraciones con la pieza entera. El precio se indica por kilogramo.</p>',
 'en_excerpt'=>'<p>Beef tenderloin, one of the most prized cuts for its tenderness and soft texture. Located on the inside alongside the loin, it works well for quick-cooked medallions, pan or griddle cooking, as well as preparations using the whole piece. The price is shown per kilogram.</p>',
 'es_content'=><<<'HTML'
<h2>Una de las piezas más tiernas</h2>
<p>El solomillo se sitúa en la zona interior junto al lomo de la ternera y es una de las piezas más valoradas por su textura tierna.</p>
<p>Su forma permite cortarlo en medallones o trabajar con porciones más grandes, por lo que puede adaptarse tanto a platos sencillos como a preparaciones más elaboradas.</p>
<h2>Cómo prepararlo</h2>
<p>En medallones, funciona especialmente bien en sartén o plancha a buena temperatura. También puede prepararse la pieza entera al horno o combinarse con salsas y guarniciones.</p>
<p>Al ser un corte tierno, conviene controlar el tiempo de cocción para evitar que pierda jugosidad innecesariamente.</p>
<p>El precio de este producto se indica por kilogramo.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>One of the most tender cuts</h2>
<p>The tenderloin is located on the inside alongside the beef loin and is one of the most valued cuts for its tender texture.</p>
<p>Its shape allows it to be cut into medallions or prepared in larger portions, making it suitable for both simple dishes and more elaborate recipes.</p>
<h2>How to cook it</h2>
<p>As medallions, it works particularly well in a hot frying pan or on a griddle. The whole piece can also be roasted in the oven or served with sauces and side dishes.</p>
<p>Because it is a naturally tender cut, control the cooking time to avoid losing juiciness unnecessarily.</p>
<p>The price of this product is shown per kilogram.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Dónde está el solomillo en la ternera?</h3>
<p>Se encuentra en la zona interior del animal, junto al lomo.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Puede cortarse en medallones para sartén o plancha, o prepararse en porciones más grandes y también al horno.</p>
<h3>¿Cómo se indica el precio?</h3>
<p>El precio de este producto se muestra por kilogramo.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Where is the tenderloin located?</h3>
<p>It is located on the inside of the animal alongside the loin.</p>
<h3>How can it be cooked?</h3>
<p>It can be cut into medallions for frying pan or griddle cooking, or prepared in larger portions and roasted in the oven.</p>
<h3>How is the price shown?</h3>
<p>The price of this product is shown per kilogram.</p>
HTML,
],
'morcillo_ternera'=>[
 'id'=>11090,'title'=>'Morcillo de ternera','slug'=>'morcillo-de-ternera','sku'=>'/morcillo-de-ternera-ak','en_title'=>'Beef Shin',
 'es_excerpt'=>'<p>Morcillo de ternera procedente de la parte baja de la pata o jarrete. Es un corte especialmente apropiado para guisos, estofados y cocidos por su buen comportamiento en cocciones prolongadas. Se entrega limpio y envasado al vacío.</p>',
 'en_excerpt'=>'<p>Beef shin from the lower leg or shank. It is particularly well suited to stews, casseroles and traditional slow-cooked dishes because it performs well during long cooking. It is supplied cleaned and vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Una pieza para cocinar sin prisas</h2>
<p>El morcillo procede de la parte baja de la pata, también conocida como jarrete. Es una pieza con tejido conjuntivo que responde especialmente bien a las cocciones largas y húmedas.</p>
<p>Con tiempo de cocción, la carne va adquiriendo una textura más tierna y aporta cuerpo al caldo o a la salsa, por lo que es un corte habitual en guisos, estofados y cocidos.</p>
<h2>Cómo prepararlo</h2>
<p>Puedes dorar primero la carne y continuar después con una cocción suave junto con verduras, caldo, vino u otros ingredientes del guiso.</p>
<p>También puede prepararse en olla a presión para reducir el tiempo necesario hasta alcanzar una textura tierna.</p>
<p>El morcillo se entrega limpio y envasado al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A cut made for slow cooking</h2>
<p>Beef shin comes from the lower leg, also known as the shank. It contains connective tissue that responds particularly well to long, moist cooking.</p>
<p>Given enough cooking time, the meat gradually becomes more tender and adds body to the broth or sauce, which is why this cut is commonly used in stews, casseroles and traditional slow-cooked dishes.</p>
<h2>How to cook it</h2>
<p>You can brown the meat first and then continue with gentle cooking alongside vegetables, stock, wine or the other ingredients in the dish.</p>
<p>It can also be prepared in a pressure cooker to reduce the time needed to achieve a tender texture.</p>
<p>The beef shin is supplied cleaned and vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué parte procede el morcillo?</h3>
<p>Procede de la parte baja de la pata de la ternera, también llamada jarrete.</p>
<h3>¿Para qué recetas es adecuado?</h3>
<p>Es especialmente apropiado para guisos, estofados, cocidos y otras recetas de cocción prolongada.</p>
<h3>¿Cómo se entrega?</h3>
<p>Se entrega limpio y envasado al vacío.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which part does beef shin come from?</h3>
<p>It comes from the lower part of the leg, also known as the shank.</p>
<h3>What recipes is it suitable for?</h3>
<p>It is particularly suitable for stews, casseroles and other long-cooked dishes.</p>
<h3>How is it supplied?</h3>
<p>It is supplied cleaned and vacuum packed.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b2_fail('TranslatePress ES→EN dictionary table not found.');
$columns=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c) if(!in_array($c,$columns,true)) mo_b2_fail("Missing TranslatePress column {$c}");

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status==='trash') mo_b2_fail("Missing product {$key}");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b2_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b2_fail("SKU mismatch {$key}");
    if(stripos(mo_b2_vendor($p),'tolecarnes')===false) mo_b2_fail("Vendor mismatch {$key}");
    $types=wp_get_post_terms($p->ID,'product_type',['fields'=>'names']);
    if(is_wp_error($types)||!in_array('simple',$types,true)) mo_b2_fail("Unexpected product type {$key}");
    if(get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b2_fail("Product no longer in stock {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content];
    $translations[$spec['title']]=$spec['en_title'];
    foreach(mo_b2_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b2_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<40) mo_b2_fail('Translation map unexpectedly small: '.count($translations));
echo 'PRECHECK translation strings: '.count($translations)."\n";

$backup_key='mo_tolecarnes_batch02_translatepress_backup_20260831';
$backup=get_option($backup_key,null);
if($backup===null){
    $backup=['created_at'=>current_time('mysql'),'posts'=>[],'trp'=>[]];
    foreach($resolved as $key=>$p) $backup['posts'][$key]=['ID'=>(int)$p->ID,'post_excerpt'=>$p->post_excerpt,'post_content'=>$p->post_content];
    foreach(array_keys($translations) as $original){
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM `{$trp}` WHERE original=%s",$original),ARRAY_A);
        $backup['trp'][$original]=$rows?:[];
    }
    if(!add_option($backup_key,$backup,'',false)) mo_b2_fail('Could not create backup option');
    echo "BACKUP created {$backup_key}\n";
}else echo "BACKUP already exists and will be preserved {$backup_key}\n";

function mo_b2_restore($backup,$trp){
    global $wpdb;
    if(!is_array($backup)) return;
    foreach(($backup['posts']??[]) as $row){wp_update_post(wp_slash(['ID'=>(int)$row['ID'],'post_excerpt'=>$row['post_excerpt'],'post_content'=>$row['post_content']]));clean_post_cache((int)$row['ID']);}
    foreach(($backup['trp']??[]) as $original=>$rows){
        $wpdb->delete($trp,['original'=>$original],['%s']);
        foreach($rows as $row){
            $data=[];$formats=[];
            foreach($row as $col=>$value){
                if($col==='id'){$data[$col]=(int)$value;$formats[]='%d';}
                elseif(in_array($col,['status','block_type','original_id'],true)&&$value!==null){$data[$col]=(int)$value;$formats[]='%d';}
                else{$data[$col]=$value;$formats[]='%s';}
            }
            $wpdb->insert($trp,$data,$formats);
        }
    }
}

try{
    foreach($resolved as $key=>$p){
        $r=wp_update_post(wp_slash(['ID'=>(int)$p->ID,'post_excerpt'=>$payload[$key]['es_excerpt'],'post_content'=>$payload[$key]['es_content']]),true);
        if(is_wp_error($r)) throw new Exception("Product update failed {$key}: ".$r->get_error_message());
        clean_post_cache((int)$p->ID); echo "UPDATED ES {$key}: {$p->ID}\n";
    }
    foreach($translations as $original=>$translated){
        $ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM `{$trp}` WHERE original=%s AND block_type=0",$original));
        if($ids){foreach($ids as $id){$ok=$wpdb->update($trp,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d']);if($ok===false) throw new Exception("TRP update failed: {$original}");}}
        else{$ok=$wpdb->insert($trp,['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0],['%s','%s','%d','%d']);if($ok===false) throw new Exception("TRP insert failed: {$original} DB={$wpdb->last_error}");}
    }
    echo 'UPDATED EN TranslatePress strings: '.count($translations)."\n";
    foreach($resolved as $key=>$p){
        $fresh=get_post((int)$p->ID);
        if(!$fresh||trim($fresh->post_excerpt)!==trim(wp_unslash($payload[$key]['es_excerpt']))) throw new Exception("ES excerpt verification failed {$key}");
        if(strpos($fresh->post_content,'<h2>Sobre Tolecarnes</h2>')===false||strpos($fresh->post_content,'<h2>Preguntas frecuentes</h2>')===false) throw new Exception("ES content verification failed {$key}");
        $found=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$trp}` WHERE original=%s AND translated=%s AND status=2",$products[$key]['title'],$products[$key]['en_title']));
        if($found<1) throw new Exception("EN title verification failed {$key}");
        echo "VERIFIED DB {$key}\n";
    }
}catch(Throwable $e){
    echo "FAILURE: {$e->getMessage()}\nROLLBACK START\n";
    mo_b2_restore(get_option($backup_key),$trp);
    mo_b2_fail('Batch rolled back: '.$e->getMessage());
}

echo "SUCCESS: five Tolecarnes batch 02 fichas updated ES + TranslatePress EN.\n";
