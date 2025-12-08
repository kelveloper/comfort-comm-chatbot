<?php
/**
 * Steven-Bot - Menus
 *
 * This file contains the code for the administrative menus for the plugin.
 * 
 * 
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Use a number lower than default (10), e.g., 5.
add_action('admin_menu', 'kognetiks_chatbot_register_menus', 5);

// Add a menu item in the admin panel
function kognetiks_chatbot_register_menus() {

    global $menu;

    // Check if the 'Steven-Bot' menu already exists
    $stevenbot_menu_exists = false;

    foreach ( $menu as $menu_item ) {

        if ( isset( $menu_item[2] ) && $menu_item[2] === 'kognetiks_main_menu' ) {
            $stevenbot_menu_exists = true;
            break;
        }

    }

    // If no Steven-Bot menu exists, add a standalone menu for this plugin
    if ( ! $stevenbot_menu_exists ) {

        add_menu_page(
            'Steven-Bot',                           // Page title
            'Steven-Bot',                           // Menu title
            'manage_options',                       // Capability
            'kognetiks_main_menu',                  // Menu slug
            'steven_bot_settings_page',        // Callback function
            'dashicons-rest-api',                   // Icon
            999                                     // Position
        );

        add_submenu_page(
            'kognetiks_main_menu',                  // Parent slug
            'Chatbot',                              // Page title
            'Chatbot',                              // Menu title
            'manage_options',                       // Capability     
            'steven-bot',                      // Menu slug
            'steven_bot_settings_page'         // Callback function
        );

    } else {

        // If Steven-Bot menu exists, add this as a submenu
        add_submenu_page(
            'kognetiks_main_menu',                  // Parent slug
            'Chatbot',                              // Page title
            'Chatbot',                              // Menu title
            'manage_options',                       // Capability     
            'steven-bot',                      // Menu slug
            'steven_bot_settings_page'         // Callback function
        );

    }

};

// Remove the extra submenu page
add_action('admin_menu', 'steven_bot_remove_extra_submenu', 999);
function steven_bot_remove_extra_submenu() {

    remove_submenu_page('kognetiks_main_menu', 'kognetiks_main_menu');

}
