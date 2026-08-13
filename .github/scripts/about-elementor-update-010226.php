<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$page = get_page_by_path( 'quienes-somos', OBJECT, 'page' );
if ( ! $page || 'page' !== $page->post_type ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: quienes-somos page not found\n" );
    exit( 2 );
}

$widget_id  = '4807dca2';
$old_marker = 'Todo comenzó en 2014 cuando empezamos a especializarnos en la administración de fincas agrícolas.';
$new_marker = 'Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.';

$new_editor = <<<'HTML'
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

$raw = (string) get_post_meta( $page->ID, '_elementor_data', true );
if ( '' === $raw ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: _elementor_data empty\n" );
    exit( 3 );
}

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: invalid Elementor JSON\n" );
    exit( 4 );
}

$found = 0;
$changed = 0;
$original_editor = null;

$walk = function ( array &$elements ) use ( &$walk, $widget_id, $old_marker, $new_marker, $new_editor, &$found, &$changed, &$original_editor ): void {
    foreach ( $elements as &$element ) {
        if ( ! is_array( $element ) ) { continue; }

        if ( isset( $element['id'] ) && $widget_id === (string) $element['id'] ) {
            $found++;
            if ( 'widget' !== (string) ( $element['elType'] ?? '' ) ) {
                throw new RuntimeException( 'Target element is not a widget.' );
            }
            $editor = (string) ( $element['settings']['editor'] ?? '' );
            $original_editor = $editor;

            if ( false !== strpos( $editor, $new_marker ) ) {
                continue;
            }
            if ( false === strpos( $editor, $old_marker ) ) {
                throw new RuntimeException( 'Expected old copy not found in target widget.' );
            }

            if ( ! isset( $element['settings'] ) || ! is_array( $element['settings'] ) ) {
                $element['settings'] = array();
            }
            $element['settings']['editor'] = $new_editor;
            $changed++;
        }

        if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
            $walk( $element['elements'] );
        }
    }
    unset( $element );
};

try {
    $walk( $data );
} catch ( Throwable $e ) {
    fwrite( STDERR, 'ABOUT_ELEMENTOR_ABORT: ' . $e->getMessage() . "\n" );
    exit( 5 );
}

if ( 1 !== $found ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: target widget count={$found}\n" );
    exit( 6 );
}

if ( 0 === $changed ) {
    echo "ABOUT_ELEMENTOR_ALREADY_APPLIED page_id={$page->ID} widget={$widget_id}\n";
    delete_post_meta( $page->ID, '_elementor_element_cache' );
    clean_post_cache( $page->ID );
    exit( 0 );
}

$backup_key = '_emdo_elementor_about_backup_20260813_010226';
if ( '' === (string) get_post_meta( $page->ID, $backup_key, true ) && is_string( $original_editor ) ) {
    update_post_meta( $page->ID, $backup_key, $original_editor );
}

$encoded = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( ! is_string( $encoded ) || '' === $encoded ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: JSON encoding failed\n" );
    exit( 7 );
}

update_post_meta( $page->ID, '_elementor_data', wp_slash( $encoded ) );
delete_post_meta( $page->ID, '_elementor_element_cache' );
clean_post_cache( $page->ID );

$check_raw = (string) get_post_meta( $page->ID, '_elementor_data', true );
$check = json_decode( $check_raw, true );
if ( ! is_array( $check ) ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: stored JSON invalid after update\n" );
    exit( 8 );
}

$stored_new = 0;
$stored_old = 0;
$verify = function ( array $elements ) use ( &$verify, $widget_id, $new_marker, $old_marker, &$stored_new, &$stored_old ): void {
    foreach ( $elements as $element ) {
        if ( ! is_array( $element ) ) { continue; }
        if ( isset( $element['id'] ) && $widget_id === (string) $element['id'] ) {
            $editor = (string) ( $element['settings']['editor'] ?? '' );
            $stored_new += substr_count( $editor, $new_marker );
            $stored_old += substr_count( $editor, $old_marker );
        }
        if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
            $verify( $element['elements'] );
        }
    }
};
$verify( $check );

if ( $stored_new < 1 || $stored_old > 0 ) {
    fwrite( STDERR, "ABOUT_ELEMENTOR_ABORT: verification new={$stored_new} old={$stored_old}\n" );
    exit( 9 );
}

echo "ABOUT_ELEMENTOR_UPDATE_OK page_id={$page->ID} widget={$widget_id} new={$stored_new} old={$stored_old}\n";
