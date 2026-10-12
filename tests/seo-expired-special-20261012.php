<?php
/** Regression checks for the expired editorial special banner. */
declare( strict_types=1 );
define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../elmercadodeorigen-child/inc/blog-expired-special-20261012.php';

function check_special( string $name, string $html, string $today, bool $should_hide ): void {
	$actual = elmercado_blog_hide_expired_special_20261012( $html, $today );
	$hidden = '' === $actual;
	if ( $hidden !== $should_hide ) {
		fwrite( STDERR, 'FAIL: ' . $name . "\n" );
		exit( 1 );
	}
}

check_special( 'Expired English card', '<aside>Available until 30 September 2026</aside>', '2026-10-12', true );
check_special( 'Expired Spanish card', '<aside>Disponible hasta 30 de septiembre de 2026</aside>', '2026-10-12', true );
check_special( 'Valid English card', '<aside>Available until 14 October 2026</aside>', '2026-10-12', false );
check_special( 'Valid on deadline day', '<aside>Available until 12 October 2026</aside>', '2026-10-12', false );
check_special( 'Expired the following day', '<aside>Available until 12 October 2026</aside>', '2026-10-13', true );
check_special( 'No expiry remains visible', '<aside>Offer available now</aside>', '2026-10-12', false );
check_special( 'Invalid date remains visible', '<aside>Available until 31 February 2026</aside>', '2026-10-12', false );
check_special( 'Unsupported date remains visible', '<aside>Available until 2026-09-30</aside>', '2026-10-12', false );
check_special( 'Ambiguous card remains visible', '<aside>Available until 30 September 2026. Available until 21 October 2026</aside>', '2026-10-12', false );
check_special( 'Spanish accented expiry', '<aside>Válido hasta 30 de septiembre de 2026</aside>', '2026-10-12', true );

echo "OK: expired specials suppressed; future and ambiguous offers preserved.\n";
