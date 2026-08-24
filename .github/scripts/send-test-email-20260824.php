<?php
if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress context required\n");
    exit(2);
}

$to = 'jose.fraga@gmail.com';
$subject = 'Prueba de correo — El Mercado de Origen';
$message = "Hola José,\n\nEste es un correo de prueba enviado desde la web de El Mercado de Origen para comprobar que el sistema de envío funciona correctamente.\n\nUn saludo,\nEl Mercado de Origen";
$headers = array('Content-Type: text/plain; charset=UTF-8');

$failure = null;
add_action('wp_mail_failed', function ($error) use (&$failure) {
    $failure = $error instanceof WP_Error ? $error->get_error_message() : 'Unknown wp_mail error';
});

$sent = wp_mail($to, $subject, $message, $headers);

echo 'EMDO_TEST_MAIL_SENT=' . ($sent ? '1' : '0') . PHP_EOL;
if ($failure) {
    echo 'EMDO_TEST_MAIL_ERROR=' . $failure . PHP_EOL;
}

exit($sent ? 0 : 1);
