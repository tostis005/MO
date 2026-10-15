<?php
/**
 * Targeted, idempotent commercial editorial improvements.
 * Run ONLY with WordPress loaded via WP-CLI on the canonical production site.
 * No URL, title, slug, canonical, SEO plugin metadata or product mutation.
 */
if (!defined('ABSPATH') || !function_exists('wp_update_post') || !taxonomy_exists('product_cat')) {
    throw new RuntimeException('WordPress/WooCommerce not available');
}

function mdo_ev_post(string $slug): WP_Post {
    $post = get_page_by_path($slug, OBJECT, 'post');
    if (!$post instanceof WP_Post || $post->post_status !== 'publish') {
        throw new RuntimeException('Expected live post missing: ' . $slug);
    }
    return $post;
}
function mdo_ev_term(string $slug, string $taxonomy): WP_Term {
    $t = get_term_by('slug', $slug, $taxonomy);
    if (!$t instanceof WP_Term) throw new RuntimeException('Required term missing: '.$taxonomy.'/'.$slug);
    return $t;
}
function mdo_ev_url(WP_Term $term): string {
    $u = get_term_link($term);
    if (is_wp_error($u)) throw new RuntimeException('Unresolved term URL: '.$term->slug);
    return (string)$u;
}
function mdo_ev_insert_before_end(string $html, string $new, string $marker): string {
    if (strpos($html,$marker) !== false) return $html;
    return rtrim($html) . "\n\n" . $new;
}
$shop = array(
    'verduras' => mdo_ev_term('hortalizas-verduras', 'product_cat'),
    'carnes' => mdo_ev_term('carnes', 'product_cat'),
    'aceites' => mdo_ev_term('aceites', 'product_cat'),
    'embutidos' => mdo_ev_term('embutidos-y-curados', 'product_cat'),
    'packs' => mdo_ev_term('packs-y-lotes', 'product_cat'),
);
$url = array();
foreach ($shop as $k => $term) $url[$k] = esc_url(mdo_ev_url($term));

$vegetables = $shop['verduras'];
$es_description = '<p>Si buscas <strong>comprar verduras y hortalizas online</strong>, aquí puedes consultar la selección disponible, conocer al vendedor y elegir los productos según su procedencia, variedad y uso en cocina. Las campañas cambian a lo largo del año: conviene revisar cada ficha antes de hacer el pedido, en lugar de suponer que una hortaliza está disponible siempre.</p>'
. '<p>En El Mercado de Origen reunimos hortalizas de productores y especialistas identificados. Revisa las condiciones de entrega y conservación de cada producto y, si quieres saber qué variedades corresponden a cada época, visita nuestras <a href="'.esc_url(home_url('/categoria/hortalizas-y-verduras/')).'">guías de hortalizas y verduras</a>.</p>';
$en_description = '<p>Explore the <strong>vegetables available to order online</strong> and check the seller, provenance, variety and suggested uses for each item. Harvests change with the season, so availability and delivery conditions should always be checked on the individual product page.</p>'
. '<p>El Mercado de Origen brings together clearly identified growers and specialist sellers. For help choosing vegetables by season and understanding their storage needs, explore our <a href="'.esc_url(home_url('/en/category/vegetables/')).'">vegetable guides</a>.</p>';
$old_description = (string)$vegetables->description;
$old_en_description = (string)get_term_meta((int)$vegetables->term_id, '_en_US_description', true);
if (get_term_meta((int)$vegetables->term_id, '_mdo_ev_original_description_20261015', true) === '') {
    update_term_meta((int)$vegetables->term_id, '_mdo_ev_original_description_20261015', $old_description);
    update_term_meta((int)$vegetables->term_id, '_mdo_ev_original_en_description_20261015', $old_en_description);
}
if ($old_description !== $es_description) {
    $result = wp_update_term((int)$vegetables->term_id,'product_cat',array('description'=>$es_description));
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
if ((string)get_term_meta((int)$vegetables->term_id,'_en_US_published',true) === '1'
    && $old_en_description !== $en_description) {
    update_term_meta((int)$vegetables->term_id,'_en_US_description',$en_description);
}

$articles = array(
 'comprar-carne-online-que-mirar-antes-elegir-corte' => array(
  'es' => '<!-- mdo-evergreen-carne-20261015 --><h2>Cuando la compra es a un ganadero o a un especialista</h2><p>Comprar cerca del origen puede facilitar preguntas que en un escaparate genérico quedan sin respuesta: de qué explotación o zona procede el animal, quién realiza el despiece, qué información de trazabilidad se facilita y quién prepara el envío. No hay que confundir automáticamente al ganadero con la carnicería o con el vendedor: cada uno puede intervenir en una fase distinta. En una compra online fiable, esas funciones deberían poder identificarse sin deducirlas de un eslogan.</p><p>Para comparar lo que realmente se puede pedir, revisa las fichas y condiciones de nuestra <a href="'.$url['carnes'].'">selección de carnes</a> antes de decidir el corte.</p>',
  'en' => '<!-- mdo-evergreen-carne-20261015 --><h2>Buying from a farmer or a specialist seller</h2><p>Buying closer to the source can make it easier to ask where an animal was raised, who prepared the cuts, what traceability information is provided and who dispatches the order. The farmer, butcher, processor and seller may be different businesses; a trustworthy product page should make their roles understandable rather than relying on a vague direct-from-farm slogan.</p><p>Check current cuts and the seller-specific delivery details in our <a href="'.esc_url(home_url('/en/product-category/meat/')).'">meat selection</a>.</p>',
 ),
 'como-comprar-alimentos-gourmet-online-que-mirar-ademas-precio' => array(
  'es' => '<!-- mdo-evergreen-gourmet-20261015 --><h2>Explora por familias de alimentos, no solo por etiquetas comerciales</h2><p>La información útil cambia según lo que quieras comprar. En un <a href="'.$url['aceites'].'">aceite de oliva</a> tiene sentido preguntar por variedad, cosecha y almazara; en <a href="'.$url['embutidos'].'">embutidos curados</a>, por denominación, elaboración e ingredientes; y en <a href="'.$url['packs'].'">packs y lotes</a>, por el contenido de cada selección y la logística de los distintos productos. No existe una única lista de atributos que demuestre la calidad de todo lo que se vende como gourmet.</p><p>Antes de comparar dos opciones, identifica también si el nombre que aparece en la ficha corresponde al productor, al elaborador o al comercio. La procedencia de una materia prima y el lugar de elaboración no son necesariamente lo mismo.</p>',
  'en' => '<!-- mdo-evergreen-gourmet-20261015 --><h2>Compare food categories, not just marketing labels</h2><p>Useful information depends on what you are buying. For <a href="'.esc_url(home_url('/en/product-category/oils/')).'">olive oil</a>, the variety, harvest and mill can matter; for cured meats, ingredients and precise denominations; for gift assortments, the composition and the logistics of each item. There is no universal premium label that replaces those details.</p><p>Before comparing two offers, establish whether the named company is the grower, maker, packer or retailer. The origin of ingredients and the place where a food was made are not necessarily the same.</p>',
 ),
 'verduras-temporada-espana-calendario-meses-que-comprar' => array(
  'es' => '<!-- mdo-evergreen-verduras-20261015 --><h2>Del calendario orientativo al pedido real</h2><p>Un calendario ayuda a preparar la lista de compra, pero la disponibilidad de una tienda depende de la cosecha, del proveedor y de la zona a la que se envía. Por eso, al <a href="'.$url['verduras'].'">comprar hortalizas online</a>, lo más útil es contrastar el mes con el origen declarado en cada ficha y consultar las condiciones de envío del vendedor. Que una verdura figure en esta tabla no significa que esté en stock hoy ni que proceda de tu provincia.</p>',
  'en' => '<!-- mdo-evergreen-verduras-20261015 --><h2>From a seasonal calendar to an actual order</h2><p>A calendar helps you plan meals, but a shop relies on the current harvest, the seller and the delivery destination. When <a href="'.esc_url(home_url('/en/product-category/vegetables/')).'">shopping for vegetables online</a>, compare the month with the origin stated in each listing and check the seller's shipping conditions. A crop appearing in a seasonal chart does not mean it is in stock today or was grown near you.</p>',
 ),
 'que-significa-aceite-oliva-virgen-extra' => array(
  'es' => '<!-- mdo-evergreen-aove-20261015 --><h2>Qué cambia al comprar a una almazara</h2><p>Comprar un AOVE con la almazara identificada permite buscar información más precisa sobre la procedencia de las aceitunas, la variedad, la campaña y el proceso de elaboración. Eso no significa que todo aceite vendido por una almazara sea automáticamente superior, ni que cualquier etiqueta que diga «de nuestra tierra» certifique por sí sola un origen concreto. Compara los datos de las fichas, el tipo de envase y las instrucciones de conservación al explorar nuestra <a href="'.$url['aceites'].'">categoría de aceites de oliva</a>.</p>',
  'en' => '<!-- mdo-evergreen-aove-20261015 --><h2>What you can check when buying from an olive mill</h2><p>An identified olive mill makes it easier to look for information on where the olives were grown, their varieties, the harvest and the production process. That does not make every oil sold by a mill automatically superior, nor does a generic local-sounding claim certify a specific origin. Compare actual labels, packaging and storage advice when exploring our <a href="'.esc_url(home_url('/en/product-category/oils/')).'">olive oil selection</a>.</p>',
 ),
);

$out=array('batch'=>'20261015-commercial-evergreen-phase2','category'=>'hortalizas-verduras','article_updates'=>array(),'checks'=>array());
foreach ($articles as $slug=>$fragments) {
    $post = mdo_ev_post($slug);
    $before = (string)$post->post_content;
    if (strlen(wp_strip_all_tags($before)) < 700) throw new RuntimeException('Unexpectedly short source post: '.$slug);
    $mark = (string)substr($fragments['es'],0,strpos($fragments['es'],' -->')+4);
    $after = mdo_ev_insert_before_end($before,$fragments['es'],$mark);
    $changed = $after !== $before;
    if ($changed) {
        if (get_post_meta($post->ID,'_mdo_ev_original_es_20261015',true)==='') update_post_meta($post->ID,'_mdo_ev_original_es_20261015',$before);
        $saved = wp_update_post(wp_slash(array('ID'=>$post->ID,'post_content'=>$after)),true);
        if (is_wp_error($saved)) throw new RuntimeException('Cannot update '.$slug.': '.$saved->get_error_message());
    }
    $en = (string)get_post_meta($post->ID,'_en_US_post_content',true);
    $en_changed = false;
    if ((string)get_post_meta($post->ID,'_en_US_published',true)==='1' && strlen(wp_strip_all_tags($en)) > 700) {
        $en_after = mdo_ev_insert_before_end($en,$fragments['en'],$mark);
        if ($en !== $en_after) {
            if (get_post_meta($post->ID,'_mdo_ev_original_en_20261015',true)==='') update_post_meta($post->ID,'_mdo_ev_original_en_20261015',$en);
            update_post_meta($post->ID,'_en_US_post_content',$en_after);
            $en_changed = true;
        }
    }
    clean_post_cache((int)$post->ID);
    $out['article_updates'][]=array('id'=>(int)$post->ID,'slug'=>$slug,'es_changed'=>$changed,'en_changed'=>$en_changed,'post_url'=>get_permalink($post->ID));
}
$out['checks']['veg_has_commercial_copy']=str_contains((string)get_term($vegetables->term_id,'product_cat')->description,'comprar verduras y hortalizas online');
$out['checks']['article_count']=count($out['article_updates']);
$out['checks']['article_marker_count']=count(array_filter($out['article_updates'],static function($a){return str_contains((string)get_post_field('post_content',$a['id']),'mdo-evergreen-');}));
if (!$out['checks']['veg_has_commercial_copy'] || $out['checks']['article_marker_count']!==4) throw new RuntimeException('Incomplete content update');
echo 'MDO_EVERGREEN_RESULT '.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
