<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$payload_path=getenv('MDO_PAYLOAD_PATH') ?: '/tmp/mdo-final-en-output.json';
$payload=json_decode(file_get_contents($payload_path),true);
if(!is_array($payload)||!isset($payload['products'])||count($payload['products'])!==205){fwrite(STDERR,"Invalid payload\n");exit(20);}
$expected=[4508=>'Puente Robles',4509=>'El Catedrático'];
$hidalgo=[1375=>true,1586=>true,4188=>true,5080=>true];
$counts=[];foreach($payload['products'] as $r){$counts[$r['vendor']]=($counts[$r['vendor']]??0)+1;}
if(($counts['Puente Robles']??0)!==106||($counts['El Catedrático']??0)!==95||($counts['Hidalgo de la Jara']??0)!==4){fwrite(STDERR,'Bad distribution '.wp_json_encode($counts)."\n");exit(21);}
function mdo_visible($s){return trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
function mdo_nums($s){$s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');preg_match_all('/\d+(?:[.,]\d+)?/u',$s,$m);$o=[];foreach($m[0] as $x){$x=str_replace(',','.',$x);$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
function mdo_ecodes($s){$s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');preg_match_all('/\bE\s*-?\s*\d+[A-Z]*\b/ui',$s,$m);$o=[];foreach($m[0] as $x){$x=preg_replace('/[^A-Z0-9]/','',strtoupper($x));$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
$spanish='/\b(?:para|desde|hasta|producto|almacenamiento|consumo|ingredientes|conservaci[oó]n|env[ií]o|peso|cerdo|cerdos|curaci[oó]n|deshuesad[oa]s?|cortad[oa]s?|piezas?|sobres?|codillo|punta|paleta|jam[oó]n|lomo|lomito|cebo\s+de\s+campo|raza\s+ib[eé]rica|alimentaci[oó]n|recomendaciones|caducidad|meses|lote|surtido|c[aá]tedra|grs)\b/ui';
$badenglish='/\b(?:palette|pallet|healing|envelopes?|sachets?|acorna|lome|cebum|ENGS)\b|Iberian\s+race|Iberic\s+race|CALL\s+6|MDOZZ|ZZTERM|ZZNUM|ZZSEG/ui';
$used=[];$existing=$wpdb->get_results("SELECT post_id,meta_value FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value<>''",ARRAY_A);foreach($existing as $e){$used[sanitize_title($e['meta_value'])]=(int)$e['post_id'];}
$validated=[];$problems=[];
foreach($payload['products'] as $row){
  $id=(int)$row['id'];$p=get_post($id);
  if(!$p||$p->post_type!=='product'||$p->post_status!==$row['source_status']){$problems[]=['id'=>$id,'reason'=>'identity_or_status'];continue;}
  if(isset($expected[(int)$row['author_id']])){
    if((int)$p->post_author!==(int)$row['author_id']||$row['vendor']!==$expected[(int)$row['author_id']]){$problems[]=['id'=>$id,'reason'=>'vendor'];continue;}
  }elseif(isset($hidalgo[$id])){
    if($row['vendor']!=='Hidalgo de la Jara'){$problems[]=['id'=>$id,'reason'=>'hidalgo_vendor'];continue;}
  }else{$problems[]=['id'=>$id,'reason'=>'unexpected_target'];continue;}
  $title=(string)$row['title'];$content=(string)$row['content'];$excerpt=(string)$row['excerpt'];$slug=sanitize_title((string)$row['slug']);
  if(mdo_visible($title)===''||$slug===''){$problems[]=['id'=>$id,'reason'=>'missing_title_or_slug'];continue;}
  if(mdo_visible($p->post_content)!==''&&mdo_visible($content)===''){$problems[]=['id'=>$id,'reason'=>'missing_content'];continue;}
  if(mdo_visible($p->post_excerpt)!==''&&mdo_visible($excerpt)===''){$problems[]=['id'=>$id,'reason'=>'missing_excerpt'];continue;}
  $src=$p->post_title.' '.$p->post_content.' '.$p->post_excerpt;$dst=$title.' '.$content.' '.$excerpt;
  if(mdo_nums($src)!==mdo_nums($dst)){$problems[]=['id'=>$id,'reason'=>'number_mismatch','src'=>mdo_nums($src),'dst'=>mdo_nums($dst)];continue;}
  if(mdo_ecodes($src)!==mdo_ecodes($dst)){$problems[]=['id'=>$id,'reason'=>'ecode_mismatch','src'=>mdo_ecodes($src),'dst'=>mdo_ecodes($dst)];continue;}
  $vis=mdo_visible($dst);
  if(preg_match($spanish,$vis,$m)){$problems[]=['id'=>$id,'reason'=>'spanish_residue','match'=>$m[0],'sample'=>mb_substr($vis,max(0,mb_stripos($vis,$m[0])-90),260)];continue;}
  if(preg_match($badenglish,$vis,$m)){$problems[]=['id'=>$id,'reason'=>'bad_english','match'=>$m[0],'sample'=>mb_substr($vis,max(0,mb_stripos($vis,$m[0])-90),260)];continue;}
  if(isset($used[$slug])&&$used[$slug]!==$id){$suffix=$row['vendor']==='Puente Robles'?'puente-robles':($row['vendor']==='El Catedrático'?'el-catedratico':'hidalgo-de-la-jara');$slug=sanitize_title($slug.'-'.$suffix);}
  if(isset($used[$slug])&&$used[$slug]!==$id){$slug=sanitize_title($slug.'-'.$id);} $used[$slug]=$id;
  $row['slug']=$slug;$validated[]=$row;
}
if($problems){echo 'PRECHECK_BAD '.wp_json_encode(array_slice($problems,0,60),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";fwrite(STDERR,'Precheck failed '.count($problems)." products\n");exit(22);}
// Backup every existing English metadata field for exact rollback.
$backup=['created'=>gmdate('c'),'site'=>get_option('siteurl'),'products'=>[]];
foreach($validated as $row){$id=(int)$row['id'];$all=get_post_meta($id);$en=[];foreach($all as $k=>$vals){if(strpos($k,'_en_US_')===0)$en[$k]=$vals;}$backup['products'][]=['id'=>$id,'status'=>get_post_status($id),'meta'=>$en];}
$up=wp_upload_dir();$dir=trailingslashit($up['basedir']).'mdo-translation-backups';wp_mkdir_p($dir);$backup_path=trailingslashit($dir).'final-en-v72-'.gmdate('Ymd-His').'.json';file_put_contents($backup_path,wp_json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
foreach($validated as $row){
  $id=(int)$row['id'];update_post_meta($id,'_en_US_post_title',(string)$row['title']);update_post_meta($id,'_en_US_post_content',(string)$row['content']);update_post_meta($id,'_en_US_post_excerpt',(string)$row['excerpt']);update_post_meta($id,'_en_US_post_name',(string)$row['slug']);
  if($row['vendor']==='Puente Robles'||$row['vendor']==='El Catedrático'){update_post_meta($id,'_en_US_published','0');update_post_meta($id,'_en_US_ready','1');}
  else{update_post_meta($id,'_en_US_published','1');delete_post_meta($id,'_en_US_ready');}
}
wp_cache_flush();
// Verify writes and launch flags.
$verify=[];$vb=[];foreach($validated as $row){$id=(int)$row['id'];$p=get_post($id);$vendor=$row['vendor'];$verify[$vendor]=($verify[$vendor]??0)+1;$title=(string)get_post_meta($id,'_en_US_post_title',true);$content=(string)get_post_meta($id,'_en_US_post_content',true);$excerpt=(string)get_post_meta($id,'_en_US_post_excerpt',true);$slug=(string)get_post_meta($id,'_en_US_post_name',true);$pub=(string)get_post_meta($id,'_en_US_published',true);$ready=(string)get_post_meta($id,'_en_US_ready',true);
  if($p->post_status!==$row['source_status']||$title!==$row['title']||$content!==$row['content']||$excerpt!==$row['excerpt']||sanitize_title($slug)!==sanitize_title($row['slug']))$vb[]=['id'=>$id,'reason'=>'write_mismatch'];
  if(($vendor==='Puente Robles'||$vendor==='El Catedrático')&&($pub!=='0'||$ready!=='1'))$vb[]=['id'=>$id,'reason'=>'prelaunch_flags','pub'=>$pub,'ready'=>$ready];
  if($vendor==='Hidalgo de la Jara'&&$pub!=='1')$vb[]=['id'=>$id,'reason'=>'hidalgo_not_published'];
}
// Re-check Tolecarnes without modifying it.
$to_ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",4507));$to_ready=0;$to_residue=[];foreach($to_ids as $id){$p=get_post((int)$id);$t=(string)get_post_meta($id,'_en_US_post_title',true);$c=(string)get_post_meta($id,'_en_US_post_content',true);$e=(string)get_post_meta($id,'_en_US_post_excerpt',true);$s=(string)get_post_meta($id,'_en_US_post_name',true);if(mdo_visible($t)!==''&&$s!==''&&(mdo_visible($p->post_content)===''||mdo_visible($c)!==''))$to_ready++;$vis=mdo_visible($t.' '.$c.' '.$e);if(preg_match($spanish,$vis,$m)||preg_match($badenglish,$vis,$m))$to_residue[]=['id'=>(int)$id,'match'=>$m[0]];}
if(count($to_ids)!==39||$to_ready!==39||$to_residue)$vb[]=['reason'=>'tolecarnes_verify','total'=>count($to_ids),'ready'=>$to_ready,'residue'=>$to_residue];
if($vb){echo 'VERIFY_BAD '.wp_json_encode($vb,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";fwrite(STDERR,"Post-write verify failed\n");exit(23);}
echo 'APPLIED_OK '.wp_json_encode(['translated'=>$verify,'tolecarnes'=>['total'=>count($to_ids),'ready'=>$to_ready],'backup'=>$backup_path],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
