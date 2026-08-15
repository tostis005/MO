<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$siteurl = (string) get_option( 'siteurl' );
if ( strpos( $siteurl, 'dev.elmercadodeorigen.com' ) === false ) {
    fwrite( STDERR, "Refusing to configure non-staging site: {$siteurl}\n" );
    exit( 2 );
}

$settings = get_option( 'trp_settings', array() );
if ( ! is_array( $settings ) ) { $settings = array(); }
$settings['default-language'] = 'es_ES';
$settings['translation-languages'] = array( 'es_ES', 'en_US' );
$settings['publish-languages'] = array( 'es_ES', 'en_US' );
$settings['native_or_english_name'] = 'english_name';
$settings['add-subdirectory-to-default-language'] = 'no';
$settings['force-language-to-custom-links'] = 'yes';
$settings['trp-ls-floater'] = 'no';
$settings['shortcode-options'] = 'flags-full-names';
$settings['menu-options'] = 'flags-full-names';
$settings['floater-options'] = 'flags-full-names';
$settings['url-slugs'] = isset( $settings['url-slugs'] ) && is_array( $settings['url-slugs'] ) ? $settings['url-slugs'] : array();
$settings['url-slugs']['en_US'] = 'en';
update_option( 'trp_settings', $settings, false );

// Remove only obvious obsolete WooCommerce/WordPress English duplicate drafts.
$obsolete = array(
    1238 => 'Terms and Conditions',
    1656 => 'My Account',
    684  => 'Shop',
    685  => 'Cart',
    686  => 'Checkout',
    1436 => 'My account',
    715  => 'Wishlist',
);
$trashed = array();
foreach ( $obsolete as $id => $expected_title ) {
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'page' ) { continue; }
    if ( $post->post_status === 'publish' ) { continue; }
    if ( trim( $post->post_title ) !== $expected_title ) { continue; }
    if ( wp_trash_post( $id ) ) { $trashed[] = $id . ':' . $expected_title; }
}

flush_rewrite_rules( false );

$result = array(
    'siteurl' => $siteurl,
    'trp_settings' => get_option( 'trp_settings' ),
    'trashed_obsolete_english_pages' => $trashed,
    'helper_present' => file_exists( WP_CONTENT_DIR . '/mu-plugins/mdo-staging-bilingual.php' ),
);
echo '__MDO_BILINGUAL_CONFIG__=' . base64_encode( wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . PHP_EOL;
