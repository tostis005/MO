<?php
/**
 * Puente de entrada para posts: activa los bloques editoriales comerciales y
 * conserva intacta la plantilla single.php existente.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inline_commerce_module = __DIR__ . '/inc/blog-inline-commerce-010268.php';
if ( is_readable( $inline_commerce_module ) ) {
	require_once $inline_commerce_module;

	if ( function_exists( 'elmercado_blog_maybe_handle_subscription' ) ) {
		elmercado_blog_maybe_handle_subscription();
	}

	if ( function_exists( 'elmercado_blog_inject_inline_commercial_blocks' ) ) {
		add_filter( 'the_content', 'elmercado_blog_inject_inline_commercial_blocks', 35 );
	}
}

require __DIR__ . '/single.php';
