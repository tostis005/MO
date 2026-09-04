<?php
/**
 * One-time guarded production configuration for CookieYes / Cookie Law Info.
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'CookieLawInfo-0.9', array() );
$third    = get_option( 'cookielawinfo_thirdparty_settings', array() );
$needed   = get_option( 'cookielawinfo_necessary_settings', array() );

if ( ! is_array( $settings ) || ! is_array( $third ) ) {
	WP_CLI::error( 'CookieYes legacy options are not available in the expected format.' );
}

if ( false === get_option( 'emdo_cookieyes_backup_20260904_main', false ) ) {
	add_option( 'emdo_cookieyes_backup_20260904_main', $settings, '', false );
}
if ( false === get_option( 'emdo_cookieyes_backup_20260904_thirdparty', false ) ) {
	add_option( 'emdo_cookieyes_backup_20260904_thirdparty', $third, '', false );
}
if ( false === get_option( 'emdo_cookieyes_backup_20260904_necessary', false ) ) {
	add_option( 'emdo_cookieyes_backup_20260904_necessary', $needed, '', false );
}

$settings['button_1_text']          = 'Aceptar';
$settings['button_1_button_colour'] = '#11250a';
$settings['button_1_link_colour']   = '#ffffff';
$settings['button_1_as_button']     = true;

$settings['button_3_text']          = 'Rechazar';
$settings['button_3_button_colour'] = '#ffffff';
$settings['button_3_link_colour']   = '#11250a';
$settings['button_3_as_button']     = true;
$settings['button_3_style']         = 'border:1px solid #11250a;';

$settings['button_4_text']        = 'Configurar';
$settings['button_4_link_colour'] = '#11250a';
$settings['button_4_as_button']   = false;

$settings['notify_message'] = 'Usamos cookies necesarias para que la web funcione y, si las aceptas, cookies analíticas y de publicidad para medir el uso y mejorar tu experiencia. Puedes aceptar o rechazar las cookies no necesarias. [cookie_link] [cookie_settings] [cookie_reject margin="5px"] [cookie_button margin="5px"]';
$settings['scroll_close']         = false;
$settings['scroll_close_reload']  = false;
$settings['accept_close_reload']  = true;
$settings['reject_close_reload']  = true;

$third['thirdparty_on_field']       = true;
$third['third_party_default_state'] = false;
$third['thirdparty_description']    = 'Cookies analíticas y de publicidad que solo se activan si das tu consentimiento. Nos ayudan a medir el uso de la web y, cuando corresponda, a medir campañas publicitarias.';

if ( is_array( $needed ) ) {
	$needed['necessary_description'] = 'Cookies necesarias para el funcionamiento básico de la web, la seguridad y para recordar tus preferencias de cookies.';
}

update_option( 'CookieLawInfo-0.9', $settings, false );
update_option( 'cookielawinfo_thirdparty_settings', $third, false );
update_option( 'cookielawinfo_necessary_settings', $needed, false );

WP_CLI::success( 'CookieYes configured with non-necessary cookies off by default and explicit accept/reject controls.' );
