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
        return MDO_Connector_Tolecarnes::scrape_product( $url );
    }

    public static function upsert_product( int $supplier_id, array $product ): string {
        return MDO_Connector_Tolecarnes::upsert_product( $supplier_id, $product );
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
