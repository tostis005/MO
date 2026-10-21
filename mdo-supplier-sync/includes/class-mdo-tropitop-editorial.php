<?php
/**
 * Tropitop-only editorial boundary between the producer's webshop copy
 * and El Mercado de Origen's own product pages.
 *
 * No factual claims are invented. Original supplier HTML remains in
 * source_products.source_payload, and prices, stock, weights and options
 * are never touched here.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Tropitop_Editorial {
    public static function init(): void {
        add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy_vegetable_category' ), 1 );
    }

    public static function redirect_legacy_vegetable_category(): void {
        $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
        // The former mixed Gourmet shelf has been replaced with verified
        // product families. Keep its indexed URL valid by redirecting to the
        // main catalog, rather than serving an empty archive or a 404.
        if ( preg_match( '~^/categoria-producto/gourmet/?$~i', $path ) &&
            function_exists( 'wc_get_page_permalink' ) ) {
            $shop = wc_get_page_permalink( 'shop' );
            if ( $shop && ! is_wp_error( $shop ) ) {
                wp_safe_redirect( $shop, 301, 'El Mercado de Origen' );
                exit;
            }
        }
        if ( ! preg_match( '~^/categoria-producto/verduras/?$~i', $path ) ) {
            return;
        }
        $term = get_term_by( 'slug', 'hortalizas-verduras', 'product_cat' );
        if ( ! $term instanceof WP_Term ) { return; }
        $url = get_term_link( $term, 'product_cat' );
        if ( ! is_wp_error( $url ) ) {
            wp_safe_redirect( $url, 301, 'El Mercado de Origen' );
            exit;
        }
    }

    private const SOURCE_LINK = '~https?://(?:www\.)?tropitop\.com(?:/[^"<> ]*)?~iu';

    public static function title( string $title ): string {
        // A single source SEO title incorrectly names the supplier webshop
        // instead of the item. Keep the existing product URL unchanged.
        if ( preg_match( '/^cerezas\s+online\s+en\s+Tropitop\s*\|\s*cerezas\s+frescas\s+de\s+castell[oó]n$/iu', trim( $title ) ) ) {
            return 'Cerezas frescas de Castellón';
        }
        return $title;
    }

    /**
     * Remove the source retailer's promotional voice, not independently useful
     * ingredients, origins, sizes, storage guidance or product descriptions.
     * Works on existing Woo HTML as well as on new supplier imports.
     */
    public static function marketplace_voice( string $html ): string {
        $html = preg_replace(
            '/\bNo\s+trabajamos\s+un\s+calibre\s+espec[ií]fico\.?/iu',
            'No se selecciona un calibre específico.', $html
        );
        // The supplier's relationship/endorsement is not the farmer's name.
        $html = preg_replace( '/,\s*agricultor\s+de\s+confianza\s+de\s+Tropitop\b/iu', '', $html );
        $html = preg_replace( '~\bIdeal\s+para\s+<strong>\s*comprar\s+[^<]{0,110}\s+online\s*</strong>\s*\.?~iu', '', $html );

        $html = preg_replace_callback(
            '~<(p|h[2-6])\b([^>]*)>(.*?)</\1>~isu',
            static function ( array $m ): string {
                $plain = trim( html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
                if ( '' === $plain ) { return ''; }
                $heading = strtolower( $m[1] ) !== 'p';
                // Remove only checkout/SEO headings. Following product facts,
                // care instructions and FAQ answers remain in place.
                if ( $heading && preg_match(
                    '/^(?:(?:comprar|compra)\b|faqs?\s+sobre\s+comprar\b|preguntas\s+frecuentes\s+(?:sobre|al)\s+comprar\b)/iu',
                    $plain
                ) ) { return ''; }
                if ( preg_match(
                    '/\b(?:Tropitop\s+(?:ofrece|presenta|env[ií]a|garantiza|selecciona)\b|'
                    . '(?:elegir|con)\s+Tropitop\b|'
                    . '(?:trabajamos|colaboramos|apostamos|nos\s+enorgullece|nos\s+comprometemos|'
                    . 'conseguimos|contamos\s+con|te\s+ofrecemos|aceptamos\s+pagos)\b|'
                    . '(?:comprar|compra)\s+[^.!?]{0,110}\bonline\b|'
                    . 'al\s+comprar\s+directamente\s+(?:del|al)\s+(?:campo|agricultor)\b|'
                    . '(?:compra|comprar)\s+[^.!?]{0,100}\bdirectamente\s+(?:del|al)\s+(?:agricultor|productor)\b)'
                    . '/iu', $plain
                ) ) {
                    return '';
                }
                return $m[0];
            },
            $html
        );
        // Remove offsite-sales persuasion from retained specification lists;
        // a measurement or origin in the same bullet is preserved.
        $html = preg_replace( '/\bIdeal\s+para\s+comprar\s+[^.]{0,140}\bonline\.?/iu', '', $html );
        // A small number of supplier pack bullets start with SEO labels like
        // "Comprar sandía online:" followed by useful variant information.
        // Delete the label alone, preserving the choice/format sentence.
        $html = preg_replace( '/\bComprar\s+[^:<]{2,100}\s+online\s*:\s*/iu', '', $html );
        $html = preg_replace( '~<strong\b[^>]*>\s*</strong>~iu', '', $html );

        return trim( (string) preg_replace( '~<(p|div|h[1-6])\b[^>]*>\s*</\1>~iu', '', $html ) );
    }

    public static function description( string $html, string $title = '', string $short = '' ): string {
        $clean = self::neutralize( $html );
        // A factual producer-identity clause in one mixed box is not a
        // checkout promise. Keep the product description, not the vendor voice.
        if ( preg_match( '/aguacates hass y mermelada de guayaba/iu', $title ) ) {
            $clean = preg_replace( '/hecha aqu[ií] en Espa[ñn]a por nosotros, los propios agricultores/iu',
                'elaborada en España', $clean );
        }

        // These three physical boxes explicitly support "Pedido único" at
        // source. Their original HTML is mostly subscription instructions,
        // phone/WhatsApp contacts and claims about the producer's fulfilment
        // timetable. On EMDO no subscription contract is sold: replace only
        // these three pages with individual-order, source-grounded factual copy.
        $seasonal = array(
            'caja de fruta de temporada' => array(
                'frutas', 'fruta fresca de temporada', 'frutas disponibles',
                'fruta para consumir en casa durante la semana'
            ),
            'caja de verdura de temporada' => array(
                'verduras y hortalizas', 'verdura fresca de temporada', 'hortalizas disponibles',
                'verduras para las comidas de la semana'
            ),
            'caja de frutas y verduras de temporada' => array(
                'frutas y verduras', 'fruta y verdura fresca de temporada', 'productos disponibles',
                'una combinación de fruta para consumir y verdura para cocinar'
            ),
        );
        $key = mb_strtolower(trim($title), 'UTF-8');
        if ( isset($seasonal[$key]) ) {
            $v = $seasonal[$key];
            $clean = '<h2>Una caja de ' . esc_html($v[0]) . ' de temporada</h2>'
              . '<p>Selección de ' . esc_html($v[1]) . ' de origen español, '
              . 'preparada según la disponibilidad de la cosecha. El contenido '
              . 'puede cambiar durante el año y, si falta alguna variedad, '
              . 'puede sustituirse por otra o ajustarse la cantidad de las disponibles.</p>'
              . '<h2>Formatos y pedido</h2>'
              . '<p>Escoge la caja pequeña de 4 kg, la mediana de 9 kg o la grande '
              . 'de 14 kg, según la cantidad que necesites. En El Mercado de Origen '
              . 'la compra es puntual: no activa una suscripción ni pagos automáticos. '
              . 'El precio y el envío aplicables se muestran al seleccionar el tamaño.</p>'
              . '<h2>Qué conviene tener en cuenta</h2>'
              . '<p>La selección reúne ' . esc_html($v[2]) . ' en el momento '
              . 'de preparación. Al tratarse de cosechas de temporada, pueden variar '
              . 'las variedades, el calibre y el grado de maduración. '
              . 'Para elegir el tamaño, valora cuántas personas consumirán '
              . esc_html($v[3]) . '.</p>';
        }
        if ( '' !== trim( wp_strip_all_tags( $clean ) ) ) {
            return $clean;
        }

        // A source section consisting solely of self-promotion must not be
        // reproduced on EMDO. Use only an independently available source
        // short description, and flag any remaining shortfall in the audit.
        $safe_short = self::short_description( $short );
        if ( '' !== trim( wp_strip_all_tags( $safe_short ) ) ) {
            return '<p>' . esc_html( wp_strip_all_tags( $safe_short ) ) . '</p>';
        }
        return '';
    }

    public static function short_description( string $html ): string {
        return self::neutralize( $html );
    }

    public static function residual_markers( string $html ): array {
        $plain = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $flags = array();
        if ( preg_match( '/\b(?:nuestr(?:o|os|a|as)|nosotro(?:s|as)|con nosotros|te ofrecemos|te enviamos|trabajamos con|colaboramos con|apostamos por|cultivamos|recolectamos|enviamos|ofrecemos|seleccionamos|garantizamos|cosechamos|preparamos|promovemos|respetamos|cuidamos|creemos|somos|ponemos|producimos|vendemos|descubrimos|nos\s+(?:permite|ayuda|asegura)|ajustamos|cont[aá]ctanos|escr[ií]benos|visita nuestra|en nuestra tienda|nuestra web)\b/iu', $plain ) ) {
            $flags[] = 'supplier_first_person';
        }
        if ( preg_match( self::SOURCE_LINK, $html ) ||
            preg_match( '/\b(?:en\s+(?:la|su)\s+web|seg[uú]n\s+(?:la|su)\s+(?:web|p[aá]gina|ficha)|en\s+(?:la|su)\s+tienda\s+(?:online|en\s+l[ií]nea))\b/iu', $plain ) ) {
            $flags[] = 'outside_shop_reference';
        }
        if ( preg_match( '/\b(?:compra\s+(?:ahora|directamente)\s+en\s+tropitop|pedidos?\s+por\s+whatsapp|suscr[ií]bete)\b/iu', $plain ) ) {
            $flags[] = 'external_call_to_action';
        }
        if ( preg_match( '/\b(?:en\s+(?:la\s+)?tienda\s+de\s+Tropitop|en\s+Tropitop|que\s+vende\s+Tropitop|(?:comprar|compras?|adquirir)\s+[^.]{0,90}\ben\s+Tropitop)\b/iu', $plain ) ) {
            $flags[] = 'producer_as_external_retailer';
        }
        return $flags;
    }

    private static function neutralize( string $html ): string {
        $html = wp_kses_post( $html );
        if ( '' === trim( $html ) ) { return ''; }


        // Product source headings often describe the producer as the checkout
        // retailer. Preserve factual pack sections but remove external-store
        // attribution. Do not invent origins or rewrite commercial conditions.
        $html = preg_replace_callback(
            '~<h([2-6])\b([^>]*)>(.*?)</h\1>~isu',
            static function ( array $m ): string {
                $heading = trim( html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
                if ( ! preg_match( '/\bTropitop\b/iu', $heading ) &&
                    ! preg_match( '/\b(?:comprar|compra)\b.*\b(?:agricultor|productor)\b/iu', $heading ) ) {
                    return $m[0];
                }
                if ( preg_match( '/\b(?:qu[eé]\s+(?:incluye|contiene|lleva)|contenido\s+de)\b/iu', $heading ) ) {
                    return '<h' . $m[1] . '>Contenido de la caja</h' . $m[1] . '>';
                }
                // Remove promotional, origin-attribution and external-store
                // headings; the following independently factual text survives.
                return '';
            },
            $html
        );
        $html = preg_replace(
            '/\bAgricultor:\s*([^<.,;]+),\s*agricultor\s+de\s+confianza\s+de\s+Tropitop\b/iu',
            'Agricultor: $1',
            $html
        );
        $html = str_ireplace(
            'Ajustamos siempre el contenido para alcanzar el peso total.',
            'El contenido se ajusta para alcanzar el peso total.',
            $html
        );

        // Remove broad SEO/advertising sections, which are not product
        // specifications. Preserve the product's factual ingredient,
        // format, origin, preparation and conservation sections.
        $html = preg_replace(
            '~<h([2-6])\b[^>]*>\s*(?:(?:[^<]|<[^>]*>)*?)\b(?:beneficios\s+de|agricultores\s+reales|compra\s+(?:online|directamente)|por\s+qu[eé]\s+comprar|de\s+d[oó]nde\s+proceden(?:\s+nuestr[oa]s?|[^<]{0,140}\bTropitop\b)|sobre\s+(?:nosotros|nuestra\s+tienda))\b.*?</h\1>(?:(?!<h[2-6]\b).)*~isu',
            '',
            $html
        );

        // Remove outbound checkout/contact links while keeping legitimate
        // factual wording for cases such as producer names or origin.
        $html = preg_replace_callback(
            '~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1[^>]*>(.*?)</a>~isu',
            static function ( array $m ): string {
                $href = html_entity_decode( $m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                if ( preg_match( '~^(?:mailto:|tel:|https?://(?:www\.)?tropitop\.com(?:/|$))~iu', $href ) ) {
                    $label = trim( wp_strip_all_tags( $m[3] ) );
                    return preg_match( '/\b(?:comprar|compra|compras?|adquiere|tienda|contactar|ver\s+m[aá]s|haz\s+tu\s+pedido|descubre\s+m[aá]s)\b/iu', $label )
                        ? '' : esc_html( $label );
                }
                return $m[0];
            },
            $html
        );

        // First-person supplier store paragraphs are not EMDO store copy.
        // Removing the whole paragraph, rather than changing "nosotros" into
        // "EMDO", avoids inventing merchant responsibilities or provenance.
        $html = preg_replace_callback(
            '~<(p|li|h[2-6])\b([^>]*)>(.*?)</\1>~isu',
            static function ( array $m ): string {
                $plain = html_entity_decode( wp_strip_all_tags( $m[3] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                if ( preg_match( '/\b(?:nuestr(?:o|os|a|as)|nosotro(?:s|as)|te\s+(?:ofrecemos|enviamos|recomendamos)|trabajamos\s+con|colaboramos\s+con|apostamos\s+por|cultivamos|recolectamos|enviamos|ofrecemos|seleccionamos|garantizamos|cosechamos|preparamos|promovemos|respetamos|cuidamos|creemos|somos|ponemos|producimos|vendemos|descubrimos|nos\s+(?:permite|ayuda|asegura)|ajustamos|disponemos\s+de|cont[aá]ctanos|escr[ií]benos|nuestra\s+(?:web|tienda)|en\s+tropitop\s+puedes\s+comprar|compra\s+directamente\s+en\s+tropitop)\b/iu', $plain ) ||
                    preg_match( '/\b(?:haz\s+tu\s+pedido|visita\s+(?:nuestra|la)\s+(?:web|tienda)|ll[aá]manos|consulta\s+(?:nuestro|el)\s+cat[aá]logo)\b/iu', $plain ) ) {
                    return '';
                }
                return $m[0];
            },
            $html
        );

        // Some PrestaShop descriptions contain plain text instead of <p>.
        if ( ! preg_match( '~<(?:p|li|h[2-6]|div|ul|ol|table)\b~i', $html ) &&
            self::residual_markers( $html ) ) {
            return '';
        }

        // Neutral fact attribution may still be copied inside surviving
        // specifications (without ever redirecting checkout to the producer).
        $html = preg_replace(
            '/\bseg[uú]n\s+(?:la\s+)?(?:web|p[aá]gina|ficha|cat[aá]logo)\s+(?:de(?:l|\s+la)\s+)?(?:productor|fabricante|proveedor|Tropitop)\b\s*,?\s*/iu',
            '',
            $html
        );
        $html = preg_replace( '~<(p|div|ul|ol|h[2-6])\b[^>]*>\s*</\1>~iu', '', $html );
        $html = preg_replace( '~(?:<br\s*/?>\s*){3,}~iu', '<br>', $html );

        // Product descriptions imported from PrestaShop may contain loose
        // unwrapped prose (not <p> tags). Remove only sentences presenting
        // Tropitop as the checkout retailer and retain the independent factual
        // descriptions, ingredients, formats and storage information.
        $html = preg_replace(
            '/\\bEn\\s+Tropitop\\s+(?:trabajamos|colaboramos|seleccionamos|promovemos|ofrecemos|cultivamos|valoramos|recolectamos|garantizamos|cuidamos)\\b[^.!?]{0,400}[.!?]/iu',
            '', $html
        );
        $html = preg_replace(
            '/\\b(?:en\\s+(?:la\\s+)?tienda\\s+de\\s+Tropitop|en\\s+Tropitop|que\\s+vende\\s+Tropitop)\\b/iu',
            '', $html
        );
        $html = preg_replace( '~<(p|div|h[1-6])\\b[^>]*>\\s*</\\1>~iu', '', $html );

        // Do not silently publish residual first-person or webshop copy:
        // caller's production audit will report every remaining occurrence.
        $html = self::marketplace_voice( $html );
        return trim( wp_kses_post( (string) $html ) );
    }
}
