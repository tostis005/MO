<?php
/**
 * Manual native English SEO for exactly 18 previously audited Tropitop goods.
 * All prose comes from source-grounded Spanish product descriptions or standard
 * culinary / storage guidance; no subscription, freight or invented origin claims.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('MDO_Database')||!class_exists('WooCommerce'))
 throw new RuntimeException('WP CLI and supplier sync required');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
$items=json_decode(<<<'PRODUCTS'
[
  {
    "source_id": 762,
    "id": 18127,
    "es_title": "Aguacate bacon de piel lisa — tropitop",
    "title": "Bacon avocados with smooth green skin — Tropitop",
    "slug": "bacon-avocados-smooth-green-skin-tropitop",
    "short": "Smooth-skinned Bacon avocados grown near Artana in Castellón. Choose a 3, 4 or 9 kg box; fruit may arrive firm and ripen at home.",
    "body": "<p>Bacon avocados have smooth green skin, an oval shape and a creamy flesh with a mild, slightly nutty character. Their delicate flavour works well in salads, on toast, in guacamole or simply sliced with a little seasoning.</p><h2>Origin and box sizes</h2><p>Grown around Artana in Castellón, near the Sierra de Espadán. Available in approximate 3, 4 and 9 kg boxes. Individual fruits vary naturally in size and shape, so box weight does not indicate a fixed number of avocados.</p><h2>Ripening and storage</h2><p>Avocados continue to ripen after picking. If they arrive firm, keep them at room temperature until ready to eat, then refrigerate to slow further ripening. Availability changes during the growing season.</p>"
  },
  {
    "source_id": 764,
    "id": 18307,
    "es_title": "Mangos ecológicos",
    "title": "Organic mangoes from Castellón",
    "slug": "organic-mangoes-castellon",
    "short": "Seasonal organic mangoes from Castellón, including Osteen and Keitt varieties according to the harvest. Choose an available box size.",
    "body": "<p>These organic mangoes are grown in Castellón, in Spain's Valencian Community. Their skin may show purple, orange, yellow or green tones, and the flesh offers a notably sweet flavour with a light acidic edge. Each fruit grows to its own size and shape rather than being graded to a single uniform calibre.</p><h2>Osteen and Keitt through the season</h2><p>The Osteen harvest generally runs from early September to mid-October. Keitt usually follows from late October into November. The variety supplied depends on the point in the season and actual availability.</p><h2>Choosing your box</h2><p>Select the available weight or pack format on this product page. The organic mango harvest is limited and seasonal; Spanish mangoes are generally at their best during September, October and November.</p>"
  },
  {
    "source_id": 765,
    "id": 18340,
    "es_title": "Naranjas",
    "title": "Valencian late-season oranges",
    "slug": "valencian-late-season-oranges",
    "short": "Sweet, balanced Valencian oranges grown in Castellón. Choose a 4 or 9 kg box; fruit sizes vary naturally.",
    "body": "<p>These Valencian Late oranges have a rounded shape, medium average size and a gentle, sweet, balanced flavour. They come from Castellón in Spain's Valencian Community and are picked to order when the fruit reaches suitable maturity.</p><h2>What is in the box?</h2><p>Choose 4 kg or 9 kg. Depending on the box size and the natural size of individual oranges, a delivery may contain approximately 16 to 60 pieces. This is fruit rather than an individually graded selection, so appearance and calibre can vary.</p><h2>Season and kitchen uses</h2><p>The producer's stated season runs approximately from 1 October to 30 May. Enjoy the oranges fresh, squeeze them for juice or use their flesh and zest in salads, desserts, marmalade and savoury recipes. Refrigeration can help slow deterioration and extend storage.</p>"
  },
  {
    "source_id": 766,
    "id": 18360,
    "es_title": "Limones",
    "title": "Fresh seasonal lemons",
    "slug": "fresh-seasonal-lemons-tropitop",
    "short": "Fresh lemons for drinks, dressings and cooking. Select the available pack size and keep chilled for longer storage.",
    "body": "<p>Lemons bring a sharp, refreshing citrus flavour to both sweet and savoury cooking. Use the juice in lemonade, marinades, dressings or lemon chicken, and add the zest to cakes, desserts and sauces when suitable for culinary use.</p><h2>Seasonal fruit</h2><p>This is part of Tropitop's seasonal fruit selection, so availability varies with the harvest rather than remaining constant throughout the year. Choose the weight or format shown on the product page.</p><h2>Keeping lemons fresh</h2><p>Store them in the refrigerator if you want to keep them for longer. Check individual fruit when unpacking and use any ripe or damaged pieces first. No producer-specific payment, subscription or delivery arrangement is part of this product description.</p>"
  },
  {
    "source_id": 769,
    "id": 18430,
    "es_title": "Naranjas y limones",
    "title": "Oranges and lemons — mixed citrus box",
    "slug": "oranges-and-lemons-mixed-citrus-box",
    "short": "A mixed box combining oranges with fresh lemons. See the available format and weight before ordering.",
    "body": "<p>This mixed citrus selection brings together oranges for eating or juicing and lemons for adding acidity and aroma to cooking. It is a practical combination for households that use fresh citrus throughout the week.</p><p>The exact format and weight are shown with the product's available buying option. Fruit sizes can differ naturally; check the selected pack before adding it to your basket. Keep the citrus in a cool place or refrigerate for longer storage.</p>"
  },
  {
    "source_id": 770,
    "id": 18457,
    "es_title": "Pitahayas ecológicas",
    "title": "Organic pitahayas (dragon fruit)",
    "slug": "organic-pitahayas-dragon-fruit",
    "short": "Organic pitahayas, also known as dragon fruit. Choose the available weight or pack format.",
    "body": "<p>Pitahaya, often called dragon fruit, is a tropical fruit suited to fresh desserts and colourful fruit dishes. Slice it for a fruit salad, add the flesh to a smoothie or blend it into a chilled bowl with other seasonal fruit.</p><h2>Ideas for serving</h2><p>It can be paired with yoghurt, mango and avocado, used in homemade ice cream or incorporated into a fresh salsa for savoury dishes. Its distinctive appearance makes it an interesting addition to a mixed-fruit platter.</p><p>This product is sold as organic pitahaya. Select the weight or pack format currently offered, which may vary with seasonal availability.</p>"
  },
  {
    "source_id": 771,
    "id": 18491,
    "es_title": "Mandarinas valencianas online a domicilio",
    "title": "Seasonal Valencian mandarins and clementines",
    "slug": "seasonal-valencian-mandarins-clementines",
    "short": "Sweet, juicy mandarins from Castellón, Spain. Choose a 4 or 9 kg box; the variety may change as the season progresses.",
    "body": "<p>Valencian mandarins from Castellón offer juicy segments, a sweet flavour with a gentle citrus tang and an easy-to-peel, relatively thin skin. The selection begins with clementines and may change to another mandarin variety as the harvest progresses.</p><h2>Box format and provenance</h2><p>Choose a 4 kg or 9 kg box. Fruits are picked when they reach the appropriate stage of maturity and are not sorted into a fixed uniform calibre; sizes and shapes can vary. The source selection comes from seasonal mandarin-growing areas around Castellón.</p><h2>Storage</h2><p>Keep the fruit somewhere cool, dry and ventilated, away from direct heat and moisture. Refrigerate it to help it last longer. Easy-to-peel mandarins are convenient for snacks, lunchboxes and fresh fruit salads.</p>"
  },
  {
    "source_id": 774,
    "id": 18541,
    "es_title": "Pomelos",
    "title": "Seasonal fresh grapefruits",
    "slug": "seasonal-fresh-grapefruits",
    "short": "Fresh seasonal grapefruits from Tropitop's family-grown selection. Availability is usually limited to November through early April.",
    "body": "<p>Grapefruit offers a distinctive balance of acidity and bitterness, making it useful as a fresh breakfast fruit, in citrus salads or squeezed into drinks. The producer describes these grapefruits as part of its own family-farm crop.</p><h2>Harvest season</h2><p>Availability is limited to the crop: the stated sales window is approximately 1 November to 3 April, depending on the season. The farm is in a transition towards more environmentally focused growing methods; this product should not be assumed to have organic certification.</p><p>Select the currently available pack option on this page. Store the fruit in a cool, ventilated location or refrigerate if keeping it longer.</p>"
  },
  {
    "source_id": 775,
    "id": 18560,
    "es_title": "Pomelos y naranjas",
    "title": "Grapefruits and oranges — mixed citrus pack",
    "slug": "grapefruits-and-oranges-mixed-citrus",
    "short": "A citrus pairing of fresh grapefruits and oranges in one pack. Check the available format and weight on this page.",
    "body": "<p>Pairing grapefruit with orange gives a contrast between the grapefruit's characteristic bittersweet tang and the more rounded sweetness of orange. Serve both chilled, make a mixed citrus salad or squeeze them separately to suit different tastes.</p><p>The pack combines both fruits rather than a single variety. Its current format and weight are shown in the product options; individual citrus fruits may vary in size. Refrigerate for longer storage.</p>"
  },
  {
    "source_id": 779,
    "id": 18604,
    "es_title": "Mandarinas y pomelos",
    "title": "Mandarins and grapefruits — mixed citrus selection",
    "slug": "mandarins-grapefruits-mixed-selection",
    "short": "Fresh mandarins and grapefruits together: easy-peel sweetness alongside a more bittersweet citrus flavour.",
    "body": "<p>This mixed fruit selection combines easy-to-peel mandarins with grapefruits. It suits anyone who enjoys both sweet citrus segments and the sharper character of grapefruit, whether served on their own or as part of a fruit salad.</p><p>Choose the pack format available at checkout. Since these are seasonal fruits, appearance, size and availability can change during the harvest. Keep them cool and inspect the pieces as you unpack.</p>"
  },
  {
    "source_id": 781,
    "id": 18636,
    "es_title": "Mix mandarinas y naranjas",
    "title": "Mandarin and orange citrus mix",
    "slug": "mandarin-and-orange-citrus-mix",
    "short": "A mixed citrus box of mandarins and oranges, offering easy-peel fruit and oranges for eating or juicing.",
    "body": "<p>Mandarins make a convenient hand-held snack, while oranges add versatility for breakfast, juice and recipes. This box brings both into one citrus selection instead of requiring separate purchases.</p><p>Check the available pack and total weight on the product page. Natural fruit sizes may differ and varieties can change during the season. Keep the box cool, or refrigerate its contents to help maintain freshness.</p>"
  },
  {
    "source_id": 783,
    "id": 18768,
    "es_title": "Aguacates bacon y pomelos",
    "title": "Bacon avocados and grapefruits — mixed fruit box",
    "slug": "bacon-avocados-and-grapefruits-box",
    "short": "Creamy smooth-skinned Bacon avocados paired with fresh, bittersweet grapefruits in a single fruit pack.",
    "body": "<p>Two contrasting fruits come together in this mixed pack: mild, creamy Bacon avocados and tart, bittersweet grapefruits. Slice the avocado into salads and use grapefruit segments to add a bright citrus note.</p><p>Refer to the available format and total weight before ordering. Bacon avocados may arrive firm and can be ripened at room temperature; refrigerate them once ripe. Grapefruits keep well in a cool place or the fridge.</p>"
  },
  {
    "source_id": 786,
    "id": 18814,
    "es_title": "Pack aguacate piel lisa y pitahaya",
    "title": "Smooth-skinned avocados and pitahaya pack",
    "slug": "smooth-skinned-avocado-and-pitahaya-pack",
    "short": "A mixed fruit pack with creamy, smooth-skinned avocados and tropical pitahaya (dragon fruit).",
    "body": "<p>This combination offers two different textures: rich, creamy avocado and pitahaya, also known as dragon fruit. Use them together in a fruit bowl or serve the avocado in savoury dishes and the pitahaya as a fresh dessert.</p><p>Choose the pack currently offered on this page. Fruit sizes vary naturally. Let firm avocados ripen at room temperature and move them into the fridge once ready; follow the condition of the pitahaya when deciding when to serve it.</p>"
  },
  {
    "source_id": 787,
    "id": 18826,
    "es_title": "Mandarina y pitahaya",
    "title": "Mandarins and pitahaya — fresh fruit pack",
    "slug": "mandarins-and-pitahaya-fresh-fruit-pack",
    "short": "Sweet, juicy mandarins combined with pitahaya (dragon fruit) in a mixed fresh-fruit selection.",
    "body": "<p>Pair easy-to-peel mandarins with dragon fruit for a colourful fruit plate. The mandarins contribute a refreshing citrus flavour, while pitahaya brings a different tropical texture and appearance.</p><p>This is a combined fresh-fruit pack. Check its available format before buying, as the selection is offered in the size shown with the product. Keep the fruits somewhere cool and use ripe pieces first.</p>"
  },
  {
    "source_id": 788,
    "id": 18838,
    "es_title": "Granadas",
    "title": "Fresh pomegranates",
    "slug": "fresh-pomegranates-tropitop",
    "short": "Fresh pomegranates with juicy seeds for eating, salads, yoghurt and desserts. Select the currently available pack.",
    "body": "<p>Pomegranates contain clusters of juicy, jewel-like arils that can be scattered over salads, spooned into yoghurt or served with desserts. Their sweet-tart character also works as a contrast to savoury grains and roasted vegetables.</p><h2>Serving and storage</h2><p>Cut the fruit carefully and separate the seeds from the bitter white membranes. Select the weight or format offered on this page. Whole pomegranates can be stored in a cool place; refrigerate the arils once removed from the fruit.</p>"
  },
  {
    "source_id": 789,
    "id": 18859,
    "es_title": "Granadas y aguacates piel lisa",
    "title": "Pomegranates and smooth-skinned avocados",
    "slug": "pomegranates-smooth-skinned-avocados-mix",
    "short": "A fruit combination of fresh pomegranates and smooth-skinned avocados, with sweetness, acidity and creamy texture.",
    "body": "<p>Pomegranate arils bring a bright, sweet-tart pop to the gentle creaminess of avocado. This mixed selection can be used for colourful salads, grain bowls or a simple platter with both fruits served separately.</p><p>The total weight and available pack format are displayed with the buying option. If an avocado arrives firm, ripen it at room temperature and refrigerate it when ready. Remove pomegranate seeds shortly before serving for their best texture.</p>"
  },
  {
    "source_id": 790,
    "id": 18873,
    "es_title": "Granadas y pitahayas",
    "title": "Pomegranates and pitahayas — mixed fruit selection",
    "slug": "pomegranates-pitahayas-mixed-fruit",
    "short": "A pairing of pomegranates and pitahayas (dragon fruit), offering contrasting textures and a colourful presentation.",
    "body": "<p>Bring together the juicy arils of pomegranate and the striking flesh of pitahaya. The two fruits make an interesting combination in smoothie bowls, salads or fresh fruit desserts, with pomegranate adding a sweet-tart accent.</p><p>Check the available pack size and weight on this product page. Both fruits can vary naturally in size and ripeness; handle them gently and keep them in suitable cool conditions until ready to eat.</p>"
  },
  {
    "source_id": 791,
    "id": 18887,
    "es_title": "Granadas y mandarinas",
    "title": "Pomegranates and mandarins — mixed citrus and fruit box",
    "slug": "pomegranates-mandarins-mixed-fruit-box",
    "short": "Fresh pomegranates paired with sweet mandarins, suitable for snacking and colourful fruit salads.",
    "body": "<p>Sweet, easy-to-peel mandarins and juicy pomegranate seeds give this mixed box two ways to enjoy seasonal fruit. Serve the mandarin segments on their own or combine them with pomegranate arils for a fresh salad.</p><p>The available selection is sold as a combined fruit pack; check the format and weight shown alongside the product before purchase. Store the whole fruits somewhere cool and refrigerate prepared arils.</p>"
  }
]
PRODUCTS
,true,512,JSON_THROW_ON_ERROR);
if(count($items)!==18)throw new RuntimeException('Only 18 authorised');
$mode=(string)(getenv('EMDO_TROPITOP_HAND_EN_MODE')?:'audit');
if(!in_array($mode,['audit','apply'],true))throw new RuntimeException('Invalid mode');
global $wpdb;
$su=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$su} WHERE code=%s",'tropitop'),ARRAY_A);
if(!$s)throw new RuntimeException('Tropitop not found');
$report=['mode'=>$mode,'expected'=>18,'verified_sources'=>0,'needs_update'=>0,'updated'=>0,'already_complete'=>0,'skipped'=>[],'verified'=>false,'ids'=>[]];
$pending=[];$backup=[];
$allowed=['_en_US_post_title','_en_US_post_name','_en_US_post_excerpt','_en_US_post_content','_en_US_published','_en_US_ready'];
foreach($items as $r){
 $source_id=(int)$r['source_id'];$id=(int)$r['id'];
 $row=$wpdb->get_row($wpdb->prepare("SELECT wc_product_id,source_url FROM {$sp} WHERE id=%d AND supplier_id=%d",$source_id,(int)$s['id']),ARRAY_A);
 $p=wc_get_product($id);
 if(!$row||(int)$row['wc_product_id']!==$id||!$p||$p->get_name()!==$r['es_title']||
   $p->get_status()!=='publish'||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock()||
   (int)get_post_field('post_author',$id)!==(int)$s['vendor_user_id']||
   !in_array(strtolower((string)wp_parse_url((string)$row['source_url'],PHP_URL_HOST)),['tropitop.com','www.tropitop.com'],true)){
  $report['skipped'][]=['source_id'=>$source_id,'reason'=>'source_identity_or_purchasability_changed'];continue;
 }
 $report['verified_sources']++;
 $translated=[
  '_en_US_post_title'=>$r['title'],'_en_US_post_name'=>$r['slug'],
  '_en_US_post_excerpt'=>'<p>'.esc_html($r['short']).'</p>',
  '_en_US_post_content'=>wp_kses_post($r['body']),
  '_en_US_published'=>'1','_en_US_ready'=>'1'
 ];
 if(strlen(wp_strip_all_tags($translated['_en_US_post_content']))<135||
    strlen($translated['_en_US_post_title'])<12||
    strlen($translated['_en_US_post_excerpt'])<40||
    preg_match('~https?://|tropitop\\.com~i',$translated['_en_US_post_content'])){
   $report['skipped'][]=['source_id'=>$source_id,'reason'=>'editorial_validation_failed'];continue;
 }
 $cur=[];foreach($allowed as $key)$cur[$key]=(string)get_post_meta($id,$key,true);
 $full=$cur['_en_US_published']==='1'&&$cur['_en_US_post_title']!==''&&$cur['_en_US_post_name']!==''&&
     trim(wp_strip_all_tags($cur['_en_US_post_content']))!==''&&trim(wp_strip_all_tags($cur['_en_US_post_excerpt']))!=='';
 if($full){
   // Publishing a hand-authored replacement over a previous translation would
   // be an unsupervised overwrite. Fail until separately reviewed.
   $report['skipped'][]=['source_id'=>$source_id,'reason'=>'already_translated_requires_review'];continue;
 }
 if($cur['_en_US_post_title']!==''||$cur['_en_US_post_name']!==''||
    trim(wp_strip_all_tags($cur['_en_US_post_content']))!==''||
    trim(wp_strip_all_tags($cur['_en_US_post_excerpt']))!==''){
   $report['skipped'][]=['source_id'=>$source_id,'reason'=>'partial_english_requires_review'];continue;
 }
 $other=$wpdb->get_var($wpdb->prepare(
  "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_name' AND meta_value=%s AND post_id<>%d LIMIT 1",
  $r['slug'],$id));
 if($other){$report['skipped'][]=['source_id'=>$source_id,'reason'=>'english_slug_collision'];continue;}
 $backup[$id]=[];
 foreach($allowed as $key)$backup[$id][$key]=['exists'=>metadata_exists('post',$id,$key),'value'=>get_post_meta($id,$key,true)];
 $pending[$id]=$translated;
 $report['needs_update']++;
}
if($report['verified_sources']!==18||count($report['skipped'])||$report['needs_update']!==18){
 echo 'EMDO_TROPITOP_HAND_EN='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;exit(17);
}
if($mode==='apply'){
 $key='emdo_tropitop_hand18_en_backup_20261021_'.gmdate('YmdHis').'_'.wp_generate_password(6,false,false);
 if(!add_option($key,['before'=>$backup,'created'=>gmdate('c')],'',false))throw new RuntimeException('Backup failed');
 $report['backup_option']=$key;
 foreach($pending as $id=>$vals){
  foreach($vals as $meta=>$val)update_post_meta($id,$meta,$val);
  clean_post_cache($id);
  $report['updated']++;$report['ids'][]=$id;
 }
}
$correct=true;
if($mode==='apply')foreach($pending as $id=>$vals)
 foreach($vals as $meta=>$val)
  if((string)get_post_meta($id,$meta,true)!==(string)$val)$correct=false;
$report['verified']=$correct;
$report['complete']=$correct&&($mode==='audit'||$report['updated']===18);
echo 'EMDO_TROPITOP_HAND_EN='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$report['complete'])exit(18);
