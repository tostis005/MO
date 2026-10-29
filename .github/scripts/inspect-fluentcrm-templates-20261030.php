<?php
/** Read-only inspection of current FluentCRM campaign styling and safe test APIs. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$cls='\\FluentCrm\\App\\Models\\Campaign';
if(!class_exists($cls)){fwrite(STDERR,"FLUENT_CLASS_MISSING\n");exit(4);}
$rows=$cls::orderBy('id','DESC')->limit(24)->get();
foreach($rows as $c){
 $body=(string)$c->email_body;
 $settings=$c->settings;
 echo 'CAMPAIGN_OVERVIEW='.wp_json_encode(['id'=>(int)$c->id,'title'=>(string)$c->title,'subject'=>(string)$c->email_subject,'status'=>(string)$c->status,'template'=>(string)$c->design_template,'body_len'=>strlen($body),'body_sha'=>hash('sha256',$body),'created'=>(string)$c->created_at,'preheader'=>(string)$c->email_pre_header,'body_start'=>mb_substr($body,0,2200),'body_end'=>mb_substr($body,-1200),'settings_keys'=>is_array($settings)?array_keys($settings):gettype($settings)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
foreach(['\\FluentCrm\\App\\Http\\Controllers\\CampaignController','\\FluentCrm\\App\\Services\\EmailCampaignService'] as $class){
 if(!class_exists($class))continue;
 $r=new ReflectionClass($class);
 echo 'FLUENT_CLASS='.wp_json_encode(['name'=>$class,'file'=>$r->getFileName()])."\n";
 foreach($r->getMethods(ReflectionMethod::IS_PUBLIC) as $m){
  if(!preg_match('/test|send|duplicate|mail|draft|campaign/i',$m->name))continue;
  echo 'FLUENT_METHOD='.wp_json_encode(['class'=>$class,'method'=>$m->name,'file'=>$m->getFileName(),'start'=>$m->getStartLine(),'end'=>$m->getEndLine(),'parameters'=>array_map(static function($p){return ['name'=>$p->name,'optional'=>$p->isOptional()];},$m->getParameters())],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }
}
echo "FLUENT_AUDIT_COMPLETE\n";
