<?php
/**
 * Branding for WooCommerce transactional emails.
 *
 * No template copies and no changes to order/payment or vendor logic:
 * preserve WooCommerce's native email templates, including their
 * personalization, accessibility and item/total tables.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Email_Branding {
	public static function init(): void {
		// All Woo emails: remove WooCommerce software attribution from footer.
		add_filter( 'woocommerce_email_footer_text', array( __CLASS__, 'footer' ), 50, 1 );
		// Customer emails only: apply the palette via WC's CSS inlining flow.
		add_filter( 'woocommerce_email_styles', array( __CLASS__, 'styles' ), 80, 2 );
	}

	/**
	 * A single understated signature: no duplicate brand name or links.
	 */
	public static function footer( $previous = '' ): string {
		return 'Del productor a tu mesa.';
	}

	/**
	 * Add minimal brand polish to Woo's native responsive HTML emails.
	 * The default footer CSS is left in place for mail-client compatibility.
	 */
	public static function styles( string $css, $email = null ): string {
		if ( ! is_object( $email )
			|| ! isset( $email->id )
			|| ! str_starts_with( (string) $email->id, 'customer_' ) ) {
			// Supplier and admin messages need their existing dense layout.
			return $css;
		}
		return $css . "\n" . <<<'CSS'
/* El Mercado de Origen — transactional customer email palette */
body, #wrapper, #outer_wrapper {
	background-color:#f4f6f1 !important;
}
#outer_wrapper {
	padding:26px 12px !important;
}
#template_container {
	background-color:#fffdfa !important;
	border:1px solid #e3eadf !important;
	border-radius:14px !important;
	box-shadow:0 8px 24px rgba(31,68,45,.07) !important;
}
#template_header {
	background-color:#1d4533 !important;
	border-radius:14px 14px 0 0 !important;
}
#header_wrapper {
	padding:32px 30px 29px !important;
}
#template_header h1 {
	color:#fffaf0 !important;
	font-family:Georgia,Times New Roman,serif !important;
	font-size:27px !important;
	font-weight:700 !important;
	line-height:1.3 !important;
	letter-spacing:0 !important;
}
#body_content {
	background-color:#fffdfa !important;
	color:#304837 !important;
}
#body_content_inner {
	padding:28px 30px 26px !important;
}
#body_content h2 {
	color:#254936 !important;
	font-family:Georgia,Times New Roman,serif !important;
	font-size:21px !important;
	line-height:1.4 !important;
}
#body_content h3 {
	color:#355942 !important;
}
#body_content p, #body_content td, #body_content li {
	line-height:1.65 !important;
}
#body_content a {
	color:#28513c !important;
}
#body_content table.td,
#body_content table.td th,
#body_content table.td td {
	border-color:#e3eadf !important;
}
#body_content table.td th {
	color:#31533f !important;
	background-color:#f5f8f1 !important;
}
#addresses {
	margin-top:12px !important;
}
#template_footer {
	background-color:#f5f8f2 !important;
	border-top:1px solid #e3eadf !important;
}
#credit {
	padding:22px 30px 26px !important;
	text-align:center !important;
}
#credit p {
	font-size:12px !important;
	line-height:1.9 !important;
	color:#617366 !important;
	text-align:center !important;
	margin:0 !important;
}
#credit a {
	color:#28513c !important;
	text-decoration:none !important;
}
CSS;
	}
}
