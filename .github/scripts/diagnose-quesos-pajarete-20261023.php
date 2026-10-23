<?php
if(!defined('ABSPATH')) { throw new RuntimeException('WP required'); }
$allowed=array('elmercadodeorigen.com','www.elmercadodeorigen.com');
if(!in_array(strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST)),$allowed,true)){ throw new RuntimeException('Wrong site'); }
$urls=array(
'https://quesospajarete.com/producto/semicurado-de-cabra/',
'https://quesospajarete.com/producto/curado-de-cabra/',
'https://quesospajarete.com/producto/pistacho-tostado-sin-sal-la-rosala/'
);
foreach($urls as $url) {
 $response=wp_remote_get($url,array('timeout'=>30,'redirection'=>3));
 if(is_wp_error($response)){echo 'PAJARETE_DIAG_ERROR='.wp_json_encode(array('url'=>$url,'error'=>$response->get_error_message())).PHP_EOL;continue;}
 $html=(string)wp_remote_retrieve_body($response); $dom=new DOMDocument();
 $prior=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($prior);$xp=new DOMXPath($dom);
 $result=array('url'=>$url,'status'=>wp_remote_retrieve_response_code($response),'bytes'=>strlen($html),'meta'=>array(),'imgs'=>array(),'headings'=>array(),'dom'=>array(),'scripts'=>array());
 foreach($xp->query('//meta[@content]')?:array() as $n){$k=(string)($n->getAttribute('property')?:$n->getAttribute('name')); if(preg_match('/image|description|title/i',$k)){$result['meta'][$k]=mb_substr((string)$n->getAttribute('content'),0,260);}}
 foreach($xp->query('//img')?:array() as $n){if(count($result['imgs'])>=16)break; $result['imgs'][]=array('alt'=>mb_substr($n->getAttribute('alt'),0,80),'src'=>mb_substr($n->getAttribute('src'),0,160),'data-src'=>mb_substr($n->getAttribute('data-src'),0,160),'data-large_image'=>mb_substr($n->getAttribute('data-large_image'),0,160),'data-lazy-src'=>mb_substr($n->getAttribute('data-lazy-src'),0,160),'class'=>mb_substr($n->getAttribute('class'),0,100));}
 foreach($xp->query('//h2|//h3|//h4|//h5')?:array() as $n){$t=trim(preg_replace('/\\s+/u',' ',(string)$n->textContent));if(!$t)continue;if(count($result['headings'])>=22)break;$parent=$n->parentNode;$result['headings'][]=array('text'=>mb_substr($t,0,105),'parent_tag'=>$parent? $parent->nodeName:'','parent_id'=>$parent instanceof DOMElement?$parent->getAttribute('id'):'','parent_class'=>$parent instanceof DOMElement?mb_substr($parent->getAttribute('class'),0,130):'');}
 foreach($xp->query('//*[@id="tab-description" or contains(@class,"woocommerce-product-details__short-description") or contains(@class,"woocommerce-Tabs-panel") or contains(@class,"avada") or contains(@class,"fusion-text") or contains(@class,"product-gallery")]')?:array() as $n){if(count($result['dom'])>=15)break;$result['dom'][]=array('tag'=>$n->nodeName,'id'=>$n->getAttribute('id'),'class'=>mb_substr($n->getAttribute('class'),0,110),'text'=>mb_substr(trim(preg_replace('/\\s+/u',' ',(string)$n->textContent)),0,150));}
 foreach($xp->query('//script[@type="application/ld+json"]')?:array() as $n){if(count($result['scripts'])>=3)break;$result['scripts'][]=mb_substr((string)$n->textContent,0,450);}
 echo 'PAJARETE_DIAG='.wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
