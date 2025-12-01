<?php
/**
 * Test Supabase Database Operations
 *
 * Tests the chatbot_conversations, chatbot_interactions, and chatbot_gap_questions tables
 *
 * @package comfort-comm-chatbot
 */

// This file is loaded by run-tests.php which loads wp-load.php

echo "=== Supabase Database Tests ===\n\n";

// Test 1: Check if Supabase is configured
echo "1. Checking Supabase configuration...\n";
$supabase_configured = function_exists('chatbot_supabase_is_configured') && chatbot_supabase_is_configured();
test_assert(
    $supabase_configured,
    'Supabase is configured',
    'CHATBOT_SUPABASE_ANON_KEY should be defined'
);

if (!$supabase_configured) {
    echo "   Skipping remaining Supabase DB tests - not configured\n\n";
    return;
}

// Test 2: Check should_use_supabase_db function
echo "2. Checking chatbot_should_use_supabase_db()...\n";
$should_use = function_exists('chatbot_should_use_supabase_db') && chatbot_should_use_supabase_db();
test_assert(
    $should_use,
    'chatbot_should_use_supabase_db returns true',
    'Should return true when Supabase is configured'
);

// Test 3: Test connection
echo "3. Testing Supabase connection...\n";
$connected = function_exists('chatbot_supabase_test_connection') && chatbot_supabase_test_connection();
test_assert(
    $connected,
    'Supabase connection successful',
    'Should be able to connect to Supabase'
);

if (!$connected) {
    echo "   Skipping remaining tests - connection failed\n\n";
    return;
}

// Test 4: Log a conversation
echo "4. Testing conversation logging...\n";
$test_session_id = 'test-session-' . time();
$log_result = false;
if (function_exists('chatbot_supabase_log_conversation')) {
    $log_result = chatbot_supabase_log_conversation(
        $test_session_id,
        0,
        1,
        'Visitor',
        null,
        null,
        'Test Bot',
        'Test message from automated test',
        null
    );
}
test_assert(
    $log_result,
    'Conversation logged to Supabase',
    'Should insert conversation record'
);

// Test 5: Get conversations
echo "5. Testing get conversations...\n";
$conversations = [];
if (function_exists('chatbot_supabase_get_conversations')) {
    $conversations = chatbot_supabase_get_conversations($test_session_id, 10);
}
test_assert(
    !empty($conversations) && count($conversations) >= 1,
    'Retrieved conversation from Supabase',
    'Should find at least 1 conversation'
);

// Test 6: Update interaction count
echo "6. Testing interaction tracking...\n";
$interaction_result = false;
if (function_exists('chatbot_supabase_update_interaction_count')) {
    $result = chatbot_supabase_update_interaction_count();
    $interaction_result = $result['success'] ?? false;
}
test_assert(
    $interaction_result,
    'Interaction count updated',
    'Should update daily interaction count'
);

// Test 7: Log gap question
echo "7. Testing gap question logging...\n";
$gap_result = false;
if (function_exists('chatbot_supabase_log_gap_question')) {
    $gap_result = chatbot_supabase_log_gap_question(
        'Test gap question from automated test?',
        $test_session_id,
        0,
        1,
        0.35,
        null
    );
}
test_assert(
    $gap_result,
    'Gap question logged to Supabase',
    'Should insert gap question record'
);

// Test 8: Get gap questions
echo "8. Testing get gap questions...\n";
$gap_questions = [];
if (function_exists('chatbot_supabase_get_gap_questions')) {
    $gap_questions = chatbot_supabase_get_gap_questions(10, false);
}
test_assert(
    !empty($gap_questions) && count($gap_questions) >= 1,
    'Retrieved gap questions from Supabase',
    'Should find at least 1 gap question'
);

// Test 9: Get diagnostics
echo "9. Testing diagnostics...\n";
$diagnostics = [];
if (function_exists('chatbot_supabase_get_diagnostics')) {
    $diagnostics = chatbot_supabase_get_diagnostics();
}
test_assert(
    !empty($diagnostics) && isset($diagnostics['chatbot_faqs']),
    'Diagnostics returned table counts',
    'Should return counts for all tables'
);

if (!empty($diagnostics)) {
    echo "   Table counts:\n";
    foreach ($diagnostics as $table => $count) {
        echo "   - $table: $count\n";
    }
}

// Test 10: Wrapper functions work
echo "10. Testing wrapper functions...\n";
$wrapper_works = false;
if (function_exists('chatbot_db_log_conversation')) {
    $wrapper_works = chatbot_db_log_conversation(
        'wrapper-test-' . time(),
        0,
        1,
        'Visitor',
        null,
        null,
        'Wrapper Test',
        'Test from wrapper function',
        null
    );
}
test_assert(
    $wrapper_works,
    'Wrapper function chatbot_db_log_conversation works',
    'Wrapper should use Supabase when configured'
);

echo "\n";
