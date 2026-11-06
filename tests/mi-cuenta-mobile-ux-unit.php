<?php
/** Source and behavior regression for responsive WooCommerce Mi cuenta. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
$GLOBALS['emdo_test_account'] = true;
$GLOBALS['emdo_test_endpoint'] = true;
$GLOBALS['emdo_test_hooks'] = array();
function add_filter( string $name, $callback, int $priority=10, int $accepted_args=1 ): void {
	$GLOBALS['emdo_test_hooks'][$name][] = $callback;
}
function add_action( string $name, $callback, int $priority=10, int $accepted_args=1 ): void {
	$GLOBALS['emdo_test_hooks'][$name][] = $callback;
}
function is_account_page(): bool { return $GLOBALS['emdo_test_account']; }
function is_wc_endpoint_url( string $name ): bool { return $name==='view-order' && $GLOBALS['emdo_test_endpoint']; }
function wc_get_account_endpoint_url( string $endpoint ): string { return 'https://example.test/mi-cuenta/'.$endpoint.'/'; }
function esc_url( string $url ): string { return $url; }
function wp_register_style() {}
function wp_enqueue_style() {}
function wp_add_inline_style() {}
function wp_kses_post($v) {return $v;}
function wp_strip_all_tags($v) {return strip_tags($v);}
function is_user_logged_in(): bool {return false;}
function get_option($key,$default=null) { return $default; }
function home_url($path) {return 'https://example.test'.$path;}
function wp_get_current_user() {return null;}

require dirname(__DIR__).'/mu-plugins/mdo-my-account-premium-20261105.php';
$assert = static function( bool $valid, string $message ): void {
	if(!$valid) {throw new RuntimeException($message);}
};
$has = static function( string $hook, string $needle ): bool {
	foreach ($GLOBALS['emdo_test_hooks'][$hook] ?? [] as $callback) {
		if (!is_callable($callback)) continue;
		if ($needle==='') return true;
	}
	return false;
};
$assert($has('wp_enqueue_scripts',''),'Scoped stylesheet is enqueued');
$assert($has('woocommerce_account_menu_items',''),'Woo menu filters registered');
$assert($has('woocommerce_account_orders_columns',''),'Order list columns translated');
$assert($has('wp',''),'Translations are registered only after WordPress routing');
$assert(!$has('gettext',''),'No global translation filters during plugin bootstrap');
$GLOBALS['emdo_test_account']=false;
foreach ($GLOBALS['emdo_test_hooks']['wp'] as $cb) { $cb(); }
$assert(!$has('gettext',''),'Non-account pages have no custom gettext filter');
$GLOBALS['emdo_test_account']=true;
foreach ($GLOBALS['emdo_test_hooks']['wp'] as $cb) { $cb(); }
$assert($has('gettext',''),'WooCommerce translations registered only on account page');
$assert($has('woocommerce_order_details_before_order_table',''),'Return navigation on order details');
foreach($GLOBALS['emdo_test_hooks']['gettext'] as $cb){
	$result=$cb('Order details','Order details','woocommerce');
	if($result==='Detalles del pedido') {
		$assert($cb('A product name','A product name','woocommerce')==='A product name','No user product name modifications');
		$assert($cb('Order #%1$s was placed on %2$s and is currently %3$s.','Order #%1$s was placed on %2$s and is currently %3$s.','woocommerce')===
			'El pedido n.º %1$s se realizó el %2$s y su estado actual es %3$s.','Order status placeholders preserved');
		$assert($cb('Order details','Order details','another-plugin')==='Order details','Third-party domain unchanged');
		$assert($cb('Detalles del pedido','Order details','woocommerce')==='Detalles del pedido','Existing Spanish translations preserved');
		// Registered only after wp resolves a specific account route.
		$translated=true;
		break;
	}
}
$assert(!empty($translated),'WooCommerce English order texts translated');
$cols=['order-number'=>'Order','order-date'=>'Date','order-status'=>'Status','order-total'=>'Total','order-actions'=>'Actions','custom'=>'Custom'];
foreach($GLOBALS['emdo_test_hooks']['woocommerce_account_orders_columns'] as $cb){$cols=$cb($cols);}
$assert($cols['order-number']==='Pedido' && $cols['order-status']==='Estado' && $cols['order-actions']==='Acciones','Order list labels Spanish');
$assert($cols['custom']==='Custom','Custom extensions preserved');
$items=['dashboard'=>'Dashboard','orders'=>'Orders','downloads'=>'Downloads','payment-methods'=>'Payment methods','customer-logout'=>'Log out','other-plugin'=>'Other'];
foreach($GLOBALS['emdo_test_hooks']['woocommerce_account_menu_items'] as $cb){$items=$cb($items);}
$assert($items['dashboard']==='Inicio' && $items['orders']==='Mis pedidos' && $items['payment-methods']==='Métodos de pago','Account menu Spanish');
$assert($items['other-plugin']==='Other','Unrelated plugin endpoint remains');
ob_start();
foreach($GLOBALS['emdo_test_hooks']['woocommerce_order_details_before_order_table'] as $cb){$cb(null);}
$html=(string)ob_get_clean();
$assert(str_contains($html,'Volver a mis pedidos') && str_contains($html,'/mi-cuenta/orders/'),'Back to orders link present on account view-order');
$GLOBALS['emdo_test_endpoint']=false;
ob_start();
foreach($GLOBALS['emdo_test_hooks']['woocommerce_order_details_before_order_table'] as $cb){$cb(null);}
$assert(''===ob_get_clean(),'No back navigation on other order pages');
$assert(isset($GLOBALS['emdo_test_hooks']['wp_footer']), 'Custom note translation is scoped to WordPress footer');
ob_start();
foreach ($GLOBALS['emdo_test_hooks']['wp_footer'] as $cb) { $cb(); }
$assert('' === ob_get_clean(), 'No note translation script on other account pages');
$GLOBALS['emdo_test_endpoint']=true;
ob_start();
foreach ($GLOBALS['emdo_test_hooks']['wp_footer'] as $cb) { $cb(); }
$note_script=(string)ob_get_clean();
$assert(str_contains($note_script,'emdo-account-order-note-es'), 'Custom order notes script loaded only on order details');
foreach ([
	'Añadir una nota al pedido','Mensaje','Adjuntar archivo (opcional)','Enviar nota',
	'textarea[name="wcfm_cus_add_note"]', 'wcfm_cus_add_note_file','wcfm_cus_note_submit',
] as $expected) {
	$assert(str_contains($note_script,$expected),'Missing custom note translation: '.$expected);
}
$GLOBALS['emdo_test_account']=false;
ob_start();
foreach ($GLOBALS['emdo_test_hooks']['wp_footer'] as $cb) { $cb(); }
$assert('' === ob_get_clean(), 'No custom notes script outside Mi cuenta');
$GLOBALS['emdo_test_account']=true;


$src=file_get_contents(dirname(__DIR__).'/mu-plugins/mdo-my-account-premium-20261105.php');
$assert((bool)preg_match("/<<<'CSS'\n([\s\S]*?)\nCSS\n/", $src, $match),'Inline CSS extracted');
$css=$match[1];
$checks = [
	'grid-template-columns:repeat(2,minmax(0,1fr))!important' => 'Mobile menu is two-column grid',
	'overflow:visible!important' => 'Account menu does not horizontally scroll',
	'.woocommerce-orders-table tbody tr' => 'Orders are readable mobile cards',
	'.woocommerce-orders-table tbody td::before' => 'Card labels from Woo data-title',
	'.woocommerce-table--order-details tbody tr' => 'Order detail items stacked in mobile grid',
	'.woocommerce-table--order-details tfoot tr' => 'Order totals in readable rows',
	'.woocommerce-customer-details .woocommerce-columns' => 'Addresses stacked on mobile',
	'.woocommerce-MyAccount-downloads' => 'Downloads remain accessible',
	'.mdo-club-columns' => 'Wallet cards remain responsive',
	'word-break:normal!important' => 'Text never forced into vertical word-breaking',
	'white-space:nowrap!important' => 'Amounts and currency kept on same line',
	'@media(max-width:400px)' => '320px devices get single-column order item row',
	'@media(max-width:1023px)' => 'Tablet account sidebar collapses above content',
	'@media(prefers-reduced-motion:reduce)' => 'Accessible motion preferences maintained',
];
foreach($checks as $needle=>$explanation) {$assert(str_contains($css,$needle),$explanation);}
$assert(str_contains($css,'form:has(textarea[name="wcfm_cus_add_note"])'), 'Custom note form layout is scoped by the exact field');
$assert(str_contains($css,'input[type="file"]'), 'Note attachment input has responsive styling');
$assert(str_contains($css,'input[type="submit"]'), 'Note submit button remains legible');
$assert(!str_contains($css, 'scroll-snap-type:x proximity'), 'Old horizontal menu scrolling removed');
$assert(!str_contains($css, '.woocommerce-orders-table {display:block;overflow-x:auto;'), 'Orders never forced to horizontal scrolling');
echo "EMDO Mi cuenta mobile, orders and Spanish labels: OK\n";
