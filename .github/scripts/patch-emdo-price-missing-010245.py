from pathlib import Path


# 1) Pricing: preserve original/current sale information without reintroducing
# related-product false positives on Tolecarnes.
pricing = Path('mdo-supplier-sync/includes/class-mdo-pricing.php')
text = pricing.read_text()
start_marker = "\t\t/*\n\t\t * Tolecarnes incluye tarjetas de productos relacionados"
end_marker = "\n\t\t// WooCommerce: <del>precio original</del> <ins>precio actual</ins>."
start = text.find(start_marker)
end = text.find(end_marker, start)
if start < 0 or end < 0:
    raise SystemExit('Pricing Tolecarnes block not found')
replacement = '''\t\t/*
\t\t * Tolecarnes incluye precios de productos relacionados en la misma ficha.
\t\t * Por eso no recorremos <del>/<ins> globales: sólo miramos el precio del
\t\t * summary o, si el tema no usa esa clase, el primer bloque de precio
\t\t * posterior al H1 que no pertenezca a relacionados/upsells/cross-sells.
\t\t * Así conservamos ofertas reales (precio original + actual) sin volver a
\t\t * atribuir al producto principal descuentos de otras tarjetas.
\t\t */
\t\t$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
\t\tif ( 'tolecarnes.com' === $host || str_ends_with( $host, '.tolecarnes.com' ) ) {
\t\t\t$queries = array(
\t\t\t\t"//*[contains(concat(' ', normalize-space(@class), ' '), ' summary ')]//p[contains(concat(' ', normalize-space(@class), ' '), ' price ')]",
\t\t\t\t"//*[contains(concat(' ', normalize-space(@class), ' '), ' summary ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' price ')]",
\t\t\t\t"(//h1[1]/following::*[contains(concat(' ', normalize-space(@class), ' '), ' price ') and not(ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' related ') or contains(concat(' ', normalize-space(@class), ' '), ' upsells ') or contains(concat(' ', normalize-space(@class), ' '), ' up-sells ') or contains(concat(' ', normalize-space(@class), ' '), ' cross-sells ')])])[1]",
\t\t\t);
\t\t\t$fallback_current = null;
\t\t\tforeach ( $queries as $query ) {
\t\t\t\t$price_nodes = $xpath->query( $query );
\t\t\t\tif ( ! $price_nodes || ! $price_nodes->length ) {
\t\t\t\t\tcontinue;
\t\t\t\t}
\t\t\t\t$node = $price_nodes->item( 0 );
\t\t\t\t$del  = $xpath->query( './/del', $node );
\t\t\t\t$ins  = $xpath->query( './/ins', $node );
\t\t\t\tif ( $del && $del->length && $ins && $ins->length ) {
\t\t\t\t\t$regular = self::first_price_in_text( (string) $del->item( 0 )->textContent );
\t\t\t\t\t$current = self::first_price_in_text( (string) $ins->item( 0 )->textContent );
\t\t\t\t\tif ( null !== $regular && null !== $current && $current < $regular ) {
\t\t\t\t\t\treturn array( 'current' => $current, 'regular' => $regular );
\t\t\t\t\t}
\t\t\t\t}

\t\t\t\t$node_text = trim( preg_replace( '/\\s+/u', ' ', (string) $node->textContent ) );
\t\t\t\tif ( preg_match( '/precio original era:\\s*([0-9.,]+)\\s*€.*?([0-9.,]+)\\s*€\\s*el precio actual es/iu', $node_text, $match ) ) {
\t\t\t\t\t$regular = self::parse_price( $match[1] );
\t\t\t\t\t$current = self::parse_price( $match[2] );
\t\t\t\t\tif ( null !== $regular && null !== $current && $current < $regular ) {
\t\t\t\t\t\treturn array( 'current' => $current, 'regular' => $regular );
\t\t\t\t\t}
\t\t\t\t}

\t\t\t\t$current = self::first_price_in_text( $node_text );
\t\t\t\tif ( null !== $current && null === $fallback_current ) {
\t\t\t\t\t$fallback_current = $current;
\t\t\t\t}
\t\t\t}
\t\t\treturn array( 'current' => $fallback_current, 'regular' => $fallback_current );
\t\t}
'''
text = text[:start] + replacement + text[end:]

old = """\t\t\t\tif (\n\t\t\t\t\tnull === $regular\n\t\t\t\t\t&& null !== $current\n\t\t\t\t\t&& null !== $detected_current\n\t\t\t\t\t&& null !== $detected_regular\n\t\t\t\t\t&& abs( $current - $detected_current ) < 0.005\n\t\t\t\t\t&& $detected_regular > $current\n\t\t\t\t) {\n\t\t\t\t\t$regular = $detected_regular;\n\t\t\t\t}\n"""
new = """\t\t\t\tif (\n\t\t\t\t\tnull !== $current\n\t\t\t\t\t&& null !== $detected_current\n\t\t\t\t\t&& null !== $detected_regular\n\t\t\t\t\t&& abs( $current - $detected_current ) < 0.005\n\t\t\t\t\t&& $detected_regular > $current\n\t\t\t\t\t&& ( null === $regular || $regular <= $current + 0.005 )\n\t\t\t\t) {\n\t\t\t\t\t$regular = $detected_regular;\n\t\t\t\t\t$sale    = $current;\n\t\t\t\t}\n"""
if old not in text:
    raise SystemExit('Pricing enrichment target not found')
pricing.write_text(text.replace(old, new, 1))


# 2) Woo importer: general source-unavailable state + automatic restoration.
importer = Path('mdo-supplier-sync/includes/class-mdo-woo-importer.php')
text = importer.read_text()
marker = "\n\tpublic static function exclude_source_product( int $source_product_id ): void {"
pos = text.find(marker)
if pos < 0:
    raise SystemExit('Importer insertion point not found')
methods = '''
\t/**
\t * Retira de la venta un producto que ya no aparece en el catálogo de origen.
\t * Conservamos la relación EMDO para poder restaurarlo automáticamente si
\t * vuelve a aparecer en una sincronización posterior.
\t */
\tpublic static function mark_source_unavailable( int $source_product_id ): bool {
\t\tglobal $wpdb;
\t\t$table = MDO_Database::table( 'source_products' );
\t\t$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id,status,wc_product_id,title FROM {$table} WHERE id = %d", $source_product_id ), ARRAY_A );
\t\tif ( ! $row || in_array( (string) $row['status'], array( 'excluded', 'unavailable' ), true ) ) {
\t\t\treturn false;
\t\t}

\t\t$wpdb->update(
\t\t\t$table,
\t\t\tarray(
\t\t\t\t'status'              => 'unavailable',
\t\t\t\t'source_stock_status' => 'outofstock',
\t\t\t\t'last_error'          => 'No encontrado en el catálogo de origen durante la última sincronización completa.',
\t\t\t),
\t\t\tarray( 'id' => $source_product_id )
\t\t);

\t\t$product_id = ! empty( $row['wc_product_id'] ) ? (int) $row['wc_product_id'] : 0;
\t\tif ( $product_id ) {
\t\t\t$product = wc_get_product( $product_id );
\t\t\tif ( $product ) {
\t\t\t\t$product->set_stock_status( 'outofstock' );
\t\t\t\t$product->set_catalog_visibility( 'hidden' );
\t\t\t\t$product->set_status( 'draft' );
\t\t\t\t$product->save();
\t\t\t\tupdate_post_meta( $product_id, '_emdo_source_unavailable', '1' );
\t\t\t}
\t\t}
\t\treturn true;
\t}

\tpublic static function mark_source_url_unavailable( int $supplier_id, string $source_url ): bool {
\t\tglobal $wpdb;
\t\t$table = MDO_Database::table( 'source_products' );
\t\t$id    = (int) $wpdb->get_var(
\t\t\t$wpdb->prepare(
\t\t\t\t"SELECT id FROM {$table} WHERE supplier_id = %d AND source_url = %s ORDER BY id DESC LIMIT 1",
\t\t\t\t$supplier_id,
\t\t\t\t$source_url
\t\t\t)
\t\t);
\t\treturn $id > 0 ? self::mark_source_unavailable( $id ) : false;
\t}

\t/**
\t * Si un producto marcado como no disponible reaparece, el payload ya ha sido
\t * actualizado por el conector. Lo reimportamos para recuperar publicación,
\t * visibilidad, precio y stock exactamente desde la fuente.
\t */
\tpublic static function restore_if_unavailable( int $supplier_id, string $source_url ): bool {
\t\tglobal $wpdb;
\t\t$table = MDO_Database::table( 'source_products' );
\t\t$row   = $wpdb->get_row(
\t\t\t$wpdb->prepare(
\t\t\t\t"SELECT id,status,wc_product_id FROM {$table} WHERE supplier_id = %d AND source_url = %s ORDER BY id DESC LIMIT 1",
\t\t\t\t$supplier_id,
\t\t\t\t$source_url
\t\t\t),
\t\t\tARRAY_A
\t\t);
\t\tif ( ! $row || 'unavailable' !== (string) $row['status'] ) {
\t\t\treturn false;
\t\t}

\t\tif ( ! empty( $row['wc_product_id'] ) ) {
\t\t\tself::import_source_product( (int) $row['id'] );
\t\t\tdelete_post_meta( (int) $row['wc_product_id'], '_emdo_source_unavailable' );
\t\t} else {
\t\t\t$wpdb->update( $table, array( 'status' => 'pending', 'last_error' => null ), array( 'id' => (int) $row['id'] ) );
\t\t}
\t\treturn true;
\t}
'''
importer.write_text(text[:pos] + methods + text[pos:])


# 3) Scheduler: reconcile a completed discovery against active source rows,
# and immediately withdraw a known URL when the source returns 404/410.
scheduler = Path('mdo-supplier-sync/includes/class-mdo-scheduler.php')
text = scheduler.read_text()
old = """\t\t\t$result  = $connector::upsert_product( $supplier_id, $product );\n\t\t\tself::increment_run( $run_id, $result );\n\n\t\t\tif ( 'updated' === $result ) {\n\t\t\t\ttry {\n\t\t\t\t\tMDO_Woo_Importer::sync_if_active( $supplier_id, (string) $product['source_url'] );\n\t\t\t\t} catch ( Throwable $sync_error ) {\n"""
new = """\t\t\t$result   = $connector::upsert_product( $supplier_id, $product );\n\t\t\t$restored = MDO_Woo_Importer::restore_if_unavailable( $supplier_id, (string) $product['source_url'] );\n\t\t\tif ( $restored && 'unchanged' === $result ) {\n\t\t\t\t$result = 'updated';\n\t\t\t}\n\t\t\tself::increment_run( $run_id, $result );\n\n\t\t\tif ( 'updated' === $result && ! $restored ) {\n\t\t\t\ttry {\n\t\t\t\t\tMDO_Woo_Importer::sync_if_active( $supplier_id, (string) $product['source_url'] );\n\t\t\t\t} catch ( Throwable $sync_error ) {\n"""
if old not in text:
    raise SystemExit('Scheduler restore target not found')
text = text.replace(old, new, 1)

old = """\t\t} catch ( Throwable $error ) {\n\t\t\tself::increment_run( $run_id, 'error' );\n\t\t\tself::log_event( $run_id, $supplier_id, 'product_error', 'error', $error->getMessage(), array( 'url' => $url ) );\n\t\t}\n\t\tself::finish_if_complete( $run_id, $supplier, $trigger_type );\n"""
new = """\t\t} catch ( Throwable $error ) {\n\t\t\tself::increment_run( $run_id, 'error' );\n\t\t\tif ( preg_match( '/\\bHTTP\\s+(404|410)\\b/i', $error->getMessage() ) && MDO_Woo_Importer::mark_source_url_unavailable( $supplier_id, $url ) ) {\n\t\t\t\tself::log_event( $run_id, $supplier_id, 'product_unavailable', 'warning', 'Retirado de la venta: la ficha de origen devuelve ' . $error->getMessage(), array( 'url' => $url ) );\n\t\t\t}\n\t\t\tself::log_event( $run_id, $supplier_id, 'product_error', 'error', $error->getMessage(), array( 'url' => $url ) );\n\t\t}\n\t\tself::finish_if_complete( $run_id, $supplier, $trigger_type );\n"""
if old not in text:
    raise SystemExit('Scheduler error target not found')
text = text.replace(old, new, 1)

old = """\t\t$status  = (int) $run['errors_count'] > 0 ? 'warning' : 'success';\n\t\t$message = sprintf(\n\t\t\t'Análisis completado: %d encontrados, %d nuevos, %d modificados, %d excluidos y %d errores.',\n\t\t\t(int) $run['products_found'],\n\t\t\t(int) $run['products_new'],\n\t\t\t(int) $run['products_updated'],\n\t\t\t(int) $run['products_excluded'],\n\t\t\t(int) $run['errors_count']\n\t\t);\n"""
new = """\t\t$unavailable = self::reconcile_missing_products( $run_id, $supplier, $run );\n\t\t$status      = (int) $run['errors_count'] > 0 ? 'warning' : 'success';\n\t\t$message     = sprintf(\n\t\t\t'Análisis completado: %d encontrados, %d nuevos, %d modificados, %d excluidos, %d retirados del origen y %d errores.',\n\t\t\t(int) $run['products_found'],\n\t\t\t(int) $run['products_new'],\n\t\t\t(int) $run['products_updated'],\n\t\t\t(int) $run['products_excluded'],\n\t\t\t$unavailable,\n\t\t\t(int) $run['errors_count']\n\t\t);\n"""
if old not in text:
    raise SystemExit('Scheduler finish target not found')
text = text.replace(old, new, 1)

marker = "\n\tprivate static function connector_class( array $supplier ): ?string {"
pos = text.find(marker)
if pos < 0:
    raise SystemExit('Scheduler helper insertion point not found')
helper = '''
\t/**
\t * Al cerrar una sincronización completa, cualquier producto activo que no
\t * haya generado un evento de descubrimiento en este run ya no forma parte
\t * del catálogo de origen. Se retira de la venta, pero se conserva para poder
\t * restaurarlo automáticamente si reaparece.
\t */
\tprivate static function reconcile_missing_products( int $run_id, array $supplier, array $run ): int {
\t\tif ( (int) ( $run['products_found'] ?? 0 ) <= 0 ) {
\t\t\tself::log_event( $run_id, (int) $supplier['id'], 'catalog_empty_guard', 'warning', 'El catálogo devolvió 0 productos; se omite la retirada automática por seguridad.' );
\t\t\treturn 0;
\t\t}

\t\tglobal $wpdb;
\t\t$events_table   = MDO_Database::table( 'sync_events' );
\t\t$products_table = MDO_Database::table( 'source_products' );
\t\t$payloads       = $wpdb->get_col(
\t\t\t$wpdb->prepare(
\t\t\t\t"SELECT payload FROM {$events_table} WHERE run_id = %d AND event_type IN ('product_new','product_updated','product_unchanged','product_excluded','product_error')",
\t\t\t\t$run_id
\t\t\t)
\t\t) ?: array();
\t\t$seen = array();
\t\tforeach ( $payloads as $payload_json ) {
\t\t\t$payload = json_decode( (string) $payload_json, true );
\t\t\t$url     = is_array( $payload ) ? esc_url_raw( (string) ( $payload['url'] ?? '' ) ) : '';
\t\t\tif ( $url ) {
\t\t\t\t$seen[ rtrim( $url, '/' ) ] = true;
\t\t\t}
\t\t}

\t\t$rows = $wpdb->get_results(
\t\t\t$wpdb->prepare(
\t\t\t\t"SELECT id,title,source_url,wc_product_id FROM {$products_table} WHERE supplier_id = %d AND status = 'active'",
\t\t\t\t(int) $supplier['id']
\t\t\t),
\t\t\tARRAY_A
\t\t) ?: array();
\t\t$count = 0;
\t\tforeach ( $rows as $row ) {
\t\t\t$url = rtrim( esc_url_raw( (string) $row['source_url'] ), '/' );
\t\t\tif ( '' !== $url && isset( $seen[ $url ] ) ) {
\t\t\t\tcontinue;
\t\t\t}
\t\t\tif ( MDO_Woo_Importer::mark_source_unavailable( (int) $row['id'] ) ) {
\t\t\t\t$count++;
\t\t\t\tself::log_event(
\t\t\t\t\t$run_id,
\t\t\t\t\t(int) $supplier['id'],
\t\t\t\t\t'product_unavailable',
\t\t\t\t\t'warning',
\t\t\t\t\t'Retirado de la venta porque ya no aparece en el catálogo de origen: ' . (string) $row['title'],
\t\t\t\t\tarray( 'url' => (string) $row['source_url'], 'source_product_id' => (int) $row['id'], 'wc_product_id' => (int) $row['wc_product_id'] )
\t\t\t\t);
\t\t\t}
\t\t}
\t\treturn $count;
\t}
'''
scheduler.write_text(text[:pos] + helper + text[pos:])


# 4) Version bump.
main = Path('mdo-supplier-sync/mdo-supplier-sync.php')
text = main.read_text()
if 'Version: 1.0.12' not in text or "MDO_SUPPLIER_SYNC_VERSION', '1.0.12'" not in text:
    raise SystemExit('Expected 1.0.12 markers not found')
text = text.replace('Version: 1.0.12', 'Version: 1.0.13', 1)
text = text.replace("MDO_SUPPLIER_SYNC_VERSION', '1.0.12'", "MDO_SUPPLIER_SYNC_VERSION', '1.0.13'", 1)
main.write_text(text)
