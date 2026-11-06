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
	wp_register_style( 'emdo-mi-cuenta-ux', false, array(), '20261106.5' );
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
	overflow-wrap:break-word;
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
	margin-top:17px;padding:22px 25px;background:linear-gradient(115deg,#183e30,#2e6244);
	color:#fff;border-radius:17px;
}
.emdo-account-hub__banner-copy {display:flex;flex-direction:column;gap:11px;min-width:0;flex:1 1 230px;}
.emdo-account-hub__banner-title {display:block;color:#fff!important;font-size:19px!important;line-height:1.3!important;font-weight:800!important;margin:0!important;}
.emdo-account-hub__banner-balance {display:flex;align-items:baseline;gap:11px;flex-wrap:wrap;min-width:0;}
.emdo-account-hub__balance-caption {display:inline!important;font-size:12px!important;color:#d6e7d9!important;line-height:1.5!important;font-weight:550!important;}
.emdo-account-hub__balance-value {
	display:inline-block!important;white-space:nowrap!important;color:#fffdf5!important;
	font-size:clamp(24px,3vw,30px)!important;font-weight:800!important;line-height:1.15!important;
}
.emdo-account-hub__balance-value .woocommerce-Price-amount,
.emdo-account-hub__balance-value .woocommerce-Price-amount bdi,
.emdo-account-hub__balance-value .woocommerce-Price-currencySymbol {
	display:inline!important;white-space:nowrap!important;float:none!important;
	font-size:inherit!important;line-height:inherit!important;color:#fffdf5!important;
}
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
	body.woocommerce-account .woocommerce-MyAccount-navigation ul {display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;overflow:visible!important;scroll-snap-type:none!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation li {min-width:0!important;float:none!important;display:block!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation li a {min-width:0!important;width:100%!important;white-space:normal!important;min-height:44px;padding:10px 12px!important;font-size:13px!important;line-height:1.4!important;overflow-wrap:break-word!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation-link--customer-logout {border-top:0!important;margin-top:0!important;padding-top:0!important;}
	body.woocommerce-account .woocommerce-MyAccount-content {padding:20px!important;}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-Addresses {grid-template-columns:1fr;}
	body.woocommerce-account:not(.logged-in) .woocommerce .u-columns.col2-set {grid-template-columns:1fr!important;}
	/* Pedidos: tarjetas verticales. Reemplaza la tabla móvil con contenido legible. */
	/* Detalle: tabla normalizada a filas flexibles más abajo. */
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

/* Mi cuenta: pulido transversal de tablas, pedidos y navegación. */
body.woocommerce-account .woocommerce-MyAccount-content,
body.woocommerce-account .woocommerce-MyAccount-content table,
body.woocommerce-account .woocommerce-MyAccount-content :is(th,td,p,li,dd,dt,a,span) {
	word-break:normal!important;
	overflow-wrap:break-word;
}
body.woocommerce-account .woocommerce-MyAccount-content table :is(th,td) {
	min-width:0;
	vertical-align:middle;
	white-space:normal;
}
body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-order-details,.woocommerce-customer-details,.woocommerce-order-downloads,.woocommerce-OrderUpdates) {
	min-width:0;
	max-width:100%;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details .woocommerce-table__product-name a {
	text-decoration:none;
	font-weight:750;
	line-height:1.55;
}
body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-OrderUpdate,.woocommerce-Address) {
	overflow-wrap:break-word;
}
body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-MyAccount-downloads-file,.woocommerce-MyAccount-paymentMethods) {
	max-width:100%;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details address,
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details .woocommerce-column {
	min-width:0;
	max-width:100%;
	white-space:normal;
	overflow-wrap:break-word;
}
body.woocommerce-account .woocommerce-MyAccount-content .emdo-account-order-back {
	display:inline-flex;align-items:center;gap:6px;margin:0 0 18px;
	padding:8px 12px;border:1px solid #dfebe0;border-radius:10px;
	background:#f4f8f3;text-decoration:none!important;font-size:13px;font-weight:750;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details .wc-item-meta {
	margin:8px 0 0!important;padding:0!important;
	list-style:none;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details .wc-item-meta li {
	display:flex;flex-wrap:wrap;gap:4px 8px;align-items:baseline;margin:3px 0;line-height:1.6;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details .wc-item-meta :is(strong,.wc-item-meta-label) {
	margin:0;font-weight:700;color:#496755;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details .wc-item-meta p {
	margin:0!important;overflow-wrap:anywhere;
}
body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-OrderUpdates li {
	padding:12px 15px;background:#fafbf8;border-radius:12px;margin:10px 0;
}
@media(max-width:1023px) {
	body.woocommerce-account .site-content .woocommerce:has(> .woocommerce-MyAccount-navigation) {
		display:block!important;
		width:100%!important;
		max-width:100%!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-navigation {
		position:static!important;
		width:100%!important;
		margin:0 0 19px!important;
		padding:14px!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-navigation::before {padding:7px 9px 11px;}
	body.woocommerce-account .woocommerce-MyAccount-navigation ul {
		display:grid!important;
		grid-template-columns:repeat(3,minmax(0,1fr))!important;
		gap:8px!important;
		padding:0!important;
		margin:0!important;
		overflow:visible!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-navigation li {
		display:block!important;
		float:none!important;
		min-width:0!important;
		margin:0!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-navigation li a {
		display:flex!important;align-items:center!important;
		min-width:0!important;width:100%!important;
		min-height:44px!important;
		padding:10px 12px!important;
		white-space:normal!important;
		overflow-wrap:break-word!important;
		word-break:normal!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content {
		max-width:100%!important;
	}
}
@media(max-width:767px) {
	body.woocommerce-account .woocommerce-MyAccount-navigation ul {
		grid-template-columns:repeat(2,minmax(0,1fr))!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content {
		padding:clamp(16px,4vw,23px)!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-orders-table,.woocommerce-table--order-details) {
		width:100%!important;max-width:100%!important;
		display:block!important;
		overflow:visible!important;
		border:0!important;background:transparent!important;
		border-radius:0!important;
		table-layout:auto!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-orders-table,.woocommerce-table--order-details) thead {
		display:none!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-orders-table,.woocommerce-table--order-details) tbody {
		display:grid!important;
		gap:12px!important;
		width:100%!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody tr {
		display:flex!important;
		flex-direction:column!important;
		width:100%!important;
		border:1px solid #e1e7dd!important;
		background:#fff!important;
		border-radius:15px!important;
		box-shadow:0 5px 17px rgba(21,63,43,.045);
		overflow:hidden;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody td {
		display:flex!important;
		align-items:center!important;
		justify-content:space-between!important;
		flex-wrap:wrap!important;
		gap:8px 14px!important;
		width:100%!important;
		max-width:100%!important;
		padding:11px 15px!important;
		margin:0!important;
		border:0!important;
		border-bottom:1px solid #f0f1eb!important;
		text-align:right!important;
		box-sizing:border-box!important;
		font-size:14px!important;
		line-height:1.55!important;
		white-space:normal!important;
		word-break:normal!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody td:last-child {
		border-bottom:0!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody td::before {
		content:attr(data-title)!important;
		display:block!important;
		float:none!important;
		flex:0 1 auto!important;
		min-width:0!important;
		margin:0!important;
		font-size:12px!important;
		font-weight:750!important;
		color:#607266!important;
		text-align:left!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody .woocommerce-orders-table__cell-order-number {
		background:#f5f8f3!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody .woocommerce-orders-table__cell-order-number a {
		font-size:16px!important;font-weight:850!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody .woocommerce-orders-table__cell-order-actions {
		justify-content:flex-end!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody .woocommerce-orders-table__cell-order-actions::before {
		margin-right:auto!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-orders-table tbody .woocommerce-orders-table__cell-order-actions .button {
		min-height:39px!important;white-space:normal!important;
	}
	/* Detalle del pedido: dos columnas flexibles, sin palabras verticales. */
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tbody tr {
		display:grid!important;
		grid-template-columns:minmax(0,1fr) max-content!important;
		align-items:start!important;
		gap:6px 12px!important;
		width:100%!important;
		padding:14px 15px!important;
		margin:0!important;
		border:1px solid #e4e9df!important;
		border-radius:13px!important;
		background:#fff!important;
		box-sizing:border-box!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tbody :is(th,td) {
		display:block!important;
		width:auto!important;min-width:0!important;max-width:100%!important;
		padding:0!important;border:0!important;
		text-align:left!important;
		word-break:normal!important;
		white-space:normal!important;
		line-height:1.65!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tbody .product-total {
		text-align:right!important;white-space:nowrap!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tfoot {
		display:block!important;
		margin-top:13px!important;
		width:100%!important;
		border:1px solid #e4e9df!important;
		border-radius:13px!important;
		overflow:hidden!important;
		background:#f8faf6!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tfoot tr {
		display:grid!important;
		grid-template-columns:minmax(0,1fr) minmax(90px,auto)!important;
		align-items:baseline!important;
		gap:12px!important;
		padding:12px 15px!important;
		margin:0!important;
		border-bottom:1px solid #e9ede6!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tfoot tr:last-child {
		border-bottom:0!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tfoot :is(th,td) {
		display:block!important;width:auto!important;min-width:0!important;
		padding:0!important;margin:0!important;
		border:0!important;word-break:normal!important;
		white-space:normal!important;font-size:14px!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tfoot th {
		text-align:left!important;color:#496253!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tfoot td {
		text-align:right!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details :is(.woocommerce-Price-amount, .woocommerce-Price-amount bdi, .woocommerce-Price-currencySymbol) {
		white-space:nowrap!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-order-details > h2,
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details > h2 {
		font-size:clamp(20px,5.4vw,26px)!important;
		overflow-wrap:break-word!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details .woocommerce-columns {
		display:grid!important;grid-template-columns:1fr!important;gap:15px!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details .woocommerce-column {
		width:100%!important;float:none!important;
		margin:0!important;padding:0!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-customer-details address {
		padding:17px!important;background:#fafbf7;
		border:1px solid #e3e9df;border-radius:13px;
	}
	/* Tablas adicionales de extensiones: desplazamiento del bloque, nunca
	   convertir las celdas en columnas microscópicas. */
	body.woocommerce-account .woocommerce-MyAccount-content :is(.woocommerce-MyAccount-downloads,.woocommerce-MyAccount-paymentMethods,.woocommerce-table--order-downloads) {
		display:block!important;
		max-width:100%!important;
		overflow-x:auto!important;
		-webkit-overflow-scrolling:touch;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club {
		max-width:100%!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-columns {
		grid-template-columns:minmax(0,1fr)!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club-card {
		min-width:0!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club table {
		display:block!important;
		max-width:100%!important;
		overflow-x:auto!important;
		-webkit-overflow-scrolling:touch;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .mdo-club :is(input,textarea,select) {
		max-width:100%!important;
		box-sizing:border-box!important;
	}
}
@media(max-width:400px) {
	body.woocommerce-account .woocommerce-MyAccount-navigation {
		padding:11px!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-navigation ul {gap:6px!important;}
	body.woocommerce-account .woocommerce-MyAccount-navigation li a {
		padding:10px 9px!important;
		font-size:12.5px!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tbody tr {
		grid-template-columns:minmax(0,1fr)!important;
	}
	body.woocommerce-account .woocommerce-MyAccount-content .woocommerce-table--order-details tbody .product-total {
		text-align:left!important;
		margin-top:2px!important;
	}
}

/* Nota de cliente añadida por una personalización de pedido (no pertenece
   al catálogo de etiquetas de WooCommerce). Siempre un formulario vertical. */
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) {
	display:block!important;
	width:100%!important;
	max-width:100%!important;
	min-width:0!important;
	margin:15px 0 25px!important;
	padding:clamp(15px,3.5vw,24px)!important;
	background:#fafbf8!important;
	border:1px solid #e1e9df!important;
	border-radius:16px!important;
	box-sizing:border-box!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) table.woocommerce-table,
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) tbody {
	display:block!important;
	width:100%!important;
	min-width:0!important;
	max-width:100%!important;
	margin:0!important;
	padding:0!important;
	border:0!important;
	background:transparent!important;
	box-shadow:none!important;
	table-layout:auto!important;
	overflow:visible!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) table.woocommerce-table tr {
	display:block!important;
	width:100%!important;
	min-width:0!important;
	max-width:100%!important;
	margin:0 0 18px!important;
	padding:0!important;
	background:transparent!important;
	border:0!important;
	box-sizing:border-box!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) table.woocommerce-table tr:last-child {
	margin-bottom:0!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) table.woocommerce-table :is(th,td) {
	display:block!important;
	float:none!important;
	width:100%!important;
	min-width:0!important;
	max-width:100%!important;
	height:auto!important;
	padding:0!important;
	margin:0!important;
	border:0!important;
	background:transparent!important;
	vertical-align:top!important;
	text-align:left!important;
	word-break:normal!important;
	overflow-wrap:break-word!important;
	box-sizing:border-box!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) table.woocommerce-table th {
	margin-bottom:8px!important;
	color:#2a4a38!important;
	font-size:14px!important;
	font-weight:750!important;
	line-height:1.4!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) label {
	display:block!important;
	margin:0!important;
	color:#2a4a38!important;
	font-weight:750!important;
	line-height:1.45!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) textarea[name="wcfm_cus_add_note"] {
	display:block!important;
	width:100%!important;
	min-width:0!important;
	max-width:100%!important;
	min-height:145px!important;
	padding:13px!important;
	border:1px solid #cddacb!important;
	border-radius:12px!important;
	background:#fff!important;
	font:inherit!important;
	font-size:16px!important;
	color:#264332!important;
	line-height:1.55!important;
	resize:vertical!important;
	box-sizing:border-box!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) input[type="file"] {
	display:block!important;
	width:100%!important;
	min-width:0!important;
	max-width:100%!important;
	margin:0!important;
	padding:9px!important;
	border:1px solid #cddacb!important;
	border-radius:10px!important;
	background:#fff!important;
	color:#2c4a39!important;
	font-size:13px!important;
	box-sizing:border-box!important;
	white-space:normal!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) input[type="file"]::file-selector-button {
	padding:8px 10px;
	margin-right:10px;
	border:0;
	border-radius:8px;
	background:#e7f0e8;
	color:#224f38;
	font:inherit;
	font-weight:750;
	cursor:pointer;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) input[type="submit"] {
	display:inline-flex!important;
	align-items:center!important;
	justify-content:center!important;
	width:auto!important;
	max-width:100%!important;
	min-height:44px!important;
	padding:11px 23px!important;
	margin:0!important;
	background:#234e37!important;
	color:#fff!important;
	border:1px solid #234e37!important;
	border-radius:10px!important;
	font-size:14px!important;
	font-weight:800!important;
	line-height:1.35!important;
	text-align:center!important;
	white-space:normal!important;
	cursor:pointer!important;
}
body.woocommerce-account .woocommerce-MyAccount-content form:has(textarea[name="wcfm_cus_add_note"]) input[type="submit"]:hover {
	background:#153d29!important;
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
	echo '<div class="emdo-account-hub__heading"><h2 id="emdo-account-hub-title">¿Qué quieres hacer?</h2></div>';
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
	// The main dashboard CTA opens the complete Club experience:
	// balance, rewards, top-ups and gift-card redemption.
	$club_url = wc_get_account_endpoint_url( 'monedero' );
	echo '<div class="emdo-account-hub__banner"><div class="emdo-account-hub__banner-copy">';
	echo '<strong class="emdo-account-hub__banner-title">Club EMDO</strong>';
	if ( null !== $balance ) {
		echo '<div class="emdo-account-hub__banner-balance"><span class="emdo-account-hub__balance-caption">Saldo disponible</span><span class="emdo-account-hub__balance-value">' . $balance . '</span></div>';
	}
	echo '</div><a href="' . esc_url( $club_url ) . '">Ir a Club EMDO</a></div>';
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


/**
 * Traducciones de respaldo para la parte privada de WooCommerce.
 * Algunas plantillas o extensiones pueden renderizar etiquetas en inglés
 * aunque la tienda esté en castellano. Se aplican solo a textos exactos
 * de interfaz en Mi cuenta; nunca traducimos nombres de productos,
 * observaciones de pedidos ni contenido escrito por clientes.
 */
add_action( 'wp', static function(): void {
	// Never add a global gettext filter while WordPress/WooCommerce is booting.
	// It can recursively load options/translations and exhaust PHP memory.
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) { return; }
	add_filter( 'gettext', static function( string $translation, string $text, string $domain ): string {
	if ( ! in_array( $domain, array( 'woocommerce', 'woostify', 'wc-frontend-manager-ultimate' ), true ) ) {
		return $translation;
	}
	static $labels = array(
		'My account' => 'Mi cuenta',
		'Dashboard' => 'Inicio',
		'Orders' => 'Pedidos',
		'Order' => 'Pedido',
		'Order details' => 'Detalles del pedido',
		'Add your note' => 'Añadir una nota al pedido',
		'Add Note' => 'Mensaje',
		'Upload File' => 'Adjuntar archivo (opcional)',
		'Submit' => 'Enviar nota',
		'See Attachment' => 'Ver archivo adjunto',
		'Your note succesfully submitted.' => 'Tu nota se ha enviado correctamente.',
		'Order Details' => 'Detalles del pedido',
		'Order updates' => 'Actualizaciones del pedido',
		'Order notes' => 'Notas del pedido',
		'Order received' => 'Pedido recibido',
		'Order again' => 'Volver a comprar',
		'Order number' => 'Número de pedido',
		'Order number:' => 'Número de pedido:',
		'Order date' => 'Fecha del pedido',
		'Order status' => 'Estado del pedido',
		'Order #%1$s was placed on %2$s and is currently %3$s.' => 'El pedido n.º %1$s se realizó el %2$s y su estado actual es %3$s.',
		'No order has been made yet.' => 'Todavía no has realizado ningún pedido.',
		'Browse products' => 'Ver productos',
		'Date' => 'Fecha',
		'Status' => 'Estado',
		'Total' => 'Total',
		'Subtotal' => 'Subtotal',
		'Product' => 'Producto',
		'Products' => 'Productos',
		'Quantity' => 'Cantidad',
		'Qty' => 'Cant.',
		'Actions' => 'Acciones',
		'View' => 'Ver',
		'View order' => 'Ver pedido',
		'Pay' => 'Pagar',
		'Cancel' => 'Cancelar',
		'Downloads' => 'Descargas',
		'Download' => 'Descargar',
		'Addresses' => 'Direcciones',
		'Billing address' => 'Dirección de facturación',
		'Shipping address' => 'Dirección de envío',
		'Billing details' => 'Datos de facturación',
		'Shipping details' => 'Datos de envío',
		'Billing & Shipping' => 'Facturación y envío',
		'Customer details' => 'Datos del cliente',
		'Payment method' => 'Método de pago',
		'Payment methods' => 'Métodos de pago',
		'Payment details' => 'Datos de pago',
		'Shipping' => 'Envío',
		'Discount' => 'Descuento',
		'Tax' => 'Impuestos',
		'Note' => 'Nota',
		'Customer note' => 'Nota del cliente',
		'Account details' => 'Datos personales',
		'Edit account' => 'Editar cuenta',
		'Save changes' => 'Guardar cambios',
		'Edit' => 'Editar',
		'Log out' => 'Cerrar sesión',
		'Log in' => 'Acceder',
		'Register' => 'Registrarse',
		'Lost your password?' => '¿Has olvidado tu contraseña?',
		'Pending payment' => 'Pendiente de pago',
		'Processing' => 'En preparación',
		'On hold' => 'En espera',
		'Completed' => 'Completado',
		'Cancelled' => 'Cancelado',
		'Failed' => 'Fallido',
		'Refunded' => 'Reembolsado',
	);
	// Don't clobber an existing Spanish translation (or a text with different
	// meaning); only use the fallback when the label is still English.
	return $translation === $text && isset( $labels[ $text ] )
		? $labels[ $text ] : $translation;
	}, 30, 3 );
}, 20 );

/** Consistent Spanish headings and responsive data-title labels in orders. */
add_filter( 'woocommerce_account_orders_columns', static function( array $columns ): array {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return $columns;
	}
	$labels = array(
		'order-number' => 'Pedido',
		'order-date' => 'Fecha',
		'order-status' => 'Estado',
		'order-total' => 'Total',
		'order-actions' => 'Acciones',
	);
	foreach ( $columns as $key => $value ) {
		if ( isset( $labels[ $key ] ) ) {
			$columns[ $key ] = $labels[ $key ];
		}
	}
	return $columns;
}, 99 );

/** Keep the account menu labels predictable in the Spanish customer area. */
add_filter( 'woocommerce_account_menu_items', static function( array $items ): array {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return $items;
	}
	$labels = array(
		'dashboard' => 'Inicio',
		'orders' => 'Mis pedidos',
		'downloads' => 'Descargas',
		'edit-address' => 'Direcciones',
		'payment-methods' => 'Métodos de pago',
		'edit-account' => 'Datos personales',
		'monedero' => 'Club EMDO',
		'customer-logout' => 'Cerrar sesión',
	);
	foreach ( $items as $endpoint => $label ) {
		if ( isset( $labels[ $endpoint ] ) ) {
			$items[ $endpoint ] = $labels[ $endpoint ];
		}
	}
	return $items;
}, 99 );

/** Clear return path when opening a previously placed order. */
add_action( 'woocommerce_order_details_before_order_table', static function( $order ): void {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page()
		|| ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'view-order' )
		|| ! function_exists( 'wc_get_account_endpoint_url' ) ) {
		return;
	}
	echo '<a class="emdo-account-order-back" href="' . esc_url( wc_get_account_endpoint_url( 'orders' ) ) . '">'
		. '<span aria-hidden="true">←</span> Volver a mis pedidos</a>';
}, 5, 1 );


/**
 * El formulario de notas del pedido es un snippet personalizado de WCFM y
 * escribe sus textos en inglés directamente, fuera de gettext. Traduce
 * solamente ese formulario identificándolo por sus campos propios, sin
 * modificar el POST, el fichero adjunto ni el funcionamiento del envío.
 */
add_action( 'wp_footer', static function(): void {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page()
		|| ! function_exists( 'is_wc_endpoint_url' )
		|| ! is_wc_endpoint_url( 'view-order' ) ) {
		return;
	}
	echo <<<'HTML'
<script id="emdo-account-order-note-es">
(function () {
	'use strict';
	function translateOrderNote() {
		var root = document.querySelector('.woocommerce-MyAccount-content');
		if (!root) return;
		var field = root.querySelector('textarea[name="wcfm_cus_add_note"],textarea#wcfm_cus_add_note');
		if (!field || !field.form) return;
		var form = field.form;
		var upload = form.querySelector('input[name="wcfm_cus_add_note_file"],input#wcfm_cus_add_note_file');
		var submit = form.querySelector('input[name="wcfm_cus_note_submit"],input#wcfm_cus_note_submit');
		if (!submit) return;
		root.querySelectorAll('h1,h2,h3,h4').forEach(function (heading) {
			if (heading.textContent.trim() === 'Add your note') {
				heading.textContent = 'Añadir una nota al pedido';
			}
		});
		form.querySelectorAll('th').forEach(function (cell) {
			var text = cell.textContent.trim();
			var translated = text === 'Add Note' ? 'Mensaje' :
				(text === 'Upload File' ? 'Adjuntar archivo (opcional)' : '');
			if (!translated) return;
			cell.textContent = '';
			var label = document.createElement('label');
			label.textContent = translated;
			if (text === 'Add Note' && field.id) label.htmlFor = field.id;
			if (text === 'Upload File' && upload && upload.id) label.htmlFor = upload.id;
			cell.appendChild(label);
		});
		if (submit.tagName === 'INPUT') {
			if (submit.value === 'Submit') submit.value = 'Enviar nota';
		} else if (submit.textContent.trim() === 'Submit') {
			submit.textContent = 'Enviar nota';
		}
		form.setAttribute('aria-label', 'Enviar una nota al pedido');
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', translateOrderNote, { once: true });
	} else {
		translateOrderNote();
	}
}());
</script>
HTML;
}, 40 );
