<?php
/**
 * Puesta en venta controlada de productos de Latitudes importados como draft.
 *
 * La tienda y el catálogo exigen post_status=publish. Esto diagnostica qué
 * borradores están completos y facilita publicarlos por decisión explícita
 * de un gestor WooCommerce. No interviene en el importador ni publica solo.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lista de productos drafts con causa concreta de bloqueo.
 *
 * @return array{vendor_id:int,items:array<int,array{id:int,title:string,eligible:bool,reason:string}>,publishable:int}
 */
function elmercado_latitudes_draft_preflight_20261015(): array {
	$vendor_id = function_exists( 'elmercado_latitudes_vendor_id_20261015' )
		? absint( elmercado_latitudes_vendor_id_20261015() ) : 0;
	$result = array( 'vendor_id' => $vendor_id, 'items' => array(), 'publishable' => 0 );

	if ( $vendor_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
		return $result;
	}

	$ids = get_posts(
		array(
			'post_type'        => 'product',
			'post_status'      => 'draft',
			'author'           => $vendor_id,
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		$id      = absint( $id );
		$product = wc_get_product( $id );
		$reason  = '';

		if ( ! $product ) {
			$reason = 'WooCommerce no reconoce el producto';
		} elseif ( '' === trim( (string) $product->get_name() ) ) {
			$reason = 'Falta el nombre';
		} elseif ( '' === (string) $product->get_price() ) {
			$reason = 'Falta el precio';
		} elseif ( ! $product->get_image_id() ) {
			$reason = 'Falta la imagen principal';
		} elseif ( ! $product->is_in_stock() ) {
			$reason = 'Sin existencias según WooCommerce';
		} elseif ( in_array( $product->get_catalog_visibility(), array( 'hidden', 'search' ), true ) ) {
			$reason = 'Oculto del catálogo por visibilidad';
		} elseif ( function_exists( 'wcfm_get_vendor_id_by_post' ) ) {
			$wcfm_vendor = absint( wcfm_get_vendor_id_by_post( $id ) );
			if ( $wcfm_vendor > 0 && $wcfm_vendor !== $vendor_id ) {
				$reason = 'WCFM atribuye el producto a otro vendedor';
			}
		}
		$eligible = '' === $reason;
		$result['items'][] = array(
			'id'       => $id,
			'title'    => get_the_title( $id ),
			'eligible' => $eligible,
			'reason'   => $eligible ? 'Listo para publicar' : $reason,
		);
		if ( $eligible ) {
			++$result['publishable'];
		}
	}
	return $result;
}

/**
 * Da pistas del origen del estado borrador y permite compararlo con los
 * permisos del usuario importador. No modifica ajustes del plugin.
 */
function elmercado_latitudes_draft_origin_admin_panel_20261015(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$vendor_id = function_exists( 'elmercado_latitudes_vendor_id_20261015' )
		? absint( elmercado_latitudes_vendor_id_20261015() ) : 0;
	$vendor = $vendor_id > 0 ? get_userdata( $vendor_id ) : false;
	?>
	<h3>¿Por qué está importando como borrador?</h3>
	<p>La causa ya es el estado <code>draft</code>; para evitar recurrencias hay que identificar quién lo escribe. Ningún ajuste de caché o envío convierte un borrador en publicado.</p>
	<table class="widefat striped"><tbody>
		<tr><th>Cuenta del vendedor</th><td><?php echo esc_html( $vendor ? $vendor->display_name . ' (#' . $vendor_id . ')' : 'No identificada' ); ?></td></tr>
		<tr><th>Capacidad de publicar productos del vendedor</th><td><?php echo esc_html( $vendor && user_can( $vendor, 'publish_products' ) ? 'Sí' : 'No; si el importador se ejecuta como este vendedor podría dejar borradores' ); ?></td></tr>
	</tbody></table>
	<?php
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$installed = get_plugins();
	$active = (array) get_option( 'active_plugins', array() );
	$related = array();
	foreach ( $installed as $basename => $plugin ) {
		if ( ! in_array( $basename, $active, true ) ) {
			continue;
		}
		$label = (string) ( $plugin['Name'] ?? $basename );
		if ( preg_match( '/import|sync|scrap|wcfm|latitud|marketplace|proveedor/i', $basename . ' ' . $label ) ) {
			$related[] = $label . ' (' . $basename . ')';
		}
	}
	?>
	<p><strong>Plugins activos relacionados:</strong> <?php echo esc_html( $related ? implode( ' · ', $related ) : 'Sin coincidencias; comprueba también plugins MU y tareas externas.' ); ?></p>
	<p><strong>Comprobación necesaria en el importador:</strong> estado del producto al crearlo (<code>post_status</code> o <code>status</code>), usuario/autor elegido y si WCFM exige aprobación de producto. Si el importador sobrescribe el estado en cada sincronización, cambiarlo allí antes de republicar.</p>
	<?php
}

/** Publicación POST con nonce: solo Latitudes y solo fichas verificadas. */
add_action(
	'admin_post_elmercado_latitudes_publish_drafts_20261015',
	static function (): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_products' ) ) {
			wp_die( 'No tienes permisos para publicar productos.' );
		}
		check_admin_referer( 'elmercado_latitudes_publish_drafts_20261015' );
		if ( ! isset( $_POST['latitudes_confirm_publish'] )
			|| 'yes' !== sanitize_text_field( wp_unslash( $_POST['latitudes_confirm_publish'] ) ) ) {
			wp_die( 'Confirmación de publicación ausente.' );
		}
		$review = elmercado_latitudes_draft_preflight_20261015();
		$vendor_id = absint( $review['vendor_id'] );
		$published = 0;
		$skipped   = 0;
		if ( $vendor_id > 0 ) {
			foreach ( $review['items'] as $item ) {
				if ( ! $item['eligible'] ) {
					++$skipped;
					continue;
				}
				$id = absint( $item['id'] );
				if ( 'draft' !== get_post_status( $id )
					|| absint( get_post_field( 'post_author', $id ) ) !== $vendor_id ) {
					++$skipped;
					continue;
				}
				$updated = wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'publish',
					),
					true
				);
				if ( is_wp_error( $updated ) || ! $updated || 'publish' !== get_post_status( $id ) ) {
					++$skipped;
					continue;
				}
				++$published;
				if ( function_exists( 'wc_delete_product_transients' ) ) {
					wc_delete_product_transients( $id );
				}
				clean_post_cache( $id );
			}
		}
		if ( $published > 0 ) {
			if ( function_exists( 'elmercado_latitudes_flush_home_file_cache_20261015' ) ) {
				elmercado_latitudes_flush_home_file_cache_20261015();
			}
			if ( function_exists( 'elmercado_latitudes_flush_early_static_20261015' ) ) {
				elmercado_latitudes_flush_early_static_20261015();
			}
			if ( function_exists( 'elmercado_latitudes_purge_page_cache_20261015' )
				&& function_exists( 'elmercado_latitudes_cache_urls_20261015' ) ) {
				elmercado_latitudes_purge_page_cache_20261015(
					elmercado_latitudes_cache_urls_20261015()
				);
			}
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                 => 'elmercado-latitudes-audit',
					'latitudes_published'  => $published,
					'latitudes_skipped'    => $skipped,
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}
);

/** Mostrar resumen de borradores en el diagnóstico del productor. */
function elmercado_latitudes_draft_publish_admin_panel_20261015(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$review = elmercado_latitudes_draft_preflight_20261015();
	$total = count( $review['items'] );
	$publishable = (int) $review['publishable'];
	?>
	<h2>Publicación de productos importados en borrador</h2>
	<?php elmercado_latitudes_draft_origin_admin_panel_20261015(); ?>
	<p>El catálogo público solo muestra <code>publish</code>. Se han localizado
		<strong><?php echo esc_html( (string) $total ); ?> borradores</strong> atribuidos al autor Latitudes;
		<strong><?php echo esc_html( (string) $publishable ); ?> completos</strong> según precio, imagen,
		existencias, visibilidad y propiedad WCFM.</p>
	<?php if ( isset( $_GET['latitudes_published'] ) && ! is_array( $_GET['latitudes_published'] ) ) : ?>
		<div class="notice notice-info"><p>
			Publicados: <?php echo esc_html( (string) absint( $_GET['latitudes_published'] ) ); ?>.
			Omitidos: <?php echo esc_html( (string) absint( $_GET['latitudes_skipped'] ?? 0 ) ); ?>.
			Comprueba ahora la tienda pública y las zonas de envío.
		</p></div>
	<?php endif; ?>
	<?php if ( $total ) : ?>
		<table class="widefat striped">
			<thead><tr><th>ID</th><th>Producto</th><th>Revisión</th></tr></thead>
			<tbody>
			<?php foreach ( array_slice( $review['items'], 0, 100 ) as $item ) : ?>
				<tr>
					<td><?php echo esc_html( (string) $item['id'] ); ?></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></td>
					<td><?php echo esc_html( $item['reason'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $total > 100 ) : ?><p>Se muestran los primeros 100; el análisis comprende los <?php echo esc_html( (string) $total ); ?> borradores.</p><?php endif; ?>
	<?php endif; ?>
	<?php if ( $publishable > 0 && current_user_can( 'edit_products' ) ) : ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="elmercado_latitudes_publish_drafts_20261015">
			<input type="hidden" name="latitudes_confirm_publish" value="yes">
			<?php wp_nonce_field( 'elmercado_latitudes_publish_drafts_20261015' ); ?>
			<?php submit_button( sprintf( 'Publicar %d productos de Latitudes revisados', $publishable ), 'primary' ); ?>
		</form>
		<p><small>Acción irreversible mediante este panel (se puede revertir editando productos). No incluye borradores sin precio, imagen, stock ni los asignados por WCFM a otro vendedor. Los cambios del importador deben revisarse para que futuras sincronizaciones no vuelvan a crear borradores.</small></p>
	<?php endif; ?>
	<?php
}
