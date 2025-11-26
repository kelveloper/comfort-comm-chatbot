<?php
/**
 * Chatbot Vector Search - Semantic Search Function
 *
 * Performs semantic similarity search using cosine similarity
 * on vector embeddings in PostgreSQL with pgvector.
 *
 * NO FALLBACK - Vector search is required.
 *
 * @package comfort-comm-chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Include dependencies
require_once plugin_dir_path(__FILE__) . 'chatbot-vector-schema.php';
require_once plugin_dir_path(__FILE__) . 'chatbot-vector-migration.php';

/**
 * Default similarity threshold settings
 */
define('CHATBOT_VECTOR_THRESHOLD_VERY_HIGH', 0.85);  // Very confident match
define('CHATBOT_VECTOR_THRESHOLD_HIGH', 0.75);       // High confidence
define('CHATBOT_VECTOR_THRESHOLD_MEDIUM', 0.65);     // Medium confidence
define('CHATBOT_VECTOR_THRESHOLD_LOW', 0.50);        // Low confidence
define('CHATBOT_VECTOR_THRESHOLD_MIN', 0.40);        // Minimum to return

/**
 * Search FAQs using vector similarity
 *
 * @param string $query The user's question
 * @param array $options Search options:
 *   - threshold: Minimum similarity score (0-1), default 0.40
 *   - limit: Maximum results to return, default 5
 *   - category: Filter by category (optional)
 *   - return_scores: Include similarity scores in results, default true
 * @return array Search results with similarity scores
 */
function chatbot_vector_search($query, $options = []) {
    // Default options
    $defaults = [
        'threshold' => CHATBOT_VECTOR_THRESHOLD_MIN,
        'limit' => 5,
        'category' => null,
        'return_scores' => true
    ];
    $options = array_merge($defaults, $options);

    // Vector search is REQUIRED - no fallback
    if (!chatbot_vector_is_available()) {
        error_log('[Chatbot Vector] CRITICAL: Vector search not available. Check PostgreSQL/pgvector configuration.');
        return [
            'success' => false,
            'error' => 'Vector search is not configured. Please check PostgreSQL connection.',
            'results' => [],
            'count' => 0,
            'search_type' => 'error'
        ];
    }

    $pdo = chatbot_vector_get_pg_connection();
    if (!$pdo) {
        error_log('[Chatbot Vector] CRITICAL: Could not connect to PostgreSQL database.');
        return [
            'success' => false,
            'error' => 'Database connection failed.',
            'results' => [],
            'count' => 0,
            'search_type' => 'error'
        ];
    }

    // Generate embedding for the query
    $query_embedding = chatbot_vector_generate_embedding($query);

    if (!$query_embedding) {
        error_log('[Chatbot Vector] Failed to generate query embedding. Check OpenAI API key.');
        return [
            'success' => false,
            'error' => 'Failed to generate embedding. Check OpenAI API configuration.',
            'results' => [],
            'count' => 0,
            'search_type' => 'error'
        ];
    }

    $embedding_str = chatbot_vector_to_pg_format($query_embedding);

    try {
        // Build the query with cosine similarity
        // pgvector uses <=> for cosine distance (1 - similarity)
        // So we calculate: 1 - (embedding <=> query_embedding) for similarity
        $sql = '
            SELECT
                faq_id,
                question,
                answer,
                category,
                keywords,
                1 - (combined_embedding <=> ?::vector) AS similarity
            FROM chatbot_faqs
            WHERE combined_embedding IS NOT NULL
        ';

        $params = [$embedding_str];

        // Add category filter if specified
        if (!empty($options['category'])) {
            $sql .= ' AND category = ?';
            $params[] = $options['category'];
        }

        // Add similarity threshold filter
        $sql .= ' AND 1 - (combined_embedding <=> ?::vector) >= ?';
        $params[] = $embedding_str;
        $params[] = $options['threshold'];

        // Order by similarity (highest first) and limit results
        $sql .= ' ORDER BY similarity DESC LIMIT ?';
        $params[] = (int) $options['limit'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Process results
        $processed_results = [];
        foreach ($results as $row) {
            $result = [
                'faq_id' => $row['faq_id'],
                'question' => $row['question'],
                'answer' => $row['answer'],
                'category' => $row['category']
            ];

            if ($options['return_scores']) {
                $result['similarity'] = round((float) $row['similarity'], 4);
                $result['confidence'] = chatbot_vector_get_confidence_level($row['similarity']);
            }

            $processed_results[] = $result;
        }

        return [
            'success' => true,
            'results' => $processed_results,
            'count' => count($processed_results),
            'search_type' => 'vector'
        ];

    } catch (PDOException $e) {
        error_log('[Chatbot Vector] Search query failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database query failed: ' . $e->getMessage(),
            'results' => [],
            'count' => 0,
            'search_type' => 'error'
        ];
    }
}

/**
 * Find the best matching FAQ for a query
 *
 * @param string $query The user's question
 * @param float $threshold Minimum similarity threshold (default 0.40)
 * @return array|null Best match with confidence info, or null if no good match
 */
function chatbot_vector_find_best_match($query, $threshold = CHATBOT_VECTOR_THRESHOLD_MIN) {
    $results = chatbot_vector_search($query, [
        'threshold' => $threshold,
        'limit' => 1,
        'return_scores' => true
    ]);

    if (!$results['success'] || empty($results['results'])) {
        return null;
    }

    $best = $results['results'][0];

    return [
        'match' => [
            'id' => $best['faq_id'],
            'question' => $best['question'],
            'answer' => $best['answer'],
            'category' => $best['category']
        ],
        'score' => $best['similarity'],
        'confidence' => $best['confidence'],
        'search_type' => $results['search_type']
    ];
}

/**
 * Get confidence level string from similarity score
 *
 * @param float $similarity Similarity score (0-1)
 * @return string Confidence level: 'very_high', 'high', 'medium', 'low', 'none'
 */
function chatbot_vector_get_confidence_level($similarity) {
    if ($similarity >= CHATBOT_VECTOR_THRESHOLD_VERY_HIGH) {
        return 'very_high';
    } elseif ($similarity >= CHATBOT_VECTOR_THRESHOLD_HIGH) {
        return 'high';
    } elseif ($similarity >= CHATBOT_VECTOR_THRESHOLD_MEDIUM) {
        return 'medium';
    } elseif ($similarity >= CHATBOT_VECTOR_THRESHOLD_LOW) {
        return 'low';
    }
    return 'none';
}

/**
 * Search with hybrid approach (vector + keyword boost)
 *
 * Combines vector similarity with keyword matching for better results.
 *
 * @param string $query The user's question
 * @param array $options Search options
 * @return array Search results with combined scoring
 */
function chatbot_vector_hybrid_search($query, $options = []) {
    $defaults = [
        'threshold' => CHATBOT_VECTOR_THRESHOLD_MIN,
        'limit' => 5,
        'vector_weight' => 0.8,  // Weight for vector similarity
        'keyword_weight' => 0.2, // Weight for keyword matching
        'category' => null
    ];
    $options = array_merge($defaults, $options);

    // Get vector search results
    $vector_results = chatbot_vector_search($query, [
        'threshold' => $options['threshold'] * 0.8, // Lower threshold for hybrid
        'limit' => $options['limit'] * 2, // Get more results to rerank
        'category' => $options['category'],
        'return_scores' => true
    ]);

    if (!$vector_results['success'] || empty($vector_results['results'])) {
        return $vector_results;
    }

    // Generate query keywords
    $query_keywords = chatbot_faq_generate_keywords($query);
    $query_words = array_filter(explode(' ', $query_keywords));

    // Rerank results with keyword boost
    $reranked = [];
    foreach ($vector_results['results'] as $result) {
        $vector_score = $result['similarity'];

        // Calculate keyword overlap
        $faq_keywords = chatbot_faq_generate_keywords($result['question']);
        $faq_words = array_filter(explode(' ', $faq_keywords));

        $keyword_matches = 0;
        foreach ($query_words as $qw) {
            foreach ($faq_words as $fw) {
                if ($qw === $fw || (strlen($qw) > 4 && strpos($fw, $qw) !== false)) {
                    $keyword_matches++;
                    break;
                }
            }
        }

        $keyword_score = count($query_words) > 0
            ? $keyword_matches / count($query_words)
            : 0;

        // Combined score
        $combined_score = ($vector_score * $options['vector_weight'])
                        + ($keyword_score * $options['keyword_weight']);

        $result['similarity'] = round($combined_score, 4);
        $result['vector_score'] = round($vector_score, 4);
        $result['keyword_score'] = round($keyword_score, 4);
        $result['confidence'] = chatbot_vector_get_confidence_level($combined_score);

        $reranked[] = $result;
    }

    // Sort by combined score
    usort($reranked, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    // Filter by threshold and limit
    $filtered = array_filter($reranked, function($r) use ($options) {
        return $r['similarity'] >= $options['threshold'];
    });

    $final_results = array_slice(array_values($filtered), 0, $options['limit']);

    return [
        'success' => true,
        'results' => $final_results,
        'count' => count($final_results),
        'search_type' => 'hybrid'
    ];
}

/**
 * Main FAQ search function - replaces chatbot_faq_search()
 *
 * This is the main entry point that should be used throughout the plugin.
 * Uses vector search ONLY - no fallback.
 *
 * @param string $query The user's question
 * @param bool $return_score Whether to return score information
 * @param string|null $session_id Session ID for analytics
 * @param int|null $user_id User ID for analytics
 * @param int|null $page_id Page ID for analytics
 * @return array|null Match result or null if no match
 */
function chatbot_vector_faq_search($query, $return_score = false, $session_id = null, $user_id = null, $page_id = null) {
    // Vector search only - no fallback
    $result = chatbot_vector_find_best_match($query, CHATBOT_VECTOR_THRESHOLD_MIN);

    // No match found
    if (!$result) {
        // Log as gap question
        if (function_exists('chatbot_log_gap_question')) {
            chatbot_log_gap_question($query, null, 0, 'none', $session_id, $user_id, $page_id);
        }

        if ($return_score) {
            return ['match' => null, 'score' => 0, 'confidence' => 'none'];
        }
        return null;
    }

    // Track FAQ usage
    if (function_exists('chatbot_track_faq_usage') && isset($result['match']['id'])) {
        chatbot_track_faq_usage($result['match']['id'], $result['score']);
    }

    // Log gap questions for low confidence matches
    if ($result['score'] < 0.6) {
        if (function_exists('chatbot_log_gap_question')) {
            chatbot_log_gap_question(
                $query,
                $result['match']['id'] ?? null,
                $result['score'],
                $result['confidence'],
                $session_id,
                $user_id,
                $page_id
            );
        }
    }

    if ($return_score) {
        return [
            'match' => $result['match'],
            'score' => $result['score'],
            'confidence' => $result['confidence'],
            'match_type' => $result['search_type']
        ];
    }

    return $result['match'];
}

/**
 * Get similar FAQs to a given FAQ (for "related questions" feature)
 *
 * @param string $faq_id The FAQ ID to find similar items for
 * @param int $limit Maximum number of similar FAQs to return
 * @return array Array of similar FAQs
 */
function chatbot_vector_get_similar_faqs($faq_id, $limit = 3) {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        error_log('[Chatbot Vector] Cannot get similar FAQs - no database connection');
        return [];
    }

    try {
        // Get the embedding of the source FAQ
        $stmt = $pdo->prepare('
            SELECT combined_embedding, category
            FROM chatbot_faqs
            WHERE faq_id = ?
        ');
        $stmt->execute([$faq_id]);
        $source = $stmt->fetch();

        if (!$source || !$source['combined_embedding']) {
            return [];
        }

        // Find similar FAQs (excluding the source)
        $stmt = $pdo->prepare('
            SELECT
                faq_id,
                question,
                answer,
                category,
                1 - (combined_embedding <=> ?::vector) AS similarity
            FROM chatbot_faqs
            WHERE faq_id != ?
            AND combined_embedding IS NOT NULL
            ORDER BY similarity DESC
            LIMIT ?
        ');
        $stmt->execute([
            $source['combined_embedding'],
            $faq_id,
            $limit
        ]);

        return $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('[Chatbot Vector] Get similar FAQs failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Search FAQs within a specific category
 *
 * @param string $query Search query
 * @param string $category Category to search in
 * @param int $limit Maximum results
 * @return array Search results
 */
function chatbot_vector_search_by_category($query, $category, $limit = 5) {
    return chatbot_vector_search($query, [
        'category' => $category,
        'limit' => $limit,
        'threshold' => CHATBOT_VECTOR_THRESHOLD_LOW
    ]);
}
