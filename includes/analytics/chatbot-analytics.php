<?php
/**
 * Steven-Bot Analytics - Ver 1.0.0
 *
 * This file contains the code for the Steven-Bot Analytics package.
 * 
 * 
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load the language-specific globals
function kognetiks_analytics_load_globals( $language_code ) {

    // Log the selected language code
    // error_log( '[Chatbot] [chatbot-analytics.php] Loading globals for language: ' . $language_code );

    $file_path = plugin_dir_path( __FILE__ ) . '/languages/' . $language_code . '.php';

    if ( file_exists( $file_path ) ) {
        require_once $file_path;
        // error_log( '[Chatbot] [chatbot-analytics.php] Loaded translation file: ' . $file_path );
    } else {
        $fallback_file = plugin_dir_path( __FILE__ ) . '/languages/en_US.php';
        require_once $fallback_file;
        error_log( '[Chatbot] [chatbot-analytics.php] Translation file not found for ' . $language_code . '. Falling back to: ' . $fallback_file );
    }

}

// Call the function after it's defined
$steven_bot_installed_language_code = get_locale();

if ( empty( $steven_bot_installed_language_code ) ) {

    // Default language code
    $steven_bot_installed_language_code = 'en_US';

}
// Load the language-specific globals
kognetiks_analytics_load_globals( $steven_bot_installed_language_code );

// Automatically add the sentiment_score column when analytics is loaded
// Use output buffering to prevent any error HTML output from SQLite plugin
if (function_exists('steven_bot_add_sentiment_score_column')) {
    ob_start();
    steven_bot_add_sentiment_score_column();
    ob_end_clean();
}
