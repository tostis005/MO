<?php
/** Regression: native category description must not be duplicated on three GSC archives. */
define( 'ABSPATH', __DIR__ );
class WP_Term { public $term_id = 9; public $taxonomy = 'product_cat'; public $description = 'Una descripción española oficial.'; }
$GLOBALS['test_category'] = 'legumbres';
function wp_unslash($v) { return $v; }
function add_action($hook,$callback,$priority=10) {}
function is_paged() { return false; }
function is_product_category($slugs=null) {
    if ($slugs===null) return true;
    return in_array($GLOBALS['test_category'], (array)$slugs, true);
}
function get_queried_object() { return new WP_Term(); }
function get_term_meta($id,$key,$single) { return $key === '_en_US_published' ? '1' : 'Approved English description'; }
function wp_kses_post($v) { return $v; }
function get_bloginfo($key) { return 'UTF-8'; }
function wp_strip_all_tags($v) { return strip_tags($v); }
function esc_html($v) { return htmlspecialchars($v,ENT_QUOTES,'UTF-8'); }
function esc_attr($v) { return esc_html($v); }
function wpautop($v) { return '<p>'.$v.'</p>'; }
require __DIR__ . '/../mu-plugins/emdo-category-seo-description.php';
foreach (array('legumbres','aceites','jamones-paletas','cafe-de-especialidad','cremas-de-cacao') as $slug) {
    $GLOBALS['test_category']=$slug;
    $_SERVER['REQUEST_URI']='/categoria-producto/'.$slug.'/';
    ob_start(); emdo_category_seo_render_description('above'); emdo_category_seo_render_description('below');
    $html=ob_get_clean();
    if ($html!=='') { fwrite(STDERR,"FAIL duplicate ES category $slug\n");exit(1); }
}
$GLOBALS['test_category']='conservas';
$_SERVER['REQUEST_URI']='/categoria-producto/conservas/';
ob_start(); emdo_category_seo_render_description('above');
$other=ob_get_clean();
if (strpos($other,'data-emdo-category-description=')===false) {fwrite(STDERR,"FAIL other ES preserved\n");exit(1);}
$GLOBALS['test_category']='aceites';
$_SERVER['REQUEST_URI']='/en/product-category/oils/';
ob_start(); emdo_category_seo_render_description('above');
$en=ob_get_clean();
if (strpos($en,'Approved English description')===false) {fwrite(STDERR,"FAIL EN preserved\n");exit(1);}
echo 'PASS ES native descriptions not duplicated, other archives/EN remain visible' . PHP_EOL;
