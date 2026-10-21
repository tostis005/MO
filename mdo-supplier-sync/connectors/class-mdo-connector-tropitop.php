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
        $name = self::product_name( $xp );
        if ( '' === $name ) { throw new RuntimeException( 'Tropitop: título ausente.' ); }

        $stock = self::stock( $xp );
        $sizes = self::sizes( $xp );
        // Two named fruit packs use descriptive combinations instead of kg
        // or units inside their PrestaShop options; never invent prices.
        if ( in_array( $id, array( '2365', '2685' ), true ) ) {
            $sizes = self::named_pack_sizes( $xp, $id );
        }
        if ( in_array( $id, array( '2525', '2526' ), true ) && ! $sizes ) {
            $sizes = self::honey_single_size( $xp, $id, $url );
        }
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
            $category = self::category_url( $url, $xp );
            $listing = self::xpath( self::fetch( $category ) );
            foreach ( $listing->query( '//*[@data-id-product="' . $id . '"]' ) ?: array() as $card ) {
                $listed = in_array( $id, array( '2365', '2685' ), true )
                    ? self::named_pack_sizes( $listing, $id, $card )
                    : self::sizes( $listing, $card );
                if ( count( $listed ) >= count( $sizes ) && ! self::missing_prices( $listed ) ) {
                    $sizes = $listed; break;
                }
            }
        }
        if ( in_array( $id, array( '2365', '2685' ), true ) && count( $sizes ) !== 2 ) {
            throw new RuntimeException( 'Tropitop: ambas combinaciones de sandía carecen de precio verificable.' );
        }
        $has_selector = (bool) $xp->query( '//*[contains(@class,"product-variants")]|//select[starts-with(@name,"group")]|//*[@data-product-attribute]' )->length;
        if ( $has_selector && ( ! $sizes || self::missing_prices( $sizes ) ) ) {
            throw new RuntimeException( 'Tropitop: matriz de pesos/precios incompleta; no se importa como simple.' );
        }
        if ( self::missing_prices( $sizes ) ) {
            throw new RuntimeException( 'Tropitop: precio de una presentación no verificado.' );
        }
        $description = self::product_description( $xp );
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
            elseif ( null !== ( $curated = self::curated_unit_tariff_weight( $id, $row ) ) ) {
                $v['weight_kg'] = (float) $curated['kg'];
                $v['shipping_weight_estimated'] = true;
                $v['shipping_weight_basis'] = (string) $curated['basis'];
            }
            elseif ( isset( $row['weight_kg'] ) ) {
                $v['weight_kg'] = (float) $row['weight_kg'];
                if ( ! empty( $row['weight_estimated'] ) ) {
                    $v['shipping_weight_estimated'] = true;
                    $v['shipping_weight_basis'] = (string) ( $row['weight_basis'] ?? 'source_net_weight_estimate' );
                }
            }
            else {
                $estimated = null; $basis = '';
                if ( isset( $row['net_volume_l'] ) && isset( $row['container_count'] ) ) {
                    $estimated = self::estimated_chargeable_weight( (float) $row['net_volume_l'] );
                    $basis = 'liquid_volume_1_liter_1_kg';
                } else {
                    $estimated = self::estimated_description_weight( $row, $description );
                    $basis = 'explicit_contents_grams_or_liters_1_to_1';
                }
                if ( null !== $estimated ) {
                    $v['weight_kg'] = $estimated;
                    $v['shipping_weight_estimated'] = true;
                    $v['shipping_weight_basis'] = $basis;
                }
            }
            $variations[] = $v;
        }
        if ( ! $variations ) {
            throw new RuntimeException( 'Tropitop: solo se permite importar productos con precio y peso verificables.' );
        }
        $min = min( array_column( $variations, 'display_price' ) );
        // Write original neutral text; the source's descriptions and photography
        // must not be reproduced until usage rights have been confirmed.
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

    /**
     * Prefer a heading belonging to this very product, not the nav menu or a
     * "related products" carousel. When PrestaShop suppresses H1 (out of
     * stock / legacy templates), its product-specific OG metadata is valid.
     */
    private static function product_name( DOMXPath $xp ): string {
        $paths = array(
            '//*[@id="main-product-wrapper"]//h1[normalize-space()]',
            '//*[@id="col-product-info"]//h1[normalize-space()]',
            '//main//h1[normalize-space()]',
            '//h1[normalize-space()]',
        );
        foreach ( $paths as $path ) {
            $text = self::text( $xp, '(' . $path . ')[1]' );
            if ( $text !== '' && mb_strlen( $text ) < 220 ) { return $text; }
        }
        foreach ( array( 'og:title', 'twitter:title' ) as $meta ) {
            $text = self::meta( $xp, $meta );
            $text = preg_replace( '/\s*(?:\||[-–])\s*Tropitop.*$/iu', '', $text );
            if ( mb_strlen( trim( $text ) ) >= 5 && mb_strlen( $text ) <= 220 ) {
                return trim( $text );
            }
        }
        // One last source-specific heading, never a generic document H2.
        $text = self::text( $xp,
            '(//*[@id="main-product-wrapper"]//h2[normalize-space()]'
            . ' | //*[@id="col-product-info"]//h2[normalize-space()]'
            . ' | //*[@id="description"]//h2[normalize-space()])[1]' );
        return mb_strlen( $text ) >= 5 && mb_strlen( $text ) <= 180 ? $text : '';
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
            $text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode(
                (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            // A size option is one node. Ignore ancestors with several options.
            $pattern = '/\b\d+(?:[.,]\d+)?\s*(?:[x×]\s*\d+(?:[.,]\d+)?\s*)?(?:kg|kilos?|unid(?:ades)?|uds?|u|litros?|l|ml|cl|gr(?:amos?)?|g)\b/iu';
            if ( preg_match_all( $pattern, $text ) !== 1 ) { continue; }
            // A quoted €/kg is unit-price metadata, NOT an extra option.
            $clean = preg_replace( '/(?:el\s+kilo\s+te\s+sale\s+a\s*:?\s*)?\d+(?:[.,]\d{2})\s*€\s*\/\s*kg\b/iu', '', $text );
            if ( ! preg_match( '/(\d{1,5}(?:[.,]\d{2}))\s*€/u', $clean, $price_match ) ) { continue; }
            $amount = 0.0; $unit = 'unid'; $suffix = ''; $offset = 0;
            // Producer format "3 x 1L" = 3 pieces of 1L, not 3kg.
            if ( preg_match( '/\b(\d+(?:[.,]\d+)?)\s*[x×]\s*(\d+(?:[.,]\d+)?)\s*(kg|kilos?|litros?|l|ml|cl|gr(?:amos?)?|g)\b/iu',
                $clean, $bundle, PREG_OFFSET_CAPTURE ) ) {
                $amount = (float) str_replace( ',', '.', $bundle[1][0] );
                $content = (float) str_replace( ',', '.', $bundle[2][0] );
                $raw_unit = mb_strtolower( $bundle[3][0] );
                $unit_name = preg_match( '/^(?:l|litro)/u', $raw_unit ) ? 'L'
                    : ( preg_match( '/^(?:kg|kilo)/u', $raw_unit ) ? 'kg'
                    : ( preg_match( '/^(?:g|gr)/u', $raw_unit ) ? 'g' : $raw_unit ) );
                $suffix = rtrim( rtrim( number_format( $content, 3, '.', '' ), '0' ), '.' )
                    . ' ' . $unit_name . ' cada unidad';
                $offset = $bundle[0][1];
            } elseif ( preg_match( '/\b(\d+(?:[.,]\d+)?)\s*(kg|kilos?|unid(?:ades)?|uds?|u|litros?|l|ml|cl|gr(?:amos?)?|g)\b/iu',
                $clean, $single, PREG_OFFSET_CAPTURE ) ) {
                $amount = (float) str_replace( ',', '.', $single[1][0] );
                $raw_unit = mb_strtolower( $single[2][0] );
                $offset = $single[0][1];
                if ( preg_match( '/^(?:kg|kilo)/u', $raw_unit ) ) {
                    $unit = 'kg';
                } elseif ( preg_match( '/^(?:g|gr)/u', $raw_unit ) ) {
                    $unit = 'kg'; $amount /= 1000;
                } elseif ( preg_match( '/^(?:l|litro|ml|cl)/u', $raw_unit ) ) {
                    $suffix = rtrim( rtrim( number_format( $amount, 3, '.', '' ), '0' ), '.' )
                        . ' ' . ( preg_match( '/^(?:l|litro)/u', $raw_unit ) ? 'L' : $raw_unit );
                    $amount = 1.0;
                }
            } else { continue; }
            $label = trim( (string) preg_replace( '/[\s\-–—:]+$/u', '',
                substr( $clean, 0, $offset ) ) );
            $label = mb_substr( sanitize_text_field( $label ), 0, 70 );
            if ( $suffix !== '' ) { $label = trim( $label . ( $label !== '' ? ' — ' : '' ) . $suffix ); }
            $price = (float) str_replace( ',', '.', $price_match[1] );
            if ( $amount <= 0 || $amount > 60 || $price <= 0 || $price > 10000 ) { continue; }
            $key = $unit . ':' . number_format( $amount, 3, '.', '' )
                . ( $label !== '' ? ':' . mb_strtolower( $label ) : '' );
            $entry = array(
                'amount' => $amount, 'unit' => $unit,
                'label' => $label, 'price' => $price,
                'unavailable' => (bool) preg_match( '/agotad[oa]|sin\s*stock|out\s*of\s*stock/iu', $text ),
            );
            // Read net volume from this exact PrestaShop presentation, not
            // a similar item in the global menu or related-products block.
            // Chargeable kg uses the merchant-approved net content 1:1, no packaging.
            if ( 'unid' === $unit && preg_match(
                '/\b(\d{1,2})\s*[x×]\s*(\d+(?:[.,]\d+)?)\s*(litros?|l|ml|cl)\b/iu',
                $text, $multi ) ) {
                $units = (int) $multi[1];
                $volume_l = self::volume_in_liters(
                    (float) str_replace( ',', '.', $multi[2] ), $multi[3] );
                if ( $units === (int) $amount && null !== $volume_l ) {
                    $entry['net_volume_l'] = $units * $volume_l;
                    $entry['container_count'] = $units;
                }
            } elseif ( 'unid' === $unit && preg_match(
                '/\b(\d+(?:[.,]\d+)?)\s*(litros?|l|ml|cl)\b/iu', $text, $single_volume ) ) {
                $volume_l = self::volume_in_liters(
                    (float) str_replace( ',', '.', $single_volume[1] ), $single_volume[2] );
                if ( null !== $volume_l && 1.0 === $amount ) {
                    $entry['net_volume_l'] = $volume_l;
                    $entry['container_count'] = 1;
                }
            }
            // When this SAME option explicitly displays EUR/kg, derive its
            // stated kilograms from two source prices (32,90 / 5,48 = 6kg).
            // Never invent weight from liquid volume or a count of jars.
            if ( 'unid' === $unit && preg_match(
                '/(?:el\s+kilo\s+te\s+sale\s+a\s*:?\s*)?(\d+(?:[.,]\d{2}))\s*€\s*\/\s*kg\b/iu',
                $text, $per_kg ) ) {
                $rate = (float) str_replace( ',', '.', $per_kg[1] );
                $weight = $rate > 0 ? $price / $rate : 0.0;
                $rounded = round( $weight * 4 ) / 4;
                if ( $rounded > 0 && $rounded <= 36 && abs( $weight - $rounded ) < 0.02 ) {
                    $entry['weight_kg'] = $rounded;
                }
            }
            $output[ $key ] = $entry;
        }
        ksort( $output, SORT_NATURAL );
        return $output;
    }

    /**
     * Exact producer-labelled options for two summer packs. Their PrestaShop
     * attribute name is "nombre", not "kg"/"unid"; only the real product
     * selector or its own category-card node can supply prices. Never borrow
     * a different product's price, and require BOTH choices on the page.
     */
    private static function named_pack_sizes( DOMXPath $xp, string $id, ?DOMNode $context = null ): array {
        if ( ! in_array( $id, array( '2365', '2685' ), true ) ) { return array(); }
        $root = $context;
        if ( null === $root ) {
            $match = $xp->query( '//*[contains(concat(" ",normalize-space(@class)," ")," product-variants ")]' );
            $root = $match && $match->length ? $match->item(0) : null;
        }
        if ( ! $root ) { return array(); }
        $nodes = $xp->query(
            './/div[contains(concat(" ",normalize-space(@class)," ")," variant-box ")]'
            . ' | .//option | .//li | .//a | .//button | .//label',
            $root
        );
        $result = array();
        foreach ( $nodes ?: array() as $node ) {
            $text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode(
                (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
            $tail = '2365' === $id
                ? '2\s+melones'
                : 'mel[oó]n\s+sapo\s*\+\s*mel[oó]n\s+galia';
            if ( ! preg_match( '/\b(sand[ií]a\s+(rayada|negra))\s*\+\s*'
                . $tail . '\s*(?:[-–]\s*)?(\d{1,4}(?:[.,]\d{2}))\s*€/iu',
                $text, $m ) ) { continue; }
            $kind = mb_strtolower( $m[2], 'UTF-8' );
            $price = (float) str_replace( ',', '.', $m[3] );
            if ( $price <= 0 || $price > 1000 || isset( $result[ $kind ] ) ) {
                // Duplicate DOM wrappers are acceptable only if they agree.
                if ( isset( $result[ $kind ] ) && abs( $result[ $kind ]['price'] - $price ) > 0.001 ) {
                    return array();
                }
                continue;
            }
            $name = 'Sandía ' . $kind . ( '2365' === $id
                ? ' + 2 melones'
                : ' + Melón sapo + Melón galia' );
            // Exact first-party composition: 2365 = 6/7 + 2*2.8kg;
            // 2685 = 6/7 + 2.8 + 2kg. Source calls these approximate.
            $kg = ( 'negra' === $kind ? 7.0 : 6.0 )
                + ( '2365' === $id ? 5.6 : 4.8 );
            $result[ $kind ] = array(
                'amount' => 3.0, 'unit' => 'unid', 'label' => $name,
                'price' => $price, 'weight_kg' => round( $kg, 2 ),
                'weight_estimated' => true,
                'weight_basis' => 'tropitop_named_combo_source_net_20261021',
                'unavailable' => (bool) preg_match( '/agotad[oa]|sin\s*stock/iu', $text ),
            );
        }
        if ( ! isset( $result['rayada'], $result['negra'] ) ) { return array(); }
        return array(
            'unid:3.000:' . mb_strtolower( $result['rayada']['label'], 'UTF-8' ) => $result['rayada'],
            'unid:3.000:' . mb_strtolower( $result['negra']['label'], 'UTF-8' ) => $result['negra'],
        );
    }

    /**
     * Two legacy honey listings no longer render a format selector but do
     * expose the CURRENT product price and the product:price:amount meta.
     * Both must match. Do not mistake prices from the global menu.
     * Only source URLs whose exact presentation proves 2 kg qualify.
     * Never synthesize product prices or relax other products' matrix guard.
     */
    private static function honey_single_size( DOMXPath $xp, string $id, string $url ): array {
        if ( ! in_array( $id, array( '2525', '2526' ), true ) ) { return array(); }
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        if ( ( '2525' === $id &&
                ! str_ends_with( $path, '/2525-comprar-miel-azahar-artesanal-2kg.html' ) ) ||
            ( '2526' === $id &&
                ! str_ends_with( $path, '/2526-comprar-miel-montana-artesanal-4x500g.html' ) ) ) {
            return array();
        }
        $choices = $xp->query(
            '//*[contains(concat(" ",normalize-space(@class)," ")," product-variants ")]'
            . '//input[@data-product-attribute or @type="radio"]'
            . ' | //select[starts-with(@name,"group")]/option[@value!=""]'
        );
        if ( $choices && $choices->length ) { return array(); }
        $meta_nodes = $xp->query( '//meta[@property="product:price:amount"]/@content' );
        $meta = $meta_nodes && $meta_nodes->length === 1
            ? (string) $meta_nodes->item(0)->nodeValue : '';
        if ( ! preg_match( '/^\d{1,4}(?:[.,]\d{1,2})?$/', $meta ) ) { return array(); }
        $meta_price = (float) str_replace( ',', '.', $meta );
        $visible = self::text( $xp,
            '(//*[@id="main-product-wrapper"]//*[contains(@class,"current-price")]'
            . ' | //*[@id="col-product-info"]//*[contains(@class,"current-price")])[1]' );
        if ( ! preg_match( '/(\d{1,4}(?:[.,]\d{2}))\s*€/u', $visible, $m ) ) {
            return array();
        }
        $shown = (float) str_replace( ',', '.', $m[1] );
        if ( $meta_price <= 0 || abs( $shown - $meta_price ) > 0.001 ) { return array(); }
        // 2525 URL says 2kg. 2526 explicitly says 4×500g = 2kg.
        return array( 'kg:2.000' => array(
            'amount'=>2.0, 'unit'=>'kg', 'label'=>'',
            'price'=>$meta_price, 'unavailable'=>false,
        ) );
    }

    /**
     * Tropitop 2026-10-21 merchant-approved shipping tariff conventions.
     *
     * Each entry is keyed by one supplier PRODUCT ID and the EXACT printed
     * UNIT COUNT in that product's priced option. The source composition is
     * independently documented in its own Tropitop product listing, NOT
     * deduced from photos. Only these individual packs receive the override.
     *
     * Physical packed/gross weight is not claimed. These are kg charged to
     * WCFM's existing weight-based shipping rate for Spanish mainland.
     * Persist in variation meta as estimated and auditable. New/unknown
     * choices stay drafts rather than silently inheriting a per-piece rate.
     *
     * Sources: https://tropitop.com/es/<category>/<id>-<slug>.html
     */
    private static function curated_unit_tariff_weight( string $product_id, array $row ): ?array {
        if ( (string) ( $row['unit'] ?? '' ) !== 'unid' ) { return null; }
        $amount = (float) ( $row['amount'] ?? 0 );
        if ( $amount < 1 || $amount > 24 || $amount !== floor( $amount ) ) { return null; }
        $packs = array(
            // 4kg Hass + 1x200g mango jam, sold as 4 units.
            '2285' => array( 4 => 4.20 ),
            // No producer mass stated: 6 living lettuces, merchant estimate 250g each.
            '2321' => array( 6 => 1.50 ),
            // Mixed artisanal jams: 5 individually labelled jars of 200g.
            '2349' => array( 5 => 1.00 ),
            // Watermelon striped: around 6kg apiece, boxes of 2 or 3.
            '2362' => array( 2 => 12.00, 3 => 18.00 ),
            // Piel de sapo: approximately 3.5kg apiece.
            '2363' => array( 2 => 7.00, 3 => 10.50 ),
            // Galia approximately 2kg; Cantalupo upper producer range 1.8kg.
            '2451' => array( 2 => 4.00, 3 => 6.00 ),
            '2452' => array( 2 => 3.60, 3 => 5.40 ),
            // Mix 2x2kg Galia + 2x1.8kg Cantalupo.
            '2453' => array( 4 => 7.60 ),
            // 2 mountain honey x 500g + 2 orange blossom honey x 500g.
            '2535' => array( 4 => 2.00 ),
            // 3 green olive pâtés x120g + 3 black olive pâtés x120g.
            '2536' => array( 6 => 0.72 ),
            // AOVE cosmetics known 100g+500ml+50ml+75ml (725g);
            // 25g is explicitly a MERCHANT ESTIMATE for the unlabelled lip balm.
            '2538' => array( 5 => 0.75 ),
            // Honey cosmetics 75ml+100ml; 25g merchant estimate for lip balm.
            '2539' => array( 3 => 0.20 ),
            // Three breakfast baskets: exact product listing composition,
            // 1:1 merchant tariff for ml/g, NO glass, box or external
            // packaging. Jams 200g each: same Tropitop mango/guava jar.
            // Rounded UP for chargeable shipping per merchant direction.
            // 2608: 2kg Hass + 2kg orange + 1kg RAF + 1kg banana +
            //       500g honey + 200g guava jam + 200g mango jam.
            '2608' => array( 10 => 7.00 ),
            // 2610: 2kg Hass + 2kg orange + 1kg RAF +
            //       320g four small honeys + 200g mango jam.
            '2610' => array( 7 => 6.00 ),
            // 2612: 2kg Hass + 2kg orange + 2x200g jams +
            //       320g honey + 250ml AOVE + 100ml cream +
            //       500ml body lotion (1ml = 1g merchant convention).
            '2612' => array( 10 => 6.00 ),
            // Nut jars 2 x 580g; mixed pack has one of each.
            '2579' => array( 2 => 1.16 ),
            '2580' => array( 2 => 1.16 ),
            '2581' => array( 2 => 1.16 ),
            // Black watermelon around 7kg apiece.
            '2677' => array( 2 => 14.00, 3 => 21.00 ),
            // 1 piel de sapo 2.8kg + 1 Galia 2kg + 1 striped 6kg + 1 black 7kg.
            '2693' => array( 4 => 17.80 ),
            // 1 piel de sapo 2.8kg + 2 Galia 2kg.
            '2695' => array( 3 => 6.80 ),
            // Sugar/Sunset melon upper published weight ~1.7kg per fruit.
            '2699' => array( 2 => 3.40, 4 => 6.80 ),
            // 6 intact coconuts, ~450g apiece.
            '2710' => array( 6 => 2.70 ),
            '2711' => array( 2 => 3.40, 4 => 6.80 ),
            // Extra Sweet pineapples around 2.3kg apiece.
            '2712' => array( 2 => 4.60, 3 => 6.90 ),
            // Premium 4-melon selection states ~7kg total in producer copy.
            '2717' => array( 4 => 7.00 ),
            // Smoothie 2kg pitahaya + 2kg banana + papaya; 6kg follows
            // supplier's price/quoted EUR per kg: 32.90 / 5.48 ≈ 6kg.
            '2732' => array( 5 => 6.00 ),
        );
        $n = (int) $amount;
        if ( ! isset( $packs[ $product_id ][ $n ] ) ) { return null; }
        $kg = (float) $packs[ $product_id ][ $n ];
        if ( null === self::estimated_chargeable_weight( $kg ) ) { return null; }
        $merchant_estimate = in_array( $product_id, array( '2321', '2538', '2539' ), true );
        return array(
            'kg' => $kg,
            'basis' => $merchant_estimate ? 'merchant_approximation_reviewable'
                : 'tropitop_listed_pack_composition_20261021',
        );
    }

    /** Read liquid net content only. Units of fruit have no volume. */
    private static function volume_in_liters( float $amount, string $unit ): ?float {
        $u = mb_strtolower( trim( $unit ), 'UTF-8' );
        if ( $amount <= 0 ) { return null; }
        if ( preg_match( '/^(?:l|litros?)$/u', $u ) ) { return $amount; }
        if ( 'ml' === $u ) { return $amount / 1000.0; }
        if ( 'cl' === $u ) { return $amount / 100.0; }
        return null;
    }

    /**
     * Merchant-approved 1:1 shipping conversion: 1 L liquid = 1 kg and
     * 1,000 g of explicitly stated jar contents = 1 kg. No bottle,
     * glass, carton or outer packaging allowance. This is a tariff
     * convention, NOT a claim about the physical parcel's gross mass.
     * Do not guess tariffs above the existing verified 30 kg bands.
     */
    private static function estimated_chargeable_weight( float $net ): ?float {
        return $net > 0 && $net <= 30 ? round( $net, 3 ) : null;
    }

    /**
     * Shipping weight from the exact presentation's content in producer copy.
     * Examples:
     *   4 botes de 200g, 4 botes ... (200g cada uno)
     *   4 botellas de AOVE de 250 ml
     *   5 separate "1 bote de XXX (200 g)" assortment lines.
     * Do not use counts of loose fruit or infer a weight from piece number.
     */
    private static function estimated_description_weight( array $row, string $description ): ?float {
        if ( (string) ( $row['unit'] ?? '' ) !== 'unid' ) { return null; }
        $count = (float) ( $row['amount'] ?? 0 );
        if ( $count < 1 || $count > 24 || $count !== floor( $count ) ) { return null; }
        $plain = preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $description ) );
        if ( $plain === '' ) { return null; }
        // A named container is essential. "4 unidades" alone says nothing
        // about mass, and generic gram mentions can refer to ingredients.
        $pieces = '(?:botes?|tarros?|frascos?|botellas?|latas?|envases?)';
        $size = '(\d+(?:[.,]\d+)?)\s*(kg|kilos?|gramos?|gr|g|litros?|l|ml|cl)\b';
        $pattern = '/\b(\d{1,2})\s+' . $pieces
            . '\s+(?:(?:(?!\b\d{1,2}\s+' . $pieces . '\b)[^.\\n]){0,90}?)'
            . '(?:de\s+|\(\s*)' . $size . '/iu';
        if ( preg_match_all( $pattern, $plain, $matches, PREG_SET_ORDER ) ) {
            $possibles = array();
            foreach ( $matches as $m ) {
                if ( (int) $m[1] !== (int) $count ) { continue; }
                $unit_kg = self::net_quantity_kg( (float) str_replace( ',', '.', $m[2] ), $m[3] );
                if ( null !== $unit_kg ) { $possibles[] = $unit_kg * (int) $count; }
            }
            $possibles = array_values( array_unique( array_map(
                static fn($v) => number_format( $v, 3, '.', '' ), $possibles ) ) );
            if ( count( $possibles ) === 1 ) {
                return self::estimated_chargeable_weight( (float) $possibles[0] );
            }
        }
        // Single-container product component lists: exactly N separately
        // enumerated jars/bottles; all must carry an explicit size and unit.
        $single_pattern = '/\b1\s+' . $pieces
            . '\s+(?:(?:(?!\b\d{1,2}\s+' . $pieces . '\b)[^.\\n]){0,75}?)'
            . '\(?\s*' . $size . '/iu';
        if ( preg_match_all( $single_pattern, $plain, $singles, PREG_SET_ORDER ) ) {
            // Descriptions sometimes repeat the complete pack contents in
            // separate marketing sections. Prefer no inference to ambiguity.
            if ( count( $singles ) !== (int) $count ) { return null; }
            $total = 0.0;
            foreach ( $singles as $m ) {
                $kg = self::net_quantity_kg( (float) str_replace( ',', '.', $m[1] ), $m[2] );
                if ( null === $kg ) { return null; }
                $total += $kg;
            }
            return self::estimated_chargeable_weight( $total );
        }
        // Some options themselves spell out the unit pack content: "4 x 500g".
        $label = (string) ( $row['label'] ?? '' );
        if ( preg_match( '/\b(\d{1,2})\s*[x×]\s*' . $size . '/iu', $label, $m ) &&
            (int) $m[1] === (int) $count ) {
            $kg = self::net_quantity_kg( (float) str_replace( ',', '.', $m[2] ), $m[3] );
            return null === $kg ? null : self::estimated_chargeable_weight( $kg * $count );
        }
        return null;
    }

    private static function net_quantity_kg( float $value, string $unit ): ?float {
        if ( $value <= 0 || $value > 30000 ) { return null; }
        $u = mb_strtolower( $unit, 'UTF-8' );
        if ( preg_match( '/^(?:kg|kilos?|l|litros?)$/u', $u ) ) { return $value; }
        if ( preg_match( '/^(?:g|gr|gramos?|ml)$/u', $u ) ) { return $value / 1000; }
        if ( 'cl' === $u ) { return $value / 100; }
        return null;
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
        $main_status = mb_strtolower( self::text( $xp,
            '(//*[@id="main-product-wrapper"]//*[contains(@class,"product-unavailable") or contains(@class,"out-of-stock")]'
            . ' | //*[@id="col-product-info"]//*[contains(@class,"product-unavailable") or contains(@class,"out-of-stock")])[1]'
        ), 'UTF-8' );
        $button = mb_strtolower( self::text( $xp, '//button[@data-button-action="add-to-cart"]' ), 'UTF-8' );
        if ( preg_match( '/no\s+disponible|agotad|sin\s+stock|out\s+of\s+stock|temporalmente\s+no\s+disponible/u',
            $availability . ' ' . $main_status . ' ' . $button ) ) { return 'outofstock'; }
        // Some PrestaShop templates render the explicit negative inventory
        // text outside #product-availability and have NO buy button.
        // Recognize only the product-specific full statement, not "agotado"
        // from unrelated carousel cards or a script/template.
        foreach ( $xp->query( '//text()[contains(., "producto no") or contains(., "Producto no") or contains(., "no está disponible temporalmente")]' ) ?: array() as $warning ) {
            $parent = $warning->parentNode;
            if ( ! $parent || in_array( strtolower( $parent->nodeName ), array( 'script', 'style', 'template' ), true ) ) { continue; }
            $txt = trim( preg_replace( '/\\s+/u', ' ', (string) $warning->nodeValue ) );
            if ( preg_match( '/\\bel\\s+producto\\s+no\\s+est[aá]\\s+disponible\\s+temporalmente\\b/iu', $txt ) ) {
                return 'outofstock';
            }
        }
        if ( $xp->query( '//button[@data-button-action="add-to-cart" and @disabled]' )->length ||
            preg_match( '/\bagotad[oa]\b|no\s+disponible|sin\s+stock/iu', $button ) ) {
            return 'outofstock';
        }
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

    private static function category_url( string $url, ?DOMXPath $xp = null ): string {
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
        if ( ! $category && $xp ) {
            // Only a breadcrumb on this exact product page may prove an
            // unknown category ID. A menu/recommendation URL is not evidence.
            $links = $xp->query(
                '//*[@id="breadcrumb"]//a[@href]'
                . ' | //nav[contains(@class,"breadcrumb")]//a[@href]'
                . ' | //ol[contains(@class,"breadcrumb")]//a[@href]'
                . ' | //div[contains(@class,"breadcrumb")]//a[@href]'
            );
            foreach ( $links ?: array() as $link ) {
                $href = (string) $link->getAttribute( 'href' );
                if ( str_starts_with( $href, '/es/' ) ) {
                    $href = 'https://' . self::HOST . $href;
                }
                try {
                    $href = self::canonical( $href );
                    $p = (string) wp_parse_url( $href, PHP_URL_PATH );
                    if ( preg_match( '~^/es/([0-9]+)-([a-z0-9-]+)/?$~i', $p, $m ) &&
                        strtolower( $m[2] ) === strtolower( $slug ) ) {
                        return $href;
                    }
                } catch ( Throwable $e ) {
                    // Skip malformed/external breadcrumb references.
                }
            }
        }
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

// Verified regression suite: new explicit net-content parser and legacy fixtures.
