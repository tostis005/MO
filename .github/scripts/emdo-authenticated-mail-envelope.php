<?php
/**
 * Plugin Name: EMDO - Authenticated mail envelope
 * Description: Keep the SMTP envelope aligned with the verified From address for mail sent from hola@elmercadodeorigen.com.
 * Version: 1.0.0
 *
 * Plesk's PHP mail transport otherwise defaults to the server-hostname envelope.
 * Gmail rejected that envelope with SPF/DKIM 5.7.26, despite a correct visible From.
 *
 * Scoped strictly to outgoing messages whose visible From is exactly the
 * shop's configured sender, leaving all other WordPress mail untouched.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'emdo_authenticated_envelope_from' ) ) {
    function emdo_authenticated_envelope_from( $mailer ) {
        if ( ! is_object( $mailer ) || ! isset( $mailer->From, $mailer->Mailer ) ) {
            return;
        }

        $shop_sender = 'hola@elmercadodeorigen.com';
        if ( strtolower( trim( (string) $mailer->From ) ) !== $shop_sender ) {
            return;
        }

        if ( ! in_array( (string) $mailer->Mailer, array( 'mail', 'sendmail' ), true ) ) {
            return;
        }

        // The From header is already set by FluentCRM. Only fix the envelope.
        $mailer->Sender = $shop_sender;
    }
}

add_action( 'phpmailer_init', 'emdo_authenticated_envelope_from', PHP_INT_MAX, 1 );
