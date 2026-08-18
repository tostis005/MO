<?php
if ( ! defined( 'ABSPATH' ) ) { fwrite(STDERR,"WordPress required\n"); exit(2); }
global $wpdb;

$expected_authors = [11095=>4507,11129=>4507,11865=>4508,11964=>4508,12631=>4509];
$meta_updates = [
    11095 => [
        '_en_US_post_content' => 'A cut adjacent to the skirt steak, sliced into small steaks and ready for the griddle. Price per kg.',
        '_en_GB_post_content' => 'A cut adjacent to the skirt steak, sliced into small steaks and ready for the griddle. Price per kg.',
    ],
    11129 => [
        '_en_US_post_content' => 'A cut adjacent to the skirt steak, diced into ragout pieces and ready for stewing. Price per kg.',
        '_en_GB_post_content' => 'A cut adjacent to the skirt steak, diced into ragout pieces and ready for stewing. Price per kg.',
    ],
    11865 => [
        '_en_US_post_title' => 'Cured ham hock',
        '_en_US_post_name'  => 'cured-ham-hock-puente-robles',
        '_en_GB_post_title' => 'Cured ham hock',
        '_en_GB_post_name'  => 'cured-ham-hock-puente-robles',
    ],
    11964 => [
        '_en_US_post_title' => 'Acorn-fed shoulder ham bundle',
        '_en_US_post_name'  => 'acorn-fed-shoulder-ham-bundle-puente-robles',
        '_en_GB_post_title' => 'Acorn-fed shoulder ham bundle',
        '_en_GB_post_name'  => 'acorn-fed-shoulder-ham-bundle-puente-robles',
    ],
    12631 => [
        '_en_US_post_title' => 'Cured ham hock',
        '_en_US_post_name'  => 'cured-ham-hock-el-catedratico',
        '_en_GB_post_title' => 'Cured ham hock',
        '_en_GB_post_name'  => 'cured-ham-hock-el-catedratico',
    ],
];

foreach ($expected_authors as $id=>$aid) {
    $p=get_post($id);
    if(!$p || $p->post_type!=='product' || (int)$p->post_author!==$aid){fwrite(STDERR,"Product identity mismatch: $id\n");exit(10);}
}

/* Ensure new English slugs do not collide with any other translated product. */
foreach ($meta_updates as $id=>$changes) {
    foreach (['_en_US_post_name','_en_GB_post_name'] as $key) {
        if (empty($changes[$key])) continue;
        $slug=sanitize_title($changes[$key]);
        $other=(int)$wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s AND post_id<>%d LIMIT 1",
            $key,$slug,$id
        ));
        if($other){fwrite(STDERR,"English slug collision: $slug with $other\n");exit(11);}
    }
}

$state_keys=['_en_US_published','_en_US_ready','_en_GB_published','_en_GB_ready'];
$before=[];$backup=[];
foreach($expected_authors as $id=>$aid){
    $p=get_post($id);
    $backup[$id]=['post_status'=>$p->post_status,'author'=>(int)$p->post_author,'meta'=>[]];
    foreach(array_unique(array_merge(array_keys($meta_updates[$id]??[]),$state_keys)) as $key){
        $backup[$id]['meta'][$key]=(string)get_post_meta($id,$key,true);
    }
    foreach($state_keys as $key){$before[$id][$key]=(string)get_post_meta($id,$key,true);}
}
$backup_key='mdo_priority_product_translation_backup_20260818_v67';
update_option($backup_key,$backup,false);

$changed=[];
foreach($meta_updates as $id=>$changes){
    foreach($changes as $key=>$value){
        $old=(string)get_post_meta($id,$key,true);
        if($old!==$value){update_post_meta($id,$key,$value);$changed[]=['id'=>$id,'key'=>$key,'old'=>$old,'new'=>$value];}
    }
}

/* Never change routing/readiness or native publication state in this repair. */
foreach($expected_authors as $id=>$aid){
    $p=get_post($id);
    if(!$p || $p->post_status!==$backup[$id]['post_status']){fwrite(STDERR,"Native status changed unexpectedly: $id\n");exit(20);}
    foreach($state_keys as $key){
        if((string)get_post_meta($id,$key,true)!==$before[$id][$key]){fwrite(STDERR,"Routing/readiness changed unexpectedly: $id $key\n");exit(21);}
    }
}

/* Explicit launch-state assertions for the priority vendors. */
foreach([11865,11964,12631] as $id){
    if((string)get_post_meta($id,'_en_US_ready',true)!=='1' || (string)get_post_meta($id,'_en_US_published',true)==='1'){
        fwrite(STDERR,"Prelaunch English state invalid: $id\n");exit(22);
    }
}
foreach([11095,11129] as $id){
    if((string)get_post_meta($id,'_en_US_published',true)!=='1'){
        fwrite(STDERR,"Tolecarnes English routing unexpectedly off: $id\n");exit(23);
    }
}

wp_cache_flush();
echo wp_json_encode(['backup_key'=>$backup_key,'changed'=>$changed],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
