<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

$categories_page = get_page_by_path( 'categorias', OBJECT, 'page' );
if ( $categories_page instanceof WP_Post ) {
	update_post_meta( $categories_page->ID, '_en_US_post_title', 'Categories' );
	update_post_meta( $categories_page->ID, '_en_US_post_name', 'categories' );
	update_post_meta( $categories_page->ID, '_en_US_published', '1' );
	update_post_meta( $categories_page->ID, '_en_US_ready', '1' );
}

$english = array(
	'1957' => <<<'HTML'
<p>The story of <strong>1957</strong> begins in that very year, when Gregorio first entered the world of olive growing. Since then, olive oil has passed from one generation to the next, becoming a way of life now shared by <strong>three generations</strong> of the same family.</p><p>That bond with the olive grove is also the starting point for their oils. To produce their <strong>extra virgin olive oils</strong>, they work with olives picked directly from the tree at their optimum stage of ripeness, ensuring that the fruit reaches the mill in the best possible condition.</p><p>From there begins a process in which every detail is designed to preserve the olive's natural qualities. After cleaning, the fruit is milled and the oil is obtained exclusively by mechanical means. Throughout the process it remains in contact with inert materials such as <strong>stainless steel</strong> to prevent any alteration.</p><p>One of the defining features of their production is temperature control. Their EVOOs are extracted <strong>cold</strong>, keeping the transformation process below <strong>19 °C</strong> in order to preserve as much as possible of the aromas, flavours and properties naturally present in the fruit.</p><p>The experience accumulated since 1957 has also been accompanied by recognition for the quality of their oils. These include first prizes in awards for the best EVOOs from the <strong>Guadalquivir Valley</strong>, in both intense green-fruit and ripe-fruit categories, as well as recognition of the career in the local olive-oil sector that Gregorio began.</p><p>Today, 1957 maintains the same bond between family, olive grove and mill that gave rise to the project. Three generations later, they continue to make their oils by combining experience passed down over decades with particular care for the raw material and for every stage of its transformation.</p>
HTML,
	'hidalgo-de-la-jara' => <<<'HTML'
<p>The livestock farmers behind <strong>Hidalgo de la Jara</strong> keep intact the tradition inherited from their ancestors in the breeding of pure Iberian pigs. Together with the distinctive microclimate of the <strong>Los Pedroches Valley</strong>, this allows them to produce hams and shoulders with an exceptional flavour and well-established reputation: a true benchmark for lovers of quality.</p><p>They are livestock farmers who have always looked beyond the breeding and fattening of their pigs. They created their own processing facilities in Los Pedroches to make their products themselves and built the <strong>Hidalgo de la Jara</strong> brand to bring them to market.</p><p>For five generations they have raised only <strong>100% Iberian pigs</strong> and transformed them in their own facilities in the heart of Los Pedroches, in <strong>Villanueva de Córdoba</strong>. The passage of time, with cold winters and hot summers, gives their hams and shoulders their characteristic intense, lingering flavour and a sweetness that wins over more consumers every day.</p><h3>How are Iberian pigs raised?</h3><p>Their 100% Iberian pigs are born outdoors and are nursed by their mothers until they are old enough to fend for themselves and begin feeding on the natural resources of the dehesa. Piglets born in autumn may enjoy their first acorns during weaning, although most of the acorns are reserved for the older animals that were born the previous year and reach the <em>montanera</em> fattening season. During this stage they grow from around 90–100 kg to 170–180 kg, feeding solely on acorns, grass and other natural resources of the dehesa such as mushrooms, roots and tubers.</p><h3>What do the pigs eat before the fattening stage?</h3><p>They feed on the resources of the dehesa. In summer, when those resources are scarcer, their diet is supplemented with natural feed made mainly from cereals and pulses. The livestock farmers themselves sow crops such as chickpeas, broad beans, vetch and oats so that, when summer arrives, the pigs can graze on them freely in the field without the crops having to be harvested first.</p><h3>Temperature, humidity and maturation time for hams and shoulders</h3><p>The production of their hams and shoulders takes place in several stages. The first is salting, carried out at a very low temperature and high humidity. Depending on the weight of each piece, this stage lasts around two weeks. After settling in temperature- and humidity-controlled chambers, the pieces move to natural cellars, where temperatures range from around 18–20 °C in summer to 10–12 °C in winter. This slow curing process ensures a long, optimal maturation of more than <strong>30 months</strong>, allowing aromas to develop that an accelerated process could never achieve.</p>
HTML,
);

$profiles = $wpdb->get_results(
	"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key='wcfmmp_profile_settings' ORDER BY user_id",
	ARRAY_A
);
$report = array(
	'categories_page_id' => $categories_page instanceof WP_Post ? $categories_page->ID : 0,
	'categories_en_slug' => $categories_page instanceof WP_Post ? get_post_meta( $categories_page->ID, '_en_US_post_name', true ) : '',
	'stores' => array(),
);

foreach ( (array) $profiles as $row ) {
	$settings = maybe_unserialize( $row['meta_value'] ?? '' );
	if ( ! is_array( $settings ) ) { continue; }
	$user_id = (int) $row['user_id'];
	$slug    = sanitize_title( (string) ( $settings['store_slug'] ?? '' ) );
	if ( ! $slug ) { continue; }
	$source = (string) get_user_meta( $user_id, '_store_description', true );
	if ( '' === trim( wp_strip_all_tags( $source ) ) ) {
		$source = (string) ( $settings['shop_description'] ?? '' );
	}
	if ( isset( $english[ $slug ] ) ) {
		update_user_meta( $user_id, '_mdo_en_store_description', $english[ $slug ] );
	}
	$en = (string) get_user_meta( $user_id, '_mdo_en_store_description', true );
	$report['stores'][] = array(
		'user_id' => $user_id,
		'store_name' => (string) ( $settings['store_name'] ?? '' ),
		'store_slug' => $slug,
		'spanish_description_chars' => mb_strlen( trim( wp_strip_all_tags( $source ) ) ),
		'english_description_chars' => mb_strlen( trim( wp_strip_all_tags( $en ) ) ),
		'english_seeded' => isset( $english[ $slug ] ),
		'published_products' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d", $user_id ) ),
	);
}

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
