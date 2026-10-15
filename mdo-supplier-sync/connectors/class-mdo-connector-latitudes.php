<?php
/** WooCommerce category connector for Latitudes. Discovery is read-only. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Connector_Latitudes {
    private const CATEGORIES = array( 'exoticos', 'premium' );
    private const MAX_PAGES = 20;

    public static function discover( array $supplier ): array {
        $host = strtolower( (string) wp_parse_url( (string) ( $supplier['source_url'] ?? '' ), PHP_URL_HOST ) );
        if ( ! in_array( $host, array( 'latitudes.es', 'www.latitudes.es' ), true ) ) {
            throw new RuntimeException( 'El conector Latitudes solo admite latitudes.es.' );
        }
        $products = array();
        $excluded = array();
        $pages = array();
        $fragments = json_decode( (string) ( $supplier['exclusion_url_fragments'] ?? '[]' ), true );
        $fragments = is_array( $fragments ) ? array_map( 'strval', $fragments ) : array();
        foreach ( self::CATEGORIES as $category ) {
            $first_url = 'https://latitudes.es/' . $category . '/';
            $first_html = self::fetch( $first_url );
            $last_page = self::page_count( $first_html, $category );
            for ( $page = 1; $page <= $last_page; $page++ ) {
                $url = 1 === $page ? $first_url : $first_url . 'page/' . $page . '/';
                $found = self::links( 1 === $page ? $first_html : self::fetch( $url ), $category );
                if ( ! $found ) {
                    throw new RuntimeException( 'Latitudes: catálogo sin fichas detectadas en ' . $url );
                }
                $pages[] = $url;
                foreach ( $found as $item ) {
                    $omit = false;
                    foreach ( $fragments as $fragment ) {
                        if ( '' !== $fragment && false !== stripos( $item, $fragment ) ) {
                            $omit = true;
                            break;
                        }
                    }
                    if ( $omit ) { $excluded[ $item ] = $item; }
                    else { $products[ $item ] = $item; }
                }
            }
        }
        if ( count( $products ) + count( $excluded ) < 60 ) {
            throw new RuntimeException( 'Latitudes: detección incompleta; se cancelan las bajas.' );
        }
        return array( 'products' => array_values( $products ), 'excluded' => array_values( $excluded ), 'pages' => $pages );
    }

    public static function scrape_product( string $url ): array {
        if ( ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ), array( 'latitudes.es', 'www.latitudes.es' ), true ) ) {
            throw new RuntimeException( 'URL ajena a Latitudes.' );
        }
        $product = MDO_Connector_Tolecarnes::scrape_product( $url );
        // Latitudes sometimes has SEO-formatted JSON-LD names instead of the
        // actual WooCommerce product heading. Keep the commercial name.
        $xpath = self::xpath( self::fetch( $url ) );
        // Confirm source availability against the visible product page, not
        // only schema.org. Never turn an unknown stock into an in-stock item.
        $visible_stock = self::visible_stock( $xpath );
        $schema_stock = (string) ( $product['stock_status'] ?? 'unknown' );
        if ( $visible_stock !== 'unknown' && $schema_stock !== 'unknown' && $visible_stock !== $schema_stock ) {
            throw new RuntimeException( 'Latitudes muestra stock contradictorio (schema=' . $schema_stock . ', html=' . $visible_stock . ') en ' . $url );
        }
        $product['stock_status'] = $visible_stock !== 'unknown' ? $visible_stock : $schema_stock;
        if ( ! in_array( $product['stock_status'], array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'Stock de Latitudes no verificable: ' . $url );
        }

        $heading = $xpath->query( "//h1[contains(concat(' ', normalize-space(@class), ' '), ' product_title ')] | //h1[contains(concat(' ', normalize-space(@class), ' '), ' entry-title ')]" );
        if ( $heading && $heading->length ) {
            $title = trim( preg_replace( '/\\s+/u', ' ', (string) $heading->item( 0 )->textContent ) );
            if ( '' !== $title ) { $product['title'] = sanitize_text_field( $title ); }
        }
        // Prefer the complete ingredients, allergens and preparation guidance
        // over the short schema.org teaser found in some product JSON-LD.
        $description = $xpath->query( "//*[@id='tab-description']" );
        if ( $description && $description->length ) {
            $node = $description->item( 0 );
            $html = '';
            foreach ( $node->childNodes as $child ) { $html .= $node->ownerDocument->saveHTML( $child ); }
            $html = wp_kses_post( trim( $html ) );
            if ( strlen( wp_strip_all_tags( $html ) ) > strlen( wp_strip_all_tags( (string) ( $product['description'] ?? '' ) ) ) ) {
                $product['description'] = $html;
            }
        }
        $hash_payload = $product;
        unset( $hash_payload['description'], $hash_payload['source_hash'] );
        $hash_payload['description_hash'] = hash( 'sha256', wp_strip_all_tags( (string) $product['description'] ) );
        $product['source_hash'] = hash( 'sha256', wp_json_encode( $hash_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
        return $product;
    }

    public static function upsert_product( int $supplier_id, array $product ): string {
        return MDO_Connector_Tolecarnes::upsert_product( $supplier_id, $product );
    }


    private static function visible_stock( DOMXPath $xpath ): string {
        // A WooCommerce product page contains related products, carousels and
        // quick-view HTML. Their stock badges do NOT describe the main item.
        // Inspect only the product's own entry-summary. If the theme does not
        // supply one, defer conservatively to the parsed product JSON-LD.
        $summary_nodes = $xpath->query(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' entry-summary ')]"
        );
        if ( ! $summary_nodes || 0 === $summary_nodes->length ) {
            return 'unknown';
        }
        $states = array();
        foreach ( $summary_nodes as $summary ) {
            $nodes = $xpath->query(
                ".//div[contains(concat(' ', normalize-space(@class), ' '), ' availability ')]" .
                " | .//div[contains(concat(' ', normalize-space(@class), ' '), ' stock ')]" .
                " | .//p[contains(concat(' ', normalize-space(@class), ' '), ' stock ')]",
                $summary
            );
            foreach ( $nodes ?: array() as $node ) {
                $class = strtolower( trim( (string) $node->getAttribute( 'class' ) ) );
                $text = mb_strtolower( trim( preg_replace( '/\\s+/u', ' ', (string) $node->textContent ) ), 'UTF-8' );
                $state = 'unknown';
                if ( preg_match( '/\\bout.of.stock\\b|agotado|sin existencias|pr[oó]ximamente|proximamente/u', $text ) ||
                    preg_match( '/\\bout-of-stock\\b/', $class ) ) {
                    $state = 'outofstock';
                } elseif ( preg_match( '/\\bin.stock\\b|\\bdisponibles?\\b|hay existencias/u', $text ) ||
                    preg_match( '/\\bin-stock\\b/', $class ) ) {
                    $state = 'instock';
                }
                if ( $state !== 'unknown' ) { $states[ $state ] = true; }
            }
        }
        if ( count( $states ) > 1 ) {
            throw new RuntimeException( 'Indicadores de stock contradictorios dentro de la ficha principal de Latitudes.' );
        }
        return $states ? (string) array_key_first( $states ) : 'unknown';
    }

    private static function fetch( string $url ): string {
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 25,
            'redirection' => 3,
            'headers' => array( 'Accept' => 'text/html,application/xhtml+xml' ),
            'user-agent' => 'EMDO/' . MDO_SUPPLIER_SYNC_VERSION . ' (https://www.elmercadodeorigen.com/)',
        ) );
        if ( is_wp_error( $response ) ) { throw new RuntimeException( $response->get_error_message() ); }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            throw new RuntimeException( 'Latitudes: HTTP ' . wp_remote_retrieve_response_code( $response ) . ' en ' . $url );
        }
        $html = (string) wp_remote_retrieve_body( $response );
        if ( strlen( $html ) < 3000 ) { throw new RuntimeException( 'Latitudes: HTML incompleto ' . $url ); }
        return $html;
    }

    private static function xpath( string $html ): DOMXPath {
        $dom = new DOMDocument();
        $old = libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $old );
        return new DOMXPath( $dom );
    }

    private static function page_count( string $html, string $category ): int {
        $xpath = self::xpath( $html );
        $max = 1;
        foreach ( $xpath->query( "//a[contains(concat(' ',normalize-space(@class),' '),' page-numbers ')]" ) ?: array() as $node ) {
            $path = (string) wp_parse_url( (string) $node->getAttribute( 'href' ), PHP_URL_PATH );
            if ( preg_match( '~^/' . preg_quote( $category, '~' ) . '/page/([0-9]+)/?$~', $path, $m ) ) {
                $max = max( $max, (int) $m[1] );
            }
        }
        if ( 1 === $max && preg_match( '/Mostrando\\s+[0-9]+[^0-9]+[0-9]+\\s+de\\s+([0-9]+)/ui', wp_strip_all_tags( $html ), $m ) ) {
            $max = (int) ceil( (int) $m[1] / 10 );
        }
        if ( $max > self::MAX_PAGES ) { throw new RuntimeException( 'Latitudes: paginación mayor que el límite seguro.' ); }
        return max( 1, $max );
    }

    private static function links( string $html, string $category ): array {
        $xpath = self::xpath( $html );
        $links = array();
        $cards = $xpath->query(
            "//li[contains(concat(' ',normalize-space(@class),' '),' product ')]" .
            " | //div[contains(concat(' ',normalize-space(@class),' '),' product-small ')]"
        );
        foreach ( $cards ?: array() as $card ) {
            // One primary link per card: category tags are also clickable.
            foreach ( $xpath->query( './/a[@href]', $card ) ?: array() as $node ) {
                $url = html_entity_decode( trim( (string) $node->getAttribute( 'href' ) ), ENT_QUOTES | ENT_HTML5 );
                if ( str_starts_with( $url, '/' ) ) { $url = 'https://latitudes.es' . $url; }
                $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
                $path = (string) wp_parse_url( $url, PHP_URL_PATH );
                if ( ! in_array( $host, array( 'latitudes.es', 'www.latitudes.es' ), true ) ||
                    ! preg_match( '~^/' . preg_quote( $category, '~' ) . '/[^/]+(?:/[^/]+)*/?$~', $path ) ||
                    preg_match( '~/page/[0-9]+/?$~', $path ) ) {
                    continue;
                }
                $canonical = 'https://latitudes.es' . trailingslashit( $path );
                $links[ $canonical ] = $canonical;
                break;
            }
        }
        return array_values( $links );
    }
}
