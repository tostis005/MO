<?php
if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress context required\n");
    exit(2);
}

$decode_env = static function (string $name): string {
    $raw = getenv($name);
    if ($raw === false || $raw === '') {
        return '';
    }
    $decoded = base64_decode($raw, true);
    return $decoded === false ? '' : $decoded;
};

$to = trim($decode_env('EMDO_MAIL_TO_B64'));
$subject = trim($decode_env('EMDO_MAIL_SUBJECT_B64'));
$message = $decode_env('EMDO_MAIL_BODY_B64');
$name = trim($decode_env('EMDO_MAIL_NAME_B64'));

if (!is_email($to)) {
    fwrite(STDERR, "Invalid recipient\n");
    exit(3);
}

if ($subject === '' && getenv('EMDO_AFFILIATE_TEMPLATE') === '1') {
    $subject = 'Propuesta de colaboración — El Mercado de Origen';
}
if (trim($message) === '' && getenv('EMDO_AFFILIATE_TEMPLATE') === '1') {
    $greeting = $name !== '' ? "¡Hola, {$name}! 👋" : "¡Hola! 👋";
    $message = $greeting . "\n\n"
        . "Te escribo desde El Mercado de Origen. Hemos estado viendo tu perfil y creemos que puede haber bastante encaje entre el tipo de contenido que compartes, tu comunidad y los productos con los que trabajamos, así que queríamos proponerte una posible colaboración.\n\n"
        . "Llevamos activos desde 2020 y durante estos años hemos estado especialmente centrados en AOVE y productos ibéricos. Ahora estamos dando un nuevo impulso a El Mercado de Origen y en los próximos días vamos a incorporar nuevos productores, ampliando progresivamente la selección disponible en la web.\n\n"
        . "La idea del proyecto es reunir productores seleccionados y acercar sus productos directamente al consumidor, haciendo visible quién está detrás de cada producto y su origen.\n\n"
        . "Tenemos ya bastante recorrido y reputación detrás: actualmente contamos con 4,9/5 en Google con más de 300 reseñas y 4,6/5 en Trustpilot con 169 opiniones.\n\n"
        . "En paralelo estamos preparando un sistema de afiliación para creadores y perfiles gastronómicos. La propuesta es sencilla: 5 % del importe de cada venta que llegue a través de ti + 0,50 € adicionales por cada pedido, mediante enlaces personalizados y, cuando tenga sentido, también códigos o cupones de descuento.\n\n"
        . "No habría exclusividad ni obligación de publicar un número determinado de veces. La idea es facilitarte las herramientas para que puedas compartirlo con tu comunidad de la forma que mejor encaje contigo y recibir una comisión por las ventas que generes.\n\n"
        . "Nos gustaría saber qué te parece la propuesta y si te interesaría explorar una colaboración de este tipo. Si tienes cualquier duda sobre el funcionamiento, las comisiones o cualquier otro detalle, solo tienes que decírnoslo 😊\n\n"
        . "https://www.elmercadodeorigen.com/\n\n"
        . "Muchas gracias,\nEl Mercado de Origen";
}

if ($subject === '' || trim($message) === '') {
    fwrite(STDERR, "Subject and body are required\n");
    exit(4);
}

$from = 'hola@elmercadodeorigen.com';
$from_name = 'El Mercado de Origen';

add_filter('wp_mail_from', static function () use ($from) {
    return $from;
});
add_filter('wp_mail_from_name', static function () use ($from_name) {
    return $from_name;
});
add_action('phpmailer_init', static function ($phpmailer) use ($from, $from_name) {
    $phpmailer->Sender = $from;
    $phpmailer->setFrom($from, $from_name, false);
});

$failure = null;
add_action('wp_mail_failed', static function ($error) use (&$failure) {
    $failure = $error instanceof WP_Error ? $error->get_error_message() : 'Unknown wp_mail error';
});

$headers = array(
    'Content-Type: text/plain; charset=UTF-8',
    'Reply-To: El Mercado de Origen <hola@elmercadodeorigen.com>',
);

$sent = wp_mail($to, $subject, $message, $headers);

echo 'EMDO_OUTREACH_MAIL_SENT=' . ($sent ? '1' : '0') . PHP_EOL;
if ($failure) {
    echo 'EMDO_OUTREACH_MAIL_ERROR=' . $failure . PHP_EOL;
}

exit($sent ? 0 : 1);
