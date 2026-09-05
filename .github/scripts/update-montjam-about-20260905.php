<?php
/**
 * One-purpose production update for Montjam producer About copy.
 * This script only updates pv_shop_description for one unambiguous Montjam vendor user.
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

function emdo_montjam_match_key( $value ) {
    $value = remove_accents( strtolower( (string) $value ) );
    return (string) preg_replace( '/[^a-z0-9]+/', '', $value );
}

$vendors = get_users(
    array(
        'role'   => 'wc_product_vendors_admin_vendor',
        'fields' => array( 'ID', 'display_name', 'user_login', 'user_email', 'user_nicename' ),
    )
);

$matches = array();
foreach ( $vendors as $vendor ) {
    $haystack = implode(
        ' ',
        array(
            (string) $vendor->display_name,
            (string) $vendor->user_login,
            (string) $vendor->user_email,
            (string) $vendor->user_nicename,
        )
    );
    $key = emdo_montjam_match_key( $haystack );
    if ( false !== strpos( $key, 'montjam' ) || false !== strpos( $key, 'montham' ) ) {
        $matches[] = $vendor;
    }
}

if ( 1 !== count( $matches ) ) {
    fwrite( STDERR, 'MONTJAM_ABOUT_ABORT: expected exactly one vendor match, found ' . count( $matches ) . "\n" );
    foreach ( $matches as $match ) {
        fwrite( STDERR, sprintf( "MATCH id=%d login=%s display=%s nicename=%s\n", (int) $match->ID, $match->user_login, $match->display_name, $match->user_nicename ) );
    }
    exit( 2 );
}

$vendor = $matches[0];
$vendor_id = (int) $vendor->ID;
$previous = (string) get_user_meta( $vendor_id, 'pv_shop_description', true );

$result = update_user_meta( $vendor_id, 'pv_shop_description', $about );
if ( false === $result && $previous !== $about ) {
    fwrite( STDERR, "MONTJAM_ABOUT_ABORT: update_user_meta failed\n" );
    exit( 3 );
}

$saved = (string) get_user_meta( $vendor_id, 'pv_shop_description', true );
if ( $saved !== $about ) {
    fwrite( STDERR, "MONTJAM_ABOUT_ABORT: verification mismatch after save\n" );
    exit( 4 );
}

printf(
    "MONTJAM_ABOUT_OK id=%d login=%s display=%s nicename=%s chars=%d previous_chars=%d\n",
    $vendor_id,
    $vendor->user_login,
    $vendor->display_name,
    $vendor->user_nicename,
    strlen( $saved ),
    strlen( $previous )
);
