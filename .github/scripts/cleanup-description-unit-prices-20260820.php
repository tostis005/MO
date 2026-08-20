<?php
/**
 * One-off production cleanup: keep only the sales unit label in descriptions.
 *
 * Converts EMDO-added notes such as:
 *   Precio por kilo: 3,83 €/kg
 * into:
 *   Precio por kilo
 *
 * It audits La Huerta de Ana Mary and Tolecarnes. It intentionally does not
 * touch unrelated source-authored monetary references (offers, shipping, etc.).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}
if ( ! class_exists( 'MDO_Database' ) ) {
	fwrite( STDERR, "ERROR: MDO database layer is not loaded.\n" );
	exit( 2 );
}

global $wpdb;
$suppliers_table = MDO_Database::table( 'suppliers' );
$products_table  = MDO_Database::table( 'source_products' );

$rows = $wpdb->get_results(
	"SELECT sp.id,sp.supplier_id,sp.source_url,sp.wc_product_id,sp.status,s.name AS supplier_name,s.code AS supplier_code,s.source_url AS supplier_url
	 FROM {$products_table} sp
	 LEFT JOIN {$suppliers_table} s ON s.id=sp.supplier_id
	 WHERE sp.wc_product_id IS NOT NULL AND sp.wc_product_id > 0
	 ORDER BY sp.id ASC",
	ARRAY_A
);

$summary = array(
	'scanned'              => 0,
	'huerta'               => 0,
	'tolecarnes'           => 0,
	'changed'              => 0,
	'huerta_changed'       => 0,
	'tolecarnes_changed'   => 0,
	'huerta_repaired'      => 0,
	'forbidden_remaining'  => 0,
	'processing_errors'    => 0,
);
$errors = array();

/**
 * Remove only a numeric sales amount attached to a "Precio por ..." label.
 * The label itself is preserved exactly, including labels such as
 * "Precio por 500 g" if one is ever introduced.
 */
$strip_amounts = static function ( string $html ): string {
	// First normalize the dedicated EMDO marker, if present.
	$html = preg_replace_callback(
		'~<p\b([^>]*)class=["\']([^"\']*\bemdo-source-unit-price\b[^"\']*)["\']([^>]*)>(.*?)</p>~isu',
		static function ( array $m ): string {
			$inner = (string) $m[4];
			$plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $inner ) ) );
			if ( preg_match( '/\b(Precio\s+por\s+(?:kilo(?:gramo)?|unidad|pieza|caja|cesta|\d+(?:[.,]\d+)?\s*(?:g|gr|gramos?|kg|kilos?)))\b/iu', $plain, $label ) ) {
				return '<p class="emdo-source-unit-price"><strong>' . esc_html( $label[1] ) . '</strong></p>';
			}
			return (string) $m[0];
		},
		$html
	);

	// Backward-compatible cleanup for an EMDO-added note that may have lost its class.
	$unit = '(?:kilo(?:gramo)?|unidad|pieza|caja|cesta|\d+(?:[.,]\d+)?\s*(?:g|gr|gramos?|kg|kilos?))';
	$html = preg_replace(
		'~(Precio\s+por\s+' . $unit . ')\s*:?[ \x{00A0}]*(?:</strong>\s*)?(?:<[^>]+>\s*)*\d{1,6}(?:[.,]\d{1,2})?\s*€(?:\s*/\s*[\p{L}\d.]+)?~iu',
		'$1',
		(string) $html
	);

	return (string) $html;
};

$has_forbidden = static function ( string $html ): bool {
	$plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
	if ( preg_match( '/Precio\s+por\s+(?:kilo(?:gramo)?|unidad|pieza|caja|cesta|\d+(?:[.,]\d+)?\s*(?:g|gr|gramos?|kg|kilos?))\s*:?\s*\d{1,6}(?:[.,]\d{1,2})?\s*€/iu', $plain ) ) {
		return true;
	}
	if ( preg_match( '~<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?€.*?</p>~isu', $html ) ) {
		return true;
	}
	return false;
};

foreach ( (array) $rows as $row ) {
	$product_id = absint( $row['wc_product_id'] ?? 0 );
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		continue;
	}

	$source_url   = strtolower( trim( (string) ( $row['source_url'] ?? '' ) ) );
	$supplier_url = strtolower( trim( (string) ( $row['supplier_url'] ?? '' ) ) );
	$supplier     = strtolower( trim( (string) ( $row['supplier_name'] ?? '' ) . ' ' . (string) ( $row['supplier_code'] ?? '' ) ) );
	$is_huerta    = str_contains( $source_url, 'lahuertadeanamary.com' ) || str_contains( $supplier_url, 'lahuertadeanamary.com' );
	$is_tole      = str_contains( $source_url, 'tolecarnes' ) || str_contains( $supplier_url, 'tolecarnes' ) || str_contains( $supplier, 'tole' );
	if ( ! $is_huerta && ! $is_tole ) {
		continue;
	}

	++$summary['scanned'];
	if ( $is_huerta ) {
		++$summary['huerta'];
	} else {
		++$summary['tolecarnes'];
	}

	try {
		if ( $is_huerta && class_exists( 'MDO_Huerta_Catalog_Quality' ) ) {
			$repair = MDO_Huerta_Catalog_Quality::repair_product( $product_id );
			if ( empty( $repair['error'] ) ) {
				++$summary['huerta_repaired'];
			} else {
				throw new RuntimeException( (string) $repair['error'] );
			}
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			throw new RuntimeException( 'WooCommerce product unavailable.' );
		}
		$before = (string) $product->get_description();
		$after  = $strip_amounts( $before );
		if ( trim( $after ) !== trim( $before ) ) {
			$product->set_description( wp_kses_post( $after ) );
			$product->save();
			++$summary['changed'];
			if ( $is_huerta ) {
				++$summary['huerta_changed'];
			} else {
				++$summary['tolecarnes_changed'];
			}
		}

		$final = (string) get_post_field( 'post_content', $product_id );
		if ( $has_forbidden( $final ) ) {
			++$summary['forbidden_remaining'];
			$errors[] = sprintf( 'Numeric unit-price note remains on product %d (%s)', $product_id, get_the_title( $product_id ) );
		}

		if ( preg_match( '~<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>(.*?)</p>~isu', $final, $marker ) ) {
			echo sprintf( "UNIT_LABEL %d | %s | %s\n", $product_id, get_the_title( $product_id ), trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $marker[1] ) ) ) );
		}
	} catch ( Throwable $error ) {
		++$summary['processing_errors'];
		$errors[] = sprintf( 'Product %d: %s', $product_id, $error->getMessage() );
	}
}

foreach ( $errors as $error ) {
	fwrite( STDERR, 'UNIT_LABEL_ERROR ' . $error . "\n" );
}

echo 'DESCRIPTION_UNIT_LABEL_SUMMARY ' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

if ( $summary['huerta'] < 46 ) {
	fwrite( STDERR, "ERROR: expected at least 46 La Huerta products in the audit.\n" );
	exit( 10 );
}
if ( $summary['forbidden_remaining'] || $summary['processing_errors'] ) {
	exit( 11 );
}

echo "description_unit_labels_ok\n";
