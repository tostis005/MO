<?php
/**
 * Fixture desechable para probar la regla admin/público en staging.
 * Nunca debe ejecutarse en producción.
 */

defined( 'ABSPATH' ) || exit;

$action = getenv( 'EMO_FIXTURE_ACTION' ) ?: 'setup';
$login  = 'emo_catalog_fixture_vendor_010224';
$marker = 'emo_catalog_fixture_010224';

$cleanup = static function () use ( $login, $marker ): void {
	$fixture_products = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_emo_catalog_fixture',
			'meta_value'     => $marker,
		)
	);
	foreach ( array_map( 'absint', (array) $fixture_products ) as $product_id ) {
		if ( $product_id > 0 ) {
			wp_delete_post( $product_id, true );
		}
	}

	$user = get_user_by( 'login', $login );
	if ( $user instanceof WP_User ) {
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		wp_delete_user( (int) $user->ID );
	}
};

if ( 'cleanup' === $action ) {
	$cleanup();
	echo 'CATALOG_DISABLED_VENDOR_FIXTURE_010224_CLEANUP_OK' . PHP_EOL;
	return;
}

if ( false === strpos( home_url( '/' ), 'dev.elmercadodeorigen.com' ) ) {
	fwrite( STDERR, 'Fixture refused outside development.' . PHP_EOL );
	exit( 3 );
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

$password = wp_generate_password( 32, true, true );
$user_id  = wp_create_user( $login, $password, $login . '@example.invalid' );
if ( is_wp_error( $user_id ) ) {
	fwrite( STDERR, $user_id->get_error_message() . PHP_EOL );
	exit( 6 );
}
$user = get_userdata( (int) $user_id );
if ( $user instanceof WP_User ) {
	$user->set_role( 'wcfm_vendor' );
}
update_user_meta( (int) $user_id, '_disable_vendor', 1 );
update_user_meta( (int) $user_id, 'store_name', 'Fixture vendedor desactivado 010224' );
update_user_meta( (int) $user_id, 'wcfmmp_profile_settings', array( 'store_name' => 'Fixture vendedor desactivado 010224' ) );

$make_product = static function ( string $name, string $sku, bool $in_stock ) use ( $category, $user_id, $marker ): int {
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
			'post_author' => (int) $user_id,
		)
	);
	update_post_meta( $product_id, '_emo_catalog_fixture', $marker );
	clean_post_cache( $product_id );
	return $product_id;
};

$instock_id    = $make_product( 'Fixture admin visible in stock 010224', 'EMO-FIXTURE-INSTOCK-010224', true );
$outofstock_id = $make_product( 'Fixture admin hidden out of stock 010224', 'EMO-FIXTURE-OUTOFSTOCK-010224', false );

if ( $instock_id <= 0 || $outofstock_id <= 0 ) {
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
			'vendor_id'      => (int) $user_id,
			'instock_id'     => $instock_id,
			'outofstock_id'  => $outofstock_id,
			'category_id'    => (int) $category->term_id,
			'category_slug'  => (string) $category->slug,
		)
	)
) . PHP_EOL;
echo 'CATALOG_DISABLED_VENDOR_FIXTURE_010224_SETUP_OK' . PHP_EOL;
