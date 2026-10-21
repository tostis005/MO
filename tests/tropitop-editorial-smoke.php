<?php
/** Standalone editorial smoke checks: php tests/tropitop-editorial-smoke.php */
define('ABSPATH', __DIR__ . '/');
function wp_kses_post($html){ return (string)$html; }
function wp_strip_all_tags($html){ return strip_tags((string)$html); }
function esc_html($html){return htmlspecialchars((string)$html,ENT_QUOTES|ENT_HTML5,'UTF-8');}
require dirname(__DIR__).'/mdo-supplier-sync/includes/class-mdo-tropitop-editorial.php';
function check($ok,$message){if(!$ok){throw new RuntimeException($message);}}

$description='<h4>Beneficios de nuestro calabacín</h4><p>En nuestra tienda queremos venderte salud.</p>'
    .'<ul><li>El mejor para reducir el colesterol</li></ul>'
    .'<h4>¿Qué contiene la caja?</h4>'
    .'<ul><li>Contenido: 2 kg o 4 kg.</li><li>Origen: Castellón.</li>'
    .'<li>Conservación: en un lugar fresco y seco.</li></ul>'
    .'<p>Trabajamos con nuestros agricultores y los enviamos desde nuestra tienda.</p>'
    .'<p>La variedad tiene piel fina y sabor suave.</p>'
    .'<p><a href="https://tropitop.com/es/verduras/2322.html">Compra en Tropitop</a></p>';
$clean=MDO_Tropitop_Editorial::description($description,'Calabacín','Calabacín negro.');
check(str_contains($clean,'2 kg o 4 kg'),'Pack options remain intact');
check(str_contains($clean,'Castellón'),'Origin must survive');
check(str_contains($clean,'Conservación'),'Storage information must survive');
check(str_contains($clean,'piel fina'),'Factual sensory details must survive');
check(!str_contains($clean,'colesterol'),'Generic source health claims removed');
check(!str_contains($clean,'nuestra'),'Supplier first-person removed');
check(!str_contains($clean,'tropitop.com'),'Outbound producer checkout removed');
check(MDO_Tropitop_Editorial::residual_markers($clean)===array(),'No residual flags: '.json_encode(MDO_Tropitop_Editorial::residual_markers($clean)).' CLEAN='.substr(strip_tags($clean),0,500));
$short=MDO_Tropitop_Editorial::short_description('<p>Nuestras berenjenas llegan a tu casa.</p><p>Formato 3 kg.</p>');
check(!str_contains($short,'Nuestras'),'Short supplier pitch removed');
check(str_contains($short,'3 kg'),'Neutral short facts preserved');
$fallback=MDO_Tropitop_Editorial::description('<p>¡Consulta nuestra tienda!</p>','Berenjenas','Berenjenas de 3 kg.');
check(str_contains($fallback,'3 kg'),'Verifiable short fallback');
check(MDO_Tropitop_Editorial::description('<p>Escríbenos</p>','X','')==='','No invented product facts on empty source');
$heading = '<h4>¿De dónde proceden las berenjenas negras que vende Tropitop?</h4>'
    . '<p>En nuestra tienda promovemos la agricultura local.</p>'
    . '<h4>Características</h4><p>Piel brillante y pulpa tierna.</p>';
$heading_clean = MDO_Tropitop_Editorial::description($heading,'Berenjenas','Berenjenas negras.');
check(!str_contains($heading_clean,'vende Tropitop'),'External seller/producer heading should be removed');
check(!str_contains($heading_clean,'promovemos'),'Remaining producer voice removed');
check(str_contains($heading_clean,'Piel brillante'),'Following factual section preserved');
$retailer = '<h4>¿Qué incluye la caja de tomates Raf en Tropitop?</h4>'
    . '<ul><li>Contenido: 2 y 4 kg.</li></ul>'
    . '<h4>¿Cuál es el origen de los tomates que puedes comprar en Tropitop?</h4>'
    . '<p>Cultivados en Castellón.</p>'
    . '<h4>Compra tomates directamente del productor</h4>'
    . '<p>El cultivo tiene un carácter mediterráneo.</p>';
$retailer_clean = MDO_Tropitop_Editorial::description( $retailer, 'Tomates Raf', 'Cajas de tomate.' );
check( str_contains($retailer_clean,'Contenido de la caja'), 'Product pack heading neutralized' );
check( ! str_contains($retailer_clean,'en Tropitop'), 'Producer must not be checkout retailer' );
check( ! str_contains($retailer_clean,'Compra tomates'), 'Remove direct-to-producer commerce headline' );
check( str_contains($retailer_clean,'2 y 4 kg'), 'Must preserve pack sizes' );
check( str_contains($retailer_clean,'Castellón'), 'Must preserve origin facts' );
check( MDO_Tropitop_Editorial::residual_markers($retailer_clean)===array(), 'No external retailer marker' );
$farmer = MDO_Tropitop_Editorial::description('<ul><li>Agricultor: Hermán, agricultor de confianza de Tropitop.</li>'
    . '<li>Número de piezas: aprox. 2 kg. Ajustamos siempre el contenido para alcanzar el peso total.</li></ul>');
check( str_contains($farmer,'Agricultor: Hermán'), 'Retain farmer identity' );
check( str_contains($farmer,'El contenido se ajusta'), 'Preserve objective pack explanation' );
check( ! str_contains($farmer,'de confianza de Tropitop'), 'No source-store attributions' );
$retailer='<h2>Comprar cerezas online en Tropitop</h2>'
    .'<p>En Tropitop trabajamos con agricultores y aseguramos la mejor compra.</p>'
    .'<p>Cerezas cultivadas en la provincia de Castellón; conservar en frío.</p>';
$retailer_clean=MDO_Tropitop_Editorial::description($retailer,'Cerezas de Castellón','Cerezas frescas.');
check(!str_contains($retailer_clean,'en Tropitop'),'External store reference removed');
check(!str_contains($retailer_clean,'trabajamos'),'First-person store pitch removed');
check(str_contains($retailer_clean,'Castellón'),'Factual origin preserved');
check(str_contains($retailer_clean,'conservar'),'Factual storage preserved');
check(MDO_Tropitop_Editorial::residual_markers($retailer_clean)===array(),'No external retailer residuals');
echo "TROPITOP_EDITORIAL_SMOKE_OK\n";
