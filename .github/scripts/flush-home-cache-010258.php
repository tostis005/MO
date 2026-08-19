<?php
if ( function_exists( 'elmercado_flush_home_cache' ) ) {
	elmercado_flush_home_cache();
	echo "elmercado-home-cache-flushed\n";
} else {
	fwrite( STDERR, "elmercado_flush_home_cache unavailable\n" );
	exit( 2 );
}
