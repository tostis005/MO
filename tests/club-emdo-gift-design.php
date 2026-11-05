<?php
/**
 * Verify approved gift card artwork and use of one shared image on page/email.
 */
declare( strict_types=1 );
$root = dirname( __DIR__ );
$encoded = file_get_contents( $root . '/mdo-supplier-sync/assets/club-emdo-tarjeta-regalo.jpg.b64' );
$bytes = base64_decode( trim( (string) $encoded ), true );
if ( ! is_string( $bytes ) || hash( 'sha256', $bytes ) !== 'f34b1e2aa850d3f1eb2e7dcb588e65828260136d83e19eb57817a249dec64e3f' ) {
	throw new RuntimeException( 'Premium gift-card artwork hash mismatch' );
}
$meta = getimagesizefromstring( $bytes );
if ( ! is_array( $meta ) || $meta['mime'] !== 'image/jpeg' || $meta[0] !== 900 || $meta[1] !== 600 ) {
	throw new RuntimeException( 'Expected 900x600 JPEG artwork' );
}
$src = file_get_contents( $root . '/mdo-supplier-sync/includes/class-mdo-club.php' );
if ( substr_count( $src, 'self::gift_art_url()' ) < 2 ||
	! str_contains( $src, 'assets/club-emdo-tarjeta-regalo.jpg' ) ||
	! str_contains( $src, 'display:inline-flex!important' ) ||
	! str_contains( $src, 'justify-content:center!important' ) ||
	str_contains( $src, 'Una tarjeta, muchos sabores.' ) ) {
	throw new RuntimeException( 'Gift card display, email or centered landing buttons not wired' );
}
echo "Club EMDO premium gift design: OK\n";
