<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

function emdo_tole_key( $value ) {
    $value = remove_accents( strtolower( (string) $value ) );
    return preg_replace( '/[^a-z0-9]+/', '', $value );
}

$candidates = array();
global $wpdb;
$user_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
    'wcfmmp_profile_settings'
) );

foreach ( $user_ids as $user_id ) {
    $settings = get_user_meta( (int) $user_id, 'wcfmmp_profile_settings', true );
    if ( ! is_array( $settings ) ) { continue; }
    $store_name = (string) ( $settings['store_name'] ?? '' );
    $store_slug = (string) ( $settings['store_slug'] ?? '' );
    if ( emdo_tole_key( $store_name ) === 'tolecarnes' || emdo_tole_key( $store_slug ) === 'tolecarnes' ) {
        $candidates[] = array( 'id' => (int) $user_id, 'settings' => $settings );
    }
}

if ( count( $candidates ) !== 1 ) {
    fwrite( STDERR, 'TOLE_UPDATE_ABORT: expected one Tolecarnes profile, found ' . count( $candidates ) . "\n" );
    exit( 2 );
}

$user_id = $candidates[0]['id'];
$settings = $candidates[0]['settings'];
$direct = get_user_meta( $user_id, '_store_description', true );
$shop = (string) ( $settings['shop_description'] ?? '' );

if ( ! is_string( $direct ) || ! isset( $settings['shop_description'] ) ) {
    fwrite( STDERR, "TOLE_UPDATE_ABORT: unexpected description fields\n" );
    exit( 3 );
}

$new_needle = 'La historia de <strong>Tolecarnes</strong> está ligada a la ganadería de vacuno desde <strong>1960</strong>.';
$new_description = <<<'HTML'
<p>La historia de <strong>Tolecarnes</strong> está ligada a la ganadería de vacuno desde <strong>1960</strong>. Son tres generaciones dedicadas a la cría de ganado en los Montes de Toledo, una experiencia que ha ido pasando de una generación a la siguiente y que sigue marcando su forma de entender la ganadería.</p>
<p>Su explotación se encuentra en una dehesa situada en la cara norte de los <strong>Montes de Toledo</strong>, un entorno especialmente favorable para el crecimiento natural de los pastos. Durante buena parte del año, los animales pastan libremente y aprovechan directamente los recursos que ofrece el campo, manteniendo una crianza estrechamente vinculada al territorio.</p>
<p>La alimentación se adapta a las condiciones de cada época. En los meses de mayor abundancia se recoge parte del pasto para disponer de él durante el invierno y, cuando es necesario complementar la dieta, utilizan <strong>piensos 100 % naturales elaborados exclusivamente con ingredientes de origen vegetal</strong>. Estos se producen en la cooperativa de Menasalbas de la que forman parte, una vinculación que les permite <strong>seguir de cerca su elaboración y mantener un mayor control sobre la alimentación de su ganado</strong>.</p>
<p>A lo largo de los años han combinado esa crianza tradicional con un trabajo de selección del ganado en el que tienen en cuenta la genética, pero también todo lo que rodea al animal durante su vida. El entorno, la alimentación y la actividad forman parte de un mismo proceso orientado a conseguir una carne equilibrada y de calidad.</p>
<p>La experiencia acumulada durante tres generaciones les ha permitido buscar una mayor regularidad en sus carnes, prestando especial atención a características como la <strong>terneza, la jugosidad y la infiltración de grasa</strong>, sin perder de vista el sabor propio de cada corte.</p>
<p>Hoy, Tolecarnes continúa desarrollando su actividad ganadera en los Montes de Toledo, manteniendo una forma de trabajar en la que la dehesa, la alimentación y el conocimiento transmitido durante generaciones siguen siendo la base de sus carnes.</p>
HTML;

if ( strpos( $direct, $new_needle ) !== false && strpos( $shop, $new_needle ) !== false ) {
    echo "TOLE_UPDATE=already_applied\n";
    exit( 0 );
}

if ( $direct !== $shop ) {
    fwrite( STDERR, "TOLE_UPDATE_ABORT: WCFM description fields are not synchronized before update\n" );
    exit( 4 );
}

$backup_key = '_emdo_tolecarnes_about_backup_20260813';
if ( '' === (string) get_user_meta( $user_id, $backup_key, true ) ) {
    update_user_meta( $user_id, $backup_key, $direct );
}

update_user_meta( $user_id, '_store_description', $new_description );
$settings['shop_description'] = $new_description;
update_user_meta( $user_id, 'wcfmmp_profile_settings', $settings );
clean_user_cache( $user_id );
wp_cache_delete( $user_id, 'user_meta' );

$after_direct = get_user_meta( $user_id, '_store_description', true );
$after_settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
$after_shop = is_array( $after_settings ) ? (string) ( $after_settings['shop_description'] ?? '' ) : '';
if ( strpos( (string) $after_direct, $new_needle ) === false || strpos( $after_shop, $new_needle ) === false ) {
    fwrite( STDERR, "TOLE_UPDATE_ABORT: post-update verification failed\n" );
    exit( 5 );
}
if ( strpos( (string) $after_direct, 'ToleCarnes' ) !== false || strpos( $after_shop, 'ToleCarnes' ) !== false ) {
    fwrite( STDERR, "TOLE_UPDATE_ABORT: old capitalization remains\n" );
    exit( 6 );
}

echo "TOLE_UPDATE=success\n";
echo "TOLE_USER_ID={$user_id}\n";
echo "TOLE_STORE_SLUG=" . (string) ( $after_settings['store_slug'] ?? '' ) . "\n";
