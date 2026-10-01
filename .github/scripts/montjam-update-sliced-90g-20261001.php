<?php
/**
 * Update the four Montjam sliced products so every pack is explicitly 90 g.
 * Scope limited to product IDs 16908, 16910, 16912 and 16914.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "ABORT: WordPress not loaded\n"); exit(2); }
if (!class_exists('WooCommerce')) { fwrite(STDERR, "ABORT: WooCommerce unavailable\n"); exit(3); }

function mj90_fail($m){ fwrite(STDERR, "MONTJAM_90G_ABORT: {$m}\n"); exit(20); }

$specs = [
  16908 => [
    'slug' => 'jamon-bellota-100-iberico-loncheado-montjam-90g',
    'title' => 'Jamón de bellota 100% ibérico loncheado Montjam (brida negra) · 90 g',
    'short' => 'Jamón de bellota 100% ibérico Montjam, loncheado y envasado al vacío en sobre de 90 g. Elaborado en El Repilado, Huelva, con el carácter intenso y la grasa infiltrada propios de la brida negra.',
    'description' => '<h2>Jamón de bellota 100% ibérico Montjam loncheado · 90 g</h2><p>Este <strong>jamón de bellota 100% ibérico Montjam</strong> se presenta en un práctico <strong>sobre de 90 g</strong>, ya loncheado y envasado al vacío. Elaborado en El Repilado, Huelva, ofrece el carácter de una pieza de brida negra con aroma intenso, grasa infiltrada y sabor persistente.</p><p>El formato de 90 g resulta cómodo para aperitivos, tablas de ibéricos, celebraciones o para abrir solo la cantidad que se vaya a consumir.</p><h3>Cómo disfrutarlo</h3><p>Para apreciar mejor la textura y el aroma, conviene sacar el sobre del frío unos minutos antes de servir y disponer las lonchas a temperatura ambiente. Combina especialmente bien con pan y aceite de oliva virgen extra.</p><h3>Ingredientes y conservación</h3><p>Según el etiquetado del envase: jamón, sal marina, azúcar, conservador E-252, antioxidante E-301 y colorante de origen E-120. Conservar refrigerado entre 0 y 5 °C y seguir siempre las indicaciones impresas en el envase.</p>',
    'focus' => 'jamón de bellota 100% ibérico loncheado Montjam 90 g',
    'seo' => 'Jamón 100% ibérico loncheado Montjam 90 g | Mercado de Origen',
    'meta' => 'Compra jamón de bellota 100% ibérico loncheado Montjam en sobre de 90 g, brida negra, elaborado en Huelva y envasado al vacío.',
    'en_title' => 'Montjam 100% Iberian acorn-fed ham, sliced · 90 g',
    'en_slug' => 'montjam-100-iberian-acorn-fed-ham-sliced-90g',
    'en_short' => 'Montjam 100% Iberian acorn-fed ham, sliced and vacuum packed in a 90 g pack. Made in El Repilado, Huelva, with the deep flavour and marbling associated with the black label.',
    'en_description' => '<h2>Montjam 100% Iberian acorn-fed ham, sliced · 90 g</h2><p>This <strong>Montjam 100% Iberian acorn-fed ham</strong> comes in a convenient <strong>90 g pack</strong>, pre-sliced and vacuum packed. Made in El Repilado, Huelva, it offers the character of a black-label ham with intense aroma, marbled fat and a long savoury finish.</p><p>The 90 g format is ideal for appetisers, Iberian charcuterie boards, celebrations or opening only the amount you plan to enjoy.</p><h3>How to serve</h3><p>For the best texture and aroma, take the pack out of the fridge a few minutes before serving and allow the slices to come towards room temperature. It pairs particularly well with bread and extra virgin olive oil.</p><h3>Ingredients and storage</h3><p>According to the pack label: ham, sea salt, sugar, preservative E-252, antioxidant E-301 and colouring E-120. Keep refrigerated between 0 and 5 °C and always follow the instructions printed on the pack.</p>',
  ],
  16910 => [
    'slug' => 'paleta-bellota-100-iberica-loncheada-montjam-90g',
    'title' => 'Paleta de bellota 100% ibérica loncheada Montjam (brida negra) · 90 g',
    'short' => 'Paleta de bellota 100% ibérica Montjam loncheada, en sobre de 90 g y envasada al vacío. Un formato listo para servir con el sabor intenso y la jugosidad característicos de la paleta de bellota.',
    'description' => '<h2>Paleta de bellota 100% ibérica Montjam loncheada · 90 g</h2><p>La <strong>paleta de bellota 100% ibérica Montjam</strong> se presenta ya loncheada en un <strong>sobre de 90 g</strong>, lista para disfrutar sin necesidad de cortar una pieza entera. Elaborada en Huelva, reúne el sabor intenso, el aroma persistente y la jugosidad propios de una paleta de brida negra.</p><p>El envasado al vacío protege las lonchas hasta el momento de apertura y facilita tener a mano una ración cómoda para aperitivos, tablas de ibéricos o reuniones.</p><h3>Cómo servirla</h3><p>Atemperar el sobre unos minutos antes de servir ayuda a que la grasa se vuelva más brillante y a que la paleta exprese mejor su aroma y textura.</p><h3>Ingredientes y conservación</h3><p>Según el etiquetado: paleta, sal común, azúcar, conservador E-252, antioxidante E-301 y corrector de acidez E-331iii. Conservar entre 0 y 5 °C y respetar las indicaciones del envase.</p>',
    'focus' => 'paleta de bellota 100% ibérica loncheada Montjam 90 g',
    'seo' => 'Paleta 100% ibérica loncheada Montjam 90 g | Mercado de Origen',
    'meta' => 'Paleta de bellota 100% ibérica Montjam loncheada, brida negra, en sobre de 90 g y envasada al vacío. Elaborada en Huelva.',
    'en_title' => 'Montjam 100% Iberian acorn-fed shoulder ham, sliced · 90 g',
    'en_slug' => 'montjam-100-iberian-acorn-fed-shoulder-ham-sliced-90g',
    'en_short' => 'Montjam 100% Iberian acorn-fed shoulder ham, sliced in a 90 g vacuum-packed portion. Ready to serve, with the intense flavour and juiciness typical of acorn-fed Iberian shoulder ham.',
    'en_description' => '<h2>Montjam 100% Iberian acorn-fed shoulder ham, sliced · 90 g</h2><p>This <strong>Montjam 100% Iberian acorn-fed shoulder ham</strong> comes pre-sliced in a <strong>90 g pack</strong>, ready to enjoy without carving a whole shoulder. Made in Huelva, it combines the intense flavour, persistent aroma and juicy texture associated with a black-label Iberian shoulder ham.</p><p>Vacuum packing protects the slices until opening and makes this a convenient portion for appetisers, Iberian charcuterie boards and gatherings.</p><h3>How to serve</h3><p>Let the pack temper for a few minutes before serving so the fat becomes glossy and the ham can show its aroma and texture at their best.</p><h3>Ingredients and storage</h3><p>According to the label: shoulder ham, salt, sugar, preservative E-252, antioxidant E-301 and acidity regulator E-331iii. Keep between 0 and 5 °C and follow the instructions on the pack.</p>',
  ],
  16912 => [
    'slug' => 'lomo-bellota-100-iberico-loncheado-montjam-90g',
    'title' => 'Lomo de bellota 100% ibérico loncheado Montjam · 90 g',
    'short' => 'Lomo de bellota 100% ibérico Montjam loncheado en sobre de 90 g. Un curado de Huelva, aromático y equilibrado, listo para servir y envasado al vacío.',
    'description' => '<h2>Lomo de bellota 100% ibérico Montjam loncheado · 90 g</h2><p>El <strong>lomo de bellota 100% ibérico Montjam</strong> se presenta loncheado en un <strong>sobre de 90 g</strong>, un formato cómodo para disfrutar de este curado de Huelva directamente en aperitivos y tablas de ibéricos.</p><p>Su corte muestra una textura firme y jugosa y un perfil aromático en el que se integran el propio sabor del lomo con el aliño tradicional. El envasado al vacío ayuda a preservar el producto hasta la apertura.</p><h3>Cómo disfrutarlo</h3><p>Servir las lonchas ligeramente atemperadas permite apreciar mejor su aroma y textura. Puede acompañarse de pan, aceite de oliva virgen extra, quesos curados o combinarse con otros ibéricos Montjam.</p><h3>Ingredientes y conservación</h3><p>El etiquetado del envase recoge lomo de cerdo ibérico de bellota, sal, pimentón, ajo, especias, azúcares y aditivos autorizados. Conservar entre 0 y 5 °C y seguir las indicaciones del envase.</p>',
    'focus' => 'lomo de bellota 100% ibérico loncheado Montjam 90 g',
    'seo' => 'Lomo 100% ibérico loncheado Montjam 90 g | Mercado de Origen',
    'meta' => 'Lomo de bellota 100% ibérico Montjam loncheado, en sobre de 90 g y envasado al vacío. Curado de Huelva listo para servir.',
    'en_title' => 'Montjam 100% Iberian acorn-fed cured loin, sliced · 90 g',
    'en_slug' => 'montjam-100-iberian-acorn-fed-cured-loin-sliced-90g',
    'en_short' => 'Montjam 100% Iberian acorn-fed cured loin, sliced in a 90 g pack. An aromatic, balanced cured meat from Huelva, vacuum packed and ready to serve.',
    'en_description' => '<h2>Montjam 100% Iberian acorn-fed cured loin, sliced · 90 g</h2><p>This <strong>Montjam 100% Iberian acorn-fed cured loin</strong> comes sliced in a convenient <strong>90 g pack</strong>, ready to enjoy in appetisers and Iberian charcuterie boards.</p><p>The slices show a firm yet succulent texture and an aromatic profile in which the flavour of the loin blends with its traditional seasoning. Vacuum packing helps protect the product until opening.</p><h3>How to enjoy it</h3><p>Serve the slices slightly tempered to appreciate their aroma and texture. Pair with bread, extra virgin olive oil, mature cheese or other Montjam Iberian cured meats.</p><h3>Ingredients and storage</h3><p>The pack label lists acorn-fed Iberian pork loin, salt, paprika, garlic, spices, sugars and authorised additives. Keep between 0 and 5 °C and follow the instructions on the pack.</p>',
  ],
  16914 => [
    'slug' => 'chorizo-cular-iberico-extra-loncheado-montjam-90g',
    'title' => 'Chorizo cular ibérico extra loncheado Montjam · 90 g',
    'short' => 'Chorizo cular ibérico extra Montjam loncheado en sobre de 90 g y envasado al vacío. Elaborado en Huelva, con sabor intenso, pimentón y especias, listo para aperitivos y tablas.',
    'description' => '<h2>Chorizo cular ibérico extra Montjam loncheado · 90 g</h2><p>El <strong>chorizo cular ibérico extra Montjam</strong> se ofrece ya loncheado en un <strong>sobre de 90 g</strong> y envasado al vacío, listo para servir. Su elaboración combina carne de cerdo ibérico con pimentón, ajo y orégano para conseguir un perfil aromático, sabroso y de carácter tradicional.</p><p>El formato de 90 g resulta especialmente cómodo para aperitivos, bocadillos y tablas de embutidos, y permite abrir el producto justo cuando se va a consumir.</p><h3>Cómo servirlo</h3><p>Atemperar unos minutos antes de servir ayuda a que el chorizo exprese mejor sus aromas y su textura. Puede combinarse con lomo, salchichón, jamón y paleta para una tabla variada.</p><h3>Ingredientes, alérgenos y conservación</h3><p>Según el etiquetado: carne de cerdo ibérico, pimentón, sal, lactosa, dextrosa, proteínas de leche, ajo, orégano y aditivos autorizados, originalmente embutido en tripa natural. <strong>Contiene leche y lactosa.</strong> Conservar entre 0 y 5 °C y seguir las indicaciones del envase.</p>',
    'focus' => 'chorizo cular ibérico extra loncheado Montjam 90 g',
    'seo' => 'Chorizo cular ibérico extra Montjam 90 g | Mercado de Origen',
    'meta' => 'Chorizo cular ibérico extra Montjam loncheado en sobre de 90 g y envasado al vacío. Elaborado en Huelva. Contiene leche y lactosa.',
    'en_title' => 'Montjam extra Iberian cular chorizo, sliced · 90 g',
    'en_slug' => 'montjam-extra-iberian-cular-chorizo-sliced-90g',
    'en_short' => 'Montjam extra Iberian cular chorizo, sliced in a 90 g vacuum-packed portion. Made in Huelva with paprika and spices, ready for appetisers and charcuterie boards.',
    'en_description' => '<h2>Montjam extra Iberian cular chorizo, sliced · 90 g</h2><p>This <strong>Montjam extra Iberian cular chorizo</strong> comes pre-sliced in a <strong>90 g pack</strong> and vacuum packed, ready to serve. Iberian pork is seasoned with paprika, garlic and oregano to create an aromatic, savoury cured sausage with a traditional character.</p><p>The 90 g format is particularly convenient for appetisers, sandwiches and charcuterie boards, allowing you to open the product only when you are ready to eat it.</p><h3>How to serve</h3><p>Let it temper for a few minutes before serving so the chorizo can show its aroma and texture more fully. Pair it with cured loin, salchichón, ham and shoulder ham for a varied Iberian board.</p><h3>Ingredients, allergens and storage</h3><p>According to the label: Iberian pork, paprika, salt, lactose, dextrose, milk proteins, garlic, oregano and authorised additives, originally filled in natural casing. <strong>Contains milk and lactose.</strong> Keep between 0 and 5 °C and follow the instructions on the pack.</p>',
  ],
];

$expected_vendor = get_user_by('login','montjam');
if (!$expected_vendor) mj90_fail('Montjam vendor not found');

$results = [];
foreach ($specs as $id => $s) {
  $p = get_post($id);
  if (!$p || $p->post_type !== 'product') mj90_fail("Product {$id} missing");
  if ((int)$p->post_author !== (int)$expected_vendor->ID) mj90_fail("Vendor mismatch {$id}");

  $product = wc_get_product($id);
  if (!$product) mj90_fail("Woo product {$id} missing");

  $product->set_name($s['title']);
  $product->set_slug($s['slug']);
  $product->set_short_description($s['short']);
  $product->set_description($s['description']);
  $product->save();

  update_post_meta($id, '_yoast_wpseo_focuskw', $s['focus']);
  update_post_meta($id, '_yoast_wpseo_title', $s['seo']);
  update_post_meta($id, '_yoast_wpseo_metadesc', $s['meta']);

  update_post_meta($id, '_en_US_post_title', $s['en_title']);
  update_post_meta($id, '_en_US_post_name', $s['en_slug']);
  update_post_meta($id, '_en_US_post_excerpt', $s['en_short']);
  update_post_meta($id, '_en_US_post_content', $s['en_description']);
  update_post_meta($id, '_en_US_ready', '1');
  update_post_meta($id, '_en_US_published', '1');
  update_post_meta($id, '_montjam_sliced_pack_weight', '90 g');
  update_post_meta($id, '_montjam_sliced_product_version', '2026-10-01-v2-90g');

  wc_delete_product_transients($id);
  clean_post_cache($id);

  $fresh = wc_get_product($id);
  $ok = $fresh
    && $fresh->get_name() === $s['title']
    && get_post_field('post_name',$id) === $s['slug']
    && strpos(wp_strip_all_tags($fresh->get_short_description()), '90 g') !== false
    && strpos(wp_strip_all_tags($fresh->get_description()), '90 g') !== false
    && get_post_meta($id, '_en_US_post_title', true) === $s['en_title']
    && get_post_meta($id, '_en_US_post_name', true) === $s['en_slug']
    && strpos(wp_strip_all_tags(get_post_meta($id, '_en_US_post_excerpt', true)), '90 g') !== false
    && strpos(wp_strip_all_tags(get_post_meta($id, '_en_US_post_content', true)), '90 g') !== false;

  if (!$ok) mj90_fail("Verification failed {$id}");

  $results[] = [
    'id'=>$id,
    'title'=>$fresh->get_name(),
    'price'=>$fresh->get_regular_price('edit'),
    'slug_es'=>get_post_field('post_name',$id),
    'slug_en'=>get_post_meta($id,'_en_US_post_name',true),
    'url_es'=>get_permalink($id),
    'url_en'=>home_url('/en/product/'.$s['en_slug'].'/'),
  ];
}

wp_cache_flush();
if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product', true);
if (function_exists('rocket_clean_domain')) rocket_clean_domain();
if (function_exists('w3tc_flush_all')) w3tc_flush_all();
do_action('litespeed_purge_all');

echo 'MONTJAM_90G_OK ' . wp_json_encode(['count'=>count($results),'products'=>$results], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
