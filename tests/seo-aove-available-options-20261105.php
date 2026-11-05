<?php
define('ABSPATH',__DIR__);
$GLOBALS['filters']=array();
function add_filter($hook,$callback,$priority=10,$accepted_args=1){$GLOBALS['filters'][$hook]=$callback;}
function is_admin(){return false;}
function is_product(){return true;}
function get_post_field($key,$id){return $id===1?'cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio':'other-product';}
function esc_html($text){return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
class WP_Term {public $name; public function __construct($name){$this->name=$name;}}
function get_term_by($field,$slug,$taxonomy){return new WP_Term(str_replace('-',' ',$slug));}
class WC_Product_Variable {
    public function get_id(){return 1;}
    public function get_children(){return array(11,12,13);}
    public function get_variation_attributes(){return array('pa_tamano'=>array('envase-de-5l-de-pet','retired-250ml','pack-de-4-unidades'),
        'pa_variedad'=>array('sin-filtrar-lechin','campana-23-24','sin-filtrar-manzanilla'));}
}
class TestVariation {
    private $status; private $attrs;
    public function __construct($status,$attrs){$this->status=$status;$this->attrs=$attrs;}
    public function get_status(){return $this->status;}
    public function is_purchasable(){return $this->status==='publish';}
    public function is_in_stock(){return true;}
    public function get_attributes(){return $this->attrs;}
}
function wc_get_product($id){
    if ($id===11) return new TestVariation('publish',array('pa_tamano'=>'envase-de-5l-de-pet','pa_variedad'=>'sin-filtrar-lechin'));
    if ($id===12) return new TestVariation('private',array('pa_tamano'=>'retired-250ml','pa_variedad'=>'campana-23-24'));
    if ($id===13) return new TestVariation('publish',array('pa_tamano'=>'pack-de-4-unidades','pa_variedad'=>'sin-filtrar-manzanilla'));
    return null;
}
require __DIR__.'/../mu-plugins/emdo-aove-2026-available-options.php';
$p=new WC_Product_Variable();
$filter=$GLOBALS['filters']['woocommerce_dropdown_variation_attribute_options_args'];
foreach(array('pa_tamano'=>array('envase-de-5l-de-pet','pack-de-4-unidades'),
             'pa_variedad'=>array('sin-filtrar-lechin','sin-filtrar-manzanilla')) as $attr=>$expected){
   $actual=$filter(array('product'=>$p,'attribute'=>$attr,'options'=>$p->get_variation_attributes()[$attr]));
   if($actual['options']!==$expected){fwrite(STDERR,"Wrong options for $attr: ".json_encode($actual['options'])."\n");exit(1);}
}
$rows=$GLOBALS['filters']['woocommerce_display_product_attributes'](array(
  'attribute_pa_tamano'=>array('value'=>'all'),
  'attribute_pa_variedad'=>array('value'=>'all')
),$p);
if(str_contains($rows['attribute_pa_tamano']['value'],'retired') ||
   str_contains($rows['attribute_pa_variedad']['value'],'23-24') ||
   !str_contains($rows['attribute_pa_variedad']['value'],'sin filtrar')){exit(2);}
echo "AOVE_AVAILABLE_OPTIONS_TEST_OK\n";
