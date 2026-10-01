<?php
/**
 * Runtime verification for Offline vs Disabled storefront policy.
 */

defined( 'ABSPATH' ) || exit( 1 );

$required = array(
	'elmercado_wcfm_vendor_is_hard_disabled_010210',
	'elmercado_wcfm_vendor_is_offline_010210',
	'elmercado_wcfm_vendor_is_hidden_010210',
	'elmercado_wcfm_hard_disabled_vendor_ids_010210',
	'elmercado_wcfm_hidden_vendor_ids_010210',
	'elmercado_wcfm_product_is_from_disabled_vendor_010210',
	'elmercado_catalog_counts_excluded_authors_010217',
	'elmercado_wcfm_disabled_vendor_ui_ids_010211',
);
foreach ( $required as $fn ) {
	if ( ! function_exists( $fn ) ) {
		fwrite( STDERR, 'Missing function: ' . $fn . PHP_EOL );
		exit( 2 );
	}
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( ! $admins ) {
	fwrite( STDERR, "Administrator user not found.\n" );
	exit( 3 );
}
$admin = $admins[0];

$all_user_ids = array_map( 'absint', get_users( array( 'fields' => 'ids' ) ) );
$hard_ids     = array_values( array_filter( array_map( 'absint', elmercado_wcfm_hard_disabled_vendor_ids_010210() ) ) );
$offline_ids  = array();
foreach ( $all_user_ids as $user_id ) {
	if (
		elmercado_wcfm_vendor_is_offline_010210( $user_id )
		&& ! elmercado_wcfm_vendor_is_hard_disabled_010210( $user_id )
	) {
		$offline_ids[] = $user_id;
	}
}
$offline_ids = array_values( array_unique( $offline_ids ) );

$assert = static function ( bool $ok, string $message ): void {
	if ( ! $ok ) {
		fwrite( STDERR, 'ASSERTION FAILED: ' . $message . PHP_EOL );
		exit( 10 );
	}
};

wp_set_current_user( 0 );
foreach ( $hard_ids as $vendor_id ) {
	$assert( elmercado_wcfm_vendor_is_hidden_010210( $vendor_id ), 'Hard-disabled vendor visible to public: ' . $vendor_id );
}
foreach ( $offline_ids as $vendor_id ) {
	$assert( elmercado_wcfm_vendor_is_hidden_010210( $vendor_id ), 'Offline vendor visible to public: ' . $vendor_id );
}

$public_hidden = elmercado_wcfm_hidden_vendor_ids_010210();
foreach ( array_merge( $hard_ids, $offline_ids ) as $vendor_id ) {
	$assert( in_array( $vendor_id, $public_hidden, true ), 'Public hidden list missing vendor: ' . $vendor_id );
}

wp_set_current_user( (int) $admin->ID );
foreach ( $hard_ids as $vendor_id ) {
	$assert( elmercado_wcfm_vendor_is_hidden_010210( $vendor_id ), 'Hard-disabled vendor visible to admin frontend: ' . $vendor_id );
}
foreach ( $offline_ids as $vendor_id ) {
	$assert( ! elmercado_wcfm_vendor_is_hidden_010210( $vendor_id ), 'Offline vendor hidden from admin frontend: ' . $vendor_id );
}

$admin_hidden = elmercado_wcfm_hidden_vendor_ids_010210();
foreach ( $hard_ids as $vendor_id ) {
	$assert( in_array( $vendor_id, $admin_hidden, true ), 'Admin hidden list missing hard-disabled vendor: ' . $vendor_id );
}
foreach ( $offline_ids as $vendor_id ) {
	$assert( ! in_array( $vendor_id, $admin_hidden, true ), 'Admin hidden list contains offline-only vendor: ' . $vendor_id );
}

$admin_count_excluded = elmercado_catalog_counts_excluded_authors_010217();
foreach ( $hard_ids as $vendor_id ) {
	$assert( in_array( $vendor_id, $admin_count_excluded, true ), 'Admin category counts include hard-disabled vendor: ' . $vendor_id );
}

$admin_ui_hidden = elmercado_wcfm_disabled_vendor_ui_ids_010211();
foreach ( $hard_ids as $vendor_id ) {
	$assert( in_array( $vendor_id, $admin_ui_hidden, true ), 'Admin vendor UI contains hard-disabled vendor: ' . $vendor_id );
}

$wcfm_excluded = apply_filters( 'wcfm_exclude_vendors_list', array() );
foreach ( $hard_ids as $vendor_id ) {
	$assert( in_array( $vendor_id, array_map( 'absint', (array) $wcfm_excluded ), true ), 'WCFM vendor list missing hard-disabled exclusion: ' . $vendor_id );
}

global $wpdb;
$sample_product = static function ( array $vendor_ids ) use ( $wpdb ): int {
	if ( ! $vendor_ids ) {
		return 0;
	}
	$ids = implode( ',', array_map( 'absint', $vendor_ids ) );
	return (int) $wpdb->get_var(
		"SELECT ID FROM {$wpdb->posts}
		 WHERE post_type='product' AND post_status='publish'
		 AND post_author IN ({$ids})
		 ORDER BY ID DESC LIMIT 1"
	);
};

$hard_product_id    = $sample_product( $hard_ids );
$offline_product_id = $sample_product( $offline_ids );

if ( $hard_product_id > 0 ) {
	$assert( elmercado_wcfm_product_is_from_disabled_vendor_010210( $hard_product_id ), 'Hard-disabled product visible to admin helper.' );
	$assert( ! apply_filters( 'woocommerce_product_is_visible', true, $hard_product_id ), 'Hard-disabled product passes WooCommerce visibility.' );
}

if ( $offline_product_id > 0 ) {
	$assert( ! elmercado_wcfm_product_is_from_disabled_vendor_010210( $offline_product_id ), 'Offline product hidden from admin helper.' );
	$assert( apply_filters( 'woocommerce_product_is_visible', true, $offline_product_id ), 'Offline product does not pass admin WooCommerce visibility.' );
}

$store_url_for = static function ( int $vendor_id ): string {
	return function_exists( 'wcfmmp_get_store_url' ) ? (string) wcfmmp_get_store_url( $vendor_id ) : '';
};

$hard_store_urls = array_values( array_filter( array_map( $store_url_for, $hard_ids ) ) );
$offline_store_urls = array_values( array_filter( array_map( $store_url_for, $offline_ids ) ) );

$payload = array(
	'hard_ids'             => $hard_ids,
	'offline_only_ids'     => $offline_ids,
	'hard_store_urls'      => $hard_store_urls,
	'offline_store_urls'   => $offline_store_urls,
	'hard_product_id'      => $hard_product_id,
	'hard_product_url'     => $hard_product_id > 0 ? get_permalink( $hard_product_id ) : '',
	'offline_product_id'   => $offline_product_id,
	'offline_product_url'  => $offline_product_id > 0 ? get_permalink( $offline_product_id ) : '',
	'producer_directory'   => home_url( '/productores/' ),
	'home_url'             => home_url( '/' ),
	'admin_cookie_name'    => defined( 'LOGGED_IN_COOKIE' ) ? LOGGED_IN_COOKIE : '',
	'admin_cookie_value'   => wp_generate_auth_cookie( (int) $admin->ID, time() + 900, 'logged_in' ),
);

echo '__POLICY__=' . base64_encode( wp_json_encode( $payload ) ) . PHP_EOL;
echo 'VENDOR_VISIBILITY_POLICY_20261001_OK' . PHP_EOL;
