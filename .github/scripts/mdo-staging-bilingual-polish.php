<?php
/**
 * Plugin Name: MDO Staging Bilingual Polish
 * Description: Staging-only final English output pass for legacy/hard-coded storefront sections.
 * Version: 0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_staging_polish_request_is_english() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';

    return 'dev.elmercadodeorigen.com' === $host
        && (bool) preg_match( '#^/en(?:/|\?|$)#', $uri );
}

function mdo_staging_polish_html( $html ) {
    if ( ! is_string( $html ) || '' === $html ) { return $html; }

    $replacements = array(
        // Header / storefront fragments.
        'Filter productos' => 'Filter products',
        'Filtrar productos' => 'Filter products',
        'Filters de productos' => 'Product filters',
        'Cerrar filtros' => 'Close filters',
        'Cargar más productos' => 'Load more products',
        'Vendido por' => 'Sold by',

        // Home — hero and selection principles.
        'NUESTRA SELECCIÓN' => 'OUR SELECTION',
        'Una forma distinta' => 'A different way',
        'de elegir.' => 'to choose.',
        'En El Mercado de Origen buscamos productores que han conseguido hacer de sus productos algo especial.' => 'At El Mercado de Origen, we look for producers who have managed to make their products genuinely distinctive.',
        'Por su trayectoria, su conocimiento, su vínculo con el origen o una manera de elaborar que aporta un valor diferencial.' => 'For their track record, expertise, connection to their place of origin, or a way of making things that brings something different.',
        'Seleccionamos sus productos y los reunimos en un mismo lugar para acercarlos directamente hasta tu casa.' => 'We select their products and bring them together in one place so they can reach your home directly.',
        'Descubrir la selección' => 'Discover the selection',
        'Conocer a los productores' => 'Meet the producers',
        'Nos fijamos en la procedencia, en quién está detrás y en cómo se elabora cada producto.' => 'We look at where each product comes from, who is behind it, and how it is made.',
        'Elegimos cuidadosamente los productos que incorporamos a El Mercado de Origen.' => 'We carefully choose the products we bring into El Mercado de Origen.',
        'Del productor a tu casa, sabiendo siempre quién lo hace y de dónde viene.' => 'From the producer to your home, always knowing who made it and where it comes from.',
        'La selección empieza en el productor.' => 'The selection starts with the producer.',
        'Buscamos productores que destacan dentro de lo que hacen y conocemos sus propuestas antes de incorporarlas a El Mercado de Origen.' => 'We look for producers who stand out in what they do, and we get to know their work before bringing them into El Mercado de Origen.',
        'Ese es el punto de partida de nuestra selección.' => 'That is where our selection begins.',
        'Seleccionamos lo que mejor representa cada propuesta.' => 'We select what best represents each producer.',
        'Una vez elegido el productor, revisamos su oferta y seleccionamos cuidadosamente, uno a uno, los productos que queremos incorporar a El Mercado de Origen.' => 'Once we choose a producer, we review their range and carefully select, one by one, the products we want to bring into El Mercado de Origen.',
        'Buscamos aquellos que aportan un valor propio y que encajan con el criterio de nuestra selección.' => 'We look for products with something distinctive of their own that fit the criteria behind our selection.',
        'Sabes de quién viene.' => 'You know who it comes from.',
        'Puedes conocer quién está detrás, dónde se elabora y qué caracteriza su trabajo, además de comprarlo directamente al productor.' => 'You can see who is behind it, where it is made, what defines their work, and buy directly from the producer.',

        // Home — category / popular / editorial blocks.
        'DESCUBRE POR CATEGORÍAS' => 'BROWSE BY CATEGORY',
        'Encuentra lo que buscas.' => 'Find what you’re looking for.',
        'Explora todos los productos de El Mercado de Origen y recorre fácilmente cada una de nuestras categorías.' => 'Browse all the products at El Mercado de Origen and explore each category with ease.',
        'Jamones y paletas' => 'Hams and shoulders',
        'LOS MÁS ELEGIDOS' => 'MOST POPULAR',
        'Los favoritos de nuestros clientes.' => 'Our customers’ favourites.',
        'Descubre los productos que más se eligen en El Mercado de Origen.' => 'Discover the products our customers choose most often at El Mercado de Origen.',
        'Ver todos' => 'View all',
        'No todos tienen que hacerlo de la misma manera. Esa es precisamente la riqueza de nuestra selección.' => 'Not everyone has to do things the same way. That is precisely the strength of our selection.',
        '01 — ORIGEN' => '01 — ORIGIN',
        'Hay productos profundamente ligados a su procedencia, a las materias primas de su entorno o a una tradición vinculada a un territorio.' => 'Some products are deeply connected to where they come from, the ingredients around them, or a tradition rooted in a particular place.',
        'Cuando ese origen aporta algo al producto, queremos ponerlo en valor.' => 'When that origin adds something meaningful to the product, we want to highlight it.',
        'Hay muchas formas de hacer un producto diferente.' => 'There are many ways to make a product stand out.',
        'El lugar también forma parte del producto.' => 'Place is part of the product too.',
        'Hay conocimientos que se construyen con los años.' => 'Some know-how is built over years.',
        'Productos con algo propio.' => 'Products with a character of their own.',
        'PARA PRODUCTORES' => 'FOR PRODUCERS',
        '¿Quieres formar parte de El Mercado de Origen?' => 'Would you like to be part of El Mercado de Origen?',
        'Si eres productor y crees que tus productos pueden encajar en nuestra selección, queremos conocer tu proyecto.' => 'If you are a producer and believe your products could fit our selection, we would like to hear about your project.',
        'Cuéntanos qué haces y qué productos te gustaría incorporar.' => 'Tell us what you make and which products you would like to bring to El Mercado de Origen.',

        // About page legacy paragraph.
        'El Mercado de Origen nace de la necesidad de que exista un acercamiento entre productores y consumidores finales.' => 'El Mercado de Origen was born from the need to bring producers and end consumers closer together.',
        'Ventajas de El Mercado de Origen' => 'Benefits of El Mercado de Origen',
        'Encuentra productos seleccionados con criterio, directamente desde su origen.' => 'Discover carefully selected products, directly from their place of origin.',

        // Contact/form fragments.
        '¿Qué podemos hacer por ti?' => 'How can we help?',
        'Tu nombre (obligatorio)' => 'Your name (required)',
        'Tu correo electrónico (obligatorio)' => 'Your email (required)',
        'Asunto (obligatorio)' => 'Subject (required)',
        'Tu mensaje (obligatorio)' => 'Your message (required)',
        'No completar este campo' => 'Do not fill in this field',

        // Escaped JSON/schema variants.
        '\u00bfQu\u00e9 podemos hacer por ti?' => 'How can we help?',
        'Tu correo electr\u00f3nico (obligatorio)' => 'Your email (required)',
        'Categor\u00edas' => 'Categories',
        'ENV\u00cdO GRATIS EN VARIOS PRODUCTORES' => 'FREE SHIPPING FROM SELECTED PRODUCERS',
        'ENV\u00cdOS EN 24-48H' => 'SHIPPING IN 24–48H',
        'DEVOLUCI\u00d3N F\u00c1CIL Y SENCILLA' => 'EASY RETURNS',
    );

    $html = strtr( $html, $replacements );

    // Complete partially translated Iberian product names without touching slugs/IDs.
    $html = preg_replace( '/Paleta de acorn-fed 100% Iberian/iu', '100% Iberian acorn-fed shoulder ham', $html );
    $html = preg_replace( '/Jamón de acorn-fed 100% Iberian/iu', '100% Iberian acorn-fed ham', $html );
    $html = preg_replace( '/Paleta de acorn-fed 50% Iberian/iu', '50% Iberian acorn-fed shoulder ham', $html );
    $html = preg_replace( '/Jamón de acorn-fed 50% Iberian/iu', '50% Iberian acorn-fed ham', $html );
    $html = preg_replace( '/Paleta de free-range grain-fed 100% Iberian/iu', '100% Iberian free-range grain-fed shoulder ham', $html );
    $html = preg_replace( '/Jamón de free-range grain-fed 100% Iberian/iu', '100% Iberian free-range grain-fed ham', $html );
    $html = preg_replace( '/Paleta de bellota 100% Ibérica/iu', '100% Iberian acorn-fed shoulder ham', $html );
    $html = preg_replace( '/Jamón de bellota 100% Ibérico/iu', '100% Iberian acorn-fed ham', $html );
    $html = preg_replace( '/Paleta de bellota 50% Ibérica/iu', '50% Iberian acorn-fed shoulder ham', $html );
    $html = preg_replace( '/Jamón de bellota 50% Ibérico/iu', '50% Iberian acorn-fed ham', $html );

    // The main preview helper translates the generic word "Origen" in some contexts.
    // Restore the protected brand and media filenames after all output substitutions.
    $html = str_replace( 'El Mercado de Origin', 'El Mercado de Origen', $html );
    $html = str_replace( 'El-Mercado-de-Origin', 'El-Mercado-de-Origen', $html );

    return $html;
}

// Start as the first MU-plugin output buffer. This captures legacy sections printed late by
// the child theme, which are not normal post content and therefore bypass TranslatePress.
if ( mdo_staging_polish_request_is_english() ) {
    ob_start( 'mdo_staging_polish_html' );
}
