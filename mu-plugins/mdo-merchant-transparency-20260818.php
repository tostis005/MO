<?php
/**
 * Plugin Name: MDO Merchant Transparency
 * Description: Clear bilingual legal identity, marketplace roles, shipping, returns and trust links for El Mercado de Origen.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const MDO_MT_VERSION = '2026-08-18-1';
const MDO_MT_OPERATOR = 'José Antonio Fraga Sánchez';
const MDO_MT_NIF = '11854364A';
const MDO_MT_EMAIL = 'hola@elmercadodeorigen.com';
const MDO_MT_PHONE = '+34 603 02 95 09';
const MDO_MT_ADDRESS = 'C/ Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, España';

function mdo_mt_is_english(): bool {
    if ( function_exists( 'mdo_en_is_request' ) ) { return (bool) mdo_en_is_request(); }
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    return $path === '/en' || strpos( $path, '/en/' ) === 0;
}

function mdo_mt_policy_specs(): array {
    return array(
        'politica' => array(
            'es_title' => 'Términos y condiciones',
            'en_title' => 'Terms and Conditions',
            'en_slug' => 'terms-and-conditions',
            'es_excerpt' => 'Condiciones de uso y compra del marketplace El Mercado de Origen, con identificación clara de la plataforma y de los vendedores.',
            'en_excerpt' => 'Terms governing use and purchases on the El Mercado de Origen marketplace, with clear roles for the platform and each seller.',
            'es_content' => mdo_mt_terms_es(),
            'en_content' => mdo_mt_terms_en(),
        ),
        'politica-de-privacidad' => array(
            'es_title' => 'Política de privacidad',
            'en_title' => 'Privacy Policy',
            'en_slug' => 'privacy-policy',
            'es_excerpt' => 'Información sobre el responsable del tratamiento, finalidades, bases legales, destinatarios y derechos de protección de datos.',
            'en_excerpt' => 'Information about the data controller, purposes, legal bases, recipients and data protection rights.',
            'es_content' => mdo_mt_privacy_es(),
            'en_content' => mdo_mt_privacy_en(),
        ),
        'politica-de-cookies' => array(
            'es_title' => 'Política de cookies',
            'en_title' => 'Cookie Policy',
            'en_slug' => 'cookie-policy',
            'es_excerpt' => 'Información sobre las cookies necesarias y opcionales utilizadas en El Mercado de Origen y sobre cómo gestionar el consentimiento.',
            'en_excerpt' => 'Information about necessary and optional cookies used by El Mercado de Origen and how to manage consent.',
            'es_content' => mdo_mt_cookies_es(),
            'en_content' => mdo_mt_cookies_en(),
        ),
        'aviso-legal' => array(
            'es_title' => 'Aviso legal',
            'en_title' => 'Legal Notice',
            'en_slug' => 'legal-notice',
            'es_excerpt' => 'Identificación legal del titular de El Mercado de Origen y condiciones generales de uso del sitio web.',
            'en_excerpt' => 'Legal identification of the operator of El Mercado de Origen and general website-use information.',
            'es_content' => mdo_mt_legal_es(),
            'en_content' => mdo_mt_legal_en(),
        ),
        'envios' => array(
            'es_title' => 'Envíos',
            'en_title' => 'Shipping',
            'en_slug' => 'shipping',
            'es_excerpt' => 'Cómo funcionan los envíos en El Mercado de Origen: cada productor prepara y expide directamente sus pedidos y la plataforma hace seguimiento y presta asistencia.',
            'en_excerpt' => 'How shipping works at El Mercado de Origen: each producer prepares and dispatches its orders directly, while the platform tracks and supports the process.',
            'es_content' => mdo_mt_shipping_es(),
            'en_content' => mdo_mt_shipping_en(),
        ),
        'devoluciones-y-reembolsos' => array(
            'es_title' => 'Devoluciones y reembolsos',
            'en_title' => 'Returns and Refunds',
            'en_slug' => 'returns-refunds',
            'es_excerpt' => 'Política de devoluciones, incidencias y reembolsos del marketplace El Mercado de Origen, incluyendo las particularidades de los alimentos perecederos.',
            'en_excerpt' => 'Returns, issues and refunds policy for the El Mercado de Origen marketplace, including rules that apply to perishable food.',
            'es_content' => mdo_mt_returns_es(),
            'en_content' => mdo_mt_returns_en(),
        ),
    );
}

function mdo_mt_h( string $title, string $body ): string {
    return '<h2>' . esc_html( $title ) . '</h2>' . $body;
}

function mdo_mt_identity_es(): string {
    return '<p><strong>El Mercado de Origen</strong> es el nombre comercial de una plataforma de intermediación online operada por <strong>' . esc_html( MDO_MT_OPERATOR ) . '</strong>, trabajador autónomo, NIF <strong>' . esc_html( MDO_MT_NIF ) . '</strong>, con domicilio en ' . esc_html( MDO_MT_ADDRESS ) . '.</p><p>Contacto: <a href="mailto:' . esc_attr( MDO_MT_EMAIL ) . '">' . esc_html( MDO_MT_EMAIL ) . '</a> · <a href="tel:+34603029509">' . esc_html( MDO_MT_PHONE ) . '</a>.</p>';
}
function mdo_mt_identity_en(): string {
    return '<p><strong>El Mercado de Origen</strong> is the trading name of an online marketplace and intermediation platform operated by <strong>' . esc_html( MDO_MT_OPERATOR ) . '</strong>, a self-employed individual in Spain, tax ID/NIF <strong>' . esc_html( MDO_MT_NIF ) . '</strong>, with address at C/ Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, Spain.</p><p>Contact: <a href="mailto:' . esc_attr( MDO_MT_EMAIL ) . '">' . esc_html( MDO_MT_EMAIL ) . '</a> · <a href="tel:+34603029509">' . esc_html( MDO_MT_PHONE ) . '</a>.</p>';
}

function mdo_mt_terms_es(): string {
    return '<div class="mdo-mt-policy"><p><strong>Última actualización: 18 de agosto de 2026.</strong></p>' . mdo_mt_identity_es() .
    mdo_mt_h('1. Qué es El Mercado de Origen','<p>El Mercado de Origen es un marketplace que conecta a clientes con productores y otros vendedores independientes. La plataforma facilita la búsqueda de productos, la realización del pedido, el flujo de pago cuando corresponda, el seguimiento de la correcta gestión del pedido y la atención al cliente.</p><p>Salvo que una ficha indique expresamente otra cosa, el <strong>vendedor identificado en la ficha del producto</strong> es quien vende el producto al cliente y quien asume las obligaciones propias de esa venta. El Mercado de Origen no prepara ni expide físicamente los productos vendidos por terceros.</p>') .
    mdo_mt_h('2. Vendedores y productos','<p>Los vendedores pueden ser personas físicas o jurídicas previamente admitidas en la plataforma. Cada vendedor es responsable de la información específica de sus productos, su disponibilidad, precio, composición, características, origen, seguridad, conservación y cumplimiento de la normativa aplicable. El Mercado de Origen puede revisar, corregir, ocultar o retirar contenidos o productos cuando detecte errores, incumplimientos o riesgos para el cliente.</p>') .
    mdo_mt_h('3. Precio y condiciones antes de comprar','<p>Los precios se muestran en euros e incluyen los impuestos aplicables salvo que se indique legalmente otra cosa. Los gastos de envío, pedidos mínimos, zonas de entrega y demás condiciones que dependan del vendedor se muestran durante el proceso de compra o en la información del vendedor antes de confirmar el pedido. Un carrito con productos de varios vendedores puede originar varios envíos y, cuando corresponda, varios gastos de envío.</p>') .
    mdo_mt_h('4. Pedido y contrato de compraventa','<p>Al confirmar un pedido, el cliente declara que los datos facilitados son correctos y acepta estas condiciones y las condiciones específicas visibles antes del pago. La compraventa de cada producto se celebra con el vendedor identificado para ese producto, sujeta a disponibilidad y a las reglas imperativas de protección de consumidores que resulten aplicables.</p><p>Si un vendedor no puede atender un pedido ya pagado, el importe correspondiente será reembolsado por el mismo medio de pago cuando sea posible.</p>') .
    mdo_mt_h('5. Pago','<p>El Mercado de Origen puede facilitar o gestionar técnicamente el cobro a través de proveedores de pago. El hecho de que el pago se procese a través de la plataforma no convierte a El Mercado de Origen en vendedor de un producto vendido por un tercero. Los datos completos de tarjeta no son almacenados por El Mercado de Origen cuando son tratados directamente por el proveedor de pago.</p>') .
    mdo_mt_h('6. Facturación','<p>La factura de los productos vendidos por un vendedor corresponde al vendedor que figura en el pedido, de acuerdo con sus obligaciones fiscales. El cliente puede solicitar asistencia a El Mercado de Origen si necesita ayuda para obtenerla.</p>') .
    mdo_mt_h('7. Preparación, expedición y entrega','<p>El vendedor es responsable de la preparación, expedición y entrega de su pedido. <strong>El Mercado de Origen realiza seguimiento de la correcta gestión del pedido y presta asistencia al cliente, pudiendo mediar con el vendedor ante cualquier incidencia.</strong> Los plazos y condiciones concretos pueden variar por vendedor y destino. Consulta también nuestra página de <a href="' . esc_url( home_url('/envios/') ) . '">Envíos</a>.</p>') .
    mdo_mt_h('8. Incidencias, falta de conformidad y garantías','<p>Si un pedido no llega, llega dañado, es incorrecto o el producto no se corresponde con lo contratado, el cliente puede contactar con el vendedor o con El Mercado de Origen. La plataforma ayudará a documentar la incidencia, trasladarla al vendedor y realizar el seguimiento. Ningún plazo interno de comunicación limita los derechos que la ley reconozca al consumidor.</p>') .
    mdo_mt_h('9. Devoluciones, desistimiento y reembolsos','<p>Las devoluciones físicas se realizan al vendedor correspondiente, no a El Mercado de Origen. La plataforma facilita la comunicación y puede mediar en la resolución. Cuando el pago se haya procesado a través de la plataforma, El Mercado de Origen podrá tramitar técnicamente el reembolso por cuenta del vendedor y al medio de pago original, cuando proceda.</p><p>Cuando exista legalmente derecho de desistimiento, el consumidor dispone con carácter general de 14 días naturales, sin perjuicio de las excepciones legales. Entre otras, el desistimiento no se aplica a bienes que puedan deteriorarse o caducar con rapidez, bienes personalizados y determinados bienes precintados por razones de salud o higiene una vez desprecintados. Consulta la política completa de <a href="' . esc_url( home_url('/devoluciones-y-reembolsos/') ) . '">Devoluciones y reembolsos</a>.</p>') .
    mdo_mt_h('10. Atención al cliente','<p>El Mercado de Origen mantiene un canal de asistencia para ayudar al cliente durante todo el proceso de compra y posventa. Puedes escribir a <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>, llamar al <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a> o utilizar el formulario de <a href="' . esc_url(home_url('/contacto/')) . '">Contacto</a>.</p>') .
    mdo_mt_h('11. Responsabilidad de la plataforma','<p>El Mercado de Origen responde de sus propias obligaciones como operador de la plataforma y prestador de servicios de intermediación. Las cláusulas de estas condiciones no pretenden excluir ni limitar derechos o responsabilidades que no puedan excluirse legalmente. Cada vendedor responde de las obligaciones que le correspondan como parte vendedora.</p>') .
    mdo_mt_h('12. Protección de datos y cookies','<p>El tratamiento de datos personales se explica en la <a href="' . esc_url(home_url('/politica-de-privacidad/')) . '">Política de privacidad</a>. El uso de cookies y tecnologías similares se explica en la <a href="' . esc_url(home_url('/politica-de-cookies/')) . '">Política de cookies</a>.</p>') .
    mdo_mt_h('13. Ley aplicable y derechos del consumidor','<p>Estas condiciones se interpretan conforme a la legislación española, sin perjuicio de las normas imperativas de protección que correspondan al consumidor por su lugar de residencia. Cualquier condición particular de un vendedor podrá mejorar, pero nunca reducir, los derechos mínimos reconocidos por la normativa aplicable.</p>') . '</div>';
}

function mdo_mt_terms_en(): string {
    return '<div class="mdo-mt-policy"><p><strong>Last updated: 18 August 2026.</strong></p>' . mdo_mt_identity_en() .
    mdo_mt_h('1. What El Mercado de Origen is','<p>El Mercado de Origen is a marketplace connecting customers with independent producers and sellers. The platform facilitates product discovery, order placement, the payment flow where applicable, monitoring of order handling and customer support.</p><p>Unless a product page expressly says otherwise, the <strong>seller identified on that product page</strong> is the party selling the product to the customer and is responsible for the obligations arising from that sale. El Mercado de Origen does not physically prepare or dispatch products sold by third parties.</p>') .
    mdo_mt_h('2. Sellers and products','<p>Sellers may be individuals or legal entities admitted to the platform. Each seller is responsible for the specific information about its products, availability, price, composition, characteristics, origin, safety, storage and compliance with applicable law. El Mercado de Origen may review, correct, hide or remove content or products where it detects errors, non-compliance or risks to customers.</p>') .
    mdo_mt_h('3. Prices and pre-purchase information','<p>Prices are shown in euros and include applicable taxes unless the law requires otherwise. Shipping charges, minimum orders, delivery areas and other seller-dependent conditions are displayed during checkout or in the seller information before the order is confirmed. A basket containing products from multiple sellers may result in separate shipments and, where applicable, separate shipping charges.</p>') .
    mdo_mt_h('4. Orders and sales contracts','<p>By confirming an order, the customer confirms that the information supplied is accurate and accepts these terms and the specific conditions displayed before payment. The sale of each product is concluded with the seller identified for that product, subject to availability and any mandatory consumer-protection rules.</p><p>If a seller cannot fulfil an order that has already been paid for, the relevant amount will be refunded to the original payment method where possible.</p>') .
    mdo_mt_h('5. Payment','<p>El Mercado de Origen may facilitate or technically process payment through payment service providers. Processing payment through the platform does not make El Mercado de Origen the seller of a product sold by a third party. Full card details are not stored by El Mercado de Origen where they are handled directly by the payment provider.</p>') .
    mdo_mt_h('6. Invoicing','<p>The invoice for products sold by a seller is issued by the seller shown on the order in accordance with that seller’s tax obligations. Customers may ask El Mercado de Origen for assistance if they need help obtaining it.</p>') .
    mdo_mt_h('7. Preparation, dispatch and delivery','<p>The seller is responsible for preparing, dispatching and delivering its order. <strong>El Mercado de Origen monitors correct order handling and provides customer support, and may mediate with the seller if an issue arises.</strong> Specific delivery times and conditions vary by seller and destination. See our <a href="' . esc_url(home_url('/en/shipping/')) . '">Shipping</a> page.</p>') .
    mdo_mt_h('8. Problems, non-conformity and guarantees','<p>If an order does not arrive, arrives damaged, is incorrect or the product does not match the contract, the customer may contact the seller or El Mercado de Origen. The platform will help document the issue, refer it to the seller and follow it up. No internal reporting period limits statutory consumer rights.</p>') .
    mdo_mt_h('9. Returns, withdrawal and refunds','<p>Physical returns are sent to the relevant seller, not to El Mercado de Origen. The platform facilitates communication and may mediate. Where payment was processed through the platform, El Mercado de Origen may technically process the refund on the seller’s behalf and to the original payment method where appropriate.</p><p>Where a statutory right of withdrawal applies, consumers generally have 14 calendar days, subject to legal exceptions. These include, among others, goods liable to deteriorate or expire rapidly, personalised goods and certain sealed goods that are unsuitable for return for health or hygiene reasons once unsealed. See the full <a href="' . esc_url(home_url('/en/returns-refunds/')) . '">Returns and Refunds</a> policy.</p>') .
    mdo_mt_h('10. Customer support','<p>El Mercado de Origen provides assistance throughout the purchase and after-sales process. Email <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>, call <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a>, or use the <a href="' . esc_url(home_url('/en/contact/')) . '">Contact</a> form.</p>') .
    mdo_mt_h('11. Platform responsibility','<p>El Mercado de Origen remains responsible for its own obligations as platform operator and intermediary service provider. Nothing in these terms is intended to exclude or limit rights or liabilities that cannot legally be excluded. Each seller remains responsible for the obligations applying to it as the selling party.</p>') .
    mdo_mt_h('12. Privacy and cookies','<p>Personal-data processing is explained in our <a href="' . esc_url(home_url('/en/privacy-policy/')) . '">Privacy Policy</a>. Cookies and similar technologies are explained in our <a href="' . esc_url(home_url('/en/cookie-policy/')) . '">Cookie Policy</a>.</p>') .
    mdo_mt_h('13. Applicable law and consumer rights','<p>These terms are interpreted under Spanish law, without prejudice to mandatory consumer protections applicable by reason of the customer’s place of residence. Seller-specific terms may improve, but never reduce, mandatory minimum consumer rights.</p>') . '</div>';
}

function mdo_mt_legal_es(): string {
    return '<div class="mdo-mt-policy"><p><strong>Última actualización: 18 de agosto de 2026.</strong></p>' . mdo_mt_identity_es() .
    mdo_mt_h('Titular y nombre comercial','<p>El Mercado de Origen es un nombre comercial. El titular y prestador del servicio de la sociedad de la información de este sitio web es ' . esc_html(MDO_MT_OPERATOR) . ', trabajador autónomo con NIF ' . esc_html(MDO_MT_NIF) . '.</p>') .
    mdo_mt_h('Actividad de la plataforma','<p>El sitio funciona principalmente como marketplace e intermediario tecnológico entre clientes y vendedores independientes. Cada producto identifica a su vendedor. Los productores preparan y envían directamente los pedidos que les corresponden, mientras El Mercado de Origen facilita la operación, hace seguimiento de la gestión y presta asistencia y mediación cuando es necesario.</p>') .
    mdo_mt_h('Contacto directo','<p>Domicilio: ' . esc_html(MDO_MT_ADDRESS) . '<br>Correo electrónico: <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a><br>Teléfono: <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a></p>') .
    mdo_mt_h('Uso del sitio','<p>El usuario se compromete a utilizar este sitio de forma lícita y a no realizar actuaciones que dañen, sobrecarguen o impidan su funcionamiento normal o vulneren derechos de terceros. Los contenidos propios del sitio están protegidos por la normativa aplicable. Las marcas, fotografías y materiales aportados por vendedores pertenecen a sus respectivos titulares.</p>') .
    mdo_mt_h('Información comercial','<p>La información sobre precios, impuestos, gastos de envío, disponibilidad y condiciones relevantes se facilita en las fichas de producto, páginas del vendedor y proceso de compra. Si detectas un dato incorrecto, escríbenos para que podamos revisarlo.</p>') .
    mdo_mt_h('Normas relacionadas','<p>Consulta también nuestros <a href="' . esc_url(home_url('/politica/')) . '">Términos y condiciones</a>, <a href="' . esc_url(home_url('/envios/')) . '">Envíos</a>, <a href="' . esc_url(home_url('/devoluciones-y-reembolsos/')) . '">Devoluciones y reembolsos</a>, <a href="' . esc_url(home_url('/politica-de-privacidad/')) . '">Política de privacidad</a> y <a href="' . esc_url(home_url('/politica-de-cookies/')) . '">Política de cookies</a>.</p>') . '</div>';
}
function mdo_mt_legal_en(): string {
    return '<div class="mdo-mt-policy"><p><strong>Last updated: 18 August 2026.</strong></p>' . mdo_mt_identity_en() .
    mdo_mt_h('Operator and trading name','<p>El Mercado de Origen is a trading name. The operator and information-society service provider for this website is ' . esc_html(MDO_MT_OPERATOR) . ', a self-employed individual with Spanish tax ID/NIF ' . esc_html(MDO_MT_NIF) . '.</p>') .
    mdo_mt_h('Marketplace activity','<p>The site operates mainly as a marketplace and technology intermediary between customers and independent sellers. Each product identifies its seller. Producers prepare and dispatch their own orders directly, while El Mercado de Origen facilitates the transaction, monitors order handling and provides support and mediation where needed.</p>') .
    mdo_mt_h('Direct contact','<p>Address: C/ Ferrocarril 7, 1º B, esc. izda., 28045 Madrid, Spain<br>Email: <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a><br>Phone: <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a></p>') .
    mdo_mt_h('Use of the website','<p>Users must use this site lawfully and must not damage, overload or interfere with its normal operation or infringe third-party rights. Original site content is protected by applicable law. Trademarks, photographs and materials supplied by sellers remain the property of their respective owners.</p>') .
    mdo_mt_h('Commercial information','<p>Information about prices, taxes, shipping charges, availability and relevant conditions is provided on product pages, seller pages and during checkout. If you find inaccurate information, please contact us so that it can be reviewed.</p>') .
    mdo_mt_h('Related policies','<p>See our <a href="' . esc_url(home_url('/en/terms-and-conditions/')) . '">Terms and Conditions</a>, <a href="' . esc_url(home_url('/en/shipping/')) . '">Shipping</a>, <a href="' . esc_url(home_url('/en/returns-refunds/')) . '">Returns and Refunds</a>, <a href="' . esc_url(home_url('/en/privacy-policy/')) . '">Privacy Policy</a> and <a href="' . esc_url(home_url('/en/cookie-policy/')) . '">Cookie Policy</a>.</p>') . '</div>';
}

function mdo_mt_shipping_es(): string {
    return '<div class="mdo-mt-policy"><p><strong>Última actualización: 18 de agosto de 2026.</strong></p>' .
    mdo_mt_h('Quién realiza el envío','<p><strong>Los productos adquiridos en El Mercado de Origen son preparados y enviados directamente por el vendedor o productor correspondiente.</strong> El Mercado de Origen no almacena ni expide físicamente los productos vendidos por terceros.</p>') .
    mdo_mt_h('Qué hace El Mercado de Origen','<p>La plataforma facilita el pedido, recibe información sobre su gestión, hace seguimiento y presta asistencia al cliente. Si hay una incidencia de preparación, transporte o entrega, puedes contactar con nosotros y la trasladaremos al vendedor para ayudar a resolverla.</p>') .
    mdo_mt_h('Gastos, pedido mínimo y zonas de entrega','<p>Cada vendedor puede establecer sus propios gastos de envío, umbrales de envío gratuito, pedido mínimo, transportista, zonas de entrega y calendario de expedición. Las condiciones aplicables se muestran en la información del vendedor y/o durante el checkout antes de confirmar y pagar el pedido.</p>') .
    mdo_mt_h('Pedidos con varios productores','<p>Si compras productos de vendedores distintos, cada vendedor prepara su parte por separado. Por ello puedes recibir varios paquetes, en fechas diferentes y, cuando corresponda, con gastos de envío calculados de forma independiente.</p>') .
    mdo_mt_h('Plazos de entrega','<p>No existe un único plazo universal para todo el marketplace. El plazo depende del productor, del tipo de producto, del destino y del transporte disponible. La estimación mostrada para el pedido es la referencia aplicable. Los productos frescos o que requieren condiciones especiales pueden tener días concretos de preparación o expedición.</p>') .
    mdo_mt_h('Dirección y seguimiento','<p>El cliente debe comprobar que la dirección, teléfono y demás datos de entrega son correctos antes de confirmar el pedido. Cuando exista información de seguimiento, el vendedor o la plataforma podrán comunicarla al cliente.</p>') .
    mdo_mt_h('Problemas con la entrega','<p>Si el paquete no llega, presenta daños visibles o existe cualquier otra incidencia, contacta cuanto antes con <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> o mediante nuestra página de <a href="' . esc_url(home_url('/contacto/')) . '">Contacto</a>. Te ayudaremos a coordinar la solución con el vendedor. Comunicarlo pronto facilita la investigación, pero no reduce los derechos legales del consumidor.</p>') . '</div>';
}
function mdo_mt_shipping_en(): string {
    return '<div class="mdo-mt-policy"><p><strong>Last updated: 18 August 2026.</strong></p>' .
    mdo_mt_h('Who ships the order','<p><strong>Products purchased through El Mercado de Origen are prepared and dispatched directly by the relevant seller or producer.</strong> El Mercado de Origen does not physically warehouse or dispatch products sold by third parties.</p>') .
    mdo_mt_h('What El Mercado de Origen does','<p>The platform facilitates the order, receives information about its handling, monitors progress and provides customer support. If there is a preparation, transport or delivery problem, you can contact us and we will liaise with the seller to help resolve it.</p>') .
    mdo_mt_h('Charges, minimum orders and delivery areas','<p>Each seller may set its own shipping charges, free-shipping thresholds, minimum order, carrier, delivery areas and dispatch calendar. Applicable conditions are shown in seller information and/or during checkout before the order is confirmed and paid.</p>') .
    mdo_mt_h('Orders from multiple producers','<p>If you buy from different sellers, each seller prepares its part separately. You may therefore receive multiple parcels on different dates and, where applicable, shipping charges calculated separately.</p>') .
    mdo_mt_h('Delivery times','<p>There is no single universal delivery time for the whole marketplace. Timing depends on the producer, product type, destination and available transport. The estimate shown for the order is the relevant reference. Fresh products or products requiring special handling may have specific preparation or dispatch days.</p>') .
    mdo_mt_h('Address and tracking','<p>Customers must check that the delivery address, phone number and other delivery details are correct before confirming the order. Where tracking is available, the seller or platform may provide it to the customer.</p>') .
    mdo_mt_h('Delivery problems','<p>If a parcel does not arrive, has visible damage or there is another delivery issue, contact <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> or use our <a href="' . esc_url(home_url('/en/contact/')) . '">Contact</a> page as soon as practical. We will help coordinate the solution with the seller. Prompt reporting helps investigation but does not reduce statutory consumer rights.</p>') . '</div>';
}

function mdo_mt_returns_es(): string {
    return '<div class="mdo-mt-policy"><p><strong>Última actualización: 18 de agosto de 2026.</strong></p>' .
    mdo_mt_h('Cómo gestionamos una devolución','<p>El vendedor identificado en el pedido es quien recibe físicamente la devolución de sus productos. <strong>El Mercado de Origen facilita la comunicación, presta asistencia al cliente y puede mediar con el vendedor.</strong> Antes de enviar un producto de vuelta, contacta con nosotros o con el vendedor para recibir la dirección y las instrucciones aplicables.</p>') .
    mdo_mt_h('Producto dañado, equivocado o no conforme','<p>Si recibes un producto dañado, incorrecto, incompleto o que no se corresponde con lo contratado, comunícalo a <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> o mediante <a href="' . esc_url(home_url('/contacto/')) . '">Contacto</a>. Podemos solicitar fotografías del producto, embalaje y etiqueta de transporte para documentar el caso. Hazlo tan pronto como sea razonablemente posible para facilitar la investigación. Esta recomendación no limita los derechos legales que correspondan.</p>') .
    mdo_mt_h('Derecho de desistimiento','<p>Cuando sea legalmente aplicable, el consumidor dispone con carácter general de <strong>14 días naturales</strong> para comunicar su decisión de desistir sin necesidad de justificarla. El plazo se cuenta conforme a la normativa aplicable y, en una venta de bienes, normalmente desde la recepción material del producto.</p>') .
    mdo_mt_h('Excepciones importantes en alimentación','<p>El derecho de desistimiento no se aplica en los supuestos legalmente exceptuados. Para los productos vendidos en este marketplace son especialmente relevantes los <strong>bienes que puedan deteriorarse o caducar con rapidez</strong>, los bienes confeccionados conforme a especificaciones del cliente o claramente personalizados, y los bienes precintados que no sean aptos para ser devueltos por razones de salud o higiene una vez desprecintados.</p><p>Que un producto no admita desistimiento por una excepción legal no elimina los derechos del cliente si llega dañado, es incorrecto o no es conforme con el contrato.</p>') .
    mdo_mt_h('Coste y forma de devolución','<p>En un desistimiento válido, el coste directo de devolver el producto corresponde al consumidor salvo que el vendedor haya aceptado asumirlo o la normativa exija otra cosa. En incidencias por producto no conforme o error imputable al vendedor, se aplicarán los derechos y remedios legalmente previstos. No envíes un producto perecedero sin instrucciones previas: contacta primero para que pueda organizarse la solución adecuada.</p>') .
    mdo_mt_h('Reembolsos','<p>Cuando proceda un reembolso, se utilizará el medio de pago original siempre que sea posible. Si el pago se procesó a través de El Mercado de Origen, la plataforma podrá ejecutar técnicamente el reembolso por cuenta del vendedor. En caso de desistimiento, se respetarán los plazos legales y, cuando la ley lo permita, el reembolso podrá quedar retenido hasta recibir los bienes o una prueba suficiente de su devolución.</p>') .
    mdo_mt_h('Condiciones del vendedor','<p>Un vendedor puede ofrecer condiciones de devolución más favorables. Sus condiciones particulares no pueden reducir los derechos mínimos obligatorios del consumidor ni contradecir esta política en perjuicio del cliente.</p>') .
    mdo_mt_h('Necesitas ayuda','<p>Escríbenos a <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>, llama al <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a> o utiliza el formulario de <a href="' . esc_url(home_url('/contacto/')) . '">Contacto</a>. Nuestro objetivo es que el cliente tenga un único punto de ayuda aunque la preparación, envío y devolución física correspondan al productor.</p>') . '</div>';
}
function mdo_mt_returns_en(): string {
    return '<div class="mdo-mt-policy"><p><strong>Last updated: 18 August 2026.</strong></p>' .
    mdo_mt_h('How a return is handled','<p>The seller identified on the order physically receives returns of its products. <strong>El Mercado de Origen facilitates communication, provides customer support and may mediate with the seller.</strong> Before sending anything back, contact us or the seller for the applicable return address and instructions.</p>') .
    mdo_mt_h('Damaged, wrong or non-conforming products','<p>If you receive a damaged, incorrect, incomplete or non-conforming product, email <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> or use our <a href="' . esc_url(home_url('/en/contact/')) . '">Contact</a> page. We may ask for photographs of the product, packaging and shipping label to document the case. Please report the issue as soon as reasonably practical to help the investigation. This recommendation does not limit any statutory rights.</p>') .
    mdo_mt_h('Right of withdrawal','<p>Where legally applicable, consumers generally have <strong>14 calendar days</strong> to notify their decision to withdraw without giving a reason. The period is calculated under applicable law and, for sales of goods, normally runs from physical receipt of the goods.</p>') .
    mdo_mt_h('Important food-related exceptions','<p>The right of withdrawal does not apply where a statutory exception applies. Particularly relevant to this marketplace are <strong>goods liable to deteriorate or expire rapidly</strong>, goods made to the customer’s specifications or clearly personalised, and sealed goods unsuitable for return for health or hygiene reasons once unsealed.</p><p>An exception from withdrawal does not remove the customer’s rights where a product arrives damaged, is incorrect or does not conform to the contract.</p>') .
    mdo_mt_h('Return costs and method','<p>For a valid withdrawal, the customer bears the direct cost of return unless the seller has agreed to bear it or the law requires otherwise. For non-conforming goods or seller error, statutory remedies apply. Do not send a perishable product back without instructions: contact us first so that the appropriate solution can be arranged.</p>') .
    mdo_mt_h('Refunds','<p>Where a refund is due, the original payment method will be used where possible. If payment was processed through El Mercado de Origen, the platform may technically execute the refund on the seller’s behalf. For withdrawal, statutory time limits will be respected and, where the law allows, reimbursement may be withheld until the goods or sufficient evidence of their return have been received.</p>') .
    mdo_mt_h('Seller-specific conditions','<p>A seller may offer more favourable return terms. Seller-specific terms cannot reduce mandatory minimum consumer rights or contradict this policy to the customer’s detriment.</p>') .
    mdo_mt_h('Need help?','<p>Email <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>, call <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a> or use our <a href="' . esc_url(home_url('/en/contact/')) . '">Contact</a> form. Our aim is to give the customer one clear support point even though physical preparation, dispatch and returns are handled by the producer.</p>') . '</div>';
}

function mdo_mt_privacy_es(): string {
    return '<div class="mdo-mt-policy"><p><strong>Última actualización: 18 de agosto de 2026.</strong></p>' . mdo_mt_identity_es() .
    mdo_mt_h('Responsable del tratamiento','<p>Para los datos tratados por la plataforma El Mercado de Origen, el responsable es ' . esc_html(MDO_MT_OPERATOR) . ', NIF ' . esc_html(MDO_MT_NIF) . '. Puedes ejercer tus derechos escribiendo a <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>.</p>') .
    mdo_mt_h('Qué datos tratamos','<p>Podemos tratar datos de identificación y contacto, direcciones de facturación y entrega, información necesaria para pedidos y pagos, cuenta de usuario, comunicaciones con atención al cliente, preferencias de marketing cuando exista consentimiento, y datos técnicos y de seguridad necesarios para operar el sitio.</p>') .
    mdo_mt_h('Para qué y con qué base jurídica','<ul><li><strong>Gestionar la cuenta, pedidos, pagos, atención e incidencias:</strong> ejecución de la relación contractual y medidas precontractuales.</li><li><strong>Cumplir obligaciones fiscales, contables, de consumo y otras obligaciones legales:</strong> cumplimiento de una obligación legal.</li><li><strong>Seguridad, prevención del fraude y defensa frente a reclamaciones:</strong> cumplimiento legal y/o interés legítimo, según el caso.</li><li><strong>Comunicaciones comerciales opcionales:</strong> consentimiento cuando sea exigible. Puedes retirarlo en cualquier momento.</li><li><strong>Cookies analíticas o publicitarias opcionales:</strong> consentimiento, que puedes gestionar o retirar.</li></ul>') .
    mdo_mt_h('Datos compartidos con los vendedores','<p>Como marketplace, debemos comunicar al vendedor correspondiente los datos estrictamente necesarios para aceptar, preparar, facturar, enviar y atender el pedido. El vendedor tratará esos datos para sus propias obligaciones relacionadas con la venta y deberá cumplir la normativa de protección de datos que le corresponda.</p>') .
    mdo_mt_h('Otros destinatarios y proveedores','<p>Podemos utilizar proveedores de servicios necesarios para operar la plataforma, como alojamiento, correo, soporte técnico, analítica consentida y proveedores de pago. Sólo acceden a los datos en la medida necesaria para prestar su servicio y bajo las garantías exigibles. También comunicaremos datos cuando exista una obligación legal o un requerimiento válido de una autoridad.</p>') .
    mdo_mt_h('Transferencias internacionales','<p>Si un proveedor implica tratamiento de datos fuera del Espacio Económico Europeo, se aplicarán los mecanismos y garantías exigidos por la normativa de protección de datos, como decisiones de adecuación o cláusulas contractuales tipo, cuando proceda.</p>') .
    mdo_mt_h('Conservación','<p>Conservamos los datos durante el tiempo necesario para prestar el servicio y, posteriormente, durante los plazos exigidos para atender obligaciones legales y posibles responsabilidades. Los datos basados exclusivamente en consentimiento se dejarán de utilizar para esa finalidad cuando lo retires, sin perjuicio de los periodos de bloqueo o conservación legal que correspondan.</p>') .
    mdo_mt_h('Tus derechos','<p>Puedes solicitar acceso, rectificación, supresión, oposición, limitación del tratamiento y portabilidad cuando proceda, así como retirar el consentimiento sin efectos retroactivos. Escribe a <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> indicando tu solicitud y la información necesaria para verificar tu identidad. También puedes presentar una reclamación ante la Agencia Española de Protección de Datos.</p>') .
    mdo_mt_h('Menores','<p>La tienda no está dirigida a menores para realizar compras. No solicitamos deliberadamente a menores datos para contratar productos.</p>') .
    mdo_mt_h('Seguridad y cambios','<p>Aplicamos medidas técnicas y organizativas razonables para proteger los datos. Esta política puede actualizarse cuando cambien los tratamientos, proveedores o requisitos legales; la fecha de actualización se mostrará al principio.</p>') . '</div>';
}
function mdo_mt_privacy_en(): string {
    return '<div class="mdo-mt-policy"><p><strong>Last updated: 18 August 2026.</strong></p>' . mdo_mt_identity_en() .
    mdo_mt_h('Data controller','<p>For personal data processed by the El Mercado de Origen platform, the controller is ' . esc_html(MDO_MT_OPERATOR) . ', Spanish tax ID/NIF ' . esc_html(MDO_MT_NIF) . '. You may exercise your rights by emailing <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>.</p>') .
    mdo_mt_h('Data we process','<p>We may process identification and contact data, billing and delivery addresses, information needed for orders and payments, user-account data, customer-support communications, marketing preferences where consent exists, and technical and security data needed to operate the site.</p>') .
    mdo_mt_h('Purposes and legal bases','<ul><li><strong>Account, orders, payments, support and issues:</strong> performance of the contract and pre-contractual steps.</li><li><strong>Tax, accounting, consumer and other legal duties:</strong> compliance with legal obligations.</li><li><strong>Security, fraud prevention and legal claims:</strong> legal obligations and/or legitimate interests as appropriate.</li><li><strong>Optional marketing:</strong> consent where required. You may withdraw it at any time.</li><li><strong>Optional analytics or advertising cookies:</strong> consent, which you can manage or withdraw.</li></ul>') .
    mdo_mt_h('Data shared with sellers','<p>As a marketplace, we must provide the relevant seller with the data strictly necessary to accept, prepare, invoice, dispatch and support the order. The seller processes that information for its own obligations connected with the sale and must comply with the data-protection rules applicable to it.</p>') .
    mdo_mt_h('Other recipients and service providers','<p>We may use service providers needed to operate the platform, such as hosting, email, technical support, consent-based analytics and payment providers. They receive data only to the extent necessary to provide their service and subject to applicable safeguards. We may also disclose data where required by law or by a valid authority request.</p>') .
    mdo_mt_h('International transfers','<p>If a provider involves processing outside the European Economic Area, the safeguards required by data-protection law will be applied, such as adequacy decisions or standard contractual clauses where appropriate.</p>') .
    mdo_mt_h('Retention','<p>We retain data for as long as needed to provide the service and afterwards for periods required to comply with legal duties and address potential liabilities. Data used solely on the basis of consent will cease to be used for that purpose when consent is withdrawn, subject to any legally required blocking or retention period.</p>') .
    mdo_mt_h('Your rights','<p>You may request access, rectification, erasure, objection, restriction and portability where applicable, and you may withdraw consent without retroactive effect. Email <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> with your request and information needed to verify your identity. You may also lodge a complaint with the Spanish Data Protection Agency (AEPD).</p>') .
    mdo_mt_h('Children','<p>The store is not directed at children for the purpose of purchasing products. We do not knowingly request data from children to enter into sales contracts.</p>') .
    mdo_mt_h('Security and updates','<p>We apply reasonable technical and organisational measures to protect personal data. This policy may be updated when processing activities, providers or legal requirements change; the update date will be shown at the top.</p>') . '</div>';
}

function mdo_mt_cookies_es(): string {
    return '<div class="mdo-mt-policy"><p><strong>Última actualización: 18 de agosto de 2026.</strong></p>' .
    mdo_mt_h('Qué son las cookies','<p>Las cookies y tecnologías similares son pequeños archivos o identificadores que permiten que una web funcione, recuerde determinadas preferencias y, cuando el usuario lo autoriza, mida el uso del sitio o personalice contenidos y publicidad.</p>') .
    mdo_mt_h('Cookies necesarias','<p>Las cookies estrictamente necesarias se utilizan para funciones como seguridad, sesión, carrito, checkout, preferencias esenciales y gestión del propio consentimiento. Pueden instalarse sin consentimiento cuando son imprescindibles para prestar el servicio solicitado.</p>') .
    mdo_mt_h('Cookies opcionales','<p>Las cookies de analítica, publicidad u otras finalidades no esenciales sólo deben activarse cuando exista una base válida y, cuando la normativa lo exija, después de obtener tu consentimiento. Rechazarlas no impide realizar una compra, aunque algunas funciones no esenciales pueden verse limitadas.</p>') .
    mdo_mt_h('Gestión del consentimiento','<p>Puedes aceptar o rechazar las categorías opcionales desde el panel de preferencias de cookies que muestra el sitio. También puedes eliminar cookies desde la configuración de tu navegador. Puedes retirar o cambiar tu consentimiento en cualquier momento; la retirada no afecta a la licitud del tratamiento realizado antes de retirarlo.</p>') .
    mdo_mt_h('Cookies de terceros','<p>Algunas funciones pueden utilizar servicios de terceros, por ejemplo proveedores de pago, analítica, contenido integrado o herramientas de marketing. Cuando estos servicios instalen cookies opcionales, se aplicará la elección de consentimiento disponible en el sitio y las políticas del proveedor correspondiente.</p>') .
    mdo_mt_h('Duración','<p>Algunas cookies se eliminan al cerrar el navegador y otras permanecen durante un periodo limitado. La duración depende de la función y del proveedor. Revisamos periódicamente la configuración para mantener sólo las cookies necesarias o autorizadas.</p>') .
    mdo_mt_h('Responsable y contacto','<p>El responsable del sitio es ' . esc_html(MDO_MT_OPERATOR) . ', NIF ' . esc_html(MDO_MT_NIF) . ', operador de El Mercado de Origen. Para dudas sobre privacidad o cookies: <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>. Consulta también la <a href="' . esc_url(home_url('/politica-de-privacidad/')) . '">Política de privacidad</a>.</p>') . '</div>';
}
function mdo_mt_cookies_en(): string {
    return '<div class="mdo-mt-policy"><p><strong>Last updated: 18 August 2026.</strong></p>' .
    mdo_mt_h('What cookies are','<p>Cookies and similar technologies are small files or identifiers that allow a website to operate, remember certain preferences and, where authorised, measure site use or personalise content and advertising.</p>') .
    mdo_mt_h('Necessary cookies','<p>Strictly necessary cookies are used for functions such as security, session management, basket, checkout, essential preferences and management of cookie consent itself. They may be set without consent where they are essential to provide a service requested by the user.</p>') .
    mdo_mt_h('Optional cookies','<p>Analytics, advertising and other non-essential cookies should only be activated where there is a valid legal basis and, where required, after obtaining your consent. Rejecting them does not prevent you from purchasing, although some non-essential features may be limited.</p>') .
    mdo_mt_h('Managing consent','<p>You can accept or reject optional categories through the cookie-preferences panel shown by the site. You can also delete cookies in your browser settings. You may withdraw or change consent at any time; withdrawal does not affect processing that was lawful before withdrawal.</p>') .
    mdo_mt_h('Third-party cookies','<p>Some features may use third-party services, for example payment providers, analytics, embedded content or marketing tools. Where those services set optional cookies, the consent choice available on this site and the relevant provider policies apply.</p>') .
    mdo_mt_h('Duration','<p>Some cookies are deleted when the browser closes and others remain for a limited period. Duration depends on the function and provider. We review the configuration periodically to retain only necessary or authorised cookies.</p>') .
    mdo_mt_h('Controller and contact','<p>The site operator is ' . esc_html(MDO_MT_OPERATOR) . ', Spanish tax ID/NIF ' . esc_html(MDO_MT_NIF) . ', operator of El Mercado de Origen. For privacy or cookie questions email <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a>. See also our <a href="' . esc_url(home_url('/en/privacy-policy/')) . '">Privacy Policy</a>.</p>') . '</div>';
}

/** Apply/update canonical Spanish pages and reviewed English metadata. Called explicitly by deployment. */
function mdo_mt_apply_pages(): array {
    $result = array();
    foreach ( mdo_mt_policy_specs() as $slug => $spec ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page instanceof WP_Post ) {
            $id = (int) $page->ID;
            if ( ! get_post_meta( $id, '_mdo_mt_backup_20260818', true ) ) {
                update_post_meta( $id, '_mdo_mt_backup_20260818', wp_json_encode( array(
                    'post_title' => $page->post_title,
                    'post_content' => $page->post_content,
                    'post_excerpt' => $page->post_excerpt,
                    'en_title' => get_post_meta($id,'_en_US_post_title',true),
                    'en_slug' => get_post_meta($id,'_en_US_post_name',true),
                    'en_content' => get_post_meta($id,'_en_US_post_content',true),
                    'en_excerpt' => get_post_meta($id,'_en_US_post_excerpt',true),
                    'en_published' => get_post_meta($id,'_en_US_published',true),
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
            }
            wp_update_post( array('ID'=>$id,'post_title'=>$spec['es_title'],'post_content'=>$spec['es_content'],'post_excerpt'=>$spec['es_excerpt'],'post_status'=>'publish') );
        } else {
            $id = (int) wp_insert_post( array('post_type'=>'page','post_status'=>'publish','post_title'=>$spec['es_title'],'post_name'=>$slug,'post_content'=>$spec['es_content'],'post_excerpt'=>$spec['es_excerpt']), true );
            if ( is_wp_error($id) ) { $result[$slug] = $id->get_error_message(); continue; }
        }
        update_post_meta($id,'_en_US_post_title',$spec['en_title']);
        update_post_meta($id,'_en_US_post_name',$spec['en_slug']);
        update_post_meta($id,'_en_US_post_content',$spec['en_content']);
        update_post_meta($id,'_en_US_post_excerpt',$spec['en_excerpt']);
        update_post_meta($id,'_en_US_published','1');
        update_post_meta($id,'_mdo_mt_version',MDO_MT_VERSION);
        $result[$slug] = $id;
    }

    $contact = get_page_by_path('contacto', OBJECT, 'page');
    if ($contact instanceof WP_Post) {
        update_post_meta($contact->ID,'_en_US_post_title','Contact');
        update_post_meta($contact->ID,'_en_US_post_name','contact');
        if ( ! get_post_meta($contact->ID,'_en_US_post_excerpt',true) ) update_post_meta($contact->ID,'_en_US_post_excerpt','Contact El Mercado de Origen for order support, marketplace questions or help with a seller.');
        update_post_meta($contact->ID,'_en_US_published','1');
        $result['contacto'] = (int)$contact->ID;
    }
    update_option('_mdo_mt_last_apply', array('version'=>MDO_MT_VERSION,'time'=>current_time('mysql'),'pages'=>$result), false);
    flush_rewrite_rules(false);
    return $result;
}

function mdo_mt_page_by_slug( string $slug ) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    return $page instanceof WP_Post ? $page : null;
}

add_filter('the_content', static function($content) {
    if ( ! is_singular('page') || ! in_the_loop() || ! is_main_query() ) return $content;
    $post = get_post();
    if ( ! $post instanceof WP_Post ) return $content;
    $slugs = array_keys(mdo_mt_policy_specs());
    if ( in_array($post->post_name, $slugs, true) && mdo_mt_is_english() ) {
        $en = (string)get_post_meta($post->ID,'_en_US_post_content',true);
        if ($en !== '') return $en;
    }
    if ($post->post_name === 'contacto') {
        return $content . mdo_mt_contact_trust_block(mdo_mt_is_english());
    }
    return $content;
}, PHP_INT_MAX);

add_filter('the_title', static function($title, $post_id = 0) {
    if ( ! mdo_mt_is_english() || is_admin() || ! $post_id ) return $title;
    $post = get_post((int)$post_id);
    if ( ! $post instanceof WP_Post || $post->post_type !== 'page' ) return $title;
    $en = (string)get_post_meta($post->ID,'_en_US_post_title',true);
    return $en !== '' ? $en : $title;
}, PHP_INT_MAX, 2);

function mdo_mt_contact_trust_block(bool $en): string {
    if ($en) {
        return '<section class="mdo-mt-contact"><h2>Business and marketplace information</h2>' . mdo_mt_identity_en() . '<p>El Mercado de Origen is the marketplace operator and customer-support point. The seller shown on each product is responsible for preparing and dispatching that order. We monitor order handling and can mediate with the seller if you need help.</p><p><a href="' . esc_url(home_url('/en/shipping/')) . '">Shipping</a> · <a href="' . esc_url(home_url('/en/returns-refunds/')) . '">Returns and Refunds</a> · <a href="' . esc_url(home_url('/en/legal-notice/')) . '">Legal Notice</a></p></section>';
    }
    return '<section class="mdo-mt-contact"><h2>Información de la empresa y del marketplace</h2>' . mdo_mt_identity_es() . '<p>El Mercado de Origen es el operador del marketplace y tu punto de asistencia. El vendedor identificado en cada producto prepara y expide directamente su pedido. Nosotros hacemos seguimiento de la gestión y podemos mediar con el vendedor si necesitas ayuda.</p><p><a href="' . esc_url(home_url('/envios/')) . '">Envíos</a> · <a href="' . esc_url(home_url('/devoluciones-y-reembolsos/')) . '">Devoluciones y reembolsos</a> · <a href="' . esc_url(home_url('/aviso-legal/')) . '">Aviso legal</a></p></section>';
}

function mdo_mt_url(string $slug, bool $en): string {
    $p = mdo_mt_page_by_slug($slug);
    if ( ! $p ) return '#';
    if ($en) {
        $eslug = sanitize_title((string)get_post_meta($p->ID,'_en_US_post_name',true));
        if ($eslug !== '') return home_url('/en/' . $eslug . '/');
    }
    return get_permalink($p);
}

add_action('wp_footer', static function() {
    if ( is_admin() ) return;
    $en = mdo_mt_is_english();
    $contact = mdo_mt_page_by_slug('contacto');
    $contact_url = $en ? home_url('/en/contact/') : ($contact ? get_permalink($contact) : home_url('/contacto/'));
    $links = $en ? array(
        'Contact'=>$contact_url,
        'Legal Notice'=>mdo_mt_url('aviso-legal',true),
        'Shipping'=>mdo_mt_url('envios',true),
        'Returns & Refunds'=>mdo_mt_url('devoluciones-y-reembolsos',true),
        'Privacy'=>mdo_mt_url('politica-de-privacidad',true),
        'Terms'=>mdo_mt_url('politica',true),
        'Cookies'=>mdo_mt_url('politica-de-cookies',true),
    ) : array(
        'Contacto'=>$contact_url,
        'Aviso legal'=>mdo_mt_url('aviso-legal',false),
        'Envíos'=>mdo_mt_url('envios',false),
        'Devoluciones y reembolsos'=>mdo_mt_url('devoluciones-y-reembolsos',false),
        'Privacidad'=>mdo_mt_url('politica-de-privacidad',false),
        'Términos'=>mdo_mt_url('politica',false),
        'Cookies'=>mdo_mt_url('politica-de-cookies',false),
    );
    echo '<section class="mdo-mt-footer" aria-label="' . esc_attr($en?'Business and legal information':'Información legal y del negocio') . '"><div class="mdo-mt-footer__inner">';
    echo '<p class="mdo-mt-footer__identity"><strong>El Mercado de Origen</strong> · ' . esc_html($en?'Marketplace operated by':'Marketplace operado por') . ' ' . esc_html(MDO_MT_OPERATOR) . ' · NIF ' . esc_html(MDO_MT_NIF) . ' · <a href="mailto:' . esc_attr(MDO_MT_EMAIL) . '">' . esc_html(MDO_MT_EMAIL) . '</a> · <a href="tel:+34603029509">' . esc_html(MDO_MT_PHONE) . '</a></p>';
    echo '<nav class="mdo-mt-footer__nav">'; $first=true; foreach($links as $label=>$url){ if(!$first) echo '<span aria-hidden="true"> · </span>'; $first=false; echo '<a href="'.esc_url($url).'">'.esc_html($label).'</a>'; } echo '</nav>';
    echo '<p class="mdo-mt-footer__role">' . esc_html($en?'Independent producers prepare and dispatch their orders directly. El Mercado de Origen facilitates the transaction, monitors handling and provides customer support.':'Los productores independientes preparan y envían directamente sus pedidos. El Mercado de Origen facilita la operación, hace seguimiento y presta atención al cliente.') . '</p>';
    echo '</div></section>';
}, 50);

add_action('wp_head', static function(){
    if (is_admin()) return;
    echo '<style id="mdo-mt-css">.mdo-mt-policy{max-width:900px}.mdo-mt-policy h2{margin-top:1.6em}.mdo-mt-policy li{margin:.35em 0}.mdo-mt-contact{margin:2rem 0;padding:1.25rem;border:1px solid #ddd;border-radius:6px}.mdo-mt-footer{border-top:1px solid #e4e4e4;background:#fafafa;color:#333;font-size:14px;line-height:1.55}.mdo-mt-footer__inner{max-width:1200px;margin:0 auto;padding:22px 20px}.mdo-mt-footer p{margin:0 0 8px}.mdo-mt-footer__nav{margin:6px 0 8px}.mdo-mt-footer a{text-decoration:underline;text-underline-offset:2px}.mdo-mt-footer__role{font-size:13px;color:#555}@media(max-width:600px){.mdo-mt-footer__inner{padding:18px 16px}.mdo-mt-footer__nav span{display:none}.mdo-mt-footer__nav a{display:block;margin:5px 0}}</style>';

    $schema = array(
        '@context'=>'https://schema.org','@type'=>'Organization','name'=>'El Mercado de Origen','legalName'=>MDO_MT_OPERATOR,'taxID'=>MDO_MT_NIF,'url'=>home_url('/'),
        'email'=>MDO_MT_EMAIL,'telephone'=>MDO_MT_PHONE,
        'address'=>array('@type'=>'PostalAddress','streetAddress'=>'C/ Ferrocarril 7, 1º B, esc. izda.','postalCode'=>'28045','addressLocality'=>'Madrid','addressCountry'=>'ES'),
        'contactPoint'=>array('@type'=>'ContactPoint','telephone'=>MDO_MT_PHONE,'email'=>MDO_MT_EMAIL,'contactType'=>'customer support','availableLanguage'=>array('Spanish','English')),
    );
    echo '<script type="application/ld+json" id="mdo-mt-organization">' . wp_json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . '</script>';
}, 50);

add_action('template_redirect', static function(){
    if (is_admin()) return;
    $path = isset($_SERVER['REQUEST_URI']) ? (string)wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']),PHP_URL_PATH) : '';
    if ($path === '/condiciones-especiales/' || $path === '/condiciones-especiales') { wp_safe_redirect(home_url('/envios/'),301,'MDO policy consolidation'); exit; }
    if (in_array($path,array('/en/special-conditions/','/en/special-conditions','/en/condiciones-especiales/','/en/condiciones-especiales'),true)) { wp_safe_redirect(home_url('/en/shipping/'),301,'MDO policy consolidation'); exit; }
}, 0);
