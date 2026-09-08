<?php
/**
 * Plugin Name: MDO Blog SEO Quick Wins 2026-09-08
 * Description: Removes duplicated in-content H1s, defers the inline newsletter and applies data-led SERP copy to the highest-opportunity blog posts.
 * Version: 2026.09.08.3
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
        || ! in_the_loop()
        || ! is_main_query()
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

    $post_id = (int) get_queried_object_id();
    if ( $post_id <= 0 ) {
        return '';
    }

    return (string) get_post_field( 'post_name', $post_id );
}

/**
 * Search Console quick wins, 2026-09-08.
 * These override only the SERP title; visible article titles and URLs stay put.
 */
function mdo_blog_seo_top3_title_20260908( $title ): string {
    $slug = mdo_blog_seo_current_slug_20260908();

    $titles = array(
        'hay-que-poner-lentejas-en-remojo-cuanto-tiempo' => '¿Las lentejas se remojan? Cuánto tiempo dejarlas en remojo',
        'how-much-protein-in-beef'                       => 'Beef protein per 100g: how much protein is in beef?',
        'garbanzos-tiempo-remojo-cuanto-tardan-cocerse' => 'Tiempo de remojo de los garbanzos: 8–12 horas y cocción',
    );

    return isset( $titles[ $slug ] ) ? $titles[ $slug ] : (string) $title;
}
add_filter( 'aioseo_title', 'mdo_blog_seo_top3_title_20260908', 20 );

/**
 * Search Console quick wins, 2026-09-08.
 * Descriptions answer the dominant query immediately and preserve factual
 * wording already supported by each article.
 */
function mdo_blog_seo_top3_description_20260908( $description ): string {
    $slug = mdo_blog_seo_current_slug_20260908();

    $descriptions = array(
        'hay-que-poner-lentejas-en-remojo-cuanto-tiempo' => 'La mayoría de las lentejas no necesitan remojo. Descubre cuáles se cocinan directamente, cuándo conviene remojarlas y durante cuánto tiempo.',
        'how-much-protein-in-beef'                       => 'Beef provides about 20–21 g of protein per 100 g raw. Compare 100 g, 150 g and 200 g servings, plus lean, cooked and minced beef.',
        'garbanzos-tiempo-remojo-cuanto-tardan-cocerse' => 'Los garbanzos suelen necesitar 8–12 horas de remojo. Consulta tiempos de cocción en olla tradicional y rápida, agua, sal y qué hacer si siguen duros.',
    );

    return isset( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : (string) $description;
}
add_filter( 'aioseo_description', 'mdo_blog_seo_top3_description_20260908', 20 );
