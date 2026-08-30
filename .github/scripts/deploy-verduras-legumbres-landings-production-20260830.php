<?php
/**
 * Production deploy: curated vegetable and legume SEO landings.
 * Spanish page + native English metadata/content. Idempotent.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

function emdo_vl_norm($s) {
    return trim(preg_replace('/\s+/', ' ', remove_accents(mb_strtolower((string)$s))));
}

function emdo_vl_find_category($needles) {
    $terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
    if (is_wp_error($terms)) throw new Exception($terms->get_error_message());
    $matches = array();
    foreach ($terms as $term) {
        $hay = emdo_vl_norm($term->slug.' '.$term->name);
        foreach ($needles as $needle) {
            if (strpos($hay, emdo_vl_norm($needle)) !== false) {
                $matches[] = $term;
                break;
            }
        }
    }
    if (!$matches) throw new Exception('Category not found: '.implode(',', $needles));
    usort($matches, function($a,$b){ return ((int)$b->count <=> (int)$a->count); });
    return $matches[0];
}

function emdo_vl_products_in_category($term_id) {
    $q = new WP_Query(array(
        'post_type'=>'product',
        'post_status'=>'publish',
        'posts_per_page'=>-1,
        'fields'=>'ids',
        'no_found_rows'=>true,
        'tax_query'=>array(array(
            'taxonomy'=>'product_cat',
            'field'=>'term_id',
            'terms'=>array((int)$term_id),
            'include_children'=>true,
        )),
        'orderby'=>'title',
        'order'=>'ASC',
    ));
    $ids = array();
    foreach ($q->posts as $id) {
        $product = wc_get_product($id);
        if ($product && $product->is_in_stock()) $ids[] = (int)$id;
    }
    return array_values(array_unique($ids));
}

function emdo_vl_filter_products($ids, $needles) {
    $out = array();
    foreach ($ids as $id) {
        $post = get_post($id);
        if (!$post) continue;
        $hay = emdo_vl_norm($post->post_title.' '.$post->post_name);
        foreach ($needles as $needle) {
            if (strpos($hay, emdo_vl_norm($needle)) !== false) {
                $out[] = (int)$id;
                break;
            }
        }
    }
    return array_values(array_unique($out));
}

function emdo_vl_shortcode($ids) {
    $live = array();
    foreach (array_unique(array_map('intval',$ids)) as $id) {
        if (get_post_status($id) === 'publish') $live[] = $id;
    }
    if (!$live) return '';
    return '[products ids="'.implode(',', $live).'" columns="3" orderby="post__in"]';
}

function emdo_vl_aioseo($post_id, $title, $description) {
    global $wpdb;
    $table = $wpdb->prefix.'aioseo_posts';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table)) !== $table) return;
    $row_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE post_id=%d LIMIT 1",$post_id));
    if ($row_id) {
        $wpdb->update(
            $table,
            array('title'=>$title,'description'=>$description),
            array('id'=>$row_id),
            array('%s','%s'),
            array('%d')
        );
    }
}

function emdo_vl_page_id($key) {
    $ids = get_posts(array(
        'post_type'=>'page',
        'post_status'=>array('publish','draft','private'),
        'posts_per_page'=>1,
        'fields'=>'ids',
        'no_found_rows'=>true,
        'meta_key'=>'_emdo_vl_landing_key',
        'meta_value'=>$key,
    ));
    return $ids ? (int)$ids[0] : 0;
}

function emdo_vl_upsert($cfg) {
    if (count($cfg['products']) < 2) throw new Exception('Too few products for '.$cfg['key']);
    $shortcode = emdo_vl_shortcode($cfg['products']);
    if ($shortcode === '') throw new Exception('No live products for '.$cfg['key']);

    $es_content = str_replace('{{PRODUCTS}}', $shortcode, $cfg['es_content']);
    $en_content = str_replace('{{PRODUCTS}}', $shortcode, $cfg['en_content']);

    $id = emdo_vl_page_id($cfg['key']);
    if (!$id) {
        $existing = get_page_by_path($cfg['es_slug'], OBJECT, 'page');
        if ($existing instanceof WP_Post) $id = (int)$existing->ID;
    }

    $args = array(
        'post_type'=>'page',
        'post_status'=>'publish',
        'post_title'=>$cfg['es_title'],
        'post_name'=>$cfg['es_slug'],
        'post_content'=>$es_content,
        'post_excerpt'=>$cfg['es_meta'],
        'comment_status'=>'closed',
        'ping_status'=>'closed',
    );
    if ($id) $args['ID'] = $id;

    $saved = wp_insert_post(wp_slash($args), true);
    if (is_wp_error($saved)) throw new Exception($saved->get_error_message());
    $id = (int)$saved;

    update_post_meta($id, '_emdo_vl_landing_key', $cfg['key']);
    update_post_meta($id, '_en_US_post_title', '<span data-no-translation>'.$cfg['en_title'].'</span>');
    update_post_meta($id, '_en_US_post_name', $cfg['en_slug']);
    update_post_meta($id, '_en_US_post_excerpt', $cfg['en_meta']);
    update_post_meta($id, '_en_US_post_content', '<span data-no-translation>'.$en_content.'</span>');
    update_post_meta($id, '_en_US_published', '1');
    emdo_vl_aioseo($id, $cfg['seo_title'], $cfg['es_meta']);

    return array(
        'key'=>$cfg['key'],
        'group'=>$cfg['group'],
        'id'=>$id,
        'es_title'=>$cfg['es_title'],
        'en_title'=>$cfg['en_title'],
        'es_url'=>home_url('/'.$cfg['es_slug'].'/'),
        'en_url'=>home_url('/en/'.$cfg['en_slug'].'/'),
        'products'=>array_values(array_map('intval',$cfg['products'])),
    );
}

function emdo_vl_link_category($term, $pages) {
    if (!$pages) return;
    $start='<!-- emdo-vl-landings-start -->';
    $end='<!-- emdo-vl-landings-end -->';
    $pattern='/\s*'.preg_quote($start,'/').'.*?'.preg_quote($end,'/').'\s*/s';

    $es = preg_replace($pattern, "\n", (string)$term->description);
    $en = (string)get_term_meta((int)$term->term_id, '_en_US_description', true);
    if ($en !== '') $en = preg_replace($pattern, "\n", $en);

    $es_links=array();
    $en_links=array();
    foreach ($pages as $p) {
        $es_links[]='<a href="'.esc_url($p['es_url']).'">'.esc_html($p['es_title']).'</a>';
        $en_links[]='<a href="'.esc_url($p['en_url']).'">'.esc_html($p['en_title']).'</a>';
    }

    $es_block=$start.'<div class="emdo-seo-landings"><p><strong>Explora también:</strong> '.implode(' · ',$es_links).'</p></div>'.$end;
    $r=wp_update_term((int)$term->term_id,'product_cat',array('description'=>trim($es)."\n\n".$es_block));
    if (is_wp_error($r)) throw new Exception($r->get_error_message());

    if ($en !== '') {
        $en_block=$start.'<div class="emdo-seo-landings"><p><strong>Explore also:</strong> '.implode(' · ',$en_links).'</p></div>'.$end;
        update_term_meta((int)$term->term_id,'_en_US_description',trim($en)."\n\n".$en_block);
    }
}

$veg_term = emdo_vl_find_category(array('hortal','verdur'));
$leg_term = emdo_vl_find_category(array('legumbr'));
$veg_ids = emdo_vl_products_in_category($veg_term->term_id);
$leg_ids = emdo_vl_products_in_category($leg_term->term_id);

$groups = array(
    'cestas'=>emdo_vl_filter_products($veg_ids,array('hortaliza','caja','cesta')),
    'patatas'=>emdo_vl_filter_products($veg_ids,array('patata')),
    'pimientos'=>emdo_vl_filter_products($veg_ids,array('pimiento')),
    'alubias'=>emdo_vl_filter_products($leg_ids,array('alubia')),
);

$configs=array();

if (count($groups['cestas']) >= 2) {
$configs[] = array(
    'key'=>'cestas-verduras',
    'group'=>'verduras',
    'es_slug'=>'cestas-verduras-hortalizas-domicilio',
    'en_slug'=>'fresh-vegetable-boxes-delivered-spain',
    'es_title'=>'Cestas de Verduras y Hortalizas a Domicilio',
    'en_title'=>'Fresh Vegetable Boxes Delivered in Spain',
    'seo_title'=>'Cestas de Verduras a Domicilio | Hortalizas de Temporada',
    'es_meta'=>'Compra cestas de verduras y hortalizas frescas a domicilio directamente del agricultor. Compara cajas y formatos de La Huerta de Ana Mary.',
    'en_meta'=>'Buy fresh seasonal vegetable boxes delivered in Spain from the grower. Compare box sizes and multi-delivery options from La Huerta de Ana Mary.',
    'products'=>$groups['cestas'],
    'es_content'=><<<'HTML'
<p>Las <strong>cestas de verduras y hortalizas a domicilio</strong> permiten recibir una selección de producto fresco sin elegir cada referencia por separado. En El Mercado de Origen esta selección procede de La Huerta de Ana Mary, productor de Fresno de la Vega, en León.</p>
<p>La composición puede cambiar con la temporada y la disponibilidad de la huerta. Por eso conviene elegir la cesta por su peso, frecuencia de entrega y tipo de surtido, además de revisar la composición orientativa de cada ficha.</p>
{{PRODUCTS}}
<h2>Cestas para una compra puntual o para varias entregas</h2>
<p>Antes de elegir, distingue entre una caja para una compra concreta y los formatos preparados para recibir varias cajas a lo largo de varias semanas. Si consumes verduras con frecuencia, repartir las entregas puede ser más práctico que recibir toda la cantidad de una sola vez.</p>
<h2>Qué puede incluir una cesta de temporada</h2>
<p>Según la época del año pueden aparecer patatas, pimientos, tomates, cebollas, calabacines, hojas y otras hortalizas. La disponibilidad agrícola cambia, por lo que una cesta de temporada no tiene por qué repetir exactamente la misma composición durante todo el año.</p>
<h2>Cómo elegir el formato</h2>
<p>Compara el peso de cada caja, el número de entregas, el consumo habitual de tu hogar y las condiciones de envío. Para una primera compra puede ser más cómodo comenzar por un formato puntual y valorar después las opciones de varias entregas.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Las cestas llevan siempre las mismas verduras?</h3><p>No necesariamente. La composición puede cambiar según la temporada y la disponibilidad de la huerta.</p>
<h3>¿Hay opciones de varias entregas?</h3><p>El catálogo puede incluir formatos con varias cajas distribuidas a lo largo de distintas semanas; revisa la ficha concreta para confirmar la frecuencia.</p>
<h3>¿De dónde proceden las hortalizas?</h3><p>Las referencias de esta selección proceden de La Huerta de Ana Mary, en Fresno de la Vega (León).</p>
{{PRODUCTS}}
HTML,
    'en_content'=><<<'HTML'
<p><strong>Fresh vegetable boxes delivered in Spain</strong> are a practical way to receive a mixed selection of produce without choosing every vegetable separately. This selection comes from La Huerta de Ana Mary, a grower based in Fresno de la Vega, León.</p>
<p>Contents can change with the season and farm availability. Compare box weight, delivery frequency and the indicative selection shown on each product page.</p>
{{PRODUCTS}}
<h2>One-off boxes and multi-delivery options</h2>
<p>Choose between a single box and formats designed for several deliveries over a number of weeks. For households that use vegetables frequently, spreading deliveries can be more practical than receiving the entire quantity at once.</p>
<h2>What a seasonal vegetable box can contain</h2>
<p>Depending on the season, boxes may include potatoes, peppers, tomatoes, onions, courgettes, leafy vegetables and other produce. Seasonal agriculture means the exact mix can change throughout the year.</p>
<h2>Choosing the right format</h2>
<p>Compare weight per box, number of deliveries, normal household consumption and shipping conditions. A one-off box can be a useful first purchase before choosing a multi-delivery format.</p>
<h2>Frequently asked questions</h2>
<h3>Do the boxes always contain the same vegetables?</h3><p>Not necessarily. Contents can change according to season and farm availability.</p>
<h3>Are multi-delivery options available?</h3><p>The catalogue can include formats with several boxes delivered over different weeks; check the individual product page for the current schedule.</p>
<h3>Where are the vegetables grown?</h3><p>The products in this selection come from La Huerta de Ana Mary in Fresno de la Vega, León, Spain.</p>
{{PRODUCTS}}
HTML
);
}

if (count($groups['patatas']) >= 2) {
$configs[] = array(
    'key'=>'patatas-online',
    'group'=>'verduras',
    'es_slug'=>'comprar-patatas-online-directas-agricultor',
    'en_slug'=>'buy-potatoes-online-direct-from-grower',
    'es_title'=>'Comprar Patatas Online Directas del Agricultor',
    'en_title'=>'Buy Potatoes Online Direct from the Grower',
    'seo_title'=>'Comprar Patatas Online Directas del Agricultor',
    'es_meta'=>'Compra patatas online directamente del agricultor. Compara variedades y formatos de La Huerta de Ana Mary, cultivadas en Fresno de la Vega.',
    'en_meta'=>'Buy potatoes online direct from the grower. Compare varieties and pack sizes from La Huerta de Ana Mary in Fresno de la Vega, Spain.',
    'products'=>$groups['patatas'],
    'es_content'=><<<'HTML'
<p>Al <strong>comprar patatas online directamente del agricultor</strong> puedes comparar variedad, procedencia y formato. Las patatas de esta selección están vinculadas a La Huerta de Ana Mary, en Fresno de la Vega.</p>
<p>La elección depende del uso culinario y, especialmente en formatos grandes, de la cantidad que realmente vas a consumir y de cómo podrás conservarla en casa.</p>
{{PRODUCTS}}
<h2>La variedad importa</h2>
<p>No todas las patatas se comportan igual en cocina. Algunas son más versátiles y otras resultan especialmente adecuadas para cocción, asado, fritura o guisos. Entre las referencias disponibles puede encontrarse Red Pontiac, reconocible por su piel roja.</p>
<h2>Formatos grandes: cuándo tienen sentido</h2>
<p>Comprar varios kilos puede reducir la frecuencia de reposición, pero solo compensa si existe suficiente consumo y un lugar adecuado para guardarlas. Una zona fresca, seca, ventilada y protegida de la luz ayuda a conservarlas mejor.</p>
<h2>Qué comparar antes de comprar</h2>
<p>Revisa variedad, kilos totales, uso previsto, condiciones de envío y capacidad de almacenamiento en casa.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es la patata Red Pontiac?</h3><p>Es una variedad de piel roja utilizada en diferentes preparaciones por su carácter versátil.</p>
<h3>¿Compensa comprar formatos grandes?</h3><p>Puede compensar en hogares con consumo alto si se dispone de un lugar adecuado para almacenarlas.</p>
<h3>¿Quién produce estas patatas?</h3><p>Las referencias de esta selección proceden de La Huerta de Ana Mary, en Fresno de la Vega.</p>
{{PRODUCTS}}
HTML,
    'en_content'=><<<'HTML'
<p>When you <strong>buy potatoes online direct from the grower</strong>, you can compare variety, origin and pack size. The products in this selection are linked to La Huerta de Ana Mary in Fresno de la Vega, León.</p>
<p>The right choice depends on cooking use and, especially with larger packs, how much your household will realistically consume and store well.</p>
{{PRODUCTS}}
<h2>Potato variety matters</h2>
<p>Different varieties behave differently in the kitchen. Some are highly versatile, while others are better suited to boiling, roasting, frying or stews. Available products can include Red Pontiac, a red-skinned variety.</p>
<h2>When larger packs make sense</h2>
<p>Buying several kilograms reduces how often you need to restock, but it works best when consumption is high enough and storage conditions are suitable. Keep potatoes in a cool, dry, ventilated place away from light.</p>
<h2>What to compare before ordering</h2>
<p>Check variety, total weight, intended use, shipping conditions and storage space at home.</p>
<h2>Frequently asked questions</h2>
<h3>What is Red Pontiac?</h3><p>It is a red-skinned potato variety used for a range of cooking methods.</p>
<h3>Are larger packs practical?</h3><p>They can be for high-consumption households with suitable cool, dry and dark storage.</p>
<h3>Who grows these potatoes?</h3><p>The references in this selection come from La Huerta de Ana Mary in Fresno de la Vega, Spain.</p>
{{PRODUCTS}}
HTML
);
}

if (count($groups['pimientos']) >= 2) {
$configs[] = array(
    'key'=>'pimientos-asar',
    'group'=>'verduras',
    'es_slug'=>'pimientos-para-asar-online',
    'en_slug'=>'roasting-peppers-online',
    'es_title'=>'Pimientos para Asar',
    'en_title'=>'Roasting Peppers Online',
    'seo_title'=>'Comprar Pimientos para Asar | Pimiento Lamuyo Rojo',
    'es_meta'=>'Compra pimientos para asar directamente del agricultor. Compara pimiento Lamuyo rojo y formatos grandes de La Huerta de Ana Mary.',
    'en_meta'=>'Buy roasting peppers direct from the grower. Compare red Lamuyo peppers and larger formats from La Huerta de Ana Mary.',
    'products'=>$groups['pimientos'],
    'es_content'=><<<'HTML'
<p>Los <strong>pimientos para asar</strong> funcionan especialmente bien cuando tienen carne suficiente para soportar el calor, pelarse con facilidad y mantener una textura agradable. En esta selección puedes encontrar pimientos de La Huerta de Ana Mary, incluidos formatos de pimiento Lamuyo rojo.</p>
{{PRODUCTS}}
<h2>Por qué el Lamuyo encaja bien para asar</h2>
<p>El pimiento Lamuyo se caracteriza por su tamaño y carne gruesa. Estas cualidades lo hacen práctico para horno, parrilla, rellenos y preparaciones en las que después se quiere retirar la piel.</p>
<h2>Comprar por unidades o en formato grande</h2>
<p>Para consumo cotidiano puede bastar con cantidades pequeñas. Si vas a preparar varias bandejas, congelar porciones o cocinar para muchas personas, un formato de varios kilos puede resultar más práctico.</p>
<h2>Del agricultor a casa</h2>
<p>Conocer el productor permite elegir por variedad, procedencia y uso. Revisa cada ficha para confirmar peso, disponibilidad y condiciones de envío.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿El pimiento Lamuyo es bueno para asar?</h3><p>Sí. Su tamaño y carne gruesa lo hacen especialmente adecuado para asado, además de otros usos.</p>
<h3>¿Puedo comprar una caja grande para asar?</h3><p>Cuando está disponible, el catálogo incluye formatos de varios kilos; revisa la ficha concreta para confirmar la cantidad.</p>
<h3>¿Se puede usar también en crudo?</h3><p>Sí. El Lamuyo puede utilizarse en ensaladas y otras preparaciones además del asado.</p>
{{PRODUCTS}}
HTML,
    'en_content'=><<<'HTML'
<p><strong>Roasting peppers</strong> benefit from enough flesh to handle high heat, peel easily and retain a pleasant texture. This selection includes peppers from La Huerta de Ana Mary, including red Lamuyo formats.</p>
{{PRODUCTS}}
<h2>Why Lamuyo works well for roasting</h2>
<p>Lamuyo peppers are known for their size and thick flesh, making them useful for oven roasting, grilling, stuffing and recipes where the skin is removed afterwards.</p>
<h2>Small quantities or larger boxes</h2>
<p>Small amounts suit everyday cooking. Larger multi-kilogram formats can be practical for batch roasting, freezing portions or cooking for a group.</p>
<h2>From the grower to your home</h2>
<p>A visible producer makes it easier to choose by variety, origin and intended use. Check each product page for current weight, availability and shipping conditions.</p>
<h2>Frequently asked questions</h2>
<h3>Are Lamuyo peppers good for roasting?</h3><p>Yes. Their size and thick flesh make them particularly suitable for roasting.</p>
<h3>Can I buy a larger box?</h3><p>Multi-kilogram formats are available when listed in the catalogue; check the current product page for quantity.</p>
<h3>Can Lamuyo peppers be eaten raw?</h3><p>Yes. They also work in salads and other raw preparations.</p>
{{PRODUCTS}}
HTML
);
}

if (count($groups['alubias']) >= 2) {
$configs[] = array(
    'key'=>'alubias-secas',
    'group'=>'legumbres',
    'es_slug'=>'comprar-alubias-secas-online',
    'en_slug'=>'buy-dried-beans-online-spain',
    'es_title'=>'Comprar Alubias Secas Online',
    'en_title'=>'Buy Dried Beans Online from Spain',
    'seo_title'=>'Comprar Alubias Secas Online | Blanca, Pinta y Canela',
    'es_meta'=>'Compra alubias secas online directamente del productor. Compara alubia blanca, pinta y canela de La Huerta de Ana Mary.',
    'en_meta'=>'Buy dried beans online from a Spanish grower. Compare white kidney, pinto and canela beans from La Huerta de Ana Mary.',
    'products'=>$groups['alubias'],
    'es_content'=><<<'HTML'
<p>Al <strong>comprar alubias secas online</strong> conviene elegir primero por variedad y por el tipo de plato que se quiere preparar. En El Mercado de Origen puedes comparar alubia blanca, alubia pinta y alubia canela de La Huerta de Ana Mary.</p>
<p>Las tres son legumbres secas, pero pueden ofrecer diferencias de tamaño, piel, textura interior y comportamiento en el caldo.</p>
{{PRODUCTS}}
<h2>Alubia blanca, pinta o canela</h2>
<p>La alubia blanca es muy versátil para guisos y potajes. La pinta aporta una apariencia y carácter diferentes, mientras que la canela se reconoce por su color uniforme. No existe una variedad universalmente mejor: la elección depende de la receta y de la textura que busques.</p>
<h2>Ventajas de cocinar legumbre seca</h2>
<p>La legumbre seca permite controlar el remojo, el punto de cocción, la sal y el caldo desde el principio. Requiere más planificación que una legumbre ya cocida, pero ofrece mayor control sobre la textura final.</p>
<h2>Directas del productor</h2>
<p>Las referencias de esta selección proceden de La Huerta de Ana Mary. Identificar al productor y la procedencia ayuda a comparar productos concretos más allá de una etiqueta genérica de “legumbres”.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Las alubias secas necesitan remojo?</h3><p>Muchas variedades agradecen un remojo previo para hidratarse de forma uniforme y reducir el tiempo de cocción. Sigue las indicaciones de cada producto y receta.</p>
<h3>¿Qué alubia es mejor para guisar?</h3><p>Depende del plato. Blanca, pinta y canela pueden funcionar en guisos, con matices de textura, aspecto y sabor diferentes.</p>
<h3>¿Quién produce estas alubias?</h3><p>Las referencias de esta selección proceden de La Huerta de Ana Mary.</p>
{{PRODUCTS}}
HTML,
    'en_content'=><<<'HTML'
<p>When you <strong>buy dried beans online</strong>, variety and intended dish are useful starting points. El Mercado de Origen lets you compare white kidney beans, pinto beans and canela beans from La Huerta de Ana Mary.</p>
<p>They are all dried pulses, but they can differ in size, skin, interior texture and how they behave in slow-cooked dishes.</p>
{{PRODUCTS}}
<h2>White, pinto or canela beans</h2>
<p>White beans are versatile for stews and soups. Pinto beans bring a different appearance and character, while canela beans are recognisable by their even cinnamon-brown colour. The best choice depends on the recipe and texture you want.</p>
<h2>Why choose dried beans</h2>
<p>Dried beans let you control soaking, cooking point, seasoning and broth from the beginning. They require more planning than ready-cooked beans but give you more control over the final texture.</p>
<h2>Direct from the producer</h2>
<p>The products in this selection come from La Huerta de Ana Mary. A visible producer and origin make it easier to compare identifiable products rather than anonymous generic pulses.</p>
<h2>Frequently asked questions</h2>
<h3>Do dried beans need soaking?</h3><p>Many varieties benefit from soaking for even hydration and shorter cooking. Follow the guidance for the specific product and recipe.</p>
<h3>Which beans are best for stews?</h3><p>It depends on the dish. White, pinto and canela beans can all work, with different textures, colours and flavour profiles.</p>
<h3>Who produces these beans?</h3><p>The products in this selection come from La Huerta de Ana Mary in Spain.</p>
{{PRODUCTS}}
HTML
);
}

$created=array();
$veg_pages=array();
$leg_pages=array();

foreach ($configs as $cfg) {
    $page = emdo_vl_upsert($cfg);
    $created[]=$page;
    if ($cfg['group']==='verduras') $veg_pages[]=$page;
    if ($cfg['group']==='legumbres') $leg_pages[]=$page;
}

emdo_vl_link_category($veg_term,$veg_pages);
emdo_vl_link_category($leg_term,$leg_pages);

$verified = count($created) > 0;
foreach ($created as $p) {
    if (get_post_status($p['id']) !== 'publish') $verified=false;
    if (count($p['products']) < 2) $verified=false;
    if ((string)get_post_meta($p['id'],'_en_US_published',true) !== '1') $verified=false;
}

echo wp_json_encode(array(
    'verified'=>$verified,
    'count'=>count($created),
    'vegetables'=>count($veg_pages),
    'legumes'=>count($leg_pages),
    'category_terms'=>array(
        'vegetables'=>array('id'=>(int)$veg_term->term_id,'slug'=>$veg_term->slug,'url'=>get_term_link($veg_term)),
        'legumes'=>array('id'=>(int)$leg_term->term_id,'slug'=>$leg_term->slug,'url'=>get_term_link($leg_term)),
    ),
    'groups'=>array_map('array_values',$groups),
    'pages'=>$created,
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
