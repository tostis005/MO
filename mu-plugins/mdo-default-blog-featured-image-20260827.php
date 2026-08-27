<?php
/**
 * Central provisional featured image support for editorial posts.
 *
 * Posts carrying _emdo_uses_default_featured=1 belong to the provisional-image
 * pool. If a different featured image is assigned later, the post leaves the
 * pool automatically so a future global placeholder change cannot overwrite a
 * definitive image.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_default_blog_featured_meta_changed_20260827( $meta_id, $object_id, $meta_key, $meta_value ): void {
    if ( '_thumbnail_id' !== (string) $meta_key ) { return; }
    $post_id = (int) $object_id;
    if ( $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) { return; }
    if ( '1' !== (string) get_post_meta( $post_id, '_emdo_uses_default_featured', true ) ) { return; }

    $default_id = (int) get_option( 'emdo_default_blog_featured_attachment_id', 0 );
    if ( $default_id > 0 && (int) $meta_value === $default_id ) { return; }

    delete_post_meta( $post_id, '_emdo_uses_default_featured' );
    delete_post_meta( $post_id, '_emdo_default_featured_hash' );
}
add_action( 'added_post_meta', 'mdo_default_blog_featured_meta_changed_20260827', 10, 4 );
add_action( 'updated_post_meta', 'mdo_default_blog_featured_meta_changed_20260827', 10, 4 );
