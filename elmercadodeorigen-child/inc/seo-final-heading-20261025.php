<?php
/**
 * Final, narrowly scoped H1 deduplication for WordPress blog articles.
 *
 * The single.php template already prints the principal H1. Some legacy post
 * bodies still contain an identical H1; eliminate only exact semantic copies
 * AFTER all the_content filters have run. Never change stored post_content,
 * URLs or headings with different wording.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @param string $content Rendered article body.
 * @param string $title   Visible main article title.
 * @return string
 */
function elmercado_blog_final_h1_guard_20261025( $content, $title ) {
    if ( ! is_string( $content ) || false === stripos( $content, '<h1' ) ) {
        return $content;
    }

    $normalize = static function ( $value ) {
        $plain = html_entity_decode( wp_strip_all_tags( (string) $value, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $plain = preg_replace( '/\s+/u', ' ', trim( (string) $plain ) );
        $plain = remove_accents( (string) $plain );
        return function_exists( 'mb_strtolower' ) ? mb_strtolower( $plain, 'UTF-8' ) : strtolower( $plain );
    };
    $expected = $normalize( $title );

    if ( '' === $expected ) {
        return $content;
    }

    $clean = preg_replace_callback(
        '~<h1\b[^>]*>(.*?)</h1>~isu',
        static function ( $matches ) use ( $expected, $normalize ) {
            return $expected === $normalize( $matches[1] ) ? '' : $matches[0];
        },
        $content
    );

    return is_string( $clean ) ? $clean : $content;
}
