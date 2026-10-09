<?php
/**
 * Precisely scoped, idempotent repair from the read-only 2026-10-09 audit.
 * Only Montjam's nine PUBLIC ham/shoulder listings; intentionally never 17129
 * (the Mentta-only duplicate). No prices, inventory, categories, variations,
 * or product visibility are modified.
 */
if (!defined('ABSPATH') || !function_exists('wc_get_product')) { exit(1); }

$public_ids = [14264, 14271, 14275, 14287, 14294, 14301, 14305, 16908, 16910];
$dop_ids = [14287, 14301];
$vendor = get_user_by('login', 'montjam');
$category = get_term_by('slug', 'jamones-paletas', 'product_cat');
$huelva = get_term_by('slug', 'huelva', 'pa_origen');
$jabugo = get_term_by('slug', 'jabugo', 'pa_dop');
$yes = get_term_by('slug', 'si', 'pa_con-dop');
$no = get_term_by('slug', 'no', 'pa_con-dop');
if (!$vendor || !$category || !$huelva || !$jabugo || !$yes || !$no) {
    throw new RuntimeException('Expected vendor/category/attribute terms missing');
}
$preflight = [];
foreach ($public_ids as $id) {
    $product = wc_get_product($id);
    $post = get_post($id);
    if (!$product || !$post || $post->post_status !== 'publish' ||
        (int)$post->post_author !== (int)$vendor->ID ||
        stripos($post->post_title, 'montjam') === false ||
        !has_term((int)$category->term_id, 'product_cat', $id) ||
        !$product->get_catalog_visibility() || $product->get_catalog_visibility() !== 'visible') {
        throw new RuntimeException('Product failed guarded preflight: '.$id);
    }
    $origin = wp_get_object_terms($id, 'pa_origen', ['fields'=>'slugs']);
    if (is_wp_error($origin) || array_diff($origin, ['huelva'])) {
        throw new RuntimeException('Unexpected preexisting origin; refusing: '.$id);
    }
    if (in_array($id, $dop_ids, true)) {
        if (stripos($post->post_title, 'D.O.P. Jabugo') === false) {
            throw new RuntimeException('Unexpected DOP title on '.$id);
        }
        $old = wp_get_object_terms($id, 'pa_dop', ['fields'=>'slugs']);
        if (is_wp_error($old) || array_diff($old, ['jabugo'])) {
            throw new RuntimeException('Unexpected current DOP on '.$id);
        }
    }
    $preflight[$id] = $post->post_title;
}

/** Add/replace one global product attribute without disturbing the others. */
function mdo_catalog_repair_attribute_20261009($product, $taxonomy, $term_id) {
    $attributes = $product->get_attributes();
    $attribute = $attributes[$taxonomy] ?? null;
    if (!$attribute instanceof WC_Product_Attribute) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id((int) wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_position(count($attributes));
        $attribute->set_visible(true);
        $attribute->set_variation(false);
    }
    $attribute->set_options([(int)$term_id]);
    $attributes[$taxonomy] = $attribute;
    $product->set_attributes($attributes);
}

foreach ($public_ids as $id) {
    $product = wc_get_product($id);
    $r = wp_set_object_terms($id, [(int)$huelva->term_id], 'pa_origen', false);
    if (is_wp_error($r)) { throw new RuntimeException('Could not set Huelva on '.$id); }
    mdo_catalog_repair_attribute_20261009($product, 'pa_origen', $huelva->term_id);
    if (in_array($id, $dop_ids, true)) {
        $r = wp_set_object_terms($id, [(int)$jabugo->term_id], 'pa_dop', false);
        if (is_wp_error($r)) { throw new RuntimeException('Could not set Jabugo on '.$id); }
        $r = wp_set_object_terms($id, [(int)$yes->term_id], 'pa_con-dop', false);
        if (is_wp_error($r)) { throw new RuntimeException('Could not set DOP Yes on '.$id); }
        mdo_catalog_repair_attribute_20261009($product, 'pa_dop', $jabugo->term_id);
        mdo_catalog_repair_attribute_20261009($product, 'pa_con-dop', $yes->term_id);
    }
    $product->save();
    wc_delete_product_transients($id);
    clean_post_cache($id);
}

$after = [];
foreach ($public_ids as $id) {
    $product = wc_get_product($id);
    $origin = wp_get_object_terms($id, 'pa_origen', ['fields'=>'slugs']);
    $dop = wp_get_object_terms($id, 'pa_dop', ['fields'=>'slugs']);
    $yes_terms = wp_get_object_terms($id, 'pa_con-dop', ['fields'=>'slugs']);
    $attrs = $product->get_attributes();
    if ($origin !== ['huelva'] || !isset($attrs['pa_origen'])) {
        throw new RuntimeException('Huelva verification failed: '.$id);
    }
    if (in_array($id, $dop_ids, true) &&
        ($dop !== ['jabugo'] || $yes_terms !== ['si'] ||
        !isset($attrs['pa_dop']) || !isset($attrs['pa_con-dop']))) {
        throw new RuntimeException('Jabugo/Yes verification failed: '.$id);
    }
    $after[] = ['id'=>$id,'origin'=>$origin,'dop'=>$dop,'con_dop'=>$yes_terms];
}
echo 'MONTJAM_CATEGORY_ATTRIBUTES_REPAIRED=' .
    wp_json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
