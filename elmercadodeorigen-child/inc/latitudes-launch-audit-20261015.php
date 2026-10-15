<?php
/**
 * Herramienta de diagnóstico de incorporación de Latitudes.
 *
 * Página de solo lectura: no altera productos, importaciones ni tarifas.
 * Ayuda a distinguir un import correcto de un vendedor oculto, productos
 * no publicados, productos agotados o productos asociados a otro autor.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	static function (): void {
		add_management_page(
			'Diagnóstico Latitudes',
			'Diagnóstico Latitudes',
			'manage_woocommerce',
			'elmercado-latitudes-audit',
			'elmercado_latitudes_render_audit_20261015'
		);
	}
);

/** Devuelve candidatos con nombre o slug Latitudes (sin suponer su ID). */
function elmercado_latitudes_vendor_candidates_20261015(): array {
	$users = get_users(
		array(
			'search'         => '*latitudes*',
			'search_columns' => array( 'user_login', 'user_nicename', 'display_name' ),
			'number'         => 30,
		)
	);
	$exact = get_user_by( 'slug', 'latitudes' );
	if ( $exact instanceof WP_User ) {
		$users[] = $exact;
	}
	/* Nombre comercial WCFM puede diferir del nombre de usuario. */
	$wcfm_vendors = get_users(
		array(
			'role__in' => array( 'wcfm_vendor', 'vendor', 'dc_vendor', 'seller' ),
			'number'   => 1000,
		)
	);
	foreach ( $wcfm_vendors as $candidate ) {
		if ( ! $candidate instanceof WP_User ) {
			continue;
		}
		$profile = get_user_meta( (int) $candidate->ID, 'wcfmmp_profile_settings', true );
		$name    = is_array( $profile ) ? (string) ( $profile['store_name'] ?? '' ) : '';
		if ( false !== stripos( $name, 'latitudes' ) ) {
			$users[] = $candidate;
		}
	}
	$unique = array();
	foreach ( $users as $user ) {
		if ( $user instanceof WP_User ) {
			$unique[ (int) $user->ID ] = $user;
		}
	}
	return array_values( $unique );
}

/** Informe de estados reales en la base de datos sin depender del filtro público. */
function elmercado_latitudes_product_stats_20261015( int $user_id ): array {
	global $wpdb;
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_status, COUNT(*) AS total
			   FROM {$wpdb->posts}
			  WHERE post_type = %s AND post_author = %d
			  GROUP BY post_status",
			'product',
			$user_id
		)
	);
	$stats = array();
	foreach ( (array) $results as $result ) {
		$stats[ (string) $result->post_status ] = (int) $result->total;
	}
	return $stats;
}

/** Presenta el reporte solo a administradores/gestores de WooCommerce. */
function elmercado_latitudes_render_audit_20261015(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver este informe.', 'elmercadodeorigen' ) );
	}
	$vendors = elmercado_latitudes_vendor_candidates_20261015();
	global $wpdb;
	$recent = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.post_author, p.post_status, COUNT(*) AS total
			   FROM {$wpdb->posts} p
			  WHERE p.post_type = %s AND p.post_date >= %s
			  GROUP BY p.post_author, p.post_status
			  ORDER BY total DESC LIMIT 20",
			'product',
			gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS )
		)
	);
	?>
	<div class="wrap">
		<h1>Latitudes · Diagnóstico de publicación</h1>
		<p><strong>Solo lectura.</strong> Este informe no activa tiendas, no publica productos y no cambia la configuración de envíos. Se basa en los datos actuales de WooCommerce y WCFM.</p>
		<?php if ( ! $vendors ) : ?>
			<div class="notice notice-warning"><p>No se ha localizado un usuario vendedor cuyo nombre o slug contenga «latitudes». Comprueba que la tienda esté creada en WCFM y que el importador tenga asignado ese vendedor.</p></div>
		<?php endif; ?>
		<?php foreach ( $vendors as $vendor ) : ?>
			<?php
			$id        = (int) $vendor->ID;
			$stats     = elmercado_latitudes_product_stats_20261015( $id );
			$profile   = get_user_meta( $id, 'wcfmmp_profile_settings', true );
			$store_name = is_array( $profile ) ? (string) ( $profile['store_name'] ?? '' ) : '';
			$is_vendor = function_exists( 'elmercado_core_filter_is_vendor' )
				? elmercado_core_filter_is_vendor( $id ) : null;
			$excluded_destination = class_exists( 'MDO_Catalog_Destination_Frontend' )
				? in_array( $id, array_map( 'absint', MDO_Catalog_Destination_Frontend::excluded_vendor_ids() ), true )
				: null;
			$all_excluded = function_exists( 'elmercado_catalog_counts_excluded_authors_010217' )
				? in_array( $id, elmercado_catalog_counts_excluded_authors_010217(), true ) : null;
			$offline   = get_user_meta( $id, '_wcfm_store_offline', true );
			$disabled  = get_user_meta( $id, '_disable_vendor', true );
			$is_hidden = function_exists( 'elmercado_wcfm_vendor_is_hidden_010210' )
				? elmercado_wcfm_vendor_is_hidden_010210( $id )
				: null;
			$products  = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'archived' ),
					'author'         => $id,
					'posts_per_page' => 25,
					'orderby'       => 'modified',
					'order'         => 'DESC',
				)
			);
			?>
			<h2><?php echo esc_html( $vendor->display_name ); ?> (ID <?php echo esc_html( (string) $id ); ?>)</h2>
			<p>Slug: <code><?php echo esc_html( $vendor->user_nicename ); ?></code> · Roles: <?php echo esc_html( implode( ', ', $vendor->roles ) ); ?></p>
			<table class="widefat striped"><tbody>
				<tr><th>Nombre comercial WCFM</th><td><?php echo esc_html( $store_name ?: 'Sin nombre comercial detectado' ); ?></td></tr>
				<tr><th>Reconocido como vendedor en los filtros de Tienda</th><td><?php echo esc_html( null === $is_vendor ? 'No disponible' : ( $is_vendor ? 'Sí' : 'NO: revisar rol/store_name' ) ); ?></td></tr>
				<tr><th>Excluido por destino de envío (para esta sesión)</th><td><?php echo esc_html( null === $excluded_destination ? 'No hay sistema de destino' : ( $excluded_destination ? 'SÍ: revisar configuración del vendedor' : 'No' ) ); ?></td></tr>
				<tr><th>Excluido en catálogo/contadores</th><td><?php echo esc_html( null === $all_excluded ? 'No disponible' : ( $all_excluded ? 'Sí' : 'No' ) ); ?></td></tr>
				<tr><th>Flag WCFM Offline</th><td><code><?php echo esc_html( var_export( $offline, true ) ); ?></code></td></tr>
				<tr><th>Flag WCFM Disabled</th><td><code><?php echo esc_html( var_export( $disabled, true ) ); ?></code></td></tr>
				<tr><th>Oculto por la política del tema (para esta sesión)</th><td><?php echo esc_html( null === $is_hidden ? 'Función no disponible' : ( $is_hidden ? 'Sí' : 'No' ) ); ?></td></tr>
				<tr><th>Productos por estado</th><td><?php echo esc_html( $stats ? wp_json_encode( $stats ) : 'Sin productos con este autor' ); ?></td></tr>
			</tbody></table>
			<h3>Últimos 25 productos de este autor</h3>
			<table class="widefat striped"><thead><tr><th>ID / nombre</th><th>Estado</th><th>Stock</th><th>Visibilidad</th><th>Vendedor WCFM real</th></tr></thead><tbody>
			<?php foreach ( $products as $post ) : ?>
				<?php
				$product  = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : false;
				$owner_id = function_exists( 'wcfm_get_vendor_id_by_post' )
					? (int) wcfm_get_vendor_id_by_post( $post->ID ) : (int) $post->post_author;
				?>
				<tr>
					<td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->ID . ' · ' . $post->post_title ); ?></a></td>
					<td><?php echo esc_html( $post->post_status ); ?></td>
					<td><?php echo esc_html( $product ? $product->get_stock_status() : '—' ); ?></td>
					<td><?php echo esc_html( $product ? $product->get_catalog_visibility() : '—' ); ?></td>
					<td><?php echo esc_html( (string) $owner_id ); ?><?php echo $owner_id !== $id ? ' ⚠' : ''; ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $products ) : ?><tr><td colspan="5">No hay productos atribuidos a este usuario.</td></tr><?php endif; ?>
			</tbody></table>
		<?php endforeach; ?>
		<h2>Autores de productos creados los últimos 7 días</h2>
		<p>Útil para detectar si el importador está creando productos con el administrador u otro usuario en lugar de Latitudes.</p>
		<table class="widefat striped"><thead><tr><th>Usuario</th><th>Estado</th><th>Cantidad</th></tr></thead><tbody>
		<?php foreach ( (array) $recent as $row ) : ?>
			<?php $author = get_userdata( (int) $row->post_author ); ?>
			<tr><td><?php echo esc_html( $author ? $author->display_name . ' (#' . $author->ID . ')' : '#' . $row->post_author ); ?></td><td><?php echo esc_html( $row->post_status ); ?></td><td><?php echo esc_html( (string) $row->total ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<h2>Tarifas acordadas para el marketplace</h2>
		<p>Para un paquete exclusivo de Latitudes: <strong>13,45 €</strong> si el importe final de sus artículos es inferior a 200 € y <strong>gratis</strong> desde 200 € (incluidos 200 € exactos). Se aplica a Península española/portuguesa y Baleares, sin tramos de peso. No se habilita Canarias, Ceuta, Melilla, Madeira o Azores. Se respeta la separación por vendedor de los paquetes WCFM.</p>
		<p>Las condiciones originales de <a href="https://latitudes.es/condiciones-de-venta/" target="_blank" rel="noopener noreferrer">latitudes.es</a> tienen otras tarifas por peso y para Baleares. Estas reglas simplificadas son una instrucción comercial específica de El Mercado de Origen, no una transcripción de los gastos oficiales.</p>
		<p>Si WooCommerce/WCFM no separa los pedidos en paquetes por vendedor, <strong>la regla no sustituye el envío de un carrito mixto</strong>; habría que corregir la separación para no cobrar menos a otros proveedores.</p>
		<?php if ( function_exists( 'elmercado_latitudes_cache_actions_20261015' ) ) : ?>
			<?php elmercado_latitudes_cache_actions_20261015(); ?>
		<?php endif; ?>
	</div>
	<?php
}
