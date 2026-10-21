<?php
/** Standalone future Tropitop Gourmet taxonomy tests, run with php. */
define('ABSPATH',__DIR__.'/');
class WP_Term{ public int $term_id;function __construct(int $id){$this->term_id=$id;} }
function wp_parse_url($url,$part=-1){return parse_url($url,$part);}
function sanitize_title($s){return strtolower(trim(preg_replace('/[^a-z0-9-]+/i','-',(string)$s),'-'));}
function get_term_by($field,$slug,$taxonomy) {
  static $slugs=['aceites'=>16,'conservas'=>435,'mermeladas-y-confituras'=>601,'bebidas'=>602,
    'zumos-nectares-y-limonadas'=>603,'tes-e-infusiones'=>604,'mieles'=>605,
    'frutos-secos'=>606,'frutas'=>514,'frutas-del-mundo'=>520,'packs-y-lotes'=>409,
    'hortalizas-verduras'=>434,'foie-pates-untables'=>495,'cosmetica-natural'=>524];
  if(!isset($slugs[$slug]))return false;
  return new WP_Term($slugs[$slug]);
}
require dirname(__DIR__).'/mdo-supplier-sync/includes/class-mdo-woo-importer.php';
function assert_cats($sourceKey,$name,$expected){
 $payload=['title'=>$name,'source_url'=>'https://tropitop.com/es/'.$sourceKey.'/3456-producto.html'];
 $ids=MDO_Woo_Importer::tropitop_category_ids($payload,false);
 sort($ids);sort($expected);
 if($ids!==$expected)throw new RuntimeException($name.': '.json_encode($ids).' != '.json_encode($expected));
}
assert_cats('gourmet','Mermelada de naranja',[435,601]);
assert_cats('gourmet','Zumo de zanahoria bio',[602,603]);
assert_cats('gourmet','Limonada natural 1l',[602,603]);
assert_cats('gourmet','Néctar de mango 1l',[602,603]);
assert_cats('gourmet','Infusión frutos del bosque bio',[602,604]);
assert_cats('gourmet','Té verde con mango bio',[602,604]);
assert_cats('gourmet','Pack de mieles artesanales: montaña + azahar',[605,409]);
assert_cats('gourmet','Castañas',[606]);
assert_cats('gourmet','Pack de avellanas y almendras',[606,409]);
assert_cats('gourmet','Dátiles medjoul de marruecos',[514,520]);
assert_cats('gourmet','Pack de patés de aceituna artesanales',[495,409]);
assert_cats('gourmet','Tomates raf y aceite de oliva',[434,409]);
assert_cats('gourmet','Mermelada de melocotón',[435,601]);
assert_cats('gourmet','Pack de mermeladas',[435,601,409]);
assert_cats('gourmet','Aceite de oliva',[16]);
assert_cats('gourmet','Nueces',[606]);
assert_cats('gourmet','Pack de zumos bio',[602,603,409]);
assert_cats('gourmet','Pack infusiones bio',[602,604,409]);
assert_cats('gourmet','Cesta regalo desayuno natural y bienestar premium',[409]);
assert_cats('gourmet','Pack mediterráneo: aguacates, tomates raf y aceite oliva',[514,434,409]);
assert_cats('cosmetica-natural','Pack cosmética con miel online',[524,409]);
assert_cats('frutas-del-mundo','Jengibre',[434]);
$blocked=false;
try{MDO_Woo_Importer::tropitop_category_ids(['title'=>'Inesperado','source_url'=>'https://tropitop.com/es/gourmet/9-new.html'],false);}
catch(RuntimeException $e){$blocked=true;}
if(!$blocked)throw new RuntimeException('Unrecognized Gourmet may not create a generic category.');
echo "CATALOG_TAXONOMY_SMOKE_OK\n";
