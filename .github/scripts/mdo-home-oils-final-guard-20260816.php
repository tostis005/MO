<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'template_redirect', static function (): void {
    if ( is_admin() || ! is_front_page() ) { return; }

    $uri   = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
    $path  = (string) wp_parse_url( $uri, PHP_URL_PATH );
    $is_en = (bool) preg_match( '#^/en(?:/|$)#i', $path );

    ob_start( static function ( string $html ) use ( $is_en ): string {
        if ( false === strpos( $html, 'emo-category-grid' ) ) { return $html; }

        $target_name = $is_en ? 'Oils' : 'Aceites';
        $has_target  = (bool) preg_match( '~<strong[^>]*>\s*' . preg_quote( $target_name, '~' ) . '\s*</strong>~i', $html );

        if ( ! $has_target ) {
            $term = get_term_by( 'slug', 'aceites', 'product_cat' );
            if ( $term instanceof WP_Term ) {
                $count = function_exists( 'elmercado_catalog_visible_category_count_010217' )
                    ? (int) elmercado_catalog_visible_category_count_010217( (int) $term->term_id )
                    : (int) $term->count;

                if ( $count > 0 ) {
                    if ( $is_en ) {
                        $link  = home_url( '/en/product-category/oils/' );
                        $label = number_format_i18n( $count ) . ( 1 === $count ? ' product' : ' products' );
                    } else {
                        $link = get_term_link( $term );
                        if ( is_wp_error( $link ) ) { $link = home_url( '/categoria-producto/aceites/' ); }
                        $label = sprintf( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ), number_format_i18n( $count ) );
                    }

                    $thumbnail_id = (int) get_term_meta( (int) $term->term_id, 'thumbnail_id', true );
                    $image        = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
                    $style        = $image ? ' style="--emo-category-image:url(' . esc_url( $image ) . ')"' : '';

                    $card  = '<a class="emo-category-card" href="' . esc_url( $link ) . '"' . $style . ' data-mdo-oils-restored="20260816">';
                    $card .= '<span class="emo-category-card__media" aria-hidden="true"></span>';
                    $card .= '<div class="emo-category-card__content"><strong>' . esc_html( $target_name ) . '</strong><small>' . esc_html( $label ) . '</small></div>';
                    $card .= '</a>';

                    $section_start = strpos( $html, '<section class="emo-section emo-categories"' );
                    if ( false !== $section_start ) {
                        $section_end = strpos( $html, '</section>', $section_start );
                        if ( false !== $section_end ) {
                            $section_end += strlen( '</section>' );
                            $section = substr( $html, $section_start, $section_end - $section_start );
                            if ( false !== strpos( $section, '<div class="emo-category-grid"' ) && preg_match( '~</div>\s*</div>\s*</section>\s*$~s', $section, $m, PREG_OFFSET_CAPTURE ) ) {
                                $section = substr_replace( $section, $card, (int) $m[0][1], 0 );
                                $html = substr_replace( $html, $section, $section_start, $section_end - $section_start );
                            }
                        }
                    }
                }
            }
        }

        if ( false === strpos( $html, 'mdo-home-category-mobile-visible-20260816' ) ) {
            $css = '<style id="mdo-home-category-mobile-visible-20260816">@media(max-width:640px){html body.home .emo-home .emo-categories .emo-category-grid{display:grid!important;grid-template-columns:minmax(0,1fr)!important;width:100%!important;max-width:100%!important;margin-right:0!important;gap:14px!important;overflow:visible!important;overflow-x:visible!important;padding-right:0!important;scroll-snap-type:none!important;box-sizing:border-box!important}html body.home .emo-home .emo-categories .emo-category-grid>.emo-category-card{display:flex!important;width:100%!important;min-width:0!important;max-width:100%!important;flex:none!important;height:clamp(180px,45vw,260px)!important;min-height:180px!important;aspect-ratio:auto!important;scroll-snap-align:none!important;box-sizing:border-box!important}}</style>';
            $head_end = stripos( $html, '</head>' );
            if ( false !== $head_end ) { $html = substr_replace( $html, $css, $head_end, 0 ); }
            else { $html .= $css; }
        }

        return $html;
    } );
}, -5000 );
