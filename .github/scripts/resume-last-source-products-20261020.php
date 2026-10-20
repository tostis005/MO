<?php
/** Inspect the most recently created product and resume only pending source imports. */
if (!defined('ABSPATH') || !class_exists('MDO_Woo_Importer') || !class_exists('MDO_Database')) {
	throw new RuntimeException('EMDO / WooCommerce must be loaded in WP-CLI.');
}
$mode=(string)(getenv('MDO_RESUME_SOURCE_MODE')?:'audit');
$limit=(int)(getenv('MDO_RESUME_SOURCE_LIMIT')?:3);
if(!in_array($mode,array('audit','apply'),true)||$limit<1||$limit>5) {
	throw new RuntimeException('Invalid resume mode/limit.');
}
global $wpdb;
$table=MDO_Database::table('source_products');
$suppliers=MDO_Database::table('suppliers');
$report=array('mode'=>$mode,'at'=>gmdate('c'),'suppliers'=>array(),'imported'=>array(),'errors'=>array());
foreach(array('latitudes','tropitop') as $code){
	$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1",$code),ARRAY_A);
	if(!$s){$report['suppliers'][$code]=array('missing'=>true);continue;}
	$id=(int)$s['id'];$vendor=(int)$s['vendor_user_id'];
	$latest=$wpdb->get_row($wpdb->prepare(
		"SELECT id,title,wc_product_id,source_url,status FROM {$table} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY wc_product_id DESC LIMIT 1",$id
	),ARRAY_A);
	$product=$latest?wc_get_product((int)$latest['wc_product_id']):null;
	$pending=(int)$wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE supplier_id=%d AND status='pending'",$id
	));
	$created=(int)$wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE supplier_id=%d AND wc_product_id>0",$id
	));
	$active=(int)$s['active']===1;
	$disabled=$vendor?(bool)get_user_meta($vendor,'_disable_vendor',true):true;
	$shipping='tropitop'===$code?MDO_Tropitop_Shipping::is_ready():null;
	$ready=$active&&!$disabled&&($code!=='tropitop'||$shipping);
	$report['suppliers'][$code]=array(
		'active'=>$active,'vendor_disabled'=>$disabled,'shipping_ready'=>$shipping,
		'source_rows'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE supplier_id=%d",$id)),
		'created_products'=>$created,'source_pending'=>$pending,'can_publish'=>$ready,
		'latest_created'=>$latest?array(
			'product_id'=>(int)$latest['wc_product_id'],'source_row'=>(int)$latest['id'],
			'name'=>$product?$product->get_name():(string)$latest['title'],
			'status'=>$product?$product->get_status():'missing',
			'cover'=>(bool)($product&&$product->get_image_id()),
			'gallery_count'=>$product?count($product->get_gallery_image_ids()):0,
			'url'=>$product?get_permalink($product->get_id()):null,
		):null,
	);
	if($code!=='tropitop'||!$ready||$mode!=='apply'){continue;}
	// The dedicated full-catalog importer owns the saved Tropitop URL cursor.
	// Never import those same products concurrently from a second workflow.
	$bulk=get_option('mdo_tropitop_full_catalog_20261020',array());
	if(is_array($bulk) && !empty($bulk['urls']) && (int)($bulk['cursor']??0)<count($bulk['urls'])){
		$report['suppliers'][$code]['bulk_cursor']=(int)($bulk['cursor']??0);
		$report['suppliers'][$code]['bulk_total']=count($bulk['urls']);
		$report['suppliers'][$code]['bulk_owns_import']=true;
		continue;
	}
	// A prior timeout might have created a Woo item but not updated the source
	// link in older releases. Match only its verified supplier-specific SKU.
	$todo=$wpdb->get_results($wpdb->prepare(
		"SELECT * FROM {$table} WHERE supplier_id=%d AND status IN ('pending','active') ORDER BY id ASC",$id
	),ARRAY_A);
	$processed=0;
	foreach($todo as $row){
		$source_id=(int)$row['id'];
		$wc_id=(int)$row['wc_product_id'];
		$p=$wc_id?wc_get_product($wc_id):null;
		$payload=json_decode((string)$row['source_payload'],true);
		if(!is_array($payload)){continue;}
		if(!$wc_id && !empty($payload['sku'])){
			$matched=wc_get_product_id_by_sku(sanitize_text_field((string)$payload['sku']));
			if($matched && (int)get_post_field('post_author',$matched)===$vendor){
				$wc_id=(int)$matched;
				$wpdb->update($table,array('wc_product_id'=>$wc_id),array('id'=>$source_id),array('%d'),array('%d'));
				$p=wc_get_product($wc_id);
			}
		}
		if('active'===(string)$row['status'] && (!$p||$p->get_status()!=='draft')){continue;}
		if($p && get_post_meta($wc_id,'_mdo_tropitop_sync_uncertain',true)==='1'){continue;}
		if(!$p && $wc_id){continue;}
		if($processed>=$limit){break;}
		try {
			// Only source products with a complete catalog entry may publish.
			if(empty($payload['images']) || empty($payload['variations']) ||
				!in_array((string)($payload['stock_status']??''),array('instock','outofstock'),true)){
				throw new RuntimeException('Incomplete images, variants or verified stock');
			}
			if('pending'===(string)$row['status'] && !MDO_Woo_Importer::mark_importing($source_id)){
				throw new RuntimeException('Source product already claimed for import');
			}
			$new_id=MDO_Woo_Importer::import_source_product($source_id);
			$loaded=wc_get_product($new_id);
			if(!$loaded){throw new RuntimeException('Woo product not saved');}
			$report['imported'][]=array(
				'row_id'=>$source_id,'product_id'=>$new_id,'status'=>$loaded->get_status(),
				'gallery'=>count($loaded->get_gallery_image_ids()),'cover'=>(bool)$loaded->get_image_id()
			);
		} catch(Throwable $e){
			MDO_Woo_Importer::mark_import_error($source_id,$e->getMessage());
			$report['errors'][]=array('source_id'=>$source_id,'message'=>mb_substr($e->getMessage(),0,200));
		}
		$processed++;
	}
}
echo 'EMDO_LAST_SOURCE_RESUME='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
