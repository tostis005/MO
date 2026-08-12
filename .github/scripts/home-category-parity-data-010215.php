<?php
/**
 * Se ejecuta con `wp eval-file` dentro de WordPress.
 */

if ( ! function_exists( 'elmercado_home_public_category_count_010212' ) || ! function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) ) {
	fwrite( STDERR, "Missing Home/WCFM visibility helpers.\n" );
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

$base_for = static function ( int $term_id ): array {
	return array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'fields'              => 'ids',
		'posts_per_page'      => 1,
		'no_found_rows'       => false,
		'ignore_sticky_posts' => true,
		'suppress_filters'    => false,
		'tax_query'           => array(
			array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array( $term_id ),
				'include_children' => true,
			),
		),
	);
};

$rows = array();
wp_set_current_user( 0 );
foreach ( (array) $terms as $term ) {
	if ( ! $term instanceof WP_Term ) {
		continue;
	}
	$query   = new WP_Query( $base_for( (int) $term->term_id ) );
	$archive = (int) $query->found_posts;
	$home    = (int) elmercado_home_public_category_count_010212( (int) $term->term_id );
	if ( $home !== $archive ) {
		fwrite( STDERR, "Public parity failed for {$term->name}: home={$home}, archive={$archive}.\n" );
		exit( 12 );
	}
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		continue;
	}
	$rows[ $term->term_id ] = array(
		'id'             => (int) $term->term_id,
		'name'           => $term->name,
		'url'            => $link,
		'public'         => $home,
		'public_archive' => $archive,
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
	$query   = new WP_Query( $base_for( (int) $term->term_id ) );
	$archive = (int) $query->found_posts;
	$home    = (int) elmercado_home_public_category_count_010212( (int) $term->term_id );
	if ( $home !== $archive ) {
		fwrite( STDERR, "Admin parity failed for {$term->name}: home={$home}, archive={$archive}.\n" );
		exit( 15 );
	}
	$rows[ $term->term_id ]['admin']         = $home;
	$rows[ $term->term_id ]['admin_archive'] = $archive;
}

$cookie = wp_generate_auth_cookie( (int) $admin->ID, time() + 900, 'logged_in' );

echo '__ROWS__=' . base64_encode( wp_json_encode( array_values( $rows ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . "\n";
echo '__COOKIE_NAME__=' . base64_encode( LOGGED_IN_COOKIE ) . "\n";
echo '__COOKIE_VALUE__=' . base64_encode( $cookie ) . "\n";
