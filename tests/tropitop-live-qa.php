<?php
/**
 * Non-mutating QA against Tropitop public product HTML.
 * Only run on demand/CI. No cart, checkout or production WordPress changes.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('MDO_SUPPLIER_SYNC_VERSION', '1.0.67');
function wp_parse_url($url,$part=-1){ return $part===-1?parse_url($url):parse_url($url,$part); }
function esc_url_raw($v){return $v;}
function esc_html($v){return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function sanitize_text_field($v){return trim(strip_tags((string)$v));}
function wp_strip_all_tags($v){return strip_tags((string)$v);}
function wp_kses($v,$tags){return strip_tags((string)$v,'<p><h2><h3><h4><ul><ol><li><strong><b><em><br><span>');}
function wp_json_encode($data,$opts=0){return json_encode($data,$opts);}
function apply_filters($filter,$default){return $default;}
function is_wp_error($v){return false;}
function wp_remote_retrieve_response_code($v){return $v['response']['code'];}
function wp_remote_retrieve_body($v){return $v['body'];}
function wp_remote_get($url,$opts=array()){
    $u=parse_url($url);
    if(($u['scheme']??'')!=='https' || ($u['host']??'')!=='tropitop.com') throw new RuntimeException('non Tropitop URL');
    $ctx=stream_context_create(array('http'=>array(
        'method'=>'GET','timeout'=>min(20,(int)($opts['timeout']??18)),
        'header'=>"Accept-Language: es-ES,es;q=0.9\r\nUser-Agent: EMDO-catalog-QA\r\n",
        'follow_location'=>1,'max_redirects'=>2
    )));
    $body=@file_get_contents($url,false,$ctx);
    $headers=$http_response_header??array();$status=0;
    foreach($headers as $h){if(preg_match('~^HTTP/\S+\s+(\d+)~',$h,$m)) $status=(int)$m[1];}
    echo "TROPITOP_REQUEST ".parse_url($url,PHP_URL_PATH)." status=".$status." bytes=".strlen((string)$body)."\n";
    return array('body'=>(string)$body,'response'=>array('code'=>$status));
}
require_once dirname(__DIR__).'/mdo-supplier-sync/connectors/class-mdo-connector-tropitop.php';
$url='https://tropitop.com/es/aguacates/33-comprar-aguacate-bacon.html';
try{
    $p=MDO_Connector_Tropitop::scrape_product($url);
    $result=array(
        'source_url'=>$p['source_url'],'title'=>$p['title'],
        'stock_status'=>$p['stock_status'],
        'count'=>$p['variation_count'],
        'variations'=>array_map(static fn($v)=>array('peso'=>$v['attributes']['Peso']??'','precio'=>$v['display_price'],'disponible'=>$v['is_in_stock'],'weight'=>$v['weight_kg']),$p['variations']),
        'image_count'=>$p['image_count'],'description'=>$p['description']
    );
    echo 'TROPITOP_LIVE_PRODUCT '.json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    if($p['variation_count']!==3)throw new RuntimeException('Expected exactly 3 weights; got '.$p['variation_count']);
    foreach(array('3 kg'=>14.9,'4 kg'=>17.9,'9 kg'=>32.9) as $weight=>$expected){
        $found=false;
        foreach($p['variations'] as $v){
            if(($v['attributes']['Peso']??'')===$weight && abs($v['display_price']-$expected)<.01)$found=true;
        }
        if(!$found)throw new RuntimeException('Missing real weight price '.$weight);
    }
    if($p['stock_status']!=='instock')throw new RuntimeException('Stock not conclusively verified');
    $catalog = MDO_Connector_Tropitop::discover( array(
        'source_url' => 'https://tropitop.com/es/84-disponible',
        'exclusion_url_fragments' => '[]',
    ) );
    $count = count( $catalog['products'] ?? array() );
    echo 'TROPITOP_DISCOVERED_AVAILABLE=' . $count . "\n";
    if ( $count < 130 ) { throw new RuntimeException( 'Catalog pagination incomplete: ' . $count ); }
    $kiwi = MDO_Connector_Tropitop::scrape_product(
        'https://tropitop.com/es/frutas/2723-4798-kiwi-amarillo.html' );
    echo 'TROPITOP_KIWI_QA=' . json_encode( array(
        'title'=>$kiwi['title'],'stock'=>$kiwi['stock_status'],'variants'=>$kiwi['variation_count'],
        'copy_length'=>strlen( $kiwi['description'] ),'images'=>$kiwi['image_count'],
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    if ( $kiwi['variation_count'] < 3 || $kiwi['image_count'] < 1 ||
        strlen( $kiwi['description'] ) < 100 ) {
        throw new RuntimeException( 'General Tropitop parser missing Kiwi attributes or content.' );
    }
    $unit_pack = MDO_Connector_Tropitop::scrape_product(
        'https://tropitop.com/es/packs/2732-4810-comprar-pack-tropical-smoothie-online.html' );
    $unit_variants = (array) ( $unit_pack['variations'] ?? array() );
    echo 'TROPITOP_UNIT_PACK_QA=' . json_encode( array(
        'title'=>$unit_pack['title'],'stock'=>$unit_pack['stock_status'],
        'count'=>$unit_pack['variation_count'],'variations'=>$unit_variants,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    if ( !$unit_variants || empty( $unit_variants[0]['attributes']['Unidades'] ) ||
        isset( $unit_variants[0]['weight_kg'] ) ) {
        throw new RuntimeException( 'Unit-only products must retain real unit count and no invented kg.' );
    }
    echo "TROPITOP_LIVE_QA_OK\n";
}catch(Throwable $e){fwrite(STDERR,'TROPITOP_LIVE_QA_FAILED '.$e->getMessage()."\n");exit(1);}
