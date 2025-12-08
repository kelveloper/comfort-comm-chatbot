<?php
/**
 * Steven-Bot - Localize
 *
 * This file contains the code for the Chatbot settings page.
 * It localizes the settings and other parameters.
 * 
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function steven_bot_localize(){

    $defaults = array(
        'steven_bot_allow_file_uploads' => 'No',
        'steven_bot_audience_choice' => 'all',
        'steven_bot_avatar_greeting_setting' => "Hi there! I'm Steven, Comfort Comm's virtual assistant. How can I help you with your internet, TV, or phone service today?",
        'steven_bot_avatar_icon_setting' => 'icon-001.png',
        'steven_bot_avatar_icon_url_setting' => '',
        'steven_bot_bot_name' => 'Steven',
        'steven_bot_bot_prompt' => 'Enter your question ...',
        'steven_bot_conversation_context' => 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.',
        'steven_bot_custom_avatar_icon_setting' => '',
        'steven_bot_custom_button_name_1' => '',
        'steven_bot_custom_button_name_2' => '',
        'steven_bot_custom_button_name_3' => '',
        'steven_bot_custom_button_name_4' => '',
        'steven_bot_custom_button_url_1' => '',
        'steven_bot_custom_button_url_2' => '',
        'steven_bot_custom_button_url_3' => '',
        'steven_bot_custom_button_url_4' => '',
        'steven_bot_disclaimer_setting' => 'No',
        'steven_bot_display_style' => 'floating',
        'steven_bot_enable_custom_buttons' => 'Off',
        'steven_bot_initial_greeting' => "Hi there! I'm Steven, Comfort Comm's virtual assistant. How can I help you with your internet, TV, or phone service today?",
        'steven_bot_model_choice' => 'gpt-3.5-turbo',
        'steven_bot_start_status' => 'closed',
        'steven_bot_start_status_new_visitor' => 'closed',
        'steven_bot_width_setting' => 'Narrow',
        'steven_bot_force_page_reload' => 'Yes',
        'steven_bot_conversation_continuation' => 'On',
        'steven_bot_diagnostics' => 'Off',
        'steven_bot_appearance_open_icon' => '',
        'steven_bot_appearance_collapse_icon' => '',
        'steven_bot_appearance_erase_icon' => '',
        'steven_bot_appearance_mic_enabled_icon' => '',
        'steven_bot_appearance_mic_disabled_icon' => '',
    );

    // Revised for Ver 1.5.0 
    $option_keys = array(
        'steven_bot_allow_file_uploads',
        'steven_bot_audience_choice',
        'steven_bot_avatar_greeting_setting',
        'steven_bot_avatar_icon_setting',
        'steven_bot_avatar_icon_url_setting',
        'steven_bot_bot_name',
        'steven_bot_bot_prompt',
        'steven_bot_conversation_context',
        'steven_bot_custom_avatar_icon_setting',
        'steven_bot_custom_button_name_1',
        'steven_bot_custom_button_name_2',
        'steven_bot_custom_button_name_3',
        'steven_bot_custom_button_name_4',
        'steven_bot_custom_button_url_1',
        'steven_bot_custom_button_url_2',
        'steven_bot_custom_button_url_3',
        'steven_bot_custom_button_url_4',
        'steven_bot_disclaimer_setting',
        'steven_bot_display_style',
        'steven_bot_enable_custom_buttons',
        'steven_bot_initial_greeting',
        'steven_bot_model_choice',
        'steven_bot_start_status',
        'steven_bot_start_status_new_visitor',
        'steven_bot_width_setting',
        'steven_bot_force_page_reload',
        'steven_bot_conversation_continuation',
        'steven_bot_diagnostics',
        'steven_bot_appearance_open_icon',
        'steven_bot_appearance_collapse_icon',
        'steven_bot_appearance_erase_icon',
        'steven_bot_appearance_mic_enabled_icon',
        'steven_bot_appearance_mic_disabled_icon',
    );

    $kchat_settings = [];
    foreach ($option_keys as $key) {
        $default_value = $defaults[$key] ?? '';
        $kchat_settings[$key] = esc_attr(get_option($key, $default_value));
        // DIAG - Diagnostics - Ver 1.6.1
        // back_trace( 'NOTICE', 'Key: ' . $key . ', Value: ' . $kchat_settings[$key]);
    }

    // Add FAQ category buttons data
    if (function_exists('chatbot_faq_get_buttons_data')) {
        $kchat_settings['faq_category_buttons'] = chatbot_faq_get_buttons_data();
    } else {
        $kchat_settings['faq_category_buttons'] = [];
    }

}
