<?php
/** Fix English short-description meta for La Huerta de Ana Mary batch 01. */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
function mo_ha1e_fail($m){ if(defined('WP_CLI')&&WP_CLI){ WP_CLI::error($m); } throw new Exception($m); }
$items=[
12699=>['title'=>'Patatas blancas','en'=>'<p>White Kennebec potatoes grown in Fresno de la Vega and sold by the kilogram. They are supplied unwashed, retaining the earthy appearance of potatoes straight from the field, with thick skin and white flesh.</p>'],
12702=>['title'=>'Calabacín','en'=>'<p>Fresh courgette grown in Fresno de la Vega. A mild, tender vegetable that is easy to use in everyday cooking and suitable for griddling, sautéing, soups, roasting or stuffing.</p>'],
12706=>['title'=>'Brócoli','en'=>'<p>Fresh broccoli grown in Fresno de la Vega. Its compact florets and distinctive vegetable flavour make it easy to steam, boil, sauté, roast or add to rice, pasta and many other dishes.</p>'],
12709=>['title'=>'20 Kg de patatas blancas variedad kennebec','en'=>'<p>20 kg format of white Kennebec potatoes. They are supplied unwashed and packed by hand, retaining the earthy appearance of the skin, with white flesh and a highly versatile range of culinary uses.</p>'],
12711=>['title'=>'Flores de calabacín 8 unidades','en'=>'<p>Box of 8 fresh courgette flowers. They are harvested when they reach the right stage and, because of their delicate nature, are particularly suitable for stuffing, battering, frying or other quick-cooking recipes.</p>'],
];
$backup_key='mo_huerta_anamary_batch01_en_excerpt_backup_20260831';
if(get_option($backup_key,null)===null){
  $backup=[];
  foreach($items as $id=>$spec){ $backup[$id]=(string)get_post_meta($id,'_en_US_post_excerpt',true); }
  if(!add_option($backup_key,$backup,'',false)) mo_ha1e_fail('Could not create English excerpt backup');
  echo "BACKUP created {$backup_key}\n";
}
foreach($items as $id=>$spec){
  $p=get_post($id);
  if(!$p||$p->post_type!=='product'||$p->post_status!=='publish'||$p->post_title!==$spec['title']) mo_ha1e_fail("Identity mismatch {$id}");
  $u=get_userdata((int)$p->post_author); $vendor=$u?(string)$u->display_name:'';
  if(stripos($vendor,'La Huerta de Ana Mary')===false) mo_ha1e_fail("Vendor mismatch {$id}");
  update_post_meta($id,'_en_US_post_excerpt',$spec['en']);
  clean_post_cache($id);
  $now=(string)get_post_meta($id,'_en_US_post_excerpt',true);
  if($now!==$spec['en']) mo_ha1e_fail("English excerpt verification failed {$id}");
  echo "UPDATED_EN_EXCERPT {$id} {$spec['title']}\n";
}
echo "DONE English excerpts=".count($items)."\n";
