<?php
/**
 * Safe SEO enrichment for the active catalogue.
 * - Adds descriptions only to blank public product categories.
 * - Adds English category descriptions only when blank and the English category is published.
 * - Fills only blank image ALT values for products of active vendors.
 * - Never changes product status, price, stock, visibility, category membership or vendor state.
 */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

global $wpdb;

function emdo_acse_flag_on( $value ): bool {
    if ( is_bool( $value ) ) return $value;
    if ( is_int( $value ) || is_float( $value ) ) return (int) $value !== 0;
    if ( is_string( $value ) ) {
        $v = strtolower( trim( $value ) );
        return ! in_array( $v, array( '', '0', 'no', 'false', 'off', 'none' ), true );
    }
    return ! empty( $value );
}

function emdo_acse_vendor_disabled( int $user_id ): bool {
    if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) ) {
        return (bool) elmercado_wcfm_vendor_is_disabled_010210( $user_id );
    }
    $user = get_userdata( $user_id );
    if ( ! $user instanceof WP_User ) return false;
    if ( in_array( 'disable_vendor', array_map( 'sanitize_key', (array) $user->roles ), true ) ) return true;
    if ( emdo_acse_flag_on( get_user_meta( $user_id, '_disable_vendor', true ) ) ) return true;
    return emdo_acse_flag_on( get_user_meta( $user_id, '_wcfm_store_offline', true ) );
}

function emdo_acse_plain( $value ): string {
    return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $value ) ) ) );
}

$category_copy = array(
    'aceites' => array(
        'es' => 'Aceites de oliva de distintas variedades, procedencias y formatos. Descubre la selección disponible y compara cada aceite por productor, origen y características.',
        'en' => 'Olive oils from different varieties, origins and formats. Explore the available selection and compare each oil by producer, origin and characteristics.',
    ),
    'jamones-paletas' => array(
        'es' => 'Jamones y paletas en distintas calidades, curaciones, formatos y presentaciones. Encuentra piezas enteras, deshuesadas y opciones loncheadas de los productores disponibles.',
        'en' => 'Hams and shoulder hams in different qualities, curing styles, formats and presentations. Find whole, boneless and sliced options from the available producers.',
    ),
    'embutidos-y-curados' => array(
        'es' => 'Chorizos, salchichones, lomos, lomitos y otros embutidos y curados. Compara formatos, elaboraciones y productores para elegir la opción que mejor encaje con tu pedido.',
        'en' => 'Chorizo, salchichón, cured loin and other cured meats. Compare formats, preparations and producers to choose the option that best suits your order.',
    ),
    'packs-y-lotes' => array(
        'es' => 'Packs y lotes que reúnen distintas especialidades en un mismo pedido. Una selección práctica para probar varios productos, compartir o regalar.',
        'en' => 'Packs and bundles combining different specialities in one order. A practical selection for trying several products, sharing or giving as a gift.',
    ),
    'carnes' => array(
        'es' => 'Carnes en distintos cortes y formatos para cocinar en casa, desde piezas y filetes hasta hamburguesas y lotes. Consulta cada ficha para conocer el corte, formato y productor.',
        'en' => 'Meat in different cuts and formats for cooking at home, from whole cuts and steaks to burgers and bundles. Check each product page for its cut, format and producer.',
    ),
    'conservas' => array(
        'es' => 'Conservas de verduras y otras elaboraciones vegetales listas para disfrutar o incorporar a tus recetas. Consulta formatos, ingredientes y productor en cada ficha.',
        'en' => 'Preserved vegetables and other plant-based preparations ready to enjoy or use in your recipes. Check formats, ingredients and producer on each product page.',
    ),
    'hortalizas-verduras' => array(
        'es' => 'Hortalizas y verduras frescas de temporada, con distintas variedades y formatos de la huerta. Revisa cada producto para conocer su presentación, origen y disponibilidad.',
        'en' => 'Fresh seasonal vegetables in different varieties and formats. Check each product for its presentation, origin and current availability.',
    ),
    'legumbres' => array(
        'es' => 'Alubias, garbanzos y lentejas en distintas variedades para guisos, potajes y otras recetas. Consulta cada ficha para conocer el formato y las características de cada legumbre.',
        'en' => 'Beans, chickpeas and lentils in different varieties for stews and other recipes. Check each product page for its format and specific characteristics.',
    ),
);

$category_rows = array();
$category_es_updated = 0;
$category_en_updated = 0;
foreach ( $category_copy as $slug => $copy ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) {
        $category_rows[] = array( 'slug' => $slug, 'status' => 'missing_term' );
        continue;
    }

    $es_before = emdo_acse_plain( $term->description );
    $es_updated = false;
    if ( $es_before === '' ) {
        $result = wp_update_term( $term->term_id, 'product_cat', array( 'description' => $copy['es'] ) );
        if ( is_wp_error( $result ) ) throw new RuntimeException( 'Category ES update failed for ' . $slug . ': ' . $result->get_error_message() );
        $category_es_updated++;
        $es_updated = true;
    }

    $en_published = (string) get_term_meta( $term->term_id, '_en_US_published', true );
    $en_before = emdo_acse_plain( get_term_meta( $term->term_id, '_en_US_description', true ) );
    $en_updated = false;
    if ( $en_published === '1' && $en_before === '' ) {
        update_term_meta( $term->term_id, '_en_US_description', $copy['en'] );
        $category_en_updated++;
        $en_updated = true;
    }

    $term_after = get_term( $term->term_id, 'product_cat' );
    $category_rows[] = array(
        'id' => (int) $term->term_id,
        'slug' => $slug,
        'count' => (int) $term->count,
        'es_updated' => $es_updated,
        'es_len_after' => $term_after instanceof WP_Term ? strlen( emdo_acse_plain( $term_after->description ) ) : 0,
        'en_published' => $en_published,
        'en_updated' => $en_updated,
        'en_len_after' => strlen( emdo_acse_plain( get_term_meta( $term->term_id, '_en_US_description', true ) ) ),
    );
}

$product_ids = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_type='product'
       AND post_status IN ('publish','draft','pending','private','future')
     ORDER BY ID ASC"
);

$status_before = array();
$active_products_checked = 0;
$disabled_products_skipped = 0;
$unique_images = array();
$alt_updated = 0;
$alt_existing_kept = 0;
$vendor_counts = array();

foreach ( array_map( 'intval', (array) $product_ids ) as $id ) {
    $post = get_post( $id );
    if ( ! $post instanceof WP_Post ) continue;
    if ( emdo_acse_vendor_disabled( (int) $post->post_author ) ) {
        $disabled_products_skipped++;
        continue;
    }

    $active_products_checked++;
    $status_before[$id] = $post->post_status;
    $vendor_counts[$post->post_author] = ($vendor_counts[$post->post_author] ?? 0) + 1;
    $label = emdo_acse_plain( $post->post_title );

    $image_ids = array();
    $thumb = (int) get_post_thumbnail_id( $id );
    if ( $thumb > 0 ) $image_ids[] = $thumb;
    foreach ( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_product_image_gallery', true ) ) ) ) as $gallery_id ) {
        $image_ids[] = $gallery_id;
    }

    foreach ( array_values( array_unique( $image_ids ) ) as $image_id ) {
        $unique_images[$image_id] = true;
        $old = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
        if ( $old === '' && $label !== '' ) {
            update_post_meta( $image_id, '_wp_attachment_image_alt', $label );
            $alt_updated++;
        } elseif ( $old !== '' ) {
            $alt_existing_kept++;
        }
    }
}

$status_changes = array();
foreach ( $status_before as $id => $before ) {
    $after = (string) get_post_status( $id );
    if ( $after !== $before ) $status_changes[] = array( 'id' => $id, 'before' => $before, 'after' => $after );
}

$blank_alt_after = array();
foreach ( array_keys( $unique_images ) as $image_id ) {
    if ( trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) === '' ) $blank_alt_after[] = (int) $image_id;
}

$summary = array(
    'categories_targeted' => count( $category_copy ),
    'category_es_updated' => $category_es_updated,
    'category_en_updated' => $category_en_updated,
    'active_products_checked' => $active_products_checked,
    'disabled_products_skipped' => $disabled_products_skipped,
    'unique_images_checked' => count( $unique_images ),
    'alt_updated' => $alt_updated,
    'alt_existing_kept' => $alt_existing_kept,
    'blank_alt_after' => count( $blank_alt_after ),
    'product_status_changes' => count( $status_changes ),
);

echo "EMDO ACTIVE CATALOG SAFE SEO ENRICHMENT 2026-08-21\n";
echo 'SUMMARY=' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
foreach ( $category_rows as $row ) echo 'CATEGORY=' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo 'VENDOR_PRODUCT_COUNTS=' . wp_json_encode( $vendor_counts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
if ( $blank_alt_after ) echo 'BLANK_ALT_AFTER=' . wp_json_encode( $blank_alt_after ) . "\n";
if ( $status_changes ) echo 'STATUS_CHANGES=' . wp_json_encode( $status_changes ) . "\n";

if ( $blank_alt_after || $status_changes ) throw new RuntimeException( 'Safe enrichment verification failed.' );
echo "ACTIVE_CATALOG_SAFE_ENRICHMENT=PASS\n";
