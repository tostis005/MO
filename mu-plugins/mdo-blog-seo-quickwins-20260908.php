<?php
/**
 * Plugin Name: MDO Blog SEO Quick Wins 2026-09-08
 * Description: Removes duplicated in-content H1s, defers the inline newsletter, applies data-led SERP copy and reinforces contextual internal links to the highest-opportunity blog posts.
 * Version: 2026.09.29.16
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
        'por-que-aceite-hace-espuma-al-freir-causas-cuando-preocuparse'          => 'Aceite con espuma al freír: causas y cuándo cambiarlo',
        'why-cut-potatoes-turn-black-oxidation-how-to-prevent-it'                => 'Why Cut Potatoes Turn Black and How to Stop It',
        'carne-magra-ternera-que-es-como-cocinar-tierna'                         => 'Carne magra de ternera: qué es y cómo cocinarla',
        'yellow-broccoli-why-it-changes-colour-and-when-it-is-edible'             => 'Yellow Broccoli: Is It Safe to Eat? Why It Changes',
        'aguja-ternera-que-corte-es-como-cocinarla'                              => 'Aguja de ternera: qué corte es y cómo cocinarla',
        'red-liquid-in-meat-is-not-blood-what-it-really-is'                      => 'Red Liquid in Meat: It Isn’t Blood—What Is It?',
        'cuantos-litros-leche-hacen-falta-1-kg-queso-rendimiento'                => 'Cuántos litros de leche hacen falta para 1 kg de queso',
        'white-foam-when-cooking-meat-what-it-is-why-it-appears'                  => 'White Foam When Cooking Meat: What It Is',
        'cuanta-carne-calcular-por-persona-corte-receta'                          => 'Cuánta carne por persona: gramos según corte y receta',
        'liquido-rojo-carne-no-es-sangre-que-es-realmente'                       => 'El líquido rojo de la carne no es sangre: qué es',
        'patata-cortada-se-pone-negra-por-que-oxidacion-como-evitarla'            => 'Patata cortada negra: por qué pasa y cómo evitarlo',
        'black-seeds-inside-pepper-why-they-appear-when-to-discard'               => 'Black Seeds Inside a Pepper: When to Discard It',
        'espuma-blanca-cocinar-carne-que-es-por-que-sale'                         => 'Espuma blanca al cocinar carne: qué es y por qué sale',
        'sobrasada-iberica-que-es-como-se-elabora-que-mirar-al-comprarla'         => 'Sobrasada ibérica: qué es, cómo se hace y cómo elegirla',
        'como-descongelar-carne-correctamente-nevera-agua-fria-microondas'        => 'Cómo descongelar carne: nevera, agua fría o microondas',
        'how-much-iron-in-beef'                                                   => 'Iron in Beef per 100 g and per Serving',
        'wrinkled-or-soft-pepper-can-you-still-eat-it'                            => 'Wrinkled or Soft Pepper: Is It Still Safe to Eat?',
        'chickpeas-soaking-time-how-long-to-cook'                                 => 'Chickpea Soaking Time: How Long to Soak and Cook',
        'hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar'                => '¿Hay que tirar el agua de remojo de las legumbres?',
        'what-is-cheese-affinage-maturation-humidity-rind-care-affineur'          => 'Cheese Affinage: What It Means and How It Works',
        'bloomy-rind-cheese-white-mould-how-rind-forms-can-you-eat-it'           => 'Bloomy Rind Cheese: How White Mould Develops & Rind Care',
        'how-cheese-ripens-temperature-humidity-microbiota-time'                 => 'How Cheese Ripens: Temperature, Humidity & Microbiota',
        'how-to-tell-cheese-gone-bad-mould-smell-texture-discard'               => 'How to Tell If Cheese Is Bad: Mould, Smell & Texture',
        'does-cheese-contain-lactose-type-maturation-how-to-tell'                => 'Does Cheese Contain Lactose? How Type and Age Matter',
        'cheese-nutrition-calories-protein-fat-salt-carbohydrates'               => 'Cheese Nutrition per 100 g: Calories, Protein & Fat',
        'can-you-eat-cheese-rind-which-rinds-edible-which-remove'                => 'Can You Eat Cheese Rind? Which Rinds Are Edible',
        'how-many-litres-milk-to-make-1-kg-cheese-yield'                         => 'How Much Milk to Make 1 kg of Cheese? Yield Explained',
        'fresh-cheese-what-it-is-how-made-storage-vs-matured-cheese'             => 'Fresh Cheese: What It Is, Storage and How It Differs',
        'what-is-rennet-in-cheese-animal-vegetable-microbial-coagulation'        => 'What Is Rennet in Cheese? Animal, Plant & Microbial',
        'how-to-store-cheese-properly-fridge-wrapping-temperature'               => 'How to Store Cheese in the Fridge: Wrap & Temperature',
        'se-pueden-congelar-legumbres-cocidas-como-hacerlo'                     => '¿Se pueden congelar legumbres cocidas? Cómo hacerlo',
        'se-pueden-congelar-legumbres-cocidas-como-hacerlo-textura'            => '¿Se pueden congelar legumbres cocidas? Cómo hacerlo',
        'cuanto-dura-carne-cocinada-nevera-conservacion-segura'                 => 'Carne cocinada en nevera: cuánto dura y cómo guardarla',
        'how-long-opened-canned-food-keeps-in-fridge-how-to-store-it'            => 'Opened Canned Food: How Long It Keeps in the Fridge',
        'se-puede-volver-congelar-carne-descongelada-cuando-si-cuando-no'        => '¿Se puede volver a congelar carne descongelada?',
        'why-meat-releases-water-in-pan-how-to-stop-it'                          => 'Why Meat Releases Water in the Pan—and How to Stop It',
        'can-you-freeze-cooked-meat-how-to-store-and-thaw-it'                    => 'Can You Freeze Cooked Meat? Storage and Thawing',
        'como-conservar-patatas-nevera-despensa-evitar-brotes'                  => 'Cómo conservar patatas y evitar brotes',
        'espuma-cocer-garbanzos-lentejas-alubias-que-es-retirarla'              => 'Espuma al cocer legumbres: qué es y si hay que retirarla',
        'como-conservar-chorizo-salchichon-lomo-curado-una-vez-abiertos'         => 'Cómo conservar chorizo, salchichón y lomo abiertos',
        'foam-when-cooking-chickpeas-lentils-beans-what-it-is-remove-it'          => 'Foam When Cooking Pulses: What It Is and Whether to Remove It',
        'que-legumbre-tiene-mas-hierro-comparativa'                              => 'Qué legumbre tiene más hierro: comparativa por 100 g',
        'se-puede-congelar-carne-cocinada-como-conservar-descongelar'            => '¿Se puede congelar carne cocinada? Cómo hacerlo',
        'que-legumbre-tiene-mas-proteina-comparativa'                            => 'Qué legumbre tiene más proteína: comparativa por 100 g',
        'como-saber-queso-mal-estado-moho-olor-textura'                          => 'Cómo saber si un queso está malo: moho, olor y textura',
        'dented-can-when-safe-when-to-discard'                                   => 'Dented Can: When It’s Safe and When to Discard It',
        'se-puede-guardar-lata-abierta-nevera-por-que-cambiar-recipiente'        => '¿Se puede guardar una lata abierta en la nevera?',
        'why-oil-foams-when-frying-causes-when-to-worry'                         => 'Why Oil Foams When Frying: Causes and When to Replace It',
        'can-you-store-an-open-can-in-the-fridge-why-transfer-food'              => 'Can You Store an Open Can in the Fridge?',
        'green-sprouted-potatoes-when-safe-when-to-discard'                      => 'Green or Sprouted Potatoes: When to Discard Them',
        'caducan-conservas-cuanto-duran-como-saber-buen-estado'                 => '¿Caducan las conservas? Duración y señales de mal estado',
        'verduras-mas-potasio-comparativa'                                       => 'Verduras con más potasio: comparativa por 100 g',
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
        'se-pueden-congelar-legumbres-cocidas-como-hacerlo-textura'            => 'Sí, las legumbres cocidas se pueden congelar. Aprende cómo enfriarlas, envasarlas y descongelarlas para conservar mejor su textura y sabor.',
        'verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo'    => 'Verdura y hortaliza no significan exactamente lo mismo. Descubre la diferencia, qué alimentos incluye cada concepto y ejemplos fáciles de recordar.',
        'cuanto-dura-carne-cocinada-nevera-conservacion-segura'                 => 'Consulta cuánto dura la carne cocinada en la nevera, cómo enfriarla y guardarla correctamente y qué señales indican que ya no conviene consumirla.',
        'por-que-aceite-hace-espuma-al-freir-causas-cuando-preocuparse'          => '¿Por qué hace espuma el aceite al freír? Repasamos las causas más habituales, cuándo es normal y qué señales indican que conviene cambiar el aceite.',
        'carne-magra-ternera-que-es-como-cocinar-tierna'                         => 'Qué es la carne magra de ternera, qué cortes encajan mejor y cómo cocinarlos para mantenerlos tiernos, jugosos y sabrosos.',
        'calorias-aceite-oliva-cucharada-100g'                                   => 'Consulta las calorías del aceite de oliva por cucharada y por 100 g, con equivalencias prácticas para entender cuánto aporta una ración habitual.',
        'aguja-ternera-que-corte-es-como-cocinarla'                              => 'Qué es la aguja de ternera, dónde se encuentra y qué técnicas funcionan mejor según grosor, grasa y tejido conjuntivo de la pieza.',
        'cuantos-litros-leche-hacen-falta-1-kg-queso-rendimiento'                => 'Cuánta leche hace falta para elaborar 1 kg de queso, por qué el rendimiento cambia según el tipo de queso y qué factores explican la diferencia.',
        'cuanta-carne-calcular-por-persona-corte-receta'                          => 'Calcula cuánta carne necesitas por persona según el corte, si lleva hueso y el tipo de receta, con rangos prácticos para ajustar las raciones.',
        'liquido-rojo-carne-no-es-sangre-que-es-realmente'                       => 'El líquido rojizo de una bandeja de carne no es principalmente sangre: te explicamos qué es, por qué aparece y qué señales importan de verdad.',
        'patata-cortada-se-pone-negra-por-que-oxidacion-como-evitarla'            => 'Por qué una patata cortada se pone negra, qué papel tiene la oxidación y cómo reducir el oscurecimiento mientras la preparas o conservas.',
        'espuma-blanca-cocinar-carne-que-es-por-que-sale'                         => 'La espuma blanca al cocinar carne suele proceder de agua y proteínas coaguladas. Descubre por qué aparece, cuándo retirarla y qué indica en la sartén.',
        'sobrasada-iberica-que-es-como-se-elabora-que-mirar-al-comprarla'         => 'Qué es la sobrasada ibérica, cómo se elabora y qué conviene mirar en ingredientes, curación, formato y etiquetado antes de comprarla.',
        'como-descongelar-carne-correctamente-nevera-agua-fria-microondas'        => 'Cómo descongelar carne correctamente en nevera, agua fría o microondas, qué método elegir según el tiempo disponible y qué prácticas evitar.',
        'hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar'                => '¿Conviene tirar el agua de remojo de garbanzos, lentejas y alubias? Explicamos qué pasa al líquido, cuándo desecharlo y cuándo puede aprovecharse.',
        'se-puede-volver-congelar-carne-descongelada-cuando-si-cuando-no'        => 'Cuándo se puede volver a congelar carne descongelada, qué cambia según cómo se descongeló y en qué situaciones es más seguro cocinarla primero.',
        'como-conservar-patatas-nevera-despensa-evitar-brotes'                  => 'Cómo conservar las patatas para retrasar brotes, verdor y deterioro: luz, temperatura, ventilación y cuándo conviene evitar la nevera.',
        'espuma-cocer-garbanzos-lentejas-alubias-que-es-retirarla'              => 'Qué es la espuma que aparece al cocer garbanzos, lentejas y alubias, por qué se forma y cuándo retirarla es útil o simplemente opcional.',
        'como-conservar-chorizo-salchichon-lomo-curado-una-vez-abiertos'         => 'Cómo conservar chorizo, salchichón y lomo curado una vez abiertos: envoltorio, frío, tiempo orientativo y señales de deterioro.',
        'que-legumbre-tiene-mas-hierro-comparativa'                              => 'Compara el hierro de lentejas, garbanzos, alubias y otras legumbres por 100 g y entiende cómo cambia la cifra entre producto seco y cocido.',
        'se-puede-congelar-carne-cocinada-como-conservar-descongelar'            => 'Sí, la carne cocinada se puede congelar. Aprende a enfriarla, envasarla, cuánto tiempo conservarla y cómo descongelarla de forma segura.',
        'que-legumbre-tiene-mas-proteina-comparativa'                            => 'Compara la proteína de lentejas, garbanzos, alubias y otras legumbres por 100 g, diferenciando valores en seco y después de la cocción.',
        'como-saber-queso-mal-estado-moho-olor-textura'                          => 'Cómo saber si un queso está en mal estado: qué indican el moho, el olor, la textura y el envase, y cuándo conviene desecharlo.',
        'se-puede-guardar-lata-abierta-nevera-por-que-cambiar-recipiente'        => '¿Se puede guardar una lata abierta en la nevera? Explicamos cuándo conviene pasar el alimento a otro recipiente y cómo conservarlo mejor.',
        'caducan-conservas-cuanto-duran-como-saber-buen-estado'                 => '¿Caducan las conservas? Aprende a interpretar la fecha, revisar el envase y reconocer señales de que una conserva ya no está en buen estado.',
        'verduras-mas-potasio-comparativa'                                       => 'Comparativa de verduras y hortalizas con más potasio por 100 g, con contexto para entender porciones y diferencias entre alimentos.',
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



/**
 * Detect the English cheese editorial cluster without hard-coding every post.
 * We use the public English route plus either the English/native category name
 * or an explicit cheese slug signal.
 */
function mdo_blog_seo_is_english_cheese_post_20260929(): bool {
    if ( ! is_singular( 'post' ) ) {
        return false;
    }

    $path = (string) wp_parse_url( mdo_blog_seo_public_request_uri_20260929(), PHP_URL_PATH );
    if ( 0 !== strpos( $path, '/en/' ) ) {
        return false;
    }

    $slug = mdo_blog_seo_current_slug_20260908();
    if ( false !== strpos( $slug, 'cheese' ) || false !== strpos( $slug, 'affinage' ) ) {
        return true;
    }

    $post_id = (int) get_queried_object_id();
    if ( $post_id <= 0 ) {
        return false;
    }

    $terms = wp_get_post_terms( $post_id, 'category' );
    if ( is_wp_error( $terms ) ) {
        return false;
    }

    foreach ( $terms as $term ) {
        if ( ! $term instanceof WP_Term ) {
            continue;
        }

        $native = strtolower( remove_accents( (string) $term->slug . ' ' . (string) $term->name ) );
        $english = strtolower( remove_accents( (string) get_term_meta( $term->term_id, '_en_US_name', true ) ) );

        if ( false !== strpos( $native, 'queso' ) || false !== strpos( $english, 'cheese' ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Clean two import artefacts found across reviewed English cheese copy:
 * a generator-style sentence about "search intent" and literal "nn" paragraph
 * separators. This runs only for the English Cheese cluster and never writes
 * back to the database.
 */
function mdo_blog_seo_clean_english_cheese_copy_20260929( $content ): string {
    $content = (string) $content;

    if ( ! mdo_blog_seo_is_english_cheese_post_20260929() ) {
        return $content;
    }

    $generic = 'This guide is designed to answer the full search intent rather than provide a one-line definition. It explains what the terms mean, what truly changes in the cheese, how to read labels and how to use that information when buying, storing or serving.';

    $cleaned = preg_replace(
        '#<p\\b[^>]*>\\s*' . preg_quote( $generic, '#' ) . '\\s*</p>#iu',
        '',
        $content
    );

    if ( ! is_string( $cleaned ) ) {
        $cleaned = $content;
    }

    $cleaned = str_replace( $generic, '', $cleaned );
    $cleaned = preg_replace( '/([.!?])nn(?=[A-Z])/u', '$1</p><p>', $cleaned );

    return is_string( $cleaned ) ? $cleaned : $content;
}


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

    $request_uri = mdo_blog_seo_public_request_uri_20260929();
    $path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $is_english  = 0 === strpos( $path, '/en/' );

    /*
     * Algunas rutas inglesas se resuelven internamente sobre el slug español.
     * El slug público sigue siendo preferente, pero añadimos como candidatos
     * el _en_US_post_name persistido y el post_name nativo del objeto actual.
     */
    $source_slugs = array_filter( array( $slug ) );
    $post_id      = (int) get_queried_object_id();

    if ( $post_id > 0 ) {
        $native_slug = sanitize_title( (string) get_post_field( 'post_name', $post_id ) );
        if ( '' !== $native_slug ) {
            $source_slugs[] = $native_slug;
        }

        if ( $is_english ) {
            $english_slug = sanitize_title( (string) get_post_meta( $post_id, '_en_US_post_name', true ) );
            if ( '' !== $english_slug ) {
                $source_slugs[] = $english_slug;
            }
        }
    }

    $source_slugs = array_values( array_unique( array_filter( $source_slugs ) ) );

    if ( empty( $source_slugs ) ) {
        return $content;
    }

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

        if ( array_intersect( $source_slugs, $candidate['sources'] ) ) {
            $cluster_key = $candidate_key;
            $cluster     = $candidate;
            break;
        }
    }

    if ( '' === $cluster_key && $is_english && mdo_blog_seo_is_english_cheese_post_20260929() ) {
        $cluster_key = 'en-cheese';
        $cluster = array(
            'english' => true,
            'label'   => 'Related cheese guides',
            'targets' => array(
                array( '/en/how-cheese-ripens-temperature-humidity-microbiota-time/', 'how cheese ripens' ),
                array( '/en/bloomy-rind-cheese-white-mould-how-rind-forms-can-you-eat-it/', 'how bloomy rinds develop' ),
                array( '/en/what-is-cheese-affinage-maturation-humidity-rind-care-affineur/', 'what cheese affinage means' ),
                array( '/en/how-to-tell-cheese-gone-bad-mould-smell-texture-discard/', 'how to tell if cheese has gone bad' ),
                array( '/en/does-cheese-contain-lactose-type-maturation-how-to-tell/', 'how lactose changes with cheese type and age' ),
                array( '/en/cheese-nutrition-calories-protein-fat-salt-carbohydrates/', 'cheese calories, protein and fat' ),
            ),
        );
    }

    if ( '' === $cluster_key || empty( $cluster['targets'] ) ) {
        return $content;
    }

    $shop_targets = array(
        'es-legumbres'  => array( '/categoria-producto/legumbres/', 'comprar legumbres online' ),
        'es-carne'      => array( '/categoria-producto/carnes/', 'comprar carne online' ),
        'es-conservas'  => array( '/categoria-producto/conservas/', 'comprar conservas online' ),
        'es-aceite'     => array( '/categoria-producto/aceites/', 'comprar aceite de oliva online' ),
        'es-hortalizas' => array( '/categoria-producto/hortalizas-verduras/', 'comprar hortalizas y verduras' ),
        'en-pulses'     => array( '/en/product-category/pulses/', 'shop pulses' ),
        'en-meat'       => array( '/en/product-category/meat/', 'shop meat' ),
        'en-canned'     => array( '/en/product-category/preserves/', 'shop preserves' ),
        'en-olive-oil'  => array( '/en/product-category/oils/', 'shop olive oil' ),
        'en-vegetables' => array( '/en/product-category/vegetables/', 'shop vegetables' ),
    );

    $links = array();

    foreach ( $cluster['targets'] as $target ) {
        $target_path  = (string) $target[0];
        $target_label = (string) $target[1];
        $target_slug  = basename( untrailingslashit( $target_path ) );

        if ( in_array( $target_slug, $source_slugs, true ) || false !== strpos( $content, $target_path ) ) {
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

    if ( isset( $shop_targets[ $cluster_key ] ) ) {
        $shop_path  = (string) $shop_targets[ $cluster_key ][0];
        $shop_label = (string) $shop_targets[ $cluster_key ][1];

        if ( '' !== $shop_path && false === strpos( $content, $shop_path ) ) {
            $links[] = '<a class="mdo-seo-cluster-shop" href="' . esc_url( home_url( $shop_path ) ) . '">' . esc_html( $shop_label ) . '</a>';
        }
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



/**
 * Direct-answer layer for GSC pages that still make the visitor work too hard
 * before the first H2. The answer is inserted only when the introduction does
 * not already contain a quick-answer heading or a table, so strong pages are
 * not duplicated. Nothing is written back to post_content.
 */
function mdo_blog_seo_direct_answer_20260929( $content ): string {
    $content = (string) $content;

    if (
        ! is_singular( 'post' )
        || false !== strpos( $content, 'data-mdo-seo-answer=' )
        || 1 !== preg_match( '/<h2\\b/iu', $content, $heading, PREG_OFFSET_CAPTURE )
    ) {
        return $content;
    }

    $offset = (int) $heading[0][1];
    $intro  = substr( $content, 0, $offset );

    if (
        false !== stripos( $intro, '<table' )
        || 1 === preg_match( '/(?:respuesta|resumen)\\s+rápid[oa]|quick\\s+(?:answer|summary|table)|tabla\\s+rápida/iu', $intro )
    ) {
        return $content;
    }

    $slug = mdo_blog_seo_current_slug_20260908();

    $answers = array(
        'eggplant-brown-inside-when-normal-and-when-overripe' => array(
            'label' => 'Quick answer',
            'text'  => 'Brown flesh alone does not mean an eggplant is spoiled. Mild beige or brown areas can come from oxidation, maturity or bruising when the flesh is still firm and smells normal; discard it if there is mould, slime, widespread watery tissue or an unpleasant smell.',
        ),
        'yellow-broccoli-why-it-changes-colour-and-when-it-is-edible' => array(
            'label' => 'Quick answer',
            'text'  => 'Yellowing usually means ageing, not automatic spoilage. Broccoli that is still firm, dry and normal-smelling can usually be cooked; discard it if it is slimy, wet, mouldy, collapsing or foul-smelling.',
        ),
        'red-liquid-in-meat-is-not-blood-what-it-really-is' => array(
            'label' => 'Quick answer',
            'text'  => 'The red liquid is mainly water released by muscle and coloured by proteins such as myoglobin, not a pool of blood. Its amount can change with cutting, storage, freezing or thawing; odour, surface texture, packaging and temperature history are more useful spoilage clues.',
        ),
        'white-foam-when-cooking-meat-what-it-is-why-it-appears' => array(
            'label' => 'Quick answer',
            'text'  => 'White or grey foam is usually water plus soluble proteins that coagulate as meat heats, sometimes with small amounts of fat and pigment. It is not a spoilage test; in stock, skimming is mainly for clarity, while heavy pan foaming often points to excess moisture or insufficient heat.',
        ),
        'aguja-ternera-que-corte-es-como-cocinarla' => array(
            'label' => 'Respuesta rápida',
            'text'  => 'La aguja es un corte del delantero con una mezcla variable de músculo, grasa y tejido conjuntivo. Algunas piezas funcionan bien a plancha o parrilla; las más fibrosas agradecen horno, guiso o cocción lenta. El formato y el grosor importan más que aplicar una técnica única a toda la aguja.',
        ),
        'liquido-rojo-carne-no-es-sangre-que-es-realmente' => array(
            'label' => 'Respuesta rápida',
            'text'  => 'El líquido rojizo de una bandeja no es principalmente sangre: es sobre todo agua del músculo teñida por proteínas y pigmentos como la mioglobina. Puede aumentar tras cortar, congelar o descongelar; para valorar el estado importan más el olor, la textura, el envase y el historial de frío.',
        ),
        'espuma-blanca-cocinar-carne-que-es-por-que-sale' => array(
            'label' => 'Respuesta rápida',
            'text'  => 'La espuma blanca o gris suele ser agua con proteínas solubles que coagulan al calentarse, a veces junto con grasa y pigmentos. No diagnostica deterioro por sí sola; en un caldo retirarla es sobre todo una decisión de claridad, y en sartén mucha espuma suele acompañar exceso de humedad o poco calor.',
        ),
        'como-descongelar-carne-correctamente-nevera-agua-fria-microondas' => array(
            'label' => 'Respuesta rápida',
            'text'  => 'La nevera es el método más controlado para descongelar carne. El agua fría y el microondas son alternativas más rápidas, pero después conviene cocinar inmediatamente; evita dejarla horas sobre la encimera o utilizar agua caliente.',
        ),
    );

    if ( empty( $answers[ $slug ] ) ) {
        return $content;
    }

    $answer = $answers[ $slug ];
    $block  = '<p class="mdo-seo-direct-answer" data-mdo-seo-answer="' . esc_attr( $slug ) . '"><strong>'
        . esc_html( (string) $answer['label'] )
        . ':</strong> '
        . esc_html( (string) $answer['text'] )
        . '</p>';

    return substr( $content, 0, $offset ) . $block . "\n" . substr( $content, $offset );
}


/**
 * English content can be replaced by translation/runtime filters registered by
 * normal plugins after MU plugins load. Register authority-link filters only
 * once every plugin has loaded, and put them at the end of the_content so the
 * final translated HTML is what we enrich.
 */
function mdo_blog_seo_register_final_content_links_20260929(): void {
    if ( is_admin() ) {
        return;
    }

    add_filter( 'the_content', 'mdo_blog_seo_clean_english_cheese_copy_20260929', PHP_INT_MAX );
    add_filter( 'the_content', 'mdo_blog_seo_internal_links_20260908', PHP_INT_MAX );
    add_filter( 'the_content', 'mdo_blog_seo_direct_answer_20260929', PHP_INT_MAX );
    add_filter( 'the_content', 'mdo_blog_seo_cluster_links_20260929', PHP_INT_MAX );
}
add_action( 'plugins_loaded', 'mdo_blog_seo_register_final_content_links_20260929', PHP_INT_MAX );
