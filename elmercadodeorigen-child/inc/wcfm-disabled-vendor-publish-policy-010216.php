<?php
/**
 * Mantiene publicados los productos cuando WCFM desactiva u offlinea una tienda.
 *
 * WCFM Marketplace, por defecto, cambia los productos publicados a `archived`
 * cuando se deshabilita un vendedor o se pone su tienda offline. En El Mercado
 * de Origen la política es distinta: los productos deben seguir publicados para
 * que los administradores puedan auditarlos y gestionarlos, mientras que
 * wcfm-disabled-vendor-visibility-010210.php los oculta y bloquea para el público.
 *
 * Este filtro afecta únicamente al cambio automático de estado provocado por
 * vendor/store offline. No deshabilita el archivado manual de productos.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wcfm_is_allow_offline_vendor_product_status_reset', '__return_false', 999 );
