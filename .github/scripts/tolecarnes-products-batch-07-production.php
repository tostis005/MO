<?php
/** Tolecarnes product copy batch 07 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b7_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b7_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b7_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b7_pair_html($es_html,$en_html,$label){
    $es=mo_b7_segments($es_html); $en=mo_b7_segments($en_html);
    if(count($es)!==count($en)) mo_b7_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b7_fail("Tag mismatch {$label} at {$i}");
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
'pecho_filetes'=>[
 'id'=>11141,'title'=>'Pecho en filetes de ternera','title_en'=>'Beef brisket steaks','slug'=>'pecho-en-filetes-de-ternera','sku'=>'/pechoternera',
 'old_marker'=>'Pecho de ternera cortado en filetes pequeños',
 'es_excerpt'=>'<p>Pecho de ternera cortado en filetes pequeños y listo para cocinar. Procede de la parte delantera del animal y se presenta ya fileteado en porciones manejables, especialmente prácticas para preparar a la plancha o en sartén.</p>',
 'en_excerpt'=>'<p>Beef brisket cut into small steaks and ready to cook. Taken from the front of the animal and supplied already sliced into manageable portions, particularly practical for cooking on a griddle or in a frying pan.</p>',
 'es_content'=><<<'HTML'
<h2>El pecho de ternera, ya preparado en filetes</h2>
<p>El pecho se encuentra en la parte delantera de la ternera. En este caso se prepara en filetes pequeños, de manera que puedes pasar directamente a la cocción sin tener que porcionar la pieza en casa.</p>
<p>Es un formato pensado para preparaciones sencillas y para quienes prefieren recibir la carne ya cortada en porciones fáciles de manejar.</p>
<h2>Cómo prepararlos</h2>
<p>Para hacerlos a la plancha o en sartén, utiliza una superficie bien caliente y cocina los filetes por ambos lados, adaptando el tiempo al grosor de cada pieza.</p>
<p>También pueden acompañarse con verduras, patatas u otras guarniciones sencillas. Si están congelados, es preferible descongelarlos previamente en el frigorífico.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Beef brisket, already prepared as steaks</h2>
<p>Brisket comes from the front part of the animal. Here it is prepared as small steaks, so it can go straight into the chosen cooking method without needing to be portioned at home.</p>
<p>It is a practical format for simple meals and for anyone who prefers to receive the beef already cut into easy-to-handle portions.</p>
<h2>How to cook them</h2>
<p>For griddle or pan cooking, use a properly heated surface and cook the steaks on both sides, adjusting the time to the thickness of each piece.</p>
<p>They can be served with vegetables, potatoes or another simple side dish. If frozen, it is best to thaw them in the refrigerator before cooking.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué parte procede el pecho de ternera?</h3>
<p>Procede de la parte delantera del animal.</p>
<h3>¿Se entrega ya fileteado?</h3>
<p>Sí. Se presenta cortado en filetes pequeños y listo para cocinar.</p>
<h3>¿Cómo se puede preparar?</h3>
<p>Está pensado especialmente para prepararlo a la plancha o en sartén.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which part of the animal does brisket come from?</h3>
<p>It comes from the front part of the animal.</p>
<h3>Is it supplied already sliced?</h3>
<p>Yes. It is supplied cut into small steaks and ready to cook.</p>
<h3>How can it be prepared?</h3>
<p>It is particularly suited to griddle or pan cooking.</p>
HTML,
],
'tomahawk_aguja'=>[
 'id'=>11145,'title'=>'Tomahawk aguja','title_en'=>'Beef chuck Tomahawk','slug'=>'tomahawk-aguja','sku'=>'/tomahawk-aguja',
 'old_marker'=>'Aguja de Ternera en formato Tomahawk',
 'es_excerpt'=>'<p>Aguja de ternera en formato Tomahawk, con el medallón de la aguja unido al hueso largo de la costilla. Cada pieza pesa aproximadamente entre 0,8 y 1 kg. Se vende por unidad y es un corte especialmente vistoso para parrilla, barbacoa u horno.</p>',
 'en_excerpt'=>'<p>Beef chuck in Tomahawk format, with the chuck medallion attached to the long rib bone. Each piece weighs approximately 0.8 to 1 kg. Sold individually and particularly striking for grilling, barbecuing or roasting.</p>',
 'es_content'=><<<'HTML'
<h2>La aguja en formato Tomahawk</h2>
<p>Este corte mantiene el medallón de la aguja unido al hueso largo de la costilla, dando lugar al característico formato Tomahawk. Cada unidad pesa aproximadamente entre 0,8 y 1 kg.</p>
<p>Por su tamaño y presentación es una pieza pensada para cocinar entera y llevar después a la mesa para cortarla y compartirla.</p>
<h2>Cómo prepararlo</h2>
<p>En parrilla o barbacoa, puedes marcar primero la superficie sobre una zona de calor más intenso y continuar después con una cocción más moderada para que el calor avance de forma uniforme hacia el interior.</p>
<p>También puede terminarse en el horno. Una vez cocinado, conviene dejarlo reposar unos minutos antes de cortarlo.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Chuck in Tomahawk format</h2>
<p>This cut keeps the chuck medallion attached to the long rib bone, creating the distinctive Tomahawk shape. Each piece weighs approximately 0.8 to 1 kg.</p>
<p>Because of its size and presentation, it is designed to be cooked whole and then brought to the table for slicing and sharing.</p>
<h2>How to cook it</h2>
<p>On a grill or barbecue, you can first sear the surface over stronger heat and then continue over more moderate heat so that the centre cooks more evenly.</p>
<p>It can also be finished in the oven. Once cooked, allow it to rest for a few minutes before slicing.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es el Tomahawk de aguja?</h3>
<p>Es un corte en el que el medallón de la aguja conserva unido el hueso largo de la costilla.</p>
<h3>¿Cuánto pesa cada pieza?</h3>
<p>Cada unidad pesa aproximadamente entre 0,8 y 1 kg.</p>
<h3>¿Cómo se vende?</h3>
<p>Se vende por unidad.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is a chuck Tomahawk?</h3>
<p>It is a cut in which the chuck medallion remains attached to the long rib bone.</p>
<h3>How much does each piece weigh?</h3>
<p>Each piece weighs approximately 0.8 to 1 kg.</p>
<h3>How is it sold?</h3>
<p>It is sold individually.</p>
HTML,
],
'burger_ternera'=>[
 'id'=>11148,'title'=>'Burger 100% ternera (2 unidades)','title_en'=>'100% Beef Burgers (2 pieces)','slug'=>'burger-100-ternera-2-unidades','sku'=>'/burguer-100-ternera',
 'old_marker'=>'hamburguesas sin gluten elaboradas artesanalmente',
 'es_excerpt'=>'<p>Hamburguesas elaboradas artesanalmente con 100% carne de ternera y sin gluten. La bandeja incluye 2 unidades de aproximadamente 150 g cada una, listas para cocinar en sartén, plancha o barbacoa.</p>',
 'en_excerpt'=>'<p>Handmade burgers prepared with 100% beef and gluten-free. Each tray contains 2 burgers of approximately 150 g each, ready to cook in a frying pan, on a griddle or on the barbecue.</p>',
 'es_content'=><<<'HTML'
<h2>Hamburguesas 100% de ternera</h2>
<p>Estas hamburguesas se elaboran artesanalmente utilizando carne de ternera y no contienen gluten. Cada bandeja incluye dos unidades de aproximadamente 150 g, un formato cómodo tanto para una comida rápida como para preparar hamburguesas en casa.</p>
<p>Al estar ya formadas, solo necesitas elegir el método de cocción y el acompañamiento.</p>
<h2>Cómo prepararlas</h2>
<p>Puedes cocinarlas en sartén, plancha o barbacoa. Utiliza una superficie caliente y dales la vuelta durante la cocción para que se hagan de forma uniforme.</p>
<p>Al tratarse de carne picada, deben cocinarse completamente antes de consumirlas. Puedes servirlas en pan o acompañarlas directamente con verduras, patatas o ensalada.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>100% beef burgers</h2>
<p>These burgers are handmade using beef and are gluten-free. Each tray contains two burgers of approximately 150 g, a convenient format for a quick meal or for making burgers at home.</p>
<p>Because they are already shaped, all you need to choose is the cooking method and the side dish.</p>
<h2>How to cook them</h2>
<p>They can be cooked in a frying pan, on a griddle or on the barbecue. Use a hot cooking surface and turn them during cooking so that they cook evenly.</p>
<p>As they are made from ground meat, they should be thoroughly cooked before eating. Serve them in a bun or directly with vegetables, potatoes or salad.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Cuántas hamburguesas incluye la bandeja?</h3>
<p>Incluye dos unidades de aproximadamente 150 g cada una.</p>
<h3>¿Llevan gluten?</h3>
<p>No. Estas hamburguesas se elaboran sin gluten.</p>
<h3>¿Son 100% de ternera?</h3>
<p>Sí. Están elaboradas con carne de ternera.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>How many burgers are included?</h3>
<p>The tray contains two burgers of approximately 150 g each.</p>
<h3>Are they gluten-free?</h3>
<p>Yes. These burgers are made without gluten.</p>
<h3>Are they 100% beef?</h3>
<p>Yes. They are made with beef.</p>
HTML,
],
'roast_beef'=>[
 'id'=>11154,'title'=>'Roast beef de ternera','title_en'=>'Beef roast','slug'=>'roast-beef-de-ternera','sku'=>'/roast-beef-de-ternera-adq',
 'old_marker'=>'Roast beef de ternera preparado a partir de lomo bajo',
 'es_excerpt'=>'<p>Roast beef de ternera preparado a partir de lomo bajo en una sola pieza de aproximadamente 1,5 kg. Está pensado principalmente para asar y puede servirse caliente, templado o frío, por lo que permite aprovechar una misma elaboración de distintas formas.</p>',
 'en_excerpt'=>'<p>Beef roast prepared from striploin in a single piece weighing approximately 1.5 kg. Intended mainly for roasting and suitable for serving hot, warm or cold, allowing the same preparation to be enjoyed in different ways.</p>',
 'es_content'=><<<'HTML'
<h2>Una pieza de lomo bajo para asar</h2>
<p>Este roast beef se prepara a partir de lomo bajo de ternera y se presenta en una sola pieza de aproximadamente 1,5 kg. El formato entero permite asarlo y cortarlo después en lonchas del grosor que prefieras.</p>
<p>Puede servirse recién hecho, templado o frío y acompañarse con distintas salsas o guarniciones según el tipo de comida.</p>
<h2>Cómo prepararlo</h2>
<p>Antes de introducirlo en el horno, puedes dorar la superficie en una sartén o cazuela caliente. Después continúa la cocción en el horno, adaptando el tiempo al tamaño de la pieza y al punto que busques.</p>
<p>Una vez fuera del horno, dejar reposar la carne antes de cortarla ayuda a que conserve mejor sus jugos. Para servirla fría, deja que se enfríe antes de lonchearla.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A striploin joint for roasting</h2>
<p>This roast is prepared from beef striploin and supplied as a single piece weighing approximately 1.5 kg. Keeping the joint whole allows it to be roasted first and then sliced to the thickness you prefer.</p>
<p>It can be served freshly cooked, warm or cold and paired with different sauces or side dishes depending on the meal.</p>
<h2>How to cook it</h2>
<p>Before placing it in the oven, you can brown the outside in a hot frying pan or casserole. Then continue cooking in the oven, adjusting the time to the size of the joint and the level of doneness you prefer.</p>
<p>Once out of the oven, allowing the meat to rest before slicing helps it retain its juices. To serve it cold, let it cool before slicing.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué pieza se prepara este roast beef?</h3>
<p>Se prepara a partir de lomo bajo de ternera.</p>
<h3>¿Cuánto pesa la pieza?</h3>
<p>El peso aproximado es de 1,5 kg.</p>
<h3>¿Se puede servir frío?</h3>
<p>Sí. Puede servirse caliente, templado o frío.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which cut is used for this roast?</h3>
<p>It is prepared from beef striploin.</p>
<h3>How much does the joint weigh?</h3>
<p>It weighs approximately 1.5 kg.</p>
<h3>Can it be served cold?</h3>
<p>Yes. It can be served hot, warm or cold.</p>
HTML,
],
'lote_tomahawk_vino'=>[
 'id'=>11156,'title'=>'Lote 2 tomahawk de aguja y vino','title_en'=>'2 Chuck Tomahawks + Wine Box','slug'=>'lote-2-tomahawk-de-aguja-y-vino','sku'=>'/lote-tomahawk-de-aguja-y-vino',
 'old_marker'=>'Compuesto por 2 Tomahawk de aguja de ternera',
 'es_excerpt'=>'<p>Lote compuesto por 2 Tomahawk de aguja de ternera y una botella de vino tinto Camina Roble o similar. Cada Tomahawk pesa aproximadamente entre 0,8 y 1 kg, según la ficha individual. Una combinación pensada para preparar una comida alrededor de dos piezas grandes de carne.</p>',
 'en_excerpt'=>'<p>Box containing 2 beef chuck Tomahawks and one bottle of Camina Roble red wine or a similar wine. Each Tomahawk weighs approximately 0.8 to 1 kg, according to the individual product specification. A combination designed for a meal built around two large cuts of beef.</p>',
 'es_content'=><<<'HTML'
<h2>Dos Tomahawk de aguja en un mismo lote</h2>
<p>Este lote reúne dos Tomahawk de aguja de ternera y una botella de vino tinto Camina Roble o similar. Cada pieza de carne tiene un peso aproximado de entre 0,8 y 1 kg.</p>
<p>El formato permite preparar los dos Tomahawk en una misma parrilla o barbacoa y servirlos después enteros para cortarlos en la mesa.</p>
<h2>Cómo preparar los Tomahawk</h2>
<p>Marca primero la superficie de la carne sobre una zona de calor más intenso y continúa después con una cocción más moderada para que el interior se haga de forma uniforme.</p>
<p>También puedes terminar las piezas en el horno. Una vez cocinadas, déjalas reposar unos minutos antes de cortarlas.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Two chuck Tomahawks in one box</h2>
<p>This box contains two beef chuck Tomahawks and one bottle of Camina Roble red wine or a similar wine. Each piece of beef weighs approximately 0.8 to 1 kg.</p>
<p>The format makes it easy to cook both Tomahawks on the same grill or barbecue and then bring them whole to the table for slicing.</p>
<h2>How to cook the Tomahawks</h2>
<p>First sear the surface of the meat over stronger heat, then continue over more moderate heat so that the centre cooks more evenly.</p>
<p>The steaks can also be finished in the oven. Once cooked, allow them to rest for a few minutes before slicing.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué incluye el lote?</h3>
<p>Incluye dos Tomahawk de aguja de ternera y una botella de vino tinto Camina Roble o similar.</p>
<h3>¿Cuánto pesa cada Tomahawk?</h3>
<p>Cada pieza pesa aproximadamente entre 0,8 y 1 kg.</p>
<h3>¿Los dos Tomahawk se pueden preparar a la barbacoa?</h3>
<p>Sí. Son piezas adecuadas para parrilla o barbacoa y también pueden terminarse en el horno.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What does the box contain?</h3>
<p>It contains two beef chuck Tomahawks and one bottle of Camina Roble red wine or a similar wine.</p>
<h3>How much does each Tomahawk weigh?</h3>
<p>Each piece weighs approximately 0.8 to 1 kg.</p>
<h3>Can both Tomahawks be cooked on the barbecue?</h3>
<p>Yes. They are suitable for grilling or barbecuing and can also be finished in the oven.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b7_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_b7_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_b7_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b7_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b7_fail("SKU mismatch {$key}");
    if(stripos(mo_b7_vendor($p),'tolecarnes')===false) mo_b7_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b7_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre Tolecarnes')===false) mo_b7_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    $translations[$spec['title']]=$spec['title_en'];
    foreach(mo_b7_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b7_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<40) mo_b7_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_tolecarnes_batch07_backup_20260831';
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
    if(!add_option($backup_key,$backup,'',false)) mo_b7_fail('Could not create batch 07 backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($resolved as $key=>$p){
    $r=wp_update_post(['ID'=>(int)$p->ID,'post_excerpt'=>$payload[$key]['es_excerpt'],'post_content'=>$payload[$key]['es_content']],true);
    if(is_wp_error($r)) mo_b7_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_b7_fail("English long-content meta update failed {$key}");
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
            if($ok===false) mo_b7_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_b7_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre Tolecarnes')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_b7_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About Tolecarnes')===false || stripos($en,'Frequently asked questions')===false) mo_b7_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE batch07 products=5 translations=".count($translations)."\n";
