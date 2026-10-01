<?php
/**
 * Read-only export of all WooCommerce products assigned to MENTTA (root or children)
 * for preparing a TikTok Shop bulk listing workbook.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

function mdo_clean_text($html) {
    $text = html_entity_decode(wp_strip_all_tags((string)$html, true), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[ \t]+/", " ", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim($text);
}
function mdo_cat_path($term) {
    if (!($term instanceof WP_Term)) return '';
    $parts = array($term->name);
    $guard = 0;
    while ((int)$term->parent > 0 && $guard++ < 20) {
        $term = get_term((int)$term->parent, 'product_cat');
        if (!($term instanceof WP_Term)) break;
        array_unshift($parts, $term->name);
    }
    return implode(' > ', $parts);
}
function mdo_identifier($product) {
    if (!$product) return '';
    if (method_exists($product, 'get_global_unique_id')) {
        $v = trim((string)$product->get_global_unique_id());
        if ($v !== '') return $v;
    }
    $keys = array('_global_unique_id','_alg_ean','_ean','ean','gtin','_gtin','_wpm_gtin_code','hwp_product_gtin','_barcode','barcode');
    foreach ($keys as $k) {
        $v = trim((string)get_post_meta($product->get_id(), $k, true));
        if ($v !== '') return $v;
    }
    return '';
}
function mdo_brand($product_id) {
    foreach (array('product_brand','pwb-brand') as $tax) {
        if (taxonomy_exists($tax)) {
            $names = wp_get_object_terms($product_id, $tax, array('fields'=>'names'));
            if (!is_wp_error($names) && $names) return implode(', ', $names);
        }
    }
    $p = wc_get_product($product_id);
    if ($p) {
        foreach ($p->get_attributes() as $attr) {
            if (!is_object($attr)) continue;
            $name = wc_attribute_label($attr->get_name());
            if (in_array(mb_strtolower($name), array('marca','brand'), true)) {
                $vals = $attr->is_taxonomy()
                    ? wc_get_product_terms($product_id, $attr->get_name(), array('fields'=>'names'))
                    : $attr->get_options();
                if (!is_wp_error($vals) && $vals) return implode(', ', $vals);
            }
        }
    }
    return '';
}
function mdo_vendor($product_id) {
    $uid = (int)get_post_field('post_author', $product_id);
    if (!$uid) return '';
    foreach (array('store_name','wcfmmp_store_name','pv_shop_name','dokan_store_name') as $k) {
        $v = trim((string)get_user_meta($uid, $k, true));
        if ($v !== '') return $v;
    }
    $u = get_userdata($uid);
    return $u ? (string)$u->display_name : '';
}
function mdo_images($product, $parent=null) {
    $ids = array();
    if ($product && $product->get_image_id()) $ids[] = (int)$product->get_image_id();
    if ($parent && $parent->get_image_id()) $ids[] = (int)$parent->get_image_id();
    $gallery_source = $parent ?: $product;
    if ($gallery_source) $ids = array_merge($ids, array_map('intval', $gallery_source->get_gallery_image_ids()));
    $ids = array_values(array_unique(array_filter($ids)));
    $urls = array();
    foreach ($ids as $id) {
        $u = wp_get_attachment_url($id);
        if ($u) $urls[] = $u;
    }
    return $urls;
}
function mdo_attrs_text($attrs, $variation=false) {
    $out = array();
    if ($variation) {
        foreach ((array)$attrs as $k=>$v) {
            $label = wc_attribute_label(str_replace('attribute_','',$k));
            $out[] = $label . ': ' . $v;
        }
    } else {
        foreach ((array)$attrs as $attr) {
            if (!is_object($attr)) continue;
            $label = wc_attribute_label($attr->get_name());
            $vals = $attr->is_taxonomy()
                ? wc_get_product_terms($attr->get_id() ? 0 : 0, $attr->get_name(), array('fields'=>'names'))
                : $attr->get_options();
            // Re-resolve taxonomy terms against the product outside this helper when possible.
            if (!is_wp_error($vals) && $vals) $out[] = $label . ': ' . implode(', ', $vals);
        }
    }
    return implode(' | ', $out);
}

$mentta = get_term_by('slug', 'mentta', 'product_cat');
if (!($mentta instanceof WP_Term)) { fwrite(STDERR, "MENTTA category not found.\n"); exit(2); }
$tree_ids = array((int)$mentta->term_id);
$children = get_term_children((int)$mentta->term_id, 'product_cat');
if (!is_wp_error($children)) $tree_ids = array_values(array_unique(array_merge($tree_ids, array_map('intval',$children))));

$product_ids = array();
foreach ($tree_ids as $tid) {
    $ids = get_objects_in_term($tid, 'product_cat');
    if (is_wp_error($ids)) continue;
    foreach ($ids as $id) if (get_post_type($id)==='product' && get_post_status($id)!=='trash') $product_ids[]=(int)$id;
}
$product_ids = array_values(array_unique($product_ids));
sort($product_ids, SORT_NUMERIC);

$products = array();
$sku_rows = array();
foreach ($product_ids as $pid) {
    $p = wc_get_product($pid);
    if (!$p) continue;

    $terms = wp_get_object_terms($pid, 'product_cat');
    $cats = array();
    $cat_paths = array();
    if (!is_wp_error($terms)) {
        foreach ($terms as $t) {
            if (in_array((int)$t->term_id, $tree_ids, true)) continue;
            $cats[] = $t->name;
            $cat_paths[] = mdo_cat_path($t);
        }
    }
    $cats = array_values(array_unique($cats));
    $cat_paths = array_values(array_unique($cat_paths));

    $attrs = array();
    foreach ($p->get_attributes() as $attr) {
        if (!is_object($attr)) continue;
        $label = wc_attribute_label($attr->get_name());
        $vals = $attr->is_taxonomy()
            ? wc_get_product_terms($pid, $attr->get_name(), array('fields'=>'names'))
            : $attr->get_options();
        if (!is_wp_error($vals)) $attrs[$label] = array_values((array)$vals);
    }

    $base = array(
        'product_id' => $pid,
        'product_name' => get_the_title($pid),
        'slug' => get_post_field('post_name',$pid),
        'status' => get_post_status($pid),
        'type' => $p->get_type(),
        'sku' => $p->get_sku(),
        'identifier_code' => mdo_identifier($p),
        'brand' => mdo_brand($pid),
        'vendor' => mdo_vendor($pid),
        'description' => mdo_clean_text($p->get_description()),
        'short_description' => mdo_clean_text($p->get_short_description()),
        'price' => $p->get_price(),
        'regular_price' => $p->get_regular_price(),
        'sale_price' => $p->get_sale_price(),
        'currency' => get_woocommerce_currency(),
        'stock_status' => $p->get_stock_status(),
        'manage_stock' => $p->get_manage_stock(),
        'stock_quantity' => $p->get_stock_quantity(),
        'backorders' => $p->get_backorders(),
        'weight_kg' => $p->get_weight(),
        'length_cm' => $p->get_length(),
        'width_cm' => $p->get_width(),
        'height_cm' => $p->get_height(),
        'shipping_class' => $p->get_shipping_class(),
        'tax_class' => $p->get_tax_class(),
        'categories' => $cats,
        'category_paths' => $cat_paths,
        'attributes' => $attrs,
        'images' => mdo_images($p),
        'permalink' => get_permalink($pid),
    );
    $products[] = $base;

    if ($p->is_type('variable')) {
        foreach ($p->get_children() as $vid) {
            $v = wc_get_product($vid);
            if (!$v) continue;
            $vattrs = array();
            foreach ($v->get_attributes() as $k=>$val) {
                $vattrs[wc_attribute_label($k)] = (string)$val;
            }
            $sku_rows[] = array(
                'product_id'=>$pid,
                'variation_id'=>(int)$vid,
                'product_name'=>$base['product_name'],
                'status'=>$base['status'],
                'product_type'=>'variable',
                'brand'=>$base['brand'],
                'vendor'=>$base['vendor'],
                'description'=>$base['description'],
                'short_description'=>$base['short_description'],
                'categories'=>$base['categories'],
                'category_paths'=>$base['category_paths'],
                'variation_attributes'=>$vattrs,
                'sku'=>$v->get_sku(),
                'identifier_code'=>mdo_identifier($v),
                'price'=>$v->get_price(),
                'regular_price'=>$v->get_regular_price(),
                'sale_price'=>$v->get_sale_price(),
                'currency'=>$base['currency'],
                'stock_status'=>$v->get_stock_status(),
                'manage_stock'=>$v->get_manage_stock(),
                'stock_quantity'=>$v->get_stock_quantity(),
                'backorders'=>$v->get_backorders(),
                'weight_kg'=>$v->get_weight() !== '' ? $v->get_weight() : $base['weight_kg'],
                'length_cm'=>$v->get_length() !== '' ? $v->get_length() : $base['length_cm'],
                'width_cm'=>$v->get_width() !== '' ? $v->get_width() : $base['width_cm'],
                'height_cm'=>$v->get_height() !== '' ? $v->get_height() : $base['height_cm'],
                'images'=>mdo_images($v,$p),
                'permalink'=>$base['permalink'],
            );
        }
    } else {
        $sku_rows[] = array(
            'product_id'=>$pid,
            'variation_id'=>0,
            'product_name'=>$base['product_name'],
            'status'=>$base['status'],
            'product_type'=>$base['type'],
            'brand'=>$base['brand'],
            'vendor'=>$base['vendor'],
            'description'=>$base['description'],
            'short_description'=>$base['short_description'],
            'categories'=>$base['categories'],
            'category_paths'=>$base['category_paths'],
            'variation_attributes'=>array(),
            'sku'=>$base['sku'],
            'identifier_code'=>$base['identifier_code'],
            'price'=>$base['price'],
            'regular_price'=>$base['regular_price'],
            'sale_price'=>$base['sale_price'],
            'currency'=>$base['currency'],
            'stock_status'=>$base['stock_status'],
            'manage_stock'=>$base['manage_stock'],
            'stock_quantity'=>$base['stock_quantity'],
            'backorders'=>$base['backorders'],
            'weight_kg'=>$base['weight_kg'],
            'length_cm'=>$base['length_cm'],
            'width_cm'=>$base['width_cm'],
            'height_cm'=>$base['height_cm'],
            'images'=>$base['images'],
            'permalink'=>$base['permalink'],
        );
    }
}

$out = array(
    'generated_at'=>gmdate('c'),
    'site'=>home_url('/'),
    'mentta_term'=>array('id'=>(int)$mentta->term_id,'name'=>$mentta->name,'slug'=>$mentta->slug),
    'product_count'=>count($products),
    'sku_count'=>count($sku_rows),
    'products'=>$products,
    'skus'=>$sku_rows,
);
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
