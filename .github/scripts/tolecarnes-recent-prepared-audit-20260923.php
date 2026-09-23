<?php
/**
 * Read-only audit of recently created Tolecarnes products in production.
 * No writes.
 */
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "ABORT: WordPress not loaded\n" );
    exit( 2 );
}
if ( ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ABORT: WooCommerce unavailable\n" );
    exit( 3 );
}

$ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => ['publish','draft','pending','private','future'],
    'posts_per_page' => 300,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'fields'         => 'ids',
    'no_found_rows'  => true,
]);

$rows = [];
foreach ( $ids as $id ) {
    $post = get_post( (int) $id );
    if ( ! $post ) continue;

    $user = get_userdata( (int) $post->post_author );
    $vendor = $user ? (string) $user->display_name : '';
    $source_url = trim( (string) get_post_meta( $id, '_emdo_source_url', true ) );
    $supplier_id = absint( get_post_meta( $id, '_emdo_supplier_id', true ) );
    $host = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );

    $is_tolecarnes =
        stripos( $vendor, 'tolecarnes' ) !== false
        || in_array( $host, ['tolecarnes.com','www.tolecarnes.com'], true );

    if ( ! $is_tolecarnes && $supplier_id && class_exists( 'MDO_Supplier_Repository' ) ) {
        $supplier = MDO_Supplier_Repository::find( $supplier_id );
        $is_tolecarnes = $supplier && (
            stripos( (string)($supplier['name'] ?? ''), 'tolecarnes' ) !== false
            || (string)($supplier['connector'] ?? '') === 'tolecarnes'
        );
    }

    if ( ! $is_tolecarnes ) continue;

    $product = wc_get_product( (int) $id );
    $rows[] = [
        'id'          => (int) $id,
        'date'        => (string) $post->post_date,
        'status'      => (string) $post->post_status,
        'title'       => (string) $post->post_title,
        'slug'        => (string) $post->post_name,
        'sku'         => $product ? (string) $product->get_sku() : (string) get_post_meta( $id, '_sku', true ),
        'type'        => $product ? (string) $product->get_type() : '',
        'vendor'      => $vendor,
        'supplier_id' => $supplier_id,
        'source_url'  => $source_url,
        'excerpt_html'=> (string) $post->post_excerpt,
        'content_html'=> (string) $post->post_content,
    ];

    if ( count( $rows ) >= 20 ) break;
}

echo "TOLECARNES_RECENT_COUNT=" . count( $rows ) . "\n";
echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "TOLECARNES_RECENT_AUDIT_OK\n";
