<?php
/**
 * B04 targeted Setas y hongos category copy, idempotent with backup.
 * Scope: Spanish product_cat term 541; no products, prices, URL or translations.
 */
if (!defined('ABSPATH')) {exit(2);}
$mode=(string)getenv('EMDO_SEO_MODE');
$backup=(string)getenv('EMDO_SEO_BACKUP');
$old_sha='e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
$term=get_term_by('slug','setas-y-hongos','product_cat');
if (!$term instanceof WP_Term || (int)$term->term_id!==541) {fwrite(STDERR,"SETAS_TERM_MISMATCH\n");exit(3);}
$old=(string)$term->description;
$slugs=array(
  'setas-frescas'=>'setas frescas',
  'setas-deshidratadas'=>'setas deshidratadas',
  'conservas-de-setas'=>'conservas y elaborados de setas',
  'entresetas-harinas-sales'=>'harinas y sales de setas',
  'infusiones-y-tes-de-setas'=>'infusiones y tés de setas',
  'extractos-de-setas'=>'extractos de setas',
);
$links=array();
foreach($slugs as $slug=>$label){
    $child=get_term_by('slug',$slug,'product_cat');
    if(!$child instanceof WP_Term || (int)$child->parent!==(int)$term->term_id){
        fwrite(STDERR,"MISSING_SETAS_SUBCATEGORY: $slug\n"); exit(4);
    }
    $url=get_term_link($child);
    if(is_wp_error($url) || !str_starts_with((string)$url,'https://www.elmercadodeorigen.com/')){
        fwrite(STDERR,"INVALID_SETAS_SUBCATEGORY_URL: $slug\n");exit(5);
    }
    $links[]='<a href="'.esc_url($url).'">'.esc_html($label).'</a>';
}
$intro='<p>Compra <strong>setas y hongos online</strong> a productores especializados. Encuentra setas frescas y deshidratadas, conservas artesanas y otras preparaciones para cocinar, además de productos elaborados con hongos. Compara cada ficha por variedad, presentación, origen y disponibilidad.</p>'."\n\n"
    .'<p><strong>Elige el formato que necesitas:</strong> '.implode(' · ',$links).'. Las setas frescas resultan ideales para cocinar al momento; las deshidratadas son prácticas para tenerlas en la despensa y rehidratarlas según el plato. Consulta la conservación y las instrucciones de cada referencia.</p>'."\n\n"
    .'<p>Explora la selección de <strong>EntreSetas</strong> y comprueba los formatos disponibles, el precio y las condiciones de entrega antes de comprar. Los productos y las existencias pueden variar según la temporada.</p>';
if (in_array($mode,array('dryrun','apply'),true)) {
   if(hash('sha256',$old)!==$old_sha){fwrite(STDERR,"SETAS_DESCRIPTION_HAS_CHANGED\n");exit(6);}
   if($mode==='dryrun'){echo "SETAS_DRYRUN_OK new_bytes=".strlen($intro)." subcategories=".count($links)."\n";return;}
   if($backup===''||strpos($backup,'/tmp/seo-setas-20261105-')!==0||file_exists($backup)){exit(7);}
   if(file_put_contents($backup,$old,LOCK_EX)===false){exit(8);}
   chmod($backup,0600);
   $res=wp_update_term((int)$term->term_id,'product_cat',array('description'=>wp_slash($intro)));
   if(is_wp_error($res)){fwrite(STDERR,$res->get_error_message()."\n");exit(9);}
   clean_term_cache($term->term_id,'product_cat');
   echo "SETAS_CATEGORY_APPLIED\n";return;
}
if($mode==='verify'){
    $d=(string)get_term_by('slug','setas-y-hongos','product_cat')->description;
    if(!str_contains($d,'Compra <strong>setas y hongos online</strong>') ||
       !str_contains($d,'EntreSetas') || substr_count($d,'/categoria-producto/')<6) {
       fwrite(STDERR,"SETAS_CATEGORY_VERIFY_FAILED\n");exit(10);
    }
    echo "SETAS_CATEGORY_DB_VERIFIED\n";return;
}
if($mode==='rollback'){
   if($backup===''||!is_file($backup)){exit(11);}
   $previous=(string)file_get_contents($backup);
   if(hash('sha256',$previous)!==$old_sha){exit(12);}
   $res=wp_update_term((int)$term->term_id,'product_cat',array('description'=>wp_slash($previous)));
   if(is_wp_error($res)){exit(13);}
   clean_term_cache($term->term_id,'product_cat');
   echo "SETAS_CATEGORY_ROLLED_BACK\n";return;
}
exit(15);
