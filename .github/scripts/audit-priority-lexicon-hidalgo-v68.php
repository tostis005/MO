<?php
if ( ! defined( 'ABSPATH' ) ) { fwrite( STDERR, "WordPress required\n" ); exit( 2 ); }
global $wpdb;

$targets = [ 4508 => 'Puente Robles', 4509 => 'El Catedrático' ];
$out = [ 'generated_at' => current_time( 'mysql' ), 'suppliers' => [], 'summary' => [ 'total' => 0, 'ok_structural' => 0, 'issues' => 0 ] ];

function mdo_audit_norm( string $s ): string {
    $s = html_entity_decode( wp_strip_all_tags( $s ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $s = remove_accents( mb_strtolower( $s, 'UTF-8' ) );
    return trim( preg_replace( '/\s+/u', ' ', $s ) );
}

function mdo_audit_spanish_hits( string $value ): array {
    $plain = ' ' . str_replace( '-', ' ', mdo_audit_norm( $value ) ) . ' ';
    $terms = [ 'jamon','jamones','paleta','paletas','iberico','iberica','ibericos','ibericas','bellota','cebo','campo','lomo','lomito','chorizo','salchichon','loncheado','loncheada','loncheados','loncheadas','deshuesado','deshuesada','pieza','piezas','sobres','cortado','cortada','cuchillo','curacion','meses','raza','embutido','embutidos' ];
    $hits = [];
    foreach ( $terms as $term ) {
        if ( preg_match( '/\b' . preg_quote( $term, '/' ) . '\b/u', $plain ) ) { $hits[] = $term; }
    }
    return array_values( array_unique( $hits ) );
}

foreach ( $targets as $author_id => $vendor ) {
    $ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future') ORDER BY ID ASC",
        $author_id
    ) ) );

    $items = [];
    foreach ( $ids as $id ) {
        $p = get_post( $id );
        if ( ! $p ) { continue; }

        $published = (string) get_post_meta( $id, '_en_US_published', true );
        $title     = trim( (string) get_post_meta( $id, '_en_US_post_title', true ) );
        $slug      = sanitize_title( (string) get_post_meta( $id, '_en_US_post_name', true ) );
        $excerpt   = trim( (string) get_post_meta( $id, '_en_US_post_excerpt', true ) );
        $content   = trim( (string) get_post_meta( $id, '_en_US_post_content', true ) );

        $issues = [];
        if ( $published !== '1' ) { $issues[] = 'not_published_en'; }
        if ( $title === '' ) { $issues[] = 'missing_title'; }
        if ( $slug === '' ) { $issues[] = 'missing_slug'; }
        if ( $content === '' ) { $issues[] = 'missing_content'; }
        if ( $excerpt === '' ) { $issues[] = 'missing_excerpt'; }
        if ( $title !== '' && mdo_audit_norm( $title ) === mdo_audit_norm( (string) $p->post_title ) ) { $issues[] = 'title_same_as_spanish'; }
        if ( $slug !== '' && $slug === sanitize_title( (string) $p->post_name ) ) { $issues[] = 'slug_same_as_spanish'; }

        $title_es_hits = mdo_audit_spanish_hits( $title );
        $slug_es_hits  = mdo_audit_spanish_hits( $slug );
        if ( $title_es_hits ) { $issues[] = 'spanish_terms_in_title'; }
        if ( $slug_es_hits ) { $issues[] = 'spanish_terms_in_slug'; }

        $url = $slug !== '' ? home_url( '/en/product/' . rawurlencode( $slug ) . '/' ) : '';
        $http_code = null;
        if ( $published === '1' && $slug !== '' && $p->post_status === 'publish' ) {
            $r = wp_remote_get( $url, [ 'timeout' => 12, 'redirection' => 3, 'user-agent' => 'MDO-English-Audit/1.0' ] );
            if ( is_wp_error( $r ) ) {
                $http_code = 'error:' . $r->get_error_code();
                $issues[] = 'english_url_request_error';
            } else {
                $http_code = (int) wp_remote_retrieve_response_code( $r );
                if ( $http_code !== 200 ) { $issues[] = 'english_url_http_' . $http_code; }
            }
        }

        $structural_ok = ! array_intersect( $issues, [ 'not_published_en','missing_title','missing_slug','missing_content','missing_excerpt','title_same_as_spanish','slug_same_as_spanish','english_url_request_error' ] ) && ! preg_grep( '/^english_url_http_/', $issues );

        $items[] = [
            'id' => $id,
            'status' => $p->post_status,
            'spanish_title' => $p->post_title,
            'spanish_slug' => $p->post_name,
            'en_published' => $published,
            'en_title' => $title,
            'en_slug' => $slug,
            'en_excerpt_chars' => mb_strlen( wp_strip_all_tags( $excerpt ), 'UTF-8' ),
            'en_content_chars' => mb_strlen( wp_strip_all_tags( $content ), 'UTF-8' ),
            'spanish_terms_in_en_title' => $title_es_hits,
            'spanish_terms_in_en_slug' => $slug_es_hits,
            'en_url' => $url,
            'http_code' => $http_code,
            'issues' => array_values( array_unique( $issues ) ),
            'structural_ok' => $structural_ok,
        ];
        $out['summary']['total']++;
        if ( $structural_ok ) { $out['summary']['ok_structural']++; } else { $out['summary']['issues']++; }
    }
    $out['suppliers'][ $vendor ] = [ 'count' => count( $items ), 'items' => $items ];
}

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
