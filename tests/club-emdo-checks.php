<?php
/**
 * Club EMDO structural/regression checks (no WordPress runtime required).
 * Actual money flows still require staging integration tests with the real gateway.
 */
declare( strict_types=1 );
$base = dirname( __DIR__ ) . '/mdo-supplier-sync/';
$files = array(
	'bootstrap' => file_get_contents( $base . 'mdo-supplier-sync.php' ),
	'club' => file_get_contents( $base . 'includes/class-mdo-club.php' ),
	'checkout' => file_get_contents( $base . 'includes/class-mdo-club-checkout.php' ),
	'ledger' => file_get_contents( $base . 'includes/class-mdo-club-ledger.php' ),
);
foreach ( $files as $name => $source ) {
	if ( ! is_string( $source ) || '' === $source ) { throw new RuntimeException( "Missing {$name}" ); }
}
$requires = array(
	'bootstrap' => array( 'class-mdo-club-ledger.php', 'class-mdo-club.php', 'class-mdo-club-checkout.php', 'MDO_Club::init()', 'MDO_Club_Checkout::init()' ),
	'club' => array( 'Club EMDO', "'mdo_club_reward_percent', '1'", "'mdo_club_' . \$feature . '_enabled', 'no'", 'woocommerce_order_status_completed', 'woocommerce_payment_complete', 'woocommerce_email_enabled_new_order', 'mdo_club_gift_cards', 'woocommerce_checkout_create_order_line_item' ),
	'checkout' => array( 'woocommerce_calculated_total', 'woocommerce_checkout_order_created', 'woocommerce_order_status_cancelled', 'woocommerce_get_order_item_totals', 'Total a facturar:', '_mdo_club_full_total_cents', 'MDO_Club::enabled' ),
	'ledger' => array( 'START TRANSACTION', 'ROLLBACK', 'COMMIT', 'UNIQUE KEY event_key', 'FOR UPDATE', 'redeemed_user_id', 'gift_code' ),
);
foreach ( $requires as $file => $strings ) {
	foreach ( $strings as $needle ) {
		if ( false === strpos( $files[ $file ], $needle ) ) {
			throw new RuntimeException( "Missing safeguard in {$file}: {$needle}" );
		}
	}
}
if ( false !== strpos( $files['checkout'], 'MDO_Club EMDO::' ) ) {
	throw new RuntimeException( 'Invalid class name introduced by UI text replacement.' );
}
if ( false !== strpos( $files['club'], "'mdo_club_' . \$feature . '_enabled', 'yes'" ) ) {
	throw new RuntimeException( 'Club must be off by default.' );
}
echo "Club EMDO static safeguards: OK\n";