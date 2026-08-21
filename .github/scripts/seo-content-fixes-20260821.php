<?php
/** One-time, idempotent SEO data fixes. Only fills empty fields. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$category_copy = array(
    'jamones-paletas' => array(
        'es' => 'Descubre nuestra selección de jamones y paletas de productores españoles, con información clara sobre su origen, elaboración, raza, alimentación y curación cuando corresponde. Compara formatos y características para elegir la pieza que mejor encaje contigo y compra online directamente a los productores presentes en El Mercado de Origen.',
        'en' => 'Discover our selection of hams and shoulders from Spanish producers, with clear information about origin, production, breed, feeding and curing whenever relevant. Compare formats and characteristics to choose the right piece and shop online from producers available at El Mercado de Origen.',
    ),
    'embutidos-y-curados' => array(
        'es' => 'Explora embutidos y productos curados seleccionados de productores españoles. Consulta ingredientes, elaboración, formatos y procedencia en cada ficha para comparar con criterio y encontrar la opción que mejor se adapte a lo que buscas. Compra online con el productor y el origen siempre visibles.',
        'en' => 'Explore selected cured meats and charcuterie from Spanish producers. Check ingredients, production methods, formats and origin on each product page so you can compare with confidence and choose what suits you best, with the producer and provenance always clearly identified.',
    ),
    'packs-y-lotes' => array(
        'es' => 'Encuentra packs y lotes que reúnen distintos productos de nuestros productores en formatos pensados para probar, compartir o resolver una compra completa. Revisa el contenido de cada lote, sus cantidades, procedencia y condiciones de envío antes de elegir el que mejor encaje con tu pedido.',
        'en' => 'Find packs and assortments that bring together products from our producers in formats designed for tasting, sharing or completing an order. Check the contents, quantities, origin and shipping conditions of each pack before choosing the option that best suits you.',
    ),
    'carnes' => array(
        'es' => 'Compra carne online de productores y profesionales especializados, con cortes y formatos pensados para diferentes recetas y formas de cocinar. En cada producto puedes consultar la información disponible sobre el corte, preparación, peso o formato y procedencia para elegir con mayor claridad.',
        'en' => 'Shop meat online from selected producers and specialists, with cuts and formats suited to different recipes and cooking methods. Each product page shows the available information about the cut, preparation, weight or format and origin so you can choose more confidently.',
    ),
    'conservas' => array(
        'es' => 'Descubre conservas seleccionadas de productores españoles y conoce qué hay detrás de cada producto: ingredientes, formato, elaboración y origen cuando esa información está disponible. Una forma sencilla de comparar propuestas y comprar conservas online directamente dentro de nuestra selección.',
        'en' => 'Discover selected preserves from Spanish producers and learn what is behind each product: ingredients, format, production method and origin whenever that information is available. An easy way to compare options and shop preserves online within our selected range.',
    ),
    'hortalizas-verduras' => array(
        'es' => 'Descubre hortalizas y verduras de productores seleccionados, con formatos que pueden variar según el producto, la temporada y la disponibilidad. Consulta cada ficha para conocer cantidades, composición, procedencia y condiciones de entrega antes de realizar tu pedido.',
        'en' => 'Discover vegetables from selected producers, with formats that may vary according to the product, season and availability. Check each product page for quantities, contents, origin and delivery conditions before placing your order.',
    ),
    'legumbres' => array(
        'es' => 'Explora nuestra selección de legumbres y compara variedades, formatos y procedencia antes de comprar. Cada ficha reúne la información disponible sobre el producto para ayudarte a distinguir sus características y elegir la opción que mejor encaje con tus recetas y tu forma de cocinar.',
        'en' => 'Explore our selection of pulses and compare varieties, formats and origin before buying. Each product page brings together the available information to help you understand its characteristics and choose the option that best suits your recipes and cooking style.',
    ),
    'accesorios' => array(
        'es' => 'Accesorios seleccionados para acompañar la preparación, el corte o el consumo de los productos de El Mercado de Origen. Consulta las características y compatibilidad de cada artículo antes de elegir el accesorio que necesitas.',
        'en' => 'Selected accessories to accompany the preparation, slicing or serving of products from El Mercado de Origen. Check the features and compatibility of each item before choosing the accessory you need.',
    ),
    'adobados' => array(
        'es' => 'Descubre productos adobados seleccionados y consulta en cada ficha el tipo de pieza, formato, preparación e información disponible sobre su elaboración. Compara las opciones antes de comprar y elige el producto que mejor se adapte a la receta que tienes en mente.',
        'en' => 'Discover selected marinated products and check each product page for the cut, format, preparation and available production information. Compare the options before buying and choose the product that best suits the recipe you have in mind.',
    ),
);

$cat_updated = 0; $cat_en_updated = 0; $cat_skipped = 0;
foreach ( $category_copy as $slug => $copy ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) { echo "CATEGORY_NOT_FOUND=$slug\n"; continue; }
    if ( trim( (string) $term->description ) === '' ) {
        $res = wp_update_term( $term->term_id, 'product_cat', array( 'description' => $copy['es'] ) );
        if ( is_wp_error( $res ) ) { echo "CATEGORY_ERROR=$slug|" . $res->get_error_message() . "\n"; }
        else { $cat_updated++; echo "CATEGORY_ES_UPDATED=$slug\n"; }
    } else { $cat_skipped++; echo "CATEGORY_ES_KEPT=$slug\n"; }
    if ( trim( (string) get_term_meta( $term->term_id, '_en_US_description', true ) ) === '' ) {
        update_term_meta( $term->term_id, '_en_US_description', $copy['en'] );
        $cat_en_updated++; echo "CATEGORY_EN_UPDATED=$slug\n";
    } else { echo "CATEGORY_EN_KEPT=$slug\n"; }
}

$products = get_posts(array('post_type'=>'product','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC'));
$alt_updated = 0; $alt_kept = 0; $unique = array();
foreach ( $products as $p ) {
    $label = trim( wp_strip_all_tags( (string) $p->post_title ) );
    if ( $label === '' ) continue;
    $image_ids = array();
    $thumb = (int) get_post_thumbnail_id( $p->ID );
    if ( $thumb ) $image_ids[] = $thumb;
    if ( function_exists( 'wc_get_product' ) ) {
        $wc = wc_get_product( $p->ID );
        if ( $wc ) $image_ids = array_merge( $image_ids, array_map( 'intval', (array) $wc->get_gallery_image_ids() ) );
    }
    foreach ( array_unique( $image_ids ) as $img_id ) {
        if ( $img_id < 1 || isset( $unique[$img_id] ) ) continue;
        $unique[$img_id] = true;
        $existing = trim( (string) get_post_meta( $img_id, '_wp_attachment_image_alt', true ) );
        if ( $existing === '' ) {
            update_post_meta( $img_id, '_wp_attachment_image_alt', $label );
            $alt_updated++;
        } else $alt_kept++;
    }
}

echo 'CATEGORY_ES_UPDATED_COUNT=' . $cat_updated . "\n";
echo 'CATEGORY_EN_UPDATED_COUNT=' . $cat_en_updated . "\n";
echo 'CATEGORY_EXISTING_KEPT_COUNT=' . $cat_skipped . "\n";
echo 'UNIQUE_PRODUCT_IMAGES_CHECKED=' . count($unique) . "\n";
echo 'IMAGE_ALT_UPDATED_COUNT=' . $alt_updated . "\n";
echo 'IMAGE_ALT_EXISTING_KEPT_COUNT=' . $alt_kept . "\n";

// Verification: all currently attached product images should now have alt text.
$missing_alt = 0;
foreach ( array_keys( $unique ) as $img_id ) if ( trim( (string) get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) === '' ) $missing_alt++;
echo 'IMAGE_ALT_MISSING_AFTER=' . $missing_alt . "\n";
if ( $missing_alt > 0 ) exit(2);

foreach ( array_keys( $category_copy ) as $slug ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( $term instanceof WP_Term ) {
        $fresh = get_term( $term->term_id, 'product_cat' );
        if ( trim( (string) $fresh->description ) === '' ) { echo "VERIFY_CATEGORY_ES_EMPTY=$slug\n"; exit(3); }
        if ( trim( (string) get_term_meta( $term->term_id, '_en_US_description', true ) ) === '' ) { echo "VERIFY_CATEGORY_EN_EMPTY=$slug\n"; exit(4); }
    }
}
echo "SEO_CONTENT_FIXES=PASS\n";
