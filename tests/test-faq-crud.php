<?php
/**
 * Test: FAQ CRUD Operations
 *
 * Tests all FAQ Create, Read, Update, Delete operations via Supabase REST API.
 */

// Test 1: FAQ functions exist
test_assert(
    function_exists('chatbot_faq_load'),
    'chatbot_faq_load function exists'
);

test_assert(
    function_exists('chatbot_faq_get_count'),
    'chatbot_faq_get_count function exists'
);

test_assert(
    function_exists('chatbot_faq_get_by_id'),
    'chatbot_faq_get_by_id function exists'
);

test_assert(
    function_exists('chatbot_faq_add'),
    'chatbot_faq_add function exists'
);

test_assert(
    function_exists('chatbot_faq_update'),
    'chatbot_faq_update function exists'
);

test_assert(
    function_exists('chatbot_faq_delete'),
    'chatbot_faq_delete function exists'
);

// Test 2: Load FAQs from Supabase
$faqs = chatbot_faq_load();
test_assert(
    is_array($faqs),
    'chatbot_faq_load returns array',
    'Returned: ' . gettype($faqs)
);

test_assert(
    count($faqs) > 0,
    'FAQs are loaded from Supabase',
    'Count: ' . count($faqs)
);

// Test 3: Get FAQ count
$count = chatbot_faq_get_count();
test_assert(
    is_int($count) && $count > 0,
    'chatbot_faq_get_count returns count',
    "Count: $count"
);

test_assert(
    $count === count($faqs),
    'FAQ count matches loaded FAQs',
    "Count: $count, Loaded: " . count($faqs)
);

// Test 4: Get single FAQ by ID
if (count($faqs) > 0) {
    $first_faq_id = $faqs[0]['id'];
    $faq = chatbot_faq_get_by_id($first_faq_id);

    test_assert(
        $faq !== null,
        'chatbot_faq_get_by_id returns FAQ',
        $faq ? 'Found: ' . substr($faq['question'], 0, 40) . '...' : 'Not found'
    );

    test_assert(
        isset($faq['id']) && isset($faq['question']) && isset($faq['answer']),
        'FAQ has required fields (id, question, answer)',
        $faq ? 'Fields present' : 'Missing fields'
    );
}

// Test 5: Get non-existent FAQ
$fake_faq = chatbot_faq_get_by_id('nonexistent_faq_id_12345');
test_assert(
    $fake_faq === null,
    'Non-existent FAQ returns null'
);

// Test 6: Get categories
test_assert(
    function_exists('chatbot_faq_get_top_categories'),
    'chatbot_faq_get_top_categories function exists'
);

$categories = chatbot_faq_get_top_categories(5);
test_assert(
    is_array($categories) && count($categories) > 0,
    'Categories are returned',
    'Found ' . count($categories) . ' categories'
);

// Test 7: CRUD cycle (Add, Update, Delete) - only run if we have permission
// This test creates a temporary FAQ, updates it, then deletes it
$test_question = '__TEST_FAQ_' . time() . '__ What is a test?';
$test_answer = 'This is a temporary test FAQ that will be deleted.';

$add_result = chatbot_faq_add($test_question, $test_answer, 'Test');
test_assert(
    $add_result['success'] === true,
    'Add FAQ succeeds',
    $add_result['success'] ? 'Added: ' . ($add_result['faq_id'] ?? 'unknown') : $add_result['message']
);

if ($add_result['success'] && isset($add_result['faq_id'])) {
    $test_faq_id = $add_result['faq_id'];

    // Verify it was added
    $verify_add = chatbot_faq_get_by_id($test_faq_id);
    test_assert(
        $verify_add !== null && $verify_add['question'] === $test_question,
        'Added FAQ can be retrieved'
    );

    // Update the FAQ
    $update_result = chatbot_faq_update($test_faq_id, $test_question . ' (updated)', $test_answer . ' Updated.', 'Test Updated');
    test_assert(
        $update_result['success'] === true,
        'Update FAQ succeeds',
        $update_result['success'] ? 'Updated' : $update_result['message']
    );

    // Verify update
    $verify_update = chatbot_faq_get_by_id($test_faq_id);
    test_assert(
        $verify_update !== null && strpos($verify_update['question'], '(updated)') !== false,
        'Updated FAQ has new content'
    );

    // Delete the FAQ
    $delete_result = chatbot_faq_delete($test_faq_id);
    test_assert(
        $delete_result === true,
        'Delete FAQ succeeds'
    );

    // Verify deletion
    $verify_delete = chatbot_faq_get_by_id($test_faq_id);
    test_assert(
        $verify_delete === null,
        'Deleted FAQ is gone'
    );

    // Verify count is back to original
    $final_count = chatbot_faq_get_count();
    test_assert(
        $final_count === $count,
        'FAQ count unchanged after CRUD cycle',
        "Original: $count, Final: $final_count"
    );
}
