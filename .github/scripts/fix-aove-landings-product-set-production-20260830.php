<?php
/**
 * Ensure all curated AOVE landings use the complete live AOVE product set.
 * Resolves products by slug so IDs never need to be hard-coded.
 * Touch: trigger the temporary vegetable and legume production runner.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

try {
    $product_slugs = array(
        'aceite-de-oliva-virgen-extra-5l',
        'aceite-de-oliva-virgen-extra-500ml-pet',
        'aceite-de-oliva-virgen-extra-1l',
    );
    $ids = array();
    foreach ($product_slugs as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'product');
        if (!($p instanceof WP_Post) || $p->post_status !== 'publish') {
            throw new Exception('Missing published AOVE product: '.$slug);
        }
        $ids[] = (int) $p->ID;
    }
    $ids = array_values(array_unique($ids));
    if (count($ids) !== 3) throw new Exception('Expected exactly 3 distinct live AOVE products.');

    $shortcode = '[products ids="'.implode(',', $ids).'" columns="3" orderby="post__in"]';
    $keys = array('aove-cordoba-almazara','aove-arbequina','aove-picual');
    $updated = array();

    foreach ($keys as $key) {
        $page_ids = get_posts(array(
            'post_type'=>'page',
            'post_status'=>array('publish','draft','private'),
            'posts_per_page'=>2,
            'fields'=>'ids',
            'no_found_rows'=>true,
            'meta_key'=>'_emdo_ac_landing_key',
            'meta_value'=>$key,
        ));
        if (!$page_ids) throw new Exception('AOVE landing missing: '.$key);
        $page_id = (int) $page_ids[0];
        $post = get_post($page_id);
        if (!($post instanceof WP_Post)) throw new Exception('Invalid AOVE landing post: '.$key);

        $pattern = '/\[products\s+ids="[^"]*"\s+columns="[^"]*"\s+orderby="post__in"\]/';
        $es = preg_replace($pattern, $shortcode, (string)$post->post_content, -1, $es_count);
        $en_raw = (string)get_post_meta($page_id, '_en_US_post_content', true);
        $en = preg_replace($pattern, $shortcode, $en_raw, -1, $en_count);
        if ($es_count < 1 || $en_count < 1) throw new Exception('Product shortcode not found on '.$key);

        $r = wp_update_post(wp_slash(array('ID'=>$page_id,'post_content'=>$es)), true);
        if (is_wp_error($r)) throw new Exception($r->get_error_message());
        update_post_meta($page_id, '_en_US_post_content', $en);
        $updated[] = $page_id;
    }

    echo wp_json_encode(array(
        'verified'=>true,
        'product_count'=>count($ids),
        'product_slugs'=>$product_slugs,
        'pages'=>$updated,
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}
