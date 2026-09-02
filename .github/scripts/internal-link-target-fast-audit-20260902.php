<?php
if (!defined('ABSPATH')) exit;
function emdo_itf_norm($url){$u=wp_parse_url(html_entity_decode((string)$url,ENT_QUOTES|ENT_HTML5,'UTF-8'));if(!$u||empty($u['path']))return null;$p='/'.trim($u['path'],'/').'/';return preg_replace('#/+#','/',$p);}
function emdo_itf_links($html){preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/isu',(string)$html,$m);return $m[2]??[];}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]);
$current=[];
foreach(get_posts(['post_type'=>'any','post_status'=>'publish','numberposts'=>-1]) as $p){$path=emdo_itf_norm(get_permalink($p->ID));if($path)$current[$path]=['id'=>$p->ID,'type'=>$p->post_type,'url'=>get_permalink($p->ID)];if($p->post_type==='post'){ $en=(string)get_post_meta($p->ID,'_en_US_post_name',true); if($en)$current['/en/'.trim($en,'/').'/']=['id'=>$p->ID,'type'=>'post-en','url'=>home_url('/en/'.trim($en,'/').'/')]; }}
$terms=get_terms(['hide_empty'=>false]); if(!is_wp_error($terms)){foreach($terms as $t){$link=get_term_link($t);if(!is_wp_error($link)){$path=emdo_itf_norm($link);if($path)$current[$path]=['id'=>$t->term_id,'type'=>'term','url'=>$link];}}}
$old=[];
global $wpdb;
$rows=$wpdb->get_results("SELECT pm.meta_value old_slug, pm.post_id, p.post_name, p.post_status FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID=pm.post_id WHERE pm.meta_key='_wp_old_slug' AND p.post_status='publish'");
foreach($rows as $r){$old['/'.trim($r->old_slug,'/').'/']=['id'=>(int)$r->post_id,'to'=>get_permalink((int)$r->post_id),'source'=>'wp_old_slug'];}
$custom=(array)get_option('emdo_authority_redirects_20260902',[]); foreach($custom as $from=>$to){$fp=emdo_itf_norm($from);if($fp)$old[$fp]=['to'=>$to,'source'=>'authority_redirect'];}
$uses=[];
foreach($posts as $p){foreach([['lang'=>'es','html'=>$p->post_content],['lang'=>'en','html'=>(string)get_post_meta($p->ID,'_en_US_post_content',true)]] as $cfg){foreach(emdo_itf_links($cfg['html']) as $href){$href=trim(html_entity_decode($href,ENT_QUOTES|ENT_HTML5,'UTF-8'));if($href===''||str_starts_with($href,'#')||str_starts_with($href,'mailto:')||str_starts_with($href,'tel:')||str_starts_with($href,'javascript:'))continue;$u=wp_parse_url($href);$host=strtolower($u['host']??'');if($host&&!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))continue;$path=emdo_itf_norm($href);if(!$path||isset($current[$path]))continue;$k=$cfg['lang'].'|'.$path;if(!isset($uses[$k]))$uses[$k]=['lang'=>$cfg['lang'],'path'=>$path,'uses'=>[]];$uses[$k]['uses'][]=$p->ID;}}}
$out=[];$counts=['current_object'=>0,'known_redirect'=>0,'unknown'=>0];
foreach($uses as $row){if(isset($current[$row['path']])){$row+=['classification'=>'current_object','target'=>$current[$row['path']]];$counts['current_object']++;}elseif(isset($old[$row['path']])){$row+=['classification'=>'known_redirect','target'=>$old[$row['path']]];$counts['known_redirect']++;}else{$row+=['classification'=>'unknown','target'=>null];$counts['unknown']++;}$out[]=$row;}
usort($out,fn($a,$b)=>strcmp($a['classification'],$b['classification'])?:strcmp($a['path'],$b['path']));
echo "EMDO_INTERNAL_TARGET_FAST_BEGIN\n";
echo wp_json_encode(['counts'=>$counts,'total'=>count($out),'rows'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_INTERNAL_TARGET_FAST_END\n";
