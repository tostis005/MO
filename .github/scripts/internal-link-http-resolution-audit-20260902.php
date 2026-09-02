<?php
if (!defined('ABSPATH')) exit;

function emdo_http_norm($url){$u=wp_parse_url(html_entity_decode((string)$url,ENT_QUOTES|ENT_HTML5,'UTF-8'));if(!$u||empty($u['path']))return null;$p='/'.trim($u['path'],'/').'/';return preg_replace('#/+#','/',$p);}
function emdo_http_links($html){preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/isu',(string)$html,$m,PREG_SET_ORDER);$o=[];foreach($m as $x){$h=trim(html_entity_decode($x[2],ENT_QUOTES|ENT_HTML5,'UTF-8'));if($h===''||str_starts_with($h,'#')||preg_match('#^(?:mailto|tel|javascript):#i',$h))continue;$o[]=['href'=>$h,'anchor'=>trim(preg_replace('/\s+/u',' ',wp_strip_all_tags($x[3])))];}return $o;}

$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]);
$current=['es'=>[],'en'=>[]];
foreach($posts as $p){$ep=emdo_http_norm(get_permalink($p->ID));if($ep)$current['es'][$ep]=$p->ID;$ens=(string)get_post_meta($p->ID,'_en_US_post_name',true);if($ens)$current['en']['/en/'.trim($ens,'/').'/']=$p->ID;}
$un=[];
foreach($posts as $p){foreach(['es','en'] as $lang){$html=$lang==='es'?$p->post_content:(string)get_post_meta($p->ID,'_en_US_post_content',true);foreach(emdo_http_links($html) as $ln){$parts=wp_parse_url($ln['href']);$host=strtolower($parts['host']??'');if($host&&!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))continue;$path=emdo_http_norm($ln['href']);if(!$path||!preg_match('#^/(?:en/)?[^/]+/$#',$path)||isset($current[$lang][$path]))continue;$key=$lang.'|'.$path;if(!isset($un[$key]))$un[$key]=['lang'=>$lang,'path'=>$path,'from'=>[],'anchors'=>[]];$un[$key]['from'][$p->ID]=1;if($ln['anchor']!=='')$un[$key]['anchors'][$ln['anchor']]=1;}}}

$queue=[];foreach($un as $key=>$r)$queue[]=$key;
$results=[];$batchSize=15;
for($offset=0;$offset<count($queue);$offset+=$batchSize){$batch=array_slice($queue,$offset,$batchSize);$mh=curl_multi_init();$handles=[];
 foreach($batch as $key){$r=$un[$key];$url=rtrim(home_url('/'),'/').$r['path'];$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_RETURNTRANSFER=>true,CURLOPT_USERAGENT=>'EMDO-Internal-Link-Audit/1.0',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_HEADER=>false]);curl_multi_add_handle($mh,$ch);$handles[$key]=$ch;}
 do{$status=curl_multi_exec($mh,$active);if($active)curl_multi_select($mh,1.0);}while($active&&$status===CURLM_OK);
 foreach($handles as $key=>$ch){$r=$un[$key];$eff=(string)curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$redir=(int)curl_getinfo($ch,CURLINFO_REDIRECT_COUNT);$err=curl_error($ch);$effPath=emdo_http_norm($eff);$lang=$r['lang'];$kind='other';$targetId=null;
  if($err!=='')$kind='error';elseif($code>=400)$kind='broken';elseif($code===200&&$effPath&&isset($current[$lang][$effPath])){$targetId=$current[$lang][$effPath];$kind=$redir>0?'canonical_redirect':'current_200';}elseif($code===200)$kind=$redir>0?'redirect_nonpost':'valid_nonpost';
  $results[]=['lang'=>$lang,'path'=>$r['path'],'from'=>array_map('intval',array_keys($r['from'])),'anchors'=>array_keys($r['anchors']),'http_code'=>$code,'redirects'=>$redir,'effective_path'=>$effPath,'kind'=>$kind,'target_id'=>$targetId,'target_title'=>$targetId?get_the_title($targetId):null,'error'=>$err?:null];curl_multi_remove_handle($mh,$ch);curl_close($ch);}
 curl_multi_close($mh);
}
$counts=['total'=>count($results),'canonical_redirect'=>0,'valid_nonpost'=>0,'redirect_nonpost'=>0,'broken'=>0,'error'=>0,'other'=>0,'current_200'=>0];foreach($results as $r){if(isset($counts[$r['kind']]))$counts[$r['kind']]++;else$counts['other']++;}
echo "EMDO_HTTP_LINK_RESOLUTION_BEGIN\n";echo wp_json_encode(['counts'=>$counts,'items'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";echo "EMDO_HTTP_LINK_RESOLUTION_END\n";
