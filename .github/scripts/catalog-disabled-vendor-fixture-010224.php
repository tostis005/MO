<?php
/**
 * Fixture desechable para probar la regla admin/público en staging.
 * Nunca debe ejecutarse en producción.
 */

defined( 'ABSPATH' ) || exit;

$action          = getenv( 'EMO_FIXTURE_ACTION' ) ?: 'setup';
$marker          = 'emo_catalog_fixture_010224';
$active_login    = 'emo_catalog_fixture_active_010224';
$disabled_login  = 'emo_catalog_fixture_disabled_010224';

/**
 * Limpieza por SQL directo para que ningún filtro de catálogo pueda ocultar los
 * propios fixtures durante el teardown.
 */
$cleanup = static function () use ( $marker, $active_login, $disabled_login ): void {
	global $wpdb;

	$product_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'product'",
			'_emo_catalog_fixture',
			$marker
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	foreach ( array_map( 'absint', (array) $product_ids ) as $product_id ) {
		if ( $product_id > 0 ) {
			wp_delete_post( $product_id, true );
		}
	}

	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}
	foreach ( array( $active_login, $disabled_login, 'emo_catalog_fixture_vendor_010224' ) as $login ) {
		$user = get_user_by( 'login', $login );
		if ( $user instanceof WP_User ) {
			wp_delete_user( (int) $user->ID );
		}
	}

	$remaining = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'product'",
			'_emo_catalog_fixture',
			$marker
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( $remaining ) {
		throw new RuntimeException( 'Fixture cleanup left product IDs: ' . implode( ',', array_map( 'absint', $remaining ) ) );
	}
};

if ( false === strpos( home_url( '/' ), 'dev.elmercadodeorigen.com' ) ) {
	fwrite( STDERR, 'Fixture refused outside development.' . PHP_EOL );
	exit( 3 );
}

if ( 'cleanup' === $action ) {
	try {
		$cleanup();
		echo 'CATALOG_DISABLED_VENDOR_FIXTURE_010224_CLEANUP_OK' . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, 'Cleanup failed: ' . $error->getMessage() . PHP_EOL );
		exit( 8 );
	}
	return;
}

$cleanup();

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	fwrite( STDERR, 'WooCommerce product class unavailable.' . PHP_EOL );
	exit( 4 );
}

$category = get_term_by( 'slug', 'jamones-paletas', 'product_cat' );
if ( ! $category instanceof WP_Term ) {
	fwrite( STDERR, 'Jamones y paletas category missing.' . PHP_EOL );
	exit( 5 );
}

$make_vendor = static function ( string $login, string $name, bool $disabled ) {
	$password = wp_generate_password( 32, true, true );
	$user_id  = wp_create_user( $login, $password, $login . '@example.invalid' );
	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}
	$user = get_userdata( (int) $user_id );
	if ( $user instanceof WP_User ) {
		$user->set_role( 'wcfm_vendor' );
	}
	update_user_meta( (int) $user_id, 'store_name', $name );
	update_user_meta( (int) $user_id, 'wcfmmp_profile_settings', array( 'store_name' => $name ) );
	if ( $disabled ) {
		update_user_meta( (int) $user_id, '_disable_vendor', 1 );
	} else {
		delete_user_meta( (int) $user_id, '_disable_vendor' );
	}
	return (int) $user_id;
};

$active_vendor_id   = $make_vendor( $active_login, 'Fixture vendedor activo 010224', false );
$disabled_vendor_id = $make_vendor( $disabled_login, 'Fixture vendedor desactivado 010224', true );
if ( is_wp_error( $active_vendor_id ) || is_wp_error( $disabled_vendor_id ) || $active_vendor_id <= 0 || $disabled_vendor_id <= 0 ) {
	$cleanup();
	fwrite( STDERR, 'Fixture vendors could not be created.' . PHP_EOL );
	exit( 6 );
}

$make_product = static function ( string $name, string $sku, int $author_id, bool $in_stock ) use ( $category, $marker ): int {
	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( $in_stock ? '123.45' : '234.56' );
	$product->set_sku( $sku );
	$product->set_category_ids( array( (int) $category->term_id ) );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( $in_stock ? 5 : 0 );
	$product->set_stock_status( $in_stock ? 'instock' : 'outofstock' );
	$product_id = (int) $product->save();
	if ( $product_id <= 0 ) {
		return 0;
	}
	wp_update_post(
		array(
			'ID'          => $product_id,
			'post_author' => $author_id,
			'post_status' => 'publish',
		)
	);
	update_post_meta( $product_id, '_emo_catalog_fixture', $marker );
	clean_post_cache( $product_id );
	return $product_id;
};

$active_instock_id     = $make_product( 'Fixture público visible en stock 010224', 'EMO-FIXTURE-ACTIVE-INSTOCK-010224', $active_vendor_id, true );
$disabled_instock_id   = $make_product( 'Fixture solo admin vendedor desactivado 010224', 'EMO-FIXTURE-DISABLED-INSTOCK-010224', $disabled_vendor_id, true );
$disabled_outstock_id  = $make_product( 'Fixture agotado vendedor desactivado 010224', 'EMO-FIXTURE-DISABLED-OUTOFSTOCK-010224', $disabled_vendor_id, false );

if ( $active_instock_id <= 0 || $disabled_instock_id <= 0 || $disabled_outstock_id <= 0 ) {
	$cleanup();
	fwrite( STDERR, 'Fixture products could not be created.' . PHP_EOL );
	exit( 7 );
}

if ( function_exists( 'wc_delete_product_transients' ) ) {
	wc_delete_product_transients();
}
clean_term_cache( array( (int) $category->term_id ), 'product_cat' );

echo '__FIXTURE__=' . base64_encode(
	wp_json_encode(
		array(
			'active_vendor_id'       => $active_vendor_id,
			'disabled_vendor_id'     => $disabled_vendor_id,
			'active_instock_id'      => $active_instock_id,
			'disabled_instock_id'    => $disabled_instock_id,
			'disabled_outofstock_id' => $disabled_outstock_id,
			'category_id'            => (int) $category->term_id,
			'category_slug'          => (string) $category->slug,
		)
	)
) . PHP_EOL;
echo 'CATALOG_DISABLED_VENDOR_FIXTURE_010224_SETUP_OK' . PHP_EOL;
