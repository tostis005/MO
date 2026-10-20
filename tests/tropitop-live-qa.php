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
    echo "TROPITOP_LIVE_QA_OK\n";
}catch(Throwable $e){fwrite(STDERR,'TROPITOP_LIVE_QA_FAILED '.$e->getMessage()."\n");exit(1);}
