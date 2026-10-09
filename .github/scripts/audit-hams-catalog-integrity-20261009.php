<?php
/**
 * Read-only audit of the live ham category and its declared vs indexed attributes.
 * Run with: wp eval-file SCRIPT --path=/path/to/wp --allow-root
 */
if (!defined('ABSPATH')) { exit(1); }
$category = get_term_by('slug', 'jamones-paletas', 'product_cat');
if (!$category || is_wp_error($category)) {
    echo "EMDO_CATALOG_AUDIT_ERROR category_not_found\n";
    exit(2);
}
$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'suppress_filters' => true,
    'tax_query' => [[
        'taxonomy' => 'product_cat',
        'field' => 'term_id',
        'terms' => [(int) $category->term_id],
        'include_children' => true,
    ]],
]);
$taxonomies = ['pa_productor','pa_tipo-pieza','pa_calidad','pa_origen','pa_dop','pa_con-dop','pa_curacion','pa_preparacion'];
$items = [];
foreach ($ids as $product_id) {
    $product = wc_get_product((int) $product_id);
    if (!$product) { continue; }
    $attributes = [];
    $declared = [];
    foreach ($taxonomies as $tax) {
        $terms = wp_get_object_terms($product_id, $tax, ['fields' => 'slugs']);
        $attributes[$tax] = is_wp_error($terms) ? [] : array_values((array) $terms);
        $attr = $product->get_attributes()[$tax] ?? null;
        $declared[$tax] = $attr instanceof WC_Product_Attribute ? $attr->get_options() : [];
    }
    $items[] = [
        'id' => (int) $product_id,
        'title' => get_the_title($product_id),
        'slug' => get_post_field('post_name', $product_id),
        'vendor_id' => (int) get_post_field('post_author', $product_id),
        'stock_status' => $product->get_stock_status(),
        'catalog_visibility' => $product->get_catalog_visibility(),
        'is_visible_cli' => $product->is_visible(),
        'attributes' => $attributes,
        'declared_attribute_options' => $declared,
    ];
}
$terms = [];
foreach (['pa_origen','pa_dop','pa_con-dop'] as $tax) {
    $v = get_terms(['taxonomy' => $tax, 'hide_empty' => false]);
    $terms[$tax] = is_wp_error($v) ? [] : array_map(static function ($t) {
        return ['id' => (int)$t->term_id, 'slug' => $t->slug, 'name' => $t->name, 'count' => (int)$t->count];
    }, $v);
}
echo "EMDO_CATALOG_AUDIT=" . wp_json_encode([
    'category_id' => (int)$category->term_id,
    'raw_published_category_total' => count($ids),
    'products' => $items,
    'attribute_terms' => $terms,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
