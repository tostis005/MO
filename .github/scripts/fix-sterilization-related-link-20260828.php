<?php
if ( ! defined( 'ABSPATH' ) ) { return; }
$post = get_page_by_path( 'esterilizacion-conservas-vegetales-por-que-duran-despensa', OBJECT, 'post' );
if ( ! $post instanceof WP_Post ) { throw new RuntimeException( 'Target post not found.' ); }
$content = (string) $post->post_content;
$old = '>Cómo elegir buenas conservas vegetales</a>';
$new = '>Conservas en tarro de vidrio o lata: diferencias de conservación y uso</a>';
$updated = str_replace( $old, $new, $content, $count );
if ( $count > 0 ) {
	$result = wp_update_post( wp_slash( array( 'ID' => (int) $post->ID, 'post_content' => $updated ) ), true );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	clean_post_cache( (int) $post->ID );
}
echo wp_json_encode( array( 'ok' => true, 'replacements' => $count, 'permalink' => get_permalink( (int) $post->ID ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
