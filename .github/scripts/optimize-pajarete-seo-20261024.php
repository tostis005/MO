<?php
/**
 * Publish-safe SEO finishing pass for Quesos Pajarete:
 * - AIOSEO native table titles and meta descriptions, only active published items.
 * - Real source-linked image ALT strings for all gallery attachments.
 * - Source-grounded product-specific short copy when source copy is brief or repeated.
 * - Source long descriptions / ingredients / allergens / nutrition unchanged.
 * Modes audit, apply, verify. One supplier/vendor only; no prices, variants,
 * slugs, stock, shipping, public/excluded state or other vendors are changed.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI ||
    !class_exists('WooCommerce') || !class_exists('MDO_Database') ||
    !class_exists('MDO_Connector_Quesos_Pajarete')) throw new RuntimeException('Expected Woo/EMDO CLI context');
foreach (['home','siteurl'] as $key) {
    $host=strtolower((string)wp_parse_url((string)get_option($key),PHP_URL_HOST));
    if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Only production');
}
global $wpdb;
$suppliers=MDO_Database::table('suppliers');$source_table=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if (!$s || (int)$s['vendor_user_id']!==4974)throw new RuntimeException('Pajarete identity mismatch');
$aio=$wpdb->prefix.'aioseo_posts';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$aio))!==$aio)throw new RuntimeException('AIOSEO native table absent');
$mode=getenv('PAJARETE_SEO_PHASE')?:'audit';
if (!in_array($mode,['audit','apply','verify'],true))throw new RuntimeException('Invalid SEO phase');
$src=$wpdb->get_results($wpdb->prepare("SELECT id,title,status,source_url,source_payload,wc_product_id FROM {$source_table} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
if (count($src)!==134) throw new RuntimeException('Pajarete catalogue changed, re-audit');
$published=[];$skipped=['excluded'=>0,'pending'=>0,'other'=>0,'photo_review'=>0];
$short_hashes=[];
$clean=static function(string $html):string {
    $text=html_entity_decode(wp_strip_all_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8');
    return trim((string)preg_replace('/\s+/u',' ', $text));
};
$compact=static function(string $text,int $limit) use ($clean):string {
    $text=$clean($text);
    if(mb_strlen($text)<=$limit)return $text;
    $cut=mb_substr($text,0,$limit+1);
    $last=mb_strrpos($cut,' ');
    if($last!==false && $last>$limit*0.65) $cut=mb_substr($cut,0,$last);
    else $cut=mb_substr($cut,0,$limit);
    return rtrim($cut," ,;:.-") . '…';
};
foreach($src as $r){
    if($r['status']==='excluded'){$skipped['excluded']++;continue;}
    if(!$r['wc_product_id']){$skipped['pending']++;continue;}
    $id=(int)$r['wc_product_id'];$p=wc_get_product($id);
    if(!$p||(int)get_post_field('post_author',$id)!==4974)throw new RuntimeException('Invalid vendor product '.$id);
    // The official source itself flags this particular wine photo as
    // "FOTO NO ES?". Keep it out of public sale until a verified image
    // is supplied; do not optimize a possibly misrepresented listing.
    if($id===21483){
        $suspicious=json_decode((string)$r['source_payload'],true);
        $note=is_array($suspicious)?$clean((string)($suspicious['short_description']??'')):'';
        if($note!=='FOTO NO ES?' || !str_contains(remove_accents(mb_strtolower($p->get_name())),'salvador rivero')){
            throw new RuntimeException('Wine photo hold mismatch, requires manual review');
        }
        if($mode==='apply'){
            $p->set_status('draft');
            $p->set_catalog_visibility('hidden');
            $p->save();
            update_post_meta($id,'_mdo_pajarete_photo_validation_required','1');
            update_post_meta($id,'_mdo_pajarete_photo_validation_reason','La fuente oficial indica "FOTO NO ES?"; solicitar foto correcta del vino.');
        }
        if($mode==='verify' && ($p->get_status()!=='draft'||$p->get_catalog_visibility()!=='hidden')){
            throw new RuntimeException('Suspect source photo must not remain published '.$id);
        }
        $skipped['photo_review']++;
        continue;
    }
    if($r['status']!=='active'||$p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible'){
        $skipped['other']++;continue;
    }
    if(MDO_Connector_Quesos_Pajarete::excluded((string)$r['source_url'],(string)$p->get_name())){
        throw new RuntimeException('Excluded product unexpectedly public '.$id);
    }
    if($p->is_type('variation')) throw new RuntimeException('Unexpected variation as parent '.$id);
    $data=json_decode((string)$r['source_payload'],true);
    if(!is_array($data)||empty($data['source_api_product_id'])||empty($data['images']))
        throw new RuntimeException('Unverified source payload '.$id);
    $raw_short=(string)($data['short_description']??'');
    $raw_long=(string)($data['description']??'');
    if(mb_strlen($clean($raw_long))<45||mb_strlen($clean($raw_short))<12)
        throw new RuntimeException('Incomplete source descriptions '.$id);
    $cover=$p->get_image_id();$all=array_unique(array_map('intval',array_merge([$cover],$p->get_gallery_image_ids())));
    if(!$cover||!get_attached_file($cover)||!is_file(get_attached_file($cover)))
        throw new RuntimeException('Incomplete original media '.$id);
    $map=json_decode((string)get_post_meta($id,'_emdo_image_map',true),true);
    if(!is_array($map))throw new RuntimeException('Original media source map missing '.$id);
    $images=array_values(array_unique(array_filter((array)$data['images'],'is_string')));
    $attached=[];
    foreach($images as $url){
        $aid=(int)($map[$url]??0);
        if(!$aid||!in_array($aid,$all,true)||!get_attached_file($aid)||!is_file(get_attached_file($aid)))
            throw new RuntimeException('Incomplete original gallery '.$id.' at '.$url);
        $attached[$aid]=$url;
    }
    $aio_row=$wpdb->get_row($wpdb->prepare("SELECT post_id,title,description,canonical_url,robots_noindex FROM {$aio} WHERE post_id=%d LIMIT 1",$id),ARRAY_A);
    if(!$aio_row)throw new RuntimeException('Missing SEO database entry '.$id);
    if((int)$aio_row['robots_noindex']!==0)throw new RuntimeException('Unexpected noindex '.$id);
    $published[$id]=['product'=>$p,'source'=>$r,'payload'=>$data,'aio'=>$aio_row,'imgs'=>$attached];
    $short_hashes[md5($clean($raw_short))][]=$id;
}
if(count($published)!==107||$skipped!==['excluded'=>22,'pending'=>4,'other'=>0,'photo_review'=>1])
    throw new RuntimeException('Unexpected publication/exclusion counts: '.wp_json_encode(['count'=>count($published),'skip'=>$skipped]));
$plans=[];$all_media=[];$new_shorts=0;
foreach($published as $id=>$r){
    $p=$r['product'];$data=$r['payload'];$title=$clean($p->get_name());
    $original_short=$clean((string)$data['short_description']);
    $long=$clean((string)$data['description']);
    // Keep the full marketplace name in the SEO title; abbreviate only
    // overly long product names, not the brand or its separator.
    $brand=' | El Mercado de Origen';
    $seo_title=$compact($title,68-mb_strlen($brand)).$brand;
    // The three "Nuestra Señora de los Ángeles" oil SKUs share a long
    // prefix. Keep each verified bottle volume at the start of its SEO
    // title rather than losing it through truncation.
    if(in_array($id,[21491,21492,21493],true)){
        $plain=remove_accents(mb_strtolower($title));
        if(!str_contains($plain,'nuestra senora de los angeles')||
           !preg_match('/\\b(2\\s*l|500\\s*ml|750\\s*ml)$/iu',$title,$match)){
            throw new RuntimeException('Expected original AOVE volume absent '.$id);
        }
        $seo_title='AOVE Nuestra Señora de los Ángeles '.$match[1].$brand;
    }
    // Each meta description uses actual source product copy. Reserve room
    // for the complete store name rather than truncating half a phrase.
    $detail=$original_short;
    if(mb_strlen($detail)<62){
       $source_sentences=preg_split('/(?<=[.!?])\\s+/u',$long,2);
       $source_first=$clean((string)($source_sentences[0]??$long));
       if(mb_strlen($source_first)>mb_strlen($detail))$detail=$source_first;
    }
    $detail=str_replace(['nuestros quesos y cremas','Nuestros quesos y cremas'],
                        ['quesos y cremas','quesos y cremas'],$detail);
    $preface=$p->get_stock_status()==='instock'?'Descubre ':'Conoce ';
    $meta_intro=$preface.$title.'. ';
    $meta_end=' En El Mercado de Origen.';
    if(mb_strlen($meta_intro)+mb_strlen($meta_end)>106)
        $meta_intro=$preface.$compact($title,70).'. ';
    $detail_limit=max(35,157-mb_strlen($meta_intro)-mb_strlen($meta_end));
    $meta_desc=$meta_intro.$compact($detail,$detail_limit).$meta_end;
    if(mb_strlen($seo_title)<15||mb_strlen($meta_desc)<40)throw new RuntimeException('Invalid SEO text '.$id);
    $duplicate=count($short_hashes[md5($original_short)]??[])>1;
    $short_to_write='';
    if(mb_strlen($original_short)<72 || $duplicate){
        // Add only a clear product-specific intro to the *original* synopsis.
        // Long source descriptions with ingredients/nutrition stay untouched.
        $label=esc_html($title);
        $source_markup=wp_kses_post((string)$data['short_description']);
        $short_to_write='<p><strong>'.$label.'</strong>: '.esc_html($original_short).'</p>';
        if(mb_strlen($clean($short_to_write))>470){
            $short_to_write='<p><strong>'.$label.'</strong>: '.esc_html($compact($original_short,350)).'</p>';
        }
        $new_shorts++;
    }
    $attachments=[];
    $i=0;
    foreach($r['imgs'] as $aid=>$url){
        $i++;
        $label='';
        $file=(string)wp_parse_url($url,PHP_URL_PATH);
        $name=pathinfo($file,PATHINFO_FILENAME);
        $name=trim((string)preg_replace('/[_\-]+/u',' ',(string)$name));
        $name=trim((string)preg_replace('/\b(?:menos|mb|jpeg|jpg|png|webp|scaled|image|foto|img)\b/iu','',$name));
        $name=trim((string)preg_replace('/\s+/u',' ',$name));
        // Filename clues such as CURADO CUÑA / CURADO MEDIO are from the
        // manufacturer's original images, not our invented visual labels.
        if(mb_strlen($name)>=4 && mb_strlen($name)<=65 &&
           !preg_match('/^(?:dsc|img|image|whatsapp|screenshot|captura|untitled|foto\s*\d+)\b/iu',$name)){
            $label=$name;
        }
        $base=$compact($title,82);
        $alt=$base;
        if($label && mb_strtolower(remove_accents($label))!==mb_strtolower(remove_accents($title))){
           $alt=$compact($base.' – '.$label,117);
        } elseif($i>1) {
           $alt=$compact($base.' – otra vista',117);
        }
        $old=(string)get_post_meta((int)$aid,'_wp_attachment_image_alt',true);
        $attachments[$aid]=['alt'=>$alt,'current'=>$old,'url'=>$url];
        if(isset($all_media[$aid]) && $all_media[$aid]['alt']!==$alt)
            throw new RuntimeException('Shared source image with conflicting SEO alt '.$aid);
        $all_media[$aid]=['alt'=>$alt,'current'=>$old,'parent'=>$id];
    }
    $plans[$id]=['title'=>$seo_title,'description'=>$meta_desc,
       'short'=>$short_to_write,'original_short_sha256'=>hash('sha256',(string)$data['short_description']),
       'media'=>$attachments,'long_source_chars'=>mb_strlen($long),
       'original_aio'=>$r['aio'],'current_short'=>$p->get_short_description(),
       'product_name'=>$p->get_name(),'status'=>$p->get_status()];
}
// Verify every SEO title and snippet remains distinct before any writes.
$unique_titles=[];$unique_descs=[];
foreach($plans as $id=>$plan){
    if(isset($unique_titles[$plan['title']]))throw new RuntimeException('Duplicate SEO titles: '.$id.'/'.$unique_titles[$plan['title']]);
    if(isset($unique_descs[$plan['description']]))throw new RuntimeException('Duplicate SEO snippets: '.$id.'/'.$unique_descs[$plan['description']]);
    $unique_titles[$plan['title']]=$id;
    $unique_descs[$plan['description']]=$id;
}
$option='_mdo_pajarete_full_seo_rollback_20261024';
if($mode==='audit'){
    echo 'PAJARETE_SEO_AUDIT='.wp_json_encode([
        'eligible'=>count($plans),'skipped'=>$skipped,'image_alt_targets'=>count($all_media),
        'editorial_short_targets'=>$new_shorts,
        'aioseo_table'=>$aio,'meta_title_targets'=>count($plans),
        'sample'=>array_slice(array_map(static fn($p)=>[
            'name'=>$p['product_name'],'seo_title'=>$p['title'],
            'seo_description'=>$p['description'],'short_optimized'=>(bool)$p['short'],
            'images'=>count($p['media'])],$plans),0,7)
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
if($mode==='apply'){
    if(!get_option($option,false)){
        $backup=['products'=>[],'alts'=>[]];
        foreach($plans as $id=>$p){
            $backup['products'][$id]=[
                'aio_title'=>$p['original_aio']['title'],
                'aio_description'=>$p['original_aio']['description'],
                'short'=>$p['current_short'],
                'editorial_short'=>get_post_meta($id,'_mdo_pajarete_seo_editorial_short',true),
                'editorial_source_sha'=>get_post_meta($id,'_mdo_pajarete_seo_short_source_sha256',true),
                'approved'=>get_post_meta($id,'_mdo_pajarete_seo_short_approved',true)];
        }
        foreach($all_media as $aid=>$m)$backup['alts'][$aid]=$m['current'];
        if(!add_option($option,$backup,'','no'))throw new RuntimeException('Could not save SEO rollback snapshot');
    }
    foreach($plans as $id=>$plan){
        $result=$wpdb->update($aio,['title'=>$plan['title'],'description'=>$plan['description']],
                             ['post_id'=>$id],['%s','%s'],['%d']);
        if(false===$result)throw new RuntimeException('AIOSEO write failed '.$id.' '.$wpdb->last_error);
        if($plan['short']){
            if(wp_kses_post((string)$plan['short'])!==wp_kses_post((string)wc_get_product($id)->get_short_description())){
                $updated=wp_update_post(['ID'=>$id,'post_excerpt'=>$plan['short']],true);
                if(is_wp_error($updated))throw new RuntimeException('Cannot update source-grounded excerpt '.$id);
            }
            update_post_meta($id,'_mdo_pajarete_seo_editorial_short',$plan['short']);
            update_post_meta($id,'_mdo_pajarete_seo_short_source_sha256',$plan['original_short_sha256']);
            update_post_meta($id,'_mdo_pajarete_seo_short_approved','1');
        }
        clean_post_cache($id);
    }
    foreach($all_media as $aid=>$m){
        // Never relabel attachments belonging to other suppliers.
        if((int)get_post_field('post_parent',$aid)!==$m['parent']){
            throw new RuntimeException('Image belongs to unexpected product '.$aid);
        }
        if($m['current']!==$m['alt'])update_post_meta($aid,'_wp_attachment_image_alt',$m['alt']);
    }
    if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
    wp_cache_flush();
}
$bad=[];$updated_alts=0;$good_shorts=0;$present=0;
foreach($plans as $id=>$plan){
    $new=$wpdb->get_row($wpdb->prepare("SELECT title,description,robots_noindex FROM {$aio} WHERE post_id=%d LIMIT 1",$id),ARRAY_A);
    $product=wc_get_product($id);
    if(!$new||$new['title']!==$plan['title']||$new['description']!==$plan['description']||(int)$new['robots_noindex']!==0)
        $bad[]=['id'=>$id,'reason'=>'aio'];
    if(!$product||$product->get_status()!=='publish'||$product->get_catalog_visibility()!=='visible')
        $bad[]=['id'=>$id,'reason'=>'publication'];
    if($plan['short']){
        $actual=wp_kses_post((string)$product->get_short_description());
        if($actual!==wp_kses_post($plan['short']))$bad[]=['id'=>$id,'reason'=>'short'];
        else $good_shorts++;
    }
    foreach($plan['media'] as $aid=>$m){
        $alt=(string)get_post_meta((int)$aid,'_wp_attachment_image_alt',true);
        if($alt!==$m['alt'])$bad[]=['id'=>$id,'reason'=>'img_alt_'.$aid];
        else $updated_alts++;
    }
    $present++;
}
foreach($src as $r){
    if($r['status']!=='excluded'||!$r['wc_product_id'])continue;
    $p=wc_get_product((int)$r['wc_product_id']);
    if($p&&($p->get_status()==='publish'||$p->get_catalog_visibility()!=='hidden'))
        $bad[]=['id'=>$r['wc_product_id'],'reason'=>'excluded_became_visible'];
}
echo 'PAJARETE_SEO_RESULT='.wp_json_encode([
    'phase'=>$mode,'products_checked'=>$present,'aioseo_titles_descriptions'=>count($plans),
    'image_alt_checked'=>$updated_alts,'short_editorial_checked'=>$good_shorts,
    'source_long_descriptions_untouched'=>true,'excluded_products_untouched'=>true,
    'problems'=>$bad,
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($bad)throw new RuntimeException('SEO checks failed; review audit.');
