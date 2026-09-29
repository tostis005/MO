<?php
/**
 * Plugin Name: MDO Blog SEO Quick Wins 2026-09-08
 * Description: Removes duplicated in-content H1s, defers the inline newsletter, applies data-led SERP copy and reinforces contextual internal links to the highest-opportunity blog posts.
 * Version: 2026.09.29.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize visible heading text for a conservative equality check.
 */
function mdo_blog_seo_normalize_heading_20260908( $value ): string {
    $value = wp_strip_all_tags( (string) $value );
    $value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = preg_replace( '/\s+/u', ' ', trim( $value ) );
    $value = is_string( $value ) ? $value : '';

    return function_exists( 'mb_strtolower' )
        ? mb_strtolower( $value, 'UTF-8' )
        : strtolower( $value );
}

/**
 * Historical posts sometimes contain an H1 in post_content while the current
 * single-post template already prints the canonical title as H1. Remove only
 * the first in-content H1 and only when its visible text exactly matches the
 * post title after whitespace/case normalization.
 */
function mdo_blog_seo_remove_duplicate_content_h1_20260908( $content ): string {
    $content = (string) $content;

    if (
        ! is_singular( 'post' )
        || false === stripos( $content, '<h1' )
    ) {
        return $content;
    }

    $match = array();
    if ( 1 !== preg_match( '/<h1\b[^>]*>(.*?)<\/h1\s*>/isu', $content, $match, PREG_OFFSET_CAPTURE ) ) {
        return $content;
    }

    $full_h1 = isset( $match[0][0] ) ? (string) $match[0][0] : '';
    $offset   = isset( $match[0][1] ) ? (int) $match[0][1] : -1;
    $inner    = isset( $match[1][0] ) ? (string) $match[1][0] : '';

    if ( '' === $full_h1 || $offset < 0 ) {
        return $content;
    }

    $content_heading = mdo_blog_seo_normalize_heading_20260908( $inner );
    $template_title  = mdo_blog_seo_normalize_heading_20260908( get_the_title() );

    if ( '' === $content_heading || $content_heading !== $template_title ) {
        return $content;
    }

    return substr( $content, 0, $offset ) . substr( $content, $offset + strlen( $full_h1 ) );
}
add_filter( 'the_content', 'mdo_blog_seo_remove_duplicate_content_h1_20260908', 33 );

/**
 * The inline-commerce module inserts the newsletter after paragraph 3 and a
 * dedicated later anchor after paragraph 7. Keep the existing special block at
 * its early position, but place the newsletter inside the later anchor so the
 * search visitor receives the answer first.
 */
function mdo_blog_seo_defer_newsletter_20260908( $content ): string {
    $content = (string) $content;

    if (
        ! is_singular( 'post' )
        || false === strpos( $content, 'id="emo-newsletter"' )
        || false === strpos( $content, 'data-emo-newsletter-anchor' )
    ) {
        return $content;
    }

    $newsletter = array();
    if (
        1 !== preg_match(
            '/<aside\b[^>]*\bid=(?:"emo-newsletter"|\'emo-newsletter\')[^>]*>.*?<\/aside\s*>/isu',
            $content,
            $newsletter,
            PREG_OFFSET_CAPTURE
        )
    ) {
        return $content;
    }

    $newsletter_html = isset( $newsletter[0][0] ) ? (string) $newsletter[0][0] : '';
    $newsletter_pos  = isset( $newsletter[0][1] ) ? (int) $newsletter[0][1] : -1;
    if ( '' === $newsletter_html || $newsletter_pos < 0 ) {
        return $content;
    }

    $without_newsletter = substr( $content, 0, $newsletter_pos )
        . substr( $content, $newsletter_pos + strlen( $newsletter_html ) );

    $moved = preg_replace_callback(
        '/(<div\b(?=[^>]*\bdata-emo-newsletter-anchor\b)[^>]*>)\s*(<\/div\s*>)/iu',
        static function ( array $matches ) use ( $newsletter_html ): string {
            return $matches[1] . "\n" . $newsletter_html . "\n" . $matches[2];
        },
        $without_newsletter,
        1,
        $replacement_count
    );

    if ( ! is_string( $moved ) || 1 !== (int) $replacement_count ) {
        return $content;
    }

    return $moved;
}
add_filter( 'the_content', 'mdo_blog_seo_defer_newsletter_20260908', 36 );

/**
 * Return the current post slug only on a front-end singular post request.
 */
function mdo_blog_seo_public_request_uri_20260929(): string {
    if ( function_exists( 'mdoer_public_uri' ) ) {
        return (string) mdoer_public_uri();
    }

    if ( isset( $GLOBALS['mdoer_public_request_uri'] ) ) {
        return (string) $GLOBALS['mdoer_public_request_uri'];
    }

    return isset( $_SERVER['REQUEST_URI'] )
        ? (string) wp_unslash( $_SERVER['REQUEST_URI'] )
        : '';
}

function mdo_blog_seo_current_slug_20260908(): string {
    if ( is_admin() || ! is_singular( 'post' ) ) {
        return '';
    }

    /*
     * La capa inglesa resuelve /en/<slug-ingles>/ sobre el post nativo y puede
     * reescribir REQUEST_URI muy pronto. Conservamos por ello la URL pública
     * original para seleccionar title, description y clúster correctos.
     */
    $request_uri = mdo_blog_seo_public_request_uri_20260929();
    $path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $path        = trim( rawurldecode( $path ), '/' );

    if ( '' !== $path ) {
        $segments = array_values( array_filter( explode( '/', $path ) ) );
        $last     = end( $segments );

        if ( is_string( $last ) && '' !== $last ) {
            return sanitize_title( $last );
        }
    }

    $post_id = (int) get_queried_object_id();

    return $post_id > 0 ? (string) get_post_field( 'post_name', $post_id ) : '';
}

/**
 * Search Console quick wins, 2026-09-08.
 * These override only the SERP title; visible article titles and URLs stay put.
 */
function mdo_blog_seo_top3_title_20260908( $title ): string {
    $slug = mdo_blog_seo_current_slug_20260908();

    $titles = array(
        'how-much-protein-in-beef'                                               => 'Beef Protein per 100 g: Amounts by Serving',
        'hay-que-poner-lentejas-en-remojo-cuanto-tiempo'                         => '¿Hay que remojar las lentejas? Tiempo de remojo',
        'garbanzos-tiempo-remojo-cuanto-tardan-cocerse'                         => 'Remojo de garbanzos: tiempo y cocción',
        'cuanta-proteina-tiene-carne-ternera'                                   => 'Proteína de la ternera por 100 g y por ración',
        'eggplant-brown-inside-when-normal-and-when-overripe'                    => 'Brown Eggplant Inside: Normal or Overripe?',
        'cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas'         => 'Conservas abiertas: cuánto duran en la nevera',
        'cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias'            => 'Legumbre seca por persona: gramos y raciones',
        'cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla'           => 'Carne fuera de la nevera: cuánto tiempo es seguro',
        'how-long-can-meat-stay-out-of-the-fridge-before-cooking'                => 'How Long Can Meat Stay Out of the Fridge?',
        'do-lentils-need-soaking-how-long'                                       => 'Do Lentils Need Soaking? Soaking Time Guide',
        'garbanzos-agua-caliente-o-fria-remojo-coccion'                         => 'Garbanzos: agua caliente o fría para remojo y cocción',
        'olive-oil-calories-tablespoon-100g'                                     => 'Olive Oil Calories: Tablespoon and 100 g',
        'verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo'    => 'Verdura y hortaliza: diferencia y ejemplos',
        'how-long-can-you-freeze-meat-beef-ground-beef-burgers'                  => 'How Long Can You Freeze Meat? Beef and Burgers',
        'calorias-aceite-oliva-cucharada-100g'                                   => 'Calorías del aceite de oliva: cucharada y 100 g',
    );

    return isset( $titles[ $slug ] ) ? $titles[ $slug ] : (string) $title;
}
add_filter( 'aioseo_title', 'mdo_blog_seo_top3_title_20260908', 20 );
add_filter( 'wpseo_title', 'mdo_blog_seo_top3_title_20260908', 20 );
add_filter( 'rank_math/frontend/title', 'mdo_blog_seo_top3_title_20260908', 20 );
add_filter( 'seopress_titles_title', 'mdo_blog_seo_top3_title_20260908', 20 );

/**
 * Search Console quick wins, 2026-09-08.
 * Spanish descriptions answer the dominant query immediately. English
 * descriptions remain owned by the reviewed _en_US_post_excerpt layer so we
 * do not create two competing sources of translated metadata.
 */
function mdo_blog_seo_top3_description_20260908( $description ): string {
    $slug = mdo_blog_seo_current_slug_20260908();

    $descriptions = array(
        'hay-que-poner-lentejas-en-remojo-cuanto-tiempo'                         => '¿Hay que poner las lentejas en remojo? Descubre cuándo hace falta, cuánto tiempo dejarlas según el tipo y cómo conseguir una buena cocción.',
        'garbanzos-tiempo-remojo-cuanto-tardan-cocerse'                         => 'Guía práctica para remojar garbanzos y calcular su cocción: tiempos orientativos, olla convencional o rápida y claves para que queden tiernos.',
        'cuanta-proteina-tiene-carne-ternera'                                   => 'La ternera magra aporta alrededor de 20–21 g de proteína por 100 g en crudo. Consulta cantidades por ración y diferencias al cocinarla.',
        'cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas'         => 'Consulta cuánto dura una conserva una vez abierta, cómo guardarla correctamente en la nevera y qué señales indican que conviene desecharla.',
        'cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias'            => 'Calcula cuánta legumbre seca necesitas por persona para garbanzos, lentejas y alubias, con equivalencias útiles para ajustar raciones sin pasarte.',
        'cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla'           => 'Cuánto tiempo puede estar la carne fuera de la nevera antes de cocinarla, qué cambia con el calor ambiente y cuándo es más seguro descartarla.',
        'garbanzos-agua-caliente-o-fria-remojo-coccion'                         => '¿Agua caliente o fría para los garbanzos? Aprende qué temperatura usar en el remojo y la cocción y cómo evitar que queden duros.',
        'se-pueden-congelar-legumbres-cocidas-como-hacerlo'                     => 'Sí, las legumbres cocidas se pueden congelar. Aprende cómo enfriarlas, envasarlas y descongelarlas para conservar mejor su textura y sabor.',
        'verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo'    => 'Verdura y hortaliza no significan exactamente lo mismo. Descubre la diferencia, qué alimentos incluye cada concepto y ejemplos fáciles de recordar.',
        'cuanto-dura-carne-cocinada-nevera-conservacion-segura'                 => 'Consulta cuánto dura la carne cocinada en la nevera, cómo enfriarla y guardarla correctamente y qué señales indican que ya no conviene consumirla.',
        'por-que-aceite-hace-espuma-al-freir-causas-cuando-preocuparse'          => '¿Por qué hace espuma el aceite al freír? Repasamos las causas más habituales, cuándo es normal y qué señales indican que conviene cambiar el aceite.',
        'carne-magra-ternera-que-es-como-cocinar-tierna'                         => 'Qué es la carne magra de ternera, qué cortes encajan mejor y cómo cocinarlos para mantenerlos tiernos, jugosos y sabrosos.',
        'calorias-aceite-oliva-cucharada-100g'                                   => 'Consulta las calorías del aceite de oliva por cucharada y por 100 g, con equivalencias prácticas para entender cuánto aporta una ración habitual.',
    );

    return isset( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : (string) $description;
}
add_filter( 'aioseo_description', 'mdo_blog_seo_top3_description_20260908', 20 );
add_filter( 'wpseo_metadesc', 'mdo_blog_seo_top3_description_20260908', 20 );
add_filter( 'rank_math/frontend/description', 'mdo_blog_seo_top3_description_20260908', 20 );
add_filter( 'seopress_titles_desc', 'mdo_blog_seo_top3_description_20260908', 20 );

/**
 * La capa inglesa registra filtros AIOSEO durante la carga de MU-plugins y
 * AIOSEO/otros plugins normales pueden registrar más callbacks después.
 * Añadimos los nuestros al final de plugins_loaded: todos los plugins ya están
 * cargados, pero el head todavía no se ha calculado. Así el override dirigido
 * queda el último callback PHP_INT_MAX antes de resolver title/description.
 */
function mdo_blog_seo_register_final_serp_filters_20260929(): void {
    if ( is_admin() ) {
        return;
    }

    add_filter( 'aioseo_title', 'mdo_blog_seo_top3_title_20260908', PHP_INT_MAX );
    add_filter( 'aioseo_description', 'mdo_blog_seo_top3_description_20260908', PHP_INT_MAX );
    add_filter( 'wpseo_title', 'mdo_blog_seo_top3_title_20260908', PHP_INT_MAX );
    add_filter( 'wpseo_metadesc', 'mdo_blog_seo_top3_description_20260908', PHP_INT_MAX );
    add_filter( 'rank_math/frontend/title', 'mdo_blog_seo_top3_title_20260908', PHP_INT_MAX );
    add_filter( 'rank_math/frontend/description', 'mdo_blog_seo_top3_description_20260908', PHP_INT_MAX );
    add_filter( 'seopress_titles_title', 'mdo_blog_seo_top3_title_20260908', PHP_INT_MAX );
    add_filter( 'seopress_titles_desc', 'mdo_blog_seo_top3_description_20260908', PHP_INT_MAX );
}
add_action( 'plugins_loaded', 'mdo_blog_seo_register_final_serp_filters_20260929', PHP_INT_MAX );

/**
 * Reinforce the three highest-opportunity URLs with contextual internal links.
 * Rules are deliberately conservative: each source gets at most one link to a
 * given target, an existing target link always wins, and only an exact visible
 * phrase is replaced. Nothing is written back to post_content.
 */
function mdo_blog_seo_internal_links_20260908( $content ): string {
    $content = (string) $content;

    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $slug = mdo_blog_seo_current_slug_20260908();
    if ( '' === $slug ) {
        return $content;
    }

    $lentils = 'https://www.elmercadodeorigen.com/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/';
    $beef    = 'https://www.elmercadodeorigen.com/en/how-much-protein-in-beef/';
    $chick   = 'https://www.elmercadodeorigen.com/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/';

    $rules = array(
        'hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar' => array(
            array(
                'target' => $chick,
                'needle' => 'Los garbanzos suelen beneficiarse de un remojo largo.',
                'replace' => 'Los garbanzos suelen beneficiarse de <a href="' . $chick . '">un remojo largo</a>.',
            ),
            array(
                'target' => $lentils,
                'needle' => 'Muchas lentejas no necesitan remojo para cocinarse bien.',
                'replace' => '<a href="' . $lentils . '">Muchas lentejas no necesitan remojo para cocinarse bien</a>.',
            ),
        ),
        'remojo-legumbres-pierden-nutrientes' => array(
            array(
                'target' => $chick,
                'needle' => 'Garbanzos y muchas alubias suelen beneficiarse de un remojo nocturno',
                'replace' => '<a href="' . $chick . '">Garbanzos</a> y muchas alubias suelen beneficiarse de un remojo nocturno',
            ),
            array(
                'target' => $lentils,
                'needle' => 'las lentejas pequeñas a menudo pueden cocinarse sin remojo',
                'replace' => '<a href="' . $lentils . '">las lentejas pequeñas a menudo pueden cocinarse sin remojo</a>',
            ),
        ),
        'por-que-legumbres-quedan-duras-se-rompen-pierden-piel' => array(
            array(
                'target' => $lentils,
                'needle' => 'Las lentejas, según variedad y tamaño, a menudo pueden cocinarse sin remojo',
                'replace' => '<a href="' . $lentils . '">Las lentejas, según variedad y tamaño, a menudo pueden cocinarse sin remojo</a>',
            ),
        ),
        'garbanzos-agua-caliente-o-fria-remojo-coccion' => array(
            array(
                'target' => $chick,
                'needle' => 'el tiempo de remojo',
                'replace' => '<a href="' . $chick . '">el tiempo de remojo</a>',
            ),
        ),
        'beef-nutrients-protein-iron-zinc-vitamins' => array(
            array(
                'target' => $beef,
                'needle' => 'roughly 20–21 g protein per 100 g',
                'replace' => '<a href="' . $beef . '">roughly 20–21 g protein per 100 g</a>',
            ),
        ),
        'high-protein-foods-meat-legumes-cured-products' => array(
            array(
                'target' => $beef,
                'needle' => '20.7 g of protein per 100 g for lean beef',
                'replace' => '<a href="' . $beef . '">20.7 g of protein per 100 g for lean beef</a>',
            ),
        ),
        'how-much-iron-in-beef' => array(
            array(
                'target' => $beef,
                'needle' => 'Lean beef provides about 20.7 g of protein per 100 g in the FEN reference',
                'replace' => 'Lean beef provides <a href="' . $beef . '">about 20.7 g of protein per 100 g</a> in the FEN reference',
            ),
        ),
        'how-much-meat-to-plan-per-person-by-cut-and-recipe' => array(
            array(
                'target' => $beef,
                'needle' => 'about 180–250 grams raw per adult',
                'replace' => '<a href="' . $beef . '">about 180–250 grams raw per adult</a>',
            ),
        ),
    );

    if ( empty( $rules[ $slug ] ) ) {
        return $content;
    }

    foreach ( $rules[ $slug ] as $rule ) {
        $target = (string) $rule['target'];
        $needle = (string) $rule['needle'];
        $replace = (string) $rule['replace'];

        if ( false !== strpos( $content, $target ) || false === strpos( $content, $needle ) ) {
            continue;
        }

        $position = strpos( $content, $needle );
        if ( false === $position ) {
            continue;
        }

        $content = substr( $content, 0, $position )
            . $replace
            . substr( $content, $position + strlen( $needle ) );
    }

    return $content;
}
add_filter( 'the_content', 'mdo_blog_seo_internal_links_20260908', 42 );


/**
 * GSC authority mesh, 2026-09-29.
 *
 * Search Console shows the largest ranking opportunities concentrated in a
 * handful of editorial clusters already sitting around positions 4-10.
 * Add a compact, user-visible contextual navigation block to the strongest
 * source pages in those clusters. This creates useful in-body links without
 * changing stored post content, URLs or canonical signals.
 */
function mdo_blog_seo_cluster_links_20260929( $content ): string {
    $content = (string) $content;

    if ( ! is_singular( 'post' ) || false !== strpos( $content, 'data-mdo-seo-cluster=' ) ) {
        return $content;
    }

    $slug = mdo_blog_seo_current_slug_20260908();
    if ( '' === $slug ) {
        return $content;
    }

    $request_uri = mdo_blog_seo_public_request_uri_20260929();
    $path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $is_english  = 0 === strpos( $path, '/en/' );

    $clusters = array(
        'es-legumbres' => array(
            'english' => false,
            'sources' => array(
                'hay-que-poner-lentejas-en-remojo-cuanto-tiempo',
                'garbanzos-tiempo-remojo-cuanto-tardan-cocerse',
                'cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias',
                'garbanzos-agua-caliente-o-fria-remojo-coccion',
                'se-pueden-congelar-legumbres-cocidas-como-hacerlo',
                'hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar',
                'espuma-cocer-garbanzos-lentejas-alubias-que-es-retirarla',
                'que-legumbre-tiene-mas-hierro-comparativa',
                'bicarbonato-en-remojo-legumbres-para-que-sirve-cuanto-usar',
                'que-legumbre-tiene-mas-proteina-comparativa',
                'cuando-echar-sal-garbanzos-lentejas-alubias-endurece-legumbres',
                'tipos-lentejas-pardina-castellana-beluga-roja-diferencias',
                'tipos-alubias-blancas-pintas-canela-fabes-judiones',
                'lentejas-vs-garbanzos-diferencias-nutricionales',
                'garbanzos-lentejas-alubias-cual-es-mas-nutritiva',
                'tipos-garbanzos-pedrosillano-castellano-lechoso-variedades',
                'como-conservar-legumbres-secas-despensa',
                'como-hacer-legumbres-den-menos-gases-remojo-coccion',
                'que-legumbre-tiene-mas-fibra',
                'legumbres-secas-vs-cocidas-calorias-nutrientes',
            ),
            'label' => 'Guías relacionadas',
            'targets' => array(
                array( '/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/', 'cuándo y cuánto tiempo remojar las lentejas' ),
                array( '/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/', 'tiempo de remojo y cocción de los garbanzos' ),
                array( '/cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias/', 'cuánta legumbre seca calcular por persona' ),
                array( '/garbanzos-agua-caliente-o-fria-remojo-coccion/', 'agua caliente o fría para los garbanzos' ),
            ),
        ),
        'es-carne' => array(
            'english' => false,
            'sources' => array(
                'cuanta-proteina-tiene-carne-ternera',
                'cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla',
                'cuanto-dura-carne-cocinada-nevera-conservacion-segura',
                'carne-magra-ternera-que-es-como-cocinar-tierna',
                'aguja-ternera-que-corte-es-como-cocinarla',
                'cuanta-carne-calcular-por-persona-corte-receta',
                'liquido-rojo-carne-no-es-sangre-que-es-realmente',
                'espuma-blanca-cocinar-carne-que-es-por-que-sale',
                'como-descongelar-carne-correctamente-nevera-agua-fria-microondas',
                'se-puede-volver-congelar-carne-descongelada-cuando-si-cuando-no',
                'se-puede-congelar-carne-cocinada-como-conservar-descongelar',
                'hay-que-lavar-carne-antes-cocinar-por-que-no',
                'cuando-salar-carne-antes-despues-cocinar',
                'como-saber-carne-fresca-buen-estado-color-olor-textura-envase',
                'nutrientes-carne-ternera-proteina-hierro-zinc-vitaminas',
                'entrecot-chuleton-cortes-ternera-plancha-parrilla',
                'como-cocinar-filetes-ternera-tiernos-jugosos',
                'solomillo-vs-entrecot-diferencias-ternura-grasa-sabor-cual-elegir',
                'cuanto-hierro-tiene-carne-ternera',
                'como-cortar-carne-contrapelo-mas-tierna',
            ),
            'label' => 'Guías relacionadas',
            'targets' => array(
                array( '/cuanta-proteina-tiene-carne-ternera/', 'proteína de la ternera por 100 g y por ración' ),
                array( '/cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla/', 'cuánto tiempo puede estar la carne fuera de la nevera' ),
                array( '/cuanto-dura-carne-cocinada-nevera-conservacion-segura/', 'cuánto dura la carne cocinada en la nevera' ),
                array( '/carne-magra-ternera-que-es-como-cocinar-tierna/', 'qué es la carne magra de ternera' ),
            ),
        ),
        'es-conservas' => array(
            'english' => false,
            'sources' => array(
                'cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas',
                'se-puede-guardar-lata-abierta-nevera-por-que-cambiar-recipiente',
                'caducan-conservas-cuanto-duran-como-saber-buen-estado',
                'se-pueden-congelar-conservas-una-vez-abiertas-que-productos-toleran-mejor',
                'peso-neto-vs-peso-escurrido-conserva-que-significa',
                'lata-conserva-abollada-cuando-segura-cuando-descartar',
                'vacio-tarro-conserva-como-saber-cierre-intacto',
                'esterilizacion-conservas-vegetales-por-que-duran-despensa',
                'botulismo-conservas-prevencion-comercial-senales-alerta',
                'ph-acidez-conservas-importancia-elaboracion-seguridad',
            ),
            'label' => 'Guías relacionadas',
            'targets' => array(
                array( '/cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas/', 'cuánto duran las conservas una vez abiertas' ),
                array( '/se-puede-guardar-lata-abierta-nevera-por-que-cambiar-recipiente/', 'cómo guardar una lata abierta en la nevera' ),
                array( '/se-pueden-congelar-conservas-una-vez-abiertas-que-productos-toleran-mejor/', 'qué conservas abiertas se pueden congelar' ),
                array( '/caducan-conservas-cuanto-duran-como-saber-buen-estado/', 'cuánto duran las conservas y cómo valorar su estado' ),
            ),
        ),
        'es-aceite' => array(
            'english' => false,
            'sources' => array(
                'por-que-aceite-hace-espuma-al-freir-causas-cuando-preocuparse',
                'calorias-aceite-oliva-cucharada-100g',
                'aceite-oliva-o-girasol-para-freir-cual-elegir',
                'acido-oleico-aceite-oliva-que-es-cuanto-tiene',
                'nutrientes-aceite-oliva-virgen-extra',
                'aceite-oliva-se-solidifica-turbio-frio-es-malo',
                'aceite-oliva-reposteria-sustituir-mantequilla-que-aove-elegir',
                'aceite-oliva-tiene-colesterol',
                'aove-omega-3-omega-6-perfil-grasas',
            ),
            'label' => 'Guías relacionadas',
            'targets' => array(
                array( '/calorias-aceite-oliva-cucharada-100g/', 'calorías del aceite de oliva por cucharada y 100 g' ),
                array( '/por-que-aceite-hace-espuma-al-freir-causas-cuando-preocuparse/', 'por qué el aceite hace espuma al freír' ),
                array( '/aceite-oliva-o-girasol-para-freir-cual-elegir/', 'aceite de oliva o girasol para freír' ),
                array( '/acido-oleico-aceite-oliva-que-es-cuanto-tiene/', 'qué es el ácido oleico del aceite de oliva' ),
            ),
        ),
        'es-hortalizas' => array(
            'english' => false,
            'sources' => array(
                'verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo',
                'patata-cortada-se-pone-negra-por-que-oxidacion-como-evitarla',
                'como-conservar-patatas-nevera-despensa-evitar-brotes',
                'verduras-mas-potasio-comparativa',
                'manchas-negras-dentro-patata-por-que-aparecen-cuando-descartarla',
                'tomates-rajados-por-que-se-agrietan-cuando-se-pueden-comer',
                'cebolla-brotada-se-puede-comer-bulbo-brote',
                'que-verduras-tienen-mas-fibra',
                'que-verduras-tienen-mas-hierro',
                'por-que-algunas-verduras-saben-amargas',
                'nutrientes-verduras-vitaminas-minerales-fibra',
                'que-verduras-tienen-mas-vitamina-c',
                'escarola-vs-lechuga-diferencias-sabor-textura-usos',
                'berenjena-marron-por-dentro-cuando-normal-cuando-pasada',
                'verduras-temporada-espana-calendario-meses-que-comprar',
                'por-que-frutas-hortalizas-se-oscurecen-al-cortarlas-oxidacion-enzimatica',
                'hortalizas-raiz-hoja-fruto-flor-bulbo-tallo',
            ),
            'label' => 'Guías relacionadas',
            'targets' => array(
                array( '/verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo/', 'diferencia entre verdura y hortaliza' ),
                array( '/patata-cortada-se-pone-negra-por-que-oxidacion-como-evitarla/', 'por qué la patata cortada se pone negra' ),
                array( '/berenjena-marron-por-dentro-cuando-normal-cuando-pasada/', 'cuándo una berenjena marrón por dentro está pasada' ),
                array( '/por-que-frutas-hortalizas-se-oscurecen-al-cortarlas-oxidacion-enzimatica/', 'por qué frutas y hortalizas se oscurecen al cortarlas' ),
            ),
        ),
        'en-pulses' => array(
            'english' => true,
            'sources' => array(
                'do-lentils-need-soaking-how-long',
                'chickpeas-soaking-time-how-long-to-cook',
                'foam-when-cooking-chickpeas-lentils-beans-what-it-is-remove-it',
                'how-much-dried-pulses-per-person-chickpeas-lentils-beans',
                'types-of-spanish-beans-white-speckled-canela-fabes-judiones',
                'do-legumes-lose-nutrients-when-cooked',
                'baking-soda-soaking-pulses-what-it-does-how-much-to-use',
                'legume-nutrients-protein-fibre-iron-vitamins-minerals',
                'which-legume-has-most-protein-comparison',
                'does-soaking-legumes-cause-nutrient-loss',
                'dry-vs-cooked-legumes-calories-nutrients',
                'types-of-lentils-pardina-castellana-beluga-red-differences',
                'is-legume-protein-complete-amino-acids-how-to-combine',
                'iron-rich-foods-meat-legumes-vegetables',
                'should-you-discard-pulse-soaking-water-can-you-use-it',
            ),
            'label' => 'Related guides',
            'targets' => array(
                array( '/en/do-lentils-need-soaking-how-long/', 'whether lentils need soaking and for how long' ),
                array( '/en/chickpeas-soaking-time-how-long-to-cook/', 'chickpea soaking and cooking times' ),
                array( '/en/how-much-dried-pulses-per-person-chickpeas-lentils-beans/', 'how much dried pulses to plan per person' ),
                array( '/en/foam-when-cooking-chickpeas-lentils-beans-what-it-is-remove-it/', 'why foam appears when cooking pulses' ),
            ),
        ),
        'en-meat' => array(
            'english' => true,
            'sources' => array(
                'how-much-protein-in-beef',
                'how-long-can-meat-stay-out-of-the-fridge-before-cooking',
                'how-long-can-you-freeze-meat-beef-ground-beef-burgers',
                'red-liquid-in-meat-is-not-blood-what-it-really-is',
                'white-foam-when-cooking-meat-what-it-is-why-it-appears',
                'how-much-iron-in-beef',
                'why-meat-releases-water-in-pan-how-to-stop-it',
                'can-you-freeze-cooked-meat-how-to-store-and-thaw-it',
                'vacuum-packed-meat-strong-smell-opened-when-normal-when-to-discard',
                'tenderloin-vs-entrecote-differences-tenderness-fat-flavour-which-to-choose',
                'freezer-burn-on-meat-what-it-is-is-it-safe-and-how-to-prevent-it',
                'vacuum-packed-meat-purple-dark-why-colour-changes-after-opening',
                'how-much-meat-to-plan-per-person-by-cut-and-recipe',
                'can-you-refreeze-thawed-meat-when-it-is-safe',
                'should-you-wash-meat-before-cooking-why-not',
                'beef-nutrients-protein-iron-zinc-vitamins',
                'why-burgers-shrink-when-cooked-causes-and-how-to-reduce-it',
                'how-long-cooked-meat-lasts-in-fridge-safe-storage',
                'how-long-to-marinate-beef-hours-salt-acid',
            ),
            'label' => 'Related guides',
            'targets' => array(
                array( '/en/how-much-protein-in-beef/', 'beef protein per 100 g and by serving' ),
                array( '/en/how-long-can-meat-stay-out-of-the-fridge-before-cooking/', 'how long meat can stay out of the fridge' ),
                array( '/en/how-long-can-you-freeze-meat-beef-ground-beef-burgers/', 'how long beef and burgers keep frozen' ),
                array( '/en/white-foam-when-cooking-meat-what-it-is-why-it-appears/', 'what the white foam when cooking meat is' ),
            ),
        ),
        'en-canned' => array(
            'english' => true,
            'sources' => array(
                'how-long-opened-canned-food-keeps-in-fridge-how-to-store-it',
                'net-weight-vs-drained-weight-in-preserves-what-each-means',
                'do-canned-foods-expire-how-long-they-last-how-to-tell-if-safe',
                'canned-vegetables-colour-change-normal-spoilage',
                'botulism-canned-food-commercial-prevention-warning-signs',
                'glass-jar-vs-can-preserves-differences-storage-use',
                'should-you-rinse-canned-jarred-vegetables-before-eating',
            ),
            'label' => 'Related guides',
            'targets' => array(
                array( '/en/how-long-opened-canned-food-keeps-in-fridge-how-to-store-it/', 'how long opened canned food keeps in the fridge' ),
                array( '/en/do-canned-foods-expire-how-long-they-last-how-to-tell-if-safe/', 'how long canned foods last and signs of spoilage' ),
                array( '/en/canned-vegetables-colour-change-normal-spoilage/', 'when colour changes in canned vegetables are normal' ),
                array( '/en/should-you-rinse-canned-jarred-vegetables-before-eating/', 'whether to rinse canned or jarred vegetables' ),
            ),
        ),
        'en-olive-oil' => array(
            'english' => true,
            'sources' => array(
                'olive-oil-calories-tablespoon-100g',
                'oleic-acid-olive-oil-what-is-it-how-much',
                'extra-virgin-vs-virgin-olive-oil-olive-oil-pomace-differences',
                'how-much-vitamin-e-extra-virgin-olive-oil',
                'olive-oil-or-sunflower-oil-for-frying-which-to-choose',
                'olive-oil-acidity-what-it-really-means',
                'how-much-saturated-fat-evoo',
                'why-extra-virgin-olive-oil-tastes-bitter-and-pungent',
                'why-olive-oil-solidifies-turns-cloudy-in-cold-is-it-bad',
                'can-you-use-evoo-in-air-fryer-temperature-amount-how-to-apply',
                'extra-virgin-olive-oil-nutrients',
                'does-extra-virgin-olive-oil-lose-properties-when-heated-temperature',
                'two-phase-vs-three-phase-olive-oil-extraction-what-changes',
            ),
            'label' => 'Related guides',
            'targets' => array(
                array( '/en/olive-oil-calories-tablespoon-100g/', 'olive oil calories per tablespoon and 100 g' ),
                array( '/en/olive-oil-or-sunflower-oil-for-frying-which-to-choose/', 'olive oil or sunflower oil for frying' ),
                array( '/en/oleic-acid-olive-oil-what-is-it-how-much/', 'what oleic acid in olive oil is' ),
                array( '/en/why-olive-oil-solidifies-turns-cloudy-in-cold-is-it-bad/', 'why olive oil turns cloudy or solid in the cold' ),
            ),
        ),
        'en-vegetables' => array(
            'english' => true,
            'sources' => array(
                'eggplant-brown-inside-when-normal-and-when-overripe',
                'why-cut-fruit-vegetables-turn-brown-enzymatic-browning',
                'why-cut-potatoes-turn-black-oxidation-how-to-prevent-it',
                'yellow-broccoli-why-it-changes-colour-and-when-it-is-edible',
                'bitter-zucchini-why-it-happens-and-when-not-to-eat-it',
                'black-seeds-inside-pepper-why-they-appear-when-to-discard',
                'wrinkled-or-soft-pepper-can-you-still-eat-it',
                'green-sprouted-potatoes-when-safe-when-to-discard',
                'black-spots-inside-potato-why-they-appear-when-to-discard',
                'seasonal-vegetables-in-spain-calendar-by-month-what-to-buy',
                'vegetable-nutrients-vitamins-minerals-fibre',
                'root-leaf-fruit-flower-bulb-stem-vegetables',
                'why-some-vegetables-taste-bitter',
                'raw-vs-cooked-vegetables-what-changes-nutrients-digestion',
                'vegetables-highest-in-iron',
                'vegetables-highest-calcium-comparison',
                'what-happens-to-vegetables-after-harvest-respiration-water-ageing',
            ),
            'label' => 'Related guides',
            'targets' => array(
                array( '/en/eggplant-brown-inside-when-normal-and-when-overripe/', 'when brown flesh inside an eggplant is normal' ),
                array( '/en/why-cut-fruit-vegetables-turn-brown-enzymatic-browning/', 'why cut fruit and vegetables turn brown' ),
                array( '/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/', 'why cut potatoes turn black and how to prevent it' ),
                array( '/en/bitter-zucchini-why-it-happens-and-when-not-to-eat-it/', 'why zucchini tastes bitter and when not to eat it' ),
            ),
        ),
    );

    $cluster_key = '';
    $cluster     = array();

    foreach ( $clusters as $candidate_key => $candidate ) {
        if ( (bool) $candidate['english'] !== $is_english ) {
            continue;
        }

        if ( in_array( $slug, $candidate['sources'], true ) ) {
            $cluster_key = $candidate_key;
            $cluster     = $candidate;
            break;
        }
    }

    if ( '' === $cluster_key || empty( $cluster['targets'] ) ) {
        return $content;
    }

    $links = array();

    foreach ( $cluster['targets'] as $target ) {
        $target_path  = (string) $target[0];
        $target_label = (string) $target[1];
        $target_slug  = basename( untrailingslashit( $target_path ) );

        if ( $target_slug === $slug || false !== strpos( $content, $target_path ) ) {
            continue;
        }

        $links[] = '<a href="' . esc_url( home_url( $target_path ) ) . '">' . esc_html( $target_label ) . '</a>';

        if ( count( $links ) >= 3 ) {
            break;
        }
    }

    if ( count( $links ) < 2 ) {
        return $content;
    }

    $separator = '<span aria-hidden="true"> · </span>';
    $block     = '<p class="mdo-seo-cluster-links" data-mdo-seo-cluster="' . esc_attr( $cluster_key ) . '"><strong>'
        . esc_html( (string) $cluster['label'] )
        . ':</strong> '
        . implode( $separator, $links )
        . '</p>';

    if ( preg_match( '/<h2\b/iu', $content, $match, PREG_OFFSET_CAPTURE ) ) {
        $offset = (int) $match[0][1];

        return substr( $content, 0, $offset ) . $block . "\n" . substr( $content, $offset );
    }

    return $content . "\n" . $block;
}
add_filter( 'the_content', 'mdo_blog_seo_cluster_links_20260929', 43 );
