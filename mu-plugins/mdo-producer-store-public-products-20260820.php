<?php
/**
 * Plugin Name: MDO Producer Store Public Products
 * Description: Keeps public WCFM producer stores scoped to their own products and normalizes the producer CTA.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_public_store_fix_is_store_010261(): bool {
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

function elmercado_public_store_fix_request_slug_010261(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	$parts       = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
	$endpoint    = trim( (string) get_option( 'wcfm_store_url', 'tienda' ), '/' );
	$position    = array_search( $endpoint, $parts, true );
	return false !== $position && isset( $parts[ $position + 1 ] )
		? sanitize_title( rawurldecode( (string) $parts[ $position + 1 ] ) )
		: '';
}

/**
 * Resolve the vendor from the real store URL, including stores whose public
 * store slug does not equal user_nicename.
 */
function elmercado_public_store_fix_vendor_id_010261(): int {
	static $resolved = null;
	if ( null !== $resolved ) {
		return $resolved;
	}

	$resolved = 0;
	$slug     = elmercado_public_store_fix_request_slug_010261();
	if ( '' === $slug ) {
		return 0;
	}

	if ( function_exists( 'elmercado_vendor_store_vendor_id_010225' ) ) {
		$vendor_id = absint( elmercado_vendor_store_vendor_id_010225() );
		if ( $vendor_id > 0 ) {
			$url_slug = function_exists( 'wcfmmp_get_store_url' )
				? sanitize_title( basename( untrailingslashit( (string) wp_parse_url( wcfmmp_get_store_url( $vendor_id ), PHP_URL_PATH ) ) ) )
				: '';
			if ( '' === $url_slug || $url_slug === $slug ) {
				$resolved = $vendor_id;
				return $resolved;
			}
		}
	}

	foreach ( array( 'slug', 'login' ) as $field ) {
		$user = get_user_by( $field, $slug );
		if ( $user instanceof WP_User ) {
			$resolved = absint( $user->ID );
			return $resolved;
		}
	}

	$vendors = get_users(
		array(
			'role__in' => array( 'wcfm_vendor', 'vendor' ),
			'fields'   => array( 'ID', 'user_nicename', 'user_login' ),
		)
	);
	foreach ( $vendors as $vendor ) {
		$vendor_id = absint( $vendor->ID ?? 0 );
		if ( $vendor_id <= 0 ) {
			continue;
		}

		if ( function_exists( 'wcfmmp_get_store_url' ) ) {
			$store_path = (string) wp_parse_url( wcfmmp_get_store_url( $vendor_id ), PHP_URL_PATH );
			$url_slug   = sanitize_title( basename( untrailingslashit( $store_path ) ) );
			if ( $url_slug === $slug ) {
				$resolved = $vendor_id;
				return $resolved;
			}
		}

		$settings = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		if ( is_array( $settings ) ) {
			foreach ( array( 'store_slug', 'store_name' ) as $key ) {
				if ( ! empty( $settings[ $key ] ) && sanitize_title( (string) $settings[ $key ] ) === $slug ) {
					$resolved = $vendor_id;
					return $resolved;
				}
			}
		}
	}

	return 0;
}

function elmercado_public_store_fix_vendor_allowed_010261( int $vendor_id ): bool {
	if ( $vendor_id <= 0 ) {
		return false;
	}
	if ( function_exists( 'elmercado_vendor_store_vendor_is_publicly_allowed_010225' ) ) {
		return elmercado_vendor_store_vendor_is_publicly_allowed_010225( $vendor_id );
	}
	if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) ) {
		return ! elmercado_wcfm_vendor_is_disabled_010210( $vendor_id );
	}
	return true;
}

function elmercado_public_store_fix_is_product_query_010261( WP_Query $query ): bool {
	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type
		|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
		|| $query->is_post_type_archive( 'product' )
		|| $query->is_tax( 'dc_vendor_shop' )
		|| ( $query->is_main_query() && elmercado_public_store_fix_is_store_010261() );
}

/** @return int[] */
function elmercado_public_store_fix_valid_ids_010261( int $vendor_id ): array {
	static $cache = array();
	$vendor_id = absint( $vendor_id );
	if ( isset( $cache[ $vendor_id ] ) ) {
		return $cache[ $vendor_id ];
	}

	$ids = array();
	if ( function_exists( 'elmercado_vendor_store_state_010225' ) ) {
		$state = elmercado_vendor_store_state_010225();
		if ( absint( $state['vendor_id'] ?? 0 ) === $vendor_id ) {
			$ids = array_values( array_filter( array_map( 'absint', (array) ( $state['filtered_ids'] ?? array() ) ) ) );
		}
	}

	if ( ! $ids && function_exists( 'elmercado_vendor_owned_product_ids_010235' ) ) {
		$ids = array_values( array_filter( array_map( 'absint', elmercado_vendor_owned_product_ids_010235( $vendor_id ) ) ) );
	}

	if ( ! $ids ) {
		$ids = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'author'           => $vendor_id,
				'fields'           => 'ids',
				'posts_per_page'   => -1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
	}

	$cache[ $vendor_id ] = array_values( array_unique( $ids ) );
	return $cache[ $vendor_id ];
}

/**
 * Register after the theme/plugins have declared their catalogue callbacks.
 */
add_action(
	'wp_loaded',
	static function (): void {
		add_action(
			'pre_get_posts',
			static function ( WP_Query $query ): void {
				if ( is_admin() || ! elmercado_public_store_fix_is_store_010261() || ! elmercado_public_store_fix_is_product_query_010261( $query ) ) {
					return;
				}

				$vendor_id = elmercado_public_store_fix_vendor_id_010261();
				if ( ! elmercado_public_store_fix_vendor_allowed_010261( $vendor_id ) ) {
					return;
				}

				$query->set( 'post_type', 'product' );
				$query->set( 'post_status', 'publish' );
				$query->set( 'author', $vendor_id );
				$query->set( 'author__not_in', array() );

				/* The visible WCFM catalogue loop must consume the store's own IDs,
				 * not a post__in inherited from the global shop/ranking/destination. */
				if ( $query->is_main_query() || ! $query->get( 'no_found_rows' ) ) {
					$valid_ids = elmercado_public_store_fix_valid_ids_010261( $vendor_id );
					$query->set( 'post__in', $valid_ids ?: array( 0 ) );
					$query->set( 'emo_public_store_loop_010261', 1 );
				}
			},
			PHP_INT_MAX
		);

		add_filter(
			'posts_clauses',
			static function ( array $clauses, WP_Query $query ): array {
				if ( is_admin() || ! $query->get( 'emo_public_store_loop_010261' ) || empty( $clauses['where'] ) ) {
					return $clauses;
				}
				$vendor_id = elmercado_public_store_fix_vendor_id_010261();
				if ( ! elmercado_public_store_fix_vendor_allowed_010261( $vendor_id ) ) {
					return $clauses;
				}

				global $wpdb;
				$table = preg_quote( (string) $wpdb->posts, '~' );

				/* Remove only contradictory author exclusions injected by global catalogue layers. */
				$author_pattern = '~\\s+AND\\s+' . $table . '\\.post_author\\s+NOT\\s+IN\\s*\\(\\s*([0-9\\s,]+)\\s*\\)~i';
				$clauses['where'] = (string) preg_replace_callback(
					$author_pattern,
					static function ( array $matches ) use ( $vendor_id ): string {
						$ids = array_values( array_filter( array_map( 'absint', preg_split( '/\\s*,\\s*/', trim( (string) $matches[1] ) ) ?: array() ) ) );
						return in_array( $vendor_id, $ids, true ) ? '' : (string) $matches[0];
					},
					(string) $clauses['where']
				);

				return $clauses;
			},
			PHP_INT_MAX,
			2
		);

		/* Last-resort consistency guard: never display an empty WCFM store loop
		 * when the same request has a non-empty, validated product ID set. */
		add_filter(
			'the_posts',
			static function ( array $posts, WP_Query $query ): array {
				if ( is_admin() || $posts || ! $query->get( 'emo_public_store_loop_010261' ) ) {
					return $posts;
				}

				$vendor_id = elmercado_public_store_fix_vendor_id_010261();
				$ids       = elmercado_public_store_fix_valid_ids_010261( $vendor_id );
				if ( ! $ids || ! elmercado_public_store_fix_vendor_allowed_010261( $vendor_id ) ) {
					return $posts;
				}

				$per_page = (int) $query->get( 'posts_per_page' );
				if ( $per_page <= 0 ) {
					$per_page = max( 1, (int) get_option( 'posts_per_page', 12 ) );
				}
				$paged  = max( 1, (int) $query->get( 'paged' ), (int) get_query_var( 'paged' ) );
				$offset = ( $paged - 1 ) * $per_page;
				$page_ids = array_slice( $ids, $offset, $per_page );
				$recovered = array();
				foreach ( $page_ids as $product_id ) {
					$post = get_post( $product_id );
					if ( $post instanceof WP_Post && 'product' === $post->post_type && 'publish' === $post->post_status ) {
						$recovered[] = $post;
					}
				}

				if ( $recovered ) {
					$query->found_posts   = count( $ids );
					$query->max_num_pages = (int) ceil( count( $ids ) / $per_page );
					return $recovered;
				}
				return $posts;
			},
			PHP_INT_MAX,
			2
		);
	},
	PHP_INT_MAX
);

function elmercado_public_store_fix_visit_label_010261( string $translated, string $text, string $domain ): string {
	if ( is_admin() || ! in_array( $domain, array( 'wc-multivendor-marketplace', 'wc-frontend-manager' ), true ) ) {
		return $translated;
	}
	if ( 'Visit Store' !== trim( $text ) && 'Visit Store' !== trim( $translated ) ) {
		return $translated;
	}
	return 'Visit';
}
add_filter( 'gettext', 'elmercado_public_store_fix_visit_label_010261', PHP_INT_MAX, 3 );
