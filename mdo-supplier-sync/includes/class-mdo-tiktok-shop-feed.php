<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TikTok Shop exports sourced exclusively from the internal WooCommerce
 * product category "mentta". This is an admin-only export; it never exposes
 * MENTTA publicly and never changes product/category membership.
 */
final class MDO_TikTok_Shop_Feed {
	private const STOCK_FALLBACK_OPTION = 'mdo_tiktok_unmanaged_stock_fallback';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 101 );
		add_action( 'admin_post_mdo_tiktok_download_master', array( __CLASS__, 'download_master' ) );
		add_action( 'admin_post_mdo_tiktok_download_accelerator', array( __CLASS__, 'download_accelerator' ) );
		add_action( 'admin_post_mdo_tiktok_save_settings', array( __CLASS__, 'save_settings' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			self::parent_slug(),
			'TikTok Shop Feed',
			'Feeds · TikTok Shop',
			'manage_woocommerce',
			'mdo-tiktok-shop-feed',
			array( __CLASS__, 'page' )
		);
	}

	private static function parent_slug(): string {
		if ( function_exists( 'mdo_gmf_admin_parent_v1' ) ) {
			return mdo_gmf_admin_parent_v1();
		}
		return 'mdo-supplier-sync';
	}

	public static function page(): void {
		self::guard();
		$data  = self::catalog();
		$rows  = $data['rows'];
		$stats = self::stats( $rows, $data['product_count'] );
		$fallback = self::stock_fallback();
		$master_url = wp_nonce_url( admin_url( 'admin-post.php?action=mdo_tiktok_download_master' ), 'mdo_tiktok_download_master' );
		$accelerator_url = wp_nonce_url( admin_url( 'admin-post.php?action=mdo_tiktok_download_accelerator' ), 'mdo_tiktok_download_accelerator' );
		$missing = array_values( array_filter( $rows, static fn( $row ) => '' === $row['identifier_code'] ) );
		$duplicate_ids = self::duplicate_identifiers( $rows );
		$category_counts = array();
		foreach ( $rows as $row ) {
			$category = $row['woocommerce_category'] ?: 'Sin categoría';
			$category_counts[ $category ] = ( $category_counts[ $category ] ?? 0 ) + 1;
		}
		arsort( $category_counts );
		?>
		<div class="wrap mdo-sync-wrap">
			<h1>Feeds · TikTok Shop</h1>
			<p class="description">Exportación interna de los productos asignados a la categoría <code>mentta</code>. La categoría continúa oculta en la tienda pública; esta pantalla solo la usa como selección de catálogo.</p>

			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p>Configuración de TikTok Shop guardada.</p></div>
			<?php endif; ?>

			<div class="mdo-cards">
				<?php self::card( 'Productos MENTTA', $stats['products'] ); ?>
				<?php self::card( 'SKUs / variaciones', $stats['rows'] ); ?>
				<?php self::card( 'Con EAN/GTIN', $stats['identified'] ); ?>
				<?php self::card( 'Sin EAN/GTIN', $stats['missing_identifier'] ); ?>
			</div>

			<div class="mdo-panel">
				<h2>Descargas</h2>
				<p><a class="button button-primary" href="<?php echo esc_url( $master_url ); ?>">Descargar Excel maestro TikTok (.xlsx)</a>
				<a class="button" href="<?php echo esc_url( $accelerator_url ); ?>">Descargar Product Upload Accelerator (.xlsx)</a></p>
				<p><strong>Excel maestro:</strong> incluye todos los productos de MENTTA y una fila por SKU/variación, con EAN/GTIN, SKU, variantes, precio, cantidad, categorías, productor, marca, dimensiones, imágenes y URL.</p>
				<p><strong>Product Upload Accelerator:</strong> genera exactamente las cinco columnas <code>Identifier Code</code>, <code>Product Name</code>, <code>Product Description</code>, <code>Price</code> y <code>Quantity</code>. Solo puede incluir filas con identificador válido y único; las filas sin EAN/GTIN quedan en el Excel maestro para poder completarlas.</p>
				<p class="description">Para la carga masiva estándar de TikTok Shop en España, TikTok genera una plantilla distinta para cada categoría final. El Excel maestro de EMDO sirve como fuente para esas plantillas; no modifica la estructura de la plantilla oficial.</p>
			</div>

			<div class="mdo-panel">
				<h2>Inventario para productos sin cantidad gestionada</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mdo_tiktok_save_settings">
					<?php wp_nonce_field( 'mdo_tiktok_save_settings' ); ?>
					<label>Unidades a exportar cuando WooCommerce indica “en stock” pero no gestiona cantidad:
						<input type="number" min="0" max="99999" name="unmanaged_stock_fallback" value="<?php echo esc_attr( (string) $fallback ); ?>" style="width:100px">
					</label>
					<?php submit_button( 'Guardar', 'secondary', 'submit', false ); ?>
				</form>
				<p class="description">Los productos agotados siempre se exportan con cantidad 0. El valor anterior solo se usa cuando WooCommerce no guarda una cantidad numérica.</p>
			</div>

			<div class="mdo-panel">
				<h2>Estado del catálogo</h2>
				<table class="widefat striped" style="max-width:900px">
					<tbody>
						<tr><th>Productos seleccionados</th><td><?php echo esc_html( number_format_i18n( $stats['products'] ) ); ?></td></tr>
						<tr><th>Filas SKU exportables</th><td><?php echo esc_html( number_format_i18n( $stats['rows'] ) ); ?></td></tr>
						<tr><th>Filas con identificador</th><td><?php echo esc_html( number_format_i18n( $stats['identified'] ) ); ?></td></tr>
						<tr><th>Identificadores únicos</th><td><?php echo esc_html( number_format_i18n( $stats['unique_identifiers'] ) ); ?></td></tr>
						<tr><th>Filas sin identificador</th><td><?php echo esc_html( number_format_i18n( $stats['missing_identifier'] ) ); ?></td></tr>
						<tr><th>Identificadores duplicados</th><td><?php echo esc_html( number_format_i18n( count( $duplicate_ids ) ) ); ?></td></tr>
					</tbody>
				</table>
			</div>

			<?php if ( $category_counts ) : ?>
			<div class="mdo-panel">
				<h2>Categorías WooCommerce representadas</h2>
				<table class="widefat striped" style="max-width:900px"><thead><tr><th>Categoría</th><th>SKUs</th></tr></thead><tbody>
				<?php foreach ( $category_counts as $category => $count ) : ?>
					<tr><td><?php echo esc_html( $category ); ?></td><td><?php echo esc_html( number_format_i18n( $count ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			</div>
			<?php endif; ?>

			<?php if ( $missing ) : ?>
			<div class="mdo-panel">
				<h2>Primeras filas sin EAN/GTIN</h2>
				<p class="description">Estas filas sí aparecen en el Excel maestro, pero TikTok no puede procesarlas mediante Product Upload Accelerator hasta que tengan un identificador reconocido.</p>
				<table class="widefat striped"><thead><tr><th>Producto</th><th>Variación</th><th>SKU</th><th>Precio</th><th>Editar</th></tr></thead><tbody>
				<?php foreach ( array_slice( $missing, 0, 30 ) as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['product_name'] ); ?></td>
						<td><?php echo esc_html( $row['variation'] ?: '—' ); ?></td>
						<td><code><?php echo esc_html( $row['seller_sku'] ?: '—' ); ?></code></td>
						<td><?php echo esc_html( wc_price( (float) $row['price'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( (int) $row['product_id'] ) ); ?>">Abrir producto</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody></table>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function save_settings(): void {
		self::guard();
		check_admin_referer( 'mdo_tiktok_save_settings' );
		$value = isset( $_POST['unmanaged_stock_fallback'] ) ? absint( $_POST['unmanaged_stock_fallback'] ) : 1;
		update_option( self::STOCK_FALLBACK_OPTION, min( 99999, $value ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=mdo-tiktok-shop-feed&saved=1' ) );
		exit;
	}

	public static function download_master(): void {
		self::guard();
		check_admin_referer( 'mdo_tiktok_download_master' );
		$data = self::catalog();
		$headers = array(
			'Product ID',
			'Variation ID',
			'Product Name',
			'Product Description',
			'Identifier Code',
			'Seller SKU',
			'Variation',
			'Price',
			'Quantity',
			'Currency',
			'Stock Status',
			'WooCommerce Category',
			'Producer',
			'Brand',
			'Weight (kg)',
			'Length (cm)',
			'Width (cm)',
			'Height (cm)',
			'Image 1',
			'Image 2',
			'Image 3',
			'Image 4',
			'Image 5',
			'Image 6',
			'Image 7',
			'Image 8',
			'Image 9',
			'Product URL',
			'WooCommerce Status',
		);
		$sheet_rows = array();
		foreach ( $data['rows'] as $row ) {
			$images = array_pad( array_slice( $row['images'], 0, 9 ), 9, '' );
			$sheet_rows[] = array_merge(
				array(
					$row['product_id'],
					$row['variation_id'],
					$row['product_name'],
					$row['description'],
					$row['identifier_code'],
					$row['seller_sku'],
					$row['variation'],
					$row['price'],
					$row['quantity'],
					$row['currency'],
					$row['stock_status'],
					$row['woocommerce_category'],
					$row['producer'],
					$row['brand'],
					$row['weight'],
					$row['length'],
					$row['width'],
					$row['height'],
				),
				$images,
				array( $row['product_url'], $row['status'] )
			);
		}
		self::send_xlsx( 'emdo-tiktok-mentta-master-' . gmdate( 'Y-m-d' ) . '.xlsx', 'MENTTA TikTok', $headers, $sheet_rows, array( 1, 2, 8, 9, 15, 16, 17, 18 ) );
	}

	public static function download_accelerator(): void {
		self::guard();
		check_admin_referer( 'mdo_tiktok_download_accelerator' );
		$data = self::catalog();
		$seen = array();
		$sheet_rows = array();
		foreach ( $data['rows'] as $row ) {
			$id = trim( (string) $row['identifier_code'] );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$name = $row['product_name'];
			if ( '' !== $row['variation'] ) {
				$name .= ' · ' . $row['variation'];
			}
			$sheet_rows[] = array(
				$id,
				$name,
				$row['description'],
				$row['price'],
				$row['quantity'],
			);
		}
		self::send_xlsx(
			'emdo-tiktok-product-upload-accelerator-' . gmdate( 'Y-m-d' ) . '.xlsx',
			'TikTok Upload',
			array( 'Identifier Code', 'Product Name', 'Product Description', 'Price', 'Quantity' ),
			$sheet_rows,
			array( 4, 5 )
		);
	}

	private static function catalog(): array {
		$term = get_term_by( 'slug', 'mentta', 'product_cat' );
		if ( ! ( $term instanceof WP_Term ) ) {
			return array( 'product_count' => 0, 'rows' => array() );
		}

		$term_ids = array( (int) $term->term_id );
		$children = get_term_children( (int) $term->term_id, 'product_cat' );
		if ( ! is_wp_error( $children ) ) {
			$term_ids = array_values( array_unique( array_merge( $term_ids, array_map( 'intval', $children ) ) ) );
		}

		$product_ids = array();
		foreach ( $term_ids as $term_id ) {
			$ids = get_objects_in_term( $term_id, 'product_cat' );
			if ( is_wp_error( $ids ) ) {
				continue;
			}
			foreach ( $ids as $id ) {
				$id = (int) $id;
				if ( 'product' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) ) {
					$product_ids[ $id ] = $id;
				}
			}
		}
		ksort( $product_ids, SORT_NUMERIC );

		$rows = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$base = self::product_base( $product, $term_ids );
			if ( $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( ! $variation || 'trash' === get_post_status( $variation_id ) ) {
						continue;
					}
					$rows[] = self::row_from_product( $variation, $product, $base );
				}
			} else {
				$rows[] = self::row_from_product( $product, null, $base );
			}
		}

		return array(
			'product_count' => count( $product_ids ),
			'rows'          => $rows,
		);
	}

	private static function product_base( WC_Product $product, array $mentta_term_ids ): array {
		$product_id = $product->get_id();
		$categories = array();
		$terms = wp_get_object_terms( $product_id, 'product_cat' );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( in_array( (int) $term->term_id, $mentta_term_ids, true ) ) {
					continue;
				}
				$categories[] = $term->name;
			}
		}
		$description = self::clean_text( $product->get_description() );
		if ( '' === $description ) {
			$description = self::clean_text( $product->get_short_description() );
		}
		return array(
			'product_id'            => $product_id,
			'product_name'          => get_the_title( $product_id ),
			'description'           => $description,
			'woocommerce_category'  => implode( ' | ', array_values( array_unique( $categories ) ) ),
			'producer'              => self::producer( $product_id ),
			'brand'                 => self::brand( $product_id ),
			'product_url'           => get_permalink( $product_id ),
			'status'                => (string) get_post_status( $product_id ),
		);
	}

	private static function row_from_product( WC_Product $item, ?WC_Product $parent, array $base ): array {
		$variation = '';
		if ( $item->is_type( 'variation' ) ) {
			$parts = array();
			foreach ( $item->get_attributes() as $key => $value ) {
				$label = wc_attribute_label( $key, $parent );
				$value_label = $value;
				if ( taxonomy_exists( $key ) ) {
					$term = get_term_by( 'slug', $value, $key );
					if ( $term instanceof WP_Term ) {
						$value_label = $term->name;
					}
				}
				if ( '' !== trim( (string) $value_label ) ) {
					$parts[] = $label . ': ' . $value_label;
				}
			}
			$variation = implode( ' | ', $parts );
		}

		$images = self::images( $item, $parent );
		$dimensions_source = $item;
		$weight = $dimensions_source->get_weight();
		$length = $dimensions_source->get_length();
		$width  = $dimensions_source->get_width();
		$height = $dimensions_source->get_height();
		if ( $parent ) {
			$weight = '' !== $weight ? $weight : $parent->get_weight();
			$length = '' !== $length ? $length : $parent->get_length();
			$width  = '' !== $width ? $width : $parent->get_width();
			$height = '' !== $height ? $height : $parent->get_height();
		}

		return array(
			'product_id'            => (int) $base['product_id'],
			'variation_id'          => $item->is_type( 'variation' ) ? (int) $item->get_id() : 0,
			'product_name'          => (string) $base['product_name'],
			'description'           => (string) $base['description'],
			'identifier_code'       => self::identifier( $item ),
			'seller_sku'            => (string) $item->get_sku(),
			'variation'             => $variation,
			'price'                 => '' === (string) $item->get_price() ? '' : wc_format_decimal( $item->get_price(), 2 ),
			'quantity'              => self::quantity( $item, $parent ),
			'currency'              => get_woocommerce_currency(),
			'stock_status'          => (string) $item->get_stock_status(),
			'woocommerce_category'  => (string) $base['woocommerce_category'],
			'producer'              => (string) $base['producer'],
			'brand'                 => (string) $base['brand'],
			'weight'                => (string) $weight,
			'length'                => (string) $length,
			'width'                 => (string) $width,
			'height'                => (string) $height,
			'images'                => $images,
			'product_url'           => (string) $base['product_url'],
			'status'                => (string) $base['status'],
		);
	}

	private static function quantity( WC_Product $item, ?WC_Product $parent ): int {
		if ( 'outofstock' === $item->get_stock_status() ) {
			return 0;
		}
		if ( $item->get_manage_stock() ) {
			return max( 0, (int) $item->get_stock_quantity() );
		}
		if ( $parent && $parent->get_manage_stock() ) {
			return max( 0, (int) $parent->get_stock_quantity() );
		}
		return self::stock_fallback();
	}

	private static function stock_fallback(): int {
		return max( 0, min( 99999, (int) get_option( self::STOCK_FALLBACK_OPTION, 1 ) ) );
	}

	private static function identifier( WC_Product $product ): string {
		if ( method_exists( $product, 'get_global_unique_id' ) ) {
			$value = trim( (string) $product->get_global_unique_id() );
			if ( '' !== $value ) {
				return $value;
			}
		}
		foreach ( array( '_global_unique_id', '_alg_ean', '_ean', 'ean', 'gtin', '_gtin', '_wpm_gtin_code', 'hwp_product_gtin', '_barcode', 'barcode' ) as $key ) {
			$value = trim( (string) get_post_meta( $product->get_id(), $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}
		return '';
	}

	private static function producer( int $product_id ): string {
		$user_id = (int) get_post_field( 'post_author', $product_id );
		if ( ! $user_id ) {
			return '';
		}
		foreach ( array( 'store_name', 'wcfmmp_store_name', 'pv_shop_name', 'dokan_store_name' ) as $key ) {
			$value = trim( (string) get_user_meta( $user_id, $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}
		$user = get_userdata( $user_id );
		return $user ? (string) $user->display_name : '';
	}

	private static function brand( int $product_id ): string {
		foreach ( array( 'product_brand', 'pwb-brand' ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$names = wp_get_object_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $names ) && $names ) {
					return implode( ', ', $names );
				}
			}
		}
		$product = wc_get_product( $product_id );
		if ( $product ) {
			foreach ( $product->get_attributes() as $attribute ) {
				if ( ! is_object( $attribute ) ) {
					continue;
				}
				$label = mb_strtolower( wc_attribute_label( $attribute->get_name() ) );
				if ( ! in_array( $label, array( 'marca', 'brand' ), true ) ) {
					continue;
				}
				$values = $attribute->is_taxonomy()
					? wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) )
					: $attribute->get_options();
				if ( ! is_wp_error( $values ) && $values ) {
					return implode( ', ', $values );
				}
			}
		}
		return '';
	}

	private static function images( WC_Product $item, ?WC_Product $parent ): array {
		$ids = array();
		if ( $item->get_image_id() ) {
			$ids[] = (int) $item->get_image_id();
		}
		if ( $parent && $parent->get_image_id() ) {
			$ids[] = (int) $parent->get_image_id();
		}
		$gallery = $parent ?: $item;
		$ids = array_merge( $ids, array_map( 'intval', $gallery->get_gallery_image_ids() ) );
		$urls = array();
		foreach ( array_values( array_unique( array_filter( $ids ) ) ) as $attachment_id ) {
			$url = wp_get_attachment_url( $attachment_id );
			if ( $url ) {
				$urls[] = $url;
			}
		}
		return $urls;
	}

	private static function clean_text( string $html ): string {
		$text = html_entity_decode( wp_strip_all_tags( $html, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = preg_replace( '/\R{3,}/u', "\n\n", (string) $text );
		return trim( (string) $text );
	}

	private static function stats( array $rows, int $product_count ): array {
		$identified = 0;
		$ids = array();
		foreach ( $rows as $row ) {
			$id = trim( (string) $row['identifier_code'] );
			if ( '' !== $id ) {
				$identified++;
				$ids[ $id ] = true;
			}
		}
		return array(
			'products'           => $product_count,
			'rows'               => count( $rows ),
			'identified'         => $identified,
			'unique_identifiers' => count( $ids ),
			'missing_identifier' => count( $rows ) - $identified,
		);
	}

	private static function duplicate_identifiers( array $rows ): array {
		$counts = array();
		foreach ( $rows as $row ) {
			$id = trim( (string) $row['identifier_code'] );
			if ( '' !== $id ) {
				$counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
			}
		}
		return array_filter( $counts, static fn( $count ) => $count > 1 );
	}

	private static function card( string $label, int $value ): void {
		echo '<div class="mdo-card"><strong>' . esc_html( number_format_i18n( $value ) ) . '</strong><span>' . esc_html( $label ) . '</span></div>';
	}

	private static function send_xlsx( string $filename, string $sheet_name, array $headers, array $rows, array $numeric_columns = array() ): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( 'El servidor no tiene disponible ZipArchive, necesario para generar el archivo XLSX.' );
		}
		$tmp = wp_tempnam( $filename );
		if ( ! $tmp ) {
			wp_die( 'No se pudo crear el archivo temporal XLSX.' );
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			@unlink( $tmp );
			wp_die( 'No se pudo construir el archivo XLSX.' );
		}

		$zip->addFromString( '[Content_Types].xml', self::xlsx_content_types() );
		$zip->addFromString( '_rels/.rels', self::xlsx_root_rels() );
		$zip->addFromString( 'xl/workbook.xml', self::xlsx_workbook( $sheet_name ) );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', self::xlsx_workbook_rels() );
		$zip->addFromString( 'xl/styles.xml', self::xlsx_styles() );
		$zip->addFromString( 'xl/worksheets/sheet1.xml', self::xlsx_sheet( $headers, $rows, $numeric_columns ) );
		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . filesize( $tmp ) );
		readfile( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		@unlink( $tmp );
		exit;
	}

	private static function xlsx_content_types(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
			. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
			. '</Types>';
	}

	private static function xlsx_root_rels(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>';
	}

	private static function xlsx_workbook( string $sheet_name ): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<sheets><sheet name="' . self::xml( mb_substr( $sheet_name, 0, 31 ) ) . '" sheetId="1" r:id="rId1"/></sheets>'
			. '</workbook>';
	}

	private static function xlsx_workbook_rels(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
			. '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
			. '</Relationships>';
	}

	private static function xlsx_styles(): string {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
			. '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
			. '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
			. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
			. '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
			. '</styleSheet>';
	}

	private static function xlsx_sheet( array $headers, array $rows, array $numeric_columns ): string {
		$numeric = array_fill_keys( array_map( 'intval', $numeric_columns ), true );
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
			. '<sheetFormatPr defaultRowHeight="15"/><sheetData>';
		$xml .= '<row r="1">';
		foreach ( array_values( $headers ) as $index => $value ) {
			$ref = self::xlsx_col( $index + 1 ) . '1';
			$xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t xml:space="preserve">' . self::xml( (string) $value ) . '</t></is></c>';
		}
		$xml .= '</row>';

		$row_number = 2;
		foreach ( $rows as $row ) {
			$xml .= '<row r="' . $row_number . '">';
			foreach ( array_values( $row ) as $index => $value ) {
				$column_number = $index + 1;
				$ref = self::xlsx_col( $column_number ) . $row_number;
				if ( isset( $numeric[ $column_number ] ) && '' !== (string) $value && is_numeric( $value ) ) {
					$xml .= '<c r="' . $ref . '" t="n"><v>' . self::xml( (string) $value ) . '</v></c>';
				} else {
					$xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . self::xml( (string) $value ) . '</t></is></c>';
				}
			}
			$xml .= '</row>';
			$row_number++;
		}
		$last_col = self::xlsx_col( max( 1, count( $headers ) ) );
		$last_row = max( 1, $row_number - 1 );
		$xml .= '</sheetData><autoFilter ref="A1:' . $last_col . $last_row . '"/></worksheet>';
		return $xml;
	}

	private static function xlsx_col( int $number ): string {
		$result = '';
		while ( $number > 0 ) {
			$number--;
			$result = chr( 65 + ( $number % 26 ) ) . $result;
			$number = intdiv( $number, 26 );
		}
		return $result;
	}

	private static function xml( string $value ): string {
		$value = preg_replace( '/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value );
		return htmlspecialchars( (string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8' );
	}

	private static function guard(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para gestionar EMDO.' );
		}
	}
}
