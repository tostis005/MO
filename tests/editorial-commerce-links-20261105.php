<?php
define('ABSPATH',__DIR__);define('OBJECT','OBJECT');
$GLOBALS['hooks']=array();$GLOBALS['current_post']=101;$GLOBALS['stock']=true;
$_SERVER['REQUEST_URI']='/aove-garrafa-5-litros-cuando-compensa-como-conservar/';
function add_filter($hook,$callback,$priority=10){$GLOBALS['hooks'][$hook]=$callback;}
function is_admin(){return false;}
function is_singular($type){return $type==='post';}
function in_the_loop(){return true;}
function is_main_query(){return true;}
function get_queried_object_id(){return $GLOBALS['current_post'];}
function get_post_type($id){return 'post';}
function get_post_field($key,$id){$posts=[
  101=>'aove-garrafa-5-litros-cuando-compensa-como-conservar',
  102=>'brida-negra-roja-verde-blanca-jamon-iberico',
  103=>'50-75-100-iberico-que-significa-porcentaje-raza-etiqueta',
  104=>'aove-filtrado-o-sin-filtrar-diferencias',
  105=>'jamon-o-paleta-diferencias-cual-elegir',
  106=>'irrelevant-page'
];return $posts[$id]??'';}
class WP_Post {public $ID;public $post_status='publish';public function __construct($id){$this->ID=$id;}}
function get_page_by_path($slug,$output,$type){$permitted=[
  'cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio',
  'aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27',
  'jamon-de-bellota-100-iberico','jamon-cebo-de-campo-iberico-50-montjam',
  'jamon-bellota-100-iberico','paleta-bellota-100-iberica'
];if(!in_array($slug,$permitted,true))return null;return new WP_Post(array_search($slug,$permitted,true)+1000);}
function get_permalink($id){return 'https://www.elmercadodeorigen.com/producto/test-'.$id.'/';}
function wc_get_product($id){return new class {public function is_visible(){return true;}public function is_in_stock(){return $GLOBALS['stock'];}};}
function esc_url($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
function esc_html($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
require __DIR__.'/../mu-plugins/emdo-editorial-commerce-links-20261105.php';
$fn=$GLOBALS['hooks']['the_content']??null;if(!$fn)exit(2);
foreach([101,102,103,104,105] as $id){
  $GLOBALS['current_post']=$id;
  $out=$fn('<p>Existing editorial content and sources</p>');
  if(substr_count($out,'data-emdo-editorial-commerce-20261105=')!==1 || strpos($out,'Existing editorial content')===false
    || substr_count($fn($out),'data-emdo-editorial-commerce-20261105=')!==1){fwrite(STDERR,"Guide failed $id\n");exit(3);}
}
$GLOBALS['current_post']=106;if($fn('Untouched')!=='Untouched')exit(4);
$GLOBALS['current_post']=101;$_SERVER['REQUEST_URI']='/en/aove-garrafa-5-litros-cuando-compensa-como-conservar/';
if($fn('English')!=='English')exit(5);
$_SERVER['REQUEST_URI']='/aove-garrafa-5-litros-cuando-compensa-como-conservar/';$GLOBALS['stock']=false;
if($fn('No stock')!=='No stock')exit(6);
echo "EMDO_EDITORIAL_COMMERCE_TESTS_OK\n";
