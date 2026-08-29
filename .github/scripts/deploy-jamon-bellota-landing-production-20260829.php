<?php
/**
 * Idempotent production deploy for the Jamón de Bellota 100% Ibérico SEO landing.
 * Spanish is the native WP page; English is stored in the site's reviewed _en_US_* layer.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

function emdo_landing_norm($value) {
    $value = remove_accents(wp_strip_all_tags((string) $value));
    $value = strtolower($value);
    return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
}

function emdo_landing_products() {
    $ids = array();
    foreach (array(
        'jamon-de-bellota-100-iberico',
        'jamon-de-bellota-100-iberico-con-dop-los-pedroches-brida-negra',
    ) as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'product');
        if ($p instanceof WP_Post && $p->post_status === 'publish') { $ids[] = (int) $p->ID; }
    }
    $q = new WP_Query(array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>100,
        'orderby'=>'menu_order title','order'=>'ASC','no_found_rows'=>true,
        'update_post_meta_cache'=>false,'update_post_term_cache'=>false,
    ));
    foreach ($q->posts as $p) {
        $t = emdo_landing_norm($p->post_title);
        if (strpos($t,'jamon')!==false && strpos($t,'bellota')!==false && strpos($t,'100')!==false && strpos($t,'iberic')!==false && strpos($t,'paleta')===false && strpos($t,'lonche')===false) {
            $ids[] = (int) $p->ID;
        }
    }
    return array_slice(array_values(array_unique(array_filter($ids))), 0, 8);
}

function emdo_landing_products_shortcode($ids) {
    if (!$ids) return '';
    $ids = array_map('intval',$ids);
    return '[products ids="'.implode(',',$ids).'" columns="'.min(3,max(1,count($ids))).'" orderby="post__in"]';
}

function emdo_landing_aioseo($post_id, $title, $description) {
    global $wpdb;
    $table = $wpdb->prefix . 'aioseo_posts';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table)) !== $table) return;
    $row_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE post_id=%d LIMIT 1",$post_id));
    if (!$row_id) return;
    $wpdb->update($table, array('title'=>$title,'description'=>$description), array('id'=>$row_id), array('%s','%s'), array('%d'));
}

function emdo_landing_append_category_links($es_url, $en_url) {
    $term = get_term_by('slug','jamones-paletas','product_cat');
    if (!$term || is_wp_error($term)) return false;
    $start='<!-- emdo-jamon-bellota-100-landing-start -->';
    $end='<!-- emdo-jamon-bellota-100-landing-end -->';
    $pattern='/\s*'.preg_quote($start,'/').'.*?'.preg_quote($end,'/').'\s*/s';

    $es=(string)$term->description;
    $es=preg_replace($pattern,"\n",$es);
    $es_block=$start.'<p><strong>¿Buscas jamón de bellota 100% ibérico?</strong> <a href="'.esc_url($es_url).'">Consulta nuestra selección de jamón brida negra y compara las opciones disponibles</a>.</p>'.$end;
    $r=wp_update_term((int)$term->term_id,'product_cat',array('description'=>trim($es)."\n\n".$es_block));

    $en=(string)get_term_meta((int)$term->term_id,'_en_US_description',true);
    if ($en!=='') {
        $en=preg_replace($pattern,"\n",$en);
        $en_block=$start.'<p><strong>Looking for 100% Ibérico de Bellota ham?</strong> <a href="'.esc_url($en_url).'">Explore our black-label ham selection and compare the available options</a>.</p>'.$end;
        update_term_meta((int)$term->term_id,'_en_US_description',trim($en)."\n\n".$en_block);
    }
    return !is_wp_error($r);
}

try {
    $ids=emdo_landing_products();
    if (!$ids) throw new Exception('No matching 100% Ibérico de Bellota ham products found.');
    $products=emdo_landing_products_shortcode($ids);

    $es=<<<'HTML'
<p>El <strong>jamón de bellota 100% ibérico</strong> reúne dos características fundamentales: procede de animales 100% de raza ibérica y pertenece a la categoría de bellota, ligada al aprovechamiento de los recursos de la dehesa durante la montanera.</p>
<p>En España se identifica mediante el <strong>precinto negro o brida negra</strong>, reservado a esta denominación. Es también la única categoría para la que la normativa permite utilizar oficialmente la expresión <strong>“pata negra”</strong>.</p>
<p>En El Mercado de Origen puedes comprar jamones de bellota 100% ibéricos procedentes directamente de productores especializados y comparar diferentes pesos, formatos y opciones con o sin Denominación de Origen Protegida.</p>
{{PRODUCTS}}
<h2>¿Qué significa Jamón de Bellota 100% Ibérico?</h2>
<p>La denominación permite saber dos cosas diferentes sobre el jamón.</p>
<p><strong>100% ibérico</strong> hace referencia a la raza. Tanto el padre como la madre del animal pertenecen a la raza ibérica y están inscritos en el correspondiente Libro Genealógico.</p>
<p><strong>De bellota</strong> hace referencia a su alimentación y manejo durante la fase final de engorde. Los animales aprovechan los recursos naturales de la dehesa durante la montanera, entre ellos bellotas y pastos.</p>
<p>La combinación de ambas características es la que identifica al <strong>jamón de bellota 100% ibérico</strong>.</p>
<h2>Brida negra: cómo identificar un jamón de bellota 100% ibérico</h2>
<p>La forma más sencilla de reconocer esta categoría es observar el precinto colocado en la pieza.</p>
<p>La normativa del ibérico establece diferentes colores según raza, alimentación y sistema de producción. El <strong>precinto negro identifica exclusivamente los jamones y paletas de bellota 100% ibéricos</strong>.</p>
<ul><li>Brida negra = bellota + 100% raza ibérica.</li><li>“Pata negra” = expresión reservada a esta misma categoría.</li><li>No todos los jamones ibéricos son brida negra.</li><li>No todos los jamones de bellota son necesariamente 100% ibéricos.</li></ul>
<h2>¿Qué jamón de bellota 100% ibérico elegir?</h2>
<p>Dentro de una misma categoría pueden existir diferencias importantes entre unas piezas y otras. Antes de comprar conviene fijarse especialmente en el productor, el origen, el peso, el formato y la existencia o no de una Denominación de Origen.</p>
<h3>Con DOP o sin DOP</h3>
<p>Un jamón de bellota 100% ibérico puede cumplir la Norma de Calidad del Ibérico sin pertenecer a una Denominación de Origen Protegida.</p>
<p>En nuestro catálogo puedes encontrar tanto <strong>jamón de bellota 100% ibérico</strong> como opciones certificadas por la <strong>DOP Los Pedroches</strong>. La DOP añade requisitos específicos relacionados con el territorio y con su propio pliego de condiciones, pero un jamón brida negra sin DOP continúa perteneciendo a la categoría de bellota 100% ibérico.</p>
<h3>Pieza entera o loncheado</h3>
<p>La pieza entera es la opción tradicional y permite ir cortando el jamón según se consume.</p>
<p>El formato loncheado resulta más cómodo cuando no se dispone de jamonero, cuchillo o experiencia de corte, o cuando se quiere consumir el producto durante más tiempo abriendo únicamente los sobres necesarios. También existen opciones cortadas a cuchillo para quienes buscan combinar comodidad y un corte tradicional.</p>
<h3>¿Qué peso elegir?</h3>
<p>No existe un peso ideal para todos los compradores. La elección depende principalmente del número de personas, la frecuencia de consumo y el formato en el que se vaya a recibir.</p>
<p>Para un consumo frecuente o reuniones numerosas puede interesar una pieza mayor. Para hogares con un consumo más ocasional puede resultar más práctico escoger un peso inferior o recurrir al formato loncheado.</p>
<h2>Jamón de bellota 100% ibérico de Hidalgo de la Jara</h2>
<p>Entre los productores presentes en El Mercado de Origen se encuentra <strong>Hidalgo de la Jara</strong>, especializado en productos ibéricos y vinculado a la dehesa.</p>
<p>Sus jamones de bellota 100% ibéricos están disponibles tanto sin DOP como con certificación <strong>DOP Los Pedroches</strong>, permitiendo comparar ambas opciones dentro del mismo productor. Además de las piezas enteras, existen diferentes alternativas de preparación y loncheado según el producto.</p>
<h2>Comprar jamón ibérico directamente del productor</h2>
<p>El objetivo de El Mercado de Origen es acercar productos españoles de origen al consumidor y mostrar de forma clara quién está detrás de cada producto.</p>
<p>Por eso en cada jamón puedes consultar su productor, características, formatos disponibles y precio antes de comprar. Puedes comparar:</p>
<ul><li>porcentaje de raza ibérica;</li><li>alimentación;</li><li>precinto;</li><li>procedencia;</li><li>DOP;</li><li>peso;</li><li>formato de preparación;</li><li>productor.</li></ul>
<h2>Preguntas frecuentes sobre el jamón de bellota 100% ibérico</h2>
<h3>¿Brida negra y pata negra significan lo mismo?</h3><p>En la normativa del ibérico, la expresión “pata negra” está reservada a los productos con denominación <strong>de bellota 100% ibérico</strong>, que se identifican mediante precinto negro.</p>
<h3>¿Un jamón de bellota siempre es 100% ibérico?</h3><p>No. Existen jamones de bellota procedentes de animales con otros porcentajes de raza ibérica. Por eso es importante comprobar tanto la alimentación como el porcentaje racial indicado en el etiquetado.</p>
<h3>¿Un jamón brida negra tiene que tener DOP?</h3><p>No. La brida negra identifica la categoría de bellota 100% ibérico. Una DOP es una certificación adicional vinculada a un territorio y a unas condiciones específicas de producción.</p>
<h3>¿Es mejor comprarlo entero o loncheado?</h3><p>Depende de cómo vaya a consumirse. La pieza entera conserva la experiencia tradicional de corte, mientras que el loncheado ofrece mayor comodidad y permite abrir pequeñas cantidades cuando se necesitan.</p>
<h3>¿Dónde puedo comprar jamón de bellota 100% ibérico?</h3><p>En El Mercado de Origen puedes comparar las distintas opciones disponibles, consultar quién produce cada jamón y elegir peso, formato y certificación según tus preferencias.</p>
{{PRODUCTS}}
HTML;

    $en=<<<'HTML'
<span data-no-translation><p><strong>100% Ibérico de Bellota ham</strong> is made from pure-bred Ibérico pigs and belongs to Spain's acorn-fed category of Ibérico ham.</p>
<p>In Spain, this designation is identified by the official <strong>black seal or “brida negra”</strong>. It is also the only category for which the term <strong>“pata negra”</strong> is legally reserved.</p>
<p>At El Mercado de Origen you can buy 100% Ibérico de Bellota ham from specialist Spanish producers and compare different sizes, formats and options, including hams certified under the <strong>Los Pedroches Protected Designation of Origin (PDO)</strong>.</p>
{{PRODUCTS}}
<h2>What does 100% Ibérico de Bellota mean?</h2>
<p>The name describes two different characteristics of the ham.</p>
<p><strong>100% Ibérico</strong> refers to breed. The pig comes from two 100% Ibérico parents registered within the official breed genealogy system.</p>
<p><strong>De bellota</strong>, or acorn-fed, refers to the animal's feeding and management during its final fattening stage, when pigs make use of the natural resources of the Spanish dehesa during the montanera season.</p>
<p>Together, these two elements give us the designation <strong>Jamón de Bellota 100% Ibérico</strong>.</p>
<h2>What is the black label on Spanish Ibérico ham?</h2>
<p>Spain uses different coloured seals to help identify the official categories of Ibérico ham. The <strong>black seal — brida negra — is exclusively used for 100% Ibérico de Bellota ham and shoulder ham</strong>.</p>
<ul><li>Black seal = acorn-fed + 100% Ibérico breed.</li><li>“Pata negra” is reserved for the same category.</li><li>Not every Ibérico ham carries a black seal.</li><li>Not every acorn-fed Ibérico ham is necessarily 100% Ibérico.</li></ul>
<h2>How to choose a 100% Ibérico de Bellota ham</h2>
<p>Hams within the same official category can still differ considerably. Producer, origin, weight, preparation and PDO certification are all useful factors to consider before buying.</p>
<h3>PDO or non-PDO</h3>
<p>A 100% Ibérico de Bellota ham does not need to belong to a Protected Designation of Origin to qualify for the Spanish black-label category.</p>
<p>Our range includes both <strong>100% Ibérico de Bellota ham</strong> and hams certified by the <strong>Los Pedroches PDO</strong>. PDO certification adds requirements connected with a specific geographical area and its production specifications. A non-PDO black-label ham, however, can still fully qualify as 100% Ibérico de Bellota.</p>
<h3>Whole ham or sliced</h3>
<p>A whole bone-in jamón offers the traditional experience of carving the ham as it is eaten.</p>
<p>Pre-sliced ham is a convenient alternative if you do not have a ham stand and carving knife or if you prefer to open individual packs whenever needed. Some products are also available hand-carved, combining the convenience of individual packs with traditional knife slicing.</p>
<h3>Choosing the right weight</h3>
<p>There is no single ideal weight for every household. Your choice will mainly depend on how many people will eat the ham, how frequently it will be served and whether you prefer a whole piece or pre-sliced packs.</p>
<p>A larger whole ham may suit families, celebrations or frequent consumption, while smaller or sliced formats can be more practical for occasional use.</p>
<h2>100% Ibérico de Bellota ham from Hidalgo de la Jara</h2>
<p>One of the specialist producers available through El Mercado de Origen is <strong>Hidalgo de la Jara</strong>, whose Ibérico products are closely connected with Spain's dehesa landscape.</p>
<p>Its range includes 100% Ibérico de Bellota hams both with and without <strong>Los Pedroches PDO certification</strong>, making it possible to compare the two options from the same producer. Different preparation and slicing options are also available depending on the product selected.</p>
<h2>Buy Ibérico ham from Spanish producers</h2>
<p>El Mercado de Origen connects customers with food producers from across Spain while making the origin of each product visible. For every ham, you can check the producer and compare the available characteristics and formats before ordering.</p>
<ul><li>Ibérico breed percentage;</li><li>feeding category;</li><li>official seal;</li><li>geographical origin;</li><li>PDO certification;</li><li>weight;</li><li>preparation;</li><li>producer.</li></ul>
<h2>Frequently asked questions</h2>
<h3>Is black-label Ibérico ham the same as pata negra?</h3><p>Under Spanish Ibérico regulations, the term “pata negra” is reserved for <strong>100% Ibérico de Bellota</strong> products. These products are identified with the official black seal.</p>
<h3>Is all acorn-fed Ibérico ham 100% Ibérico?</h3><p>No. Acorn-fed Ibérico ham can come from pigs with different percentages of Ibérico ancestry. This is why both breed percentage and feeding category should be checked.</p>
<h3>Does black-label Ibérico ham need PDO certification?</h3><p>No. The black seal identifies the 100% Ibérico de Bellota category. PDO certification is separate and adds geographical and production requirements associated with a particular protected region.</p>
<h3>Should I buy a whole ham or sliced packs?</h3><p>A whole ham offers the traditional carving experience and works particularly well for regular consumption or entertaining. Sliced packs are more convenient and allow smaller portions to be opened as needed.</p>
<h3>Where can I buy 100% Ibérico de Bellota ham online?</h3><p>At El Mercado de Origen you can compare available hams from Spanish producers and choose according to weight, format, origin and certification.</p>
{{PRODUCTS}}</span>
HTML;

    $es=str_replace('{{PRODUCTS}}',$products,$es);
    $en=str_replace('{{PRODUCTS}}',$products,$en);

    $slug='jamon-bellota-100-iberico';
    $page=get_page_by_path($slug,OBJECT,'page');
    $data=array(
        'post_type'=>'page','post_status'=>'publish','post_name'=>$slug,
        'post_title'=>'Jamón de Bellota 100% Ibérico: Brida Negra',
        'post_excerpt'=>'Compra jamón de bellota 100% ibérico de brida negra, directo del productor. Compara formatos, DOP Los Pedroches y opciones disponibles.',
        'post_content'=>$es,'comment_status'=>'closed','ping_status'=>'closed'
    );
    if ($page instanceof WP_Post) { $data['ID']=(int)$page->ID; $id=wp_update_post(wp_slash($data),true); }
    else { $id=wp_insert_post(wp_slash($data),true); }
    if (is_wp_error($id)) throw new Exception($id->get_error_message());
    $id=(int)$id;

    update_post_meta($id,'_en_US_post_title','<span data-no-translation>100% Ibérico de Bellota Ham: Spanish Black Label Jamón</span>');
    update_post_meta($id,'_en_US_post_excerpt','Buy 100% Ibérico acorn-fed ham from selected Spanish producers. Compare whole ham, sliced formats and Los Pedroches PDO options.');
    update_post_meta($id,'_en_US_post_content',$en);
    update_post_meta($id,'_en_US_post_name','100-iberico-acorn-fed-ham');
    update_post_meta($id,'_en_US_published','1');

    // Legacy SEO meta kept compatible; active AIOSEO row is updated below when present.
    update_post_meta($id,'_yoast_wpseo_title','Comprar Jamón de Bellota 100% Ibérico | Brida Negra');
    update_post_meta($id,'_yoast_wpseo_metadesc','Compra jamón de bellota 100% ibérico de brida negra, directo del productor. Compara formatos, DOP Los Pedroches y opciones disponibles.');
    delete_post_meta($id,'_yoast_wpseo_meta-robots-noindex');
    update_post_meta($id,'rank_math_robots',array('index','follow'));
    emdo_landing_aioseo($id,'Comprar Jamón de Bellota 100% Ibérico | Brida Negra','Compra jamón de bellota 100% ibérico de brida negra, directo del productor. Compara formatos, DOP Los Pedroches y opciones disponibles.');

    clean_post_cache($id);
    $es_url=home_url('/jamon-bellota-100-iberico/');
    $en_url=home_url('/en/100-iberico-acorn-fed-ham/');
    $linked=emdo_landing_append_category_links($es_url,$en_url);

    if (function_exists('wp_cache_flush')) wp_cache_flush();
    echo wp_json_encode(array('verified'=>true,'id'=>$id,'es_url'=>$es_url,'en_url'=>$en_url,'products'=>$ids,'category_linked'=>$linked),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
    fwrite(STDERR,wp_json_encode(array('verified'=>false,'error'=>$e->getMessage()),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
    exit(1);
}
