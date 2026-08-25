<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function mdo_gmf_actual_country_map_v1(): array {
    // Primary source: exactly the same map used by the storefront destination
    // selector. This keeps Merchant country feeds and customer-visible choices
    // in lockstep.
    if ( function_exists( 'mdo_catalog_allowed_map_v060' ) ) {
        $selector = (array) mdo_catalog_allowed_map_v060();
        $result = array();
        foreach ( $selector as $code => $name ) {
            $code = mdo_gmf_country_code_v1( (string) $code );
            if ( '' !== $code ) {
                $result[ $code ] = mdo_gmf_clean_text_v1( $name, 100 );
            }
        }
        if ( ! empty( $result ) ) {
            return $result;
        }
    }

    // Fallback for installations where the selector module is unavailable:
    // derive destinations from active producers that own public products.
    $vendors = array();
    foreach ( mdo_gmf_parent_ids_v1() as $product_id ) {
        $post = get_post( $product_id );
        if ( $post instanceof WP_Post && mdo_gmf_vendor_is_active_v1( (int) $post->post_author ) ) {
            $vendors[] = (int) $post->post_author;
        }
    }

    $names = mdo_gmf_country_names_v1();
    $result = array();
    foreach ( array_unique( $vendors ) as $vendor_id ) {
        foreach ( mdo_gmf_vendor_country_codes_v1( $vendor_id ) as $country ) {
            $name = $names[ $country ] ?? '';
            if ( '' === $name && function_exists( 'mdo_sst_country_name' ) ) {
                $name = mdo_sst_country_name( $country );
            }
            $result[ $country ] = '' !== trim( (string) $name ) ? (string) $name : $country;
        }
    }
    ksort( $result );
    return $result;
}

function mdo_gmf_render_xml_item_v1( array $offer, string $country ): void {
    echo "    <item>\n";
    foreach ( array(
        'id'           => $offer['id'],
        'title'        => $offer['title'],
        'description'  => $offer['description'],
        'link'         => $offer['link'],
        'image_link'   => $offer['image_link'],
        'availability' => $offer['availability'],
        'condition'    => 'new',
        'price'        => $offer['price'],
    ) as $tag => $value ) {
        echo '      <g:' . $tag . '>' . mdo_gmf_xml_v1( $value ) . '</g:' . $tag . ">\n";
    }

    if ( ! empty( $offer['sale_price'] ) ) {
        echo '      <g:sale_price>' . mdo_gmf_xml_v1( $offer['sale_price'] ) . "</g:sale_price>\n";
    }
    foreach ( (array) $offer['additional_images'] as $url ) {
        if ( $url !== $offer['image_link'] ) {
            echo '      <g:additional_image_link>' . mdo_gmf_xml_v1( $url ) . "</g:additional_image_link>\n";
        }
    }
    foreach ( array(
        'brand'                   => $offer['brand'],
        'gtin'                    => $offer['gtin'],
        'mpn'                     => $offer['mpn'],
        'item_group_id'           => $offer['item_group_id'],
        'product_type'            => $offer['product_type'],
        'google_product_category' => $offer['google_category'],
        'shipping_weight'         => $offer['shipping_weight'],
    ) as $tag => $value ) {
        if ( '' !== (string) $value ) {
            echo '      <g:' . $tag . '>' . mdo_gmf_xml_v1( $value ) . '</g:' . $tag . ">\n";
        }
    }

    echo "      <g:shipping>\n";
    echo '        <g:country>' . mdo_gmf_xml_v1( $country ) . "</g:country>\n";
    echo "        <g:service>Standard</g:service>\n";
    echo '        <g:price>' . mdo_gmf_xml_v1( mdo_gmf_money_v1( $offer['shipping_cost'] ) ) . "</g:price>\n";
    echo "      </g:shipping>\n";

    if ( (float) $offer['free_threshold'] > 0 && (float) $offer['shipping_cost'] > 0 ) {
        echo "      <g:free_shipping_threshold>\n";
        echo '        <g:country>' . mdo_gmf_xml_v1( $country ) . "</g:country>\n";
        echo '        <g:price_threshold>' . mdo_gmf_xml_v1( mdo_gmf_money_v1( $offer['free_threshold'] ) ) . "</g:price_threshold>\n";
        echo "      </g:free_shipping_threshold>\n";
    }

    // Google requires minimum_order_value only when the individual offer price
    // does not already satisfy the producer minimum by itself.
    if ( (float) $offer['minimum_order'] > 0 && (float) $offer['effective_price'] <= (float) $offer['minimum_order'] + 0.0001 ) {
        echo "      <g:minimum_order_value>\n";
        echo '        <g:country>' . mdo_gmf_xml_v1( $country ) . "</g:country>\n";
        echo "        <g:service>Standard</g:service>\n";
        echo "        <g:surface>online</g:surface>\n";
        echo '        <g:price>' . mdo_gmf_xml_v1( mdo_gmf_money_v1( $offer['minimum_order'] ) ) . "</g:price>\n";
        echo "      </g:minimum_order_value>\n";
    }

    echo "    </item>\n";
}

function mdo_gmf_country_is_known_v1( string $country ): bool {
    $country = mdo_gmf_country_code_v1( $country );
    if ( '' === $country ) {
        return false;
    }
    $allowed = mdo_gmf_country_names_v1();
    if ( isset( $allowed[ $country ] ) ) {
        return true;
    }
    foreach ( mdo_gmf_parent_ids_v1() as $product_id ) {
        $post = get_post( $product_id );
        if ( ! $post instanceof WP_Post || ! mdo_gmf_vendor_is_active_v1( (int) $post->post_author ) ) {
            continue;
        }
        if ( in_array( $country, mdo_gmf_vendor_country_codes_v1( (int) $post->post_author ), true ) ) {
            return true;
        }
    }
    return false;
}

function mdo_gmf_render_feed_v1( string $country ): void {
    $country = mdo_gmf_country_code_v1( $country );
    $allowed = mdo_gmf_country_names_v1();
    if ( '' === $country || ! mdo_gmf_country_is_known_v1( $country ) ) {
        status_header( 404 );
        header( 'Content-Type: text/plain; charset=UTF-8' );
        echo 'Unknown EMDO shipping country';
        exit;
    }
    if ( ! function_exists( 'wc_get_product' ) ) {
        status_header( 503 );
        header( 'Content-Type: text/plain; charset=UTF-8' );
        echo 'WooCommerce unavailable';
        exit;
    }

    while ( ob_get_level() > 0 ) {
        @ob_end_clean();
    }

    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'Content-Disposition: inline; filename="emdo-google-' . strtolower( $country ) . '.xml"' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
    header( 'X-Robots-Tag: noindex, nofollow', true );

    $language = mdo_gmf_feed_language_v1( $country );
    $country_name = isset( $allowed[ $country ] ) ? mdo_gmf_clean_text_v1( $allowed[ $country ], 100 ) : '';
    if ( '' === $country_name && function_exists( 'mdo_sst_country_name' ) ) {
        $country_name = mdo_gmf_clean_text_v1( mdo_sst_country_name( $country ), 100 );
    }
    if ( '' === $country_name ) {
        $country_name = $country;
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
    echo "  <channel>\n";
    echo '    <title>' . mdo_gmf_xml_v1( 'El Mercado de Origen - Google Shopping ' . $country_name ) . "</title>\n";
    echo '    <link>' . mdo_gmf_xml_v1( home_url( 'en' === $language ? '/en/shop/' : '/tienda/' ) ) . "</link>\n";
    echo '    <description>' . mdo_gmf_xml_v1( 'es' === $language ? 'Catálogo de productos disponibles para envío a ' . $country_name : 'Products available for delivery to ' . $country_name ) . "</description>\n";

    mdo_gmf_foreach_offer_v1( $country, static function( array $offer ) use ( $country ): void {
        mdo_gmf_render_xml_item_v1( $offer, $country );
    } );

    echo "  </channel>\n";
    echo "</rss>";

    $last_key = 'mdo_gmf_last_fetch_' . $country;
    $previous = (int) get_option( $last_key, 0 );
    if ( time() - $previous > HOUR_IN_SECONDS ) {
        update_option( $last_key, time(), false );
    }
    exit;
}

add_action( 'parse_request', static function(): void {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( preg_match( '#^/emdo-feed/([a-z]{2})\.xml/?$#i', $path, $matches ) ) {
        mdo_gmf_render_feed_v1( strtoupper( $matches[1] ) );
    }
}, -25000 );
