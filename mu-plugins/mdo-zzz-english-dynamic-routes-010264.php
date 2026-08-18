<?php
/**
 * Plugin Name: MDO English Dynamic Routes
 * Description: Completes English pagination and WooCommerce account/checkout endpoint routing without changing Spanish permalinks.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdodr_en_010264(): bool {
	if ( function_exists( 'mdo_en_is_request' ) ) { return mdo_en_is_request(); }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return $path === '/en' || str_starts_with( $path, '/en/' );
}

function mdodr_parts_010264(): array {
	if ( function_exists( 'mdo_en_segments' ) ) { return mdo_en_segments(); }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$parts = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	if ( isset( $parts[0] ) && strtolower( (string) $parts[0] ) === 'en' ) { array_shift( $parts ); }
	return array_map( 'sanitize_title', $parts );
}

function mdodr_clear_010264( WP $wp ): void {
	foreach ( array( 'error','name','pagename','page_id','p','page','attachment','product','product_cat','product_tag','category_name','cat','tag' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
}

function mdodr_shop_slug_010264(): string {
	$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : (int) get_option( 'woocommerce_shop_page_id' );
	if ( $id > 0 && function_exists( 'mdo_en_slug_for_post' ) ) { $slug = mdo_en_slug_for_post( $id ); if ( '' !== $slug ) { return $slug; } }
	return 'shop';
}

function mdodr_myaccount_slug_010264(): string {
	$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'myaccount' ) : (int) get_option( 'woocommerce_myaccount_page_id' );
	if ( $id > 0 && function_exists( 'mdo_en_slug_for_post' ) ) { $slug = mdo_en_slug_for_post( $id ); if ( '' !== $slug ) { return $slug; } }
	return 'my-account';
}

function mdodr_checkout_slug_010264(): string {
	$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : (int) get_option( 'woocommerce_checkout_page_id' );
	if ( $id > 0 && function_exists( 'mdo_en_slug_for_post' ) ) { $slug = mdo_en_slug_for_post( $id ); if ( '' !== $slug ) { return $slug; } }
	return 'checkout';
}

function mdodr_wc_endpoint_keys_010264(): array {
	$keys = array( 'orders','view-order','downloads','edit-account','edit-address','payment-methods','add-payment-method','delete-payment-method','set-default-payment-method','lost-password','customer-logout','order-pay','order-received' );
	if ( function_exists( 'WC' ) && WC() && isset( WC()->query ) ) {
		foreach ( array_keys( (array) WC()->query->get_query_vars() ) as $key ) { $key = sanitize_title( (string) $key ); if ( '' !== $key ) { $keys[] = $key; } }
	}
	return array_values( array_unique( $keys ) );
}

function mdodr_for_producers_url_010264(): string {
	$id = function_exists( 'mdo_en_find_by_slug' ) ? mdo_en_find_by_slug( 'page', 'for-producers' ) : 0;
	if ( $id && function_exists( 'mdo_en_page_url' ) ) { $url = mdo_en_page_url( $id ); if ( '' !== $url ) { return $url; } }
	return rtrim( (string) get_option( 'home' ), '/' ) . '/en/for-producers/';
}

add_action( 'parse_request', static function ( WP $wp ): void {
	if ( ! mdodr_en_010264() ) { return; }
	$parts = mdodr_parts_010264();

	if ( count( $parts ) === 3 && $parts[0] === mdodr_shop_slug_010264() && $parts[1] === 'page' && ctype_digit( $parts[2] ) && (int) $parts[2] > 0 ) {
		mdodr_clear_010264( $wp ); $wp->query_vars['post_type'] = 'product'; $wp->query_vars['paged'] = (int) $parts[2]; return;
	}

	if ( count( $parts ) === 4 && $parts[2] === 'page' && ctype_digit( $parts[3] ) && (int) $parts[3] > 0 && function_exists( 'mdo_en_taxonomy_for_base' ) && function_exists( 'mdo_en_find_term_by_slug' ) ) {
		$taxonomy = mdo_en_taxonomy_for_base( $parts[0] ); $term = '' !== $taxonomy ? mdo_en_find_term_by_slug( $taxonomy, $parts[1] ) : null;
		if ( $term instanceof WP_Term ) {
			mdodr_clear_010264( $wp ); $wp->query_vars['paged'] = (int) $parts[3];
			if ( 'product_cat' === $taxonomy ) { $wp->query_vars['product_cat'] = $term->slug; }
			elseif ( 'product_tag' === $taxonomy ) { $wp->query_vars['product_tag'] = $term->slug; }
			elseif ( 'category' === $taxonomy ) { $wp->query_vars['category_name'] = $term->slug; }
			return;
		}
	}

	if ( count( $parts ) >= 2 && count( $parts ) <= 3 && $parts[0] === mdodr_myaccount_slug_010264() && in_array( $parts[1], mdodr_wc_endpoint_keys_010264(), true ) ) {
		$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'myaccount' ) : (int) get_option( 'woocommerce_myaccount_page_id' );
		if ( $id > 0 ) { mdodr_clear_010264( $wp ); unset( $wp->query_vars['post_type'] ); $wp->query_vars['page_id'] = $id; $wp->query_vars[ $parts[1] ] = $parts[2] ?? ''; return; }
	}

	if ( count( $parts ) === 3 && $parts[0] === mdodr_checkout_slug_010264() && in_array( $parts[1], array( 'order-pay','order-received' ), true ) ) {
		$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : (int) get_option( 'woocommerce_checkout_page_id' );
		if ( $id > 0 ) { mdodr_clear_010264( $wp ); unset( $wp->query_vars['post_type'] ); $wp->query_vars['page_id'] = $id; $wp->query_vars[ $parts[1] ] = $parts[2]; }
	}
}, PHP_INT_MAX );

add_filter( 'redirect_canonical', static function ( $redirect ) {
	if ( ! mdodr_en_010264() ) { return $redirect; }
	$parts = mdodr_parts_010264();
	if ( count( $parts ) === 3 && $parts[0] === mdodr_shop_slug_010264() && $parts[1] === 'page' && ctype_digit( $parts[2] ) ) { return false; }
	if ( count( $parts ) === 4 && $parts[2] === 'page' && ctype_digit( $parts[3] ) && function_exists( 'mdo_en_taxonomy_for_base' ) && function_exists( 'mdo_en_find_term_by_slug' ) ) {
		$taxonomy = mdo_en_taxonomy_for_base( $parts[0] ); if ( '' !== $taxonomy && mdo_en_find_term_by_slug( $taxonomy, $parts[1] ) instanceof WP_Term ) { return false; }
	}
	if ( count( $parts ) >= 2 && count( $parts ) <= 3 && $parts[0] === mdodr_myaccount_slug_010264() && in_array( $parts[1], mdodr_wc_endpoint_keys_010264(), true ) ) { return false; }
	if ( count( $parts ) === 3 && $parts[0] === mdodr_checkout_slug_010264() && in_array( $parts[1], array( 'order-pay','order-received' ), true ) ) { return false; }
	return $redirect;
}, PHP_INT_MAX );

/* WCFM is configured to page 1141 for registration, but that page is intentionally draft. Do not expose it: route the public English CTA to the live producer-contact page. */
add_action( 'template_redirect', static function (): void {
	if ( ! mdodr_en_010264() || is_admin() || wp_doing_ajax() ) { return; }
	$parts = mdodr_parts_010264();
	if ( count( $parts ) === 1 && $parts[0] === 'vendor-registration' ) { wp_safe_redirect( mdodr_for_producers_url_010264(), 301, 'MDO closed vendor registration' ); exit; }
}, 0 );

add_action( 'template_redirect', static function (): void {
	if ( ! mdodr_en_010264() || is_admin() || wp_doing_ajax() ) { return; }
	ob_start( static function ( string $html ): string {
		$target = esc_url( mdodr_for_producers_url_010264() );
		$html = (string) preg_replace_callback(
			'#<a\b([^>]*?)href=("|\')([^"\']*/en/vendor-registration/?)\2([^>]*)>(.*?)</a>#isu',
			static function ( array $m ) use ( $target ): string {
				$label = trim( wp_strip_all_tags( $m[5] ) );
				$inner = preg_match( '/vendor\s+registration/i', $label ) ? 'For producers' : $m[5];
				return '<a' . $m[1] . 'href=' . $m[2] . $target . $m[2] . $m[4] . '>' . $inner . '</a>';
			},
			$html
		);
		return $html;
	} );
}, 180 );
