<?php
/** Safely retire exactly one obsolete empty English blog term alias; Spanish untouched. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)throw new RuntimeException('WP-CLI required');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong environment');
$old=get_term(238,'category');$current=get_term(438,'category');
if(!$old instanceof WP_Term || !$current instanceof WP_Term||
  $old->slug!=='jamones-paletas'||$current->slug!=='jamones-y-paletas'||
  (int)$old->count!==0||(int)$current->count<25)throw new RuntimeException('Term identity or inventory drift');
$aliasOld=(string)get_term_meta(238,'_en_US_slug',true);
$aliasCurrent=(string)get_term_meta(438,'_en_US_slug',true);
$live=(string)get_term_meta(438,'_en_US_published',true);
$before=(string)get_term_meta(238,'_en_US_published',true);
if($aliasOld!=='hams-and-shoulders'||$aliasCurrent!==$aliasOld||$live!=='1')
 throw new RuntimeException('No verifiable collision exists; nothing to change');
if($before==='1'){
  if(!metadata_exists('term',238,'_emdo_english_legacy_duplicate_backup_20261021')){
      add_term_meta(238,'_emdo_english_legacy_duplicate_backup_20261021',
        ['before'=>$before,'english_slug'=>$aliasOld,'count'=>(int)$old->count],true);
  }
  update_term_meta(238,'_en_US_published','0');
  clean_term_cache([238,438],'category');
}
$after=(string)get_term_meta(238,'_en_US_published',true);
$selected=function_exists('mdoer_term_row')?mdoer_term_row('category','hams-and-shoulders',true):null;
$ok=$after==='0'&&$live===(string)get_term_meta(438,'_en_US_published',true)
  &&is_array($selected)&&(int)$selected['term_id']===438;
echo 'EMDO_EN_HAM_TERM_DEDUPE='.wp_json_encode([
 'legacy_term'=>238,'old_before'=>$before,'old_after'=>$after,
 'active_term'=>438,'active_count'=>(int)$current->count,
 'resolved_term'=>$selected['term_id']??null,'verified'=>$ok
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$ok)exit(17);
