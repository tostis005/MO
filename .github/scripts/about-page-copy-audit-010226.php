<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function emdo_cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
$theme=get_stylesheet_directory();
$module=$theme.'/inc/product-navigation-performance-010237.php';
$functions=$theme.'/functions.php';
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

echo "=== PRE STATE ===\n";
echo 'MODULE_EXISTS '.(is_file($module)?'yes':'no')."\n";
$before=(string)file_get_contents($functions);
echo 'INCLUDE_EXISTS '.(strpos($before,"'inc/product-navigation-performance-010237.php'")!==false?'yes':'no')."\n";

/* Put production in the exact intended state, idempotently. */
if(false===file_put_contents($module,$module_code."\n")){echo "WRITE_MODULE_FAILED\n";exit(1);}
$fc=(string)file_get_contents($functions);
$needle="\t'inc/wcfm-disabled-vendor-visibility-010210.php',";
$include="\t'inc/product-navigation-performance-010237.php',";
if(strpos($fc,"'inc/product-navigation-performance-010237.php'")===false){
  $patched=str_replace($needle,$needle."\n".$include,$fc,$count);
  if($count!==1||false===file_put_contents($functions,$patched)){echo "PATCH_FUNCTIONS_FAILED count=$count\n";exit(1);}
}
$php=escapeshellarg(PHP_BINARY);
$lint_module=emdo_cmd($php.' -l '.escapeshellarg($module));
$lint_functions=emdo_cmd($php.' -l '.escapeshellarg($functions));
echo "LINT_MODULE $lint_module\n";
echo "LINT_FUNCTIONS $lint_functions\n";
if(strpos($lint_module,'No syntax errors detected')===false||strpos($lint_functions,'No syntax errors detected')===false){echo "LINT_FAILED\n";exit(1);}

/* Ensure this already-running WP-CLI process uses the new filter too. */
if(!function_exists('elmercado_product_navigation_adjacent_where_010237')) require_once $module;

global $wpdb;
$disabled=function_exists('elmercado_wcfm_disabled_vendor_ids_010210')?elmercado_wcfm_disabled_vendor_ids_010210():[];
$disabled_sql=$disabled?implode(',',array_map('intval',$disabled)):'0';
$sample=(int)$wpdb->get_var("SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_status='publish' AND p.post_author NOT IN ($disabled_sql) ORDER BY p.post_date DESC LIMIT 1");
echo 'DISABLED_VENDOR_IDS '.json_encode(array_map('intval',$disabled))."\n";
echo "SAMPLE_ID $sample\n";
if(!$sample){echo "NO_ACTIVE_PRODUCT\n";exit(1);}
$GLOBALS['post']=get_post($sample);setup_postdata($GLOBALS['post']);
$counts=['prev'=>0,'next'=>0,'visible'=>0];
$fp=function($w)use(&$counts){$counts['prev']++;return $w;};
$fn=function($w)use(&$counts){$counts['next']++;return $w;};
$fv=function($v,$pid)use(&$counts){$counts['visible']++;return $v;};
add_filter('get_previous_post_where',$fp,PHP_INT_MAX);
add_filter('get_next_post_where',$fn,PHP_INT_MAX);
add_filter('woocommerce_product_is_visible',$fv,PHP_INT_MAX,2);
$t=microtime(true);$prev=woostify_get_prev_product();$tp=microtime(true)-$t;
$t=microtime(true);$next=woostify_get_next_product();$tn=microtime(true)-$t;
remove_filter('get_previous_post_where',$fp,PHP_INT_MAX);
remove_filter('get_next_post_where',$fn,PHP_INT_MAX);
remove_filter('woocommerce_product_is_visible',$fv,PHP_INT_MAX);
echo 'BENCH prev_s='.sprintf('%.6f',$tp).' next_s='.sprintf('%.6f',$tn).' prev_loops='.$counts['prev'].' next_loops='.$counts['next'].' visible_checks='.$counts['visible'].' prev_id='.($prev?$prev->get_id():0).' next_id='.($next?$next->get_id():0)."\n";
$url=get_permalink($sample);echo 'PRODUCT_URL '.$url."\n";wp_reset_postdata();
$health=emdo_cmd("curl -sS -L -o /dev/null --max-time 25 -w 'HTTP=%{http_code} TTFB=%{time_starttransfer} TOTAL=%{time_total}' ".escapeshellarg($url)."?emdo-nav-hotfix=1");
echo "HEALTH $health\n";
if(strpos($health,'HTTP=200')===false){echo "HEALTH_FAILED\n";exit(1);}
echo "=== POST STATE ===\nMODULE_EXISTS yes\nINCLUDE_EXISTS yes\nHOTFIX_OK\n";
