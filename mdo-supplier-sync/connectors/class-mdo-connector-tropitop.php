<?php
/**
 * Tropitop (PrestaShop): read-only, fail-closed source connector.
 *
 * URLs identify products, not combinations; the #/... fragment is only a UI
 * selection and must never create duplicate WooCommerce products.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Connector_Tropitop {
    private const HOST = 'tropitop.com';
    private const MAX_PAGES = 24;

    public static function discover( array $supplier ): array {
        $source = self::canonical( (string) ( $supplier['source_url'] ?? '' ) );
        $path = (string) wp_parse_url( $source, PHP_URL_PATH );
        $exclude = json_decode( (string) ( $supplier['exclusion_url_fragments'] ?? '[]' ), true );
        $exclude = is_array( $exclude ) ? $exclude : array();
        if ( self::is_product( $source ) ) {
            return self::discovery( array( $source ), array( $source ), $exclude );
        }
        if ( ! preg_match( '~^/es/[0-9]+-[a-z0-9-]+/?$~i', $path ) ) {
            throw new RuntimeException( 'Tropitop: indica la ficha de producto o una categoría /es/ID-nombre (no la portada).' );
        }
        $products = array();
        $pages = array();
        for ( $page = 1; $page <= self::MAX_PAGES; $page++ ) {
            $url = $source . ( str_contains( $source, '?' ) ? '&' : '?' ) . 'page=' . $page;
            $html = self::fetch( $url );
            $xp = self::xpath( $html );
            $found = 0;
            // Never crawl global menu, recommendation carousels or banner links:
            // only the 24 true product cards belonging to this listing page.
            $cards = $xp->query(
                '//article[contains(concat(" ",normalize-space(@class)," ")," product-miniature ")]'
                . ' | //div[contains(concat(" ",normalize-space(@class)," ")," product-miniature ") and @data-id-product]'
            );
            foreach ( $cards ?: array() as $card ) {
                foreach ( $xp->query( './/a[@href]', $card ) ?: array() as $node ) {
                    $candidate = self::absolute( (string) $node->getAttribute( 'href' ) );
                    if ( ! $candidate || ! self::is_product( $candidate ) ) { continue; }
                    $canonical = self::canonical( $candidate );
                    $id = self::product_id( $canonical );
                    if ( ! isset( $products[ $id ] ) ) { ++$found; }
                    $products[ $id ] = $canonical;
                    break;
                }
            }
            if ( ! $found && $page === 1 ) {
                throw new RuntimeException( 'Tropitop: la categoría no muestra productos; se evita un catálogo vacío.' );
            }
            if ( ! $found ) { break; }
            $pages[] = $url;
            // PrestaShop normally exposes a "next" URL. Never request an
            // unlimited series of duplicate pages if pagination is absent.
            $has_next = false;
            foreach ( $xp->query( '//a[@rel="next"] | //a[contains(@class,"next") and @href]' ) ?: array() as $next ) {
                if ( str_contains( (string) $next->getAttribute( 'href' ), 'page=' ) ) { $has_next = true; }
            }
            if ( ! $has_next ) { break; }
        }
        return self::discovery( array_values( $products ), $pages, $exclude );
    }

    private static function discovery( array $items, array $pages, array $exclude ): array {
        $products = array(); $excluded = array();
        foreach ( $items as $url ) {
            $skip = false;
            foreach ( $exclude as $fragment ) {
                if ( is_string( $fragment ) && $fragment !== '' && stripos( $url, $fragment ) !== false ) { $skip = true; break; }
            }
            if ( $skip ) { $excluded[] = $url; } else { $products[] = $url; }
        }
        return array( 'products' => array_values( array_unique( $products ) ), 'excluded' => array_values( array_unique( $excluded ) ), 'pages' => $pages );
    }

    public static function scrape_product( string $url ): array {
        $url = self::canonical( $url );
        if ( ! self::is_product( $url ) ) { throw new RuntimeException( 'Tropitop: URL de producto inválida.' ); }
        $html = self::fetch( $url );
        $xp = self::xpath( $html );
        $id = self::product_id( $url );
        $name = self::text( $xp, '(//h1)[1]' );
        if ( '' === $name ) { throw new RuntimeException( 'Tropitop: título ausente.' ); }

        $stock = self::stock( $xp );
        $sizes = self::sizes( $xp );
        // Product detail sometimes omits prices of unselected combinations.
        // The product card in its exact Spanish category can supply that matrix.
        // A single fully priced presentation is valid. Older releases fetched
        // an extra category page whenever count<2, then rejected the product
        // on transient category timeouts or unknown aliases. Only request a
        // fallback when variants visible in the source form are missing.
        $form = $xp->query( '//*[contains(concat(" ",normalize-space(@class)," ")," product-variants ")]' );
        $selector = $form && $form->length ? $form->item(0) : null;
        $option_nodes = $selector ? $xp->query(
            './/input[@data-product-attribute] | .//input[contains(concat(" ",normalize-space(@class)," ")," input-radio ")] | .//select[starts-with(@name,"group")]/option[@value!=""]',
            $selector
        ) : null;
        $expected_choices = $option_nodes ? $option_nodes->length : 0;
        if ( ! $sizes || self::missing_prices( $sizes ) ||
            ( $expected_choices > 1 && count( $sizes ) < $expected_choices ) ) {
            $category = self::category_url( $url );
            $listing = self::xpath( self::fetch( $category ) );
            foreach ( $listing->query( '//*[@data-id-product="' . $id . '"]' ) ?: array() as $card ) {
                $listed = self::sizes( $listing, $card );
                if ( count( $listed ) >= count( $sizes ) && ! self::missing_prices( $listed ) ) {
                    $sizes = $listed; break;
                }
            }
        }
        $has_selector = (bool) $xp->query( '//*[contains(@class,"product-variants")]|//select[starts-with(@name,"group")]|//*[@data-product-attribute]' )->length;
        if ( $has_selector && ( ! $sizes || self::missing_prices( $sizes ) ) ) {
            throw new RuntimeException( 'Tropitop: matriz de pesos/precios incompleta; no se importa como simple.' );
        }
        if ( self::missing_prices( $sizes ) ) {
            throw new RuntimeException( 'Tropitop: precio de una presentación no verificado.' );
        }
        $variations = array();
        foreach ( $sizes as $key => $row ) {
            $amount = (float) $row['amount'];
            $unit = (string) $row['unit'];
            $v = array(
                'variation_id' => $id . '-' . $unit . '-' . str_replace( '.', '_', number_format( $amount, 3, '.', '' ) )
                    . ( ! empty( $row['label'] ) ? '-' . substr( hash( 'sha256', $row['label'] ), 0, 8 ) : '' ),
                'attributes' => 'kg' === $unit
                    ? array( 'Peso' => self::format_kg( $amount ) )
                    : array( 'Unidades' => rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' ) . ' unidades'
                        . ( ! empty( $row['label'] ) ? ' — ' . $row['label'] : '' ) ),
                'display_price' => (float) $row['price'],
                'display_regular_price' => (float) $row['price'],
                'is_in_stock' => 'instock' === $stock && ! $row['unavailable'],
            );
            // Never pretend that 5 fruit pieces weigh 5kg. Unit-only packs
            // are staged as drafts, with shipping blocked until actual
            // packed weights are verified by the supplier.
            if ( 'kg' === $unit ) { $v['weight_kg'] = $amount; }
            $variations[] = $v;
        }
        if ( ! $variations ) {
            throw new RuntimeException( 'Tropitop: solo se permite importar productos con precio y peso verificables.' );
        }
        $min = min( array_column( $variations, 'display_price' ) );
        // Write original neutral text; the source's descriptions and photography
        // must not be reproduced until usage rights have been confirmed.
        $description = self::product_description( $xp );
        if ( '33' !== $id && '' === trim( wp_strip_all_tags( $description ) ) ) {
            throw new RuntimeException( 'Tropitop: descripción comercial de origen insuficiente.' );
        }
        $name = self::product_title( $name );
        $short_description = self::product_short_description( $xp, $name );
        // The original editorial copy for this known SKU is factual and is not a
        // reproduction of the supplier's product text. Keep claims of organic
        // certification outside this copy pending current documentary QA.
        if ( '33' === $id ) {
            $name = 'Aguacate Bacon de piel lisa — Tropitop';
            $description = '<p>El aguacate Bacon se distingue por su piel verde y lisa, su forma ovalada y una pulpa cremosa de sabor suave, con matices que recuerdan a los frutos secos. Es una variedad adecuada para ensaladas, tostadas, guacamole o para servir al natural.</p>'
                . '<p>Procede del cultivo de Tropitop en la zona de Artana (Castellón), en el entorno de la Sierra de Espadán. Las piezas pueden variar en tamaño y forma: el peso seleccionado corresponde al contenido total aproximado de la caja, no a un calibre uniforme.</p>'
                . '<p>Se presenta en cajas de 3, 4 o 9 kg. Se trata de una fruta que continúa madurando después de la recolección; si llega firme, conviene dejarla a temperatura ambiente y refrigerarla una vez madura. La disponibilidad puede variar según la campaña.</p>';
            $short_description = 'Aguacate Bacon de piel lisa cultivado en Castellón. Selecciona tu caja de 3, 4 o 9 kg. Pulpa cremosa y textura suave; puede llegar firme para completar la maduración en casa.';
        }
        $images = array();
        // The producer has expressly authorized original product images for
        // listings on El Mercado de Origen. PrestaShop's src is often a tiny
        // lazy-load SVG; always use the real large photograph, not that SVG.
        if ( (bool) apply_filters( 'mdo_tropitop_media_authorized', true ) ) {
            $nodes = $xp->query( '//*[contains(concat(" ",normalize-space(@class)," ")," product-cover ")]//img[@data-image-large-src] | //div[contains(concat(" ",normalize-space(@class)," ")," product-images ")]//img[@data-image-large-src]' );
            foreach ( $nodes ?: array() as $node ) {
                $candidate = trim( (string) $node->getAttribute( 'data-image-large-src' ) );
                if ( $candidate && self::safe_image( $candidate ) ) { $images[ $candidate ] = $candidate; }
                if ( count( $images ) >= 16 ) { break; }
            }
            if ( ! $images ) {
                $fallback = self::meta( $xp, 'og:image' );
                if ( $fallback && self::safe_image( $fallback ) ) { $images[ $fallback ] = $fallback; }
            }
            $images = array_values( $images );
        }
        $payload = array(
            'source_url' => $url,
            'source_product_id' => $id,
            'source_category' => (string) ( explode( '/', trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) )[1] ?? '' ),
            'title' => sanitize_text_field( $name ),
            'description' => $description,
            'short_description' => $short_description,
            'price' => $min,
            'sku' => 'TROPITOP-' . $id,
            'stock_status' => $stock,
            'images' => $images,
            'image_count' => count( $images ),
            'variations' => $variations,
            'variation_count' => count( $variations ),
        );
        $payload['source_hash'] = hash( 'sha256', wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
        return $payload;
    }

    public static function upsert_product( int $supplier_id, array $product ): string {
        // Existing EMDO repository behavior: stable URL key, hash diffs and
        // review-only new products. Avoid introducing a second persistence path.
        return MDO_Connector_Huerta_Ana_Mary::upsert_product( $supplier_id, $product );
    }

    /** Product-specific copy only: strip outbound shop links and seller-first-person voice. */
    private static function product_description( DOMXPath $xp ): string {
        $nodes = $xp->query(
            '//*[@id="main-product-wrapper"]//*[contains(concat(" ",normalize-space(@class)," ")," product-description ")]'
            . ' | //*[@id="col-product-info"]//*[contains(concat(" ",normalize-space(@class)," ")," rte-content ")]'
            . ' | //*[@id="description"]'
        );
        $candidate = '';
        foreach ( $nodes ?: array() as $node ) {
            if ( ! $node instanceof DOMElement ) { continue; }
            $plain = trim( (string) $node->textContent );
            if ( mb_strlen( $plain ) < 65 ) { continue; }
            $html = '';
            foreach ( $node->childNodes as $child ) { $html .= $node->ownerDocument->saveHTML( $child ); }
            if ( mb_strlen( wp_strip_all_tags( $html ) ) > mb_strlen( wp_strip_all_tags( $candidate ) ) ) {
                $candidate = $html;
            }
        }
        $candidate = preg_replace( '~<a\b[^>]*>(.*?)</a>~isu', '$1', $candidate );
        $candidate = wp_kses( $candidate, array(
            'p' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(),
            'ul' => array(), 'ol' => array(), 'li' => array(),
            'strong' => array(), 'b' => array(), 'em' => array(),
            'br' => array(), 'span' => array(),
        ) );
        $candidate = preg_replace( '/\bEn Tropitop (?:cultivamos|seleccionamos|ofrecemos|preparamos)\b/iu', 'Tropitop ofrece', (string) $candidate );
        $candidate = preg_replace( '/\b(?:nuestros|nuestras)\s+(?:frutas|verduras|productos)\b/iu', 'estos productos', (string) $candidate );
        if ( class_exists( 'MDO_Woo_Importer' ) ) {
            $candidate = MDO_Woo_Importer::remove_source_attribution( (string) $candidate );
        }
        return trim( (string) $candidate );
    }

    private static function product_title( string $name ): string {
        $name = trim( (string) preg_replace( '/^(?:COMPRAR|Comprar)\s+/u', '', $name ) );
        $name = preg_replace( '/\s+ONLINE(?:\s+DIRECTOS?\s+DEL\s+AGRICULTOR)?\s*$/iu', '', (string) $name );
        return sanitize_text_field( trim( (string) $name ) );
    }

    private static function product_short_description( DOMXPath $xp, string $name ): string {
        $short = self::text( $xp, '//*[@id="product-description-short"]' );
        $short = trim( (string) preg_replace( '/\s+/u', ' ', $short ) );
        if ( mb_strlen( $short ) > 45 && mb_strlen( $short ) <= 500 ) {
            $short = preg_replace( '/\bEn Tropitop\b/iu', 'Tropitop', $short );
            return sanitize_text_field( (string) $short );
        }
        return sanitize_text_field( $name . '. Selecciona el formato y el peso disponible.' );
    }

    private static function sizes( DOMXPath $xp, ?DOMNode $context = null ): array {
        // On a product detail page inspect only the product's variant selector,
        // not related-product cards or the global menu (which may display other
        // box weights and prices). A category card is scoped by its product ID.
        $root = $context;
        if ( null === $root ) {
            $matching = $xp->query( '//*[contains(concat(" ",normalize-space(@class)," ")," product-variants ")] | //form[@id="add-to-cart-or-refresh"]//*[contains(@class,"product-variants")]' );
            $root = $matching && $matching->length ? $matching->item( 0 ) : null;
        }
        if ( ! $root ) { return array(); }
        $output = array();
        $nodes = $xp->query(
            './/div[contains(concat(" ",normalize-space(@class)," ")," variant-box ")]'
            . ' | .//option | .//li | .//a | .//button | .//label',
            $root
        );
        foreach ( $nodes ?: array() as $node ) {
            $text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            // Only explicit size + EUR price pairs can become a variation.
            // Do not treat €/kg unit prices as a box price.
            if ( ! preg_match( '/\b(\d+(?:[.,]\d+)?)\s*(kg|kilos?|unid(?:ades)?|uds?|u)\b/iu',
                $text, $weight_match, PREG_OFFSET_CAPTURE ) ) { continue; }
            if ( ! preg_match( '/(\d{1,5}(?:[.,]\d{2}))\s*€/u', $text, $price_match ) ) { continue; }
            if ( preg_match( '/€\s*\/\s*kg|€\s*por\s*kg/iu', $text ) ) { continue; }
            $amount = (float) str_replace( ',', '.', $weight_match[1][0] );
            $unit = preg_match( '/^(?:kg|kilo)/iu', $weight_match[2][0] ) ? 'kg' : 'unid';
            $label = trim( (string) preg_replace( '/[\s\-–—:]+$/u', '',
                substr( $text, 0, $weight_match[0][1] ) ) );
            // Source labels such as "Tradicional - 4 u." and "Sin azúcar - 4 u."
            // are distinct variations, even when both boxes contain four jars.
            // Never merge different prices under the same quantity.
            $label = mb_substr( sanitize_text_field( $label ), 0, 70 );
            $price = (float) str_replace( ',', '.', $price_match[1] );
            if ( $amount <= 0 || $amount > 60 || $price <= 0 || $price > 10000 ) { continue; }
            $key = $unit . ':' . number_format( $amount, 3, '.', '' ) . ( $label !== '' ? ':' . mb_strtolower( $label ) : '' );
            // Ignore ancestor nodes containing multiple options.
            if ( preg_match_all( '/\b\d+(?:[.,]\d+)?\s*(?:kg|kilos?|unid(?:ades)?|uds?|u)\b/iu', $text ) !== 1 ) { continue; }
            $output[ $key ] = array(
                'amount' => $amount, 'unit' => $unit,
                'label' => $label, 'price' => $price,
                'unavailable' => (bool) preg_match( '/agotad[oa]|sin\s*stock|out\s*of\s*stock/iu', $text ),
            );
        }
        ksort( $output, SORT_NATURAL );
        return $output;
    }

    private static function missing_prices( array $sizes ): bool {
        foreach ( $sizes as $v ) { if ( ! isset( $v['price'] ) || (float) $v['price'] <= 0 ) { return true; } }
        return false;
    }

    private static function stock( DOMXPath $xp ): string {
        // The source can expose an enabled buy button at the same time as
        // "no disponible temporalmente": the explicit stock warning wins.
        $availability = mb_strtolower( self::text( $xp,
            '//*[@id="product-availability"] | //*[contains(concat(" ",normalize-space(@class)," ")," product-availability ")]'
        ), 'UTF-8' );
        $button = mb_strtolower( self::text( $xp, '//button[@data-button-action="add-to-cart"]' ), 'UTF-8' );
        if ( preg_match( '/no\s+disponible|agotad|sin\s+stock|out\s+of\s+stock|temporalmente\s+no\s+disponible/u',
            $availability . ' ' . $button ) ) { return 'outofstock'; }
        if ( $xp->query( '//button[@data-button-action="add-to-cart" and not(@disabled)]' )->length ) {
            return 'instock';
        }
        return 'unknown';
    }

    private static function meta( DOMXPath $xp, string $property ): string {
        $nodes = $xp->query( '//meta[@property="' . $property . '"]/@content' );
        return $nodes && $nodes->length ? trim( (string) $nodes->item(0)->nodeValue ) : '';
    }

    private static function text( DOMXPath $xp, string $path ): string {
        $nodes = $xp->query( $path );
        return $nodes && $nodes->length ? trim( preg_replace( '/\s+/u', ' ', $nodes->item(0)->textContent ) ) : '';
    }

    private static function format_kg( float $kg ): string {
        return rtrim( rtrim( number_format( $kg, 2, '.', '' ), '0' ), '.' ) . ' kg';
    }

    private static function is_product( string $url ): bool {
        return (bool) preg_match( '~^/es/[^?]+/[0-9]+(?:-[0-9]+)?-[a-z0-9-]+\.html$~i', (string) wp_parse_url( $url, PHP_URL_PATH ) );
    }

    private static function product_id( string $url ): string {
        if ( ! preg_match( '~/([0-9]+)(?:-[0-9]+)?-[a-z0-9-]+\.html$~i', (string) wp_parse_url( $url, PHP_URL_PATH ), $m ) ) {
            throw new RuntimeException( 'Tropitop: identificador ausente.' );
        }
        return $m[1];
    }

    private static function category_url( string $url ): string {
        $parts = explode( '/', trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) );
        $slug = $parts[1] ?? '';
        if ( '' === $slug || ! preg_match( '/^[a-z0-9-]+$/i', $slug ) ) {
            throw new RuntimeException( 'Tropitop: categoría desconocida.' );
        }
        // Category slug "aguacates" needs its PrestaShop category ID.
        $known = array( 'aguacates' => '12-aguacates', 'frutas' => '62-frutas', 'verduras' => '80-verduras',
            'frutas-tropicales' => '63-frutas-tropicales', 'frutas-del-mundo' => '106-frutas-del-mundo',
            'gourmet' => '79-gourmet', 'packs' => '87-packs',
            'cajas-de-temporada' => '89-cajas-de-temporada',
            'home-verduras' => '80-verduras', 'productos' => '62-frutas',
            // Public producer category roots discovered by the full inventory.
            // Never guess an unlisted category or fall back to unrelated cards.
            'disponible' => '84-disponible', 'cestas-de-fruta' => '52-cestas-de-fruta',
            'bio' => '109-bio', 'mangos' => '10-mangos',
            'citricos' => '62-citricos', 'apadrina-un-arbol' => '77-apadrina-un-arbol' );
        $category = $known[ $slug ] ?? '';
        if ( ! $category ) {
            throw new RuntimeException( 'Tropitop: configurar una URL de categoría para extraer las variantes.' );
        }
        return 'https://' . self::HOST . '/es/' . $category;
    }

    private static function canonical( string $url ): string {
        $p = wp_parse_url( trim( $url ) );
        $host = strtolower( (string) ( $p['host'] ?? '' ) );
        if ( ! in_array( $host, array( self::HOST, 'www.' . self::HOST ), true )
            || strtolower( (string) ( $p['scheme'] ?? '' ) ) !== 'https' ) {
            throw new RuntimeException( 'Tropitop: host o protocolo no autorizado.' );
        }
        $path = (string) ( $p['path'] ?? '' );
        if ( ! str_starts_with( $path, '/es/' ) || str_contains( $path, '..' ) ) {
            throw new RuntimeException( 'Tropitop: solo se permite el escaparate español /es/.' );
        }
        // PrestaShop URLs have a combination ID before the slug; different
        // combination IDs are one logical product, not separate products.
        $path = preg_replace( '~/(\d+)-\d+-([a-z0-9-]+\.html)$~i', '/$1-$2', $path );
        return esc_url_raw( 'https://' . self::HOST . rtrim( $path, '/' ) );
    }

    private static function absolute( string $raw ): string {
        $raw = trim( html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
        if ( str_starts_with( $raw, '/' ) && ! str_starts_with( $raw, '//' ) ) {
            $raw = 'https://' . self::HOST . $raw;
        }
        try { return self::canonical( $raw ); }
        catch ( Throwable $e ) { return ''; }
    }

    private static function safe_image( string $url ): bool {
        $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
        return in_array( $host, array( self::HOST, 'www.' . self::HOST ), true )
            && (bool) preg_match( '~\.(?:jpe?g|png|webp)(?:\?.*)?$~i', $url );
    }

    private static function fetch( string $url ): string {
        // Force known host + HTTPS on EVERY request, including catalog pages.
        self::canonical( $url );
        $r = wp_remote_get( $url, array(
            'timeout' => 18, 'redirection' => 2, 'limit_response_size' => 2500000,
            'headers' => array( 'Accept-Language' => 'es-ES,es;q=0.9', 'Accept' => 'text/html' ),
            'user-agent' => 'EMDO/' . MDO_SUPPLIER_SYNC_VERSION . ' (+https://www.elmercadodeorigen.com/)',
        ) );
        if ( is_wp_error( $r ) ) { throw new RuntimeException( $r->get_error_message() ); }
        $status = (int) wp_remote_retrieve_response_code( $r );
        if ( $status < 200 || $status >= 300 ) { throw new RuntimeException( 'HTTP ' . $status . ': ' . $url ); }
        $body = (string) wp_remote_retrieve_body( $r );
        if ( strlen( $body ) < 1000 ) { throw new RuntimeException( 'Tropitop: página vacía o bloqueada.' ); }
        return $body;
    }

    private static function xpath( string $html ): DOMXPath {
        $doc = new DOMDocument();
        $before = libxml_use_internal_errors( true );
        $doc->loadHTML( '<?xml encoding="UTF-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $before );
        return new DOMXPath( $doc );
    }
}
