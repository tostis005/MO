<?php
/**
 * Tolecarnes product copy batch 03 (ES + EN).
 * Products: 11092, 11095, 11097, 11099, 11103.
 * Scope: Spanish WooCommerce excerpt/content, TranslatePress ES→EN strings,
 *        and the custom _en_US_post_content field actually used by the English description tab.
 */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b3_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b3_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b3_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b3_pair_html($es_html,$en_html,$label){
    $es=mo_b3_segments($es_html); $en=mo_b3_segments($en_html);
    if(count($es)!==count($en)) mo_b3_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b3_fail("Tag mismatch {$label} at {$i}");
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
'churrasco_ternera'=>[
 'id'=>11092,'title'=>'Churrasco de ternera','slug'=>'churrasco-de-ternera','sku'=>'/churrasco-de-ternera-awq',
 'old_marker'=>'Corte indispensable a la hora de preparar cualquier parrillada o barbacoa',
 'es_excerpt'=>'<p>Churrasco de ternera con grasa natural infiltrada que ayuda a mantener la jugosidad durante la cocción. Un corte especialmente adecuado para parrilla y barbacoa. Se presenta en bandeja de 1 kg y envasado al vacío.</p>',
 'en_excerpt'=>'<p>Beef churrasco with natural marbling that helps the meat remain juicy during cooking. A cut particularly well suited to grilling and barbecuing. Supplied in a 1 kg tray and vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Un corte pensado para la parrilla</h2>
<p>El churrasco de ternera es una opción especialmente indicada para cocinar sobre fuego vivo. En este corte destaca la presencia de grasa natural infiltrada, una característica que ayuda a mantener la jugosidad y aporta sabor durante la cocción.</p>
<p>Es una pieza muy práctica para preparar una parrillada o una barbacoa sin necesidad de elaboraciones complicadas. Una cocción bien controlada y una guarnición sencilla permiten que la carne sea la protagonista.</p>
<h2>Cómo prepararlo</h2>
<p>Prepara la parrilla o la barbacoa con una temperatura suficiente antes de colocar la carne. Cocina el churrasco por ambos lados y ajusta el tiempo al grosor de las piezas, evitando mantenerlo sobre un calor intenso más tiempo del necesario.</p>
<p>También puede cocinarse en una plancha amplia y bien caliente. Después de cocinarlo, un breve reposo antes de servir ayuda a conservar mejor los jugos.</p>
<p>El producto se presenta en una bandeja de 1 kg y envasado al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A cut made for the grill</h2>
<p>Beef churrasco is particularly well suited to cooking over high heat. This cut has natural marbling, a characteristic that helps it remain juicy and contributes flavour during cooking.</p>
<p>It is a practical choice for a mixed grill or barbecue without the need for complicated preparation. Careful cooking and a simple side dish allow the meat itself to remain the focus.</p>
<h2>How to cook it</h2>
<p>Bring the grill or barbecue up to temperature before adding the meat. Cook the churrasco on both sides and adjust the time to the thickness of the pieces, avoiding leaving it over intense heat longer than necessary.</p>
<p>It can also be cooked on a large, properly heated griddle. After cooking, allow it to rest briefly before serving to help retain its juices.</p>
<p>The product is supplied in a 1 kg tray and vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Por qué es adecuado para barbacoa?</h3>
<p>La grasa natural infiltrada ayuda a mantener la jugosidad durante una cocción intensa y hace que sea un corte especialmente apropiado para parrilla o barbacoa.</p>
<h3>¿Cómo se presenta?</h3>
<p>Se presenta en una bandeja de 1 kg y envasado al vacío.</p>
<h3>¿Se puede cocinar en plancha?</h3>
<p>Sí. También puede prepararse en una plancha bien caliente, controlando el tiempo para no secar innecesariamente la carne.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Why is it suitable for a barbecue?</h3>
<p>Its natural marbling helps retain juiciness during high-heat cooking, making it particularly well suited to grilling or barbecuing.</p>
<h3>How is it supplied?</h3>
<p>It is supplied in a 1 kg tray and vacuum packed.</p>
<h3>Can I cook it on a griddle?</h3>
<p>Yes. It can also be cooked on a properly heated griddle, controlling the cooking time so the meat does not dry out unnecessarily.</p>
HTML,
],
'pito_fileteado'=>[
 'id'=>11095,'title'=>'Pito de vacuno fileteado','slug'=>'pito-de-vacuno-fileteado','sku'=>'/pito-de-vacuno-fileteado',
 'old_marker'=>'Pito de vacuno fileteado, una pieza situada próxima a la entraña',
 'es_excerpt'=>'<p>Pito de vacuno fileteado, una pieza próxima a la entraña que destaca por su sabor intenso y su textura jugosa. Se presenta ya cortado en filetes para facilitar una preparación rápida en sartén, plancha o parrilla.</p>',
 'en_excerpt'=>'<p>Sliced beef pito, a cut located close to the skirt that stands out for its pronounced flavour and juicy texture. It is supplied already sliced for quick cooking in a frying pan, on a griddle or on the grill.</p>',
 'es_content'=><<<'HTML'
<h2>Un corte menos habitual del vacuno</h2>
<p>El pito de vacuno es una pieza situada próxima a la entraña. Se caracteriza por un sabor marcado y una textura jugosa, y permite descubrir una parte menos habitual del despiece de vacuno.</p>
<p>En este producto la pieza se presenta ya fileteada, de modo que no es necesario porcionarla en casa antes de cocinarla.</p>
<h2>Cómo prepararlo</h2>
<p>Al venir cortado en filetes, resulta especialmente cómodo para preparaciones rápidas. Puede cocinarse en sartén, plancha o parrilla utilizando una superficie bien caliente.</p>
<p>Cocina los filetes por ambos lados y controla el tiempo en función de su grosor. Evitar una cocción excesivamente larga ayuda a conservar mejor la jugosidad del corte.</p>
<p>Puede servirse con una guarnición sencilla de verduras, patatas o ensalada.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A less common beef cut</h2>
<p>Beef pito is a cut located close to the skirt. It is characterised by a pronounced flavour and juicy texture and offers a chance to try a less common part of the beef carcass.</p>
<p>For this product, the cut is supplied already sliced, so there is no need to portion it at home before cooking.</p>
<h2>How to cook it</h2>
<p>Because it is already sliced into steaks, it is particularly convenient for quick cooking. It can be prepared in a frying pan, on a griddle or on the grill using a properly heated surface.</p>
<p>Cook the steaks on both sides and adjust the time to their thickness. Avoiding unnecessarily long cooking helps retain the juiciness of the cut.</p>
<p>Serve it with a simple side of vegetables, potatoes or salad.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es el pito de vacuno?</h3>
<p>Es una pieza del despiece de vacuno situada próxima a la entraña y caracterizada por un sabor intenso y una textura jugosa.</p>
<h3>¿Cómo se presenta?</h3>
<p>Se entrega ya cortado en filetes para facilitar su preparación.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Puede prepararse en sartén, plancha o parrilla mediante una cocción relativamente rápida.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is beef pito?</h3>
<p>It is a beef cut located close to the skirt and characterised by a pronounced flavour and juicy texture.</p>
<h3>How is it supplied?</h3>
<p>It is supplied already sliced into steaks for easier preparation.</p>
<h3>How can it be cooked?</h3>
<p>It can be cooked in a frying pan, on a griddle or on the grill using a relatively quick cooking method.</p>
HTML,
],
'huesos_ternera'=>[
 'id'=>11097,'title'=>'Huesos de ternera','slug'=>'huesos-de-ternera','sku'=>'/huesos-de-ternera-arr',
 'old_marker'=>'Huesos frescos de ternera preparados para elaborar caldos, fondos, guisos y cocidos',
 'es_excerpt'=>'<p>Huesos frescos de ternera cortados y limpios, preparados para elaborar caldos, fondos, guisos y cocidos. Se presentan envasados al vacío y listos para incorporarlos a recetas que necesiten una base de carne y hueso.</p>',
 'en_excerpt'=>'<p>Fresh beef bones, cut and cleaned for making stocks, broths, stews and traditional boiled dishes. They are supplied vacuum packed and ready to use in recipes that call for a meat-and-bone base.</p>',
 'es_content'=><<<'HTML'
<h2>Una base para caldos y fondos</h2>
<p>Los huesos de ternera son un ingrediente tradicional para preparar caldos, fondos, cocidos y guisos. Durante una cocción prolongada ayudan a aportar sabor y cuerpo al líquido en el que se cocinan.</p>
<p>En este caso los huesos se entregan ya cortados y limpios, lo que permite incorporarlos directamente a la preparación elegida.</p>
<h2>Cómo utilizarlos</h2>
<p>Pueden cocinarse directamente con agua, verduras y otros ingredientes para preparar un caldo, o utilizarse como base para fondos que después formen parte de arroces, salsas, guisos y otras recetas.</p>
<p>También pueden dorarse previamente en el horno si se busca un fondo con un perfil más tostado antes de continuar con la cocción en líquido.</p>
<p>Se presentan envasados al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A base for stocks and broths</h2>
<p>Beef bones are a traditional ingredient for making stocks, broths, stews and boiled dishes. During long cooking they help add flavour and body to the cooking liquid.</p>
<p>These bones are supplied already cut and cleaned, making them ready to add directly to the chosen preparation.</p>
<h2>How to use them</h2>
<p>They can be cooked directly with water, vegetables and other ingredients to make a broth, or used as the base for stocks that later form part of rice dishes, sauces, stews and other recipes.</p>
<p>They can also be browned in the oven first when a more roasted profile is wanted before continuing the preparation with liquid.</p>
<p>They are supplied vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Para qué se utilizan los huesos de ternera?</h3>
<p>Son especialmente útiles para preparar caldos, fondos, cocidos, guisos y bases que después pueden utilizarse en otras recetas.</p>
<h3>¿Hay que cortarlos o limpiarlos en casa?</h3>
<p>No. Se entregan ya cortados y limpios para facilitar su uso.</p>
<h3>¿Se pueden dorar antes de hacer un caldo?</h3>
<p>Sí. Dorarlos previamente en el horno es una opción cuando se busca un fondo con notas más tostadas.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are beef bones used for?</h3>
<p>They are particularly useful for making stocks, broths, boiled dishes, stews and bases that can later be used in other recipes.</p>
<h3>Do I need to cut or clean them at home?</h3>
<p>No. They are supplied already cut and cleaned for easier use.</p>
<h3>Can they be browned before making stock?</h3>
<p>Yes. Browning them in the oven first is an option when you want a stock with more roasted notes.</p>
HTML,
],
'entrecot_vaca'=>[
 'id'=>11099,'title'=>'Entrecot de vaca','slug'=>'entrecot-de-vaca','sku'=>'/entrecot-de-vaca-adf',
 'old_marker'=>'Entrecot de vaca con grasa infiltrada y sabor intenso',
 'es_excerpt'=>'<p>Entrecot de vaca con grasa infiltrada, sabor intenso y un periodo de maduración superior a 20 días. Se presenta fileteado en bandejas envasadas al vacío; de forma orientativa, un kilogramo suele contener entre cuatro y cinco piezas.</p>',
 'en_excerpt'=>'<p>Beef entrecote with intramuscular fat, a pronounced flavour and a maturation period of more than 20 days. It is supplied as steaks in vacuum-packed trays; as a guide, one kilogram usually contains around four to five pieces.</p>',
 'es_content'=><<<'HTML'
<h2>Entrecot de vaca con más de 20 días de maduración</h2>
<p>Este entrecot de vaca se caracteriza por la presencia de grasa infiltrada y un sabor intenso. La carne procede de piezas con un periodo de maduración superior a 20 días, un dato que forma parte de la preparación específica de este producto.</p>
<p>Se presenta ya cortado en filetes, por lo que está listo para una elaboración sencilla en la que la carne sea la protagonista.</p>
<h2>Cómo prepararlo</h2>
<p>Puede cocinarse en sartén, plancha, parrilla o barbacoa. Utiliza una superficie bien caliente y adapta el tiempo de cocción al grosor de cada filete y al punto que prefieras.</p>
<p>Después de cocinarlo, deja reposar la carne brevemente antes de cortarla. Puede acompañarse simplemente con sal y una guarnición de verduras, patatas o ensalada.</p>
<p>Como referencia, un kilogramo suele contener aproximadamente cuatro o cinco piezas y el producto se presenta en bandejas envasadas al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Beef entrecote matured for more than 20 days</h2>
<p>This beef entrecote is characterised by intramuscular fat and a pronounced flavour. The meat comes from cuts with a maturation period of more than 20 days, which is part of the specific preparation of this product.</p>
<p>It is supplied already cut into steaks, ready for a simple preparation in which the meat itself can remain the focus.</p>
<h2>How to cook it</h2>
<p>It can be cooked in a frying pan, on a griddle, on the grill or on a barbecue. Use a properly heated surface and adjust the cooking time to the thickness of each steak and your preferred doneness.</p>
<p>After cooking, allow the meat to rest briefly before slicing. It can be served simply with salt and a side of vegetables, potatoes or salad.</p>
<p>As a guide, one kilogram usually contains around four to five pieces, and the product is supplied in vacuum-packed trays.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuánto tiempo de maduración tiene?</h3>
<p>La carne procede de piezas con un periodo de maduración superior a 20 días.</p>
<h3>¿Cuántas piezas suele haber por kilogramo?</h3>
<p>De forma orientativa, un kilogramo suele contener aproximadamente cuatro o cinco filetes.</p>
<h3>¿Cómo se recomienda cocinarlo?</h3>
<p>Es especialmente adecuado para sartén, plancha, parrilla o barbacoa, ajustando el tiempo al grosor del filete y al punto de cocción deseado.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How long is the meat matured for?</h3>
<p>The meat comes from cuts with a maturation period of more than 20 days.</p>
<h3>How many steaks are there per kilogram?</h3>
<p>As a guide, one kilogram usually contains approximately four to five steaks.</p>
<h3>How should I cook it?</h3>
<p>It is particularly suitable for a frying pan, griddle, grill or barbecue, adjusting the cooking time to the thickness of the steak and your preferred doneness.</p>
HTML,
],
'redondo_ternera'=>[
 'id'=>11103,'title'=>'Redondo de ternera','slug'=>'redondo-de-ternera','sku'=>'/redondo-de-ternera-aj',
 'old_marker'=>'Redondo de ternera, una pieza cilíndrica y muy magra procedente de la pierna trasera',
 'es_excerpt'=>'<p>Redondo de ternera, una pieza cilíndrica y muy magra procedente de la pierna trasera. Puede cocinarse entero o porcionado para asados, guisos, medallones y preparaciones tipo carne mechada. Se presenta envasado al vacío.</p>',
 'en_excerpt'=>'<p>Beef eye of round, a cylindrical and very lean cut from the hind leg. It can be cooked whole or portioned for roasting, stews, medallions and stuffed-style preparations. Supplied vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Una pieza magra y de forma regular</h2>
<p>El redondo procede de la pierna trasera de la ternera y se reconoce por su forma cilíndrica y por ser una pieza muy magra. Su formato permite trabajarlo entero o cortarlo en porciones según la receta.</p>
<p>Al tener poca grasa, conviene adaptar la técnica y el tiempo de cocción para evitar que la carne se seque más de lo necesario.</p>
<h2>Cómo utilizarlo</h2>
<p>Puede prepararse entero al horno, utilizarse en guisos o cortarse en medallones. También es adecuado para elaboraciones tipo carne mechada y otras recetas en las que interesa una pieza de forma regular.</p>
<p>Para un asado, puede marcarse primero el exterior y continuar después la cocción de forma más suave. En guisos, una cocción con líquido permite integrar la carne con la salsa y el resto de ingredientes.</p>
<p>El producto se presenta envasado al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A lean cut with a regular shape</h2>
<p>Eye of round comes from the beef hind leg and is recognised by its cylindrical shape and very lean character. Its shape makes it suitable for cooking whole or cutting into portions depending on the recipe.</p>
<p>Because it contains little fat, the cooking method and time should be controlled so the meat does not dry out more than necessary.</p>
<h2>How to use it</h2>
<p>It can be roasted whole, used in stews or cut into medallions. It is also suitable for stuffed-style preparations and other recipes where a regularly shaped piece is useful.</p>
<p>For roasting, the outside can be browned first before continuing with gentler cooking. In stews, cooking with liquid allows the meat to become part of the sauce and the other ingredients.</p>
<p>The product is supplied vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué parte procede el redondo?</h3>
<p>Procede de la pierna trasera de la ternera.</p>
<h3>¿Es una pieza grasa?</h3>
<p>No. El redondo es una pieza muy magra, por lo que conviene controlar la cocción para no secarla innecesariamente.</p>
<h3>¿Para qué recetas se puede utilizar?</h3>
<p>Puede utilizarse para asados, guisos, medallones, carne mechada y otras preparaciones tanto con la pieza entera como porcionada.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which part does eye of round come from?</h3>
<p>It comes from the beef hind leg.</p>
<h3>Is it a fatty cut?</h3>
<p>No. Eye of round is a very lean cut, so the cooking time should be controlled to avoid drying it out unnecessarily.</p>
<h3>What recipes can I use it for?</h3>
<p>It can be used for roasting, stews, medallions, stuffed-style preparations and other dishes, either whole or portioned.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b3_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_b3_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_b3_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b3_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b3_fail("SKU mismatch {$key}");
    if(stripos(mo_b3_vendor($p),'tolecarnes')===false) mo_b3_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b3_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre Tolecarnes')===false) mo_b3_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    foreach(mo_b3_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b3_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<35) mo_b3_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_tolecarnes_batch03_backup_20260831';
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
    if(!add_option($backup_key,$backup,'',false)) mo_b3_fail('Could not create batch 03 backup');
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
    if(is_wp_error($r)) mo_b3_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_b3_fail("English long-content meta update failed {$key}");
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
            if($ok===false) mo_b3_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_b3_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre Tolecarnes')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_b3_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About Tolecarnes')===false || stripos($en,'Frequently asked questions')===false) mo_b3_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE batch03 products=5 translations=".count($translations)."\n";
