<?php
/**
 * MU bootstrap for the blog-wide image SEO layer.
 * Keeps the production activation independent from the current child-theme bootstrap.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$module = WP_CONTENT_DIR . '/themes/elmercadodeorigen-child/inc/blog-image-seo-010266.php';
if ( is_readable( $module ) ) {
	require_once $module;
}
