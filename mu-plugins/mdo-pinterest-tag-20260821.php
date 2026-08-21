<?php
/**
 * Plugin Name: MDO Pinterest Tag
 * Description: Loads Pinterest Tag after non-necessary cookie consent is granted.
 * Version: 2026.08.21.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EMDO has its own 10-minute Home HTML cache. If that cache predates this tag,
 * invalidate only the stale Home copy before the theme serves it, so the next
 * render is rebuilt with Pinterest included.
 */
add_action(
    'template_redirect',
    static function (): void {
        if ( ! function_exists( 'elmercado_home_cache_key' ) ) {
            return;
        }

        $is_home = function_exists( 'elmercado_is_optimized_home' )
            ? (bool) elmercado_is_optimized_home()
            : ( is_front_page() || is_home() );
        if ( ! $is_home ) {
            return;
        }

        $key = elmercado_home_cache_key();
        $cached = get_transient( $key );
        if ( ! is_string( $cached ) || '' === $cached || false !== strpos( $cached, '2612375296577' ) ) {
            return;
        }

        delete_transient( $key );

        if ( function_exists( 'elmercado_home_static_cache_file' ) ) {
            $file = elmercado_home_static_cache_file();
            if ( is_string( $file ) && is_file( $file ) ) {
                @unlink( $file );
            }
        }
    },
    -3000
);

add_action(
    'wp_head',
    static function (): void {
        ?>
<!-- Pinterest Tag - EMDO -->
<script>
(function () {
    'use strict';

    var TAG_ID = '2612375296577';
    var started = false;
    var lastConsent = null;

    function readCookie(name) {
        var prefix = name + '=';
        var parts = document.cookie ? document.cookie.split(';') : [];
        for (var i = 0; i < parts.length; i++) {
            var value = parts[i].trim();
            if (value.indexOf(prefix) === 0) {
                return decodeURIComponent(value.substring(prefix.length));
            }
        }
        return null;
    }

    function hasMarketingConsent() {
        return readCookie('cookielawinfo-checkbox-non-necessary') === 'yes';
    }

    function bootstrapPinterest() {
        if (started) {
            return;
        }
        started = true;

        !function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version='3.0';var t=document.createElement('script');t.async=!0,t.src=e;var r=document.getElementsByTagName('script')[0];r.parentNode.insertBefore(t,r)}}('https://s.pinimg.com/ct/core.js');

        pintrk('load', TAG_ID);
        pintrk('setconsent', true);
        pintrk('page');
    }

    function syncConsent() {
        var granted = hasMarketingConsent();

        if (granted) {
            if (!started) {
                bootstrapPinterest();
            } else if (lastConsent !== true && window.pintrk) {
                pintrk('setconsent', true);
            }
        } else if (started && lastConsent !== false && window.pintrk) {
            pintrk('setconsent', false);
        }

        lastConsent = granted;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncConsent, {once: true});
    } else {
        syncConsent();
    }

    document.addEventListener('click', function () {
        window.setTimeout(syncConsent, 300);
    }, true);

    window.addEventListener('focus', syncConsent);
    window.setInterval(syncConsent, 2000);
})();
</script>
<!-- End Pinterest Tag - EMDO -->
        <?php
    },
    5
);
