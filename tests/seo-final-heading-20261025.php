<?php
/** Offline regression tests for the final duplicate article H1 guard. */
define( 'ABSPATH', __DIR__ );
function wp_strip_all_tags( $text, $remove_breaks = false ) { return strip_tags( $text ); }
function remove_accents( $text ) { return strtr( $text, 'ÁÉÍÓÚÜáéíóúü', 'AEIOUUaeiouu' ); }
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
echo 'PASS: final H1 guard keeps one main H1 and preserves distinct content headings' . PHP_EOL;
