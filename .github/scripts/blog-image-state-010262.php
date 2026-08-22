<?php
/** Backup/restore the small set of posts touched by the blog image repair. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$mode = (string) getenv( 'MDO_STATE_MODE' );
$file = (string) getenv( 'MDO_STATE_FILE' );
$authority_file = (string) getenv( 'MDO_AUTHORITY_IMAGE_OVERRIDES' );
$legacy_file = (string) getenv( 'MDO_LEGACY_IMAGE_OVERRIDES' );
if ( ! in_array( $mode, array( 'backup', 'restore' ), true ) ) { throw new RuntimeException( 'Invalid state mode.' ); }
if ( '' === $file ) { throw new RuntimeException( 'MDO_STATE_FILE missing.' ); }

$meta_keys = array(
	'_thumbnail_id',
	'_emdo_editorial_image_approved_id',
	'_emdo_editorial_image_approved_pexels_id',
	'_emdo_editorial_image_approved_at',
	'_emdo_editorial_image_override',
	'_emdo_featured_from_product',
	'_emdo_featured_image_repaired',
);

if ( 'backup' === $mode ) {
	if ( ! is_file( $authority_file ) || ! is_file( $legacy_file ) ) { throw new RuntimeException( 'Override files required for backup.' ); }
	$authority = require $authority_file;
	$legacy = require $legacy_file;
	$ids = array();
	foreach ( array_keys( $authority ) as $key ) {
		$matches = get_posts( array( 'post_type'=>'post', 'post_status'=>'publish', 'posts_per_page'=>1, 'fields'=>'ids', 'meta_key'=>'_emdo_authority_key', 'meta_value'=>(string)$key ) );
		if ( ! empty( $matches ) ) { $ids[] = (int) $matches[0]; }
	}
	$legacy_slugs = array_values(
		array_unique(
			array_merge(
				array_keys( $legacy ),
				array(
					'naranjas',
					'jamon-o-paleta-diferencias-cual-elegir',
					'jamon-pieza-entera-o-loncheado-como-elegir',
					'que-significa-aceite-oliva-virgen-extra',
				)
			)
		)
	);
	foreach ( $legacy_slugs as $slug ) {
		$post = get_page_by_path( (string) $slug, OBJECT, 'post' );
		if ( $post instanceof WP_Post ) { $ids[] = (int) $post->ID; }
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	$state = array( 'created_at'=>gmdate('c'), 'posts'=>array() );
	foreach ( $ids as $post_id ) {
		$row = array(
			'id'=>$post_id,
			'post_content'=>(string)get_post_field( 'post_content', $post_id ),
			'meta'=>array(),
		);
		foreach ( $meta_keys as $key ) {
			$row['meta'][ $key ] = array(
				'exists'=>metadata_exists( 'post', $post_id, $key ),
				'value'=>get_post_meta( $post_id, $key, true ),
			);
		}
		$state['posts'][] = $row;
	}
	$json = wp_json_encode( $state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	if ( ! is_string( $json ) || false === file_put_contents( $file, $json ) ) { throw new RuntimeException( 'Could not write state backup.' ); }
	echo 'BACKUP_OK posts=' . count( $state['posts'] ) . PHP_EOL;
	return;
}

if ( ! is_file( $file ) ) { throw new RuntimeException( 'State backup missing for restore.' ); }
$state = json_decode( (string) file_get_contents( $file ), true );
if ( ! is_array( $state ) || empty( $state['posts'] ) || ! is_array( $state['posts'] ) ) { throw new RuntimeException( 'Invalid state backup.' ); }
foreach ( $state['posts'] as $row ) {
	$post_id = (int) ( $row['id'] ?? 0 );
	if ( $post_id <= 0 || ! get_post( $post_id ) ) { continue; }
	$result = wp_update_post( array( 'ID'=>$post_id, 'post_content'=>(string)( $row['post_content'] ?? '' ) ), true );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( 'Restore post ' . $post_id . ': ' . $result->get_error_message() ); }
	foreach ( $meta_keys as $key ) {
		$entry = $row['meta'][ $key ] ?? array( 'exists'=>false, 'value'=>'' );
		if ( empty( $entry['exists'] ) ) { delete_post_meta( $post_id, $key ); }
		else { update_post_meta( $post_id, $key, $entry['value'] ); }
	}
}
echo 'RESTORE_OK posts=' . count( $state['posts'] ) . PHP_EOL;
