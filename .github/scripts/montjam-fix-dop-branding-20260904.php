<?php
if (!defined('ABSPATH')) { exit(1); }

global $wpdb;

function mfd_out($label, $value = null) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

function mfd_replace_onofre($value) {
    if (is_string($value)) return str_ireplace('Onofre', 'Montjam', $value);
    if (is_array($value)) {
        foreach ($value as $k => $v) $value[$k] = mfd_replace_onofre($v);
        return $value;
    }
    if (is_object($value)) {
        foreach (get_object_vars($value) as $k => $v) $value->$k = mfd_replace_onofre($v);
        return $value;
    }
    return $value;
}

$specs = [
    14287 => [
        'title' => 'Jamón de bellota 100% ibérico D.O.P. Jabugo Montjam (brida negra)',
        'slug' => 'jamon-bellota-100-iberico-dop-jabugo-montjam',
        'short' => 'Jamón de bellota 100% ibérico Montjam amparado por la D.O.P. Jabugo, elaborado en la Sierra de Huelva y distinguido con brida negra. Una pieza de perfil intenso, textura jugosa y curación lenta en condiciones naturales.',
        'description' => '<h2>Jamón de bellota 100% ibérico D.O.P. Jabugo Montjam</h2><p>Este jamón de bellota 100% ibérico de Montjam está amparado por la <strong>Denominación de Origen Protegida Jabugo</strong> y se identifica con brida negra. Se elabora en la Sierra de Huelva, un entorno estrechamente ligado a la tradición del ibérico y a los procesos de curación lenta.</p><p>Procede de cerdos 100% ibéricos alimentados con bellota durante la montanera. La combinación de raza, alimentación y una maduración pausada favorece una carne de color rojo intenso, grasa infiltrada y una textura suave y jugosa.</p><h3>Características</h3><p>Su perfil aromático es profundo y persistente, con notas propias de la bellota y una grasa brillante que se funde con facilidad. Es una referencia pensada para quienes buscan un jamón ibérico con certificación D.O.P. Jabugo y el carácter de los productos elaborados por Montjam.</p><h3>Conservación y consumo</h3><p>Se recomienda conservar la pieza en un lugar fresco, seco y protegido de fuentes directas de calor. Para apreciar mejor sus aromas y textura, conviene consumir el jamón a temperatura ambiente.</p>',
        'seo_title' => 'Jamón de bellota 100% ibérico D.O.P. Jabugo Montjam',
        'meta' => 'Jamón de bellota 100% ibérico D.O.P. Jabugo Montjam, brida negra y elaboración tradicional en la Sierra de Huelva.',
        'focus' => 'jamón de bellota 100% ibérico DOP Jabugo Montjam',
    ],
    14301 => [
        'title' => 'Paleta de bellota 100% ibérica D.O.P. Jabugo Montjam (brida negra)',
        'slug' => 'paleta-bellota-100-iberica-dop-jabugo-montjam',
        'short' => 'Paleta de bellota 100% ibérica Montjam amparada por la D.O.P. Jabugo y distinguida con brida negra. Una pieza intensa y aromática, elaborada en la Sierra de Huelva mediante una curación lenta y tradicional.',
        'description' => '<h2>Paleta de bellota 100% ibérica D.O.P. Jabugo Montjam</h2><p>La paleta de bellota 100% ibérica de Montjam está amparada por la <strong>Denominación de Origen Protegida Jabugo</strong> y se identifica con brida negra. Su elaboración en la Sierra de Huelva sigue un proceso de salado y curación pausada que permite desarrollar el carácter propio de este tipo de ibérico.</p><p>Procede de cerdos 100% ibéricos alimentados con bellota durante la montanera. La paleta ofrece un sabor intenso y persistente, con grasa infiltrada, textura jugosa y una marcada concentración aromática.</p><h3>Características</h3><p>Por su morfología, la paleta presenta una proporción diferente de hueso y grasa respecto al jamón, además de un perfil de sabor especialmente expresivo. Esta referencia combina la certificación D.O.P. Jabugo con la elaboración de Montjam.</p><h3>Conservación y consumo</h3><p>Se recomienda conservar la pieza en un lugar fresco, seco y ventilado, alejado de fuentes de calor. Para disfrutar mejor de su aroma y textura, conviene consumirla a temperatura ambiente.</p>',
        'seo_title' => 'Paleta de bellota 100% ibérica D.O.P. Jabugo Montjam',
        'meta' => 'Paleta de bellota 100% ibérica D.O.P. Jabugo Montjam, brida negra y elaboración tradicional en la Sierra de Huelva.',
        'focus' => 'paleta de bellota 100% ibérica DOP Jabugo Montjam',
    ],
];

$results = [];
foreach ($specs as $product_id => $spec) {
    $post = get_post($product_id);
    if (!$post || $post->post_type !== 'product') throw new RuntimeException('Product not found: ' . $product_id);

    $attachment_ids = [];
    $thumb = (int)get_post_thumbnail_id($product_id);
    if ($thumb) $attachment_ids[] = $thumb;
    $gallery = array_filter(array_map('intval', explode(',', (string)get_post_meta($product_id, '_product_image_gallery', true))));
    $attachment_ids = array_values(array_unique(array_merge($attachment_ids, $gallery)));
    delete_post_thumbnail($product_id);
    delete_post_meta($product_id, '_product_image_gallery');
    foreach ($attachment_ids as $aid) {
        $att = get_post($aid);
        if ($att && $att->post_type === 'attachment' && (int)$att->post_parent === (int)$product_id) {
            wp_delete_attachment($aid, true);
        }
    }

    wp_update_post([
        'ID' => $product_id,
        'post_title' => $spec['title'],
        'post_name' => $spec['slug'],
        'post_excerpt' => $spec['short'],
        'post_content' => $spec['description'],
    ]);

    update_post_meta($product_id, '_yoast_wpseo_title', $spec['seo_title']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $spec['meta']);
    update_post_meta($product_id, '_yoast_wpseo_focuskw', $spec['focus']);

    $meta_rows = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $product_id), ARRAY_A);
    foreach ($meta_rows as $m) {
        $decoded = maybe_unserialize($m['meta_value']);
        $new = mfd_replace_onofre($decoded);
        if ($new !== $decoded) update_metadata_by_mid('post', (int)$m['meta_id'], $new);
    }

    $product = wc_get_product($product_id);
    $children = $product ? array_map('intval', $product->get_children()) : [];
    foreach ($children as $child_id) {
        $child_post = get_post($child_id);
        if ($child_post) {
            $new_title = str_ireplace('Onofre', 'Montjam', $child_post->post_title);
            $new_name = str_ireplace('onofre', 'montjam', $child_post->post_name);
            if ($new_title !== $child_post->post_title || $new_name !== $child_post->post_name) {
                wp_update_post(['ID'=>$child_id, 'post_title'=>$new_title, 'post_name'=>$new_name]);
            }
        }
        $child_meta = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $child_id), ARRAY_A);
        foreach ($child_meta as $m) {
            $decoded = maybe_unserialize($m['meta_value']);
            $new = mfd_replace_onofre($decoded);
            if ($new !== $decoded) update_metadata_by_mid('post', (int)$m['meta_id'], $new);
        }
    }

    foreach (get_object_taxonomies('product') as $taxonomy) {
        $assigned = wp_get_object_terms($product_id, $taxonomy, ['fields'=>'all']);
        if (is_wp_error($assigned)) continue;
        foreach ($assigned as $term) {
            if (stripos($term->name, 'Onofre') !== false || stripos($term->slug, 'onofre') !== false) {
                wp_remove_object_terms($product_id, (int)$term->term_id, $taxonomy);
            }
        }
    }

    if (taxonomy_exists('pa_productor')) {
        $montjam_term = get_term_by('slug', 'montjam', 'pa_productor');
        if (!$montjam_term) $montjam_term = get_term_by('name', 'Montjam', 'pa_productor');
        if ($montjam_term) wp_set_object_terms($product_id, [(int)$montjam_term->term_id], 'pa_productor', false);
    }

    WC_Product_Variable::sync($product_id);
    wc_delete_product_transients($product_id);
    clean_post_cache($product_id);

    $fresh = get_post($product_id);
    $check_blob = implode(' ', [
        $fresh->post_title,
        $fresh->post_name,
        $fresh->post_excerpt,
        $fresh->post_content,
        (string)get_post_meta($product_id, '_yoast_wpseo_title', true),
        (string)get_post_meta($product_id, '_yoast_wpseo_metadesc', true),
        (string)get_post_meta($product_id, '_yoast_wpseo_focuskw', true),
    ]);
    if (stripos($check_blob, 'Onofre') !== false) throw new RuntimeException('Onofre remains in visible copy for product ' . $product_id);
    if (get_post_thumbnail_id($product_id)) throw new RuntimeException('Featured image still set on ' . $product_id);
    if ((string)get_post_meta($product_id, '_product_image_gallery', true) !== '') throw new RuntimeException('Gallery still set on ' . $product_id);

    $results[] = [
        'id' => $product_id,
        'title' => $fresh->post_title,
        'slug' => $fresh->post_name,
        'url' => get_permalink($product_id),
        'featured' => (int)get_post_thumbnail_id($product_id),
        'gallery' => (string)get_post_meta($product_id, '_product_image_gallery', true),
        'deleted_wrong_brand_attachments' => $attachment_ids,
        'variations' => $children,
    ];
}

wp_cache_flush();
mfd_out('MONTJAM_DOP_BRANDING_FIXED', [
    'products' => $results,
    'note' => 'Onofre references and wrong-brand images removed only from the two D.O.P. products.',
]);
