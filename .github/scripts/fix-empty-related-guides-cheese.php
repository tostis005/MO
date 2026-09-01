<?php
/**
 * Remove empty "Guías relacionadas / Related guides" headings from cheese batch 01.
 * Keeps the heading only when the immediately following related-links block contains at least one anchor.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$slugs = array(
    'queso-manchego-dop-que-es-como-se-elabora-reconocer-autentico',
    'tipos-de-queso-guia-leche-maduracion-pasta-corteza-elaboracion',
    'queso-tierno-semicurado-curado-viejo-anejo-diferencias',
    'queso-oveja-cabra-vaca-diferencias-como-elegir',
    'queso-leche-cruda-o-pasteurizada-diferencias-sabor-seguridad',
    'como-conservar-queso-correctamente-nevera-envoltorio-temperatura',
    'se-puede-congelar-queso-tipos-como-hacerlo',
    'como-cortar-servir-queso-temperatura-cuchillos-presentacion',
    'como-preparar-tabla-quesos-cantidades-orden-acompanamientos',
    'como-saber-queso-mal-estado-moho-olor-textura',
);

function emdo_remove_empty_related_heading( string $html, string $heading ): string {
    $quoted = preg_quote( $heading, '/' );

    // Remove heading followed by an empty list (allowing whitespace/newlines).
    $html = preg_replace(
        '/<h2>\s*' . $quoted . '\s*<\/h2>\s*<ul>\s*<\/ul>/iu',
        '',
        $html
    );

    // Also remove a heading that is immediately followed by the next section without any links/list.
    $html = preg_replace(
        '/<h2>\s*' . $quoted . '\s*<\/h2>\s*(?=<h2>)/iu',
        '',
        $html
    );

    // Defensive cleanup: if a related heading remains but there is no anchor before the next h2/end, remove it.
    $html = preg_replace_callback(
        '/<h2>\s*' . $quoted . '\s*<\/h2>(.*?)(?=<h2>|$)/isu',
        static function ( array $m ): string {
            return false !== stripos( $m[1], '<a ' ) ? $m[0] : $m[1];
        },
        $html
    );

    return $html;
}

$results = array();
foreach ( $slugs as $slug ) {
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) {
        throw new RuntimeException( 'Post not found: ' . $slug );
    }

    $es = (string) $post->post_content;
    $en = (string) get_post_meta( $post->ID, '_en_US_post_content', true );

    $new_es = emdo_remove_empty_related_heading( $es, 'Guías relacionadas' );
    $new_en = emdo_remove_empty_related_heading( $en, 'Related guides' );

    if ( $new_es !== $es ) {
        $updated = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $new_es ) ), true );
        if ( is_wp_error( $updated ) ) { throw new RuntimeException( $updated->get_error_message() ); }
    }
    if ( $new_en !== $en ) {
        update_post_meta( $post->ID, '_en_US_post_content', $new_en );
    }

    $es_empty = preg_match( '/<h2>\s*Guías relacionadas\s*<\/h2>\s*(?:<ul>\s*<\/ul>|(?=<h2>))/iu', $new_es ) === 1;
    $en_empty = preg_match( '/<h2>\s*Related guides\s*<\/h2>\s*(?:<ul>\s*<\/ul>|(?=<h2>))/iu', $new_en ) === 1;
    if ( $es_empty || $en_empty ) {
        throw new RuntimeException( 'Empty related heading still present: ' . $slug );
    }

    $results[] = array(
        'id' => (int) $post->ID,
        'slug' => $slug,
        'changed_es' => $new_es !== $es,
        'changed_en' => $new_en !== $en,
    );
}

echo "EMDO_RELATED_GUIDES_FIX_BEGIN\n";
echo wp_json_encode( array( 'count' => count( $results ), 'posts' => $results ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
echo "EMDO_RELATED_GUIDES_FIX_END\n";
