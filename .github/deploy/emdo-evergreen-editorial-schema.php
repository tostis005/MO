<?php
/**
 * El Mercado de Origen: evergreen blog schema policy.
 * - Brand/editorial team is represented as Organization, not a fake Person.
 * - Evergreen blog posts do not expose publication/update dates via Slim SEO schema/OpenGraph.
 * - WordPress still retains truthful internal dates and XML sitemap lastmod remains available.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'slim_seo_schema_author_enable', '__return_false' );
add_filter( 'slim_seo_schema_author_image_enable', '__return_false' );

function emdo_editorial_org_ref() {
    return array( '@id' => home_url( '/' ) . '#organization' );
}

add_filter( 'slim_seo_schema_article', function ( $schema ) {
    if ( is_singular( 'post' ) ) {
        $schema['author'] = emdo_editorial_org_ref();
        unset( $schema['datePublished'], $schema['dateModified'] );
    }
    return $schema;
}, 20 );

add_filter( 'slim_seo_schema_webpage', function ( $schema ) {
    if ( is_singular( 'post' ) ) {
        $schema['author']  = emdo_editorial_org_ref();
        $schema['creator'] = emdo_editorial_org_ref();
        unset( $schema['datePublished'], $schema['dateModified'] );
    }
    return $schema;
}, 20 );

add_filter( 'slim_seo_open_graph_tags', function ( $tags ) {
    if ( ! is_singular( 'post' ) ) { return $tags; }
    $remove = array( 'article:published_time', 'article:modified_time', 'og:updated_time' );
    return array_values( array_diff( $tags, $remove ) );
}, 20 );

add_filter( 'wp_robots', function ( $robots ) {
    if ( is_author() ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}, 20 );
