from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    file_path = Path(path)
    text = file_path.read_text(encoding="utf-8")
    if old not in text:
        raise SystemExit(f"Expected block not found in {path}: {old[:100]!r}")
    if text.count(old) != 1:
        raise SystemExit(f"Expected block is not unique in {path}: found {text.count(old)} matches")
    file_path.write_text(text.replace(old, new, 1), encoding="utf-8")


repository = "mdo-supplier-sync/includes/class-mdo-supplier-repository.php"
replace_once(
    repository,
    "\t\t\t'source_url'              => esc_url_raw( trim( (string) ( $data['source_url'] ?? '' ) ) ),",
    "\t\t\t'source_url'              => self::normalize_source_urls( (string) ( $data['source_url'] ?? '' ) ),",
)
replace_once(
    repository,
    "\tpublic static function fragments_as_text( ?string $json ): string {\n\t\t$items = json_decode( (string) $json, true );\n\t\tif ( ! is_array( $items ) ) {\n\t\t\treturn '';\n\t\t}\n\t\treturn implode( \"\\n\", array_map( 'strval', $items ) );\n\t}\n\n\tprivate static function normalize_fragments( string $raw ): string {",
    "\tpublic static function fragments_as_text( ?string $json ): string {\n\t\t$items = json_decode( (string) $json, true );\n\t\tif ( ! is_array( $items ) ) {\n\t\t\treturn '';\n\t\t}\n\t\treturn implode( \"\\n\", array_map( 'strval', $items ) );\n\t}\n\n\tpublic static function source_urls( ?string $raw ): array {\n\t\t$lines = preg_split( '/\\R+/', (string) $raw ) ?: array();\n\t\t$urls  = array();\n\t\tforeach ( $lines as $line ) {\n\t\t\t$url = esc_url_raw( trim( (string) $line ) );\n\t\t\tif ( $url ) {\n\t\t\t\t$urls[ $url ] = $url;\n\t\t\t}\n\t\t}\n\t\treturn array_values( $urls );\n\t}\n\n\tprivate static function normalize_source_urls( string $raw ): string {\n\t\treturn implode( \"\\n\", self::source_urls( $raw ) );\n\t}\n\n\tprivate static function normalize_fragments( string $raw ): string {",
)

admin = "mdo-supplier-sync/includes/class-mdo-admin.php"
replace_once(
    admin,
    "\t\t\t\t\t<td><a href=\"<?php echo esc_url( $supplier['source_url'] ); ?>\" target=\"_blank\" rel=\"noopener\">Abrir web</a></td>",
    "\t\t\t\t\t<?php $source_urls = MDO_Supplier_Repository::source_urls( (string) $supplier['source_url'] ); ?>\n\t\t\t\t\t<td><?php if ( $source_urls ) : ?><a href=\"<?php echo esc_url( $source_urls[0] ); ?>\" target=\"_blank\" rel=\"noopener\">Abrir web</a><?php else : ?>—<?php endif; ?><?php if ( count( $source_urls ) > 1 ) : ?><br><small><?php echo esc_html( sprintf( '%d URLs configuradas', count( $source_urls ) ) ); ?></small><?php endif; ?></td>",
)
replace_once(
    admin,
    "\t\t\t\t<label class=\"mdo-span-2\"><span>URL de la tienda / catálogo</span><input class=\"large-text\" type=\"url\" name=\"source_url\" required value=\"<?php echo esc_attr( $supplier['source_url'] ); ?>\"></label>",
    "\t\t\t\t<label class=\"mdo-span-2\"><span>URLs de la tienda / catálogo (una por línea)</span><textarea class=\"large-text code\" name=\"source_url\" rows=\"5\" required><?php echo esc_textarea( $supplier['source_url'] ); ?></textarea><small>El sincronizador recorrerá todas las URLs indicadas y unificará los productos encontrados. Si solo indicas una, funcionará como hasta ahora.</small></label>",
)

scheduler = "mdo-supplier-sync/includes/class-mdo-scheduler.php"
replace_once(
    scheduler,
    "\t\t\t$discovery = $connector::discover( $supplier );\n\t\t\t$products  = $discovery['products'] ?? array();\n\t\t\t$excluded  = $discovery['excluded'] ?? array();\n\t\t\t$total     = count( $products ) + count( $excluded );",
    "\t\t\t$source_urls = MDO_Supplier_Repository::source_urls( (string) $supplier['source_url'] );\n\t\t\tif ( ! $source_urls ) {\n\t\t\t\tthrow new RuntimeException( 'El proveedor no tiene ninguna URL de catálogo válida.' );\n\t\t\t}\n\n\t\t\t$products_by_url      = array();\n\t\t\t$excluded_by_url      = array();\n\t\t\t$explicit_source_urls = count( $source_urls ) > 1;\n\n\t\t\tforeach ( $source_urls as $source_url ) {\n\t\t\t\t$source_supplier               = $supplier;\n\t\t\t\t$source_supplier['source_url'] = $source_url;\n\t\t\t\tif ( $explicit_source_urls ) {\n\t\t\t\t\t$source_supplier['_mdo_catalog_source_only'] = 1;\n\t\t\t\t}\n\n\t\t\t\t$source_discovery = $connector::discover( $source_supplier );\n\t\t\t\tforeach ( (array) ( $source_discovery['products'] ?? array() ) as $url ) {\n\t\t\t\t\t$url = esc_url_raw( (string) $url );\n\t\t\t\t\tif ( $url ) {\n\t\t\t\t\t\t$products_by_url[ $url ] = $url;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t\tforeach ( (array) ( $source_discovery['excluded'] ?? array() ) as $url ) {\n\t\t\t\t\t$url = esc_url_raw( (string) $url );\n\t\t\t\t\tif ( $url ) {\n\t\t\t\t\t\t$excluded_by_url[ $url ] = $url;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\n\t\t\t$products = array_values( $products_by_url );\n\t\t\t$excluded = array_values( $excluded_by_url );\n\t\t\t$total    = count( $products ) + count( $excluded );",
)
replace_once(
    scheduler,
    "\t\t$supplier = MDO_Supplier_Repository::find( $supplier_id );\n\t\tif ( ! $supplier ) {\n\t\t\treturn;\n\t\t}\n\t\ttry {\n\t\t\t$connector = self::connector_class( $supplier );",
    "\t\t$supplier = MDO_Supplier_Repository::find( $supplier_id );\n\t\tif ( ! $supplier ) {\n\t\t\treturn;\n\t\t}\n\t\t$source_urls = MDO_Supplier_Repository::source_urls( (string) $supplier['source_url'] );\n\t\tif ( $source_urls ) {\n\t\t\t$supplier['source_url'] = $source_urls[0];\n\t\t}\n\t\ttry {\n\t\t\t$connector = self::connector_class( $supplier );",
)

iberico = "mdo-supplier-sync/connectors/class-mdo-connector-iberico-family.php"
replace_once(
    iberico,
    "\t\t$config    = self::config( (string) $supplier['connector'], (string) $supplier['source_url'] );\n\t\t$products  = array();",
    "\t\t$config    = self::config( (string) $supplier['connector'], (string) $supplier['source_url'] );\n\t\tif ( ! empty( $supplier['_mdo_catalog_source_only'] ) ) {\n\t\t\t$config['catalog_urls'] = array( (string) $supplier['source_url'] );\n\t\t}\n\t\t$products  = array();",
)

print("multi_source_urls_patch_ok")
