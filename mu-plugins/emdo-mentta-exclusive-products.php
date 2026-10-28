<?php
/**
 * Plugin Name: EMDO - Productos exclusivos para Mentta
 * Description: Oculta productos exclusivos Mentta del escaparate, con acceso directo a seis packs autorizados para revision de Menta.
 * Version: 1.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_mentta_exclusive_ids() {
    $ids = get_option( 'emdo_mentta_exclusive_ids', array() );
    return array_values( array_filter( array_unique( array_map( 'absint', (array) $ids ) ) ) );
}

/**
 * Six already-approved Mentta-only packs may be opened via their exact direct
 * product URL for partner diagnostics. They remain excluded from shop listings,
 * searches, product feeds, related items and checkout.
 * The four unapproved Musee packs are NOT on this allowlist.
 */
function emdo_mentta_partner_view_products() {
    return array(
        'pack-musee-mentta-pasaporte-salvaje' => 21814,
        'pack-musee-mentta-safari-africano' => 21818,
        'pack-musee-mentta-vuelta-al-mundo' => 21820,
        'pack-cafe-especialidad-esencia-costa-rica-ikore' => 21904,
        'pack-cafe-especialidad-ruta-cuatro-origenes-ikore' => 21906,
        'pack-cafe-especialidad-coleccion-completa-ikore' => 21908,
    );
}

/** Only the MAIN single-product query can pass the public shop exclusion. */
function emdo_mentta_partner_direct_product_query( $query ) {
    if ( ! ( $query instanceof WP_Query ) || ! $query->is_main_query() ) { return false; }

    // A real direct product URL can still be unresolved as a singular query
    // during pre_get_posts. Use its exact canonical route rather than relying
    // only on WordPress's early is_singular() state.
    $path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    $parts = explode( '/', $path );
    if ( count( $parts ) !== 2 || $parts[0] !== 'producto' ) { return false; }
    $approved = emdo_mentta_partner_view_products();
    $slug = $parts[1];
    if ( ! isset( $approved[ $slug ] ) ) { return false; }
    $id = (int) $approved[ $slug ];

    // Reject incompatible explicit product targets rather than allowing a
    // query to surface another protected product under a shared URL.
    $requested_id = absint( $query->get( 'p' ) );
    if ( $requested_id && $requested_id !== $id ) { return false; }
    return true;
}

function emdo_mentta_exclusive_public_request() {
    // Allow WordPress admin to preview only the seven paused Musée Gourmet packs.
    // Everyone else (including public visitors) retains the normal 404 exclusions.
    if ( function_exists( 'emdo_musee_review_preview_allowed' ) && emdo_musee_review_preview_allowed() ) { return false; }
    if ( defined( 'WP_CLI' ) && WP_CLI ) { return false; }
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return false; }
    if ( is_admin() ) {
        if ( ! wp_doing_ajax() ) { return false; }
        $referer = (string) ( $_SERVER['HTTP_REFERER'] ?? '' );
        if ( false !== strpos( strtolower( (string) wp_parse_url( $referer, PHP_URL_PATH ) ), '/wp-admin/' ) ) { return false; }
    }
    return true;
}

/**
 * Lightweight, read-only public detail page for just the six agreed partner
 * review URLs. The standard frontend product template is exceptionally heavy
 * for these new packs; this renders current WooCommerce data directly.
 * No catalog listings, checkout, Store API or Mentta endpoint behavior changes.
 */
add_action( 'template_redirect', function () {
    if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { return; }
    if ( ! in_array( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ), array( 'GET', 'HEAD' ), true ) ) { return; }

    $path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    $parts = explode( '/', $path );
    if ( count( $parts ) !== 2 || $parts[0] !== 'producto' ) { return; }
    $approved = emdo_mentta_partner_view_products();
    if ( ! isset( $approved[ $parts[1] ] ) ) { return; }
    $id = (int) $approved[ $parts[1] ];
    $product = wc_get_product( $id );
    if ( ! $product || $product->get_status() !== 'publish'
        || $product->get_slug() !== $parts[1]
        || ! in_array( $id, emdo_mentta_exclusive_ids(), true ) ) { return; }

    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
    header( 'X-Robots-Tag: noindex, nofollow', true );
    if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'HEAD' ) { exit; }

    $name = $product->get_name();
    $image = $product->get_image_id() ? wp_get_attachment_url( $product->get_image_id() ) : '';
    $gross = (float) wc_get_price_including_tax( $product );
    $gallery = array_values( array_unique( array_map( 'absint', $product->get_gallery_image_ids() ) ) );
    $variations = array();
    if ( $product->is_type( 'variable' ) ) {
        foreach ( $product->get_children() as $varid ) {
            $v = wc_get_product( $varid );
            if ( ! $v || $v->get_status() !== 'publish' ) { continue; }
            $attrs = array();
            foreach ( $v->get_attributes() as $key => $val ) {
                $attrs[] = wc_attribute_label( $key ) . ': ' . $val;
            }
            $variations[] = array(
                'name' => implode( ' · ', $attrs ),
                'gross' => (float) wc_get_price_including_tax( $v )
            );
        }
    }
    $price = static function( $num ) {
        return number_format( (float) $num, 2, ',', '.' ) . ' €';
    };
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>' . esc_html( $name ) . ' · El Mercado de Origen</title>';
    echo '<style>*{box-sizing:border-box}body{margin:0;background:#f8f5ee;color:#25382e;font:16px/1.6 system-ui,-apple-system,Segoe UI,Arial,sans-serif}';
    echo 'header{background:#1d3d30;color:#fff;padding:24px 5vw}header strong{font-size:20px;letter-spacing:.04em}header span{display:block;font-size:13px;color:#e7d7b5}';
    echo 'main{max-width:1120px;margin:36px auto;padding:0 22px}h1{font-size:clamp(27px,4vw,40px);line-height:1.2;margin:0 0 18px}h2{font-size:22px;margin-top:32px}';
    echo '.top{display:grid;grid-template-columns:minmax(280px,450px) 1fr;gap:36px;align-items:start}.hero{width:100%;height:auto;border-radius:10px;border:1px solid #e6dfcf;background:white}';
    echo '.price{font-weight:750;font-size:29px;color:#1c4932}.note{padding:13px 17px;background:#e9eee8;border-left:4px solid #54755b;border-radius:4px;margin:18px 0}';
    echo '.gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(125px,1fr));gap:12px}.gallery img{width:100%;height:auto;aspect-ratio:1;object-fit:contain;background:#fff;border:1px solid #e6dfcf;border-radius:7px}';
    echo 'table{width:100%;border-collapse:collapse;background:#fff}td,th{text-align:left;padding:10px 13px;border-bottom:1px solid #e5dfd3}';
    echo 'footer{margin-top:44px;padding:24px;text-align:center;color:#657466;background:#ece7dc}';
    echo '@media(max-width:750px){.top{grid-template-columns:1fr}main{margin-top:24px}}</style></head><body>';
    echo '<header><strong>EL MERCADO DE ORIGEN</strong><span>Ficha de producto para revisión de Menta</span></header>';
    echo '<main><div class="top"><div>';
    if ( $image ) { echo '<img class="hero" src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '">'; }
    echo '</div><div><h1>' . esc_html( $name ) . '</h1>';
    echo '<p class="price">' . esc_html( $price( $gross ) ) . ' <small style="font-size:14px;font-weight:400">IVA incluido</small></p>';
    echo '<p><strong>Referencia:</strong> ' . esc_html( $product->get_sku() ) . '</p>';
    echo '<p><strong>Disponibilidad:</strong> ' . ( $product->is_in_stock() ? 'Disponible' : 'Sin existencias' ) . '</p>';
    echo '<div class="note">Esta ficha puede abrirse mediante su enlace directo para su revisión. El producto está reservado para la integración con Menta y no se ofrece a la venta en nuestra tienda.</div>';
    echo '<div>' . wp_kses_post( $product->get_short_description() ) . '</div></div></div>';
    echo '<section><h2>Descripción del producto</h2>' . wp_kses_post( $product->get_description() ) . '</section>';
    if ( $variations ) {
        echo '<section><h2>Opciones disponibles</h2><table><thead><tr><th>Variación</th><th>Precio (IVA incluido)</th></tr></thead><tbody>';
        foreach( $variations as $v ) {
            echo '<tr><td>' . esc_html( $v['name'] ) . '</td><td>' . esc_html( $price( $v['gross'] ) ) . '</td></tr>';
        }
        echo '</tbody></table></section>';
    }
    if ( $gallery ) {
        echo '<section><h2>Imágenes de los productos incluidos</h2><div class="gallery">';
        foreach ( $gallery as $attachment ) {
            $url = wp_get_attachment_url( $attachment );
            if ( $url ) { echo '<img loading="lazy" src="' . esc_url( $url ) . '" alt="' . esc_attr( $name ) . '">'; }
        }
        echo '</div></section>';
    }
    echo '</main><footer>El Mercado de Origen · Ficha informativa para el equipo de Menta</footer></body></html>';
    exit;
}, -100 );

/** Protect main shop, producer pages, archives, widgets and custom WP_Query grids. */
add_action( 'pre_get_posts', function ( $query ) {
    if ( ! emdo_mentta_exclusive_public_request() || emdo_mentta_partner_direct_product_query( $query ) ) { return; }
    $post_type = $query->get( 'post_type' );
    if ( 'product' !== $post_type && ! ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
        && ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( 'product_cat' )
        && ! $query->is_tax( 'product_tag' ) ) { return; }
    $ids = emdo_mentta_exclusive_ids();
    if ( ! $ids ) { return; }
    $query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), $ids ) ) ) );
}, 100 );

/** Final guard for grids using hand-written product queries. */
add_filter( 'the_posts', function ( $posts, $query ) {
    if ( ! emdo_mentta_exclusive_public_request() || ! is_array( $posts ) ) { return $posts; }
    $ids = emdo_mentta_exclusive_ids();
    if ( ! $ids ) { return $posts; }
    $partner_detail = emdo_mentta_partner_direct_product_query( $query );
    $approved_ids = $partner_detail ? array_values( emdo_mentta_partner_view_products() ) : array();
    return array_values( array_filter( $posts, static function ( $post ) use ( $ids, $approved_ids ) {
        if ( ! ( $post instanceof WP_Post ) || 'product' !== $post->post_type ) { return true; }
        $id = (int) $post->ID;
        return ! in_array( $id, $ids, true ) || in_array( $id, $approved_ids, true );
    } ) );
}, 100, 2 );
add_filter( 'woocommerce_product_is_visible', function ( $visible, $product_id ) {
    if ( ! emdo_mentta_exclusive_public_request() || ! in_array( (int) $product_id, emdo_mentta_exclusive_ids(), true ) ) { return $visible; }
    if ( in_array( (int) $product_id, array_values( emdo_mentta_partner_view_products() ), true )
        && is_singular( 'product' ) && (int) get_queried_object_id() === (int) $product_id ) { return $visible; }
    return false;
}, 100, 2 );
add_filter( 'woocommerce_related_products', function ( $ids ) {
    return emdo_mentta_exclusive_public_request() ? array_values( array_diff( (array) $ids, emdo_mentta_exclusive_ids() ) ) : $ids;
}, 100 );

/** A direct browser URL must be 404, but authorized WooCommerce REST remains readable. */
add_action( 'template_redirect', function () {
    if ( ! emdo_mentta_exclusive_public_request() || ! is_singular( 'product' ) ) { return; }
    $id = (int) get_queried_object_id();
    if ( ! in_array( $id, emdo_mentta_exclusive_ids(), true ) ) { return; }
    if ( in_array( $id, array_values( emdo_mentta_partner_view_products() ), true ) ) { return; }
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
}, 0 );

/** Keep these commercial-only duplicates outside WordPress and Yoast sitemaps. */
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
    if ( 'product' !== $post_type ) { return $args; }
    $args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), emdo_mentta_exclusive_ids() ) ) );
    return $args;
}, 100, 2 );
add_filter( 'wpseo_sitemap_entry', function ( $url, $type, $post ) {
    if ( 'post' === $type && is_object( $post ) && in_array( (int) ( $post->ID ?? 0 ), emdo_mentta_exclusive_ids(), true ) ) { return false; }
    return $url;
}, 100, 3 );
