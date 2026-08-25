<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function mdo_gmf_english_ready_v1( int $product_id ): bool {
    if ( '1' !== (string) get_post_meta( $product_id, '_en_US_published', true ) ) {
        return false;
    }
    return '' !== mdo_gmf_clean_text_v1( get_post_meta( $product_id, '_en_US_post_title', true ), 150 );
}

function mdo_gmf_product_title_v1( WC_Product $product, WP_Post $parent_post, ?WC_Product $parent, string $language ): string {
    $parent_id = (int) $parent_post->ID;
    if ( 'en' === $language ) {
        $base = mdo_gmf_clean_text_v1( get_post_meta( $parent_id, '_en_US_post_title', true ), 150 );
    } else {
        $base = mdo_gmf_clean_text_v1( $parent ? $parent->get_name() : $product->get_name(), 150 );
    }
    if ( '' === $base ) {
        return '';
    }

    if ( ! $product instanceof WC_Product_Variation ) {
        return $base;
    }

    $parts = array();
    foreach ( $product->get_attributes() as $taxonomy => $value ) {
        $taxonomy = (string) $taxonomy;
        $value = (string) $value;
        if ( '' === $value ) {
            continue;
        }
        $display = $value;
        if ( taxonomy_exists( $taxonomy ) ) {
            $term = get_term_by( 'slug', $value, $taxonomy );
            if ( $term instanceof WP_Term ) {
                if ( 'en' === $language ) {
                    $english = mdo_gmf_clean_text_v1( get_term_meta( $term->term_id, '_en_US_name', true ), 80 );
                    $display = '' !== $english ? $english : $term->name;
                } else {
                    $display = $term->name;
                }
            }
        } elseif ( 'en' === $language && function_exists( 'mdoea_translate_custom_attribute_value_010263' ) ) {
            $display = mdoea_translate_custom_attribute_value_010263( $display );
        }
        $display = mdo_gmf_clean_text_v1( $display, 80 );
        if ( '' !== $display ) {
            $parts[] = $display;
        }
    }

    return mdo_gmf_clean_text_v1( $base . ( $parts ? ' - ' . implode( ', ', $parts ) : '' ), 150 );
}

function mdo_gmf_product_description_v1( WC_Product $product, WP_Post $parent_post, ?WC_Product $parent, string $language ): string {
    $parent_id = (int) $parent_post->ID;
    if ( 'en' === $language ) {
        $description = (string) get_post_meta( $parent_id, '_en_US_post_excerpt', true );
        if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
            $description = (string) get_post_meta( $parent_id, '_en_US_post_content', true );
        }
    } else {
        $source = $parent instanceof WC_Product ? $parent : $product;
        $description = $source->get_short_description();
        if ( '' === trim( wp_strip_all_tags( (string) $description ) ) ) {
            $description = $source->get_description();
        }
    }

    $description = mdo_gmf_clean_text_v1( strip_shortcodes( (string) $description ), 5000 );
    if ( '' === $description ) {
        $description = mdo_gmf_product_title_v1( $product, $parent_post, $parent, $language );
    }
    return $description;
}

function mdo_gmf_parent_link_v1( WP_Post $parent_post, string $language ): string {
    if ( 'en' === $language ) {
        if ( function_exists( 'emdo_cleanup_english_product_url' ) ) {
            $url = (string) emdo_cleanup_english_product_url( $parent_post );
            if ( '' !== $url ) {
                return $url;
            }
        }
        if ( function_exists( 'mdoer_en_url' ) ) {
            $url = (string) mdoer_en_url( $parent_post );
            if ( '' !== $url ) {
                return $url;
            }
        }
        $slug = sanitize_title( (string) get_post_meta( $parent_post->ID, '_en_US_post_name', true ) );
        return '' !== $slug ? home_url( '/en/product/' . rawurlencode( $slug ) . '/' ) : '';
    }

    $url = get_permalink( $parent_post );
    return is_string( $url ) ? $url : '';
}

function mdo_gmf_product_link_v1( WC_Product $product, WP_Post $parent_post, string $language, string $country ): string {
    $base = mdo_gmf_parent_link_v1( $parent_post, $language );
    if ( '' === $base ) {
        return '';
    }

    if ( $product instanceof WC_Product_Variation ) {
        $native = $product->get_permalink();
        $query = is_string( $native ) ? (string) wp_parse_url( $native, PHP_URL_QUERY ) : '';
        if ( '' !== $query ) {
            parse_str( $query, $args );
            if ( is_array( $args ) && ! empty( $args ) ) {
                $base = add_query_arg( $args, $base );
            }
        }
    }

    return esc_url_raw( add_query_arg( 'mdo_country', $country, $base ) );
}

function mdo_gmf_price_fields_v1( WC_Product $product ): array {
    $raw_current = $product->get_price();
    if ( '' === $raw_current || ! is_numeric( $raw_current ) ) {
        return array( '', '', 0.0 );
    }

    $current = (float) wc_get_price_to_display( $product, array( 'price' => (float) $raw_current ) );
    if ( $current <= 0 ) {
        return array( '', '', 0.0 );
    }

    $regular_raw = $product->get_regular_price();
    $sale_raw = $product->get_sale_price();
    $regular = is_numeric( $regular_raw ) ? (float) wc_get_price_to_display( $product, array( 'price' => (float) $regular_raw ) ) : $current;
    $sale = is_numeric( $sale_raw ) ? (float) wc_get_price_to_display( $product, array( 'price' => (float) $sale_raw ) ) : 0.0;

    if ( $product->is_on_sale() && $regular > 0 && $sale > 0 && $sale < $regular ) {
        return array( mdo_gmf_money_v1( $regular ), mdo_gmf_money_v1( $sale ), $sale );
    }

    return array( mdo_gmf_money_v1( $current ), '', $current );
}

function mdo_gmf_availability_v1( WC_Product $product ): string {
    $status = (string) $product->get_stock_status();
    if ( 'instock' === $status ) {
        return 'in_stock';
    }
    // Without a reliable availability_date, advertising a backorder as such can
    // create a Merchant requirement violation. Keep it unavailable instead.
    return 'out_of_stock';
}

function mdo_gmf_image_v1( WC_Product $product, ?WC_Product $parent ): string {
    $image_id = (int) $product->get_image_id();
    if ( $image_id <= 0 && $parent instanceof WC_Product ) {
        $image_id = (int) $parent->get_image_id();
    }
    if ( $image_id <= 0 ) {
        return '';
    }
    $url = wp_get_attachment_image_url( $image_id, 'full' );
    return is_string( $url ) ? esc_url_raw( $url ) : '';
}

function mdo_gmf_additional_images_v1( WC_Product $product, ?WC_Product $parent ): array {
    $ids = array_map( 'absint', (array) $product->get_gallery_image_ids() );
    if ( empty( $ids ) && $parent instanceof WC_Product ) {
        $ids = array_map( 'absint', (array) $parent->get_gallery_image_ids() );
    }
    $urls = array();
    foreach ( array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, 10 ) as $image_id ) {
        $url = wp_get_attachment_image_url( $image_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            $urls[] = esc_url_raw( $url );
        }
    }
    return array_values( array_unique( $urls ) );
}

function mdo_gmf_valid_gtin_v1( string $raw ): string {
    $gtin = preg_replace( '/\D+/', '', $raw );
    if ( ! in_array( strlen( $gtin ), array( 8, 12, 13, 14 ), true ) ) {
        return '';
    }
    $sum = 0;
    $reverse = strrev( substr( $gtin, 0, -1 ) );
    for ( $i = 0, $len = strlen( $reverse ); $i < $len; ++$i ) {
        $digit = (int) $reverse[ $i ];
        $sum += $digit * ( 0 === $i % 2 ? 3 : 1 );
    }
    $check = ( 10 - ( $sum % 10 ) ) % 10;
    return $check === (int) substr( $gtin, -1 ) ? $gtin : '';
}

function mdo_gmf_gtin_v1( WC_Product $product, ?WC_Product $parent ): string {
    $ids = array( (int) $product->get_id() );
    if ( $parent instanceof WC_Product && (int) $parent->get_id() !== (int) $product->get_id() ) {
        $ids[] = (int) $parent->get_id();
    }

    if ( is_callable( array( $product, 'get_global_unique_id' ) ) ) {
        $candidate = mdo_gmf_valid_gtin_v1( (string) $product->get_global_unique_id() );
        if ( '' !== $candidate ) {
            return $candidate;
        }
    }

    foreach ( $ids as $id ) {
        foreach ( array( '_global_unique_id', '_alg_ean', '_gtin', 'gtin', '_ean', 'ean', '_barcode', 'barcode' ) as $key ) {
            $candidate = mdo_gmf_valid_gtin_v1( (string) get_post_meta( $id, $key, true ) );
            if ( '' !== $candidate ) {
                return $candidate;
            }
        }
    }
    return '';
}

function mdo_gmf_mpn_v1( WC_Product $product, ?WC_Product $parent ): string {
    $ids = array( (int) $product->get_id() );
    if ( $parent instanceof WC_Product && (int) $parent->get_id() !== (int) $product->get_id() ) {
        $ids[] = (int) $parent->get_id();
    }
    foreach ( $ids as $id ) {
        foreach ( array( '_mpn', 'mpn', '_alg_mpn', 'rank_math_mpn' ) as $key ) {
            $value = mdo_gmf_clean_text_v1( get_post_meta( $id, $key, true ), 70 );
            if ( '' !== $value ) {
                return $value;
            }
        }
    }
    return '';
}

function mdo_gmf_term_name_v1( WP_Term $term, string $language ): string {
    if ( 'en' !== $language ) {
        return mdo_gmf_clean_text_v1( $term->name, 120 );
    }
    if ( '1' !== (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) {
        return '';
    }
    return mdo_gmf_clean_text_v1( get_term_meta( $term->term_id, '_en_US_name', true ), 120 );
}

function mdo_gmf_product_type_v1( int $product_id, string $language ): string {
    $terms = wp_get_post_terms( $product_id, 'product_cat' );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }

    usort( $terms, static function( WP_Term $a, WP_Term $b ): int {
        $depth = static function( WP_Term $term ): int {
            return count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
        };
        return $depth( $b ) <=> $depth( $a );
    } );

    $leaf = $terms[0];
    if ( ! $leaf instanceof WP_Term ) {
        return '';
    }

    $ids = array_reverse( get_ancestors( $leaf->term_id, 'product_cat', 'taxonomy' ) );
    $ids[] = $leaf->term_id;
    $names = array();
    foreach ( $ids as $term_id ) {
        $term = get_term( $term_id, 'product_cat' );
        if ( ! $term instanceof WP_Term ) {
            continue;
        }
        $name = mdo_gmf_term_name_v1( $term, $language );
        // Avoid mixing Spanish category labels into an English feed.
        if ( 'en' === $language && '' === $name ) {
            return '';
        }
        if ( '' !== $name ) {
            $names[] = $name;
        }
    }
    return implode( ' > ', $names );
}

function mdo_gmf_google_category_v1( int $product_id ): string {
    foreach ( array( '_google_product_category', 'google_product_category', '_wc_gpf_google_product_category' ) as $key ) {
        $value = mdo_gmf_clean_text_v1( get_post_meta( $product_id, $key, true ), 750 );
        if ( '' !== $value ) {
            return $value;
        }
    }
    return '';
}

function mdo_gmf_shipping_weight_v1( WC_Product $product, ?WC_Product $parent ): string {
    $weight = $product->get_weight();
    if ( ( '' === $weight || ! is_numeric( $weight ) || (float) $weight <= 0 ) && $parent instanceof WC_Product ) {
        $weight = $parent->get_weight();
    }
    if ( '' === $weight || ! is_numeric( $weight ) || (float) $weight <= 0 ) {
        return '';
    }
    $unit = strtolower( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );
    if ( 'lbs' === $unit ) {
        $unit = 'lb';
    }
    if ( ! in_array( $unit, array( 'kg', 'g', 'lb', 'oz' ), true ) ) {
        return '';
    }
    return rtrim( rtrim( number_format( (float) $weight, 3, '.', '' ), '0' ), '.' ) . ' ' . $unit;
}

function mdo_gmf_offer_data_v1( WC_Product $product, WP_Post $parent_post, ?WC_Product $parent, string $country ): ?array {
    $language = mdo_gmf_feed_language_v1( $country );
    $vendor_id = (int) $parent_post->post_author;

    if ( ! mdo_gmf_vendor_is_active_v1( $vendor_id ) ) {
        return null;
    }
    if ( 'en' === $language && ! mdo_gmf_english_ready_v1( (int) $parent_post->ID ) ) {
        return null;
    }

    $shipping = mdo_gmf_vendor_shipping_v1( $vendor_id, $country );
    if ( empty( $shipping['can_ship'] ) || ! array_key_exists( 'cost', $shipping ) || ! is_numeric( $shipping['cost'] ) ) {
        return null;
    }

    list( $price, $sale_price, $effective_price ) = mdo_gmf_price_fields_v1( $product );
    if ( '' === $price || $effective_price <= 0 ) {
        return null;
    }

    $title = mdo_gmf_product_title_v1( $product, $parent_post, $parent, $language );
    $description = mdo_gmf_product_description_v1( $product, $parent_post, $parent, $language );
    $link = mdo_gmf_product_link_v1( $product, $parent_post, $language, $country );
    $image = mdo_gmf_image_v1( $product, $parent );
    if ( '' === $title || '' === $description || '' === $link || '' === $image ) {
        return null;
    }

    $gtin = mdo_gmf_gtin_v1( $product, $parent );
    $mpn = mdo_gmf_mpn_v1( $product, $parent );
    $minimum = isset( $shipping['minimum_order'] ) && is_numeric( $shipping['minimum_order'] ) ? (float) $shipping['minimum_order'] : 0.0;
    $free_threshold = isset( $shipping['free_threshold'] ) && is_numeric( $shipping['free_threshold'] ) ? (float) $shipping['free_threshold'] : 0.0;

    return array(
        'id'                    => 'emdo-' . (int) $product->get_id(),
        'item_group_id'         => $product instanceof WC_Product_Variation ? 'emdo-' . (int) $parent_post->ID : '',
        'title'                 => $title,
        'description'           => $description,
        'link'                  => $link,
        'image_link'            => $image,
        'additional_images'     => mdo_gmf_additional_images_v1( $product, $parent ),
        'availability'          => mdo_gmf_availability_v1( $product ),
        'price'                 => $price,
        'sale_price'            => $sale_price,
        'effective_price'       => $effective_price,
        'brand'                 => mdo_gmf_vendor_brand_v1( $vendor_id ),
        'gtin'                  => $gtin,
        'mpn'                   => $mpn,
        'product_type'          => mdo_gmf_product_type_v1( (int) $parent_post->ID, $language ),
        'google_category'       => mdo_gmf_google_category_v1( (int) $parent_post->ID ),
        'shipping_weight'       => mdo_gmf_shipping_weight_v1( $product, $parent ),
        'shipping_cost'         => max( 0.0, (float) $shipping['cost'] ),
        'free_threshold'        => max( 0.0, $free_threshold ),
        'minimum_order'         => max( 0.0, $minimum ),
    );
}

function mdo_gmf_foreach_offer_v1( string $country, callable $callback ): int {
    $country = mdo_gmf_country_code_v1( $country );
    if ( '' === $country || ! function_exists( 'wc_get_product' ) ) {
        return 0;
    }

    $count = 0;
    foreach ( mdo_gmf_parent_ids_v1() as $parent_id ) {
        $parent_post = get_post( $parent_id );
        $parent = wc_get_product( $parent_id );
        if ( ! $parent_post instanceof WP_Post || ! $parent instanceof WC_Product || ! mdo_gmf_vendor_is_active_v1( (int) $parent_post->post_author ) ) {
            continue;
        }

        if ( $parent->is_type( 'variable' ) && $parent instanceof WC_Product_Variable ) {
            foreach ( array_map( 'absint', $parent->get_children() ) as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( ! $variation instanceof WC_Product_Variation || ! $variation->variation_is_visible() ) {
                    continue;
                }
                $offer = mdo_gmf_offer_data_v1( $variation, $parent_post, $parent, $country );
                if ( is_array( $offer ) ) {
                    ++$count;
                    $callback( $offer );
                }
            }
            continue;
        }

        $offer = mdo_gmf_offer_data_v1( $parent, $parent_post, null, $country );
        if ( is_array( $offer ) ) {
            ++$count;
            $callback( $offer );
        }
    }
    return $count;
}

function mdo_gmf_offer_count_v1( string $country ): int {
    return mdo_gmf_foreach_offer_v1( $country, static function( array $offer ): void {
        unset( $offer );
    } );
}
