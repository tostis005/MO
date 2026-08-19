<?php
/**
 * Home performance: load FiboSearch only when the visitor interacts with search.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the public front page.
 */
function mdo_is_public_home_010261(): bool {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'elmercado_is_optimized_home' ) ) {
		return elmercado_is_optimized_home();
	}

	return is_front_page();
}

/**
 * FiboSearch performs layout reads during its initialisation. It is not needed
 * until the visitor opens/focuses the search UI, so on Home its external bundle
 * is replaced by a tiny interaction loader. The original plugin remains intact.
 *
 * @param string $tag    Original script tag.
 * @param string $handle WordPress script handle.
 * @param string $src    Script URL.
 */
function mdo_home_lazy_fibosearch_tag_010261( string $tag, string $handle, string $src ): string {
	if ( ! mdo_is_public_home_010261() || 'jquery-dgwt-wcas' !== $handle || '' === $src ) {
		return $tag;
	}

	$src_json = wp_json_encode( $src, JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $src_json ) ) {
		return $tag;
	}

	return '<script id="mdo-home-fibosearch-lazy-010261">' .
		'(()=>{\'use strict\';const src=' . $src_json . ';let promise=null,started=false;' .
		'const load=()=>{if(promise)return promise;started=true;promise=new Promise((resolve,reject)=>{const s=document.createElement(\'script\');s.src=src;s.defer=true;s.id=\'jquery-dgwt-wcas-js\';s.onload=resolve;s.onerror=reject;document.head.append(s)});return promise};' .
		'const selector=\'.dgwt-wcas-search-wrapp,.dgwt-wcas-search-input,.dgwt-wcas-search-form,.site-header .search-icon,.site-header .header-search-icon,.site-header .site-search-toggle,.sidebar-menu .search-form\';' .
		'const zone=t=>t instanceof Element?t.closest(selector):null;' .
		'[\'pointerover\',\'focusin\',\'touchstart\'].forEach(name=>document.addEventListener(name,e=>{if(zone(e.target))load()},{capture:true,passive:true}));' .
		'document.addEventListener(\'click\',e=>{const z=zone(e.target);if(!z||started)return;const target=e.target.closest(\'a,button,input,label\')||z;e.preventDefault();e.stopImmediatePropagation();load().then(()=>requestAnimationFrame(()=>{if(target instanceof HTMLInputElement)target.focus();else target.click()}))},true);' .
		'setTimeout(load,20000)})();</script>';
}
add_filter( 'script_loader_tag', 'mdo_home_lazy_fibosearch_tag_010261', PHP_INT_MAX, 3 );
