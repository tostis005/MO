<?php
/**
 * One-off idempotent creation/update of four Montjam sliced products.
 * Requested 2026-10-01. Scope is intentionally limited to the four slugs below.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "ABORT: WordPress not loaded\n"); exit(2); }
if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) { fwrite(STDERR, "ABORT: WooCommerce unavailable\n"); exit(3); }

function mjsp_fail($message, $code = 20) {
    fwrite(STDERR, "MONTJAM_SLICED_ABORT: " . $message . "\n");
    exit($code);
}
function mjsp_term($taxonomy, $slug, $required = false) {
    if (!taxonomy_exists($taxonomy)) {
        if ($required) mjsp_fail("Missing taxonomy {$taxonomy}");
        return null;
    }
    $term = get_term_by('slug', $slug, $taxonomy);
    if ((!$term || is_wp_error($term)) && $required) mjsp_fail("Missing term {$taxonomy}={$slug}");
    return ($term && !is_wp_error($term)) ? $term : null;
}
function mjsp_category(array $slugs, array $names = []) {
    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term && !is_wp_error($term)) return $term;
    }
    foreach ($names as $name) {
        $term = get_term_by('name', $name, 'product_cat');
        if ($term && !is_wp_error($term)) return $term;
    }
    mjsp_fail('Required product category not found: ' . implode(',', $slugs));
}
function mjsp_attr($taxonomy, $term, $position) {
    if (!$term) return null;
    $attribute_id = wc_attribute_taxonomy_id_by_name($taxonomy);
    if (!$attribute_id) return null;
    $a = new WC_Product_Attribute();
    $a->set_id((int)$attribute_id);
    $a->set_name($taxonomy);
    $a->set_options([(int)$term->term_id]);
    $a->set_position((int)$position);
    $a->set_visible(true);
    $a->set_variation(false);
    return $a;
}
function mjsp_media($source_path, $post_id, $asset_key, $alt) {
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_montjam_source_asset',
        'meta_value' => $asset_key,
    ]);
    if ($existing) {
        $id = (int)$existing[0];
        wp_update_post(['ID'=>$id, 'post_parent'=>$post_id, 'post_title'=>$alt]);
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
        return $id;
    }
    if (!is_readable($source_path)) mjsp_fail('Image not readable: ' . $source_path);
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam(basename($source_path));
    if (!$tmp || !copy($source_path, $tmp)) mjsp_fail('Could not stage image: ' . $source_path);
    $file = ['name'=>basename($source_path), 'tmp_name'=>$tmp];
    $id = media_handle_sideload($file, $post_id, $alt);
    if (is_wp_error($id)) {
        @unlink($tmp);
        mjsp_fail('Image upload failed: ' . $id->get_error_message());
    }
    $id = (int)$id;
    update_post_meta($id, '_montjam_source_asset', $asset_key);
    update_post_meta($id, '_wp_attachment_image_alt', $alt);
    wp_update_post(['ID'=>$id, 'post_title'=>$alt]);
    return $id;
}
function mjsp_set_term($product_id, $taxonomy, $term) {
    if (!$term) return;
    $r = wp_set_object_terms($product_id, [(int)$term->term_id], $taxonomy, false);
    if (is_wp_error($r)) mjsp_fail("Could not assign {$taxonomy}: " . $r->get_error_message());
}
function mjsp_existing_english_slug_owner($slug) {
    global $wpdb;
    return (int)$wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value=%s LIMIT 1",
        $slug
    ));
}

$vendor = get_user_by('login', 'montjam');
if (!$vendor) mjsp_fail('Montjam vendor user not found', 4);
$producer = mjsp_term('pa_productor', 'montjam', true);

$terms = [
    'huelva'       => mjsp_term('pa_origen', 'huelva'),
    'bellota'      => mjsp_term('pa_alimentacion', 'bellota'),
    'no_dop'       => mjsp_term('pa_con-dop', 'no'),
    'race100'      => mjsp_term('pa_raza-iberica', '100-iberico'),
    'quality100'   => mjsp_term('pa_calidad', 'bellota-100-iberico'),
    'jamon'        => mjsp_term('pa_tipo-pieza', 'jamon'),
    'paleta'       => mjsp_term('pa_tipo-pieza', 'paleta'),
    'loncheado'    => mjsp_term('pa_preparacion', 'loncheado'),
];

$cat_ham = mjsp_category(
    ['jamones-y-paletas', 'jamones-paletas'],
    ['Jamones y Paletas', 'Jamones y paletas']
);
$cat_cured = mjsp_category(
    ['embutidos-y-curados'],
    ['Embutidos y curados']
);

$specs = [
    [
        'key' => 'jamon',
        'slug' => 'jamon-bellota-100-iberico-loncheado-montjam',
        'title' => 'Jamón de bellota 100% ibérico loncheado Montjam (brida negra)',
        'price' => '15.15',
        'category' => $cat_ham,
        'image' => '/tmp/montjam-sliced-jamon.jpg',
        'alt' => 'Jamón de bellota 100% ibérico loncheado Montjam brida negra',
        'short' => 'Jamón de bellota 100% ibérico Montjam, ya loncheado y envasado al vacío para servir con comodidad. Elaborado en El Repilado, Huelva, con el carácter intenso y la grasa infiltrada propios de la brida negra.',
        'description' => '<h2>Jamón de bellota 100% ibérico Montjam loncheado</h2><p>Este <strong>jamón de bellota 100% ibérico Montjam</strong> ofrece en un formato listo para servir el carácter de una pieza de brida negra elaborada en El Repilado, Huelva. Las lonchas permiten disfrutar de su aroma, su grasa infiltrada y su sabor persistente sin necesidad de cortar una pieza entera.</p><p>El producto se presenta <strong>loncheado y envasado al vacío</strong>, una opción práctica para aperitivos, tablas de ibéricos, celebraciones o para abrir solo cuando se vaya a consumir.</p><h3>Cómo disfrutarlo</h3><p>Para apreciar mejor la textura y el aroma, conviene sacar el sobre del frío unos minutos antes de servir y disponer las lonchas a temperatura ambiente. Combina especialmente bien con pan y aceite de oliva virgen extra.</p><h3>Ingredientes y conservación</h3><p>Según el etiquetado del envase: jamón, sal marina, azúcar, conservador E-252, antioxidante E-301 y colorante de origen E-120. Conservar refrigerado entre 0 y 5 °C y seguir siempre las indicaciones impresas en el envase.</p>',
        'focus' => 'jamón de bellota 100% ibérico loncheado Montjam',
        'seo' => 'Jamón 100% ibérico loncheado Montjam | Mercado de Origen',
        'meta' => 'Compra jamón de bellota 100% ibérico loncheado Montjam, brida negra, elaborado en Huelva y envasado al vacío listo para servir.',
        'tags' => ['Montjam','Jamón ibérico','Jamón de bellota','100% ibérico','Brida negra','Loncheado','Huelva'],
        'attrs' => ['huelva','bellota','no_dop','race100','quality100','jamon','loncheado'],
        'en_title' => 'Montjam 100% Iberian acorn-fed ham, sliced (black label)',
        'en_slug' => 'montjam-100-iberian-acorn-fed-ham-sliced',
        'en_short' => 'Montjam 100% Iberian acorn-fed ham, sliced and vacuum packed for easy serving. Made in El Repilado, Huelva, with the deep flavour and marbling associated with the black label.',
        'en_description' => '<h2>Montjam 100% Iberian acorn-fed ham, sliced</h2><p>This <strong>Montjam 100% Iberian acorn-fed ham</strong> brings the character of a black-label ham made in El Repilado, Huelva, to a convenient ready-to-serve format. The slices showcase its aroma, marbled fat and long, savoury finish without the need to carve a whole ham.</p><p>It is supplied <strong>sliced and vacuum packed</strong>, making it a practical choice for appetisers, Iberian charcuterie boards, celebrations or opening only when you are ready to enjoy it.</p><h3>How to serve</h3><p>For the best texture and aroma, take the pack out of the fridge a few minutes before serving and allow the slices to come towards room temperature. It pairs particularly well with bread and extra virgin olive oil.</p><h3>Ingredients and storage</h3><p>According to the pack label: ham, sea salt, sugar, preservative E-252, antioxidant E-301 and colouring E-120. Keep refrigerated between 0 and 5 °C and always follow the instructions printed on the pack.</p>',
    ],
    [
        'key' => 'paleta',
        'slug' => 'paleta-bellota-100-iberica-loncheada-montjam-80g',
        'title' => 'Paleta de bellota 100% ibérica loncheada Montjam (brida negra) · 80 g',
        'price' => '11.00',
        'category' => $cat_ham,
        'image' => '/tmp/montjam-sliced-paleta.jpg',
        'alt' => 'Paleta de bellota 100% ibérica loncheada Montjam 80 g brida negra',
        'short' => 'Paleta de bellota 100% ibérica Montjam loncheada, en sobre de 80 g y envasada al vacío. Un formato listo para servir con el sabor intenso y la jugosidad característicos de la paleta de bellota.',
        'description' => '<h2>Paleta de bellota 100% ibérica Montjam loncheada · 80 g</h2><p>La <strong>paleta de bellota 100% ibérica Montjam</strong> se presenta ya loncheada en un sobre de 80 g, lista para disfrutar sin necesidad de cortar una pieza entera. Elaborada en Huelva, reúne el sabor intenso, el aroma persistente y la jugosidad propios de una paleta de brida negra.</p><p>El envasado al vacío protege las lonchas hasta el momento de apertura y facilita tener a mano una ración cómoda para aperitivos, tablas de ibéricos o reuniones.</p><h3>Cómo servirla</h3><p>Atemperar el sobre unos minutos antes de servir ayuda a que la grasa se vuelva más brillante y a que la paleta exprese mejor su aroma y textura.</p><h3>Ingredientes y conservación</h3><p>Según el etiquetado: paleta, sal común, azúcar, conservador E-252, antioxidante E-301 y corrector de acidez E-331iii. Conservar entre 0 y 5 °C y respetar las indicaciones del envase.</p>',
        'focus' => 'paleta de bellota 100% ibérica loncheada Montjam',
        'seo' => 'Paleta 100% ibérica loncheada Montjam 80 g | Mercado de Origen',
        'meta' => 'Paleta de bellota 100% ibérica Montjam loncheada, brida negra, en sobre de 80 g y envasada al vacío. Elaborada en Huelva.',
        'tags' => ['Montjam','Paleta ibérica','Paleta de bellota','100% ibérico','Brida negra','Loncheado','Huelva'],
        'attrs' => ['huelva','bellota','no_dop','race100','quality100','paleta','loncheado'],
        'en_title' => 'Montjam 100% Iberian acorn-fed shoulder ham, sliced · 80 g',
        'en_slug' => 'montjam-100-iberian-acorn-fed-shoulder-ham-sliced-80g',
        'en_short' => 'Montjam 100% Iberian acorn-fed shoulder ham, sliced in an 80 g vacuum-packed portion. Ready to serve, with the intense flavour and juiciness typical of acorn-fed Iberian shoulder ham.',
        'en_description' => '<h2>Montjam 100% Iberian acorn-fed shoulder ham, sliced · 80 g</h2><p>This <strong>Montjam 100% Iberian acorn-fed shoulder ham</strong> comes pre-sliced in an 80 g pack, ready to enjoy without carving a whole shoulder. Made in Huelva, it combines the intense flavour, persistent aroma and juicy texture associated with a black-label Iberian shoulder ham.</p><p>Vacuum packing protects the slices until opening and makes this a convenient portion for appetisers, Iberian charcuterie boards and gatherings.</p><h3>How to serve</h3><p>Let the pack temper for a few minutes before serving so the fat becomes glossy and the ham can show its aroma and texture at their best.</p><h3>Ingredients and storage</h3><p>According to the label: shoulder ham, salt, sugar, preservative E-252, antioxidant E-301 and acidity regulator E-331iii. Keep between 0 and 5 °C and follow the instructions on the pack.</p>',
    ],
    [
        'key' => 'lomo',
        'slug' => 'lomo-bellota-100-iberico-loncheado-montjam-96g',
        'title' => 'Lomo de bellota 100% ibérico loncheado Montjam · 96 g',
        'price' => '8.25',
        'category' => $cat_cured,
        'image' => '/tmp/montjam-sliced-lomo.jpg',
        'alt' => 'Lomo de bellota 100% ibérico loncheado Montjam 96 g',
        'short' => 'Lomo de bellota 100% ibérico Montjam loncheado en sobre de 96 g. Un curado de Huelva, aromático y equilibrado, listo para servir y envasado al vacío.',
        'description' => '<h2>Lomo de bellota 100% ibérico Montjam loncheado · 96 g</h2><p>El <strong>lomo de bellota 100% ibérico Montjam</strong> se presenta loncheado en un sobre de 96 g, un formato cómodo para disfrutar de este curado de Huelva directamente en aperitivos y tablas de ibéricos.</p><p>Su corte muestra una textura firme y jugosa y un perfil aromático en el que se integran el propio sabor del lomo con el aliño tradicional. El envasado al vacío ayuda a preservar el producto hasta la apertura.</p><h3>Cómo disfrutarlo</h3><p>Servir las lonchas ligeramente atemperadas permite apreciar mejor su aroma y textura. Puede acompañarse de pan, aceite de oliva virgen extra, quesos curados o combinarse con otros ibéricos Montjam.</p><h3>Ingredientes y conservación</h3><p>El etiquetado del envase recoge lomo de cerdo ibérico de bellota, sal, pimentón, ajo, especias, azúcares y aditivos autorizados. Conservar entre 0 y 5 °C y seguir las indicaciones del envase.</p>',
        'focus' => 'lomo de bellota 100% ibérico loncheado Montjam',
        'seo' => 'Lomo 100% ibérico loncheado Montjam 96 g | Mercado de Origen',
        'meta' => 'Lomo de bellota 100% ibérico Montjam loncheado, en sobre de 96 g y envasado al vacío. Curado de Huelva listo para servir.',
        'tags' => ['Montjam','Lomo ibérico','Lomo de bellota','100% ibérico','Loncheado','Embutidos ibéricos','Huelva'],
        'attrs' => ['huelva','bellota','no_dop','race100','loncheado'],
        'en_title' => 'Montjam 100% Iberian acorn-fed cured loin, sliced · 96 g',
        'en_slug' => 'montjam-100-iberian-acorn-fed-cured-loin-sliced-96g',
        'en_short' => 'Montjam 100% Iberian acorn-fed cured loin, sliced in a 96 g pack. An aromatic, balanced cured meat from Huelva, vacuum packed and ready to serve.',
        'en_description' => '<h2>Montjam 100% Iberian acorn-fed cured loin, sliced · 96 g</h2><p>This <strong>Montjam 100% Iberian acorn-fed cured loin</strong> comes sliced in a convenient 96 g pack, ready to enjoy in appetisers and Iberian charcuterie boards.</p><p>The slices show a firm yet succulent texture and an aromatic profile in which the flavour of the loin blends with its traditional seasoning. Vacuum packing helps protect the product until opening.</p><h3>How to enjoy it</h3><p>Serve the slices slightly tempered to appreciate their aroma and texture. Pair with bread, extra virgin olive oil, mature cheese or other Montjam Iberian cured meats.</p><h3>Ingredients and storage</h3><p>The pack label lists acorn-fed Iberian pork loin, salt, paprika, garlic, spices, sugars and authorised additives. Keep between 0 and 5 °C and follow the instructions on the pack.</p>',
    ],
    [
        'key' => 'chorizo',
        'slug' => 'chorizo-cular-iberico-extra-loncheado-montjam',
        'title' => 'Chorizo cular ibérico extra loncheado Montjam',
        'price' => '3.45',
        'category' => $cat_cured,
        'image' => '/tmp/montjam-sliced-chorizo.jpg',
        'alt' => 'Chorizo cular ibérico extra loncheado Montjam',
        'short' => 'Chorizo cular ibérico extra Montjam ya loncheado y envasado al vacío. Elaborado en Huelva, con sabor intenso, pimentón y especias, listo para aperitivos y tablas.',
        'description' => '<h2>Chorizo cular ibérico extra Montjam loncheado</h2><p>El <strong>chorizo cular ibérico extra Montjam</strong> se ofrece ya loncheado y envasado al vacío, listo para servir. Su elaboración combina carne de cerdo ibérico con pimentón, ajo y orégano para conseguir un perfil aromático, sabroso y de carácter tradicional.</p><p>El formato loncheado resulta especialmente cómodo para aperitivos, bocadillos y tablas de embutidos, y permite abrir el producto justo cuando se va a consumir.</p><h3>Cómo servirlo</h3><p>Atemperar unos minutos antes de servir ayuda a que el chorizo exprese mejor sus aromas y su textura. Puede combinarse con lomo, salchichón, jamón y paleta para una tabla variada.</p><h3>Ingredientes, alérgenos y conservación</h3><p>Según el etiquetado: carne de cerdo ibérico, pimentón, sal, lactosa, dextrosa, proteínas de leche, ajo, orégano y aditivos autorizados, originalmente embutido en tripa natural. <strong>Contiene leche y lactosa.</strong> Conservar entre 0 y 5 °C y seguir las indicaciones del envase.</p>',
        'focus' => 'chorizo cular ibérico extra loncheado Montjam',
        'seo' => 'Chorizo cular ibérico extra Montjam loncheado | Mercado de Origen',
        'meta' => 'Chorizo cular ibérico extra Montjam loncheado y envasado al vacío. Elaborado en Huelva con pimentón y especias. Contiene leche y lactosa.',
        'tags' => ['Montjam','Chorizo ibérico','Chorizo cular','Loncheado','Embutidos ibéricos','Huelva'],
        'attrs' => ['huelva','no_dop','loncheado'],
        'en_title' => 'Montjam extra Iberian cular chorizo, sliced',
        'en_slug' => 'montjam-extra-iberian-cular-chorizo-sliced',
        'en_short' => 'Montjam extra Iberian cular chorizo, sliced and vacuum packed. Made in Huelva with paprika and spices, ready for appetisers and charcuterie boards.',
        'en_description' => '<h2>Montjam extra Iberian cular chorizo, sliced</h2><p>This <strong>Montjam extra Iberian cular chorizo</strong> comes pre-sliced and vacuum packed, ready to serve. Iberian pork is seasoned with paprika, garlic and oregano to create an aromatic, savoury cured sausage with a traditional character.</p><p>The sliced format is particularly convenient for appetisers, sandwiches and charcuterie boards, allowing you to open the product only when you are ready to eat it.</p><h3>How to serve</h3><p>Let it temper for a few minutes before serving so the chorizo can show its aroma and texture more fully. Pair it with cured loin, salchichón, ham and shoulder ham for a varied Iberian board.</p><h3>Ingredients, allergens and storage</h3><p>According to the label: Iberian pork, paprika, salt, lactose, dextrose, milk proteins, garlic, oregano and authorised additives, originally filled in natural casing. <strong>Contains milk and lactose.</strong> Keep between 0 and 5 °C and follow the instructions on the pack.</p>',
    ],
];

$attr_taxonomy_map = [
    'huelva'     => 'pa_origen',
    'bellota'    => 'pa_alimentacion',
    'no_dop'     => 'pa_con-dop',
    'race100'    => 'pa_raza-iberica',
    'quality100' => 'pa_calidad',
    'jamon'      => 'pa_tipo-pieza',
    'paleta'     => 'pa_tipo-pieza',
    'loncheado'  => 'pa_preparacion',
];

$results = [];
foreach ($specs as $spec) {
    $post = get_page_by_path($spec['slug'], OBJECT, 'product');
    $product_id = $post ? (int)$post->ID : 0;

    if ($product_id) {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('simple')) mjsp_fail('Existing slug is not a simple product: ' . $spec['slug']);
        $existing_producer = wp_get_object_terms($product_id, 'pa_productor', ['fields'=>'slugs']);
        if (is_wp_error($existing_producer) || !in_array('montjam', $existing_producer, true)) {
            mjsp_fail('Existing product slug is not assigned to Montjam: ' . $spec['slug']);
        }
    } else {
        $product = new WC_Product_Simple();
    }

    $english_owner = mjsp_existing_english_slug_owner($spec['en_slug']);
    if ($english_owner && $english_owner !== $product_id) {
        mjsp_fail('English slug collision: ' . $spec['en_slug'] . ' owned by ' . $english_owner);
    }

    $product->set_name($spec['title']);
    $product->set_slug($spec['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($spec['description']);
    $product->set_short_description($spec['short']);
    $product->set_regular_price($spec['price']);
    $product->set_sale_price('');
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_tax_status('taxable');
    $product->set_category_ids([(int)$spec['category']->term_id]);

    $attrs = [];
    $position = 0;
    $a = mjsp_attr('pa_productor', $producer, $position++);
    if ($a) $attrs[] = $a;
    foreach ($spec['attrs'] as $key) {
        if (empty($terms[$key]) || empty($attr_taxonomy_map[$key])) continue;
        $a = mjsp_attr($attr_taxonomy_map[$key], $terms[$key], $position++);
        if ($a) $attrs[] = $a;
    }
    $product->set_attributes($attrs);

    $saved = (int)$product->save();
    if (!$saved) mjsp_fail('Product save failed: ' . $spec['slug']);
    $product_id = $saved;
    wp_update_post(['ID'=>$product_id, 'post_author'=>(int)$vendor->ID]);

    mjsp_set_term($product_id, 'pa_productor', $producer);
    foreach ($spec['attrs'] as $key) {
        if (empty($terms[$key]) || empty($attr_taxonomy_map[$key])) continue;
        mjsp_set_term($product_id, $attr_taxonomy_map[$key], $terms[$key]);
    }
    $tag_result = wp_set_object_terms($product_id, $spec['tags'], 'product_tag', false);
    if (is_wp_error($tag_result)) mjsp_fail('Tag assignment failed: ' . $tag_result->get_error_message());

    update_post_meta($product_id, '_yoast_wpseo_focuskw', $spec['focus']);
    update_post_meta($product_id, '_yoast_wpseo_title', $spec['seo']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $spec['meta']);

    update_post_meta($product_id, '_en_US_post_title', $spec['en_title']);
    update_post_meta($product_id, '_en_US_post_name', $spec['en_slug']);
    update_post_meta($product_id, '_en_US_post_excerpt', $spec['en_short']);
    update_post_meta($product_id, '_en_US_post_content', $spec['en_description']);
    update_post_meta($product_id, '_en_US_ready', '1');
    update_post_meta($product_id, '_en_US_published', '1');

    update_post_meta($product_id, '_montjam_sliced_product_version', '2026-10-01-v1');

    $attachment_id = mjsp_media(
        $spec['image'],
        $product_id,
        '2026-10-01-sliced-' . $spec['key'],
        $spec['alt']
    );
    set_post_thumbnail($product_id, $attachment_id);
    delete_post_meta($product_id, '_product_image_gallery');

    wc_delete_product_transients($product_id);
    clean_post_cache($product_id);

    $fresh = wc_get_product($product_id);
    $producer_slugs = wp_get_object_terms($product_id, 'pa_productor', ['fields'=>'slugs']);
    $category_slugs = wp_get_object_terms($product_id, 'product_cat', ['fields'=>'slugs']);
    $en_ok =
        (string)get_post_meta($product_id, '_en_US_post_title', true) === $spec['en_title'] &&
        (string)get_post_meta($product_id, '_en_US_post_name', true) === $spec['en_slug'] &&
        (string)get_post_meta($product_id, '_en_US_post_excerpt', true) === $spec['en_short'] &&
        (string)get_post_meta($product_id, '_en_US_post_content', true) === $spec['en_description'] &&
        (string)get_post_meta($product_id, '_en_US_ready', true) === '1' &&
        (string)get_post_meta($product_id, '_en_US_published', true) === '1';

    $row = [
        'id' => $product_id,
        'title' => $fresh ? $fresh->get_name() : '',
        'price' => $fresh ? $fresh->get_regular_price('edit') : '',
        'slug' => get_post_field('post_name', $product_id),
        'producer' => is_wp_error($producer_slugs) ? [] : array_values($producer_slugs),
        'categories' => is_wp_error($category_slugs) ? [] : array_values($category_slugs),
        'thumbnail_id' => (int)get_post_thumbnail_id($product_id),
        'english_ready' => $en_ok,
        'url_es' => get_permalink($product_id),
        'url_en' => home_url('/en/product/' . $spec['en_slug'] . '/'),
    ];

    if (
        !$fresh ||
        'publish' !== $fresh->get_status() ||
        abs((float)$row['price'] - (float)$spec['price']) > 0.0005 ||
        !in_array('montjam', $row['producer'], true) ||
        !$row['thumbnail_id'] ||
        !$en_ok
    ) {
        mjsp_fail('Verification failed: ' . wp_json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    $results[] = $row;
}

wp_cache_flush();
if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product', true);
if (function_exists('rocket_clean_domain')) rocket_clean_domain();
if (function_exists('w3tc_flush_all')) w3tc_flush_all();
do_action('litespeed_purge_all');

echo 'MONTJAM_SLICED_PRODUCTS_OK ' . wp_json_encode([
    'vendor_id' => (int)$vendor->ID,
    'count' => count($results),
    'products' => $results,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
