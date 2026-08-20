<?php
/**
 * One-time outbound mail test for El Mercado de Origen.
 * Temporary: remove after the test has run.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', static function () {
    register_rest_route('mdo/v1', '/mail-test-20260820', [
        'methods'  => 'GET',
        'callback' => static function (WP_REST_Request $request) {
            $token = (string) $request->get_param('token');
            $to    = strtolower(trim((string) $request->get_param('to')));

            // Random one-time token plus an allow-list fingerprint for the test recipient.
            $expected_token = 'Av56M2AJ9miSXRIYHkMyfM05-_7fk8rSkWs4vb6zHP4';
            $recipient_hash = '1bc71a4b79293d6ab2ab59c465e20c2d7250ef7a97d68d99af75ed0dafff40e7';

            if (!hash_equals($expected_token, $token)) {
                return new WP_REST_Response(['ok' => false, 'error' => 'unauthorized'], 403);
            }

            if (!is_email($to) || !hash_equals($recipient_hash, hash('sha256', $to))) {
                return new WP_REST_Response(['ok' => false, 'error' => 'recipient_not_allowed'], 403);
            }

            if (get_option('mdo_mail_test_20260820_done')) {
                return new WP_REST_Response(['ok' => false, 'error' => 'already_used'], 409);
            }

            $subject = 'Propuesta de colaboración con El Mercado de Origen';
            $body = '<p>Hola José,</p>'
                . '<p>Te escribo desde <strong>El Mercado de Origen</strong>, un marketplace en el que reunimos productos seleccionados de productores españoles y los acercamos directamente al consumidor.</p>'
                . '<p>Estamos empezando a trabajar con creadores y perfiles relacionados con gastronomía mediante un sistema de afiliación sencillo: cada colaborador dispone de su propio enlace y recibe una comisión por las compras que genere.</p>'
                . '<p>No hay compromiso de publicaciones ni exclusividad. La idea es que puedan recomendar productos concretos cuando encajen de forma natural con su contenido y obtener una comisión por las ventas que lleguen a través de sus recomendaciones.</p>'
                . '<p><strong>Este es un correo de prueba</strong> enviado directamente desde el sistema de correo de nuestra web para comprobar que el envío corporativo funciona correctamente.</p>'
                . '<p>Un saludo,<br><strong>El Mercado de Origen</strong><br>www.elmercadodeorigen.com</p>';

            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sent = wp_mail($to, $subject, $body, $headers);

            if (!$sent) {
                return new WP_REST_Response(['ok' => false, 'error' => 'wp_mail_failed'], 500);
            }

            update_option('mdo_mail_test_20260820_done', gmdate('c'), false);

            return new WP_REST_Response([
                'ok'      => true,
                'accepted' => true,
                'message' => 'WordPress accepted the test message for delivery.',
            ], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
