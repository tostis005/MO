<?php
/** Audit the six approved featured-image selections without mutating content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = array(
  array('slug'=>'aceite-oliva-o-girasol-para-freir-cual-elegir','source_page'=>'https://www.pexels.com/photo/preparing-food-on-frying-pan-9431741/'),
  array('slug'=>'cuanto-hierro-tiene-carne-ternera','source_page'=>'https://www.pexels.com/photo/close-up-photo-of-fresh-meat-slice-5892851/'),
  array('slug'=>'cuando-echar-sal-garbanzos-lentejas-alubias-endurece-legumbres','source_page'=>'https://www.pexels.com/photo/a-close-up-shot-of-chickpeas-in-a-bowl-7656561/'),
  array('slug'=>'hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar','source_page'=>'https://www.pexels.com/photo/close-up-photo-chickpeas-in-a-bowl-7656564/'),
  array('slug'=>'conserva-vs-semiconserva-diferencia-por-que-necesita-frio','source_page'=>'https://www.pexels.com/photo/close-up-shot-of-sardines-in-a-can-9797030/'),
  array('slug'=>'que-verduras-tienen-mas-fibra','source_page'=>'https://www.pexels.com/photo/fresh-veggies-and-greens-flat-lay-on-marble-29959934/'),
);
$out = array('generated_at'=>gmdate('c'),'posts'=>array());
foreach ($items as $item) {
  $post = get_page_by_path($item['slug'], OBJECT, 'post');
  if (!$post instanceof WP_Post) {
    $out['posts'][] = array('slug'=>$item['slug'],'post_exists'=>false,'desired_source_page'=>$item['source_page']);
    continue;
  }
  $post_id = (int)$post->ID;
  $thumb_id = (int)get_post_thumbnail_id($post_id);
  $thumb_url = $thumb_id ? (string)wp_get_attachment_url($thumb_id) : '';
  $thumb_source_page = $thumb_id ? (string)get_post_meta($thumb_id,'_mdo_source_page',true) : '';
  $out['posts'][] = array(
    'slug'=>$item['slug'],
    'post_exists'=>true,
    'post_id'=>$post_id,
    'permalink'=>get_permalink($post_id),
    'thumbnail_id'=>$thumb_id,
    'thumbnail_url'=>$thumb_url,
    'thumbnail_source_page'=>$thumb_source_page,
    'desired_source_page'=>$item['source_page'],
    'source_matches'=>($thumb_source_page === $item['source_page']),
    'approved_id'=>(int)get_post_meta($post_id,'_emdo_editorial_image_approved_id',true),
    'uses_default'=>(string)get_post_meta($post_id,'_emdo_uses_default_featured',true),
    'cover_asset'=>(string)get_post_meta($post_id,'_emdo_editorial_cover_asset_id',true),
  );
}
$out['all_match'] = count($out['posts']) === 6 && count(array_filter($out['posts'], fn($p)=>!empty($p['source_matches']))) === 6;
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . PHP_EOL;
