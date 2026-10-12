<?php
/**
 * One-time targeted repair of the two Montjam D.O.P. Jabugo products.
 * Run only through WP-CLI against the verified production site URL.
 * No prices, variations, descriptions, shipping, plugin logic or other products.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "MONTJAM_DOP_ABORT: WooCommerce WP-CLI required\n" );
    exit( 2 );
}
function emdo_montjam_dop_fail_20261012( $message, $code = 3 ) {
    fwrite( STDERR, 'MONTJAM_DOP_ABORT: ' . $message . "\n" );
    exit( $code );
}
$site = untrailingslashit( strtolower( (string) get_option( 'siteurl' ) ) );
if ( ! in_array( $site, array( 'https://www.elmercadodeorigen.com', 'https://elmercadodeorigen.com', 'http://www.elmercadodeorigen.com', 'http://elmercadodeorigen.com' ), true ) ) {
    emdo_montjam_dop_fail_20261012( 'Not the verified production installation: ' . $site );
}
$vendor = get_user_by( 'login', 'montjam' );
if ( ! $vendor instanceof WP_User || ! taxonomy_exists( 'pa_productor' ) ) {
    emdo_montjam_dop_fail_20261012( 'Montjam vendor/producer taxonomy missing' );
}
$definitions = array(
    'pa_con-dop' => array( 'attribute' => 'con-dop', 'slug' => 'si', 'label' => 'Sí' ),
    'pa_dop'     => array( 'attribute' => 'dop', 'slug' => 'jabugo', 'label' => 'Jabugo' ),
);
foreach ( $definitions as $taxonomy => $value ) {
    if ( ! taxonomy_exists( $taxonomy ) || ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) || ! wc_attribute_taxonomy_id_by_name( $value['attribute'] ) ) {
        emdo_montjam_dop_fail_20261012( 'Global taxonomy absent: ' . $taxonomy );
    }
}
global $wpdb;
$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT ID, post_title, post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_title LIKE %s",
    'product', 'publish', '%Montjam%'
) );
$found = array();
foreach ( (array) $rows as $post ) {
    $normalized = sanitize_title( $post->post_title );
    if ( false === strpos( $normalized, 'jabugo' ) || false === strpos( $normalized, 'montjam' )
        || ! preg_match( '/(?:^|-)d-o-p(?:-|$)|(?:^|-)dop(?:-|$)/', $normalized ) ) {
        continue;
    }
    $type = false !== strpos( $normalized, 'paleta' ) ? 'paleta' : ( false !== strpos( $normalized, 'jamon' ) ? 'jamon' : '' );
    if ( ! $type || isset( $found[ $type ] ) ) {
        emdo_montjam_dop_fail_20261012( 'Duplicate/unexpected Montjam DOP product name: ' . $post->post_title );
    }
    $id = (int) $post->ID;
    $p = wc_get_product( $id );
    $producer = wp_get_object_terms( $id, 'pa_productor', array( 'fields' => 'slugs' ) );
    $categories = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
    if ( ! $p || ! $p->is_type( 'variable' ) || $p->get_status() !== 'publish'
        || (int) $post->post_author !== (int) $vendor->ID
        || is_wp_error( $producer ) || ! in_array( 'montjam', $producer, true )
        || is_wp_error( $categories ) || ! in_array( 'jamones-paletas', $categories, true ) ) {
        emdo_montjam_dop_fail_20261012( 'Product identity/type/category mismatch: ' . $id );
    }
    foreach ( $definitions as $taxonomy => $value ) {
        $existing = $p->get_attributes();
        if ( isset( $existing[ $taxonomy ] ) &&
            ( ! $existing[ $taxonomy ] instanceof WC_Product_Attribute || $existing[ $taxonomy ]->get_variation() ) ) {
            emdo_montjam_dop_fail_20261012( 'Attribute participates in variations: ' . $id . ' ' . $taxonomy );
        }
    }
    $found[ $type ] = $p;
}
if ( count( $found ) !== 2 || ! isset( $found['jamon'], $found['paleta'] ) ) {
    emdo_montjam_dop_fail_20261012( 'Expected exactly one DOP Jabugo ham and one shoulder. Matched: ' . count( $found ) );
}
$terms = array();
foreach ( $definitions as $taxonomy => $value ) {
    $term = get_term_by( 'slug', $value['slug'], $taxonomy );
    if ( ! $term && $taxonomy === 'pa_dop' ) {
        $created = wp_insert_term( $value['label'], $taxonomy, array( 'slug' => $value['slug'] ) );
        if ( is_wp_error( $created ) ) {
            emdo_montjam_dop_fail_20261012( 'Could not create Jabugo taxonomy term: ' . $created->get_error_message() );
        }
        $term = get_term( (int) $created['term_id'], $taxonomy );
    }
    if ( ! $term instanceof WP_Term ) {
        emdo_montjam_dop_fail_20261012( 'Missing required taxonomy value: ' . $taxonomy . '/' . $value['slug'] );
    }
    $terms[ $taxonomy ] = (int) $term->term_id;
}
$before = array();
foreach ( $found as $type => $p ) {
    $id = $p->get_id();
    $variations = array();
    foreach ( $p->get_children() as $child_id ) {
        $child = wc_get_product( $child_id );
        if ( $child ) {
            $variations[ $child_id ] = array( $child->get_regular_price( 'edit' ), $child->get_sale_price( 'edit' ), $child->get_price( 'edit' ) );
        }
    }
    if ( ! $variations ) {
        emdo_montjam_dop_fail_20261012( 'Product has no variations: ' . $id );
    }
    $old_terms = array();
    foreach ( array_keys( $terms ) as $taxonomy ) {
        $t = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
        if ( is_wp_error( $t ) ) {
            emdo_montjam_dop_fail_20261012( 'Unable to read old taxonomy: ' . $id );
        }
        $old_terms[ $taxonomy ] = array_map( 'intval', $t );
    }
    $before[ $id ] = array(
        'attributes' => $p->get_attributes(), 'old_terms' => $old_terms,
        'regular' => $p->get_regular_price( 'edit' ), 'sale' => $p->get_sale_price( 'edit' ), 'price' => $p->get_price( 'edit' ),
        'categories' => $p->get_category_ids(), 'visibility' => $p->get_catalog_visibility(), 'status' => $p->get_status(),
        'name' => $p->get_name(), 'description' => $p->get_description(), 'short_description' => $p->get_short_description(),
        'sku' => $p->get_sku(), 'image' => $p->get_image_id(), 'variations' => $variations,
    );
}
$changed = array();
try {
    foreach ( $found as $type => $product ) {
        $id = $product->get_id();
        $attributes = $product->get_attributes();
        foreach ( $definitions as $taxonomy => $value ) {
            $attribute = isset( $attributes[ $taxonomy ] ) ? clone $attributes[ $taxonomy ] : new WC_Product_Attribute();
            $attribute->set_id( (int) wc_attribute_taxonomy_id_by_name( $value['attribute'] ) );
            $attribute->set_name( $taxonomy );
            $attribute->set_options( array( $terms[ $taxonomy ] ) );
            $attribute->set_visible( true );
            $attribute->set_variation( false );
            if ( ! isset( $attributes[ $taxonomy ] ) ) {
                $attribute->set_position( count( $attributes ) );
            }
            $attributes[ $taxonomy ] = $attribute;
        }
        $product->set_attributes( $attributes );
        $product->save();
        $changed[] = $id;
        foreach ( $terms as $taxonomy => $term_id ) {
            $assigned = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
            if ( is_wp_error( $assigned ) || array_map( 'intval', (array) $assigned ) !== array( $term_id ) ) {
                throw new RuntimeException( 'Taxonomy verification failed: ' . $id . '/' . $taxonomy );
            }
        }
    }
    $report = array();
    foreach ( $found as $type => $product ) {
        $id = $product->get_id();
        wc_delete_product_transients( $id );
        clean_post_cache( $id );
        $fresh = wc_get_product( $id );
        $b = $before[ $id ];
        $cats_actual = $fresh->get_category_ids();
        $cats_before = $b['categories'];
        sort( $cats_actual ); sort( $cats_before );
        if ( $fresh->get_regular_price( 'edit' ) !== $b['regular'] || $fresh->get_sale_price( 'edit' ) !== $b['sale']
            || $fresh->get_price( 'edit' ) !== $b['price'] || $fresh->get_status() !== $b['status']
            || $fresh->get_catalog_visibility() !== $b['visibility'] || $cats_actual !== $cats_before
            || $fresh->get_name() !== $b['name'] || $fresh->get_description() !== $b['description']
            || $fresh->get_short_description() !== $b['short_description'] || $fresh->get_sku() !== $b['sku']
            || $fresh->get_image_id() !== $b['image'] ) {
            throw new RuntimeException( 'Protected parent fields changed: ' . $id );
        }
        foreach ( $b['variations'] as $child_id => $original_prices ) {
            $child = wc_get_product( $child_id );
            if ( ! $child || array( $child->get_regular_price( 'edit' ), $child->get_sale_price( 'edit' ), $child->get_price( 'edit' ) ) !== $original_prices ) {
                throw new RuntimeException( 'Protected variation prices changed: ' . $child_id );
            }
        }
        if ( function_exists( 'rocket_clean_post' ) ) {
            rocket_clean_post( $id );
        }
        $report[] = array( 'id' => $id, 'type' => $type, 'name' => $fresh->get_name(),
            'with_dop' => wp_get_object_terms( $id, 'pa_con-dop', array( 'fields' => 'slugs' ) ),
            'dop' => wp_get_object_terms( $id, 'pa_dop', array( 'fields' => 'slugs' ) ),
            'variations_unchanged' => count( $b['variations'] ) );
    }
    if ( class_exists( 'WC_Cache_Helper' ) ) {
        WC_Cache_Helper::get_transient_version( 'product', true );
    }
    if ( function_exists( 'rocket_clean_domain' ) ) {
        rocket_clean_domain();
    }
    echo 'MONTJAM_DOP_PRODUCTION_OK ' . wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
} catch ( Throwable $error ) {
    fwrite( STDERR, 'MONTJAM_DOP_FAILED ' . $error->getMessage() . "\n" );
    foreach ( $changed as $id ) {
        try {
            $product = wc_get_product( $id );
            if ( ! $product ) { continue; }
            $product->set_attributes( $before[ $id ]['attributes'] );
            $product->save();
            foreach ( $before[ $id ]['old_terms'] as $taxonomy => $ids ) {
                $restored = wp_set_object_terms( $id, $ids, $taxonomy, false );
                if ( is_wp_error( $restored ) ) { throw new RuntimeException( $restored->get_error_message() ); }
            }
            wc_delete_product_transients( $id );
        } catch ( Throwable $restore_error ) {
            fwrite( STDERR, 'MONTJAM_DOP_ROLLBACK_ERROR ' . $id . ': ' . $restore_error->getMessage() . "\n" );
        }
    }
    exit( 20 );
}
