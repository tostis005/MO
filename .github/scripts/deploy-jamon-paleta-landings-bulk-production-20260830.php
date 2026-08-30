<?php
/**
 * Production deploy: curated Jamones y Paletas SEO landings.
 * Native Spanish pages + reviewed English aliases/content in _en_US_* metadata.
 * Idempotent: updates pages by _emdo_jp_landing_key.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

function emdo_jp_products_shortcode(array $ids): string {
    $ids = array_values(array_filter(array_map('intval', $ids), static fn($id) => get_post_status($id) === 'publish'));
    if (!$ids) return '';
    return '[products ids="'.implode(',', $ids).'" columns="'.min(3, max(1, count($ids))).'" orderby="post__in"]';
}

function emdo_jp_aioseo(int $post_id, string $title, string $description): void {
    global $wpdb;
    $table = $wpdb->prefix . 'aioseo_posts';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return;
    $row_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE post_id=%d LIMIT 1", $post_id));
    if ($row_id) {
        $wpdb->update($table, array('title'=>$title,'description'=>$description), array('id'=>$row_id), array('%s','%s'), array('%d'));
    }
}

function emdo_jp_page_id(string $key): int {
    $ids = get_posts(array(
        'post_type'=>'page','post_status'=>array('publish','draft','private'),'posts_per_page'=>2,'fields'=>'ids','no_found_rows'=>true,
        'meta_key'=>'_emdo_jp_landing_key','meta_value'=>$key,
    ));
    return count($ids) ? (int) $ids[0] : 0;
}

function emdo_jp_upsert(array $cfg): array {
    $id = emdo_jp_page_id($cfg['key']);
    if (!$id) {
        $existing = get_page_by_path($cfg['es_slug'], OBJECT, 'page');
        if ($existing instanceof WP_Post) $id = (int) $existing->ID;
    }
    $products = emdo_jp_products_shortcode($cfg['products']);
    if ($products === '') throw new Exception('No live products for '.$cfg['key']);
    $es = str_replace('{{PRODUCTS}}', $products, $cfg['es_content']);
    $en = str_replace('{{PRODUCTS}}', $products, $cfg['en_content']);
    $args = array(
        'post_type'=>'page','post_status'=>'publish','post_title'=>$cfg['es_title'],'post_name'=>$cfg['es_slug'],
        'post_content'=>$es,'post_excerpt'=>$cfg['es_meta'],'comment_status'=>'closed','ping_status'=>'closed',
    );
    if ($id) $args['ID'] = $id;
    $saved = wp_insert_post(wp_slash($args), true);
    if (is_wp_error($saved)) throw new Exception($saved->get_error_message());
    $id = (int) $saved;
    update_post_meta($id, '_emdo_jp_landing_key', $cfg['key']);
    update_post_meta($id, '_en_US_post_title', '<span data-no-translation>'.$cfg['en_title'].'</span>');
    update_post_meta($id, '_en_US_post_name', $cfg['en_slug']);
    update_post_meta($id, '_en_US_post_excerpt', $cfg['en_meta']);
    update_post_meta($id, '_en_US_post_content', '<span data-no-translation>'.$en.'</span>');
    update_post_meta($id, '_en_US_published', '1');
    emdo_jp_aioseo($id, $cfg['seo_title'], $cfg['es_meta']);
    return array(
        'key'=>$cfg['key'],'id'=>$id,
        'es_url'=>home_url('/'.$cfg['es_slug'].'/'),
        'en_url'=>home_url('/en/'.$cfg['en_slug'].'/'),
        'products'=>array_values(array_filter(array_map('intval',$cfg['products']), static fn($pid)=>get_post_status($pid)==='publish')),
    );
}

$pages = array();

$pages[] = array(
'key'=>'paleta-bellota-100','es_slug'=>'paleta-bellota-100-iberica','en_slug'=>'100-iberico-acorn-fed-shoulder-ham',
'es_title'=>'Paleta de Bellota 100% Ibérica','en_title'=>'100% Ibérico de Bellota Shoulder Ham',
'seo_title'=>'Comprar Paleta de Bellota 100% Ibérica | Brida Negra',
'es_meta'=>'Compra paleta de bellota 100% ibérica de brida negra y compara piezas, DOP Los Pedroches y formatos loncheados directamente del productor.',
'en_meta'=>'Buy 100% Ibérico de Bellota shoulder ham from Spanish producers. Compare black-label pieces, Los Pedroches PDO and sliced formats.',
'products'=>array(1356,4199,1370),
'es_content'=><<<'HTML'
<p>La <strong>paleta de bellota 100% ibérica</strong> combina raza ibérica pura y alimentación de bellota durante la montanera. Se identifica mediante <strong>brida negra</strong>, la categoría superior prevista por la norma del ibérico para jamones y paletas.</p>
<p>Frente al jamón, la paleta procede de la extremidad delantera del animal. Su menor tamaño, mayor proporción de hueso y sabor habitualmente más intenso hacen que sea una opción especialmente interesante para hogares que buscan una pieza ibérica de máxima categoría con un presupuesto y una cantidad más contenidos.</p>
{{PRODUCTS}}
<h2>Qué estás comprando cuando eliges una paleta brida negra</h2>
<p>La denominación reúne dos datos distintos: <strong>100% ibérica</strong> se refiere a la raza y <strong>de bellota</strong> a la alimentación y manejo en la fase final. La brida negra permite reconocer esta combinación de forma rápida.</p>
<h2>Paleta 100% ibérica con DOP Los Pedroches o sin DOP</h2>
<p>Dentro de la misma categoría puedes encontrar piezas con y sin <strong>DOP Los Pedroches</strong>. La DOP añade un vínculo geográfico y un pliego de condiciones propio; una pieza sin DOP puede seguir siendo perfectamente de bellota 100% ibérica si cumple la norma correspondiente.</p>
<h2>Pieza entera o loncheada</h2>
<p>La pieza entera es apropiada para quien disfruta del corte y va a consumirla con cierta frecuencia. Los sobres loncheados son más cómodos, facilitan el control de las raciones y permiten abrir únicamente la cantidad necesaria.</p>
<h2>¿Paleta o jamón?</h2>
<p>La paleta suele tener una curación y una anatomía diferentes, con una relación entre carne y hueso distinta. Por eso conviene comparar no solo el precio de la pieza, sino también el número de personas, la frecuencia de consumo y el formato deseado.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿La paleta brida negra es pata negra?</h3><p>La expresión “pata negra” está reservada a productos de bellota 100% ibéricos. La paleta que cumple esta denominación se identifica con precinto negro.</p>
<h3>¿Es mejor una paleta con DOP?</h3><p>La DOP aporta una certificación territorial adicional. La elección depende de si valoras específicamente ese origen protegido además de la categoría del producto.</p>
<h3>¿Cuándo compensa comprarla loncheada?</h3><p>Cuando se busca comodidad, consumo ocasional o porciones fáciles de conservar. Para un consumo continuado, la pieza entera puede resultar más adecuada.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>100% Ibérico de Bellota shoulder ham</strong> combines pure Ibérico breed with the acorn-fed finishing category. In Spain it is identified by the official <strong>black seal</strong>.</p>
<p>Shoulder ham comes from the front leg and is smaller than a full jamón. It normally has a different meat-to-bone ratio and a characteristically intense flavour, making it a practical premium option for smaller households or more moderate consumption.</p>
{{PRODUCTS}}
<h2>What the black seal means</h2><p><strong>100% Ibérico</strong> refers to breed, while <strong>de bellota</strong> refers to the acorn-fed finishing stage. The black seal identifies products that combine both conditions.</p>
<h2>Los Pedroches PDO or non-PDO</h2><p>Our selection includes shoulder hams with and without <strong>Los Pedroches PDO</strong>. PDO certification adds a protected geographical link and its own production requirements; non-PDO products may still fully qualify as 100% Ibérico de Bellota.</p>
<h2>Whole or sliced shoulder ham</h2><p>A whole piece suits regular consumption and traditional carving. Sliced packs are convenient for smaller servings and allow you to open only what you need.</p>
<h2>Shoulder ham or full jamón?</h2><p>The right choice depends on household size, consumption frequency, yield and budget. Shoulder ham is smaller and generally offers a more compact way to enjoy the same black-label category.</p>
<h2>Frequently asked questions</h2><h3>Is black-label shoulder ham pata negra?</h3><p>The “pata negra” term is reserved for 100% Ibérico de Bellota products, identified with the black seal.</p><h3>Does it need PDO certification?</h3><p>No. PDO is an additional geographical certification, separate from the official black-label category.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'jamon-cebo-campo','es_slug'=>'jamon-cebo-campo-iberico','en_slug'=>'iberico-free-range-grain-fed-ham',
'es_title'=>'Jamón de Cebo de Campo Ibérico','en_title'=>'Ibérico Cebo de Campo Ham',
'seo_title'=>'Comprar Jamón de Cebo de Campo Ibérico | Brida Verde',
'es_meta'=>'Compra jamón de cebo de campo ibérico de brida verde. Compara pieza y formatos loncheados con DOP Los Pedroches y origen conocido.',
'en_meta'=>'Buy Ibérico cebo de campo ham, Spain’s green-label free-range grain-fed category. Compare whole and sliced Los Pedroches PDO options.',
'products'=>array(5045,8624),
'es_content'=><<<'HTML'
<p>El <strong>jamón de cebo de campo ibérico</strong> se identifica mediante <strong>brida verde</strong>. Procede de cerdos ibéricos criados con acceso al campo y alimentados con piensos y recursos naturales, pero no pertenece a la categoría de bellota.</p>
<p>Es una alternativa especialmente interesante cuando se busca un jamón ibérico con crianza en campo y un precio normalmente más contenido que las categorías de bellota.</p>
{{PRODUCTS}}
<h2>Qué significa cebo de campo</h2><p>“Cebo de campo” describe el sistema de alimentación y manejo. No debe confundirse con el porcentaje de raza: dentro de esta categoría pueden existir diferentes porcentajes de ibérico. Por eso conviene leer ambos datos por separado.</p>
<h2>Brida verde frente a brida negra o roja</h2><p>La brida verde distingue el cebo de campo. Las bridas negra y roja corresponden a productos de bellota. Elegir una u otra no es solo una cuestión de precio: cambia el sistema de producción y el perfil del producto.</p>
<h2>Cebo de campo con DOP Los Pedroches</h2><p>En El Mercado de Origen puedes encontrar jamón de cebo de campo 100% ibérico certificado por la <strong>DOP Los Pedroches</strong>, tanto en pieza como en formatos preparados para un consumo más cómodo.</p>
<h2>Pieza o sobres</h2><p>La pieza ofrece la experiencia tradicional y es adecuada para consumo frecuente. Los sobres resultan prácticos para hogares pequeños, para servir raciones concretas o para quien no quiere ocuparse del corte.</p>
<h2>Preguntas frecuentes</h2><h3>¿Cebo de campo significa que ha comido bellota?</h3><p>No. Puede aprovechar recursos naturales del campo, pero no es la categoría de bellota.</p><h3>¿La brida verde puede ser 100% ibérica?</h3><p>Sí. Alimentación y porcentaje racial son conceptos distintos.</p><h3>¿Para quién es una buena opción?</h3><p>Para quien busca un ibérico de campo con una relación entre calidad, origen y presupuesto diferente a la de los productos de bellota.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>Ibérico cebo de campo ham</strong> is Spain’s <strong>green-label</strong> category. The pigs are raised with outdoor access and are fed on natural resources and feed, but the product is not classified as acorn-fed bellota.</p>
<p>It is a useful option for customers who want an outdoor-raised Ibérico ham at a price point generally below the bellota categories.</p>
{{PRODUCTS}}
<h2>What cebo de campo means</h2><p>The term describes feeding and husbandry, not breed percentage. A cebo de campo ham can have different percentages of Ibérico ancestry, so both pieces of information should be checked separately.</p>
<h2>Green label versus black or red label</h2><p>The green seal identifies cebo de campo. Black and red seals identify bellota products. The distinction reflects a different production system, not simply a different price.</p>
<h2>Los Pedroches PDO cebo de campo</h2><p>Our catalogue includes 100% Ibérico cebo de campo ham certified by the <strong>Los Pedroches PDO</strong>, in whole and convenient sliced formats.</p>
<h2>Whole ham or sliced packs</h2><p>A whole piece suits frequent consumption and traditional carving. Sliced packs are convenient for smaller households and controlled portions.</p>
<h2>Frequently asked questions</h2><h3>Does cebo de campo mean acorn-fed?</h3><p>No. It is a separate official category.</p><h3>Can green-label ham be 100% Ibérico?</h3><p>Yes. Breed percentage and feeding category are different classifications.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'jamon-dop-pedroches','es_slug'=>'jamon-iberico-dop-los-pedroches','en_slug'=>'los-pedroches-pdo-iberico-ham',
'es_title'=>'Jamón Ibérico DOP Los Pedroches','en_title'=>'Los Pedroches PDO Ibérico Ham',
'seo_title'=>'Comprar Jamón Ibérico DOP Los Pedroches | Bellota y Cebo de Campo',
'es_meta'=>'Compra jamón ibérico DOP Los Pedroches y compara opciones de bellota 100% ibérico y cebo de campo, piezas enteras y formatos loncheados.',
'en_meta'=>'Buy Los Pedroches PDO Ibérico ham. Compare 100% Ibérico bellota and cebo de campo options in whole and sliced formats.',
'products'=>array(4160,1363,5045,8624),
'es_content'=><<<'HTML'
<p>La <strong>DOP Los Pedroches</strong> protege jamones ibéricos vinculados a una de las grandes zonas de dehesa del norte de Córdoba. Esta certificación no sustituye a la información sobre raza o alimentación: la complementa.</p>
<p>Dentro de la DOP puedes encontrar distintas categorías, por lo que conviene comparar la brida, el porcentaje racial, el formato y el peso además del sello territorial.</p>
{{PRODUCTS}}
<h2>Qué aporta la DOP Los Pedroches</h2><p>Una Denominación de Origen Protegida vincula el producto a un territorio y a un pliego de condiciones controlado. En el caso de Los Pedroches, el origen geográfico pasa a ser un elemento central de la elección.</p>
<h2>Bellota 100% ibérico o cebo de campo</h2><p>Dos jamones con DOP Los Pedroches pueden pertenecer a categorías comerciales distintas. La <strong>brida negra</strong> identifica bellota 100% ibérico; la <strong>brida verde</strong>, cebo de campo. La DOP no convierte automáticamente un jamón en bellota.</p>
<h2>Cómo comparar los jamones de esta selección</h2><ul><li>alimentación y color de brida;</li><li>porcentaje de raza ibérica;</li><li>peso;</li><li>pieza entera o formato loncheado;</li><li>tipo de corte y preparación;</li><li>precio final.</li></ul>
<h2>Los Pedroches como origen</h2><p>La comarca de Los Pedroches está estrechamente asociada a la dehesa y a la producción ibérica. Para quien prioriza procedencia verificable, la DOP permite añadir ese criterio territorial a la comparación de producto.</p>
<h2>Preguntas frecuentes</h2><h3>¿Todo jamón de Los Pedroches tiene DOP?</h3><p>No. Para utilizar la denominación protegida debe estar certificado conforme al pliego de la DOP.</p><h3>¿Todo jamón DOP Los Pedroches es brida negra?</h3><p>No. La DOP y la categoría de alimentación son clasificaciones distintas.</p><h3>¿Hay formatos loncheados?</h3><p>Sí, según la referencia puedes encontrar piezas y sobres listos para consumir.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>Los Pedroches PDO</strong> identifies Ibérico hams linked to the protected production area in northern Córdoba, one of Spain’s major dehesa landscapes. PDO certification complements, rather than replaces, information about breed and feeding category.</p>
{{PRODUCTS}}
<h2>What Los Pedroches PDO adds</h2><p>A Protected Designation of Origin links the product to a defined geographical area and controlled production specifications. It is useful for customers who want origin to be part of the buying decision.</p>
<h2>100% Ibérico bellota or cebo de campo</h2><p>Los Pedroches PDO hams can belong to different official categories. A <strong>black seal</strong> identifies 100% Ibérico de Bellota, while a <strong>green seal</strong> identifies cebo de campo. PDO status does not automatically mean bellota.</p>
<h2>How to compare the selection</h2><ul><li>feeding category and seal colour;</li><li>Ibérico breed percentage;</li><li>weight;</li><li>whole or sliced format;</li><li>preparation;</li><li>final price.</li></ul>
<h2>Frequently asked questions</h2><h3>Is every ham from Los Pedroches PDO-certified?</h3><p>No. Certification is required to use the protected designation.</p><h3>Is every Los Pedroches PDO ham black label?</h3><p>No. PDO and feeding classification are separate.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'paleta-dop-pedroches','es_slug'=>'paleta-iberica-dop-los-pedroches','en_slug'=>'los-pedroches-pdo-iberico-shoulder-ham',
'es_title'=>'Paleta Ibérica DOP Los Pedroches','en_title'=>'Los Pedroches PDO Ibérico Shoulder Ham',
'seo_title'=>'Comprar Paleta Ibérica DOP Los Pedroches | Brida Negra',
'es_meta'=>'Compra paleta ibérica DOP Los Pedroches. Compara paleta de bellota 100% ibérica en pieza y formatos loncheados de origen protegido.',
'en_meta'=>'Buy Los Pedroches PDO Ibérico shoulder ham. Compare 100% Ibérico de Bellota black-label whole and sliced formats.',
'products'=>array(4199,1370),
'es_content'=><<<'HTML'
<p>La <strong>paleta ibérica DOP Los Pedroches</strong> combina una procedencia protegida con la información propia de cada pieza sobre raza, alimentación y formato.</p>
<p>En nuestra selección puedes comparar paletas de bellota 100% ibéricas certificadas por la DOP Los Pedroches, tanto en pieza como en sobres.</p>
{{PRODUCTS}}
<h2>Origen protegido y brida negra</h2><p>La DOP Los Pedroches acredita el vínculo con el territorio y su pliego de condiciones. La brida negra, por su parte, indica que la pieza es de bellota y 100% ibérica. Son dos garantías diferentes que se complementan.</p>
<h2>Por qué elegir paleta en lugar de jamón</h2><p>La paleta es más pequeña y presenta una anatomía distinta. Puede ser especialmente adecuada cuando se quiere una pieza de alta categoría para menos comensales o un consumo menos continuado.</p>
<h2>Pieza entera o sobres</h2><p>Si disfrutas cortando y consumes ibérico con frecuencia, la pieza entera ofrece la experiencia tradicional. Los sobres facilitan el servicio y la conservación por raciones.</p>
<h2>Qué comparar antes de comprar</h2><ul><li>peso y rendimiento esperado;</li><li>formato;</li><li>precio;</li><li>tipo de corte;</li><li>frecuencia de consumo.</li></ul>
<h2>Preguntas frecuentes</h2><h3>¿DOP Los Pedroches y brida negra son lo mismo?</h3><p>No. La DOP certifica origen y pliego de condiciones; la brida negra identifica bellota 100% ibérico.</p><h3>¿La paleta tiene menos carne que un jamón?</h3><p>Es una pieza más pequeña y con diferente proporción de hueso, por lo que el rendimiento es distinto.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>Los Pedroches PDO Ibérico shoulder ham</strong> combines protected geographical origin with the specific breed, feeding and format information of each product.</p>
<p>Our selection includes 100% Ibérico de Bellota black-label shoulder ham certified by Los Pedroches PDO, in whole and sliced formats.</p>
{{PRODUCTS}}
<h2>Protected origin and black label</h2><p>Los Pedroches PDO certifies geographical origin and production specifications. The black seal identifies the 100% Ibérico de Bellota category. They are separate, complementary guarantees.</p>
<h2>Why choose shoulder ham</h2><p>Shoulder ham is smaller than a full jamón and has a different yield. It can be a practical premium choice for smaller households and more moderate consumption.</p>
<h2>Whole or sliced</h2><p>Whole pieces suit frequent consumption and traditional carving; sliced packs simplify serving and portion control.</p>
<h2>Frequently asked questions</h2><h3>Are PDO and black label the same?</h3><p>No. PDO refers to protected origin; black label refers to breed and feeding category.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'jamon-loncheado','es_slug'=>'jamon-iberico-loncheado','en_slug'=>'sliced-iberico-ham',
'es_title'=>'Jamón Ibérico Loncheado','en_title'=>'Sliced Ibérico Ham',
'seo_title'=>'Comprar Jamón Ibérico Loncheado | Sobres y Corte Preparado',
'es_meta'=>'Compra jamón ibérico loncheado en sobres y compara bellota, cebo de campo, DOP Los Pedroches y diferentes opciones de corte.',
'en_meta'=>'Buy sliced Ibérico ham in convenient packs. Compare bellota, cebo de campo and Los Pedroches PDO options ready to serve.',
'products'=>array(1363,8624,1350,4160,3948,5045),
'es_content'=><<<'HTML'
<p>El <strong>jamón ibérico loncheado</strong> permite disfrutar del producto sin disponer de jamonero ni experiencia de corte. Es una opción especialmente práctica para consumo ocasional, hogares pequeños, regalos o para servir cantidades controladas.</p>
<p>La selección reúne distintas categorías de ibérico y formatos preparados. En algunas referencias el loncheado es el formato principal y en otras puede elegirse como preparación de la pieza.</p>
{{PRODUCTS}}
<h2>Ventajas del formato loncheado</h2><ul><li>raciones fáciles de controlar;</li><li>mayor comodidad;</li><li>no requiere herramientas de corte;</li><li>permite abrir solo los sobres necesarios;</li><li>facilita llevar el jamón a reuniones o viajes.</li></ul>
<h2>Bellota, cebo de campo y DOP</h2><p>“Loncheado” describe el formato, no la calidad del jamón. Antes de comprar conviene revisar también alimentación, raza, brida y, cuando corresponda, DOP.</p>
<h2>Cortado a cuchillo o a máquina</h2><p>Según la referencia pueden existir diferentes preparaciones. El corte a cuchillo busca reproducir el servicio tradicional, mientras que el corte a máquina ofrece regularidad y comodidad. Lo importante es elegir el formato que mejor encaje con el uso previsto.</p>
<h2>Cómo servir los sobres</h2><p>El jamón debe servirse sin estar excesivamente frío. Sacar el sobre con antelación ayuda a que la grasa recupere textura y aromas antes de emplatar.</p>
<h2>Preguntas frecuentes</h2><h3>¿El jamón loncheado dura más?</h3><p>Los sobres cerrados facilitan la conservación y permiten consumir el producto por raciones, pero siempre deben respetarse las condiciones y fecha indicadas en cada envase.</p><h3>¿Es peor que una pieza entera?</h3><p>No necesariamente. Es un formato diferente, pensado para comodidad y control de consumo.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>Sliced Ibérico ham</strong> is a convenient way to enjoy jamón without a ham stand or carving skills. It works particularly well for occasional consumption, smaller households, gifts and controlled portions.</p>
<p>Our selection covers several Ibérico categories and preparations. Some products are sold specifically as sliced packs, while others can be ordered with slicing as a preparation option.</p>
{{PRODUCTS}}
<h2>Why choose sliced packs</h2><ul><li>easy portion control;</li><li>no carving tools required;</li><li>open only what you need;</li><li>convenient for entertaining and travel.</li></ul>
<h2>Bellota, cebo de campo and PDO</h2><p>“Sliced” describes format, not quality. Check feeding category, breed percentage, seal colour and PDO status separately.</p>
<h2>Knife-carved or machine-sliced</h2><p>Available preparation varies by product. Knife carving follows the traditional serving style, while machine slicing offers consistency and convenience.</p>
<h2>Serving sliced Ibérico ham</h2><p>Avoid serving it excessively cold. Letting the pack warm slightly before plating helps the fat recover its texture and aroma.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'paleta-loncheada','es_slug'=>'paleta-iberica-loncheada','en_slug'=>'sliced-iberico-shoulder-ham',
'es_title'=>'Paleta Ibérica Loncheada','en_title'=>'Sliced Ibérico Shoulder Ham',
'seo_title'=>'Comprar Paleta Ibérica Loncheada | Sobres Listos para Servir',
'es_meta'=>'Compra paleta ibérica loncheada y compara bellota 100% ibérica, DOP Los Pedroches y opciones en sobres listas para servir.',
'en_meta'=>'Buy sliced Ibérico shoulder ham. Compare 100% Ibérico de Bellota and Los Pedroches PDO options in convenient ready-to-serve packs.',
'products'=>array(1370,1356,4199,3979),
'es_content'=><<<'HTML'
<p>La <strong>paleta ibérica loncheada</strong> combina el sabor característico de la paleta con un formato cómodo y fácil de servir. Resulta especialmente útil cuando no se quiere mantener una pieza abierta en casa o cuando el consumo es ocasional.</p>
{{PRODUCTS}}
<h2>Qué aporta el loncheado en una paleta</h2><p>La paleta tiene un tamaño y una anatomía distintos al jamón. En sobres, esa diferencia se mantiene en el sabor y textura del producto, pero desaparece la dificultad de aprovechar una pieza con mayor proporción de hueso.</p>
<h2>Cómo comparar las opciones</h2><p>El formato no sustituye a la clasificación del producto. Revisa si es bellota, el porcentaje ibérico, la brida y la existencia de DOP antes de decidir.</p>
<h2>Para consumo ocasional o regalos</h2><p>Los sobres permiten repartir el consumo en el tiempo y son sencillos de presentar. También evitan que el destinatario necesite jamonero y cuchillo, por lo que pueden resultar especialmente prácticos como regalo.</p>
<h2>Cómo servirla</h2><p>Conviene evitar servir las lonchas directamente de la nevera. Un breve tiempo a temperatura ambiente permite que la grasa recupere brillo y textura.</p>
<h2>Preguntas frecuentes</h2><h3>¿Paleta loncheada y jamón loncheado saben igual?</h3><p>No. Proceden de piezas anatómicamente distintas y presentan matices diferentes.</p><h3>¿Los sobres son una categoría de calidad?</h3><p>No. Solo describen la presentación. La calidad y clasificación se consultan en la ficha de cada producto.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>Sliced Ibérico shoulder ham</strong> combines the distinctive character of paleta with an easy-to-serve format. It is particularly practical for occasional consumption and for households that do not want to keep a whole piece open.</p>
{{PRODUCTS}}
<h2>Why sliced shoulder ham is practical</h2><p>Paleta has a different size and anatomy from full jamón. Sliced packs preserve the product’s character while removing the challenge of carving a smaller, bone-rich piece.</p>
<h2>How to compare products</h2><p>Format is separate from quality classification. Check bellota or cebo category, Ibérico percentage, seal colour and PDO status.</p>
<h2>For occasional use and gifts</h2><p>Individual packs make consumption easy to spread over time and do not require a ham stand or carving knife.</p>
<h2>Serving tips</h2><p>Do not serve the slices straight from a very cold refrigerator. A short period at room temperature helps the fat recover texture and aroma.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'jamon-deshuesado','es_slug'=>'jamon-iberico-deshuesado','en_slug'=>'boneless-iberico-ham',
'es_title'=>'Jamón Ibérico Deshuesado','en_title'=>'Boneless Ibérico Ham',
'seo_title'=>'Comprar Jamón Ibérico Deshuesado | Formato Fácil de Cortar',
'es_meta'=>'Compra jamón ibérico deshuesado y compara opciones de bellota, cebo de campo y DOP Los Pedroches en un formato práctico para cortar.',
'en_meta'=>'Buy boneless Ibérico ham and compare bellota, cebo de campo and Los Pedroches PDO options in an easy-to-slice format.',
'products'=>array(1350,4160,3948,5045),
'es_content'=><<<'HTML'
<p>El <strong>jamón ibérico deshuesado</strong> mantiene la categoría y procedencia de la pieza original, pero se prepara sin hueso para facilitar el almacenamiento y el corte.</p>
<p>Es un formato útil para quien quiere cortar jamón en casa con mayor comodidad, aprovechar mejor el espacio o utilizar una cortadora sin manejar una pieza entera con hueso.</p>
{{PRODUCTS}}
<h2>Qué cambia al deshuesar un jamón</h2><p>Deshuesar no transforma un cebo de campo en bellota ni modifica el porcentaje ibérico. La clasificación sigue siendo la de la pieza original. Lo que cambia es la preparación y la forma de utilizarla.</p>
<h2>Ventajas del formato deshuesado</h2><ul><li>ocupa menos espacio;</li><li>es más sencillo de porcionar;</li><li>facilita el corte con máquina;</li><li>reduce la necesidad de trabajar alrededor del hueso;</li><li>puede ser práctico para hostelería o consumo frecuente.</li></ul>
<h2>Bellota, DOP o cebo de campo</h2><p>Antes de elegir, compara la categoría del producto igual que harías con una pieza entera: brida, raza, alimentación, DOP y productor siguen siendo los criterios principales.</p>
<h2>Deshuesado frente a loncheado</h2><p>El deshuesado conserva una pieza que todavía debes cortar. El loncheado llega ya preparado en raciones. El primero ofrece más control sobre grosor y cantidad; el segundo prioriza comodidad inmediata.</p>
<h2>Preguntas frecuentes</h2><h3>¿Pierde calidad por estar deshuesado?</h3><p>La categoría de calidad no cambia por la preparación. La conservación y el corte posterior sí deben hacerse correctamente.</p><h3>¿Es buena opción si no sé cortar jamón?</h3><p>Puede simplificar mucho el corte, especialmente con una cortadora adecuada, aunque sigue requiriendo porcionar la pieza.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p><strong>Boneless Ibérico ham</strong> keeps the classification and origin of the original ham while removing the bone to make storage and slicing easier.</p>
<p>It is a useful format for customers who want more control over slicing at home without handling a full bone-in leg.</p>
{{PRODUCTS}}
<h2>What changes when a ham is deboned</h2><p>Deboning does not change feeding category, breed percentage or PDO status. It changes only the preparation and how the ham is handled.</p>
<h2>Advantages of boneless ham</h2><ul><li>takes less storage space;</li><li>easier to portion;</li><li>works well with a slicer;</li><li>avoids carving around the bone;</li><li>practical for frequent use.</li></ul>
<h2>Bellota, PDO or cebo de campo</h2><p>Compare the same quality criteria as for a whole ham: seal colour, breed, feeding, origin, PDO and producer.</p>
<h2>Boneless versus pre-sliced</h2><p>Boneless ham still needs to be sliced, giving you control over thickness and quantity. Pre-sliced packs prioritise immediate convenience.</p>
{{PRODUCTS}}
HTML
);

$pages[] = array(
'key'=>'ibericos-regalo','es_slug'=>'jamon-paleta-ibericos-para-regalar','en_slug'=>'iberico-ham-gifts',
'es_title'=>'Jamón y Paleta Ibéricos para Regalar','en_title'=>'Ibérico Ham and Shoulder Ham Gifts',
'seo_title'=>'Comprar Jamón y Paleta Ibéricos para Regalar | Guía y Selección',
'es_meta'=>'Encuentra jamón y paleta ibéricos para regalar. Compara bellota, cebo de campo, pieza, loncheado, peso y presupuesto antes de elegir.',
'en_meta'=>'Choose Ibérico ham and shoulder ham gifts by budget, feeding category, size and format. Compare whole and sliced Spanish options.',
'products'=>array(1350,1356,4160,4199,3948,3979,5045),
'es_content'=><<<'HTML'
<p>Regalar <strong>jamón o paleta ibéricos</strong> funciona especialmente bien cuando se elige el producto pensando en quién lo va a recibir: número de personas, frecuencia de consumo, presupuesto y facilidad para cortarlo.</p>
<p>Esta selección reúne alternativas desde piezas de bellota 100% ibéricas hasta opciones de cebo de campo y formatos que pueden prepararse para hacer el regalo más cómodo.</p>
{{PRODUCTS}}
<h2>Jamón o paleta para regalar</h2><p>Un jamón suele ofrecer mayor cantidad y rendimiento. Una paleta es más compacta y puede encajar mejor en hogares pequeños o presupuestos más contenidos. Ninguna es automáticamente “mejor”: depende del destinatario.</p>
<h2>Pieza entera o formato preparado</h2><p>Si la persona disfruta del ritual de corte y consume ibérico con frecuencia, una pieza entera tiene mucho sentido. Si no dispone de jamonero o prefiere comodidad, los formatos loncheados o preparados reducen las barreras de uso.</p>
<h2>Qué categoría elegir</h2><p>Para un regalo de máxima categoría comercial, la combinación <strong>bellota 100% ibérico</strong> es la más reconocible. El cebo de campo puede ser una alternativa muy interesante cuando se busca equilibrar presupuesto, crianza en campo y producto ibérico.</p>
<h2>DOP y origen</h2><p>Si el origen tiene un valor especial para el regalo, las referencias con <strong>DOP Los Pedroches</strong> añaden una certificación territorial concreta. En todas las fichas puedes comprobar quién produce cada pieza.</p>
<h2>Cómo elegir por presupuesto</h2><p>Compara el precio final junto con peso, rendimiento y formato. Una paleta de categoría superior puede encajar mejor que un jamón mayor si el consumo previsto es pequeño.</p>
<h2>Preguntas frecuentes</h2><h3>¿Qué es más fácil de regalar, jamón o paleta?</h3><p>La paleta es más compacta; el jamón suele aportar más cantidad. Para destinatarios sin experiencia de corte, un formato preparado puede ser la opción más sencilla.</p><h3>¿Debo elegir siempre brida negra?</h3><p>No. Es la categoría de bellota 100% ibérico, pero la elección correcta depende del presupuesto y del perfil del destinatario.</p>
{{PRODUCTS}}
HTML,
'en_content'=><<<'HTML'
<p>Giving <strong>Ibérico ham or shoulder ham</strong> works best when the product is chosen for the recipient: household size, consumption frequency, budget and willingness to carve a whole piece.</p>
<p>This selection includes 100% Ibérico de Bellota pieces, cebo de campo alternatives and formats that can make the gift easier to enjoy.</p>
{{PRODUCTS}}
<h2>Ham or shoulder ham as a gift</h2><p>A full jamón generally offers more meat and yield. A shoulder ham is more compact and can suit smaller households or a lower budget.</p>
<h2>Whole piece or prepared format</h2><p>A whole piece suits recipients who enjoy carving and eat Ibérico regularly. Sliced or prepared formats are easier for anyone without a ham stand or carving experience.</p>
<h2>Which category to choose</h2><p><strong>100% Ibérico de Bellota</strong> is the most recognisable top commercial category. Cebo de campo can offer an attractive balance between outdoor husbandry, Ibérico character and budget.</p>
<h2>PDO and origin</h2><p>Los Pedroches PDO options add a defined protected origin. Product pages also identify the producer behind each piece.</p>
<h2>Choosing by budget</h2><p>Compare final price together with size, expected yield and preparation. A premium shoulder ham may fit the recipient better than a larger full ham.</p>
{{PRODUCTS}}
HTML
);

try {
    $results = array();
    foreach ($pages as $cfg) $results[] = emdo_jp_upsert($cfg);

    // Consolidate discoverability from the commercial category; remove the earlier single-link block first.
    $term = get_term_by('slug','jamones-paletas','product_cat');
    if ($term && !is_wp_error($term)) {
        $es = (string) $term->description;
        $en = (string) get_term_meta((int)$term->term_id,'_en_US_description',true);
        $es = preg_replace('/\s*<!-- emdo-jamon-bellota-100-landing-start -->.*?<!-- emdo-jamon-bellota-100-landing-end -->\s*/s', "\n", $es);
        $en = preg_replace('/\s*<!-- emdo-jamon-bellota-100-landing-start -->.*?<!-- emdo-jamon-bellota-100-landing-end -->\s*/s', "\n", $en);
        $start='<!-- emdo-jamon-landings-hub-start -->'; $end='<!-- emdo-jamon-landings-hub-end -->';
        $pattern='/\s*'.preg_quote($start,'/').'.*?'.preg_quote($end,'/').'\s*/s';
        $es=preg_replace($pattern,"\n",$es); $en=preg_replace($pattern,"\n",$en);
        $links_es = array(
            home_url('/jamon-bellota-100-iberico/')=>'Jamón de bellota 100% ibérico',
        );
        $links_en = array(
            home_url('/en/100-iberico-acorn-fed-ham/')=>'100% Ibérico de Bellota ham',
        );
        foreach($results as $r){
            $cfg=null; foreach($pages as $p){if($p['key']===$r['key']){$cfg=$p;break;}}
            if($cfg){$links_es[$r['es_url']]=$cfg['es_title']; $links_en[$r['en_url']]=$cfg['en_title'];}
        }
        $es_items=''; foreach($links_es as $url=>$label){$es_items.='<li><a href="'.esc_url($url).'">'.esc_html($label).'</a></li>';}
        $en_items=''; foreach($links_en as $url=>$label){$en_items.='<li><a href="'.esc_url($url).'">'.esc_html($label).'</a></li>';}
        $es_block=$start.'<div class="emdo-jamon-landings"><h2>Comprar jamón y paleta por tipo</h2><p>Explora selecciones específicas según categoría, origen y formato.</p><ul>'.$es_items.'</ul></div>'.$end;
        $en_block=$start.'<div class="emdo-jamon-landings"><h2>Shop ham and shoulder ham by type</h2><p>Explore selections by category, origin and format.</p><ul>'.$en_items.'</ul></div>'.$end;
        wp_update_term((int)$term->term_id,'product_cat',array('description'=>trim($es)."\n\n".$es_block));
        if($en!=='') update_term_meta((int)$term->term_id,'_en_US_description',trim($en)."\n\n".$en_block);
    }

    // Commercial intent bridge from the existing gift guide, avoiding duplicate informational copy.
    $gift = get_page_by_path('que-jamon-iberico-regalar-pieza-paleta-loncheado-presupuesto', OBJECT, 'post');
    if ($gift instanceof WP_Post) {
        $marker='<!-- emdo-gift-commercial-landing -->';
        $content=(string)$gift->post_content;
        if (strpos($content,$marker)===false) {
            $content .= "\n\n".$marker.'<p><strong>¿Quieres ver directamente las opciones disponibles?</strong> <a href="'.esc_url(home_url('/jamon-paleta-ibericos-para-regalar/')).'">Consulta la selección de jamones y paletas ibéricos para regalar</a>.</p>';
            wp_update_post(array('ID'=>(int)$gift->ID,'post_content'=>$content));
        }
    }

    echo wp_json_encode(array('verified'=>true,'count'=>count($results),'pages'=>$results), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n"); exit(2);
}
