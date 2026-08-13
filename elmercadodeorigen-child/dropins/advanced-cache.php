<?php
/**
 * El Mercado de Origen early cache drop-in.
 *
 * The public Home HTML cache is intentionally disabled. Home copy changes must
 * be visible immediately and consistently across devices, so WordPress must
 * render the current Home instead of serving a previously generated HTML file.
 *
 * WP_CACHE may remain enabled for compatibility with WordPress, but this
 * drop-in deliberately does not serve cached HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ELMERCADO_EARLY_HOME_CACHE' ) ) {
	define( 'ELMERCADO_EARLY_HOME_CACHE', false );
}

return;
