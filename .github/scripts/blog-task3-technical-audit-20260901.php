<?php
/**
 * EMDO SEO task 3 — read-only production audit.
 * Run with WP-CLI eval-file. This script MUST NOT change production data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

global $wpdb;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

function emdo_task3_option_keys( string $like ): array {
	global $wpdb;
	$rows = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name LIMIT 200", $like ) );
	return is_array( $rows ) ? array_values( $rows ) : array();
}

function emdo_task3_selected_option( string $name, array $wanted_keys ): array {
	$value = get_option( $name, null );
	if ( ! is_array( $value ) ) {
		return array( '_present' => null !== $value, '_type' => gettype( $value ) );
	}
	$out = array( '_present' => true );
	foreach ( $wanted_keys as $key ) {
		if ( array_key_exists( $key, $value ) ) {
			$out[ $key ] = $value[ $key ];
		}
	}
	return $out;
}

function emdo_task3_sample_term( string $taxonomy ): ?array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return null;
	}
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => 1, 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}
	$url = get_term_link( $terms[0] );
	return array(
		'slug'  => $terms[0]->slug,
		'count' => (int) $terms[0]->count,
		'url'   => is_wp_error( $url ) ? null : $url,
	);
}

$active_plugins = (array) get_option( 'active_plugins', array() );
$mu_plugins     = array_keys( get_mu_plugins() );

$post_counts = array();
foreach ( array( 'post', 'page', 'product', 'attachment' ) as $post_type ) {
	$obj = wp_count_posts( $post_type );
	$post_counts[ $post_type ] = $obj ? array(
		'publish' => isset( $obj->publish ) ? (int) $obj->publish : 0,
		'draft'   => isset( $obj->draft ) ? (int) $obj->draft : 0,
		'trash'   => isset( $obj->trash ) ? (int) $obj->trash : 0,
	) : null;
}

$taxonomy_counts = array();
foreach ( array( 'category', 'post_tag', 'product_cat', 'product_tag' ) as $taxonomy ) {
	if ( taxonomy_exists( $taxonomy ) ) {
		$taxonomy_counts[ $taxonomy ] = array(
			'all'      => (int) wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ),
			'nonempty' => (int) wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) ),
		);
	}
}

$published_authors = (int) $wpdb->get_var(
	"SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','page','product')"
);

$retired = array(
	'cuanta-vitamina-e-tiene-aove',
	'aove-omega-3-omega-6-perfil-grasas',
	'aceite-oliva-tiene-colesterol',
	'cuanta-grasa-saturada-tiene-aove',
	'cuanto-hierro-tiene-lomo-iberico',
	'grasa-lomo-iberico-saturada-monoinsaturada-poliinsaturada',
	'cuanto-hierro-tiene-chorizo-iberico',
	'grasa-chorizo-iberico-saturada-monoinsaturada-poliinsaturada',
	'verduras-mas-vitamina-c-comparativa',
	'aove-filtrado-vs-sin-filtrar-diferencias',
);
$retired_state = array();
foreach ( $retired as $slug ) {
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_type, post_status FROM {$wpdb->posts} WHERE post_name=%s ORDER BY ID DESC LIMIT 1", $slug ), ARRAY_A );
	$retired_state[ $slug ] = $row ?: null;
}

$sample_author = null;
$author_id = (int) $wpdb->get_var( "SELECT post_author FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post' GROUP BY post_author ORDER BY COUNT(*) DESC LIMIT 1" );
if ( $author_id > 0 ) {
	$user = get_userdata( $author_id );
	$sample_author = array(
		'id'   => $author_id,
		'slug' => $user ? $user->user_nicename : null,
		'url'  => get_author_posts_url( $author_id ),
	);
}

$aioseo_option = get_option( 'aioseo_options', null );
$aioseo_summary = array( '_present' => null !== $aioseo_option, '_type' => gettype( $aioseo_option ) );
if ( is_string( $aioseo_option ) ) {
	$aioseo_summary['_length'] = strlen( $aioseo_option );
	$decoded = json_decode( $aioseo_option, true );
	if ( is_array( $decoded ) ) {
		$aioseo_summary['_decoded_keys'] = array_keys( $decoded );
		if ( isset( $decoded['searchAppearance'] ) && is_array( $decoded['searchAppearance'] ) ) {
			$aioseo_summary['searchAppearance_keys'] = array_keys( $decoded['searchAppearance'] );
		}
	}
} elseif ( is_array( $aioseo_option ) ) {
	$aioseo_summary['_keys'] = array_keys( $aioseo_option );
}

$data = array(
	'marker'              => 'EMDO_TASK3_AUDIT_20260901',
	'home'                => home_url( '/' ),
	'siteurl'             => site_url( '/' ),
	'blog_public'         => (int) get_option( 'blog_public' ),
	'permalink_structure' => (string) get_option( 'permalink_structure' ),
	'show_on_front'       => (string) get_option( 'show_on_front' ),
	'page_on_front'       => (int) get_option( 'page_on_front' ),
	'page_for_posts'      => (int) get_option( 'page_for_posts' ),
	'active_plugins'      => $active_plugins,
	'mu_plugins'          => $mu_plugins,
	'theme'               => array(
		'stylesheet' => get_stylesheet(),
		'template'   => get_template(),
	),
	'post_counts'         => $post_counts,
	'taxonomy_counts'     => $taxonomy_counts,
	'published_authors'   => $published_authors,
	'sample_post_tag'     => emdo_task3_sample_term( 'post_tag' ),
	'sample_product_tag'  => emdo_task3_sample_term( 'product_tag' ),
	'sample_category'     => emdo_task3_sample_term( 'category' ),
	'sample_product_cat'  => emdo_task3_sample_term( 'product_cat' ),
	'sample_author'       => $sample_author,
	'sitemap_provider_options' => array(
		'aioseo_names'    => emdo_task3_option_keys( '%aioseo%' ),
		'wpseo_names'     => emdo_task3_option_keys( '%wpseo%' ),
		'rankmath_names'  => array_merge( emdo_task3_option_keys( '%rank_math%' ), emdo_task3_option_keys( '%rank-math%' ) ),
		'wpml_names'      => array_merge( emdo_task3_option_keys( '%icl_%' ), emdo_task3_option_keys( '%sitepress%' ) ),
		'polylang_names'  => emdo_task3_option_keys( '%polylang%' ),
	),
	'aioseo_summary' => $aioseo_summary,
	'yoast_titles_selected' => emdo_task3_selected_option(
		'wpseo_titles',
		array(
			'disable-author', 'disable-date', 'noindex-author-wpseo', 'noindex-archive-wpseo',
			'noindex-tax-post_tag', 'noindex-tax-product_tag', 'noindex-tax-category', 'noindex-tax-product_cat',
			'noindex-ptarchive-product', 'noindex-post', 'noindex-page', 'noindex-product',
		)
	),
	'wpml_selected' => emdo_task3_selected_option(
		'icl_sitepress_settings',
		array( 'default_language', 'admin_default_language', 'language_negotiation_type', 'urls', 'hidden_languages' )
	),
	'polylang_selected' => emdo_task3_selected_option(
		'polylang',
		array( 'default_lang', 'force_lang', 'rewrite', 'hide_default', 'redirect_lang', 'media_support' )
	),
	'retired_state'       => $retired_state,
);

echo "EMDO_TASK3_DIAG_JSON=" . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
