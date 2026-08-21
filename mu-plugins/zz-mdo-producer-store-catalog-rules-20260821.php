<?php
/**
 * Plugin Name: MDO Producer Store Catalog Rules
 * Description: Reuses EMDO destination eligibility and recommended ranking inside public producer storefronts.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_store_catalog_is_request_20260821(): bool {
	if ( function_exists( 'elmercado_public_store_fix_is_store_010261' ) ) {
		return elmercado_public_store_fix_is_store_010261();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

function mdo_producer_store_catalog_vendor_id_20260821(): int {
	return function_exists( 'elmercado_public_store_fix_vendor_id_010261' )
		? absint( elmercado_public_store_fix_vendor_id_010261() )
		: 0;
}

function mdo_producer_store_catalog_vendor_allowed_20260821( int $vendor_id ): bool {
	return function_exists( 'elmercado_public_store_fix_vendor_allowed_010261' )
		? elmercado_public_store_fix_vendor_allowed_010261( $vendor_id )
		: $vendor_id > 0;
}

function mdo_producer_store_catalog_is_product_query_20260821( WP_Query $query ): bool {
	if ( function_exists( 'elmercado_public_store_fix_is_product_query_010261' ) ) {
		return elmercado_public_store_fix_is_product_query_010261( $query );
	}
	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type
		|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
		|| $query->is_post_type_archive( 'product' )
		|| $query->is_main_query();
}

/** @return int[] */
function mdo_producer_store_catalog_owned_ids_20260821( int $vendor_id ): array {
	$vendor_id = absint( $vendor_id );
	if ( ! $vendor_id ) {
		return array();
	}
	if ( function_exists( 'elmercado_public_store_fix_valid_ids_010261' ) ) {
		return array_values( array_unique( array_filter( array_map( 'absint', elmercado_public_store_fix_valid_ids_010261( $vendor_id ) ) ) ) );
	}
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
	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * Pure catalogue resolver used by the frontend and deployment verification.
 * It never mixes products from another producer.
 *
 * @return int[]
 */
function mdo_producer_store_catalog_ids_for_destination_20260821(
	int $vendor_id,
	string $country,
	string $postcode = '',
	bool $recommended = true
): array {
	$vendor_id = absint( $vendor_id );
	if ( ! $vendor_id || ! mdo_producer_store_catalog_vendor_allowed_20260821( $vendor_id ) ) {
		return array();
	}

	$ids = mdo_producer_store_catalog_owned_ids_20260821( $vendor_id );
	if ( ! $ids ) {
		return array();
	}

	if ( class_exists( 'MDO_Shipping_Destinations' ) && ! MDO_Shipping_Destinations::vendor_can_ship_to( $vendor_id, $country, $postcode ) ) {
		return array();
	}

	if ( $recommended && class_exists( 'MDO_Catalog_Ranking' ) ) {
		$ids = MDO_Catalog_Ranking::rank_products(
			$ids,
			array(
				'rotation_seed'      => gmdate( 'Y-m-d' ),
				'diversify_vendors' => true,
			)
		);
	}

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

function mdo_producer_store_catalog_requested_orderby_20260821(): string {
	return isset( $_GET['orderby'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_key( wp_unslash( $_GET['orderby'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';
}

function mdo_producer_store_catalog_is_recommended_20260821(): bool {
	return in_array( mdo_producer_store_catalog_requested_orderby_20260821(), array( '', 'mdo_recommended' ), true );
}

/** @return array{country:string,postcode:string} */
function mdo_producer_store_catalog_current_destination_20260821(): array {
	if ( class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		$destination = MDO_Catalog_Destination_Frontend::current_destination();
		return array(
			'country'  => strtoupper( (string) ( $destination['country'] ?? 'ES' ) ),
			'postcode' => (string) ( $destination['postcode'] ?? '' ),
		);
	}
	return array( 'country' => 'ES', 'postcode' => '' );
}

/**
 * Temporarily make Woo's public selector renderer see this WCFM store as a
 * product archive. The original global-query state is restored immediately.
 */
function mdo_producer_store_catalog_with_woo_surface_20260821( callable $callback ): void {
	global $wp_query;
	if ( ! $wp_query instanceof WP_Query ) {
		return;
	}

	$old_archive = $wp_query->is_post_type_archive;
	$had_type    = array_key_exists( 'post_type', $wp_query->query_vars );
	$old_type    = $had_type ? $wp_query->query_vars['post_type'] : null;

	$wp_query->is_post_type_archive    = true;
	$wp_query->query_vars['post_type'] = 'product';
	try {
		$callback();
	} finally {
		$wp_query->is_post_type_archive = $old_archive;
		if ( $had_type ) {
			$wp_query->query_vars['post_type'] = $old_type;
		} else {
			unset( $wp_query->query_vars['post_type'] );
		}
	}
}

function mdo_producer_store_catalog_render_destination_20260821( $store_id = null ): void {
	unset( $store_id );
	static $rendered = false;
	if ( $rendered || ! mdo_producer_store_catalog_is_request_20260821() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	$rendered = true;
	mdo_producer_store_catalog_with_woo_surface_20260821(
		static function (): void {
			MDO_Catalog_Destination_Frontend::render_destination_control();
		}
	);
}

function mdo_producer_store_catalog_render_styles_20260821(): void {
	if ( ! mdo_producer_store_catalog_is_request_20260821() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	mdo_producer_store_catalog_with_woo_surface_20260821(
		static function (): void {
			MDO_Catalog_Destination_Frontend::render_styles();
		}
	);
}

function mdo_producer_store_catalog_render_script_20260821(): void {
	if ( ! mdo_producer_store_catalog_is_request_20260821() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
		return;
	}
	mdo_producer_store_catalog_with_woo_surface_20260821(
		static function (): void {
			MDO_Catalog_Destination_Frontend::render_script();
		}
	);
}

/* Keep the selector available even when the destination leaves the store with zero products. */
add_action( 'wcfmmp_store_before_products', 'mdo_producer_store_catalog_render_destination_20260821', 2, 1 );
add_action( 'wp_head', 'mdo_producer_store_catalog_render_styles_20260821', PHP_INT_MAX );
add_action( 'wp_footer', 'mdo_producer_store_catalog_render_script_20260821', PHP_INT_MAX );

add_action(
	'wp',
	static function (): void {
		if ( ! mdo_producer_store_catalog_is_request_20260821() || ! class_exists( 'MDO_Catalog_Destination_Frontend' ) ) {
			return;
		}
		/* On store routes this integration is the single renderer, preventing a
		 * duplicate control if WCFM/Woo also happens to classify the route as a taxonomy. */
		remove_action( 'woocommerce_before_shop_loop', array( 'MDO_Catalog_Destination_Frontend', 'render_destination_control' ), 22 );
		remove_action( 'wp_head', array( 'MDO_Catalog_Destination_Frontend', 'render_styles' ), PHP_INT_MAX );
		remove_action( 'wp_footer', array( 'MDO_Catalog_Destination_Frontend', 'render_script' ), PHP_INT_MAX );
	},
	PHP_INT_MAX
);

add_action(
	'template_redirect',
	static function (): void {
		if ( ! mdo_producer_store_catalog_is_request_20260821() ) {
			return;
		}
		$destination = mdo_producer_store_catalog_current_destination_20260821();
		if ( 'ES' === $destination['country'] && '' === $destination['postcode'] ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	},
	1
);

/* Register after the existing producer-store anti-leak guard. */
add_action(
	'wp_loaded',
	static function (): void {
		add_action(
			'pre_get_posts',
			static function ( WP_Query $query ): void {
				if ( is_admin() || ! mdo_producer_store_catalog_is_request_20260821() || ! mdo_producer_store_catalog_is_product_query_20260821( $query ) ) {
					return;
				}

				$vendor_id = mdo_producer_store_catalog_vendor_id_20260821();
				if ( ! mdo_producer_store_catalog_vendor_allowed_20260821( $vendor_id ) ) {
					return;
				}

				$destination = mdo_producer_store_catalog_current_destination_20260821();
				$recommended = mdo_producer_store_catalog_is_recommended_20260821();
				$ids = mdo_producer_store_catalog_ids_for_destination_20260821(
					$vendor_id,
					$destination['country'],
					$destination['postcode'],
					$recommended
				);

				$query->set( 'post_type', 'product' );
				$query->set( 'post_status', 'publish' );
				$query->set( 'author', $vendor_id );
				$query->set( 'author__not_in', array() );
				$query->set( 'post__in', $ids ?: array( 0 ) );
				$query->set( 'mdo_producer_store_catalog_20260821', 1 );
				$query->set( 'mdo_producer_store_destination_blocked_20260821', $ids ? 0 : 1 );

				if ( $recommended && $ids ) {
					$query->set( 'orderby', 'post__in' );
					$query->set( 'order', 'ASC' );
					$query->set( 'meta_key', '' );
				}
			},
			PHP_INT_MAX
		);

		/* Runs after the previous store-recovery guard. A blocked destination must
		 * remain empty and can never be repopulated by that recovery layer. */
		add_filter(
			'the_posts',
			static function ( array $posts, WP_Query $query ): array {
				if ( is_admin() || ! $query->get( 'mdo_producer_store_catalog_20260821' ) ) {
					return $posts;
				}

				if ( $query->get( 'mdo_producer_store_destination_blocked_20260821' ) ) {
					$query->found_posts   = 0;
					$query->max_num_pages = 0;
					return array();
				}

				$vendor_id = mdo_producer_store_catalog_vendor_id_20260821();
				$allowed = array_flip( mdo_producer_store_catalog_owned_ids_20260821( $vendor_id ) );
				$posts = array_values(
					array_filter(
						$posts,
						static function ( $post ) use ( $vendor_id, $allowed ): bool {
							return $post instanceof WP_Post
								&& 'product' === $post->post_type
								&& 'publish' === $post->post_status
								&& (int) $post->post_author === $vendor_id
								&& isset( $allowed[ (int) $post->ID ] );
						}
					)
				);

				if ( $posts && mdo_producer_store_catalog_is_recommended_20260821() ) {
					$destination = mdo_producer_store_catalog_current_destination_20260821();
					$ranked = mdo_producer_store_catalog_ids_for_destination_20260821(
						$vendor_id,
						$destination['country'],
						$destination['postcode'],
						true
					);
					$position = array_flip( $ranked );
					usort(
						$posts,
						static fn( WP_Post $a, WP_Post $b ): int => ( $position[ $a->ID ] ?? PHP_INT_MAX ) <=> ( $position[ $b->ID ] ?? PHP_INT_MAX )
					);
				}

				return $posts;
			},
			PHP_INT_MAX,
			2
		);
	},
	PHP_INT_MAX
);
