<?php
/**
 * Validate one production article HTML response against the approved
 * 20-page GSC manifest. Read-only. Prints booleans only, no page contents.
 *
 * php tests/seo-native-mu-html-verify-20261014.php manifest.json /url/ page.html 200
 */
declare( strict_types=1 );
if ( $argc !== 5 ) { fwrite( STDERR, "SEO_HTML_VERIFY_INVALID_ARGS\n" ); exit( 2 ); }
$manifest = json_decode( (string) @file_get_contents( $argv[1] ), true );
$path = $argv[2];
$html = @file_get_contents( $argv[3] );
$http = $argv[4];
if ( ! is_array( $manifest ) || ! isset( $manifest[ $path ]['title'], $manifest[ $path ]['description'] ) || ! is_string( $html ) || strlen( $html ) > 25000000 ) {
    fwrite( STDERR, "SEO_HTML_VERIFY_INVALID_INPUT\n" ); exit( 2 );
}
function seo_norm_20261014( $value ): string {
    return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}
$title_ok = false;
if ( preg_match( '~<title\b[^>]*>(.*?)</title>~is', $html, $match ) ) {
    $actual_title = seo_norm_20261014( strip_tags( $match[1] ) );
    $title_ok = str_starts_with( $actual_title, seo_norm_20261014( $manifest[ $path ]['title'] ) );
}
$h1_count = preg_match_all( '~<h1(?:\s|>)~i', $html );
$desc_values = array();
if ( preg_match_all( '~<meta\b[^>]*>~is', $html, $tags ) ) {
    foreach ( $tags[0] as $tag ) {
        if ( ! preg_match( '~\bname\s*=\s*([\x22\x27])description\1~i', $tag ) ) { continue; }
        if ( preg_match( '~\bcontent\s*=\s*([\x22\x27])(.*?)\1~is', $tag, $content ) ) {
            $desc_values[] = seo_norm_20261014( $content[2] );
        } else {
            $desc_values[] = '';
        }
    }
}
$desc_ok = count( $desc_values ) === 1 &&
    $desc_values[0] === seo_norm_20261014( $manifest[ $path ]['description'] );
$ok = $http === '200' && $title_ok && $h1_count === 1 && $desc_ok;
echo 'SEO_GSC_HTML_PAGE http=' . ( $http === '200' ? '200' : 'non200' )
    . ' title=' . ( $title_ok ? 'yes' : 'no' )
    . ' h1=' . (int) $h1_count
    . ' desc=' . ( $desc_ok ? 'yes' : 'no' )
    . ' description_tags=' . count( $desc_values )
    . ' language=' . ( str_starts_with( $path, '/en/' ) ? 'EN' : 'ES' )
    . "\n";
exit( $ok ? 0 : 1 );
