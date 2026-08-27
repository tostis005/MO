<?php
/**
 * Plugin Name: MDO Blog Category Guard 010263
 * Description: Keeps editorial authority posts assigned to one canonical product-family blog category.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<string,array{name:string,aliases:array<int,string>}> */
function mdo_blog_category_families_010263(): array {
	return array(
		'jamones-y-paletas' => array(
			'name'    => 'Jamones y paletas',
			'aliases' => array( 'jamones-y-paletas', 'jamones-paletas', 'jamones', 'jamones-paletas-ibericas' ),
		),
		'embutidos-y-curados' => array(
			'name'    => 'Embutidos y curados',
			'aliases' => array( 'embutidos-y-curados', 'embutidos', 'curados', 'embutidos-curados' ),
		),
		'carnes' => array(
			'name'    => 'Carnes',
			'aliases' => array( 'carnes', 'carne' ),
		),
		'aceites' => array(
			'name'    => 'Aceites',
			'aliases' => array( 'aceites', 'aceite-de-oliva', 'aceites-de-oliva' ),
		),
		'conservas' => array(
			'name'    => 'Conservas',
			'aliases' => array( 'conservas', 'preserves' ),
		),
		'hortalizas-y-verduras' => array(
			'name'    => 'Hortalizas y verduras',
			'aliases' => array( 'hortalizas-y-verduras', 'hortalizas-verduras', 'hortalizas', 'verduras', 'frutas-y-hortalizas', 'frutas-hortalizas' ),
		),
		'legumbres' => array(
			'name'    => 'Legumbres',
			'aliases' => array( 'legumbres', 'pulses' ),
		),
		'packs-y-lotes' => array(
			'name'    => 'Packs y lotes',
			'aliases' => array( 'packs-y-lotes', 'lotes', 'packs', 'lotes-y-regalos' ),
		),
	);
}

function mdo_blog_category_from_topic_010263( string $topic ): string {
	$topic = strtolower( trim( $topic ) );
	$map = array(
		'ham'            => 'jamones-y-paletas',
		'hams'           => 'jamones-y-paletas',
		'cured_meats'    => 'embutidos-y-curados',
		'cured-meats'    => 'embutidos-y-curados',
		'charcuterie'    => 'embutidos-y-curados',
		'meat'           => 'carnes',
		'meats'          => 'carnes',
		'beef'           => 'carnes',
		'olive_oil'      => 'aceites',
		'olive-oil'      => 'aceites',
		'oil'            => 'aceites',
		'oils'           => 'aceites',
		'preserves'      => 'conservas',
		'conserves'      => 'conservas',
		'vegetable'      => 'hortalizas-y-verduras',
		'vegetables'     => 'hortalizas-y-verduras',
		'produce'        => 'hortalizas-y-verduras',
		'peaches'        => 'hortalizas-y-verduras',
		'tomatoes'       => 'hortalizas-y-verduras',
		'legumes'        => 'legumbres',
		'pulse'          => 'legumbres',
		'pulses'         => 'legumbres',
		'packs'          => 'packs-y-lotes',
		'bundles'        => 'packs-y-lotes',
		'packs_bundles'  => 'packs-y-lotes',
	);
	return $map[ $topic ] ?? '';
}

/** Normalize one editorial post after the publisher has assigned its categories. */
function mdo_blog_category_guard_010263( int $post_id ): void {
	static $running = false;
	if ( $running || $post_id <= 0 || 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	$authority_key   = trim( (string) get_post_meta( $post_id, '_emdo_authority_key', true ) );
	$authority_topic = trim( (string) get_post_meta( $post_id, '_emdo_authority_topic', true ) );
	if ( '' === $authority_key && '' === $authority_topic ) {
		return;
	}

	$families = mdo_blog_category_families_010263();
	$alias_to_family = array();
	foreach ( $families as $family_slug => $cfg ) {
		foreach ( $cfg['aliases'] as $alias ) {
			$alias_to_family[ sanitize_title( $alias ) ] = $family_slug;
		}
	}

	$family = '';
	foreach ( (array) wp_get_post_categories( $post_id, array( 'fields' => 'all' ) ) as $category ) {
		if ( ! $category instanceof WP_Term ) {
			continue;
		}
		$slug = sanitize_title( $category->slug );
		if ( isset( $alias_to_family[ $slug ] ) ) {
			$family = $alias_to_family[ $slug ];
			break;
		}
	}

	if ( '' === $family && '' !== $authority_topic ) {
		$family = mdo_blog_category_from_topic_010263( $authority_topic );
	}
	if ( '' === $family || ! isset( $families[ $family ] ) ) {
		return;
	}

	$term = get_term_by( 'slug', $family, 'category' );
	if ( ! $term instanceof WP_Term ) {
		$created = wp_insert_term( $families[ $family ]['name'], 'category', array( 'slug' => $family ) );
		if ( is_wp_error( $created ) ) {
			return;
		}
		$term = get_term( (int) $created['term_id'], 'category' );
	}
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$current = array_map( 'intval', wp_get_post_categories( $post_id ) );
	$target  = array( (int) $term->term_id );
	sort( $current );
	if ( $current === $target ) {
		if ( $family !== (string) get_post_meta( $post_id, '_emdo_blog_primary_category', true ) ) {
			update_post_meta( $post_id, '_emdo_blog_primary_category', $family );
		}
		return;
	}

	$running = true;
	wp_set_post_categories( $post_id, $target, false );
	update_post_meta( $post_id, '_emdo_blog_primary_category', $family );
	update_post_meta( $post_id, '_emdo_blog_category_reason', 'authority-guard' );
	update_post_meta( $post_id, '_emdo_blog_category_guarded_at', gmdate( 'c' ) );
	$running = false;
}

function mdo_blog_category_guard_meta_010263( $meta_id, $post_id, $meta_key, $meta_value ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( in_array( (string) $meta_key, array( '_emdo_authority_key', '_emdo_authority_topic' ), true ) ) {
		mdo_blog_category_guard_010263( (int) $post_id );
	}
}
add_action( 'added_post_meta', 'mdo_blog_category_guard_meta_010263', 20, 4 );
add_action( 'updated_post_meta', 'mdo_blog_category_guard_meta_010263', 20, 4 );
