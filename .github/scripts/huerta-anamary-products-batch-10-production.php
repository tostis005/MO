<?php
/** La Huerta de Ana Mary product copy batch 10 (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;
function mo_ha10_fail($m){ if(defined('WP_CLI')&&WP_CLI){WP_CLI::error($m);} throw new Exception($m); }
function mo_ha10_vendor($p){$u=get_userdata((int)$p->post_author);return $u?(string)$u->display_name:'';}
function mo_ha10_segments($h){$o=[];if(preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu',$h,$m,PREG_SET_ORDER)){foreach($m as $r){$t=trim(html_entity_decode(wp_strip_all_tags($r[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));if($t!=='')$o[]=['tag'=>strtolower($r[1]),'text'=>$t];}}return $o;}
function mo_ha10_pair($es,$en,$label){$a=mo_ha10_segments($es);$b=mo_ha10_segments($en);if(count($a)!==count($b))mo_ha10_fail("Segment mismatch {$label}: ".count($a)."/".count($b));$o=[];foreach($a as $i=>$r){if($r['tag']!==$b[$i]['tag'])mo_ha10_fail("Tag mismatch {$label}");$o[$r['text']]=$b[$i]['text'];}return $o;}
function mo_ha10_trp($table,$o,$t){global $wpdb;$id=$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE original=%s ORDER BY id DESC LIMIT 1",$o));if($id){if($wpdb->update($table,['translated'=>$t,'status'=>2,'block_type'=>0],['id'=>(int)$id],['%s','%d','%d'],['%d'])===false)mo_ha10_fail("TRP update failed: {$o}");}else{if($wpdb->insert($table,['original'=>$o,'translated'=>$t,'status'=>2,'block_type'=>0],['%s','%s','%d','%d'])===false)mo_ha10_fail("TRP insert failed: {$o}");}}
$producer_es=<<<'HTML'
<h2>Sobre La Huerta de Ana Mary</h2>
<p>En Fresno de la Vega, en la provincia de León, la agricultura y el cultivo de hortalizas forman parte de una tradición ligada a la Vega del Esla desde hace generaciones. La Huerta de Ana Mary continúa esa actividad familiar, con más de tres generaciones vinculadas al trabajo en el campo.</p>
<p>Las verduras y hortalizas se cultivan en parcelas de Fresno de la Vega, aprovechando la experiencia agrícola de la zona y adaptando el trabajo a los ciclos y temporadas de cada cultivo.</p>
<p>La recolección se organiza en función de los pedidos para reducir el tiempo de almacenamiento y acortar al máximo el recorrido entre la huerta y el consumidor, favoreciendo que el producto llegue fresco y en buenas condiciones.</p>
HTML;
$producer_en=<<<'HTML'
<h2>About La Huerta de Ana Mary</h2>
<p>In Fresno de la Vega, in the province of León, farming and vegetable growing are part of a tradition connected to the Vega del Esla that goes back generations. La Huerta de Ana Mary continues this family activity, with more than three generations linked to working the land.</p>
<p>The vegetables are grown on plots in Fresno de la Vega, drawing on the farming experience of the area and adapting the work to the cycles and seasons of each crop.</p>
<p>Harvesting is organised around orders to reduce storage time and keep the journey from the fields to the customer as short as possible, helping the produce arrive fresh and in good condition.</p>
HTML;
$products=[
['id'=>12849,'title'=>'Lenteja pardina','title_en'=>'Pardina Lentils','slug'=>'lenteja-pardina','es_excerpt'=>'<p>Lenteja pardina de pequeño tamaño, color pardo y piel fina. Mantiene bien la forma durante la cocción y resulta adecuada para guisos, potajes, ensaladas y otros platos de legumbres. Se vende por kilo.</p>','en_excerpt'=>'<p>Small Pardina lentils with a brown colour and thin skin. They hold their shape well during cooking and are suitable for stews, salads and other pulse-based dishes. Sold by the kilogram.</p>','es_content'=><<<'HTML'
<h2>Lenteja pardina pequeña y consistente</h2>
<p>La lenteja pardina se caracteriza por su pequeño diámetro, de aproximadamente 3,5 a 4,5 mm, su color pardo y una piel fina. Durante la cocción mantiene una textura consistente y no se deshace con facilidad.</p>
<p>Su cultivo se sitúa en la comarca leonesa de Los Oteros y también en zonas de Palencia, Valladolid y Zamora, dentro del entorno de Tierra de Campos. Esta referencia se vende por kilo.</p>
<h2>Cómo prepararla</h2>
<p>Es adecuada para guisos, potajes, sopas y ensaladas de legumbres. Al ser una lenteja de tamaño pequeño, conviene controlar la cocción para mantener el punto de textura deseado.</p>
HTML,'en_content'=><<<'HTML'
<h2>Small, firm Pardina lentils</h2>
<p>Pardina lentils are characterised by their small diameter, approximately 3.5 to 4.5 mm, brown colour and thin skin. They keep a firm texture during cooking and do not fall apart easily.</p>
<p>They are grown in the Los Oteros area of León and also in parts of Palencia, Valladolid and Zamora, within the Tierra de Campos region. This product is sold by the kilogram.</p>
<h2>How to cook them</h2>
<p>They work well in stews, soups and lentil salads. Because they are small, it is worth monitoring the cooking time to keep the texture you prefer.</p>
HTML,'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2><h3>¿Qué tamaño tiene la lenteja pardina?</h3><p>Su diámetro aproximado es de 3,5 a 4,5 mm.</p><h3>¿Mantiene la forma al cocer?</h3><p>Sí. Es una lenteja de textura consistente que no se deshace con facilidad.</p><h3>¿Cómo se vende?</h3><p>Esta referencia se vende por kilo.</p>
HTML,'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2><h3>How large are Pardina lentils?</h3><p>Their approximate diameter is 3.5 to 4.5 mm.</p><h3>Do they hold their shape when cooked?</h3><p>Yes. They have a firm texture and do not fall apart easily.</p><h3>How are they sold?</h3><p>This product is sold by the kilogram.</p>
HTML],
['id'=>13233,'title'=>'10 kg de pimientos lamuyos rojos, especiales para asar','title_en'=>'10 kg Red Lamuyo Peppers for Roasting','slug'=>'10-kg-de-pimientos-lamuyos-rojos-especiales-para-asar','es_excerpt'=>'<p>Caja de 10 kg de pimientos lamuyos rojos, de gran tamaño, carne gruesa y sabor dulce. Especialmente adecuados para asar, rellenar o preparar conservas. Gastos de envío incluidos.</p>','en_excerpt'=>'<p>10 kg box of red Lamuyo peppers, large in size with thick flesh and a sweet flavour. Especially suitable for roasting, stuffing or preserving. Shipping costs included.</p>','es_content'=><<<'HTML'
<h2>10 kg de pimientos lamuyos rojos para asar</h2>
<p>El pimiento lamuyo rojo destaca por su gran tamaño, su carne gruesa y firme, su sabor dulce y su característico color rojo intenso. Este formato incluye 10 kg de pimientos y los gastos de envío están incluidos.</p>
<p>Por su grosor y tamaño es una variedad especialmente adecuada para asar y también puede utilizarse para rellenar o preparar conservas caseras.</p>
<h2>Cómo aprovecharlos</h2>
<p>Pueden asarse enteros hasta que la piel se desprenda con facilidad, prepararse rellenos o incorporarse a ensaladas, guarniciones y otros platos. Para conservarlos frescos, mantenlos en un lugar adecuado para hortalizas y evita el exceso de humedad.</p>
HTML,'en_content'=><<<'HTML'
<h2>10 kg of red Lamuyo peppers for roasting</h2>
<p>Red Lamuyo peppers stand out for their large size, thick firm flesh, sweet flavour and characteristic deep red colour. This format contains 10 kg of peppers and shipping costs are included.</p>
<p>Their size and thick flesh make them especially suitable for roasting, and they can also be stuffed or used for homemade preserves.</p>
<h2>How to use them</h2>
<p>Roast them whole until the skin is easy to remove, prepare them stuffed, or add them to salads, side dishes and other recipes. To keep them fresh, store them appropriately for fresh vegetables and avoid excess moisture.</p>
HTML,'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2><h3>¿Cuánta cantidad incluye?</h3><p>Incluye 10 kg de pimientos lamuyos rojos.</p><h3>¿Son adecuados para asar?</h3><p>Sí. Su carne gruesa y su tamaño hacen que sean especialmente apropiados para asar.</p><h3>¿Los gastos de envío están incluidos?</h3><p>Sí. La ficha del producto indica que los gastos de envío están incluidos.</p>
HTML,'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2><h3>How much is included?</h3><p>The box contains 10 kg of red Lamuyo peppers.</p><h3>Are they suitable for roasting?</h3><p>Yes. Their thick flesh and large size make them especially suitable for roasting.</p><h3>Are shipping costs included?</h3><p>Yes. The product information states that shipping costs are included.</p>
HTML],
['id'=>13235,'title'=>'Tomates','title_en'=>'Tomatoes','slug'=>'tomates','es_excerpt'=>'<p>Tomates de tamaño medio-grande, forma achatada, piel fina y sabor intenso. No son de invernadero y se venden por kilo. Adecuados para ensaladas, salsas, tostadas y numerosas preparaciones de cocina.</p>','en_excerpt'=>'<p>Medium-to-large tomatoes with a flattened shape, thin skin and intense flavour. They are not greenhouse-grown and are sold by the kilogram. Suitable for salads, sauces, toast and many everyday recipes.</p>','es_content'=><<<'HTML'
<h2>Tomates de piel fina y sabor intenso</h2>
<p>Estos tomates tienen un tamaño medio-grande, forma achatada y piel fina. Destacan por su sabor intenso y no proceden de cultivo en invernadero.</p>
<p>Esta referencia se vende por kilo.</p>
<h2>Cómo aprovecharlos</h2>
<p>Son adecuados para ensaladas, tostadas, salsas, sofritos y otras preparaciones en las que el tomate tenga protagonismo. Para conservarlos, evita golpes y exceso de humedad y adapta la temperatura de almacenamiento al grado de maduración.</p>
HTML,'en_content'=><<<'HTML'
<h2>Thin-skinned tomatoes with intense flavour</h2>
<p>These tomatoes are medium to large, with a flattened shape and thin skin. They have an intense flavour and are not greenhouse-grown.</p>
<p>This product is sold by the kilogram.</p>
<h2>How to use them</h2>
<p>They are suitable for salads, toast, sauces, sofrito and other dishes where tomato is a main ingredient. To store them, avoid bruising and excess moisture and adjust the storage temperature to their stage of ripeness.</p>
HTML,'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2><h3>¿Son tomates de invernadero?</h3><p>No. La ficha del producto indica que no son de invernadero.</p><h3>¿Cómo se venden?</h3><p>Esta referencia se vende por kilo.</p><h3>¿Para qué preparaciones son adecuados?</h3><p>Para ensaladas, tostadas, salsas, sofritos y muchas otras recetas.</p>
HTML,'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2><h3>Are they greenhouse-grown?</h3><p>No. The product information states that they are not greenhouse-grown.</p><h3>How are they sold?</h3><p>This product is sold by the kilogram.</p><h3>What are they suitable for?</h3><p>Salads, toast, sauces, sofrito and many other recipes.</p>
HTML],
['id'=>13238,'title'=>'Loras o ñoras','title_en'=>'Loras or Ñora Peppers','slug'=>'loras-o-noras','es_excerpt'=>'<p>Loras o ñoras de tipo pimiento de bola, color rojo brillante y carne abundante. Se venden por kilo y resultan adecuadas para asar, rellenar o incorporar a distintas preparaciones de cocina.</p>','en_excerpt'=>'<p>Loras or ñora-style round peppers with a bright red colour and generous flesh. Sold by the kilogram and suitable for roasting, stuffing or using in a variety of dishes.</p>','es_content'=><<<'HTML'
<h2>Loras o ñoras, pimientos rojos de forma redondeada</h2>
<p>Las loras, también conocidas como ñoras según la zona, son un tipo de pimiento de bola de color rojo brillante. Tienen abundante carne y una piel que se retira con facilidad después de cocinarlas.</p>
<p>Esta referencia se vende por kilo.</p>
<h2>Cómo prepararlas</h2>
<p>Pueden asarse, rellenarse o utilizarse en distintas preparaciones de cocina. Su forma y cantidad de carne las hacen especialmente prácticas para recetas al horno o para servir como acompañamiento.</p>
HTML,'en_content'=><<<'HTML'
<h2>Loras or ñora peppers with a rounded shape</h2>
<p>Loras, also known as ñoras depending on the area, are a type of round bright-red pepper. They have plenty of flesh and a skin that is easy to remove after cooking.</p>
<p>This product is sold by the kilogram.</p>
<h2>How to cook them</h2>
<p>They can be roasted, stuffed or used in a range of dishes. Their shape and generous flesh make them especially practical for oven cooking or serving as a side dish.</p>
HTML,'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2><h3>¿Qué son las loras o ñoras?</h3><p>Son un tipo de pimiento de bola, de forma redondeada y color rojo brillante.</p><h3>¿Cómo se venden?</h3><p>Esta referencia se vende por kilo.</p><h3>¿Cómo pueden prepararse?</h3><p>Se pueden asar, rellenar o incorporar a distintas preparaciones de cocina.</p>
HTML,'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2><h3>What are loras or ñora peppers?</h3><p>They are a type of rounded, bright-red pepper.</p><h3>How are they sold?</h3><p>This product is sold by the kilogram.</p><h3>How can they be prepared?</h3><p>They can be roasted, stuffed or used in a variety of dishes.</p>
HTML],
['id'=>13241,'title'=>'Tomate pera','title_en'=>'Pear Tomatoes','slug'=>'tomate-pera','es_excerpt'=>'<p>Tomate pera de temporada, cultivado al aire libre y vendido por kilo. De forma alargada, piel fina, mucha carne y sabor suave y dulce, resulta especialmente adecuado para gazpacho, salmorejo, salsas y conservas.</p>','en_excerpt'=>'<p>Seasonal pear tomatoes grown outdoors and sold by the kilogram. Elongated, thin-skinned and fleshy with a mild sweet flavour, they are especially suitable for gazpacho, salmorejo, sauces and preserves.</p>','es_content'=><<<'HTML'
<h2>Tomate pera de temporada cultivado al aire libre</h2>
<p>El tomate pera recibe su nombre por su característica forma alargada. Tiene una piel fina, abundante carne y un sabor suave y dulce. Se trata de tomate de temporada cultivado al aire libre, no en invernadero.</p>
<p>Esta referencia se vende por kilo.</p>
<h2>Cómo aprovecharlo</h2>
<p>Por su cantidad de carne resulta especialmente práctico para gazpacho, salmorejo, sopas frías, salsas y conservas. También puede utilizarse en ensaladas o para untar sobre pan.</p>
HTML,'en_content'=><<<'HTML'
<h2>Seasonal pear tomatoes grown outdoors</h2>
<p>Pear tomatoes take their name from their characteristic elongated shape. They have thin skin, plenty of flesh and a mild sweet flavour. These are seasonal tomatoes grown outdoors rather than in a greenhouse.</p>
<p>This product is sold by the kilogram.</p>
<h2>How to use them</h2>
<p>Their fleshy texture makes them especially practical for gazpacho, salmorejo, cold soups, sauces and preserves. They can also be used in salads or rubbed onto bread.</p>
HTML,'es_faq'=><<<'HTML'
<h2>Preguntas frecuentes</h2><h3>¿Se cultiva en invernadero?</h3><p>No. Es tomate de temporada cultivado al aire libre.</p><h3>¿Cómo se vende?</h3><p>Esta referencia se vende por kilo.</p><h3>¿Para qué preparaciones es especialmente adecuado?</h3><p>Para gazpacho, salmorejo, salsas, conservas, ensaladas y pan con tomate.</p>
HTML,'en_faq'=><<<'HTML'
<h2>Frequently asked questions</h2><h3>Is it greenhouse-grown?</h3><p>No. It is a seasonal tomato grown outdoors.</p><h3>How is it sold?</h3><p>This product is sold by the kilogram.</p><h3>What is it especially suitable for?</h3><p>Gazpacho, salmorejo, sauces, preserves, salads and tomato on bread.</p>
HTML]
];
$table=$wpdb->prefix.'trp_dictionary_es_es_en_us';if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table)mo_ha10_fail('TranslatePress table missing');
$backup=[];$pairs=[];
foreach($products as $i=>$p){$post=get_post($p['id']);if(!$post||$post->post_type!=='product'||$post->post_status!=='publish')mo_ha10_fail("Missing/unpublished {$p['id']}");if($post->post_title!==$p['title'])mo_ha10_fail("Title mismatch {$p['id']}: {$post->post_title}");if($post->post_name!==$p['slug'])mo_ha10_fail("Slug mismatch {$p['id']}: {$post->post_name}");if(stripos(mo_ha10_vendor($post),'Huerta de Ana Mary')===false)mo_ha10_fail("Vendor mismatch {$p['id']}");if(get_post_meta($p['id'],'_stock_status',true)!=='instock')mo_ha10_fail("Not instock {$p['id']}");$backup[$p['id']]=['post_excerpt'=>$post->post_excerpt,'post_content'=>$post->post_content,'_en_US_post_excerpt'=>get_post_meta($p['id'],'_en_US_post_excerpt',true),'_en_US_post_content'=>get_post_meta($p['id'],'_en_US_post_content',true)];$products[$i]['full_es']=$p['es_content']."\n".$producer_es."\n".$p['es_faq'];$products[$i]['full_en']=$p['en_content']."\n".$producer_en."\n".$p['en_faq'];$pairs[$p['title']]=$p['title_en'];$pairs=array_merge($pairs,mo_ha10_pair($p['es_excerpt'],$p['en_excerpt'],"excerpt {$p['id']}"));$pairs=array_merge($pairs,mo_ha10_pair($products[$i]['full_es'],$products[$i]['full_en'],"content {$p['id']}"));}
$key='mo_huerta_anamary_batch10_backup_20260831';if(get_option($key,false)===false){add_option($key,$backup,'','no');echo "BACKUP created {$key}\n";}else echo "BACKUP exists {$key}\n";
foreach($products as $p){$r=wp_update_post(['ID'=>$p['id'],'post_excerpt'=>$p['es_excerpt'],'post_content'=>$p['full_es']],true);if(is_wp_error($r))mo_ha10_fail("Update failed {$p['id']}: ".$r->get_error_message());update_post_meta($p['id'],'_en_US_post_excerpt',$p['en_excerpt']);update_post_meta($p['id'],'_en_US_post_content',$p['full_en']);}
foreach($pairs as $o=>$t)mo_ha10_trp($table,$o,$t);
foreach($products as $p){$post=get_post($p['id']);$en=(string)get_post_meta($p['id'],'_en_US_post_content',true);$ens=(string)get_post_meta($p['id'],'_en_US_post_excerpt',true);if(strpos($post->post_content,'Sobre La Huerta de Ana Mary')===false||strpos($post->post_content,'Preguntas frecuentes')===false)mo_ha10_fail("ES verify {$p['id']}");if(strpos($en,'About La Huerta de Ana Mary')===false||strpos($en,'Frequently asked questions')===false)mo_ha10_fail("EN verify {$p['id']}");if(trim(wp_strip_all_tags($ens))!==trim(wp_strip_all_tags($p['en_excerpt'])))mo_ha10_fail("EN excerpt verify {$p['id']}");echo "UPDATED_AND_VERIFIED ID={$p['id']} {$p['title']}\n";}
if(function_exists('wp_cache_flush'))wp_cache_flush();
echo "DONE huerta_batch10_products=".count($products)." translations=".count($pairs)."\n";
