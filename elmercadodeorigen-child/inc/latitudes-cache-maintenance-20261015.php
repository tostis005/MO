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

/** Páginas de catálogo que pueden mantener un HTML antiguo de Latitudes. */
function elmercado_latitudes_cache_urls_20261015(): array {
	$urls = array(
		home_url( '/' ),
		home_url( '/productores/' ),
	);
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			$urls[] = get_permalink( $shop_id );
		}
	}
	if ( function_exists( 'elmercado_latitudes_store_url_20261015' ) ) {
		$url = elmercado_latitudes_store_url_20261015();
		if ( $url ) {
			$urls[] = $url;
		}
	}
	return array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
}

/** Purga URLs desde el plugin de caché instalado, si expone la función. */
function elmercado_latitudes_purge_page_cache_20261015( array $urls ): array {
	$purged = array();
	if ( function_exists( 'rocket_clean_files' ) ) {
		rocket_clean_files( $urls );
		$purged[] = 'WP Rocket: ' . count( $urls ) . ' URLs';
	}
	if ( has_action( 'litespeed_purge_url' ) ) {
		foreach ( $urls as $url ) {
			do_action( 'litespeed_purge_url', $url );
		}
		$purged[] = 'LiteSpeed: ' . count( $urls ) . ' URLs';
	}
	if ( function_exists( 'w3tc_flush_url' ) ) {
		foreach ( $urls as $url ) {
			w3tc_flush_url( $url );
		}
		$purged[] = 'W3 Total Cache: ' . count( $urls ) . ' URLs';
	}
	return $purged;
}

/** Invalida la portada estática personalizada creada por el tema hijo. */
function elmercado_latitudes_flush_home_file_cache_20261015(): void {
	if ( function_exists( 'elmercado_flush_home_cache' ) ) {
		elmercado_flush_home_cache();
	}
}

/** Agrupa cambios masivos del importador y purga las URLs una sola vez. */
add_action(
	'save_post_product',
	static function ( $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$vendor_id = function_exists( 'elmercado_latitudes_vendor_id_20261015' )
			? elmercado_latitudes_vendor_id_20261015() : 0;
		if ( $vendor_id <= 0 || (int) get_post_field( 'post_author', $post_id ) !== $vendor_id ) {
			return;
		}
		if ( ! wp_next_scheduled( 'elmercado_latitudes_delayed_purge_20261015' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'elmercado_latitudes_delayed_purge_20261015' );
		}
	},
	100
);
add_action(
	'elmercado_latitudes_delayed_purge_20261015',
	static function (): void {
		elmercado_latitudes_flush_home_file_cache_20261015();
		elmercado_latitudes_purge_page_cache_20261015( elmercado_latitudes_cache_urls_20261015() );
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
	}
);

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
		elmercado_latitudes_flush_home_file_cache_20261015();
		$page_purged = elmercado_latitudes_purge_page_cache_20261015( elmercado_latitudes_cache_urls_20261015() );
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
				'page_purged' => $page_purged,
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
		<p><?php echo esc_html( ! empty( $last['page_purged'] ) ? implode( ' · ', (array) $last['page_purged'] ) : 'Sin motor de caché de páginas reconocido; revisa CDN/hosting.' ); ?></p>
	<?php endif; ?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="elmercado_latitudes_clear_cache_20261015">
		<?php wp_nonce_field( 'elmercado_latitudes_clear_cache_20261015' ); ?>
		<p><button class="button button-primary" type="submit">Invalidar caché de productos, tienda y vendedor</button></p>
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
