<?php
/**
 * Plugin Name: EMDO Montjam black shoulder format associations
 * Scope: the non-DOP black-brida Montjam shoulder only.
 */
if (!defined('ABSPATH')) exit;

function emdo_montjam_black_format_sync_20261126($parent_id, $apply = true) {
    global $wpdb;
    $p = wc_get_product($parent_id);
    if (!$p || !$p->is_type('variable') || $p->get_slug() !== 'paleta-de-bellota-100-iberica-montjam') {
        return new WP_Error('wrong_product', 'Unexpected product');
    }
    $bt = $wpdb->prefix . 'yith_wapo_blocks';
    $at = $wpdb->prefix . 'yith_wapo_addons';
    $rt = $wpdb->prefix . 'yith_wapo_blocks_assoc';
    $blocks = $wpdb->get_results($wpdb->prepare(
        "SELECT id, visibility, product_association, settings FROM `$bt` WHERE name IN (%s,%s)",
        'Montjam · Formato · '.$parent_id, 'Mont Jam · Formato · '.$parent_id
    ), ARRAY_A);
    if (count((array)$blocks) !== 1 || (int)$blocks[0]['visibility'] !== 1 || $blocks[0]['product_association'] !== 'products') {
        return new WP_Error('unexpected_block', 'Missing, duplicate or inactive YITH format block');
    }
    $bid = (int)$blocks[0]['id'];
    $settings = maybe_unserialize($blocks[0]['settings']);
    if (!in_array((string)$parent_id, array_map('strval',(array)($settings['rules']['show_in_products'] ?? [])), true)) {
        return new WP_Error('unexpected_rule', 'YITH parent association not verified');
    }
    $addons = $wpdb->get_results($wpdb->prepare("SELECT settings, options FROM `$at` WHERE block_id=%d AND visibility=1", $bid), ARRAY_A);
    if (count((array)$addons) !== 1) return new WP_Error('unexpected_addon','Expected one active addon');
    $as = maybe_unserialize($addons[0]['settings']);
    $ao = maybe_unserialize($addons[0]['options']);
    if (($as['title'] ?? '') !== 'FORMATO' || ($as['type'] ?? '') !== 'select' ||
        !in_array('Pieza entera',(array)($ao['label'] ?? []),true)) return new WP_Error('unexpected_options','Format options not verified');
    $children = get_posts(['post_type'=>'product_variation','post_parent'=>(int)$parent_id,'post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','suppress_filters'=>true]);
    $desired = array_merge([(int)$parent_id],array_map('intval',$children));
    $existing = $wpdb->get_col($wpdb->prepare("SELECT object FROM `$rt` WHERE rule_id=%d AND type='product'",$bid));
    if ($wpdb->last_error) return new WP_Error('read_error','YITH association query failed');
    $missing = array_values(array_diff($desired,array_map('intval',$existing)));
    $inserted = [];
    if ($apply) {
        foreach ($missing as $vid) {
            $ok = $wpdb->insert($rt,['rule_id'=>$bid,'object'=>(string)$vid,'type'=>'product']);
            if (!$ok) return new WP_Error('insert_error','YITH association insertion failed');
            $inserted[] = $vid;
        }
        $after = $wpdb->get_col($wpdb->prepare("SELECT object FROM `$rt` WHERE rule_id=%d AND type='product'",$bid));
        if ($wpdb->last_error || array_diff($desired,array_map('intval',$after))) return new WP_Error('verify_error','YITH associations not verified');
        if ($inserted) {
            wc_delete_product_transients($parent_id);
            clean_post_cache($parent_id);
            if (function_exists('rocket_clean_post')) rocket_clean_post($parent_id);
        }
    }
    $variations=[];
    foreach ($children as $vid) {
        $v=wc_get_product($vid);
        if ($v) $variations[]=['id'=>$vid,'attributes'=>$v->get_attributes(),'price'=>$v->get_price('edit'),'stock'=>$v->get_stock_status(),'purchasable'=>$v->is_purchasable()];
    }
    return ['product_id'=>$parent_id,'block_id'=>$bid,'missing'=>$missing,'inserted'=>$inserted,'variations'=>$variations,'format_labels'=>$ao['label'] ?? [],'format_prices'=>$ao['price'] ?? []];
}
function emdo_montjam_black_format_on_variation_20261126($variation_id) {
    $parent_id = wp_get_post_parent_id($variation_id);
    if (!$parent_id || get_post_field('post_name',$parent_id) !== 'paleta-de-bellota-100-iberica-montjam') return;
    $result = emdo_montjam_black_format_sync_20261126($parent_id,true);
    if (is_wp_error($result)) error_log('[EMDO Montjam format] '.$result->get_error_message());
}
add_action('woocommerce_new_product_variation','emdo_montjam_black_format_on_variation_20261126',100,1);
add_action('woocommerce_update_product_variation','emdo_montjam_black_format_on_variation_20261126',100,1);
