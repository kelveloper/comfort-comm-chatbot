<?php
/**
 * Test: Embedding Generation
 *
 * Tests that embeddings are generated correctly via Gemini API.
 */

// Test 1: Gemini API key is configured
$gemini_key = get_option('chatbot_gemini_api_key', '');
test_assert(
    !empty($gemini_key),
    'Gemini API key is configured',
    !empty($gemini_key) ? 'Key present (hidden)' : 'No key found'
);

// Test 2: Embedding generation function exists
test_assert(
    function_exists('chatbot_vector_generate_embedding'),
    'chatbot_vector_generate_embedding function exists'
);

test_assert(
    function_exists('chatbot_vector_generate_embedding_gemini'),
    'chatbot_vector_generate_embedding_gemini function exists'
);

// Test 3: Generate embedding for short text
$short_text = "Hello";
$short_embedding = chatbot_vector_generate_embedding($short_text);

test_assert(
    $short_embedding !== null,
    'Embedding generated for short text',
    $short_embedding ? 'Success' : 'Failed'
);

// Test 4: Generate embedding for long text
$long_text = str_repeat("This is a test sentence with multiple words. ", 100);
$long_embedding = chatbot_vector_generate_embedding($long_text);

test_assert(
    $long_embedding !== null,
    'Embedding generated for long text',
    $long_embedding ? 'Success' : 'Failed'
);

// Test 5: Embedding dimensions are correct
if ($short_embedding) {
    test_assert(
        count($short_embedding) === 1536,
        'Embedding has 1536 dimensions (padded from 768)',
        'Dimensions: ' . count($short_embedding)
    );
}

// Test 6: Embeddings are normalized floats
if ($short_embedding) {
    $all_floats = true;
    $in_range = true;

    foreach (array_slice($short_embedding, 0, 100) as $val) {
        if (!is_float($val) && !is_int($val)) {
            $all_floats = false;
            break;
        }
        if ($val < -2 || $val > 2) {
            $in_range = false;
        }
    }

    test_assert(
        $all_floats,
        'Embedding values are numeric'
    );

    test_assert(
        $in_range,
        'Embedding values are in reasonable range (-2 to 2)'
    );
}

// Test 7: Empty text handling
$empty_embedding = chatbot_vector_generate_embedding("");
test_assert(
    $empty_embedding === null,
    'Empty text returns null'
);

$whitespace_embedding = chatbot_vector_generate_embedding("   ");
test_assert(
    $whitespace_embedding === null,
    'Whitespace-only text returns null'
);

// Test 8: Special characters handling
$special_text = "What's the price for \"unlimited\" plans? (including tax)";
$special_embedding = chatbot_vector_generate_embedding($special_text);

test_assert(
    $special_embedding !== null && count($special_embedding) === 1536,
    'Special characters handled correctly',
    $special_embedding ? 'Success' : 'Failed'
);

// Test 9: Different questions produce different embeddings
$q1 = "What are your store hours?";
$q2 = "How much does internet cost?";

$emb1 = chatbot_vector_generate_embedding($q1);
$emb2 = chatbot_vector_generate_embedding($q2);

if ($emb1 && $emb2) {
    // Calculate cosine similarity
    $dot_product = 0;
    $norm1 = 0;
    $norm2 = 0;

    for ($i = 0; $i < min(768, count($emb1)); $i++) { // Only check first 768 (non-padded)
        $dot_product += $emb1[$i] * $emb2[$i];
        $norm1 += $emb1[$i] * $emb1[$i];
        $norm2 += $emb2[$i] * $emb2[$i];
    }

    $similarity = $dot_product / (sqrt($norm1) * sqrt($norm2));

    test_assert(
        $similarity < 0.95,
        'Different questions have different embeddings',
        'Similarity: ' . round($similarity * 100) . '%'
    );

    test_assert(
        $similarity > 0.1,
        'Embeddings are not completely random',
        'Similarity: ' . round($similarity * 100) . '%'
    );
}

// Test 10: Similar questions produce similar embeddings
$similar_q1 = "What are your business hours?";
$similar_q2 = "When are you open?";

$similar_emb1 = chatbot_vector_generate_embedding($similar_q1);
$similar_emb2 = chatbot_vector_generate_embedding($similar_q2);

if ($similar_emb1 && $similar_emb2) {
    $dot_product = 0;
    $norm1 = 0;
    $norm2 = 0;

    for ($i = 0; $i < min(768, count($similar_emb1)); $i++) {
        $dot_product += $similar_emb1[$i] * $similar_emb2[$i];
        $norm1 += $similar_emb1[$i] * $similar_emb1[$i];
        $norm2 += $similar_emb2[$i] * $similar_emb2[$i];
    }

    $similarity = $dot_product / (sqrt($norm1) * sqrt($norm2));

    test_assert(
        $similarity > 0.5,
        'Similar questions have similar embeddings',
        'Similarity: ' . round($similarity * 100) . '%'
    );
}
