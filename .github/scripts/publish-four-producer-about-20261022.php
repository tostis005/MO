<?php
/**
 * Publish four approved Spanish producer About narratives in production.
 * Updates only WCFM user-meta _store_description and profile shop_description.
 * Other stores, products, translations and SEO fields are untouched.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('WordPress CLI required');
}
$host = strtolower((string) wp_parse_url((string) get_option('siteurl'), PHP_URL_HOST));
if (!in_array($host, ['www.elmercadodeorigen.com', 'elmercadodeorigen.com'], true)) {
    throw new RuntimeException('Refusing to edit non-production site: '.$host);
}
$approved = [
    'chocolates-artesanos-isabel' => <<<'HTML'
<p>La historia de Chocolates Artesanos Isabel comienza mucho antes de la apertura de su obrador en Alcorisa, un municipio del Bajo Aragón, en la provincia de Teruel. Detrás del proyecto se encuentra Isabel Félez Roselló, una profesional que, después de años formándose y trabajando en algunas de las pastelerías y chocolaterías más reconocidas de Europa, decidió regresar a su lugar de origen para desarrollar una forma muy personal de entender el chocolate.</p>

<p>En 2013 aquel propósito se convirtió en realidad con la apertura de un pequeño obrador artesanal en Alcorisa. La idea era unir los conocimientos adquiridos durante años de experiencia internacional con un proyecto arraigado en el medio rural, donde cada elaboración tuviera una identidad propia y el origen de los ingredientes formara parte esencial del resultado.</p>

<p>Su trayectoria la ha llevado a formarse en pastelería y chocolatería en lugares como Salamanca, París, Estocolmo y Vancouver, especializándose también en la elaboración del chocolate bean to bar, un proceso que permite trabajar directamente desde el haba de cacao hasta la tableta terminada.</p>

<p>Una de las particularidades de Chocolates Artesanos Isabel es la selección de sus materias primas. En el obrador trabajan con ingredientes ecológicos y de Comercio Justo, especialmente cacao y azúcar de caña, que combinan con productos de su entorno, como el aceite de oliva virgen extra y la almendra marcona. De esta manera, ingredientes procedentes de distintas regiones productoras de cacao encuentran un vínculo con la tradición alimentaria del Bajo Aragón.</p>

<p>La elaboración artesanal también ocupa un lugar fundamental. Además de trabajar parte de sus chocolates desde el propio haba de cacao, preparan en el obrador elaboraciones complementarias como pralinés, pastas de frutos secos y frutas confitadas o deshidratadas. Ese conocimiento les permite desarrollar recetas que van desde las tabletas de chocolate hasta bombones, cremas y otras especialidades, combinando preparaciones tradicionales con propuestas más innovadoras.</p>

<p>Con el tiempo, el proyecto ha recibido distintos reconocimientos por su innovación, su compromiso social y su trabajo en el ámbito de la producción ecológica. Entre ellos se encuentran el premio al Mejor Producto Ecológico de BioCultura Barcelona en 2015 y el reconocimiento a la Excelencia a la Innovación para Mujeres Rurales.</p>

<p>Hoy, Chocolates Artesanos Isabel continúa desarrollando su actividad desde Alcorisa, demostrando que un pequeño obrador rural puede reunir conocimiento internacional, elaboración artesanal y compromiso con el origen de sus ingredientes. Una forma de entender el chocolate en la que importan tanto las características del cacao como las personas y los lugares que participan en su elaboración.</p>
HTML,
    'tropitop' => <<<'HTML'
<p>La historia de Tropitop nace de una tradición agrícola familiar profundamente vinculada al cultivo de cítricos en la provincia de Castellón. Detrás del proyecto se encuentra Joaquín, un agricultor que creció entre campos de naranjos y que, con los años, decidió buscar nuevas posibilidades para una actividad que había formado parte de su familia durante generaciones.</p>

<p>Su finca familiar, Peña Alta, se encuentra en el término municipal de Artana, en el entorno del Parque Natural de la Sierra de Espadán. Es un lugar con unas condiciones geográficas particulares, protegido de los vientos fríos del norte y próximo a la influencia mediterránea, donde el cultivo de cítricos ha estado presente durante décadas.</p>

<p>Durante más de treinta años, la explotación estuvo dedicada principalmente al cultivo de naranjas. Sin embargo, las dificultades que afrontaba la agricultura tradicional llevaron a la familia a replantearse su actividad y explorar nuevas alternativas que permitieran dar continuidad a su trabajo.</p>

<p>Fue entonces cuando comenzaron a incorporar cultivos tropicales a sus tierras, apostando por variedades como el aguacate y el mango. Esta diversificación permitió aprovechar las particularidades del clima de la zona y desarrollar una producción en la que conviven los frutales mediterráneos tradicionales con otros cultivos menos habituales en la provincia.</p>

<p>Una de las características que mejor define la filosofía de Tropitop es su manera de entender la frescura. En su producción propia, la fruta se recolecta manualmente y bajo pedido, buscando reducir el tiempo que transcurre entre el árbol y el consumidor y evitando almacenamientos innecesarios cuando las condiciones del producto lo permiten.</p>

<p>También conceden importancia a las características naturales de cada cosecha. No todas las frutas tienen exactamente el mismo tamaño, color o apariencia, y el proyecto defiende que esas diferencias no deben restar valor a una pieza cuando conserva las cualidades que se esperan de ella.</p>

<p>Esta forma de trabajar está acompañada de una apuesta por prácticas agrícolas más sostenibles y por la incorporación de cultivos ecológicos en determinadas variedades. Junto a las frutas procedentes de sus propias fincas, Tropitop ha desarrollado un catálogo más amplio de frutas, hortalizas y otros productos seleccionados, manteniendo la atención en su procedencia y disponibilidad.</p>

<p>Hoy, Tropitop representa la evolución de una explotación agrícola familiar que ha sabido adaptarse a nuevos cultivos y nuevas formas de comercialización sin perder su vínculo con la tierra. Una historia que comienza entre naranjos, continúa con la incorporación de frutas tropicales y mantiene como protagonista el trabajo de quienes cultivan y recolectan cada cosecha.</p>
HTML,
    'ikore-kofi' => <<<'HTML'
<p>La historia de Ikore Kofi está ligada a la búsqueda de una manera diferente de entender el café. Detrás del proyecto se encuentran Alfredo y Taira, dos profesionales vinculados al mundo del tueste desde 2014 que han desarrollado su trayectoria alrededor del café de especialidad y del conocimiento de sus distintos orígenes.</p>

<p>Desde Valencia, su trabajo comienza mucho antes de que el café llegue a la taza. Cada lote procede de un territorio, unas condiciones de cultivo y unas decisiones de elaboración que influyen directamente en el resultado final. Comprender esas diferencias y saber cómo respetarlas durante el tueste es una de las bases de su proyecto.</p>

<p>Para Ikore Kofi, el café no es una materia prima uniforme. Las variedades cultivadas, la altitud de las fincas y los procesos empleados después de la recolección pueden dar lugar a perfiles aromáticos muy distintos. Por eso trabajan con cafés de diferentes países y regiones productoras, buscando que cada uno conserve las características que lo hacen reconocible.</p>

<p>El tueste es una de las fases más importantes de su trabajo. En lugar de aplicar un mismo tratamiento a todos los cafés, adaptan el perfil de tueste a las particularidades de cada origen. El objetivo es desarrollar sus aromas y sabores sin ocultar las cualidades naturales del grano.</p>

<p>Ese cuidado continúa durante la selección y el control de calidad. Los granos se revisan manualmente para retirar aquellos que presentan imperfecciones y, después del tueste, se realizan catas para comprobar el resultado antes de incorporarlo a su oferta.</p>

<p>Su propuesta también presta atención al trabajo realizado en las fincas de origen. Detrás de cada café hay agricultores, variedades concretas y procesos de cosecha y transformación que contribuyen a definir sus características. Dar visibilidad a esas diferencias permite comprender mejor el producto y valorar todo el recorrido que realiza antes de llegar al consumidor.</p>

<p>Entre los cafés seleccionados por Ikore Kofi se encuentran orígenes como Colombia, Costa Rica, Panamá y Kenia, con variedades y procesos que permiten descubrir perfiles muy diferentes dentro del café de especialidad.</p>

<p>Después de más de una década dedicada al tueste profesional, Alfredo y Taira continúan desarrollando su trabajo desde Valencia, manteniendo una misma idea como punto de partida: que cada café exprese sus características de origen y que el conocimiento del tostador sirva para acompañarlas, no para ocultarlas.</p>
HTML,
    'latitudes' => <<<'HTML'
<p>La historia de Latitudes está vinculada a una especialización poco habitual dentro del sector cárnico español: acercar al consumidor carnes procedentes de diferentes regiones del mundo que normalmente no se encuentran en las carnicerías convencionales.</p>

<p>Desde Alcobendas, en Madrid, Latitudes ha desarrollado durante más de quince años una actividad centrada en la selección y distribución de carnes exóticas y cortes premium. Su experiencia les ha permitido construir un catálogo que reúne productos procedentes de los cinco continentes, destinados tanto a consumidores particulares como a profesionales de la restauración, la hostelería y el comercio especializado.</p>

<p>La particularidad de su propuesta está precisamente en esa variedad de procedencias. Cada especie ofrece características propias de sabor, textura y composición, y algunas de ellas forman parte de tradiciones gastronómicas muy diferentes de las habituales en España.</p>

<p>En su catálogo se encuentran carnes como las de avestruz, bisonte, búfalo, canguro, alce o venado, junto a otras especialidades menos frecuentes. Esta selección permite conocer diferentes formas de aprovechar y cocinar productos cárnicos que tienen una presencia limitada en el mercado convencional.</p>

<p>Junto a las carnes exóticas, Latitudes ha desarrollado una línea de carnes premium en la que destacan el wagyu y el Black Angus. Son productos apreciados por las características particulares de sus cortes y que requieren conocer aspectos como la raza, la procedencia y el tipo de pieza para comprender sus diferencias.</p>

<p>Su especialización también se refleja en los formatos disponibles. Además de cortes enteros o porcionados, trabajan con distintas preparaciones, como hamburguesas, carpaccios y elaborados cárnicos, que permiten descubrir estas carnes a través de formas de consumo más familiares.</p>

<p>A diferencia de los productores que centran su actividad en una explotación o una elaboración propia, el trabajo de Latitudes comienza en la búsqueda y selección de productos. Su aportación consiste en reunir especialidades de distintas procedencias y facilitar el acceso a carnes que, de otro modo, serían difíciles de encontrar.</p>

<p>Hoy, Latitudes mantiene esa misma orientación hacia las carnes exóticas y premium, con una trayectoria construida alrededor del conocimiento del producto y de la diversidad de sus orígenes. Una propuesta que amplía las posibilidades de elección y permite descubrir cómo las distintas especies y tradiciones gastronómicas dan lugar a carnes con características propias.</p>
HTML,
];
global $wpdb;
$target_records = [];
$rows = $wpdb->get_results(
    "SELECT u.ID, u.user_login, u.user_nicename, u.display_name, um.meta_value
     FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
     WHERE um.meta_key = 'wcfmmp_profile_settings'",
    ARRAY_A
);
foreach ($approved as $slug => $html) {
    $matches = [];
    foreach ((array) $rows as $row) {
        $settings = maybe_unserialize($row['meta_value']);
        if (!is_array($settings)) continue;
        $store_slug = sanitize_title((string) ($settings['store_slug'] ?? ''));
        $nicename = sanitize_title((string) $row['user_nicename']);
        if ($store_slug === $slug || $nicename === $slug) {
            $row['_settings'] = $settings;
            $matches[(int) $row['ID']] = $row;
        }
    }
    if (count($matches) !== 1) {
        throw new RuntimeException("Vendor slug $slug: expected 1 WCFM store, found ".count($matches));
    }
    $vendor = array_values($matches)[0];
    $id = (int) $vendor['ID'];
    $user = get_userdata($id);
    if (!$user instanceof WP_User || !in_array('wcfm_vendor', (array) $user->roles, true)) {
        throw new RuntimeException("Vendor $slug: unexpected user role");
    }
    $old_primary = (string) get_user_meta($id, '_store_description', true);
    $old_settings = $vendor['_settings'];
    $old_profile = (string) ($old_settings['shop_description'] ?? '');
    $old_visible = trim(wp_strip_all_tags($old_primary !== '' ? $old_primary : $old_profile));
    // A concurrent editorial revision must never be overwritten automatically.
    if (($old_primary !== $html || $old_profile !== $html) && (mb_strlen($old_visible) > 750 || mb_strlen($old_visible) < 40)) {
        throw new RuntimeException("Vendor $slug: About copy has unexpectedly changed (".mb_strlen($old_visible)." characters)");
    }
    if (mb_strlen(wp_strip_all_tags($html)) < 2000 || count(explode('<p>', $html)) < 7) {
        throw new RuntimeException("Vendor $slug: incomplete approved About narrative");
    }
    $target_records[$slug] = [
        'id' => $id,
        'settings' => $old_settings,
        'primary' => $old_primary,
        'html' => $html
    ];
}
$backup_option = '_mdo_four_vendor_about_backup_20261022';
if (get_option($backup_option, false) === false) {
    $backup = [];
    foreach ($target_records as $slug => $data) {
        $backup[$slug] = [
            'user_id' => $data['id'],
            'primary' => $data['primary'],
            'profile' => (string)($data['settings']['shop_description'] ?? ''),
        ];
    }
    if (!add_option($backup_option, $backup, '', false)) {
        throw new RuntimeException('Unable to persist rollback snapshot');
    }
}
$written = [];
try {
    foreach ($target_records as $slug => $data) {
        $id = $data['id'];
        $html = $data['html'];
        $settings = $data['settings'];
        $settings['shop_description'] = $html;
        update_user_meta($id, '_store_description', $html);
        update_user_meta($id, 'wcfmmp_profile_settings', $settings);
        $written[] = $slug;
        clean_user_cache($id);
        $saved = (string)get_user_meta($id, '_store_description', true);
        $saved_settings = get_user_meta($id, 'wcfmmp_profile_settings', true);
        if ($saved !== $html || !is_array($saved_settings)
            || (string)($saved_settings['shop_description'] ?? '') !== $html) {
            throw new RuntimeException("Vendor $slug: persistence verification failed");
        }
        echo 'ABOUT_PUBLISHED='.wp_json_encode([
            'slug'=>$slug, 'user_id'=>$id,
            'paragraphs'=>substr_count($html,'<p>'),
            'characters'=>mb_strlen(wp_strip_all_tags($saved)),
            'sha256'=>hash('sha256', $html)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
} catch (Throwable $e) {
    foreach ($written as $slug) {
        $data = $target_records[$slug];
        update_user_meta($data['id'], '_store_description', $data['primary']);
        update_user_meta($data['id'], 'wcfmmp_profile_settings', $data['settings']);
        clean_user_cache($data['id']);
    }
    throw $e;
}
wp_cache_flush();
if (function_exists('rocket_clean_domain')) rocket_clean_domain();
echo "ABOUT_FOUR_PRODUCERS_PUBLISHED=4\n";
