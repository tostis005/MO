<?php
if(!defined('ABSPATH'))exit(1);
if(!in_array(wp_parse_url(home_url(),PHP_URL_HOST),array('www.elmercadodeorigen.com','elmercadodeorigen.com'),true))throw new RuntimeException('Production required');
$v=get_user_by('login','ikore-kofi');if(!$v||$v->ID!=4970)throw new RuntimeException('Vendor mismatch');
$parents=array('46002/V0112'=>array(20,76),'46002/V0116'=>array(18,68.4),'46002/V0122'=>array(12.9,49.02),'46002/V0123'=>array(12.9,49.02),'46002/V0127'=>array(18,68.4),'46002/V0124'=>array(18,68.4));
foreach($parents as $sku=>$prices){
 $p=wc_get_product(wc_get_product_id_by_sku($sku));if(!$p||count($p->get_children())!==14)throw new RuntimeException('Variation count');
 foreach($p->get_children() as $cid){$c=wc_get_product($cid);$kg=$c->get_attribute('peso')==='1 kg';$expected=$prices[$kg?1:0];$price=wc_get_price_including_tax($c);
  if(abs($price-$expected)>.015||abs(wc_get_weight((float)$c->get_weight(),'kg')-($kg?1:.25))>.001)throw new RuntimeException('Price/weight mismatch '.$cid.' '.$price);
 }
}
WC()->initialize_session();WC()->customer=new WC_Customer(0,true);WC()->cart=new WC_Cart();
$child=wc_get_product(wc_get_product_id_by_sku('46002/V0112-250-1'));$pid=$child->get_parent_id();
$results=array();
foreach(array('peninsula'=>array('ES','M','28001',3.99),'baleares'=>array('ES','PM','07001',6.99),'portugal'=>array('PT','','1000-001',5.99)) as $label=>$loc){
 WC()->customer->set_billing_country($loc[0]);WC()->customer->set_billing_state($loc[1]);WC()->customer->set_billing_postcode($loc[2]);
 WC()->customer->set_shipping_country($loc[0]);WC()->customer->set_shipping_state($loc[1]);WC()->customer->set_shipping_postcode($loc[2]);WC()->customer->set_calculated_shipping(true);
 foreach(array(1,2) as $qty){
  WC()->cart->empty_cart(false);WC()->shipping()->reset_shipping();
  $key=WC()->cart->add_to_cart($pid,$qty,$child->get_id(),array('attribute_peso'=>'250 g','attribute_molienda'=>'En grano'));
  if(!$key)throw new RuntimeException('Cart fixture rejected');
  WC()->cart->calculate_totals();$packs=WC()->shipping()->get_packages();$rates=array();
  foreach($packs as $pack)foreach(($pack['rates']??array()) as $rate)$rates[]=array('id'=>$rate->get_id(),'method'=>$rate->get_method_id(),'cost'=>(float)$rate->get_cost(),'label'=>$rate->get_label());
  $results[]=array('destination'=>$label,'qty'=>$qty,'product_subtotal'=>WC()->cart->get_subtotal()+WC()->cart->get_subtotal_tax(),'shipping_total'=>WC()->cart->get_shipping_total(),'rates'=>$rates);
 }
}
WC()->cart->empty_cart(false);
echo 'IKORE_CART_AUDIT='.wp_json_encode($results,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
echo "IKORE_CATALOG_84_VARIATIONS_VERIFIED\n";
