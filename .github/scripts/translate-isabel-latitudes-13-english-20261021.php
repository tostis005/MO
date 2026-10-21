<?php
/**
 * Hand-reviewed English product content for exactly 13 physically purchasable
 * Isabel / Latitudes items. Never writes the native Spanish post or commerce fields.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI ||
 !class_exists('WooCommerce') || !class_exists('MDO_Database')) throw new RuntimeException('WP-CLI WooCommerce supplier-sync required');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) throw new RuntimeException('Production only');

$kit_title='Make your own bean-to-bar chocolate kit';
$kit_body='<p>Work with cocoa beans from the start rather than melting a ready-made bar. This physical kit provides the main ingredients, equipment and step-by-step instructions to make chocolate at home, mould it into bars and wrap the results. It is a one-off product purchase, not a workshop booking.</p>'
 .'<h2>What is included?</h2><ul>'
 .'<li>Organic Fairtrade cocoa beans and organic Fairtrade cocoa butter</li>'
 .'<li>A polycarbonate chocolate-bar mould, scraper and thermometer</li>'
 .'<li>Wrapping paper and labels for the finished bars</li>'
 .'<li>A laminated making guide and an instruction manual with tips on storage and tasting</li>'
 .'</ul><h2>What to expect</h2>'
 .'<p>Professional chocolate makers use dedicated equipment to refine chocolate to below 25 microns. Ordinary home kitchen equipment cannot reproduce exactly that texture, so the finished chocolate may be more rustic than a professionally refined bar. The kit is designed as a hands-on introduction to working from cocoa beans. You can use the supplied mould or your own suitable moulds.</p>'
 .'<p>Pack dimensions: approximately 33.5 × 19 × 10 cm.</p>';

$cracker_body='<p>A 30 g organic, Fairtrade white-chocolate snack with puffed quinoa for a light, crisp contrast. Its individually wrapped format makes it distinct from a conventional chocolate bar.</p>'
 .'<h2>Ingredients and certification</h2><p>Whole cane sugar, cocoa butter, whole milk powder, puffed quinoa, vanilla and soya lecithin. The producer identifies cane sugar, cocoa butter and vanilla as Fairtrade ingredients; all listed ingredients are of organic agricultural origin. Certified Fairtrade by FLO and organic by CAAE.</p>'
 .'<p>Gluten-free and egg-free. Suitable for a lacto-vegetarian diet. Contains <strong>milk and soya</strong>. The producer also reports peanuts elsewhere on site and tree nuts and sesame on the production line; refer to the product packaging for the full allergen matrix.</p>'
 .'<h2>Packaging and storage</h2><p>Biodegradable cellophane wrapper and FSC-certified paper label. Net weight 30 g. Pack dimensions approximately 9.5 × 1 × 8 cm. Store in a cool, dry place at 14–20 °C and around 55% relative humidity.</p>'
 .'<h2>Nutrition per 100 g</h2><ul><li>Energy: 555.3 kcal</li><li>Fat: 33.68 g, of which saturates: 21.14 g</li><li>Carbohydrate: 56.03 g, of which sugars: 49.01 g</li><li>Protein: 6.66 g</li><li>Salt: 0.002 g</li></ul>';

$chocobone_body='<p>White chocolate with a pronounced almond crunch: a 100 g organic and Fairtrade chocolate confection made with 35% cocoa white chocolate and 15% caramelised Marcona almonds. Honey and a small amount of lemon complete the recipe.</p>'
 .'<h2>Ingredients and allergen information</h2>'
 .'<p>Cocoa butter, whole cane sugar, whole milk powder, 15% Marcona almonds, honey, vanilla, soya lecithin and lemon. The ingredients are identified as organic; some are Fairtrade and the producer marks the almonds as locally sourced. Certified Fairtrade by FLO and organic by CAAE.</p>'
 .'<p>Contains <strong>almonds, milk and soya</strong>. The producer describes this product as gluten-free and suitable for a lacto-vegetarian diet. Its allergen matrix also identifies peanuts on site and sesame in the production environment; consult the pack for detailed traces and cross-contact information.</p>'
 .'<h2>Presentation and storage</h2><p>100 g individually wrapped piece with biodegradable cellophane and an FSC-certified paper label. Store in a cool, dry place at 14–20 °C and around 55% relative humidity.</p>'
 .'<h2>Nutrition per 100 g</h2><ul><li>Energy: 557.89 kcal</li><li>Fat: 36.60 g, of which saturates: 19.16 g</li><li>Carbohydrate: 47.93 g, of which sugars: 47.82 g</li><li>Protein: 8.95 g</li><li>Fibre: 1.48 g</li><li>Salt: 0.003 g</li></ul>';

$items=[
 656=>['supplier'=>'chocolates-artesanos-isabel','id'=>17526,'expected'=>'Kit haz tu propio chocolate bean to bar',
 'title'=>$kit_title.' — Isabel','slug'=>'bean-to-bar-chocolate-making-kit-isabel',
 'short'=>'A complete physical home kit with Fairtrade cocoa beans, cocoa butter, a mould, scraper, thermometer and instructions.',
 'long'=>$kit_body],
 688=>['supplier'=>'chocolates-artesanos-isabel','id'=>17621,'expected'=>'Cracker crujiente de chocolate blanco BIO&FT',
 'title'=>'Organic Fairtrade white chocolate quinoa cracker — BIO&FT',
 'slug'=>'organic-fairtrade-white-chocolate-quinoa-cracker',
 'short'=>'30 g gluten-free white chocolate cracker with crispy puffed quinoa. Organic and Fairtrade; contains milk and soya.',
 'long'=>$cracker_body],
 698=>['supplier'=>'chocolates-artesanos-isabel','id'=>17647,'expected'=>'Kit haz tu propio chocolate bean to bar',
 'title'=>'DIY cocoa bean to chocolate kit — Isabel',
 'slug'=>'diy-cocoa-bean-to-chocolate-kit-isabel',
 'short'=>'A boxed bean-to-bar activity kit: cocoa beans, cocoa butter, chocolate mould and essential utensils for home chocolate making.',
 'long'=>'<p>Discover the process from cocoa bean to finished chocolate bar at home. This boxed kit contains organic Fairtrade cocoa beans and cocoa butter alongside the tools and guides needed for a hands-on introduction.</p>'.$kit_body],
 754=>['supplier'=>'chocolates-artesanos-isabel','id'=>17745,'expected'=>'Chocobone BIO&FT chocolate blanco y almendra caramelizada',
 'title'=>'Chocobone organic Fairtrade white chocolate with caramelised almonds — BIO&FT',
 'slug'=>'chocobone-fairtrade-white-chocolate-caramelised-almonds',
 'short'=>'100 g organic and Fairtrade white chocolate confection with 15% caramelised Marcona almonds. Contains nuts, milk and soya.',
 'long'=>$chocobone_body],
 535=>['supplier'=>'latitudes','id'=>17210,'expected'=>'Filete de 1ª cebra (Ración de 200 gr. apróx.)',
 'title'=>'First-cut zebra steak — approximately 200 g',
 'slug'=>'first-cut-zebra-steak-approx-200g',
 'short'=>'An individual zebra steak portion of approximately 200 g, with a game-like flavour and a slightly sweeter note than beef.',
 'long'=>'<p>A first-cut zebra steak offered as an individual portion of approximately 200 g. Its flavour has some of the character of game meat, with a subtly sweeter note than conventional beef.</p><h2>Preparing the steak</h2><p>A short cooking time and a simple vegetable accompaniment can help the flavour and texture of the cut stand out. This single-portion format is a way to try zebra without ordering a larger joint. Keep and prepare the product according to its packaging instructions.</p>'],
 544=>['supplier'=>'latitudes','expected'=>'Pack burger meat avestruz 2 x 150 gr.',
 'title'=>'Ostrich burger meat pack — 2 × 150 g','slug'=>'ostrich-burger-meat-pack-two-150g',
 'short'=>'Two 150 g seasoned ostrich burger portions, vacuum-packed and frozen. Contains sulphites.',
 'opening'=>'Two seasoned ostrich burger portions, 150 g each. Ostrich is a red-meat poultry option with a flavour distinct from conventional beef burgers.'],
 545=>['supplier'=>'latitudes','id'=>17230,'expected'=>'Burger meat bisonte 150 gr.',
 'title'=>'Bison burger meat — 150 g','slug'=>'bison-burger-meat-150g',
 'short'=>'A 150 g seasoned bison burger portion, vacuum-packed and frozen. Contains sulphites.',
 'opening'=>'One 150 g bison burger meat portion. This is a seasoned preparation, not an unprocessed bison steak.'],
 546=>['supplier'=>'latitudes','id'=>17232,'expected'=>'Pack burger meat bisonte 2×150 gr.',
 'title'=>'Bison burger meat pack — 2 × 150 g','slug'=>'bison-burger-meat-pack-2x150g',
 'short'=>'Two seasoned bison burger portions of 150 g each, packed frozen. Contains sulphites.',
 'opening'=>'A two-portion bison burger pack, with each seasoned patty weighing 150 g. Suitable for serving two matching burgers.'],
 547=>['supplier'=>'latitudes','id'=>17234,'expected'=>'Burger meat búfalo 150 gr.',
 'title'=>'Buffalo burger meat — 150 g','slug'=>'buffalo-burger-meat-150g',
 'short'=>'One 150 g seasoned buffalo burger meat portion, frozen and vacuum-packed. Contains sulphites.',
 'opening'=>'A 150 g buffalo burger preparation, seasoned before freezing for cooking on a griddle or grill.'],
 548=>['supplier'=>'latitudes','id'=>17236,'expected'=>'Pack burger meat búfalo 2×150 gr.',
 'title'=>'Buffalo burger meat pack — 2 × 150 g','slug'=>'buffalo-burger-meat-pack-2x150g',
 'short'=>'A pack of two 150 g seasoned buffalo burger portions. Frozen and vacuum-packed; contains sulphites.',
 'opening'=>'Two seasoned buffalo burger patties of 150 g each, offering a convenient pair of portions for pan-frying or grilling.'],
 552=>['supplier'=>'latitudes','id'=>17244,'expected'=>'Burger meat cocodrilo 150 gr.',
 'title'=>'Crocodile burger meat — 150 g','slug'=>'crocodile-burger-meat-150g',
 'short'=>'A 150 g seasoned crocodile burger preparation, frozen for grilling or pan-frying. Contains sulphites.',
 'opening'=>'A 150 g crocodile meat burger preparation. It contains additional cereal and vegetable ingredients rather than being a plain crocodile steak.'],
 557=>['supplier'=>'latitudes','id'=>17254,'expected'=>'Burger meat búfalo (500 gr.)',
 'title'=>'Buffalo burger meat — 500 g','slug'=>'buffalo-burger-meat-500g',
 'short'=>'500 g of seasoned buffalo burger meat to portion before cooking. Frozen and vacuum-packed; contains sulphites.',
 'opening'=>'A 500 g format of seasoned buffalo burger meat. Portion it to the size you need; it is a prepared meat product rather than a plain buffalo fillet.'],
 621=>['supplier'=>'latitudes','id'=>17398,'expected'=>'Burger meat black angus 2×150 gr.',
 'title'=>'Black Angus burger meat pack — 2 × 150 g','slug'=>'black-angus-burger-meat-pack-2x150g',
 'short'=>'Two 150 g seasoned Black Angus burger portions, frozen and vacuum-packed. Contains sulphites.',
 'opening'=>'Two equal-sized 150 g Black Angus burger portions, seasoned and frozen. Unlike an unprepared Angus steak, this product contains other ingredients.'],
];
if(count($items)!==13)throw new RuntimeException('Expected exactly 13 reviewed translation definitions');
global $wpdb;
$suppliers=MDO_Database::table('suppliers');$sources=MDO_Database::table('source_products');
$mode=(string)(getenv('EMDO_13_EN_MODE')?:'audit');
if(!in_array($mode,['audit','apply'],true))throw new RuntimeException('Invalid mode');
$report=['mode'=>$mode,'defined'=>13,'matched'=>0,'needs_translation'=>0,'already_complete'=>0,
 'published'=>0,'skipped'=>[],'verified'=>false,'changed_product_ids'=>[]];
$prepared=[];$backups=[];
foreach($items as $source_id=>$info){
 $row=$wpdb->get_row($wpdb->prepare("SELECT p.id,p.wc_product_id,p.supplier_id,s.code,s.vendor_user_id
 FROM {$sources} p JOIN {$suppliers} s ON s.id=p.supplier_id WHERE p.id=%d",$source_id),ARRAY_A);
 $id=(int)($row['wc_product_id']??0);
 $product=$id?wc_get_product($id):null;
 if(!$row||!$product||$row['code']!==$info['supplier']||
  $product->get_name()!==$info['expected']||
  $product->get_status()!=='publish'||!$product->is_visible()||
  !$product->is_purchasable()||!$product->is_in_stock()||
  (int)get_post_field('post_author',$id)!==(int)$row['vendor_user_id']||
  (isset($info['id'])&&$id!==$info['id'])){
  $report['skipped'][]=['source_id'=>$source_id,'reason'=>'source_product_identity_or_buyability_changed'];continue;
 }
 $report['matched']++;
 if(!isset($info['long'])){
    $src=html_entity_decode(wp_strip_all_tags($product->get_description()),ENT_QUOTES|ENT_HTML5,'UTF-8');
    if(!preg_match('/Al[eé]rgenos:\s*Contiene sulfitos/iu',$src)||
       !preg_match('/Ingredientes:\s*([^\r\n]+|.*)$/iu',$src,$m)){
       $report['skipped'][]=['source_id'=>$source_id,'reason'=>'ingredient_or_sulphite_declaration_missing'];continue;
    }
    $raw_ingredients=trim($m[1]);
    // Translate declared ingredients while retaining all numeric additive codes
    // unchanged from the producer record, including the preservative code.
    $ingredients=str_ireplace([
      'Carne de Avestruz','Carne de Bisonte','Carne de Búfalo','Carne de Cocodrilo','Carne de Angus Negro',
      'Cereales/hortalizas','arroz','maiz','maíz','patata','ajo','cebolla',
      'almidon de maiz','almidón de maiz','almidón de maíz','sal','dextrosa','especias',
      'antioxidantes','conservador','conservadores','colorante'
    ],[
      'ostrich meat','bison meat','buffalo meat','crocodile meat','Black Angus beef',
      'cereals/vegetables','rice','corn','corn','potato','garlic','onion',
      'corn starch','corn starch','corn starch','salt','dextrose','spices',
      'antioxidants','preservative','preservatives','colour'
    ],$raw_ingredients);
    $ingredients=rtrim($ingredients,'. ');
    if(!preg_match('/\b(?:meat|beef)\b/i',$ingredients)||
       !str_contains(strtolower($ingredients),'preservative')||
       !preg_match('/E\s*-?\s*1?20/i',$ingredients)){
       $report['skipped'][]=['source_id'=>$source_id,'reason'=>'ingredient_translation_failed'];continue;
    }
    $info['long']='<p>'.esc_html($info['opening']).' This is a seasoned, vacuum-packed and frozen meat preparation.</p>'
       .'<h2>Ingredients and allergens</h2><p><strong>Ingredients:</strong> '.esc_html($ingredients).'.</p>'
       .'<p><strong>Allergen:</strong> contains sulphites. Check the product label for the complete ingredient and allergen declaration, including additive codes.</p>'
       .'<h2>Cooking and storage</h2><p>Prepared for cooking on a barbecue, grill or hot griddle. Keep frozen according to the pack instructions and cook thoroughly before serving. The product is not an unseasoned whole-muscle cut.</p>';
 }
 $values=[
   '_en_US_post_title'=>$info['title'],
   '_en_US_post_name'=>$info['slug'],
   '_en_US_post_excerpt'=>'<p>'.esc_html($info['short']).'</p>',
   '_en_US_post_content'=>wp_kses_post($info['long']),
   '_en_US_published'=>'1','_en_US_ready'=>'1',
 ];
 $existing=[];
 foreach($values as $key=>$value)$existing[$key]=(string)get_post_meta($id,$key,true);
 $native_complete=($existing['_en_US_published']==='1'&&
  trim($existing['_en_US_post_title'])!==''&&trim($existing['_en_US_post_name'])!==''&&
  trim(wp_strip_all_tags($existing['_en_US_post_content']))!==''&&
  trim(wp_strip_all_tags($existing['_en_US_post_excerpt']))!=='');
 if($native_complete){$report['already_complete']++;continue;}
 // Do not overwrite any partial hand-written or otherwise saved English work.
 if($existing['_en_US_published']==='1'||
    trim($existing['_en_US_post_title'])!==''||
    trim($existing['_en_US_post_name'])!==''||
    trim(wp_strip_all_tags($existing['_en_US_post_content']))!==''||
    trim(wp_strip_all_tags($existing['_en_US_post_excerpt']))!==''){
    $report['skipped'][]=['source_id'=>$source_id,'reason'=>'partial_english_requires_review'];continue;
 }
 $clash=$wpdb->get_var($wpdb->prepare(
  "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value=%s AND post_id<>%d LIMIT 1",
  $info['slug'],$id));
 if($clash){
    $report['skipped'][]=['source_id'=>$source_id,'reason'=>'slug_collision'];continue;
 }
 if(count($values)!==6||trim(wp_strip_all_tags($values['_en_US_post_content']))===''){
    $report['skipped'][]=['source_id'=>$source_id,'reason'=>'invalid_editorial_copy'];continue;
 }
 $backups[$id]=$existing;
 $prepared[$id]=['source_id'=>$source_id,'values'=>$values];
 $report['needs_translation']++;
}
if($report['matched']!==13 || count($report['skipped'])!==0 ||
   $report['needs_translation']+$report['already_complete']!==13){
   echo 'EMDO_13_NATIVE_EN='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
   exit(18);
}
if($mode==='apply'&&$prepared){
 $backup_key='emdo_13_english_backup_20261021_'.gmdate('YmdHis').'_'.wp_generate_password(6,false,false);
 if(!add_option($backup_key,['made'=>gmdate('c'),'previous'=>$backups],'',false))
   throw new RuntimeException('Unable to persist backup');
 $report['backup_key']=$backup_key;
 foreach($prepared as $id=>$entry){
    foreach($entry['values'] as $key=>$v)update_post_meta($id,$key,$v);
    clean_post_cache($id);
    $report['changed_product_ids'][]=$id;
    $report['published']++;
 }
}
$verified=true;
if($mode==='apply')foreach($prepared as $id=>$entry)
 foreach($entry['values'] as $key=>$v)
   if((string)get_post_meta($id,$key,true)!==($v))$verified=false;
$report['verified']=$verified;
$report['complete']=$verified&&($report['published']+$report['already_complete']===13||$mode==='audit');
echo 'EMDO_13_NATIVE_EN='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$report['complete'])exit(19);
