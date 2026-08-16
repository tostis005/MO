<?php
/**
 * El Mercado de Origen early cache drop-in.
 *
 * The pre-bootstrap public Home cache is intentionally disabled. The child
 * theme's WordPress-level Home cache remains available so the final presentation
 * and translation pipeline are preserved.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'ELMERCADO_EARLY_HOME_CACHE' ) ) {
	define( 'ELMERCADO_EARLY_HOME_CACHE', false );
}
return;
