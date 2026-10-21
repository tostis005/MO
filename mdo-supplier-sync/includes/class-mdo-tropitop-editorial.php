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

    public static function description( string $html, string $title = '', string $short = '' ): string {
        $clean = self::neutralize( $html );
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
        if ( preg_match( '/\b(?:nuestr(?:o|os|a|as)|nosotro(?:s|as)|con nosotros|te ofrecemos|te enviamos|trabajamos con|colaboramos con|apostamos por|cultivamos|recolectamos|enviamos|ofrecemos|seleccionamos|garantizamos|cosechamos|preparamos|promovemos|respetamos|cuidamos|creemos|somos|ponemos|producimos|vendemos|descubrimos|cont[aá]ctanos|escr[ií]benos|visita nuestra|en nuestra tienda|nuestra web)\b/iu', $plain ) ) {
            $flags[] = 'supplier_first_person';
        }
        if ( preg_match( self::SOURCE_LINK, $html ) ||
            preg_match( '/\b(?:en\s+(?:la|su)\s+web|seg[uú]n\s+(?:la|su)\s+(?:web|p[aá]gina|ficha)|en\s+(?:la|su)\s+tienda\s+(?:online|en\s+l[ií]nea))\b/iu', $plain ) ) {
            $flags[] = 'outside_shop_reference';
        }
        if ( preg_match( '/\b(?:compra\s+(?:ahora|directamente)\s+en\s+tropitop|pedidos?\s+por\s+whatsapp|suscr[ií]bete)\b/iu', $plain ) ) {
            $flags[] = 'external_call_to_action';
        }
        return $flags;
    }

    private static function neutralize( string $html ): string {
        $html = wp_kses_post( $html );
        if ( '' === trim( $html ) ) { return ''; }

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
                    return preg_match( '/\b(?:comprar|tienda|contactar|ver\s+m[aá]s|haz\s+tu\s+pedido|descubre\s+m[aá]s)\b/iu', $label )
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
                if ( preg_match( '/\b(?:nuestr(?:o|os|a|as)|nosotro(?:s|as)|te\s+(?:ofrecemos|enviamos|recomendamos)|trabajamos\s+con|colaboramos\s+con|apostamos\s+por|cultivamos|recolectamos|enviamos|ofrecemos|seleccionamos|garantizamos|cosechamos|preparamos|promovemos|respetamos|cuidamos|creemos|somos|ponemos|producimos|vendemos|descubrimos|disponemos\s+de|cont[aá]ctanos|escr[ií]benos|nuestra\s+(?:web|tienda)|en\s+tropitop\s+puedes\s+comprar|compra\s+directamente\s+en\s+tropitop)\b/iu', $plain ) ||
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

        // Do not silently publish residual first-person or webshop copy:
        // caller's production audit will report every remaining occurrence.
        return trim( wp_kses_post( (string) $html ) );
    }
}
