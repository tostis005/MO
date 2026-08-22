<?php
/**
 * Plugin Name: MDO Pinterest Catalog Feed
 * Description: Serves a live Pinterest-compatible retail catalog generated from the public WooCommerce catalog.
 * Version: 2026.08.21.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mdo_pinterest_feed_clean_text( $value, int $max_length = 10000 ): string {
    $value = html_entity_decode( wp_strip_all_tags( (string) $value, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = preg_replace( '/\s+/u', ' ', $value );
    $value = trim( (string) $value );
    if ( function_exists( 'mb_substr' ) ) {
        return mb_substr( $value, 0, $max_length, 'UTF-8' );
    }
    return substr( $value, 0, $max_length );
}

function mdo_pinterest_feed_vendor_brand( int $user_id ): string {
    if ( $user_id <= 0 ) {
        return 'El Mercado de Origen';
    }

    $settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
    if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
        $name = mdo_pinterest_feed_clean_text( $settings['store_name'], 100 );
        if ( '' !== $name ) {
            return $name;
        }
    }

    $user = get_userdata( $user_id );
    if ( $user instanceof WP_User ) {
        $name = mdo_pinterest_feed_clean_text( $user->display_name, 100 );
        if ( '' !== $name ) {
            return $name;
        }
    }

    return 'El Mercado de Origen';
}

function mdo_pinterest_feed_product_type( int $product_id ): string {
    $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }
    $terms = array_values( array_unique( array_filter( array_map( static function ( $term ) {
        return mdo_pinterest_feed_clean_text( $term, 150 );
    }, (array) $terms ) ) ) );
    return implode( ' > ', array_slice( $terms, 0, 5 ) );
}

function mdo_pinterest_feed_image_url( WC_Product $product, ?WC_Product $parent = null ): string {
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

function mdo_pinterest_feed_additional_images( WC_Product $product, ?WC_Product $parent = null ): string {
    $ids = array_map( 'intval', (array) $product->get_gallery_image_ids() );
    if ( empty( $ids ) && $parent instanceof WC_Product ) {
        $ids = array_map( 'intval', (array) $parent->get_gallery_image_ids() );
    }

    $urls = array();
    foreach ( array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, 10 ) as $image_id ) {
        $url = wp_get_attachment_image_url( $image_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            $urls[] = esc_url_raw( $url );
        }
    }
    return implode( ',', $urls );
}

function mdo_pinterest_feed_availability( WC_Product $product ): string {
    $status = (string) $product->get_stock_status();
    if ( 'instock' === $status ) {
        return 'in stock';
    }
    if ( 'onbackorder' === $status ) {
        return 'preorder';
    }
    return 'out of stock';
}

function mdo_pinterest_feed_price_fields( WC_Product $product ): array {
    $current = (float) wc_get_price_to_display( $product, array( 'price' => (float) $product->get_price() ) );
    $regular_raw = $product->get_regular_price();
    $sale_raw = $product->get_sale_price();

    $regular = '' !== $regular_raw
        ? (float) wc_get_price_to_display( $product, array( 'price' => (float) $regular_raw ) )
        : $current;
    $sale = '' !== $sale_raw
        ? (float) wc_get_price_to_display( $product, array( 'price' => (float) $sale_raw ) )
        : 0.0;

    if ( $regular <= 0 && $current > 0 ) {
        $regular = $current;
    }

    $price = $regular > 0 ? number_format( $regular, 2, '.', '' ) . ' EUR' : '';
    $sale_price = ( $sale > 0 && $regular > 0 && $sale < $regular )
        ? number_format( $sale, 2, '.', '' ) . ' EUR'
        : '';

    return array( $price, $sale_price );
}

function mdo_pinterest_feed_description( WC_Product $product, ?WC_Product $parent = null ): string {
    $description = $product->get_short_description();
    if ( '' === trim( (string) $description ) ) {
        $description = $product->get_description();
    }
    if ( '' === trim( (string) $description ) && $parent instanceof WC_Product ) {
        $description = $parent->get_short_description();
        if ( '' === trim( (string) $description ) ) {
            $description = $parent->get_description();
        }
    }
    $description = mdo_pinterest_feed_clean_text( $description, 10000 );
    if ( '' === $description ) {
        $description = mdo_pinterest_feed_clean_text( $product->get_name(), 500 );
    }
    return $description;
}

function mdo_pinterest_feed_variant_title( WC_Product_Variation $variation, WC_Product $parent ): string {
    $title = $parent->get_name();
    $attributes = array();
    foreach ( $variation->get_attributes() as $taxonomy => $value ) {
        if ( '' === (string) $value ) {
            continue;
        }
        $label = wc_attribute_label( str_replace( 'attribute_', '', (string) $taxonomy ), $parent );
        $display = (string) $value;
        if ( taxonomy_exists( (string) $taxonomy ) ) {
            $term = get_term_by( 'slug', (string) $value, (string) $taxonomy );
            if ( $term instanceof WP_Term ) {
                $display = $term->name;
            }
        }
        $attributes[] = trim( (string) $label . ': ' . $display );
    }
    if ( ! empty( $attributes ) ) {
        $title .= ' - ' . implode( ', ', $attributes );
    }
    return mdo_pinterest_feed_clean_text( $title, 500 );
}

function mdo_pinterest_feed_build_row( WC_Product $product, WP_Post $parent_post, ?WC_Product $parent_product = null, string $item_group_id = '' ): ?array {
    if ( ! $product->exists() ) {
        return null;
    }

    list( $price, $sale_price ) = mdo_pinterest_feed_price_fields( $product );
    if ( '' === $price ) {
        return null;
    }

    $image = mdo_pinterest_feed_image_url( $product, $parent_product );
    if ( '' === $image ) {
        return null;
    }

    $link = $product->get_permalink();
    if ( ! is_string( $link ) || '' === $link ) {
        return null;
    }

    $is_variation = $product instanceof WC_Product_Variation;
    $title = $is_variation && $parent_product instanceof WC_Product
        ? mdo_pinterest_feed_variant_title( $product, $parent_product )
        : mdo_pinterest_feed_clean_text( $product->get_name(), 500 );

    if ( '' === $title ) {
        return null;
    }

    return array(
        'id'                    => 'emdo-' . (int) $product->get_id(),
        'title'                 => $title,
        'description'           => mdo_pinterest_feed_description( $product, $parent_product ),
        'link'                  => esc_url_raw( $link ),
        'image_link'            => $image,
        'price'                 => $price,
        'availability'          => mdo_pinterest_feed_availability( $product ),
        'item_group_id'         => $item_group_id,
        'brand'                 => mdo_pinterest_feed_vendor_brand( (int) $parent_post->post_author ),
        'condition'             => 'new',
        'product_type'          => mdo_pinterest_feed_product_type( (int) $parent_post->ID ),
        'sale_price'            => $sale_price,
        'additional_image_link' => mdo_pinterest_feed_additional_images( $product, $parent_product ),
    );
}

function mdo_pinterest_feed_parent_ids(): array {
    if ( function_exists( 'emdo_cleanup_eligible_product_ids' ) ) {
        return array_map( 'intval', (array) emdo_cleanup_eligible_product_ids() );
    }

    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ) );
    return array_map( 'intval', (array) $ids );
}

function mdo_pinterest_feed_render_csv(): void {
    if ( ! function_exists( 'wc_get_product' ) ) {
        status_header( 503 );
        header( 'Content-Type: text/plain; charset=UTF-8' );
        echo 'WooCommerce unavailable';
        exit;
    }

    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: inline; filename="emdo-pinterest-catalog.csv"' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
    header( 'X-Robots-Tag: noindex, nofollow', true );

    $columns = array(
        'id',
        'title',
        'description',
        'link',
        'image_link',
        'price',
        'availability',
        'item_group_id',
        'brand',
        'condition',
        'product_type',
        'sale_price',
        'additional_image_link',
    );

    $output = fopen( 'php://output', 'w' );
    if ( false === $output ) {
        status_header( 500 );
        exit;
    }

    // UTF-8 BOM keeps accented Spanish product names intact in spreadsheet tools.
    fwrite( $output, "\xEF\xBB\xBF" );
    fputcsv( $output, $columns );

    foreach ( mdo_pinterest_feed_parent_ids() as $parent_id ) {
        $parent_post = get_post( $parent_id );
        $parent = wc_get_product( $parent_id );
        if ( ! $parent_post instanceof WP_Post || ! $parent instanceof WC_Product ) {
            continue;
        }

        if ( $parent->is_type( 'variable' ) && $parent instanceof WC_Product_Variable ) {
            $group_id = 'emdo-' . $parent_id;
            foreach ( array_map( 'intval', $parent->get_children() ) as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( ! $variation instanceof WC_Product_Variation || ! $variation->variation_is_visible() ) {
                    continue;
                }
                $row = mdo_pinterest_feed_build_row( $variation, $parent_post, $parent, $group_id );
                if ( is_array( $row ) ) {
                    fputcsv( $output, array_values( $row ) );
                }
            }
            continue;
        }

        $row = mdo_pinterest_feed_build_row( $parent, $parent_post );
        if ( is_array( $row ) ) {
            fputcsv( $output, array_values( $row ) );
        }
    }

    fclose( $output );
    exit;
}

add_action( 'parse_request', static function (): void {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( '/pinterest-catalog.csv' === rtrim( $path, '/' ) ) {
        mdo_pinterest_feed_render_csv();
    }
}, -20000 );
