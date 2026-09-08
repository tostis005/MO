<?php
/** Read-only verifier for EMDO master gap batch 06. */
if ( ! defined( 'ABSPATH' ) ) { exit( 2 ); }
const EMDO_GAP06_VERIFY_MARKER = '2026-09-08.master-gap-06';
const EMDO_GAP06_VERIFY_IMAGE = 13442;
const EMDO_GAP06_VERIFY_COMP_SHA = 'e057e1e087154844a440ba504ba5c8cf0fc0db5525c2a131740a53d8fc9ee83a';
const EMDO_GAP06_VERIFY_RAW_SHA = '9792f7f263b29ad45d162df57403b64e5b45c7b519ba8f17a0da50702c9c0f02';
function emdo_gap06_verify_words(string $html): int { $plain=wp_strip_all_tags(strip_shortcodes($html)); preg_match_all('/\p{L}[\p{L}\p{M}\'’\-]*/u',$plain,$m); return count($m[0]); }
$enc=''; for($i=1;$i<=2;$i++){ $p=__DIR__.'/blog-master-gap-06-'.$i.'.b64'; if(!is_readable($p)) throw new RuntimeException('Missing payload part '.$i); $enc.=trim((string)file_get_contents($p)); }
$comp=base64_decode($enc,true); if(false===$comp || hash('sha256',$comp)!==EMDO_GAP06_VERIFY_COMP_SHA) throw new RuntimeException('Compressed payload validation failed.');
$json=gzdecode($comp); if(false===$json || hash('sha256',$json)!==EMDO_GAP06_VERIFY_RAW_SHA) throw new RuntimeException('Decoded payload validation failed.');
$articles=json_decode($json,true,512,JSON_THROW_ON_ERROR); if(!is_array($articles)||count($articles)!==4) throw new RuntimeException('Expected 4 articles.');
$marker_ids=get_posts(array('post_type'=>'post','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_emdo_master_gap_06','meta_value'=>EMDO_GAP06_VERIFY_MARKER));
$errors=[];$rows=[];$resolved=[];
if(count($marker_ids)!==4) $errors[]='Marker-scoped count='.count($marker_ids).', expected 4';
foreach($articles as $a){
 $slug=(string)$a['slug']; $post=get_page_by_path($slug,OBJECT,'post'); if(!$post instanceof WP_Post){$errors[]='Missing post: '.$slug;continue;}
 $id=(int)$post->ID; $resolved[]=$id; $en_title=(string)get_post_meta($id,'_en_US_post_title',true); $en_slug=(string)get_post_meta($id,'_en_US_post_name',true); $en_content=(string)get_post_meta($id,'_en_US_post_content',true); $es_words=emdo_gap06_verify_words((string)$post->post_content); $en_words=emdo_gap06_verify_words($en_content); $needle='[products category="'.(string)$a['product_cat_slug'].'"';
 if($post->post_status!=='publish')$errors[]='Not published: '.$slug;
 if((string)$post->post_title!==(string)$a['title'])$errors[]='Spanish title mismatch: '.$slug;
 $cat=get_category_by_slug((string)$a['category_slug']); if(!$cat instanceof WP_Term || !has_category((int)$cat->term_id,$id))$errors[]='Category mismatch: '.$slug;
 if((int)get_post_thumbnail_id($id)!==EMDO_GAP06_VERIFY_IMAGE)$errors[]='Thumbnail mismatch: '.$slug;
 if((string)get_post_meta($id,'_emdo_uses_default_featured',true)!=='1')$errors[]='Default image flag missing: '.$slug;
 if((string)get_post_meta($id,'_emdo_master_gap_06',true)!==EMDO_GAP06_VERIFY_MARKER)$errors[]='Batch marker mismatch: '.$slug;
 if((string)get_post_meta($id,'_emdo_editorial_position',true)!==(string)(int)$a['pos'])$errors[]='Position mismatch: '.$slug;
 if($en_title!==(string)$a['en_title'])$errors[]='English title mismatch: '.$slug;
 if($en_slug!==(string)$a['en_slug'])$errors[]='English slug mismatch: '.$slug;
 if((string)get_post_meta($id,'_en_US_published',true)!=='1'||(string)get_post_meta($id,'_en_US_ready',true)!=='1')$errors[]='English flags missing: '.$slug;
 if($es_words<850||$en_words<750)$errors[]='Word count too low: '.$slug.' ES='.$es_words.' EN='.$en_words;
 if(strpos((string)$post->post_content,$needle)===false)$errors[]='Spanish related products mismatch: '.$slug;
 if(strpos($en_content,$needle)===false)$errors[]='English related products mismatch: '.$slug;
 if(trim((string)get_post_meta($id,'rank_math_title',true))==='')$errors[]='SEO title missing: '.$slug;
 if(trim((string)get_post_meta($id,'rank_math_description',true))==='')$errors[]='SEO description missing: '.$slug;
 if(trim((string)get_post_meta($id,'rank_math_focus_keyword',true))!==trim((string)$a['focus_keyword']))$errors[]='Focus keyword mismatch: '.$slug;
 $rows[]=array('position'=>(int)$a['pos'],'id'=>$id,'slug'=>$slug,'en_slug'=>$en_slug,'title'=>(string)$post->post_title,'en_title'=>$en_title,'category_slug'=>(string)$a['category_slug'],'product_cat'=>(string)$a['product_cat_slug'],'es_words'=>$es_words,'en_words'=>$en_words,'thumbnail_id'=>(int)get_post_thumbnail_id($id),'permalink'=>(string)get_permalink($id),'en_permalink'=>(string)home_url('/en/'.trim($en_slug,'/').'/'));
}
sort($resolved); $marker_ids=array_map('intval',$marker_ids); sort($marker_ids); if($resolved!==$marker_ids)$errors[]='Marker scope differs from target IDs.';
usort($rows,static fn($a,$b)=>$a['position']<=>$b['position']);
$result=array('verified'=>empty($errors)&&count($rows)===4,'count'=>count($rows),'default_featured_image_id'=>EMDO_GAP06_VERIFY_IMAGE,'posts'=>$rows,'errors'=>$errors);
$out=wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); file_put_contents('/tmp/emdo-gap06-verify.json',$out."\n"); echo $out."\n"; if(!$result['verified']) throw new RuntimeException('Batch-06 verification failed: '.implode(' | ',$errors));
