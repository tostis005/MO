<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deterministic request fallback for the English Specials routes.
 *
 * Some multilingual rewrite stacks can consume /en/* before custom rewrite rules
 * are evaluated. This router normalizes only the two EMDO Specials English paths
 * at parse_request time, leaving the rest of the site's language routing untouched.
 */
final class MDO_Specials_Router {
	private const POST_TYPE = 'mdo_promotion';

	public static function init(): void {
		add_action( 'parse_request', array( __CLASS__, 'parse_request' ), 1 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'disable_canonical' ), 5, 2 );
	}

	public static function parse_request( WP $wp ): void {
		if ( is_admin() ) {
			return;
		}

		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( 'en/specials' === $path ) {
			$wp->query_vars = array(
				'post_type'         => self::POST_TYPE,
				'post_status'       => 'publish',
				'mdo_specials_lang' => 'en',
			);
			$wp->matched_rule  = 'emdo-en-specials-archive';
			$wp->matched_query = 'post_type=' . self::POST_TYPE . '&mdo_specials_lang=en';
			return;
		}

		if ( ! preg_match( '#^en/specials/([^/]+)$#', $path, $matches ) ) {
			return;
		}

		$slug = sanitize_title( rawurldecode( (string) $matches[1] ) );
		$ids  = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mdo_promo_slug_en',
				'meta_value'     => $slug,
			)
		);
		if ( ! $ids ) {
			return;
		}

		$wp->query_vars = array(
			'post_type'         => self::POST_TYPE,
			'p'                 => (int) $ids[0],
			'post_status'       => 'publish',
			'mdo_specials_lang' => 'en',
		);
		$wp->matched_rule  = 'emdo-en-specials-single';
		$wp->matched_query = 'post_type=' . self::POST_TYPE . '&p=' . (int) $ids[0] . '&mdo_specials_lang=en';
	}

	public static function disable_canonical( $redirect, $requested ) {
		unset( $requested );
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( 'en/specials' === $path || 0 === strpos( $path, 'en/specials/' ) ) {
			return false;
		}
		return $redirect;
	}
}
