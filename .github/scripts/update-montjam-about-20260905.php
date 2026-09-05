<?php
/**
 * One-purpose production update for Montjam producer About copy.
 * Montjam is a WCFM vendor, whose public About tab reads the _store_description user meta.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$about = <<<'TEXT'
La historia de Montjam está ligada a El Repilado, en plena Sierra de Aracena y Picos de Aroche, una de las zonas con mayor tradición en la elaboración de jamones y paletas ibéricas de Huelva. Detrás de la marca se encuentra Pedro Parra e Hijos, una familia que ha ido transmitiendo su oficio de generación en generación y que hoy alcanza ya la cuarta generación dedicada a la elaboración de productos derivados del cerdo ibérico.

El lugar forma una parte importante de su manera de trabajar. El Repilado se encuentra en un entorno históricamente vinculado a la elaboración del jamón, donde las condiciones naturales de la sierra han acompañado durante décadas los procesos de secado y maduración de las piezas. Es precisamente allí donde Montjam mantiene sus secaderos y bodegas naturales.

A lo largo de los años, la familia se ha especializado en la elaboración de jamones, paletas, lomos y embutidos ibéricos. Un conocimiento construido campaña tras campaña, en el que cada pieza atraviesa lentamente las distintas fases necesarias hasta alcanzar el punto de maduración buscado.

Los secaderos y bodegas ocupan un lugar especialmente importante dentro de ese proceso. Montjam conserva instalaciones naturales en las que jamones y paletas permanecen durante largos periodos de curación, dejando que el tiempo y las condiciones propias de la Sierra de Aracena acompañen la evolución de cada pieza. La propia casa sitúa la tradición de sus bodegas y secaderos en campañas que se remontan a 1887.

Ese vínculo con el territorio se refleja también en una parte de su producción amparada por la Denominación de Origen Protegida Jabugo. Bajo ella elaboran jamones y paletas que deben cumplir los requisitos establecidos por la denominación y cuyo proceso de elaboración permanece ligado a los municipios que forman parte de su zona protegida.

Junto a esas piezas, Montjam trabaja diferentes categorías de ibérico, desde jamones y paletas de bellota 100 % ibéricos hasta otras elaboraciones de bellota y una selección de lomos y embutidos. Una variedad que nace de una especialización muy concreta: el conocimiento de la curación y transformación de los productos del cerdo ibérico.

Cuatro generaciones después, Montjam continúa elaborando sus productos desde El Repilado, manteniendo como eje de su trabajo aquello que ha definido históricamente a esta zona de Huelva: experiencia, tiempo y unas condiciones naturales especialmente ligadas a la maduración del jamón ibérico.
TEXT;

$vendor = get_user_by( 'login', 'montjam' );
if ( ! $vendor instanceof WP_User ) {
    fwrite( STDERR, "MONTJAM_ABOUT_ABORT: user login montjam not found\n" );
    exit( 2 );
}

if ( ! in_array( 'wcfm_vendor', (array) $vendor->roles, true ) ) {
    fwrite( STDERR, sprintf( "MONTJAM_ABOUT_ABORT: user id=%d login=%s is not wcfm_vendor; roles=%s\n", (int) $vendor->ID, $vendor->user_login, implode( ',', (array) $vendor->roles ) ) );
    exit( 3 );
}

$vendor_id = (int) $vendor->ID;
$previous = (string) get_user_meta( $vendor_id, '_store_description', true );

$result = update_user_meta( $vendor_id, '_store_description', $about );
if ( false === $result && $previous !== $about ) {
    fwrite( STDERR, "MONTJAM_ABOUT_ABORT: update_user_meta(_store_description) failed\n" );
    exit( 4 );
}

$saved = (string) get_user_meta( $vendor_id, '_store_description', true );
if ( $saved !== $about ) {
    fwrite( STDERR, "MONTJAM_ABOUT_ABORT: verification mismatch after save\n" );
    exit( 5 );
}

printf(
    "MONTJAM_ABOUT_OK id=%d login=%s display=%s nicename=%s meta=_store_description chars=%d previous_chars=%d\n",
    $vendor_id,
    $vendor->user_login,
    $vendor->display_name,
    $vendor->user_nicename,
    strlen( $saved ),
    strlen( $previous )
);
