<?php
/**
 * Correcciones finales 0.10.239 solicitadas tras la revisión en producción.
 *
 * - El filtro global por vendedor debe restringir el SQL real también para
 *   visitantes anónimos, aunque otra capa reconstruya la consulta después.
 * - Las etiquetas de atributos de productos variables no llevan recuadro.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vendedor seleccionado y válido en el catálogo global.
 */
function elmercado_catalog_selected_vendor_010239(): int {
	if ( ! isset( $_GET['vendor_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 0;
	}

	$vendor_id = absint( wp_unslash( $_GET['vendor_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $vendor_id <= 0 || ! function_exists( 'elmercado_core_filter_is_vendor' ) || ! elmercado_core_filter_is_vendor( $vendor_id ) ) {
		return 0;
	}

	return $vendor_id;
}

/**
 * Solo actúa sobre el loop principal del catálogo global, nunca sobre la tienda
 * individual de un productor ni sobre consultas auxiliares.
 */
function elmercado_catalog_vendor_filter_target_010239( WP_Query $query ): bool {
	if ( is_admin() || ! $query->is_main_query() ) {
		return false;
	}

	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return false;
	}

	if ( function_exists( 'elmercado_catalog_is_main_query_010224' ) ) {
		return elmercado_catalog_is_main_query_010224( $query );
	}

	if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) {
		return true;
	}

	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
}

/**
 * Reaplica la restricción después de las capas históricas que puedan modificar
 * author durante pre_get_posts.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		$vendor_id = elmercado_catalog_selected_vendor_010239();
		if ( $vendor_id <= 0 || ! elmercado_catalog_vendor_filter_target_010239( $query ) ) {
			return;
		}

		$query->set( 'author', $vendor_id );
		$query->set( 'author__in', array( $vendor_id ) );
		$query->set( 'emo_selected_vendor_010239', $vendor_id );
	},
	PHP_INT_MAX
);

/**
 * Última defensa antes de ejecutar el SELECT. De esta forma una reconstrucción
 * tardía de WooCommerce/WCFM no puede devolver productos de otro vendedor.
 *
 * @param array<string,string> $clauses Partes SQL de WP_Query.
 * @return array<string,string>
 */
add_filter(
	'posts_clauses',
	static function ( array $clauses, WP_Query $query ): array {
		$vendor_id = elmercado_catalog_selected_vendor_010239();
		if ( $vendor_id <= 0 || ! elmercado_catalog_vendor_filter_target_010239( $query ) ) {
			return $clauses;
		}

		global $wpdb;
		$clauses['where'] = (string) ( $clauses['where'] ?? '' ) . $wpdb->prepare(
			" AND {$wpdb->posts}.post_author = %d",
			$vendor_id
		);
		return $clauses;
	},
	PHP_INT_MAX,
	2
);

/**
 * Marcador de release y corrección visual del atributo de variación.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-user-reported-corrections-010239">
			body.single-product form.variations_form table.variations th.label {
				border:0 !important;
				border-width:0 !important;
				border-style:none !important;
				outline:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
