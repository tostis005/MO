<?php
/** Tolecarnes product copy batch 08 (ES + EN): final published untreated products. */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_b8_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_b8_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}
function mo_b8_segments($html){
    $segments=[];
    if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$html,$m,PREG_SET_ORDER)){
        foreach($m as $row){
            $text=trim(html_entity_decode(wp_strip_all_tags($row[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if($text!=='') $segments[]=['tag'=>strtolower($row[1]),'text'=>$text];
        }
    }
    return $segments;
}
function mo_b8_pair_html($es_html,$en_html,$label){
    $es=mo_b8_segments($es_html); $en=mo_b8_segments($en_html);
    if(count($es)!==count($en)) mo_b8_fail("Segment mismatch {$label}: ES=".count($es)." EN=".count($en));
    $pairs=[];
    foreach($es as $i=>$seg){
        if($seg['tag']!==$en[$i]['tag']) mo_b8_fail("Tag mismatch {$label} at {$i}");
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
'lote_parejas'=>[
 'id'=>11159,'title'=>'Lote parejas','title_en'=>'Couples beef box','slug'=>'lote-parejas','sku'=>'/lote-parejas-awz',
 'old_marker'=>'Lote Parejas compuesto por 2 Entrecot de aprox. 300 gr. cada uno, 2 Burger Clasic y una botella de vino Tinto Camina Roble o vino similar.',
 'es_excerpt'=>'<p>Lote compuesto por 2 entrecots de aproximadamente 300 g cada uno, 2 Burger Classic y una botella de vino tinto Camina Roble o vino similar. Una combinación pensada para preparar una comida o cena para compartir sin tener que elegir cada producto por separado.</p>',
 'en_excerpt'=>'<p>Box containing 2 entrecote steaks of approximately 300 g each, 2 Classic burgers and one bottle of Camina Roble red wine or a similar wine. A combination designed for a meal for two without having to choose each product separately.</p>',
 'es_content'=><<<'HTML'
<h2>Carne y vino en un mismo lote</h2>
<p>El Lote Parejas reúne dos entrecots de aproximadamente 300 g cada uno, dos Burger Classic y una botella de vino tinto Camina Roble o un vino similar.</p>
<p>Los entrecots permiten preparar un plato de carne sencillo a la plancha o a la brasa, mientras que las hamburguesas ofrecen una alternativa diferente dentro del mismo lote.</p>
<h2>Cómo aprovecharlo</h2>
<p>Para los entrecots, utiliza una plancha, sartén o parrilla bien caliente y adapta el tiempo de cocción al grosor de la pieza y al punto que prefieras.</p>
<p>Las hamburguesas deben cocinarse completamente en el interior. El vino incluido completa el lote y puede variar por una referencia similar cuando sea necesario.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Beef and wine in one box</h2>
<p>The Couples Box contains two entrecote steaks of approximately 300 g each, two Classic burgers and one bottle of Camina Roble red wine or a similar wine.</p>
<p>The entrecote steaks are well suited to simple pan, griddle or grill cooking, while the burgers provide a different option within the same box.</p>
<h2>How to make the most of it</h2>
<p>For the entrecote steaks, use a properly heated frying pan, griddle or grill and adjust the cooking time to the thickness of the meat and your preferred level of doneness.</p>
<p>The burgers should be cooked thoroughly in the centre. The included wine completes the box and may be replaced by a similar wine when necessary.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué incluye el Lote Parejas?</h3>
<p>Incluye 2 entrecots de aproximadamente 300 g cada uno, 2 Burger Classic y una botella de vino tinto Camina Roble o vino similar.</p>
<h3>¿Cuánto pesa cada entrecot?</h3>
<p>Cada entrecot tiene un peso aproximado de 300 g.</p>
<h3>¿La botella de vino es siempre Camina Roble?</h3>
<p>El lote incluye Camina Roble o un vino similar, según disponibilidad.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What does the Couples Box contain?</h3>
<p>It contains 2 entrecote steaks of approximately 300 g each, 2 Classic burgers and one bottle of Camina Roble red wine or a similar wine.</p>
<h3>How much does each entrecote steak weigh?</h3>
<p>Each entrecote steak weighs approximately 300 g.</p>
<h3>Is the wine always Camina Roble?</h3>
<p>The box includes Camina Roble or a similar wine, depending on availability.</p>
HTML,
],
'picana'=>[
 'id'=>11163,'title'=>'Tapilla o picaña de ternera','title_en'=>'Beef picanha','slug'=>'tapilla-o-picana-de-ternera','sku'=>'/tapillapicaña',
 'old_marker'=>'Tapilla o picaña de ternera, corte de pequeño tamaño cuya parte más estrecha destaca por su sabor.',
 'es_excerpt'=>'<p>Tapilla o picaña de ternera, una pieza de pequeño tamaño que puede prepararse entera o cortarse en porciones. Es un corte versátil para cocinar a la brasa, a la plancha o en sartén, con una preparación sencilla que permita disfrutar del sabor de la carne.</p>',
 'en_excerpt'=>'<p>Beef picanha, a relatively small cut that can be cooked whole or sliced into portions. A versatile piece for grilling, griddle cooking or pan frying, with a simple preparation that lets the flavour of the beef come through.</p>',
 'es_content'=><<<'HTML'
<h2>Una pieza versátil para plancha o brasa</h2>
<p>La tapilla, también conocida como picaña, es una pieza de ternera de tamaño contenido. Su parte más estrecha destaca especialmente por su sabor y permite trabajar el corte de distintas formas según la preparación elegida.</p>
<p>Puede cocinarse entera y cortarse después, o dividirse previamente en porciones para prepararlas directamente sobre la plancha, la sartén o la parrilla.</p>
<h2>Cómo prepararla</h2>
<p>Si la cocinas entera, conviene controlar el calor para que el interior alcance el punto deseado sin cocinar en exceso la superficie. Después, deja reposar la pieza unos minutos antes de cortarla.</p>
<p>Si prefieres prepararla en porciones, utiliza una superficie bien caliente y adapta el tiempo al grosor de cada corte.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>A versatile cut for the griddle or grill</h2>
<p>Tapilla, also known as picanha, is a relatively small beef cut. Its narrower end is particularly noted for its flavour and the piece can be handled in different ways depending on the chosen preparation.</p>
<p>It can be cooked whole and sliced afterwards, or divided into portions first and cooked directly on a griddle, in a frying pan or on the grill.</p>
<h2>How to cook it</h2>
<p>If cooking it whole, control the heat so the centre reaches the desired doneness without overcooking the surface. Then allow the meat to rest for a few minutes before slicing.</p>
<p>If you prefer to cook it in portions, use a properly heated surface and adjust the cooking time to the thickness of each piece.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Tapilla y picaña son el mismo corte?</h3>
<p>En esta ficha se utiliza la denominación tapilla o picaña para la misma pieza.</p>
<h3>¿Se puede cocinar entera?</h3>
<p>Sí. Puede cocinarse entera y cortarse después, o prepararse previamente en porciones.</p>
<h3>¿Qué métodos de cocción le van bien?</h3>
<p>Puede cocinarse a la brasa, a la plancha o en sartén.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Are tapilla and picanha the same cut here?</h3>
<p>This product uses the names tapilla and picanha for the same piece.</p>
<h3>Can it be cooked whole?</h3>
<p>Yes. It can be cooked whole and sliced afterwards, or divided into portions before cooking.</p>
<h3>Which cooking methods work well?</h3>
<p>It can be cooked on the grill, on a griddle or in a frying pan.</p>
HTML,
],
'burger_vaca'=>[
 'id'=>11166,'title'=>'Burguer 100% vaca (2 unidades)','title_en'=>'100% beef burgers (2 units)','slug'=>'burguer-100-vaca-2-unidades','sku'=>'/burger-vaca',
 'old_marker'=>'Jugosas hamburguesas sin gluten elaboradas artesanalmente sólo a base de nuestra mejor carne de vaca.',
 'es_excerpt'=>'<p>Hamburguesas elaboradas artesanalmente con 100% carne de vaca y sin gluten. Se presentan en bandeja de 2 unidades, con un peso aproximado de 150 g por hamburguesa, listas para cocinar en sartén, plancha o barbacoa.</p>',
 'en_excerpt'=>'<p>Handmade burgers prepared with 100% beef and no gluten. Supplied in a tray of 2 units, with an approximate weight of 150 g per burger, ready for cooking in a frying pan, on a griddle or on the barbecue.</p>',
 'es_content'=><<<'HTML'
<h2>Hamburguesas 100% de vaca</h2>
<p>Estas hamburguesas se elaboran artesanalmente únicamente con carne de vaca y no contienen gluten. Cada bandeja incluye dos unidades de aproximadamente 150 g cada una.</p>
<p>El formato permite preparar dos hamburguesas de buen tamaño sin necesidad de manipular ni dar forma previamente a la carne.</p>
<h2>Cómo prepararlas</h2>
<p>Pueden cocinarse en sartén, plancha o barbacoa. Utiliza una superficie bien caliente y cocina las hamburguesas por ambos lados hasta que el interior quede completamente hecho.</p>
<p>Puedes servirlas en pan de hamburguesa o directamente con verduras, patatas, ensalada u otra guarnición.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>100% beef burgers</h2>
<p>These burgers are handmade using only beef and contain no gluten. Each tray contains two burgers weighing approximately 150 g each.</p>
<p>The format gives you two generously sized burgers without having to shape or handle the meat before cooking.</p>
<h2>How to cook them</h2>
<p>They can be cooked in a frying pan, on a griddle or on the barbecue. Use a properly heated surface and cook both sides until the centre is thoroughly cooked.</p>
<p>Serve them in a burger bun or directly with vegetables, potatoes, salad or another side dish.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Son 100% carne de vaca?</h3>
<p>Sí. Están elaboradas únicamente con carne de vaca.</p>
<h3>¿Contienen gluten?</h3>
<p>No. Estas hamburguesas se elaboran sin gluten.</p>
<h3>¿Cuántas unidades incluye la bandeja?</h3>
<p>Incluye 2 hamburguesas de aproximadamente 150 g cada una.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Are they made with 100% beef?</h3>
<p>Yes. They are made only with beef.</p>
<h3>Do they contain gluten?</h3>
<p>No. These burgers are made without gluten.</p>
<h3>How many burgers are included?</h3>
<p>The tray contains 2 burgers weighing approximately 150 g each.</p>
HTML,
],
'tbone'=>[
 'id'=>13246,'title'=>'T-bone de ternera','title_en'=>'Beef T-bone steak','slug'=>'t-bone-de-ternera','sku'=>'/t-bone-de-ternera',
 'old_marker'=>'Lomo bajo y solomillo en un solo corte. Precio por unidad de 800 gr aprox.',
 'es_excerpt'=>'<p>T-bone de ternera que reúne lomo bajo y solomillo en un mismo corte, separados por el característico hueso central. Cada pieza tiene un peso aproximado de 800 g y está especialmente indicada para parrilla, barbacoa o una plancha amplia.</p>',
 'en_excerpt'=>'<p>Beef T-bone steak combining striploin and tenderloin in a single cut, separated by the characteristic central bone. Each piece weighs approximately 800 g and is particularly suited to grilling, barbecuing or cooking on a large griddle.</p>',
 'es_content'=><<<'HTML'
<h2>Dos cortes en una misma pieza</h2>
<p>El T-bone reúne lomo bajo y solomillo en una sola pieza. El hueso central separa ambos cortes y da al T-bone su forma característica.</p>
<p>Cada unidad pesa aproximadamente 800 g, por lo que es una pieza de tamaño considerable y conviene adaptar la cocción a su grosor.</p>
<h2>Cómo prepararlo</h2>
<p>Funciona especialmente bien en parrilla o barbacoa. Puedes marcar primero ambos lados a temperatura alta y continuar después con un calor más moderado para controlar mejor la cocción interior.</p>
<p>También puede prepararse en una plancha o sartén suficientemente amplia. Una vez cocinado, dejarlo reposar unos minutos antes de cortar ayuda a que los jugos se redistribuyan por la carne.</p>
HTML,
 'en_content'=><<<'HTML'
<h2>Two cuts in a single steak</h2>
<p>A T-bone combines striploin and tenderloin in one piece. The central bone separates the two cuts and gives the T-bone its characteristic shape.</p>
<p>Each steak weighs approximately 800 g, making it a substantial piece whose cooking time should be adapted to its thickness.</p>
<h2>How to cook it</h2>
<p>It works particularly well on a grill or barbecue. You can first sear both sides over high heat and then continue over more moderate heat for better control of the centre.</p>
<p>It can also be cooked on a sufficiently large griddle or frying pan. Once cooked, allowing it to rest for a few minutes before slicing helps the juices redistribute through the meat.</p>
HTML,
 'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué cortes incluye el T-bone?</h3>
<p>Incluye lomo bajo y solomillo en una misma pieza.</p>
<h3>¿Cuánto pesa cada unidad?</h3>
<p>Cada T-bone tiene un peso aproximado de 800 g.</p>
<h3>¿Cómo se puede cocinar?</h3>
<p>Es especialmente adecuado para parrilla o barbacoa y también puede prepararse en una plancha o sartén amplia.</p>
HTML,
 'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which cuts are included in a T-bone?</h3>
<p>It combines striploin and tenderloin in a single steak.</p>
<h3>How much does each steak weigh?</h3>
<p>Each T-bone weighs approximately 800 g.</p>
<h3>How can it be cooked?</h3>
<p>It is particularly well suited to grilling or barbecuing and can also be prepared on a large griddle or frying pan.</p>
HTML,
],
];

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))!==$trp) mo_b8_fail('TranslatePress ES→EN dictionary table not found.');
$cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
foreach(['id','original','translated','status','block_type'] as $c){ if(!in_array($c,$cols,true)) mo_b8_fail("Missing TranslatePress column {$c}"); }

$resolved=[];
foreach($products as $key=>$spec){
    $p=get_post((int)$spec['id']);
    if(!$p||$p->post_type!=='product'||$p->post_status!=='publish') mo_b8_fail("Product {$key} missing or not published");
    if($p->post_title!==$spec['title']||$p->post_name!==$spec['slug']) mo_b8_fail("Identity mismatch {$key}: {$p->ID} {$p->post_title} / {$p->post_name}");
    if(strcasecmp((string)get_post_meta($p->ID,'_sku',true),(string)$spec['sku'])!==0) mo_b8_fail("SKU mismatch {$key}");
    if(stripos(mo_b8_vendor($p),'tolecarnes')===false) mo_b8_fail("Vendor mismatch {$key}");
    if((string)get_post_meta($p->ID,'_stock_status',true)!=='instock') mo_b8_fail("Product {$key} is not in stock");
    if(stripos((string)$p->post_content,$spec['old_marker'])===false && stripos((string)$p->post_content,'Sobre Tolecarnes')===false) mo_b8_fail("Current content changed unexpectedly for {$key}");
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

$payload=[];$translations=[];
foreach($products as $key=>$spec){
    $es_content=trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content=trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key]=['es_excerpt'=>$spec['es_excerpt'],'es_content'=>$es_content,'en_content'=>$en_content];
    $translations[$spec['title']]=$spec['title_en'];
    foreach(mo_b8_pair_html($spec['es_excerpt'],$spec['en_excerpt'],"{$key} excerpt") as $o=>$t) $translations[$o]=$t;
    foreach(mo_b8_pair_html($es_content,$en_content,"{$key} content") as $o=>$t) $translations[$o]=$t;
}
if(count($translations)<35) mo_b8_fail('Translation map unexpectedly small: '.count($translations));

$backup_key='mo_tolecarnes_batch08_backup_20260831';
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
    if(!add_option($backup_key,$backup,'',false)) mo_b8_fail('Could not create batch 08 backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($resolved as $key=>$p){
    $r=wp_update_post(['ID'=>(int)$p->ID,'post_excerpt'=>$payload[$key]['es_excerpt'],'post_content'=>$payload[$key]['es_content']],true);
    if(is_wp_error($r)) mo_b8_fail("wp_update_post failed {$key}: ".$r->get_error_message());
    if(update_post_meta($p->ID,'_en_US_post_content',$payload[$key]['en_content'])===false){
        $now=(string)get_post_meta($p->ID,'_en_US_post_content',true);
        if($now!==$payload[$key]['en_content']) mo_b8_fail("English long-content meta update failed {$key}");
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
            if($ok===false) mo_b8_fail("TranslatePress update failed for {$original}");
        }
    }else{
        $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
        $format=['%s','%s','%d','%d'];
        if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
        if($wpdb->insert($trp,$data,$format)===false) mo_b8_fail("TranslatePress insert failed for {$original}");
    }
}

foreach($resolved as $key=>$p){
    $fresh=get_post($p->ID);
    if(stripos((string)$fresh->post_content,'Sobre Tolecarnes')===false || stripos((string)$fresh->post_content,'Preguntas frecuentes')===false) mo_b8_fail("Spanish verification failed {$key}");
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    if(stripos($en,'About Tolecarnes')===false || stripos($en,'Frequently asked questions')===false) mo_b8_fail("English meta verification failed {$key}");
    echo "VERIFIED {$key}: ES+EN complete\n";
}

echo "DONE batch08 products=4 translations=".count($translations)."\n";
