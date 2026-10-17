<?php
/** Acceptance tests after deploying Isabel-stock improvements. Read only. */
if(!defined('ABSPATH'))exit(1);
if(!in_array(strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST)),array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('Producción requerida');
if(!class_exists('MDO_Connector_Chocolates_Isabel')||!method_exists('MDO_Connector_Chocolates_Isabel','detect_stock_from_html')
||!class_exists('MDO_Isabel_Quality')||!method_exists('MDO_Isabel_Quality','sync_review_stock'))throw new RuntimeException('EMDO stock improvements not deployed');
$tests=array(
 'explicit_no_disponible'=>array('<main><div class="entry-summary"><p class="stock out-of-stock">No disponible</p></div></main>','outofstock'),
 'explicit_agotado'=>array('<div class="summary"><p class="availability"><span>Agotado</span></p></div>','outofstock'),
 'woo_css_outofstock'=>array('<div class="entry-summary"><span class="stock outofstock">Sin existencias</span></div>','outofstock'),
 'css_instock'=>array('<div class="summary"><p class="stock in-stock">Hay existencias</p></div>','instock'),
 'stock_unknown'=>array('<div class="summary"><p>Información de envío y preparación.</p></div>','unknown'),
 'unavailable_fallback'=>array('<div class="entry-summary"><h2>Chocolate</h2><p>No disponible</p></div>','outofstock'),
 'notification_is_not_stock'=>array('<div class="summary"><div class="availability">Notificarme cuando esté disponible</div></div>','unknown'),
);
$test_results=array();foreach($tests as $name=>$case){
 $got=MDO_Connector_Chocolates_Isabel::detect_stock_from_html($case[0]);
 if($got!==$case[1])throw new RuntimeException('Fallo de parser '.$name.': '.$got.' esperado '.$case[1]);
 $test_results[$name]=$got;
}
global $wpdb;
$s=MDO_Database::table('suppliers');$t=MDO_Database::table('source_products');$runs=MDO_Database::table('sync_runs');
$vendor=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id,sync_frequency FROM {$s} WHERE code=%s",'chocolates-artesanos-isabel'),ARRAY_A);
if(!$vendor||(int)$vendor['vendor_user_id']!==4964||$vendor['sync_frequency']!=='daily')throw new RuntimeException('Proveedor o programación nocturna no válida');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,source_url,status,source_stock_status,wc_product_id FROM {$t} WHERE supplier_id=%d ORDER BY id",(int)$vendor['id']),ARRAY_A);
$summary=array('fixtures'=>$test_results,'total'=>count($rows),'supplier_daily'=>true,'turron_count'=>0,
 'turron_outofstock'=>0,'source_instock'=>0,'source_outofstock'=>0,'wc_mismatches'=>array(),
 'review_flags'=>0,'review_not_purchasable'=>0,'recent_run'=>null,'live'=>array());
foreach($rows as $r){
 $stat=$r['source_stock_status'];
 if($stat==='instock')$summary['source_instock']++;elseif($stat==='outofstock')$summary['source_outofstock']++;
 else throw new RuntimeException('Estado del origen desconocido en '.$r['id']);
 $pid=(int)$r['wc_product_id'];$p=$pid?wc_get_product($pid):false;
 if($p){
  if((int)get_post_field('post_author',$pid)!==4964)throw new RuntimeException('Producto fuera del proveedor '.$pid);
  $actual=$p->get_stock_status('edit');
  if($actual!==$stat)$summary['wc_mismatches'][]=array('source_id'=>$r['id'],'source'=>$stat,'woo'=>$actual);
  if(get_post_meta($pid,'_emdo_isabel_customization_review_required',true)==='1'){
   $summary['review_flags']++;
   if(!$p->is_purchasable())$summary['review_not_purchasable']++;
  }
 }
 if(preg_match('/turr[oó]n/ui',(string)$r['title'])){
  $summary['turron_count']++;
  if($stat==='outofstock')$summary['turron_outofstock']++;
 }
}
$summary['recent_run']=$wpdb->get_row($wpdb->prepare("SELECT id,status,products_found,products_updated,errors_count,finished_at FROM {$runs} WHERE supplier_id=%d ORDER BY id DESC LIMIT 1",(int)$vendor['id']),ARRAY_A);
$sample=array();
foreach($rows as $r){if(preg_match('/turr[oó]n/ui',(string)$r['title'])){$sample[]=$r;break;}}
foreach($rows as $r){if($r['source_stock_status']==='instock'){$sample[]=$r;break;}}
foreach($sample as $r){
 $remote=MDO_Connector_Chocolates_Isabel::scrape_product($r['source_url']);
 $status=(string)($remote['stock_status']??'unknown');
 $summary['live'][]=array('source_id'=>$r['id'],'expected'=>$r['source_stock_status'],'live'=>$status);
 if($status!==$r['source_stock_status'])throw new RuntimeException('Origen live cambió desde último snapshot; no se altera automáticamente hasta el siguiente run');
}
if(count($rows)!==106||count($summary['wc_mismatches'])!==0||$summary['review_flags']!==7||
 $summary['review_not_purchasable']!==7||$summary['turron_count']!==10)throw new RuntimeException('La aceptación de stock Isabel no se cumplió: '.wp_json_encode($summary));
echo 'ISABEL_STOCK_ACCEPTED='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
