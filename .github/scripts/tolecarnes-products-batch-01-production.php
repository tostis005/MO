<?php
/**
 * Production update: first five Tolecarnes product descriptions (ES + EN).
 * Scope: post_excerpt + post_content only.
 * Safety: preflight all products/translations/vendor first; backup originals; rollback on write failure.
 */

if (!defined('ABSPATH')) {
    exit("This script must run inside WordPress.\n");
}

global $wpdb;

function mo_fail($message) {
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::error($message);
    }
    throw new Exception($message);
}

function mo_lang_code($post_id) {
    $details = apply_filters('wpml_post_language_details', null, (int) $post_id);
    if (is_array($details) && !empty($details['language_code'])) {
        return $details['language_code'];
    }
    return null;
}

function mo_vendor_name($post) {
    if (!$post || empty($post->post_author)) {
        return '';
    }
    $user = get_userdata((int) $post->post_author);
    return $user ? (string) $user->display_name : '';
}

function mo_find_es_product($key, $spec) {
    global $wpdb;

    $matches = [];

    if (!empty($spec['slug'])) {
        $p = get_page_by_path($spec['slug'], OBJECT, 'product');
        if ($p && $p->post_status !== 'trash') {
            $matches[(int)$p->ID] = $p;
        }
    }

    if (!$matches && !empty($spec['title_terms'])) {
        $where = [
            "post_type = 'product'",
            "post_status NOT IN ('trash','auto-draft')",
        ];
        $params = [];
        foreach ($spec['title_terms'] as $term) {
            $where[] = 'post_title LIKE %s';
            $params[] = '%' . $wpdb->esc_like($term) . '%';
        }
        $sql = "SELECT * FROM {$wpdb->posts} WHERE " . implode(' AND ', $where);
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        foreach ($rows as $row) {
            $lang = mo_lang_code($row->ID);
            if ($lang && $lang !== 'es') {
                continue;
            }
            $matches[(int)$row->ID] = $row;
        }
    }

    if (count($matches) !== 1) {
        $titles = array_map(fn($p) => "{$p->ID}: {$p->post_title}", array_values($matches));
        mo_fail("Preflight failed for {$key}: expected exactly one Spanish product, found " . count($matches) . ". " . implode(' | ', $titles));
    }

    $post = array_values($matches)[0];
    $lang = mo_lang_code($post->ID);
    if ($lang && $lang !== 'es') {
        mo_fail("Preflight failed for {$key}: product {$post->ID} is language {$lang}, expected es.");
    }

    $vendor = mo_vendor_name($post);
    if ($vendor && stripos($vendor, 'tolecarnes') === false) {
        mo_fail("Preflight failed for {$key}: product {$post->ID} vendor/author is '{$vendor}', expected Tolecarnes.");
    }

    return $post;
}

$producer_es = <<<'HTML'
<h2>Sobre Tolecarnes</h2>
<p>Tolecarnes es una ganadería familiar de Menasalbas, situada a los pies de los Montes de Toledo, con tres generaciones dedicadas a la cría de ganado vacuno.</p>
<p>Sus terneras se crían desde el nacimiento en la propia ganadería y pastan libremente durante buena parte del año en su dehesa de los Montes de Toledo. La zona, especialmente húmeda en la cara norte de los Montes, permite disponer de pastos naturales durante gran parte del año y mantener a los animales en movimiento en un entorno abierto.</p>
<p>Durante el invierno, cuando el pasto natural es menos abundante, su alimentación se complementa con pasto recogido en los meses de mayor disponibilidad y con piensos elaborados en la Cooperativa Valle de Mena, de la que Tolecarnes forma parte. Estos piensos se producen a partir de ingredientes de origen vegetal, lo que permite a la ganadería tener un mayor control sobre la alimentación de sus animales.</p>
<p>Tolecarnes controla el proceso de cría desde el nacimiento de las terneras hasta la comercialización de la carne, manteniendo un modelo de ganadería tradicional muy vinculado a los Montes de Toledo.</p>
HTML;

$producer_en = <<<'HTML'
<h2>About Tolecarnes</h2>
<p>Tolecarnes is a family-run cattle farm based in Menasalbas, at the foot of the Montes de Toledo, with three generations of experience raising cattle.</p>
<p>Its calves are raised by the farm from birth and spend much of the year grazing freely on its dehesa in the Montes de Toledo. The wetter conditions on the northern side of the mountains provide natural pasture for a large part of the year, allowing the cattle to remain outdoors and active in an open environment.</p>
<p>During winter, when fresh pasture is less abundant, their diet is supplemented with forage collected during the more productive months and with feed produced by Cooperativa Valle de Mena, of which Tolecarnes is a member. This feed is made from plant-based ingredients, giving the farm greater control over the animals' diet.</p>
<p>Tolecarnes oversees the farming process from the birth of its calves through to the sale of the meat, maintaining a traditional approach to cattle farming closely connected to the Montes de Toledo.</p>
HTML;

$products = [
    'carne_picada' => [
        'slug' => 'carne-picada-extra',
        'title_terms' => ['Carne picada', 'extra'],
        'es_excerpt' => '<p>Carne picada elaborada con 100% ternera y sin aditivos. Un básico muy versátil para preparar desde unas albóndigas o unas hamburguesas caseras hasta rellenos, salsas, platos de pasta o recetas de toda la vida.</p>',
        'es_content' => <<<'HTML'
<h2>Una carne para el día a día</h2>
<p>La carne picada es una de esas opciones que permiten resolver muchas comidas diferentes partiendo de un mismo producto. Puede utilizarse sola, mezclarse con otros ingredientes o formar parte de elaboraciones más completas.</p>
<p>En este caso se utiliza carne de ternera procedente de partes jugosas del animal y no se añaden aditivos.</p>
<h2>Cómo aprovecharla</h2>
<p>Puedes utilizarla para preparar albóndigas, hamburguesas caseras, filetes rusos, lasañas, canelones, empanadas, verduras rellenas o salsas con carne.</p>
<p>También es una buena opción para saltearla con verduras, incorporarla a un arroz o preparar platos sencillos de pasta.</p>
<p>Al tratarse de carne picada, debe cocinarse completamente antes de consumirla. Si está congelada, lo más recomendable es dejar que se descongele poco a poco en el frigorífico.</p>
HTML,
        'en_excerpt' => '<p>Ground beef made with 100% beef and no added additives. A versatile everyday staple that works equally well for meatballs, homemade burgers, fillings, sauces, pasta dishes and traditional home cooking.</p>',
        'en_content' => <<<'HTML'
<h2>An everyday kitchen staple</h2>
<p>Ground beef is one of those ingredients that can be used in many different ways. It can be cooked on its own, combined with other ingredients or used as the base for more complete dishes.</p>
<p>This ground beef is made from juicy cuts of beef without added additives.</p>
<h2>How to use it</h2>
<p>Use it for meatballs, homemade burgers, lasagne, cannelloni, pies, stuffed vegetables or meat sauces.</p>
<p>It also works well simply sautéed with vegetables, added to rice dishes or used in easy pasta recipes.</p>
<p>As with all ground meat, it should be thoroughly cooked before eating. If frozen, the best option is to let it thaw gradually in the refrigerator.</p>
HTML,
        'es_faq' => <<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿La carne picada lleva aditivos?</h3>
<p>No. Está elaborada con 100% carne de ternera y sin aditivos añadidos.</p>
<h3>¿Qué puedo preparar con ella?</h3>
<p>Puedes utilizarla para hamburguesas, albóndigas, rellenos, pasta, arroces, empanadas y muchas otras preparaciones.</p>
<h3>¿Cómo descongelarla?</h3>
<p>Siempre que sea posible, lo mejor es pasarla del congelador al frigorífico con suficiente antelación para que se descongele de forma progresiva.</p>
HTML,
        'en_faq' => <<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Does this ground beef contain additives?</h3>
<p>No. It is made with 100% beef and no added additives.</p>
<h3>What can I use it for?</h3>
<p>It is suitable for burgers, meatballs, fillings, pasta, rice dishes, pies and many other everyday meals.</p>
<h3>How should I defrost it?</h3>
<p>Whenever possible, move it from the freezer to the refrigerator well in advance and allow it to thaw gradually.</p>
HTML,
    ],

    'burger_mixta' => [
        'slug' => null,
        'title_terms' => ['mixt', 'gluten'],
        'es_excerpt' => '<p>Hamburguesas elaboradas con una mezcla al 50% de carne de ternera y carne de cerdo de la zona. No contienen gluten y ofrecen una alternativa a la hamburguesa elaborada únicamente con vacuno, con un sabor más suave y una textura jugosa.</p>',
        'es_content' => <<<'HTML'
<h2>Una hamburguesa diferente</h2>
<p>La combinación de ternera y cerdo aporta un perfil distinto al de una hamburguesa 100% de vacuno. La carne de cerdo contribuye a que el resultado sea tierno y jugoso, mientras que la ternera mantiene el carácter de la carne de vacuno.</p>
<p>Son una opción sencilla para preparar una comida rápida en casa sin necesidad de añadir muchos ingredientes.</p>
<h2>Cómo prepararlas</h2>
<p>Puedes cocinarlas en sartén, plancha o barbacoa.</p>
<p>En sartén o plancha, utiliza una superficie bien caliente y cocínalas por ambos lados hasta que el interior esté completamente hecho. En barbacoa conviene controlar el fuego para que se cocinen por dentro sin quemarse por fuera.</p>
<p>Puedes servirlas en pan de hamburguesa o acompañarlas directamente con verduras, patatas, ensalada u otra guarnición.</p>
HTML,
        'en_excerpt' => '<p>Burgers made with an equal blend of beef and locally sourced pork. They are gluten-free and offer a slightly different profile from a 100% beef burger, with a milder flavour and a juicy texture.</p>',
        'en_content' => <<<'HTML'
<h2>A different style of burger</h2>
<p>Combining beef and pork creates a different result from an all-beef burger. The pork helps provide tenderness and juiciness, while the beef keeps the characteristic flavour of red meat.</p>
<p>They are an easy option for a quick meal at home and require very little preparation.</p>
<h2>How to cook them</h2>
<p>They can be cooked in a frying pan, on a griddle or on the barbecue.</p>
<p>Use a properly heated cooking surface and cook both sides until the centre is thoroughly cooked. On a barbecue, moderate the heat so that the outside does not burn before the centre is done.</p>
<p>Serve them in a burger bun or simply alongside vegetables, potatoes, salad or another side dish.</p>
HTML,
        'es_faq' => <<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Llevan gluten?</h3>
<p>No. Estas hamburguesas se elaboran sin gluten.</p>
<h3>¿Son 100% de ternera?</h3>
<p>No. Están elaboradas con un 50% de carne de ternera y un 50% de carne de cerdo de la zona.</p>
<h3>¿Se pueden hacer a la barbacoa?</h3>
<p>Sí. Pueden cocinarse a la barbacoa, en sartén o a la plancha, asegurándose siempre de que el interior quede completamente cocinado.</p>
HTML,
        'en_faq' => <<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Are these burgers gluten-free?</h3>
<p>Yes. They are made without gluten.</p>
<h3>Are they 100% beef?</h3>
<p>No. They contain 50% beef and 50% locally sourced pork.</p>
<h3>Can I cook them on a barbecue?</h3>
<p>Yes. They can be cooked on a barbecue, in a frying pan or on a griddle, always making sure that the centre is thoroughly cooked.</p>
HTML,
    ],

    'filetes_primera' => [
        'slug' => null,
        'title_terms' => ['Filetes', 'primera'],
        'es_excerpt' => '<p>Filetes de ternera procedentes de piezas especialmente adecuadas para una cocción rápida, como la babilla o la cadera. Son cortes jugosos que funcionan muy bien a la plancha, a la brasa o en preparaciones sencillas donde la carne es la protagonista.</p>',
        'es_content' => <<<'HTML'
<h2>Para plancha o brasa</h2>
<p>La babilla y la cadera son dos piezas muy habituales cuando buscamos filetes de ternera para cocinar en pocos minutos.</p>
<p>No necesitan preparaciones complicadas. Una buena temperatura de cocción y controlar el tiempo en el fuego suele ser suficiente para disfrutar de la carne sin ocultar su sabor con demasiados ingredientes.</p>
<p>También pueden utilizarse en bocadillos, acompañarse con verduras o patatas o cortarse después de cocinar para incorporarlos a ensaladas y otros platos.</p>
<h2>Cómo prepararlos</h2>
<p>Antes de cocinar, seca ligeramente la superficie si tiene exceso de humedad.</p>
<p>Para hacerlos a la plancha o en sartén, espera a que la superficie esté caliente antes de añadir la carne. El tiempo dependerá del grosor de cada filete y del punto que prefieras.</p>
<p>Si los preparas a la brasa, evita una exposición excesiva al fuego para que no pierdan innecesariamente jugosidad.</p>
HTML,
        'en_excerpt' => '<p>Beef steaks cut from tender pieces such as the knuckle or rump, both well suited to quick cooking. They are juicy cuts that work particularly well on a griddle, in a frying pan or over charcoal when you want to keep the preparation simple and let the meat take centre stage.</p>',
        'en_content' => <<<'HTML'
<h2>Made for quick cooking</h2>
<p>Knuckle and rump are commonly used for beef steaks that only need a short time over the heat.</p>
<p>There is no need for a complicated preparation. A properly heated pan or grill and careful control of the cooking time are usually enough.</p>
<p>The steaks can also be used in sandwiches, served with vegetables or potatoes, or sliced after cooking and added to salads and other dishes.</p>
<h2>How to cook them</h2>
<p>Pat the surface dry before cooking if there is excess moisture.</p>
<p>For pan or griddle cooking, wait until the surface is hot before adding the meat. Cooking time will depend on the thickness of the steak and how well done you prefer it.</p>
<p>When grilling over charcoal, avoid leaving the steaks over intense heat for longer than necessary so they retain their juiciness.</p>
HTML,
        'es_faq' => <<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿De qué parte de la ternera salen estos filetes?</h3>
<p>Los filetes de primera pueden proceder de piezas como la babilla o la cadera.</p>
<h3>¿Cuál es la mejor forma de cocinarlos?</h3>
<p>Son especialmente adecuados para plancha, sartén o brasa, utilizando una cocción relativamente rápida.</p>
<h3>¿Hay que descongelarlos antes de cocinarlos?</h3>
<p>Si están congelados, es preferible descongelarlos previamente en el frigorífico para conseguir una cocción más uniforme.</p>
HTML,
        'en_faq' => <<<'HTML'
<h2>Frequently asked questions</h2>
<h3>Which cuts are used for these steaks?</h3>
<p>They may be cut from pieces such as the knuckle or rump.</p>
<h3>What is the best way to cook them?</h3>
<p>They are particularly suitable for quick cooking in a frying pan, on a griddle or over charcoal.</p>
<h3>Should they be defrosted before cooking?</h3>
<p>If frozen, it is best to thaw them in the refrigerator first for more even cooking.</p>
HTML,
    ],

    'ragu' => [
        'slug' => null,
        'title_terms' => ['Magro', 'rag'],
        'es_excerpt' => '<p>Carne de ternera cortada a mano y pensada especialmente para preparaciones en las que interesa una carne jugosa y con tiempo para cocinarse. Procede principalmente de piezas del cuarto delantero, como la aguja, y presenta grasa infiltrada que aporta sabor y jugosidad durante la cocción.</p>',
        'es_content' => <<<'HTML'
<h2>Una carne para guisos y cocciones tranquilas</h2>
<p>El ragú es una buena elección cuando buscamos una carne que forme parte del plato y se cocine junto con el resto de ingredientes.</p>
<p>En guisos y estofados, el tiempo y la humedad permiten que la carne vaya adquiriendo una textura más tierna y que sus jugos se integren con el caldo o la salsa.</p>
<p>Es un corte especialmente cómodo para platos de cuchara y preparaciones que pueden dejarse cocinar sin prisas.</p>
<h2>Cómo prepararlo</h2>
<p>Puedes comenzar dorando la carne brevemente en la olla antes de incorporar verduras, caldo, vino o los ingredientes que formen parte del guiso.</p>
<p>Después, la clave es continuar con una cocción más suave hasta que la carne alcance la textura que buscas.</p>
<p>También puede prepararse en olla a presión si quieres reducir el tiempo de cocción.</p>
HTML,
        'en_excerpt' => '<p>Hand-cut beef designed for dishes where the meat has time to cook slowly and develop a tender, juicy texture. It is mainly taken from the forequarter, including cuts such as the chuck, and contains intramuscular fat that contributes flavour and juiciness during cooking.</p>',
        'en_content' => <<<'HTML'
<h2>Ideal for stews and slow cooking</h2>
<p>Diced beef for ragout is a good choice when the meat is intended to cook together with the other ingredients in the dish.</p>
<p>In stews and casseroles, time and moisture gradually soften the meat while its juices become part of the broth or sauce.</p>
<p>It is particularly well suited to comforting one-pot dishes and meals that benefit from unhurried cooking.</p>
<h2>How to cook it</h2>
<p>Start by browning the beef briefly in the pot before adding vegetables, stock, wine or the other ingredients in your dish.</p>
<p>Then lower the heat and allow it to cook until the meat reaches the tenderness you are looking for.</p>
<p>A pressure cooker can also be used when you want to shorten the cooking time.</p>
HTML,
        'es_faq' => <<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué es el ragú de ternera?</h3>
<p>Es carne de ternera cortada en porciones para cocinar principalmente en guisos, estofados y otras preparaciones de cocción prolongada.</p>
<h3>¿De qué parte procede?</h3>
<p>Este ragú se obtiene principalmente de piezas del cuarto delantero de la ternera, entre ellas la aguja.</p>
<h3>¿Se puede preparar en olla a presión?</h3>
<p>Sí. La olla a presión es una buena alternativa cuando quieres conseguir una carne tierna reduciendo el tiempo de cocción.</p>
HTML,
        'en_faq' => <<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What is beef ragout?</h3>
<p>It is beef cut into pieces for dishes such as stews, casseroles and other slow-cooked preparations.</p>
<h3>Which part of the animal does it come from?</h3>
<p>This product is mainly taken from forequarter cuts, including the chuck.</p>
<h3>Can I cook it in a pressure cooker?</h3>
<p>Yes. A pressure cooker is a useful option when you want tender beef with a shorter cooking time.</p>
HTML,
    ],

    'entrana' => [
        'slug' => null,
        'title_terms' => ['Entraña'],
        'es_excerpt' => '<p>La entraña es un corte fino y muy sabroso situado en la parte interior del costillar. Tiene un sabor más intenso que otros cortes de ternera y una presencia apreciable de grasa infiltrada, dos características que la hacen especialmente interesante para preparar a la parrilla o en la barbacoa.</p>',
        'es_content' => <<<'HTML'
<h2>Un corte con mucho sabor</h2>
<p>La entraña se distingue fácilmente de otros cortes por su forma, por el tono algo más oscuro de la carne y por la intensidad de su sabor.</p>
<p>Procede de la zona interior de las costillas y de cada animal se obtienen pocas piezas, por lo que tradicionalmente ha sido un corte menos habitual que otros filetes de ternera.</p>
<p>Es especialmente popular en la cocina a la parrilla, donde una preparación sencilla permite aprovechar muy bien sus características.</p>
<h2>Cómo prepararla</h2>
<p>La entraña funciona especialmente bien en barbacoa, parrilla o una plancha muy caliente.</p>
<p>Al ser una pieza relativamente fina, conviene controlar el tiempo para no cocinarla más de lo necesario. Una vez hecha, dejarla reposar brevemente y cortarla en sentido contrario a las fibras ayuda a conseguir una textura más agradable al comerla.</p>
<p>No necesita demasiados acompañamientos: sal, una buena cocción y una guarnición sencilla son suficientes.</p>
HTML,
        'en_excerpt' => '<p>Entraña is a thin, flavourful cut taken from the inside of the rib area. It has a more pronounced flavour than many other beef cuts and a noticeable amount of intramuscular fat, making it particularly well suited to grilling and barbecuing.</p>',
        'en_content' => <<<'HTML'
<h2>A cut with plenty of flavour</h2>
<p>Entraña stands apart from many other steaks because of its shape, slightly darker colour and more intense beef flavour.</p>
<p>It comes from the inner rib area, and only a small number of these pieces are obtained from each animal, which helps explain why it has traditionally been less common than standard beef steaks.</p>
<p>It is especially popular for grilling, where a simple preparation allows the character of the cut to come through.</p>
<h2>How to cook it</h2>
<p>Entraña works particularly well on a barbecue, grill or very hot griddle.</p>
<p>Because it is a relatively thin cut, it is worth keeping a close eye on cooking time so it does not stay over the heat longer than necessary. After cooking, allow it to rest briefly and slice it across the grain for a more tender bite.</p>
<p>It needs very little else: salt, careful cooking and a simple side dish are enough.</p>
HTML,
        'es_faq' => <<<'HTML'
<h2>Preguntas frecuentes</h2>
<h3>¿Qué parte de la ternera es la entraña?</h3>
<p>Es un corte situado en la zona interior del costillar.</p>
<h3>¿A qué sabe la entraña?</h3>
<p>Tiene un sabor más intenso que muchos de los filetes habituales de ternera y una textura muy característica.</p>
<h3>¿Cómo queda mejor?</h3>
<p>Es un corte especialmente apropiado para parrilla, barbacoa o plancha a temperatura alta.</p>
<h3>¿Cómo conviene cortarla después de cocinarla?</h3>
<p>Cortarla en sentido contrario a las fibras facilita la masticación y ayuda a conseguir una sensación más tierna en boca.</p>
HTML,
        'en_faq' => <<<'HTML'
<h2>Frequently asked questions</h2>
<h3>What part of the animal is entraña?</h3>
<p>It is a cut taken from the inner rib area.</p>
<h3>What does entraña taste like?</h3>
<p>It has a more pronounced beef flavour than many standard steaks and a distinctive texture.</p>
<h3>What is the best way to cook it?</h3>
<p>It is particularly well suited to a barbecue, grill or very hot griddle.</p>
<h3>How should it be sliced after cooking?</h3>
<p>Slice it across the grain to shorten the muscle fibres and make it easier to chew.</p>
HTML,
    ],
];

// PRE-FLIGHT: resolve all ES products, Tolecarnes vendor, and EN translations before any write.
$resolved = [];
foreach ($products as $key => $spec) {
    $es = mo_find_es_product($key, $spec);
    $en_id = (int) apply_filters('wpml_object_id', (int)$es->ID, 'product', false, 'en');
    if (!$en_id || $en_id === (int)$es->ID) {
        mo_fail("Preflight failed for {$key}: English translation not found for ES product {$es->ID} ({$es->post_title}).");
    }
    $en = get_post($en_id);
    if (!$en || $en->post_type !== 'product' || $en->post_status === 'trash') {
        mo_fail("Preflight failed for {$key}: invalid English product ID {$en_id}.");
    }
    $lang = mo_lang_code($en_id);
    if ($lang && $lang !== 'en') {
        mo_fail("Preflight failed for {$key}: translation {$en_id} language is {$lang}, expected en.");
    }
    $resolved[$key] = ['es' => $es, 'en' => $en];
    echo "PRECHECK {$key}: ES {$es->ID} '{$es->post_title}' -> EN {$en->ID} '{$en->post_title}'\n";
}

// Backup original content once.
$backup_key = 'mo_tolecarnes_batch01_backup_20260831';
$existing_backup = get_option($backup_key, null);
if ($existing_backup === null) {
    $backup = [
        'created_at' => current_time('mysql'),
        'products' => [],
    ];
    foreach ($resolved as $key => $pair) {
        $backup['products'][$key] = [
            'es' => [
                'ID' => (int)$pair['es']->ID,
                'title' => $pair['es']->post_title,
                'post_excerpt' => $pair['es']->post_excerpt,
                'post_content' => $pair['es']->post_content,
            ],
            'en' => [
                'ID' => (int)$pair['en']->ID,
                'title' => $pair['en']->post_title,
                'post_excerpt' => $pair['en']->post_excerpt,
                'post_content' => $pair['en']->post_content,
            ],
        ];
    }
    add_option($backup_key, $backup, '', false);
    echo "BACKUP created: {$backup_key}\n";
} else {
    echo "BACKUP already exists: {$backup_key} (preserved)\n";
}

try {
    foreach ($products as $key => $spec) {
        $es = $resolved[$key]['es'];
        $en = $resolved[$key]['en'];

        $es_content = trim($spec['es_content']) . "\n" . trim($producer_es) . "\n" . trim($spec['es_faq']);
        $en_content = trim($spec['en_content']) . "\n" . trim($producer_en) . "\n" . trim($spec['en_faq']);

        $r1 = wp_update_post(wp_slash([
            'ID' => (int)$es->ID,
            'post_excerpt' => $spec['es_excerpt'],
            'post_content' => $es_content,
        ]), true);
        if (is_wp_error($r1)) {
            throw new Exception("Failed ES update {$key}: " . $r1->get_error_message());
        }

        $r2 = wp_update_post(wp_slash([
            'ID' => (int)$en->ID,
            'post_excerpt' => $spec['en_excerpt'],
            'post_content' => $en_content,
        ]), true);
        if (is_wp_error($r2)) {
            throw new Exception("Failed EN update {$key}: " . $r2->get_error_message());
        }

        clean_post_cache((int)$es->ID);
        clean_post_cache((int)$en->ID);
        echo "UPDATED {$key}: ES {$es->ID}, EN {$en->ID}\n";
    }
} catch (Throwable $e) {
    echo "WRITE FAILURE: {$e->getMessage()}\nROLLING BACK current batch...\n";
    $backup = get_option($backup_key);
    if (is_array($backup) && !empty($backup['products'])) {
        foreach ($backup['products'] as $key => $langs) {
            foreach (['es','en'] as $lang) {
                if (empty($langs[$lang]['ID'])) continue;
                wp_update_post(wp_slash([
                    'ID' => (int)$langs[$lang]['ID'],
                    'post_excerpt' => $langs[$lang]['post_excerpt'],
                    'post_content' => $langs[$lang]['post_content'],
                ]));
                clean_post_cache((int)$langs[$lang]['ID']);
            }
        }
    }
    mo_fail("Batch rolled back because an update failed: {$e->getMessage()}");
}

// Post-write verification.
foreach ($products as $key => $spec) {
    $es = get_post((int)$resolved[$key]['es']->ID);
    $en = get_post((int)$resolved[$key]['en']->ID);
    if (trim($es->post_excerpt) !== trim(wp_unslash($spec['es_excerpt']))) {
        mo_fail("Verification failed for {$key} ES excerpt.");
    }
    if (strpos($es->post_content, '<h2>Sobre Tolecarnes</h2>') === false) {
        mo_fail("Verification failed for {$key} ES producer section.");
    }
    if (trim($en->post_excerpt) !== trim(wp_unslash($spec['en_excerpt']))) {
        mo_fail("Verification failed for {$key} EN excerpt.");
    }
    if (strpos($en->post_content, '<h2>About Tolecarnes</h2>') === false) {
        mo_fail("Verification failed for {$key} EN producer section.");
    }
    echo "VERIFIED {$key}: ES + EN\n";
}

echo "SUCCESS: 5 Tolecarnes products updated in Spanish and English.\n";
