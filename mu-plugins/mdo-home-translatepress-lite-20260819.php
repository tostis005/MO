<?php
/**
 * Plugin Name: MDO - Home TranslatePress Lite 2026-08-19
 * Description: Sustituye en la Home el runtime geométrico del selector flotante de TranslatePress por una interacción mínima sin reflow forzado.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    return;
}

function mdo_home_trp_lite_transform_html( string $html ): string {
    if ( '' === $html || ! str_contains( $html, 'trp-language-switcher' ) ) {
        return $html;
    }

    /* Evita setAutoWidth()/getBoundingClientRect() del runtime oficial. */
    $html = str_replace( '--switcher-width:auto', '--switcher-width:118px', $html );

    /* Sólo en Home: se conserva el marcado y CSS de TranslatePress, se sustituye su JS. */
    $html = preg_replace(
        '~<script\b[^>]*\bid=["\']trp-language-switcher-js-v2-js["\'][^>]*>\s*</script\s*>~i',
        '',
        $html,
        1
    ) ?? $html;

    $lite = <<<'HTML'
<script id="mdo-trp-language-switcher-lite-js">
(() => {
    'use strict';
    const boot = () => {
        document.querySelectorAll('.trp-language-switcher').forEach((root) => {
            const trigger = root.querySelector('.trp-language-item__current[role="button"]');
            const list = root.querySelector('.trp-switcher-dropdown-list');
            if (!trigger || !list) return;

            const setOpen = (open) => {
                list.hidden = !open;
                list.toggleAttribute('inert', !open);
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                root.classList.toggle('is-open', open);
            };

            setOpen(false);
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                setOpen(trigger.getAttribute('aria-expanded') !== 'true');
            });
            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setOpen(trigger.getAttribute('aria-expanded') !== 'true');
                } else if (event.key === 'Escape') {
                    setOpen(false);
                    trigger.focus();
                }
            });
            root.addEventListener('mouseenter', () => setOpen(true));
            root.addEventListener('mouseleave', () => setOpen(false));
            document.addEventListener('click', (event) => {
                if (!root.contains(event.target)) setOpen(false);
            }, true);
        });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
</script>
HTML;

    if ( str_contains( $html, '</body>' ) ) {
        return str_replace( '</body>', $lite . "\n</body>", $html );
    }

    return $html . $lite;
}

add_action(
    'template_redirect',
    static function (): void {
        if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
            return;
        }
        ob_start( 'mdo_home_trp_lite_transform_html' );
    },
    -800
);
