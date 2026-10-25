<?php
/** Offline regression tests for the final duplicate article H1 guard. */
define( 'ABSPATH', __DIR__ );
function wp_strip_all_tags( $text, $remove_breaks = false ) { return strip_tags( $text ); }
function remove_accents( $text ) { return strtr( $text, 'ÁÉÍÓÚÜáéíóúü', 'AEIOUUaeiouu' ); }
$GLOBALS['seo_h1_on_post'] = false;
$GLOBALS['seo_h1_admin'] = false;
$GLOBALS['seo_h1_removed'] = array();
$GLOBALS['seo_h1_hooked'] = array();
function add_action( $hook, $callback, $priority = 10 ) {
    $GLOBALS['seo_h1_hooked'][] = array( $hook, $callback, $priority );
}
function remove_action( $hook, $callback, $priority = 10 ) {
    $GLOBALS['seo_h1_removed'][] = array( $hook, $callback, $priority );
}
function is_admin() { return $GLOBALS['seo_h1_admin']; }
function is_singular( $post_type ) { return 'post' === $post_type && $GLOBALS['seo_h1_on_post']; }
require dirname( __DIR__ ) . '/elmercadodeorigen-child/inc/seo-final-heading-20261025.php';

$cases = array(
    array(
        'title' => 'Garbanzos: remojo y cocción',
        'body' => '<h1>Garbanzos: remojo y cocción</h1><p>Respuesta fiable.</p>',
        'expected' => '<p>Respuesta fiable.</p>',
    ),
    array(
        'title' => 'Legumbre seca por persona',
        'body' => '<div><h1 class="entry-title"><span>Legumbre seca por persona</span></h1><h2>Cantidades</h2></div>',
        'expected' => '<div><h2>Cantidades</h2></div>',
    ),
    array(
        'title' => 'Beef & vegetables',
        'body' => '<h1>Beef &amp; vegetables</h1><h2>100 grams</h2>',
        'expected' => '<h2>100 grams</h2>',
    ),
    array(
        'title' => 'Garbanzos',
        'body' => '<h1>Advertencia de conservación</h1><p>Texto</p>',
        'expected' => '<h1>Advertencia de conservación</h1><p>Texto</p>',
    ),
    array(
        'title' => 'Sin H1',
        'body' => '<h2>Subtítulo</h2><p>Contenido</p>',
        'expected' => '<h2>Subtítulo</h2><p>Contenido</p>',
    ),
);
foreach ( $cases as $index => $case ) {
    $got = elmercado_blog_final_h1_guard_20261025( $case['body'], $case['title'] );
    if ( $got !== $case['expected'] ) {
        fwrite( STDERR, 'FAIL: duplicate H1 test #' . ( $index + 1 ) . ': ' . $got . PHP_EOL );
        exit( 1 );
    }
}
// Woostify parent .page-header H1 must be removed at the 'wp' hook only for
// actual blog posts; the custom editorial template keeps its own visible H1.
$expected_callback = array( 'wp', 'elmercado_blog_skip_woostify_post_page_header_20261025', 100 );
if ( ! in_array( $expected_callback, $GLOBALS['seo_h1_hooked'], true ) ) {
    fwrite( STDERR, "FAIL: missing wp hook for native H1 removal\n" ); exit( 1 );
}
$GLOBALS['seo_h1_on_post'] = true;
elmercado_blog_skip_woostify_post_page_header_20261025();
$expected_removal = array( 'woostify_after_header', 'woostify_page_header', 10 );
if ( $GLOBALS['seo_h1_removed'] !== array( $expected_removal ) ) {
    fwrite( STDERR, "FAIL: incorrect Woostify parent title removal\n" ); exit( 1 );
}
$GLOBALS['seo_h1_removed'] = array();
$GLOBALS['seo_h1_on_post'] = false; // other categories, pages, products
elmercado_blog_skip_woostify_post_page_header_20261025();
$GLOBALS['seo_h1_admin'] = true;
$GLOBALS['seo_h1_on_post'] = true;
elmercado_blog_skip_woostify_post_page_header_20261025();
if ( $GLOBALS['seo_h1_removed'] ) {
    fwrite( STDERR, "FAIL: parent header altered outside public posts\n" ); exit( 1 );
}
echo 'PASS: unique blog H1; native Woostify title removed on posts only' . PHP_EOL;
