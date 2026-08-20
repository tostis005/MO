<?php
/** Current production audit for English product coverage, with Huerta de Ana Mari focus. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;

function mdo_en_audit_plain( $value ): string {
    return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}
function mdo_en_audit_norm( $value ): string {
    $value = strtolower( remove_accents( mdo_en_audit_plain( $value ) ) );
    $value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );
    return trim( preg_replace( '/\s+/', ' ', $value ) );
}
function mdo_en_audit_vendor( int $uid ): string {
    foreach ( array( 'wcfmmp_profile_settings', 'wcfm_profile_settings' ) as $key ) {
        $value = get_user_meta( $uid, $key, true );
        if ( is_array( $value ) && ! empty( $value['store_name'] ) ) { return mdo_en_audit_plain( $value['store_name'] ); }
    }
    $user = get_userdata( $uid );
    return $user ? mdo_en_audit_plain( $user->display_name ?: $user->user_login ) : 'author-' . $uid;
}
function mdo_en_audit_spanish_hits( string $value ): array {
    $plain = mdo_en_audit_plain( $value );
    if ( '' === $plain ) { return array(); }
    $patterns = array(
        'descripcion' => '/\bdescripci[oó]n\b/iu',
        'ingredientes' => '/\bingredientes?\b/iu',
        'conservacion' => '/\bconservaci[oó]n\b/iu',
        'precio' => '/\bprecio\b/iu',
        'peso' => '/\bpeso\b/iu',
        'aproximadamente' => '/\baproximadamente\b/iu',
        'caja de' => '/\bcaja\s+de\b/iu',
        'pieza de' => '/\bpieza\s+de\b/iu',
        'unidad' => '/\bunidades?\b/iu',
        'producto' => '/\bproducto\b/iu',
        'fresco' => '/\bfresc[oa]s?\b/iu',
        'huerta' => '/\bhuerta\b/iu',
        'recoleccion' => '/\brecolecci[oó]n\b/iu',
        'temporada' => '/\btemporada\b/iu',
        'origen' => '/\borigen\b/iu',
        'contacto' => '/\bcontacto\b/iu',
        'correo' => '/\bcorreo\b/iu',
        'envio' => '/\benv[ií]o\b/iu',
        'legumbres' => '/\blegumbres?\b/iu',
        'hortalizas' => '/\bhortalizas?\b/iu',
        'conservas' => '/\bconservas?\b/iu'
    );
    $hits = array();
    foreach ( $patterns as $label => $regex ) { if ( preg_match( $regex, $plain ) ) { $hits[] = $label; } }
    return $hits;
}

$huerta_ids = array();
if ( class_exists( 'MDO_Database' ) ) {
    $table = MDO_Database::table( 'source_products' );
    $ids = $wpdb->get_col( "SELECT DISTINCT wc_product_id FROM {$table} WHERE wc_product_id > 0 AND source_url LIKE '%lahuertadeanamary.com%'" );
    foreach ( (array) $ids as $id ) { $huerta_ids[ (int) $id ] = true; }
}

$statuses = array( 'publish', 'draft', 'pending', 'private', 'future', 'archived' );
$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
$sql = $wpdb->prepare( "SELECT ID,post_author,post_status,post_title,post_name,post_excerpt,post_content FROM {$wpdb->posts} WHERE post_type='product' AND post_status IN ({$placeholders}) ORDER BY ID", ...$statuses );
$rows = $wpdb->get_results( $sql );

$out = array(
    'generated_at' => gmdate( 'c' ),
    'summary' => array(
        'catalog_products' => 0,
        'complete_english_fields' => 0,
        'incomplete_english_fields' => 0,
        'spanish_residue_products' => 0,
        'exact_copy_products' => 0,
        'published_native_without_english_route' => 0,
        'by_status' => array(),
    ),
    'huerta' => array(
        'source_linked_products' => count( $huerta_ids ),
        'catalog_products' => 0,
        'complete_english_fields' => 0,
        'incomplete_english_fields' => 0,
        'spanish_residue_products' => 0,
        'exact_copy_products' => 0,
        'by_status' => array(),
    ),
    'vendors' => array(),
    'issues' => array(),
    'huerta_products' => array(),
);

foreach ( (array) $rows as $row ) {
    $id = (int) $row->ID;
    $uid = (int) $row->post_author;
    $status = (string) $row->post_status;
    $vendor = mdo_en_audit_vendor( $uid );
    $is_huerta = isset( $huerta_ids[ $id ] );
    $src = array(
        'title' => (string) $row->post_title,
        'slug' => (string) $row->post_name,
        'excerpt' => (string) $row->post_excerpt,
        'content' => (string) $row->post_content,
    );
    $en = array(
        'published' => (string) get_post_meta( $id, '_en_US_published', true ),
        'ready' => (string) get_post_meta( $id, '_en_US_ready', true ),
        'title' => (string) get_post_meta( $id, '_en_US_post_title', true ),
        'slug' => (string) get_post_meta( $id, '_en_US_post_name', true ),
        'excerpt' => (string) get_post_meta( $id, '_en_US_post_excerpt', true ),
        'content' => (string) get_post_meta( $id, '_en_US_post_content', true ),
    );

    $missing = array();
    if ( '' === mdo_en_audit_plain( $en['title'] ) ) { $missing[] = 'title'; }
    if ( '' === trim( $en['slug'] ) ) { $missing[] = 'slug'; }
    if ( '' !== mdo_en_audit_plain( $src['excerpt'] ) && '' === mdo_en_audit_plain( $en['excerpt'] ) ) { $missing[] = 'excerpt'; }
    if ( '' !== mdo_en_audit_plain( $src['content'] ) && '' === mdo_en_audit_plain( $en['content'] ) ) { $missing[] = 'content'; }

    $spanish = array();
    foreach ( array( 'title', 'excerpt', 'content' ) as $field ) {
        $hits = mdo_en_audit_spanish_hits( $en[ $field ] );
        if ( $hits ) { $spanish[ $field ] = $hits; }
    }
    $slug_hits = mdo_en_audit_spanish_hits( str_replace( '-', ' ', $en['slug'] ) );
    if ( $slug_hits ) { $spanish['slug'] = $slug_hits; }

    $exact = array();
    foreach ( array( 'title', 'excerpt', 'content' ) as $field ) {
        $a = mdo_en_audit_norm( $src[ $field ] );
        $b = mdo_en_audit_norm( $en[ $field ] );
        if ( '' !== $a && $a === $b ) {
            $words = count( array_filter( explode( ' ', $a ) ) );
            if ( ( 'title' === $field && $words >= 2 ) || ( 'title' !== $field && $words >= 6 ) ) { $exact[] = $field; }
        }
    }

    $complete = empty( $missing );
    $route_issue = ( 'publish' === $status && '1' !== $en['published'] );
    ++$out['summary']['catalog_products'];
    $out['summary']['by_status'][ $status ] = ( $out['summary']['by_status'][ $status ] ?? 0 ) + 1;
    $out['summary'][ $complete ? 'complete_english_fields' : 'incomplete_english_fields' ]++;
    if ( $spanish ) { ++$out['summary']['spanish_residue_products']; }
    if ( $exact ) { ++$out['summary']['exact_copy_products']; }
    if ( $route_issue ) { ++$out['summary']['published_native_without_english_route']; }

    if ( ! isset( $out['vendors'][ $vendor ] ) ) {
        $out['vendors'][ $vendor ] = array( 'products'=>0, 'complete'=>0, 'incomplete'=>0, 'spanish_residue'=>0, 'exact_copy'=>0, 'huerta'=>0 );
    }
    $out['vendors'][ $vendor ]['products']++;
    $out['vendors'][ $vendor ][ $complete ? 'complete' : 'incomplete' ]++;
    if ( $spanish ) { $out['vendors'][ $vendor ]['spanish_residue']++; }
    if ( $exact ) { $out['vendors'][ $vendor ]['exact_copy']++; }
    if ( $is_huerta ) { $out['vendors'][ $vendor ]['huerta']++; }

    $record = array(
        'id' => $id,
        'vendor' => $vendor,
        'author_id' => $uid,
        'status' => $status,
        'huerta' => $is_huerta,
        'native_title' => mdo_en_audit_plain( $src['title'] ),
        'native_slug' => $src['slug'],
        'english_title' => mdo_en_audit_plain( $en['title'] ),
        'english_slug' => $en['slug'],
        'en_US_published' => $en['published'],
        'en_US_ready' => $en['ready'],
        'missing' => $missing,
        'spanish_residue' => $spanish,
        'exact_copy' => $exact,
        'route_issue' => $route_issue,
    );

    if ( $is_huerta ) {
        ++$out['huerta']['catalog_products'];
        $out['huerta']['by_status'][ $status ] = ( $out['huerta']['by_status'][ $status ] ?? 0 ) + 1;
        $out['huerta'][ $complete ? 'complete_english_fields' : 'incomplete_english_fields' ]++;
        if ( $spanish ) { ++$out['huerta']['spanish_residue_products']; }
        if ( $exact ) { ++$out['huerta']['exact_copy_products']; }
        $out['huerta_products'][] = $record;
    }
    if ( ! $complete || $spanish || $exact || $route_issue ) { $out['issues'][] = $record; }
}

ksort( $out['summary']['by_status'] );
ksort( $out['huerta']['by_status'] );
ksort( $out['vendors'], SORT_NATURAL | SORT_FLAG_CASE );
echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
