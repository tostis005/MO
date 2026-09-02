<?php
if (!defined('ABSPATH')) exit;
function emdo_its_norm($url){$u=wp_parse_url(html_entity_decode((string)$url,ENT_QUOTES|ENT_HTML5,'UTF-8'));if(!$u||empty($u['path']))return null;$p='/'.trim($u['path'],'/').'/';return preg_replace('#/+#','/',$p);}
function emdo_its_links($html){preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/isu',(string)$html,$m);return $m[2]??[];}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]);
$current=[];foreach($posts as $p){$current[emdo_its_norm(get_permalink($p->ID))]=true;$es=(string)get_post_meta($p->ID,'_en_US_post_name',true);if($es)$current['/en/'.trim($es,'/').'/']=true;}
$uses=[];
foreach($posts as $p){foreach([['lang'=>'es','html'=>$p->post_content],['lang'=>'en','html'=>(string)get_post_meta($p->ID,'_en_US_post_content',true)]] as $cfg){foreach(emdo_its_links($cfg['html']) as $href){$href=trim(html_entity_decode($href,ENT_QUOTES|ENT_HTML5,'UTF-8'));if($href===''||str_starts_with($href,'#')||str_starts_with($href,'mailto:')||str_starts_with($href,'tel:')||str_starts_with($href,'javascript:'))continue;$u=wp_parse_url($href);$host=strtolower($u['host']??'');if($host&&!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))continue;$path=emdo_its_norm($href);if(!$path||isset($current[$path]))continue;$k=$cfg['lang'].'|'.$path;if(!isset($uses[$k]))$uses[$k]=['lang'=>$cfg['lang'],'path'=>$path,'uses'=>[]];$uses[$k]['uses'][]=$p->ID;}}}
$rows=[];$counts=['resolved_wp'=>0,'http_200'=>0,'redirect'=>0,'broken'=>0,'other'=>0];
foreach($uses as $row){$url=home_url($row['path']);$id=url_to_postid($url);if($id){$row+=['classification'=>'resolved_wp','code'=>200,'location'=>get_permalink($id),'object_id'=>$id];$counts['resolved_wp']++;$rows[]=$row;continue;}
 $r=wp_remote_head($url,['timeout'=>5,'redirection'=>0,'user-agent'=>'Mozilla/5.0 EMDO-LinkAudit/1.0']);
 if(is_wp_error($r)){$row+=['classification'=>'broken','code'=>0,'error'=>$r->get_error_message(),'location'=>null];$counts['broken']++;$rows[]=$row;continue;}
 $code=(int)wp_remote_retrieve_response_code($r);$loc=(string)wp_remote_retrieve_header($r,'location');
 if($code>=300&&$code<400&&$loc!==''){$class='redirect';$counts['redirect']++;}
 elseif($code===200){$class='http_200';$counts['http_200']++;}
 elseif($code===404||$code===410){$class='broken';$counts['broken']++;}
 else{$class='other';$counts['other']++;}
 $row+=['classification'=>$class,'code'=>$code,'location'=>$loc?:null];$rows[]=$row;
}
usort($rows,function($a,$b){$order=['broken'=>0,'redirect'=>1,'other'=>2,'resolved_wp'=>3,'http_200'=>4];$d=($order[$a['classification']]??9)<=>($order[$b['classification']]??9);return $d?:strcmp($a['path'],$b['path']);});
echo "EMDO_INTERNAL_TARGET_STATUS_BEGIN\n";
echo wp_json_encode(['counts'=>$counts,'total'=>count($rows),'rows'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_INTERNAL_TARGET_STATUS_END\n";
