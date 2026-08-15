<?php
/**
 * Plugin Name: MDO Staging Language Dropdown
 * Description: Converts the staging language switcher into a single-current-language dropdown.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_staging_language_dropdown_is_dev() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    return 'dev.elmercadodeorigen.com' === $host;
}

if ( ! mdo_staging_language_dropdown_is_dev() ) { return; }

add_action( 'wp_head', function () {
    ?>
    <style id="mdo-language-dropdown-css">
        .mdo-language-switcher{position:relative;visibility:hidden}
        .mdo-language-switcher.mdo-language-dropdown-ready{visibility:visible}
        .mdo-language-switcher .mdo-language-current{
            display:flex;align-items:center;justify-content:center;gap:5px;
            min-width:34px;height:34px;padding:0 5px;border:0;background:transparent;
            cursor:pointer;border-radius:999px;line-height:1
        }
        .mdo-language-switcher .mdo-language-current:hover,
        .mdo-language-switcher .mdo-language-current:focus-visible{background:rgba(0,0,0,.05);outline:none}
        .mdo-language-switcher .mdo-language-current .mdo-language-flag img,
        .mdo-language-switcher .mdo-language-current .mdo-language-flag svg,
        .mdo-language-switcher .mdo-language-menu img,
        .mdo-language-switcher .mdo-language-menu svg{display:block;width:22px;height:auto;max-height:16px;object-fit:cover}
        .mdo-language-switcher .mdo-language-caret{font-size:10px;line-height:1;opacity:.65;transform:translateY(-1px)}
        .mdo-language-switcher .mdo-language-menu{
            position:absolute;top:calc(100% + 8px);right:0;z-index:99999;
            min-width:54px;margin:0;padding:6px;list-style:none;background:#fff;
            border:1px solid rgba(0,0,0,.10);border-radius:10px;
            box-shadow:0 10px 28px rgba(0,0,0,.14);display:none
        }
        .mdo-language-switcher.mdo-language-open .mdo-language-menu{display:block}
        .mdo-language-switcher .mdo-language-menu li{margin:0;padding:0;list-style:none}
        .mdo-language-switcher .mdo-language-menu a{
            display:flex;align-items:center;justify-content:center;min-width:40px;height:34px;
            padding:4px 7px;border-radius:7px;text-decoration:none
        }
        .mdo-language-switcher .mdo-language-menu a:hover,
        .mdo-language-switcher .mdo-language-menu a:focus-visible{background:rgba(0,0,0,.05);outline:none}
        .mdo-language-switcher .mdo-language-original-items{display:none!important}
        @media (max-width:767px){
            .mdo-language-switcher .mdo-language-current{min-width:31px;height:31px;padding:0 3px}
            .mdo-language-switcher .mdo-language-current .mdo-language-flag img,
            .mdo-language-switcher .mdo-language-current .mdo-language-flag svg,
            .mdo-language-switcher .mdo-language-menu img,
            .mdo-language-switcher .mdo-language-menu svg{width:20px;max-height:15px}
        }
    </style>
    <?php
}, 99 );

add_action( 'wp_footer', function () {
    ?>
    <script id="mdo-language-dropdown-js">
    (function(){
        function normalizePath(path){
            path = path || '/';
            if(path.charAt(0) !== '/') path = '/' + path;
            return path.length > 1 ? path.replace(/\/+$/, '') : '/';
        }

        function initLanguageDropdown(){
            document.querySelectorAll('.mdo-language-switcher').forEach(function(box){
                if (box.classList.contains('mdo-language-dropdown-ready')) return;

                var links = Array.prototype.slice.call(box.querySelectorAll('a[href]'));
                if (!links.length) { box.style.visibility='visible'; return; }

                var currentPath = normalizePath(window.location.pathname);
                var current = links.find(function(a){
                    try {
                        return normalizePath(new URL(a.getAttribute('href') || '', window.location.origin).pathname) === currentPath;
                    } catch(e) { return false; }
                }) || links.find(function(a){
                    return a.getAttribute('aria-current') === 'page' || a.classList.contains('current') || a.classList.contains('active');
                }) || links[0];

                var originals = document.createElement('div');
                originals.className = 'mdo-language-original-items';
                links.forEach(function(a){ originals.appendChild(a); });
                box.appendChild(originals);

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'mdo-language-current';
                button.setAttribute('aria-haspopup','true');
                button.setAttribute('aria-expanded','false');
                button.setAttribute('aria-label','Change language');

                var flag = document.createElement('span');
                flag.className = 'mdo-language-flag';
                flag.innerHTML = current.innerHTML;
                button.appendChild(flag);

                var caret = document.createElement('span');
                caret.className = 'mdo-language-caret';
                caret.setAttribute('aria-hidden','true');
                caret.textContent = '▾';
                button.appendChild(caret);

                var menu = document.createElement('ul');
                menu.className = 'mdo-language-menu';
                menu.setAttribute('role','menu');

                links.forEach(function(a){
                    var li = document.createElement('li');
                    li.setAttribute('role','none');
                    var clone = a.cloneNode(true);
                    clone.setAttribute('role','menuitem');
                    if (a === current) clone.setAttribute('aria-current','page');
                    li.appendChild(clone);
                    menu.appendChild(li);
                });

                button.addEventListener('click', function(ev){
                    ev.stopPropagation();
                    var open = box.classList.toggle('mdo-language-open');
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                });

                document.addEventListener('click', function(){
                    box.classList.remove('mdo-language-open');
                    button.setAttribute('aria-expanded','false');
                });
                document.addEventListener('keydown', function(ev){
                    if(ev.key === 'Escape'){
                        box.classList.remove('mdo-language-open');
                        button.setAttribute('aria-expanded','false');
                    }
                });

                box.insertBefore(button, originals);
                box.insertBefore(menu, originals);
                box.classList.add('mdo-language-dropdown-ready');
            });
        }

        if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initLanguageDropdown);
        else initLanguageDropdown();
    })();
    </script>
    <?php
}, 99 );
