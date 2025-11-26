<?php
/**
 * Check Gemini API key
 * Visit: http://localhost:8881/wp-content/plugins/comfort-comm-chatbot/check-gemini-key.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

$api_key = get_option('chatbot_gemini_api_key', '');

echo '<h1>Gemini API Key Check</h1>';

if (empty($api_key)) {
    echo '<p style="color: red;">❌ No API key found in database</p>';
    echo '<p>Option name: <code>chatbot_gemini_api_key</code></p>';
} else {
    $masked_key = substr($api_key, 0, 10) . '...' . substr($api_key, -4);
    echo '<p style="color: green;">✓ API key found: <code>' . $masked_key . '</code></p>';
    echo '<p>Length: ' . strlen($api_key) . ' characters</p>';

    // Test the API
    echo '<h2>Testing API...</h2>';

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
            'contents' => [['parts' => [['text' => 'Say hello']]]],
        ]),
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        echo '<p style="color: red;">Error: ' . $response->get_error_message() . '</p>';
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            echo '<p style="color: red;">❌ API Error: ' . $body['error']['message'] . '</p>';
            echo '<pre>' . print_r($body['error'], true) . '</pre>';
        } elseif (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            echo '<p style="color: green;">✓ API is working!</p>';
            echo '<p>Response: ' . $body['candidates'][0]['content']['parts'][0]['text'] . '</p>';
        } else {
            echo '<p style="color: orange;">⚠️ Unexpected response</p>';
            echo '<pre>' . print_r($body, true) . '</pre>';
        }
    }
}

echo '<hr>';
echo '<p><a href="/wp-admin/admin.php?page=chatbot-chatgpt&tab=api_model&model=gemini">Go to Gemini API Settings</a></p>';
