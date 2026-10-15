<?php
/**
 * Lanzamiento acotado de Latitudes en el WordPress REAL desde GitHub Actions.
 *
 * LATITUDES_MODE=dry-run|publish wp eval-file <ruta>
 *
 * 138 referencias importadas expresamente como drafts con catalog_visibility
 * hidden y el vendedor WCFM desactivado. Nunca tocar otros proveedores.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$mode = (string) getenv( 'LATITUDES_MODE' );
if ( ! in_array( $mode, array( 'dry-run', 'publish' ), true ) ) {
	WP_CLI::error( 'LATITUDES_MODE solo permite dry-run o publish.' );
}
$home = untrailingslashit( home_url( '/' ) );
$site = untrailingslashit( site_url( '/' ) );
$allowed = array( 'https://www.elmercadodeorigen.com', 'https://elmercadodeorigen.com' );
if ( ! in_array( $home, $allowed, true ) || ! in_array( $site, $allowed, true ) ) {
	WP_CLI::error( 'No es producción: home=' . $home . ', siteurl=' . $site );
}
if ( ! class_exists( 'MDO_Database' ) || ! function_exists( 'wc_get_product' ) ) {
	WP_CLI::error( 'EMDO o WooCommerce no están cargados en producción.' );
}

global $wpdb;
$supplier_table = MDO_Database::table( 'suppliers' );
$source_table   = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row(
	$wpdb->prepare( "SELECT id, code, connector, vendor_user_id FROM {$supplier_table} WHERE code=%s LIMIT 1", 'latitudes' ),
	ARRAY_A
);
if ( ! $supplier || 'latitudes' !== $supplier['connector'] || (int) $supplier['vendor_user_id'] <= 0 ) {
	WP_CLI::error( 'Latitudes no está vinculado al conector y vendedor esperados en EMDO.' );
}
$vendor_id = (int) $supplier['vendor_user_id'];
$user = get_userdata( $vendor_id );
if ( ! $user instanceof WP_User ) {
	WP_CLI::error( 'No existe el vendedor vinculado a Latitudes.' );
}
$profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
$store_name = is_array( $profile ) ? (string) ( $profile['store_name'] ?? '' ) : '';
$store_url = function_exists( 'wcfmmp_get_store_url' ) ? (string) wcfmmp_get_store_url( $vendor_id ) : '';
$path = (string) wp_parse_url( $store_url, PHP_URL_PATH );
$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
$store_slug = sanitize_title( (string) end( $parts ) );
if ( ! in_array( 'latitudes', array(
	sanitize_title( $user->user_nicename ),
	sanitize_title( $user->user_login ),
	sanitize_title( $store_name ),
	$store_slug,
), true ) ) {
	WP_CLI::error( 'El usuario vinculado a EMDO no corresponde inequívocamente a la tienda Latitudes.' );
}

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, status, wc_product_id, title, source_price, source_stock_status
		   FROM {$source_table} WHERE supplier_id=%d ORDER BY id",
		(int) $supplier['id']
	),
	ARRAY_A
);
if ( 138 !== count( $rows ) ) {
	WP_CLI::error( 'El lote de Latitudes no coincide con las 138 referencias esperadas; hay ' . count( $rows ) );
}

$bad = array();
$ready = array();
$already_public = 0;
foreach ( $rows as $row ) {
	$source_id = (int) $row['id'];
	$product_id = (int) $row['wc_product_id'];
	$product = $product_id > 0 ? wc_get_product( $product_id ) : false;
	$reason = '';
	if ( 'active' !== $row['status'] || ! $product ) {
		$reason = 'origen no activo o producto ausente';
	} elseif ( (int) get_post_field( 'post_author', $product_id ) !== $vendor_id
		|| (int) get_post_meta( $product_id, '_emdo_source_product_id', true ) !== $source_id
		|| (int) get_post_meta( $product_id, '_emdo_supplier_id', true ) !== (int) $supplier['id'] ) {
		$reason = 'el producto no pertenece inequívocamente al origen/vendedor Latitudes';
	} elseif ( function_exists( 'wcfm_get_vendor_id_by_post' )
		&& (int) wcfm_get_vendor_id_by_post( $product_id ) !== $vendor_id ) {
		$reason = 'WCFM atribuye el producto a otro usuario';
	} elseif ( ! in_array( $product->get_status(), array( 'draft', 'publish' ), true ) ) {
		$reason = 'estado inesperado: ' . $product->get_status();
	} elseif ( ! in_array( $product->get_catalog_visibility(), array( 'hidden', 'visible' ), true ) ) {
		$reason = 'visibilidad inesperada';
	} elseif ( '' === trim( (string) $product->get_name() )
		|| ! $product->get_image_id()
		|| mb_strlen( trim( wp_strip_all_tags( (string) $product->get_description() ) ) ) < 35 ) {
		$reason = 'nombre, imagen o descripción incompletos';
	} elseif ( ! is_numeric( $product->get_price() )
		|| (float) $product->get_price() <= 0
		|| abs( (float) $product->get_price() - (float) $row['source_price'] ) > 0.02 ) {
		$reason = 'precio incorrecto o diferente del origen';
	} elseif ( ! in_array( (string) $row['source_stock_status'], array( 'instock', 'outofstock' ), true )
		|| $product->get_stock_status() !== $row['source_stock_status'] ) {
		$reason = 'stock no verificado o distinto del origen';
	}

	if ( '' !== $reason ) {
		$bad[] = array( 'source' => $source_id, 'product' => $product_id, 'error' => $reason );
		continue;
	}
	if ( 'publish' === $product->get_status() && 'visible' === $product->get_catalog_visibility() ) {
		++$already_public;
	} else {
		$ready[] = $product_id;
	}
}

$shipping = get_user_meta( $vendor_id, '_wcfmmp_shipping', true );
$shipping_enabled = is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_enable'] ?? '' ) : '';
$shipping_type = is_array( $shipping ) ? (string) ( $shipping['_wcfmmp_user_shipping_type'] ?? '' ) : '';
$destination_checks = array();
if ( class_exists( 'MDO_Shipping_Destinations' ) ) {
	foreach ( array( 'Península' => array( 'ES', '28001' ), 'Baleares' => array( 'ES', '07001' ), 'Portugal' => array( 'PT', '1000-001' ) ) as $label => $dest ) {
		$destination_checks[ $label ] = MDO_Shipping_Destinations::vendor_can_ship_to( $vendor_id, $dest[0], $dest[1] );
	}
}

$shipping_ok = 'yes' === $shipping_enabled && 'by_zone' === $shipping_type
	&& count( $destination_checks ) === 3
	&& ! in_array( false, $destination_checks, true );
if ( ! $shipping_ok ) {
	$bad[] = array( 'shipping' => 'WCFM no tiene envíos por zonas habilitados para Península, Baleares y Portugal.' );
}

// Datos no sensibles del esquema WCFM para poder preparar las zonas NATIVAS.
$native_zone_debug = array();
if ( function_exists( 'wcfmmp_get_shipping_zone' ) ) {
	$native = wcfmmp_get_shipping_zone( '', $vendor_id );
	$native_zone_debug['latitudes_zone_count'] = is_array( $native ) ? count( $native ) : 0;
}
if ( class_exists( 'WC_Shipping_Zones' ) ) {
	$global_zones = WC_Shipping_Zones::get_zones();
	$native_zone_debug['woocommerce_zones'] = array();
	foreach ( $global_zones as $zid => $zone ) {
		$locs = array();
		foreach ( (array) ( $zone['zone_locations'] ?? array() ) as $loc ) {
			$locs[] = (string) ( $loc->type ?? '' ) . ':' . (string) ( $loc->code ?? '' );
		}
		$methods = array();
		foreach ( (array) ( $zone['shipping_methods'] ?? array() ) as $method ) {
			$methods[] = array(
				'id' => (string) ( $method->id ?? '' ),
				'enabled' => (string) ( $method->enabled ?? '' ),
			);
		}
		$native_zone_debug['woocommerce_zones'][] = array(
			'id' => (int) $zid,
			'name' => (string) ( $zone['zone_name'] ?? '' ),
			'locations' => array_slice( $locs, 0, 16 ),
			'shipping_methods' => $methods,
		);
	}
}
$native_zone_debug['working_vendor_example'] = null;
if ( function_exists( 'wcfmmp_get_shipping_zone' ) ) {
	foreach ( get_users( array( 'role' => 'wcfm_vendor', 'number' => 50 ) ) as $other_vendor ) {
		if ( (int) $other_vendor->ID === $vendor_id ) {
			continue;
		}
		$other_shipping = get_user_meta( $other_vendor->ID, '_wcfmmp_shipping', true );
		if ( ! is_array( $other_shipping ) || ( $other_shipping['_wcfmmp_user_shipping_type'] ?? '' ) !== 'by_zone' ) {
			continue;
		}
		$other_zones = wcfmmp_get_shipping_zone( '', (int) $other_vendor->ID );
		if ( ! is_array( $other_zones ) || ! $other_zones ) {
			continue;
		}
		$sample = reset( $other_zones );
		$native_zone_debug['working_vendor_example'] = array(
			'shipping_settings_keys' => array_keys( $other_shipping ),
			'shipping_enabled' => (string) ( $other_shipping['_wcfmmp_user_shipping_enable'] ?? '' ),
			'zone_fields' => is_array( $sample ) ? array_keys( $sample ) : ( is_object( $sample ) ? array_keys( get_object_vars( $sample ) ) : array() ),
			'zone_count' => count( $other_zones ),
		);
		break;
	}
}
$vendor_roles = (array) $user->roles;
if ( ! in_array( 'wcfm_vendor', $vendor_roles, true ) && ! in_array( 'disable_vendor', $vendor_roles, true ) ) {
	$bad[] = array( 'vendor_roles' => 'Roles de vendedor inesperados: ' . implode( ',', $vendor_roles ) );
}
$summary = array(
	'supplier_id' => (int) $supplier['id'],
	'vendor_id' => $vendor_id,
	'expected' => 138,
	'ready_to_publish' => count( $ready ),
	'already_public' => $already_public,
	'blocked' => count( $bad ),
	'shipping_type' => $shipping_type,
	'shipping_enabled' => $shipping_enabled,
	'destinations' => $destination_checks,
	'disabled_flag' => (string) get_user_meta( $vendor_id, '_disable_vendor', true ),
	'offline_flag' => (string) get_user_meta( $vendor_id, '_wcfm_store_offline', true ),
	'vendor_roles' => $vendor_roles,
	'bad_sample' => array_slice( $bad, 0, 15 ),
	'zone_debug' => $native_zone_debug,
	'mode' => $mode,
);
WP_CLI::log( 'LATITUDES_PREFLIGHT=' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
if ( $bad ) {
	WP_CLI::error( 'No se activa Latitudes: ' . count( $bad ) . ' comprobaciones pendientes. No se ha modificado ningún producto.' );
}
if ( 'dry-run' === $mode ) {
	WP_CLI::success( 'Latitudes validado, sin cambios. Productos a actualizar: ' . count( $ready ) );
	exit;
}

// Primero hacer visibles las fichas mientras el productor sigue deshabilitado.
// El cambio de vendedor a activo es el ÚLTIMO paso de la publicación.
$published = 0;
foreach ( $ready as $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product || (int) get_post_field( 'post_author', $product_id ) !== $vendor_id ) {
		WP_CLI::error( 'Producto inesperado durante publicación #' . $product_id . '; se mantiene bloqueado el vendedor.' );
	}
	$product->set_catalog_visibility( 'visible' );
	$product->set_status( 'publish' );
	$product->save();
	if ( 'publish' !== get_post_status( $product_id )
		|| 'visible' !== wc_get_product( $product_id )->get_catalog_visibility() ) {
		WP_CLI::error( 'Error al publicar #' . $product_id . '; vendedor todavía bloqueado.' );
	}
	++$published;
}

if ( in_array( 'disable_vendor', $vendor_roles, true ) ) {
	$user->remove_role( 'disable_vendor' );
	if ( ! in_array( 'wcfm_vendor', (array) $user->roles, true ) ) {
		$user->add_role( 'wcfm_vendor' );
	}
}
delete_user_meta( $vendor_id, '_disable_vendor' );
delete_user_meta( $vendor_id, '_wcfm_store_offline' );
clean_user_cache( $vendor_id );
if ( function_exists( 'wc_delete_product_transients' ) ) {
	wc_delete_product_transients();
}
if ( class_exists( 'WC_Cache_Helper' ) ) {
	WC_Cache_Helper::incr_cache_prefix( 'product' );
}
foreach ( array(
	WP_CONTENT_DIR . '/uploads/elmercado-hot-static-v1/es-shop-root.html',
	WP_CONTENT_DIR . '/cache/elmercado-home-static/index.html',
	WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html',
) as $file ) {
	if ( is_file( $file ) && ! @unlink( $file ) ) {
		WP_CLI::warning( 'No se pudo limpiar un archivo de caché: ' . $file );
	}
}
if ( function_exists( 'rocket_clean_domain' ) ) {
	rocket_clean_domain();
}
if ( function_exists( 'w3tc_flush_all' ) ) {
	w3tc_flush_all();
}
do_action( 'litespeed_purge_all' );
wp_cache_flush();

WP_CLI::success( 'LATITUDES_PUBLISHED=' . wp_json_encode( array(
	'published' => $published,
	'previously_public' => $already_public,
	'vendor_id' => $vendor_id,
	'vendor_active' => ! get_user_meta( $vendor_id, '_disable_vendor', true ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
