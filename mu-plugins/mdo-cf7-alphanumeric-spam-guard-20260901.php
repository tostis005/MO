<?php
/**
 * Plugin Name: MDO Contact Form 7 Alphanumeric Spam Guard
 * Description: Detects pseudo-random mixed-case alphanumeric content across multiple Contact Form 7 fields without blocking ordinary single codes or references.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MDO_CF7_ALNUM_GUARD_VERSION = '1.0.0';

/**
 * Shannon entropy for the compact ASCII tokens handled by this guard.
 */
function mdo_cf7_alnum_entropy( $value ) {
    $length = strlen( $value );
    if ( $length < 1 ) {
        return 0.0;
    }

    $counts  = count_chars( strtolower( $value ), 1 );
    $entropy = 0.0;

    foreach ( $counts as $count ) {
        $probability = $count / $length;
        $entropy    -= $probability * log( $probability, 2 );
    }

    return $entropy;
}

/**
 * Detect the newer spam pattern: unbroken random-looking strings that mix upper
 * case, lower case and digits. A normal code/reference is intentionally not enough.
 */
function mdo_cf7_alnum_looks_generated_token( $raw_value ) {
    if ( ! is_scalar( $raw_value ) ) {
        return false;
    }

    $value  = trim( wp_strip_all_tags( (string) wp_unslash( $raw_value ) ) );
    $length = strlen( $value );

    if ( $length < 9 || $length > 80 || ! preg_match( '/^[A-Za-z0-9]+$/', $value ) ) {
        return false;
    }

    $upper  = preg_match_all( '/[A-Z]/', $value, $unused );
    $lower  = preg_match_all( '/[a-z]/', $value, $unused );
    $digits = preg_match_all( '/[0-9]/', $value, $unused );

    // Require all three character classes. This excludes normal words, telephone
    // numbers and most order/product references before scoring randomness.
    if ( $upper < 2 || $lower < 2 || $digits < 1 ) {
        return false;
    }

    $letters = $upper + $lower;
    if ( $letters < 1 || ( $upper / $letters ) < 0.25 ) {
        return false;
    }

    $characters       = str_split( $value );
    $class_transitions = 0;
    $case_transitions  = 0;

    $class_of = static function( $character ) {
        if ( ctype_upper( $character ) ) {
            return 'U';
        }
        if ( ctype_lower( $character ) ) {
            return 'L';
        }
        if ( ctype_digit( $character ) ) {
            return 'D';
        }
        return 'O';
    };

    for ( $index = 1, $count = count( $characters ); $index < $count; $index++ ) {
        $previous_class = $class_of( $characters[ $index - 1 ] );
        $current_class  = $class_of( $characters[ $index ] );

        if ( $previous_class !== $current_class ) {
            $class_transitions++;
        }

        if (
            ( 'U' === $previous_class || 'L' === $previous_class ) &&
            ( 'U' === $current_class || 'L' === $current_class ) &&
            $previous_class !== $current_class
        ) {
            $case_transitions++;
        }
    }

    if ( $class_transitions < 4 || $case_transitions < 2 ) {
        return false;
    }

    $entropy      = mdo_cf7_alnum_entropy( $value );
    $unique_ratio = count( array_unique( str_split( strtolower( $value ) ) ) ) / $length;

    // Short tokens need very high uniqueness because legitimate short references
    // are relatively common. Longer bot payloads can repeat characters naturally.
    if ( $length < 16 ) {
        return $entropy >= 2.50 && $unique_ratio >= 0.65;
    }

    return $entropy >= 3.35 && $unique_ratio >= 0.40;
}

/**
 * Read the data Contact Form 7 is actually processing, with raw POST as a fallback.
 */
function mdo_cf7_alnum_posted_data( $submission = null ) {
    if ( is_object( $submission ) && method_exists( $submission, 'get_posted_data' ) ) {
        $posted = $submission->get_posted_data();
        if ( is_array( $posted ) ) {
            return $posted;
        }
    }

    if ( class_exists( 'WPCF7_Submission' ) ) {
        $current = WPCF7_Submission::get_instance();
        if ( is_object( $current ) && method_exists( $current, 'get_posted_data' ) ) {
            $posted = $current->get_posted_data();
            if ( is_array( $posted ) ) {
                return $posted;
            }
        }
    }

    return is_array( $_POST ) ? wp_unslash( $_POST ) : array();
}

/**
 * Return suspicious human-readable fields. Email/URL/telephone-shaped values are
 * excluded because they are expected to be machine-shaped strings.
 */
function mdo_cf7_alnum_generated_fields( $posted_data ) {
    if ( ! is_array( $posted_data ) ) {
        return array();
    }

    $matches = array();

    foreach ( $posted_data as $field => $raw_value ) {
        if ( ! is_string( $field ) || 0 === strpos( $field, '_' ) || ! is_scalar( $raw_value ) ) {
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

        if ( mdo_cf7_alnum_looks_generated_token( $value ) ) {
            $matches[] = sanitize_key( $field );
        }
    }

    return array_values( array_unique( $matches ) );
}

/**
 * Add a reason to CF7's spam log without storing submitted values.
 */
function mdo_cf7_alnum_log( $submission, $fields ) {
    $reason = 'Multiple generated-looking alphanumeric fields: ' . implode( ', ', $fields ) . '.';

    if ( is_object( $submission ) && method_exists( $submission, 'add_spam_log' ) ) {
        $submission->add_spam_log( array(
            'agent'  => 'mdo_cf7_alnum_guard',
            'reason' => sanitize_text_field( $reason ),
        ) );
        return;
    }

    if ( function_exists( 'mdo_cf7_guard_log_spam' ) ) {
        mdo_cf7_guard_log_spam( $reason );
    }
}

/**
 * Two independent suspicious fields are required before a message is classified as
 * spam. This preserves legitimate submissions containing one unusual code/reference.
 */
function mdo_cf7_alnum_filter_spam( $spam, $submission = null ) {
    if ( $spam ) {
        return true;
    }

    $posted_data = mdo_cf7_alnum_posted_data( $submission );
    $fields      = mdo_cf7_alnum_generated_fields( $posted_data );

    if ( count( $fields ) < 2 ) {
        return false;
    }

    mdo_cf7_alnum_log( $submission, $fields );
    return true;
}

add_filter( 'wpcf7_spam', 'mdo_cf7_alnum_filter_spam', 18, 2 );
