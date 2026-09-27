<?php
/**
 * Mantiene el clúster SEO de pato como entradas reales del blog sin exponerlo
 * en listados, filtros, archivos, feeds o navegación desde artículos visibles.
 *
 * Las URLs individuales siguen siendo públicas e indexables. Dentro de una
 * entrada oculta, las consultas de posts y la navegación adyacente permanecen
 * dentro del mismo clúster para reforzar el enlazado interno.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EMDO_HIDDEN_BLOG_ISLAND_META_010312 = '_emdo_blog_hidden_listing';

function emdo_hidden_blog_island_is_sitemap_010312(): bool {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( (string) $_SERVER['REQUEST_URI'] ) : '';
	return false !== strpos( $uri, 'sitemap' ) || false !== strpos( $uri, 'wp-sitemap' );
}

function emdo_hidden_blog_island_is_hidden_010312( int $post_id ): bool {
	return '1' === (string) get_post_meta( $post_id, EMDO_HIDDEN_BLOG_ISLAND_META_010312, true );
}

/**
 * Añade una condición meta EXISTS / NOT EXISTS a una meta_query preexistente.
 *
 * @param mixed $existing Meta query actual.
 */
function emdo_hidden_blog_island_merge_meta_query_010312( $existing, bool $only_hidden ): array {
	$visibility = array(
		'key'     => EMDO_HIDDEN_BLOG_ISLAND_META_010312,
		'compare' => $only_hidden ? 'EXISTS' : 'NOT EXISTS',
	);

	if ( ! is_array( $existing ) || empty( $existing ) ) {
		return array( $visibility );
	}

	return array(
		'relation' => 'AND',
		$existing,
		$visibility,
	);
}

/**
 * Excluye las islas SEO de cualquier consulta pública de entradas. Si estamos
 * leyendo una isla, las consultas secundarias se limitan a otras islas.
 *
 * Se omiten sitemaps para que las entradas sigan siendo descubribles por los
 * buscadores y conserven su condición indexable.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || wp_doing_cron() || emdo_hidden_blog_island_is_sitemap_010312() ) {
			return;
		}

		/* La consulta principal de una entrada debe poder resolver su URL. */
		if ( $query->is_main_query() && $query->is_singular( 'post' ) ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$is_post_query = false;

		if ( empty( $post_type ) ) {
			$is_post_query = $query->is_home() || $query->is_archive() || $query->is_search() || $query->is_feed();
		} elseif ( is_array( $post_type ) ) {
			$is_post_query = in_array( 'post', $post_type, true ) || in_array( 'any', $post_type, true );
		} else {
			$is_post_query = in_array( (string) $post_type, array( 'post', 'any' ), true );
		}

		if ( ! $is_post_query ) {
			return;
		}

		$current_id = (int) get_queried_object_id();
		$only_hidden = is_singular( 'post' ) && $current_id > 0 && emdo_hidden_blog_island_is_hidden_010312( $current_id );

		$query->set(
			'meta_query',
			emdo_hidden_blog_island_merge_meta_query_010312( $query->get( 'meta_query' ), $only_hidden )
		);
	},
	18
);

/**
 * Mantiene Anterior/Siguiente dentro de la misma visibilidad.
 *
 * @param string  $where WHERE generado por WordPress.
 * @param bool    $in_same_term No usado.
 * @param int[]   $excluded_terms No usado.
 * @param string  $taxonomy No usado.
 * @param WP_Post $post Entrada actual.
 */
function emdo_hidden_blog_island_adjacent_where_010312( string $where, $in_same_term, $excluded_terms, $taxonomy, $post ): string {
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return $where;
	}

	global $wpdb;
	$hidden = emdo_hidden_blog_island_is_hidden_010312( (int) $post->ID );

	if ( $hidden ) {
		$where .= $wpdb->prepare(
			" AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} emdo_island_pm WHERE emdo_island_pm.post_id = p.ID AND emdo_island_pm.meta_key = %s AND emdo_island_pm.meta_value = '1')",
			EMDO_HIDDEN_BLOG_ISLAND_META_010312
		);
	} else {
		$where .= $wpdb->prepare(
			" AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} emdo_island_pm WHERE emdo_island_pm.post_id = p.ID AND emdo_island_pm.meta_key = %s)",
			EMDO_HIDDEN_BLOG_ISLAND_META_010312
		);
	}

	return $where;
}
add_filter( 'get_previous_post_where', 'emdo_hidden_blog_island_adjacent_where_010312', 20, 5 );
add_filter( 'get_next_post_where', 'emdo_hidden_blog_island_adjacent_where_010312', 20, 5 );
