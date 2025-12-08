<?php
/**
 * Steven-Bot - Globals - Ver 1.6.5
 *
 * This file contains the code for global variables used
 * by the program.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Declare global language code variable
global $steven_bot_language_code;

// Get the site language using get_locale()
$steven_bot_installed_language_code = get_locale();

// Log the site language for debugging
// error_log('[Chatbot] [chatbot-globals.php] Site locale: ' . $steven_bot_installed_language_code);

// Set a default language code if site language is not set
if ( empty( $steven_bot_installed_language_code ) ) {
    $steven_bot_installed_language_code = "en_US";
}

// Load the global arrays for the determined language
steven_bot_load_globals($steven_bot_installed_language_code);

// Load chatbot language globals based on the site language.
function steven_bot_load_globals($language_code) {

    global $steven_bot_language_code;
    global $steven_bot_globals_loaded;

    // If the globals have already been loaded, return
    if ( $steven_bot_globals_loaded ) {
        // DIAG - Diagnotics
        // error_log('[Chatbot] [chatbot-globals.php]Globals already loaded for language: ' . $language_code);
        return;
    }

    // Set the global language code
    $steven_bot_language_code = $language_code;

    // Log the selected language code
    // error_log('[Chatbot] [chatbot-globals.php] Loading globals for language: ' . $language_code);

    // Define the file path for translations
    $file_path = plugin_dir_path(__FILE__) . 'translations/chatbot-globals-' . substr(strtolower($language_code), 0, 2) . '.php';

    // Check if the file exists and include it, else log a warning
    if ( file_exists($file_path) ) {
        require_once $file_path;
        // error_log('[Chatbot] [chatbot-globals.php] Loaded translation file: ' . $file_path);
        $steven_bot_globals_loaded = true;
    } else {
        // Fall back to English if the file does not exist
        $steven_bot_language_code = "en_US";
        $fallback_file = plugin_dir_path(__FILE__) . 'translations/chatbot-globals-en.php';
        require_once $fallback_file;
        // error_log('[Chatbot] [chatbot-globals.php] Translation file not found for ' . $language_code . '. Falling back to English: ' . $fallback_file);
        $steven_bot_globals_loaded = true;
    }

    // Log a warning if the file could not be loaded
    if ( ! $steven_bot_globals_loaded ) {
        error_log ('[Chatbot] [chatbot-globals.php] Could not load translation file: ' . $file_path);
        $chatbot_chatgtp_globals_loaded = false;
    }

}
