<?php
/**
 * Authorized Tropitop catalog ingestion into hidden Woo drafts, in bounded
 * production WP-CLI batches. No cart, purchase, or vendor activation.
 * Modes: TROPITOP_BULK_PHASE=init|step|status; TROPITOP_BULK_BATCH=1..5.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
foreach ( array( 'home', 'siteurl' ) as $o ) {
    if ( ! in_array( strtolower( (string) wp_parse_url( get_option( $o ), PHP_URL_HOST ) ),
        array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'Only EMDO production host.' );
    }
}
foreach ( array( 'MDO_Database', 'MDO_Connector_Tropitop', 'MDO_Woo_Importer',
    'MDO_Tropitop_Shipping', 'WooCommerce' ) as $class ) {
    if ( ! class_exists( $class ) ) { throw new RuntimeException( 'Missing required class ' . $class ); }
}
global $wpdb;
$sup_table = MDO_Database::table( 'suppliers' );
$src_table = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $sup_table WHERE code=%s", 'tropitop' ), ARRAY_A );
if ( ! $supplier || (int) $supplier['id'] !== 8 ||
    (int) $supplier['vendor_user_id'] !== 4971 ||
    abs( (float) $supplier['commission_percent'] - 10.0 ) > 0.001 ) {
    throw new RuntimeException( 'Tropitop identity or agreed 10% commission not verified.' );
}
$seller_disabled = (bool) get_user_meta( 4971, '_disable_vendor', true );
$checkout_ready = MDO_Tropitop_Shipping::is_ready();
// A vendor can already be enabled by WCFM while EMDO shipping is still
// blocked. That is a legitimate PRE-ACTIVATION state: the shipping guard
// returns zero available rates and our importer keeps new SKUs as drafts.
// Permit the activation preflight to repair it, never assume free delivery.
// Only a disabled vendor with checkout marked ready is inconsistent.
if ( $seller_disabled && $checkout_ready ) {
    throw new RuntimeException( 'Disabled Tropitop vendor with shipping incorrectly marked ready.' );
}
if ( get_option( 'woocommerce_weight_unit' ) !== 'kg' ) {
    throw new RuntimeException( 'Woo shop weight unit is not kg.' );
}
$phase = (string) ( getenv( 'TROPITOP_BULK_PHASE' ) ?: 'status' );
$key = 'mdo_tropitop_full_catalog_20261020';
$state = get_option( $key, array() );
$state = is_array( $state ) ? $state : array();
if ( 'init' === $phase ) {
    if ( ! empty( $state['urls'] ) ) {
        echo 'TROPITOP_BULK_ALREADY_INITIALIZED=' . count( $state['urls'] ) . "\n";
        return;
    }
    // Root paths are verified against the producer homepage menu; the
    // 84-disponible list is included to catch items filed under unusual paths.
    $categories = array(
        '84-disponible',
        '109-bio', '63-frutas-tropicales', '106-frutas-del-mundo',
        '12-aguacates', '62-frutas', '80-verduras', '79-gourmet',
        '87-packs', '89-cajas-de-temporada', '77-apadrina-un-arbol',
        '62-citricos', '10-mangos', '52-cestas-de-fruta',
    );
    $urls = array(); $roots = array(); $warnings = array();
    foreach ( $categories as $category ) {
        $url = 'https://tropitop.com/es/' . $category;
        try {
            $items = MDO_Connector_Tropitop::discover( array(
                'source_url' => $url, 'exclusion_url_fragments' => '[]',
            ) );
            $found = (array) ( $items['products'] ?? array() );
            if ( ! $found ) { continue; }
            $roots[] = $url;
            foreach ( $found as $purl ) {
                if ( preg_match( '~/([0-9]+)(?:-[0-9]+)?-[a-z0-9-]+\.html$~i',
                    (string) wp_parse_url( $purl, PHP_URL_PATH ), $m ) ) {
                    // Key by *product* ID, never PrestaShop combination or URL.
                    $id = (int) $m[1];
                    if ( ! isset( $urls[ $id ] ) ) { $urls[ $id ] = $purl; }
                }
            }
            echo 'TROPITOP_CATEGORY_DISCOVER=' . $category . ' count=' . count( $found ) . "\n";
        } catch ( Throwable $e ) {
            $warnings[$category] = $e->getMessage();
            echo 'TROPITOP_CATEGORY_WARNING=' . $category . ' ' . $e->getMessage() . "\n";
        }
    }
    $count = count( $urls );
    if ( $count < 130 || $count > 600 ||
        ! in_array( 'https://tropitop.com/es/84-disponible', $roots, true ) ) {
        throw new RuntimeException( 'Catalog discovery unexpectedly incomplete/large: ' . $count );
    }
    ksort( $urls, SORT_NUMERIC );
    $state = array(
        'urls' => array_values( $urls ), 'roots' => $roots,
        'cursor' => 0, 'imported' => array(), 'failed' => array(),
        'warnings' => $warnings, 'started_at' => gmdate( 'c' ),
    );
    update_option( $key, $state, false );
    // This activates catalogue discovery only after bootstrap completes,
    // not public WCFM sales. Register the full set of roots for daily updates.
    $wpdb->update( $sup_table, array(
        'source_url' => implode( "\n", $roots ),
        'sync_frequency' => 'manual', 'active' => 0, 'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => 8 ) );
    echo 'TROPITOP_BULK_INIT=' . wp_json_encode( array(
        'products' => $count, 'root_categories' => count( $roots ),
        'root_warnings' => $warnings, 'seller_disabled' => true,
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
    return;
}
if ( ! in_array( $phase, array( 'step', 'status', 'activate' ), true ) ) {
    throw new RuntimeException( 'Unknown phase ' . $phase );
}
if ( empty( $state['urls'] ) || ! is_array( $state['urls'] ) ) {
    throw new RuntimeException( 'Tropitop bulk catalog has not been initialized.' );
}
$total = count( $state['urls'] );
if ( 'status' === $phase ) {
    echo 'TROPITOP_BULK_STATUS=' . wp_json_encode( array(
        'total' => $total, 'cursor' => (int) $state['cursor'],
        'imported' => count( (array) $state['imported'] ),
        'failed' => count( (array) $state['failed'] ),
        'failed_samples' => array_slice( (array) $state['failed'], 0, 12, true ),
        'producer_active' => (int) $supplier['active'],
        'seller_disabled' => $seller_disabled,
        'shipping_ready' => $checkout_ready,
        'last_source_url' => (string) ( $state['urls'][max( 0, (int)$state['cursor'] - 1 )] ?? '' ),
        'last_woocommerce_product' => (static function () use ($wpdb, $src_table) {
            $row=$wpdb->get_row($wpdb->prepare(
                "SELECT wc_product_id,title FROM $src_table WHERE supplier_id=%d AND wc_product_id>0 ORDER BY wc_product_id DESC LIMIT 1", 8
            ),ARRAY_A);
            if(!$row) return null;
            $woo=wc_get_product((int)$row['wc_product_id']);
            return array(
                'id'=>(int)$row['wc_product_id'], 'name'=>$woo?$woo->get_name():(string)$row['title'],
                'status'=>$woo?$woo->get_status():'missing',
                'image_count'=>$woo?((int)(bool)$woo->get_image_id()+count($woo->get_gallery_image_ids())):0,
            );
        })(),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    return;
}
if ( 'activate' === $phase ) {
    // Sales activation is explicitly requested by the merchant. It is allowed
    // only after the WCFM mainland shipping configuration passes live QA.
    // Portugal/Canary Islands/Balearics stay blocked: no verified quote.
    if ( ! class_exists( 'WCFMmp_Shipping_Zone' ) ) {
        throw new RuntimeException( 'WCFM shipping zones unavailable.' );
    }
    if ( $checkout_ready && ! $seller_disabled ) {
        echo 'TROPITOP_SALES_ALREADY_ACTIVE=1' . "\n";
        return;
    }
    $vendor = get_user_by( 'id', 4971 );
    if ( ! $vendor || ! in_array( 'wcfm_vendor', (array) $vendor->roles, true ) ) {
        throw new RuntimeException( 'Tropitop has no active WCFM vendor account.' );
    }
    $zone = new WC_Shipping_Zone( 1 );
    if ( 'España (península)' !== $zone->get_zone_name() ) {
        throw new RuntimeException( 'WooCommerce mainland shipping zone has changed.' );
    }
    $ship = get_user_meta( 4971, '_wcfmmp_shipping', true );
    if ( ! is_array( $ship ) || (string)( $ship['_wcfmmp_user_shipping_type'] ?? '' ) !== 'by_zone' ||
        (string)( $ship['_wcfmmp_user_shipping_enable'] ?? '' ) !== 'yes' ) {
        throw new RuntimeException( 'Tropitop WCFM by-zone shipping is not enabled.' );
    }
    $method_table = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
    $methods = $wpdb->get_results( $wpdb->prepare(
        "SELECT zone_id,method_id,is_enabled FROM $method_table WHERE vendor_id=%d", 4971
    ), ARRAY_A );
    if ( count( (array)$methods ) !== 1 || (int)$methods[0]['zone_id'] !== 1 ||
        (string)$methods[0]['method_id'] !== 'flat_rate' || (int)$methods[0]['is_enabled'] !== 1 ) {
        throw new RuntimeException( 'Tropitop WCFM zone rate is missing or ambiguous.' );
    }
    $previous_ready=(string)get_option('mdo_tropitop_shipping_rates_verified','0');
    $original_active=(int)$supplier['active'];
    $fake_ready=static fn()=>'1';
    add_filter( 'pre_option_mdo_tropitop_shipping_rates_verified', $fake_ready, 10, 0 );
    try {
        $checks=array(3=>2.95,4=>2.95,6=>5.95,8=>5.95,9=>5.95,12=>7.95,18=>10.95,20=>10.95,24=>12.95,27=>15.95,36=>20.95);
        foreach($checks as $kg=>$expect) {
            $p=new WC_Product_Simple();
            $p->set_weight((string)$kg);
            $package=array('contents'=>array(array('product_id'=>0,'data'=>$p,'quantity'=>1)),
                'vendor_id'=>4971,'destination'=>array('country'=>'ES','postcode'=>'28001','state'=>'M'));
            // Package detection requires a product authored by this vendor.
            $bacon_id=wc_get_product_id_by_sku('TROPITOP-33');
            if(!$bacon_id || (int)get_post_field('post_author',$bacon_id)!==4971){
                throw new RuntimeException('Approved Bacon product/vendor reference missing.');
            }
            $package['contents'][0]['product_id']=$bacon_id;
            $rate=new WC_Shipping_Rate('wcfmmp_product_shipping_by_zone:test','Tropitop',2.95,array(),'wcfmmp_product_shipping_by_zone',1);
            $result=MDO_Tropitop_Shipping::filter_rates(array('rate'=>$rate),$package);
            $actual=$result?(float)reset($result)->get_cost():null;
            if(null===$actual || abs($actual-$expect)>0.001){
                throw new RuntimeException('Shipping preflight failed for '.$kg.' kg.');
            }
        }
        foreach(array(array('ES','07001'),array('PT','1000-001'),array('FR','75001')) as $dest){
            $package['destination']=array('country'=>$dest[0],'postcode'=>$dest[1]);
            if(MDO_Tropitop_Shipping::filter_rates(array('rate'=>$rate),$package)!==array()){
                throw new RuntimeException('Unverified destination incorrectly allowed.');
            }
        }
    } finally {
        remove_filter( 'pre_option_mdo_tropitop_shipping_rates_verified', $fake_ready, 10 );
    }
    $enabled=0;
    try {
        update_option( 'mdo_tropitop_shipping_rates_verified', '1', false );
        delete_user_meta( 4971, '_disable_vendor' );
        $ok=$wpdb->update( $sup_table,
            array('active'=>1,'sync_frequency'=>'daily','updated_at'=>current_time('mysql')),
            array('id'=>8) );
        if(false===$ok){throw new RuntimeException('Supplier activation DB error.');}
        $enabled=1;
    } finally {
        if(!$enabled){
            update_option('mdo_tropitop_shipping_rates_verified',$previous_ready,false);
            if($seller_disabled)update_user_meta(4971,'_disable_vendor',1);
            $wpdb->update($sup_table,array('active'=>$original_active),array('id'=>8));
        }
    }
    $state['sales_active']=1;
    $state['sales_activated_at']=gmdate('c');
    update_option($key,$state,false);
    // Resume from the current cursor: existing records are inspected locally,
    // never scraped again just to convert approved draft listings to published.
    $existing=$wpdb->get_results($wpdb->prepare(
        "SELECT id,wc_product_id,source_payload FROM $src_table WHERE supplier_id=%d AND wc_product_id>0 ORDER BY id",8
    ),ARRAY_A);
    $published=0;$held=0;$errors=array();
    foreach($existing as $row) {
        $wc_id=(int)$row['wc_product_id'];
        $woo=wc_get_product($wc_id);
        $payload=json_decode((string)$row['source_payload'],true);
        $ok=$woo && is_array($payload) && $woo->is_type('variable')
            && (int)get_post_field('post_author',$wc_id)===4971
            && (bool)$woo->get_image_id() && (bool)$woo->get_category_ids()
            && in_array((string)($payload['stock_status']??''),array('instock','outofstock'),true);
        $vars=(array)($payload['variations']??array());
        if(!$vars || count($vars)!==(int)($payload['variation_count']??-1) ||
            count($woo?$woo->get_children():array())!==count($vars)){$ok=false;}
        foreach($vars as $v) {
            $kg=isset($v['weight_kg'])&&is_numeric($v['weight_kg'])?(float)$v['weight_kg']:0;
            if($kg<=0 || null===MDO_Tropitop_Shipping::cost_for_weight($kg)
                || !isset($v['display_price']) || (float)$v['display_price']<=0){$ok=false;break;}
        }
        if($ok){
            foreach($woo->get_children() as $child_id){
                $child=wc_get_product($child_id);
                if(!$child || (float)$child->get_weight()<=0 ||
                    null===MDO_Tropitop_Shipping::cost_for_weight((float)$child->get_weight())){
                    $ok=false;break;
                }
            }
        }
        try {
            if($ok){
                $woo->set_status('publish');
                $woo->set_catalog_visibility('visible');
                $woo->save();
                $published++;
            } else {
                if($woo){
                    $woo->set_status('draft');$woo->set_catalog_visibility('hidden');$woo->save();
                }
                $held++;
            }
        } catch(Throwable $e) {
            $errors[$wc_id]=mb_substr($e->getMessage(),0,160);
        }
    }
    echo 'TROPITOP_SALES_ACTIVATED='.wp_json_encode(array(
        'seller_enabled'=>true,'mainland_shipping_verified'=>true,'portugal_sales_blocked'=>true,
        'existing_published'=>$published,'held_for_validation'=>$held,'errors'=>$errors,
        'cursor'=>(int)$state['cursor'],'total'=>$total
    ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    return;
}

$limit = max( 1, min( 5, (int) ( getenv( 'TROPITOP_BULK_BATCH' ) ?: 2 ) ) );
$start = (int) $state['cursor'];
$retrying = false;
// The saved cursor is authoritative for first-pass progress. After reaching
// the end, retry only failed source URLs (at most twice), never regenerate the
// entire catalog merely because a previous upload was interrupted.
if ( $start >= $total && ! empty( $state['failed'] ) ) {
    foreach ( $state['failed'] as $failed_pid => $failure ) {
        if ( (int) ( $state['retry_attempts'][$failed_pid] ?? 0 ) >= 2 ) { continue; }
        $index = array_search( (string) ( $failure['url'] ?? '' ), $state['urls'], true );
        if ( false !== $index ) {
            $start = (int) $index;
            $retrying = true;
            break;
        }
    }
}
$end = $retrying ? min( $total, $start + 1 ) : min( $total, $start + $limit );
for ( $i = $start; $i < $end; $i++ ) {
    $woo_id = 0;
    $url = (string) $state['urls'][$i];
    $m = array();
    preg_match( '~/([0-9]+)(?:-[0-9]+)?-[a-z0-9-]+\.html$~i',
        (string) wp_parse_url( $url, PHP_URL_PATH ), $m );
    $pid = (string) ( $m[1] ?? $i );
    try {
        $payload = MDO_Connector_Tropitop::scrape_product( $url );
        $stock = (string) ( $payload['stock_status'] ?? 'unknown' );
        if ( ! in_array( $stock, array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'Stock sin verificación suficiente.' );
        }
        if ( (int) ( $payload['image_count'] ?? 0 ) < 1 ||
            mb_strlen( wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) ) < 80 ) {
            throw new RuntimeException( 'Faltan fotos o descripción de producto verificable.' );
        }
        $variations = (array) ( $payload['variations'] ?? array() );
        if ( ! $variations || count( $variations ) !== (int) ( $payload['variation_count'] ?? 0 ) ) {
            throw new RuntimeException( 'Variaciones incompletas.' );
        }
        $needs_weight = false;
        foreach ( $variations as $variation ) {
            if ( empty( $variation['attributes'] ) ||
                ! isset( $variation['display_price'] ) ||
                ! is_numeric( $variation['display_price'] ) ||
                (float) $variation['display_price'] <= 0.0 ) {
                throw new RuntimeException( 'Precio o variante inválida.' );
            }
            if ( ! isset( $variation['weight_kg'] ) ) {
                if ( empty( $variation['attributes']['Unidades'] ) ) {
                    throw new RuntimeException( 'Unidad de presentación no identificada.' );
                }
                $needs_weight = true;
            } elseif ( ! is_numeric( $variation['weight_kg'] ) ||
                (float) $variation['weight_kg'] <= 0.0 ||
                (float) $variation['weight_kg'] > 36.0 ) {
                throw new RuntimeException( 'Peso inviable o no cotizable.' );
            }
        }
        MDO_Connector_Tropitop::upsert_product( 8, $payload );
        $source_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $src_table WHERE supplier_id=%d AND source_url=%s ORDER BY id DESC LIMIT 1",
            8, $payload['source_url']
        ) );
        if ( ! $source_id ) { throw new RuntimeException( 'Origen sin registrar tras upsert.' ); }
        $woo_id = MDO_Woo_Importer::import_source_product( $source_id );
        $woo = wc_get_product( $woo_id );
        $can_publish_now = ! $needs_weight && ! $seller_disabled && $checkout_ready && (int)$supplier['active']===1;
        $expected_status = $can_publish_now ? 'publish' : 'draft';
        $expected_visibility = $can_publish_now ? 'visible' : 'hidden';
        if ( ! $woo || ! $woo->is_type( 'variable' ) ||
            (int) get_post_field( 'post_author', $woo_id ) !== 4971 ||
            $woo->get_status() !== $expected_status || $woo->get_catalog_visibility() !== $expected_visibility ||
            ! $woo->get_image_id() ||
            1 + count( $woo->get_gallery_image_ids() ) < min( 20, (int) $payload['image_count'] ) ||
            ! $woo->get_category_ids() ) {
            throw new RuntimeException( 'QA Woo: estado, autor, taxonomía o galería incompletos.' );
        }
        $weight_values = array();
        foreach ( $woo->get_children() as $child_id ) {
            $child = wc_get_product( $child_id );
            if ( ! $child ) { throw new RuntimeException( 'Variación Woo no encontrada.' ); }
            if ( (float) $child->get_weight() <= 0 ) { $needs_weight = true; }
            $weight_values[] = (float) $child->get_weight();
        }
        if ( count( $weight_values ) !== count( $variations ) ) {
            throw new RuntimeException( 'Número de variaciones guardadas diferente a origen.' );
        }
        if ( $needs_weight ) {
            update_post_meta( $woo_id, '_mdo_tropitop_prelaunch_validated', '0' );
            update_post_meta( $woo_id, '_mdo_tropitop_missing_shipping_weight', '1' );
            $state['failed'][$pid] = array(
                'url' => $url, 'woo_id' => (int) $woo_id,
                'reason' => 'Ficha guardada en borrador con variantes por unidades: falta el peso embalado para calcular los portes.',
            );
            echo 'TROPITOP_IMPORT_DRAFT_NEEDS_WEIGHT=' . (int) $woo_id . "\n";
        } else {
            update_post_meta( $woo_id, '_mdo_tropitop_prelaunch_validated', '1' );
            delete_post_meta( $woo_id, '_mdo_tropitop_missing_shipping_weight' );
            $state['imported'][$pid] = array(
                'woo_id' => (int) $woo_id, 'stock' => $stock,
                'options' => count( $variations ), 'images' => 1 + count( $woo->get_gallery_image_ids() ),
            );
            unset( $state['failed'][$pid] );
        }
        echo 'TROPITOP_IMPORT_OK=' . wp_json_encode( array(
            'source_id' => $pid, 'woo_id' => (int) $woo_id,
            'stock' => $stock, 'options' => count( $variations ),
        ), JSON_UNESCAPED_SLASHES ) . "\n";
    } catch ( Throwable $e ) {
        $state['failed'][$pid] = array( 'url' => $url, 'reason' => substr( $e->getMessage(), 0, 250 ) );
        // The seller can now be active: quarantine a SKU failing final QA.
        // A PHP error can occur before import_source_product returns its ID.
        // Recover only the unique supplier-specific SKU and verified WCFM author.
        if ( ! $woo_id ) {
            $candidate=wc_get_product_id_by_sku('TROPITOP-'.$pid);
            if ( $candidate && (int)get_post_field('post_author',$candidate)===4971 ) {
                $woo_id=(int)$candidate;
            }
        }
        if ( $woo_id && (int)get_post_field('post_author',$woo_id)===4971 ) {
            $unsafe = wc_get_product( (int) $woo_id );
            if ( $unsafe ) {
                $unsafe->set_status( 'draft' );
                $unsafe->set_catalog_visibility( 'hidden' );
                $unsafe->save();
            }
        }
        echo 'TROPITOP_IMPORT_REVIEW=' . wp_json_encode( array(
            'source_id' => $pid, 'error' => $e->getMessage(),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    }
    if ( $retrying ) {
        $state['retry_attempts'][$pid] = (int) ( $state['retry_attempts'][$pid] ?? 0 ) + 1;
    } else {
        $state['cursor'] = $i + 1;
    }
    update_option( $key, $state, false );
    unset( $payload, $woo );
    gc_collect_cycles();
}
$outstanding = 0;
foreach ( (array) ( $state['failed'] ?? array() ) as $fid => $failed ) {
    if ( (int) ( $state['retry_attempts'][$fid] ?? 0 ) < 2 &&
        in_array( (string) ( $failed['url'] ?? '' ), (array) $state['urls'], true ) ) { $outstanding++; }
}
if ( (int) $state['cursor'] >= $total && 0 === $outstanding ) {
    $state['finished_at'] = gmdate( 'c' );
    update_option( $key, $state, false );
    // Enable *source synchronisation*, not publication. The Woo seller stays
    // blocked and shipping verification remains false.
    $wpdb->update( $sup_table, array(
        'active' => 1, 'sync_frequency' => 'daily',
        'last_sync_at' => current_time( 'mysql' ),
        'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => 8 ) );
    echo 'TROPITOP_BULK_DONE=' . wp_json_encode( array(
        'discovered' => $total,
        'imported' => count( $state['imported'] ),
        'review_required' => count( $state['failed'] ),
        'retried_failed' => count( (array)( $state['retry_attempts'] ?? array() ) ),
        'seller_disabled' => $seller_disabled, 'checkout_enabled' => $checkout_ready,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
} else {
    echo 'TROPITOP_BULK_PROGRESS=' . (int) $state['cursor'] . '/' . $total .
        ' imported=' . count( $state['imported'] ) .
        ' review=' . count( $state['failed'] ) .
        ' retry_remaining=' . $outstanding . "\n";
}
