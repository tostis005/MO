<?php
/**
 * A04 targeted Aceites category copy refresh.
 * Run under WP-CLI with EMDO_SEO_MODE=dryrun|apply|verify|rollback and
 * EMDO_SEO_BACKUP=/tmp/known-path. Only the Spanish term description changes.
 */
if ( ! defined('ABSPATH') ) { exit(2); }
$mode = (string) getenv('EMDO_SEO_MODE');
$backup = (string) getenv('EMDO_SEO_BACKUP');
$original_sha = 'a0a0291ffbaab1a852d4f2754f25c0ebfa3bdf59ba8b5e54e214c4360fe95191';
$term = get_term_by('slug','aceites','product_cat');
if ( ! $term instanceof WP_Term || (int)$term->term_id!==16 ) { fwrite(STDERR,"ACEITES_TERM_MISMATCH\n");exit(3); }
$original = (string)$term->description;
$marker = '<!-- emdo-ac-landings-start -->';
$closing = '<!-- emdo-ac-landings-end -->';
$new = '<p>Compra <strong>aceite de oliva virgen extra online</strong> y compara los formatos y variedades disponibles directamente de los productores de El Mercado de Origen. En cada ficha puedes consultar el origen, las opciones de compra y el precio correspondiente.</p>' . "\n\n"
    . '<p><strong>Nueva cosecha 2026/27 de 1957.</strong> Descubre el aceite recién molturado del productor <a href="https://www.elmercadodeorigen.com/tienda/1957/">1957</a>, con opciones sin filtrar de distintas variedades según disponibilidad. Elige entre <a href="https://www.elmercadodeorigen.com/producto/cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio/">garrafa de 5 litros</a>, <a href="https://www.elmercadodeorigen.com/producto/sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l/">pack de 15 botellas de 1 litro</a> o <a href="https://www.elmercadodeorigen.com/producto/aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27/">pack de 12 botellas PET de 500 ml</a>.</p>' . "\n\n"
    . '<p><strong>¿Qué formato elegir?</strong> La garrafa de 5 litros resulta práctica para el uso diario; los packs de botellas son una alternativa para repartir el consumo o regalar. Puedes elegir por variedad, formato y estilo disponible. Consulta las fichas para comprobar existencias y condiciones de envío de cada referencia.</p>' . "\n\n";
if ( in_array($mode,array('dryrun','apply'),true) ) {
    if (hash('sha256',$original)!==$original_sha) {
        fwrite(STDERR,"ACEITES_PREFLIGHT_SHA_CHANGED: ".hash('sha256',$original)."\n");
        exit(4);
    }
    $start = strpos($original,$marker);
    $end = strpos($original,$closing);
    if ($start===false || $end===false || $end<$start || substr_count($original,$marker)!==1) {
        fwrite(STDERR,"ACEITES_INTERNAL_LINKS_NOT_VERIFIED\n"); exit(5);
    }
    $new .= substr($original,$start); // Preserve existing AOVE landing + blog links as-is.
    if (substr_count($new,$marker)!==1 || substr_count($new,$closing)!==1) { exit(6); }
    if ($mode==='dryrun') {
        echo 'ACEITES_DRYRUN_OK=' . wp_json_encode(array('term_id'=>$term->term_id,'old_bytes'=>strlen($original),'new_bytes'=>strlen($new),'new_links'=>4),JSON_UNESCAPED_SLASHES)."\n";
        return;
    }
    if ($backup==='' || strpos($backup,'/tmp/seo-aceites-20261105-')!==0) { exit(7); }
    if (file_exists($backup)) { fwrite(STDERR,"BACKUP_EXISTS\n");exit(8); }
    if (file_put_contents($backup, $original, LOCK_EX) !== strlen($original)) { exit(9); }
    chmod($backup,0600);
    $result = wp_update_term((int)$term->term_id,'product_cat',array('description'=>wp_slash($new)));
    if (is_wp_error($result)) { fwrite(STDERR,$result->get_error_message()."\n");exit(10); }
    clean_term_cache((int)$term->term_id,'product_cat');
    echo "ACEITES_CATEGORY_APPLIED\n";
    return;
}
if ($mode==='verify') {
    $c=(string)get_term_by('slug','aceites','product_cat')->description;
    $pass=strpos($c,'Compra <strong>aceite de oliva virgen extra online</strong>')!==false
      && strpos($c,'Nueva cosecha 2026/27 de 1957.')!==false
      && strpos($c,'/tienda/1957/')!==false
      && substr_count($c,$marker)===1 && substr_count($c,$closing)===1
      && strpos($c,'El primer paso es el molido.')===false;
    if (!$pass) {fwrite(STDERR,"ACEITES_CATEGORY_VERIFY_FAILED\n");exit(11);}
    echo "ACEITES_CATEGORY_DB_VERIFIED\n";return;
}
if ($mode==='rollback') {
    if ($backup===''||!is_file($backup)) {fwrite(STDERR,"BACKUP_MISSING\n");exit(12);}
    $old=(string)file_get_contents($backup);
    if(hash('sha256',$old)!==$original_sha) {exit(13);}
    $r=wp_update_term((int)$term->term_id,'product_cat',array('description'=>wp_slash($old)));
    if(is_wp_error($r)) {exit(14);}
    clean_term_cache((int)$term->term_id,'product_cat');
    echo "ACEITES_CATEGORY_ROLLED_BACK\n";return;
}
fwrite(STDERR,"INVALID_MODE\n");exit(15);
