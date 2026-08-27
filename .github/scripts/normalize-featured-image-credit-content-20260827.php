<?php
/** Remove legacy manual featured-image credit blocks now handled by the global attribution rule. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$ids = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids',
    'meta_key'=>'_emdo_authority_key','meta_value'=>'iberian-sobrasada-guide',
));
if (empty($ids)) { throw new RuntimeException('Sobrasada article not found'); }
$post_id = (int)$ids[0];

function emdo_normalize_credit_remove(string $html): string {
    $new = preg_replace('~\s*<p class=["\']emdo-image-credit["\'][^>]*>.*?</p>\s*~is', "\n", $html);
    return is_string($new) ? $new : $html;
}

$es = (string)get_post_field('post_content',$post_id);
$es_new = emdo_normalize_credit_remove($es);
if ($es_new !== $es) {
    $r = wp_update_post(array('ID'=>$post_id,'post_content'=>$es_new),true);
    if (is_wp_error($r)) { throw new RuntimeException($r->get_error_message()); }
}
$en = (string)get_post_meta($post_id,'_en_US_post_content',true);
$en_new = emdo_normalize_credit_remove($en);
if ($en_new !== $en) { update_post_meta($post_id,'_en_US_post_content',$en_new); }
update_post_meta($post_id,'_emdo_editorial_updated',gmdate('c'));

echo wp_json_encode(array('normalized'=>true,'post_id'=>$post_id,'es_removed'=>$es_new!==$es,'en_removed'=>$en_new!==$en),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
