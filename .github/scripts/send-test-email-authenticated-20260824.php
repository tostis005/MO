<?php
if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress context required\n");
    exit(2);
}

$to = 'jose.fraga@gmail.com';
$from = 'hola@elmercadodeorigen.com';
$subject = 'Prueba de correo autenticado — El Mercado de Origen';
$message = "Hola José,\n\nEsta es una segunda prueba enviada desde El Mercado de Origen usando el dominio elmercadodeorigen.com como remitente técnico para validar la entrega en Gmail.\n\nUn saludo,\nEl Mercado de Origen";

add_filter('wp_mail_from', static function () use ($from) {
    return $from;
});
add_filter('wp_mail_from_name', static function () {
    return 'El Mercado de Origen';
});
add_action('phpmailer_init', static function ($phpmailer) use ($from) {
    $phpmailer->Sender = $from;
    $phpmailer->setFrom($from, 'El Mercado de Origen', false);
});

$failure = null;
add_action('wp_mail_failed', static function ($error) use (&$failure) {
    $failure = $error instanceof WP_Error ? $error->get_error_message() : 'Unknown wp_mail error';
});

$sent = wp_mail($to, $subject, $message, array('Content-Type: text/plain; charset=UTF-8'));
echo 'EMDO_AUTH_TEST_MAIL_SENT=' . ($sent ? '1' : '0') . PHP_EOL;
if ($failure) {
    echo 'EMDO_AUTH_TEST_MAIL_ERROR=' . $failure . PHP_EOL;
}
exit($sent ? 0 : 1);
