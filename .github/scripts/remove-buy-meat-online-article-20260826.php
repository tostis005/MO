<?php
/** Remove the rejected buy-meat-online editorial article from production. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$key  = 'buy-meat-online-guide';
$slug = 'comprar-carne-online-domicilio-que-comprobar';

$ids = get_posts(array(
    'post_type'      => 'post',
    'post_status'    => array('publish','draft','pending','future','private','trash'),
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_authority_key',
    'meta_value'     => $key,
));

if (empty($ids)) {
    $post = get_page_by_path($slug, OBJECT, 'post');
    if ($post instanceof WP_Post) {
        $ids = array((int) $post->ID);
    }
}

$deleted = array();
foreach ($ids as $id) {
    $id = (int) $id;
    if ($id > 0 && wp_delete_post($id, true)) {
        $deleted[] = $id;
    }
}

$remaining = get_posts(array(
    'post_type'      => 'post',
    'post_status'    => array('publish','draft','pending','future','private','trash'),
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_authority_key',
    'meta_value'     => $key,
));

if (!empty($remaining)) {
    throw new RuntimeException('Rejected article still exists after deletion attempt: ' . implode(',', array_map('intval', $remaining)));
}

echo wp_json_encode(array(
    'key'       => $key,
    'deleted'   => $deleted,
    'remaining' => 0,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
