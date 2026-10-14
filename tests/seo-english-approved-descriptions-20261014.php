<?php
/** Standalone AIOSEO regression for the native public English route plugin. */
declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['test_filters'] = array();
$GLOBALS['test_post'] = null;
$GLOBALS['mdoer_public_request_uri'] = '/';
$_SERVER['HTTP_HOST'] = 'unmatched.invalid';
$_SERVER['REQUEST_URI'] = '/';

function add_filter( $name, $callback, $priority = 10, $args = 1 ) {
    $GLOBALS['test_filters'][ $name ][] = array( 'callback' => $callback, 'priority' => $priority );
}
function add_action( $name, $callback, $priority = 10, $args = 1 ) {}
function get_option( $name ) { return 'https://unmatched.invalid'; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function is_admin(): bool { return false; }
function is_front_page(): bool { return false; }
function is_singular(): bool { return false; }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function is_shop(): bool { return false; }
function get_queried_object() { return $GLOBALS['test_post']; }
function trailingslashit( $path ): string { return rtrim( (string) $path, '/' ) . '/'; }
function get_post_meta( $id, $key, $single = false ) {
    return '_en_US_post_excerpt' === $key ? 'Previously translated excerpt from WordPress' : '';
}
function wp_strip_all_tags( $value, $remove_breaks = false ) { return strip_tags( (string) $value ); }
function strip_shortcodes( $value ) { return $value; }
function wp_html_excerpt( $value, $n, $more = '' ) { return substr( (string) $value, 0, $n ) . $more; }
class WP_Post {
    public int $ID = 123;
    public string $post_type = 'post';
    public string $post_status = 'publish';
}
require __DIR__ . '/../elmercadodeorigen-child/inc/seo-gsc-batch-20-20261012.php';
require __DIR__ . '/../mu-plugins/mdo-production-english-seo-routes.php';

function ensure_meta( $good, $message ): void {
    if ( ! $good ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
$hook = $GLOBALS['test_filters']['aioseo_description'] ?? array();
$native = array_filter( $hook, static fn( $record ) => $record['priority'] === PHP_INT_MAX );
ensure_meta( count( $native ) >= 2, 'Native and approved GSC SEO filters registered' );
$callback = end( $native )['callback']; // The MU route's final override callback.

$_SERVER['HTTP_HOST'] = 'www.elmercadodeorigen.com';
$GLOBALS['test_post'] = new WP_Post();
$targets = elmercado_gsc_batch_20_20261012();
$en = 0; $es = 0;
foreach ( $targets as $path => $item ) {
    $GLOBALS['mdoer_public_request_uri'] = $path;
    $result = $callback( 'Original Spanish metadata' );
    if ( str_starts_with( $path, '/en/' ) ) {
        ++$en;
        ensure_meta( $result === $item['description'], 'Approved English metadata: ' . $path );
    } else {
        ++$es;
        ensure_meta( $result === 'Original Spanish metadata', 'Spanish metadata must not change' );
    }
}
ensure_meta( $en === 16 && $es === 4, 'Exactly 16 EN and 4 ES' );
$GLOBALS['mdoer_public_request_uri'] = '/en/not-selected/';
ensure_meta( str_starts_with( $callback( 'Original' ), 'Previously translated excerpt from WordPress' ), 'Other EN pages preserve translated excerpt' );
$GLOBALS['mdoer_public_request_uri'] = '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/';
$GLOBALS['test_post']->post_type = 'product';
ensure_meta( str_starts_with( $callback( 'Original' ), 'Previously translated excerpt from WordPress' ), 'Products preserve existing translation rules' );
$GLOBALS['test_post']->post_type = 'post';
$GLOBALS['test_post']->post_status = 'draft';
ensure_meta( str_starts_with( $callback( 'Original' ), 'Previously translated excerpt from WordPress' ), 'Draft content must not get approved production metadata' );
$_SERVER['HTTP_HOST'] = 'unmatched.invalid';
ensure_meta( $callback( 'Original' ) === 'Original', 'Unrelated host must not be changed' );

// Final HTML may be rewritten after the AIOSEO hook by TranslatePress.
// Verify the outermost native MU buffer restores exactly one meta description
// without disturbing title, canonical, robots, H1 or other body HTML.
$_SERVER['HTTP_HOST'] = 'www.elmercadodeorigen.com';
$GLOBALS['test_post']->post_type = 'post';
$GLOBALS['test_post']->post_status = 'publish';
$GLOBALS['mdoer_public_request_uri'] = '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/';
$html = '<!doctype html><html lang="en"><head><title>Original SEO title</title><meta name="description" content="Old translated excerpt"><meta name="robots" content="index,follow"><link rel="canonical" href="https://www.elmercadodeorigen.com/en/example/"></head><body><h1>Keep article heading</h1><p>Keep content as is</p></body></html>';
$fixed = mdoer_gsc_final_description_20261014( $html );
$approved = $targets['/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/']['description'];
ensure_meta( $fixed !== $html, 'Final HTML must change the selected description' );
ensure_meta( str_contains( $fixed, esc_attr( $approved ) ), 'Approved description must be in final HTML' );
ensure_meta( substr_count( $fixed, 'name="description"' ) === 1, 'One meta description only' );
ensure_meta( str_contains( $fixed, '<title>Original SEO title</title>' ), 'Title untouched' );
ensure_meta( str_contains( $fixed, '<meta name="robots" content="index,follow">' ), 'Robots untouched' );
ensure_meta( str_contains( $fixed, '<link rel="canonical" href="https://www.elmercadodeorigen.com/en/example/">' ), 'Canonical untouched' );
ensure_meta( str_contains( $fixed, '<h1>Keep article heading</h1><p>Keep content as is</p>' ), 'Body untouched' );
ensure_meta( $fixed === mdoer_gsc_final_description_20261014( $fixed ), 'Idempotent final HTML' );
$second_tag = str_replace( '</head>', '<meta name="description" content="duplicate"></head>', $html );
ensure_meta( $second_tag === mdoer_gsc_final_description_20261014( $second_tag ), 'Duplicates untouched' );
$nohead = str_replace( '</head>', '', $html );
ensure_meta( $nohead === mdoer_gsc_final_description_20261014( $nohead ), 'Malformed HTML untouched' );
$no_description = str_replace( '<meta name="description" content="Old translated excerpt">', '', $html );
ensure_meta( $no_description === mdoer_gsc_final_description_20261014( $no_description ), 'No injected description' );
$GLOBALS['mdoer_public_request_uri'] = '/en/not-selected/';
ensure_meta( $html === mdoer_gsc_final_description_20261014( $html ), 'Other EN HTML untouched' );
$GLOBALS['mdoer_public_request_uri'] = '/cuanta-carne-calcular-por-persona-corte-receta/';
ensure_meta( $html === mdoer_gsc_final_description_20261014( $html ), 'Spanish HTML untouched' );
$GLOBALS['mdoer_public_request_uri'] = '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/';
$GLOBALS['test_post']->post_type = 'product';
ensure_meta( $html === mdoer_gsc_final_description_20261014( $html ), 'Product HTML untouched' );
$GLOBALS['test_post']->post_type = 'post';
$_SERVER['HTTP_HOST'] = 'unmatched.invalid';
ensure_meta( $html === mdoer_gsc_final_description_20261014( $html ), 'Other domain HTML untouched' );

echo "OK: 16 approved EN descriptions, 4 ES unaffected; other pages, product, draft and host protected.\n";
