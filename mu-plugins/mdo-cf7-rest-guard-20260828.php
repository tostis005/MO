<?php
/**
 * Plugin Name: MDO Contact Form 7 REST-safe Guard
 * Description: Closes direct Contact Form 7 REST submission bypasses while allowing browser challenge fields that CF7 may omit from canonical posted data.
 * Version: 1.0.1
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MDO_CF7_REST_GUARD_VERSION = '1.0.1';

/**
 * Get the active Contact Form 7 submission instance.
 */
function mdo_cf7_rest_guard_submission( $submission = null ) {
    if ( is_object( $submission ) && method_exists( $submission, 'get_posted_data' ) ) {
        return $submission;
    }

    if ( class_exists( 'WPCF7_Submission' ) ) {
        $current = WPCF7_Submission::get_instance();
        if ( is_object( $current ) && method_exists( $current, 'get_posted_data' ) ) {
            return $current;
        }
    }

    return null;
}

/**
 * Add a spam-log entry without exposing submitted values.
 */
function mdo_cf7_rest_guard_log( $submission, $reason ) {
    if ( is_object( $submission ) && method_exists( $submission, 'add_spam_log' ) ) {
        $submission->add_spam_log( array(
            'agent'  => 'mdo_cf7_rest_guard',
            'reason' => sanitize_text_field( $reason ),
        ) );
        return;
    }

    if ( function_exists( 'mdo_cf7_guard_log_spam' ) ) {
        mdo_cf7_guard_log_spam( $reason );
    }
}

/**
 * Read the canonical posted data that Contact Form 7 itself is using.
 */
function mdo_cf7_rest_guard_posted_data( $submission ) {
    if ( is_object( $submission ) && method_exists( $submission, 'get_posted_data' ) ) {
        $posted = $submission->get_posted_data();
        if ( is_array( $posted ) ) {
            return $posted;
        }
    }

    return is_array( $_POST ) ? wp_unslash( $_POST ) : array();
}

/**
 * Resolve the actual Contact Form 7 form ID from the submission context.
 */
function mdo_cf7_rest_guard_form_id( $submission, $posted_data ) {
    if ( is_object( $submission ) && method_exists( $submission, 'get_contact_form' ) ) {
        $contact_form = $submission->get_contact_form();
        if ( is_object( $contact_form ) && method_exists( $contact_form, 'id' ) ) {
            $id = absint( $contact_form->id() );
            if ( $id ) {
                return $id;
            }
        }
    }

    if ( isset( $posted_data['_wpcf7'] ) ) {
        return absint( $posted_data['_wpcf7'] );
    }

    if ( isset( $_POST['_wpcf7'] ) ) {
        return absint( wp_unslash( $_POST['_wpcf7'] ) );
    }

    return 0;
}

/**
 * Read the signed guard token.
 *
 * CF7 deliberately normalizes posted data to fields declared in the form template.
 * The MDO browser challenge is appended as a hidden field by JavaScript, so some CF7
 * versions omit it from WPCF7_Submission::get_posted_data() even though the browser
 * sent it in the multipart request. Prefer canonical data when available, then fall
 * back only for this one signed field to the raw request. The token still has to pass
 * form-ID, age, nonce, signature and replay validation below.
 */
function mdo_cf7_rest_guard_token( $posted_data, $field_name ) {
    if ( isset( $posted_data[ $field_name ] ) && is_scalar( $posted_data[ $field_name ] ) ) {
        $token = sanitize_text_field( (string) $posted_data[ $field_name ] );
        if ( '' !== $token ) {
            return $token;
        }
    }

    if ( isset( $_POST[ $field_name ] ) && is_scalar( $_POST[ $field_name ] ) ) {
        return sanitize_text_field( (string) wp_unslash( $_POST[ $field_name ] ) );
    }

    return '';
}

/**
 * Detect multiple independent random-looking human text fields using the detector
 * installed by the main MDO CF7 guard. One suspicious field alone never blocks.
 */
function mdo_cf7_rest_guard_generated_fields( $posted_data ) {
    if ( ! function_exists( 'mdo_cf7_guard_looks_generated_token' ) || ! is_array( $posted_data ) ) {
        return array();
    }

    $matches = array();

    foreach ( $posted_data as $field => $raw_value ) {
        if ( ! is_string( $field ) || 0 === strpos( $field, '_' ) ) {
            continue;
        }

        if ( ! is_scalar( $raw_value ) ) {
            continue;
        }

        $value = trim( (string) $raw_value );
        if ( '' === $value ) {
            continue;
        }

        if (
            is_email( $value ) ||
            preg_match( '#^https?://#i', $value ) ||
            preg_match( '/^[+()\d\s.\-]{5,}$/', $value )
        ) {
            continue;
        }

        if ( mdo_cf7_guard_looks_generated_token( $value ) ) {
            $matches[] = sanitize_key( $field );
        }
    }

    return array_values( array_unique( $matches ) );
}

/**
 * Validate the signed browser challenge against the form ID from the real CF7
 * submission object. This prevents direct REST requests from bypassing the guard by
 * simply omitting the browser form's hidden _wpcf7 field.
 */
function mdo_cf7_rest_guard_filter( $spam, $submission = null ) {
    if ( $spam ) {
        return true;
    }

    $submission  = mdo_cf7_rest_guard_submission( $submission );
    $posted_data = mdo_cf7_rest_guard_posted_data( $submission );

    $generated_fields = mdo_cf7_rest_guard_generated_fields( $posted_data );
    if ( count( $generated_fields ) >= 2 ) {
        mdo_cf7_rest_guard_log(
            $submission,
            'Multiple generated-looking text fields: ' . implode( ', ', $generated_fields ) . '.'
        );
        return true;
    }

    $form_id = mdo_cf7_rest_guard_form_id( $submission, $posted_data );
    if ( ! $form_id ) {
        mdo_cf7_rest_guard_log( $submission, 'Missing Contact Form 7 form context.' );
        return true;
    }

    $field_name = defined( 'MDO_CF7_GUARD_FIELD' ) ? MDO_CF7_GUARD_FIELD : '_mdo_cf7_guard';
    $token      = mdo_cf7_rest_guard_token( $posted_data, $field_name );

    if ( '' === $token ) {
        mdo_cf7_rest_guard_log( $submission, 'Missing browser challenge.' );
        return true;
    }

    $parts = explode( '.', $token, 3 );
    if ( 3 !== count( $parts ) ) {
        mdo_cf7_rest_guard_log( $submission, 'Malformed browser challenge.' );
        return true;
    }

    list( $issued_raw, $nonce, $sig ) = $parts;
    $issued = ctype_digit( $issued_raw ) ? (int) $issued_raw : 0;
    $age    = time() - $issued;

    if ( ! $issued || $age < 1 || $age > 7200 ) {
        mdo_cf7_rest_guard_log( $submission, 'Invalid browser challenge age.' );
        return true;
    }

    if ( ! preg_match( '/^[a-zA-Z0-9]{20,64}$/', $nonce ) || ! preg_match( '/^[a-f0-9]{64}$/', $sig ) ) {
        mdo_cf7_rest_guard_log( $submission, 'Invalid browser challenge format.' );
        return true;
    }

    $ua_hash = function_exists( 'mdo_cf7_guard_ua_hash' )
        ? mdo_cf7_guard_ua_hash()
        : substr( hash( 'sha256', isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '' ), 0, 20 );

    $payload  = $form_id . '|' . $issued . '|' . $nonce . '|' . $ua_hash;
    $expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

    if ( ! hash_equals( $expected, $sig ) ) {
        mdo_cf7_rest_guard_log( $submission, 'Browser challenge signature mismatch.' );
        return true;
    }

    // Separate replay bucket from the legacy guard so both filters can coexist.
    $replay_key = 'mdo_cf7rr_' . substr( hash( 'sha256', $token ), 0, 32 );
    $uses       = (int) get_transient( $replay_key );
    if ( $uses >= 2 ) {
        mdo_cf7_rest_guard_log( $submission, 'Browser challenge replay limit exceeded.' );
        return true;
    }
    set_transient( $replay_key, $uses + 1, 2 * HOUR_IN_SECONDS );

    return false;
}

// Modern Contact Form 7 passes the WPCF7_Submission object as the second argument.
add_filter( 'wpcf7_spam', 'mdo_cf7_rest_guard_filter', 19, 2 );
