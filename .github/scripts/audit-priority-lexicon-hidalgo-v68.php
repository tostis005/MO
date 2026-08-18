<?php
if ( ! defined('ABSPATH') ) { fwrite(STDERR,"WordPress required\n"); exit(2); }
global $wpdb;
$targets=[4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático'];
$patterns=[
 'codillo'=>'/\bcodillo\b/iu','punta'=>'/\bpunta\b/iu','tacos'=>'/\btacos\b/iu','sobres'=>'/\bsobres\b/iu',
 'cortado'=>'/\bcortad[oa]s?\b/iu','cuchillo'=>'/\bcuchillo\b/iu','paleta'=>'/\bpaletas?\b/iu','jamon'=>'/\bjam[oó]n\b/iu',
 'bellota'=>'/\bbellota\b/iu','cebo de campo'=>'/\bcebo\s+de\s+campo\b/iu','raza iberica'=>'/\braza\s+ib[eé]rica\b/iu',
 'lomo'=>'/\blomo\b/iu','pieza'=>'/\bpiezas?\b/iu','deshuesado'=>'/\bdeshuesad[oa]s?\b/iu','brida'=>'/\bbrida\b/iu',
 'envasado'=>'/\benvasad[oa]s?\b/iu','vacio'=>'/\bvac[ií]o\b/iu','curacion'=>'/\bcuraci[oó]n\b/iu','meses'=>'/\bmeses\b/iu',
 'precio por'=>'/\bprecio\s+por\b/iu','listos para'=>'/\blistos?\s+para\b/iu','grs'=>'/\bgrs\b/iu','engs'=>'/\bENGS\b/u'
];
$out=['target_residue'=>['total'=>0,'by_vendor'=>[],'hits'=>[]],'hidalgo'=>[]];
foreach($targets as $aid=>$vendor){
  $out['target_residue']['by_vendor'][$vendor]=0;
  $ids=array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$aid)));
  foreach($ids as $id){$p=get_post($id);if(!$p)continue;
    foreach(['_en_US_post_title'=>'title','_en_US_post_excerpt'=>'excerpt','_en_US_post_content'=>'content','_en_US_post_name'=>'slug'] as $key=>$field){
      $value=(string)get_post_meta($id,$key,true);if($value==='')continue;$plain=$field==='slug'?$value:html_entity_decode(wp_strip_all_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8');
      foreach($patterns as $label=>$re){if(!preg_match($re,$plain))continue;preg_match_all('/.{0,100}'.substr($re,1,strrpos($re,'/')-1).'.{0,140}/iu',$plain,$m);$contexts=$m[0]??[];if(!$contexts){$contexts=[mb_substr(trim(preg_replace('/\s+/u',' ',$plain)),0,400)];}$contexts=array_values(array_unique(array_map(static fn($s)=>trim(preg_replace('/\s+/u',' ',$s)),$contexts)));
        $out['target_residue']['total']++;$out['target_residue']['by_vendor'][$vendor]++;$out['target_residue']['hits'][]=['id'=>$id,'vendor'=>$vendor,'native_title'=>$p->post_title,'field'=>$field,'term'=>$label,'contexts'=>array_slice($contexts,0,3)];
      }
    }
  }
}
foreach([1375,1586,4188,5080] as $id){$p=get_post($id);if(!$p)continue;$product=function_exists('wc_get_product')?wc_get_product($id):null;$attrs=[];if($product){foreach($product->get_attributes() as $a){$vals=[];if($a->is_taxonomy()){foreach((array)$a->get_options() as $tid){$t=get_term((int)$tid,$a->get_name());if($t&&!is_wp_error($t))$vals[]=$t->name;}}else{$vals=(array)$a->get_options();}$attrs[]=['name'=>$a->get_name(),'values'=>$vals];}}
  $out['hidalgo'][]=['id'=>$id,'status'=>$p->post_status,'title'=>$p->post_title,'slug'=>$p->post_name,'excerpt'=>$p->post_excerpt,'content'=>$p->post_content,'attributes'=>$attrs,'en_US'=>['published'=>(string)get_post_meta($id,'_en_US_published',true),'title'=>(string)get_post_meta($id,'_en_US_post_title',true),'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),'content'=>(string)get_post_meta($id,'_en_US_post_content',true)]];
}
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
