<?php
/**
 * Third English GSC cohort: 20 targeted existing article URLs; metadata only.
 * GSC period Aug 12–Oct 10, 2026. No changes to bodies, permalinks, ads,
 * canonical/hreflang or WooCommerce.
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function elmercado_gsc_en_third_batch_20261012(): array {
	return array(
		'/en/how-long-opened-canned-food-keeps-in-fridge-how-to-store-it/' => array( 'title' => 'Opened Canned Food: How Long in the Fridge?', 'description' => 'How long does opened canned food keep in the fridge? Learn how to store leftovers, why the type of food matters and when it should be discarded.' ),
		'/en/raw-wagyu-tataki-carpaccio-safety/' => array( 'title' => 'Raw Wagyu: Tataki, Carpaccio and Food Safety', 'description' => 'Is Wagyu safe to serve raw as tataki or carpaccio? Understand foodborne risks, hygiene and why cooking remains the safer choice for vulnerable people.' ),
		'/en/vegetable-nutrients-vitamins-minerals-fibre/' => array( 'title' => 'Vegetable Nutrients: Vitamins and Fibre', 'description' => 'What nutrients do vegetables provide? Explore fibre, vitamins, minerals and variation by vegetable type, portion and preparation method.' ),
		'/en/net-weight-vs-drained-weight-in-preserves-what-each-means/' => array( 'title' => 'Net Weight vs Drained Weight: What They Mean', 'description' => 'What is the difference between net weight and drained weight on canned foods? Learn which figure includes the packing liquid and how to compare products.' ),
		'/en/white-mold-on-chorizo-salchichon-when-normal-when-not/' => array( 'title' => 'White Mould on Chorizo: Normal or Unsafe?', 'description' => 'White mould can be normal on some dry-cured sausages but unexpected growth needs care. Learn what labels, storage history and warning signs tell you.' ),
		'/en/vacuum-packed-meat-purple-dark-why-colour-changes-after-opening/' => array( 'title' => 'Why Vacuum-Packed Meat Looks Purple or Dark', 'description' => 'Why does vacuum-packed meat look dark or purple? Learn how oxygen changes myoglobin colour and which handling or spoilage signs require caution.' ),
		'/en/oleic-acid-olive-oil-what-is-it-how-much/' => array( 'title' => 'Oleic Acid in Olive Oil: What and How Much?', 'description' => 'What is oleic acid in olive oil? Explore the typical fatty-acid range, the role of cultivar and why higher oleic content alone does not prove EVOO quality.' ),
		'/en/bloomy-rind-cheese-white-mould-how-rind-forms-can-you-eat-it/' => array( 'title' => 'White Mould on Cheese Rinds: Can You Eat It?', 'description' => 'Why do Brie-style cheeses develop a white bloomy rind? Learn when that coating is intentional, which rinds are edible and when spoilage is different.' ),
		'/en/freezer-burn-on-meat-what-it-is-is-it-safe-and-how-to-prevent-it/' => array( 'title' => 'Freezer Burn on Meat: Safety and Prevention', 'description' => 'What causes freezer burn on meat, and is the affected food safe? Learn how dehydration affects texture, what to check and how packaging can help.' ),
		'/en/do-canned-foods-expire-how-long-they-last-how-to-tell-if-safe/' => array( 'title' => 'Do Canned Foods Expire? Safety and Shelf Life', 'description' => 'How long do canned foods last? Understand best-before dates, can damage, storage conditions and the warning signs that mean a can should be discarded.' ),
		'/en/beef-nutrients-protein-iron-zinc-vitamins/' => array( 'title' => 'Beef Nutrition: Protein, Iron and Zinc', 'description' => 'What nutrients does beef provide? Compare protein, iron, zinc and B vitamins, and learn why amounts differ between cuts and raw or cooked portions.' ),
		'/en/which-parts-of-a-leek-can-you-eat-how-to-clean-it/' => array( 'title' => 'Which Parts of a Leek Can You Eat?', 'description' => 'Can you eat the green part of a leek? Discover how white and green sections differ, how to clean trapped grit and which parts suit soups and stocks.' ),
		'/en/ham-smells-like-ammonia-why-it-happens-when-it-is-a-problem/' => array( 'title' => 'Ham Smells Like Ammonia: What It Means', 'description' => 'Why can cured ham smell of ammonia? Learn what may affect its aroma, when an unusual odour deserves concern and how storage history matters.' ),
		'/en/why-burgers-shrink-when-cooked-causes-and-how-to-reduce-it/' => array( 'title' => 'Why Burgers Shrink When Cooked and What Helps', 'description' => 'Why do burgers shrink as they cook? Learn how fat, moisture, heat and handling affect size, and what techniques can reduce shrinkage.' ),
		'/en/extra-virgin-vs-virgin-olive-oil-olive-oil-pomace-differences/' => array( 'title' => 'Extra Virgin vs Virgin vs Pomace Olive Oil', 'description' => 'Compare extra virgin, virgin and olive-pomace oils: how they are produced, how categories differ and which labels matter when choosing an oil.' ),
		'/en/how-much-dried-pulses-per-person-chickpeas-lentils-beans/' => array( 'title' => 'How Much Dried Pulses per Person?', 'description' => 'How many grams of dried chickpeas, lentils or beans should you plan per person? Compare portions by dish and see how soaking and cooking affect yield.' ),
		'/en/why-cauliflower-smells-when-cooked-how-to-reduce-the-odour/' => array( 'title' => 'Why Cauliflower Smells When Cooked', 'description' => 'Why does cauliflower smell strong during cooking? Understand sulfur compounds, the effect of cooking time and simple ways to reduce odours.' ),
		'/en/olive-oil-or-sunflower-oil-for-frying-which-to-choose/' => array( 'title' => 'Olive Oil vs Sunflower Oil for Frying', 'description' => 'Olive oil or sunflower oil for frying? Compare fat composition, heat stability and high-oleic versions, and learn why temperature and reuse matter.' ),
		'/en/how-much-protein-iberian-chorizo/' => array( 'title' => 'How Much Protein Is in Iberian Chorizo?', 'description' => 'How much protein does Iberian chorizo contain per 100 g? Learn why nutrition differs by recipe, curing, fat content and serving size.' ),
		'/en/rib-steak-vs-entrecote-differences-which-to-choose/' => array( 'title' => 'Rib Steak vs Entrecôte: Differences and Uses', 'description' => 'Rib steak and entrecôte come from related beef cuts but differ in bone, thickness and naming. Learn which suits grilling and how to compare portions.' ),
	);
}
function elmercado_gsc_en_third_target_20261012(): array {
	if ( is_admin() || ! is_singular( 'post' ) ) { return array(); }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$path = '/' . trim( $path, '/' ) . '/';
	$batch = elmercado_gsc_en_third_batch_20261012();
	return $batch[ $path ] ?? array();
}
function elmercado_gsc_en_third_title_20261012( $current ) {
	$target = elmercado_gsc_en_third_target_20261012();
	return isset( $target['title'] ) ? $target['title'] . ' | El Mercado de Origen' : $current;
}
function elmercado_gsc_en_third_description_20261012( $current ) {
	$target = elmercado_gsc_en_third_target_20261012();
	return $target['description'] ?? $current;
}
function elmercado_gsc_en_third_fallback_description_20261012(): string {
	$target = elmercado_gsc_en_third_target_20261012();
	return $target['description'] ?? '';
}
add_filter( 'pre_get_document_title', 'elmercado_gsc_en_third_title_20261012', 125 );
add_filter( 'wpseo_title', 'elmercado_gsc_en_third_title_20261012', 125 );
add_filter( 'rank_math/frontend/title', 'elmercado_gsc_en_third_title_20261012', 125 );
add_filter( 'aioseo_title', 'elmercado_gsc_en_third_title_20261012', 125 );
add_filter( 'seopress_titles_title', 'elmercado_gsc_en_third_title_20261012', 125 );
add_filter( 'wpseo_metadesc', 'elmercado_gsc_en_third_description_20261012', 125 );
add_filter( 'rank_math/frontend/description', 'elmercado_gsc_en_third_description_20261012', 125 );
add_filter( 'aioseo_description', 'elmercado_gsc_en_third_description_20261012', 125 );
add_filter( 'seopress_titles_desc', 'elmercado_gsc_en_third_description_20261012', 125 );
