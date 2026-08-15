<?php
/**
 * Plugin Name: MDO Staging Bilingual Preview
 * Description: Staging-only EN preview helpers, language switcher and core translations.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_staging_is_english() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    return (bool) preg_match( '#^/en(?:/|\?|$)#', $uri );
}

function mdo_staging_language_url( $language ) {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $parts = wp_parse_url( $uri );
    $path = isset( $parts['path'] ) ? $parts['path'] : '/';
    $query = isset( $parts['query'] ) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

    $path = preg_replace( '#^/en(?=/|$)#', '', $path );
    if ( $path === '' ) { $path = '/'; }
    if ( $path[0] !== '/' ) { $path = '/' . $path; }

    if ( $language === 'en' ) {
        $path = '/en' . ( $path === '/' ? '/' : $path );
    }

    return home_url( $path ) . $query;
}

function mdo_staging_exact_translations() {
    return array(
        'Inicio' => 'Home',
        'Tienda' => 'Shop',
        'Productores' => 'Producers',
        'Quiénes somos' => 'About us',
        'Quienes somos' => 'About us',
        'Contacto' => 'Contact',
        'Carrito' => 'Cart',
        'Finalizar compra' => 'Checkout',
        'Mi cuenta' => 'My account',
        'Lista de deseos' => 'Wishlist',
        'Buscar' => 'Search',
        'Buscar productos…' => 'Search products…',
        'Buscar productos...' => 'Search products...',
        'Ver carrito' => 'View cart',
        'Añadir al carrito' => 'Add to cart',
        'Añadir a la cesta' => 'Add to cart',
        'Comprar' => 'Buy',
        'Comprar ahora' => 'Buy now',
        'Seguir comprando' => 'Continue shopping',
        'Agotado' => 'Out of stock',
        'En stock' => 'In stock',
        'Oferta' => 'Sale',
        'Descripción' => 'Description',
        'Información adicional' => 'Additional information',
        'Valoraciones' => 'Reviews',
        'Productos relacionados' => 'Related products',
        'También te recomendamos…' => 'You may also like…',
        'Filtros' => 'Filters',
        'Filtro' => 'Filter',
        'Ordenar por' => 'Sort by',
        'Orden predeterminado' => 'Default sorting',
        'Ordenar por popularidad' => 'Sort by popularity',
        'Ordenar por puntuación media' => 'Sort by average rating',
        'Ordenar por los últimos' => 'Sort by latest',
        'Ordenar por precio: bajo a alto' => 'Sort by price: low to high',
        'Ordenar por precio: alto a bajo' => 'Sort by price: high to low',
        'Seleccionar categoría' => 'Select category',
        'Categorías' => 'Categories',
        'Categoría' => 'Category',
        'Mostrando todos los resultados' => 'Showing all results',
        'No se encontraron productos' => 'No products were found',
        'Leer más' => 'Read more',
        'Saber más' => 'Learn more',
        'Ver productos' => 'View products',
        'Ver todos' => 'View all',
        'Descubrir' => 'Discover',
        'ENVÍO GRATIS EN VARIOS PRODUCTORES' => 'FREE SHIPPING FROM SELECTED PRODUCERS',
        'ENVÍOS EN 24-48H' => 'SHIPPING IN 24–48H',
        'DEVOLUCIÓN FÁCIL Y SENCILLA' => 'EASY RETURNS',
        'RESOLVEMOS TUS DUDAS' => 'WE ARE HERE TO HELP',
        'Nuevos productos' => 'New products',
        'Productos más vendidos' => 'Best sellers',
        'Conoce nuestra historia' => 'Discover our story',
        'Opiniones de nuestros clientes en Google' => 'What our customers say on Google',
        '¿Qué podemos hacer por ti?' => 'How can we help?',
        'Tu nombre (obligatorio)' => 'Your name (required)',
        'Tu correo electrónico (obligatorio)' => 'Your email (required)',
        'Asunto (obligatorio)' => 'Subject (required)',
        'Tu mensaje (obligatorio)' => 'Your message (required)',
        'No completar este campo' => 'Do not fill in this field',
        'Enviar' => 'Send',
        'Aceites' => 'Oils',
        'Naranjas' => 'Oranges',
        'Embutidos y curados' => 'Cured meats',
        'Jamones y paletas' => 'Hams and shoulders',
        'Quesos' => 'Cheeses',
        'Carne' => 'Meat',
        'Pack gourmet' => 'Gourmet packs',
        'Alimentación' => 'Food',
        'Calidad' => 'Quality',
        'Con DOP' => 'With PDO',
        'Curación' => 'Curing time',
        'Denominación de origen' => 'Designation of origin',
        'Origen' => 'Origin',
        'Peso' => 'Weight',
        'Preparación' => 'Preparation',
        'Productor' => 'Producer',
        'Raza ibérica' => 'Iberian breed',
        'Tamaño' => 'Size',
        'Tipo de pieza' => 'Cut type',
        'Tipo de producto' => 'Product type',
        'Variedad' => 'Variety',
        'Política de privacidad' => 'Privacy policy',
        'Política de cookies' => 'Cookie policy',
        'Términos y condiciones' => 'Terms and conditions',
        'Condiciones especiales' => 'Special conditions',
    );
}

function mdo_staging_translate_productish( $text ) {
    if ( ! is_string( $text ) || $text === '' ) { return $text; }
    $exact = mdo_staging_exact_translations();
    if ( isset( $exact[ $text ] ) ) { return $exact[ $text ]; }

    $replacements = array(
        'Jamón ibérico' => 'Iberian ham',
        'Jamón Ibérico' => 'Iberian ham',
        'Jamón' => 'Ham',
        'jamón' => 'ham',
        'Paleta ibérica' => 'Iberian shoulder ham',
        'Paleta Ibérica' => 'Iberian shoulder ham',
        'Paleta' => 'Shoulder ham',
        'paleta' => 'shoulder ham',
        'Lomo ibérico' => 'Iberian cured loin',
        'Lomo Ibérico' => 'Iberian cured loin',
        'Lomito' => 'Cured tenderloin',
        'Lomo' => 'Cured loin',
        'Chorizo' => 'Chorizo',
        'Salchichón' => 'Salchichón',
        'Cecina' => 'Cured beef',
        'Queso' => 'Cheese',
        'queso' => 'cheese',
        'Naranja' => 'Orange',
        'naranja' => 'orange',
        'Aceite de oliva virgen extra' => 'Extra virgin olive oil',
        'Aceite de Oliva Virgen Extra' => 'Extra virgin olive oil',
        'Ternera' => 'Beef',
        'ternera' => 'beef',
        'Bellota' => 'Acorn-fed',
        'bellota' => 'acorn-fed',
        'Cebo de campo' => 'Free-range grain-fed',
        'cebo de campo' => 'free-range grain-fed',
        'Cebo' => 'Grain-fed',
        'cebo' => 'grain-fed',
        'Ibérico' => 'Iberian',
        'ibérico' => 'Iberian',
        'Ibérica' => 'Iberian',
        'ibérica' => 'Iberian',
        'loncheado' => 'sliced',
        'Loncheado' => 'Sliced',
        'pieza' => 'piece',
        'Pieza' => 'Piece',
        'kg' => 'kg',
    );
    return strtr( $text, $replacements );
}

add_filter( 'body_class', function( $classes ) {
    if ( mdo_staging_is_english() ) { $classes[] = 'mdo-language-en'; }
    $classes[] = 'mdo-bilingual-preview';
    return $classes;
}, 50 );

add_filter( 'the_title', function( $title, $id = 0 ) {
    if ( ! mdo_staging_is_english() || is_admin() ) { return $title; }
    return mdo_staging_translate_productish( $title );
}, 999, 2 );

add_filter( 'document_title_parts', function( $parts ) {
    if ( mdo_staging_is_english() && isset( $parts['title'] ) ) {
        $parts['title'] = mdo_staging_translate_productish( $parts['title'] );
    }
    return $parts;
}, 999 );

add_filter( 'wp_nav_menu_objects', function( $items ) {
    if ( ! mdo_staging_is_english() ) { return $items; }
    foreach ( $items as $item ) {
        $item->title = mdo_staging_translate_productish( $item->title );
    }
    return $items;
}, 999 );

add_filter( 'get_term', function( $term, $taxonomy ) {
    if ( ! mdo_staging_is_english() || is_admin() || ! is_object( $term ) || ! isset( $term->name ) ) { return $term; }
    $term = clone $term;
    $term->name = mdo_staging_translate_productish( $term->name );
    if ( isset( $term->description ) && is_string( $term->description ) ) {
        $term->description = mdo_staging_translate_productish( $term->description );
    }
    return $term;
}, 999, 2 );

add_filter( 'gettext', function( $translated, $text, $domain ) {
    if ( ! mdo_staging_is_english() || is_admin() ) { return $translated; }
    $exact = mdo_staging_exact_translations();
    if ( isset( $exact[ $translated ] ) ) { return $exact[ $translated ]; }
    if ( isset( $exact[ $text ] ) ) { return $exact[ $text ]; }
    return $translated;
}, 999, 3 );

add_filter( 'ngettext', function( $translation, $single, $plural, $number, $domain ) {
    if ( ! mdo_staging_is_english() || is_admin() ) { return $translation; }
    return mdo_staging_translate_productish( $translation );
}, 999, 5 );

add_filter( 'the_content', function( $content ) {
    if ( ! mdo_staging_is_english() || is_admin() ) { return $content; }
    $content = mdo_staging_translate_productish( $content );
    $phrases = array(
        'Todo comenzó en 2014' => 'It all began in 2014',
        'cuando empezamos a especializarnos' => 'when we began to specialise',
        'administración de fincas agrícolas' => 'management of agricultural estates',
        'El productor' => 'The producer',
        'productos' => 'products',
        'productores' => 'producers',
        'selección' => 'selection',
        'Selección' => 'Selection',
        'directamente' => 'directly',
        'origen' => 'origin',
        'Origen' => 'Origin',
        'envío' => 'shipping',
        'Envío' => 'Shipping',
    );
    return strtr( $content, $phrases );
}, 999 );

add_action( 'wp_footer', function() {
    if ( is_admin() ) { return; }
    $is_en = mdo_staging_is_english();
    $es = esc_url( mdo_staging_language_url( 'es' ) );
    $en = esc_url( mdo_staging_language_url( 'en' ) );
    ?>
    <div id="mdo-language-switcher" class="mdo-language-switcher" aria-label="Language selector">
        <a class="mdo-language-switcher__item<?php echo ! $is_en ? ' is-active' : ''; ?>" href="<?php echo $es; ?>" hreflang="es" lang="es" aria-label="Español" title="Español"><span aria-hidden="true">🇪🇸</span></a>
        <a class="mdo-language-switcher__item<?php echo $is_en ? ' is-active' : ''; ?>" href="<?php echo $en; ?>" hreflang="en" lang="en" aria-label="English" title="English"><span aria-hidden="true">🇺🇸</span></a>
    </div>
    <style id="mdo-language-switcher-css">
        .mdo-language-switcher{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:2px!important;padding:3px!important;border:1px solid rgba(23,63,50,.13)!important;border-radius:999px!important;background:#fff!important;box-shadow:0 2px 10px rgba(13,33,27,.04)!important;flex:0 0 auto!important;width:58px!important;height:34px!important;margin:0 2px 0 0!important}
        .mdo-language-switcher__item{display:grid!important;place-items:center!important;width:24px!important;height:26px!important;min-width:24px!important;min-height:26px!important;padding:0!important;margin:0!important;border:0!important;border-radius:999px!important;background:transparent!important;text-decoration:none!important;line-height:1!important;opacity:.62!important;filter:saturate(.86)!important;transition:background-color .16s,opacity .16s,transform .16s!important}
        .mdo-language-switcher__item span{font-size:16px!important;line-height:1!important;transform:none!important;margin:0!important}
        .mdo-language-switcher__item:hover,.mdo-language-switcher__item:focus-visible{background:#eef6f1!important;opacity:1!important;transform:translateY(-1px)!important}
        .mdo-language-switcher__item.is-active{background:#e4eee8!important;opacity:1!important;filter:none!important;box-shadow:inset 0 0 0 1px rgba(23,63,50,.07)!important}
        @media (min-width:992px){
          html body.elmercado-child-theme .site-header .site-tools{display:flex!important;width:auto!important;min-width:0!important;gap:7px!important;grid-template-columns:none!important;grid-auto-flow:initial!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher{display:inline-flex!important;width:58px!important;min-width:58px!important;max-width:58px!important;height:34px!important;min-height:34px!important;margin-right:1px!important}
          html body.elmercado-child-theme .site-header-inner>.woostify-container{grid-template-columns:minmax(190px,auto) minmax(0,1fr) auto!important}
        }
        @media (max-width:991px){
          html body.elmercado-child-theme .site-header-inner>.woostify-container{grid-template-columns:28px minmax(0,1fr) 150px!important;padding-inline:10px!important}
          html body.elmercado-child-theme .site-header .site-tools{display:grid!important;grid-template-columns:56px repeat(3,30px)!important;grid-auto-flow:column!important;grid-auto-columns:auto!important;gap:1px!important;width:150px!important;min-width:150px!important;max-width:150px!important;justify-content:end!important;place-items:center!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher{display:inline-flex!important;width:56px!important;min-width:56px!important;max-width:56px!important;height:32px!important;min-height:32px!important;margin:0!important;place-self:center!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher>a{display:grid!important;width:23px!important;min-width:23px!important;max-width:23px!important;height:24px!important;min-height:24px!important;padding:0!important;margin:0!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher>a span{font-size:15px!important}
          html body.elmercado-child-theme .site-header .site-branding :is(.site-title,a){font-size:clamp(11px,2.8vw,13px)!important}
        }
        @media (max-width:390px){
          html body.elmercado-child-theme .site-header-inner>.woostify-container{grid-template-columns:26px minmax(0,1fr) 142px!important;padding-inline:8px!important}
          html body.elmercado-child-theme .site-header .site-tools{grid-template-columns:52px repeat(3,29px)!important;width:142px!important;min-width:142px!important;max-width:142px!important}
          html body.elmercado-child-theme .site-header .site-tools>.mdo-language-switcher{width:52px!important;min-width:52px!important;max-width:52px!important}
          html body.elmercado-child-theme .site-header .site-branding :is(.site-title,a){font-size:11px!important}
        }
    </style>
    <script id="mdo-language-switcher-js">
    (function(){
      function mount(){
        var sw=document.getElementById('mdo-language-switcher');
        if(!sw || sw.dataset.mounted==='1') return;
        var tools=document.querySelector('#masthead .site-tools');
        if(!tools) return;
        tools.insertBefore(sw, tools.firstChild);
        sw.dataset.mounted='1';
      }
      if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',mount); else mount();
      window.setTimeout(mount,400);
    })();
    </script>
    <?php
}, 999 );
