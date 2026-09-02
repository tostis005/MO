<?php
if (!defined('ABSPATH')) { exit; }
const EMDO_FINALIZE_MARKER = '2026-09-02.authority-cleanup.finalize.v1';
function emdo_f_words($html){ preg_match_all("/[\\p{L}\\p{N}]+(?:[’'\\-][\\p{L}\\p{N}]+)*/u", wp_strip_all_tags((string)$html), $m); return count($m[0]); }
function emdo_f_insert_before($html,$heading,$insert){
    $re='/<h2[^>]*>\\s*'.preg_quote($heading,'/').'\\s*<\\/h2>/iu';
    return preg_match($re,$html) ? preg_replace($re,$insert.'$0',$html,1) : $html.$insert;
}
$adds=array(
  14097=>array(
    'es'=>'<h2>Montoro-Adamuz y el norte de Córdoba: otra lectura del Picual</h2><p>El norte cordobés añade un contraste útil frente a la Subbética. La DOP Montoro-Adamuz se asienta en un territorio más serrano y con fuerte presencia de Picual, de modo que permite comparar cómo una misma variedad puede expresarse de forma distinta según altitud, madurez, campaña y trabajo de almazara. No debe interpretarse como una competición con Priego de Córdoba o Baena: cada figura protege su propio territorio, variedades admitidas y condiciones de elaboración.</p><p>Para quien quiera aprender mediante cata, una comparación entre un aceite de Montoro-Adamuz y otro de Priego resulta más informativa que intentar atribuir todo el sabor a la provincia de Córdoba. El origen aporta contexto; variedad, fecha de cosecha y extracción terminan de construir el perfil.</p>',
    'en'=>'<h2>Montoro-Adamuz and northern Córdoba: another reading of Picual</h2><p>Northern Córdoba offers a useful contrast with the Subbética. The Montoro-Adamuz PDO covers a more mountainous setting with a strong presence of Picual, making it useful for seeing how the same cultivar can change with altitude, ripeness, season and mill decisions. It should not be treated as a contest with Priego de Córdoba or Baena: each protected name has its own territory, authorised cultivars and production rules.</p><p>For tasting, comparing a Montoro-Adamuz oil with one from Priego can teach more than trying to assign one flavour to the whole province. Origin provides context; cultivar, harvest timing and extraction complete the sensory profile.</p>'
  ),
  14099=>array(
    'es'=>'<h2>Carrasqueña y Verdial: el sur extremeño no se explica solo con Manzanilla Cacereña</h2><p>La identidad oleícola extremeña cambia al desplazarse hacia Badajoz. Carrasqueña y distintos tipos de Verdial aparecen junto a otras variedades en territorios donde el clima, la maduración y la tradición productiva difieren del norte cacereño. Esta diversidad ayuda a entender por qué dos botellas extremeñas pueden tener perfiles muy alejados sin que ninguna sea menos representativa de su zona.</p><p>Al comprar, conviene leer la variedad junto al nombre geográfico y la campaña. Esa combinación informa mucho más que una descripción genérica como “aceite de Extremadura”.</p>',
    'en'=>'<h2>Carrasqueña and Verdial: southern Extremadura is not explained by Manzanilla Cacereña alone</h2><p>Extremadura’s olive-oil identity changes toward Badajoz. Carrasqueña and different Verdial types appear alongside other cultivars in territories where climate, ripening and production traditions differ from northern Cáceres. This diversity explains why two Extremaduran bottles may have very different sensory profiles without either being less representative of its area.</p><p>When buying, read cultivar together with geographical name and harvest season. That combination is much more informative than a generic description such as “olive oil from Extremadura”.</p>'
  )
);
$report=array();
foreach($adds as $id=>$d){
    $p=get_post($id); if(!$p || $p->post_status!=='publish') throw new RuntimeException("Missing published post $id");
    if((string)get_post_meta($id,'_emdo_authority_cleanup_finalize_20260902',true)!==EMDO_FINALIZE_MARKER){
        $es=emdo_f_insert_before($p->post_content,'Preguntas relacionadas',$d['es']);
        $en0=(string)get_post_meta($id,'_en_US_post_content',true);
        $en=emdo_f_insert_before($en0,'Related questions',$d['en']);
        $r=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$es)),true); if(is_wp_error($r)) throw new RuntimeException($r->get_error_message());
        update_post_meta($id,'_en_US_post_content',$en);
        update_post_meta($id,'_emdo_authority_cleanup_finalize_20260902',EMDO_FINALIZE_MARKER);
    }
    $report[]=array('id'=>$id,'es'=>emdo_f_words(get_post_field('post_content',$id)),'en'=>emdo_f_words((string)get_post_meta($id,'_en_US_post_content',true)));
}
foreach($report as $x){ if($x['es']<650 || $x['en']<520) throw new RuntimeException('Still too short '.wp_json_encode($x)); }
$canon=array(14040,14041,14042,14043,14044,14045,14046,14047,14048,14049);
$dup=array(14061,14062,14063,14064,14065,14066,14067,14068,14069,14070);
foreach($canon as $id){ if(get_post_status($id)!=='publish') throw new RuntimeException("Canonical not published $id"); }
foreach($dup as $id){ if(get_post_status($id)==='publish' || (string)get_post_meta($id,'_en_US_published',true)==='1') throw new RuntimeException("Duplicate still public $id"); }
$redirects=get_option('emdo_authority_redirects_20260902',array()); if(!is_array($redirects) || count($redirects)<20) throw new RuntimeException('Redirect map incomplete');
echo "EMDO_FINALIZE_BEGIN\n".wp_json_encode(array('marker'=>EMDO_FINALIZE_MARKER,'posts'=>$report,'redirect_count'=>count($redirects),'backup_present'=>get_option('emdo_authority_cleanup_backup_20260902',false)!==false),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\nEMDO_FINALIZE_END\n";
