<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$page = get_page_by_path( 'quienes-somos', OBJECT, 'page' );
if ( ! $page || 'page' !== $page->post_type ) {
    fwrite( STDERR, "ABOUT_UPDATE_ABORT: quienes-somos page not found\n" );
    exit( 2 );
}

$old_marker = 'Todo comenzó en 2014 cuando empezamos a especializarnos en la administración de fincas agrícolas.';
$new_marker = 'Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.';
$current    = (string) $page->post_content;

if ( false !== strpos( wp_strip_all_tags( $current ), $new_marker ) ) {
    echo "ABOUT_UPDATE_ALREADY_APPLIED page_id={$page->ID}\n";
    exit( 0 );
}

if ( false === strpos( wp_strip_all_tags( $current ), $old_marker ) ) {
    fwrite( STDERR, "ABOUT_UPDATE_ABORT: expected old copy not found on page {$page->ID}\n" );
    exit( 3 );
}

$new_content = <<<'HTML'
<h2>Nuestra historia</h2>
<p>Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.</p>
<p>Ese trabajo nos permitió conocer de primera mano una de las realidades más difíciles del campo: muchas veces, quien produce es precisamente quien menos capacidad tiene para decidir el valor de lo que produce.</p>
<p>La rentabilidad de una explotación podía depender de una buena cosecha, pero también de un precio de venta que el productor no controlaba. Cuando ambas cosas acompañaban, el año podía ser bueno. Cuando la producción bajaba o los precios caían, la situación cambiaba por completo.</p>
<p>Y en el campo, ningún año está garantizado.</p>
<p>En 2017 decidimos buscar una alternativa con nuestras propias producciones. Creamos nuestra primera tienda online con una idea sencilla: intentar llegar directamente al consumidor y tener una mayor capacidad para decidir cómo y a qué precio vender aquello que producíamos.</p>
<p>Los comienzos no fueron fáciles. Apenas teníamos experiencia en comercio electrónico y durante los primeros años tuvimos que aprender prácticamente desde cero.</p>
<p>Pero teníamos algo importante a nuestro favor: confiábamos en nuestros productos.</p>
<p>Poco a poco comenzaron a llegar los primeros clientes. Después, las recomendaciones y las personas que volvían a comprar. Descubrimos que había consumidores que no solo valoraban el producto, sino también poder conocer su procedencia y comprarlo de una forma más directa.</p>
<p>Aquella experiencia fue la que terminó dando sentido a El Mercado de Origen.</p>
<p>Nos hizo plantearnos una pregunta muy sencilla: si este modelo había funcionado con nuestros propios productos, ¿por qué no podía servir también para otros productores?</p>
<p>Así nació la idea de construir un proyecto en el que el origen volviera a tener el protagonismo que merece.</p>
<p>Queremos que detrás de cada producto vuelva a reconocerse lo que realmente le da valor: su procedencia, el trabajo de quien lo produce y la forma en la que ha sido elaborado.</p>
<p>Porque para nosotros el origen no es solo el lugar del que viene un producto. Es todo lo que hay detrás de él. Y eso es precisamente lo que queremos poner en valor en El Mercado de Origen.</p>
HTML;

$backup_key = '_emdo_about_backup_20260813_010226';
if ( '' === (string) get_post_meta( $page->ID, $backup_key, true ) ) {
    update_post_meta( $page->ID, $backup_key, $current );
}

$result = wp_update_post(
    array(
        'ID'           => $page->ID,
        'post_content' => $new_content,
    ),
    true
);

if ( is_wp_error( $result ) ) {
    fwrite( STDERR, 'ABOUT_UPDATE_ABORT: ' . $result->get_error_message() . "\n" );
    exit( 4 );
}

clean_post_cache( $page->ID );

$updated = get_post( $page->ID );
$plain   = wp_strip_all_tags( (string) $updated->post_content );
if ( false === strpos( $plain, $new_marker ) || false !== strpos( $plain, $old_marker ) ) {
    fwrite( STDERR, "ABOUT_UPDATE_ABORT: post-write verification failed\n" );
    exit( 5 );
}

echo "ABOUT_UPDATE_OK page_id={$page->ID} slug={$page->post_name}\n";
