<?php
/**
 * Plugin Name: MDO Blog SEO Quick Wins 2026-09-08
 * Description: Removes duplicated in-content H1s, defers the inline newsletter, applies data-led SERP copy and reinforces contextual internal links to the highest-opportunity blog posts.
 * Version: 2026.09.29.1
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
function mdo_blog_seo_current_slug_20260908(): string {
    if ( is_admin() || ! is_singular( 'post' ) ) {
        return '';
    }

    /*
     * Falang puede conservar el post_name original en algunas rutas /en/.
     * La URL pública es la señal más fiable para seleccionar el snippet.
     */
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
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
 * Descriptions answer the dominant query immediately and preserve factual
 * wording already supported by each article.
 */
function mdo_blog_seo_top3_description_20260908( $description ): string {
    $slug = mdo_blog_seo_current_slug_20260908();

    $descriptions = array(
        'how-much-protein-in-beef'                                               => 'Beef provides about 20–21 g of protein per 100 g raw. See protein amounts by serving and how values change between raw and cooked beef.',
        'hay-que-poner-lentejas-en-remojo-cuanto-tiempo'                         => '¿Hay que poner las lentejas en remojo? Descubre cuándo hace falta, cuánto tiempo dejarlas según el tipo y cómo conseguir una buena cocción.',
        'garbanzos-tiempo-remojo-cuanto-tardan-cocerse'                         => 'Guía práctica para remojar garbanzos y calcular su cocción: tiempos orientativos, olla convencional o rápida y claves para que queden tiernos.',
        'cuanta-proteina-tiene-carne-ternera'                                   => 'La ternera magra aporta alrededor de 20–21 g de proteína por 100 g en crudo. Consulta cantidades por ración y diferencias al cocinarla.',
        'eggplant-brown-inside-when-normal-and-when-overripe'                    => 'Brown flesh inside an eggplant is not always a reason to discard it. Learn why it browns, signs of overripeness and when it is better not to eat it.',
        'cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas'         => 'Consulta cuánto dura una conserva una vez abierta, cómo guardarla correctamente en la nevera y qué señales indican que conviene desecharla.',
        'cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias'            => 'Calcula cuánta legumbre seca necesitas por persona para garbanzos, lentejas y alubias, con equivalencias útiles para ajustar raciones sin pasarte.',
        'cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla'           => 'Cuánto tiempo puede estar la carne fuera de la nevera antes de cocinarla, qué cambia con el calor ambiente y cuándo es más seguro descartarla.',
        'how-long-can-meat-stay-out-of-the-fridge-before-cooking'                => 'How long can raw meat stay out before cooking? See the key food-safety time limits, what changes in warm conditions and when to discard it.',
        'do-lentils-need-soaking-how-long'                                       => 'Do lentils need soaking? Learn which lentils benefit from it, how long to soak them and how soaking can affect cooking time and texture.',
        'garbanzos-agua-caliente-o-fria-remojo-coccion'                         => '¿Agua caliente o fría para los garbanzos? Aprende qué temperatura usar en el remojo y la cocción y cómo evitar que queden duros.',
        'olive-oil-calories-tablespoon-100g'                                     => 'Check olive oil calories per tablespoon and per 100 g, with practical serving conversions to understand how much energy your usual portion provides.',
        'se-pueden-congelar-legumbres-cocidas-como-hacerlo'                     => 'Sí, las legumbres cocidas se pueden congelar. Aprende cómo enfriarlas, envasarlas y descongelarlas para conservar mejor su textura y sabor.',
        'verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo'    => 'Verdura y hortaliza no significan exactamente lo mismo. Descubre la diferencia, qué alimentos incluye cada concepto y ejemplos fáciles de recordar.',
        'cuanto-dura-carne-cocinada-nevera-conservacion-segura'                 => 'Consulta cuánto dura la carne cocinada en la nevera, cómo enfriarla y guardarla correctamente y qué señales indican que ya no conviene consumirla.',
        'how-long-opened-canned-food-keeps-in-fridge-how-to-store-it'            => 'How long does opened canned food keep in the fridge? Learn how to store leftovers safely, choose a container and spot signs that it should be discarded.',
        'how-long-can-you-freeze-meat-beef-ground-beef-burgers'                  => 'How long can beef, ground beef and burgers stay frozen? Compare storage times and learn how packaging and thawing affect quality and safety.',
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
