<?php
/** Complete missing ALT metadata for images used by published blog posts. */
if ( ! defined( 'ABSPATH' ) ) { return; }
if ( ! function_exists( 'elmercado_blog_image_alt_010266' ) ) { throw new RuntimeException( 'Blog image SEO module is not loaded.' ); }

$posts = get_posts( array(
	'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1,
	'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true,
) );
$seen = array();
$result = array(
	'posts_scanned' => 0, 'unique_attachments' => 0, 'alt_added' => 0,
	'alt_preserved' => 0, 'unresolved' => 0, 'external_images' => 0,
	'blog_url' => '', 'sample_post_url' => '', 'items' => array(),
);

$apply = static function ( int $attachment_id, int $post_id, string $source ) use ( &$seen, &$result ): void {
	if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) { $result['unresolved']++; return; }
	if ( isset( $seen[ $attachment_id ] ) ) { return; }
	$seen[ $attachment_id ] = true;
	$result['unique_attachments']++;
	$current = elmercado_clean_image_alt_010266( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	if ( '' !== $current ) {
		$result['alt_preserved']++;
		$result['items'][] = array( 'id' => $attachment_id, 'post_id' => $post_id, 'source' => $source, 'action' => 'preserved', 'alt' => $current );
		return;
	}
	$alt = elmercado_blog_image_alt_010266( $attachment_id, $post_id );
	if ( '' === $alt ) {
		$result['unresolved']++;
		$result['items'][] = array( 'id' => $attachment_id, 'post_id' => $post_id, 'source' => $source, 'action' => 'unresolved', 'alt' => '' );
		return;
	}
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	$result['alt_added']++;
	$result['items'][] = array( 'id' => $attachment_id, 'post_id' => $post_id, 'source' => $source, 'action' => 'added', 'alt' => $alt );
};

foreach ( $posts as $post_id ) {
	$post_id = (int) $post_id;
	$result['posts_scanned']++;
	$featured = (int) get_post_thumbnail_id( $post_id );
	if ( $featured ) { $apply( $featured, $post_id, 'featured' ); }
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match_all( '/wp-image-(\d+)/i', $content, $matches ) ) {
		foreach ( array_unique( array_map( 'intval', $matches[1] ) ) as $id ) { $apply( $id, $post_id, 'content' ); }
	}
	if ( preg_match_all( '/<img\b[^>]*\bsrc=["\x27]([^"\x27]+)["\x27]/i', $content, $sources ) ) {
		foreach ( $sources[1] as $src ) {
			$id = (int) attachment_url_to_postid( html_entity_decode( $src, ENT_QUOTES, 'UTF-8' ) );
			if ( $id ) { $apply( $id, $post_id, 'content-src' ); }
			elseif ( false === strpos( $src, (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) { $result['external_images']++; }
		}
	}
}

$page_for_posts = (int) get_option( 'page_for_posts' );
$result['blog_url'] = $page_for_posts > 0 ? get_permalink( $page_for_posts ) : home_url( '/blog/' );
$sample_id = ! empty( $posts ) ? (int) end( $posts ) : 0;
$result['sample_post_url'] = $sample_id > 0 ? get_permalink( $sample_id ) : '';
$result['ok'] = $result['posts_scanned'] > 0;
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
