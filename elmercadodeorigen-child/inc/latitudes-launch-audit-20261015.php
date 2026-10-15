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
		<h2>Referencias para configurar los envíos de Latitudes</h2>
		<p>Origen: condiciones de venta oficiales de <a href="https://latitudes.es/condiciones-de-venta/" target="_blank" rel="noopener noreferrer">latitudes.es</a>. No cambia tarifas; hay que configurar una regla por vendedor/paquete y comprobar los pesos importados.</p>
		<table class="widefat striped"><thead><tr><th>Destino</th><th>Condición</th><th>Importe</th></tr></thead><tbody>
			<tr><td>Península española y Portugal peninsular</td><td>0–4 kg, pedido inferior a 200 €</td><td>13,45 €</td></tr>
			<tr><td>Península española y Portugal peninsular</td><td>Más de 4–7 kg, pedido inferior a 200 €</td><td>16,45 €</td></tr>
			<tr><td>Península española y Portugal peninsular</td><td>Más de 7–20 kg, pedido inferior a 200 €</td><td>21,45 €</td></tr>
			<tr><td>Baleares</td><td>Hasta 20 kg, pedido inferior a 200 €</td><td>29,00 €</td></tr>
			<tr><td>Península / Baleares</td><td>Pedido Latitudes desde 200 €</td><td>Gratis</td></tr>
			<tr><td>Canarias, Ceuta, Melilla; más de 20 kg</td><td>No enviar automáticamente</td><td>No aplicable / consultar</td></tr>
		</tbody></table>
		<p><strong>Orden de corrección:</strong> 1. vendedor y estado Offline/Disabled; 2. autor WCFM del producto; 3. estado publish; 4. stock y visibilidad; 5. restricciones de destino; 6. purgar caché, regenerar lookup y probar catálogo sin sesión.</p>
	</div>
	<?php
}
