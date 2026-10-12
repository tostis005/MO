<?php
/** Second GSC batch: 20 existing English articles, metadata only.
 * No content, URLs, canonical/hreflang, product logic, ads or translations change.
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function elmercado_gsc_en_second_batch_20261012(): array {
	return array(
		'/en/how-much-iron-in-beef/' => array( 'title' => 'Iron in Beef: Amount per 100 g and Serving', 'description' => 'How much iron does beef provide? Compare amounts per 100 g and serving, learn about heme iron, and see how cuts and cooking affect the figures.' ),
		'/en/can-you-store-an-open-can-in-the-fridge-why-transfer-food/' => array( 'title' => 'Opened Cans in the Fridge: Is It Safe?', 'description' => 'Can you refrigerate food in an opened can? Learn when to transfer leftovers, how to store them safely and which signs mean you should discard them.' ),
		'/en/chickpeas-soaking-time-how-long-to-cook/' => array( 'title' => 'How Long to Soak Chickpeas? Cooking Times', 'description' => 'See typical chickpea soaking and cooking times, what changes with a pressure cooker, and how to check for tenderness without overcooking.' ),
		'/en/green-sprouted-potatoes-when-safe-when-to-discard/' => array( 'title' => 'Green or Sprouted Potatoes: Are They Safe?', 'description' => 'Green or sprouted potatoes can contain higher glycoalkaloid levels. Learn the warning signs, why trimming may not be enough and when to discard them.' ),
		'/en/yellow-or-slimy-spinach-when-to-use-and-when-to-discard/' => array( 'title' => 'Yellow or Slimy Spinach: When to Discard It', 'description' => 'Is yellowing spinach still usable? Learn the difference between ageing leaves and spoilage, what slime or off smells mean, and when to discard it.' ),
		'/en/should-you-wash-meat-before-cooking-why-not/' => array( 'title' => 'Should You Wash Raw Meat Before Cooking?', 'description' => 'Washing raw meat can spread bacteria through splashes. Learn why food-safety authorities discourage it and how to handle and cook meat more safely.' ),
		'/en/verdura-vs-hortaliza-difference-which-foods-belong-to-each-group/' => array( 'title' => 'Verdura vs Hortaliza: What\'s the Difference?', 'description' => 'What do verdura and hortaliza mean in Spanish food terminology? Compare the categories, see examples and learn why the terms are not exact synonyms.' ),
		'/en/kuroge-washu-japanese-black-wagyu/' => array( 'title' => 'Kuroge Washu: Japanese Black Wagyu Explained', 'description' => 'Learn what Kuroge Washu means, how Japanese Black cattle fit within Wagyu breeds, and why breed, origin and grading are different things.' ),
		'/en/what-do-wagyu-cattle-eat-beer-massage-myths/' => array( 'title' => 'Wagyu Cattle: Beer and Massage Myths', 'description' => 'Do Wagyu cattle really drink beer or receive massages? Separate popular myths from everyday feeding and farming practices behind Wagyu beef.' ),
		'/en/iberian-sobrasada-what-it-is-how-made-what-to-look-for-when-buying/' => array( 'title' => 'Iberian Sobrasada: How It Is Made', 'description' => 'Discover Iberian sobrasada, its ingredients and curing process, how it differs from other spreads, and what to check when reading the label.' ),
		'/en/can-you-eat-chorizo-salchichon-casing-natural-collagen-artificial/' => array( 'title' => 'Can You Eat Chorizo or Salchichón Casing?', 'description' => 'Learn the difference between natural, collagen and artificial casings on cured sausages, how labels help identify them and when to remove the skin.' ),
		'/en/lettuce-red-or-brown-edges-why-it-happens-and-when-to-discard/' => array( 'title' => 'Brown or Red Lettuce Edges: Safe to Eat?', 'description' => 'Why do lettuce edges turn red or brown? Learn about oxidation and ageing, signs of spoilage and when a lettuce leaf is better discarded.' ),
		'/en/cheese-nutrition-calories-protein-fat-salt-carbohydrates/' => array( 'title' => 'Cheese Nutrition: Calories, Protein, Fat and Salt', 'description' => 'Compare cheese nutrition by type, including calories, protein, fat, carbohydrates and salt, and learn why water content changes the numbers.' ),
		'/en/what-is-cheese-affinage-maturation-humidity-rind-care-affineur/' => array( 'title' => 'Cheese Affinage: How Maturation Works', 'description' => 'What is cheese affinage? Learn how ageing, humidity, rind care and the affineur influence texture and flavour, with differences across cheese styles.' ),
		'/en/jar-vacuum-seal-how-to-check-closure/' => array( 'title' => 'How to Check a Jar\'s Vacuum Seal', 'description' => 'How can you tell whether a jar has a good vacuum seal? Learn what to check before opening, why lid movement matters and which warning signs mean discard.' ),
		'/en/root-leaf-fruit-flower-bulb-stem-vegetables/' => array( 'title' => 'Types of Vegetables: Root, Leaf, Stem and More', 'description' => 'Explore root, leaf, stem, bulb, flower and fruit vegetables with familiar examples, and learn how culinary categories differ from botany.' ),
		'/en/lean-beef-what-it-is-how-to-cook-tender/' => array( 'title' => 'Lean Beef: Best Cuts and How to Cook Them', 'description' => 'What counts as lean beef? Explore common cuts, how fat affects texture and practical cooking tips to keep lean meat tender and juicy.' ),
		'/en/how-long-cooked-meat-lasts-in-fridge-safe-storage/' => array( 'title' => 'How Long Does Cooked Meat Last in the Fridge?', 'description' => 'How long can cooked meat be refrigerated? Learn typical storage windows, cooling and container tips, plus safer ways to reheat or freeze leftovers.' ),
		'/en/how-much-meat-to-plan-per-person-by-cut-and-recipe/' => array( 'title' => 'How Much Meat per Person? Cuts and Portions', 'description' => 'Plan meat quantities per person with serving ranges for different cuts and recipes, including bone-in pieces, roasts and dishes with side portions.' ),
		'/en/how-many-litres-milk-to-make-1-kg-cheese-yield/' => array( 'title' => 'How Much Milk Makes 1 kg of Cheese?', 'description' => 'How many litres of milk are needed for 1 kg of cheese? Discover why yield differs for fresh and aged styles and how moisture affects the result.' ),
	);
}
function elmercado_gsc_en_second_target_20261012(): array {
	if ( is_admin() || ! is_singular( 'post' ) ) { return array(); }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$path = '/' . trim( $path, '/' ) . '/';
	$batch = elmercado_gsc_en_second_batch_20261012();
	return $batch[ $path ] ?? array();
}
function elmercado_gsc_en_second_title_20261012( $current ) {
	$target = elmercado_gsc_en_second_target_20261012();
	return isset( $target['title'] ) ? $target['title'] . ' | El Mercado de Origen' : $current;
}
function elmercado_gsc_en_second_description_20261012( $current ) {
	$target = elmercado_gsc_en_second_target_20261012();
	return $target['description'] ?? $current;
}
function elmercado_gsc_en_second_fallback_description_20261012(): string {
	$target = elmercado_gsc_en_second_target_20261012();
	return $target['description'] ?? '';
}
add_filter( 'pre_get_document_title', 'elmercado_gsc_en_second_title_20261012', 125 );
add_filter( 'wpseo_title', 'elmercado_gsc_en_second_title_20261012', 125 );
add_filter( 'rank_math/frontend/title', 'elmercado_gsc_en_second_title_20261012', 125 );
add_filter( 'aioseo_title', 'elmercado_gsc_en_second_title_20261012', 125 );
add_filter( 'seopress_titles_title', 'elmercado_gsc_en_second_title_20261012', 125 );
add_filter( 'wpseo_metadesc', 'elmercado_gsc_en_second_description_20261012', 125 );
add_filter( 'rank_math/frontend/description', 'elmercado_gsc_en_second_description_20261012', 125 );
add_filter( 'aioseo_description', 'elmercado_gsc_en_second_description_20261012', 125 );
add_filter( 'seopress_titles_desc', 'elmercado_gsc_en_second_description_20261012', 125 );
