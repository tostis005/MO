<?php
/**
 * Read-only production audit of category image assignments.
 * Categories visible in the home and /categorias/ are computed using the
 * exact same function that the public category hub uses.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'elmercado_categories_hub_items_010257' ) ) {
    throw new RuntimeException( 'Category hub source unavailable' );
}
$rows = elmercado_categories_hub_items_010257();
$out = array(
  'date_utc' => gmdate( 'c' ),
  'siteurl' => get_option('siteurl'),
  'categories' => array(),
);
foreach ($rows as $row) {
  if ( (int)($row['parent'] ?? -1) !== 0 ) { continue; }
  $slug = (string)($row['slug'] ?? '');
  if ( in_array($slug, array('mentta','menta'), true) ) { continue; }
  $id = (int)($row['id'] ?? 0);
  $thumb = (int)get_term_meta($id, 'thumbnail_id', true);
  $img = $thumb > 0 ? (string)wp_get_attachment_image_url($thumb, 'full') : '';
  $media = $thumb > 0 ? (array)wp_get_attachment_metadata($thumb) : array();
  $out['categories'][] = array(
    'id' => $id,
    'slug' => $slug,
    'name' => (string)($row['name'] ?? ''),
    'count' => (int)($row['count'] ?? 0),
    'thumbnail_id' => $thumb,
    'has_working_image' => $thumb > 0 && $img !== '' && get_post_status($thumb) !== false,
    'image_url' => $img,
    'image_width' => (int)($media['width'] ?? 0),
    'image_height' => (int)($media['height'] ?? 0),
    'image_file' => (string)($media['file'] ?? ''),
  );
}
$fruit_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$out['fruit_candidates'] = array();
if ( ! is_wp_error( $fruit_terms ) ) {
  foreach ( $fruit_terms as $term ) {
    if ( ! preg_match( '/frut|fruit|aguacat|mango/i', $term->slug . ' ' . $term->name ) ) { continue; }
    $tid = (int) $term->term_id;
    $thumb = (int) get_term_meta( $tid, 'thumbnail_id', true );
    $out['fruit_candidates'][] = array(
      'id' => $tid,
      'slug' => $term->slug,
      'name' => $term->name,
      'parent' => (int) $term->parent,
      'native_count' => (int) $term->count,
      'thumbnail_id' => $thumb,
      'image_url' => $thumb ? wp_get_attachment_image_url( $thumb, 'full' ) : false,
    );
  }
}
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
