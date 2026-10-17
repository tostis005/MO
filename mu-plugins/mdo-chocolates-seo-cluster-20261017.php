<?php
/**
 * Plugin Name: El Mercado de Origen — Chocolates: SEO editorial dinámico
 * Description: Relación bidireccional entre las 30 guías y productos disponibles, sin modificar fichas.
 * Version: 2026.10.17
 */
if (!defined('ABSPATH')) exit;
function emdo_choco_en(): bool {
  return (bool)preg_match('~^/en(?:/|$)~i', (string)wp_parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH));
}
function emdo_choco_articles(): array {
  return get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>40,
    'meta_key'=>'_emdo_chocolate_release','meta_value'=>'emdo-chocolates-isabel-20261017-30-bilingual',
    'suppress_filters'=>true]);
}
function emdo_choco_post_url(int $id): string {
  if(!emdo_choco_en())return (string)get_permalink($id);
  $slug=sanitize_title((string)get_post_meta($id,'_en_US_post_name',true));
  return $slug?home_url('/en/'.$slug.'/'):'';
}
function emdo_choco_post_title(int $id): string {
  $title=emdo_choco_en()?(string)get_post_meta($id,'_en_US_post_title',true):'';
  return $title?:get_the_title($id);
}
function emdo_choco_tokens(): array {
  return [
    1=>['tableta'],2=>['bean to bar','tableta'],3=>['negro','75','85','100'],4=>['cobertura','gota','chip'],
    5=>['nib'],6=>['cascarilla'],7=>[],8=>[],9=>['regalo','pack','bombon'],10=>['turron'],
    11=>['tableta'],12=>['blanco','leche','negro'],13=>['peru','origen','piura'],14=>['bio','eco'],
    15=>[],16=>['vegano'],17=>['100','sin azucar'],18=>['oliva'],19=>['cacao polvo'],
    20=>['pasta','manteca','cacao polvo'],21=>['manteca'],22=>['cacao polvo','cacao puro'],
    23=>['cobertura','gota'],24=>['cobertura','gota'],25=>['cacao puro','cacao en polvo','chocolate a la taza','cacao natural'],
    26=>['chocolate negro'],27=>['cobertura','bombon'],28=>['tableta','bean to bar'],
    29=>['bombon'],30=>['personaliz','regalo','pack']
  ];
}
function emdo_choco_categories(): array {
  if(!taxonomy_exists('product_cat'))return [];
  $slugs=['chocolates-artesanos','tabletas-de-chocolate','bombones-y-chocolatinas','cremas-de-cacao','cacao-para-beber-y-cocinar'];
  return array_values(array_filter($slugs,static function($slug){return get_term_by('slug',$slug,'product_cat') instanceof WP_Term;}));
}
function emdo_choco_related_products(int $topic): array {
  if(!function_exists('wc_get_products'))return [];
  $cats=emdo_choco_categories();if(!$cats)return [];
  $items=wc_get_products(['status'=>'publish','category'=>$cats,'limit'=>90,'orderby'=>'popularity']);
  $tokens=emdo_choco_tokens()[$topic]??[];
  $strict=in_array($topic,[5,6,10,13,14,15,16,17,18,19,20,21,22,23,24,25,27,29,30],true);
  $ranked=[];
  foreach($items as $p) {
    if(!$p instanceof WC_Product || !$p->is_visible() || !$p->is_purchasable() || !$p->is_in_stock() || $p->get_stock_status()!=='instock')continue;
    $value=remove_accents(mb_strtolower($p->get_name().' '.$p->get_slug()));
    $score=0;
    foreach($tokens as $token)if(str_contains($value,remove_accents($token)))$score+=5;
    if($strict && !$score)continue;
    $ranked[]=['p'=>$p,'score'=>$score];
  }
  usort($ranked,static fn($a,$b)=>$b['score']<=>$a['score']);
  return array_slice(array_column($ranked,'p'),0,4);
}
function emdo_choco_links(array $articles): string {
  $items='';
  foreach($articles as $p) {
    if(get_post_status($p->ID)!=='publish')continue;
    $url=emdo_choco_post_url((int)$p->ID);if(!$url)continue;
    $items.='<li><a href="'.esc_url($url).'">'.esc_html(emdo_choco_post_title((int)$p->ID)).'</a></li>';
  }
  return $items?'<ul class="emdo-choco-guides-list">'.$items.'</ul>':'';
}
add_filter('the_content',static function($html) {
  if(is_admin()||is_feed()||!is_singular('post')||!in_the_loop()||!is_main_query())return $html;
  $id=(int)get_the_ID();
  if(get_post_meta($id,'_emdo_chocolate_release',true)!=='emdo-chocolates-isabel-20261017-30-bilingual')return $html;
  $html=preg_replace('/\\[elmercado_dynamic_products\\b[^\\]]*\\]/i','',$html);
  $topic=(int)get_post_meta($id,'_emdo_chocolate_topic',true);
  $products=emdo_choco_related_products($topic);
  if($products) {
    $cards='';
    foreach($products as $product) {
      $pid=$product->get_id();
      $cards.='<li><a href="'.esc_url(get_permalink($pid)).'">'.get_the_post_thumbnail($pid,'woocommerce_thumbnail',['loading'=>'lazy']).'<span>'.esc_html($product->get_name()).'</span></a><span>'.$product->get_price_html().'</span></li>';
    }
    $html.='<section data-emdo-choco-products="1" class="emdo-choco-grid"><h2>'.esc_html(emdo_choco_en()?'Available chocolate products':'Chocolates disponibles').'</h2><ul>'.$cards.'</ul></section>';
  }
  $n=$topic;$matches=[];
  foreach(emdo_choco_articles() as $p) {
    if((int)$p->ID===$id)continue;
    $pn=(int)get_post_meta($p->ID,'_emdo_chocolate_topic',true);
    $score=20-abs($pn-$n);
    if(($n<=18&&$pn<=18)||($n>=19&&$pn>=19))$score+=10;
    $matches[]=['p'=>$p,'score'=>$score];
  }
  usort($matches,static fn($a,$b)=>$b['score']<=>$a['score']);
  $links=emdo_choco_links(array_slice(array_column($matches,'p'),0,3));
  if($links)$html.='<nav class="emdo-choco-guides" aria-label="Chocolate"><h2>'.esc_html(emdo_choco_en()?'More chocolate guides':'Más sobre chocolate').'</h2>'.$links.'</nav>';
  return $html;
},10);
add_action('woocommerce_after_single_product_summary',static function(): void {
  if(!is_product()||!function_exists('wc_get_product'))return;
  $p=wc_get_product(get_the_ID());if(!$p)return;
  $cats=emdo_choco_categories();if(!$cats)return;
  $allowed=has_term($cats,'product_cat',$p->get_id());
  if(!$allowed)foreach(wp_get_post_terms($p->get_id(),'product_cat',['fields'=>'ids']) as $term_id){
    foreach($cats as $slug){$parent=get_term_by('slug',$slug,'product_cat');
      if($parent instanceof WP_Term && term_is_ancestor_of($parent->term_id,$term_id,'product_cat')){$allowed=true;break 2;}
    }
  }
  if(!$allowed)return;
  $label=remove_accents(mb_strtolower($p->get_name().' '.$p->get_slug()));
  $matches=[];
  foreach(emdo_choco_articles() as $article) {
    $topic=(int)get_post_meta($article->ID,'_emdo_chocolate_topic',true);$score=in_array($topic,[1,2,3],true)?2:0;
    foreach(emdo_choco_tokens()[$topic]??[] as $token)if(str_contains($label,remove_accents($token)))$score+=8;
    $matches[]=['p'=>$article,'score'=>$score];
  }
  usort($matches,static fn($a,$b)=>$b['score']<=>$a['score']);
  $links=emdo_choco_links(array_slice(array_column($matches,'p'),0,3));
  if(!$links)return;
  echo '<section class="emdo-choco-guides" data-emdo-choco-product-guides="1"><h2>'.
    esc_html(emdo_choco_en()?'Chocolate guides and recipes':'Guías y recetas sobre chocolate').'</h2>'.$links.'</section>';
},18);
add_action('wp_head',static function(){
  if(!is_singular(['post','product']))return;
  echo '<style id="emdo-choco-cluster">.emdo-choco-grid,.emdo-choco-guides{margin:32px 0;padding:20px 0;border-top:1px solid #ddd}.emdo-choco-grid ul{list-style:none;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.emdo-choco-grid img{display:block;aspect-ratio:1;object-fit:contain;width:100%;height:auto}.emdo-choco-grid li span{display:block}.emdo-choco-guides li{margin:8px 0}@media(max-width:800px){.emdo-choco-grid ul{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:480px){.emdo-choco-grid ul{grid-template-columns:1fr}}</style>';
},99);
add_action('woocommerce_product_set_stock_status',static function($id) {
  if(!function_exists('rocket_clean_post'))return;
  foreach(emdo_choco_articles() as $post)rocket_clean_post((int)$post->ID);
},10,1);

/** Archive-to-archive entry points; no stored post or product descriptions changed. */
add_filter('get_the_archive_description',static function($description) {
  if(is_admin()||!is_category('chocolates-y-cacao'))return $description;
  $term=get_term_by('slug','chocolates-artesanos','product_cat');
  if(!$term instanceof WP_Term)return $description;
  $url=get_term_link($term);if(is_wp_error($url)||!$url)return $description;
  $label=emdo_choco_en()?'Explore chocolate products':'Ver chocolates artesanos disponibles';
  $description.='<p class="emdo-choco-archive-link"><a href="'.esc_url($url).'">'.esc_html($label).' &rarr;</a></p>';
  return $description;
},20);
add_action('woocommerce_archive_description',static function(): void {
  if(!is_product_category(emdo_choco_categories()))return;
  $term=get_category_by_slug('chocolates-y-cacao');if(!$term instanceof WP_Term)return;
  $url=get_category_link((int)$term->term_id);
  if(emdo_choco_en()) {
    foreach(emdo_choco_articles() as $p) {
      if((int)get_post_meta($p->ID,'_emdo_chocolate_topic',true)===1) {
        $url=emdo_choco_post_url((int)$p->ID);break;
      }
    }
  }
  if(is_wp_error($url)||!$url)return;
  echo '<div class="emdo-choco-archive-link"><a href="'.esc_url($url).'">'.
    esc_html(emdo_choco_en()?'Read our chocolate guides':'Leer las guías de chocolates y cacao').
    ' &rarr;</a></div>';
},30);
