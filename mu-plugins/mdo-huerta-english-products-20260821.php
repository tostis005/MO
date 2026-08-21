<?php
/**
 * Plugin Name: MDO Huerta English Products
 * Description: Serves La Huerta de Ana Mari product titles, descriptions and product URLs from the persisted reviewed English metadata on English storefront requests.
 * Version: 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdohep_is_english_20260821(): bool {
	if ( function_exists( 'mdoer_en' ) && mdoer_en() ) { return true; }
	if ( function_exists( 'mdo_en_is_request' ) && mdo_en_is_request() ) { return true; }
	$uri = (string) ( $GLOBALS['mdoer_public_request_uri'] ?? ( $_SERVER['REQUEST_URI'] ?? '' ) );
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return $path === '/en' || 0 === strpos( $path, '/en/' );
}

function mdohep_source_table_20260821(): string {
	global $wpdb;
	return class_exists( 'MDO_Database' ) ? MDO_Database::table( 'source_products' ) : $wpdb->prefix . 'mdo_source_products';
}

function mdohep_is_huerta_product_20260821( int $product_id ): bool {
	static $cache = array();
	if ( isset( $cache[ $product_id ] ) ) { return $cache[ $product_id ]; }
	if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) { return $cache[ $product_id ] = false; }
	global $wpdb;
	$table = mdohep_source_table_20260821();
	$found = $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE wc_product_id=%d AND source_url LIKE %s LIMIT 1", $product_id, '%lahuertadeanamary.com%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	return $cache[ $product_id ] = ( '1' === (string) $found );
}

function mdohep_has_english_20260821( int $product_id ): bool {
	return mdohep_is_huerta_product_20260821( $product_id ) && '1' === (string) get_post_meta( $product_id, '_en_US_published', true );
}

function mdohep_meta_20260821( int $product_id, string $key ): string {
	if ( ! mdohep_has_english_20260821( $product_id ) ) { return ''; }
	return (string) get_post_meta( $product_id, $key, true );
}

function mdohep_title_20260821( int $product_id ): string {
	return trim( wp_strip_all_tags( mdohep_meta_20260821( $product_id, '_en_US_post_title' ) ) );
}

function mdohep_content_20260821( int $product_id ): string {
	return mdohep_meta_20260821( $product_id, '_en_US_post_content' );
}

function mdohep_excerpt_20260821( int $product_id ): string {
	return mdohep_meta_20260821( $product_id, '_en_US_post_excerpt' );
}

function mdohep_slug_20260821( int $product_id ): string {
	return sanitize_title( wp_strip_all_tags( mdohep_meta_20260821( $product_id, '_en_US_post_name' ) ) );
}

function mdohep_url_20260821( int $product_id ): string {
	$slug = mdohep_slug_20260821( $product_id );
	if ( '' === $slug ) { return ''; }
	if ( function_exists( 'mdo_en_product_url' ) ) {
		$url = (string) mdo_en_product_url( $product_id );
		if ( '' !== $url ) { return $url; }
	}
	return rtrim( (string) get_option( 'home' ), '/' ) . '/en/product/' . rawurlencode( $slug ) . '/';
}

add_filter( 'the_title', static function ( string $title, int $post_id = 0 ): string {
	if ( ! mdohep_is_english_20260821() || ! mdohep_has_english_20260821( $post_id ) ) { return $title; }
	$english = mdohep_title_20260821( $post_id );
	return '' !== $english ? $english : $title;
}, PHP_INT_MAX, 2 );

add_filter( 'the_content', static function ( string $content ): string {
	if ( ! mdohep_is_english_20260821() ) { return $content; }
	$post = $GLOBALS['post'] ?? null;
	if ( ! $post instanceof WP_Post || ! mdohep_has_english_20260821( (int) $post->ID ) ) { return $content; }
	$english = mdohep_content_20260821( (int) $post->ID );
	return '' !== trim( wp_strip_all_tags( $english ) ) ? $english : $content;
}, -9999 );

add_filter( 'get_the_excerpt', static function ( string $excerpt, $post ): string {
	if ( ! mdohep_is_english_20260821() ) { return $excerpt; }
	$id = $post instanceof WP_Post ? (int) $post->ID : absint( $post );
	if ( ! mdohep_has_english_20260821( $id ) ) { return $excerpt; }
	$english = mdohep_excerpt_20260821( $id );
	if ( '' === trim( wp_strip_all_tags( $english ) ) ) {
		$english = wp_trim_words( wp_strip_all_tags( mdohep_content_20260821( $id ) ), 34, '…' );
	}
	return '' !== trim( $english ) ? $english : $excerpt;
}, PHP_INT_MAX, 2 );

add_filter( 'woocommerce_short_description', static function ( string $description ): string {
	if ( ! mdohep_is_english_20260821() ) { return $description; }
	$post = $GLOBALS['post'] ?? null;
	if ( ! $post instanceof WP_Post || ! mdohep_has_english_20260821( (int) $post->ID ) ) { return $description; }
	$english = mdohep_excerpt_20260821( (int) $post->ID );
	return '' !== trim( wp_strip_all_tags( $english ) ) ? $english : $description;
}, PHP_INT_MAX );

foreach ( array(
	'woocommerce_product_get_name'              => '_en_US_post_title',
	'woocommerce_product_get_description'       => '_en_US_post_content',
	'woocommerce_product_get_short_description' => '_en_US_post_excerpt',
) as $hook => $meta_key ) {
	add_filter( $hook, static function ( $value, $product ) use ( $meta_key ) {
		if ( ! mdohep_is_english_20260821() || ! $product instanceof WC_Product ) { return $value; }
		$id = (int) $product->get_id();
		if ( ! mdohep_has_english_20260821( $id ) ) { return $value; }
		$english = mdohep_meta_20260821( $id, $meta_key );
		if ( '_en_US_post_title' === $meta_key ) { $english = trim( wp_strip_all_tags( $english ) ); }
		return '' !== trim( wp_strip_all_tags( $english ) ) ? $english : $value;
	}, PHP_INT_MAX, 2 );
}

add_filter( 'post_type_link', static function ( string $url, WP_Post $post ): string {
	if ( ! mdohep_is_english_20260821() || 'product' !== $post->post_type || ! mdohep_has_english_20260821( (int) $post->ID ) ) { return $url; }
	$english = mdohep_url_20260821( (int) $post->ID );
	return '' !== $english ? $english : $url;
}, PHP_INT_MAX, 2 );

add_filter( 'woocommerce_loop_product_link', static function ( string $url, $product ): string {
	if ( ! mdohep_is_english_20260821() || ! $product instanceof WC_Product ) { return $url; }
	$id = (int) $product->get_id();
	if ( ! mdohep_has_english_20260821( $id ) ) { return $url; }
	$english = mdohep_url_20260821( $id );
	return '' !== $english ? $english : $url;
}, PHP_INT_MAX, 2 );

function mdohep_render_fallback_20260821( string $html ): string {
	if ( '' === $html || ! mdohep_is_english_20260821() ) { return $html; }
	static $rows = null;
	if ( null === $rows ) {
		global $wpdb;
		$source_table = mdohep_source_table_20260821();
		$rows = $wpdb->get_results(
			"SELECT DISTINCT p.ID,p.post_title,p.post_name
			 FROM {$wpdb->posts} p
			 INNER JOIN {$source_table} src ON src.wc_product_id=p.ID AND src.source_url LIKE '%lahuertadeanamary.com%'
			 INNER JOIN {$wpdb->postmeta} pub ON pub.post_id=p.ID AND pub.meta_key='_en_US_published' AND pub.meta_value='1'
			 WHERE p.post_type='product' AND p.post_status='publish'",
			ARRAY_A
		) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}
	$home = rtrim( (string) get_option( 'home' ), '/' );
	$perms = (array) get_option( 'woocommerce_permalinks', array() );
	$product_base = trim( (string) ( $perms['product_base'] ?? '/producto' ), '/' );
	if ( '' === $product_base ) { $product_base = 'producto'; }

	foreach ( $rows as $row ) {
		$id = (int) $row['ID'];
		$english_title = mdohep_title_20260821( $id );
		$english_url   = mdohep_url_20260821( $id );
		$english_slug  = mdohep_slug_20260821( $id );
		$native_title  = (string) $row['post_title'];
		$native_slug   = (string) $row['post_name'];
		if ( '' !== $english_title && '' !== $native_title && $english_title !== $native_title ) {
			$html = str_replace( $native_title, $english_title, $html );
		}
		if ( '' === $english_url || '' === $native_slug ) { continue; }
		$native_urls = array(
			$home . '/' . $product_base . '/' . rawurlencode( $native_slug ) . '/',
			$home . '/producto/' . rawurlencode( $native_slug ) . '/',
			$home . '/product/' . rawurlencode( $native_slug ) . '/',
			$home . '/en/' . $product_base . '/' . rawurlencode( $native_slug ) . '/',
			$home . '/en/producto/' . rawurlencode( $native_slug ) . '/',
		);
		foreach ( array_unique( $native_urls ) as $native_url ) {
			if ( $native_url !== $english_url ) {
				$html = str_replace( array( $native_url, esc_url( $native_url ) ), array( $english_url, esc_url( $english_url ) ), $html );
			}
		}
		if ( '' !== $english_slug && $english_slug !== $native_slug ) {
			$html = str_replace(
				array( '/producto/' . $native_slug . '/', '/en/producto/' . $native_slug . '/', '/product/' . $native_slug . '/' ),
				array( '/en/product/' . $english_slug . '/', '/en/product/' . $english_slug . '/', '/en/product/' . $english_slug . '/' ),
				$html
			);
		}
	}
	return $html;
}

add_action( 'template_redirect', static function (): void {
	if ( is_admin() || wp_doing_ajax() || ! mdohep_is_english_20260821() ) { return; }
	ob_start( 'mdohep_render_fallback_20260821' );
}, 210 );
