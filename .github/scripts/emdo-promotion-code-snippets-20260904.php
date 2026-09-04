<?php
if (!defined('ABSPATH')) exit(1);
if(!class_exists('MDO_Home_Featured_Special')) throw new RuntimeException('home special class missing');
$block=MDO_Home_Featured_Special::render();
if(stripos($block,'Montjam')===false || stripos($block,'225')===false) throw new RuntimeException('Rendered special block does not contain Montjam/225');
$url=add_query_arg('mdo_verify',(string)time(),home_url('/'));
$r=wp_remote_get($url,['timeout'=>30,'redirection'=>3,'headers'=>['Cache-Control'=>'no-cache','Pragma'=>'no-cache']]);
if(is_wp_error($r)) throw new RuntimeException($r->get_error_message());
$code=(int)wp_remote_retrieve_response_code($r); $body=(string)wp_remote_retrieve_body($r);
$has_marker=strpos($body,'data-mdo-home-featured-special=')!==false;
$has_montjam=stripos($body,'Montjam')!==false; $has_225=stripos($body,'225')!==false;
if($code!==200) throw new RuntimeException('Home HTTP '.$code);
if(!$has_marker || !$has_montjam || !$has_225) throw new RuntimeException('Fresh home response missing featured Montjam special');
echo 'HOME_SPECIAL_HTTP_VERIFIED:'.wp_json_encode(['http'=>$code,'has_marker'=>$has_marker,'has_montjam'=>$has_montjam,'has_225'=>$has_225,'bytes'=>strlen($body)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
