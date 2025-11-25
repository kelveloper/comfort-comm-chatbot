<?php
/**
 * Kognetiks Chatbot - Google Gemini API - Ver 2.3.7
 *
 * This file contains the code accessing Google's Gemini API.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Gemini API
function chatbot_call_gemini_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $learningMessages;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $errorResponses;

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates

    // FAQ Search - Check for matching FAQ before calling API - Ver 2.3.7
    $faq_context = '';
    if (function_exists('chatbot_faq_search')) {
        $faq_match = chatbot_faq_search($message);
        if ($faq_match && !empty($faq_match['answer'])) {
            // Log the FAQ match
            prod_trace('NOTICE', 'FAQ match found for: ' . $message);

            // Build FAQ context for Gemini to rephrase naturally
            $faq_context = "\n\nIMPORTANT: Answer the user's question using ONLY this information from our FAQ:\n" .
                           "Question: " . $faq_match['question'] . "\n" .
                           "Answer: " . $faq_match['answer'] . "\n\n" .
                           "Rephrase this answer in a friendly, conversational tone. Do not add information beyond what's provided.";
        }
    }

    // Current Page Context - Inject current page content so bot knows what page user is viewing
    $page_context = '';
    if (!empty($page_id) && $page_id !== '999999') {
        $current_page = get_post($page_id);
        if ($current_page && $current_page->post_status === 'publish') {
            $page_title = $current_page->post_title;
            $page_url = get_permalink($page_id);

            // Strip HTML tags and shortcodes from page content
            $page_content = wp_strip_all_tags($current_page->post_content);
            $page_content = strip_shortcodes($page_content);

            // Remove extra whitespace
            $page_content = preg_replace('/\s+/', ' ', $page_content);
            $page_content = trim($page_content);

            // Limit to first 800 words to avoid token limits
            $page_content_words = explode(' ', $page_content);
            if (count($page_content_words) > 800) {
                $page_content = implode(' ', array_slice($page_content_words, 0, 800)) . '...';
            }

            // Build page context for Gemini
            if (!empty($page_content)) {
                $page_context = "\n\nCURRENT PAGE CONTEXT:\n" .
                               "The user is currently viewing the page titled: \"$page_title\"\n" .
                               "Page URL: $page_url\n\n" .
                               "Page Content:\n$page_content\n\n" .
                               "When answering questions, you can reference information from this page by saying things like 'On this page...' or 'According to the current page...'. " .
                               "If the user asks what page they're on, tell them they're viewing the \"$page_title\" page.";

                // Log for diagnostics
                prod_trace('NOTICE', 'Page context added for page: ' . $page_title . ' (ID: ' . $page_id . ')');
            }
        }
    }

    // Google Gemini API Documentation
    // https://ai.google.dev/gemini-api/docs

    // Get the saved model from the settings or default to "gemini-1.5-flash"
    $model = esc_attr(get_option('chatbot_gemini_model_choice', 'gemini-1.5-flash'));

    // Build the API URL with API key as query parameter
    $base_url = esc_attr(get_option('chatbot_gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'));
    $api_url = $base_url . '/models/' . $model . ':generateContent?key=' . $api_key;

    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_gemini_max_tokens_setting', '2048')));

    // Temperature
    $temperature = floatval(esc_attr(get_option('chatbot_gemini_temperature', '0.7')));

    // Top P
    $top_p = floatval(esc_attr(get_option('chatbot_gemini_top_p', '0.95')));

    // Conversation Context
    $context = esc_attr(get_option('chatbot_gemini_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
    $raw_context = $context;

    // Context History
    $chatgpt_last_response = concatenateHistory('chatbot_chatgpt_context_history');

    // Strip any href links and text from the $chatgpt_last_response
    $chatgpt_last_response = preg_replace('/\[URL:.*?\]/', '', $chatgpt_last_response);

    // Strip any $learningMessages from the $chatgpt_last_response
    if (get_locale() !== "en_US") {
        $localized_learningMessages = get_localized_learningMessages(get_locale(), $learningMessages);
    } else {
        $localized_learningMessages = $learningMessages;
    }
    $chatgpt_last_response = str_replace($localized_learningMessages, '', $chatgpt_last_response);

    // Strip any $errorResponses from the $chatgpt_last_response
    if (get_locale() !== "en_US") {
        $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
    } else {
        $localized_errorResponses = $errorResponses;
    }
    $chatgpt_last_response = str_replace($localized_errorResponses, '', $chatgpt_last_response);

    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', 'Yes'));

    $sys_message = 'We previously have been talking about the following things: ';

    // ENHANCED CONTEXT - Select some context to send with the message
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    if ($use_enhanced_content_search == 'Yes') {

        $search_results = chatbot_chatgpt_content_search($message);
        If ( !empty ($search_results) ) {
            // Extract relevant content from search results array
            $content_texts = [];
            foreach ($search_results['results'] as $result) {
                if (!empty($result['excerpt'])) {
                    $content_texts[] = $result['excerpt'];
                }
            }
            // Join the content texts and append to context
            if (!empty($content_texts)) {
                $context = ' When answering the prompt, please consider the following information: ' . implode(' ', $content_texts);
            }
        }

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Conversation Continuity
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary
    $context_length = intval(strlen($context) / 4); // Assuming 1 token ≈ 4 characters
    $max_context_length = 65536; // Example: 65536 characters ≈ 16384 tokens
    if ($context_length > $max_context_length) {
        // Truncate to the max length
        $truncated_context = substr($context, 0, $max_context_length);
        // Ensure truncation happens at the last complete word
        $truncated_context = preg_replace('/\s+[^\s]*$/', '', $truncated_context);
        // Fallback if regex fails (e.g., no spaces in the string)
        if (empty($truncated_context)) {
            $truncated_context = substr($context, 0, $max_context_length);
        }
        $context = $truncated_context;
    }

    // Build the Gemini API request body
    // Gemini uses a different format than OpenAI

    // Define the header
    $headers = array(
        'Content-Type' => 'application/json'
    );

    // Define the request body for Gemini format
    $body = json_encode(array(
        'contents' => array(
            array(
                'role' => 'user',
                'parts' => array(
                    array(
                        'text' => $context . $page_context . $faq_context . "\n\nUser: " . $message
                    )
                )
            )
        ),
        'generationConfig' => array(
            'temperature' => $temperature,
            'topP' => $top_p,
            'maxOutputTokens' => $max_tokens,
        ),
        'safetySettings' => array(
            array(
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ),
            array(
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ),
            array(
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ),
            array(
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            )
        )
    ));

    $timeout = esc_attr(get_option('chatbot_gemini_timeout_setting', 240));

    // Context History
    addEntry('chatbot_chatgpt_context_history', $message);

    // API Call
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body' => $body,
        'timeout' => $timeout,
    ));

    // Handle WP Error
    if (is_wp_error($response)) {

        prod_trace( 'ERROR', 'Error: ' . $response->get_error_message());
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An API error occurred.';

    }

    // Retrieve and Decode Response
    $response_body = json_decode(wp_remote_retrieve_body($response));

    // Handle API Errors
    if (isset($response_body->error)) {

        // Extract error type and message safely
        $error_code = $response_body->error->code ?? 'Unknown Error Code';
        $error_message = $response_body->error->message ?? 'No additional information.';

        prod_trace( 'ERROR', 'Error: Code: ' . $error_code . ' Message: ' . $error_message);
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An error occurred.';

    }

    // Get the user ID and page ID
    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_the_ID(); // Get the ID of the queried object if $page_id is not set
        }
    }

    // Extract token usage from Gemini response
    $input_tokens = $response_body->usageMetadata->promptTokenCount ?? 0;
    $output_tokens = $response_body->usageMetadata->candidatesTokenCount ?? 0;
    $total_tokens = $response_body->usageMetadata->totalTokenCount ?? 0;

    // Check if the response content is not empty
    if (!empty($response_body->candidates[0]->content->parts[0]->text)) {
        if ($input_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $input_tokens);
        }

        if ($output_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $output_tokens);
        }

        if ($total_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $total_tokens);
        }
    }

    // Access response content properly - Gemini format
    if (isset($response_body->candidates[0]->content->parts[0]->text) && !empty($response_body->candidates[0]->content->parts[0]->text)) {
        $response_text = $response_body->candidates[0]->content->parts[0]->text;
        addEntry('chatbot_chatgpt_context_history', $response_text);
        return $response_text;
    } else {
        prod_trace( 'WARNING', 'No valid response text found in Gemini API response.');

        $localized_errorResponses = (get_locale() !== "en_US")
            ? get_localized_errorResponses(get_locale(), $errorResponses)
            : $errorResponses;

        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }

}

// Get available Gemini models
function chatbot_gemini_get_models() {

    $api_key = esc_attr(get_option('chatbot_gemini_api_key'));

    // Decrypt the API key
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    if (empty($api_key)) {
        return 'Error: API key is not set.';
    }

    $base_url = esc_attr(get_option('chatbot_gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'));
    $api_url = $base_url . '/models?key=' . $api_key;

    $response = wp_remote_get($api_url, array(
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['error'])) {
        return 'Error: ' . ($body['error']['message'] ?? 'Unknown error');
    }

    $models = array();
    if (isset($body['models']) && is_array($body['models'])) {
        foreach ($body['models'] as $model) {
            // Filter to only include generateContent supported models
            if (isset($model['supportedGenerationMethods']) &&
                in_array('generateContent', $model['supportedGenerationMethods'])) {
                $model_id = str_replace('models/', '', $model['name']);
                $models[] = array(
                    'id' => $model_id,
                    'name' => $model['displayName'] ?? $model_id,
                    'owned_by' => 'google'
                );
            }
        }
    }

    // If no models found, return default list
    if (empty($models)) {
        $models = array(
            array('id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash', 'owned_by' => 'google'),
            array('id' => 'gemini-1.5-flash-8b', 'name' => 'Gemini 1.5 Flash 8B', 'owned_by' => 'google'),
            array('id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro', 'owned_by' => 'google'),
            array('id' => 'gemini-1.0-pro', 'name' => 'Gemini 1.0 Pro', 'owned_by' => 'google'),
        );
    }

    return $models;
}
