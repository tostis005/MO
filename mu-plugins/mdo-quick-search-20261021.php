<?php
/**
 * Búsqueda incremental del diálogo Woostify.
 * Se carga como MU-plugin para no modificar el formulario ni el tema padre.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'elmercado/v1',
			'/quick-search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => 'elmercado_quick_search_20261021',
				'args'                => array(
					'q'    => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
					'lang' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}
);

/**
 * Devuelve el precio comercial legible, sin texto oculto de WooCommerce.
 * Un producto variable se muestra solo como "mínimo – máximo".
 *
 * @param WC_Product $product Producto WooCommerce.
 * @return string
 */
function elmercado_quick_search_price_20261021( $product ): string {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$format = static function ( $amount ): string {
		return html_entity_decode(
			wp_strip_all_tags( wc_price( (float) $amount ) ),
			ENT_QUOTES | ENT_HTML5,
			'UTF-8'
		);
	};

	if ( $product->is_type( 'variable' ) ) {
		$min = $product->get_variation_price( 'min', true );
		$max = $product->get_variation_price( 'max', true );

		if ( '' === $min || '' === $max ) {
			return '';
		}

		$low = $format( $min );
		return (float) $min === (float) $max ? $low : $low . ' – ' . $format( $max );
	}

	// En productos simples mostramos el precio efectivo (incluidas rebajas).
	$price = $product->get_price();
	if ( '' !== $price ) {
		return $format( wc_get_price_to_display( $product, array( 'price' => $price ) ) );
	}

	// Productos agrupados / packs sin precio simple: conservar el HTML visible,
	// eliminando el texto screen-reader que duplica el rango en WooCommerce.
	$html = (string) $product->get_price_html();
	$html = preg_replace( '~<span\\b[^>]*class=["\\\'][^"\\\']*screen-reader-text[^"\\\']*["\\\'][^>]*>.*?</span>~is', '', $html );
	return trim( html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

/**
 * Orden de relevancia del autocomplete: título exacto, comienzo del título,
 * coincidencia en título y, por último, descripción corta (post_excerpt).
 *
 * La consulta normal con Enter conserva su relevancia original.
 *
 * @param string   $orderby Cláusula original de WordPress.
 * @param WP_Query $query Consulta actual.
 * @return string
 */
function elmercado_quick_search_orderby_20261021( string $orderby, WP_Query $query ): string {
	if ( ! $query->get( 'emo_quick_search_20261021' ) ) {
		return $orderby;
	}

	$term = trim( (string) $query->get( 's' ) );
	if ( '' === $term ) {
		return $orderby;
	}

	global $wpdb;
	$like = $wpdb->esc_like( $term );

	// Solo se aplica a la consulta acotada del autocomplete, nunca a búsquedas generales.
	return $wpdb->prepare(
		"CASE
			WHEN {$wpdb->posts}.post_title = %s THEN 0
			WHEN {$wpdb->posts}.post_title LIKE %s THEN 1
			WHEN {$wpdb->posts}.post_title LIKE %s THEN 2
			WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 3
			ELSE 4
		END ASC, {$wpdb->posts}.post_title ASC",
		$term,
		$like . '%',
		'%' . $like . '%',
		'%' . $like . '%'
	);
}
add_filter( 'posts_orderby', 'elmercado_quick_search_orderby_20261021', 1000, 2 );

/**
 * Limita la búsqueda incremental al nombre y descripción corta de WooCommerce.
 * La descripción larga (post_content) nunca debe originar una sugerencia.
 *
 * @param string                $term Búsqueda.
 * @param array<string, mixed>  $tax_query Restricciones de visibilidad.
 * @return array<string, mixed>
 */
function elmercado_quick_search_query_args_20261021( string $term, array $tax_query ): array {
	return array(
		'post_type'                  => 'product',
		'post_status'                => 'publish',
		's'                          => $term,
		'search_columns'             => array( 'post_title', 'post_excerpt' ),
		'emo_quick_search_20261021'  => true,
		'posts_per_page'             => 12,
		'no_found_rows'              => true,
		'ignore_sticky_posts'        => true,
		'suppress_filters'           => false,
		'fields'                     => 'ids',
		'orderby'                    => 'relevance',
		'tax_query'                  => $tax_query,
	);
}

/**
 * Responde exclusivamente con productos visibles, publicados y con existencias.
 * Cachea únicamente IDs para no congelar precios ni disponibilidad.
 *
 * @param WP_REST_Request $request Petición de consulta.
 * @return WP_REST_Response
 */
function elmercado_quick_search_20261021( WP_REST_Request $request ): WP_REST_Response {
	$empty = array( 'products' => array() );

	if ( ! function_exists( 'wc_get_product' ) ) {
		return rest_ensure_response( $empty );
	}

	$term = (string) $request->get_param( 'q' );
	$term = trim( (string) preg_replace( '/\s+/u', ' ', $term ) );

	// No ejecutar consultas con una sola letra ni con cadenas largas de bots.
	if ( ! preg_match( '/^.{2,50}$/us', $term ) ) {
		return rest_ensure_response( $empty );
	}

	$lang = (string) $request->get_param( 'lang' );
	$lang = in_array( $lang, array( 'es', 'en' ), true ) ? $lang : 'es';
	$normalized = function_exists( 'mb_strtolower' ) ? mb_strtolower( $term, 'UTF-8' ) : strtolower( $term );
	$role = current_user_can( 'manage_options' ) ? 'admin' : 'visitor';
	$cache_key = 'emo_quick_search_v2_' . md5( $lang . ':' . $role . ':' . $normalized );

	$ids = get_transient( $cache_key );
	if ( ! is_array( $ids ) ) {
		$tax_query = array();
		if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			$visibility = wc_get_product_visibility_term_ids();
			$excluded = array_filter(
				array_map(
					'absint',
					array(
						$visibility['exclude-from-search'] ?? 0,
						$visibility['exclude-from-catalog'] ?? 0,
						$visibility['outofstock'] ?? 0,
					)
				)
			);
			if ( $excluded ) {
				$tax_query[] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_id',
					'terms'    => array_values( $excluded ),
					'operator' => 'NOT IN',
				);
			}
		}

		// WP_Query sigue aplicando los filtros de visibilidad del marketplace.
		$query = new WP_Query( elmercado_quick_search_query_args_20261021( $term, $tax_query ) );

		$ids = array_values( array_filter( array_map( 'absint', (array) $query->posts ) ) );
		set_transient( $cache_key, $ids, MINUTE_IN_SECONDS );
	}

	$products = array();
	foreach ( $ids as $id ) {
		if ( count( $products ) >= 6 ) {
			break;
		}

		$post = get_post( (int) $id );
		if ( ! $post instanceof WP_Post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			continue;
		}

		// Defensa adicional incluso si otro plugin altera el filtrado de WP_Query.
		if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' )
			&& elmercado_wcfm_vendor_is_disabled_010210( (int) $post->post_author ) ) {
			continue;
		}

		$product = wc_get_product( (int) $id );
		if ( ! $product || ! $product->is_visible() || ! $product->is_in_stock() ) {
			continue;
		}

		// La visibilidad de WooCommerce puede variar tras crear el transient.
		if ( ! has_term( array( 'exclude-from-search', 'exclude-from-catalog', 'outofstock' ), 'product_visibility', (int) $id ) ) {
			$categories = get_the_terms( (int) $id, 'product_cat' );
			$category = ! is_wp_error( $categories ) && ! empty( $categories ) ? (string) reset( $categories )->name : '';

			$products[] = array(
				'id'       => (int) $id,
				'name'     => wp_strip_all_tags( $product->get_name() ),
				'url'      => esc_url_raw( get_permalink( (int) $id ) ),
				'image'    => (string) ( get_the_post_thumbnail_url( (int) $id, 'thumbnail' ) ?: '' ),
				'category' => $category,
				'price'    => elmercado_quick_search_price_20261021( $product ),
			);
		}
	}

	$response = rest_ensure_response( array( 'products' => $products ) );
	$response->header( 'X-Robots-Tag', 'noindex, nofollow' );
	return $response;
}

/**
 * Assets ligeros en todo el escaparate: el diálogo puede abrirse desde cualquier página.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || ! function_exists( 'WC' ) || 'elmercadodeorigen-child' !== get_stylesheet() ) {
			return;
		}

		$base = get_stylesheet_directory_uri();
		wp_enqueue_style(
			'elmercado-quick-search-20261021',
			$base . '/assets/css/quick-search-20261021.css',
			array(),
			'20261027.2'
		);
		wp_enqueue_script(
			'elmercado-quick-search-20261021',
			$base . '/assets/js/quick-search-20261021.js',
			array(),
			'20261027.1',
			true
		);
		wp_add_inline_script(
			'elmercado-quick-search-20261021',
			'window.elmercadoQuickSearch20261021 = ' . wp_json_encode(
				array( 'endpoint' => esc_url_raw( rest_url( 'elmercado/v1/quick-search' ) ) )
			) . ';',
			'before'
		);
	},
	1000
);
