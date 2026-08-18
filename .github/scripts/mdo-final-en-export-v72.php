<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$vendors = [4508 => 'Puente Robles', 4509 => 'El Catedrático'];
$hidalgo_ids = [1375,1586,4188,5080];
$out = ['site'=>get_option('siteurl'),'generated'=>gmdate('c'),'products'=>[]];
foreach ($vendors as $uid=>$vendor) {
    $u=get_user_by('id',$uid);
    if(!$u || stripos(remove_accents($u->display_name),remove_accents($vendor))===false){fwrite(STDERR,"Vendor identity mismatch {$uid}\n");exit(10);}
    $ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$uid));
    foreach($ids as $id){
        $p=get_post((int)$id); if(!$p) continue;
        $out['products'][]=['id'=>(int)$id,'author_id'=>(int)$uid,'vendor'=>$vendor,'status'=>$p->post_status,'title'=>$p->post_title,'content'=>$p->post_content,'excerpt'=>$p->post_excerpt,'spanish_slug'=>$p->post_name];
    }
}
foreach($hidalgo_ids as $id){
    $p=get_post($id); if(!$p || $p->post_type!=='product' || $p->post_status!=='publish'){fwrite(STDERR,"Hidalgo target invalid {$id}\n");exit(11);}
    $producer_terms=wp_get_post_terms($id,'pa_productor',['fields'=>'names']);
    $producer=implode(' ',is_wp_error($producer_terms)?[]:$producer_terms);
    if(stripos(remove_accents($producer),'Hidalgo de la Jara')===false){fwrite(STDERR,"Hidalgo producer mismatch {$id}: {$producer}\n");exit(12);}
    $out['products'][]=['id'=>$id,'author_id'=>(int)$p->post_author,'vendor'=>'Hidalgo de la Jara','status'=>$p->post_status,'title'=>$p->post_title,'content'=>$p->post_content,'excerpt'=>$p->post_excerpt,'spanish_slug'=>$p->post_name];
}
$counts=[];foreach($out['products'] as $p){$counts[$p['vendor']]=($counts[$p['vendor']]??0)+1;}
if(($counts['Puente Robles']??0)!==106 || ($counts['El Catedrático']??0)!==95 || ($counts['Hidalgo de la Jara']??0)!==4){fwrite(STDERR,'Unexpected target distribution '.wp_json_encode($counts)."\n");exit(13);}
$path=getenv('MDO_EXPORT_PATH') ?: '/tmp/mdo-final-en-source.json';
file_put_contents($path,wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo 'EXPORT '.wp_json_encode(['count'=>count($out['products']),'vendors'=>$counts,'path'=>$path],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
