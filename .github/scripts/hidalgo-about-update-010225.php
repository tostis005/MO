<?php
/**
 * One-time guarded production update for Hidalgo de la Jara store description.
 * Updates only the two WCFM fields that currently contain the approved old copy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$user_id = 6;
$old_needle = 'Los ganaderos que integran Hidalgo de la Jara';
$new_needle = 'La historia de <strong>Hidalgo de la Jara</strong> está profundamente ligada';

$new_description = <<<'HTML'
<p>La historia de <strong>Hidalgo de la Jara</strong> está profundamente ligada al cerdo ibérico y a la dehesa de Los Pedroches. Son cinco generaciones dedicadas a la cría del <strong>cerdo 100 % ibérico</strong>, una raza que conocen desde el origen y en torno a la que han desarrollado toda su forma de trabajar.</p>
<p>Todo empieza en el campo. Los cerdos nacen y se crían en libertad, primero junto a sus madres y, cuando pueden valerse por sí mismos, aprovechando los recursos que encuentran en la dehesa. Los lechones nacidos en otoño pueden incluso disfrutar de sus primeras bellotas durante el destete; más adelante llegará la montanera, cuando los animales destinados a bellota realizan su engorde final alimentándose de bellotas, hierba y otros recursos naturales del entorno.</p>
<p>La dehesa continúa siendo parte de su alimentación durante el resto del año. En verano, cuando sus recursos naturales son más escasos, se siembran <strong>garbanzos, habas, veza, avena y otros cereales y leguminosas</strong> que los cerdos aprovechan <em>ad libitum</em>, alimentándose <strong>a diente directamente sobre el terreno</strong>. Una forma de mantener la cría estrechamente vinculada al campo también fuera de la montanera.</p>
<p>Con los años, esa experiencia en la cría del ibérico llevó a Hidalgo de la Jara a ocuparse también de la elaboración de sus productos. En <strong>Villanueva de Córdoba, en pleno corazón de Los Pedroches</strong>, controlan el proceso desde la cría de los animales hasta la elaboración y maduración de sus jamones y paletas.</p>
<p>Esa misma vinculación con el entorno está presente en sus instalaciones, donde cuentan con un sistema de autoconsumo mediante <strong>energía solar fotovoltaica</strong>, incorporando el uso de energía renovable a una actividad que siempre ha estado estrechamente ligada a los recursos y al paisaje de Los Pedroches.</p>
<p>Después de la salazón y el asentamiento en condiciones controladas, los jamones y paletas pasan a bodegas naturales donde las temperaturas propias de la zona acompañan lentamente su maduración. Los inviernos fríos y los veranos calurosos forman parte de un proceso de curación pausado que, en sus jamones, supera los <strong>30 meses</strong>, dando tiempo a que cada pieza desarrolle sus aromas, textura y carácter.</p>
<p>La vinculación de Hidalgo de la Jara con Los Pedroches va más allá de su ubicación. Formaron parte de los impulsores de la <strong>Denominación de Origen Los Pedroches</strong>, participando junto a otros ganaderos e industrias de la comarca en el desarrollo de los criterios que acabarían definiendo la denominación.</p>
<p>Cinco generaciones después, Hidalgo de la Jara continúa trabajando el cerdo ibérico desde el mismo lugar del que parte todo su recorrido: la dehesa. Una forma de entender la ganadería y la elaboración en la que la experiencia, el entorno y el respeto por los tiempos de cada proceso siguen marcando el carácter de sus productos.</p>
HTML;

$user = get_user_by( 'id', $user_id );
if ( ! $user ) {
    fwrite( STDERR, "HIDALGO_UPDATE_ABORT: user not found\n" );
    exit( 2 );
}

$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
$direct   = get_user_meta( $user_id, '_store_description', true );

if ( ! is_array( $settings ) || ! isset( $settings['store_name'], $settings['store_slug'], $settings['shop_description'] ) ) {
    fwrite( STDERR, "HIDALGO_UPDATE_ABORT: unexpected WCFM settings shape\n" );
    exit( 3 );
}

if ( $settings['store_name'] !== 'Hidalgo de la Jara' || $settings['store_slug'] !== 'hidalgo-de-la-jara' ) {
    fwrite( STDERR, "HIDALGO_UPDATE_ABORT: producer identity mismatch\n" );
    exit( 4 );
}

$direct_has_old   = is_string( $direct ) && strpos( $direct, $old_needle ) !== false;
$settings_has_old = is_string( $settings['shop_description'] ) && strpos( $settings['shop_description'], $old_needle ) !== false;
$direct_is_new    = is_string( $direct ) && strpos( $direct, $new_needle ) !== false;
$settings_is_new  = is_string( $settings['shop_description'] ) && strpos( $settings['shop_description'], $new_needle ) !== false;

if ( $direct_is_new && $settings_is_new ) {
    echo "__HIDALGO_UPDATE__=already_applied\n";
    exit( 0 );
}

if ( ! $direct_has_old || ! $settings_has_old ) {
    fwrite( STDERR, "HIDALGO_UPDATE_ABORT: approved old copy not found in both fields\n" );
    exit( 5 );
}

$old_direct_hash   = hash( 'sha256', $direct );
$old_settings_hash = hash( 'sha256', $settings['shop_description'] );

$direct_ok = update_user_meta( $user_id, '_store_description', $new_description );
$settings['shop_description'] = $new_description;
$settings_ok = update_user_meta( $user_id, 'wcfmmp_profile_settings', $settings );

clean_user_cache( $user_id );
wp_cache_delete( $user_id, 'user_meta' );

$direct_after   = get_user_meta( $user_id, '_store_description', true );
$settings_after = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );

if (
    ! is_string( $direct_after ) ||
    strpos( $direct_after, $new_needle ) === false ||
    ! is_array( $settings_after ) ||
    ! isset( $settings_after['shop_description'] ) ||
    strpos( $settings_after['shop_description'], $new_needle ) === false
) {
    fwrite( STDERR, "HIDALGO_UPDATE_ABORT: post-update verification failed\n" );
    exit( 6 );
}

echo "__HIDALGO_UPDATE__=success\n";
echo "__USER_ID__={$user_id}\n";
echo "__OLD_DIRECT_SHA256__={$old_direct_hash}\n";
echo "__OLD_SETTINGS_SHA256__={$old_settings_hash}\n";
echo "__NEW_SHA256__=" . hash( 'sha256', $new_description ) . "\n";
