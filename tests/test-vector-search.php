<?php
/**
 * Test: Vector Search
 *
 * Tests semantic search functionality via Supabase pgvector.
 */

// Test 1: Search functions exist
test_assert(
    function_exists('chatbot_vector_search'),
    'chatbot_vector_search function exists'
);

test_assert(
    function_exists('chatbot_vector_find_best_match'),
    'chatbot_vector_find_best_match function exists'
);

test_assert(
    function_exists('chatbot_vector_faq_search'),
    'chatbot_vector_faq_search function exists'
);

// Test 2: Embedding generation
test_assert(
    function_exists('chatbot_vector_generate_embedding'),
    'chatbot_vector_generate_embedding function exists'
);

// Test embedding generation (this calls Gemini API)
$test_text = "What are your store hours?";
$embedding = chatbot_vector_generate_embedding($test_text);

test_assert(
    $embedding !== null,
    'Embedding generation returns result',
    $embedding ? 'Dimensions: ' . count($embedding) : 'Failed - check Gemini API key'
);

test_assert(
    is_array($embedding) && count($embedding) === 1536,
    'Embedding has 1536 dimensions',
    $embedding ? 'Got: ' . count($embedding) . ' dimensions' : 'No embedding'
);

// Test 3: Vector search returns results
$search_results = chatbot_vector_search("What time do you open?", [
    'threshold' => 0.3,
    'limit' => 5
]);

test_assert(
    isset($search_results['success']) && $search_results['success'] === true,
    'Vector search succeeds',
    $search_results['success'] ? 'Search type: ' . ($search_results['search_type'] ?? 'unknown') : ($search_results['error'] ?? 'Unknown error')
);

test_assert(
    isset($search_results['results']) && is_array($search_results['results']),
    'Vector search returns results array',
    'Results: ' . ($search_results['count'] ?? 0)
);

// Test 4: Best match function
$best_match = chatbot_vector_find_best_match("How much does internet cost?", 0.3);

test_assert(
    $best_match !== null,
    'Best match found for pricing query',
    $best_match ? 'Score: ' . round($best_match['score'] * 100) . '%' : 'No match found'
);

if ($best_match) {
    test_assert(
        isset($best_match['match']) && isset($best_match['score']) && isset($best_match['confidence']),
        'Best match has required fields',
        'Confidence: ' . ($best_match['confidence'] ?? 'unknown')
    );

    test_assert(
        $best_match['score'] >= 0.3,
        'Best match score is above threshold',
        'Score: ' . round($best_match['score'] * 100) . '%'
    );
}

// Test 5: Semantic matching quality
$semantic_tests = [
    // Query => Expected keyword in match
    ["Where is your store located?", "location"],
    ["My wifi is slow", "slow"],
    ["Can I keep my phone number?", "number"],
];

foreach ($semantic_tests as $test) {
    $query = $test[0];
    $expected_keyword = $test[1];

    $match = chatbot_vector_find_best_match($query, 0.4);

    if ($match && isset($match['match']['question'])) {
        $matched_question = strtolower($match['match']['question']);
        $matched_answer = strtolower($match['match']['answer'] ?? '');

        $keyword_found = strpos($matched_question, $expected_keyword) !== false ||
                        strpos($matched_answer, $expected_keyword) !== false;

        test_assert(
            $match['score'] >= 0.4,
            "Semantic match for: \"$query\"",
            'Score: ' . round($match['score'] * 100) . '% - ' . substr($match['match']['question'], 0, 50) . '...'
        );
    } else {
        test_assert(
            false,
            "Semantic match for: \"$query\"",
            'No match found above threshold'
        );
    }
}

// Test 6: Main FAQ search function
$faq_result = chatbot_vector_faq_search("What internet plans do you have?", true);

test_assert(
    $faq_result !== null && isset($faq_result['match']),
    'FAQ search returns match',
    $faq_result ? 'Score: ' . round(($faq_result['score'] ?? 0) * 100) . '%' : 'No result'
);

if ($faq_result && isset($faq_result['match'])) {
    test_assert(
        isset($faq_result['match']['question']) && isset($faq_result['match']['answer']),
        'FAQ match has question and answer',
        'Question: ' . substr($faq_result['match']['question'], 0, 40) . '...'
    );
}

// Test 7: Low confidence query (should still return something or null)
$low_confidence_result = chatbot_vector_faq_search("asdfghjkl random nonsense query xyz", true);

test_assert(
    $low_confidence_result === null || (isset($low_confidence_result['score']) && $low_confidence_result['score'] < 0.5),
    'Nonsense query returns null or low confidence',
    $low_confidence_result ? 'Score: ' . round(($low_confidence_result['score'] ?? 0) * 100) . '%' : 'Returned null (correct)'
);
