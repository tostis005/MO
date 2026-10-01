<?php
/**
 * One-shot web-SAPI opcode cache reset used only during deployment.
 * The workflow copies this file to a random production filename, requests it
 * once, then immediately deletes it.
 */
header( 'Content-Type: application/json; charset=UTF-8' );

$target = __DIR__ . '/wp-content/themes/elmercadodeorigen-child/page-categorias.php';
$result = array(
	'target'     => $target,
	'readable'   => is_readable( $target ),
	'md5'        => is_readable( $target ) ? md5_file( $target ) : '',
	'invalidate' => function_exists( 'opcache_invalidate' ) ? opcache_invalidate( $target, true ) : null,
	'reset'      => function_exists( 'opcache_reset' ) ? opcache_reset() : null,
);

echo json_encode( $result, JSON_UNESCAPED_SLASHES );
