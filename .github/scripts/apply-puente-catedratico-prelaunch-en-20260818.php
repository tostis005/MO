<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$payload_path=getenv('MDO_PAYLOAD_PATH') ?: '/tmp/mdo-prelaunch-en-output.json';
$payload=json_decode(file_get_contents($payload_path),true);
if(!is_array($payload)||!isset($payload['products'])||count($payload['products'])!==201){fwrite(STDERR,"Invalid payload\n");exit(20);}
$expected=[4508=>'Puente Robles',4509=>'El Catedrático'];
$counts=[];foreach($payload['products'] as $r){$counts[$r['vendor']]=($counts[$r['vendor']]??0)+1;}
if(($counts['Puente Robles']??0)!==106||($counts['El Catedrático']??0)!==95){fwrite(STDERR,'Bad distribution '.wp_json_encode($counts)."\n");exit(21);}
function mdo_pre_vis($s){return trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
function mdo_pre_num_canon($x){$x=str_replace(',','.',(string)$x);if(strpos($x,'.')!==false){$x=rtrim(rtrim($x,'0'),'.');}if($x==='')$x='0';if(strpos($x,'.')===0)$x='0'.$x;return $x;}
function mdo_pre_nums($s){$s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');preg_match_all('/\d+(?:[.,]\d+)?/u',$s,$m);$o=[];foreach($m[0] as $x){$x=mdo_pre_num_canon($x);$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
function mdo_pre_ecodes($s){$s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');preg_match_all('/\bE\s*-?\s*\d+[A-Z]*\b/ui',$s,$m);$o=[];foreach($m[0] as $x){$x=preg_replace('/[^A-Z0-9]/','',strtoupper($x));$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
$spanish='/\b(?:para|desde|hasta|producto|almacenamiento|consumo|ingredientes|conservaci[oó]n|env[ií]o|peso|cerdo|cerdos|curaci[oó]n|deshuesad[oa]s?|cortad[oa]s?|piezas?|sobres?|codillo|punta|paletas?|jam[oó]nes?|lomos?|lomito|cebo\s+de\s+campo|raza\s+ib[eé]rica|alimentaci[oó]n|recomendaciones|caducidad|meses|lotes?|surtido|c[aá]tedra|grs)\b/ui';
$bad='/\b(?:palette|pallet|healing|envelopes?|sachets?|acorna|lome|cebum|headboard|reservation|cesarean|caesarean)\b|Iberian\s+race|Iberic\s+race|Iberian\s+Iberian|CALL\s+6|MDOZZ|ZZTERM|ZZNUM|ZZSEG/ui';
$used=[];$existing=$wpdb->get_results("SELECT post_id,meta_value FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value<>''",ARRAY_A);foreach($existing as $e){$used[sanitize_title($e['meta_value'])]=(int)$e['post_id'];}
$validated=[];$problems=[];
foreach($payload['products'] as $row){
  $id=(int)$row['id'];$author=(int)$row['author_id'];$p=get_post($id);
  if(!$p||$p->post_type!=='product'||$p->post_status!==$row['source_status']){$problems[]=['id'=>$id,'reason'=>'identity_or_status'];continue;}
  if(!isset($expected[$author])||(int)$p->post_author!==$author||$row['vendor']!==$expected[$author]){$problems[]=['id'=>$id,'reason'=>'vendor'];continue;}
  $title=(string)$row['title'];$content=(string)$row['content'];$excerpt=(string)$row['excerpt'];$slug=sanitize_title((string)$row['slug']);
  if(mdo_pre_vis($title)===''||$slug===''){$problems[]=['id'=>$id,'reason'=>'missing_title_or_slug'];continue;}
  if(mdo_pre_vis($p->post_content)!==''&&mdo_pre_vis($content)===''){$problems[]=['id'=>$id,'reason'=>'missing_content'];continue;}
  if(mdo_pre_vis($p->post_excerpt)!==''&&mdo_pre_vis($excerpt)===''){$problems[]=['id'=>$id,'reason'=>'missing_excerpt'];continue;}
  $src=$p->post_title.' '.$p->post_content.' '.$p->post_excerpt;$dst=$title.' '.$content.' '.$excerpt;
  if(mdo_pre_nums($src)!==mdo_pre_nums($dst)){$problems[]=['id'=>$id,'reason'=>'number_mismatch','src'=>mdo_pre_nums($src),'dst'=>mdo_pre_nums($dst)];continue;}
  if(mdo_pre_ecodes($src)!==mdo_pre_ecodes($dst)){$problems[]=['id'=>$id,'reason'=>'ecode_mismatch'];continue;}
  $vis=mdo_pre_vis($dst);
  if(preg_match($spanish,$vis,$m)){$problems[]=['id'=>$id,'reason'=>'spanish_residue','match'=>$m[0]];continue;}
  if(preg_match($bad,$vis,$m)){$problems[]=['id'=>$id,'reason'=>'bad_english','match'=>$m[0]];continue;}
  if(isset($used[$slug])&&$used[$slug]!==$id){$slug=sanitize_title($slug.'-'.($author===4508?'puente-robles':'el-catedratico'));}
  if(isset($used[$slug])&&$used[$slug]!==$id){$slug=sanitize_title($slug.'-'.$id);} $used[$slug]=$id;$row['slug']=$slug;$validated[]=$row;
}
if($problems){echo 'PRECHECK_BAD '.wp_json_encode(array_slice($problems,0,100),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";fwrite(STDERR,'Precheck failed '.count($problems)." products\n");exit(22);}
$backup=['created'=>gmdate('c'),'site'=>get_option('siteurl'),'products'=>[]];
foreach($validated as $row){$id=(int)$row['id'];$all=get_post_meta($id);$en=[];foreach($all as $k=>$vals){if(strpos($k,'_en_US_')===0)$en[$k]=$vals;}$backup['products'][]=['id'=>$id,'status'=>get_post_status($id),'meta'=>$en];}
$up=wp_upload_dir();$dir=trailingslashit($up['basedir']).'mdo-translation-backups';wp_mkdir_p($dir);$backup_path=trailingslashit($dir).'prelaunch-puente-catedratico-'.gmdate('Ymd-His').'.json';file_put_contents($backup_path,wp_json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
foreach($validated as $row){$id=(int)$row['id'];update_post_meta($id,'_en_US_post_title',(string)$row['title']);update_post_meta($id,'_en_US_post_content',(string)$row['content']);update_post_meta($id,'_en_US_post_excerpt',(string)$row['excerpt']);update_post_meta($id,'_en_US_post_name',(string)$row['slug']);update_post_meta($id,'_en_US_published','0');update_post_meta($id,'_en_US_ready','1');}
wp_cache_flush();
$verify=[];$bad_after=[];
foreach($validated as $row){$id=(int)$row['id'];$vendor=$row['vendor'];$verify[$vendor]=($verify[$vendor]??0)+1;$p=get_post($id);$title=(string)get_post_meta($id,'_en_US_post_title',true);$content=(string)get_post_meta($id,'_en_US_post_content',true);$excerpt=(string)get_post_meta($id,'_en_US_post_excerpt',true);$slug=(string)get_post_meta($id,'_en_US_post_name',true);$pub=(string)get_post_meta($id,'_en_US_published',true);$ready=(string)get_post_meta($id,'_en_US_ready',true);if($p->post_status!==$row['source_status']||$title!==$row['title']||$content!==$row['content']||$excerpt!==$row['excerpt']||sanitize_title($slug)!==sanitize_title($row['slug']))$bad_after[]=['id'=>$id,'reason'=>'write_mismatch'];if($pub!=='0'||$ready!=='1')$bad_after[]=['id'=>$id,'reason'=>'prelaunch_flags','published'=>$pub,'ready'=>$ready];$v=mdo_pre_vis($title.' '.$content.' '.$excerpt);if(preg_match($spanish,$v,$m)||preg_match($bad,$v,$m))$bad_after[]=['id'=>$id,'reason'=>'postwrite_language','match'=>$m[0]];}
if($bad_after){echo 'VERIFY_BAD '.wp_json_encode($bad_after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";fwrite(STDERR,"Post-write verify failed\n");exit(23);}
echo 'APPLIED_OK '.wp_json_encode(['translated'=>$verify,'total'=>count($validated),'published_en'=>0,'ready_en'=>count($validated),'backup'=>$backup_path],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
