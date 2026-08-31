<?php
/** Tolecarnes product copy batch 05 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b5_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b5_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b5_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b5_pair_html($es_html,$en_html,$label){
    $es=mo_b5_segments($es_html); $en=mo_b5_segments($en_html);
    if(count($es)!==count($en)) mo_b5_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b5_fail("Tag mismatch {$label} at {$i}");
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
'filetes_segunda'=>[
 'id'=>11117,'title'=>'Filetes segunda','slug'=>'filetes-segunda','sku'=>'/filetes-segunda-de-ternera-ah',
 'old_marker'=>'Filetes de segunda de ternera obtenidos de piezas como tapa o contra.',
 'es_excerpt'=>'<p>Filetes de segunda de ternera obtenidos de piezas como la tapa o la contra. Una opción muy práctica para empanar, preparar a la plancha o utilizar en recetas cotidianas. Se entregan ya fileteados y listos para cocinar.</p>',
 'en_excerpt'=>'<p>Second-category beef steaks cut from pieces such as topside or silverside. A practical choice for breading, pan cooking or everyday recipes. They are supplied already sliced and ready to cook.</p>',
 'es_content'=><<<'HTML'
<h2>Filetes para recetas de todos los días</h2>
<p>Estos filetes proceden de piezas como la tapa o la contra y están pensados para preparaciones sencillas y habituales en casa. Al venir ya fileteados, permiten pasar directamente a la elaboración sin tener que porcionar la carne.</p>
<p>Son especialmente útiles cuando buscamos un filete para empanar, cocinar a la plancha o incorporar a platos en los que la carne se prepara en poco tiempo.</p>
<h2>Cómo prepararlos</h2>
<p>Para hacerlos a la plancha o en sartén, utiliza una superficie caliente y adapta el tiempo al grosor de cada filete. También pueden empanarse y freírse o cocinarse dentro de recetas con salsa.</p>
<p>Si están congelados, lo más recomendable es descongelarlos previamente en el frigorífico antes de cocinarlos.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Steaks for everyday meals</h2>
<p>These steaks are cut from pieces such as topside or silverside and are intended for simple, familiar home cooking. Because they are supplied already sliced, they can go straight into the chosen preparation without needing to be portioned first.</p>
<p>They are particularly useful when you want a steak for breading, quick pan cooking or dishes in which the meat is cooked in a relatively short time.</p>
<h2>How to cook them</h2>
<p>For pan or griddle cooking, use a hot surface and adjust the cooking time to the thickness of each steak. They can also be breaded and fried or cooked as part of dishes with sauce.</p>
<p>If frozen, it is best to thaw them in the refrigerator before cooking.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué piezas proceden estos filetes?</h3>
<p>Se obtienen de piezas como la tapa o la contra de ternera.</p>
<h3>¿Para qué preparaciones son adecuados?</h3>
<p>Funcionan especialmente bien para empanar, cocinar a la plancha o utilizar en recetas con salsa.</p>
<h3>¿Se entregan ya cortados?</h3>
<p>Sí. Se entregan ya fileteados y listos para su elaboración.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which cuts are these steaks taken from?</h3>
<p>They are cut from pieces such as beef topside or silverside.</p>
<h3>What are they suitable for?</h3>
<p>They work particularly well for breading, pan cooking or dishes prepared with sauce.</p>
<h3>Are they supplied already sliced?</h3>
<p>Yes. They are supplied already sliced and ready to cook.</p>
HTML,
],
'tira_churrasco'=>[
 'id'=>11120,'title'=>'Tira de churrasco argentino','slug'=>'tira-de-churrasco-argentino','sku'=>'/churrascoargentino',
 'old_marker'=>'Tira de churrasco argentino obtenida mediante un corte transversal del costillar.',
 'es_excerpt'=>'<p>Tira de churrasco argentino obtenida mediante un corte transversal del costillar. Un corte pensado para parrilla y barbacoa, presentado en tiras siguiendo el formato habitual del asado argentino. Se prepara y envasa al vacío.</p>',
 'en_excerpt'=>'<p>Argentinian-style beef rib strips cut crosswise through the rib section. A cut designed for grilling and barbecuing, prepared in the traditional strip format associated with Argentine asado. Supplied vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Un corte clásico para la parrilla</h2>
<p>La tira de churrasco argentino se obtiene cortando el costillar de forma transversal, de manera que cada tira conserva pequeñas secciones de hueso junto a la carne.</p>
<p>Es un formato muy ligado a la cocina a la parrilla y al asado argentino, aunque también puede prepararse en barbacoa o en el horno.</p>
<h2>Cómo prepararla</h2>
<p>En parrilla o barbacoa, cocina las tiras sobre una superficie bien caliente y controla el tiempo para que la carne se haga de forma uniforme alrededor del hueso.</p>
<p>También puede prepararse al horno si se prefiere una cocción más progresiva. El producto se prepara y se presenta envasado al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A classic cut for the grill</h2>
<p>Argentinian-style beef rib strips are produced by cutting crosswise through the rib section, so each strip retains small sections of bone alongside the meat.</p>
<p>This format is closely associated with grilling and Argentine asado, although it can also be cooked on a barbecue or in the oven.</p>
<h2>How to cook it</h2>
<p>On a grill or barbecue, cook the strips over a properly heated surface and control the cooking time so the meat cooks evenly around the bone.</p>
<p>It can also be prepared in the oven for a more gradual cooking method. The product is prepared and supplied vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es la tira de churrasco argentino?</h3>
<p>Es un corte transversal del costillar de vacuno presentado en tiras con pequeñas secciones de hueso.</p>
<h3>¿Cómo queda mejor?</h3>
<p>Es especialmente adecuada para parrilla o barbacoa, aunque también puede cocinarse en el horno.</p>
<h3>¿Cómo se presenta?</h3>
<p>Se prepara y se presenta envasada al vacío.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What are Argentinian-style beef rib strips?</h3>
<p>They are produced by cutting crosswise through the beef rib section and are presented as strips containing small sections of bone.</p>
<h3>What is the best way to cook them?</h3>
<p>They are particularly well suited to a grill or barbecue, although they can also be cooked in the oven.</p>
<h3>How are they supplied?</h3>
<p>They are prepared and supplied vacuum packed.</p>
HTML,
],
'vacio_ternera'=>[
 'id'=>11123,'title'=>'Vacío de ternera','slug'=>'vacio-de-ternera','sku'=>'/vacio-de-ternera-awk',
 'old_marker'=>'Vacío de ternera, corte situado en la parte interior de las costillas.',
 'es_excerpt'=>'<p>Vacío de ternera, un corte situado en la parte interior de las costillas y especialmente adecuado para parrilla, barbacoa y asados. Puede cocinarse entero o prepararse en filetes finos. Se presenta envasado al vacío.</p>',
 'en_excerpt'=>'<p>Beef vacío, a cut located on the inner side of the rib area and particularly well suited to grilling, barbecuing and roasting. It can be cooked whole or prepared as thin steaks. Supplied vacuum packed.</p>',
 'es_content'=><<<'HTML'
<h2>Un corte para cocinar entero o en filetes</h2>
<p>El vacío de ternera se encuentra en la zona interior de las costillas. Es una pieza que puede trabajarse de varias formas, tanto entera para una barbacoa o un asado como cortada en filetes finos para una cocción más rápida.</p>
<p>Esta versatilidad permite adaptarlo al tipo de comida y al método de cocción que prefieras.</p>
<h2>Cómo prepararlo</h2>
<p>Si se cocina entero, funciona especialmente bien en parrilla, barbacoa u horno. Cuando se prepara en filetes finos, puede cocinarse sobre una plancha o sartén bien caliente.</p>
<p>Una vez cocinado, conviene cortarlo en sentido contrario a la fibra para conseguir una textura más agradable al comerlo. Se presenta envasado al vacío.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A cut that can be cooked whole or sliced</h2>
<p>Beef vacío is located on the inner side of the rib area. It can be prepared in several ways, either cooked whole for a barbecue or roast or sliced into thin steaks for quicker cooking.</p>
<p>This makes it easy to adapt the cut to the type of meal and cooking method you prefer.</p>
<h2>How to cook it</h2>
<p>When cooked whole, it works particularly well on a grill, barbecue or in the oven. When prepared as thin steaks, it can be cooked on a hot griddle or frying pan.</p>
<p>After cooking, slice it across the grain for a more pleasant texture when eating. It is supplied vacuum packed.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Dónde se encuentra el vacío de ternera?</h3>
<p>Es un corte situado en la parte interior de las costillas.</p>
<h3>¿Se puede cocinar entero?</h3>
<p>Sí. Puede cocinarse entero en parrilla, barbacoa u horno, o prepararse en filetes finos.</p>
<h3>¿Cómo conviene cortarlo después de cocinarlo?</h3>
<p>Lo recomendable es cortarlo en sentido contrario a la fibra.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Where is beef vacío located?</h3>
<p>It is a cut located on the inner side of the rib area.</p>
<h3>Can it be cooked whole?</h3>
<p>Yes. It can be cooked whole on a grill, barbecue or in the oven, or prepared as thin steaks.</p>
<h3>How should it be sliced after cooking?</h3>
<p>It is best sliced across the grain.</p>
HTML,
],
'solomillo_vaca'=>[
 'id'=>11125,'title'=>'Solomillo de vaca','slug'=>'solomillo-de-vaca','sku'=>'/solomillo-de-vaca-add',
 'old_marker'=>'Solomillo de vaca, corte de la zona lumbar de textura tierna y con poca grasa.',
 'es_excerpt'=>'<p>Solomillo de vaca procedente de la zona lumbar, de textura tierna y con poca grasa. Se prepara en medallones de aproximadamente 200 g, por lo que 1 kg suele equivaler a unas cinco piezas. Ideal para plancha, sartén y cocciones rápidas.</p>',
 'en_excerpt'=>'<p>Beef tenderloin from the loin area, with a tender texture and little fat. It is prepared as medallions of approximately 200 g, so 1 kg usually corresponds to around five pieces. Ideal for a griddle, frying pan and quick cooking.</p>',
 'es_content'=><<<'HTML'
<h2>Uno de los cortes más tiernos de la vaca</h2>
<p>El solomillo se encuentra en la zona lumbar y destaca por su textura tierna y su bajo contenido en grasa. En este caso se prepara en medallones de aproximadamente 200 g, un formato cómodo para cocinar las piezas de manera individual.</p>
<p>Un kilogramo suele equivaler aproximadamente a cinco medallones, aunque el número puede variar ligeramente según el tamaño de cada corte.</p>
<h2>Cómo prepararlo</h2>
<p>Los medallones funcionan especialmente bien en sartén o plancha a temperatura alta, con una cocción relativamente rápida. El tiempo dependerá del grosor de cada pieza y del punto que se quiera conseguir.</p>
<p>Antes de servir, puede dejarse reposar brevemente para que los jugos se redistribuyan dentro de la carne.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>One of the most tender beef cuts</h2>
<p>Tenderloin is located in the loin area and is known for its tender texture and low fat content. Here it is prepared as medallions of approximately 200 g, a convenient format for cooking individual portions.</p>
<p>One kilogram usually corresponds to around five medallions, although the exact number may vary slightly depending on the size of each cut.</p>
<h2>How to cook it</h2>
<p>The medallions work particularly well in a frying pan or on a griddle at high heat, using a relatively quick cooking method. The time will depend on the thickness of each piece and your preferred doneness.</p>
<p>Before serving, let the meat rest briefly so the juices can redistribute through the cut.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cómo se presenta el solomillo?</h3>
<p>Se prepara en medallones de aproximadamente 200 g.</p>
<h3>¿Cuántos medallones suele haber en 1 kg?</h3>
<p>Aproximadamente cinco, aunque puede haber pequeñas variaciones según el tamaño de las piezas.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Es especialmente adecuado para sartén o plancha y para preparaciones de cocción rápida.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How is the tenderloin prepared?</h3>
<p>It is cut into medallions of approximately 200 g.</p>
<h3>How many medallions are usually in 1 kg?</h3>
<p>Around five, although the exact number can vary slightly depending on the size of the pieces.</p>
<h3>How can it be cooked?</h3>
<p>It is particularly well suited to a frying pan or griddle and to quick-cooking preparations.</p>
HTML,
],
'canon_espaldilla'=>[
 'id'=>11127,'title'=>'Cañon de espaldilla','slug'=>'canon-de-espaldilla','sku'=>'/canon-de-espaldilla',
 'old_marker'=>'Solomillo del carnicero para hacer a la plancha en filetitos o asado al horno.',
 'es_excerpt'=>'<p>Cañón de espaldilla de vacuno, una pieza conocida también como “solomillo del carnicero”. Puede cortarse en filetes pequeños para cocinar a la plancha o prepararse entera como asado al horno.</p>',
 'en_excerpt'=>'<p>Beef shoulder tender, a cut also known as the “butcher’s tender”. It can be sliced into small steaks for griddle cooking or prepared whole as an oven roast.</p>',
 'es_content'=><<<'HTML'
<h2>Una pieza con dos formas sencillas de prepararla</h2>
<p>El cañón de espaldilla es una pieza de vacuno conocida también como “solomillo del carnicero”. Puede utilizarse entera o cortarse en filetes pequeños según la preparación que se quiera hacer.</p>
<p>Su formato permite pasar de una comida rápida a la plancha a un asado más pausado sin necesidad de cambiar de corte.</p>
<h2>Cómo prepararlo</h2>
<p>Para una preparación rápida, puede cortarse en filetes pequeños y cocinarse sobre una plancha o sartén caliente.</p>
<p>También puede prepararse entero al horno. En ese caso, controlar el tiempo de cocción según el tamaño de la pieza ayuda a evitar que permanezca más tiempo del necesario en el calor.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>One cut, two simple ways to cook it</h2>
<p>Beef shoulder tender is a cut also known as the “butcher’s tender”. It can be cooked whole or sliced into small steaks depending on the preparation you want to make.</p>
<p>Its format makes it suitable for both a quick griddle meal and a slower oven roast without changing to a different cut.</p>
<h2>How to cook it</h2>
<p>For a quick preparation, slice it into small steaks and cook them on a hot griddle or frying pan.</p>
<p>It can also be roasted whole in the oven. In that case, adjust the cooking time to the size of the piece so it does not remain under heat longer than necessary.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es el cañón de espaldilla?</h3>
<p>Es una pieza de vacuno conocida también como “solomillo del carnicero”.</p>
<h3>¿Se puede hacer a la plancha?</h3>
<p>Sí. Puede cortarse en filetes pequeños y cocinarse en plancha o sartén.</p>
<h3>¿Se puede cocinar entero?</h3>
<p>Sí. También puede prepararse entero como asado al horno.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is beef shoulder tender?</h3>
<p>It is a beef cut also known as the “butcher’s tender”.</p>
<h3>Can it be cooked on a griddle?</h3>
<p>Yes. It can be sliced into small steaks and cooked on a griddle or in a frying pan.</p>
<h3>Can it be cooked whole?</h3>
<p>Yes. It can also be prepared whole as an oven roast.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b5_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_b5_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_b5_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b5_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b5_fail("SKU mismatch {$key}");
    if(stripos(mo_b5_vendor($p),'tolecarnes')===false) mo_b5_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b5_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre Tolecarnes')===false) mo_b5_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    foreach(mo_b5_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b5_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<35) mo_b5_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_tolecarnes_batch05_backup_20260831';
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
    if(!add_option($backup_key,$backup,'',false)) mo_b5_fail('Could not create batch 05 backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($resolved as $key=>$p){
    $r=wp_update_post(['ID'=>(int)$p->ID,'post_excerpt'=>$payload[$key]['es_excerpt'],'post_content'=>$payload[$key]['es_content']],true);
    if(is_wp_error($r)) mo_b5_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_b5_fail("English long-content meta update failed {$key}");
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
            if($ok===false) mo_b5_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_b5_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre Tolecarnes')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_b5_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About Tolecarnes')===false || stripos($en,'Frequently asked questions')===false) mo_b5_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE batch05 products=5 translations=".count($translations)."\n";
