from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text(encoding="utf-8")
    if old not in text:
        raise SystemExit(f"Expected block not found in {path}: {old[:160]!r}")
    if text.count(old) != 1:
        raise SystemExit(f"Expected block is not unique in {path}: {text.count(old)} matches")
    p.write_text(text.replace(old, new, 1), encoding="utf-8")


bootstrap = "mdo-supplier-sync/mdo-supplier-sync.php"
replace_once(
    bootstrap,
    " * Version: 1.0.18",
    " * Version: 1.0.19",
)
replace_once(
    bootstrap,
    "define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.18' );",
    "define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.19' );",
)
replace_once(
    bootstrap,
    "require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-accessories-catalog.php';\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-woo-importer.php';",
    "require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-accessories-catalog.php';\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-auto-categorizer.php';\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-woo-importer.php';",
)

importer = "mdo-supplier-sync/includes/class-mdo-woo-importer.php"
replace_once(
    importer,
    "\t\tself::apply_common_fields( $product, $payload );\n\t\tif ( $is_variable ) {",
    "\t\tself::apply_common_fields( $product, $payload );\n\t\t$category_result = class_exists( 'MDO_Auto_Categorizer' )\n\t\t\t? MDO_Auto_Categorizer::maybe_assign( $product, $payload, $row )\n\t\t\t: array();\n\t\tif ( $is_variable ) {",
)
replace_once(
    importer,
    "\t\twp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );\n\n\t\tself::sync_images( $product, $payload );",
    "\t\twp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );\n\t\tif ( class_exists( 'MDO_Auto_Categorizer' ) ) {\n\t\t\tMDO_Auto_Categorizer::record_result( $product_id, $category_result );\n\t\t}\n\n\t\tself::sync_images( $product, $payload );",
)

helper = "mdo-supplier-sync/includes/class-mdo-auto-categorizer.php"
replace_once(
    helper,
    "\t\treturn array_values( array_unique( array_merge( ... ( $sets ?: array( array() ) ) ) ) );",
    "\t\t$keywords = array();\n\t\tforeach ( $sets as $set ) {\n\t\t\t$keywords = array_merge( $keywords, $set );\n\t\t}\n\t\treturn array_values( array_unique( $keywords ) );",
)

print("auto_categorization_patch_ok")
