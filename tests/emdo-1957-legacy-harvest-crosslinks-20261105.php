<?php
define('ABSPATH',__DIR__);define('OBJECT','OBJECT');
$GLOBALS['actions']=array();$GLOBALS['in_stock']=true;
function add_action($name,$cb,$priority=10){$GLOBALS['actions'][$name]=$cb;}
function is_admin(){return false;}
function is_product(){return true;}
function get_post_type($id){return 'product';}
function get_post_field($name,$id){$slugs=array(
  11=>'aceite-de-oliva-virgen-extra-5l',12=>'aceite-de-oliva-virgen-extra-1l',
  13=>'aceite-de-oliva-virgen-extra-500ml-2',14=>'some-other-product'
);return $slugs[$id]??'';}
function get_queried_object_id(){return 11;}
class WP_Post {public $ID;public $post_status='publish';function __construct($id){$this->ID=$id;}}
function get_page_by_path($slug,$output,$type){return $type==='product'?new WP_Post(101):null;}
function wc_get_product($id){return new class {function is_visible(){return true;}function is_in_stock(){return $GLOBALS['in_stock'];}};}
function get_permalink($id){return 'https://www.elmercadodeorigen.com/producto/new-harvest/';}
function esc_url($v){return $v;}
function esc_html($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
require __DIR__.'/../mu-plugins/emdo-1957-legacy-harvest-crosslinks-20261105.php';
foreach (array(11,12,13) as $id){
  $markup=emdo_1957_legacy_harvest_markup_20261105($id);
  if(!str_contains($markup,'data-emdo-1957-harvest-link-20261105="1"')
      || !str_contains($markup,'26/27') || !str_contains($markup,'/producto/new-harvest/'))exit(1);
}
if(emdo_1957_legacy_harvest_markup_20261105(14)!=='')exit(2);
$GLOBALS['in_stock']=false;
if(emdo_1957_legacy_harvest_markup_20261105(11)!=='')exit(3);
echo "EMDO_1957_HARVEST_BRIDGE_TEST_OK\n";
