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
check(MDO_Tropitop_Editorial::residual_markers($clean)===array(),'No residual flags');
$short=MDO_Tropitop_Editorial::short_description('<p>Nuestras berenjenas llegan a tu casa.</p><p>Formato 3 kg.</p>');
check(!str_contains($short,'Nuestras'),'Short supplier pitch removed');
check(str_contains($short,'3 kg'),'Neutral short facts preserved');
$fallback=MDO_Tropitop_Editorial::description('<p>¡Consulta nuestra tienda!</p>','Berenjenas','Berenjenas de 3 kg.');
check(str_contains($fallback,'3 kg'),'Verifiable short fallback');
check(MDO_Tropitop_Editorial::description('<p>Escríbenos</p>','X','')==='','No invented product facts on empty source');
echo "TROPITOP_EDITORIAL_SMOKE_OK\n";
