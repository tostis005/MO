<?php
if ( ! defined('ABSPATH') ) { exit(1); }
$id=11097;
$p=get_post($id);
if(!$p || $p->post_type!=='product' || (int)$p->post_author!==4507){fwrite(STDERR,"Unexpected product identity\n");exit(1);}
$keys=['_en_US_post_title','_en_US_post_content','_en_US_post_excerpt'];
$changed=[];
foreach($keys as $key){
    $old=(string)get_post_meta($id,$key,true);
    $new=preg_replace('/\btray of\s+2\s+marrow bones\b/i','tray of marrow bones',$old);
    $new=preg_replace('/\b2\s+marrow bones\b/i','marrow bones',$new);
    if($new!==$old){update_post_meta($id,$key,$new);$changed[]=$key;}
}
if(!$changed){fwrite(STDERR,"Expected legacy phrase not found for product 11097\n");exit(2);}
wp_cache_flush();
echo 'FIXED '.wp_json_encode(['id'=>$id,'fields'=>$changed],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
