<?php
/**
 * One-off production operation: mirror under MENTTA every real top-level
 * WooCommerce product category represented by at least one MENTTA product.
 * Categories with no MENTTA products are not created.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

function mdo_mentta_product_ids_for_term( $term_id ) {
    $ids = get_objects_in_term( (int) $term_id, 'product_cat' );
    if ( is_wp_error( $ids ) ) {
        return $ids;
    }
    $ids = array_values(
        array_unique(
            array_filter(
                array_map( 'intval', $ids ),
                static function ( $id ) {
                    return 'product' === get_post_type( $id );
                }
            )
        )
    );
    sort( $ids, SORT_NUMERIC );
    return $ids;
}

function mdo_mentta_root_term( $term ) {
    if ( ! ( $term instanceof WP_Term ) ) {
        return null;
    }

    $current = $term;
    $guard   = 0;
    while ( (int) $current->parent > 0 && $guard < 30 ) {
        $parent = get_term( (int) $current->parent, 'product_cat' );
        if ( ! ( $parent instanceof WP_Term ) ) {
            return null;
        }
        $current = $parent;
        ++$guard;
    }

    return $current;
}

$mentta = get_term_by( 'slug', 'mentta', 'product_cat' );
if ( ! ( $mentta instanceof WP_Term ) ) {
    fwrite( STDERR, "ERROR: MENTTA category with slug 'mentta' not found.\n" );
    exit( 2 );
}

$mentta_products = mdo_mentta_product_ids_for_term( (int) $mentta->term_id );
if ( is_wp_error( $mentta_products ) ) {
    fwrite( STDERR, 'ERROR reading MENTTA products: ' . $mentta_products->get_error_message() . "\n" );
    exit( 3 );
}
if ( ! $mentta_products ) {
    fwrite( STDERR, "ERROR: MENTTA contains no products.\n" );
    exit( 4 );
}

$mentta_descendants = get_term_children( (int) $mentta->term_id, 'product_cat' );
if ( is_wp_error( $mentta_descendants ) ) {
    fwrite( STDERR, 'ERROR resolving MENTTA descendants: ' . $mentta_descendants->get_error_message() . "\n" );
    exit( 5 );
}
$mentta_tree_ids = array_merge( array( (int) $mentta->term_id ), array_map( 'intval', $mentta_descendants ) );
$mentta_tree_ids = array_values( array_unique( $mentta_tree_ids ) );

$represented = array();
$uncategorized = array();

foreach ( $mentta_products as $product_id ) {
    $terms = wp_get_object_terms( $product_id, 'product_cat' );
    if ( is_wp_error( $terms ) ) {
        fwrite( STDERR, sprintf( "ERROR reading categories for product %d: %s\n", $product_id, $terms->get_error_message() ) );
        exit( 6 );
    }

    $roots_for_product = array();
    foreach ( $terms as $term ) {
        if ( in_array( (int) $term->term_id, $mentta_tree_ids, true ) ) {
            continue;
        }
        $root = mdo_mentta_root_term( $term );
        if ( ! ( $root instanceof WP_Term ) ) {
            fwrite( STDERR, sprintf( "ERROR resolving top-level category for product %d term %d.\n", $product_id, (int) $term->term_id ) );
            exit( 7 );
        }
        if ( (int) $root->term_id === (int) $mentta->term_id ) {
            continue;
        }
        $roots_for_product[ (int) $root->term_id ] = $root;
    }

    if ( ! $roots_for_product ) {
        $uncategorized[] = $product_id;
        continue;
    }

    foreach ( $roots_for_product as $root_id => $root ) {
        if ( ! isset( $represented[ $root_id ] ) ) {
            $represented[ $root_id ] = array(
                'term'     => $root,
                'products' => array(),
            );
        }
        $represented[ $root_id ]['products'][] = $product_id;
    }
}

if ( $uncategorized ) {
    fwrite( STDERR, "ERROR: some MENTTA products have no non-MENTTA top-level catalog category. No changes were made.\n" );
    foreach ( $uncategorized as $product_id ) {
        fwrite( STDERR, sprintf( "UNCATEGORIZED %d | %s\n", $product_id, get_the_title( $product_id ) ) );
    }
    exit( 8 );
}

if ( ! $represented ) {
    fwrite( STDERR, "ERROR: no represented top-level categories found. No changes were made.\n" );
    exit( 9 );
}

foreach ( $represented as &$data ) {
    $data['products'] = array_values( array_unique( array_map( 'intval', $data['products'] ) ) );
    sort( $data['products'], SORT_NUMERIC );
}
unset( $data );

uasort(
    $represented,
    static function ( $a, $b ) {
        return strcasecmp( $a['term']->name, $b['term']->name );
    }
);

/* Preflight: report exactly what will be mirrored before any write. */
echo 'MENTTA parent: ' . $mentta->name . ' (' . (int) $mentta->term_id . ")\n";
echo 'MENTTA products: ' . count( $mentta_products ) . "\n";
echo 'Represented top-level categories: ' . count( $represented ) . "\n";
foreach ( $represented as $source_id => $data ) {
    $published = 0;
    foreach ( $data['products'] as $product_id ) {
        if ( 'publish' === get_post_status( $product_id ) ) {
            ++$published;
        }
    }
    echo sprintf(
        "SOURCE %s | slug=%s | id=%d | products=%d | published=%d\n",
        $data['term']->name,
        $data['term']->slug,
        (int) $source_id,
        count( $data['products'] ),
        $published
    );
}

$managed_child_ids = array();
foreach ( $represented as $source_id => &$data ) {
    $source = $data['term'];
    $existing = term_exists( $source->name, 'product_cat', (int) $mentta->term_id );

    if ( $existing ) {
        $child_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
        $child = get_term( $child_id, 'product_cat' );
        if ( ! ( $child instanceof WP_Term ) || (int) $child->parent !== (int) $mentta->term_id ) {
            fwrite( STDERR, sprintf( "ERROR: existing MENTTA child '%s' is invalid.\n", $source->name ) );
            exit( 10 );
        }

        $updated = wp_update_term(
            $child_id,
            'product_cat',
            array(
                'name'        => $source->name,
                'description' => $source->description,
            )
        );
        if ( is_wp_error( $updated ) ) {
            fwrite( STDERR, sprintf( "ERROR updating MENTTA child '%s': %s\n", $source->name, $updated->get_error_message() ) );
            exit( 11 );
        }
    } else {
        $created = wp_insert_term(
            $source->name,
            'product_cat',
            array(
                'parent'      => (int) $mentta->term_id,
                'slug'        => 'mentta-' . sanitize_title( $source->slug ),
                'description' => $source->description,
            )
        );
        if ( is_wp_error( $created ) ) {
            fwrite( STDERR, sprintf( "ERROR creating MENTTA child '%s': %s\n", $source->name, $created->get_error_message() ) );
            exit( 12 );
        }
        $child_id = (int) $created['term_id'];
    }

    update_term_meta( $child_id, '_mdo_mentta_source_term_id', (int) $source_id );

    $source_thumbnail = get_term_meta( (int) $source_id, 'thumbnail_id', true );
    if ( '' !== (string) $source_thumbnail ) {
        update_term_meta( $child_id, 'thumbnail_id', (int) $source_thumbnail );
    }
    $display_type = get_term_meta( (int) $source_id, 'display_type', true );
    if ( '' !== (string) $display_type ) {
        update_term_meta( $child_id, 'display_type', $display_type );
    }

    $data['child_id'] = $child_id;
    $managed_child_ids[] = $child_id;
}
unset( $data );

/* Remove only stale child categories previously managed by this operation. */
$current_children = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => (int) $mentta->term_id,
    )
);
if ( is_wp_error( $current_children ) ) {
    fwrite( STDERR, 'ERROR reading MENTTA children: ' . $current_children->get_error_message() . "\n" );
    exit( 13 );
}

foreach ( $current_children as $child ) {
    $source_marker = (int) get_term_meta( (int) $child->term_id, '_mdo_mentta_source_term_id', true );
    if ( $source_marker && ! isset( $represented[ $source_marker ] ) ) {
        $assigned = mdo_mentta_product_ids_for_term( (int) $child->term_id );
        if ( is_wp_error( $assigned ) ) {
            fwrite( STDERR, sprintf( "ERROR reading stale child '%s': %s\n", $child->name, $assigned->get_error_message() ) );
            exit( 14 );
        }
        foreach ( $assigned as $product_id ) {
            $removed = wp_remove_object_terms( $product_id, (int) $child->term_id, 'product_cat' );
            if ( is_wp_error( $removed ) ) {
                fwrite( STDERR, sprintf( "ERROR cleaning stale child '%s': %s\n", $child->name, $removed->get_error_message() ) );
                exit( 15 );
            }
        }
        $deleted = wp_delete_term( (int) $child->term_id, 'product_cat' );
        if ( is_wp_error( $deleted ) ) {
            fwrite( STDERR, sprintf( "ERROR deleting stale child '%s': %s\n", $child->name, $deleted->get_error_message() ) );
            exit( 16 );
        }
    }
}

foreach ( $represented as $source_id => $data ) {
    $child_id = (int) $data['child_id'];
    $expected = $data['products'];
    $current  = mdo_mentta_product_ids_for_term( $child_id );
    if ( is_wp_error( $current ) ) {
        fwrite( STDERR, sprintf( "ERROR reading assignments for '%s': %s\n", $data['term']->name, $current->get_error_message() ) );
        exit( 17 );
    }

    foreach ( array_values( array_diff( $current, $expected ) ) as $product_id ) {
        $result = wp_remove_object_terms( $product_id, $child_id, 'product_cat' );
        if ( is_wp_error( $result ) ) {
            fwrite( STDERR, sprintf( "ERROR removing product %d from '%s': %s\n", $product_id, $data['term']->name, $result->get_error_message() ) );
            exit( 18 );
        }
    }

    foreach ( array_values( array_diff( $expected, $current ) ) as $product_id ) {
        $result = wp_set_object_terms( $product_id, $child_id, 'product_cat', true );
        if ( is_wp_error( $result ) ) {
            fwrite( STDERR, sprintf( "ERROR assigning product %d to '%s': %s\n", $product_id, $data['term']->name, $result->get_error_message() ) );
            exit( 19 );
        }
    }

    clean_term_cache( $child_id, 'product_cat' );
}
clean_term_cache( (int) $mentta->term_id, 'product_cat' );

$covered = array();
foreach ( $represented as $source_id => $data ) {
    $final = mdo_mentta_product_ids_for_term( (int) $data['child_id'] );
    if ( is_wp_error( $final ) ) {
        fwrite( STDERR, sprintf( "ERROR verifying '%s': %s\n", $data['term']->name, $final->get_error_message() ) );
        exit( 20 );
    }
    if ( $final !== $data['products'] ) {
        fwrite( STDERR, sprintf( "ERROR: final membership mismatch for '%s'. expected=%d actual=%d\n", $data['term']->name, count( $data['products'] ), count( $final ) ) );
        exit( 21 );
    }
    $covered = array_merge( $covered, $final );
    echo sprintf(
        "MENTTA CHILD %s | id=%d | source_id=%d | products=%d\n",
        $data['term']->name,
        (int) $data['child_id'],
        (int) $source_id,
        count( $final )
    );
}

$covered = array_values( array_unique( array_map( 'intval', $covered ) ) );
sort( $covered, SORT_NUMERIC );
if ( $covered !== $mentta_products ) {
    fwrite( STDERR, sprintf( "ERROR: MENTTA coverage mismatch. mentta=%d categorized=%d\n", count( $mentta_products ), count( $covered ) ) );
    exit( 22 );
}

echo "mentta_subcategories_ok\n";
