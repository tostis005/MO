<?php
/**
 * UI regression tests for Club EMDO account and gift checkout.
 * No WordPress session and no monetary operations are executed.
 */
declare(strict_types=1);
$root = dirname( __DIR__ );
$club = (string) file_get_contents( $root . '/mdo-supplier-sync/includes/class-mdo-club.php' );
$checkout = (string) file_get_contents( $root . '/mdo-supplier-sync/includes/class-mdo-club-checkout.php' );
$account = (string) file_get_contents( $root . '/mu-plugins/mdo-my-account-premium-20261105.php' );
$assert = static function( bool $valid, string $message ): void {
	if ( ! $valid ) {
		throw new RuntimeException( $message );
	}
};
foreach ( array(
	'Compra digital: no necesitas dirección de envío',
	'digital_checkout_intro',
	'mdo-club-checkout-digital',
) as $fragment ) {
	$assert( ! str_contains( $checkout, $fragment ), 'Remove redundant checkout help: ' . $fragment );
}
foreach ( array(
	'Esta compra se hace por separado de otros productos',
	'mdo-club-separate-note',
) as $fragment ) {
	$assert( ! str_contains( $club, $fragment ), 'Remove redundant cart explanation: ' . $fragment );
}
$assert( ! str_contains( $account, 'Compra con origen, disfruta de recompensas y comparte buenos regalos.' ), 'Dashboard slogan removed' );
$assert( ! str_contains( $account, '.emdo-account-hub__banner span {display:block' ), 'Do not force WC currency to a new line' );
$assert( str_contains( $account, 'emdo-account-hub__banner-balance' ), 'New compact account Club balance' );
$assert( str_contains( $account, 'emdo-account-hub__balance-value' ), 'Balance markup and specific CSS' );
$assert( str_contains( $account, '.emdo-account-hub__balance-value .woocommerce-Price-currencySymbol' ), 'Dashboard currency symbol stays inline' );
$assert( str_contains( $account, 'white-space:nowrap!important' ), 'Currency amount cannot break' );
$assert( str_contains( $club, 'mdo-club-hero--wallet' ) && str_contains( $club, 'mdo-club-hero__balance' ), 'Dedicated account wallet hero layout' );
$assert( str_contains( $club, 'mdo-club-hero--wallet .mdo-club-hero__amount .woocommerce-Price-currencySymbol' ), 'Wallet currency symbol stays inline' );
$assert( str_contains( $club, '.mdo-club .mdo-club-card h3{color:#224334!important' ), 'Readable card headings in light surfaces' );
$assert( str_contains( $checkout, "digital_checkout_fields' ), PHP_INT_MAX" ), 'Keep reduced billing fields for digital checkout' );
$assert( str_contains( $checkout, 'prepaid_needs_no_shipping' ), 'Keep virtual/no-shipping behavior' );
$assert( str_contains( $club, 'admin_post_nopriv_mdo_club_purchase' ), 'Preserve gift checkout flow' );
echo "Club EMDO account visual cleanup: OK\n";
