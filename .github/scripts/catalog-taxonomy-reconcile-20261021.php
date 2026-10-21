<?php
/**
 * Reconcile proven product families, keeping full category backups per product.
 * WooCommerce and WordPress are bootstrapped by WP-CLI. Audit by default.
 * Does not alter product status, slug, price, inventory, shipping or translations.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI){exit(1);}
$site=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($site,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)){throw new RuntimeException('Production only');}
if(!class_exists('WooCommerce'))throw new RuntimeException('WooCommerce required');
$mode=(string)(getenv('EMDO_CATALOG_CATEGORY_MODE')?:'audit');
if(!in_array($mode,['audit','apply'],true))throw new RuntimeException('Invalid mode');
$specs=[
 'conservas'=>['Conservas',''],
 'mermeladas-y-confituras'=>['Mermeladas y confituras','conservas'],
 'bebidas'=>['Bebidas',''],
 'zumos-nectares-y-limonadas'=>['Zumos, néctares y limonadas','bebidas'],
 'tes-e-infusiones'=>['Tés e infusiones','bebidas'],
 'vinos-y-licores'=>['Vinos y licores','bebidas'],
 'frutos-secos'=>['Frutos secos',''],
 'mieles'=>['Mieles',''],
 'cosmetica-natural'=>['Cosmética natural',''],
 'condimentos-y-especias'=>['Condimentos y especias',''],
];
$desc=[
 'mermeladas-y-confituras'=>'Mermeladas y confituras artesanas, dulces y saladas.',
 'bebidas'=>'Zumos, néctares, infusiones y otras bebidas.',
 'zumos-nectares-y-limonadas'=>'Zumos, néctares y limonadas de distintas frutas y hortalizas.',
 'tes-e-infusiones'=>'Tés e infusiones de distintos sabores y plantas.',
 'vinos-y-licores'=>'Vinos y licores del catálogo.',
 'frutos-secos'=>'Frutos secos y sus surtidos.',
 'mieles'=>'Mieles artesanales y surtidos de miel.',
 'cosmetica-natural'=>'Productos de cosmética natural.',
 'condimentos-y-especias'=>'Sales, especias y condimentos culinarios.',
];
$created=[];
$errors=0;
foreach($specs as $slug=>$info) {
 $term=get_term_by('slug',$slug,'product_cat');
 if($term instanceof WP_Term)continue;
 if($mode!=='apply'){echo 'CATEGORY_WOULD_CREATE='.wp_json_encode(['slug'=>$slug,'parent'=>$info[1]],JSON_UNESCAPED_UNICODE).PHP_EOL;continue;}
 $parent=0;
 if($info[1]){$p=get_term_by('slug',$info[1],'product_cat');if(!$p instanceof WP_Term)throw new RuntimeException('Missing parent '.$info[1]);$parent=(int)$p->term_id;}
 $made=wp_insert_term($info[0],'product_cat',['slug'=>$slug,'parent'=>$parent,'description'=>$desc[$slug]??'']);
 if(is_wp_error($made))throw new RuntimeException('Cannot create '.$slug.': '.$made->get_error_message());
 $created[]=$slug;
}
$cos=get_term_by('slug','cosmetica-natural','product_cat');
$cosReparent=false;
if($cos instanceof WP_Term && (int)$cos->parent>0){
 $cosReparent=true;
 if($mode==='apply') {
  $change=wp_update_term((int)$cos->term_id,'product_cat',['parent'=>0,'description'=>$desc['cosmetica-natural']]);
  if(is_wp_error($change))throw new RuntimeException('Cosmetics reparent error');
 }
}
function emdo_category_slugs($id) {
 $slugs=wp_get_post_terms((int)$id,'product_cat',['fields'=>'slugs']);
 if(is_wp_error($slugs))throw new RuntimeException('Cannot read taxonomy '.$id);
 return array_values(array_unique(array_map('strval',$slugs)));
}
function emdo_gourmet_targets($title){
 $t=mb_strtolower($title);
 if(preg_match('/\b(?:mermeladas?|confituras?)\b/u',$t))return ['conservas','mermeladas-y-confituras'];
 if(preg_match('/\b(?:zumos?|n[eé]ctar|limonada)\b/u',$t))return ['bebidas','zumos-nectares-y-limonadas'];
 if(preg_match('/\b(?:infusi[oó](?:n|nes)|t[eé]\s+(?:verde|negro))\b/u',$t))return ['bebidas','tes-e-infusiones'];
 if(preg_match('/\b(?:miel|mieles)\b/u',$t))return ['mieles'];
 if(preg_match('/\b(?:almendra|almendras|avellana|avellanas|casta[nñ]as?|nueces)\b/u',$t))return ['frutos-secos'];
 if(preg_match('/^aceite\s+de\s+oliva\b/u',$t) && !preg_match('/\b(?:pack|cesta|lote)\b/u',$t))return ['aceites'];
 if(preg_match('/\bd[aá]tiles?\b/u',$t))return ['frutas','frutas-del-mundo'];
 if(preg_match('/\bpat[eé]s?\s+de\s+aceituna\b/u',$t))return ['foie-pates-untables'];
 if(preg_match('/\bpack\s+mediterr[aá]neo\b/u',$t))return ['frutas','hortalizas-verduras','packs-y-lotes'];
 if(preg_match('/\btomates?\s+raf\s+y\s+aceite\b/u',$t))return ['hortalizas-verduras','packs-y-lotes'];
 if(preg_match('/\b(?:cesta\s+(?:desayuno|regalo))\b/u',$t))return ['packs-y-lotes'];
 return [];
}
function emdo_uncategorized_targets($title){
 $t=mb_strtolower(trim($title));
 if(preg_match('/^(?:mermelada|confitura|jalea)\b/u',$t))return ['conservas','mermeladas-y-confituras'];
 if(preg_match('/^miel(?:\s|$)/u',$t))return ['mieles'];
 if(preg_match('/^alubia|^garbanzo|^lenteja/u',$t))return ['legumbres'];
 if(preg_match('/^aceite\s+de\s+oliva/u',$t))return ['aceites'];
 if(preg_match('/^queso\b|^cu[nñ]a\s+de\s+queso/u',$t))return ['quesos'];
 if(preg_match('/^t-bone\s+de\s+ternera\b/u',$t))return ['carnes'];
 if(preg_match('/^(?:gel[eé]e\s+de\s+juran[cç][oó]n|crema\s+de\s+casta[nñ]a)\b/u',$t))return ['conservas','mermeladas-y-confituras'];
 if(preg_match('/^(?:monbazillac|c[oô]tes?\s+de\s+bergerac|vino\b|aguardiente\b|licor\b|crema\s+de\s+orujo)/u',$t))return ['bebidas','vinos-y-licores'];
 if(preg_match('/^(?:tubo\s+(?:sal|pimienta)|reducci[oó]n\s+de\s+pedro)/u',$t))return ['condimentos-y-especias'];
 if(preg_match('/^(?:crema\s+(?:de\s+morcilla|de\s+boletus))/u',$t))return ['foie-pates-untables'];
 if(preg_match('/^(?:pimientos?\s+asados|cebolla\s+caramelizada|chutney\b|agridulce\b|fais[aá]n\s+escabechado|casta[nñ]as\s+al\s+natural)/u',$t))return ['conservas'];
 return [];
}
global $wpdb;
$offset=0;$limit=250;$summary=['mode'=>$mode,'scanned'=>0,'published'=>0,'draft'=>0,'changes'=>0,'gourmet'=>0,'other'=>0,'uncategorized'=>0,'unresolved'=>0,'errors'=>0,'created_terms'=>$created,'cosmetics_reparented'=>$cosReparent,'legacy_gourmet_remaining'=>null];
$seen=[];
do{
 $posts=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status IN ('publish','draft','private','pending','future') ORDER BY ID ASC LIMIT %d OFFSET %d",'product',$limit,$offset));
 foreach($posts as $id) {
  if(isset($seen[$id]))continue;
  $seen[$id]=true;
  $p=wc_get_product($id);if(!$p)continue;
  ++$summary['scanned'];
  $status=$p->get_status();if($status==='publish')++$summary['published'];if($status==='draft')++$summary['draft'];
  $original=emdo_category_slugs($id);
  $new=$original;$reason='';
  $name=(string)$p->get_name();
  $src=(string)get_post_meta($id,'_emdo_source_url',true);
  $isTropitop=in_array(strtolower((string)wp_parse_url($src,PHP_URL_HOST)),['tropitop.com','www.tropitop.com'],true);
  try{
   if(in_array('gourmet',$original,true) && $isTropitop) {
    $targets=emdo_gourmet_targets($name);
    if(!$targets)throw new RuntimeException('Unclassified gourmet title');
    $new=array_values(array_diff($original,['gourmet']));
    $new=array_values(array_unique(array_merge($new,$targets)));
    $reason='gourmet';
   }elseif(in_array('gourmet',$original,true)) {
    throw new RuntimeException('Gourmet belongs to another vendor, requires manual review');
   }
   if(in_array('conservas',$new,true) && preg_match('/\b(?:mermelada|confitura)\b/iu',$name)) {
    $new[]='mermeladas-y-confituras';$reason=$reason?:'existing_conservas';
   }
   if(count($new)===1 && in_array('sin-categorizar',$new,true)) {
    $target=emdo_uncategorized_targets($name);
    if($target) {
      $new=$target;$reason='uncategorized_draft';
    }else{
      ++$summary['unresolved'];
      echo 'CATEGORY_REVIEW='.wp_json_encode(['id'=>$id,'status'=>$status,'title'=>$name,'reason'=>'uncategorized; needs manual verification'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
   }
   if($isTropitop && in_array('frutas-del-mundo',$new,true) && preg_match('/^(?:yuca|c[uú]rcuma|jengibre)\b/iu',$name)) {
     $new=array_values(array_diff($new,['frutas-del-mundo','frutas']));
     $new[]='hortalizas-verduras';$reason='roots_not_fruit';
   }
   if($isTropitop && in_array('frutas-tropicales',$new,true) && preg_match('/^granadas?\b/iu',$name)) {
     $new=array_values(array_diff($new,['frutas-tropicales']));
     $new[]='frutas';$reason='granada_as_fruit';
   }
   $new=array_values(array_unique($new));
   sort($original);sort($new);
   if($original===$new)continue;
   $summary['changes']++;
   if($reason==='gourmet')$summary['gourmet']++;
   elseif($reason==='uncategorized_draft')$summary['uncategorized']++;
   else $summary['other']++;
   echo 'CATEGORY_PRODUCT='.wp_json_encode(['id'=>$id,'name'=>$name,'status'=>$status,'from'=>$original,'to'=>$new,'reason'=>$reason],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
   if($mode!=='apply')continue;
   $termIds=[];
   foreach($new as $slug) {
    $term=get_term_by('slug',$slug,'product_cat');
    if(!$term instanceof WP_Term)throw new RuntimeException('Desired category missing '.$slug);
    $termIds[]=(int)$term->term_id;
   }
   if(!metadata_exists('post',$id,'_emdo_taxonomy_backup_20261021'))add_post_meta($id,'_emdo_taxonomy_backup_20261021',$original,true);
   $saved=wp_set_object_terms($id,$termIds,'product_cat',false);
   if(is_wp_error($saved))throw new RuntimeException('Category save failed '.$saved->get_error_message());
   clean_post_cache($id);
   $actual=emdo_category_slugs($id);sort($actual);
   if($actual!==$new)throw new RuntimeException('Post-save taxonomy mismatch');
  }catch(Throwable $e){
   ++$summary['errors'];
   echo 'CATEGORY_ERROR='.wp_json_encode(['id'=>$id,'title'=>$name,'reason'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
  }
 }
 $offset+=$limit;
}while(count($posts)===$limit && $offset<12000);
$legacy=get_term_by('slug','gourmet','product_cat');
$remaining=$legacy instanceof WP_Term?get_objects_in_term((int)$legacy->term_id,'product_cat'):[];
$summary['legacy_gourmet_remaining']=is_wp_error($remaining)?-1:count((array)$remaining);
if($mode==='audit' && !is_wp_error($remaining) && $remaining) {
 foreach(array_slice((array)$remaining,0,75) as $legacyId) {
   $related=get_post((int)$legacyId);
   echo 'CATEGORY_LEGACY_OBJECT='.wp_json_encode(['id'=>(int)$legacyId,'type'=>$related?$related->post_type:'not_post','status'=>$related?$related->post_status:'missing','title'=>$related?$related->post_title:'missing','was_scanned'=>isset($seen[$legacyId]),'slugs'=>emdo_category_slugs((int)$legacyId)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
 }
}
$children=$legacy instanceof WP_Term?get_terms(['taxonomy'=>'product_cat','parent'=>(int)$legacy->term_id,'hide_empty'=>false]):[];
$summary['legacy_gourmet_children']=is_wp_error($children)?-1:count((array)$children);
$summary['gourmet_deleted']=false;
if($mode==='apply'&&$legacy instanceof WP_Term && $summary['legacy_gourmet_remaining']===0 && $summary['legacy_gourmet_children']===0 && $summary['errors']===0){
 $deleted=wp_delete_term((int)$legacy->term_id,'product_cat');
 $summary['gourmet_deleted']=!is_wp_error($deleted)&&(bool)$deleted;
}
echo 'CATEGORY_SUMMARY='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($summary['errors']>0)throw new RuntimeException('Taxonomy safety errors');
