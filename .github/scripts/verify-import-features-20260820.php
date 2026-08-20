<?php

if ( ! class_exists( 'MDO_Auto_Categorizer' ) ) {
	fwrite( STDERR, "auto categorizer class missing\n" );
	exit( 31 );
}

$urls = MDO_Supplier_Repository::source_urls( "https://example.com/a\nhttps://example.com/b" );
if ( 2 !== count( $urls ) ) {
	fwrite( STDERR, "multi source parser failed\n" );
	exit( 32 );
}

$probe  = new WC_Product_Simple();
$result = MDO_Auto_Categorizer::maybe_assign(
	$probe,
	array(
		'title'       => 'EMDO category probe qzxw',
		'description' => '',
	),
	array( 'source_url' => 'https://example.invalid/qzxw' )
);
if ( ! is_array( $result ) ) {
	fwrite( STDERR, "auto category probe failed\n" );
	exit( 33 );
}

$terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $terms ) ) {
	fwrite( STDERR, "product categories unavailable\n" );
	exit( 34 );
}

echo 'mdo_import_features_production_ok categories=' . count( $terms ) . ' probe_reason=' . ( $result['reason'] ?? 'unknown' ) . PHP_EOL;
