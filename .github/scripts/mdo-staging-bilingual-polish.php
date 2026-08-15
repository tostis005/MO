<?php
/**
 * Plugin Name: MDO Staging Bilingual Polish
 * Description: Final staging-only polish for rendered English output.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_staging_polish_is_english() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    return (bool) preg_match( '#^/en(?:/|\?|$)#', $uri );
}

function mdo_staging_polish_html( $html ) {
    if ( ! is_string( $html ) || $html === '' ) { return $html; }

    $replacements = array(
        // Home headings and editorial copy still rendered from legacy blocks.
        'Encuentra lo que buscas.' => 'Find what you’re looking for.',
        'Los favoritos de nuestros clientes.' => 'Our customers’ favourites.',
        'Hay muchas formas de hacer un producto diferente.' => 'There are many ways to make a product stand out.',
        'El lugar también forma parte del producto.' => 'Place is part of the product too.',
        'Hay conocimientos que se construyen con los años.' => 'Some know-how is built over years.',
        'Productos con algo propio.' => 'Products with a character of their own.',
        '¿Quieres formar parte de El Mercado de Origen?' => 'Would you like to be part of El Mercado de Origen?',
        'NUESTRA SELECCIÓN' => 'OUR SELECTION',
        'NUESTRA' => 'OUR',
        'SELECCIÓN' => 'SELECTION',
        'Selección' => 'Selection',
        'Categorías' => 'Categories',
        'ENVÍO GRATIS EN VARIOS PRODUCTORES' => 'FREE SHIPPING FROM SELECTED PRODUCERS',
        'ENVÍOS EN 24-48H' => 'SHIPPING IN 24–48H',
        'DEVOLUCIÓN FÁCIL Y SENCILLA' => 'EASY RETURNS',
        'RESOLVEMOS TUS DUDAS' => 'WE ARE HERE TO HELP',
        'Saber más' => 'Learn more',
        'TOP valoraciones en Trustpilot' => 'Top ratings on Trustpilot',
        'TOP valoraciones en Google' => 'Top ratings on Google',

        // Commerce/filter fragments coming from mixed plugin strings.
        'Filter productos' => 'Filter products',
        'Filtrar productos' => 'Filter products',
        'Mostrar productos' => 'Show products',
        'Vendido por' => 'Sold by',

        // Contact form/SEO fragments.
        '¿Qué podemos hacer por ti?' => 'How can we help?',
        'Tu nombre (obligatorio)' => 'Your name (required)',
        'Tu correo electrónico (obligatorio)' => 'Your email (required)',
        'Asunto (obligatorio)' => 'Subject (required)',
        'Tu mensaje (obligatorio)' => 'Your message (required)',
        'No completar este campo' => 'Do not fill in this field',

        // A few escaped JSON forms seen in SEO/schema output.
        '\u00bfQu\u00e9 podemos hacer por ti?' => 'How can we help?',
        'Tu correo electr\u00f3nico (obligatorio)' => 'Your email (required)',
        'Categor\u00edas' => 'Categories',
        'ENV\u00cdO GRATIS EN VARIOS PRODUCTORES' => 'FREE SHIPPING FROM SELECTED PRODUCERS',
        'ENV\u00cdOS EN 24-48H' => 'SHIPPING IN 24–48H',
        'DEVOLUCI\u00d3N F\u00c1CIL Y SENCILLA' => 'EASY RETURNS',
    );

    $html = strtr( $html, $replacements );

    // The main preview helper intentionally translates the generic word "Origen".
    // Restore the brand and media filenames after all English substitutions.
    $html = str_replace( 'El Mercado de Origin', 'El Mercado de Origen', $html );
    $html = str_replace( 'El-Mercado-de-Origin', 'El-Mercado-de-Origen', $html );

    return $html;
}

add_action( 'template_redirect', function() {
    if ( is_admin() || ! mdo_staging_polish_is_english() ) { return; }
    $home = home_url( '/' );
    if ( strpos( $home, 'dev.elmercadodeorigen.com' ) === false ) { return; }

    // Start before the main preview buffer so this callback is the final pass.
    ob_start( 'mdo_staging_polish_html' );
}, -999 );
