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
$remaining = '<p>En Tropitop trabajamos junto a agricultores que recolectan la fruta.</p>'
    .'<p>Comprar uva verde online en Tropitop es una experiencia especial.</p>'
    .'<p>Tropitop ofrece cada melón para ofrecerte una caja especial.</p>'
    .'<p>La fruta tiene pulpa dulce y un aroma intenso.</p>';
$neutral = MDO_Tropitop_Editorial::description( $remaining );
check( !str_contains( $neutral,'En Tropitop' ), 'Reject supplier as retailer' );
check( !str_contains( $neutral,'Tropitop ofrece' ), 'Reject supplier self-promotion' );
check( str_contains( $neutral, 'pulpa dulce' ), 'Retain independently factual product copy' );
check( MDO_Tropitop_Editorial::residual_markers($neutral)===array(), 'No residual producer-store markers' );
check( MDO_Tropitop_Editorial::title('Cerezas online en tropitop | cerezas frescas de castellón')
    === 'Cerezas frescas de Castellón', 'Verified source SEO title normalized' );
check( MDO_Tropitop_Editorial::title('Aguacate Bacon')==='Aguacate Bacon', 'Do not rename clean titles' );
$farmer_tag = MDO_Tropitop_Editorial::description('<li><strong>Agricultor:</strong> Hermán, agricultor de confianza de Tropitop.</li>');
check( str_contains($farmer_tag,'Hermán'), 'Farmer name retained with HTML formatting' );
check( !str_contains($farmer_tag,'Tropitop'), 'Producer self-endorsement removed with HTML formatting' );
echo "TROPITOP_EDITORIAL_SMOKE_OK\n";
