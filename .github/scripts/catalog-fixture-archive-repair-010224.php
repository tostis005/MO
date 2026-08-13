<?php
/**
 * Repara exclusivamente la mutación accidental causada por el teardown del
 * primer fixture de catálogo el 2026-08-13 entre 08:53:20 y 08:53:23 UTC.
 *
 * El primer test borró un usuario WCFM mediante wp_delete_user(); un hook del
 * marketplace archivó productos reales. El repair usa SQL directo para no volver
 * a disparar esos hooks. Solo funciona en desarrollo y solo si el conjunto
 * candidato coincide con las salvaguardas observadas.
 */

defined( 'ABSPATH' ) || exit;

if ( false === strpos( home_url( '/' ), 'dev.elmercadodeorigen.com' ) ) {
	fwrite( STDERR, 'Repair refused outside development.' . PHP_EOL );
	exit( 3 );
}

global $wpdb;
$action = getenv( 'EMO_REPAIR_ACTION' ) ?: 'dry-run';
$from   = '2026-08-13 08:53:20';
$to     = '2026-08-13 08:53:24';

$candidates = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT ID, post_author, post_status, post_modified_gmt, post_title
		FROM {$wpdb->posts}
		WHERE post_type = 'product'
		AND post_status = 'archived'
		AND post_modified_gmt >= %s
		AND post_modified_gmt < %s
		ORDER BY ID",
		$from,
		$to
	),
	ARRAY_A
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

$ids = array_values( array_map( 'absint', wp_list_pluck( (array) $candidates, 'ID' ) ) );
$known_baseline_ids = array( 1056, 1350, 1356, 1363, 1370, 1380, 1382, 1599, 1693, 1695, 2484, 3948, 3979, 4160, 4199, 5045, 5336, 5343, 5348, 8624 );
$missing_known = array_values( array_diff( $known_baseline_ids, $ids ) );

$payload = array(
	'action'        => $action,
	'window'        => array( $from, $to ),
	'candidate_ids' => $ids,
	'candidate_count' => count( $ids ),
	'missing_known_baseline_ids' => $missing_known,
	'candidates'    => $candidates,
);
echo '__ARCHIVE_REPAIR__=' . base64_encode( wp_json_encode( $payload ) ) . PHP_EOL;

if ( 'dry-run' === $action ) {
	echo 'CATALOG_FIXTURE_ARCHIVE_REPAIR_010224_DRY_RUN_OK' . PHP_EOL;
	return;
}

if ( 'apply' !== $action ) {
	fwrite( STDERR, 'Unknown repair action.' . PHP_EOL );
	exit( 4 );
}

/*
 * Guardas: el incidente produjo exactamente 25 productos en esa ventana y los
 * 20 IDs conocidos del set visible previo deben estar dentro. Si cambia algo,
 * no tocamos la base de datos.
 */
if ( 25 !== count( $ids ) || $missing_known ) {
	fwrite(
		STDERR,
		'Repair guard failed: count=' . count( $ids ) . '; missing=' . implode( ',', $missing_known ) . PHP_EOL
	);
	exit( 5 );
}

$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
$sql = "UPDATE {$wpdb->posts} SET post_status = 'publish' WHERE post_type = 'product' AND post_status = 'archived' AND ID IN ({$placeholders})";
$updated = $wpdb->query( $wpdb->prepare( $sql, ...$ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

if ( 25 !== (int) $updated ) {
	fwrite( STDERR, 'Repair updated ' . (int) $updated . ' rows; expected 25.' . PHP_EOL );
	exit( 6 );
}

foreach ( $ids as $id ) {
	clean_post_cache( $id );
}
if ( function_exists( 'wc_delete_product_transients' ) ) {
	wc_delete_product_transients();
}

$remaining = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts}
		WHERE post_type = 'product'
		AND post_status = 'archived'
		AND post_modified_gmt >= %s
		AND post_modified_gmt < %s",
		$from,
		$to
	)
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

if ( 0 !== $remaining ) {
	fwrite( STDERR, 'Repair left ' . $remaining . ' archived candidates.' . PHP_EOL );
	exit( 7 );
}

echo 'CATALOG_FIXTURE_ARCHIVE_REPAIR_010224_APPLY_OK updated=25' . PHP_EOL;
