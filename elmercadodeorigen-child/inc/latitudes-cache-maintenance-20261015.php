<?php
/**
 * Mantenimiento de caché para la puesta en marcha de Latitudes.
 *
 * Por defecto invalida únicamente transitorios del catálogo y caché de
 * productos. Las cachés completas requieren un segundo botón explícito,
 * nonce y privilegios de administración de WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Solo acción POST autenticada; nunca borra datos de los productos. */
add_action(
	'admin_post_elmercado_latitudes_clear_cache_20261015',
	static function (): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para borrar la caché.', 'elmercadodeorigen' ) );
		}
		check_admin_referer( 'elmercado_latitudes_clear_cache_20261015' );

		$deep = isset( $_POST['deep'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['deep'] ) );
		$count = 0;

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
			++$count;
		}
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::incr_cache_prefix( 'product' );
			++$count;
		}
		$vendor_id = function_exists( 'elmercado_latitudes_vendor_id_20261015' )
			? elmercado_latitudes_vendor_id_20261015() : 0;
		if ( $vendor_id > 0 ) {
			$ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'author'         => $vendor_id,
					'posts_per_page' => 500,
					'fields'         => 'ids',
				)
			);
			foreach ( $ids as $id ) {
				clean_post_cache( (int) $id );
				++$count;
			}
			clean_user_cache( $vendor_id );
		}

		/* El vaciado total solo ocurre tras pulsar el botón correspondiente. */
		if ( $deep ) {
			if ( function_exists( 'rocket_clean_domain' ) ) {
				rocket_clean_domain();
			}
			if ( function_exists( 'w3tc_flush_all' ) ) {
				w3tc_flush_all();
			}
			if ( function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
			}
			/* LiteSpeed Cache escucha esta acción en instalaciones compatibles. */
			do_action( 'litespeed_purge_all' );
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
		}
		update_option(
			'elmercado_latitudes_cache_last_purge_20261015',
			array(
				'time'  => current_time( 'mysql' ),
				'mode'  => $deep ? 'global' : 'catalogo',
				'count' => $count,
			),
			false
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                   => 'elmercado-latitudes-audit',
					'latitudes_cache_cleared' => $deep ? 'all' : 'catalog',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}
);

/** Campos del panel; usar únicamente dentro del wp-admin. */
function elmercado_latitudes_cache_actions_20261015(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$last = get_option( 'elmercado_latitudes_cache_last_purge_20261015', array() );
	?>
	<h2>Caché y diagnóstico de publicación</h2>
	<p>Vacía primero el catálogo, recarga la tienda en incógnito y vuelve a revisar el informe. Si los productos siguen ausentes, comprueba su autor, estado, stock, destino y permisos: ninguna caché corrige esos problemas.</p>
	<?php if ( ! empty( $last['time'] ) ) : ?>
		<p><strong>Última invalidación:</strong> <?php echo esc_html( $last['time'] . ' (' . ( $last['mode'] ?? '' ) . ')' ); ?></p>
	<?php endif; ?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="elmercado_latitudes_clear_cache_20261015">
		<?php wp_nonce_field( 'elmercado_latitudes_clear_cache_20261015' ); ?>
		<p><button class="button button-primary" type="submit">Invalidar caché de productos</button></p>
	</form>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="elmercado_latitudes_clear_cache_20261015">
		<input type="hidden" name="deep" value="yes">
		<?php wp_nonce_field( 'elmercado_latitudes_clear_cache_20261015' ); ?>
		<p><button class="button" type="submit">Vaciar cachés de página y objetos (global)</button></p>
	</form>
	<p><small>El borrado global puede aumentar temporalmente el tiempo de carga. No puede purgar cachés de CDN externos si no existe integración en WordPress. Ninguna acción elimina productos ni pedidos.</small></p>
	<?php
}
