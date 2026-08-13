<?php
/**
 * Genera expectativas de Home para visitante y administrador en staging.
 */

if ( ! function_exists( 'elmercado_catalog_visible_category_count_010217' ) || ! function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) ) {
	fwrite( STDERR, "Missing catalog/WCFM visibility helpers.\n" );
	exit( 10 );
}

$exclude = array_filter( array( (int) get_option( 'default_product_cat' ) ) );
$terms   = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'number'     => 6,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'exclude'    => $exclude,
	)
);

if ( is_wp_error( $terms ) ) {
	fwrite( STDERR, "Category query failed.\n" );
	exit( 11 );
}

$rows = array();
wp_set_current_user( 0 );
foreach ( (array) $terms as $term ) {
	if ( ! $term instanceof WP_Term ) {
		continue;
	}
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		continue;
	}
	$count = (int) elmercado_catalog_visible_category_count_010217( (int) $term->term_id );
	$rows[ $term->term_id ] = array(
		'id'             => (int) $term->term_id,
		'name'           => $term->name,
		'url'            => $link,
		'public'         => $count,
		'public_archive' => $count,
	);
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( ! $admins ) {
	fwrite( STDERR, "Administrator user not found.\n" );
	exit( 13 );
}

$admin = $admins[0];
wp_set_current_user( (int) $admin->ID );
if ( ! elmercado_wcfm_disabled_visibility_can_view_010210() ) {
	fwrite( STDERR, "Administrator visibility capability did not activate.\n" );
	exit( 14 );
}

foreach ( (array) $terms as $term ) {
	if ( ! $term instanceof WP_Term || ! isset( $rows[ $term->term_id ] ) ) {
		continue;
	}
	$count = (int) elmercado_catalog_visible_category_count_010217( (int) $term->term_id );
	$rows[ $term->term_id ]['admin']         = $count;
	$rows[ $term->term_id ]['admin_archive'] = $count;
}

$cookie = wp_generate_auth_cookie( (int) $admin->ID, time() + 900, 'logged_in' );

echo '__ROWS__=' . base64_encode( wp_json_encode( array_values( $rows ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . "\n";
echo '__COOKIE_NAME__=' . base64_encode( LOGGED_IN_COOKIE ) . "\n";
echo '__COOKIE_VALUE__=' . base64_encode( $cookie ) . "\n";