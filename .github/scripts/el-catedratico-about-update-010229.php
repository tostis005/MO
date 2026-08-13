<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

function emdo_catedratico_key( $value ) {
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
    $name_key = emdo_catedratico_key( $store_name );
    $slug_key = emdo_catedratico_key( $store_slug );
    if ( $name_key === 'elcatedratico' || $slug_key === 'elcatedratico' ) {
        $candidates[] = array( 'id' => (int) $user_id, 'settings' => $settings );
    }
}

if ( count( $candidates ) !== 1 ) {
    fwrite( STDERR, 'CATEDRATICO_UPDATE_ABORT: expected one El Catedratico profile, found ' . count( $candidates ) . "\n" );
    exit( 2 );
}

$user_id = $candidates[0]['id'];
$settings = $candidates[0]['settings'];
$direct = get_user_meta( $user_id, '_store_description', true );
$shop = (string) ( $settings['shop_description'] ?? '' );

if ( ! is_string( $direct ) || ! isset( $settings['shop_description'] ) ) {
    fwrite( STDERR, "CATEDRATICO_UPDATE_ABORT: unexpected description fields\n" );
    exit( 3 );
}

$new_needle = 'La historia de <strong>El Catedrático</strong> nace de una experiencia familiar vinculada al ibérico durante <strong>tres generaciones</strong>.';
$new_description = <<<'HTML'
<p>La historia de <strong>El Catedrático</strong> nace de una experiencia familiar vinculada al ibérico durante <strong>tres generaciones</strong>. Un conocimiento transmitido con el tiempo y que hoy se refleja en una forma de trabajar muy centrada en la selección de cada pieza, la elaboración tradicional y el respeto por los tiempos de curación.</p>
<p>El cerdo ibérico de bellota ocupa un lugar fundamental en sus productos. Sus jamones y paletas de bellota 100 % ibéricos proceden de animales criados en libertad en la dehesa, donde aprovechan los recursos naturales del entorno y completan su engorde durante la montanera.</p>
<p>A partir de ahí comienza un proceso en el que el tiempo tiene un papel protagonista. Sus jamones pasan por una maduración lenta que puede prolongarse alrededor de <strong>36 meses</strong>, dejando que cada pieza evolucione de forma pausada hasta alcanzar el punto adecuado de curación.</p>
<p>Antes de salir a la venta, cada jamón se revisa mediante el tradicional <strong>calado</strong>, una técnica que permite comprobar su evolución y valorar si la pieza está preparada. Es una parte del oficio que depende tanto del proceso seguido como de la experiencia acumulada durante años trabajando con el ibérico.</p>
<p>Ese mismo cuidado se traslada a sus embutidos y curados, donde mantienen elaboraciones tradicionales y dejan que cada producto desarrolle lentamente su textura, sus aromas y su sabor. El resultado de ese trabajo ha recibido también reconocimientos fuera de la propia casa, como el <strong>Oro en el Frankfurt International Trophy 2025</strong> obtenido por su salchichón de bellota 100 % ibérico.</p>
<p>Tres generaciones después, El Catedrático continúa trabajando el ibérico desde una forma de entender el oficio en la que la experiencia, la selección de la materia prima y el respeto por el tiempo siguen siendo fundamentales en el carácter de sus productos.</p>
HTML;

if ( strpos( $direct, $new_needle ) !== false && strpos( $shop, $new_needle ) !== false ) {
    echo "CATEDRATICO_UPDATE=already_applied\n";
    echo "CATEDRATICO_USER_ID={$user_id}\n";
    echo "CATEDRATICO_STORE_SLUG=" . (string) ( $settings['store_slug'] ?? '' ) . "\n";
    exit( 0 );
}

if ( $direct !== $shop ) {
    fwrite( STDERR, "CATEDRATICO_UPDATE_ABORT: WCFM description fields are not synchronized before update\n" );
    exit( 4 );
}

$backup_key = '_emdo_el_catedratico_about_backup_20260813';
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
    fwrite( STDERR, "CATEDRATICO_UPDATE_ABORT: post-update verification failed\n" );
    exit( 5 );
}

if ( $after_direct !== $after_shop ) {
    fwrite( STDERR, "CATEDRATICO_UPDATE_ABORT: fields diverged after update\n" );
    exit( 6 );
}

echo "CATEDRATICO_UPDATE=success\n";
echo "CATEDRATICO_USER_ID={$user_id}\n";
echo "CATEDRATICO_STORE_NAME=" . (string) ( $after_settings['store_name'] ?? '' ) . "\n";
echo "CATEDRATICO_STORE_SLUG=" . (string) ( $after_settings['store_slug'] ?? '' ) . "\n";
