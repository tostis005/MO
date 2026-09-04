<?php
/**
 * Final production adjustments for Montjam products:
 * - enforce exact brand spelling "Montjam"
 * - keep product copy free of changeable weight/preparation options
 * - associate YITH format blocks with parent products AND every variation
 */
if (!defined('ABSPATH')) { exit(1); }
if (!class_exists('WooCommerce')) { throw new RuntimeException('WooCommerce unavailable'); }

global $wpdb;

function mja_out($label, $value = null) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

function mja_brand_text($value) {
    return is_string($value) ? str_replace(['Mont Jam', 'MontJam'], 'Montjam', $value) : $value;
}

// Vendor / WCFM store exact spelling.
$user = get_user_by('login', 'montjam');
if (!$user) {
    throw new RuntimeException('Montjam vendor not found');
}
$r = wp_update_user([
    'ID'            => $user->ID,
    'display_name'  => 'Montjam',
    'nickname'      => 'Montjam',
    'user_nicename' => 'montjam',
]);
if (is_wp_error($r)) throw new RuntimeException($r->get_error_message());
update_user_meta($user->ID, 'store_name', 'Montjam');
$profile = get_user_meta($user->ID, 'wcfmmp_profile_settings', true);
if (!is_array($profile)) $profile = [];
$profile['store_name'] = 'Montjam';
$profile['store_slug'] = 'montjam';
update_user_meta($user->ID, 'wcfmmp_profile_settings', $profile);

// Normalize producer attribute term without changing relationships.
$producer = get_term_by('slug', 'montjam', 'pa_productor');
if (!$producer) $producer = get_term_by('slug', 'mont-jam', 'pa_productor');
if (!$producer) $producer = get_term_by('name', 'Mont Jam', 'pa_productor');
if ($producer && !is_wp_error($producer)) {
    $u = wp_update_term((int)$producer->term_id, 'pa_productor', ['name' => 'Montjam', 'slug' => 'montjam']);
    if (is_wp_error($u)) throw new RuntimeException($u->get_error_message());
}

// Normalize the Montjam product tag if it exists.
$tag = get_term_by('slug', 'montjam', 'product_tag');
if (!$tag) $tag = get_term_by('slug', 'mont-jam', 'product_tag');
if (!$tag) $tag = get_term_by('name', 'Mont Jam', 'product_tag');
if ($tag && !is_wp_error($tag)) {
    $u = wp_update_term((int)$tag->term_id, 'product_tag', ['name' => 'Montjam', 'slug' => 'montjam']);
    if (is_wp_error($u)) throw new RuntimeException($u->get_error_message());
}

$nutrition_ham = '<h3>Ingredientes e información nutricional</h3><p><strong>Ingredientes:</strong> jamón de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten.</p><p><strong>Valores nutricionales por 100 g:</strong> 383,20 kcal; proteínas 48,59 g; hidratos de carbono &lt;0,1 g; grasas 20,98 g (saturadas 4,72 g); azúcares &lt;0,1 g; sal 2,10 g.</p>';
$nutrition_paleta = '<h3>Ingredientes e información nutricional</h3><p><strong>Ingredientes:</strong> paleta de cerdo ibérico, sal, azúcar, corrector de la acidez E-331iii, conservadores E-252 y E-250 y antioxidante E-301. Sin gluten y sin lactosa.</p><p><strong>Valores nutricionales por 100 g:</strong> 383,20 kcal; proteínas 48,59 g; hidratos de carbono &lt;0,1 g; grasas 20,98 g (saturadas 4,72 g); azúcares &lt;0,1 g; sal 2,10 g.</p>';

$specs = [
    [
        'id' => 14264,
        'slug' => 'jamon-de-bellota-100-iberico-montjam',
        'title' => 'Jamón de bellota 100% ibérico Montjam (brida negra)',
        'short' => 'Jamón de bellota 100% ibérico Montjam (brida negra), elaborado en El Repilado, Huelva, con curación lenta en secaderos y bodegas naturales. Una pieza de sabor profundo, aroma persistente y grasa infiltrada.',
        'description' => '<h2>Jamón de bellota 100% ibérico Montjam, brida negra</h2><p>Una pieza pensada para quienes buscan un jamón ibérico de bellota con sabor profundo, grasa infiltrada y una textura jugosa. Montjam elabora sus ibéricos en El Repilado, en la Sierra de Aracena y Picos de Aroche (Huelva), donde la curación lenta en secaderos y bodegas naturales forma parte del carácter de la casa.</p><p>Este jamón procede de cerdo <strong>100% ibérico alimentado con bellota en montanera</strong> y se identifica con brida negra. La curación se sitúa alrededor de 36 meses, permitiendo desarrollar un aroma intenso y un sabor largo y equilibrado.</p><h3>Por qué elegir este jamón Montjam</h3><ul><li>100% raza ibérica y alimentación de bellota.</li><li>Elaborado en Huelva por una casa especializada en productos ibéricos.</li><li>Curación prolongada en condiciones naturales para desarrollar aroma y sabor.</li><li>Perfil intenso, jugoso y persistente, ideal para disfrutar en ocasiones especiales o en casa.</li></ul>' . $nutrition_ham . '<h3>Conservación</h3><p>Conservar en un lugar fresco y seco, protegido de fuentes de calor. Una vez iniciado, conviene cubrir la zona de corte con una fina loncha de grasa de la propia pieza y consumirlo de forma regular para mantener su mejor textura y aroma.</p>',
        'focus' => 'jamón de bellota 100% ibérico Montjam',
        'seo_title' => 'Jamón de bellota 100% ibérico Montjam | Mercado de Origen',
        'meta' => 'Compra jamón de bellota 100% ibérico Montjam, brida negra, elaborado en Huelva con curación lenta y sabor intenso, jugoso y persistente.',
        'tags' => ['Montjam', 'Jamón ibérico', 'Jamón de bellota', '100% ibérico', 'Brida negra', 'Huelva'],
        'alt' => 'Jamón de bellota 100% ibérico Montjam brida negra',
    ],
    [
        'id' => 14271,
        'slug' => 'jamon-de-bellota-iberico-50-montjam',
        'title' => 'Jamón de bellota ibérico 50% raza ibérica Montjam (brida roja)',
        'short' => 'Jamón de bellota ibérico 50% raza ibérica Montjam (brida roja), elaborado en El Repilado, Huelva, con curación prolongada. Perfil sabroso, aromático y equilibrado.',
        'description' => '<h2>Jamón de bellota ibérico 50% Montjam, brida roja</h2><p>El jamón de bellota ibérico 50% raza ibérica Montjam ofrece un perfil sabroso, aromático y equilibrado, con la intensidad propia de la bellota y una curación prolongada en El Repilado (Huelva).</p><p>Identificado con <strong>brida roja</strong>, procede de cerdo ibérico 50% alimentado con bellotas durante la montanera. La pieza tiene una curación mínima de 32 meses, pensada para desarrollar un sabor persistente sin perder jugosidad.</p><h3>Un jamón de bellota de Huelva para disfrutar en casa</h3><p>Su equilibrio entre infiltración, aroma y rendimiento lo convierte en una opción especialmente interesante para celebraciones, reuniones familiares o para quien busca un jamón de bellota ibérico de brida roja con carácter y buena persistencia en boca.</p>' . $nutrition_ham . '<h3>Conservación</h3><p>Guardar en un lugar fresco y seco. Una vez empezado, proteger la superficie de corte con grasa de la propia pieza y evitar cubrirla directamente con materiales que favorezcan la condensación.</p>',
        'focus' => 'jamón de bellota ibérico 50% Montjam',
        'seo_title' => 'Jamón de bellota ibérico 50% Montjam | Mercado de Origen',
        'meta' => 'Jamón de bellota ibérico 50% Montjam, brida roja, elaborado en Huelva con curación prolongada y un perfil sabroso, aromático y equilibrado.',
        'tags' => ['Montjam', 'Jamón ibérico', 'Jamón de bellota', '50% ibérico', 'Brida roja', 'Huelva'],
        'alt' => 'Jamón de bellota ibérico 50% Montjam brida roja',
    ],
    [
        'id' => 14275,
        'slug' => 'paleta-de-bellota-100-iberica-montjam',
        'title' => 'Paleta de bellota 100% ibérica Montjam (brida negra)',
        'short' => 'Paleta de bellota 100% ibérica Montjam (brida negra), elaborada en El Repilado, Huelva. Sabor intenso, aroma persistente y textura jugosa, con curación natural.',
        'description' => '<h2>Paleta de bellota 100% ibérica Montjam, brida negra</h2><p>La paleta de bellota 100% ibérica Montjam concentra el carácter de los ibéricos de Huelva: sabor intenso, aroma persistente y una textura jugosa favorecida por la infiltración de grasa.</p><p>Elaborada en El Repilado por Montjam, procede de cerdo <strong>100% ibérico alimentado con bellota</strong> y se identifica con brida negra. Su curación mínima de 24 meses ayuda a desarrollar un perfil profundo y equilibrado.</p><h3>Por qué elegir una paleta Montjam</h3><ul><li>100% raza ibérica y alimentación de bellota.</li><li>Elaboración en Huelva, en la Sierra de Aracena y Picos de Aroche.</li><li>Curación natural que potencia aroma, jugosidad y persistencia.</li><li>Una alternativa de gran intensidad para disfrutar del ibérico de bellota en casa.</li></ul>' . $nutrition_paleta . '<h3>Conservación</h3><p>Mantener en un lugar fresco y seco. Una vez comenzada, cubrir la superficie de corte con grasa de la propia paleta y consumir con regularidad para conservar su aroma y textura.</p>',
        'focus' => 'paleta de bellota 100% ibérica Montjam',
        'seo_title' => 'Paleta de bellota 100% ibérica Montjam | Mercado de Origen',
        'meta' => 'Paleta de bellota 100% ibérica Montjam, brida negra, elaborada en Huelva con curación natural, sabor intenso y textura jugosa.',
        'tags' => ['Montjam', 'Paleta ibérica', 'Paleta de bellota', '100% ibérico', 'Brida negra', 'Huelva'],
        'alt' => 'Paleta de bellota 100% ibérica Montjam brida negra',
    ],
];

$blocks_table = $wpdb->prefix . 'yith_wapo_blocks';
$assoc_table  = $wpdb->prefix . 'yith_wapo_blocks_assoc';
$addons_table = $wpdb->prefix . 'yith_wapo_addons';
foreach ([$blocks_table, $assoc_table, $addons_table] as $table) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        throw new RuntimeException('Missing YITH table: ' . $table);
    }
}

$results = [];
foreach ($specs as $spec) {
    $post = get_post((int)$spec['id']);
    if (!$post || $post->post_type !== 'product') {
        $post = get_page_by_path($spec['slug'], OBJECT, 'product');
    }
    if (!$post) throw new RuntimeException('Product missing: ' . $spec['slug']);
    $product_id = (int)$post->ID;

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) {
        throw new RuntimeException('Expected variable product: ' . $product_id);
    }

    $product->set_name($spec['title']);
    $product->set_slug($spec['slug']);
    $product->set_description($spec['description']);
    $product->set_short_description($spec['short']);
    $product->save();
    wp_update_post(['ID' => $product_id, 'post_author' => (int)$user->ID]);

    update_post_meta($product_id, '_yoast_wpseo_focuskw', $spec['focus']);
    update_post_meta($product_id, '_yoast_wpseo_title', $spec['seo_title']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $spec['meta']);
    update_post_meta($product_id, '_montjam_adjustment_version', '2026-09-04-v2');
    wp_set_object_terms($product_id, $spec['tags'], 'product_tag', false);

    // Keep image alt text brand-consistent.
    $image_ids = array_filter(array_merge(
        [(int)get_post_thumbnail_id($product_id)],
        array_map('intval', explode(',', (string)get_post_meta($product_id, '_product_image_gallery', true)))
    ));
    foreach (array_values(array_unique($image_ids)) as $i => $attachment_id) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $spec['alt'] . ($i ? ' - detalle' : ''));
    }

    // Find the dedicated Montjam format block for this product.
    $like = '%Formato · ' . $product_id;
    $block_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT id FROM `$blocks_table` WHERE name LIKE %s ORDER BY id DESC LIMIT 1",
        $like
    ));
    if (!$block_id) throw new RuntimeException('YITH format block not found for product ' . $product_id);

    // Normalize block name/settings to exact brand spelling.
    $block = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$blocks_table` WHERE id=%d", $block_id), ARRAY_A);
    $block_settings = maybe_unserialize($block['settings']);
    if (!is_array($block_settings)) $block_settings = [];
    $block_settings['name'] = 'Montjam · Formato · ' . $product_id;
    if (!isset($block_settings['rules']) || !is_array($block_settings['rules'])) $block_settings['rules'] = [];
    $block_settings['rules']['show_in'] = 'products';
    $block_settings['rules']['show_in_products'] = [(string)$product_id];
    $block_settings['rules']['show_to'] = 'all';
    $wpdb->update($blocks_table, [
        'name' => 'Montjam · Formato · ' . $product_id,
        'settings' => maybe_serialize($block_settings),
        'product_association' => 'products',
        'vendor_id' => '0',
        'visibility' => 1,
        'user_association' => 'all',
        'last_update' => current_time('mysql', true),
    ], ['id' => $block_id]);

    // CRITICAL: YITH switches its lookup from parent product ID to variation ID after
    // variation selection/AJAX. Official YITH save logic therefore creates associations
    // for the parent AND every available variation. Mirror that behaviour here.
    $children = array_values(array_map('intval', $product->get_children()));
    $association_ids = array_merge([$product_id], $children);
    $wpdb->delete($assoc_table, ['rule_id' => $block_id, 'type' => 'product']);
    foreach ($association_ids as $object_id) {
        $ok = $wpdb->insert($assoc_table, [
            'rule_id' => $block_id,
            'object' => (string)$object_id,
            'type' => 'product',
        ]);
        if ($ok === false) throw new RuntimeException('Could not add YITH association for ' . $object_id);
    }

    WC_Product_Variable::sync($product_id);
    wc_delete_product_transients($product_id);
    clean_post_cache($product_id);

    // Verify exactly the path YITH uses after a variation has been chosen.
    $variation_checks = [];
    if (!function_exists('YITH_WAPO_DB')) throw new RuntimeException('YITH_WAPO_DB unavailable');
    $parent_blocks = array_map('intval', YITH_WAPO_DB()->yith_wapo_get_blocks_by_product($product_id, null, 'yes'));
    if (!in_array($block_id, $parent_blocks, true)) {
        throw new RuntimeException('YITH block missing for parent ' . $product_id);
    }
    foreach ($children as $child_id) {
        $found = array_map('intval', YITH_WAPO_DB()->yith_wapo_get_blocks_by_product($product_id, $child_id, 'yes'));
        $ok = in_array($block_id, $found, true);
        $variation_checks[(string)$child_id] = $ok;
        if (!$ok) throw new RuntimeException('YITH block disappears for variation ' . $child_id);
    }

    // Guard against future-stale copy in the visible descriptions and SEO snippet.
    $copy_to_check = implode(' ', [$spec['short'], $spec['description'], $spec['meta']]);
    foreach (['Mont Jam', 'loncheado a cuchillo', 'deshuesad', 'formatos disponibles', 'peso y preparación'] as $forbidden) {
        if (stripos($copy_to_check, $forbidden) !== false) {
            throw new RuntimeException('Stale/dynamic wording remains: ' . $forbidden . ' in product ' . $product_id);
        }
    }

    $results[] = [
        'id' => $product_id,
        'title' => get_the_title($product_id),
        'slug' => get_post_field('post_name', $product_id),
        'block_id' => $block_id,
        'associations' => $association_ids,
        'variation_checks' => $variation_checks,
        'short_description' => get_post_field('post_excerpt', $product_id),
        'meta_description' => get_post_meta($product_id, '_yoast_wpseo_metadesc', true),
    ];
}

wp_cache_flush();
mja_out('MONTJAM_ADJUSTMENTS_SUCCESS', [
    'vendor_id' => (int)$user->ID,
    'vendor_display' => get_userdata($user->ID)->display_name,
    'products' => $results,
]);
