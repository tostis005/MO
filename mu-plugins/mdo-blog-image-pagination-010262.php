<?php
/**
 * Plugin Name: MDO - Blog image and English pagination guard 0.10.262
 * Description: Keeps approved editorial images in place and makes /en/journal/ pagination deterministic.
 * Version: 0.10.262
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the requested English Journal page, or 0 outside that route. */
function mdo_english_journal_page_010262(): int {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

	if ( ! preg_match( '#^/en/journal(?:/page/([1-9][0-9]*))?/?$#i', $path, $matches ) ) {
		return 0;
	}

	return ! empty( $matches[1] ) ? max( 1, (int) $matches[1] ) : 1;
}

/**
 * WordPress can fail to resolve the translated paged route before Falang gets a
 * chance to render it. Turn that exact route into the normal posts query.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$page = mdo_english_journal_page_010262();
		if ( $page <= 0 ) {
			return;
		}

		$query->set( 'post_type', 'post' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'paged', $page );
		$query->set( 'page', 0 );
		$query->set( 'page_id', 0 );
		$query->set( 'pagename', '' );
		$query->set( 'name', '' );
		$query->set( 'error', '' );

		$query->is_home     = true;
		$query->is_page     = false;
		$query->is_single   = false;
		$query->is_singular = false;
		$query->is_404      = false;
	},
	-1000
);

/** Do not let canonical redirects strip the /en/journal/ route. */
add_filter(
	'redirect_canonical',
	static function ( $redirect_url, $requested_url ) {
		return mdo_english_journal_page_010262() > 0 ? false : $redirect_url;
	},
	PHP_INT_MAX,
	2
);

/** Suppress a false 404 after the translated route has been normalised. */
add_filter(
	'pre_handle_404',
	static function ( $preempt, WP_Query $query ) {
		if ( mdo_english_journal_page_010262() <= 0 ) {
			return $preempt;
		}

		$query->is_404 = false;
		status_header( 200 );
		return true;
	},
	PHP_INT_MAX,
	2
);

/** Force the blog archive template on translated paginated requests. */
add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( mdo_english_journal_page_010262() <= 0 ) {
			return $template;
		}

		$home_template = get_home_template();
		return is_string( $home_template ) && '' !== $home_template ? $home_template : $template;
	},
	PHP_INT_MAX
);

/** Build pagination links that remain inside the translated Journal. */
add_filter(
	'get_pagenum_link',
	static function ( string $url, int $pagenum ): string {
		if ( mdo_english_journal_page_010262() <= 0 ) {
			return $url;
		}

		$pagenum = max( 1, $pagenum );
		$target  = 1 === $pagenum
			? home_url( '/en/journal/' )
			: home_url( '/en/journal/page/' . $pagenum . '/' );

		$query_args = array();
		if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			foreach ( wp_unslash( $_GET ) as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'paged' === $key || ! is_scalar( $value ) ) {
					continue;
				}
				$query_args[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
			}
		}

		return empty( $query_args ) ? $target : add_query_arg( $query_args, $target );
	},
	PHP_INT_MAX,
	2
);

/**
 * Editorial repair jobs persist an approved attachment id. Prefer it over any
 * stale _thumbnail_id value restored by an older publisher.
 */
add_filter(
	'post_thumbnail_id',
	static function ( $thumbnail_id, $post ) {
		$post_id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
		if ( $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) {
			return $thumbnail_id;
		}

		$approved = (int) get_post_meta( $post_id, '_emdo_editorial_image_approved_id', true );
		if ( $approved <= 0 || 'attachment' !== get_post_type( $approved ) ) {
			return $thumbnail_id;
		}

		return $approved;
	},
	PHP_INT_MAX,
	2
);
