<?php
/**
 * One-off FluentCRM draft based on existing oil campaign #8; only one native test email.
 * No campaign scheduling or bulk sending.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) {
 fwrite(STDERR,"AOVE_MAIL_ABORT_WP_REQUIRED\n");exit(2);
}
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)){
 fwrite(STDERR,"AOVE_MAIL_ABORT_NOT_PRODUCTION\n");exit(3);
}
$campaign_class='\\FluentCrm\\App\\Models\\Campaign';
$controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
$app_class='\\FluentCrm\\Framework\\Foundation\\App';
if(!class_exists($campaign_class)||!class_exists($controller_class)||!class_exists($app_class)){
 fwrite(STDERR,"AOVE_MAIL_ABORT_FLUENTCRM_UNAVAILABLE\n");exit(4);
}
$source=$campaign_class::find(8);
if(!$source || $source->status!=='archived' || $source->design_template!=='simple'
   || stripos((string)$source->title,'AOVE')===false) {
 fwrite(STDERR,"AOVE_MAIL_ABORT_REFERENCE_MISMATCH\n");exit(5);
}
$vendor=get_user_by('login','1957');
if(!$vendor)$vendor=get_user_by('slug','1957');
if(!$vendor){fwrite(STDERR,"AOVE_MAIL_ABORT_VENDOR_1957\n");exit(6);}
$ids=get_posts(['post_type'=>'product','post_status'=>'publish','author'=>(int)$vendor->ID,'fields'=>'ids','posts_per_page'=>-1,'suppress_filters'=>true]);
$matching=[];
foreach($ids as $id){
 $prod=wc_get_product($id);
 if($prod&&preg_match('/COSECHA\s*26\/27/ui',$prod->get_name()))$matching[]=(int)$id;
}
if(count($matching)!==3){fwrite(STDERR,"AOVE_MAIL_ABORT_UNEXPECTED_PRODUCTS=".count($matching)."\n");exit(7);}
$expected_shop='https://www.elmercadodeorigen.com/tienda/1957/';
$store= function_exists('wcfmmp_get_store_url')?trailingslashit((string)wcfmmp_get_store_url((int)$vendor->ID)):$expected_shop;
if(untrailingslashit($store)!==untrailingslashit($expected_shop)){
 fwrite(STDERR,"AOVE_MAIL_ABORT_STORE_URL_MISMATCH ".esc_url_raw($store)."\n");exit(8);
}
$coupon=class_exists('WC_Coupon')?new WC_Coupon('SINFILTRO'):null;
if(!$coupon || !$coupon->get_id() || $coupon->get_discount_type()!=='percent' || abs((float)$coupon->get_amount()-10)>0.001
   || get_post_status($coupon->get_id())!=='publish'){
 fwrite(STDERR,"AOVE_MAIL_ABORT_SINFILTRO_COUPON_INVALID\n");exit(9);
}
$subject='Aceite recién molturado: descubre el Lechín y la Manzanilla sin filtrar';
$preheader='Lechín de olivos centenarios y Manzanilla recién molturados. 10 % de descuento con SINFILTRO.';
$title='Cosecha 26/27 · Lechín y Manzanilla sin filtrar · 10 % SINFILTRO';
$state_key='_emdo_fluentcrm_aove_1957_20261030';
$test_email='jose.fraga@gmail.com';

$source_body=(string)$source->email_body;
if(!preg_match('/\A\s*(<!-- wp:image .*?<!-- \/wp:image -->)/s',$source_body,$images)){
 fwrite(STDERR,"AOVE_MAIL_ABORT_SOURCE_HEADER_IMAGE_MISSING\n");exit(10);
}
$brand_logo=$images[1];
if(strpos($brand_logo,'Logo-sin-fondo-150x150.jpg')===false) {
 fwrite(STDERR,"AOVE_MAIL_ABORT_REFERENCE_BRAND_MISMATCH\n");exit(11);
}
$p=function($body){
 return '<!-- wp:paragraph {"lineHeight":27,"marginBottom":18} -->'."\n"
  .'<p style="line-height:27px;margin-bottom:18px">'.$body.'</p>'."\n"
  .'<!-- /wp:paragraph -->';
};
$heading=function($body){
 return '<!-- wp:heading {"level":3,"marginBottom":14} -->'."\n"
  .'<h3 class="wp-block-heading" style="margin-bottom:14px"><strong>'.$body.'</strong></h3>'."\n"
  .'<!-- /wp:heading -->';
};
$cta = '<!-- wp:buttons -->'."\n"
 .'<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","width":100,"className":"is-style-fill","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"radius":"0px"},"color":{"background":"#5eb041"},"typography":{"fontSize":"18px"}}} -->'."\n"
 .'<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-fill"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" href="'.esc_url($expected_shop).'" style="border-radius:0px;background-color:#5eb041;font-size:18px"><strong>DESCUBRIR LOS ACEITES RECIÉN MOLTURADOS</strong></a></div>'."\n"
 .'<!-- /wp:button --></div>'."\n"
 .'<!-- /wp:buttons -->';

$body=implode("\n\n",[
 $brand_logo,
 $p('Buenas noches,'),
 $p('Hay un momento del año especialmente esperado por quienes disfrutamos de un buen aceite de oliva: cuando las primeras aceitunas llegan a la almazara y podemos probar el aceite recién elaborado.'),
 $p('<strong>Esta misma semana se han molturado los primeros aceites de la cosecha 2026/27</strong>, y ya tenemos disponibles las variedades Lechín y Manzanilla, ambas sin filtrar y obtenidas mediante extracción en frío.'),
 $p('Queríamos contaros un poco qué hace especiales a estos aceites.'),
 $heading('Lechín: el carácter de los olivares centenarios'),
 $p('El Lechín es una variedad tradicional andaluza, procedente en este caso de olivos centenarios cultivados de forma no intensiva.'),
 $p('Son árboles que forman parte del paisaje desde hace generaciones y cuya producción es más limitada que la de las plantaciones modernas. Precisamente por eso, cada año disponemos de una cantidad reducida de este aceite.'),
 $p('En boca, el Lechín recién elaborado destaca por su personalidad. Es un aceite de carácter intenso, con notas verdes y vegetales, un amargor agradable y un picor final que aporta viveza y persistencia. Un aceite con cuerpo, especialmente interesante para quienes buscan sabores auténticos y con carácter.'),
 $p('Es una variedad que recomendamos especialmente para disfrutar en crudo, sobre una buena tostada, con tomate o simplemente acompañada de pan.'),
 $heading('Manzanilla: frescura, aromas y equilibrio'),
 $p('La Manzanilla ofrece un perfil diferente. Es un aceite aromático y afrutado, con recuerdos de aceituna fresca, hierba verde y matices vegetales.'),
 $p('Frente al carácter más intenso del Lechín, la Manzanilla suele resultar más redonda y equilibrada en boca, con un amargor y un picor presentes, pero generalmente más suaves.'),
 $p('Es una magnífica elección para el consumo diario, especialmente en tostadas, ensaladas, verduras y platos en los que queramos apreciar el sabor del aceite sin que domine el conjunto.'),
 $heading('El sabor del aceite recién salido de la almazara'),
 $p('Estos aceites se presentan sin filtrar, conservando ese aspecto ligeramente turbio tan característico de los primeros aceites de la cosecha.'),
 $p('La frescura de la aceituna recién molturada se aprecia especialmente en sus aromas verdes, su intensidad y ese agradable amargor y picor relacionados con los polifenoles presentes de forma natural en el aceite de oliva virgen extra.'),
 $p('Es un aceite para disfrutar ahora, cuando los aromas y sabores de la nueva cosecha están especialmente vivos.'),
 $heading('Una pequeña ventaja para disfrutar de esta cosecha'),
 $p('Para quienes queráis probar estos aceites, tenemos disponible un <strong>10 % de descuento</strong> utilizando el código <strong>SINFILTRO</strong> al realizar el pedido.'),
 $p('Podéis encontrar las dos variedades y elegir entre diferentes formatos en la tienda de nuestro productor 1957:'),
 $cta,
 $p('En el caso del Lechín, como ocurre cada año, la disponibilidad es limitada por las características de su cultivo tradicional.'),
 $p('Esperamos que disfrutéis de estos primeros aceites de la cosecha tanto como nosotros.'),
 $p('Un saludo,'),
 $p('<strong>El equipo de El Mercado de Origen</strong>')
]);

if(substr_count($body,$expected_shop)!==1||strpos($body,'Buenas noches,')===false
 || strpos($body,'SINFILTRO')===false||strpos($body,'Manzanilla')===false
 || strpos($body,'Lechín')===false || strpos($body,'olivos centenarios')===false) {
 fwrite(STDERR,"AOVE_MAIL_ABORT_BODY_VALIDATION\n");exit(12);
}

$state=get_option($state_key,[]);
if(!is_array($state))$state=[];
$draft=null;
if(!empty($state['campaign_id']))$draft=$campaign_class::find((int)$state['campaign_id']);
if(!$draft)$draft=$campaign_class::where('status','draft')->where('title',$title)->orderBy('id','DESC')->first();
if($draft&&$draft->status!=='draft'){fwrite(STDERR,"AOVE_MAIL_ABORT_PREVIOUS_CAMPAIGN_NOT_DRAFT\n");exit(13);}
if(!$draft){
 $app=$app_class::getInstance();
 $controller=new $controller_class();
 $duplicate=$controller->duplicateCampaign($app['request'],8);
 $draft=$duplicate['campaign']??null;
 if(!$draft||(int)$draft->id===8){fwrite(STDERR,"AOVE_MAIL_ABORT_DUPLICATE_FAILED\n");exit(14);}
 $state['campaign_id']=(int)$draft->id;
 $state['source_id']=8;
 $state['test_sent']=false;
 update_option($state_key,$state,false);
}
$draft->title=$title;
$draft->email_subject=$subject;
$draft->email_pre_header=$preheader;
$draft->email_body=$body;
$draft->status='draft';
$draft->save();
$draft=$campaign_class::find((int)$draft->id);
if(!$draft||$draft->status!=='draft'||$draft->email_subject!==$subject || (string)$draft->email_body!==$body
 || $draft->design_template!=='simple'){
 fwrite(STDERR,"AOVE_MAIL_ABORT_DRAFT_VERIFY_FAILED\n");exit(15);
}
echo 'AOVE_FLUENT_DRAFT_OK='.wp_json_encode([
 'id'=>(int)$draft->id,'source_template_campaign'=>8,'status'=>$draft->status,
 'subject'=>$draft->email_subject,'template'=>$draft->design_template,
 'preheader'=>$draft->email_pre_header,'body_sha256'=>hash('sha256',$body),
 'shop_url'=>$expected_shop
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";

$state['campaign_id']=(int)$draft->id;
$state['source_id']=8;
update_option($state_key,$state,false);
if(!empty($state['test_sent'])) {
 if(($state['test_recipient']??'')!==$test_email){fwrite(STDERR,"AOVE_MAIL_ABORT_TEST_STATE_MISMATCH\n");exit(16);}
 echo "AOVE_FLUENT_TEST_ALREADY_SENT id=".(int)$draft->id."\n";
 echo "AOVE_FLUENT_OPERATION_COMPLETE\n";
 exit(0);
}

// Use FluentCRM's own test-only controller pathway, not the bulk campaign sender.
// Explicit, single destination. Fails closed if the injected request cannot be controlled.
$controller=new $controller_class();
$ref=new ReflectionProperty($controller,'request');
$ref->setAccessible(true);
$type=$ref->getType();
if($type){fwrite(STDERR,"AOVE_MAIL_ABORT_CONTROLLER_REQUEST_TYPED ".(string)$type."\n");exit(17);}
$request=new class($draft,$test_email) {
 private $params;
 public function __construct($draft,$recipient) {
  $this->params=['test_campaign'=>'no','campaign_id'=>(int)$draft->id,'email'=>$recipient];
 }
 public function get($key,$default=null){return $this->params[$key]??$default;}
};
$ref->setValue($controller,$request);
if($request->get('email')!==$test_email || (int)$request->get('campaign_id')!==(int)$draft->id
 ||$request->get('test_campaign')==='yes'){fwrite(STDERR,"AOVE_MAIL_ABORT_TEST_REQUEST\n");exit(18);}
try {$test_result=$controller->sendTestEmail();}
catch(Throwable $e){fwrite(STDERR,"AOVE_MAIL_TEST_ERROR ".get_class($e)." ".substr($e->getMessage(),0,200)."\n");exit(19);}
$send_result=is_array($test_result)?($test_result['result']??null):null;
if(is_wp_error($send_result)||$send_result===false||$send_result===null){
 fwrite(STDERR,"AOVE_MAIL_TEST_NOT_CONFIRMED result_type=".gettype($send_result)."\n");exit(20);
}
$state['test_sent']=true;
$state['test_recipient']=$test_email;
$state['test_sent_at']=current_time('mysql');
update_option($state_key,$state,false);
$check=$campaign_class::find((int)$draft->id);
if(!$check||$check->status!=='draft'){fwrite(STDERR,"AOVE_MAIL_ABORT_POST_TEST_NOT_DRAFT\n");exit(21);}
echo 'AOVE_FLUENT_TEST_OK='.wp_json_encode(['campaign_id'=>(int)$draft->id,'recipient'=>$test_email,'test_result_type'=>gettype($send_result),'result_truthy'=>(bool)$send_result,'campaign_status'=>$check->status],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "AOVE_FLUENT_OPERATION_COMPLETE\n";
