<?php
/**
 * Plugin Name: MDO Staging About English Hotfix
 * Description: Staging-only final output correction for one legacy About paragraph containing inline markup.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';

if ( 'dev.elmercadodeorigen.com' === $host && preg_match( '#^/en(?:/|\?|$)#', $uri ) ) {
    ob_start(
        static function ( $html ) {
            if ( ! is_string( $html ) || '' === $html ) { return $html; }
            return preg_replace(
                '~El Mercado de (?:Origen|Origin) nace de la necesidad de que exista un acercamiento entre.{0,700}?consumidores finales\.~isu',
                'El Mercado de Origen was born from the need to bring producers and end consumers closer together.',
                $html
            );
        }
    );
}
