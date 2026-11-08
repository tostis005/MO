<?php
/** Regression checks for EMDO WooCommerce customer email branding. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=[];
function add_filter($name,$callback,$priority=10,$argc=1) {
	$GLOBALS['hooks'][$name][]=['callback'=>$callback,'priority'=>$priority,'argc'=>$argc];
}
function home_url($path='/'): string {return 'https://www.elmercadodeorigen.com'.$path;}
function esc_url($v): string {return $v;}
require_once dirname(__DIR__).'/mdo-supplier-sync/includes/class-mdo-email-branding.php';
function ck(bool $v,string $why):void {if(!$v)throw new RuntimeException($why);}
MDO_Email_Branding::init();
ck(count($GLOBALS['hooks']['woocommerce_email_footer_text']??[])===1,'One footer filter');
ck(count($GLOBALS['hooks']['woocommerce_email_styles']??[])===1,'One style filter');
$footer=MDO_Email_Branding::footer('El Mercado de Origen — Built with WooCommerce');
ck($footer === 'Del productor a tu mesa.','Footer must contain just the short requested phrase');
ck(!str_contains($footer,'El Mercado de Origen'),'No duplicated brand name in footer');
ck(!str_contains($footer,'https://') && !str_contains($footer,'<a'),'No distracting footer links');
ck(!str_contains($footer,'WooCommerce') && !str_contains($footer,'Built with'),'No software attribution');
$style='/* WooCommerce base CSS */';
$customer=(object)['id'=>'customer_completed_order'];
$processing=(object)['id'=>'customer_processing_order'];
$admin=(object)['id'=>'new_order'];
$other=(object)['id'=>'email_vendor_order'];
$result=MDO_Email_Branding::styles($style,$customer);
ck(str_starts_with($result,$style),'Preserves Woo baseline CSS');
ck(str_contains($result,'#template_header h1'),'Branded typography');
ck(str_contains($result,'background-color:#1d4533'),'Brand header');
ck(str_contains($result,'#template_container'),'Customer email body retains Woo structure');
ck(str_contains($result,'#credit p'),'Branded footer style');
ck(str_contains(MDO_Email_Branding::styles($style,$processing),'#body_content_inner'),'Processing email also styled');
ck(MDO_Email_Branding::styles($style,$admin)===$style,'Admin/supplier emails untouched');
ck(MDO_Email_Branding::styles($style,$other)===$style,'Vendor plugin emails untouched');
ck(MDO_Email_Branding::styles($style,null)===$style,'Missing email object is safe');
ck(MDO_Email_Branding::styles($style,(object)[])===$style,'Unknown mail object is safe');
$bootstrap=file_get_contents(dirname(__DIR__).'/mdo-supplier-sync/mdo-supplier-sync.php');
ck(substr_count($bootstrap,'MDO_Email_Branding::init();')===1,'No duplicate hooks during plugin bootstrap');
ck(str_contains($bootstrap,"includes/class-mdo-email-branding.php"),'Brand class loaded');
echo "EMDO WooCommerce email branding and footer checks: OK\n";
