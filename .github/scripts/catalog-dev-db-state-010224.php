<?php
/** Read-only development catalog database state diagnostic. */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT p.ID, p.post_status, p.post_author, p.post_modified_gmt,
		MAX(CASE WHEN pm.meta_key = '_stock_status' THEN pm.meta_value END) AS stock_status,
		MAX(CASE WHEN pm.meta_key = '_emo_catalog_fixture' THEN pm.meta_value END) AS fixture
	FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ('_stock_status','_emo_catalog_fixture')
	WHERE p.post_type = 'product'
	GROUP BY p.ID, p.post_status, p.post_author, p.post_modified_gmt
	ORDER BY p.ID",
	ARRAY_A
); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

$visibility = array();
$vis_rows = $wpdb->get_results(
	"SELECT tr.object_id AS product_id, t.slug
	FROM {$wpdb->term_relationships} tr
	INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
	INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
	WHERE tt.taxonomy = 'product_visibility'
	ORDER BY tr.object_id, t.slug",
	ARRAY_A
); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
foreach ( (array) $vis_rows as $row ) {
	$id = absint( $row['product_id'] ?? 0 );
	if ( $id > 0 ) {
		$visibility[ $id ][] = (string) ( $row['slug'] ?? '' );
	}
}

$status_counts = array();
$stock_counts  = array();
$fixtures      = array();
$author_status = array();
foreach ( (array) $rows as &$row ) {
	$id = absint( $row['ID'] ?? 0 );
	$row['ID'] = $id;
	$row['post_author'] = absint( $row['post_author'] ?? 0 );
	$row['visibility'] = $visibility[ $id ] ?? array();
	$status = (string) ( $row['post_status'] ?? '' );
	$stock  = (string) ( $row['stock_status'] ?? '' );
	$author = (int) $row['post_author'];
	$status_counts[ $status ] = ( $status_counts[ $status ] ?? 0 ) + 1;
	$stock_counts[ $stock ] = ( $stock_counts[ $stock ] ?? 0 ) + 1;
	$key = $author . ':' . $status;
	$author_status[ $key ] = ( $author_status[ $key ] ?? 0 ) + 1;
	if ( ! empty( $row['fixture'] ) ) {
		$fixtures[] = $id;
	}
}
unset( $row );
ksort( $author_status, SORT_NATURAL );

$fixture_users = $wpdb->get_results(
	"SELECT u.ID, u.user_login FROM {$wpdb->users} u WHERE u.user_login LIKE 'emo_catalog_fixture_%' ORDER BY u.ID",
	ARRAY_A
); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

$known_baseline_ids = array( 1056, 1350, 1356, 1363, 1370, 1380, 1382, 1599, 1693, 1695, 2484, 3948, 3979, 4160, 4199, 5045, 5336, 5343, 5348, 8624 );
$baseline_rows = array_values(
	array_filter(
		$rows,
		static fn( array $row ): bool => in_array( (int) $row['ID'], $known_baseline_ids, true )
	)
);

$recently_modified = $rows;
usort(
	$recently_modified,
	static fn( array $a, array $b ): int => strcmp( (string) ( $b['post_modified_gmt'] ?? '' ), (string) ( $a['post_modified_gmt'] ?? '' ) )
);
$recently_modified = array_slice( $recently_modified, 0, 50 );

echo '__DEV_DB_STATE__=' . base64_encode(
	wp_json_encode(
		array(
			'product_count'        => count( $rows ),
			'status_counts'        => $status_counts,
			'stock_counts'         => $stock_counts,
			'author_status_counts' => $author_status,
			'fixture_product_ids'  => $fixtures,
			'fixture_users'        => $fixture_users,
			'known_baseline_rows'  => $baseline_rows,
			'recently_modified'    => $recently_modified,
		)
	)
) . PHP_EOL;
