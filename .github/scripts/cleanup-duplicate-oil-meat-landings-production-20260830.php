<?php
/**
 * Remove accidental alternate oil/meat landing variants and their category-link blocks.
 * Keeps the curated _emdo_ac_landing_key set as the single canonical structure.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

try {
    $duplicate_slugs = array(
        'aceite-oliva-virgen-extra-directo-almazara',
        'aceite-oliva-virgen-extra',
        'lotes-carne-online',
        'packs-carne-online',
        'cortes-premium-vacuno',
        'hamburguesas-ternera-vaca-online',
        'hamburguesas-vaca',
        'carne-fresca-domicilio',
    );

    $deleted = array();
    foreach ($duplicate_slugs as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'page');
        if (!($p instanceof WP_Post)) continue;
        $alt_key = (string)get_post_meta($p->ID, '_emdo_oc_landing_key', true);
        if ($alt_key === '') continue; // Never delete an unrelated pre-existing page.
        $deleted[] = array('id'=>(int)$p->ID,'slug'=>$p->post_name,'alt_key'=>$alt_key);
        wp_delete_post((int)$p->ID, true);
    }

    // Shared canonical slugs may have been overwritten by the alternate deploy; remove only its marker.
    $alt_ids = get_posts(array(
        'post_type'=>'page',
        'post_status'=>array('publish','draft','private','trash'),
        'posts_per_page'=>-1,
        'fields'=>'ids',
        'no_found_rows'=>true,
        'meta_key'=>'_emdo_oc_landing_key',
    ));
    $meta_removed = array();
    foreach ($alt_ids as $id) {
        $id = (int)$id;
        $key = (string)get_post_meta($id, '_emdo_oc_landing_key', true);
        if ($key !== '') {
            delete_post_meta($id, '_emdo_oc_landing_key');
            $meta_removed[] = array('id'=>$id,'key'=>$key,'slug'=>(string)get_post_field('post_name',$id));
        }
    }

    // Remove category blocks injected by the alternate workflow, preserving our emdo-ac block.
    foreach (array('aceites','carnes') as $term_slug) {
        $term = get_term_by('slug', $term_slug, 'product_cat');
        if (!$term || is_wp_error($term)) continue;
        $start='<!-- emdo-commercial-landings-'.$term_slug.'-start -->';
        $end='<!-- emdo-commercial-landings-'.$term_slug.'-end -->';
        $pattern='/\s*'.preg_quote($start,'/').'.*?'.preg_quote($end,'/').'\s*/s';
        $es = preg_replace($pattern, "\n", (string)$term->description);
        wp_update_term((int)$term->term_id, 'product_cat', array('description'=>trim($es)));
        $en = (string)get_term_meta((int)$term->term_id, '_en_US_description', true);
        if ($en !== '') update_term_meta((int)$term->term_id, '_en_US_description', trim((string)preg_replace($pattern, "\n", $en)));
    }

    flush_rewrite_rules(false);
    echo wp_json_encode(array(
        'verified'=>true,
        'deleted'=>$deleted,
        'deleted_count'=>count($deleted),
        'alt_meta_removed'=>$meta_removed,
        'alt_meta_removed_count'=>count($meta_removed),
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}
