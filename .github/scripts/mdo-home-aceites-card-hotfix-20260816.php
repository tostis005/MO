<?php
/**
 * Temporary production guard: ensure the Spanish Home category grid includes Aceites.
 * Safe to keep when the newer theme category renderer is deployed: it becomes a no-op
 * as soon as an Aceites card already exists.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'template_redirect', static function (): void {
    if ( is_admin() || ! is_front_page() ) { return; }

    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( preg_match( '#^/en(?:/|$)#i', $path ) ) { return; }

    ob_start( static function ( string $html ): string {
        if ( false === strpos( $html, 'emo-category-grid' ) || preg_match( '~<strong[^>]*>\s*Aceites\s*</strong>~i', $html ) ) {
            return $html;
        }

        $term = get_term_by( 'slug', 'aceites', 'product_cat' );
        if ( ! $term instanceof WP_Term ) { return $html; }

        $count = function_exists( 'elmercado_catalog_visible_category_count_010217' )
            ? (int) elmercado_catalog_visible_category_count_010217( (int) $term->term_id )
            : (int) $term->count;
        if ( $count <= 0 ) { return $html; }

        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) { return $html; }

        $thumbnail_id = (int) get_term_meta( (int) $term->term_id, 'thumbnail_id', true );
        $image = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
        $style = $image ? ' style="--emo-category-image:url(' . esc_url( $image ) . ')"' : '';
        $label = sprintf(
            _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ),
            number_format_i18n( $count )
        );

        $card  = '<a class="emo-category-card" href="' . esc_url( $link ) . '"' . $style . ' data-mdo-aceites-restored="20260816">';
        $card .= '<span class="emo-category-card__media" aria-hidden="true"></span>';
        $card .= '<div class="emo-category-card__content"><strong>' . esc_html( $term->name ) . '</strong><small>' . esc_html( $label ) . '</small></div>';
        $card .= '</a>';

        $section_start = strpos( $html, '<section class="emo-section emo-categories"' );
        if ( false === $section_start ) { return $html; }
        $section_end = strpos( $html, '</section>', $section_start );
        if ( false === $section_end ) { return $html; }
        $section_end += strlen( '</section>' );
        $section = substr( $html, $section_start, $section_end - $section_start );

        $grid_start = strpos( $section, '<div class="emo-category-grid"' );
        if ( false === $grid_start ) { return $html; }

        // The grid is the penultimate div in this section (grid, then shell).
        if ( ! preg_match( '~</div>\s*</div>\s*</section>\s*$~s', $section, $m, PREG_OFFSET_CAPTURE ) ) {
            return $html;
        }
        $insert_at = (int) $m[0][1];
        $section = substr_replace( $section, $card, $insert_at, 0 );

        return substr_replace( $html, $section, $section_start, $section_end - $section_start );
    } );
}, -4000 );
