<?php
/** Replace only the canonical producer block on the first five La Huerta de Ana Mary product fichas (ES + EN). */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_hap_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_hap_vendor($post){
    $u=get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}

$ids=[12699,12702,12706,12709,12711];
$es=<<<'HTML'
<h2>Sobre La Huerta de Ana Mary</h2>
<p>En Fresno de la Vega, en la provincia de León, la agricultura y el cultivo de hortalizas forman parte de una tradición ligada a la Vega del Esla desde hace generaciones. La Huerta de Ana Mary continúa esa actividad familiar, con más de tres generaciones vinculadas al trabajo en el campo.</p>
<p>Las verduras y hortalizas se cultivan en parcelas de Fresno de la Vega, aprovechando la experiencia agrícola de la zona y adaptando el trabajo a los ciclos y temporadas de cada cultivo.</p>
<p>La recolección se organiza en función de los pedidos para reducir el tiempo de almacenamiento y acortar al máximo el recorrido entre la huerta y el consumidor, favoreciendo que el producto llegue fresco y en buenas condiciones.</p>
HTML;
$en=<<<'HTML'
<h2>About La Huerta de Ana Mary</h2>
<p>In Fresno de la Vega, in the province of León, agriculture and vegetable growing have been part of a tradition connected to the Vega del Esla for generations. La Huerta de Ana Mary continues this family farming activity, with more than three generations linked to working the land.</p>
<p>Vegetables and produce are grown on plots in Fresno de la Vega, drawing on the area's agricultural experience and adapting the work to the cycles and seasons of each crop.</p>
<p>Harvesting is organised around orders to reduce storage time and keep the journey from the field to the customer as short as possible, helping the produce arrive fresh and in good condition.</p>
HTML;

$backup_key='mo_huerta_producer_block_standard_backup_20260831';
if(get_option($backup_key,null)===null){
    $backup=['created_at'=>current_time('mysql'),'products'=>[]];
    foreach($ids as $id){
        $p=get_post($id);
        if(!$p || $p->post_type!=='product' || $p->post_status!=='publish') mo_hap_fail("Missing published product {$id}");
        if(stripos(mo_hap_vendor($p),'Huerta de Ana Mary')===false) mo_hap_fail("Vendor mismatch {$id}");
        $backup['products'][$id]=[
            'post_content'=>$p->post_content,
            'en_US_post_content'=>(string)get_post_meta($id,'_en_US_post_content',true),
        ];
    }
    if(!add_option($backup_key,$backup,'',false)) mo_hap_fail('Could not create producer block backup');
    echo "BACKUP created {$backup_key}\n";
}else{
    echo "BACKUP already exists and is preserved {$backup_key}\n";
}

foreach($ids as $id){
    $p=get_post($id);
    if(!$p || stripos((string)$p->post_content,'<h2>Sobre La Huerta de Ana Mary</h2>')===false) mo_hap_fail("Spanish producer heading missing {$id}");
    $new_es=preg_replace('~<h2>Sobre La Huerta de Ana Mary</h2>.*?(?=<h2>Preguntas frecuentes</h2>)~isu',trim($es)."\n",(string)$p->post_content,1,$count_es);
    if($count_es!==1) mo_hap_fail("Spanish producer block replacement count {$id}: {$count_es}");

    $old_en=(string)get_post_meta($id,'_en_US_post_content',true);
    if(stripos($old_en,'<h2>About La Huerta de Ana Mary</h2>')===false) mo_hap_fail("English producer heading missing {$id}");
    $new_en=preg_replace('~<h2>About La Huerta de Ana Mary</h2>.*?(?=<h2>Frequently asked questions</h2>)~isu',trim($en)."\n",$old_en,1,$count_en);
    if($count_en!==1) mo_hap_fail("English producer block replacement count {$id}: {$count_en}");

    $r=wp_update_post(['ID'=>$id,'post_content'=>$new_es],true);
    if(is_wp_error($r)) mo_hap_fail("Spanish content update failed {$id}: ".$r->get_error_message());
    update_post_meta($id,'_en_US_post_content',$new_en);
    clean_post_cache($id);

    $fresh=get_post($id);
    $fresh_en=(string)get_post_meta($id,'_en_US_post_content',true);
    if(substr_count((string)$fresh->post_content,'Sobre La Huerta de Ana Mary')!==1) mo_hap_fail("Spanish heading verification failed {$id}");
    if(stripos((string)$fresh->post_content,'Arsenio Pérez Crespo')!==false || stripos((string)$fresh->post_content,'Según explica el propio productor')!==false) mo_hap_fail("Old Spanish producer wording remains {$id}");
    if(stripos((string)$fresh->post_content,'La recolección se organiza en función de los pedidos')===false) mo_hap_fail("New Spanish producer wording missing {$id}");
    if(stripos($fresh_en,'Harvesting is organised around orders')===false) mo_hap_fail("New English producer wording missing {$id}");
    if(stripos($fresh_en,'Arsenio Pérez Crespo')!==false || stripos($fresh_en,'According to the producer')!==false) mo_hap_fail("Old English producer wording remains {$id}");
    echo "UPDATED_AND_VERIFIED ID={$id} {$p->post_title}\n";
}

$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))===$trp){
    $pairs=[
        'Sobre La Huerta de Ana Mary'=>'About La Huerta de Ana Mary',
        'En Fresno de la Vega, en la provincia de León, la agricultura y el cultivo de hortalizas forman parte de una tradición ligada a la Vega del Esla desde hace generaciones. La Huerta de Ana Mary continúa esa actividad familiar, con más de tres generaciones vinculadas al trabajo en el campo.'=>'In Fresno de la Vega, in the province of León, agriculture and vegetable growing have been part of a tradition connected to the Vega del Esla for generations. La Huerta de Ana Mary continues this family farming activity, with more than three generations linked to working the land.',
        'Las verduras y hortalizas se cultivan en parcelas de Fresno de la Vega, aprovechando la experiencia agrícola de la zona y adaptando el trabajo a los ciclos y temporadas de cada cultivo.'=>"Vegetables and produce are grown on plots in Fresno de la Vega, drawing on the area's agricultural experience and adapting the work to the cycles and seasons of each crop.",
        'La recolección se organiza en función de los pedidos para reducir el tiempo de almacenamiento y acortar al máximo el recorrido entre la huerta y el consumidor, favoreciendo que el producto llegue fresco y en buenas condiciones.'=>'Harvesting is organised around orders to reduce storage time and keep the journey from the field to the customer as short as possible, helping the produce arrive fresh and in good condition.',
    ];
    $cols=$wpdb->get_col("SHOW COLUMNS FROM `{$trp}`",0);
    $has_original_id=in_array('original_id',$cols,true);
    foreach($pairs as $original=>$translated){
        $row=$wpdb->get_row($wpdb->prepare("SELECT id FROM `{$trp}` WHERE original=%s ORDER BY id ASC LIMIT 1",$original),ARRAY_A);
        if($row){
            if($wpdb->update($trp,['translated'=>$translated,'status'=>2,'block_type'=>0],['id'=>(int)$row['id']],['%s','%d','%d'],['%d'])===false) mo_hap_fail("TRP update failed: {$original}");
        }else{
            $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
            $format=['%s','%s','%d','%d'];
            if($has_original_id){ $data['original_id']=0; $format[]='%d'; }
            if($wpdb->insert($trp,$data,$format)===false) mo_hap_fail("TRP insert failed: {$original}");
        }
    }
    echo "TRANSLATEPRESS producer strings updated\n";
}

echo "DONE producer_block_products=5\n";
