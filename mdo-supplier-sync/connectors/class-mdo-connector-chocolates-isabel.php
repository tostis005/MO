<?php
/** Chocolates Artesanos Isabel: read-only WooCommerce catalog and product connector. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Connector_Chocolates_Isabel {
    private const HOST = 'chocolatesartesanosisabel.com';
    private const MAX_PAGES = 40;
    private const MIN_DISCOVERY = 20;

    public static function discover( array $supplier ): array {
        self::assert_host( (string) ( $supplier['source_url'] ?? '' ) );
        $products = array();
        $excluded = array();
        $pages = array();
        $fragments = json_decode( (string) ( $supplier['exclusion_url_fragments'] ?? '[]' ), true );
        $fragments = is_array( $fragments ) ? array_filter( array_map( 'strval', $fragments ) ) : array();

        // /tienda/ is a category index, NOT a conventional Woo product archive.
        // Crawl every /categoria-producto/<slug>/ archive and its pagination;
        // dedupe products appearing in multiple merchandising categories.
        $landing = self::xpath( self::fetch( 'https://' . self::HOST . '/tienda/' ) );
        $archives = array();
        foreach ( $landing->query( '//a[@href]' ) ?: array() as $link ) {
            $href = html_entity_decode( trim( (string) $link->getAttribute( 'href' ) ), ENT_QUOTES | ENT_HTML5 );
            if ( str_starts_with( $href, '/' ) ) { $href = 'https://' . self::HOST . $href; }
            $host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
            $path = (string) wp_parse_url( $href, PHP_URL_PATH );
            if ( in_array( $host, array( self::HOST, 'www.' . self::HOST ), true ) &&
                preg_match( '~^/categoria-producto/[^/]+/?$~', $path ) ) {
                $archives[ $path ] = 'https://' . self::HOST . trailingslashit( $path );
            }
        }
        if ( count( $archives ) < 5 ) {
            throw new RuntimeException( 'Isabel: índice de categorías incompleto; se cancela la conciliación.' );
        }

        foreach ( $archives as $archive ) {
            $first_html = self::fetch( $archive );
            $count = self::page_count( $first_html, (string) wp_parse_url( $archive, PHP_URL_PATH ) );
            for ( $page = 1; $page <= $count; $page++ ) {
                $url = $page === 1 ? $archive : $archive . 'page/' . $page . '/';
                $html = $page === 1 ? $first_html : self::fetch( $url );
                $links = self::catalog_links( $html );
                if ( ! $links ) {
                    throw new RuntimeException( 'Isabel: página de categoría sin fichas de producto: ' . $url );
                }
                $pages[] = $url;
                foreach ( $links as $link ) {
                    $skip = false;
                    foreach ( $fragments as $fragment ) {
                        if ( '' !== $fragment && false !== stripos( $link, $fragment ) ) { $skip = true; break; }
                    }
                    if ( $skip ) { $excluded[ $link ] = $link; }
                    else { $products[ $link ] = $link; }
                }
            }
        }
        if ( count( $products ) + count( $excluded ) < self::MIN_DISCOVERY ) {
            throw new RuntimeException( 'Isabel: catálogo incompleto; se cancelan las bajas.' );
        }
        return array( 'products' => array_values( $products ), 'excluded' => array_values( $excluded ), 'pages' => $pages );
    }

    public static function scrape_product( string $url ): array {
        self::assert_host( $url );
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        if ( ! preg_match( '~^/producto/[^/]+/?$~', $path ) ) {
            throw new RuntimeException( 'Isabel: la URL no es una ficha individual.' );
        }
        $canonical = 'https://' . self::HOST . trailingslashit( $path );
        $html = self::fetch( $canonical );
        $xpath = self::xpath( $html );
        $product = MDO_Connector_Tolecarnes::scrape_product( $canonical );
        $headings = $xpath->query( "//h1[contains(concat(' ',normalize-space(@class),' '),' product_title ')] | //h1[contains(concat(' ',normalize-space(@class),' '),' entry-title ')]" );
        if ( $headings && $headings->length ) {
            $title = trim( preg_replace( '/\s+/u', ' ', (string) $headings->item( 0 )->textContent ) );
            if ( $title ) { $product['title'] = sanitize_text_field( $title ); }
        }
        $description = $xpath->query( "//*[@id='tab-description'] | //div[contains(concat(' ',normalize-space(@class),' '),' woocommerce-Tabs-panel--description ')]" );
        if ( $description && $description->length ) {
            $node = $description->item( 0 );
            $full = '';
            foreach ( $node->childNodes as $child ) { $full .= $node->ownerDocument->saveHTML( $child ); }
            $full = wp_kses_post( trim( $full ) );
            if ( mb_strlen( wp_strip_all_tags( $full ) ) > mb_strlen( wp_strip_all_tags( (string) ( $product['description'] ?? '' ) ) ) ) {
                $product['description'] = $full;
            }
        }

        $visible = self::visible_stock( $xpath );
        $schema = (string) ( $product['stock_status'] ?? 'unknown' );
        if ( 'unknown' !== $visible && 'unknown' !== $schema && $visible !== $schema ) {
            throw new RuntimeException( 'Isabel: stock contradictorio entre ficha y datos estructurados en ' . $canonical );
        }
        $stock = 'unknown' !== $visible ? $visible : $schema;
        if ( ! in_array( $stock, array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'Isabel: stock no verificable; producto retenido en revisión: ' . $canonical );
        }
        $product['stock_status'] = $stock;

        // AJAX-only WooCommerce variable products do not expose the full
        // price/stock matrix in HTML. Block importing an incomplete simple item.
        $variation_forms = $xpath->query( "//form[contains(concat(' ',normalize-space(@class),' '),' variations_form ')]" );
        if ( $variation_forms && $variation_forms->length && empty( $product['variations'] ) ) {
            $product['variation_count'] = 2;
        }
        // Customisable/personalised items also require human review.
        $custom = $xpath->query( "//form[contains(concat(' ',normalize-space(@class),' '),' cart ')]//input[@type='file'] | //form[contains(concat(' ',normalize-space(@class),' '),' cart ')]//textarea" );
        if ( $custom && $custom->length ) {
            $product['variation_count'] = max( 2, (int) ( $product['variation_count'] ?? 0 ) );
            $product['variations'] = array();
        }
        $product['source_url'] = $canonical;
        $data = $product;
        unset( $data['source_hash'], $data['description'] );
        $data['description_hash'] = hash( 'sha256', wp_strip_all_tags( (string) ( $product['description'] ?? '' ) ) );
        $product['source_hash'] = hash( 'sha256', wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        return $product;
    }

    public static function upsert_product( int $supplier_id, array $product ): string {
        return MDO_Connector_Tolecarnes::upsert_product( $supplier_id, $product );
    }

    private static function assert_host( string $url ): void {
        $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        if ( ! in_array( $host, array( self::HOST, 'www.' . self::HOST ), true ) ) {
            throw new RuntimeException( 'Isabel: dominio de origen no autorizado.' );
        }
    }

    private static function fetch( string $url ): string {
        self::assert_host( $url );
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 25, 'redirection' => 3,
            'headers' => array( 'Accept' => 'text/html,application/xhtml+xml' ),
            'user-agent' => 'EMDO/' . MDO_SUPPLIER_SYNC_VERSION . ' (https://www.elmercadodeorigen.com/)',
        ) );
        if ( is_wp_error( $response ) ) { throw new RuntimeException( 'Isabel: ' . $response->get_error_message() ); }
        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status ) { throw new RuntimeException( 'Isabel: HTTP ' . $status . ' en ' . $url ); }
        $html = (string) wp_remote_retrieve_body( $response );
        if ( strlen( $html ) < 3500 ) { throw new RuntimeException( 'Isabel: HTML incompleto en ' . $url ); }
        return $html;
    }

    private static function xpath( string $html ): DOMXPath {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return new DOMXPath( $dom );
    }

    private static function page_count( string $html, string $archive_path ): int {
        $max = 1;
        $xpath = self::xpath( $html );
        $base = trailingslashit( $archive_path );
        foreach ( $xpath->query( "//a[contains(concat(' ',normalize-space(@class),' '),' page-numbers ')]" ) ?: array() as $node ) {
            $path = (string) wp_parse_url( (string) $node->getAttribute( 'href' ), PHP_URL_PATH );
            if ( preg_match( '~^' . preg_quote( $base, '~' ) . 'page/(\d+)/?$~', $path, $m ) ) {
                $max = max( $max, (int) $m[1] );
            }
        }
        if ( 1 === $max && preg_match( '/Mostrando\s+[0-9]+\s*[–\-]\s*[0-9]+\s+de\s+([0-9]+)\s+resultados/ui', wp_strip_all_tags( $html ), $m ) ) {
            $max = (int) ceil( (int) $m[1] / 12 );
        }
        if ( $max > self::MAX_PAGES ) { throw new RuntimeException( 'Isabel: número de páginas inesperado.' ); }
        return max( 1, $max );
    }

    private static function catalog_links( string $html ): array {
        $xpath = self::xpath( $html );
        $urls = array();
        $cards = $xpath->query( "//li[contains(concat(' ',normalize-space(@class),' '),' product ')] | //div[contains(concat(' ',normalize-space(@class),' '),' product ')]" );
        foreach ( $cards ?: array() as $card ) {
            foreach ( $xpath->query( ".//a[@href]", $card ) ?: array() as $anchor ) {
                $href = html_entity_decode( (string) $anchor->getAttribute( 'href' ), ENT_QUOTES | ENT_HTML5 );
                if ( str_starts_with( $href, '/' ) ) { $href = 'https://' . self::HOST . $href; }
                $host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
                $path = (string) wp_parse_url( $href, PHP_URL_PATH );
                if ( ! in_array( $host, array( self::HOST, 'www.' . self::HOST ), true ) ||
                    ! preg_match( '~^/producto/[^/]+/?$~', $path ) ) { continue; }
                $canonical = 'https://' . self::HOST . trailingslashit( $path );
                $urls[ $canonical ] = $canonical;
                break;
            }
        }
        return array_values( $urls );
    }

    private static function visible_stock( DOMXPath $xpath ): string {
        $scope = $xpath->query( "//div[contains(concat(' ',normalize-space(@class),' '),' summary ')] | //div[contains(concat(' ',normalize-space(@class),' '),' entry-summary ')]" );
        if ( ! $scope || ! $scope->length ) { return 'unknown'; }
        $statuses = array();
        foreach ( $scope as $summary ) {
            foreach ( $xpath->query( ".//*[contains(concat(' ',normalize-space(@class),' '),' stock ')] | .//*[contains(concat(' ',normalize-space(@class),' '),' availability ')]", $summary ) ?: array() as $node ) {
                $c = strtolower( (string) $node->getAttribute( 'class' ) );
                $t = mb_strtolower( trim( (string) $node->textContent ), 'UTF-8' );
                if ( preg_match( '/out.of.stock|agotad|sin existencias|no disponible/u', $c . ' ' . $t ) ) { $statuses['outofstock'] = true; }
                elseif ( preg_match( '/in.stock|disponible|hay existencias|en stock/u', $c . ' ' . $t ) ) { $statuses['instock'] = true; }
            }
        }
        if ( count( $statuses ) > 1 ) { throw new RuntimeException( 'Isabel: indicadores de stock contradictorios.' ); }
        return $statuses ? (string) array_key_first( $statuses ) : 'unknown';
    }
}
