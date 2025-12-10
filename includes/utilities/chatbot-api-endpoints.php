<?php
/**
 * Steven-Bot - API Endpoints - Ver 2.2.2  - Revised - Ver 2.5.2
 *
 * This file contains the code for managing the API endpoints.
 *
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Get the active API configuration based on user's platform choice
 * Ver 2.5.2: Central function for all API calls to ensure consistency
 *
 * This function should be used by ALL background tasks (gap analysis, feedback analysis,
 * guardrails, embeddings, etc.) to ensure they use the same API the user configured.
 *
 * @return array Configuration with keys: platform, api_key, model, embedding_model, base_url, chat_url, embedding_url
 */
function steven_bot_get_api_config() {
    $platform = get_option('chatbot_ai_platform_choice', 'OpenAI');

    $config = [
        'platform' => $platform,
        'api_key' => '',
        'model' => '',
        'embedding_model' => '',
        'base_url' => '',
        'chat_url' => '',
        'embedding_url' => '',
    ];

    switch ($platform) {
        case 'OpenAI':
            $encrypted_key = get_option('steven_bot_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('steven_bot_model_choice', 'gpt-4o-mini');
            $config['embedding_model'] = 'text-embedding-3-small';
            $config['base_url'] = get_option('steven_bot_base_url', 'https://api.openai.com/v1');
            $config['chat_url'] = $config['base_url'] . '/chat/completions';
            $config['embedding_url'] = $config['base_url'] . '/embeddings';
            break;

        case 'Gemini':
            $encrypted_key = get_option('chatbot_gemini_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('chatbot_gemini_model_choice', 'gemini-2.5-flash-lite');
            $config['embedding_model'] = 'text-embedding-004';
            $config['base_url'] = get_option('chatbot_gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta');
            $config['chat_url'] = $config['base_url'] . '/models/' . $config['model'] . ':generateContent?key=' . $config['api_key'];
            $config['embedding_url'] = $config['base_url'] . '/models/' . $config['embedding_model'] . ':embedContent?key=' . $config['api_key'];
            break;

        case 'Azure OpenAI':
            $encrypted_key = get_option('chatbot_azure_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('chatbot_azure_model_choice', 'gpt-4o-mini');
            $config['embedding_model'] = 'text-embedding-3-small';
            $resource = get_option('chatbot_azure_resource_name', '');
            $deployment = get_option('chatbot_azure_deployment_name', '');
            $api_version = get_option('chatbot_azure_api_version', '2024-08-01-preview');
            $config['base_url'] = "https://{$resource}.openai.azure.com";
            $config['chat_url'] = $config['base_url'] . "/openai/deployments/{$deployment}/chat/completions?api-version={$api_version}";
            $config['embedding_url'] = $config['base_url'] . "/openai/deployments/{$deployment}/embeddings?api-version={$api_version}";
            break;

        case 'Anthropic':
            $encrypted_key = get_option('chatbot_anthropic_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest');
            $config['embedding_model'] = ''; // Anthropic doesn't have embeddings
            $config['base_url'] = get_option('chatbot_anthropic_base_url', 'https://api.anthropic.com/v1');
            $config['chat_url'] = $config['base_url'] . '/messages';
            $config['embedding_url'] = ''; // Not supported
            break;

        case 'DeepSeek':
            $encrypted_key = get_option('chatbot_deepseek_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('chatbot_deepseek_model_choice', 'deepseek-chat');
            $config['embedding_model'] = ''; // DeepSeek doesn't have embeddings
            $config['base_url'] = get_option('chatbot_deepseek_base_url', 'https://api.deepseek.com');
            $config['chat_url'] = $config['base_url'] . '/chat/completions';
            $config['embedding_url'] = ''; // Not supported
            break;

        case 'Mistral':
            $encrypted_key = get_option('chatbot_mistral_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('chatbot_mistral_model_choice', 'mistral-small-latest');
            $config['embedding_model'] = 'mistral-embed';
            $config['base_url'] = get_option('chatbot_mistral_base_url', 'https://api.mistral.ai/v1');
            $config['chat_url'] = $config['base_url'] . '/chat/completions';
            $config['embedding_url'] = $config['base_url'] . '/embeddings';
            break;

        case 'NVIDIA':
            $encrypted_key = get_option('chatbot_nvidia_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct');
            $config['embedding_model'] = ''; // NVIDIA doesn't have embeddings via this API
            $config['base_url'] = get_option('chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1');
            $config['chat_url'] = $config['base_url'] . '/chat/completions';
            $config['embedding_url'] = ''; // Not supported
            break;

        default:
            // Default to OpenAI
            $encrypted_key = get_option('steven_bot_api_key', '');
            $config['api_key'] = function_exists('steven_bot_decrypt_api_key')
                ? steven_bot_decrypt_api_key($encrypted_key)
                : $encrypted_key;
            $config['model'] = get_option('steven_bot_model_choice', 'gpt-4o-mini');
            $config['embedding_model'] = 'text-embedding-3-small';
            $config['base_url'] = get_option('steven_bot_base_url', 'https://api.openai.com/v1');
            $config['chat_url'] = $config['base_url'] . '/chat/completions';
            $config['embedding_url'] = $config['base_url'] . '/embeddings';
            break;
    }

    return $config;
}

/**
 * Check if the current platform supports embeddings
 * Ver 2.5.2
 *
 * @return bool True if embeddings are supported
 */
function steven_bot_platform_supports_embeddings() {
    $config = steven_bot_get_api_config();
    return !empty($config['embedding_model']) && !empty($config['embedding_url']);
}

/**
 * Get embedding dimensions for the current platform
 * Ver 2.5.2
 *
 * @return int Embedding dimensions (1536 for OpenAI, 768 for Gemini, etc.)
 */
function steven_bot_get_embedding_dimensions() {
    $config = steven_bot_get_api_config();

    switch ($config['platform']) {
        case 'Gemini':
            return 768;
        case 'OpenAI':
        case 'Azure OpenAI':
        default:
            return 1536;
    }
}

// Base URL for the API calls - Ver 2.2.6
function get_api_base_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    switch ($chatbot_ai_platform_choice) {

        // Base URL for the OpenAI API calls - Ver 1.8.1
        case 'OpenAI':

            return esc_attr(get_option('steven_bot_base_url', 'https://api.openai.com/v1'));
            break;

        // Base URL for the Azure OpenAI API calls - Ver 2.2.6
        case 'Azure OpenAI':

            return esc_attr(get_option('chatbot_azure_base_url', 'https://YOUR_RESOURCE_NAME.openai.azure.com/DEPLOYMENT_NAME/MODEL/chat/completions?api-version=YYYY-MM-DD'));
            break;

        // Base URL for the NVIDIA API calls - Ver 2.1.8
        case 'NVIDIA':

            return esc_attr(get_option('chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1'));
            break;

        // Base URL for the Anthropic API calls - Ver 2.2.1
        case 'Anthropic':

            return esc_attr(get_option('chatbot_anthropic_base_url', 'https://api.anthropic.com/v1'));
            break;

        // Base URL for the DeepSeek API calls - Ver 2.2.2
        case 'DeepSeek':

            return esc_attr(get_option('chatbot_deepseek_base_url', 'https://api.deepseek.com'));
            break;

        // Base URL for the Mistral API calls - Ver 2.3.0
        case 'Mistral':

            return esc_attr(get_option('chatbot_mistral_base_url', 'https://api.mistral.ai/v1'));
            break;

        // Base URL for the Local API calls - Ver 2.2.6
        case 'Local Server':

            return esc_attr(get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1'));
            break;

        default:

            return get_api_base_url();
            break;

    }

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_threads_api_url() {

    return get_api_base_url() . "/threads";

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_files_api_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    if ($chatbot_ai_platform_choice == 'OpenAI') {

        return get_api_base_url() . "/files";

    } elseif ($chatbot_ai_platform_choice == 'Azure OpenAI') {

        $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
        $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
        $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
        // Assemble the URL
        // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai/files?api-version=2024-03-01-preview';
        $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/files?api-version=' . $chatbot_azure_api_version;

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Azure OpenAI endpoint $url is: ' . $url );
        return $url;

    }

    return 'ERROR: No file API URL found!';

}

// Base URL for the ChatGPT API calls - Ver 2.2.2 - Revised - Ver 2.2.6
function get_chat_completions_api_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    switch ($chatbot_ai_platform_choice) {

        // Base URL for the OpenAI API calls - Ver 1.8.1
        case 'OpenAI':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Azure OpenAI API calls - Ver 2.2.6
        case 'Azure OpenAI':

            $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
            $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
            $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));

            // Assemble the URL
            return 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/deployments/' . $chatbot_azure_deployment_name . '/chat/completions?api-version=' . $chatbot_azure_api_version;
            break;

        // Base URL for the NVIDIA API calls - Ver 2.1.8
        case 'NVIDIA':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Anthropic API calls - Ver 2.2.1
        case 'Anthropic':

            return get_api_base_url() . "/messages";
            break;

        // Base URL for the DeepSeek API calls - Ver 2.2.2
        case 'DeepSeek':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Mistral API calls - Ver 2.3.0
        case 'Mistral':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Local API calls - Ver 2.2.6
        case 'Local Server':

            return get_api_base_url() . "/chat/completions";
            break;

        default:

            return get_api_base_url() . "/chat/completions";
            break;
    }

}
