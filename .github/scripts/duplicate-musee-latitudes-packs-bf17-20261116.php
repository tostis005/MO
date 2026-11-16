<?php
/**
 * Copy the seven existing Musee / Latitudes Mentta packs to the public shop
 * for Black Friday with an exact 17% WooCommerce sale.
 *
 * WP-CLI: MDO_WEB_PACKS_MODE=preflight|apply|verify|rollback wp eval-file this.php
 * Idempotent. Never writes to the seven protected Mentta originals.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('WC_Product_Simple')) {
    fwrite(STDERR, "WPCLI_WOOCOMMERCE_REQUIRED\n"); exit(2);
}
$mode = (string)(getenv('MDO_WEB_PACKS_MODE') ?: 'preflight');
if (!in_array($mode, ['preflight','apply','verify','rollback'], true)) exit(2);
$host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)) exit(2);
if (!function_exists('emdo_mentta_exclusive_ids') ||
    !function_exists('emdo_web_pack_components_available')) {
    fwrite(STDERR, "REQUIRED_STOCK_OR_MENTTA_GUARD_MISSING\n"); exit(3);
}

$ids = [
    21814 => ['pasaporte-salvaje', 'carnes-exoticas'],
    21816 => ['reserva-de-caza', 'carnes-exoticas'],
    21818 => ['safari-africano', 'carnes-exoticas'],
    21820 => ['vuelta-al-mundo', 'carnes-exoticas'],
    21822 => ['parrilla-sin-fronteras-xl', 'carnes-exoticas'],
    21824 => ['universo-wagyu', 'wagyu'],
    21826 => ['leyendas-del-vacuno', 'wagyu'],
];
$tax = [];
foreach (['carnes','packs-y-lotes','carnes-exoticas','wagyu','mentta','mentta-carnes'] as $slug) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!($term instanceof WP_Term)) {
        fwrite(STDERR, "CATEGORY_NOT_FOUND $slug\n"); exit(3);
    }
    $tax[$slug] = (int)$term->term_id;
}
$exclusive = emdo_mentta_exclusive_ids();
$rows = [];
$errors = [];
foreach ($ids as $sourceId => [$key, $extra]) {
    $source = wc_get_product($sourceId);
    if (!$source || !$source->is_type('simple') ||
        !in_array($source->get_status(), ['publish','draft'], true) ||
        (int)get_post_field('post_author', $sourceId) !== 4949 ||
        get_post_meta($sourceId, '_emdo_mentta_only', true) !== 'yes' ||
        get_post_meta($sourceId, '_emdo_mentta_pack_key', true) !== $key ||
        !(in_array($sourceId, $exclusive, true) || $source->get_catalog_visibility('edit') === 'hidden') ||
        !in_array($tax['mentta'], array_map('intval', $source->get_category_ids()), true) ||
        !$source->get_image_id()) {
        $errors[] = "MENTTA_SOURCE_MISMATCH $sourceId $key"; continue;
    }
    $components = get_post_meta($sourceId, '_emdo_mentta_pack_lines', true);
    if (!is_array($components) || count($components) < 5) {
        $errors[] = "PACK_COMPONENTS_MISSING $sourceId"; continue;
    }
    foreach ($components as $line) {
        $part = wc_get_product((int)($line['id'] ?? 0));
        if (!$part || !$part->is_type('simple') ||
            $part->get_status() !== 'publish' ||
            (int)($line['qty'] ?? 0) < 1) {
            $errors[] = "PACK_COMPONENT_INVALID $sourceId"; break;
        }
    }
    $base = (float)$source->get_price('edit');
    if ($base <= 0 || !is_finite($base)) {
        $errors[] = "INVALID_CURRENT_PRICE $sourceId"; continue;
    }
    $regular = wc_format_decimal($base, 2);
    $sale = wc_format_decimal(round($base * 0.83 + 0.00000001, 2), 2);
    if ((float)$sale >= (float)$regular) {
        $errors[] = "INVALID_DISCOUNT $sourceId"; continue;
    }
    $sku = 'MDO-WEB-BF17-'.strtoupper(str_replace('-', '_', $key));
    $slug = 'pack-musee-'.$key.'-black-friday';
    $existingId = (int)wc_get_product_id_by_sku($sku);
    $bySlug = get_page_by_path($slug, OBJECT, 'product');
    if ($bySlug && (!$existingId || (int)$bySlug->ID !== $existingId)) {
        $errors[] = "PUBLIC_SLUG_COLLISION $slug"; continue;
    }
    if ($existingId) {
        $existing = wc_get_product($existingId);
        if (!$existing || !$existing->is_type('simple') ||
            (int)get_post_meta($existingId, '_emdo_web_source_mentta_pack_id', true) !== $sourceId ||
            get_post_meta($existingId, '_emdo_web_pack_key', true) !== $key ||
            in_array($existingId, $exclusive, true)) {
            $errors[] = "PUBLIC_SKU_COLLISION $sku"; continue;
        }
    }
    $rows[] = compact('sourceId','key','extra','source','components','regular','sale','sku','slug','existingId');
}
if ($mode !== 'rollback' && (count($rows) !== 7 || $errors)) {
    echo "WEB_PACK_PREFLIGHT_ERRORS ".wp_json_encode($errors, JSON_UNESCAPED_UNICODE)."\n";
    exit(4);
}
if ($mode === 'preflight') {
    foreach ($rows as $r) {
        echo 'WEB_PACK_PREFLIGHT '.wp_json_encode([
            'source_id'=>$r['sourceId'], 'key'=>$r['key'],
            'source_status'=>$r['source']->get_status(),
            'existing_web_id'=>$r['existingId'],
            'original_current_price'=>$r['regular'],
            'sale_price_17_percent'=>$r['sale'],
            'lines'=>count($r['components']),
            'components_in_stock'=>emdo_web_pack_components_available($r['components'], 1),
            'target_categories'=>['carnes','packs-y-lotes',$r['extra']]
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
    echo "SEVEN_WEB_PACKS_PREFLIGHT_OK\n"; exit(0);
}
if ($mode === 'rollback') {
    foreach ($ids as $sourceId => [$key]) {
        $sku = 'MDO-WEB-BF17-'.strtoupper(str_replace('-', '_', $key));
        $id = (int)wc_get_product_id_by_sku($sku);
        if (!$id || (int)get_post_meta($id, '_emdo_web_source_mentta_pack_id', true) !== $sourceId) continue;
        $p = wc_get_product($id);
        if (!$p) continue;
        $p->set_status('draft'); $p->save();
        clean_post_cache($id); wc_delete_product_transients($id);
        echo "WEB_PACK_ROLLED_BACK_TO_DRAFT $id\n";
    }
    exit(0);
}
if ($mode === 'apply') {
    $done = [];
    try {
        foreach ($rows as $r) {
            $src = $r['source'];
            $p = $r['existingId'] ? wc_get_product($r['existingId']) : new WC_Product_Simple();
            if ($r['existingId'] && $p->get_status() === 'publish') {
                $done[] = $r['existingId']; continue;
            }
            $p->set_status('draft');
            $p->set_name($src->get_name('edit'));
            $p->set_slug($r['slug']);
            $p->set_sku($r['sku']);
            $p->set_description($src->get_description('edit'));
            $p->set_short_description($src->get_short_description('edit'));
            $p->set_regular_price($r['regular']);
            $p->set_sale_price($r['sale']);
            $p->set_date_on_sale_from(null);
            $p->set_date_on_sale_to(null);
            $p->set_catalog_visibility('visible');
            $p->set_category_ids([$tax['carnes'], $tax['packs-y-lotes'], $tax[$r['extra']]]);
            $p->set_image_id($src->get_image_id());
            $p->set_gallery_image_ids($src->get_gallery_image_ids());
            $p->set_weight($src->get_weight('edit'));
            $p->set_length($src->get_length('edit'));
            $p->set_width($src->get_width('edit'));
            $p->set_height($src->get_height('edit'));
            $p->set_shipping_class_id($src->get_shipping_class_id());
            $p->set_tax_status($src->get_tax_status('edit'));
            $p->set_tax_class($src->get_tax_class('edit'));
            $p->set_virtual(false);
            $p->set_sold_individually($src->get_sold_individually('edit'));
            $p->set_manage_stock(false);
            $p->set_backorders('no');
            $p->set_stock_status(emdo_web_pack_components_available($r['components'], 1) ? 'instock' : 'outofstock');
            $p->set_attributes($src->get_attributes('edit'));
            $p->set_purchase_note($src->get_purchase_note('edit'));
            $p->set_reviews_allowed($src->get_reviews_allowed('edit'));
            $p->set_featured(false);
            $p->update_meta_data('_emdo_web_source_mentta_pack_id', $r['sourceId']);
            $p->update_meta_data('_emdo_web_pack_key', $r['key']);
            $p->update_meta_data('_emdo_web_pack_lines', $r['components']);
            $p->update_meta_data('_emdo_web_black_friday_discount_percent', '17');
            // No _emdo_mentta_only flag, no Mentta categories, no Mentta ID exclusion,
            // and no source noindex flag. Public site versions remain separately indexed.
            $pid = $p->save();
            if (!$pid) throw new RuntimeException('SAVE_FAILED '.$r['key']);
            $done[] = $pid;
            if ((int)get_post_field('post_author', $pid) !== 4949) {
                $updated = wp_update_post(['ID'=>$pid, 'post_author'=>4949], true);
                if (is_wp_error($updated)) throw new RuntimeException('AUTHOR_FAILED '.$r['key']);
            }
            $producerTerms = wp_get_object_terms($r['sourceId'], 'pa_productor', ['fields'=>'ids']);
            if (!is_wp_error($producerTerms) && $producerTerms) {
                $set = wp_set_object_terms($pid, array_map('intval',$producerTerms), 'pa_productor', false);
                if (is_wp_error($set)) throw new RuntimeException('PRODUCER_FAILED '.$r['key']);
            }
            // Copy original search metadata, but never its Mentta-only noindex.
            foreach (['_yoast_wpseo_title','_yoast_wpseo_metadesc'] as $metaKey) {
                $v = get_post_meta($r['sourceId'], $metaKey, true);
                if (is_string($v) && $v !== '') update_post_meta($pid, $metaKey, $v);
            }
            delete_post_meta($pid, '_yoast_wpseo_meta-robots-noindex');
            delete_post_meta($pid, '_emdo_mentta_only');
            wc_delete_product_transients($pid);
        }
        foreach ($rows as $r) {
            $id = (int)wc_get_product_id_by_sku($r['sku']);
            $p = $id ? wc_get_product($id) : false;
            if (!$p || $p->get_status() !== 'draft' && $p->get_status() !== 'publish') {
                throw new RuntimeException('DRAFT_PREPARE_FAILED '.$r['key']);
            }
        }
        foreach ($rows as $r) {
            $id = (int)wc_get_product_id_by_sku($r['sku']);
            $p = wc_get_product($id);
            $p->set_status('publish');
            $p->save();
            clean_post_cache($id); wc_delete_product_transients($id);
            if (function_exists('rocket_clean_post')) rocket_clean_post($id);
        }
        if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product', true);
        foreach (['carnes','packs-y-lotes','carnes-exoticas','wagyu'] as $slug) clean_term_cache($tax[$slug], 'product_cat');
        if (function_exists('rocket_clean_domain')) rocket_clean_domain();
        wp_cache_flush();
    } catch (Throwable $e) {
        foreach ($done as $id) {
            $p = wc_get_product($id);
            if ($p) {$p->set_status('draft'); $p->save();}
        }
        fwrite(STDERR, "WEB_PACK_APPLY_FAILED_ALL_DRAFT ".$e->getMessage()."\n");
        exit(6);
    }
}
$results = [];
foreach ($rows as $r) {
    $source = wc_get_product($r['sourceId']);
    $id = (int)wc_get_product_id_by_sku($r['sku']);
    $p = $id ? wc_get_product($id) : false;
    $cats = $p ? array_map('intval', $p->get_category_ids()) : [];
    $visTerms = $p ? wp_get_object_terms($id,'product_visibility',['fields'=>'slugs']) : [];
    $required = [$tax['carnes'],$tax['packs-y-lotes'],$tax[$r['extra']]];
    sort($cats); sort($required);
    if (!$p || $p->get_status() !== 'publish' ||
        $p->get_catalog_visibility('edit') !== 'visible' ||
        $cats !== $required ||
        (string)$p->get_regular_price('edit') !== (string)$r['regular'] ||
        (string)$p->get_sale_price('edit') !== (string)$r['sale'] ||
        $p->get_name('edit') !== $source->get_name('edit') ||
        $p->get_description('edit') !== $source->get_description('edit') ||
        $p->get_short_description('edit') !== $source->get_short_description('edit') ||
        $p->get_image_id() !== $source->get_image_id() ||
        $p->get_gallery_image_ids() !== $source->get_gallery_image_ids() ||
        $p->get_weight('edit') !== $source->get_weight('edit') ||
        get_post_meta($id, '_emdo_web_pack_lines', true) !== $r['components'] ||
        (int)get_post_field('post_author', $id) !== 4949 ||
        in_array($id, emdo_mentta_exclusive_ids(), true) ||
        get_post_meta($id, '_emdo_mentta_only', true) === 'yes' ||
        (is_array($visTerms) && array_intersect($visTerms, ['exclude-from-catalog','exclude-from-search'])) ||
        in_array($tax['mentta'],$cats,true) || in_array($tax['mentta-carnes'],$cats,true)) {
        fwrite(STDERR, "WEB_PACK_VERIFICATION_FAILED {$r['key']} id=$id\n");
        exit(7);
    }
    $results[] = [
        'web_id'=>$id, 'source_id'=>$r['sourceId'], 'name'=>$p->get_name(),
        'regular_price'=>$p->get_regular_price('edit'), 'black_friday_price'=>$p->get_sale_price('edit'),
        'in_stock'=>$p->is_in_stock(), 'categories'=>['carnes','packs-y-lotes',$r['extra']],
        'permalink'=>get_permalink($id)
    ];
}
echo 'SEVEN_WEB_PACKS_VERIFIED '.wp_json_encode($results, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
