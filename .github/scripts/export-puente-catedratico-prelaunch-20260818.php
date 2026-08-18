<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$vendors = [4508 => 'Puente Robles', 4509 => 'El Catedrático'];
$out = ['site'=>get_option('siteurl'),'generated'=>gmdate('c'),'products'=>[]];
foreach ($vendors as $uid=>$vendor) {
    $u=get_user_by('id',$uid);
    if(!$u || stripos(remove_accents($u->display_name),remove_accents($vendor))===false){fwrite(STDERR,"Vendor identity mismatch {$uid}\n");exit(10);}
    $ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$uid));
    foreach($ids as $id){
        $p=get_post((int)$id); if(!$p) continue;
        $out['products'][]=[
            'id'=>(int)$id,
            'author_id'=>(int)$uid,
            'vendor'=>$vendor,
            'status'=>$p->post_status,
            'title'=>$p->post_title,
            'content'=>$p->post_content,
            'excerpt'=>$p->post_excerpt,
            'spanish_slug'=>$p->post_name,
            'current_en_title'=>(string)get_post_meta($id,'_en_US_post_title',true),
            'current_en_slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
            'en_ready'=>(string)get_post_meta($id,'_en_US_ready',true),
            'en_published'=>(string)get_post_meta($id,'_en_US_published',true),
        ];
    }
}
$counts=[];foreach($out['products'] as $p){$counts[$p['vendor']]=($counts[$p['vendor']]??0)+1;}
if(($counts['Puente Robles']??0)!==106 || ($counts['El Catedrático']??0)!==95){fwrite(STDERR,'Unexpected target distribution '.wp_json_encode($counts)."\n");exit(13);}
$path=getenv('MDO_EXPORT_PATH') ?: '/tmp/mdo-prelaunch-source-201.json';
file_put_contents($path,wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo 'EXPORT '.wp_json_encode(['count'=>count($out['products']),'vendors'=>$counts,'path'=>$path],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
