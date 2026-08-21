<?php
/**
 * Plugin Name: MDO Pinterest Tag
 * Description: Loads Pinterest Tag after non-necessary cookie consent is granted.
 * Version: 2026.08.21.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
