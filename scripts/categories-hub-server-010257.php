<?php
/**
 * One-off production QA for the categories hub visibility contract.
 * Executed through WP-CLI; never deployed as a WordPress plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function mdo_categories_hub_qa_fail_010257( string $message ): void {
	fwrite( STDERR, 'CATEGORIES_HUB_010257_ERROR: ' . $message . PHP_EOL );
	exit( 2 );
}

if ( ! function_exists( 'elmercado_categories_hub_items_010257' ) ) {
	mdo_categories_hub_qa_fail_010257( 'categories hub functions unavailable' );
}
if ( ! function_exists( 'elmercado_catalog_visible_category_counts_010217' ) ) {
	mdo_categories_hub_qa_fail_010257( 'catalog visibility counts unavailable' );
}

/* Public contract: no empty categories and never the internal MENTTA category. */
wp_set_current_user( 0 );
$public       = elmercado_categories_hub_items_010257();
$public_slugs = array_values( array_map( static fn( array $item ): string => (string) ( $item['slug'] ?? '' ), $public ) );

if ( ! $public ) {
	mdo_categories_hub_qa_fail_010257( 'public category set is empty' );
}
if ( in_array( 'mentta', $public_slugs, true ) ) {
	mdo_categories_hub_qa_fail_010257( 'MENTTA leaked into public category set' );
}
foreach ( $public as $item ) {
	if ( empty( $item['id'] ) || empty( $item['name'] ) || empty( $item['link'] ) || (int) ( $item['count'] ?? 0 ) < 1 ) {
		mdo_categories_hub_qa_fail_010257( 'invalid public category item: ' . wp_json_encode( $item ) );
	}
}

/* Administrator contract: same or broader set, using the existing admin count scope. */
$admins = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
if ( ! $admins ) {
	mdo_categories_hub_qa_fail_010257( 'no administrator account available for visibility QA' );
}

wp_set_current_user( (int) $admins[0] );
if ( function_exists( 'mdo_mentta_enable_frontend_for_admin_010257' ) ) {
	mdo_mentta_enable_frontend_for_admin_010257();
}
$admin       = elmercado_categories_hub_items_010257();
$admin_slugs = array_values( array_map( static fn( array $item ): string => (string) ( $item['slug'] ?? '' ), $admin ) );

if ( count( $admin ) < count( $public ) ) {
	mdo_categories_hub_qa_fail_010257( sprintf( 'admin set smaller than public set: public=%d admin=%d', count( $public ), count( $admin ) ) );
}

/* If the internal category currently contains admin-visible products, admins must see it. */
$mentta = get_term_by( 'slug', 'mentta', 'product_cat' );
$admin_counts = elmercado_catalog_visible_category_counts_010217();
$mentta_count = $mentta instanceof WP_Term ? (int) ( $admin_counts[ $mentta->term_id ] ?? 0 ) : 0;
if ( $mentta_count > 0 && ! in_array( 'mentta', $admin_slugs, true ) ) {
	mdo_categories_hub_qa_fail_010257( 'MENTTA has admin-visible products but is absent from admin category set' );
}

$payload = array(
	'public_count' => count( $public ),
	'admin_count'  => count( $admin ),
	'mentta_count' => $mentta_count,
	'public_slugs' => $public_slugs,
	'admin_slugs'  => $admin_slugs,
);

echo 'CATEGORIES_HUB_010257_SERVER_OK ' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
