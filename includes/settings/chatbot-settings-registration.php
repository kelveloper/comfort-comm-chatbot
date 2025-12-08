<?php
/**
 * Steven-Bot - Registration
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the registration of settings and other parameters.
 * 
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register settings
function steven_bot_settings_init() {

    
}

add_action('admin_init', 'steven_bot_settings_init');
