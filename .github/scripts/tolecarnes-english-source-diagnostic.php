<?php
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;
$needles=[
 'Extra minced beef made from 100% natural veal',
 'Striploin entrecote traditionally cut by hand',
];
foreach($needles as $needle){
 echo "==== NEEDLE: {$needle} ====\n";
 $like='%'.$wpdb->esc_like($needle).'%';
 $posts=$wpdb->get_results($wpdb->prepare("SELECT ID,post_type,post_status,post_title,post_name,post_excerpt,post_content FROM {$wpdb->posts} WHERE post_excerpt LIKE %s OR post_content LIKE %s LIMIT 20",$like,$like),ARRAY_A);
 echo "-- POSTS --\n";
 if(!$posts) echo "NONE\n";
 foreach($posts as $r){
   $excerpt=preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$r['post_excerpt']));
   $content=preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$r['post_content']));
   echo "ID={$r['ID']} type={$r['post_type']} status={$r['post_status']} title={$r['post_title']} slug={$r['post_name']}\nEXCERPT={$excerpt}\nCONTENT={$content}\n";
 }
 $meta=$wpdb->get_results($wpdb->prepare("SELECT post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s LIMIT 50",$like),ARRAY_A);
 echo "-- POSTMETA --\n";
 if(!$meta) echo "NONE\n";
 foreach($meta as $r){$v=preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$r['meta_value']));echo "post_id={$r['post_id']} meta_key={$r['meta_key']} value={$v}\n";}
 $tables=$wpdb->get_col("SHOW TABLES");
 echo "-- TRANSLATION TABLES --\n";
 foreach($tables as $t){
   if(!preg_match('/trp_|translate|translation|icl_/i',$t)) continue;
   $cols=$wpdb->get_col("SHOW COLUMNS FROM `{$t}`",0);
   $textcols=array_values(array_intersect($cols,['original','translated','value','meta_value','string','translation']));
   if(!$textcols) continue;
   foreach($textcols as $col){
     $sql=$wpdb->prepare("SELECT * FROM `{$t}` WHERE `{$col}` LIKE %s LIMIT 20",$like);
     $rows=$wpdb->get_results($sql,ARRAY_A);
     foreach($rows as $row){
       echo "TABLE={$t} COL={$col} ROW=".wp_json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
     }
   }
 }
}

echo "==== PRODUCT 11058 META KEYS ====\n";
foreach($wpdb->get_results("SELECT meta_key,meta_value FROM {$wpdb->postmeta} WHERE post_id=11058 ORDER BY meta_key",ARRAY_A) as $r){
 if(preg_match('/trans|lang|english|desc|content|trp|wpml|seo/i',$r['meta_key'])) echo $r['meta_key'].'='.preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$r['meta_value']))."\n";
}
echo "==== PRODUCT 11077 META KEYS ====\n";
foreach($wpdb->get_results("SELECT meta_key,meta_value FROM {$wpdb->postmeta} WHERE post_id=11077 ORDER BY meta_key",ARRAY_A) as $r){
 if(preg_match('/trans|lang|english|desc|content|trp|wpml|seo/i',$r['meta_key'])) echo $r['meta_key'].'='.preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$r['meta_value']))."\n";
}
echo "DONE\n";
