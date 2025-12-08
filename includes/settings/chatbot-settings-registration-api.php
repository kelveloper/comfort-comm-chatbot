<?php
/**
 * Steven-Bot - Registration - API Settings
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

// Register API settings
function steven_bot_api_settings_init() {

    add_settings_section(
        'steven_bot_model_settings_section',
        'API/ChatGPT Settings',
        'steven_bot_model_settings_section_callback',
        'steven_bot_model_settings_general'
    );

    // API/ChatGPT settings tab - Ver 1.3.0
    register_setting('steven_bot_api_chatgpt', 'steven_bot_api_key', 'steven_bot_sanitize_api_key');

    add_settings_section(
        'steven_bot_api_chatgpt_general_section',
        'ChatGPT API Settings',
        'steven_bot_api_chatgpt_general_section_callback',
        'steven_bot_api_chatgpt_general'
    );

    add_settings_field(
        'steven_bot_api_key',
        'ChatGPT API Key',
        'steven_bot_api_key_callback',
        'steven_bot_api_chatgpt_general',
        'steven_bot_api_chatgpt_general_section'
    );

    // Advanced Model Settings - Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_base_url', 'steven_bot_sanitize_url'); // Ver 1.8.1
    register_setting('steven_bot_api_chatgpt', 'steven_bot_timeout_setting', 'steven_bot_sanitize_text'); // Ver 1.8.8

    add_settings_section(
        'steven_bot_api_chatgpt_advanced_section',
        'Advanced API Settings',
        'steven_bot_api_chatgpt_advanced_section_callback',
        'steven_bot_api_chatgpt_advanced'
    );

    // Set the base URL for the API - Ver 1.8.1
    add_settings_field(
        'steven_bot_base_url',
        'Base URL for API',
        'steven_bot_base_url_callback',
        'steven_bot_api_chatgpt_advanced',
        'steven_bot_api_chatgpt_advanced_section'
    );

    // Timeout setting - Ver 1.8.8
    add_settings_field(
        'steven_bot_timeout_setting',
        'Timeout Setting (in seconds)',
        'steven_bot_timeout_setting_callback',
        'steven_bot_api_chatgpt_advanced',
        'steven_bot_api_chatgpt_advanced_section'
    );

    // Chat Options - Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_api_enabled', 'steven_bot_sanitize_checkbox');
    register_setting('steven_bot_api_chatgpt', 'steven_bot_model_choice', 'steven_bot_sanitize_model');
    register_setting('steven_bot_api_chatgpt', 'steven_bot_max_tokens_setting', 'steven_bot_sanitize_float'); // Max Tokens setting options - Ver 1.4.2
    register_setting('steven_bot_api_chatgpt', 'steven_bot_conversation_context', 'steven_bot_sanitize_text'); // Conversation Context - Ver 1.6.1
    register_setting('steven_bot_api_chatgpt', 'steven_bot_temperature', 'steven_bot_sanitize_float'); // Temperature - Ver 2.0.1
    register_setting('steven_bot_api_chatgpt', 'steven_bot_top_p', 'steven_bot_sanitize_float'); // Top P - Ver 2.0.1

    add_settings_section(
        'steven_bot_api_chatgpt_chat_section',
        'Chat Settings',
        'steven_bot_api_chatgpt_chat_section_callback',
        'steven_bot_api_chatgpt_chat'
    );

    add_settings_field(
        'steven_bot_model_choice',
        'ChatGPT Model Default',
        'steven_bot_model_choice_callback',
        'steven_bot_api_chatgpt_chat',
        'steven_bot_api_chatgpt_chat_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'steven_bot_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_max_tokens_setting_callback',
        'steven_bot_api_chatgpt_chat',
        'steven_bot_api_chatgpt_chat_section'
    );

    // Setting to adjust the conversation context - Ver 1.4.2
    add_settings_field(
        'steven_bot_conversation_context',
        'System Prompt',
        'steven_bot_conversation_context_callback',
        'steven_bot_api_chatgpt_chat',
        'steven_bot_api_chatgpt_chat_section'
    );

    // Temperature - Ver 2.0.1
    add_settings_field(
        'steven_bot_temperature',
        'Temperature',
        'steven_bot_temperature_callback',
        'steven_bot_api_chatgpt_chat',
        'steven_bot_api_chatgpt_chat_section'
    );

    // Top P - Ver 2.0.1
    add_settings_field(
        'steven_bot_top_p',
        'Top P',
        'steven_bot_top_p_callback',
        'steven_bot_api_chatgpt_chat',
        'steven_bot_api_chatgpt_chat_section'
    );

    // Voice Options - Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_voice_model_option', 'steven_bot_sanitize_model'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_voice_option', 'steven_bot_sanitize_text'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_audio_output_format', 'steven_bot_sanitize_text'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_read_aloud_option', 'steven_bot_sanitize_checkbox'); // Ver 2.0.0
    
    // Voice Options - Ver 1.9.5
    add_settings_section(
        'steven_bot_api_chatgpt_voice_section',
        'Voice Settings (Text to Speech)',
        'steven_bot_api_chatgpt_voice_section_callback',
        'steven_bot_api_chatgpt_voice'
    );

    // Voice Option - Ver 1.9.5
    add_settings_field(
        'steven_bot_voice_model_option',
        'Voice Model Default',
        'steven_bot_voice_model_option_callback',
        'steven_bot_api_chatgpt_voice',
        'steven_bot_api_chatgpt_voice_section'
    );

    // Voice Option
    add_settings_field(
        'steven_bot_voice_option',
        'Voice',
        'steven_bot_voice_option_callback',
        'steven_bot_api_chatgpt_voice',
        'steven_bot_api_chatgpt_voice_section'
    );

    // Audio Output Options
    add_settings_field(
        'steven_bot_audio_output_format',
        'Audio Output Option',
        'steven_bot_audio_output_format_callback',
        'steven_bot_api_chatgpt_voice',
        'steven_bot_api_chatgpt_voice_section'
    );

    // Allow Read Aloud - Ver 2.0.0
    add_settings_field(
        'steven_bot_read_aloud_option',
        'Allow Read Aloud',
        'steven_bot_read_aloud_option_callback',
        'steven_bot_api_chatgpt_voice',
        'steven_bot_api_chatgpt_voice_section'
    );

    // Image Options - Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_image_model_option', 'steven_bot_sanitize_model'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_image_output_format', 'steven_bot_sanitize_text'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_image_output_size', 'steven_bot_sanitize_text'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_image_output_quantity', 'steven_bot_sanitize_text'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_image_output_quality', 'steven_bot_sanitize_text'); // Ver 1.9.5
    register_setting('steven_bot_api_chatgpt', 'steven_bot_image_style_output', 'steven_bot_sanitize_text'); // Ver 1.9.5

    // Image Options - Ver 1.9.5
    add_settings_section(
        'steven_bot_api_chatgpt_image_section',
        'Image Settings',
        'steven_bot_api_chatgpt_image_section_callback',
        'steven_bot_api_chatgpt_image'
    );

    add_settings_field(
        'steven_bot_image_model_option',
        'Image Model Default',
        'steven_bot_image_model_option_callback',
        'steven_bot_api_chatgpt_image',
        'steven_bot_api_chatgpt_image_section'
    );

    add_settings_field(
        'steven_bot_image_output_format',
        'Image Output Option',
        'steven_bot_image_output_format_callback',
        'steven_bot_api_chatgpt_image',
        'steven_bot_api_chatgpt_image_section'
    );

    add_settings_field(
        'steven_bot_image_output_size',
        'Image Output Size',
        'steven_bot_image_output_size_callback',
        'steven_bot_api_chatgpt_image',
        'steven_bot_api_chatgpt_image_section'
    );

    add_settings_field(
        'steven_bot_image_output_quantity',
        'Image Quantity',
        'steven_bot_image_output_quantity_callback',
        'steven_bot_api_chatgpt_image',
        'steven_bot_api_chatgpt_image_section'
    );

    add_settings_field(
        'steven_bot_image_output_quality',
        'Image Quality',
        'steven_bot_image_output_quality_callback',
        'steven_bot_api_chatgpt_image',
        'steven_bot_api_chatgpt_image_section'
    );

    add_settings_field(
        'steven_bot_image_style_output',
        'Image Style Output',
        'steven_bot_image_style_output_callback',
        'steven_bot_api_chatgpt_image',
        'steven_bot_api_chatgpt_image_section'
    );

    // Whisper Options - Ver 2.0.1
    register_setting('steven_bot_api_chatgpt', 'steven_bot_whisper_model_option', 'steven_bot_sanitize_model');
    register_setting('steven_bot_api_chatgpt', 'steven_bot_whisper_response_format', 'steven_bot_sanitize_text');

    // Image Options - Ver 1.9.5
    add_settings_section(
        'steven_bot_api_chatgpt_whisper_section',
        'Whisper Settings (Speech to Text)',
        'steven_bot_api_chatgpt_whisper_section_callback',
        'steven_bot_api_chatgpt_whisper'
    );

    add_settings_field(
        'steven_bot_whisper_model_option',
        'Whisper Model Default',
        'steven_bot_whisper_model_option_callback',
        'steven_bot_api_chatgpt_whisper',
        'steven_bot_api_chatgpt_whisper_section'
    );

    add_settings_field(
        'steven_bot_whisper_response_format',
        'Whisper Output Option',
        'steven_bot_whisper_response_format_callback',
        'steven_bot_api_chatgpt_whisper',
        'steven_bot_api_chatgpt_whisper_section'
    );

}
add_action('admin_init', 'steven_bot_api_settings_init');