<?php
if ( ! defined( 'ABSPATH' ) ) { fwrite( STDERR, "WordPress required\n" ); exit( 2 ); }
global $wpdb;

$suppliers_table = $wpdb->prefix . 'mdo_suppliers';
$products_table  = $wpdb->prefix . 'mdo_source_products';

$targets = $wpdb->get_results(
    "SELECT id, code, name, source_url, vendor_user_id
     FROM {$suppliers_table}
     WHERE LOWER(name) LIKE '%puente robles%'
        OR LOWER(name) LIKE '%catedr%'
        OR LOWER(source_url) LIKE '%puenterobles%'
        OR LOWER(source_url) LIKE '%elcatedratico%'
     ORDER BY name ASC",
    ARRAY_A
);

function mdo_en_norm( string $s ): string {
    $s = html_entity_decode( wp_strip_all_tags( $s ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $s = remove_accents( mb_strtolower( $s, 'UTF-8' ) );
    return trim( preg_replace( '/\s+/u', ' ', $s ) );
}

function mdo_spanish_terms( string $value ): array {
    $plain = ' ' . str_replace( '-', ' ', mdo_en_norm( $value ) ) . ' ';
    $terms = [ 'jamon','jamones','paleta','paletas','iberico','iberica','ibericos','ibericas','bellota','cebo','campo','lomo','lomito','chorizo','salchichon','loncheado','loncheada','loncheados','loncheadas','deshuesado','deshuesada','pieza','piezas','sobres','cortado','cortada','cuchillo','curacion','meses','raza','embutido','embutidos','queso','aceite','vino' ];
    $hits = [];
    foreach ( $terms as $term ) {
        if ( preg_match( '/\b' . preg_quote( $term, '/' ) . '\b/u', $plain ) ) { $hits[] = $term; }
    }
    return array_values( array_unique( $hits ) );
}

$out = [
    'generated_at' => current_time( 'mysql' ),
    'supplier_matches' => $targets,
    'suppliers' => [],
    'summary' => [ 'products' => 0, 'ready' => 0, 'with_issues' => 0, 'published_es' => 0, 'unpublished_es' => 0 ]
];

foreach ( $targets as $supplier ) {
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, wc_product_id, title, status, source_url
         FROM {$products_table}
         WHERE supplier_id=%d AND wc_product_id IS NOT NULL AND wc_product_id>0
         ORDER BY wc_product_id ASC",
        (int) $supplier['id']
    ), ARRAY_A );

    $items = [];
    $seen = [];
    foreach ( $rows as $row ) {
        $id = (int) $row['wc_product_id'];
        if ( isset( $seen[$id] ) ) { continue; }
        $seen[$id] = true;
        $p = get_post( $id );
        if ( ! $p || $p->post_type !== 'product' ) { continue; }

        $published = (string) get_post_meta( $id, '_en_US_published', true );
        $title     = trim( (string) get_post_meta( $id, '_en_US_post_title', true ) );
        $slug_raw  = trim( (string) get_post_meta( $id, '_en_US_post_name', true ) );
        $slug      = sanitize_title( $slug_raw );
        $excerpt   = trim( (string) get_post_meta( $id, '_en_US_post_excerpt', true ) );
        $content   = trim( (string) get_post_meta( $id, '_en_US_post_content', true ) );

        $issues = [];
        if ( $published !== '1' ) { $issues[] = 'english_not_enabled'; }
        if ( $title === '' ) { $issues[] = 'missing_en_title'; }
        if ( $slug === '' ) { $issues[] = 'missing_en_slug'; }
        if ( $excerpt === '' ) { $issues[] = 'missing_en_excerpt'; }
        if ( $content === '' ) { $issues[] = 'missing_en_content'; }
        if ( $title !== '' && mdo_en_norm( $title ) === mdo_en_norm( (string) $p->post_title ) ) { $issues[] = 'en_title_same_as_es'; }
        if ( $slug !== '' && $slug === sanitize_title( (string) $p->post_name ) ) { $issues[] = 'en_slug_same_as_es'; }
        $title_hits = mdo_spanish_terms( $title );
        $slug_hits  = mdo_spanish_terms( $slug );
        if ( $title_hits ) { $issues[] = 'spanish_terms_in_en_title'; }
        if ( $slug_hits ) { $issues[] = 'spanish_terms_in_en_slug'; }

        $ready = empty( $issues );
        $items[] = [
            'wc_product_id' => $id,
            'wp_status' => $p->post_status,
            'supplier_status' => $row['status'],
            'es_title' => (string) $p->post_title,
            'es_slug' => (string) $p->post_name,
            'en_published' => $published,
            'en_title' => $title,
            'en_slug' => $slug,
            'en_excerpt_chars' => mb_strlen( wp_strip_all_tags( $excerpt ), 'UTF-8' ),
            'en_content_chars' => mb_strlen( wp_strip_all_tags( $content ), 'UTF-8' ),
            'spanish_terms_in_en_title' => $title_hits,
            'spanish_terms_in_en_slug' => $slug_hits,
            'issues' => $issues,
            'ready' => $ready,
            'source_url' => (string) $row['source_url'],
        ];

        $out['summary']['products']++;
        if ( $p->post_status === 'publish' ) { $out['summary']['published_es']++; } else { $out['summary']['unpublished_es']++; }
        if ( $ready ) { $out['summary']['ready']++; } else { $out['summary']['with_issues']++; }
    }

    $out['suppliers'][] = [ 'supplier' => $supplier, 'count' => count( $items ), 'items' => $items ];
}

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
