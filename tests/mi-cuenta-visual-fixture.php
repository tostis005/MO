<?php
/** Synthetic customer-area layout for viewport tests, no live customer data. */
declare(strict_types=1);
$source = (string) file_get_contents( dirname( __DIR__ ) . '/mu-plugins/mdo-my-account-premium-20261105.php' );
if ( ! preg_match( "/<<<'CSS'\n([\s\S]*?)\nCSS\n/", $source, $matches ) ) {
	throw new RuntimeException( 'Cannot extract scoped account stylesheet' );
}
$css = $matches[1];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prueba de diseño de Mi cuenta</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;color:#253c31;background:#f5f7f3}
.site-content{max-width:1180px;margin:auto;padding:20px 12px;}
.site-content .woocommerce{width:100%;margin:0 auto}
a{color:#24513a}h1{font-size:30px}h2{font-size:25px}
.woocommerce .woocommerce-MyAccount-navigation{width:25%;float:left}
.woocommerce .woocommerce-MyAccount-content{width:70%;float:right}
.woocommerce-MyAccount-navigation ul{margin:0;padding:0;list-style:none}
.woocommerce-MyAccount-navigation li{margin:0;padding:0}
.woocommerce-MyAccount-navigation li a{display:block;padding:10px}
.woocommerce-MyAccount-content table{border-spacing:0;width:100%;table-layout:fixed}
.woocommerce-MyAccount-content table td,.woocommerce-MyAccount-content table th{padding:10px;border-bottom:1px solid #e4e4e4}
.woocommerce-order-details{margin:22px 0}
.woocommerce-customer-details .woocommerce-columns{display:flex;gap:16px}
.woocommerce-customer-details .woocommerce-column{width:50%}
</style>
<style><?php echo $css; ?></style></head>
<body class="woocommerce-account logged-in">
<main class="site-content"><h1>Mi cuenta</h1>
<div class="woocommerce">
<nav class="woocommerce-MyAccount-navigation" aria-label="Mi cuenta">
<ul>
<li class="woocommerce-MyAccount-navigation-link is-active"><a href="#">Inicio</a></li>
<li class="woocommerce-MyAccount-navigation-link"><a href="#">Mis pedidos</a></li>
<li class="woocommerce-MyAccount-navigation-link"><a href="#">Club EMDO</a></li>
<li class="woocommerce-MyAccount-navigation-link"><a href="#">Direcciones</a></li>
<li class="woocommerce-MyAccount-navigation-link"><a href="#">Datos personales</a></li>
<li class="woocommerce-MyAccount-navigation-link"><a href="#">Métodos de pago</a></li>
<li class="woocommerce-MyAccount-navigation-link"><a href="#">Descargas</a></li>
<li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--customer-logout"><a href="#">Cerrar sesión</a></li>
</ul></nav>
<div class="woocommerce-MyAccount-content">
<h2>Mis pedidos</h2>
<table class="woocommerce-orders-table shop_table"><thead><tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Total</th><th>Acciones</th></tr></thead>
<tbody><tr>
<td class="woocommerce-orders-table__cell-order-number" data-title="Pedido"><a href="#">#50312</a></td>
<td data-title="Fecha">6 de noviembre de 2026</td>
<td data-title="Estado">En preparación</td>
<td data-title="Total"><span class="woocommerce-Price-amount"><bdi>85,50<span class="woocommerce-Price-currencySymbol">€</span></bdi></span></td>
<td class="woocommerce-orders-table__cell-order-actions" data-title="Acciones"><a class="button" href="#">Ver pedido</a></td>
</tr></tbody></table>
<section class="woocommerce-order-details">
<h2>Detalles del pedido</h2>
<table class="woocommerce-table--order-details shop_table"><thead><tr><th>Producto</th><th>Total</th></tr></thead>
<tbody><tr><td class="woocommerce-table__product-name product-name"><a href="#">Jamón ibérico de bellota de la dehesa extremeña — lote de degustación</a><ul class="wc-item-meta"><li><strong class="wc-item-meta-label">Variedad:</strong><p>Ibérico de bellota</p></li></ul></td><td class="product-total"><span class="woocommerce-Price-amount"><bdi>78,00<span class="woocommerce-Price-currencySymbol">€</span></bdi></span></td></tr></tbody>
<tfoot>
<tr><th>Subtotal:</th><td>78,00 €</td></tr>
<tr><th>Envío:</th><td>7,50 €</td></tr>
<tr><th>Forma de pago:</th><td>Transferencia bancaria</td></tr>
<tr><th>Total:</th><td><strong>85,50 €</strong></td></tr>
</tfoot></table></section>
<h2>Add your note</h2>
<form action="" method="POST" enctype="multipart/form-data">
<table class="woocommerce-table"><tbody>
<tr><th>Add Note</th><td><textarea name="wcfm_cus_add_note" id="wcfm_cus_add_note"></textarea></td></tr>
<tr><th>Upload File</th><td><input type="file" name="wcfm_cus_add_note_file" id="wcfm_cus_add_note_file"></td></tr>
<tr><td colspan="2"><input type="submit" name="wcfm_cus_note_submit" id="wcfm_cus_note_submit" value="Submit"></td></tr>
</tbody></table>
</form>
<section class="woocommerce-customer-details"><h2>Direcciones</h2><div class="woocommerce-columns">
<div class="woocommerce-column"><h3>Facturación</h3><address>Nombre de prueba<br>Calle Ejemplo, 14<br>Madrid</address></div>
<div class="woocommerce-column"><h3>Envío</h3><address>Nombre de prueba<br>Av. Ejemplo, 21<br>Madrid</address></div>
</div></section>
</div></div></main>
<?php
// Use the exact script embedded in the plugin, not a rewritten mock.
if ( ! preg_match( '~echo <<<\x27HTML\x27\s*(<script id="emdo-account-order-note-es">[\s\S]*?</script>)\s*HTML;~', $source, $note_script ) ) {
	throw new RuntimeException( 'Missing real order-note translation script' );
}
echo $note_script[1];
?>
</body></html>
