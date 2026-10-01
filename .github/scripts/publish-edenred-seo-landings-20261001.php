<?php
/**
 * Publish 10 Spanish-only Edenred / Ticket Restaurant SEO landing pages.
 * Idempotent by _emdo_edenred_landing_key.
 */
if ( ! defined( 'ABSPATH' ) ) { fwrite( STDERR, "WordPress is not loaded.\n" ); exit( 1 ); }

/*
 * The publisher can run with regular plugins/themes skipped. Register the
 * stored WooCommerce object types for this CLI process so catalogue taxonomy
 * data remains queryable without booting checkout/payment plugins.
 */
if ( ! post_type_exists( 'product' ) ) {
    register_post_type( 'product', array( 'public' => false, 'show_ui' => false ) );
}
if ( ! taxonomy_exists( 'product_cat' ) ) {
    register_taxonomy( 'product_cat', array( 'product' ), array(
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array( 'slug' => 'categoria-producto' ),
    ) );
}
if ( ! taxonomy_exists( 'product_visibility' ) ) {
    register_taxonomy( 'product_visibility', array( 'product' ), array(
        'public' => false,
        'hierarchical' => false,
    ) );
}

function emdo_edenred_norm( $value ) {
    return trim( preg_replace( '/\s+/', ' ', remove_accents( mb_strtolower( wp_strip_all_tags( (string) $value ) ) ) ) );
}

function emdo_edenred_sellable( $id ) {
    $id = (int) $id;
    if ( $id < 1 || 'publish' !== get_post_status( $id ) ) return false;

    $stock = (string) get_post_meta( $id, '_stock_status', true );
    if ( 'outofstock' === $stock ) return false;

    if ( taxonomy_exists( 'product_visibility' ) ) {
        $visibility = wp_get_object_terms( $id, 'product_visibility', array( 'fields' => 'slugs' ) );
        if ( ! is_wp_error( $visibility ) && in_array( 'exclude-from-catalog', (array) $visibility, true ) ) return false;
    }

    if ( function_exists( 'elmercado_wcfm_product_is_from_disabled_vendor_010210' ) && elmercado_wcfm_product_is_from_disabled_vendor_010210( $id ) ) return false;
    if ( function_exists( 'wc_get_product' ) ) {
        $p = wc_get_product( $id );
        if ( ! $p || 'hidden' === (string) $p->get_catalog_visibility() || ! $p->is_in_stock() ) return false;
    }
    return true;
}

function emdo_edenred_find_term( array $needles ) {
    $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
    if ( is_wp_error( $terms ) ) return null;
    $best = null; $best_score = -1;
    foreach ( $terms as $term ) {
        if ( ! $term instanceof WP_Term ) continue;
        $name = emdo_edenred_norm( $term->name );
        $slug = emdo_edenred_norm( $term->slug );
        $score = 0;
        foreach ( $needles as $needle ) {
            $n = emdo_edenred_norm( $needle );
            if ( '' === $n ) continue;
            if ( $name === $n || $slug === $n ) $score += 1000;
            elseif ( false !== strpos( $name, $n ) || false !== strpos( $slug, $n ) ) $score += 200;
        }
        $score += min( 150, (int) $term->count );
        if ( $score > $best_score ) { $best = $term; $best_score = $score; }
    }
    return $best_score > 150 ? $best : null;
}

function emdo_edenred_products_in_term( $term_id, $limit = 8 ) {
    $q = new WP_Query( array(
        'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 100,
        'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true,
        'orderby' => 'date', 'order' => 'DESC', 'suppress_filters' => true,
        'tax_query' => array( array(
            'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( (int) $term_id ), 'include_children' => true,
        ) ),
    ) );
    $ids = array();
    foreach ( (array) $q->posts as $id ) {
        if ( emdo_edenred_sellable( $id ) ) $ids[] = (int) $id;
        if ( count( $ids ) >= $limit ) break;
    }
    return array_values( array_unique( $ids ) );
}

function emdo_edenred_products_by_title( array $needles, $limit = 8 ) {
    $q = new WP_Query( array(
        'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1,
        'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true,
        'orderby' => 'date', 'order' => 'DESC', 'suppress_filters' => true,
    ) );
    $ids = array();
    foreach ( (array) $q->posts as $id ) {
        if ( ! emdo_edenred_sellable( $id ) ) continue;
        $p = get_post( $id ); if ( ! $p instanceof WP_Post ) continue;
        $hay = emdo_edenred_norm( $p->post_title . ' ' . $p->post_name );
        foreach ( $needles as $needle ) {
            if ( false !== strpos( $hay, emdo_edenred_norm( $needle ) ) ) { $ids[] = (int) $id; break; }
        }
        if ( count( $ids ) >= $limit ) break;
    }
    return array_values( array_unique( $ids ) );
}

function emdo_edenred_general_products( $limit = 12 ) {
    $q = new WP_Query( array(
        'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 120,
        'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true,
        'meta_key' => 'total_sales', 'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
        'order' => 'DESC', 'suppress_filters' => true,
    ) );
    $ids = array();
    foreach ( (array) $q->posts as $id ) {
        if ( emdo_edenred_sellable( $id ) ) $ids[] = (int) $id;
        if ( count( $ids ) >= $limit ) break;
    }
    return array_values( array_unique( $ids ) );
}

function emdo_edenred_shortcode( array $ids ) {
    $ids = array_values( array_filter( array_map( 'intval', $ids ), 'emdo_edenred_sellable' ) );
    return $ids ? '[products ids="' . implode( ',', $ids ) . '" columns="4" orderby="post__in"]' : '';
}

function emdo_edenred_existing( $key, $slug ) {
    $ids = get_posts( array(
        'post_type' => 'page', 'post_status' => array( 'publish','draft','private','pending' ),
        'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'suppress_filters' => true,
        'meta_key' => '_emdo_edenred_landing_key', 'meta_value' => (string) $key,
    ) );
    if ( $ids ) return (int) $ids[0];
    $page = get_page_by_path( sanitize_title( $slug ), OBJECT, 'page' );
    return $page instanceof WP_Post ? (int) $page->ID : 0;
}

function emdo_edenred_words( $html ) {
    $plain = html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    preg_match_all( '/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u', $plain, $m );
    return count( $m[0] );
}

function emdo_edenred_aioseo( $post_id, $title, $description ) {
    global $wpdb;
    $table = $wpdb->prefix . 'aioseo_posts';
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) return;
    $row_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $table . " WHERE post_id=%d LIMIT 1", (int) $post_id ) );
    if ( $row_id ) {
        $wpdb->update( $table, array( 'title' => $title, 'description' => $description ), array( 'id' => $row_id ), array( '%s','%s' ), array( '%d' ) );
    }
}

function emdo_edenred_render( array $p, array $products, $category_url ) {
    $shop = home_url( '/tienda/' );
    if ( ! $category_url ) $category_url = $shop;
    return strtr( $p['content'], array(
        '{{PRODUCT_GRID}}' => emdo_edenred_shortcode( $products ),
        '{{SHOP_URL}}' => esc_url( $shop ),
        '{{CATEGORY_URL}}' => esc_url( $category_url ),
    ) );
}

$pages = array(
array(
'key'=>'food','slug'=>'comprar-comida-online-edenred',
'title'=>'Comprar comida online con Edenred (Ticket Restaurant)',
'excerpt'=>'Compra comida online con Edenred o Ticket Restaurant en El Mercado de Origen. Descubre aceite, jamón, carne, verduras, conservas, legumbres y más.',
'seo_title'=>'Comprar comida online con Edenred | El Mercado de Origen',
'seo_description'=>'¿Tienes saldo Edenred? Compra comida online: aceite, jamón, carne, verduras, conservas, legumbres y más productos para casa.',
'focus'=>'comprar comida online con Edenred','mode'=>'general',
'content'=><<<'HTML'
<p>Si tienes una tarjeta <strong>Edenred Ticket Restaurant</strong> y quieres utilizarla para comprar comida online, en El Mercado de Origen puedes preparar una compra de alimentación con productos de distintos productores españoles. Entra en la tienda, elige lo que realmente necesitas para casa y utiliza Edenred como método de pago al finalizar el pedido.</p>
<p>No estás limitado a una sola familia de alimentos. Nuestro catálogo reúne <strong>aceite de oliva virgen extra, jamón y embutidos, carne, verduras y hortalizas, conservas, legumbres</strong> y otros productos gastronómicos. Los productos de esta página son una muestra: la tienda completa ofrece más opciones y productores.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres aprovechar tu saldo Edenred en comida?</h2><p>Empieza por productos que ya compras para casa y completa después la cesta con otras categorías.</p><p style="margin-bottom:0"><a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Una compra de alimentación para casa</h2>
<p>Cuando queda saldo disponible, puede tener sentido transformarlo en productos que vayas a consumir durante días o semanas. Un formato de AOVE para cocinar, carne para varias comidas, una pieza de jamón, verduras para la semana, legumbres o conservas de despensa son compras con un uso claro.</p>
<p>El Mercado de Origen funciona como marketplace y mantiene visible al productor o vendedor de cada referencia. Así puedes valorar procedencia, formato y características antes de añadir un producto al carrito.</p>
<h2>Qué puedes comprar con Edenred en nuestra tienda</h2>
<p>El catálogo cambia con la disponibilidad, pero las familias con mayor presencia incluyen aceite de oliva, carne, jamones y paletas, embutidos, verduras y hortalizas, conservas y legumbres. También pueden existir packs, cajas y formatos múltiples.</p>
<p>Si has llegado buscando “comprar comida con Edenred”, utiliza esta selección para empezar y continúa después por la tienda completa.</p>
{{PRODUCT_GRID}}
<p><a class="button" href="{{SHOP_URL}}">Explorar todos los productos</a></p>
<h2>Cómo pagar con Edenred o Ticket Restaurant</h2>
<p>Añade al carrito los productos que quieras, revisa cantidades, vendedores y envío y continúa al pago. Edenred / Ticket Restaurant está disponible como método de pago en El Mercado de Origen. Como en cualquier operación con tarjeta, la autorización final depende de que la tarjeta esté operativa y disponga de saldo suficiente.</p>
<p>Si quieres utilizar aproximadamente una cantidad concreta, prepara primero el carrito y revisa el total. Puedes ajustar formatos, unidades o añadir otros alimentos hasta construir una compra que te resulte útil.</p>
<h2>Qué comprar si quieres aprovechar saldo disponible</h2>
<p>Para consumo recurrente, el aceite de oliva, las legumbres y las conservas pueden ser especialmente prácticos. Para una compra más gastronómica, revisa jamón, paleta y embutidos. Si prefieres producto fresco, la carne y las verduras permiten llevar parte del gasto directamente a las comidas de los próximos días.</p>
<p>No necesitas gastar todo en una sola categoría. Combinar productos suele generar una cesta más realista y mejor adaptada a tu consumo.</p>
<h2>Compra para varios días, no solo para una comida</h2>
<p>Una compra de alimentación puede tener más recorrido que una comida puntual. Elige formatos en función de la frecuencia con la que consumes cada producto y revisa siempre peso, conservación y condiciones de envío. En un marketplace, la logística puede variar si el carrito contiene productos de vendedores distintos.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Se puede pagar con Edenred en El Mercado de Origen?</h3><p>Sí. El Mercado de Origen acepta Edenred / Ticket Restaurant como método de pago. La operación está sujeta al estado y saldo disponible de tu tarjeta.</p>
<h3>¿Los productos de esta página son todos los disponibles?</h3><p>No. Son una selección para empezar. Desde la tienda puedes ver el catálogo completo.</p>
<h3>¿Puedo preparar un carrito ajustado al saldo que me queda?</h3><p>Sí. Revisa el total antes de pagar y ajusta cantidades o productos según el importe que quieras utilizar.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Empieza por lo que necesitas en casa</h2><p>Aceite, carne, jamón, verduras, conservas, legumbres y más productos de productores españoles.</p><p style="margin-bottom:0"><a class="button" href="{{SHOP_URL}}">Comprar en El Mercado de Origen</a></p></div>
HTML
),
array(
'key'=>'oil','slug'=>'comprar-aceite-oliva-edenred',
'title'=>'Comprar aceite de oliva virgen extra con Edenred',
'excerpt'=>'Compra aceite de oliva virgen extra online y paga con Edenred Ticket Restaurant. Encuentra AOVE de productor en distintos formatos para casa.',
'seo_title'=>'Comprar aceite de oliva con Edenred | AOVE online',
'seo_description'=>'Compra AOVE online y paga con Edenred. Descubre aceite de oliva virgen extra de productor en formatos para consumo diario.',
'focus'=>'comprar aceite de oliva con Edenred','term_needles'=>array('aceites','aceite de oliva','aove'),
'content'=><<<'HTML'
<p>Si utilizas aceite de oliva a diario, dedicar parte de tu saldo de <strong>Edenred Ticket Restaurant</strong> a comprar AOVE online puede ser una forma práctica de convertirlo en un básico para casa. En El Mercado de Origen encontrarás aceite de oliva virgen extra de productor en diferentes formatos, incluidos formatos pensados para un consumo frecuente.</p>
<p>Esta landing está orientada a quienes buscan <strong>comprar aceite de oliva con Edenred</strong>, pero no necesitas quedarte únicamente en el AOVE: desde la tienda puedes añadir carne, jamón, verduras, conservas, legumbres y otros alimentos al carrito.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">AOVE para el día a día pagado con Edenred</h2><p>Compara formatos y elige la cantidad que mejor encaje con el consumo de tu casa.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver aceites disponibles</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Por qué el AOVE encaja bien si quieres aprovechar saldo Edenred</h2>
<p>El aceite de oliva virgen extra se utiliza para cocinar, aliñar, desayunos, guisos y muchas otras recetas. Eso hace que pueda tener sentido elegir un formato que vayas a consumir de forma natural durante las siguientes semanas o meses, en lugar de buscar una compra puntual que no necesitabas.</p>
<p>Si tu consumo es alto, los formatos de mayor capacidad pueden resultar cómodos. Si prefieres abrir menos cantidad cada vez, los packs de botellas o formatos más pequeños facilitan el manejo. La ficha de cada producto indica la presentación concreta.</p>
<h2>Aceite de oliva de productor</h2>
<p>En El Mercado de Origen el AOVE mantiene visible su procedencia y el productor que lo comercializa. Además del método de pago, puedes comparar variedad, capacidad, origen y características declaradas por el elaborador.</p>
<p>Cuando estén disponibles varias variedades, utiliza su perfil como orientación. Arbequina suele resultar más suave, mientras que Picual suele tener más intensidad, aunque cada aceite depende también de campaña y elaboración.</p>
<h2>AOVE disponible para comprar online</h2>
{{PRODUCT_GRID}}
<p>La selección muestra referencias activas en este momento. Entra en la categoría para ver todas las opciones disponibles.</p>
<h2>Cómo elegir el formato</h2>
<p>Piensa primero en cuánto aceite consumes. Un hogar que cocina a diario puede aprovechar un formato grande; otro que usa AOVE sobre todo en crudo puede preferir envases más manejables. Compara litros totales y formato, no solo el precio del envase.</p>
<p>Si quieres gastar una parte concreta del saldo Edenred, añade primero el aceite que te interese y completa el carrito con otros alimentos hasta alcanzar una compra útil.</p>
<h2>No tienes que gastar Edenred solo en aceite</h2>
<p>El AOVE puede ser el producto principal, pero no el único. Puedes seguir por verduras, carne, legumbres, conservas, jamón y embutidos. Así la compra se parece a una cesta real para casa y no a un gasto forzado.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo comprar aceite de oliva con Edenred?</h3><p>Sí. Puedes preparar tu pedido y utilizar Edenred / Ticket Restaurant como método de pago disponible en la tienda.</p>
<h3>¿Hay formatos grandes?</h3><p>La disponibilidad depende del productor. Consulta la selección y la categoría de aceites para ver los formatos activos.</p>
<h3>¿Puedo mezclar aceite con otros productos?</h3><p>Sí. Puedes añadir otras familias de alimentación antes de finalizar la compra.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Convierte parte de tu saldo en un básico de despensa</h2><p>Elige AOVE y completa el carrito con lo que necesites.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar AOVE</a></p></div>
HTML
),
array(
'key'=>'meat','slug'=>'comprar-carne-online-edenred',
'title'=>'Comprar carne online con Edenred (Ticket Restaurant)',
'excerpt'=>'Compra carne online y paga con Edenred Ticket Restaurant. Descubre cortes y formatos de productores presentes en El Mercado de Origen.',
'seo_title'=>'Comprar carne online con Edenred | Ticket Restaurant',
'seo_description'=>'Compra carne online y paga con Edenred. Elige productos de carnicería y completa tu cesta con alimentación de distintos productores.',
'focus'=>'comprar carne online con Edenred','term_needles'=>array('carnes','carne'),
'content'=><<<'HTML'
<p>Si estás buscando <strong>comprar carne online con Edenred</strong>, en El Mercado de Origen puedes utilizar Ticket Restaurant para preparar una compra de alimentación para casa. La carne puede formar parte de una cesta para varios días y combinarse con aceite, verduras, legumbres, conservas, jamón y otros productos.</p>
<p>El catálogo incluye referencias de productores y vendedores especializados. Como los cortes y formatos cambian con la disponibilidad, lo más útil es revisar las fichas activas y elegir según la receta y la cantidad que necesites.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Carne para tus próximas comidas</h2><p>Consulta los productos disponibles, prepara el carrito y paga con Edenred cuando termines.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver carne online</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Una forma de utilizar Edenred en la compra para casa</h2>
<p>Cuando tienes saldo pendiente, comprar alimentos que ya forman parte de tu dieta puede ser más práctico que buscar un gasto artificial. La carne permite planificar varias comidas y escoger formatos diferentes según el número de personas y el tipo de receta.</p>
<p>Antes de añadir un producto al carrito revisa peso, presentación, conservación y condiciones de envío. En producto fresco esos datos son especialmente importantes para organizar la recepción y el consumo.</p>
<h2>Qué tipo de carne comprar</h2>
<p>No hay un corte que sirva para todo. Algunas piezas encajan mejor en plancha o parrilla; otras tienen sentido para guisos, asados o cocciones lentas. También pueden existir preparados o formatos porcionados. Utiliza la información de cada ficha para decidir en función del uso.</p>
<h2>Carne disponible en la tienda</h2>
{{PRODUCT_GRID}}
<p>Estos productos son una muestra del catálogo actual. Desde la categoría puedes consultar más referencias y desde la tienda completa añadir alimentos de otros productores.</p>
<h2>Completa la cesta</h2>
<p>Si estás organizando comidas para la semana, puede tener sentido añadir AOVE, verduras y hortalizas o legumbres. Si buscas productos con mayor vida útil, revisa también conservas y otras opciones de despensa. El objetivo es utilizar Edenred en productos que realmente vayas a consumir.</p>
<h2>Cómo pagar la carne con Edenred</h2>
<p>Añade los productos al carrito y continúa con el proceso habitual. Edenred / Ticket Restaurant está disponible como método de pago. La autorización depende del estado de la tarjeta y del saldo disponible.</p>
<p>Si quieres gastar aproximadamente una cantidad concreta, revisa el total antes de pagar y ajusta unidades o añade otros productos.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo pagar una compra de carne con Edenred?</h3><p>Sí. El Mercado de Origen acepta Edenred / Ticket Restaurant como método de pago.</p>
<h3>¿La selección de esta página es todo el catálogo?</h3><p>No. Es una muestra. La categoría correspondiente puede contener más referencias según el stock.</p>
<h3>¿Puedo añadir productos de otros tipos?</h3><p>Sí. Puedes continuar por la tienda y completar la cesta con otras familias.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Prepara una compra que te sirva para varios días</h2><p>Elige carne y completa tu cesta con los alimentos que necesites.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar carne</a></p></div>
HTML
),
array(
'key'=>'ham','slug'=>'comprar-jamon-iberico-edenred',
'title'=>'Comprar jamón ibérico y paleta con Edenred',
'excerpt'=>'Compra jamón ibérico, paleta y otros formatos y paga con Edenred Ticket Restaurant. Compara productores, categorías y presentaciones.',
'seo_title'=>'Comprar jamón ibérico con Edenred | Ticket Restaurant',
'seo_description'=>'Compra jamón ibérico y paleta online y paga con Edenred. Compara piezas, formatos y productores en El Mercado de Origen.',
'focus'=>'comprar jamón ibérico con Edenred','term_needles'=>array('jamones','jamon','paletas','paleta'),
'content'=><<<'HTML'
<p>Si tienes saldo de <strong>Edenred Ticket Restaurant</strong> y quieres dedicarlo a una compra gastronómica, el jamón y la paleta son una de las familias con más profundidad de El Mercado de Origen. Puedes encontrar piezas y otros formatos según productor, categoría y disponibilidad.</p>
<p>Esta página está pensada para búsquedas como <strong>“comprar jamón con Edenred”</strong>, “jamón ibérico Edenred” o “pagar jamón con Ticket Restaurant”. Empieza comparando referencias y continúa después por la categoría completa o por el resto de la tienda.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Elige jamón o paleta y paga con Edenred</h2><p>Compara formato, categoría, peso y productor antes de decidir.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver jamones y paletas</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Jamón o paleta: qué mirar antes de comprar</h2>
<p>Jamón y paleta no son el mismo producto ni tienen el mismo tamaño. Revisa el peso aproximado, la categoría indicada por el productor, la presentación y si buscas una pieza completa u otro formato más cómodo. En una compra online estos datos importan más que quedarse únicamente con el nombre comercial.</p>
<h2>Una compra especial para aprovechar saldo Edenred</h2>
<p>Una pieza de jamón o una paleta puede concentrar una parte importante del importe de una compra. Para quien dispone de saldo y quiere utilizarlo en alimentación puede ser una opción interesante siempre que sea un producto que realmente vaya a consumir o compartir.</p>
<p>Si prefieres repartir el importe entre varias cosas, combina jamón con embutidos, aceite, conservas u otros alimentos. No necesitas construir la compra alrededor de una sola familia.</p>
<h2>Jamones y paletas disponibles</h2>
{{PRODUCT_GRID}}
<p>La disponibilidad cambia según cada productor. Consulta la categoría completa para ver referencias, pesos y formatos activos.</p>
<h2>Productores diferentes, productos diferentes</h2>
<p>El Mercado de Origen reúne a distintos vendedores y productores. Puedes encontrarte con jamones y paletas de procedencias, categorías y presentaciones distintas. El productor aparece identificado en la ficha, por lo que puedes comparar más allá del precio.</p>
<h2>Pieza entera y consumo en casa</h2>
<p>Una pieza completa tiene sentido si se consume jamón con frecuencia y se dispone de los utensilios adecuados. Otros formatos pueden ser más sencillos cuando el consumo es ocasional. Piensa en cuánto vas a consumir y cómo conservarás el producto una vez abierto.</p>
<h2>Cómo pagar jamón con Edenred</h2>
<p>Realiza el pedido de forma habitual y elige Edenred / Ticket Restaurant en el proceso de pago. La autorización depende de que la tarjeta esté operativa y tenga saldo disponible suficiente.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo comprar jamón ibérico online con Edenred?</h3><p>Sí. El Mercado de Origen acepta Edenred / Ticket Restaurant como método de pago.</p>
<h3>¿Hay jamones y paletas de varios productores?</h3><p>La oferta depende del catálogo activo, pero las fichas identifican al vendedor o productor de cada referencia.</p>
<h3>¿Puedo añadir aceite o embutidos al mismo carrito?</h3><p>Sí. Puedes completar la compra con otras categorías antes de pagar.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Una compra gastronómica con tu saldo Edenred</h2><p>Empieza por jamón o paleta y completa después la cesta con el resto de la tienda.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar jamón y paleta</a></p></div>
HTML
),
array(
'key'=>'cured','slug'=>'comprar-embutidos-edenred',
'title'=>'Comprar embutidos e ibéricos con Edenred',
'excerpt'=>'Compra embutidos, curados e ibéricos online y paga con Edenred Ticket Restaurant. Descubre productores y formatos para casa.',
'seo_title'=>'Comprar embutidos con Edenred | Ibéricos online',
'seo_description'=>'Compra embutidos e ibéricos online y paga con Edenred. Descubre chorizo, salchichón, lomo y otros curados según disponibilidad.',
'focus'=>'comprar embutidos con Edenred','term_needles'=>array('embutidos','embutido','curados'),
'content'=><<<'HTML'
<p>Los embutidos y curados permiten preparar una compra flexible si quieres utilizar <strong>Edenred Ticket Restaurant</strong> en alimentación. En El Mercado de Origen puedes encontrar referencias de distintos productores y añadirlas al carrito junto con jamón, aceite, conservas u otros productos.</p>
<p>Si has llegado buscando <strong>comprar embutidos con Edenred</strong>, esta página te muestra una parte del catálogo. La selección cambia con el stock y desde la categoría puedes seguir comparando antes de decidir.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Embutidos y curados para completar tu compra</h2><p>Elige formatos que encajen con tu consumo y continúa por el resto del catálogo si quieres aprovechar más saldo.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver embutidos</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Qué puedes encontrar</h2>
<p>Dependiendo de la oferta activa, pueden aparecer chorizos, salchichones, lomos y otros productos curados o ibéricos. No todos tienen la misma composición, formato o procedencia, así que revisa la ficha individual y el productor antes de comprar.</p>
<p>Para una compra destinada a casa, piensa también en cuánto tardarás en consumir cada producto y en sus indicaciones de conservación.</p>
<h2>Embutidos para compartir o tener en casa</h2>
<p>Los curados funcionan como producto de despensa, aperitivo o ingrediente para cenas sencillas. Si tienes saldo Edenred y quieres convertirlo en alimentos que puedas utilizar en distintos momentos, esta versatilidad puede resultar útil.</p>
<h2>Embutidos disponibles</h2>
{{PRODUCT_GRID}}
<p>Son una selección del catálogo actual. Entra en la categoría para comprobar todas las referencias activas.</p>
<h2>Cómo comparar productos</h2>
<p>Mira peso neto, composición, presentación, procedencia y productor. En ibéricos y curados puede haber diferencias importantes de materia prima y elaboración, por lo que dos productos con nombres parecidos no tienen por qué ser equivalentes.</p>
<p>Si estás ajustando una compra a tu saldo disponible, combina varias referencias o añade productos de otras familias hasta construir un carrito que tenga sentido.</p>
<h2>Más allá de los embutidos</h2>
<p>El Mercado de Origen no se limita al ibérico. Puedes completar la cesta con carne, aceite, verduras y hortalizas, conservas, legumbres y jamón. Revisa la logística si compras a varios vendedores.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo pagar embutidos con Edenred?</h3><p>Sí. Puedes utilizar Edenred / Ticket Restaurant como método de pago en El Mercado de Origen.</p>
<h3>¿La tienda tiene solo productos ibéricos?</h3><p>No. Esta es una de las familias, pero puedes completar la cesta con otras categorías.</p>
<h3>¿Puedo usar el saldo en varios productos diferentes?</h3><p>Sí. Prepara el carrito y revisa el total antes de pagar.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Monta una cesta a tu medida</h2><p>Empieza por embutidos y añade el resto de alimentos que quieras comprar.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar embutidos</a></p></div>
HTML
),
array(
'key'=>'vegetables','slug'=>'comprar-verduras-hortalizas-edenred',
'title'=>'Comprar verduras y hortalizas frescas con Edenred',
'excerpt'=>'Compra verduras y hortalizas online y paga con Edenred Ticket Restaurant. Descubre producto de huerta y completa tu compra de alimentación.',
'seo_title'=>'Comprar verduras con Edenred | Hortalizas online',
'seo_description'=>'Compra verduras y hortalizas online y paga con Edenred. Descubre producto de huerta y completa tu cesta en El Mercado de Origen.',
'focus'=>'comprar verduras con Edenred','term_needles'=>array('verduras','hortalizas','huerta'),
'content'=><<<'HTML'
<p>Si quieres utilizar <strong>Edenred Ticket Restaurant</strong> en una compra cotidiana, las verduras y hortalizas son una de las opciones más ligadas a la cesta semanal. En El Mercado de Origen puedes comprar producto de huerta online y combinarlo con carne, aceite, legumbres, conservas y otras familias.</p>
<p>Esta página está pensada para quien busca <strong>comprar verduras con Edenred</strong> o pagar una compra de hortalizas con Ticket Restaurant. Los productos visibles son una muestra del catálogo disponible.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Lleva parte de tu saldo Edenred a la cesta semanal</h2><p>Consulta verduras y hortalizas disponibles y completa el pedido con otros alimentos.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver verduras y hortalizas</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Producto de huerta para una compra real</h2>
<p>Las verduras forman parte de comidas, guarniciones, ensaladas, cremas y guisos. Si buscas una forma útil de gastar saldo Edenred, puede tener sentido destinarlo a alimentos que ya estaban en tu lista de la compra.</p>
<p>La disponibilidad de producto fresco puede variar. Revisa siempre la ficha, el formato y la cantidad incluida para saber qué estás añadiendo al carrito.</p>
<h2>Cajas, formatos y productos de temporada</h2>
<p>Según la oferta del productor, puedes encontrar referencias individuales o combinaciones. En una caja de huerta conviene comprobar qué incluye, el peso o unidades indicadas y cualquier información sobre composición variable.</p>
<p>La temporalidad puede hacer que la oferta cambie, por lo que es mejor consultar la categoría actual antes de planificar una compra concreta.</p>
<h2>Verduras y hortalizas disponibles</h2>
{{PRODUCT_GRID}}
<p>La selección se genera a partir de productos activos. Consulta la categoría completa para ver más opciones.</p>
<h2>Combina la huerta con otros alimentos</h2>
<p>Una cesta semanal rara vez se compone de una sola familia. Puedes continuar por carne, legumbres y aceite para preparar platos completos, o añadir conservas para ampliar la despensa.</p>
<p>Si quieres utilizar un importe concreto de Edenred, añade primero lo que necesitas y ajusta después el carrito.</p>
<h2>Comprar fresco online: qué revisar</h2>
<p>Comprueba conservación, cantidad, disponibilidad y envío. Al tratarse de un marketplace, los productos pueden salir de distintos vendedores y la operativa depende de la composición del pedido.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo comprar verduras online con Edenred?</h3><p>Sí. El Mercado de Origen acepta Edenred / Ticket Restaurant como método de pago.</p>
<h3>¿Las verduras de esta página son siempre las mismas?</h3><p>No necesariamente. La oferta cambia con el stock y la disponibilidad del productor.</p>
<h3>¿Puedo añadir carne, aceite o legumbres?</h3><p>Sí. Puedes combinar distintas familias antes de finalizar la compra.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Haz una compra útil para casa</h2><p>Empieza por verduras y hortalizas y completa después la cesta.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar verduras y hortalizas</a></p></div>
HTML
),
array(
'key'=>'preserves','slug'=>'comprar-conservas-edenred',
'title'=>'Comprar conservas online con Edenred',
'excerpt'=>'Compra conservas online y paga con Edenred Ticket Restaurant. Añade productos de despensa de productores españoles y completa tu cesta.',
'seo_title'=>'Comprar conservas con Edenred | Tienda online',
'seo_description'=>'Compra conservas online y paga con Edenred. Descubre productos de despensa y completa tu cesta de alimentación.',
'focus'=>'comprar conservas con Edenred','term_needles'=>array('conservas','conserva'),
'content'=><<<'HTML'
<p>Las conservas son una opción práctica para quien quiere utilizar saldo de <strong>Edenred Ticket Restaurant</strong> en productos que pueda guardar y consumir poco a poco. En El Mercado de Origen encontrarás referencias de productores y puedes combinarlas con aceite, legumbres, carne, verduras, jamón y otros alimentos.</p>
<p>Si buscas <strong>comprar conservas con Edenred</strong>, empieza por la selección de esta página y entra después en la categoría completa para comparar formatos, ingredientes y productores.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Despensa útil pagada con Edenred</h2><p>Elige conservas que vayas a consumir y completa el pedido con otras familias.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver conservas</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Por qué encajan bien si quieres aprovechar saldo</h2>
<p>Muchas conservas permiten organizar la despensa con más margen que los productos frescos. Eso las hace útiles cuando quieres transformar parte del saldo en alimentos que no necesariamente consumirás esa misma semana.</p>
<p>La vida útil concreta depende de cada producto. Revisa fecha, conservación e instrucciones una vez abierto el envase.</p>
<h2>Qué mirar antes de comprar conservas online</h2>
<p>Lee la denominación, ingredientes, peso neto y, cuando corresponda, peso escurrido. Dos envases similares pueden contener cantidades distintas de producto útil. También conviene fijarse en el productor y la procedencia indicada.</p>
<h2>Conservas disponibles</h2>
{{PRODUCT_GRID}}
<p>Estas referencias son una selección de productos activos. Consulta la categoría completa para descubrir más opciones.</p>
<h2>Combina conservas con legumbres y aceite</h2>
<p>Una compra de despensa puede reunir varias familias. Las legumbres y el AOVE complementan bien una cesta pensada para cocinar en casa. Si quieres fresco, también puedes recorrer verduras, hortalizas y carne.</p>
<p>El saldo Edenred no obliga a concentrar el pedido en un único tipo de alimento. Utiliza la tienda como una cesta completa.</p>
<h2>Cómo pagar con Ticket Restaurant</h2>
<p>Añade las conservas al carrito, continúa comprando si lo necesitas y finaliza utilizando Edenred / Ticket Restaurant. La tarjeta debe estar operativa y disponer del saldo necesario.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo comprar conservas online con Edenred?</h3><p>Sí. Puedes realizar la compra en El Mercado de Origen y utilizar Edenred / Ticket Restaurant.</p>
<h3>¿Puedo comprar varias unidades?</h3><p>Puedes añadir las unidades disponibles que necesites, sujetas al stock de cada producto.</p>
<h3>¿Puedo completar el carrito con alimentos frescos?</h3><p>Sí. Puedes añadir productos de otras categorías antes de finalizar.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Llena la despensa con productos que vas a utilizar</h2><p>Explora conservas y añade después el resto de alimentos que necesites.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar conservas</a></p></div>
HTML
),
array(
'key'=>'legumes','slug'=>'comprar-legumbres-edenred',
'title'=>'Comprar legumbres online con Edenred',
'excerpt'=>'Compra legumbres online y paga con Edenred Ticket Restaurant. Descubre productos de despensa y completa tu cesta con alimentación para casa.',
'seo_title'=>'Comprar legumbres con Edenred | Ticket Restaurant',
'seo_description'=>'Compra legumbres online y paga con Edenred. Descubre productos para tu despensa y completa la compra con otros alimentos.',
'focus'=>'comprar legumbres con Edenred','term_needles'=>array('legumbres','legumbre'),
'content'=><<<'HTML'
<p>Si buscas una forma práctica de utilizar <strong>Edenred Ticket Restaurant</strong> en alimentación para casa, las legumbres son un básico de despensa que encaja bien en una compra planificada. En El Mercado de Origen puedes comprar legumbres online y completar la cesta con aceite, verduras, carne, conservas y otras familias.</p>
<p>Esta landing responde a búsquedas como <strong>“comprar legumbres con Edenred”</strong> o “pagar legumbres con Ticket Restaurant”. Los productos mostrados son una selección del catálogo activo.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Legumbres para tu despensa</h2><p>Compra productos que puedas integrar en tus comidas habituales y aprovecha el pedido para completar la cesta.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver legumbres</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Una compra pensada para cocinar en casa</h2>
<p>Las legumbres permiten planificar platos para varios días y combinan con verduras, carne, aceite y otros ingredientes. Si tienes saldo Edenred, utilizarlas como parte de una compra real puede ser más útil que buscar un gasto puntual que no tenías previsto.</p>
<p>Muchos formatos tienen una conservación sencilla mientras permanecen cerrados. Revisa la ficha para conocer peso, variedad y cualquier indicación específica.</p>
<h2>Qué legumbres elegir</h2>
<p>La oferta depende del productor y del catálogo activo. La elección debería partir del tipo de recetas que prepares y de la cantidad que consumes, no únicamente del importe del envase.</p>
<p>Si cocinas con frecuencia puede tener sentido llevar varias unidades. Si las utilizas de forma ocasional, prueba con una cantidad menor y completa la cesta con otras familias.</p>
<h2>Legumbres disponibles</h2>
{{PRODUCT_GRID}}
<p>Consulta la categoría completa para ver las referencias activas y sus formatos actuales.</p>
<h2>Construye una cesta de despensa completa</h2>
<p>Una compra de legumbres combina especialmente bien con AOVE y conservas, y también puede complementarse con verduras o carne. Desde esta landing puedes continuar por la tienda y seguir añadiendo productos.</p>
<p>Si quieres gastar un saldo concreto, utiliza el carrito como guía: comprueba el total, ajusta cantidades y finaliza cuando la compra tenga sentido.</p>
<h2>Productores y procedencia visibles</h2>
<p>Cada producto conserva la referencia al vendedor o productor correspondiente. Consulta también ingredientes, peso y características antes de cerrar el pedido.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo pagar legumbres con Edenred?</h3><p>Sí. Edenred / Ticket Restaurant está disponible como método de pago en El Mercado de Origen.</p>
<h3>¿Puedo mezclar legumbres con otros productos?</h3><p>Sí. Puedes añadir productos de otras categorías y preparar una cesta más amplia.</p>
<h3>¿Los productos de esta página son todos los que hay?</h3><p>No. Son una selección; la categoría muestra las referencias activas.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Aprovecha Edenred en alimentos que forman parte de tu cocina</h2><p>Empieza por legumbres y completa después tu compra.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Comprar legumbres</a></p></div>
HTML
),
array(
'key'=>'packs','slug'=>'comprar-packs-lotes-comida-edenred',
'title'=>'Comprar packs y lotes de comida con Edenred',
'excerpt'=>'Compra packs, cajas y lotes de alimentación y paga con Edenred Ticket Restaurant. Descubre formatos para casa y productos de distintos productores.',
'seo_title'=>'Comprar packs y lotes de comida con Edenred',
'seo_description'=>'Compra packs y lotes de alimentación online y paga con Edenred. Descubre cajas y formatos para casa en El Mercado de Origen.',
'focus'=>'comprar lotes de comida con Edenred','term_needles'=>array('packs','pack','lotes','lote'),'title_needles'=>array('pack','lote','caja'),
'content'=><<<'HTML'
<p>Cuando quieres utilizar una cantidad mayor de <strong>saldo Edenred</strong> en una compra, los packs, cajas y lotes de alimentación pueden resultar cómodos porque reúnen varias unidades o productos en un mismo formato. En El Mercado de Origen la disponibilidad depende de cada productor.</p>
<p>Esta página responde a búsquedas como <strong>“comprar lotes de comida con Edenred”</strong>, “packs de alimentación con Ticket Restaurant” o una forma de aprovechar saldo en productos para casa.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres hacer una compra más amplia?</h2><p>Empieza por packs y cajas y entra después en la tienda completa para añadir otros productos.</p><p style="margin-bottom:0"><a class="button" href="{{CATEGORY_URL}}">Ver packs y lotes</a> <a class="button" href="{{SHOP_URL}}">Ver toda la tienda</a></p></div>
<h2>Qué entendemos por pack o lote</h2>
<p>Puede ser un conjunto de varias unidades del mismo producto, una caja con diferentes alimentos o una combinación preparada por el productor. Revisa exactamente qué incluye, cuántas unidades contiene, el peso total y las condiciones de conservación.</p>
<p>También puede haber productos que, sin llamarse lote, se vendan en formatos múltiples. Si compras para varias semanas, compara esos formatos con las unidades individuales.</p>
<h2>Packs para utilizar saldo de forma práctica</h2>
<p>Un pack puede ayudarte a concentrar parte del saldo en productos que ya utilizas: varias botellas de aceite, una caja de huerta o una selección de despensa pueden tener más recorrido que una compra puntual.</p>
<p>Un pack solo es una buena compra si su contenido te interesa. No lo elijas únicamente porque su importe coincide con el saldo.</p>
<h2>Packs, cajas y lotes disponibles</h2>
{{PRODUCT_GRID}}
<p>La selección depende del catálogo activo. Si hay pocos packs específicos, utiliza la tienda completa para combinar tú mismo productos y construir una cesta equivalente.</p>
<h2>Crea tu propio lote mezclando categorías</h2>
<p>No necesitas comprar un pack cerrado. Puedes montar una cesta con AOVE, jamón, embutidos, conservas, legumbres, verduras o carne y ajustar el carrito al importe que quieras utilizar.</p>
<h2>Cómo pagar un lote con Edenred</h2>
<p>Prepara el carrito, comprueba el total y elige Edenred / Ticket Restaurant durante el pago. La autorización final depende del estado de la tarjeta y del saldo disponible.</p>
<h2>Preguntas frecuentes</h2>
<h3>¿Puedo pagar un pack de comida con Edenred?</h3><p>Sí. Puedes realizar tu compra utilizando Edenred / Ticket Restaurant.</p>
<h3>¿Hay lotes cerrados y packs de varias unidades?</h3><p>La oferta depende de cada productor y del stock. Revisa las fichas activas.</p>
<h3>¿Puedo crear mi propio lote?</h3><p>Sí. Puedes combinar productos de distintas categorías en el carrito.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Pack cerrado o cesta a tu medida</h2><p>Elige la opción que te permita aprovechar Edenred en productos que realmente vas a consumir.</p><p style="margin-bottom:0"><a class="button" href="{{SHOP_URL}}">Explorar toda la tienda</a></p></div>
HTML
),
array(
'key'=>'balance','slug'=>'como-gastar-saldo-edenred-comida-online',
'title'=>'Cómo gastar el saldo de Edenred en comida online',
'excerpt'=>'Ideas para aprovechar el saldo de Edenred Ticket Restaurant comprando comida online: aceite, carne, jamón, verduras, conservas, legumbres y más.',
'seo_title'=>'Cómo gastar el saldo de Edenred en comida online',
'seo_description'=>'¿Te queda saldo en Edenred? Descubre cómo utilizarlo en una compra de alimentación online con productos para casa y despensa.',
'focus'=>'cómo gastar saldo Edenred','mode'=>'general',
'content'=><<<'HTML'
<p>Si te queda saldo en <strong>Edenred Ticket Restaurant</strong> y estás buscando cómo aprovecharlo, una opción es utilizarlo en una compra de comida online para casa. En El Mercado de Origen puedes preparar un carrito con distintas familias de alimentación y pagar con Edenred al finalizar.</p>
<p>La clave no debería ser gastar por gastar, sino convertir ese saldo en productos que ya ibas a consumir: aceite para cocinar, carne para varias comidas, jamón o embutidos, verduras y hortalizas, conservas, legumbres y otros alimentos.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Te queda saldo Edenred?</h2><p>Prepara una cesta útil para casa, revisa el total y paga con Ticket Restaurant cuando termines.</p><p style="margin-bottom:0"><a class="button" href="{{SHOP_URL}}">Ver productos para comprar con Edenred</a></p></div>
<h2>1. Empieza por productos que consumes de forma recurrente</h2>
<p>Si utilizas aceite de oliva todos los días, un formato adecuado a tu consumo puede ser un buen punto de partida. Si cocinas carne varias veces a la semana, revisa cortes y formatos. Si en tu despensa nunca faltan legumbres o conservas, añade primero esos básicos.</p>
<p>Este enfoque evita terminar con productos comprados únicamente porque había saldo. La mejor compra es la que sustituye gasto de alimentación que ibas a realizar de todos modos.</p>
<h2>2. Si tienes más saldo, combina varias familias</h2>
<p>No necesitas elegir entre aceite o carne, jamón o verduras. Puedes construir un carrito variado. Mezclar fresco y despensa puede hacer que la compra sea útil durante varios días o semanas.</p>
<ul><li><strong>AOVE:</strong> un básico de uso habitual.</li><li><strong>Carne:</strong> para planificar varias comidas.</li><li><strong>Jamón y embutidos:</strong> una compra gastronómica para casa o compartir.</li><li><strong>Verduras y hortalizas:</strong> producto para la cesta semanal.</li><li><strong>Conservas:</strong> fáciles de integrar en la despensa.</li><li><strong>Legumbres:</strong> versátiles para recetas de diario.</li></ul>
<h2>3. Utiliza el carrito para ajustar el importe</h2>
<p>Si sabes aproximadamente cuánto saldo quieres utilizar, añade primero los productos prioritarios y revisa el total. Después puedes ajustar cantidades, cambiar formatos o incorporar otro alimento que necesites.</p>
<h2>Productos para empezar la compra</h2>
{{PRODUCT_GRID}}
<p>Esta selección es solo una puerta de entrada. Entra en la tienda completa antes de finalizar si quieres comparar otras opciones.</p>
<h2>4. Piensa en la duración de la compra</h2>
<p>Combina productos con ritmos de consumo diferentes. El fresco puede utilizarse primero; los alimentos de despensa pueden permanecer cerrados hasta que los necesites; los formatos grandes deben elegirse solo cuando el consumo en casa justifique esa cantidad.</p>
<h2>5. Revisa vendedores y envío</h2>
<p>El Mercado de Origen es un marketplace. Los productos pueden proceder de vendedores distintos y cada productor prepara su parte del pedido. Antes de pagar, revisa cómo queda organizada la compra y las condiciones de envío aplicables.</p>
<h2>6. Paga con Edenred / Ticket Restaurant</h2>
<p>Cuando el carrito esté listo, continúa al pago y utiliza Edenred / Ticket Restaurant. La tarjeta debe estar operativa y disponer del saldo necesario para que la transacción sea autorizada.</p>
<p>Si tienes dudas sobre el saldo disponible, compruébalo en los canales de Edenred antes de iniciar el pago. El Mercado de Origen acepta el método de pago, pero no gestiona el saldo de tu tarjeta.</p>
<h2>Ideas según el tipo de compra</h2>
<h3>Quiero cubrir parte de la compra semanal</h3><p>Prioriza verduras, hortalizas, carne y otros alimentos para los próximos días y completa con básicos de despensa.</p>
<h3>Quiero guardar productos para más adelante</h3><p>Mira conservas, legumbres y formatos de aceite adecuados a tu consumo.</p>
<h3>Quiero hacer una compra más especial</h3><p>Jamón, paleta y embutidos pueden funcionar como compra gastronómica para casa o para compartir.</p>
<h3>Quiero gastar un importe alto</h3><p>Compara packs o construye una cesta variada con productos que ya sabes que vas a utilizar.</p>
<h2>Preguntas frecuentes sobre el saldo Edenred</h2>
<h3>¿Puedo usar Edenred para comprar comida online?</h3><p>Sí. Edenred / Ticket Restaurant está disponible como método de pago en El Mercado de Origen.</p>
<h3>¿Tengo que comprar solo los productos de esta página?</h3><p>No. Puedes recorrer la tienda completa y añadir otras referencias.</p>
<h3>¿Cómo sé cuánto saldo me queda?</h3><p>Consulta tu saldo en los canales oficiales de Edenred. Nosotros no vemos ni gestionamos el saldo de tu tarjeta.</p>
<h3>¿Qué pasa si no tengo saldo suficiente?</h3><p>La autorización depende del saldo y de las condiciones de tu tarjeta. Revisa el total y el saldo disponible antes de pagar.</p>
<div class="emdo-edenred-cta" style="margin:28px 0;padding:26px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Convierte el saldo en una compra que vayas a utilizar</h2><p>Empieza por tus básicos y completa después la cesta.</p><p style="margin-bottom:0"><a class="button" href="{{SHOP_URL}}">Comprar comida online con Edenred</a></p></div>
HTML
),
);

if ( count( $pages ) !== 10 ) throw new Exception( 'Expected exactly 10 pages, found ' . count( $pages ) );

$admins = get_users( array( 'role'=>'administrator', 'number'=>1, 'fields'=>'ID' ) );
$author = $admins ? (int) $admins[0] : 1;
$rows = array();

foreach ( $pages as $p ) {
    $term = null; $products = array();
    if ( ! empty( $p['mode'] ) && 'general' === $p['mode'] ) {
        $products = emdo_edenred_general_products( 12 );
    } else {
        $term = emdo_edenred_find_term( (array) ( $p['term_needles'] ?? array() ) );
        if ( $term instanceof WP_Term ) $products = emdo_edenred_products_in_term( $term->term_id, 8 );
        if ( count( $products ) < 2 && ! empty( $p['title_needles'] ) ) $products = emdo_edenred_products_by_title( (array) $p['title_needles'], 8 );
        if ( count( $products ) < 2 ) $products = emdo_edenred_general_products( 8 );
    }

    $category_url = '';
    if ( $term instanceof WP_Term ) {
        $u = get_term_link( $term );
        if ( ! is_wp_error( $u ) && is_string( $u ) ) $category_url = $u;
    }

    $content = emdo_edenred_render( $p, $products, $category_url );
    $words = emdo_edenred_words( $content );
    if ( $words < 430 ) throw new Exception( $p['key'] . ' too short: ' . $words );

    $existing = emdo_edenred_existing( $p['key'], $p['slug'] );
    $args = array(
        'post_type'=>'page','post_status'=>'publish','post_author'=>$author,
        'post_title'=>$p['title'],'post_name'=>$p['slug'],'post_excerpt'=>$p['excerpt'],
        'post_content'=>$content,'comment_status'=>'closed','ping_status'=>'closed',
        'post_parent'=>0,'menu_order'=>0,
    );
    if ( $existing ) $args['ID'] = $existing;
    $saved = $existing ? wp_update_post( wp_slash( $args ), true ) : wp_insert_post( wp_slash( $args ), true );
    if ( is_wp_error( $saved ) ) throw new Exception( $p['key'] . ': ' . $saved->get_error_message() );
    $id = (int) $saved;

    update_post_meta( $id, '_emdo_edenred_landing_key', $p['key'] );
    update_post_meta( $id, '_emdo_edenred_landing_batch', '20261001-edenred-01' );
    update_post_meta( $id, '_emdo_seo_landing_hidden_navigation', '1' );
    update_post_meta( $id, '_emdo_edenred_updated_at', gmdate( 'c' ) );

    foreach ( array( '_en_US_published','_en_US_post_title','_en_US_post_name','_en_US_post_excerpt','_en_US_post_content' ) as $meta_key ) delete_post_meta( $id, $meta_key );

    update_post_meta( $id, '_yoast_wpseo_title', $p['seo_title'] );
    update_post_meta( $id, '_yoast_wpseo_metadesc', $p['seo_description'] );
    update_post_meta( $id, '_yoast_wpseo_focuskw', $p['focus'] );
    update_post_meta( $id, 'rank_math_title', $p['seo_title'] );
    update_post_meta( $id, 'rank_math_description', $p['seo_description'] );
    update_post_meta( $id, 'rank_math_focus_keyword', $p['focus'] );
    delete_post_meta( $id, '_yoast_wpseo_meta-robots-noindex' );
    delete_post_meta( $id, 'rank_math_robots' );
    emdo_edenred_aioseo( $id, $p['seo_title'], $p['seo_description'] );
    clean_post_cache( $id );

    $rows[] = array(
        'key'=>$p['key'],'id'=>$id,'title'=>get_the_title($id),'slug'=>get_post_field('post_name',$id),
        'type'=>get_post_type($id),'status'=>get_post_status($id),'words'=>$words,
        'product_count'=>count($products),'category'=>$term instanceof WP_Term ? $term->name : null,
        'url'=>get_permalink($id),'english_live'=>'1' === (string) get_post_meta($id,'_en_US_published',true),
    );
}

echo wp_json_encode( array( 'batch'=>'20261001-edenred-01','count'=>count($rows),'pages'=>$rows ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT ) . PHP_EOL;
