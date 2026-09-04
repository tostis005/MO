<?php
/**
 * Invalidate the public Home cache whenever an MDO Special changes.
 *
 * The Home HTML cache is stored both as an `elmercado_home_*` transient and as
 * uploads/elmercado-home-static/index.html. A Special is injected only while a
 * fresh Home render is being built, so keeping either stale artifact means
 * anonymous visitors can miss a newly published or edited featured Special.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove every Home HTML cache variant and its static HTML snapshot.
 */
function mdo_home_special_purge_cache_20260904(): void {
	global $wpdb;

	/* Use the canonical theme purge when available (current transient + files). */
	if ( function_exists( 'elmercado_flush_home_cache' ) ) {
		elmercado_flush_home_cache();
	}

	/*
	 * Also remove older/current Home transient variants. The cache key includes
	 * the theme version and critical-CSS timestamp, so deleting only one guessed
	 * key is not sufficient after deployments.
	 */
	$prefixes = array(
		'_transient_elmercado_home_',
		'_transient_timeout_elmercado_home_',
	);

	foreach ( $prefixes as $prefix ) {
		$like  = $wpdb->esc_like( $prefix ) . '%';
		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
		foreach ( (array) $names as $name ) {
			delete_option( (string) $name );
		}
	}

	$static_html = WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
	if ( is_file( $static_html ) ) {
		@unlink( $static_html );
	}
}

/**
 * Coalesce the many meta updates of one Special save into one final purge.
 */
function mdo_home_special_queue_cache_purge_20260904(): void {
	static $queued = false;
	if ( $queued ) {
		return;
	}
	$queued = true;
	add_action( 'shutdown', 'mdo_home_special_purge_cache_20260904', PHP_INT_MAX );
}

/**
 * A promotion post was saved. Run late so MDO_Specials has already saved meta.
 */
add_action(
	'save_post_mdo_promotion',
	static function ( int $post_id, WP_Post $post, bool $update ): void {
		unset( $post_id, $post, $update );
		mdo_home_special_queue_cache_purge_20260904();
	},
	100,
	3
);

/**
 * Publishing, unpublishing, scheduling, trashing or restoring also changes Home.
 */
add_action(
	'transition_post_status',
	static function ( string $new_status, string $old_status, WP_Post $post ): void {
		unset( $new_status, $old_status );
		if ( 'mdo_promotion' === $post->post_type ) {
			mdo_home_special_queue_cache_purge_20260904();
		}
	},
	100,
	3
);

/**
 * Catch programmatic edits that bypass the normal Special editor save action.
 */
$meta_change_handler_20260904 = static function ( $meta_id, $object_id, $meta_key, $meta_value = null ): void {
	unset( $meta_id, $meta_value );
	$object_id = (int) $object_id;
	$meta_key  = (string) $meta_key;
	if ( $object_id > 0 && str_starts_with( $meta_key, '_mdo_promo_' ) && 'mdo_promotion' === get_post_type( $object_id ) ) {
		mdo_home_special_queue_cache_purge_20260904();
	}
};
add_action( 'added_post_meta', $meta_change_handler_20260904, 100, 4 );
add_action( 'updated_post_meta', $meta_change_handler_20260904, 100, 4 );
add_action( 'deleted_post_meta', $meta_change_handler_20260904, 100, 4 );

/**
 * Queue before permanent deletion, while the post type is still resolvable.
 */
add_action(
	'before_delete_post',
	static function ( int $post_id ): void {
		if ( 'mdo_promotion' === get_post_type( $post_id ) ) {
			mdo_home_special_queue_cache_purge_20260904();
		}
	},
	100,
	1
);
