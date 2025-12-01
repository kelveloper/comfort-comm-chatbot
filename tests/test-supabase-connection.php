<?php
/**
 * Test: Supabase Connection
 *
 * Tests that Supabase is properly configured and accessible.
 */

// Test 1: Configuration exists
test_assert(
    defined('CHATBOT_PG_HOST'),
    'CHATBOT_PG_HOST is defined',
    defined('CHATBOT_PG_HOST') ? CHATBOT_PG_HOST : 'Not defined'
);

test_assert(
    defined('CHATBOT_SUPABASE_ANON_KEY'),
    'CHATBOT_SUPABASE_ANON_KEY is defined',
    defined('CHATBOT_SUPABASE_ANON_KEY') ? 'Key present (hidden)' : 'Not defined'
);

// Test 2: Supabase config function works
test_assert(
    function_exists('chatbot_vector_get_supabase_config'),
    'chatbot_vector_get_supabase_config function exists'
);

if (function_exists('chatbot_vector_get_supabase_config')) {
    $config = chatbot_vector_get_supabase_config();

    test_assert(
        $config !== null,
        'Supabase config is returned',
        $config ? 'URL: ' . ($config['url'] ?? 'missing') : 'Config is null'
    );

    test_assert(
        isset($config['url']) && !empty($config['url']),
        'Supabase URL is configured',
        $config['url'] ?? 'Not set'
    );

    test_assert(
        isset($config['anon_key']) && !empty($config['anon_key']),
        'Supabase anon key is configured'
    );
}

// Test 3: Vector search availability
test_assert(
    function_exists('chatbot_vector_is_available'),
    'chatbot_vector_is_available function exists'
);

if (function_exists('chatbot_vector_is_available')) {
    $available = chatbot_vector_is_available();
    test_assert(
        $available === true,
        'Vector search is available',
        $available ? 'Available' : 'Not available - check Supabase config'
    );
}
