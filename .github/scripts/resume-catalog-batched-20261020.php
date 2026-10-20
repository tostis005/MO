<?php
/**
 * Resume catalog text and photo reconciliation in bounded, idempotent WP-CLI batches.
 * EMDO_RESUME_TASK=copy|gallery, EMDO_RESUME_MODE=audit|apply,
 * EMDO_RESUME_OFFSET=N, EMDO_RESUME_SIZE=N.
 */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) || ! class_exists( 'MDO_Database' ) ) {
	throw new RuntimeException( 'WooCommerce y EMDO deben estar activos.' );
}
set_time_limit( 0 );
ini_set( 'max_execution_time', '0' );

$task = (string) ( getenv( 'EMDO_RESUME_TASK' ) ?: 'gallery' );
$mode = (string) ( getenv( 'EMDO_RESUME_MODE' ) ?: 'audit' );
$offset = (int) getenv( 'EMDO_RESUME_OFFSET' );
$size = (int) ( getenv( 'EMDO_RESUME_SIZE' ) ?: 6 );
if ( ! in_array( $task, array( 'copy', 'gallery' ), true ) ||
	! in_array( $mode, array( 'audit', 'apply' ), true ) ||
	$offset < 0 || $size < 1 || $size > ( 'gallery' === $task ? 8 : 100 ) ) {
	throw new RuntimeException( 'Parámetros de lote no válidos.' );
}
$apply = 'apply' === $mode;
global $wpdb;
$report = array(
	'task'=>$task, 'mode'=>$mode, 'offset'=>$offset, 'size'=>$size,
	'checked'=>0, 'changed'=>0, 'added'=>0, 'already_present'=>0,
	'candidates'=>0, 'errors'=>array(), 'sample'=>array(), 'at'=>gmdate('c'),
);

if ( 'copy' === $task ) {
	$ids = get_posts( array(
		'post_type'=>'product', 'post_status'=>array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page'=>$size, 'offset'=>$offset, 'fields'=>'ids',
		'orderby'=>'ID', 'order'=>'ASC', 'suppress_filters'=>true,
	) );
	$patterns = array(
		'~\bseg[uú]n\s+(?:se\s+(?:indica|informa)\s+en\s+)?(?:la\s+)?(?:web|p[aá]gina|ficha(?:\s+t[eé]cnica)?|informaci[oó]n|cat[aá]logo)\s+(?:de(?:l|\s+la)\s+)?(?:productor|fabricante|proveedor|Latitudes)\b,?\s*~iu'=>'',
		'~\bseg[uú]n\s+la\s+lista\s+de\s+ingredientes\s+publicada\s+por\s+(?:el\s+)?(?:productor|fabricante|proveedor)\b,?\s*~iu'=>'',
		'~\b(?:la\s+)?(?:ficha|web|p[aá]gina)\s+del\s+(?:productor|fabricante|proveedor)\s+(?:indica|se[nñ]ala|explica|especifica)\s+que\s+~iu'=>'',
		'~\b(?:el\s+)?(?:productor|fabricante|proveedor)\s+(?:indica|informa|se[nñ]ala|explica|especifica)\s+que\s+~iu'=>'',
	);
	$fix = static function( string $old ) use ( $patterns ): string {
		$fixed = preg_replace( array_keys( $patterns ), array_values( $patterns ), $old );
		if ( ! is_string( $fixed ) || '' === trim( wp_strip_all_tags( $fixed ) ) ) { return $old; }
		return $fixed;
	};
	foreach ( $ids as $id ) {
		$product = wc_get_product( (int) $id );
		if ( ! $product || $product->is_type( 'variation' ) ) { continue; }
		$report['checked']++;
		$before = array( (string)$product->get_description( 'edit' ), (string)$product->get_short_description( 'edit' ) );
		$after = array_map( $fix, $before );
		$updates = array();
		foreach ( array(
			'_emdo_latitudes_editorial_description',
			'_emdo_latitudes_editorial_short_description',
			'_emdo_isabel_editorial_intro',
			'_emdo_isabel_editorial_short_description',
			'_emdo_isabel_personalizable_editorial_intro',
		) as $key ) {
			$value = get_post_meta( $id, $key, true );
			if ( ! is_string( $value ) || '' === $value ) { continue; }
			$new = $fix( $value );
			if ( $new !== $value ) { $updates[ $key ] = $new; }
		}
		if ( $before !== $after || $updates ) {
			$report['candidates']++;
			if ( $apply ) {
				if ( $before !== $after ) {
					if ( ! metadata_exists( 'post', $id, '_emdo_copy_backup_20261020' ) ) {
						add_post_meta( $id, '_emdo_copy_backup_20261020', wp_json_encode( $before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), true );
					}
					$product->set_description( $after[0] );
					$product->set_short_description( $after[1] );
					$product->save();
				}
				foreach ( $updates as $key=>$text ) { update_post_meta( $id, $key, $text ); }
				$report['changed']++;
			}
			if ( count( $report['sample'] ) < 12 ) { $report['sample'][] = array( 'id'=>(int)$id, 'meta'=>count($updates), 'changed_html'=>$before!==$after ); }
		}
	}
} else {
	if ( ! class_exists( 'MDO_Connector_Tolecarnes' ) ) { throw new RuntimeException( 'Conector origen no disponible.' ); }
	$table = MDO_Database::table( 'source_products' );
	$supplier_table = MDO_Database::table( 'suppliers' );
	$lat = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
	if ( ! $lat ) { throw new RuntimeException( 'Latitudes no existe en EMDO.' ); }
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT id,wc_product_id,source_url FROM {$table} WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id LIMIT %d OFFSET %d",
		(int)$lat['id'], $size, $offset
	), ARRAY_A );
	$total = (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE supplier_id=%d AND wc_product_id>0", (int)$lat['id'] ) );
	if ( $total < 125 || $total > 165 ) { throw new RuntimeException( 'Conteo Latitudes inconsistente: ' . $total ); }
	$report['total']=$total;
	if ( $apply ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	foreach ( $rows as $row ) {
		$id=(int)$row['wc_product_id'];
		$url=(string)$row['source_url'];
		$product=wc_get_product($id);
		$host=strtolower((string)wp_parse_url($url,PHP_URL_HOST));
		if ( ! $product || ! in_array( $host, array( 'latitudes.es', 'www.latitudes.es' ), true ) ||
			(int)get_post_field( 'post_author', $id ) !== (int)$lat['vendor_user_id'] ) {
			$report['errors'][]=array('id'=>$id,'error'=>'Identidad de producto/proveedor no verificada');
			continue;
		}
		$report['checked']++;
		try {
			$source=MDO_Connector_Tolecarnes::scrape_product($url);
			$urls=array_values(array_unique(array_filter((array)($source['images']??array()),static function($u):bool{
				$h=strtolower((string)wp_parse_url((string)$u,PHP_URL_HOST));
				$p=(string)wp_parse_url((string)$u,PHP_URL_PATH);
				return in_array($h,array('latitudes.es','www.latitudes.es'),true)
					&& str_contains($p,'/wp-content/uploads/')
					&& (bool)preg_match('~\.(?:jpe?g|png|webp)$~i',$p);
			})));
			if(count($urls)>30){throw new RuntimeException('Exceso de archivos en la ficha de origen');}
			$map=json_decode((string)get_post_meta($id,'_emdo_image_map',true),true);
			$map=is_array($map)?$map:array();
			$gallery=array_map('intval',$product->get_gallery_image_ids());
			$cover=(int)$product->get_image_id();
			$new_ids=$gallery;
			$added=0;
			foreach ($urls as $index=>$img) {
				$aid=(int)($map[$img]??0);
				if($aid && get_post_type($aid)!=='attachment'){$aid=0;}
				if(!$aid) {
					$attachment=$wpdb->get_var($wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_source_url' AND meta_value=%s LIMIT 1",$img
					));
					$aid=(int)$attachment;
				}
				// When the primary image has no source metadata, assume image 0 is
				// the existing cover; never replace it or import it as a duplicate.
				if($index===0 && !$aid && $cover){$aid=$cover;}
				if($aid && $aid!==$cover) {
					if(!in_array($aid,$new_ids,true)){$new_ids[]=$aid;}
					$report['already_present']++;
				}
				if($aid){$map[$img]=$aid;continue;}
				$report['candidates']++;
				if(!$apply){continue;}
				// download_url() has its OWN 12-second network timeout. Check bytes
				// before importing to avoid oversized files, HTML or bad MIME.
				$tmp=download_url($img,12);
				if(is_wp_error($tmp)) {
					$report['errors'][]=array('id'=>$id,'image'=>$img,'error'=>$tmp->get_error_message());
					continue;
				}
				$bytes=(int)filesize($tmp);
				$info=@getimagesize($tmp);
				if($bytes<=0 || $bytes>14000000 || !$info || !in_array(($info['mime']??''),array('image/jpeg','image/png','image/webp'),true)){
					@unlink($tmp);
					$report['errors'][]=array('id'=>$id,'image'=>$img,'error'=>'Archivo inválido o mayor de 14 MB');
					continue;
				}
				$path=(string)wp_parse_url($img,PHP_URL_PATH);
				$file=array('name'=>sanitize_file_name(basename($path)), 'tmp_name'=>$tmp, 'error'=>0, 'size'=>$bytes);
				$aid=media_handle_sideload($file,$id,$product->get_name());
				if(is_wp_error($aid)){
					@unlink($tmp);
					$report['errors'][]=array('id'=>$id,'image'=>$img,'error'=>$aid->get_error_message());
					continue;
				}
				$aid=(int)$aid;
				update_post_meta($aid,'_source_url',esc_url_raw($img));
				update_post_meta($aid,'_wp_attachment_image_alt',sanitize_text_field($product->get_name().' — fotografía '.($index+1)));
				$map[$img]=$aid;
				if($aid!==$cover && !in_array($aid,$new_ids,true)){$new_ids[]=$aid;$added++;}
			}
			if($apply) {
				// Commit attachment map and gallery per product, permitting safe
				// reruns after individual network errors or action cancellation.
				update_post_meta($id,'_emdo_image_map',wp_json_encode($map,JSON_UNESCAPED_SLASHES));
				if($new_ids!==$gallery){
					$product->set_gallery_image_ids($new_ids);
					$product->save();
					$saved=wc_get_product($id);
					if(!$saved || count($saved->get_gallery_image_ids())!==count($new_ids)){
						throw new RuntimeException('Galería no persistida en WooCommerce');
					}
					$report['changed']++;
				}
			}
			$report['added']+=$added;
			if(count($report['sample'])<8){$report['sample'][]=array('id'=>$id,'source_count'=>count($urls),'before'=>count($gallery),'after'=>count($new_ids),'added'=>$added);}
		} catch(Throwable $e) {
			$report['errors'][]=array('id'=>$id,'error'=>mb_substr($e->getMessage(),0,230));
		}
	}
}
echo 'EMDO_RESUME_BATCH='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($report['checked']===0){throw new RuntimeException('Lote vacío, revisar el desplazamiento.');}
