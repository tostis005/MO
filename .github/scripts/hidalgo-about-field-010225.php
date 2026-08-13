<?php
/**
 * Read-only diagnostic: locate the production usermeta field containing the
 * current Hidalgo de la Jara store "Acerca de" copy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

global $wpdb;

$needle = 'Los ganaderos que integran Hidalgo de la Jara';
$like   = '%' . $wpdb->esc_like( $needle ) . '%';

$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_value LIKE %s ORDER BY user_id, meta_key",
        $like
    ),
    ARRAY_A
);

if ( empty( $rows ) ) {
    fwrite( STDERR, "HIDALGO_ABOUT_FIELD_NOT_FOUND\n" );
    exit( 2 );
}

foreach ( $rows as $row ) {
    echo "__USER_ID__=" . (int) $row['user_id'] . "\n";
    echo "__META_KEY__=" . $row['meta_key'] . "\n";
    echo "__META_VALUE_B64__=" . base64_encode( $row['meta_value'] ) . "\n";
}
