from pathlib import Path
import re

root = Path('.')
connector = root / 'mdo-supplier-sync/connectors/class-mdo-connector-huerta-ana-mary-v3.php'
plugin = root / 'mdo-supplier-sync/mdo-supplier-sync.php'
deploy = root / '.github/workflows/deploy-mdo-supplier-sync-production-20260819.yml'
marker = root / 'mdo-supplier-sync/deploy-version.txt'

text = connector.read_text(encoding='utf-8')

old = """\t\t$price = self::price_from_json( $json );\n\t\tif ( null === $price ) {\n\t\t\t$price = self::price_from_html( $xpath );\n\t\t}\n\t\tif ( null === $price || $price <= 0 ) {\n\t\t\tthrow new RuntimeException( 'No se pudo detectar un precio válido para el producto.' );\n\t\t}\n"""
new = """\t\t$price = self::price_from_json( $json );\n\t\tif ( null === $price ) {\n\t\t\t$price = self::price_from_html( $xpath );\n\t\t}\n\t\t// Algunas fichas antiguas (p. ej. cajas de 20 kg y bolsa de Padrón)\n\t\t// no imprimen el precio con la misma estructura. Como respaldo, lo leemos\n\t\t// dinámicamente de la tarjeta del propio catálogo, nunca de un valor fijo.\n\t\tif ( null === $price ) {\n\t\t\t$price = self::price_from_catalog( $url );\n\t\t}\n\t\tif ( null === $price || $price <= 0 ) {\n\t\t\tthrow new RuntimeException( 'No se pudo detectar un precio válido para el producto.' );\n\t\t}\n"""
if old not in text:
    raise SystemExit('price block not found')
text = text.replace(old, new, 1)

anchor = "\tprivate static function parse_price( string $raw ): ?float {"
method = r'''\tprivate static function price_from_catalog( string $product_url ): ?float {
\t\t$parts = wp_parse_url( $product_url );
\t\tif ( empty( $parts['host'] ) ) {
\t\t\treturn null;
\t\t}
\t\t$root = ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . '/hortalizas-y-conservas';
\t\t$target = self::canonical_url( $product_url );
\t\tfor ( $page = 1; $page <= 10; $page++ ) {
\t\t\t$catalog_url = 1 === $page ? $root : $root . '/' . $page;
\t\t\ttry {
\t\t\t\t$xp = self::xpath( self::fetch_html( $catalog_url ) );
\t\t\t} catch ( Throwable $error ) {
\t\t\t\tcontinue;
\t\t\t}
\t\t\tforeach ( $xp->query( '//a[@href]' ) ?: array() as $link ) {
\t\t\t\tif ( ! $link instanceof DOMElement ) continue;
\t\t\t\t$href = self::absolute_url( (string) $link->getAttribute( 'href' ), $catalog_url );
\t\t\t\tif ( self::canonical_url( $href ) !== $target ) continue;
\t\t\t\t$node = $link;
\t\t\t\tfor ( $i = 0; $i < 7 && $node->parentNode instanceof DOMElement; $i++ ) {
\t\t\t\t\t$node = $node->parentNode;
\t\t\t\t\t$txt = self::repair_text( (string) $node->textContent );
\t\t\t\t\tif ( mb_strlen( $txt ) > 2500 ) break;
\t\t\t\t\tif ( preg_match_all( '/(\\d{1,4}(?:[.,]\\d{1,2})?)\\s*€/u', $txt, $m ) ) {
\t\t\t\t\t\t$vals = array_values( array_unique( array_map( 'strval', $m[1] ) ) );
\t\t\t\t\t\tif ( 1 === count( $vals ) ) return self::parse_price( $vals[0] );
\t\t\t\t\t}
\t\t\t\t}
\t\t\t}
\t\t}
\t\treturn null;
\t}

'''
method = method.replace('\\t', '\t')
if anchor not in text:
    raise SystemExit('parse_price anchor not found')
text = text.replace(anchor, method + anchor, 1)

# Flores: la ficha tiene el título corrupto, así que el fallback basado en el título limpio
# puede quedarse sin descripción. Extraemos solo el texto comercial antes de "Te interesa".
old_return = "\t\treturn '';\n\t}\n\n\tprivate static function clean_description_text"
flower = """\t\tif ( 'Flores de calabacín 8 unidades' === $title ) {\n\t\t\t$body = self::repair_text( (string) ( $xpath->query( '//body' )->item( 0 )->textContent ?? '' ) );\n\t\t\tif ( preg_match( '/(Te ofrecemos una caja con 8 delicadas flores de calabac[ií]n.*?)(?:Te interesa|Producto de Fresno)/isu', $body, $match ) ) {\n\t\t\t\t$clean = trim( preg_replace( '/\\s+/u', ' ', $match[1] ) );\n\t\t\t\tif ( mb_strlen( $clean ) >= 40 ) return wpautop( esc_html( $clean ) );\n\t\t\t}\n\t\t}\n\t\treturn '';\n\t}\n\n\tprivate static function clean_description_text"""
if old_return not in text:
    raise SystemExit('description return anchor not found')
text = text.replace(old_return, flower, 1)
connector.write_text(text, encoding='utf-8')

p = plugin.read_text(encoding='utf-8')
p = p.replace('Version: 1.0.16', 'Version: 1.0.17').replace("MDO_SUPPLIER_SYNC_VERSION', '1.0.16'", "MDO_SUPPLIER_SYNC_VERSION', '1.0.17'")
plugin.write_text(p, encoding='utf-8')

d = deploy.read_text(encoding='utf-8').replace('1.0.16', '1.0.17')
deploy.write_text(d, encoding='utf-8')
marker.write_text('MDO Supplier Sync 1.0.17\nProduction deployment trigger: 2026-08-19\nConnector: La Huerta de Ana Mary\n', encoding='utf-8')
