<?php
/**
 * Invalidation for the anonymous product early cache.
 *
 * The cache drop-in can serve product HTML before WordPress boots, so product
 * mutations must remove the matching file explicitly. This keeps the longer,
 * jittered cache TTL safe for price, stock, content and vendor-state changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize a public URL into the exact path key used by advanced-cache.php.
 */
function mdo_product_early_cache_path_20261002( string $url ): string {
	$path = parse_url( $url, PHP_URL_PATH );
	$path = is_string( $path ) && '' !== $path ? '/' . ltrim( $path, '/' ) : '/';
	return '/' !== $path ? rtrim( $path, '/' ) . '/' : '/';
}

/**
 * Delete one cached product route.
 */
function mdo_product_early_cache_purge_path_20261002( string $path ): void {
	$path = '/' !== $path ? rtrim( '/' . ltrim( $path, '/' ), '/' ) . '/' : '/';
	if ( 1 !== preg_match( '#^/(?:producto|en/product)/[^/]+/$#i', $path ) ) {
		return;
	}

	$file = WP_CONTENT_DIR . '/uploads/elmercado-product-static-v1/' . hash( 'sha256', $path ) . '.html';
	if ( is_file( $file ) ) {
		@unlink( $file );
	}
}

/**
 * Purge Spanish and English public routes for a product.
 */
function mdo_product_early_cache_purge_product_20261002( int $product_id ): void {
	if ( $product_id <= 0 ) {
		return;
	}

	$post = get_post( $product_id );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( 'product_variation' === $post->post_type && $post->post_parent > 0 ) {
		$product_id = (int) $post->post_parent;
		$post       = get_post( $product_id );
	}

	if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
		return;
	}

	$permalink = get_permalink( $product_id );
	if ( is_string( $permalink ) && '' !== $permalink ) {
		mdo_product_early_cache_purge_path_20261002(
			mdo_product_early_cache_path_20261002( $permalink )
		);
	}

	$english_slug = sanitize_title( (string) get_post_meta( $product_id, '_en_US_post_name', true ) );
	if ( '' !== $english_slug ) {
		mdo_product_early_cache_purge_path_20261002( '/en/product/' . $english_slug . '/' );
	}
}

/**
 * Product edits, imports and visibility changes all pass through post saves.
 */
add_action(
	'save_post_product',
	static function ( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		mdo_product_early_cache_purge_product_20261002( $post_id );
	},
	100
);

add_action(
	'save_post_product_variation',
	static function ( int $variation_id ): void {
		mdo_product_early_cache_purge_product_20261002( $variation_id );
	},
	100
);

/**
 * Some stock changes are persisted through WooCommerce data stores without a
 * normal product edit screen. Purge explicitly as a second line of defence.
 */
add_action(
	'woocommerce_product_set_stock_status',
	static function ( int $product_id ): void {
		mdo_product_early_cache_purge_product_20261002( $product_id );
	},
	100
);

add_action(
	'woocommerce_variation_set_stock_status',
	static function ( int $variation_id ): void {
		mdo_product_early_cache_purge_product_20261002( $variation_id );
	},
	100
);

/**
 * Reviews are visible on product pages, so approved/edited review changes should
 * not wait for the product TTL.
 */
function mdo_product_early_cache_purge_comment_20261002( int $comment_id ): void {
	$comment = get_comment( $comment_id );
	if ( ! $comment instanceof WP_Comment ) {
		return;
	}
	$post_id = (int) $comment->comment_post_ID;
	if ( $post_id > 0 && 'product' === get_post_type( $post_id ) ) {
		mdo_product_early_cache_purge_product_20261002( $post_id );
	}
}

add_action(
	'comment_post',
	static function ( int $comment_id ): void {
		mdo_product_early_cache_purge_comment_20261002( $comment_id );
	},
	100
);
add_action( 'edit_comment', 'mdo_product_early_cache_purge_comment_20261002', 100 );
add_action(
	'wp_set_comment_status',
	static function ( int $comment_id ): void {
		mdo_product_early_cache_purge_comment_20261002( $comment_id );
	},
	100
);

/**
 * Offline/Disabled vendor state changes affect every product owned by that
 * vendor. Purge only that vendor's product files rather than the whole cache.
 */
function mdo_product_early_cache_purge_vendor_20261002( int $vendor_id ): void {
	if ( $vendor_id <= 0 ) {
		return;
	}

	$product_ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'author'         => $vendor_id,
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( array_map( 'intval', (array) $product_ids ) as $product_id ) {
		mdo_product_early_cache_purge_product_20261002( $product_id );
	}
}

function mdo_product_early_cache_vendor_meta_changed_20261002( $meta_id, $user_id, $meta_key ): void {
	unset( $meta_id );
	if ( in_array( (string) $meta_key, array( '_disable_vendor', '_wcfm_store_offline' ), true ) ) {
		mdo_product_early_cache_purge_vendor_20261002( (int) $user_id );
	}
}

add_action( 'added_user_meta', 'mdo_product_early_cache_vendor_meta_changed_20261002', 100, 3 );
add_action( 'updated_user_meta', 'mdo_product_early_cache_vendor_meta_changed_20261002', 100, 3 );
add_action( 'deleted_user_meta', 'mdo_product_early_cache_vendor_meta_changed_20261002', 100, 3 );

add_action(
	'set_user_role',
	static function ( int $user_id ): void {
		mdo_product_early_cache_purge_vendor_20261002( $user_id );
	},
	100
);
