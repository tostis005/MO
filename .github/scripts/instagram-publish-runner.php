<?php
/**
 * Runs inside an already bootstrapped WordPress instance via `wp eval-file`.
 * Reads a JSON queue file and delegates publishing to the EMDO Instagram MU plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress is not loaded.\n" );
	exit( 2 );
}

if ( ! function_exists( 'emdo_instagram_publish_image' ) ) {
	fwrite( STDERR, "EMDO Instagram publisher is not loaded.\n" );
	exit( 3 );
}

$queue_file = getenv( 'EMDO_IG_QUEUE_FILE' );
if ( ! $queue_file || ! is_readable( $queue_file ) ) {
	fwrite( STDERR, "Instagram queue file is missing or unreadable.\n" );
	exit( 4 );
}

$payload = json_decode( file_get_contents( $queue_file ), true );
if ( ! is_array( $payload ) ) {
	fwrite( STDERR, "Instagram queue JSON is invalid.\n" );
	exit( 5 );
}

if ( empty( $payload['publish'] ) ) {
	echo wp_json_encode( array( 'status' => 'skipped', 'reason' => 'publish=false' ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
	exit( 0 );
}

$request_id = isset( $payload['request_id'] ) ? sanitize_key( (string) $payload['request_id'] ) : '';
$image_url  = isset( $payload['image_url'] ) ? esc_url_raw( (string) $payload['image_url'] ) : '';
$caption    = isset( $payload['caption'] ) ? (string) $payload['caption'] : '';

if ( '' === $request_id || '' === $image_url || '' === trim( $caption ) ) {
	fwrite( STDERR, "request_id, image_url and caption are required.\n" );
	exit( 6 );
}

$last_request = (string) get_option( 'emdo_ig_last_queue_request_id', '' );
if ( hash_equals( $last_request, $request_id ) ) {
	echo wp_json_encode( array( 'status' => 'skipped', 'reason' => 'duplicate_request_id', 'request_id' => $request_id ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
	exit( 0 );
}

$result = emdo_instagram_publish_image( $image_url, $caption );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, 'Instagram publish failed: ' . $result->get_error_message() . "\n" );
	exit( 7 );
}

update_option( 'emdo_ig_last_queue_request_id', $request_id, false );

$output = array(
	'status'     => 'published',
	'request_id' => $request_id,
	'media_id'   => isset( $result['media_id'] ) ? $result['media_id'] : '',
	'username'   => isset( $result['username'] ) ? $result['username'] : '',
);

echo wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
