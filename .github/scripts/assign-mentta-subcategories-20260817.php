<?php
/**
 * One-off production operation: create three child categories under MENTTA
 * mirroring the live catalog groups and assign every MENTTA product to the
 * corresponding child category based on its existing catalog categories.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

function mdo_mentta_subcat_norm( $value ) {
    $value = wp_strip_all_tags( (string) $value );
    $value = remove_accents( $value );
    $value = preg_replace( '/\s+/u', ' ', trim( $value ) );
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
}

function mdo_mentta_subcat_ids( $term_id ) {
    $ids = array( (int) $term_id );
    $children = get_term_children( (int) $term_id, 'product_cat' );
    if ( is_wp_error( $children ) ) {
        return $children;
    }
    foreach ( $children as $child_id ) {
        $ids[] = (int) $child_id;
    }
    $ids = array_values( array_unique( $ids ) );
    sort( $ids, SORT_NUMERIC );
    return $ids;
}

function mdo_mentta_subcat_product_ids_for_term( $term_id ) {
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

$mentta = get_term_by( 'slug', 'mentta', 'product_cat' );
if ( ! ( $mentta instanceof WP_Term ) ) {
    fwrite( STDERR, "ERROR: MENTTA category with slug 'mentta' not found.\n" );
    exit( 2 );
}

$mentta_descendants = get_term_children( (int) $mentta->term_id, 'product_cat' );
if ( is_wp_error( $mentta_descendants ) ) {
    fwrite( STDERR, 'ERROR resolving MENTTA descendants: ' . $mentta_descendants->get_error_message() . "\n" );
    exit( 3 );
}
$mentta_descendants = array_map( 'intval', $mentta_descendants );

$groups = array(
    'jamones' => array(
        'label' => 'Jamones y paletas',
        'slug'  => 'mentta-jamones-y-paletas',
    ),
    'embutidos' => array(
        'label' => 'Embutidos',
        'slug'  => 'mentta-embutidos',
    ),
    'aceites' => array(
        'label' => 'Aceites',
        'slug'  => 'mentta-aceites',
    ),
);

$all_terms = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    )
);
if ( is_wp_error( $all_terms ) ) {
    fwrite( STDERR, 'ERROR reading product categories: ' . $all_terms->get_error_message() . "\n" );
    exit( 4 );
}

foreach ( $groups as $key => &$group ) {
    $wanted_norm = mdo_mentta_subcat_norm( $group['label'] );
    $candidates = array();
    foreach ( $all_terms as $term ) {
        if ( (int) $term->term_id === (int) $mentta->term_id || in_array( (int) $term->term_id, $mentta_descendants, true ) ) {
            continue;
        }
        if ( mdo_mentta_subcat_norm( $term->name ) === $wanted_norm ) {
            $candidates[] = $term;
        }
    }

    $top_level = array_values(
        array_filter(
            $candidates,
            static function ( $term ) {
                return 0 === (int) $term->parent;
            }
        )
    );

    if ( 1 === count( $top_level ) ) {
        $source = $top_level[0];
    } elseif ( 1 === count( $candidates ) ) {
        $source = $candidates[0];
    } else {
        $ids = array_map(
            static function ( $term ) {
                return (int) $term->term_id;
            },
            $candidates
        );
        fwrite( STDERR, sprintf( "ERROR: expected one unambiguous source category for '%s'; candidates=%s\n", $group['label'], implode( ',', $ids ) ) );
        exit( 5 );
    }

    $source_ids = mdo_mentta_subcat_ids( (int) $source->term_id );
    if ( is_wp_error( $source_ids ) ) {
        fwrite( STDERR, sprintf( "ERROR reading descendants for source category '%s': %s\n", $group['label'], $source_ids->get_error_message() ) );
        exit( 6 );
    }

    $group['source_id']  = (int) $source->term_id;
    $group['source_ids'] = $source_ids;
}
unset( $group );

$mentta_products = mdo_mentta_subcat_product_ids_for_term( (int) $mentta->term_id );
if ( is_wp_error( $mentta_products ) ) {
    fwrite( STDERR, 'ERROR reading MENTTA products: ' . $mentta_products->get_error_message() . "\n" );
    exit( 7 );
}
if ( ! $mentta_products ) {
    fwrite( STDERR, "ERROR: MENTTA contains no products.\n" );
    exit( 8 );
}

$expected = array();
foreach ( array_keys( $groups ) as $key ) {
    $expected[ $key ] = array();
}
$unmatched = array();
$multi_match = array();

foreach ( $mentta_products as $product_id ) {
    $product_terms = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $product_terms ) ) {
        fwrite( STDERR, sprintf( "ERROR reading categories for product %d: %s\n", $product_id, $product_terms->get_error_message() ) );
        exit( 9 );
    }
    $product_terms = array_map( 'intval', $product_terms );

    $matched_keys = array();
    foreach ( $groups as $key => $group ) {
        if ( array_intersect( $product_terms, $group['source_ids'] ) ) {
            $expected[ $key ][] = $product_id;
            $matched_keys[] = $key;
        }
    }

    if ( ! $matched_keys ) {
        $names = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
        if ( is_wp_error( $names ) ) {
            $names = array();
        }
        $unmatched[ $product_id ] = array(
            'title'      => get_the_title( $product_id ),
            'categories' => $names,
        );
    } elseif ( count( $matched_keys ) > 1 ) {
        $multi_match[ $product_id ] = $matched_keys;
    }
}

if ( $unmatched ) {
    fwrite( STDERR, "ERROR: some MENTTA products do not belong to Jamones y paletas, Embutidos or Aceites. No changes were made.\n" );
    foreach ( $unmatched as $product_id => $data ) {
        fwrite( STDERR, sprintf( "UNMATCHED %d | %s | %s\n", $product_id, $data['title'], implode( ' > ', $data['categories'] ) ) );
    }
    exit( 10 );
}

foreach ( $expected as &$ids ) {
    $ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
    sort( $ids, SORT_NUMERIC );
}
unset( $ids );

foreach ( $groups as $key => &$group ) {
    $existing = term_exists( $group['label'], 'product_cat', (int) $mentta->term_id );
    if ( $existing ) {
        $child_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
        $child = get_term( $child_id, 'product_cat' );
        if ( ! ( $child instanceof WP_Term ) || (int) $child->parent !== (int) $mentta->term_id ) {
            fwrite( STDERR, sprintf( "ERROR: existing child category '%s' is invalid.\n", $group['label'] ) );
            exit( 11 );
        }
    } else {
        $created = wp_insert_term(
            $group['label'],
            'product_cat',
            array(
                'parent' => (int) $mentta->term_id,
                'slug'   => $group['slug'],
            )
        );
        if ( is_wp_error( $created ) ) {
            fwrite( STDERR, sprintf( "ERROR creating MENTTA child '%s': %s\n", $group['label'], $created->get_error_message() ) );
            exit( 12 );
        }
        $child_id = (int) $created['term_id'];
    }
    $group['child_id'] = $child_id;
}
unset( $group );

foreach ( $groups as $key => $group ) {
    $current = mdo_mentta_subcat_product_ids_for_term( (int) $group['child_id'] );
    if ( is_wp_error( $current ) ) {
        fwrite( STDERR, sprintf( "ERROR reading current assignments for '%s': %s\n", $group['label'], $current->get_error_message() ) );
        exit( 13 );
    }

    $to_remove = array_values( array_diff( $current, $expected[ $key ] ) );
    $to_add    = array_values( array_diff( $expected[ $key ], $current ) );

    foreach ( $to_remove as $product_id ) {
        $result = wp_remove_object_terms( $product_id, (int) $group['child_id'], 'product_cat' );
        if ( is_wp_error( $result ) ) {
            fwrite( STDERR, sprintf( "ERROR removing product %d from '%s': %s\n", $product_id, $group['label'], $result->get_error_message() ) );
            exit( 14 );
        }
    }

    foreach ( $to_add as $product_id ) {
        $result = wp_set_object_terms( $product_id, (int) $group['child_id'], 'product_cat', true );
        if ( is_wp_error( $result ) ) {
            fwrite( STDERR, sprintf( "ERROR assigning product %d to '%s': %s\n", $product_id, $group['label'], $result->get_error_message() ) );
            exit( 15 );
        }
    }

    clean_term_cache( (int) $group['child_id'], 'product_cat' );
}
clean_term_cache( (int) $mentta->term_id, 'product_cat' );

$verified_union = array();
foreach ( $groups as $key => $group ) {
    $final = mdo_mentta_subcat_product_ids_for_term( (int) $group['child_id'] );
    if ( is_wp_error( $final ) ) {
        fwrite( STDERR, sprintf( "ERROR verifying '%s': %s\n", $group['label'], $final->get_error_message() ) );
        exit( 16 );
    }
    if ( $final !== $expected[ $key ] ) {
        fwrite( STDERR, sprintf( "ERROR: final membership mismatch for '%s'. expected=%d actual=%d\n", $group['label'], count( $expected[ $key ] ), count( $final ) ) );
        exit( 17 );
    }
    $verified_union = array_merge( $verified_union, $final );
}
$verified_union = array_values( array_unique( array_map( 'intval', $verified_union ) ) );
sort( $verified_union, SORT_NUMERIC );

if ( $verified_union !== $mentta_products ) {
    fwrite( STDERR, sprintf( "ERROR: MENTTA child union mismatch. mentta=%d categorized=%d\n", count( $mentta_products ), count( $verified_union ) ) );
    exit( 18 );
}

echo 'MENTTA parent: ' . $mentta->name . ' (' . (int) $mentta->term_id . ")\n";
echo 'MENTTA products categorized: ' . count( $mentta_products ) . "\n";
foreach ( $groups as $key => $group ) {
    $published = 0;
    foreach ( $expected[ $key ] as $product_id ) {
        if ( 'publish' === get_post_status( $product_id ) ) {
            ++$published;
        }
    }
    echo sprintf(
        "%s | source=%d | mentta_child=%d | products=%d | published=%d\n",
        $group['label'],
        (int) $group['source_id'],
        (int) $group['child_id'],
        count( $expected[ $key ] ),
        $published
    );
}
if ( $multi_match ) {
    foreach ( $multi_match as $product_id => $keys ) {
        echo sprintf( "MULTI_MATCH %d | %s | %s\n", $product_id, get_the_title( $product_id ), implode( ',', $keys ) );
    }
}
echo "mentta_subcategories_ok\n";
