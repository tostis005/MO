<?php
/**
 * MDO English related-story teaser fix.
 * English-only, presentation-only. Spanish content is never modified.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'plugins_loaded', static function (): void {
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( ! preg_match( '#^/en(?:/|$)#i', $path ) ) { return; }

    ob_start( static function ( string $html ): string {
        // Related-story cards are rendered from a direct DB field and bypass normal excerpt filters.
        $html = (string) preg_replace(
            '#>\s*INTRODUCCI(?:Ó|&Oacute;|&#211;|&#xD3;)N\s*<#iu',
            '>INTRODUCTION<',
            $html
        );

        $html = (string) preg_replace(
            '#Noviembre\s+es\s+el\s+primer\s+mes\s+de\s+recolecci(?:ó|&oacute;|&#243;|&#xF3;)n\s+de\s+naranjas\s+de\s+variedades\s+tempranas\.\s*En\s+El\s+Mercado\s+de\s+Origen\s+queremos\s+llevaros\s+naranjas\s+de\s+la\s+finca\s+de(?:\s*Palma\s+del\s+R(?:í|&iacute;|&#237;|&#xED;)o[^<]{0,120})?(?:…|&hellip;|&#8230;|\.\.\.)?#iu',
            'November is the first month of the harvest for early orange varieties. At El Mercado de Origen we want the fruit to travel from the grove in Palma del Río, Córdoba, directly to your table…',
            $html
        );

        return $html;
    } );
}, -PHP_INT_MAX + 10 );
