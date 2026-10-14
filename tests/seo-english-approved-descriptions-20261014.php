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
echo "OK: 16 approved EN descriptions, 4 ES unaffected; other pages, product, draft and host protected.\n";
