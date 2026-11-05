<?php
/**
 * Plugin Name: EMDO · Mi cuenta, experiencia premium
 * Description: Mejora visual del área privada de WooCommerce: navegación, panel, formularios y Club EMDO.
 * Version: 1.0.0
 *
 * Diseño, sin modificar pedidos, autenticación, permisos ni lógica del monedero.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Cargar exclusivamente en Mi cuenta, nunca en checkout, tienda o panel de vendedores. */
add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) { return; }
	wp_register_style( 'emdo-mi-cuenta-ux', false, array(), '20261105.1' );
	wp_enqueue_style( 'emdo-mi-cuenta-ux' );
	wp_add_inline_style( 'emdo-mi-cuenta-ux', <<<'CSS'
/* EL MERCADO DE ORIGEN · MI CUENTA 2026
   Scope fuerte para convivir con Woostify y WooCommerce. */
body.woocommerce-account {
	--emdo-account-ink: #203d32;
	--emdo-account-dark: #173f31;
	--emdo-account-muted: #627168;
	--emdo-account-cream: #f7f5ef;
	--emdo-account-line: #e8e5dc;
	--emdo-account-accent: #b99256;
}
body.woocommerce-account .site-content .entry-header,
body.woocommerce-account .site-content .page-header { margin-bottom:clamp(18px,3vw,32px); }
body.woocommerce-account .site-content h1.entry-title,
body.woocommerce-account .site-content .page-title {
	color:var(--emdo-account-dark);
	font-family:Georgia,"Times New Roman",serif;
	font-size:clamp(30px,4vw,48px);
	font-weight:700;
	letter-spacing:-.035em;
	line-height:1.12;
}
body.woocommerce-account .site-content .woocommerce {
	color:var(--emdo-account-ink);
	font-size:15px;
	line-height:1.65;
}
body.woocommerce-account .site-content .woocommerce::after { content:"";display:block;clear:both; }
body.woocommerce-account .site-content .woocommerce:has(> .woocommerce-MyAccount-navigation) {
	width:100%;
	max-width:1170px;
	margin-inline:auto;
	display:grid;
	grid-template-columns:minmax(215px,260px) minmax(0,1fr);
	gap:clamp(18px,2.6vw,32px);
	align-items:start;
}
body.woocommerce-account .site-content .woocommerce > .woocommerce-notices-wrapper,
body.woocommerce-account .site-content .woocommerce > .woocommerce-message,
body.woocommerce-account .site-content .woocommerce > .woocommerce-info,
body.woocommerce-account .site-content .woocommerce > .woocommerce-error {
	grid-column:1/-1;
}
body.woocommerce-account .woocommerce-MyAccount-navigation {
	float:none!important;
	width:100%!important;
	min-width:0!important;
	margin:0!important;
	background:#fff!important;
	border:1px solid var(--emdo-account-line)!important;
	border-radius:21px!important;
	box-shadow:0 14px 40px rgba(23,63,49,.055)!important;
	overflow:hidden;
	position:sticky;
	top:95px;
	padding:14px!important;
}
body.woocommerce-account .woocommerce-MyAccount-navigation::before {
	content:"TU ESPACIO";
	display:block;
	padding:13px 13px 14px;
	font-size:11px;
	font-weight:850;
	letter-spacing:.16em;
	color:#7d816d;
}
body.woocommerce-account .woocommerce-MyAccount-navigation ul {
	margin:0!important;
	padding:0!important;
	list-style:none!important;
	display:grid;
	gap:4px;
}
body.woocommerce-account .woocommerce-MyAccount-navigation li { border:0!important;margin:0!important;padding:0!important;list-style:none!important; }
body.woocommerce-account .woocommerce-MyAccount-navigation li a {
	display:flex!important;
	align-items:center;
	min-height:45px;
	padding:10px 13px!important;
	border-radius:11px!important;
	font-size:14px!important;
	font-weight:650!important;
	color:#3b5045!important;
	border:1px solid transparent!important;
	background:transparent!important;
	line-height:1.35!important;
	text-decoration:none!important;
	box-shadow:none!important;
	transition:background-color .15s ease,color .15s ease,transform .15s ease;
}
body.woocommerce-account .woocommerce-MyAccount-navigation li a:hover,
body.woocommerce-account .woocommerce-MyAccount-navigation li a:focus-visible {
	background:#f2f5ef!important;
	color:var(--emdo-account-dark)!important;
}
body.woocommerce-account .woocommerce-MyAccount-navigation li.is-active a {
	background:#eaf1e8!important;
	border-color:#d9e5d6!important;
	color:#194631!important;
	font-weight:800!important;
}
body.woocommerce-account .woocommerce-MyAccount-navigation-link--customer-logout {
	border-top:1px solid var(--emdo-account-line)!important;
	margin-top:10px!important;
	padding-top:10px!important;
}
body.woocommerce-account .woocommerce-MyAccount-navigation-link--customer-logout a {color:#82584c!important;}
body.woocommerce-account .woocommerce-MyAccount-content {
	float:none!important;
	width:100%!important;
	min-width:0!important;
	margin:0!important;
	padding:clamp(22px,3vw,34px)!important;
	background:#fff!important;
	border:1px solid var(--emdo-account-line)!important;
	border-radius:21px!important;
	box-shadow:0 16px 48px rgba(23,63,49,.055)!important;
	overflow-wrap:anywhere;
}
body.woocommerce-account .woocommerce-MyAccount-content > p {font-size:15px;line-height:1.8;color:#54675c;}
body.woocommerce-account .woocommerce-MyAccount-content > p:first-child {color:#304739;}
body.woocommerce-account .woocommerce-MyAccount-content strong {color:#244735;}
body.woocommerce-account .woocommerce-MyAccount-content a:not(.button):not(.woocommerce-button) {
	color:#205943;
	text-decoration-thickness:1px;
	text-underline-offset:3px;
}
body.woocommerce-account .woocommerce-MyAccount-content h2,
body.woocommerce-account .woocommerce-MyAccount-content h3 {
	color:var(--emdo-account-ink);
	line-height:1.24;
}
body.woocommerce-account .woocommerce-MyAccount-content h2 {font-size:clamp(22px,2.3vw,29px);}
body.woocommerce-account .woocommerce-MyAccount-content h3 {font-size:20px;}
/* Dashboard: enlaces comerciales claros sin duplicar la navegación. */
.emdo-account-hub {margin-top:27px;border-top:1px solid var(--emdo-account-line);padding-top:27px;}
.emdo-account-hub__heading {display:flex;flex-wrap:wrap;align-items:end;justify-content:space-between;gap:10px;margin-bottom:17px;}
.emdo-account-hub__heading h2 {margin:0!important;font-family:Georgia,"Times New Roman",serif;font-weight:700;letter-spacing:-.025em;}
.emdo-account-hub__heading p {margin:0;color:var(--emdo-account-muted);font-size:13px;}
.emdo-account-hub__grid {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-tile {
	display:flex!important;align-items:flex-start;gap:14px;min-width:0;
	padding:18px!important;border:1px solid #e8e6df!important;border-radius:16px!important;
	background:#fcfbf7!important;color:#254435!important;text-decoration:none!important;
	transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease;
}
body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-tile:hover,
body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-tile:focus-visible {
	border-color:#adcab8!important;box-shadow:0 13px 28px rgba(23,63,49,.09);transform:translateY(-2px);
}
.emdo-account-tile__icon {display:grid;place-items:center;flex:0 0 43px;width:43px;height:43px;background:#e9f0e7;border-radius:13px;color:#244e38;}
.emdo-account-tile__icon svg {height:21px;width:21px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.emdo-account-tile__body {display:block;min-width:0;flex:1;}
.emdo-account-tile__title {display:block;color:#244535;font-weight:850;font-size:15px;line-height:1.3;margin:1px 0 5px;}
.emdo-account-tile__desc {display:block;color:#6c786c;font-size:12px;line-height:1.5;}
.emdo-account-tile__end {margin-left:auto;font-size:18px;line-height:1.2;color:#7b947f;}
.emdo-account-hub__banner {
	display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap;
	margin-top:17px;padding:20px 22px;background:linear-gradient(115deg,#183e30,#2e6244);
	color:#fff;border-radius:17px;
}
.emdo-account-hub__banner strong {display:block;color:#fff!important;font-size:20px;line-height:1.2;margin:0 0 4px;}
.emdo-account-hub__banner span {display:block;color:#d7e5d9;font-size:13px;line-height:1.5;}
body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-hub__banner a {
	display:inline-flex!important;align-items:center;justify-content:center;min-height:43px;
	padding:11px 16px!important;border-radius:10px!important;background:#fff9ea!important;
	color:#204b36!important;text-decoration:none!important;font-size:13px;font-weight:850;
}
body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-hub__banner a:hover {background:#fff!important;}
/* Datos personales, direcciones, pedidos y métodos de pago. */
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-Addresses {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin:16px 0;}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-Address,
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-Addresses .col-1,
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-Addresses .col-2 {
	float:none!important;width:100%!important;max-width:none!important;margin:0!important;
	background:#faf9f4;border:1px solid var(--emdo-account-line);
	border-radius:15px;padding:19px!important;
}
body.woocommerce-account .woocommerce-Address-title {display:flex;align-items:center;justify-content:space-between;gap:10px;}
body.woocommerce-account .woocommerce-Address-title h3 {margin:0;}
body.woocommerce-account .woocommerce-Address-title .edit {float:none!important;white-space:nowrap;}
body.woocommerce-account .woocommerce-MyAccount-content address {font-style:normal;line-height:1.9;color:#526256;margin-top:15px;}
body.woocommerce-account .woocommerce-MyAccount-content form .form-row {margin:0 0 17px!important;padding:0!important;}
body.woocommerce-account .woocommerce-MyAccount-content .form-row label,
body.woocommerce-account .woocommerce form.login .form-row label,
body.woocommerce-account .woocommerce form.register .form-row label {display:block;margin:0 0 7px;color:#2d4838;font-size:13px;font-weight:750;}
body.woocommerce-account .woocommerce-MyAccount-content .form-row input.input-text,
body.woocommerce-account .woocommerce-MyAccount-content .form-row select,
body.woocommerce-account .woocommerce-MyAccount-content .form-row textarea,
body.woocommerce-account .woocommerce form.login input.input-text,
body.woocommerce-account .woocommerce form.register input.input-text {
	display:block;width:100%!important;max-width:100%!important;
	min-height:47px;padding:11px 13px!important;color:#183d2c!important;
	background:#fff!important;border:1px solid #cdd8ce!important;border-radius:10px!important;
	font-size:15px!important;line-height:1.4!important;
	box-shadow:none!important;box-sizing:border-box!important;
}
body.woocommerce-account .woocommerce-MyAccount-content .form-row textarea {min-height:112px;resize:vertical;}
body.woocommerce-account .woocommerce-MyAccount-content fieldset {
	padding:19px;margin:22px 0;border:1px solid #e2e8df;border-radius:15px;background:#fbfcf9;
}
body.woocommerce-account .woocommerce-MyAccount-content legend {font-size:15px;font-weight:800;color:#2a4b37;padding:0 9px;}
body.woocommerce-account .woocommerce-MyAccount-content .select2-container {max-width:100%!important;}
body.woocommerce-account .woocommerce-MyAccount-content .select2-selection--single {
	min-height:47px!important;border:1px solid #cdd8ce!important;border-radius:10px!important;
	display:flex!important;align-items:center;padding:7px 9px;
}
body.woocommerce-account .woocommerce-MyAccount-content table.shop_table,
body.woocommerce-account .woocommerce-MyAccount-content table.woocommerce-orders-table,
body.woocommerce-account .woocommerce-MyAccount-content table.woocommerce-table--order-details {
	width:100%!important;border:1px solid #e5e8e1!important;border-radius:14px!important;
	border-collapse:separate!important;border-spacing:0!important;box-shadow:none!important;
}
body.woocommerce-account .woocommerce-MyAccount-content table th {background:#f7f8f4;color:#385543!important;font-size:12px!important;letter-spacing:.02em;}
body.woocommerce-account .woocommerce-MyAccount-content table td,
body.woocommerce-account .woocommerce-MyAccount-content table th {padding:12px!important;vertical-align:middle;}
body.woocommerce-account .woocommerce-MyAccount-content table tr:last-child td {border-bottom:0!important;}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details,
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details {margin-top:24px;}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-pagination {display:flex;gap:10px;flex-wrap:wrap;}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-pagination a {border-radius:10px;}
body.woocommerce-account .woocommerce-MyAccount-content :is(.button,button.button,input.button,.woocommerce-Button,.woocommerce-button) {
	display:inline-flex!important;align-items:center!important;justify-content:center!important;
	text-align:center!important;min-height:42px;padding:10px 16px!important;
	border:1px solid #234e37!important;border-radius:10px!important;background:#234e37!important;
	color:#fff!important;text-decoration:none!important;font-weight:800!important;
	line-height:1.4!important;cursor:pointer;box-sizing:border-box;
}
body.woocommerce-account .woocommerce-MyAccount-content :is(.button,button.button,input.button,.woocommerce-Button,.woocommerce-button):hover {background:#163c2b!important;border-color:#163c2b!important;}
body.woocommerce-account :is(.woocommerce-MyAccount-content,.woocommerce-form-login,.woocommerce-form-register) :is(a,input,button,select,textarea):focus-visible,
body.woocommerce-account .woocommerce-MyAccount-navigation a:focus-visible {
	outline:3px solid #bb9a65!important;outline-offset:3px!important;
}
/* Club EMDO: misma arquitectura visual sin anular la lógica de recarga/canje. */
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club {max-width:100%!important;margin:0!important;}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-hero {
	border-radius:18px!important;padding:clamp(23px,3vw,31px)!important;
	margin-bottom:20px!important;background:linear-gradient(120deg,#163d2e,#326247)!important;
	color:#f9f6ec!important;
	border:1px solid rgba(255,255,255,.13)!important;
	box-shadow:0 18px 44px rgba(18,57,39,.15);
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-hero :is(h2,h3,p,span,strong,small,b,em),
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-hero .amount,
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-hero .woocommerce-Price-currencySymbol {
	color:#fffdf5!important;-webkit-text-fill-color:#fffdf5!important;text-shadow:none!important;
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-hero > span {
	color:#e4d3ac!important;-webkit-text-fill-color:#e4d3ac!important;opacity:1!important;font-size:11px;letter-spacing:.13em;
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-hero > p {
	color:#e4ede4!important;-webkit-text-fill-color:#e4ede4!important;opacity:1!important;
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-hero > strong {
	display:block;margin:12px 0;font-size:clamp(29px,4vw,37px);line-height:1.25;
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-card {
	border:1px solid var(--emdo-account-line)!important;border-radius:16px!important;
	background:#fdfcf9!important;padding:22px!important;
	box-shadow:0 5px 18px rgba(23,63,49,.035);
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-columns {gap:16px!important;}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club table {border-radius:12px!important;}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-amounts {display:flex;gap:8px;flex-wrap:wrap;margin:15px 0;}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-amounts .button {
	background:#e8f0e6!important;border-color:#cfe2d0!important;color:#234e37!important;
	margin:0!important;min-width:70px!important;
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club form label {font-size:13px;color:#345342;}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club :is(input:not([type=hidden]),textarea) {
	border-radius:10px!important;border:1px solid #cbd9cd!important;background:#fff!important;min-height:45px;
}
body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-buy button.alt {width:100%;margin-top:12px!important;}
/* Acceso y registro: escritorio en dos tarjetas, móvil en columna. */
body.woocommerce-account:not(.logged-in) .site-content .woocommerce {max-width:1040px;margin-inline:auto;}
body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set {
	display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));
	gap:22px;align-items:start;margin-top:16px;
}
body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set::before,
body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set::after {display:none!important;}
body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set > :is(.u-column1,.u-column2,.col-1,.col-2) {
	float:none!important;width:100%!important;max-width:none!important;margin:0!important;min-width:0!important;
}
body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set h2 {
	font-family:Georgia,"Times New Roman",serif;
	font-size:clamp(23px,2.5vw,30px);color:#254331;margin:0 0 16px;
}
body.woocommerce-account:not(.logged-in) .woocommerce form.login,
body.woocommerce-account:not(.logged-in) .woocommerce form.register {
	padding:clamp(22px,3vw,33px)!important;
	border-radius:20px!important;border:1px solid var(--emdo-account-line)!important;
	box-shadow:0 12px 37px rgba(23,63,49,.065)!important;margin:0!important;
	background:#fff!important;
}
body.woocommerce-account:not(.logged-in) .woocommerce form .form-row {margin-bottom:17px!important;}
body.woocommerce-account:not(.logged-in) .woocommerce form :is(button.button,input.button) {
	display:inline-flex;justify-content:center;align-items:center;min-height:46px;
	background:#244c39!important;color:#fff!important;border-radius:10px!important;border:0!important;
	padding:11px 23px!important;float:none!important;
}
body.woocommerce-account:not(.logged-in) .woocommerce .woocommerce-form-login__rememberme {display:inline-flex!important;align-items:center;gap:7px;}
body.woocommerce-account:not(.logged-in) .woocommerce .woocommerce-form-login__rememberme input {margin:0;}
body.woocommerce-account:not(.logged-in) .woocommerce .lost_password {margin:15px 0 0!important;}
body.woocommerce-account:not(.logged-in) .woocommerce .woocommerce-privacy-policy-text {color:#617064;font-size:13px;line-height:1.65;}
.emdo-account-login-intro {padding:19px 22px 19px;margin-bottom:24px;background:#f7f7f0;border:1px solid #e7e9df;border-radius:17px;}
.emdo-account-login-intro strong {display:block;font-size:18px;color:#214734;margin-bottom:3px;}
.emdo-account-login-intro p {margin:0;color:#69766a;line-height:1.6;}
@media(max-width:1000px){
	body.woocommerce-account .site-content .woocommerce:has(> .woocommerce-MyAccount-navigation) {grid-template-columns:minmax(185px,215px) minmax(0,1fr);gap:17px;}
	body.woocommerce-account .woocommerce-MyAccount-content {padding:23px!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-columns {grid-template-columns:1fr!important;}
}
@media(max-width:767px){
	body.woocommerce-account .site-content .woocommerce:has(> .woocommerce-MyAccount-navigation) {display:block!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation {position:static!important;margin-bottom:18px!important;padding:12px!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation::before {padding:8px 9px 12px;}
	body.woocommerce-account .woocommerce-MyAccount-navigation ul {display:flex!important;overflow-x:auto;gap:8px;padding:2px 2px 6px!important;scroll-snap-type:x proximity;scrollbar-width:thin;}
	body.woocommerce-account .woocommerce-MyAccount-navigation li {flex:none;scroll-snap-align:start;}
	body.woocommerce-account .woocommerce-MyAccount-navigation li a {white-space:nowrap;min-height:41px;padding:9px 12px!important;font-size:13px!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation-link--customer-logout {border-top:0!important;margin-top:0!important;padding-top:0!important;}
	body.woocommerce-account .woocommerce-MyAccount-content {padding:20px!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-Addresses {grid-template-columns:1fr;}
	body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set {grid-template-columns:1fr!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table {display:block;overflow-x:auto;-webkit-overflow-scrolling:touch;}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details table {display:block;overflow-x:auto;}
}
@media(max-width:560px){
	body.woocommerce-account .site-content .woocommerce {font-size:14px;}
	body.woocommerce-account .woocommerce-MyAccount-content {padding:17px!important;border-radius:16px!important;}
	.emdo-account-hub__grid {grid-template-columns:1fr;}
	.emdo-account-hub__banner {padding:19px;align-items:stretch;}
	body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-hub__banner a {width:100%;}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-card {padding:16px!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-hero {padding:19px!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-hero strong {font-size:26px!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club table {display:block;max-width:100%;overflow-x:auto;}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club .mdo-club-amounts .button {flex:1 1 auto;}
}
@media(prefers-reduced-motion:reduce){
	body.woocommerce-account .emdo-account-tile,
	body.woocommerce-account .woocommerce-MyAccount-navigation li a {transition:none!important;}
}
CSS
	);
}, 130 );

/** Tarjetas de navegación en el dashboard, sin consultas de pedidos ni cambios en transacciones. */
add_action( 'woocommerce_account_dashboard', static function (): void {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_get_account_endpoint_url' ) ) { return; }
	$cards = array(
		array( 'orders', 'Mis pedidos', 'Revisa tus compras, estados y detalles.', 'orders' ),
		array( 'monedero', 'Mi monedero', 'Recompensas, recargas y tarjetas regalo.', 'wallet' ),
		array( 'edit-address', 'Mis direcciones', 'Actualiza tus datos de envío y facturación.', 'map' ),
		array( 'edit-account', 'Datos personales', 'Gestiona tu correo y contraseña.', 'user' ),
	);
	$icons = array(
		'orders' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>',
		'wallet' => '<rect x="3" y="6" width="18" height="14" rx="3"/><path d="M3 10V7a3 3 0 0 1 3-3h12M15 13h6M16.5 13h.01"/>',
		'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3zM9 3v15M15 6v15"/>',
		'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
	);
	echo '<section class="emdo-account-hub" aria-labelledby="emdo-account-hub-title">';
	echo '<div class="emdo-account-hub__heading"><h2 id="emdo-account-hub-title">¿Qué quieres hacer?</h2><p>Todo tu mercado, en un solo lugar</p></div>';
	echo '<div class="emdo-account-hub__grid">';
	foreach ( $cards as $card ) {
		list( $endpoint, $title, $description, $icon ) = $card;
		$url = wc_get_account_endpoint_url( $endpoint );
		echo '<a class="emdo-account-tile" href="' . esc_url( $url ) . '">';
		echo '<span class="emdo-account-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24">' . $icons[ $icon ] . '</svg></span>';
		echo '<span class="emdo-account-tile__body"><span class="emdo-account-tile__title">' . esc_html( $title ) . '</span><span class="emdo-account-tile__desc">' . esc_html( $description ) . '</span></span>';
		echo '<span class="emdo-account-tile__end" aria-hidden="true">›</span></a>';
	}
	echo '</div>';
	$balance = null;
	if ( class_exists( 'MDO_Club_Ledger' ) && function_exists( 'wc_price' ) ) {
		$balance = wp_kses_post( wc_price( MDO_Club_Ledger::money( MDO_Club_Ledger::balance( get_current_user_id() ) ) ) );
	}
	$gift_page_id = (int) get_option( 'mdo_club_gift_page', 0 );
	$gift_url = $gift_page_id && 'publish' === get_post_status( $gift_page_id )
		? get_permalink( $gift_page_id )
		: home_url( '/tarjetas-regalo/' );
	echo '<div class="emdo-account-hub__banner"><div>';
	echo '<strong>Club EMDO' . ( null !== $balance ? ' · ' . $balance : '' ) . '</strong>';
	echo '<span>Compra con origen, disfruta de recompensas y comparte buenos regalos.</span></div>';
	echo '<a href="' . esc_url( $gift_url ) . '">Descubrir tarjetas regalo</a></div>';
	echo '</section>';
}, 25 );

/** Una bienvenida breve cuando el visitante accede o crea una cuenta. */
add_action( 'woocommerce_before_customer_login_form', static function (): void {
	if ( is_user_logged_in() ) { return; }
	echo '<div class="emdo-account-login-intro"><strong>Bienvenido a El Mercado de Origen</strong>';
	echo '<p>Accede a tus pedidos, direcciones y recompensas de Club EMDO desde un mismo lugar.</p></div>';
}, 5 );


/**
 * Hide legacy account entries which aren't part of the customer journey.
 * This affects WooCommerce's My Account menu only. Underlying routes,
 * admin controls and integrations stay untouched.
 */
add_filter( 'woocommerce_account_menu_items', static function( array $items ): array {
	foreach ( $items as $endpoint => $label ) {
		$name = strtolower( (string) $endpoint . ' ' . wp_strip_all_tags( (string) $label ) );
		if ( preg_match( '/afiliad|afiliat|affiliate|consulta|consultation|enquir|product[\s_-]*feed|feed[\s_-]*product/i', $name ) ) {
			unset( $items[ $endpoint ] );
		}
	}
	return $items;
}, PHP_INT_MAX );
