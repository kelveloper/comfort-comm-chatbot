<?php
/**
 * Steven-Bot - Sanitization Functions
 *
 * Provides sanitization callbacks for register_setting() calls
 * to comply with WordPress Plugin Directory requirements.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Sanitize text field
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized value.
 */
function steven_bot_sanitize_text( $value ) {
    return sanitize_text_field( $value );
}

/**
 * Sanitize textarea field (allows line breaks)
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized value.
 */
function steven_bot_sanitize_textarea( $value ) {
    return sanitize_textarea_field( $value );
}

/**
 * Sanitize URL field
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized URL.
 */
function steven_bot_sanitize_url( $value ) {
    return esc_url_raw( $value );
}

/**
 * Sanitize integer field
 *
 * @param mixed $value The value to sanitize.
 * @return int Sanitized integer.
 */
function steven_bot_sanitize_int( $value ) {
    return absint( $value );
}

/**
 * Sanitize float field
 *
 * @param mixed $value The value to sanitize.
 * @return float Sanitized float.
 */
function steven_bot_sanitize_float( $value ) {
    return floatval( $value );
}

/**
 * Sanitize checkbox (Yes/No values)
 *
 * @param mixed $value The value to sanitize.
 * @return string 'Yes' or 'No'.
 */
function steven_bot_sanitize_checkbox( $value ) {
    return ( $value === 'Yes' || $value === '1' || $value === true ) ? 'Yes' : 'No';
}

/**
 * Sanitize select field with allowed values
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized value or empty string.
 */
function steven_bot_sanitize_select( $value ) {
    return sanitize_text_field( $value );
}

/**
 * Sanitize API key field
 * Preserves existing encrypted value if new value is empty or placeholder
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized and possibly encrypted API key.
 */
function steven_bot_sanitize_api_key( $value ) {
    // If empty or placeholder, return empty
    if ( empty( $value ) || strpos( $value, '********' ) !== false ) {
        return '';
    }

    // Sanitize the value
    $sanitized = sanitize_text_field( $value );

    // Encrypt if function exists
    if ( function_exists( 'steven_bot_encrypt_api_key' ) ) {
        return steven_bot_encrypt_api_key( $sanitized );
    }

    return $sanitized;
}

/**
 * Sanitize color field (hex color)
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized hex color.
 */
function steven_bot_sanitize_color( $value ) {
    return sanitize_hex_color( $value );
}

/**
 * Sanitize array of values
 *
 * @param mixed $value The value to sanitize.
 * @return array Sanitized array.
 */
function steven_bot_sanitize_array( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }
    return array_map( 'sanitize_text_field', $value );
}

/**
 * Sanitize JSON string
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized JSON string.
 */
function steven_bot_sanitize_json( $value ) {
    if ( empty( $value ) ) {
        return '';
    }

    // Decode and re-encode to validate JSON
    $decoded = json_decode( $value, true );
    if ( json_last_error() === JSON_ERROR_NONE ) {
        return wp_json_encode( $decoded );
    }

    return '';
}

/**
 * Sanitize HTML content (allows safe HTML)
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized HTML.
 */
function steven_bot_sanitize_html( $value ) {
    return wp_kses_post( $value );
}

/**
 * Sanitize file path
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized file path.
 */
function steven_bot_sanitize_file_path( $value ) {
    return sanitize_file_name( $value );
}

/**
 * Sanitize model name (AI model identifiers)
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized model name.
 */
function steven_bot_sanitize_model( $value ) {
    // Allow alphanumeric, hyphens, underscores, dots, colons, and slashes
    return preg_replace( '/[^a-zA-Z0-9\-_\.:\/@]/', '', $value );
}

/**
 * Sanitize assistant ID
 *
 * @param mixed $value The value to sanitize.
 * @return string Sanitized assistant ID.
 */
function steven_bot_sanitize_assistant_id( $value ) {
    // Allow alphanumeric, hyphens, and underscores
    return preg_replace( '/[^a-zA-Z0-9\-_]/', '', $value );
}

/**
 * No-op sanitization for values that should pass through unchanged
 * Used for complex serialized data that is sanitized elsewhere
 *
 * @param mixed $value The value to sanitize.
 * @return mixed The unchanged value.
 */
function steven_bot_sanitize_passthrough( $value ) {
    return $value;
}
