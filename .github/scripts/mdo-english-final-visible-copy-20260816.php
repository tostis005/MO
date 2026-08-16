<?php
/**
 * Final English-only visible text cleanup for hard-coded theme/vendor UI copy.
 * Native Spanish output is never modified.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_final_en_request_20260816(): bool {
    $path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH );
    return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

add_action( 'plugins_loaded', static function (): void {
    if ( ! mdo_final_en_request_20260816() ) { return; }
    ob_start( static function ( string $html ): string {
        $pairs = array(
            'Categorías del producto' => 'Product categories',
            'Etiquetas del producto' => 'Product tags',
            'Tipo de pieza' => 'Piece type',
            'Tipo de producto' => 'Product type',
            'Calidad' => 'Quality',
            'Raza ibérica' => 'Iberian breed',
            'Alimentación' => 'Feeding',
            'Denominación de origen' => 'Designation of origin',
            'Preparación' => 'Preparation',
            'Curación' => 'Curing',
            'Tamaño' => 'Size',
            'Variedad' => 'Variety',
            'Productor' => 'Producer',
            'Origen' => 'Origin',
            'Peso' => 'Weight',
            'Con DOP' => 'PDO',
            'IVA incl.' => 'VAT incl.',
            'IVA' => 'VAT',
            'El plazo de preparación y envío es de varios días dependiendo de la demanda del momento' => 'Preparation and dispatch take several days, depending on current demand.',
            'El resultado es una pieza de carne limpia, que se puede presentar en trozos, ya sea para lonchear fácilmente en casa o para ser envasada al vacío y dividida en porciones.' => 'The result is a clean, boneless piece of meat that can be cut into portions, either for easy slicing at home or for vacuum-packing and portioning.',
            'Ocupa menos espacio y se puede envasar al vacío de manera más eficaz, lo que ayuda a conservar su sabor y textura por más tiempo.' => 'It takes up less space and can be vacuum-packed more efficiently, helping preserve its flavour and texture for longer.',
        );

        // Replace only text between HTML tags. This avoids modifying URLs, classes or JS identifiers.
        foreach ( $pairs as $es => $en ) {
            $quoted = preg_quote( $es, '#' );
            $html = (string) preg_replace( '#>(\s*)' . $quoted . '(\s*)<#u', '>$1' . $en . '$2<', $html );
        }
        return $html;
    } );
}, -PHP_INT_MAX + 50 );
