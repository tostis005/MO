<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;
$payload = json_decode( file_get_contents('/tmp/mdo-launch-translations.json'), true );
if ( ! is_array($payload) || ! isset($payload['products']) || count($payload['products']) !== 202 || ! empty($payload['qa']) ) {
    fwrite(STDERR,"Invalid translation payload\n"); exit(30);
}
$expected = [4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático'];
$used = [];
$rows = $wpdb->get_results("SELECT post_id,meta_value FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value<>''",ARRAY_A);
foreach($rows as $r){ $used[sanitize_title($r['meta_value'])]=(int)$r['post_id']; }
$counts=[]; $status_mismatches=[];
foreach($payload['products'] as $row){
    $id=(int)$row['id']; $aid=(int)$row['author_id'];
    if(!isset($expected[$aid]) || $row['vendor'] !== $expected[$aid]){fwrite(STDERR,"Vendor mismatch {$id}\n");exit(31);}
    $p=get_post($id);
    if(!$p || $p->post_type!=='product' || (int)$p->post_author!==$aid){fwrite(STDERR,"Product identity mismatch {$id}\n");exit(32);}
    if($p->post_status !== $row['source_status']){$status_mismatches[]=$id;continue;}
    $slug=sanitize_title((string)$row['slug']); if($slug===''){$slug='product-'.$id;}
    if(isset($used[$slug]) && $used[$slug]!==$id){
        $suffix=$aid===4508?'puente-robles':($aid===4509?'el-catedratico':'tolecarnes');
        $slug=sanitize_title($slug.'-'.$suffix);
    }
    if(isset($used[$slug]) && $used[$slug]!==$id){$slug=sanitize_title($slug.'-'.$id);}
    $used[$slug]=$id;
    update_post_meta($id,'_en_US_post_title',(string)$row['title']);
    update_post_meta($id,'_en_US_post_content',(string)$row['content']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$row['excerpt']);
    update_post_meta($id,'_en_US_post_name',$slug);
    if($aid===4507){
        update_post_meta($id,'_en_US_published','1');
        delete_post_meta($id,'_en_US_ready');
    }else{
        // Copy is launch-ready, but keep routing disabled until the vendor itself is launched.
        update_post_meta($id,'_en_US_published','0');
        update_post_meta($id,'_en_US_ready','1');
    }
    $counts[$expected[$aid]]=($counts[$expected[$aid]]??0)+1;
}
if($status_mismatches){fwrite(STDERR,'Product statuses changed during translation: '.implode(',',$status_mismatches)."\n");exit(33);}
wp_cache_flush();
echo 'APPLIED '.wp_json_encode($counts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
