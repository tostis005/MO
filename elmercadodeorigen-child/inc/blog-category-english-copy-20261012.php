<?php
/**
 * Conservative English fallbacks for two editorial category terms.
 *
 * Falang translations remain authoritative. When an old Spanish term is shown
 * on /en/ without an English translation, provide readable navigation copy
 * and an introductory paragraph. This never modifies terms in the database,
 * post titles/bodies, canonical URLs, hreflang or the category queries.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Use an explicit /en/ URL; referrer and global locale are not reliable with Falang. */
function elmercado_blog_en_category_request_20261012(): bool {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return 1 === preg_match( '~^/en(?:/|$)~i', $path );
}

/**
 * Display-only copy for categories that are visibly Spanish on English hubs.
 *
 * @return array{title:string,description:string}|null
 */
function elmercado_blog_category_english_fallback_20261012( WP_Term $term ): ?array {
	if ( ! elmercado_blog_en_category_request_20261012() || 'category' !== $term->taxonomy ) {
		return null;
	}

	$slugs = array(
		'pato' => array(
			'spanish' => 'Pato',
			'title' => 'Duck',
			'description' => 'Explore guides to duck meat and its main cuts, from duck breast (magret) and confit to foie gras and cured duck ham. Learn how to choose, store, prepare and serve them, with articles on cooking, nutrition and food safety.',
		),
		'foie-pates-untables' => array(
			'spanish' => 'Foie, patés y untables',
			'title' => 'Foie Gras, Pâtés and Spreads',
			'description' => 'Learn the differences between foie gras, pâté, mousse, parfait, bloc, mi-cuit and rillettes. Compare ingredients, preparation, texture, food labels and storage, and find practical ideas for serving each kind of product.',
		),
	);

	$slug = (string) $term->slug;
	if ( ! isset( $slugs[ $slug ] ) ) {
		return null;
	}
	$copy = $slugs[ $slug ];
	/*
	 * A published translation is owned by Falang: never override its name or
	 * description, even if its text happens to resemble the original.
	 */
	if ( '1' === (string) get_term_meta( (int) $term->term_id, '_en_US_published', true ) ) {
		return null;
	}
	if ( trim( (string) $term->name ) !== $copy['spanish'] ) {
		return null;
	}

	return array( 'title' => $copy['title'], 'description' => $copy['description'] );
}

/** Visible category label, used by chips, cards and blog post cards. */
function elmercado_blog_category_label_20261012( WP_Term $term ): string {
	$fallback = elmercado_blog_category_english_fallback_20261012( $term );
	return null !== $fallback ? $fallback['title'] : (string) $term->name;
}
