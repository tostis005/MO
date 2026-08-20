<?php
/** One-off production repair and verification for La Huerta de Ana Mary. */
// Trigger after the workflow is present on main.
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! class_exists( 'MDO_Database' ) || ! class_exists( 'MDO_Huerta_Defaults' ) || ! class_exists( 'MDO_Huerta_Catalog_Quality' ) ) {
	fwrite( STDERR, "ERROR: required EMDO Huerta classes are not loaded.\n" );
	exit( 2 );
}

global $wpdb;
$table = MDO_Database::table( 'source_products' );
$rows = $wpdb->get_results(
	"SELECT id,supplier_id,source_url,wc_product_id,title,status,source_price
	 FROM {$table}
	 WHERE source_url LIKE '%lahuertadeanamary.com%'
	 ORDER BY id ASC",
	ARRAY_A
);

$source_hosts = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
$counts = array(
	'rows' => 0,
	'products' => 0,
	'changed' => 0,
	'titles_changed' => 0,
	'descriptions_changed' => 0,
	'unit_prices' => 0,
	'legume_unit_prices' => 0,
	'category_errors' => 0,
	'text_errors' => 0,
	'processing_errors' => 0,
);

$family_counts = array( 'conservas' => 0, 'legumbres' => 0, 'hortalizas' => 0 );
$unit_lines = array();
$errors = array();

foreach ( (array) $rows as $row ) {
	$url = trim( (string) ( $row['source_url'] ?? '' ) );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( ! in_array( $host, $source_hosts, true ) ) { continue; }
	++$counts['rows'];

	$product_id = absint( $row['wc_product_id'] ?? 0 );
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		$errors[] = 'Missing Woo product for source row ' . (int) $row['id'];
		++$counts['processing_errors'];
		continue;
	}
	++$counts['products'];

	$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$family = 'hortalizas';
	if ( str_contains( $path, '/conservas-3/' ) ) { $family = 'conservas'; }
	elseif ( str_contains( $path, '/legumbres-10/' ) ) { $family = 'legumbres'; }
	++$family_counts[ $family ];

	MDO_Huerta_Defaults::apply_to_product( $product_id );
	$quality = MDO_Huerta_Catalog_Quality::repair_product( $product_id );
	if ( ! empty( $quality['error'] ) ) {
		$errors[] = sprintf( 'Product %d quality error: %s', $product_id, (string) $quality['error'] );
		++$counts['processing_errors'];
	}
	if ( ! empty( $quality['changed'] ) ) { ++$counts['changed']; }
	if ( ! empty( $quality['title_changed'] ) ) { ++$counts['titles_changed']; }
	if ( ! empty( $quality['description_changed'] ) ) { ++$counts['descriptions_changed']; }
	if ( ! empty( $quality['unit_price'] ) ) {
		++$counts['unit_prices'];
		if ( 'legumbres' === $family ) { ++$counts['legume_unit_prices']; }
		$unit_lines[] = sprintf( '%d|%s|%s', $product_id, get_the_title( $product_id ), (string) $quality['unit_price'] );
	}

	$title = (string) get_the_title( $product_id );
	$description = (string) get_post_field( 'post_content', $product_id );
	if ( preg_match( '/(?:Ã|Â|â|�|\?\?)/u', $title ) || preg_match( '/\b(?:Calabacin|Brocoli|padron|recibelas|picate)\b/iu', $title ) ) {
		$errors[] = sprintf( 'Text verification failed for title %d: %s', $product_id, $title );
		++$counts['text_errors'];
	}
	if ( preg_match( '/(?:grecaptcha\.ready|document\.ready|thickbox|@media\s+all|g_recaptcha)/iu', wp_strip_all_tags( $description ) ) ) {
		$errors[] = sprintf( 'Polluted description remains on product %d', $product_id );
		++$counts['text_errors'];
	}

	$expected_slug = 'hortalizas-verduras';
	$forbidden = array( 'conservas', 'legumbres', 'sin-categorizar' );
	if ( 'conservas' === $family ) {
		$expected_slug = 'conservas';
		$forbidden = array( 'hortalizas-verduras', 'legumbres', 'sin-categorizar' );
	} elseif ( 'legumbres' === $family ) {
		$expected_slug = 'legumbres';
		$forbidden = array( 'hortalizas-verduras', 'conservas', 'sin-categorizar' );
	}

	if ( ! has_term( $expected_slug, 'product_cat', $product_id ) ) {
		$errors[] = sprintf( 'Category verification failed for %d: missing %s', $product_id, $expected_slug );
		++$counts['category_errors'];
	}
	foreach ( $forbidden as $slug ) {
		if ( has_term( $slug, 'product_cat', $product_id ) ) {
			$errors[] = sprintf( 'Category verification failed for %d: forbidden %s', $product_id, $slug );
			++$counts['category_errors'];
		}
	}

	echo sprintf(
		"HUERTA_REPAIRED %d | %s | family=%s | unit=%s | cats=%s\n",
		$product_id,
		$title,
		$family,
		! empty( $quality['unit_price'] ) ? (string) $quality['unit_price'] : '-',
		implode( ',', wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) ) ?: array() )
	);
}

foreach ( $unit_lines as $line ) {
	echo 'HUERTA_UNIT ' . $line . "\n";
}
foreach ( $errors as $error ) {
	fwrite( STDERR, 'HUERTA_ERROR ' . $error . "\n" );
}

ksort( $family_counts );
echo 'HUERTA_FAMILIES ' . wp_json_encode( $family_counts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo 'HUERTA_REPAIR_SUMMARY ' . wp_json_encode( $counts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

if ( 46 !== $counts['products'] || 23 !== $family_counts['conservas'] || 5 !== $family_counts['legumbres'] || 18 !== $family_counts['hortalizas'] ) {
	fwrite( STDERR, "ERROR: Huerta catalog cardinality changed during repair.\n" );
	exit( 10 );
}
if ( $counts['processing_errors'] || $counts['category_errors'] || $counts['text_errors'] ) {
	exit( 11 );
}
if ( $counts['legume_unit_prices'] < 1 ) {
	fwrite( STDERR, "ERROR: no source per-unit/per-kilo price was detected for any legume.\n" );
	exit( 12 );
}

echo "huerta_repair_ok\n";
