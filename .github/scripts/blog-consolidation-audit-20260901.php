<?php
/**
 * Read-only inventory for the blog consolidation audit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'suppress_filters' => false,
	)
);

echo "BLOG_CONSOLIDATION_INVENTORY_BEGIN\n";
foreach ( $posts as $post ) {
	$cats = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
	$row  = array(
		'id'         => (int) $post->ID,
		'slug'       => $post->post_name,
		'title'      => get_the_title( $post ),
		'date'       => get_post_time( 'c', false, $post ),
		'modified'   => get_post_modified_time( 'c', false, $post ),
		'categories' => array_values( $cats ),
		'url'        => get_permalink( $post ),
		'words'      => str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ),
	);
	echo wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
echo "BLOG_CONSOLIDATION_INVENTORY_END\n";
