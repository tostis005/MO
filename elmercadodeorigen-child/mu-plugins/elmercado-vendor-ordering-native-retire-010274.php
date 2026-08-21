<?php
/**
 * Compatibility tombstone for vendor ordering native-retire 0.10.274.
 *
 * Ordering is now owned exclusively by elmercado-vendor-ordering-popover-010272.php
 * (implementation 0.10.276). Keeping this file as a no-op prevents old deployment
 * paths from reintroducing the previous observer-based workaround.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
