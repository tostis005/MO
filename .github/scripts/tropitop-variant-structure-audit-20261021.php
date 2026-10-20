<?php
/**
 * Bounded source HTML structure diagnostic. No writes, carts, hidden tokens
 * or cookies; only reads four public Tropitop product pages.
 */
if(!defined('ABSPATH')||!class_exists('MDO_Connector_Tropitop'))throw new RuntimeException('Tropitop connector unavailable');
foreach(array('siteurl','home') as $opt){
 $host=strtolower((string)wp_parse_url(get_option($opt),PHP_URL_HOST));
 if(!in_array($host,array('www.elmercadodeorigen.com','elmercadodeorigen.com'),true))throw new RuntimeException('Unexpected production');
}
$cases=array(
 '2345'=>'https://tropitop.com/es/gourmet/2345-comprar-mermelada-naranja-artesanal.html',
 '2317'=>'https://tropitop.com/es/gourmet/2317-comprar-mermelada-de-guayaba.html',
 '2732'=>'https://tropitop.com/es/packs/2732-comprar-pack-tropical-smoothie-online.html',
 '2785'=>'https://tropitop.com/es/packs/2385-comprar-aguacate-hass-y-mermelada-de-mango-7427128965005.html',
);
foreach($cases as $id=>$url){
 $out=array('sku'=>$id);
 try{
  $r=wp_remote_get($url,array('timeout'=>15,'redirection'=>2,'limit_response_size'=>450000,'headers'=>array('Accept-Language'=>'es-ES,es;q=0.9','Accept'=>'text/html')));
  if(is_wp_error($r))throw new RuntimeException($r->get_error_message());
  $html=wp_remote_retrieve_body($r);
  $out['status']=(int)wp_remote_retrieve_response_code($r);
  $out['bytes']=strlen($html);
  if(strlen($html)<1000)throw new RuntimeException('short HTML');
  $doc=new DOMDocument();$prev=libxml_use_internal_errors(true);
  $doc->loadHTML('<?xml encoding="UTF-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
  libxml_clear_errors();libxml_use_internal_errors($prev);$xp=new DOMXPath($doc);
  $out['title']=trim((string)($xp->query('//h1')->item(0)?->textContent??''));
  $out['heading']=trim((string)($xp->query('//h1')->item(0)?->getAttribute('class')??''));
  $out['variant_selectors']=array();
  foreach($xp->query('//*[contains(@class,"product-variants") or contains(@class,"variant") or contains(@class,"attribute") or @data-product-attribute]') as $node){
   if(count($out['variant_selectors'])>=18)break;
   $raw=trim(preg_replace('/\s+/u',' ',(string)$node->textContent));
   if(strlen($raw)>240)$raw=substr($raw,0,240);
   $out['variant_selectors'][]=array('tag'=>$node->nodeName,'class'=>substr((string)($node instanceof DOMElement?$node->getAttribute('class'):''),0,140),'text'=>$raw);
  }
  $out['selector_labels']=array();
  foreach($xp->query('//select[starts-with(@name,"group")]//option | //input[@data-product-attribute] | //button[contains(@class,"dropdown")] | //div[contains(@class,"product-variants")]//li') as $n){
   if(count($out['selector_labels'])>=14)break;
   $out['selector_labels'][]=substr(trim(preg_replace('/\s+/u',' ',(string)$n->textContent)),0,120);
  }
  $out['prices']=array();
  foreach($xp->query('//*[contains(@class,"current-price")]|//*[contains(@class,"product-price")]') as $n){
   if(count($out['prices'])>=6)break;
   $out['prices'][]=substr(trim(preg_replace('/\s+/u',' ',(string)$n->textContent)),0,110);
  }
  $out['stock']=array();
  foreach($xp->query('//*[@id="product-availability"]|//button[@data-button-action="add-to-cart"]') as $n){
   if(count($out['stock'])>=3)break;
   $out['stock'][]=substr(trim(preg_replace('/\s+/u',' ',(string)$n->textContent)),0,150);
  }
  $out['description_weight_text']=array();
  foreach($xp->query('//*[@id="description"]//p | //div[contains(@class,"product-description")]//p | //div[@id="product-description-short"]') as $n){
    if(count($out['description_weight_text'])>=8)break;
    $txt=trim(preg_replace('/\s+/u',' ',(string)$n->textContent));
    if(preg_match('/\b(?:kg|kilos|gramos|g\b|ml\b|unidades|botes|caja|l(?:itro)?s?)\b/iu',$txt))$out['description_weight_text'][]=mb_substr($txt,0,240);
  }
  $out['script_indicators']=array();
  foreach($xp->query('//script') as $n){
    $sc=(string)$n->textContent;
    if(preg_match('/(?:"weight"\s*:|"combinations"\s*:|"attributes"\s*:|window\.prestashop|var prestashop)/i',$sc)){
     if(count($out['script_indicators'])>=4)break;
     $offset=strpos($sc,'prestashop');if($offset===false)$offset=0;
     $out['script_indicators'][]=array('length'=>strlen($sc),'snippet'=>substr(preg_replace('/\s+/',' ',$sc),max(0,$offset-100),450));
    }
  }
 }catch(Throwable $e){$out['error']=mb_substr($e->getMessage(),0,190);}
 echo 'TROPITOP_VARIANT_DIAG='.wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
}
