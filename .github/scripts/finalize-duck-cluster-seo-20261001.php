<?php
/**
 * Final production pass for the Selectos de Castilla + editorial duck cluster.
 * Idempotent and guarded: taxonomy, cluster metadata, category copy, producer
 * About copy and removal of the artificial legacy depth block.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! taxonomy_exists( 'product_cat' ) || ! class_exists( 'MDO_Database' ) || ! function_exists( 'wc_get_product' ) ) {
    throw new Exception( 'Required WooCommerce/EMDO components unavailable.' );
}

global $wpdb;
$supplier_id = 5;
$source_table = MDO_Database::table( 'source_products' );
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id,wc_product_id,title,status,source_url,source_stock_status FROM {$source_table} WHERE supplier_id=%d ORDER BY id ASC",
        $supplier_id
    ),
    ARRAY_A
);
if ( count( $rows ) < 150 ) {
    throw new Exception( 'Selectos source catalog unexpectedly small: ' . count( $rows ) );
}

$normalize = static function ( string $value ): string {
    $value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = remove_accents( strtolower( $value ) );
    return trim( preg_replace( '/\s+/u', ' ', $value ) );
};

$get_term = static function ( string $slug ): WP_Term {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) {
        throw new Exception( 'Missing product category: ' . $slug );
    }
    return $term;
};

$required_slugs = array(
    'carnes','conservas','embutidos-y-curados','packs-y-lotes','pato',
    'confit-de-pato','foie-gras-de-pato','jamon-de-pato','magret-de-pato',
    'pate-mousse-rillettes-de-pato','pato-fresco','pescados-mariscos',
    'foie-pates-untables'
);
$terms = array();
foreach ( $required_slugs as $slug ) {
    $terms[ $slug ] = $get_term( $slug );
}

/**
 * Mirror the intended Selectos global taxonomy, with the explicit goose/codorniz
 * exclusions that keep the Pato cluster semantically pure.
 */
$classify = static function ( string $url, string $title ) use ( $normalize ): array {
    $url = strtolower( rawurldecode( $url ) );
    $t   = $normalize( $title );
    $is_pack = str_starts_with( $t, 'lote ' )
        || str_starts_with( $t, 'cesta ' )
        || str_starts_with( $t, 'estuche ' )
        || str_contains( $t, 'pie de pates' );

    if ( str_contains( $url, '/detalles-bodas-bautizos-y-comuniones/' )
        || str_contains( $url, '/estuches-y-regalos-de-empresa/' )
        || $is_pack ) {
        return array( 'packs-y-lotes' );
    }
    if ( str_contains( $url, '/jamon-de-pato/' ) ) {
        return array( 'embutidos-y-curados', 'pato', 'jamon-de-pato' );
    }
    if ( str_contains( $url, '/trucha/' ) ) {
        return array( 'pescados-mariscos' );
    }
    if ( str_contains( $url, '/pato-fresco/' ) ) {
        return array( 'pato', 'pato-fresco' );
    }
    if ( str_contains( $url, '/magret/' ) ) {
        return array( 'pato', 'magret-de-pato' );
    }
    if ( str_contains( $url, '/foie-gras/' ) ) {
        if ( str_contains( $t, 'oca' ) && ! str_contains( $t, 'pato' ) ) {
            return array( 'foie-pates-untables' );
        }
        return array(
            'foie-pates-untables',
            'pato',
            str_contains( $t, 'mousse' ) ? 'pate-mousse-rillettes-de-pato' : 'foie-gras-de-pato',
        );
    }
    if ( str_contains( $url, '/pates/' ) ) {
        if ( str_contains( $t, 'trucha' ) ) {
            return array( 'pescados-mariscos', 'foie-pates-untables' );
        }
        $out = array( 'foie-pates-untables' );
        $other_species = str_contains( $t, 'avestruz' )
            || str_contains( $t, 'cochinillo' )
            || str_contains( $t, 'oca' )
            || ( str_contains( $t, 'lechazo' ) && ! str_contains( $t, 'pato' ) );
        if ( ! $other_species ) {
            $out[] = 'pato';
            $out[] = str_contains( $t, 'bloc de foie gras' )
                ? 'foie-gras-de-pato'
                : 'pate-mousse-rillettes-de-pato';
        }
        return $out;
    }
    if ( str_contains( $url, '/confit/' ) ) {
        if ( str_contains( $t, 'cochinillo' ) || str_contains( $t, 'codorniz' ) ) {
            return array( 'carnes' );
        }
        if ( str_contains( $t, 'rillettes' ) ) {
            return str_contains( $t, 'lechazo' ) && ! str_contains( $t, 'pato' )
                ? array( 'foie-pates-untables' )
                : array( 'foie-pates-untables', 'pato', 'pate-mousse-rillettes-de-pato' );
        }
        if ( str_contains( $t, 'grasa refinada' ) ) {
            return array( 'pato' );
        }
        return array( 'pato', 'confit-de-pato' );
    }
    if ( str_contains( $url, '/los-manjares-de-la-tierra/' ) && str_contains( $t, 'cochinillo' ) ) {
        return array( 'carnes' );
    }
    if ( str_contains( $url, '/complementos/' ) ) {
        return array( 'conservas' );
    }
    if ( str_contains( $url, '/productos/' ) ) {
        if ( str_contains( $t, 'grasa refinada de pato' ) ) {
            return array( 'pato' );
        }
        if ( str_contains( $t, 'manchon' ) && str_contains( $t, 'confit' ) ) {
            return array( 'pato', 'confit-de-pato' );
        }
        if ( str_starts_with( $t, 'cesta ' ) || str_starts_with( $t, 'estuche ' ) ) {
            return array( 'packs-y-lotes' );
        }
    }
    return array();
};

$group_from_slugs = static function ( array $slugs ): string {
    $map = array(
        'magret-de-pato'                => 'magret',
        'confit-de-pato'                => 'confit',
        'jamon-de-pato'                 => 'jamon',
        'pato-fresco'                   => 'fresh',
        'foie-gras-de-pato'             => 'foie',
        'pate-mousse-rillettes-de-pato' => 'prepared',
    );
    foreach ( $map as $slug => $group ) {
        if ( in_array( $slug, $slugs, true ) ) { return $group; }
    }
    return 'root';
};

$word_count = static function ( string $html ): int {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
    if ( '' === $text ) { return 0; }
    preg_match_all( '/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u', $text, $matches );
    return count( $matches[0] );
};

/* ---------- Preflight editorial cleanup without touching the database. ---------- */
$duck_posts = get_posts( array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 100,
    'orderby'        => 'ID',
    'order'          => 'ASC',
    'meta_key'       => '_emdo_blog_cluster',
    'meta_value'     => 'duck',
) );
if ( 60 !== count( $duck_posts ) ) {
    throw new Exception( 'Expected 60 duck posts, found ' . count( $duck_posts ) );
}

$filler_marker = '<h2>Detalles prácticos para completar la guía</h2>';
$depth_marker  = '<!-- emdo-duck-depth-patch-20260928:';
$clean_plan = array();
$preflight_min_words = PHP_INT_MAX;
$preflight_min_h2 = PHP_INT_MAX;
$filler_before = 0;

foreach ( $duck_posts as $post ) {
    if ( ! $post instanceof WP_Post ) { continue; }
    $content = (string) $post->post_content;
    $cleaned = $content;
    $start = strpos( $content, $filler_marker );
    if ( false !== $start ) {
        $filler_before++;
        $end = strpos( $content, $depth_marker, $start + strlen( $filler_marker ) );
        $cleaned = false === $end
            ? rtrim( substr( $content, 0, $start ) )
            : rtrim( substr( $content, 0, $start ) ) . "\n\n" . ltrim( substr( $content, $end ) );
    }
    $words = $word_count( $cleaned );
    $h2    = preg_match_all( '/<h2\b/i', $cleaned );
    $preflight_min_words = min( $preflight_min_words, $words );
    $preflight_min_h2    = min( $preflight_min_h2, (int) $h2 );
    $clean_plan[] = array(
        'id'      => (int) $post->ID,
        'before'  => $content,
        'after'   => $cleaned,
        'changed' => $content !== $cleaned,
        'words'   => $words,
        'h2'      => (int) $h2,
    );
}
if ( $filler_before < 50 ) {
    throw new Exception( 'Unexpected filler footprint before cleanup: ' . $filler_before );
}
if ( $preflight_min_words < 650 || $preflight_min_h2 < 6 ) {
    throw new Exception(
        'Editorial cleanup preflight would make an article too thin: min_words=' .
        $preflight_min_words . ' min_h2=' . $preflight_min_h2
    );
}

/* ---------- Canonicalize current Selectos taxonomy and duck metadata. ---------- */
$active = 0;
$unmapped = array();
$duck_ids = array();
$duck_group_counts = array_fill_keys( array( 'root','fresh','magret','foie','confit','jamon','prepared' ), 0 );
$taxonomy_changes = 0;
$stale_meta_cleared = 0;
$cluster_meta_repaired = 0;
$non_duck_removed = array();
$sample_repaired_url = '';
$sample_repaired_title = '';
$sample_non_duck_url = '';

foreach ( $rows as $row ) {
    if ( 'active' !== (string) $row['status'] ) { continue; }
    $active++;
    $wc_id = (int) $row['wc_product_id'];
    if ( $wc_id <= 0 || ! wc_get_product( $wc_id ) ) {
        throw new Exception( 'Missing Woo product for active Selectos source row ' . (int) $row['id'] );
    }

    $expected = $classify( (string) $row['source_url'], (string) $row['title'] );
    if ( ! $expected ) {
        $unmapped[] = array( 'source_id'=>(int)$row['id'], 'wc_product_id'=>$wc_id, 'title'=>(string)$row['title'] );
        continue;
    }
    $expected = array_values( array_unique( $expected ) );

    $current = wp_get_post_terms( $wc_id, 'product_cat', array( 'fields'=>'slugs' ) );
    $current = is_wp_error( $current ) ? array() : array_values( $current );
    $a = $current; $b = $expected; sort( $a ); sort( $b );

    if ( '' === (string) get_post_meta( $wc_id, '_emdo_duck_final_categories_backup_20261001', true ) ) {
        update_post_meta( $wc_id, '_emdo_duck_final_categories_backup_20261001', wp_json_encode( $current ) );
    }

    if ( $a !== $b ) {
        $ids = array();
        foreach ( $expected as $slug ) {
            if ( ! isset( $terms[ $slug ] ) ) { $terms[ $slug ] = $get_term( $slug ); }
            $ids[] = (int) $terms[ $slug ]->term_id;
        }
        $set = wp_set_object_terms( $wc_id, $ids, 'product_cat', false );
        if ( is_wp_error( $set ) ) {
            throw new Exception( 'Taxonomy update failed for ' . $wc_id . ': ' . $set->get_error_message() );
        }
        $taxonomy_changes++;
        if ( in_array( 'pato', $current, true ) && ! in_array( 'pato', $expected, true ) ) {
            $non_duck_removed[] = array(
                'id'=>$wc_id,
                'title'=>(string)$row['title'],
                'before'=>$current,
                'after'=>$expected,
            );
            if ( '' === $sample_non_duck_url && 'publish' === get_post_status( $wc_id ) ) {
                $sample_non_duck_url = (string) get_permalink( $wc_id );
            }
        }
    }

    $is_duck = in_array( 'pato', $expected, true );
    $old_cluster = (string) get_post_meta( $wc_id, '_emdo_duck_commercial_cluster', true );
    $old_group   = (string) get_post_meta( $wc_id, '_emdo_duck_product_cluster', true );

    if ( $is_duck ) {
        $group = $group_from_slugs( $expected );
        $duck_ids[] = $wc_id;
        $duck_group_counts[ $group ]++;
        if ( '1' !== $old_cluster || $group !== $old_group ) {
            $cluster_meta_repaired++;
            if ( '' === $sample_repaired_url && 'publish' === get_post_status( $wc_id ) && 'instock' === wc_get_product( $wc_id )->get_stock_status() ) {
                $sample_repaired_url = (string) get_permalink( $wc_id );
                $sample_repaired_title = (string) get_the_title( $wc_id );
            }
        }
        update_post_meta( $wc_id, '_emdo_duck_commercial_cluster', '1' );
        update_post_meta( $wc_id, '_emdo_duck_product_cluster', $group );
        update_post_meta( $wc_id, '_emdo_duck_commerce_version', '20261001-final' );

        if ( function_exists( 'mdo_duck_commerce_product_title_for_id_20261001' ) ) {
            $seo_title = mdo_duck_commerce_product_title_for_id_20261001( $wc_id );
            $seo_desc  = mdo_duck_commerce_product_description_for_id_20261001( $wc_id );
            update_post_meta( $wc_id, '_yoast_wpseo_title', $seo_title );
            update_post_meta( $wc_id, '_yoast_wpseo_metadesc', $seo_desc );
            update_post_meta( $wc_id, 'rank_math_title', $seo_title );
            update_post_meta( $wc_id, 'rank_math_description', $seo_desc );
        }
        delete_post_meta( $wc_id, '_yoast_wpseo_meta-robots-noindex' );
    } else {
        if ( '1' === $old_cluster || '' !== $old_group ) { $stale_meta_cleared++; }
        delete_post_meta( $wc_id, '_emdo_duck_commercial_cluster' );
        delete_post_meta( $wc_id, '_emdo_duck_product_cluster' );
        delete_post_meta( $wc_id, '_emdo_duck_commerce_version' );
    }
    clean_post_cache( $wc_id );
}

if ( $unmapped ) {
    throw new Exception( 'Unmapped active Selectos products: ' . wp_json_encode( $unmapped, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) );
}
if ( $active < 100 || count( $duck_ids ) < 45 ) {
    throw new Exception( 'Unexpected final Selectos/duck counts: active=' . $active . ' duck=' . count( $duck_ids ) );
}

/* ---------- Strengthen commercial category copy after the global taxonomy change. ---------- */
$root_url = get_term_link( $terms['pato'] );
$foie_url = get_term_link( $terms['foie-gras-de-pato'] );
$prep_url = get_term_link( $terms['pate-mousse-rillettes-de-pato'] );
$global_url = get_term_link( $terms['foie-pates-untables'] );
foreach ( array( $root_url,$foie_url,$prep_url,$global_url ) as $url ) {
    if ( is_wp_error( $url ) ) { throw new Exception( 'Could not resolve a product category URL.' ); }
}
$store_url = home_url( '/tienda/selectos-de-castilla/' );
$blog_url  = home_url( '/category/carnes/pato/' );
$compare_url = home_url( '/foie-gras-pate-mousse-parfait-bloc-micuit-diferencias/' );
$prepared_compare_url = home_url( '/pate-mousse-rillettes-diferencias/' );

if ( '' === (string) get_term_meta( $terms['foie-pates-untables']->term_id, '_emdo_pre_duck_final_description_20261001', true ) ) {
    update_term_meta( $terms['foie-pates-untables']->term_id, '_emdo_pre_duck_final_description_20261001', (string) $terms['foie-pates-untables']->description );
}
$global_desc = '<p>En esta categoría reunimos foie gras, patés, mousses, parfaits, rillettes y otros untables de distintos productores. No todos significan lo mismo: cambian la proporción de hígado o carne, la textura, el tratamiento térmico, la conservación y la forma de servirlos.</p>'
    . '<p>Si buscas elaboraciones de pato de Selectos de Castilla, puedes ir directamente a <a href="' . esc_url( $foie_url ) . '">foie gras de pato</a> o a <a href="' . esc_url( $prep_url ) . '">patés, mousses y rillettes de pato</a>. Para ver carne, magret, confit y jamón junto al resto del catálogo, consulta <a href="' . esc_url( $root_url ) . '">todos los productos de pato</a>.</p>'
    . '<p>Antes de elegir formato, consulta nuestra guía sobre las <a href="' . esc_url( $compare_url ) . '">diferencias entre foie gras, paté, mousse, parfait, bloc y micuit</a> y la comparativa de <a href="' . esc_url( $prepared_compare_url ) . '">paté, mousse y rillettes</a>.</p>';
$updated = wp_update_term( $terms['foie-pates-untables']->term_id, 'product_cat', array( 'description'=>$global_desc ) );
if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }

$root_desc = (string) term_description( $terms['pato'] );
if ( false === strpos( $root_desc, '/tienda/selectos-de-castilla' ) ) {
    $root_desc .= '<p>Estos productos proceden de <a href="' . esc_url( $store_url ) . '">Selectos de Castilla</a>. Desde la ficha del productor puedes conocer su especialización y recorrer el catálogo completo; para contenidos de cocina y elección, visita también nuestras <a href="' . esc_url( $blog_url ) . '">guías sobre pato</a>.</p>';
    $updated = wp_update_term( $terms['pato']->term_id, 'product_cat', array( 'description'=>$root_desc ) );
    if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }
}

foreach ( array(
    'foie-gras-de-pato' => 'Si quieres comparar estas elaboraciones con patés, mousses, parfaits y rillettes de otros tipos, visita también <a href="' . esc_url( $global_url ) . '">Foie, patés y untables</a>.',
    'pate-mousse-rillettes-de-pato' => 'Para ampliar la comparación a foie gras y otros untables, visita también <a href="' . esc_url( $global_url ) . '">Foie, patés y untables</a>.',
) as $slug => $sentence ) {
    $term = $terms[ $slug ];
    $desc = (string) term_description( $term );
    if ( false === strpos( $desc, (string) $global_url ) ) {
        $desc .= '<p>' . $sentence . '</p>';
        $updated = wp_update_term( $term->term_id, 'product_cat', array( 'description'=>$desc ) );
        if ( is_wp_error( $updated ) ) { throw new Exception( $updated->get_error_message() ); }
    }
}

/* ---------- Turn the producer into a topical authority node. ---------- */
$supplier = class_exists( 'MDO_Supplier_Repository' ) ? MDO_Supplier_Repository::find( $supplier_id ) : null;
if ( ! is_array( $supplier ) || empty( $supplier['vendor_user_id'] ) ) {
    throw new Exception( 'Selectos supplier/vendor mapping unavailable.' );
}
$vendor_id = (int) $supplier['vendor_user_id'];
$settings  = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
$settings  = is_array( $settings ) ? $settings : array();
$store_name = (string) ( $settings['store_name'] ?? '' );
if ( '' !== $store_name && false === stripos( remove_accents( $store_name ), 'Selectos de Castilla' ) ) {
    throw new Exception( 'Unexpected supplier store name: ' . $store_name );
}

$about = '<p><strong>Selectos de Castilla</strong> es una empresa familiar creada en 1989 en Villamartín de Campos, Palencia, especializada en productos derivados del pato. Su proyecto une la tradición gastronómica de una familia hispano-francesa con la cría y elaboración en Tierra de Campos.</p>'
    . '<p>La casa trabaja con su Pato de Villamartín, criado en semilibertad en amplias zonas de cereal. A partir de esta materia prima elabora distintas familias de producto, desde piezas frescas hasta curados y elaboraciones listas para servir.</p>'
    . '<p>En El Mercado de Origen puedes recorrer su selección de <a href="' . esc_url( $root_url ) . '">productos de pato</a>, incluyendo <a href="' . esc_url( $foie_url ) . '">foie gras de pato</a>, <a href="' . esc_url( get_term_link( $terms['confit-de-pato'] ) ) . '">confit de pato</a>, <a href="' . esc_url( get_term_link( $terms['jamon-de-pato'] ) ) . '">jamón de pato</a>, <a href="' . esc_url( get_term_link( $terms['magret-de-pato'] ) ) . '">magret</a> y cortes de pato fresco.</p>'
    . '<p>Para comparar foie, patés, mousses, parfaits y rillettes más allá de una única elaboración, consulta también la categoría <a href="' . esc_url( $global_url ) . '">Foie, patés y untables</a>. Si quieres entender qué formato elegir, cómo conservarlo o cómo cocinar cada corte, el <a href="' . esc_url( $blog_url ) . '">clúster de guías sobre pato</a> conecta el catálogo con contenidos específicos.</p>'
    . '<p>Esta estructura permite pasar del productor al producto y del producto a la guía correspondiente sin mezclar intenciones: la tienda resuelve la compra y las guías explican cortes, formatos, cocción, servicio, conservación y acompañamientos.</p>';

if ( '' === (string) get_user_meta( $vendor_id, '_emdo_selectos_about_backup_20261001', true ) ) {
    update_user_meta( $vendor_id, '_emdo_selectos_about_backup_20261001', wp_json_encode( array(
        'primary'=>(string)get_user_meta($vendor_id,'_store_description',true),
        'profile'=>(string)($settings['shop_description']??''),
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) );
}
update_user_meta( $vendor_id, '_store_description', $about );
$settings['shop_description'] = $about;
update_user_meta( $vendor_id, 'wcfmmp_profile_settings', $settings );
clean_user_cache( $vendor_id );

/* ---------- Apply the preflighted editorial cleanup. ---------- */
$filler_removed = 0;
foreach ( $clean_plan as $item ) {
    if ( ! $item['changed'] ) { continue; }
    $id = (int) $item['id'];
    if ( '' === (string) get_post_meta( $id, '_emdo_duck_pre_final_cleanup_20261001', true ) ) {
        update_post_meta( $id, '_emdo_duck_pre_final_cleanup_20261001', (string) $item['before'] );
    }
    $result = wp_update_post( wp_slash( array( 'ID'=>$id, 'post_content'=>(string)$item['after'] ) ), true );
    if ( is_wp_error( $result ) ) { throw new Exception( 'Failed cleaning duck article ' . $id . ': ' . $result->get_error_message() ); }
    update_post_meta( $id, '_emdo_duck_content_rewrite', '20261001-final-clean' );
    clean_post_cache( $id );
    $filler_removed++;
}

/* ---------- Final internal verification. ---------- */
$taxonomy_mismatches = array();
$duck_meta_missing = array();
$stale_duck_meta = array();
$non_duck_species_in_pato = array();
$category_counts = array();
$stock_counts = array();
$title_lengths = array();
$desc_lengths = array();

foreach ( $rows as $row ) {
    if ( 'active' !== (string)$row['status'] ) { continue; }
    $wc_id = (int)$row['wc_product_id'];
    $expected = $classify( (string)$row['source_url'], (string)$row['title'] );
    $actual = wp_get_post_terms( $wc_id, 'product_cat', array( 'fields'=>'slugs' ) );
    $actual = is_wp_error( $actual ) ? array() : array_values( $actual );
    $a=$actual; $b=$expected; sort($a); sort($b);
    if ( $a !== $b ) {
        $taxonomy_mismatches[] = array( 'id'=>$wc_id, 'title'=>(string)$row['title'], 'expected'=>$b, 'actual'=>$a );
    }
    $is_duck = in_array( 'pato', $expected, true );
    $cluster = (string)get_post_meta($wc_id,'_emdo_duck_commercial_cluster',true);
    $group = (string)get_post_meta($wc_id,'_emdo_duck_product_cluster',true);
    if ( $is_duck ) {
        if ( '1' !== $cluster || '' === $group ) {
            $duck_meta_missing[] = array( 'id'=>$wc_id, 'title'=>(string)$row['title'], 'cluster'=>$cluster, 'group'=>$group );
        }
        foreach ( $actual as $slug ) {
            if ( str_contains($slug,'pato') || 'foie-pates-untables' === $slug ) {
                $category_counts[$slug]=($category_counts[$slug]??0)+1;
            }
        }
        $product=wc_get_product($wc_id);
        $stock=$product?(string)$product->get_stock_status():'';
        $stock_counts[$stock]=($stock_counts[$stock]??0)+1;
        if ( function_exists( 'mdo_duck_commerce_product_title_for_id_20261001' ) ) {
            $title_lengths[] = mdo_duck_commerce_strlen_20261001( mdo_duck_commerce_product_title_for_id_20261001( $wc_id ) );
            $desc_lengths[]  = mdo_duck_commerce_strlen_20261001( mdo_duck_commerce_product_description_for_id_20261001( $wc_id ) );
        }
        $nt=$normalize((string)$row['title']);
        if ( ( str_contains($nt,'codorniz') || str_contains($nt,'cochinillo') || (str_contains($nt,'oca') && !str_contains($nt,'pato')) ) ) {
            $non_duck_species_in_pato[] = array('id'=>$wc_id,'title'=>(string)$row['title'],'categories'=>$actual);
        }
    } elseif ( '1' === $cluster || '' !== $group ) {
        $stale_duck_meta[] = array( 'id'=>$wc_id, 'title'=>(string)$row['title'], 'cluster'=>$cluster, 'group'=>$group, 'categories'=>$actual );
    }
}
ksort($category_counts); ksort($stock_counts);

$filler_after=0;
$repeated_after=0;
$after_min_words=PHP_INT_MAX;
$after_max_words=0;
$after_sum_words=0;
$patterns=array(
    'Antes de aplicar una regla fija, conviene mirar el formato concreto que tenemos delante.',
    'Este punto merece atención porque suele ser el que más cambia el resultado final.',
    'Es una diferencia pequeña sobre el papel, pero muy visible cuando el producto llega al plato.',
    'Aquí conviene separar lo que pertenece al producto de lo que depende de la técnica.',
    'Más que memorizar una receta única, interesa comprender las variables que de verdad cambian el resultado',
    'En esta guía nos centramos en la intención concreta de búsqueda',
);
foreach ( $duck_posts as $post ) {
    $content=(string)get_post_field('post_content',$post->ID);
    if(false!==strpos($content,$filler_marker)) $filler_after++;
    foreach($patterns as $pattern) $repeated_after+=substr_count($content,$pattern);
    $wc=$word_count($content);
    $after_min_words=min($after_min_words,$wc);
    $after_max_words=max($after_max_words,$wc);
    $after_sum_words+=$wc;
}

$vendor_primary=(string)get_user_meta($vendor_id,'_store_description',true);
$vendor_settings=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
$vendor_profile=is_array($vendor_settings)?(string)($vendor_settings['shop_description']??''):'';
$global_term=get_term($terms['foie-pates-untables']->term_id,'product_cat');
$global_chars=$global_term instanceof WP_Term ? strlen(wp_strip_all_tags((string)$global_term->description)) : 0;

if ( $taxonomy_mismatches || $duck_meta_missing || $stale_duck_meta || $non_duck_species_in_pato ) {
    throw new Exception( 'Duck taxonomy/meta verification failed: ' . wp_json_encode( compact(
        'taxonomy_mismatches','duck_meta_missing','stale_duck_meta','non_duck_species_in_pato'
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) );
}
if ( 0 !== $filler_after || 0 !== $repeated_after || $after_min_words < 650 ) {
    throw new Exception( "Duck editorial cleanup failed: filler={$filler_after} repeated={$repeated_after} min_words={$after_min_words}" );
}
if ( $vendor_primary !== $about || $vendor_profile !== $about || $global_chars < 500 ) {
    throw new Exception( 'Producer/global-category content verification failed.' );
}

flush_rewrite_rules( false );
if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }

echo wp_json_encode( array(
    'batch'=>'20261001-duck-cluster-final',
    'source_rows'=>count($rows),
    'active_selectos'=>$active,
    'true_duck_products'=>count($duck_ids),
    'taxonomy_changes'=>$taxonomy_changes,
    'cluster_meta_repaired'=>$cluster_meta_repaired,
    'stale_meta_cleared'=>$stale_meta_cleared,
    'removed_non_duck_count'=>count($non_duck_removed),
    'removed_non_duck'=>$non_duck_removed,
    'group_counts'=>$duck_group_counts,
    'category_counts'=>$category_counts,
    'stock_counts'=>$stock_counts,
    'sample_repaired_url'=>$sample_repaired_url,
    'sample_repaired_title'=>$sample_repaired_title,
    'sample_non_duck_url'=>$sample_non_duck_url,
    'commercial_root_url'=>(string)$root_url,
    'global_foie_url'=>(string)$global_url,
    'producer_url'=>untrailingslashit($store_url),
    'producer_about_url'=>untrailingslashit($store_url) . '/acercade/',
    'producer_description_chars'=>strlen(wp_strip_all_tags($about)),
    'global_foie_description_chars'=>$global_chars,
    'blog'=>array(
        'count'=>count($duck_posts),
        'filler_before'=>$filler_before,
        'filler_removed'=>$filler_removed,
        'filler_after'=>$filler_after,
        'repeated_patterns_after'=>$repeated_after,
        'min_words'=>$after_min_words,
        'max_words'=>$after_max_words,
        'avg_words'=>round($after_sum_words/count($duck_posts),1),
        'hub_url'=>$blog_url,
    ),
    'seo'=>array(
        'product_title_min'=>$title_lengths?min($title_lengths):null,
        'product_title_max'=>$title_lengths?max($title_lengths):null,
        'product_description_min'=>$desc_lengths?min($desc_lengths):null,
        'product_description_max'=>$desc_lengths?max($desc_lengths):null,
    ),
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT ) . PHP_EOL;
