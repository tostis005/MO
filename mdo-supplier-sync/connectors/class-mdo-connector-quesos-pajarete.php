<?php
/**
 * Quesos Pajarete WooCommerce connector.
 * Imports public physical catalogue, excluding fresh, tender and spreadable cheeses.
 * Never guesses a variant price or stock.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Connector_Quesos_Pajarete {
    private const HOST = 'quesospajarete.com';
    private const MAX_ARCHIVES = 90;
    private const MAX_VARIANT_COMBINATIONS = 70;

    public static function discover( array $supplier ): array {
        self::assert_origin( (string) ( $supplier['source_url'] ?? '' ) );
        $queue = array( 'https://' . self::HOST . '/tienda/' );
        $seen = array();
        $products = array();
        $excluded = array();
        $fragments = json_decode( (string) ( $supplier['exclusion_url_fragments'] ?? '[]' ), true );
        $fragments = is_array( $fragments ) ? array_values( array_filter( array_map( 'strval', $fragments ) ) ) : array();
        while ( $queue ) {
            if ( count( $seen ) >= self::MAX_ARCHIVES ) {
                throw new RuntimeException( 'Pajarete: exceso de páginas de catálogo; revisar paginación antes de sincronizar.' );
            }
            $page = array_shift( $queue );
            if ( isset( $seen[ $page ] ) ) { continue; }
            $seen[ $page ] = true;
            $xpath = self::xpath( self::fetch( $page ) );
            $page_products = array();
            foreach ( $xpath->query( '//a[@href]' ) ?: array() as $link ) {
                $url = self::canonical( (string) $link->getAttribute( 'href' ) );
                if ( ! $url ) { continue; }
                $path = (string) wp_parse_url( $url, PHP_URL_PATH );
                if ( preg_match( '~^/producto/[^/]+/$~', $path ) ) {
                    $title = '';
                    $ancestor = $link;
                    for ( $i = 0; $i < 6 && $ancestor; $i++, $ancestor = $ancestor->parentNode ) {
                        if ( ! $ancestor instanceof DOMElement ) { continue; }
                        $classes = ' ' . (string) $ancestor->getAttribute( 'class' ) . ' ';
                        if ( preg_match( '/\bproduct\b/i', $classes ) ) {
                            foreach ( $xpath->query( './/h2 | .//h3 | .//*[contains(@class,"woocommerce-loop-product__title")]', $ancestor ) ?: array() as $heading ) {
                                $title = trim( (string) $heading->textContent );
                                if ( $title ) { break; }
                            }
                            break;
                        }
                    }
                    $page_products[$url] = $title;
                }
                // Catalog archive pagination and explicit product categories only;
                // never crawl articles, user accounts or checkout.
                if ( preg_match( '~^/(?:tienda(?:/page/[0-9]+)?|categoria-producto/[a-z0-9/-]+(?:page/[0-9]+)?)/$~', $path ) &&
                    ! isset( $seen[ $url ] ) ) {
                    $queue[$url] = $url;
                }
            }
            if ( ! $page_products && $page === 'https://' . self::HOST . '/tienda/' ) {
                throw new RuntimeException( 'Pajarete: tienda sin fichas detectables; no se considera catálogo vacío.' );
            }
            foreach ( $page_products as $url => $title ) {
                $skip = self::excluded( $url, $title );
                foreach ( $fragments as $fragment ) {
                    if ( '' !== $fragment && false !== stripos( $url, $fragment ) ) { $skip = true; break; }
                }
                if ( $skip ) { $excluded[$url] = $url; unset( $products[$url] ); }
                elseif ( ! isset( $excluded[$url] ) ) { $products[$url] = $url; }
            }
        }
        if ( count( $products ) + count( $excluded ) < 20 ) {
            throw new RuntimeException( 'Pajarete: descubierto menos de 20 productos; catálogo incompleto.' );
        }
        return array( 'products' => array_values( $products ), 'excluded' => array_values( $excluded ), 'pages' => array_keys( $seen ) );
    }

    public static function excluded( string $url, string $title = '' ): bool {
        $path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
        if ( preg_match( '~/(?:fresco|queso-fresco|tiernos?|queso-tierno|untables?|crema-de-queso|cremas-de-queso|queso-para-untar|queso-crema)/?$~i', $path ) ) { return true; }
        $plain = remove_accents( strtolower( wp_strip_all_tags( $title ) ) );
        return (bool) preg_match( '/\b(?:quesos?\s+)?(?:frescos?|tiernos?|untables?)\b|\bcremas?\s+de\s+queso\b|\bquesos?\s+para\s+untar\b|\bquesos?\s+cremosos?\s+para\s+untar\b/i', $plain );
    }

    public static function scrape_product( string $url ): array {
        $canonical = self::canonical( $url );
        if ( ! $canonical || ! preg_match( '~^/producto/[^/]+/$~', (string) wp_parse_url( $canonical, PHP_URL_PATH ) ) ) {
            throw new RuntimeException( 'Pajarete: URL de producto no autorizada.' );
        }
        if ( self::excluded( $canonical ) ) { throw new RuntimeException( 'Pajarete: producto excluido por política.' ); }
        $html = self::fetch( $canonical );
        $xp = self::xpath( $html );
        $product = MDO_Connector_Tolecarnes::scrape_product( $canonical );
        if ( self::excluded( $canonical, (string) $product['title'] ) ) {
            throw new RuntimeException( 'Pajarete: producto excluido por nombre.' );
        }
        $description = $xp->query( "//*[@id='tab-description'] | //div[contains(@class,'woocommerce-Tabs-panel--description')]" );
        if ( $description && $description->length ) {
            $node = $description->item( 0 );
            $full = '';
            foreach ( $node->childNodes as $child ) { $full .= $node->ownerDocument->saveHTML( $child ); }
            $full = wp_kses_post( trim( $full ) );
            if ( mb_strlen( wp_strip_all_tags( $full ) ) > mb_strlen( wp_strip_all_tags( (string) ( $product['description'] ?? '' ) ) ) ) {
                $product['description'] = $full;
            }
        }
        $form = $xp->query( "//form[contains(concat(' ',normalize-space(@class),' '),' variations_form ')]" );
        if ( $form && $form->length ) {
            $node = $form->item( 0 );
            $raw = html_entity_decode( (string) $node->getAttribute( 'data-product_variations' ), ENT_QUOTES | ENT_HTML5 );
            $all = json_decode( $raw, true );
            if ( is_array( $all ) && $all ) {
                $product['variation_count'] = count( $all );
                if ( count( (array) ( $product['variations'] ?? array() ) ) !== count( $all ) ) {
                    throw new RuntimeException( 'Pajarete: matriz de variantes HTML incompleta.' );
                }
            } else {
                $product['variations'] = self::ajax_variations( $node, $xp );
                $product['variation_count'] = count( $product['variations'] );
            }
            if ( empty( $product['variations'] ) ) {
                throw new RuntimeException( 'Pajarete: variantes sin precios verificados; se retiene para revisión.' );
            }
            foreach ( $product['variations'] as $variation ) {
                if ( ! isset( $variation['display_price'] ) || ! is_numeric( $variation['display_price'] ) ||
                    (float) $variation['display_price'] <= 0 ) {
                    throw new RuntimeException( 'Pajarete: opción sin precio válido.' );
                }
            }
            $product['stock_status'] = array_filter( $product['variations'], static fn( $v ) => ! empty( $v['is_in_stock'] ) ) ? 'instock' : 'outofstock';
            $product['price'] = min( array_map( static fn( $v ) => (float) $v['display_price'], $product['variations'] ) );
        }
        if ( ! in_array( (string) ( $product['stock_status'] ?? '' ), array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'Pajarete: stock sin verificar.' );
        }
        $hash_data = $product;
        unset( $hash_data['source_hash'] );
        $product['source_hash'] = hash( 'sha256', wp_json_encode( $hash_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        return $product;
    }

    public static function upsert_product( int $supplier_id, array $product ): string {
        return MDO_Connector_Tolecarnes::upsert_product( $supplier_id, $product );
    }

    private static function ajax_variations( DOMNode $form, DOMXPath $xp ): array {
        $product_id = absint( (string) $form->getAttribute( 'data-product_id' ) );
        if ( ! $product_id ) { throw new RuntimeException( 'Pajarete: faltan ID de producto para variantes AJAX.' ); }
        $fields = array();
        foreach ( $xp->query( './/select[starts-with(@name,"attribute_")]', $form ) ?: array() as $select ) {
            $name = (string) $select->getAttribute( 'name' );
            $values = array();
            foreach ( $xp->query( './/option[@value]', $select ) ?: array() as $option ) {
                $value = trim( (string) $option->getAttribute( 'value' ) );
                if ( '' !== $value ) { $values[$value] = $value; }
            }
            if ( ! $values ) { throw new RuntimeException( 'Pajarete: opción de variante sin valores.' ); }
            $fields[$name] = array_values( $values );
        }
        if ( ! $fields ) { throw new RuntimeException( 'Pajarete: faltan atributos de variantes.' ); }
        $combinations = array( array() );
        foreach ( $fields as $name => $values ) {
            $expanded = array();
            foreach ( $combinations as $combination ) {
                foreach ( $values as $value ) { $expanded[] = array_merge( $combination, array( $name => $value ) ); }
            }
            $combinations = $expanded;
            if ( count( $combinations ) > self::MAX_VARIANT_COMBINATIONS ) {
                throw new RuntimeException( 'Pajarete: demasiadas combinaciones; requiere revisión manual.' );
            }
        }
        $variations = array();
        foreach ( $combinations as $attributes ) {
            $body = array_merge( array( 'product_id' => $product_id ), $attributes );
            $response = wp_remote_post( 'https://' . self::HOST . '/?wc-ajax=get_variation',
                array( 'timeout' => 20, 'body' => $body, 'headers' => array( 'Accept' => 'application/json' ) ) );
            if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
                throw new RuntimeException( 'Pajarete: no se pueden comprobar todas las variantes AJAX.' );
            }
            $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
            if ( false === $data || null === $data ) { continue; } // Combination is not sold.
            if ( ! is_array( $data ) || empty( $data['variation_id'] ) || ! isset( $data['display_price'] ) ||
                ! is_numeric( $data['display_price'] ) ) {
                throw new RuntimeException( 'Pajarete: respuesta AJAX de variantes no válida.' );
            }
            $id = absint( $data['variation_id'] );
            $variations[$id] = array(
                'variation_id' => $id,
                'attributes' => isset( $data['attributes'] ) && is_array( $data['attributes'] ) ? $data['attributes'] : $attributes,
                'display_price' => (float) $data['display_price'],
                'display_regular_price' => isset( $data['display_regular_price'] ) && is_numeric( $data['display_regular_price'] ) ? (float) $data['display_regular_price'] : (float) $data['display_price'],
                'is_in_stock' => ! empty( $data['is_in_stock'] ),
                'image' => esc_url_raw( (string) ( $data['image']['full_src'] ?? '' ) ),
            );
        }
        return array_values( $variations );
    }

    private static function assert_origin( string $url ): void {
        $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        if ( ! in_array( $host, array( self::HOST, 'www.' . self::HOST ), true ) ) {
            throw new RuntimeException( 'Pajarete: dominio no autorizado.' );
        }
    }

    private static function canonical( string $href ): string {
        $href = html_entity_decode( trim( $href ), ENT_QUOTES | ENT_HTML5 );
        if ( str_starts_with( $href, '/' ) ) { $href = 'https://' . self::HOST . $href; }
        elseif ( str_starts_with( $href, '//' ) ) { $href = 'https:' . $href; }
        if ( ! preg_match( '~^https?://~i', $href ) ) { return ''; }
        $host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
        if ( ! in_array( $host, array( self::HOST, 'www.' . self::HOST ), true ) ) { return ''; }
        $path = (string) wp_parse_url( $href, PHP_URL_PATH );
        if ( ! $path || preg_match( '~/(?:\.\.|%2e)~i', $path ) ) { return ''; }
        return esc_url_raw( 'https://' . self::HOST . trailingslashit( $path ) );
    }

    private static function fetch( string $url ): string {
        $response = wp_remote_get( $url, array( 'timeout' => 30, 'redirection' => 4,
            'user-agent' => 'EMDO/' . MDO_SUPPLIER_SYNC_VERSION . ' (+https://www.elmercadodeorigen.com/)' ) );
        if ( is_wp_error( $response ) ) { throw new RuntimeException( 'Pajarete: ' . $response->get_error_message() ); }
        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status ) { throw new RuntimeException( 'Pajarete: HTTP ' . $status . ' ' . $url ); }
        $body = (string) wp_remote_retrieve_body( $response );
        if ( strlen( $body ) < 1500 ) { throw new RuntimeException( 'Pajarete: HTML incompleto ' . $url ); }
        return $body;
    }

    private static function xpath( string $html ): DOMXPath {
        $dom = new DOMDocument();
        $before = libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET );
        libxml_clear_errors();
        libxml_use_internal_errors( $before );
        return new DOMXPath( $dom );
    }
}
