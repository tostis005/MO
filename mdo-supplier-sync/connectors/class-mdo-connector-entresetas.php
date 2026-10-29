<?php
/** EntreSetas: bounded, read-only WooCommerce catalog connector. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Connector_EntreSetas {
    private const HOST = 'www.entresetas.com';
    private const MIN_PRODUCTS = 20;
    private const MAX_PRODUCTS = 100;

    private static function check_url( string $url, bool $product = false ): string {
        $parts = wp_parse_url( $url );
        $host = strtolower( (string) ( $parts['host'] ?? '' ) );
        $scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
        $path = (string) ( $parts['path'] ?? '' );
        if ( 'https' !== $scheme || ! in_array( $host, array( 'entresetas.com', self::HOST ), true ) ||
            ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) || ! empty( $parts['port'] ) ) {
            throw new RuntimeException( 'EntreSetas: dominio o protocolo no autorizado.' );
        }
        if ( $product && ! preg_match( '~^/product/[a-z0-9-]+/?$~i', $path ) ) {
            throw new RuntimeException( 'EntreSetas: no es una ficha individual válida.' );
        }
        if ( ! $product && ! in_array( rtrim( $path, '/' ), array( '/tienda', '/product-category/fresco',
            '/product-category/cocinado', '/product-category/deshidratados', '/product-category/infusiones-y-tes',
            '/product-category/setas-medicinales' ), true ) ) {
            throw new RuntimeException( 'EntreSetas: archivo fuera del ámbito autorizado.' );
        }
        return 'https://' . self::HOST . trailingslashit( $path );
    }

    private static function fetch( string $url ): string {
        $response = wp_safe_remote_get( $url, array(
            'timeout' => 25, 'redirection' => 2,
            'user-agent' => 'EMDO/' . MDO_SUPPLIER_SYNC_VERSION . ' (https://www.elmercadodeorigen.com/)',
            'headers' => array( 'Accept' => 'text/html,application/xhtml+xml' ),
        ) );
        if ( is_wp_error( $response ) ) { throw new RuntimeException( 'EntreSetas: ' . $response->get_error_message() ); }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            throw new RuntimeException( 'EntreSetas: HTTP ' . wp_remote_retrieve_response_code( $response ) . ' en ' . $url );
        }
        $html = (string) wp_remote_retrieve_body( $response );
        if ( strlen( $html ) < 2500 ) { throw new RuntimeException( 'EntreSetas: página incompleta ' . $url ); }
        return $html;
    }


    /** Product's own entry summary OR its standalone WooCommerce cart form. */
    private static function product_scope( DOMXPath $xp ): ?DOMNode {
        $summary = $xp->query(
            '//div[contains(concat(" ",normalize-space(@class)," ")," entry-summary ")]' .
            ' | //div[contains(concat(" ",normalize-space(@class)," ")," summary ")]' .
            ' | //section[contains(concat(" ",normalize-space(@class)," ")," summary ")]'
        );
        if ( $summary && $summary->length ) { return $summary->item(0); }
        // Elementor product pages place form.cart directly within a widget.
        $forms = $xp->query( '//form[contains(concat(" ",normalize-space(@class)," ")," cart ")]' );
        if ( $forms && $forms->length ) { return $forms->item(0); }
        return null;
    }


    /**
     * Strictly accept the producer's own displayed euro price, not the first
     * generic Woo price (which could belong to an unrelated recommendation).
     * Under-order truffles have no price and must remain out of automation.
     */
    private static function visible_price( DOMXPath $xp ): ?float {
        $nodes = $xp->query(
            '//div[contains(@class,"elementor-widget-woocommerce-product-price")]' .
            ' | //p[contains(concat(" ",normalize-space(@class)," ")," price ")][not(ancestor::*[contains(@class,"related")])]'
        );
        foreach ( $nodes ?: array() as $node ) {
            $value = self::euros( (string) $node->textContent, false );
            if ( null !== $value ) { return $value; }
        }
        // EntreSetas custom Elementor product layout displays e.g. "€6,50"
        // in the FIRST h4 following its h1 (before any related product cards).
        $heading = $xp->query( '(//h1)[1]/following::h4[1]' );
        if ( $heading && $heading->length ) {
            return self::euros( (string) $heading->item(0)->textContent, true );
        }
        return null;
    }

    private static function euros( string $text, bool $one_heading ): ?float {
        $text = trim( preg_replace( '/\s+/u', ' ', str_replace( "\xC2\xA0", ' ', $text ) ) );
        if ( ! str_contains( $text, '€' ) ) { return null; }
        if ( $one_heading && ! preg_match( '/^\s*(?:€\s*)?[0-9]+(?:[.,][0-9]{2})\s*€?\s*$/u', $text ) ) {
            return null;
        }
        if ( ! preg_match_all( '/[0-9]+(?:[.,][0-9]{2})/u', $text, $m ) || empty($m[0]) ) {
            return null;
        }
        $price = (float) str_replace( ',', '.', (string) end($m[0]) );
        return $price > 0 && $price < 100000 ? $price : null;
    }

    private static function xpath( string $html ): DOMXPath {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors(); libxml_use_internal_errors( $prev );
        return new DOMXPath( $dom );
    }

    private static function href( string $raw ): string {
        $raw = html_entity_decode( trim( $raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        if ( str_starts_with( $raw, '/' ) && ! str_starts_with( $raw, '//' ) ) {
            $raw = 'https://' . self::HOST . $raw;
        }
        $host = strtolower( (string) wp_parse_url( $raw, PHP_URL_HOST ) );
        $path = (string) wp_parse_url( $raw, PHP_URL_PATH );
        if ( ! in_array( $host, array( self::HOST, 'entresetas.com' ), true ) ||
            ! preg_match( '~^/product/[a-z0-9-]+/?$~i', $path ) ) { return ''; }
        return 'https://' . self::HOST . trailingslashit( $path );
    }

    public static function discover( array $supplier ): array {
        self::check_url( (string) ( $supplier['source_url'] ?? '' ) );
        $url = 'https://' . self::HOST . '/tienda/';
        $xpath = self::xpath( self::fetch( $url ) );
        $products = array();
        $excluded = array();
        $fragments = json_decode( (string) ( $supplier['exclusion_url_fragments'] ?? '[]' ), true );
        $fragments = is_array( $fragments ) ? array_map( 'strval', $fragments ) : array();
        foreach ( $xpath->query( '//a[@href]' ) ?: array() as $node ) {
            $link = self::href( (string) $node->getAttribute( 'href' ) );
            if ( ! $link ) { continue; }
            $skip = false;
            foreach ( $fragments as $fragment ) {
                if ( '' !== $fragment && stripos( $link, $fragment ) !== false ) { $skip = true; break; }
            }
            if ( $skip ) { $excluded[$link] = $link; }
            else { $products[$link] = $link; }
        }
        $count = count( $products ) + count( $excluded );
        if ( $count < self::MIN_PRODUCTS || $count > self::MAX_PRODUCTS ) {
            throw new RuntimeException( 'EntreSetas: índice incompleto o inesperado (' . $count . '); no se concilia el catálogo.' );
        }
        return array( 'products' => array_values($products), 'excluded' => array_values($excluded), 'pages' => array($url) );
    }

    public static function scrape_product( string $url ): array {
        $url = self::check_url( $url, true );
        $html = self::fetch( $url );
        $xp = self::xpath( $html );
        // Reuse the generic WooCommerce JSON-LD parser, then check the actual
        // product summary (never use recommendations for stock or descriptions).
        $product = MDO_Connector_Tolecarnes::scrape_product( $url );
        $h1 = $xp->query( '//h1[contains(concat(" ",normalize-space(@class)," ")," product_title ")] | //h1[1]' );
        if ( $h1 && $h1->length ) {
            $name = trim( preg_replace( '/\s+/u', ' ', (string) $h1->item(0)->textContent ) );
            if ( $name ) { $product['title'] = sanitize_text_field( $name ); }
        }
        // EntreSetas uses Elementor for several product templates. Its
        // WooCommerce cart form is reliable even where .summary is absent.
        // Never borrow the stock/price of cross-sells from the page footer.
        $main = self::product_scope( $xp );
        if ( ! $main ) { throw new RuntimeException( 'EntreSetas: sin formulario o resumen principal ' . $url ); }
        $add = $xp->query( './/button[(contains(@class,"single_add_to_cart_button") or @name="add-to-cart") and not(@disabled)]', $main );
        $main_text = mb_strtolower( (string) $main->textContent, 'UTF-8' );
        $unavailable = (bool) preg_match( '/agotad[oa]|sin existencias|sin stock|no disponible/iu', $main_text );
        $schema_status = (string) ( $product['stock_status'] ?? 'unknown' );
        if ( $unavailable && 'instock' === $schema_status ) {
            throw new RuntimeException( 'EntreSetas: stock contradictorio en ficha y schema ' . $url );
        }
        if ( $unavailable ) { $product['stock_status'] = 'outofstock'; }
        elseif ( $add && $add->length ) {
            if ( 'outofstock' === $schema_status ) {
                throw new RuntimeException( 'EntreSetas: el carrito y el schema discrepan ' . $url );
            }
            $product['stock_status'] = 'instock';
        }
        if ( ! in_array( (string) ( $product['stock_status'] ?? '' ), array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'EntreSetas: disponibilidad no verificable ' . $url );
        }
        $variations = $xp->query( '//form[contains(concat(" ",normalize-space(@class)," ")," variations_form ")]' );
        if ( $variations && $variations->length && empty( $product['variations'] ) ) {
            $product['variation_count'] = 2;
        }
        // Detailed ingredients, allergens and conservation must remain sourced,
        // without inventing medical claims or substituting related-products text.
        $desc = $xp->query( '//*[@id="tab-description"] | //div[contains(@class,"woocommerce-Tabs-panel--description")]' );
        if ( $desc && $desc->length ) {
            $node = $desc->item(0);
            $body = '';
            foreach ( $node->childNodes as $child ) { $body .= $node->ownerDocument->saveHTML($child); }
            $body = wp_kses_post( $body );
            if ( mb_strlen( wp_strip_all_tags($body) ) > mb_strlen( wp_strip_all_tags((string)($product['description']??'')) ) ) {
                $product['description'] = $body;
            }
        }
        $short = $xp->query( '//div[contains(@class,"woocommerce-product-details__short-description")] | //div[contains(@class,"elementor-widget-woocommerce-product-short-description")]' );
        if ( $short && $short->length ) {
            $product['short_description'] = wp_kses_post( trim( (string) $short->item(0)->textContent ) );
        }
        $weight = $xp->query( '//table[contains(@class,"woocommerce-product-attributes")]//tr' );
        foreach ( $weight ?: array() as $tr ) {
            $th = $xp->query( './th', $tr ); $td = $xp->query( './td', $tr );
            if ( ! $th || ! $th->length || ! $td || ! $td->length ) { continue; }
            $k = mb_strtolower( trim( (string) $th->item(0)->textContent ), 'UTF-8' );
            $v = trim( (string) $td->item(0)->textContent );
            if ( str_contains( $k, 'peso' ) && preg_match( '/\b([0-9]+(?:[,.][0-9]+)?)\s*(kg|g)\b/iu', $v, $m ) ) {
                $kg = (float) str_replace(',', '.', $m[1]);
                if ( strtolower($m[2]) === 'g' ) { $kg /= 1000; }
                if ( $kg > 0 && $kg < 100 ) { $product['weight_kg'] = $kg; }
            }
        }
        // Elementor renders the genuine price as a visual heading (h4),
        // rather than a standard .summary .price or Product JSON-LD.
        // Never reuse a price from "También te puede gustar" recommendations.
        $product['price'] = self::visible_price( $xp );
        if ( ! isset($product['price']) || ! is_numeric($product['price']) || (float)$product['price'] <= 0 ) {
            throw new RuntimeException( 'EntreSetas: precio no válido en ' . $url );
        }
        $product['source_url'] = $url;
        $copy = $product;
        unset($copy['source_hash'], $copy['description']);
        $copy['description_hash'] = hash('sha256',wp_strip_all_tags((string)($product['description']??'')));
        $product['source_hash'] = hash('sha256',wp_json_encode($copy,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        return $product;
    }

    public static function upsert_product( int $supplier_id, array $product ): string {
        return MDO_Connector_Tolecarnes::upsert_product( $supplier_id, $product );
    }
}
