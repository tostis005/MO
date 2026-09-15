<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra la pestaña pública de reseñas de tienda aunque WCFM tenga
 * desactivada su preferencia nativa de vendor reviews.
 */
final class MDO_Reviews_Route {
	private const ROUTE_VERSION = '1.0.0';
	private const ROUTE_OPTION = 'mdo_reviews_route_version';

	public static function init(): void {
		add_action( 'wcfmmp_rewrite_rules_loaded', array( __CLASS__, 'rewrite_rules' ), 50 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ), 50 );
		add_filter( 'wcfmp_store_tabs_url', array( __CLASS__, 'store_tab_url' ), 50, 2 );
		add_filter( 'wcfmp_store_default_query_vars', array( __CLASS__, 'default_query_var' ), 50 );
		add_filter( 'wcfmmp_store_default_template', array( __CLASS__, 'default_template' ), 50, 2 );
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 99 );
	}

	public static function rewrite_rules( string $wcfm_store_url ): void {
		$base = trim( $wcfm_store_url, '/' );
		if ( '' === $base ) {
			return;
		}

		add_rewrite_rule(
			$base . '/([^/]+)/reviews/?$',
			'index.php?' . $base . '=$matches[1]&reviews=true',
			'top'
		);
		add_rewrite_rule(
			$base . '/([^/]+)/reviews/page/?([0-9]{1,})/?$',
			'index.php?' . $base . '=$matches[1]&paged=$matches[2]&reviews=true',
			'top'
		);
	}

	public static function query_vars( array $vars ): array {
		if ( ! in_array( 'reviews', $vars, true ) ) {
			$vars[] = 'reviews';
		}
		return $vars;
	}

	public static function store_tab_url( string $store_tab_url, string $tab ): string {
		if ( 'reviews' === $tab && ! preg_match( '~/reviews/?$~', $store_tab_url ) ) {
			$store_tab_url = trailingslashit( $store_tab_url ) . 'reviews';
		}
		return $store_tab_url;
	}

	public static function default_query_var( $query_var ) {
		if ( get_query_var( 'reviews' ) ) {
			return 'reviews';
		}
		return $query_var;
	}

	public static function default_template( string $template, string $tab ): string {
		if ( 'reviews' === $tab ) {
			return 'store/wcfmmp-view-store-reviews.php';
		}
		return $template;
	}

	public static function maybe_flush_rewrite_rules(): void {
		if ( self::ROUTE_VERSION === (string) get_option( self::ROUTE_OPTION, '' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( self::ROUTE_OPTION, self::ROUTE_VERSION, false );
	}
}
