<?php
/**
 * Assign already-approved category images without changing category content
 * or replacing an image that a site editor may have selected independently.
 * Run: EMDO_CATEGORY_IMAGES_DIR=/tmp/assets wp eval-file <this-file>
 */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
if ( ! taxonomy_exists( 'product_cat' ) ) { throw new RuntimeException( 'WooCommerce taxonomy unavailable' ); }
$dir = rtrim( (string)getenv('EMDO_CATEGORY_IMAGES_DIR'), '/');
if ( $dir === '' || ! is_dir( $dir ) ) { throw new RuntimeException('Missing source image directory'); }
$defs = array(
  'chocolates-artesanos' => array(
    'filename'=>'categoria-chocolates-artesanos-20261020.webp',
    'name'=>'Chocolates artesanos',
    'alt'=>'Selección de tabletas de chocolate, bombones y trufas sobre pizarra negra'
  ),
  'cafe-de-especialidad' => array(
    'filename'=>'categoria-cafe-de-especialidad-20261020.webp',
    'name'=>'Café de especialidad',
    'alt'=>'Taza de espresso y granos de café tostado sobre pizarra negra'
  ),
  'huevos' => array(
    'filename'=>'categoria-huevos-20261020.webp',
    'name'=>'Huevos',
    'alt'=>'Huevos enteros de diferentes tamaños sobre pizarra negra'
  ),
);
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
$out = array( 'batch'=>'category-images-20261020', 'date_utc'=>gmdate('c'), 'categories'=>array() );
// Validate *all* inputs before making any database or media changes.
foreach ( $defs as $slug => $def ) {
  $term = get_term_by( 'slug', $slug, 'product_cat' );
  if ( ! $term instanceof WP_Term ) { throw new RuntimeException('Missing category '.$slug); }
  $source = $dir.'/'.$def['filename'];
  if ( ! is_readable($source) || filesize($source) < 4000 ) { throw new RuntimeException('Missing or invalid file '.$source); }
  $info = @getimagesize( $source );
  if ( !$info || $info['mime'] !== 'image/webp' || (int)$info[0] < 250 || (int)$info[1] < 375 ) {
    throw new RuntimeException('Bad image format or dimensions '.$source);
  }
}
foreach ( $defs as $slug => $def ) {
  $term = get_term_by( 'slug', $slug, 'product_cat' );
  $id = (int)$term->term_id;
  $existing = (int)get_term_meta($id, 'thumbnail_id', true);
  $existing_url = $existing ? wp_get_attachment_image_url($existing, 'full') : false;
  if ( $existing_url && get_post_status($existing) ) {
    $out['categories'][$slug] = array( 'status'=>'skipped_existing_image', 'term_id'=>$id, 'attachment_id'=>$existing, 'image_url'=>$existing_url );
    continue;
  }
  $key = 'category-approved-20261020-'.$slug;
  $stored = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_category_visual_key','meta_value'=>$key));
  if ( $stored ) {
    $attachment = (int)$stored[0];
  } else {
    $source = $dir.'/'.$def['filename'];
    $uploaded = wp_upload_bits($def['filename'], null, file_get_contents($source));
    if ( !empty($uploaded['error']) || empty($uploaded['file']) ) {
      throw new RuntimeException('Upload failed for '.$slug.': '.($uploaded['error'] ?? ''));
    }
    $attachment = wp_insert_attachment(array(
      'guid'=>$uploaded['url'],
      'post_mime_type'=>'image/webp',
      'post_title'=>$def['name'].' — fotografía de categoría',
      'post_status'=>'inherit',
    ),$uploaded['file']);
    if ( is_wp_error($attachment) || !$attachment ) { throw new RuntimeException('Attachment insert failed '.$slug); }
    $attachment = (int)$attachment;
    $metadata = wp_generate_attachment_metadata($attachment, $uploaded['file']);
    if ( is_array($metadata) ) { wp_update_attachment_metadata($attachment, $metadata); }
    update_post_meta($attachment, '_emdo_category_visual_key', $key);
  }
  update_post_meta($attachment, '_wp_attachment_image_alt', $def['alt']);
  update_term_meta($id, 'thumbnail_id', $attachment);
  clean_term_cache($id,'product_cat');
  $check_id = (int)get_term_meta($id, 'thumbnail_id', true);
  $check_url = wp_get_attachment_image_url($check_id, 'full');
  if ( $check_id !== $attachment || !$check_url ) { throw new RuntimeException('Failed to verify '.$slug); }
  $out['categories'][$slug] = array(
    'status'=>'assigned', 'term_id'=>$id,'attachment_id'=>$attachment,
    'image_url'=>$check_url,
  );
}
wp_cache_flush();
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), PHP_EOL;
