<?php
/**
 * MDO English Island Runtime + SEO Bridge.
 * English-only presentation layer. Native Spanish objects/slugs/content are never modified.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mdo_island_en_request(): bool {
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return (bool) preg_match( '#^/en(?:/|$)#i', $path );
}

function mdo_island_root(): string {
    $root = untrailingslashit( (string) get_option( 'home' ) );
    $root = preg_replace( '#/en/?$#i', '', $root );
    return untrailingslashit( (string) $root );
}

function mdo_island_post_published( int $post_id ): bool {
    return (string) get_post_meta( $post_id, '_en_US_published', true ) === '1';
}

function mdo_island_post_meta( int $post_id, string $field ): string {
    if ( ! mdo_island_post_published( $post_id ) ) { return ''; }
    return (string) get_post_meta( $post_id, '_en_US_' . $field, true );
}

function mdo_island_clean_title( string $title ): string {
    return trim( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

function mdo_island_english_description(): string {
    if ( ! mdo_island_en_request() ) { return ''; }

    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return 'Shop food and artisan products directly from carefully selected producers at El Mercado de Origen.';
    }

    if ( is_tax( array( 'product_cat', 'product_tag' ) ) ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $value = (string) get_term_meta( $term->term_id, '_en_US_description', true );
            if ( $value !== '' ) {
                $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $value ) ) ) );
                return wp_trim_words( $text, 28, '' );
            }
        }
    }

    $post_id = (int) get_queried_object_id();
    if ( $post_id > 0 && get_post( $post_id ) ) {
        $excerpt = mdo_island_post_meta( $post_id, 'post_excerpt' );
        $content = mdo_island_post_meta( $post_id, 'post_content' );
        $text    = $excerpt !== '' ? $excerpt : $content;
        if ( $text !== '' ) {
            $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );
            if ( $text !== '' ) { return wp_trim_words( $text, 28, '' ); }
        }
    }

    $path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH );
    if ( preg_match( '#^/en/store/([^/]+)/?#i', $path, $m ) ) {
        $name = ucwords( str_replace( '-', ' ', sanitize_title( $m[1] ) ) );
        return 'Shop products from ' . $name . ' at El Mercado de Origen.';
    }
    if ( $path === '/en/' || $path === '/en' ) {
        return 'Discover food and artisan products directly from selected producers at El Mercado de Origen.';
    }
    return '';
}

function mdo_island_native_spanish_url(): string {
    $root = mdo_island_root();
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/en/';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );

    if ( $path === '/en' || $path === '/en/' ) { return $root . '/'; }

    if ( preg_match( '#^/en/store/([^/]+)/?#i', $path, $m ) ) {
        return $root . '/tienda/' . sanitize_title( $m[1] ) . '/';
    }

    if ( function_exists( 'is_shop' ) && is_shop() ) { return $root . '/tienda/'; }

    if ( function_exists( 'is_product' ) && is_product() ) {
        $post = get_post( get_queried_object_id() );
        if ( $post instanceof WP_Post ) { return $root . '/producto/' . $post->post_name . '/'; }
    }

    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) { return $root . '/categoria-producto/' . $term->slug . '/'; }
    }

    if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) { return $root . '/etiqueta-producto/' . $term->slug . '/'; }
    }

    if ( is_home() ) {
        $posts_page = (int) get_option( 'page_for_posts' );
        $post = $posts_page ? get_post( $posts_page ) : null;
        if ( $post instanceof WP_Post ) { return $root . '/' . get_page_uri( $post ) . '/'; }
        return $root . '/';
    }

    if ( is_singular() ) {
        $post = get_post( get_queried_object_id() );
        if ( $post instanceof WP_Post ) {
            if ( $post->post_type === 'page' ) { return $root . '/' . trim( get_page_uri( $post ), '/' ) . '/'; }
            return $root . '/' . $post->post_name . '/';
        }
    }

    $without_en = preg_replace( '#^/en(?:/|$)#i', '/', $path );
    return $root . '/' . ltrim( (string) $without_en, '/' );
}

function mdo_island_english_url(): string {
    $root = mdo_island_root();
    $path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/en/', PHP_URL_PATH );
    if ( $path === '' ) { $path = '/en/'; }
    if ( substr( $path, -1 ) !== '/' && ! pathinfo( $path, PATHINFO_EXTENSION ) ) { $path .= '/'; }
    return $root . $path;
}

function mdo_island_spanish_signal( string $value ): bool {
    return (bool) preg_match( '/\b(?:añadir al carrito|pedido mínimo|importe mínimo|gastos de envío|política de privacidad|política de cookies|envíos en|elaborado a partir|información nutricional|introducción|aceite de oliva virgen extra|jamón procedente|paleta procedente|recolección|nuestros jamones|nuestras variedades|devolución fácil|resolvemos tus dudas)\b/iu', $value );
}

// Titles across pages, posts and WooCommerce product output.
add_filter( 'the_title', static function ( $title, $post_id ) {
    if ( ! mdo_island_en_request() || ! $post_id ) { return $title; }
    $translated = mdo_island_post_meta( (int) $post_id, 'post_title' );
    return $translated !== '' ? mdo_island_clean_title( $translated ) : $title;
}, PHP_INT_MAX, 2 );

add_filter( 'woocommerce_product_get_name', static function ( $name, $product ) {
    if ( ! mdo_island_en_request() || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) { return $name; }
    $translated = mdo_island_post_meta( (int) $product->get_id(), 'post_title' );
    return $translated !== '' ? mdo_island_clean_title( $translated ) : $name;
}, PHP_INT_MAX, 2 );

// Main singular content: serve the reviewed English copy stored in _en_US_* metadata.
add_filter( 'the_content', static function ( $content ) {
    if ( ! mdo_island_en_request() || ! is_singular() ) { return $content; }
    // Home and WooCommerce system pages are dynamic. Replacing their content here runs
    // after do_shortcode(), which would expose literal [woocommerce_*] shortcodes and
    // break cart, checkout and account behaviour. Keep the native executable content
    // and let TranslatePress translate the rendered output instead.
    if (
        is_front_page()
        || ( function_exists( 'is_cart' ) && is_cart() )
        || ( function_exists( 'is_checkout' ) && is_checkout() )
        || ( function_exists( 'is_account_page' ) && is_account_page() )
    ) {
        return $content;
    }
    $post_id = (int) get_the_ID();
    if ( $post_id < 1 || $post_id !== (int) get_queried_object_id() ) { return $content; }
    $translated = mdo_island_post_meta( $post_id, 'post_content' );
    // Reviewed English page copy can contain functional shortcodes (for example Contact Form 7).
    // This filter runs after WordPress' normal shortcode pass, so execute shortcodes in the reviewed copy here.
    return $translated !== '' ? do_shortcode( $translated ) : $content;
}, PHP_INT_MAX );

add_filter( 'get_the_excerpt', static function ( $excerpt, $post ) {
    if ( ! mdo_island_en_request() ) { return $excerpt; }
    $post_id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
    if ( $post_id < 1 ) { return $excerpt; }
    $translated = mdo_island_post_meta( $post_id, 'post_excerpt' );
    return $translated !== '' ? wp_strip_all_tags( $translated ) : $excerpt;
}, PHP_INT_MAX, 2 );

add_filter( 'woocommerce_short_description', static function ( $description ) {
    if ( ! mdo_island_en_request() ) { return $description; }
    $post_id = (int) get_the_ID();
    $translated = $post_id ? mdo_island_post_meta( $post_id, 'post_excerpt' ) : '';
    return $translated !== '' ? wpautop( $translated ) : $description;
}, PHP_INT_MAX );

// Product-category and product-tag names/descriptions.
add_filter( 'get_term', static function ( $term, $taxonomy ) {
    if ( ! mdo_island_en_request() || ! $term instanceof WP_Term || ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) { return $term; }
    $copy = clone $term;
    $name = (string) get_term_meta( $term->term_id, '_en_US_name', true );
    $desc = (string) get_term_meta( $term->term_id, '_en_US_description', true );
    if ( $name !== '' ) { $copy->name = mdo_island_clean_title( $name ); }
    if ( $desc !== '' ) { $copy->description = $desc; }
    return $copy;
}, PHP_INT_MAX, 2 );

add_filter( 'term_description', static function ( $description, $term_id, $taxonomy ) {
    if ( ! mdo_island_en_request() || ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) { return $description; }
    $translated = (string) get_term_meta( (int) $term_id, '_en_US_description', true );
    return $translated !== '' ? $translated : $description;
}, PHP_INT_MAX, 3 );

add_filter( 'get_the_archive_description', static function ( $description ) {
    if ( ! mdo_island_en_request() || ! is_tax( array( 'product_cat', 'product_tag' ) ) ) { return $description; }
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term ) { return $description; }
    $translated = (string) get_term_meta( $term->term_id, '_en_US_description', true );
    return $translated !== '' ? $translated : $description;
}, PHP_INT_MAX );

// CookieYes link is language-specific without changing the Spanish plugin settings.
add_filter( 'wt_readmore_link_settings', static function ( $settings ) {
    if ( ! mdo_island_en_request() || ! is_array( $settings ) ) { return $settings; }
    $settings['button_x_url']  = mdo_island_root() . '/en/cookie-policy/';
    $settings['button_x_text'] = 'Cookie Policy';
    return $settings;
}, PHP_INT_MAX );

// AIOSEO description and structured data must use English source copy.
add_filter( 'aioseo_description', static function ( $description ) {
    if ( ! mdo_island_en_request() ) { return $description; }
    $english = mdo_island_english_description();
    return $english !== '' ? $english : $description;
}, PHP_INT_MAX );

add_filter( 'aioseo_schema_output', static function ( $graph ) {
    if ( ! mdo_island_en_request() || ! is_array( $graph ) ) { return $graph; }
    $english = mdo_island_english_description();
    if ( $english === '' ) { return $graph; }
    $walk = static function ( &$node ) use ( &$walk, $english ): void {
        if ( ! is_array( $node ) ) { return; }
        foreach ( $node as $key => &$value ) {
            if ( is_array( $value ) ) { $walk( $value ); continue; }
            if ( is_string( $value ) && in_array( (string) $key, array( 'description', 'articleBody' ), true ) && mdo_island_spanish_signal( $value ) ) {
                $value = $english;
            }
        }
        unset( $value );
    };
    $walk( $graph );
    return $graph;
}, PHP_INT_MAX );

function mdo_island_replace_meta_content( string $tag, string $content ): string {
    $escaped = esc_attr( $content );
    if ( preg_match( '/\bcontent\s*=\s*(["\']).*?\1/is', $tag ) ) {
        return (string) preg_replace( '/\bcontent\s*=\s*(["\']).*?\1/is', 'content="' . $escaped . '"', $tag, 1 );
    }
    return rtrim( substr( $tag, 0, -1 ) ) . ' content="' . $escaped . '">';
}

function mdo_island_fix_jsonld_html( string $json, string $english ): string {
    $decoded = json_decode( html_entity_decode( $json, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
    if ( ! is_array( $decoded ) ) { return $json; }
    $walk = static function ( &$node ) use ( &$walk, $english ): void {
        if ( ! is_array( $node ) ) { return; }
        foreach ( $node as $key => &$value ) {
            if ( is_array( $value ) ) { $walk( $value ); continue; }
            if ( is_string( $value ) && in_array( (string) $key, array( 'description', 'articleBody' ), true ) && mdo_island_spanish_signal( $value ) ) { $value = $english; }
        }
        unset( $value );
    };
    $walk( $decoded );
    $encoded = wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    return is_string( $encoded ) ? $encoded : $json;
}

// Outermost English-only final HTML pass: clean hard-coded UI copy, SEO descriptions and hreflang.
add_action( 'plugins_loaded', static function (): void {
    if ( ! mdo_island_en_request() ) { return; }
    ob_start( static function ( string $html ): string {
        $english_desc = mdo_island_english_description();

        $replacements = array(
            'ENVÍO GRATIS EN VARIOS PRODUCTORES' => 'FREE SHIPPING FROM SELECT PRODUCERS',
            'Envío gratis en varios productores' => 'Free shipping from select producers',
            'ENVÍOS EN 24-48H' => '24–48H DELIVERY',
            'Envíos en 24-48h' => '24–48h delivery',
            'DEVOLUCIÓN FÁCIL Y SENCILLA' => 'EASY RETURNS',
            'Devolución fácil y sencilla' => 'Easy returns',
            'RESOLVEMOS TUS DUDAS' => "WE’RE HERE TO HELP",
            'Resolvemos tus dudas' => "We’re here to help",
            '>Política de cookies<' => '>Cookie Policy<',
            '>Política de Cookies<' => '>Cookie Policy<',
            '>Política de privacidad<' => '>Privacy Policy<',
            '>Política de Privacidad<' => '>Privacy Policy<',
        );
        $html = strtr( $html, $replacements );

        // Legacy English-visible aliases created by WCFM/other plugins.
        $shop = mdo_island_root() . '/en/shop/';
        $html = (string) preg_replace( '#href=("|\')https?://(?:www\.)?elmercadodeorigen\.com/en/tienda/([^"\']*)\1#i', 'href="' . esc_url( $shop ) . '$2"', $html );
        $html = (string) preg_replace( '#href=("|\')/en/tienda/([^"\']*)\1#i', 'href="' . esc_url( $shop ) . '$2"', $html );

        if ( $english_desc !== '' ) {
            $html = (string) preg_replace_callback( '#<meta\b[^>]*>#i', static function ( array $m ) use ( $english_desc ): string {
                $tag = $m[0];
                if ( preg_match( '/\bname\s*=\s*(["\'])description\1/i', $tag ) || preg_match( '/\bproperty\s*=\s*(["\'])og:description\1/i', $tag ) || preg_match( '/\bname\s*=\s*(["\'])twitter:description\1/i', $tag ) ) {
                    return mdo_island_replace_meta_content( $tag, $english_desc );
                }
                return $tag;
            }, $html );
            $html = (string) preg_replace_callback( '#(<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>)(.*?)(</script>)#is', static function ( array $m ) use ( $english_desc ): string {
                return $m[1] . mdo_island_fix_jsonld_html( $m[2], $english_desc ) . $m[3];
            }, $html );
        }

        // TranslatePress alternates use the English slug on the Spanish side; replace with canonical native pairs.
        $html = (string) preg_replace( '#<link\b(?=[^>]*\bhreflang\s*=)[^>]*>\s*#i', '', $html );
        $es = esc_url( mdo_island_native_spanish_url() );
        $en = esc_url( mdo_island_english_url() );
        $alternates  = "\n<link rel=\"alternate\" hreflang=\"es\" href=\"{$es}\" />";
        $alternates .= "\n<link rel=\"alternate\" hreflang=\"en\" href=\"{$en}\" />";
        $alternates .= "\n<link rel=\"alternate\" hreflang=\"x-default\" href=\"{$es}\" />\n";
        if ( stripos( $html, '</head>' ) !== false ) { $html = (string) preg_replace( '#</head>#i', $alternates . '</head>', $html, 1 ); }
        return $html;
    } );
}, -PHP_INT_MAX );
