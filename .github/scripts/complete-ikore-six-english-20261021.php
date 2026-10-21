<?php
/** Scoped, backup-safe English product metadata for six official Ikore Kofi coffees. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('WooCommerce'))throw new RuntimeException('WooCommerce WP-CLI required');
$host=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
$vendor=get_user_by('login','ikore-kofi');
if(!$vendor)throw new RuntimeException('Ikore Kofi account missing');
$items=[
 '46002/V0112'=>['Panama Chiriquí','panama-chiriqui','Panama','1,650–1,700 m','natural',86,'ripe fruit, chocolate, sweet caramel and spice','A fruit-forward and sweet cup, with cocoa notes and a gentle spiced finish'],
 '46002/V0116'=>['Colombia El Indio','colombia-el-indio','Colombia','1,500–2,150 m','natural',87,'passion fruit, chocolate, sweet wine, orange and raspberry','A lively expression of tropical fruit and citrus with a sweet, berry-like finish'],
 '46002/V0122'=>['Costa Rica Villa Sarchí Honey','costa-rica-villa-sarchi-honey','Costa Rica','1,500 m','honey',86,'honey, nuts, butter, vanilla, caramel and green apple','A rounded, sweet cup balancing nutty richness with crisp green-apple freshness'],
 '46002/V0123'=>['Costa Rica Typica Mejorado','costa-rica-typica-mejorado','Costa Rica','1,500 m','yellow honey',86,'caramel, sugar cane, butter, dark chocolate and orange','A sweet, cocoa-led profile with a bright citrus lift'],
 '46002/V0127'=>['Kenya Washed Adumu','kenya-washed-adumu','Kenya','1,550–2,000 m','washed',84,'brown sugar, cherry, raspberry and milk chocolate','Red-berry flavours layered over brown-sugar sweetness and gentle cocoa notes'],
 '46002/V0124'=>['Costa Rica Geisha','costa-rica-geisha','Costa Rica','1,500 m','honey',87,'jasmine, stone fruit, raisins, vanilla, brown sugar and lime','A floral, aromatic cup combining ripe stone fruit with a fresh lime accent']
];
$preflight=(string)getenv('EMDO_IKORE_EN_PREFLIGHT')==='1';
$report=['mode'=>$preflight?'audit':'apply','expected'=>6,'matched'=>0,'updated'=>0,'already_complete'=>0,'skipped'=>[],'verified'=>true,'ids'=>[]];
$prepared=[];$backup=[];
foreach($items as $sku=>$v){
 $id=(int)wc_get_product_id_by_sku($sku);
 $p=$id?wc_get_product($id):false;
 if(!$p||!$p->is_type('variable')||$p->get_status()!=='publish'||
    (int)get_post_field('post_author',$id)!==(int)$vendor->ID||
    (string)get_post_meta($id,'_mdo_ikore_manual',true)!=='1'||
    count($p->get_children())!==14||
    !str_contains(mb_strtolower($p->get_name()),'ikore kofi')){
    $report['skipped'][]=['sku'=>$sku,'id'=>$id,'reason'=>'identity_or_variants_drift'];continue;
 }
 $report['matched']++;
 [$name,$slug,$country,$height,$process,$sca,$notes,$opening]=$v;
 $en_title=$name.' specialty coffee — Ikore Kofi';
 $en_slug='ikore-kofi-'.$slug.'-specialty-coffee';
 $en_excerpt='<p>100% Arabica specialty coffee from '.esc_html($country).', medium roasted in Valencia. '.esc_html($opening).'. SCA score: '.$sca.'. Available as whole beans or ground for your brewing method.</p>';
 $en_body='<h2>'.esc_html($name).': specialty coffee roasted in Valencia</h2>'
  .'<p>'.esc_html($opening).'. Ikore Kofi selects the beans for this origin and uses a medium roast to bring out its distinctive flavours. This coffee is 100% Arabica with no flavourings added.</p>'
  .'<h3>Origin and tasting profile</h3>'
  .'<ul><li>Country of origin: '.esc_html($country).'</li><li>Altitude: '.esc_html($height).' above sea level</li>'
  .'<li>Processing method: '.esc_html($process).'</li><li>Roast: medium</li>'
  .'<li>Specialty coffee cupping score: '.$sca.' SCA</li>'
  .'<li>Tasting notes: '.esc_html($notes).'</li><li>Pack size: 250 g or 1 kg</li></ul>'
  .'<p>Tasting notes describe the sensory profile of the roasted coffee: they do not indicate extra ingredients.</p>'
  .'<h3>Choose your grind</h3>'
  .'<p>Select whole beans to grind at home, or choose a grind suited to a moka pot, espresso machine, French press, cold brew, AeroPress or filter and V60 brewing. Select both pack size and grind before adding the coffee to your basket.</p>'
  .'<h3>Storage</h3>'
  .'<p>Keep the package tightly sealed in a cool, dry place, away from sunlight and strong odours. Refer to the pack for batch details and the best-before date.</p>';
 $values=['_en_US_post_title'=>$en_title,'_en_US_post_name'=>$en_slug,
   '_en_US_post_excerpt'=>$en_excerpt,'_en_US_post_content'=>$en_body,
   '_en_US_published'=>'1','_en_US_ready'=>'1'];
 $current=[];foreach($values as $key=>$val)$current[$key]=(string)get_post_meta($id,$key,true);
 if($current===$values){$report['already_complete']++;continue;}
 // Native English copy is never overwritten once it has been published.
 if($current['_en_US_published']==='1'||trim(wp_strip_all_tags($current['_en_US_post_content']))!==''||
    trim($current['_en_US_post_name'])!==''){
    $report['skipped'][]=['sku'=>$sku,'id'=>$id,'reason'=>'existing_english_requires_manual_review'];continue;
 }
 $prepared[$id]=['sku'=>$sku,'values'=>$values];
 $backup[$id]=$current;
}
if(!$preflight && $prepared){
 $key='emdo_ikore_en_20261021_'.gmdate('Ymd_His').'_'.wp_generate_password(6,false,false);
 if(!add_option($key,['created'=>gmdate('c'),'previous'=>$backup],'',false))throw new RuntimeException('Could not back up existing English metadata');
 $report['backup_option']=$key;
 foreach($prepared as $id=>$entry){
  foreach($entry['values'] as $k=>$val)update_post_meta($id,$k,$val);
  clean_post_cache($id);$report['updated']++;$report['ids'][]=(int)$id;
  foreach($entry['values'] as $k=>$val)if((string)get_post_meta($id,$k,true)!==$val)$report['verified']=false;
 }
}
$report['ready']=$report['matched']===6&&$report['verified']&&count($report['skipped'])===0&&($report['updated']+$report['already_complete']===6||$preflight);
echo 'EMDO_IKORE_ENGLISH='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$report['ready'])exit(19);
