<?php
/**
 * Plugin Name: MDO Native Translation Render Bridge
 * Description: Production-only compatibility bridge for third-party/theme output that TranslatePress does not render from its native database by itself. Reads only human-reviewed translations already persisted in TranslatePress; no machine translation and no external calls.
 * Version: 1.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_native_bridge_is_production() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    return in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true );
}
function mdo_native_bridge_is_english() {
    if ( ! mdo_native_bridge_is_production() || is_admin() ) { return false; }
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '/';
    return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}
function mdo_native_bridge_translations() {
    static $map = null;
    if ( null !== $map ) { return $map; }
    $map = array();
    if ( ! mdo_native_bridge_is_english() ) { return $map; }
    global $wpdb;
    $table = $wpdb->prefix . 'trp_dictionary_es_es_en_us';
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { return $map; }
    $rows = $wpdb->get_results( "SELECT original, translated FROM `{$table}` WHERE status = 2 AND translated IS NOT NULL AND translated <> ''", ARRAY_A );
    foreach ( $rows as $row ) {
        if ( isset( $row['original'], $row['translated'] ) && '' !== (string) $row['original'] && '' !== (string) $row['translated'] ) {
            $map[ (string) $row['original'] ] = (string) $row['translated'];
        }
    }
    return $map;
}
function mdo_native_bridge_normalized_translations() {
    static $normalized = null;
    if ( null !== $normalized ) { return $normalized; }
    $normalized = array();
    foreach ( mdo_native_bridge_translations() as $original => $translated ) {
        $key = preg_replace( '/\s+/u', ' ', trim( (string) $original ) );
        if ( is_string( $key ) && '' !== $key && ! isset( $normalized[ $key ] ) ) { $normalized[ $key ] = $translated; }
    }
    return $normalized;
}
function mdo_native_bridge_lookup( $text ) {
    $text = (string) $text;
    $map = mdo_native_bridge_translations();
    if ( isset( $map[ $text ] ) ) { return $map[ $text ]; }
    $lf = str_replace( "\r\n", "\n", $text );
    if ( isset( $map[ $lf ] ) ) { return $map[ $lf ]; }
    $collapsed = preg_replace( '/\s+/u', ' ', trim( $text ) );
    if ( is_string( $collapsed ) && '' !== $collapsed ) {
        $normalized = mdo_native_bridge_normalized_translations();
        if ( isset( $normalized[ $collapsed ] ) ) { return $normalized[ $collapsed ]; }
    }

    /* Compose common WooCommerce/SEO labels from a product title whose reviewed
     * translation is already persisted in the native dictionary. */
    if ( preg_match( '/^(.+?)\s+(?:cantidad|quantity)$/iu', trim( $text ), $m ) ) {
        $title = mdo_native_bridge_lookup( $m[1] );
        if ( $title !== $m[1] ) { return $title . ' quantity'; }
    }
    if ( preg_match( '/^(.+?)\s+-\s+El Mercado de Origen$/u', trim( $text ), $m ) ) {
        $title = mdo_native_bridge_lookup( $m[1] );
        if ( $title !== $m[1] ) { return $title . ' - El Mercado de Origen'; }
    }
    return $text;
}
function mdo_native_bridge_attribute_value( $value ) {
    $value = (string) $value;
    $direct = mdo_native_bridge_lookup( $value );
    if ( $direct !== $value ) { return $direct; }
    if ( preg_match( '/^(.*?[“"])(.+?)([”"])$/u', $value, $m ) ) {
        $inner = mdo_native_bridge_lookup( $m[2] );
        if ( $inner !== $m[2] ) { return $m[1] . $inner . $m[3]; }
    }
    return $value;
}
function mdo_native_bridge_translate_html_fragment( $html ) {
    if ( ! is_string( $html ) || '' === $html ) { return $html; }
    return (string) preg_replace_callback( '/>([^<>]+)</su', static function( $m ) {
        $raw = $m[1];
        if ( '' === trim( $raw ) ) { return $m[0]; }
        $leading_len = strlen( $raw ) - strlen( ltrim( $raw ) );
        $trailing_len = strlen( $raw ) - strlen( rtrim( $raw ) );
        $leading = $leading_len ? substr( $raw, 0, $leading_len ) : '';
        $trailing = $trailing_len ? substr( $raw, -$trailing_len ) : '';
        $decoded = html_entity_decode( trim( $raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $translated = mdo_native_bridge_lookup( $decoded );
        return $translated === $decoded ? $m[0] : '>' . $leading . esc_html( $translated ) . $trailing . '<';
    }, $html );
}
function mdo_native_bridge_translate_json_value( $value ) {
    if ( is_string( $value ) ) {
        if ( preg_match( '~^(?:https?:)?//|^mailto:|^tel:~i', $value ) ) { return $value; }
        $direct = mdo_native_bridge_lookup( $value );
        if ( $direct !== $value ) { return $direct; }
        if ( false !== strpos( $value, '<' ) && false !== strpos( $value, '>' ) ) {
            return mdo_native_bridge_translate_html_fragment( $value );
        }
        return $value;
    }
    if ( is_array( $value ) ) {
        foreach ( $value as $key => $child ) { $value[ $key ] = mdo_native_bridge_translate_json_value( $child ); }
    }
    return $value;
}
function mdo_native_bridge_final_html( $html ) {
    if ( ! is_string( $html ) || '' === $html || ! mdo_native_bridge_is_english() ) { return $html; }

    /* The switcher is intentionally disabled until the owner explicitly enables
     * it. Remove TranslatePress' generated floating nav from the final response,
     * so no flag assets or selector controls reach the browser. */
    $html = (string) preg_replace( '#<nav\b[^>]*class=("|\')[^"\']*\btrp-language-switcher\b[^"\']*\1[^>]*>.*?</nav>#isu', '', $html );

    $html = (string) preg_replace_callback( '#<title([^>]*)>(.*?)</title>#isu', static function( $m ) {
        $decoded = html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $translated = mdo_native_bridge_lookup( $decoded );
        return $translated === $decoded ? $m[0] : '<title' . $m[1] . '>' . esc_html( $translated ) . '</title>';
    }, $html );

    /* WooCommerce serializes variation descriptions/options into this attribute
     * and reconstructs them after page load. Translate the serialized payload
     * from the same reviewed DB map before WooCommerce JavaScript sees it. */
    $html = (string) preg_replace_callback( '/\sdata-product_variations=("|\')(.*?)\1/isu', static function( $m ) {
        $decoded_attr = html_entity_decode( $m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $payload = json_decode( $decoded_attr, true );
        if ( ! is_array( $payload ) || JSON_ERROR_NONE !== json_last_error() ) { return $m[0]; }
        $translated = mdo_native_bridge_translate_json_value( $payload );
        $encoded = wp_json_encode( $translated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        return is_string( $encoded ) ? ' data-product_variations=' . $m[1] . esc_attr( $encoded ) . $m[1] : $m[0];
    }, $html );

    /* Translate persisted values in accessibility/metadata attributes. */
    $html = (string) preg_replace_callback( '/\s(aria-label|title|alt|placeholder)=("|\')(.*?)\2/isu', static function( $m ) {
        $decoded = html_entity_decode( $m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $translated = mdo_native_bridge_attribute_value( $decoded );
        return $translated === $decoded ? $m[0] : ' ' . $m[1] . '=' . $m[2] . esc_attr( $translated ) . $m[2];
    }, $html );

    /* Translate plain HTML text nodes by O(1) dictionary lookup. This only
     * resolves strings that already exist as status=2 reviewed DB translations. */
    $html = (string) preg_replace_callback( '/>([^<>]+)</su', static function( $m ) {
        $raw = $m[1];
        if ( '' === trim( $raw ) ) { return $m[0]; }
        $leading_len = strlen( $raw ) - strlen( ltrim( $raw ) );
        $trailing_len = strlen( $raw ) - strlen( rtrim( $raw ) );
        $leading = $leading_len ? substr( $raw, 0, $leading_len ) : '';
        $trailing = $trailing_len ? substr( $raw, -$trailing_len ) : '';
        $core = trim( $raw );
        /* Whole script/style payloads are not plain text nodes for translation. */
        if ( strlen( $core ) > 1200 ) { return $m[0]; }
        $decoded = html_entity_decode( $core, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $translated = mdo_native_bridge_lookup( $decoded );
        if ( $translated === $decoded ) { return $m[0]; }
        return '>' . $leading . esc_html( $translated ) . $trailing . '<';
    }, $html );

    /* Custom theme pseudo-element copy lives inside CSS rather than a DOM text
     * node. Translate only exact persisted content:"..." values. */
    $html = (string) preg_replace_callback( '/\bcontent\s*:\s*("|\')([^"\']+)\1/iu', static function( $m ) {
        $translated = mdo_native_bridge_lookup( html_entity_decode( $m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        return $translated === $m[2] ? $m[0] : 'content:' . $m[1] . $translated . $m[1];
    }, $html );

    /* SEO/schema plugins often emit untranslated source fields inside JSON-LD.
     * Keep the structure intact and replace only exact persisted string values. */
    $html = (string) preg_replace_callback( '#<script([^>]*)>(.*?)</script>#isu', static function( $m ) {
        $trimmed = trim( $m[2] );
        if ( '' === $trimmed || ( '{' !== $trimmed[0] && '[' !== $trimmed[0] ) ) { return $m[0]; }
        $decoded = json_decode( $trimmed, true );
        if ( ! is_array( $decoded ) || JSON_ERROR_NONE !== json_last_error() ) { return $m[0]; }
        $translated = mdo_native_bridge_translate_json_value( $decoded );
        $encoded = wp_json_encode( $translated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        return is_string( $encoded ) ? '<script' . $m[1] . '>' . $encoded . '</script>' : $m[0];
    }, $html );

    return $html;
}

add_filter( 'document_title_parts', static function( $parts ) {
    if ( ! mdo_native_bridge_is_english() || ! is_array( $parts ) ) { return $parts; }
    foreach ( $parts as $key => $value ) { if ( is_string( $value ) ) { $parts[ $key ] = mdo_native_bridge_lookup( $value ); } }
    return $parts;
}, 9999 );

/* WooCommerce builds variation select labels after plugins have rendered the
 * initial page. Resolve term names through the already persisted English map. */
add_filter( 'woocommerce_variation_option_name', static function( $name ) {
    return mdo_native_bridge_is_english() && is_string( $name ) ? mdo_native_bridge_lookup( $name ) : $name;
}, PHP_INT_MAX );
add_filter( 'woocommerce_attribute_label', static function( $label ) {
    return mdo_native_bridge_is_english() && is_string( $label ) ? mdo_native_bridge_lookup( $label ) : $label;
}, PHP_INT_MAX );

/* Some theme/WooCommerce scripts repaint small labels after the server response
 * (variation selects, Swiper slides, accessibility labels). Reconcile only exact
 * short strings that already exist as status=2 human-reviewed DB translations. */
add_action( 'wp_footer', static function() {
    if ( ! mdo_native_bridge_is_english() ) { return; }
    $short = array();
    foreach ( mdo_native_bridge_translations() as $source => $translated ) {
        if ( $source === $translated || mb_strlen( $source, 'UTF-8' ) > 180 || preg_match( '~^(?:https?:)?//|^mailto:|^tel:~i', $source ) ) { continue; }
        $key = preg_replace( '/\s+/u', ' ', trim( $source ) );
        if ( is_string( $key ) && '' !== $key ) { $short[ $key ] = $translated; }
    }
    if ( ! $short ) { return; }
    $json = wp_json_encode( $short, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    if ( ! is_string( $json ) ) { return; }
    $encoded = base64_encode( $json );
    ?>
    <script id="mdo-native-db-runtime-labels">
    (()=>{
      'use strict';
      const raw=<?php echo wp_json_encode( $encoded ); ?>;
      let map={};
      try{const bytes=Uint8Array.from(atob(raw),c=>c.charCodeAt(0));map=JSON.parse(new TextDecoder().decode(bytes));}catch(_){return;}
      const norm=v=>String(v??'').replace(/\s+/g,' ').trim();
      const lookup=v=>{
        const key=norm(v); if(!key)return v;
        if(Object.prototype.hasOwnProperty.call(map,key))return map[key];
        const m=key.match(/^(.+?)\s+(?:cantidad|quantity)$/i);
        if(m&&Object.prototype.hasOwnProperty.call(map,norm(m[1])))return map[norm(m[1])]+' quantity';
        return v;
      };
      const skip=n=>!!n?.parentElement?.closest('script,style,noscript,textarea,code,pre');
      const fixText=root=>{
        const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);
        const nodes=[]; while(walker.nextNode())nodes.push(walker.currentNode);
        for(const n of nodes){if(skip(n))continue;const old=n.nodeValue||'';const trimmed=norm(old);if(!trimmed)continue;const next=lookup(trimmed);if(next!==trimmed){const lead=(old.match(/^\s*/)||[''])[0],tail=(old.match(/\s*$/)||[''])[0];n.nodeValue=lead+next+tail;}}
      };
      const fixElement=el=>{
        if(!(el instanceof Element)||el.matches('script,style,noscript,textarea,code,pre'))return;
        for(const attr of ['aria-label','title','alt','placeholder']){if(!el.hasAttribute(attr))continue;const old=el.getAttribute(attr)||'';const next=lookup(old);if(next!==old)el.setAttribute(attr,next);}
        if(el instanceof HTMLOptionElement){const old=norm(el.textContent);const next=lookup(old);if(next!==old)el.textContent=next;}
      };
      const reconcile=root=>{if(root instanceof Element)fixElement(root);fixText(root);if(root.querySelectorAll)root.querySelectorAll('option,[aria-label],[title],[alt],[placeholder]').forEach(fixElement);};
      let timer=0;
      const schedule=root=>{clearTimeout(timer);timer=setTimeout(()=>reconcile(root||document.body),30);};
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>reconcile(document.body),{once:true});else reconcile(document.body);
      const observer=new MutationObserver(records=>{let root=null;for(const rec of records){if(rec.type==='characterData')root=rec.target.parentElement||root;else if(rec.addedNodes.length)root=rec.target instanceof Element?rec.target:root;}if(root)schedule(root);});
      observer.observe(document.documentElement,{subtree:true,childList:true,characterData:true});
      document.body?.addEventListener('found_variation',()=>schedule(document.body));
      document.body?.addEventListener('emo:catalog-products-appended',()=>schedule(document.body));
    })();
    </script>
    <?php
}, PHP_INT_MAX - 10 );

/* Start before normal plugins establish their render buffers. This makes our
 * callback outermost and therefore the last compatibility pass over the final
 * TranslatePress/WooCommerce HTML. */
if ( mdo_native_bridge_is_english() ) { ob_start( 'mdo_native_bridge_final_html' ); }
