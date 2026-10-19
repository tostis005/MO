<?php
/** Fill missing Falang English product fields without touching native commerce data. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
$file = getenv('MDO_EN_PAYLOAD');
if (!$file || !is_readable($file)) { fwrite(STDERR, "Missing English payload\n"); exit(3); }
$data = json_decode(file_get_contents($file), true);
if (!is_array($data) || !isset($data['products']) || !is_array($data['products'])) { exit(4); }
function mdo20_plain($s) { return trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8'))); }
function mdo20_numbers($s) {
    preg_match_all('/\d+(?:[.,]\d+)?/u', mdo20_plain($s),$m);
    $out=[];
    foreach($m[0] as $v){ $v=str_replace(',','.',$v); if(strpos($v,'.')!==false){$v=rtrim(rtrim($v,'0'),'.');} if($v==='')$v='0';if(strpos($v,'.')===0)$v='0'.$v;$out[$v]=($out[$v]??0)+1; }
    ksort($out); return $out;
}
$used=[];
foreach((array)$wpdb->get_results("SELECT post_id,meta_value FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value<>''",ARRAY_A) as $r) {
    $used[sanitize_title((string)$r['meta_value'])]=(int)$r['post_id'];
}
$report=['requested'=>count($data['products']),'updated'=>0,'published_english'=>0,'unchanged'=>0,'skipped'=>[],'field_updates'=>['title'=>0,'slug'=>0,'excerpt'=>0,'content'=>0]];
$prepared=[];$backup=[];
foreach ($data['products'] as $r) {
    $id=(int)($r['id']??0); $p=get_post($id);
    if (!$p || $p->post_type!=='product' || $p->post_status!=='publish' || (int)$p->post_author!==(int)($r['author_id']??-1)) {
        $report['skipped'][]=['id'=>$id,'reason'=>'identity_or_status_changed'];continue;
    }
    if ($p->post_title!==($r['source_title']??null) || $p->post_excerpt!==($r['source_excerpt']??null) || $p->post_content!==($r['source_content']??null)) {
        $report['skipped'][]=['id'=>$id,'reason'=>'spanish_source_changed'];continue;
    }
    $src=['title'=>$p->post_title,'excerpt'=>$p->post_excerpt,'content'=>$p->post_content];
    $keys=['title'=>'_en_US_post_title','slug'=>'_en_US_post_name','excerpt'=>'_en_US_post_excerpt','content'=>'_en_US_post_content'];
    $values=[];$bad=[];
    foreach($keys as $field=>$key) {
        $current=(string)get_post_meta($id,$key,true);
        $candidate=(string)($r[$field]??'');
        if($field==='slug'){
            if(trim($current)!==''){$values[$field]=$current;continue;}
            $candidate=sanitize_title($candidate);
            if(!$candidate){$bad[]='empty_slug';continue;}
            if(isset($used[$candidate])&&$used[$candidate]!==$id)$candidate=sanitize_title($candidate.'-'.$id);
            if(isset($used[$candidate])&&$used[$candidate]!==$id){$bad[]='slug_collision';continue;}
            $values[$field]=$candidate;
        } else {
            if (mdo20_plain($current)!=='') {$values[$field]=$current;continue;}
            if(mdo20_plain($src[$field])==='' && $field!=='title') { $values[$field]=''; continue; }
            if(mdo20_plain($candidate)===''){$bad[]='missing_'.$field;continue;}
            if(mdo20_numbers($src[$field])!==mdo20_numbers($candidate)){$bad[]='numbers_'.$field;continue;}
            if($field==='title' && preg_match('/BIO\s*&\s*FT/iu',$src[$field]) && !preg_match('/BIO\s*&\s*FT/iu',$candidate)){$bad[]='brand_BIOFT';continue;}
            $values[$field]=$candidate;
        }
    }
    if($bad){$report['skipped'][]=['id'=>$id,'reason'=>implode(',',$bad)];continue;}
    if(mdo20_plain($values['title'])===''||$values['slug']===''||(mdo20_plain($src['content'])!==''&&mdo20_plain($values['content'])==='')||(mdo20_plain($src['excerpt'])!==''&&mdo20_plain($values['excerpt'])==='')){
        $report['skipped'][]=['id'=>$id,'reason'=>'still_incomplete'];continue;
    }
    $used[sanitize_title($values['slug'])]=$id;
    $changes=[];
    foreach($keys as $field=>$key){
        if((string)get_post_meta($id,$key,true)!==($values[$field]??''))$changes[$key]=[$field,$values[$field]];
    }
    $published=(string)get_post_meta($id,'_en_US_published',true);
    if($published!=='1')$changes['_en_US_published']=['published','1'];
    if((string)get_post_meta($id,'_en_US_ready',true)!=='1')$changes['_en_US_ready']=['ready','1'];
    if(!$changes){$report['unchanged']++;continue;}
    $old=[];foreach($changes as $key=>$change)$old[$key]=['exists'=>metadata_exists('post',$id,$key),'value'=>get_post_meta($id,$key,true)];
    $backup[$id]=$old;$prepared[$id]=$changes;
}
$backup_id='mdo_english_products_backup_20261020_'.gmdate('Ymd_His').'_'.substr(wp_generate_password(8,false,false),0,8);
if($prepared&&!add_option($backup_id,['created'=>gmdate('c'),'before'=>$backup], '', false)){
    fwrite(STDERR,"Backup could not be saved\n");exit(10);
}
foreach($prepared as $id=>$changes){
    foreach($changes as $key=>$change){update_post_meta($id,$key,$change[1]);if(isset($report['field_updates'][$change[0]]))$report['field_updates'][$change[0]]++;}
    $report['updated']++;
    if(isset($changes['_en_US_published']))$report['published_english']++;
    clean_post_cache($id);
}
$report['backup_option']=$prepared?$backup_id:null;
$report['skipped_count']=count($report['skipped']);
$report['verified']=true;
foreach($prepared as $id=>$changes){
    foreach($changes as $key=>$change){
        if((string)get_post_meta($id,$key,true)!==(string)$change[1]){$report['verified']=false;$report['verification_errors'][]=['id'=>$id,'key'=>$key];}
    }
}
wp_cache_flush();
echo 'MDO_EN_20261020='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(!$report['verified'])exit(11);
