<?php
/**
 * Verified SEO content enrichment, 2026-08-21.
 * Sources were reviewed against producer/original product information.
 * Safety: preserve product status, price, stock, vendor state and English publication flags.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

function emdo_seo_len( $html ): int {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $html ) ) ) );
    return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
}

function emdo_seo_backup_product( int $id ): void {
    $key = '_emdo_seo_content_backup_20260821';
    if ( get_post_meta( $id, $key, true ) !== '' ) return;
    $p = get_post( $id );
    if ( ! $p ) return;
    add_post_meta( $id, $key, array(
        'post_content' => (string) $p->post_content,
        'post_excerpt' => (string) $p->post_excerpt,
        'en_content' => (string) get_post_meta( $id, '_en_US_post_content', true ),
        'en_excerpt' => (string) get_post_meta( $id, '_en_US_post_excerpt', true ),
        'en_ready' => (string) get_post_meta( $id, '_en_US_ready', true ),
        'en_published' => (string) get_post_meta( $id, '_en_US_published', true ),
        'status' => (string) $p->post_status,
    ), true );
}

function emdo_seo_apply_pair( int $id, string $es, string $en, array &$log ): void {
    $p = get_post( $id );
    if ( ! $p || $p->post_type !== 'product' ) throw new RuntimeException( "Missing product {$id}" );
    $status = $p->post_status;
    $author = (int) $p->post_author;
    $price = get_post_meta( $id, '_price', true );
    $regular = get_post_meta( $id, '_regular_price', true );
    $stock = get_post_meta( $id, '_stock', true );
    $en_ready = (string) get_post_meta( $id, '_en_US_ready', true );
    $en_published = (string) get_post_meta( $id, '_en_US_published', true );
    emdo_seo_backup_product( $id );

    $res = wp_update_post( array( 'ID' => $id, 'post_content' => wp_kses_post( $es ) ), true );
    if ( is_wp_error( $res ) ) throw new RuntimeException( "Update failed {$id}: " . $res->get_error_message() );
    update_post_meta( $id, '_en_US_post_content', wp_kses_post( $en ) );
    update_post_meta( $id, '_emdo_seo_content_reviewed_20260821', '1' );

    $after = get_post( $id );
    if ( ! $after || $after->post_status !== $status || (int) $after->post_author !== $author ) throw new RuntimeException( "State changed {$id}" );
    if ( get_post_meta( $id, '_price', true ) != $price || get_post_meta( $id, '_regular_price', true ) != $regular || get_post_meta( $id, '_stock', true ) != $stock ) throw new RuntimeException( "Commerce data changed {$id}" );
    if ( (string) get_post_meta( $id, '_en_US_ready', true ) !== $en_ready || (string) get_post_meta( $id, '_en_US_published', true ) !== $en_published ) throw new RuntimeException( "English flags changed {$id}" );
    if ( emdo_seo_len( $after->post_content ) < 120 || emdo_seo_len( get_post_meta( $id, '_en_US_post_content', true ) ) < 120 ) throw new RuntimeException( "Content still short {$id}" );
    $log[] = array( 'id'=>$id, 'title'=>$after->post_title, 'status'=>$status, 'es_len'=>emdo_seo_len($after->post_content), 'en_len'=>emdo_seo_len(get_post_meta($id,'_en_US_post_content',true)), 'en_ready'=>$en_ready, 'en_published'=>$en_published );
}

$log = array();

// El Catedrático: three accessory pages where the original producer text was too short.
$catedratico = array(
    12566 => array(
        'Tabla jamonera modelo Góndola personalizada por El Catedrático e incluye cuchillo. Está pensada para sujetar la pieza de jamón durante el corte y disponer de una base estable en casa. El conjunto reúne el soporte y el cuchillo en un mismo producto.',
        'Gondola-model ham holder customised by El Catedrático and supplied with a knife. It is designed to hold a ham securely while slicing and provide a stable base for use at home. The set combines the holder and knife in one product.'
    ),
    12575 => array(
        'Tabla jamonera modelo Bellota 3 con sistema giratorio, personalizada con el logotipo de El Catedrático e incluye cuchillo. El mecanismo permite orientar la pieza durante el corte con mayor comodidad y mantenerla sujeta sobre una base específica para jamón.',
        'Bellota 3 ham holder with a rotating system, customised with the El Catedrático logo and supplied with a knife. The rotating mechanism makes it easier to reposition the ham while slicing and keeps the piece supported on a dedicated ham stand.'
    ),
    12587 => array(
        'Blíster de El Catedrático compuesto por cuchillo y afilador. Reúne en un mismo pack las dos herramientas básicas para cortar jamón y mantener el filo del cuchillo, de modo que puedan guardarse y utilizarse conjuntamente cuando sea necesario.',
        'El Catedrático blister pack containing a knife and sharpener. It brings together the two basic tools used for slicing ham and maintaining the knife edge, so they can be stored and used together whenever needed.'
    ),
);
foreach ( $catedratico as $id => $pair ) emdo_seo_apply_pair( (int)$id, $pair[0], $pair[1], $log );

// Hidalgo de la Jara: the long description was blank while a reviewed short description already existed.
// Reuse that existing factual copy rather than inventing new product information.
$hidalgo_copy_excerpt = array( 1370, 1375, 1380, 1382, 4188 );
foreach ( $hidalgo_copy_excerpt as $id ) {
    $p = get_post( $id );
    if ( ! $p ) throw new RuntimeException( "Missing Hidalgo product {$id}" );
    $es = trim( (string) $p->post_excerpt );
    $en = trim( (string) get_post_meta( $id, '_en_US_post_excerpt', true ) );
    if ( emdo_seo_len( $es ) < 120 || emdo_seo_len( $en ) < 120 ) throw new RuntimeException( "Hidalgo source excerpt too short {$id}" );
    emdo_seo_apply_pair( $id, $es, $en, $log );
}

// Tolecarnes: concise, factual paraphrases based on the producer's current product catalogue/despiece.
$tolecarnes = array(
11058 => array(
'Carne picada extra de ternera 100% natural, sin aditivos. Se obtiene de cortes jugosos y resulta adecuada para preparar albóndigas, filetes rusos o pasteles de carne. Se presenta en paquetes de 1 kg envasados al vacío.',
'Extra minced beef made from 100% natural veal with no additives. It comes from juicy cuts and is suitable for meatballs, Russian-style patties or meat pies. Supplied in 1 kg vacuum-packed portions.'),
11064 => array(
'Filetes de primera procedentes de cortes como babilla o cadera, piezas jugosas adecuadas para plancha, brasa o asado. Se preparan en bandejas de 1 kg y se presentan envasados al vacío para facilitar su conservación y manejo.',
'First-class veal steaks cut from joints such as knuckle or rump, juicy cuts suited to griddling, grilling or roasting. They are prepared in 1 kg trays and vacuum packed for easier handling and storage.'),
11067 => array(
'Chuletón de lomo alto de ternera, un corte con infiltración de grasa que aporta jugosidad y un sabor marcado. Es una pieza indicada para plancha o brasa. Se prepara envasada al vacío y, de forma orientativa, suelen obtenerse entre dos y tres chuletones por kilogramo.',
'High-loin veal steak with intramuscular fat that contributes juiciness and a pronounced flavour. It is well suited to a griddle or barbecue. Supplied vacuum packed; as a guide, a kilogram usually contains around two to three steaks.'),
11073 => array(
'Magro o ragú de ternera elaborado con cortes jugosos de los cuartos delanteros, especialmente piezas como aguja o lardeo. Está pensado para guisos y preparaciones de cocción lenta. Se presenta en bandejas de 1 kg envasadas al vacío.',
'Lean veal or ragout prepared from juicy forequarter cuts, particularly pieces such as chuck. It is intended for stews and slower-cooked dishes. Supplied in 1 kg vacuum-packed trays.'),
11077 => array(
'Entrecot de lomo bajo cortado a mano de forma tradicional. Es un corte uniforme con infiltración de grasa, apropiado para plancha o parrilla. Se presenta fresco y envasado al vacío; de forma orientativa, suelen obtenerse cuatro o cinco piezas por kilogramo.',
'Striploin entrecote traditionally cut by hand. It is an even cut with intramuscular fat, suitable for a griddle or grill. Supplied fresh and vacuum packed; as a guide, a kilogram usually contains around four to five pieces.'),
11090 => array(
'Morcillo de ternera procedente de la parte baja de la pata o jarrete. Es un corte especialmente apropiado para guisos, estofados y cocidos por su comportamiento en cocciones prolongadas. Se entrega limpio y envasado al vacío.',
'Veal shin from the lower leg. This cut is particularly suited to stews, braises and slow-cooked dishes because of the way it responds to longer cooking. It is supplied trimmed and vacuum packed.'),
11095 => array(
'Pito de vacuno fileteado, una pieza situada próxima a la entraña y caracterizada por un sabor intenso y una textura jugosa. Se presenta ya cortado en filetes para facilitar su preparación y permite cocinar una pieza menos habitual del despiece de vacuno.',
'Sliced beef pito, a cut located close to the skirt and characterised by a pronounced flavour and juicy texture. It is supplied already sliced for easier preparation and offers a less common cut from the beef carcass.'),
11097 => array(
'Huesos frescos de ternera preparados para elaborar caldos, fondos, guisos y cocidos. Se cortan y limpian antes de su preparación y se presentan envasados al vacío, listos para incorporarlos a recetas que necesiten una base de carne y hueso.',
'Fresh veal bones prepared for stocks, broths, stews and traditional boiled dishes. They are cut and cleaned before packing and supplied vacuum packed, ready to use in recipes that call for a meat-and-bone base.'),
11099 => array(
'Entrecot de vaca con grasa infiltrada y sabor intenso. Se prepara en filetes y se presenta en bandejas envasadas al vacío; de forma orientativa, suelen obtenerse cuatro o cinco piezas por kilogramo. La carne procede de piezas con un periodo de maduración superior a 20 días.',
'Beef entrecote with intramuscular fat and a pronounced flavour. It is portioned into steaks and supplied in vacuum-packed trays; as a guide, a kilogram usually contains four to five pieces. The meat comes from cuts matured for more than 20 days.'),
11103 => array(
'Redondo de ternera, una pieza cilíndrica y muy magra procedente de la pierna trasera. Puede utilizarse en guisos, asados, medallones o preparaciones tipo carne mechada, tanto entero como porcionado. Se presenta envasado al vacío.',
'Veal eye of round, a cylindrical and very lean cut from the hind leg. It can be used for stews, roasting, medallions or stuffed-style preparations, either whole or portioned. Supplied vacuum packed.'),
11107 => array(
'Carne de vaca madurada preparada para cocinar a la piedra, en plancha o sartén. Presenta grasa infiltrada y un sabor intenso, y se entrega fileteada en bandejas envasadas al vacío. Las piezas utilizadas cuentan con más de 20 días de maduración.',
'Matured beef prepared for cooking on a hot stone, griddle or pan. It has intramuscular fat and a pronounced flavour and is supplied sliced in vacuum-packed trays. The cuts used have been matured for more than 20 days.'),
11114 => array(
'Filetes de ternera preparados especialmente para cachopos, obtenidos de cortes como tapa o contra. Se cortan finos y en formato adecuado para rellenar, empanar y freír, facilitando la elaboración de este plato sin tener que abrir la pieza en casa.',
'Veal steaks prepared specifically for cachopos, using cuts such as topside or silverside. They are sliced thinly in a format suitable for filling, breading and frying, so the meat does not need to be opened out at home.'),
11117 => array(
'Filetes de segunda de ternera obtenidos de piezas como tapa o contra. Son cortes pensados para preparaciones cotidianas y especialmente adecuados para empanar o cocinar rápidamente. Se entregan ya fileteados y listos para su elaboración.',
'Second-cut veal steaks taken from joints such as topside or silverside. They are intended for everyday cooking and are particularly suitable for breading or quick cooking. Supplied already sliced and ready to prepare.'),
11120 => array(
'Tira de churrasco argentino obtenida mediante un corte transversal del costillar. Se presenta en tiras y está pensada para barbacoa, parrilla u horno, siguiendo el formato habitual del asado argentino. Se prepara y envasa al vacío.',
'Argentinian-style short-rib strip cut across the rib section. It is supplied in strips for barbecue, grilling or oven cooking in the traditional Argentine asado format. Prepared and vacuum packed.'),
11123 => array(
'Vacío de ternera, corte situado en la parte interior de las costillas. Puede cocinarse entero para barbacoa y asados o prepararse en filetes finos. Se presenta envasado al vacío y, una vez cocinado, conviene cortarlo en sentido contrario a la fibra.',
'Veal flank cut from the inside of the rib area. It can be cooked whole for barbecues and roasting or prepared as thin steaks. Supplied vacuum packed; after cooking, slicing it across the grain helps with serving.'),
11125 => array(
'Solomillo de vaca, corte de la zona lumbar de textura tierna y con poca grasa. Se prepara en medallones de aproximadamente 200 g, por lo que un kilogramo suele equivaler a unas cinco piezas. Es adecuado para plancha, sartén o elaboraciones de cocción rápida.',
'Beef tenderloin, a lean and tender cut from the loin area. It is prepared as medallions of approximately 200 g, so one kilogram usually provides around five pieces. Suitable for griddling, pan cooking and other quick preparations.'),
11127 => array(
'Cañón de espaldilla de ternera, procedente de la espaldilla, una pieza del cuarto delantero conocida por su jugosidad y versatilidad. Puede utilizarse para asar, cocinar a la plancha o en otras preparaciones donde interese un corte tierno del delantero.',
'Veal shoulder cannon cut from the shoulder, a forequarter joint known for its juiciness and versatility. It can be roasted, cooked on a griddle or used in other preparations where a tender forequarter cut is suitable.'),
11129 => array(
'Ragout de pito de vacuno preparado a partir de una pieza situada próxima a la entraña. Tiene un sabor marcado y una textura jugosa, y se presenta troceado para facilitar su uso en guisos, estofados y otras preparaciones de cocción en salsa.',
'Beef pito ragout made from a cut located close to the skirt. It has a pronounced flavour and juicy texture and is supplied diced for stews, braises and other slow-cooked dishes with sauce.'),
11131 => array(
'Lote compuesto por aproximadamente 1,5 kg de filetes de primera de ternera y 1,5 kg de carne magra de ternera, con un peso total aproximado de 3 kg. Ambos productos se preparan por separado y se presentan envasados al vacío.',
'Pack containing approximately 1.5 kg of first-class veal steaks and 1.5 kg of lean veal, for an approximate total weight of 3 kg. The two products are prepared separately and supplied vacuum packed.'),
11136 => array(
'Aleta de ternera preparada ya abierta para rellenar en casa. Es un corte especialmente utilizado para rellenos y permite completar la pieza con distintos ingredientes antes de cocinarla al horno o en olla. Se entrega lista para facilitar esta elaboración.',
'Veal flank prepared already opened out for stuffing at home. This cut is commonly used for filled dishes and can be combined with different ingredients before oven or pot cooking. Supplied ready to make this preparation easier.'),
11139 => array(
'Lote barbacoa compuesto por 2 kg de churrasco de ternera, cuatro hamburguesas Classic y 1 kg de entraña. Las piezas de carne se preparan para cocinar a la parrilla o barbacoa y los cortes principales se presentan envasados al vacío.',
'Barbecue pack containing 2 kg of veal short ribs, four Classic burgers and 1 kg of skirt steak. The meat is prepared for grilling or barbecuing, with the main cuts supplied vacuum packed.'),
11141 => array(
'Pecho de ternera cortado en filetes pequeños procedentes de la parte delantera del animal. Se entrega ya fileteado y está pensado para una preparación rápida, especialmente a la plancha, aprovechando este corte del pecho en porciones manejables.',
'Veal breast cut into small steaks from the front section of the animal. It is supplied already sliced for quick preparation, particularly on a griddle, making this breast cut easy to cook in manageable portions.'),
11154 => array(
'Roast beef de ternera preparado a partir de lomo bajo en una sola pieza de aproximadamente 1,5 kg. Es un corte pensado principalmente para asar y puede servirse caliente, templado o frío, acompañado de distintas salsas según la preparación elegida.',
'Veal roast beef prepared from striploin as a single piece of approximately 1.5 kg. It is primarily intended for roasting and can be served hot, warm or cold, with different sauces depending on the chosen preparation.'),
11163 => array(
'Tapilla o picaña de ternera, corte de pequeño tamaño cuya parte más estrecha destaca por su sabor. Es una pieza versátil que puede cocinarse a la brasa, a la plancha o en sartén y se integra dentro de los cortes de primera del despiece de ternera.',
'Veal rump cap or picanha, a relatively small cut whose narrow end is particularly flavourful. It is a versatile first-class cut that can be cooked on a barbecue, griddle or in a pan.'),
);
foreach ( $tolecarnes as $id => $pair ) emdo_seo_apply_pair( (int)$id, $pair[0], $pair[1], $log );

// Verify El Catedrático remains disabled and all its prelaunch English routes remain off.
foreach ( array( 4508, 4509 ) as $uid ) {
    $u = get_userdata( $uid );
    if ( ! $u || ! in_array( 'disable_vendor', (array)$u->roles, true ) ) throw new RuntimeException( "Disabled vendor role changed {$uid}" );
}
foreach ( array_keys( $catedratico ) as $id ) {
    if ( (string)get_post_meta( $id, '_en_US_published', true ) !== '0' || (string)get_post_meta( $id, '_en_US_ready', true ) !== '1' ) throw new RuntimeException( "Prelaunch flags changed {$id}" );
}

echo "EMDO VERIFIED SEO PRODUCT CONTENT ENRICHMENT 2026-08-21\n";
echo 'UPDATED_COUNT=' . count( $log ) . "\n";
foreach ( $log as $row ) echo 'UPDATED=' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES ) . "\n";
echo "SEO_PRODUCT_CONTENT_ENRICHMENT=PASS\n";
