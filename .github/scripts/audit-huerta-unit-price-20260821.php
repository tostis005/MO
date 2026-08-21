<?php

if ( ! class_exists( 'MDO_Huerta_Unit_Price' ) ) {
	fwrite( STDERR, "ERROR: MDO_Huerta_Unit_Price no está cargada.\n" );
	exit( 20 );
}

$stats = MDO_Huerta_Unit_Price::audit_all_products();
echo 'huerta_unit_price_ok ' . wp_json_encode( $stats, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;

if ( ! empty( $stats['errors'] ) ) {
	exit( 2 );
}
