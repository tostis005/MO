<?php
/** Regression: dashboard Club destination and equal prominence of gift actions. */
declare(strict_types=1);
$root = dirname( __DIR__ );
$account = (string) file_get_contents( $root . '/mu-plugins/mdo-my-account-premium-20261105.php' );
$club = (string) file_get_contents( $root . '/mdo-supplier-sync/includes/class-mdo-club.php' );
$assert = static function( bool $ok, string $message ): void {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
};
$assert( str_contains( $account, "\$club_url = wc_get_account_endpoint_url( 'monedero' );" ), 'Dashboard CTA links to full wallet page' );
$assert( str_contains( $account, 'esc_url( $club_url )' ) && str_contains( $account, 'Ir a Club EMDO' ), 'Dashboard Club CTA has expected label and URL' );
$assert( ! str_contains( $account, 'Ver tarjetas regalo</a></div>' ), 'Old gift-only dashboard button removed' );
$assert( str_contains( $club, 'class="mdo-club-redeem__actions"' ), 'Redeem form has dedicated two-button action row' );
$assert( str_contains( $club, 'type="submit" class="button">Canjear</button>' ), 'Redeem action still submits its form' );
$assert( str_contains( $club, '<a class="button" href="<?php echo esc_url( get_permalink( (int) get_option( \'mdo_club_gift_page\' ) ) ); ?>">Comprar tarjeta</a>' ), 'Gift purchase action links to public gift page and has same button class' );
$assert( preg_match( '~<div class="mdo-club-redeem__actions">[\s\S]*?<button type="submit" class="button">Canjear</button>[\s\S]*?<a class="button" href="[\s\S]*?Comprar tarjeta</a>[\s\S]*?</div>[\s\S]*?</form>~', $club ) === 1, 'Both actions are together inside redeem form without invalid nested forms' );
$assert( ! str_contains( $club, '<p><a href="<?php echo esc_url( get_permalink( (int) get_option( \'mdo_club_gift_page\' ) ) ); ?>">Comprar tarjeta regalo</a></p>' ), 'Old low-prominence gift link removed' );
$assert( str_contains( $club, 'grid-template-columns:repeat(2,minmax(0,1fr))' ), 'Two equal-width button columns' );
$assert( str_contains( $club, '.mdo-club-redeem__actions > :is(button.button,a.button)' ), 'Both controls use exactly one shared style rule' );
$assert( str_contains( $club, '@media(max-width:360px)' ), 'Compact mobile device layout accounted for' );
$assert( str_contains( $club, 'wp_nonce_field( \'mdo_club_redeem\' )' ), 'Redeem nonce remains intact' );
$assert( str_contains( $club, 'name="gift_code"' ), 'Redeem input remains intact' );
echo "Club EMDO dashboard and gift action CTAs: OK\n";
