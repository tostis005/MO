<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function emdo_cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
$theme=get_stylesheet_directory();
$module=$theme.'/inc/product-navigation-performance-010237.php';
$functions=$theme.'/functions.php';
$backup_module='/tmp/emdo-product-navigation-performance-010237.php.bak';
$backup_functions='/tmp/emdo-functions-010237.php.bak';
$module_code=<<<'PHP'
<?php
/**
 * Optimiza la navegación anterior/siguiente de Woostify en fichas de producto.
 *
 * Woostify 2.0.6 recorre candidatos uno a uno y carga cada WC_Product completo
 * hasta encontrar uno visible. En un marketplace esto puede multiplicar las
 * consultas cuando hay vendedores desactivados o productos ocultos. Esta capa
 * descarta esos candidatos directamente en la consulta SQL de WordPress, sin
 * modificar el HTML ni el comportamiento visible de las flechas.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_product_navigation_adjacent_where_010237( string $where ): string {
	global $wpdb;
	if ( false === strpos( $where, "p.post_type = 'product'" ) ) {
		return $where;
	}
	if ( function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) && elmercado_wcfm_disabled_visibility_can_view_010210() ) {
		return $where;
	}
	$clauses = array();
	if ( function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
		$disabled_vendors = array_values( array_unique( array_filter( array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() ) ) ) );
		if ( ! empty( $disabled_vendors ) ) {
			$clauses[] = 'p.post_author NOT IN (' . implode( ',', $disabled_vendors ) . ')';
		}
	}
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$visibility = wc_get_product_visibility_term_ids();
		$excluded = array_filter( array(
			absint( $visibility['exclude-from-catalog'] ?? 0 ),
			'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ? absint( $visibility['outofstock'] ?? 0 ) : 0,
		) );
		if ( ! empty( $excluded ) ) {
			$clauses[] = sprintf(
				'NOT EXISTS (SELECT 1 FROM %1$s AS emdo_nav_tr WHERE emdo_nav_tr.object_id = p.ID AND emdo_nav_tr.term_taxonomy_id IN (%2$s))',
				$wpdb->term_relationships,
				implode( ',', array_map( 'absint', $excluded ) )
			);
		}
	}
	return empty( $clauses ) ? $where : $where . ' AND ' . implode( ' AND ', $clauses );
}
add_filter( 'get_previous_post_where', 'elmercado_product_navigation_adjacent_where_010237', 20 );
add_filter( 'get_next_post_where', 'elmercado_product_navigation_adjacent_where_010237', 20 );
PHP;

function rollback_nav_hotfix(){global $module,$functions,$backup_module,$backup_functions;if(is_file($backup_functions))copy($backup_functions,$functions);if(is_file($backup_module))copy($backup_module,$module);else @unlink($module);}

echo "=== DEPLOY HOTFIX ===\n";
if(is_file($module))copy($module,$backup_module); else @unlink($backup_module);
copy($functions,$backup_functions);
if(false===file_put_contents($module,$module_code."\n")){echo "WRITE_MODULE_FAILED\n";rollback_nav_hotfix();exit(1);} 
$fc=file_get_contents($functions);$needle="\t'inc/wcfm-disabled-vendor-visibility-010210.php',";$insert=$needle."\n\t'inc/product-navigation-performance-010237.php',";
if(strpos($fc,"'inc/product-navigation-performance-010237.php'")===false){$patched=str_replace($needle,$insert,$fc,$count);if($count!==1||false===file_put_contents($functions,$patched)){echo "PATCH_FUNCTIONS_FAILED count=$count\n";rollback_nav_hotfix();exit(1);}}
echo emdo_cmd('php -l '.escapeshellarg($module))."\n";echo emdo_cmd('php -l '.escapeshellarg($functions))."\n";
if(strpos(emdo_cmd('php -l '.escapeshellarg($module)),'No syntax errors detected')===false){rollback_nav_hotfix();exit(1);} 

/* Load the new filter in this CLI request so we can benchmark it immediately. */
if(!function_exists('elmercado_product_navigation_adjacent_where_010237'))require_once $module;
global $wpdb;
$disabled=function_exists('elmercado_wcfm_disabled_vendor_ids_010210')?elmercado_wcfm_disabled_vendor_ids_010210():[];$disabled_sql=$disabled?implode(',',array_map('intval',$disabled)):'0';
$sample=(int)$wpdb->get_var("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='product_cat' WHERE p.post_type='product' AND p.post_status='publish' AND p.post_author NOT IN ($disabled_sql) ORDER BY p.post_date DESC LIMIT 1");
echo "SAMPLE_ID $sample\n";
if($sample){$GLOBALS['post']=get_post($sample);setup_postdata($GLOBALS['post']);$counts=['prev'=>0,'next'=>0];$fp=function($w)use(&$counts){$counts['prev']++;return $w;};$fn=function($w)use(&$counts){$counts['next']++;return $w;};add_filter('get_previous_post_where',$fp,PHP_INT_MAX);add_filter('get_next_post_where',$fn,PHP_INT_MAX);$t=microtime(true);$prev=woostify_get_prev_product();$tp=microtime(true)-$t;$t=microtime(true);$next=woostify_get_next_product();$tn=microtime(true)-$t;remove_filter('get_previous_post_where',$fp,PHP_INT_MAX);remove_filter('get_next_post_where',$fn,PHP_INT_MAX);echo 'BENCH prev_s='.sprintf('%.4f',$tp).' next_s='.sprintf('%.4f',$tn).' prev_loops='.$counts['prev'].' next_loops='.$counts['next'].' prev_id='.($prev?$prev->get_id():0).' next_id='.($next?$next->get_id():0)."\n";$url=get_permalink($sample);echo 'URL '.$url."\n";wp_reset_postdata();sleep(3);$health=emdo_cmd("curl -sS -L -o /dev/null --max-time 25 -w 'HTTP=%{http_code} TTFB=%{time_starttransfer} TOTAL=%{time_total}' ".escapeshellarg($url));echo "HEALTH $health\n";if(strpos($health,'HTTP=200')===false){echo "HEALTH_FAILED_ROLLBACK\n";rollback_nav_hotfix();exit(1);}}
@unlink($backup_module);@unlink($backup_functions);
echo "HOTFIX_OK\n";
