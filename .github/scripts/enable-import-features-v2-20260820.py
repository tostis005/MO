from pathlib import Path


def ensure_replace(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text(encoding="utf-8")
    if new in text:
        return
    if old not in text:
        raise SystemExit(f"Neither expected old nor new block found in {path}: {old[:140]!r}")
    p.write_text(text.replace(old, new, 1), encoding="utf-8")


bootstrap = "mdo-supplier-sync/mdo-supplier-sync.php"
ensure_replace(bootstrap, " * Version: 1.0.18", " * Version: 1.0.19")
ensure_replace(
    bootstrap,
    "define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.18' );",
    "define( 'MDO_SUPPLIER_SYNC_VERSION', '1.0.19' );",
)
ensure_replace(
    bootstrap,
    "require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-accessories-catalog.php';\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-woo-importer.php';",
    "require_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-accessories-catalog.php';\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-auto-categorizer.php';\nrequire_once MDO_SUPPLIER_SYNC_PATH . 'includes/class-mdo-woo-importer.php';",
)

importer = "mdo-supplier-sync/includes/class-mdo-woo-importer.php"
ensure_replace(
    importer,
    "\t\tself::apply_common_fields( $product, $payload );\n\t\tif ( $is_variable ) {",
    "\t\tself::apply_common_fields( $product, $payload );\n\t\t$category_result = class_exists( 'MDO_Auto_Categorizer' )\n\t\t\t? MDO_Auto_Categorizer::maybe_assign( $product, $payload, $row )\n\t\t\t: array();\n\t\tif ( $is_variable ) {",
)
ensure_replace(
    importer,
    "\t\twp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );\n\n\t\tself::sync_images( $product, $payload );",
    "\t\twp_update_post( array( 'ID' => $product_id, 'post_author' => $vendor_id ) );\n\t\tif ( class_exists( 'MDO_Auto_Categorizer' ) ) {\n\t\t\tMDO_Auto_Categorizer::record_result( $product_id, $category_result );\n\t\t}\n\n\t\tself::sync_images( $product, $payload );",
)

helper = "mdo-supplier-sync/includes/class-mdo-auto-categorizer.php"
ensure_replace(
    helper,
    "\t\treturn array_values( array_unique( array_merge( ... ( $sets ?: array( array() ) ) ) ) );",
    "\t\t$keywords = array();\n\t\tforeach ( $sets as $set ) {\n\t\t\t$keywords = array_merge( $keywords, $set );\n\t\t}\n\t\treturn array_values( array_unique( $keywords ) );",
)

print("import_features_v2_patch_ok")
