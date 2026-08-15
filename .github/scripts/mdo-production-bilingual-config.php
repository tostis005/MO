<?php
/** Production ES/EN structure. English is available by URL; no visible language selector. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = rtrim( (string) get_option( 'siteurl' ), '/' );
if ( ! preg_match( '~^https?://(www\.)?elmercadodeorigen\.com$~i', $siteurl ) ) { fwrite( STDERR, "Refusing non-production site: {$siteurl}\n" ); exit( 2 ); }
if ( ! class_exists( 'TRP_Translate_Press' ) ) { fwrite( STDERR, "TranslatePress is not loaded.\n" ); exit( 3 ); }

$settings = get_option( 'trp_settings', array() );
if ( ! is_array( $settings ) ) { $settings = array(); }
$settings['default-language'] = 'es_ES';
$settings['translation-languages'] = array( 'es_ES', 'en_US' );
$settings['publish-languages'] = array( 'es_ES', 'en_US' );
$settings['native_or_english_name'] = 'english_name';
$settings['add-subdirectory-to-default-language'] = 'no';
$settings['force-language-to-custom-links'] = 'yes';
$settings['trp-ls-floater'] = 'no';
$settings['url-slugs'] = isset( $settings['url-slugs'] ) && is_array( $settings['url-slugs'] ) ? $settings['url-slugs'] : array();
$settings['url-slugs']['en_US'] = 'en';
update_option( 'trp_settings', $settings, false );

/* Ensure machine translation is not enabled. We deliberately do not configure any provider or API key. */
$machine = get_option( 'trp_machine_translation_settings', array() );
if ( is_array( $machine ) ) {
    $machine['machine-translation'] = 'no';
    update_option( 'trp_machine_translation_settings', $machine, false );
}

flush_rewrite_rules( false );
wp_cache_flush();
echo 'Production bilingual structure configured. English URL slug: en. Visible language switcher: not installed. Machine translation: disabled.' . PHP_EOL;
