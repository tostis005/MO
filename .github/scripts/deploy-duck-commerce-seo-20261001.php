<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! taxonomy_exists( 'product_cat' ) || ! function_exists( 'wc_get_product' ) ) {
    throw new Exception( 'WooCommerce product taxonomy is not available.' );
}
if ( ! function_exists( 'mdo_duck_commerce_product_title_for_id_20261001' ) ) {
    throw new Exception( 'Duck commerce MU plugin is not loaded.' );
}

$supplier_id = EMDO_DUCK_COMMERCE_SUPPLIER_ID_20261001;
$all_selectos = get_posts( array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_supplier_id',
    'meta_value'     => $supplier_id,
    'orderby'        => 'ID',
    'order'          => 'ASC',
) );
if ( count( $all_selectos ) < 30 ) {
    throw new Exception( 'Selectos de Castilla published catalog unexpectedly small: ' . count( $all_selectos ) );
}

$normalize = static function ( string $text ): string {
    $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = remove_accents( $text );
    $text = strtolower( $text );
    $text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
    return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
};

$ensure_term = static function ( string $name, string $slug, int $parent = 0 ): WP_Term {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( $term instanceof WP_Term ) {
        $args = array( 'name'=>$name, 'slug'=>$slug );
        if ( (int) $term->parent !== $parent ) { $args['parent'] = $parent; }
        $updated = wp_update_term( (int) $term->term_id, 'product_cat', $args );
        if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }
        $term = get_term( (int) $term->term_id, 'product_cat' );
        if ( ! $term instanceof WP_Term ) { throw new Exception( 'Could not reload product category ' . $slug ); }
        return $term;
    }
    $created = wp_insert_term( $name, 'product_cat', array( 'slug'=>$slug, 'parent'=>$parent ) );
    if ( is_wp_error( $created ) ) { throw new Exception( $created->get_error_message() ); }
    $term = get_term( (int) $created['term_id'], 'product_cat' );
    if ( ! $term instanceof WP_Term ) { throw new Exception( 'Could not create product category ' . $slug ); }
    return $term;
};

$carnes = get_term_by( 'slug', 'carnes', 'product_cat' );
if ( ! $carnes instanceof WP_Term ) {
    throw new Exception( 'Missing existing WooCommerce category Carnes.' );
}

$root = $ensure_term( 'Productos de pato', 'pato', (int) $carnes->term_id );
$terms = array(
    'root'     => $root,
    'magret'   => $ensure_term( 'Magret de pato', 'magret-de-pato', (int) $root->term_id ),
    'foie'     => $ensure_term( 'Foie gras de pato', 'foie-gras-de-pato', (int) $root->term_id ),
    'confit'   => $ensure_term( 'Confit de pato', 'confit-de-pato', (int) $root->term_id ),
    'jamon'    => $ensure_term( 'Jamón de pato', 'jamon-de-pato', (int) $root->term_id ),
    'prepared' => $ensure_term( 'Patés, mousses y rillettes de pato', 'pate-mousse-rillettes-de-pato', (int) $root->term_id ),
    'fresh'    => $ensure_term( 'Pato fresco', 'pato-fresco', (int) $root->term_id ),
);
foreach ( $terms as $term ) {
    update_term_meta( (int) $term->term_id, '_emdo_duck_commerce_category', '1' );
    update_term_meta( (int) $term->term_id, 'display_type', 'products' );
}

$classify = static function ( int $id ) use ( $normalize ): ?string {
    $title = $normalize( (string) get_the_title( $id ) );
    $source = $normalize( rawurldecode( (string) get_post_meta( $id, '_emdo_source_url', true ) ) );
    $identity = trim( $title . ' ' . $source );

    $has_pato   = (bool) preg_match( '/\bpato\b/', $identity );
    $has_magret = (bool) preg_match( '/\bmagret(s)?\b/', $identity );
    $has_foie   = (bool) preg_match( '/\bfoie\b/', $identity );
    $has_oca    = (bool) preg_match( '/\boca\b/', $title );

    if ( ! $has_pato && ! $has_magret && ! ( $has_foie && ! $has_oca ) ) {
        return null;
    }
    if ( $has_oca && ! $has_pato && ! $has_magret ) {
        return null;
    }

    if ( preg_match( '/\bconfit|confitado|confitada|confitados|confitadas\b/', $title ) ) {
        return 'confit';
    }
    if ( $has_pato && preg_match( '/\bjamon\b/', $title ) ) {
        return 'jamon';
    }
    if ( preg_match( '/\b(pate|mousse|parfait|rillette|rillettes)\b/', $title ) ) {
        return 'prepared';
    }
    if ( $has_magret ) {
        return 'magret';
    }
    if ( $has_foie ) {
        return 'foie';
    }
    if ( preg_match( '/\b(fresco|fresca|frescos|frescas|carcasa|solomillo|muslo|manchon|molleja|mollejas|corazon|corazones|pato entero)\b/', $title ) ) {
        return 'fresh';
    }
    return 'root';
};

$duck = array();
$group_counts = array_fill_keys( array( 'root','fresh','magret','foie','confit','jamon','prepared' ), 0 );
$sample_urls = array();
$image_alt_updated = 0;
$uncategorized_removed = 0;
$default_cat = (int) get_option( 'default_product_cat', 0 );
$cluster_term_ids = array_values( array_unique( array_map( static fn( WP_Term $t ): int => (int) $t->term_id, $terms ) ) );

foreach ( $all_selectos as $id ) {
    $id = (int) $id;
    $group = $classify( $id );
    if ( null === $group ) { continue; }
    $duck[] = $id;
    $group_counts[ $group ]++;

    if ( ! isset( $sample_urls[ $group ] ) ) {
        $sample_urls[ $group ] = (string) get_permalink( $id );
    }

    if ( '' === (string) get_post_meta( $id, '_emdo_duck_commerce_categories_backup_20261001', true ) ) {
        update_post_meta( $id, '_emdo_duck_commerce_categories_backup_20261001', wp_json_encode( wp_get_post_terms( $id, 'product_cat', array( 'fields'=>'ids' ) ) ) );
    }

    $current = wp_get_post_terms( $id, 'product_cat', array( 'fields'=>'ids' ) );
    $current = is_wp_error( $current ) ? array() : array_values( array_unique( array_map( 'intval', $current ) ) );
    $keep = array_values( array_filter( $current, static function ( int $term_id ) use ( $cluster_term_ids, $default_cat ): bool {
        if ( in_array( $term_id, $cluster_term_ids, true ) ) { return false; }
        if ( $term_id === $default_cat ) { return false; }
        $term = get_term( $term_id, 'product_cat' );
        return $term instanceof WP_Term && 'sin-categorizar' !== $term->slug && 'uncategorized' !== $term->slug;
    } ) );
    if ( count( $current ) !== count( $keep ) ) { $uncategorized_removed++; }

    $assign = $keep;
    $assign[] = (int) $root->term_id;
    if ( 'root' !== $group && isset( $terms[ $group ] ) ) {
        $assign[] = (int) $terms[ $group ]->term_id;
    }
    $assign = array_values( array_unique( array_map( 'intval', $assign ) ) );
    $set = wp_set_object_terms( $id, $assign, 'product_cat', false );
    if ( is_wp_error( $set ) ) { throw new Exception( 'Category assignment failed for product ' . $id . ': ' . $set->get_error_message() ); }

    update_post_meta( $id, '_emdo_duck_commercial_cluster', '1' );
    update_post_meta( $id, '_emdo_duck_product_cluster', $group );
    update_post_meta( $id, '_emdo_duck_commerce_version', '20261001' );

    $seo_title = mdo_duck_commerce_product_title_for_id_20261001( $id );
    $seo_desc  = mdo_duck_commerce_product_description_for_id_20261001( $id );
    update_post_meta( $id, '_yoast_wpseo_title', $seo_title );
    update_post_meta( $id, '_yoast_wpseo_metadesc', $seo_desc );
    update_post_meta( $id, 'rank_math_title', $seo_title );
    update_post_meta( $id, 'rank_math_description', $seo_desc );
    delete_post_meta( $id, '_yoast_wpseo_meta-robots-noindex' );
    delete_post_meta( $id, 'rank_math_robots' );

    $product = wc_get_product( $id );
    if ( $product ) {
        $image_ids = array_values( array_unique( array_filter( array_merge(
            array( (int) $product->get_image_id() ),
            array_map( 'intval', $product->get_gallery_image_ids() )
        ) ) ) );
        foreach ( $image_ids as $index => $attachment_id ) {
            $alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
            $desired = $product->get_name() . ( 0 === $index ? ' - Selectos de Castilla' : ', imagen ' . ( $index + 1 ) . ' - Selectos de Castilla' );
            if ( '' === $alt || false !== stripos( $alt, 'imagen provisional' ) || $alt === $product->get_name() ) {
                update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $desired ) );
                $image_alt_updated++;
            }
        }
    }
    clean_post_cache( $id );
}

if ( count( $duck ) < 35 ) {
    throw new Exception( 'Duck catalog classification unexpectedly small: ' . count( $duck ) );
}

$guide_urls = array();
$guide_ids = get_posts( array(
    'post_type'=>'post',
    'post_status'=>'publish',
    'posts_per_page'=>100,
    'fields'=>'ids',
    'meta_key'=>'_emdo_blog_cluster',
    'meta_value'=>'duck',
) );
foreach ( $guide_ids as $guide_id ) {
    $key = (string) get_post_meta( (int) $guide_id, '_emdo_seo_landing_key', true );
    if ( '' !== $key ) { $guide_urls[ $key ] = (string) get_permalink( (int) $guide_id ); }
}
$guide = static function ( string $key, string $label ) use ( $guide_urls ): string {
    return isset( $guide_urls[ $key ] )
        ? '<a href="' . esc_url( $guide_urls[ $key ] ) . '">' . esc_html( $label ) . '</a>'
        : esc_html( $label );
};

$term_urls = array();
foreach ( $terms as $group => $term ) {
    $url = get_term_link( $term );
    if ( is_wp_error( $url ) ) { throw new Exception( 'Could not resolve product category URL for ' . $group ); }
    $term_urls[ $group ] = (string) $url;
}
$link_term = static function ( string $group, string $label ) use ( $term_urls ): string {
    return '<a href="' . esc_url( $term_urls[ $group ] ) . '">' . esc_html( $label ) . '</a>';
};

$descriptions = array(
    'root' => '<p>Compra productos de pato de Selectos de Castilla en un catálogo organizado por tipo de producto y forma de uso. Aquí reunimos cortes frescos, magret, foie gras, confit, jamón de pato, patés, mousses y rillettes para que puedas comparar formatos, conservación y disponibilidad sin mezclar elaboraciones diferentes.</p>'
        . '<p>Si buscas una pieza para cocinar desde cero, empieza por ' . $link_term( 'fresh', 'pato fresco y cortes' ) . ' o por ' . $link_term( 'magret', 'magret de pato' ) . '. Para productos listos para terminar o servir, explora ' . $link_term( 'confit', 'confit de pato' ) . ', ' . $link_term( 'jamon', 'jamón de pato' ) . ', ' . $link_term( 'foie', 'foie gras de pato' ) . ' y ' . $link_term( 'prepared', 'patés, mousses y rillettes' ) . '.</p>'
        . '<p>El catálogo comercial está conectado con nuestras guías para ayudarte antes y después de comprar: consulta la ' . $guide( 'duck-01', 'guía de carne de pato y cortes' ) . ', aprende ' . $guide( 'duck-03', 'qué es el magret y cómo cocinarlo' ) . ', compara ' . $guide( 'duck-02', 'formatos de foie gras' ) . ' o revisa ' . $guide( 'duck-04', 'cómo preparar y acompañar confit de pato' ) . '.</p>',
    'magret' => '<p>Selección de magret de pato de Selectos de Castilla. El magret es la pechuga del pato con su capa de piel y grasa, una pieza pensada para cocciones rápidas en sartén o plancha y para recetas donde interesa controlar con precisión el punto interior.</p>'
        . '<p>Antes de comprar puedes consultar ' . $guide( 'duck-03', 'qué es el magret de pato' ) . ', el método para ' . $guide( 'duck-09', 'cocinar magret en sartén' ) . ' y la guía de ' . $guide( 'duck-24', 'temperaturas y puntos de cocción' ) . '. Así puedes elegir el formato sabiendo cómo vas a cocinarlo.</p>',
    'foie' => '<p>Foie gras de pato de Selectos de Castilla en formatos que pueden incluir foie fresco, micuit, bloc, entier u otras presentaciones según disponibilidad. La categoría separa el foie gras de las mousses, patés y rillettes para que la intención de compra sea clara.</p>'
        . '<p>Si dudas entre formatos, revisa nuestra guía para ' . $guide( 'duck-02', 'elegir foie gras' ) . ', la comparación entre ' . $guide( 'duck-14', 'entier, bloc y bloc con trozos' ) . ' y las diferencias entre ' . $guide( 'duck-15', 'foie fresco, micuit y conserva' ) . '.</p>',
    'confit' => '<p>Confit de pato de Selectos de Castilla: muslos y otras piezas ya cocinadas lentamente en grasa, disponibles en distintos formatos según el producto. Es una opción especialmente práctica cuando buscas carne tierna que solo necesite calentarse y dorarse antes de servir.</p>'
        . '<p>Consulta ' . $guide( 'duck-28', 'cómo calentar confit en conserva o al vacío' ) . ', cómo conseguir ' . $guide( 'duck-10', 'una piel crujiente' ) . ' y qué ' . $guide( 'duck-29', 'guarniciones combinan con el confit' ) . '.</p>',
    'jamon' => '<p>Jamón de pato de Selectos de Castilla en pieza, loncheado o variantes ahumadas según disponibilidad. Es un curado pensado para aperitivos, tablas y platos donde interesa un sabor intenso en lonchas finas.</p>'
        . '<p>Para elegir y servirlo mejor, consulta la ' . $guide( 'duck-05', 'guía de jamón de pato' ) . ', las diferencias entre ' . $guide( 'duck-16', 'jamón curado y ahumado' ) . ' y cómo ' . $guide( 'duck-34', 'atemperarlo, cortarlo y presentarlo' ) . '.</p>',
    'prepared' => '<p>Patés, mousses, parfaits y rillettes de pato de Selectos de Castilla. Son elaboraciones diferentes en composición y textura: algunas son completamente lisas y untables, mientras que las rillettes conservan fibras de carne y los parfaits suelen tener una proporción elevada de foie.</p>'
        . '<p>Antes de elegir, compara ' . $guide( 'duck-06', 'foie, paté, mousse, parfait, bloc y micuit' ) . ' o revisa específicamente las ' . $guide( 'duck-41', 'diferencias entre paté, mousse y rillettes' ) . '.</p>',
    'fresh' => '<p>Pato fresco y cortes de pato de Selectos de Castilla para cocinar desde crudo. Según disponibilidad puedes encontrar piezas como muslo, solomillo, corazones, mollejas, carcasa o pato entero, cada una con tiempos y técnicas diferentes.</p>'
        . '<p>Consulta la ' . $guide( 'duck-07', 'guía de cortes de pato fresco' ) . ', cómo preparar ' . $guide( 'duck-48', 'pato entero al horno' ) . ' y las pautas para ' . $guide( 'duck-53', 'conservar, congelar y descongelar carne de pato' ) . '.</p>',
);

foreach ( $terms as $group => $term ) {
    $updated = wp_update_term( (int) $term->term_id, 'product_cat', array(
        'description' => $descriptions[ $group ],
        'parent'      => 'root' === $group ? (int) $carnes->term_id : (int) $root->term_id,
    ) );
    if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }

    $thumb_ids = get_posts( array(
        'post_type'=>'product',
        'post_status'=>'publish',
        'posts_per_page'=>20,
        'fields'=>'ids',
        'tax_query'=>array(
            array( 'taxonomy'=>'product_cat', 'field'=>'term_id', 'terms'=>array( (int) $term->term_id ), 'include_children'=>false ),
        ),
        'meta_query'=>array(
            array( 'key'=>'_emdo_supplier_id', 'value'=>$supplier_id, 'compare'=>'=' ),
            array( 'key'=>'_emdo_duck_commercial_cluster', 'value'=>'1', 'compare'=>'=' ),
        ),
    ) );
    foreach ( $thumb_ids as $thumb_product_id ) {
        $thumb = (int) get_post_thumbnail_id( (int) $thumb_product_id );
        if ( $thumb > 0 ) {
            update_term_meta( (int) $term->term_id, 'thumbnail_id', $thumb );
            break;
        }
    }
}

$blog_pato = get_term_by( 'slug', 'pato', 'category' );
if ( $blog_pato instanceof WP_Term ) {
    $desc = (string) term_description( $blog_pato );
    if ( false === strpos( $desc, $term_urls['root'] ) ) {
        $desc .= '<h2>Comprar productos de pato</h2><p>Las guías de esta sección están conectadas con el catálogo de Selectos de Castilla. Puedes ' .
            '<a href="' . esc_url( $term_urls['root'] ) . '">ver todos los productos de pato</a>, o ir directamente a ' .
            '<a href="' . esc_url( $term_urls['magret'] ) . '">magret</a>, ' .
            '<a href="' . esc_url( $term_urls['foie'] ) . '">foie gras</a>, ' .
            '<a href="' . esc_url( $term_urls['confit'] ) . '">confit</a> y ' .
            '<a href="' . esc_url( $term_urls['jamon'] ) . '">jamón de pato</a>.</p>';
        $r = wp_update_term( (int) $blog_pato->term_id, 'category', array( 'description'=>$desc ) );
        if ( is_wp_error( $r ) ) { throw new Exception( $r->get_error_message() ); }
    }
    update_term_meta( (int) $blog_pato->term_id, '_emdo_duck_commerce_bridge', '20261001' );
}

$carnes_desc = (string) term_description( $carnes );
if ( false === strpos( $carnes_desc, $term_urls['root'] ) ) {
    $carnes_desc .= '<p>También puedes explorar la selección específica de <a href="' . esc_url( $term_urls['root'] ) . '">productos de pato</a>, con magret, foie gras, confit, jamón de pato y cortes frescos de Selectos de Castilla.</p>';
    $r = wp_update_term( (int) $carnes->term_id, 'product_cat', array( 'description'=>$carnes_desc ) );
    if ( is_wp_error( $r ) ) { throw new Exception( $r->get_error_message() ); }
}

$uncategorized_after = 0;
$root_missing = 0;
$noindex_meta = 0;
$title_lengths = array();
$description_lengths = array();
$rows = array();

foreach ( $duck as $id ) {
    $id = (int) $id;
    $slugs = wp_get_post_terms( $id, 'product_cat', array( 'fields'=>'slugs' ) );
    $slugs = is_wp_error( $slugs ) ? array() : array_values( $slugs );
    if ( in_array( 'sin-categorizar', $slugs, true ) || in_array( 'uncategorized', $slugs, true ) ) { $uncategorized_after++; }
    if ( ! in_array( 'pato', $slugs, true ) ) { $root_missing++; }
    if ( '' !== (string) get_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', true ) ) { $noindex_meta++; }
    $seo_title = mdo_duck_commerce_product_title_for_id_20261001( $id );
    $seo_desc = mdo_duck_commerce_product_description_for_id_20261001( $id );
    $title_lengths[] = mdo_duck_commerce_strlen_20261001( $seo_title );
    $description_lengths[] = mdo_duck_commerce_strlen_20261001( $seo_desc );
    $rows[] = array(
        'id'=>$id,
        'title'=>get_the_title($id),
        'url'=>get_permalink($id),
        'group'=>(string)get_post_meta($id,'_emdo_duck_product_cluster',true),
        'categories'=>$slugs,
        'seo_title'=>$seo_title,
        'seo_description'=>$seo_desc,
        'stock'=>wc_get_product($id) ? wc_get_product($id)->get_stock_status() : null,
    );
}

clean_term_cache( array_map( static fn( WP_Term $t ): int => (int) $t->term_id, $terms ), 'product_cat' );
flush_rewrite_rules( false );

echo wp_json_encode( array(
    'batch'=>'20261001-duck-commerce-seo-cluster',
    'supplier_id'=>$supplier_id,
    'selectos_published'=>count($all_selectos),
    'duck_products'=>count($duck),
    'group_counts'=>$group_counts,
    'uncategorized_removed'=>$uncategorized_removed,
    'uncategorized_after'=>$uncategorized_after,
    'root_missing'=>$root_missing,
    'noindex_meta'=>$noindex_meta,
    'image_alt_updated'=>$image_alt_updated,
    'seo'=>array(
        'title_min'=>min($title_lengths),
        'title_max'=>max($title_lengths),
        'description_min'=>min($description_lengths),
        'description_max'=>max($description_lengths),
    ),
    'category_urls'=>$term_urls,
    'sample_urls'=>$sample_urls,
    'rows'=>$rows,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
