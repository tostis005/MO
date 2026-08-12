<?php
/**
 * Export de solo lectura para auditar Embutidos y curados en staging.
 * Ejecutar con WP-CLI: wp eval-file <este archivo>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}
if ( ! function_exists( 'wc_get_products' ) ) {
	fwrite( STDERR, "WooCommerce no disponible\n" );
	exit( 1 );
}

function emo_cured_plain( string $html, int $limit = 8000 ): string {
	$text = html_entity_decode( wp_strip_all_tags( $html, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( (string) $text, 0, $limit );
	}
	return substr( (string) $text, 0, $limit );
}

function emo_cured_terms( int $product_id, string $taxonomy ): array {
	$terms = wp_get_post_terms( $product_id, $taxonomy );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}
	return array_values( array_map(
		static fn( WP_Term $term ): array => array(
			'id'   => (int) $term->term_id,
			'name' => $term->name,
			'slug' => $term->slug,
		),
		$terms
	) );
}

function emo_cured_attributes( WC_Product $product ): array {
	$out = array();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}
		$name = $attribute->get_name();
		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) )
			: $attribute->get_options();
		$out[ $name ] = array(
			'id'        => (int) $attribute->get_id(),
			'values'    => array_values( array_map( 'strval', (array) $values ) ),
			'visible'   => (bool) $attribute->get_visible(),
			'variation' => (bool) $attribute->get_variation(),
			'position'  => (int) $attribute->get_position(),
		);
	}
	return $out;
}

function emo_cured_variations( WC_Product $product ): array {
	if ( ! $product->is_type( 'variable' ) ) {
		return array();
	}
	$out = array();
	foreach ( $product->get_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			continue;
		}
		$out[] = array(
			'id'            => (int) $variation->get_id(),
			'sku'           => $variation->get_sku(),
			'attributes'    => $variation->get_attributes(),
			'description'   => emo_cured_plain( $variation->get_description(), 3000 ),
			'weight'        => $variation->get_weight( 'edit' ),
			'price'         => $variation->get_price( 'edit' ),
			'regular_price' => $variation->get_regular_price( 'edit' ),
			'sale_price'    => $variation->get_sale_price( 'edit' ),
			'stock_status'  => $variation->get_stock_status(),
		);
	}
	return $out;
}

function emo_cured_source( int $product_id ): array {
	global $wpdb;
	$source_id = (int) get_post_meta( $product_id, '_emdo_source_product_id', true );
	if ( $source_id <= 0 || ! class_exists( 'MDO_Database' ) ) {
		return array();
	}
	$table = MDO_Database::table( 'source_products' );
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $source_id ), ARRAY_A );
	if ( ! is_array( $row ) ) {
		return array();
	}
	if ( isset( $row['source_payload'] ) ) {
		$decoded = json_decode( (string) $row['source_payload'], true );
		$row['source_payload'] = is_array( $decoded ) ? $decoded : $row['source_payload'];
	}
	return $row;
}

function emo_cured_normalize( string $text ): string {
	return strtolower( remove_accents( $text ) );
}

function emo_cured_bucket( string $haystack ): string {
	$h = emo_cured_normalize( $haystack );
	$accessory = '/\b(?:jamonero|cuchillo|chaira|afilador|pinza|tabla|soporte|funda)\b/u';
	if ( preg_match( $accessory, $h ) && ! preg_match( '/\b(?:choriz|salchich|lomo|lomito|cec|morcon|sobras|fuet|longan|embuch|curad|panceta|coppa|cabe(?:za|cero)|secreto)\b/u', $h ) ) {
		return 'accessory';
	}
	if ( preg_match( '/\b(?:adobad[oa]s?|adobo|marinad[oa]s?)\b/u', $h ) ) {
		return 'adobado_borderline';
	}
	if ( preg_match( '/\b(?:chorizo|chorizos|salchichon|salchichones|lomo|lomito|cecinas?|morcon|morcones|sobrasada|sobrasadas|fuet|fuets|longaniza|longanizas|embuchado|embuchados|embuchada|embuchadas|coppa|curado|curada|curados|curadas|embutido|embutidos)\b/u', $h ) ) {
		return 'likely_cured';
	}
	return 'other';
}

$ids = wc_get_products( array(
	'limit'  => -1,
	'return' => 'ids',
	'status' => array( 'publish', 'private', 'draft', 'pending' ),
) );

$index = array();
$candidates = array();
$counts = array(
	'all'                => 0,
	'likely_cured'       => 0,
	'adobado_borderline' => 0,
	'accessory'          => 0,
	'other'              => 0,
);

foreach ( array_map( 'intval', $ids ) as $id ) {
	$product = wc_get_product( $id );
	if ( ! $product instanceof WC_Product || $product->is_type( 'variation' ) ) {
		continue;
	}

	$name = $product->get_name();
	$description = emo_cured_plain( $product->get_description() );
	$short_description = emo_cured_plain( $product->get_short_description(), 4000 );
	$categories = emo_cured_terms( $id, 'product_cat' );
	$tags = emo_cured_terms( $id, 'product_tag' );
	$category_text = implode( ' ', array_map( static fn( array $t ): string => $t['name'] . ' ' . $t['slug'], $categories ) );
	$tag_text = implode( ' ', array_map( static fn( array $t ): string => $t['name'] . ' ' . $t['slug'], $tags ) );
	$haystack = implode( ' ', array( $name, $category_text, $tag_text, $short_description, $description ) );
	$bucket = emo_cured_bucket( $haystack );

	$author = get_user_by( 'id', (int) get_post_field( 'post_author', $id ) );
	$index[] = array(
		'id'         => $id,
		'name'       => $name,
		'status'     => $product->get_status(),
		'type'       => $product->get_type(),
		'vendor'     => $author ? $author->display_name : '',
		'categories' => array_values( array_map( static fn( array $t ): string => $t['name'], $categories ) ),
		'tags'       => array_values( array_map( static fn( array $t ): string => $t['name'], $tags ) ),
		'bucket'     => $bucket,
	);
	$counts['all']++;
	$counts[ $bucket ]++;

	if ( ! in_array( $bucket, array( 'likely_cured', 'adobado_borderline' ), true ) ) {
		continue;
	}

	$candidates[] = array(
		'id'                => $id,
		'name'              => $name,
		'slug'              => $product->get_slug(),
		'permalink'         => get_permalink( $id ),
		'status'            => $product->get_status(),
		'type'              => $product->get_type(),
		'sku'               => $product->get_sku(),
		'vendor'            => $author ? array( 'id' => (int) $author->ID, 'name' => $author->display_name ) : null,
		'bucket'            => $bucket,
		'categories'        => $categories,
		'tags'              => $tags,
		'description'       => $description,
		'short_description' => $short_description,
		'weight'            => $product->get_weight( 'edit' ),
		'dimensions'        => array(
			'length' => $product->get_length( 'edit' ),
			'width'  => $product->get_width( 'edit' ),
			'height' => $product->get_height( 'edit' ),
		),
		'attributes'        => emo_cured_attributes( $product ),
		'variations'        => emo_cured_variations( $product ),
		'source_product_id' => (int) get_post_meta( $id, '_emdo_source_product_id', true ),
		'source'            => emo_cured_source( $id ),
	);
}

usort( $index, static fn( array $a, array $b ): int => strcasecmp( $a['name'], $b['name'] ) );
usort( $candidates, static fn( array $a, array $b ): int => strcasecmp( $a['name'], $b['name'] ) );

$out = array(
	'generated_at' => current_time( 'mysql' ),
	'counts'       => $counts,
	'candidates'   => $candidates,
	'index'        => $index,
);

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
