<?php
/**
 * Plugin Name: MDO Staging Native Language Switcher
 * Description: Staging-only language selector shell. It performs no content translation.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_native_switcher_is_dev() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    return 'dev.elmercadodeorigen.com' === $host || false !== strpos( (string) home_url(), 'dev.elmercadodeorigen.com' );
}
if ( ! mdo_native_switcher_is_dev() ) { return; }

function mdo_native_switcher_settings() {
    $settings = get_option( 'trp_settings', array() );
    return is_array( $settings ) ? $settings : array();
}

function mdo_native_switcher_languages() {
    $settings = mdo_native_switcher_settings();
    $langs = isset( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] )
        ? $settings['publish-languages']
        : ( isset( $settings['translation-languages'] ) && is_array( $settings['translation-languages'] ) ? $settings['translation-languages'] : array() );
    if ( empty( $langs ) ) {
        $default = isset( $settings['default-language'] ) ? (string) $settings['default-language'] : 'es_ES';
        $langs = array( $default );
    }
    return array_values( array_unique( array_filter( array_map( 'strval', $langs ) ) ) );
}

function mdo_native_switcher_slug_map() {
    $settings = mdo_native_switcher_settings();
    $langs = mdo_native_switcher_languages();
    $configured = isset( $settings['url-slugs'] ) && is_array( $settings['url-slugs'] ) ? $settings['url-slugs'] : array();
    $default = isset( $settings['default-language'] ) ? (string) $settings['default-language'] : ( isset( $langs[0] ) ? $langs[0] : 'es_ES' );
    $map = array();
    foreach ( $langs as $lang ) {
        if ( isset( $configured[ $lang ] ) && is_string( $configured[ $lang ] ) ) {
            $slug = trim( $configured[ $lang ], '/' );
        } elseif ( $lang === $default && 'yes' !== ( $settings['add-subdirectory-to-default-language'] ?? 'no' ) ) {
            $slug = '';
        } else {
            $slug = strtolower( substr( $lang, 0, 2 ) );
        }
        $map[ $lang ] = $slug;
    }
    return $map;
}

function mdo_native_switcher_current_language() {
    $settings = mdo_native_switcher_settings();
    $langs = mdo_native_switcher_languages();
    $default = isset( $settings['default-language'] ) ? (string) $settings['default-language'] : ( isset( $langs[0] ) ? $langs[0] : 'es_ES' );
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '/';
    $first = trim( explode( '/', trim( $path, '/' ) )[0] ?? '', '/' );
    foreach ( mdo_native_switcher_slug_map() as $lang => $slug ) {
        if ( '' !== $slug && $first === $slug ) { return $lang; }
    }
    return $default;
}

function mdo_native_switcher_url( $target_language ) {
    $settings = mdo_native_switcher_settings();
    $map = mdo_native_switcher_slug_map();
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $parts = wp_parse_url( $uri );
    $path = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
    $query = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';

    foreach ( $map as $slug ) {
        if ( '' === $slug ) { continue; }
        $path = preg_replace( '#^/' . preg_quote( $slug, '#' ) . '(?=/|$)#', '', $path, 1 );
    }
    if ( '' === $path ) { $path = '/'; }
    if ( '/' !== substr( $path, 0, 1 ) ) { $path = '/' . $path; }

    $target_slug = isset( $map[ $target_language ] ) ? $map[ $target_language ] : strtolower( substr( (string) $target_language, 0, 2 ) );
    if ( '' !== $target_slug ) {
        $path = '/' . $target_slug . ( '/' === $path ? '/' : $path );
    }
    return home_url( $path ) . $query;
}

function mdo_native_switcher_flag( $locale ) {
    $parts = preg_split( '/[-_]/', strtoupper( (string) $locale ) );
    $region = isset( $parts[1] ) && preg_match( '/^[A-Z]{2}$/', $parts[1] ) ? $parts[1] : '';
    if ( '' === $region ) {
        $fallbacks = array( 'ES' => 'ES', 'EN' => 'GB', 'FR' => 'FR', 'DE' => 'DE', 'IT' => 'IT', 'PT' => 'PT' );
        $region = $fallbacks[ $parts[0] ?? '' ] ?? '';
    }
    if ( '' === $region ) { return '🌐'; }
    $out = '';
    foreach ( str_split( $region ) as $letter ) {
        $out .= mb_chr( 127397 + ord( $letter ), 'UTF-8' );
    }
    return $out;
}

function mdo_native_switcher_label( $locale ) {
    $labels = array(
        'es_ES' => 'Español', 'en_US' => 'English', 'en_GB' => 'English',
        'fr_FR' => 'Français', 'de_DE' => 'Deutsch', 'it_IT' => 'Italiano', 'pt_PT' => 'Português',
    );
    if ( isset( $labels[ $locale ] ) ) { return $labels[ $locale ]; }
    return str_replace( '_', '-', (string) $locale );
}

add_filter( 'body_class', function( $classes ) {
    $classes[] = 'mdo-native-multilingual';
    $current = mdo_native_switcher_current_language();
    $classes[] = 'mdo-language-' . sanitize_html_class( strtolower( substr( $current, 0, 2 ) ) );
    return $classes;
}, 50 );

add_action( 'wp_footer', function() {
    if ( is_admin() ) { return; }
    $languages = mdo_native_switcher_languages();
    $current = mdo_native_switcher_current_language();
    ?>
    <div id="mdo-language-switcher" class="mdo-language-switcher" aria-label="Website language selector">
        <?php foreach ( $languages as $language ) :
            $active = $language === $current;
            $label = mdo_native_switcher_label( $language );
            $code = strtolower( substr( $language, 0, 2 ) );
            ?>
            <a class="mdo-language-switcher__item<?php echo $active ? ' is-active' : ''; ?>"
               href="<?php echo esc_url( mdo_native_switcher_url( $language ) ); ?>"
               hreflang="<?php echo esc_attr( $code ); ?>"
               lang="<?php echo esc_attr( $code ); ?>"
               <?php echo $active ? 'aria-current="page"' : ''; ?>
               aria-label="<?php echo esc_attr( $label ); ?>" title="<?php echo esc_attr( $label ); ?>"><span aria-hidden="true"><?php echo esc_html( mdo_native_switcher_flag( $language ) ); ?></span></a>
        <?php endforeach; ?>
    </div>
    <style id="mdo-language-switcher-css">
        .trp-language-switcher-container,.trp-floating-switcher,.trp-language-switcher{display:none!important}
        .mdo-language-switcher{display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:2px!important;border:1px solid rgba(23,63,50,.13)!important;border-radius:999px!important;background:#fff!important;box-shadow:0 2px 10px rgba(13,33,27,.04)!important;flex:0 0 auto!important;width:42px!important;min-width:42px!important;height:34px!important;margin:0 2px 0 0!important}
        .mdo-language-switcher__item{display:grid!important;place-items:center!important;width:26px!important;height:26px!important;padding:0!important;margin:0!important;border:0!important;border-radius:999px!important;background:transparent!important;text-decoration:none!important;line-height:1!important}
        .mdo-language-switcher__item span{font-size:17px!important;line-height:1!important;margin:0!important}
        @media (min-width:992px){
          html body.elmercado-child-theme .site-header .site-tools{display:flex!important;width:auto!important;min-width:0!important;gap:7px!important;grid-template-columns:none!important;grid-auto-flow:initial!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher{width:42px!important;min-width:42px!important;max-width:42px!important;height:34px!important;margin-right:1px!important}
        }
        @media (max-width:991px){
          html body.elmercado-child-theme .site-header-inner>.woostify-container{grid-template-columns:28px minmax(0,1fr) 136px!important;padding-inline:10px!important}
          html body.elmercado-child-theme .site-header .site-tools{display:grid!important;grid-template-columns:42px repeat(3,30px)!important;grid-auto-flow:column!important;gap:1px!important;width:136px!important;min-width:136px!important;max-width:136px!important;justify-content:end!important;place-items:center!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher{width:42px!important;min-width:42px!important;max-width:42px!important;height:32px!important;margin:0!important;place-self:center!important}
        }
        @media (max-width:390px){
          html body.elmercado-child-theme .site-header-inner>.woostify-container{grid-template-columns:26px minmax(0,1fr) 130px!important;padding-inline:8px!important}
          html body.elmercado-child-theme .site-header .site-tools{grid-template-columns:40px repeat(3,29px)!important;width:130px!important;min-width:130px!important;max-width:130px!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher{width:40px!important;min-width:40px!important;max-width:40px!important}
        }
    </style>
    <script id="mdo-language-switcher-js">
    (function(){
      function mount(){
        var sw=document.getElementById('mdo-language-switcher');
        if(!sw || sw.dataset.mounted==='1') return;
        var tools=document.querySelector('#masthead .site-tools');
        if(!tools) return;
        tools.insertBefore(sw, tools.firstChild);
        sw.dataset.mounted='1';
      }
      if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',mount); else mount();
      window.setTimeout(mount,400);
    })();
    </script>
    <?php
}, 999 );
