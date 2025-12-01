<?php
/**
 * Supabase Database Operations
 *
 * Replaces WordPress $wpdb calls with Supabase REST API
 * for conversation logging, interactions, and gap questions.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * Check if Supabase is configured
 */
function chatbot_supabase_is_configured() {
    // Check admin settings first, then wp-config.php
    if (function_exists('chatbot_supabase_get_config')) {
        $config = chatbot_supabase_get_config();
        return !empty($config['anon_key']);
    }
    // Fallback to wp-config.php constant
    return defined('CHATBOT_SUPABASE_ANON_KEY') && !empty(CHATBOT_SUPABASE_ANON_KEY);
}

/**
 * Get Supabase anon key from settings or wp-config
 */
function chatbot_supabase_get_anon_key() {
    if (function_exists('chatbot_supabase_get_config')) {
        $config = chatbot_supabase_get_config();
        if (!empty($config['anon_key'])) {
            return $config['anon_key'];
        }
    }
    // Fallback to wp-config.php constant
    return defined('CHATBOT_SUPABASE_ANON_KEY') ? CHATBOT_SUPABASE_ANON_KEY : '';
}

/**
 * Get Supabase REST API base URL
 */
function chatbot_supabase_get_url() {
    // Check admin settings first
    if (function_exists('chatbot_supabase_get_config')) {
        $config = chatbot_supabase_get_config();
        if (!empty($config['project_url'])) {
            return rtrim($config['project_url'], '/') . '/rest/v1';
        }
    }

    // Fallback to wp-config.php constant
    if (defined('CHATBOT_PG_HOST')) {
        // Extract project ref from host (db.xxxxx.supabase.co -> xxxxx)
        $host = CHATBOT_PG_HOST;
        if (preg_match('/db\.([^.]+)\.supabase\.co/', $host, $matches)) {
            return 'https://' . $matches[1] . '.supabase.co/rest/v1';
        }
        // Also handle without db. prefix
        if (preg_match('/([^.]+)\.supabase\.co/', $host, $matches)) {
            return 'https://' . $matches[1] . '.supabase.co/rest/v1';
        }
    }
    return null;
}

/**
 * Make Supabase REST API request
 */
function chatbot_supabase_request($endpoint, $method = 'GET', $data = null, $query_params = []) {
    $base_url = chatbot_supabase_get_url();
    $anon_key = chatbot_supabase_get_anon_key();

    if (!$base_url || empty($anon_key)) {
        return ['success' => false, 'error' => 'Supabase not configured'];
    }

    $url = $base_url . '/' . $endpoint;

    // Add query parameters
    if (!empty($query_params)) {
        $url .= '?' . http_build_query($query_params);
    }

    $headers = [
        'apikey: ' . $anon_key,
        'Authorization: Bearer ' . $anon_key,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    switch ($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log('[Chatbot Supabase] cURL error: ' . $error);
        return ['success' => false, 'error' => $error];
    }

    $decoded = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'data' => $decoded, 'http_code' => $http_code];
    }

    error_log('[Chatbot Supabase] API error: ' . $response);
    return ['success' => false, 'error' => $decoded['message'] ?? 'Unknown error', 'http_code' => $http_code];
}

// =============================================================================
// CONVERSATION LOGGING (replaces wp_chatbot_chatgpt_conversation_log)
// =============================================================================

/**
 * Append message to conversation log
 */
function chatbot_supabase_log_conversation($session_id, $user_id, $page_id, $user_type, $thread_id, $assistant_id, $assistant_name, $message, $sentiment_score = null) {
    $data = [
        'session_id' => $session_id,
        'user_id' => (string)$user_id,
        'page_id' => (string)$page_id,
        'user_type' => $user_type,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'assistant_name' => $assistant_name,
        'message_text' => $message,
        'interaction_time' => gmdate('c') // ISO 8601 format
    ];

    if ($sentiment_score !== null) {
        $data['sentiment_score'] = $sentiment_score;
    }

    $result = chatbot_supabase_request('chatbot_conversations', 'POST', $data);

    if (!$result['success']) {
        error_log('[Chatbot Supabase] Failed to log conversation: ' . ($result['error'] ?? 'Unknown'));
    }

    return $result['success'];
}

/**
 * Get conversations by session ID
 */
function chatbot_supabase_get_conversations($session_id, $limit = 100) {
    $query_params = [
        'session_id' => 'eq.' . $session_id,
        'order' => 'interaction_time.asc',
        'limit' => $limit
    ];

    $result = chatbot_supabase_request('chatbot_conversations', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

/**
 * Get recent conversations (for reporting)
 */
function chatbot_supabase_get_recent_conversations($days = 30, $limit = 1000) {
    $since = gmdate('c', strtotime("-{$days} days"));

    $query_params = [
        'interaction_time' => 'gte.' . $since,
        'order' => 'interaction_time.desc',
        'limit' => $limit
    ];

    $result = chatbot_supabase_request('chatbot_conversations', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

/**
 * Delete conversations older than X days
 */
function chatbot_supabase_delete_old_conversations($days) {
    $cutoff = gmdate('c', strtotime("-{$days} days"));

    $query_params = [
        'interaction_time' => 'lt.' . $cutoff
    ];

    $result = chatbot_supabase_request('chatbot_conversations', 'DELETE', null, $query_params);

    return $result['success'];
}

/**
 * Get conversation count by date range
 */
function chatbot_supabase_get_conversation_stats($start_date, $end_date) {
    $query_params = [
        'interaction_time' => 'gte.' . $start_date,
        'and' => '(interaction_time.lte.' . $end_date . ')',
        'select' => 'id'
    ];

    // Use Prefer header for count
    $base_url = chatbot_supabase_get_url();
    $anon_key = chatbot_supabase_get_anon_key();
    $url = $base_url . '/chatbot_conversations?' . http_build_query($query_params);

    $headers = [
        'apikey: ' . $anon_key,
        'Authorization: Bearer ' . $anon_key,
        'Prefer: count=exact'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers_str = substr($response, 0, $header_size);
    curl_close($ch);

    // Extract count from Content-Range header
    if (preg_match('/content-range: \d+-\d+\/(\d+)/i', $headers_str, $matches)) {
        return (int)$matches[1];
    }

    return 0;
}

// =============================================================================
// INTERACTION TRACKING (replaces wp_chatbot_chatgpt_interactions)
// =============================================================================

/**
 * Update daily interaction count
 */
function chatbot_supabase_update_interaction_count() {
    $today = gmdate('Y-m-d');

    // First, try to get existing record
    $query_params = ['date' => 'eq.' . $today];
    $result = chatbot_supabase_request('chatbot_interactions', 'GET', null, $query_params);

    if ($result['success'] && !empty($result['data'])) {
        // Update existing record
        $current_count = $result['data'][0]['count'];
        $data = ['count' => $current_count + 1];

        return chatbot_supabase_request('chatbot_interactions', 'PATCH', $data, $query_params);
    } else {
        // Insert new record
        $data = [
            'date' => $today,
            'count' => 1
        ];

        return chatbot_supabase_request('chatbot_interactions', 'POST', $data);
    }
}

/**
 * Get interaction counts for date range
 */
function chatbot_supabase_get_interaction_counts($start_date, $end_date) {
    $query_params = [
        'date' => 'gte.' . $start_date,
        'and' => '(date.lte.' . $end_date . ')',
        'order' => 'date.asc'
    ];

    $result = chatbot_supabase_request('chatbot_interactions', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

/**
 * Get total interactions for a period
 */
function chatbot_supabase_get_total_interactions($days = 30) {
    $start_date = gmdate('Y-m-d', strtotime("-{$days} days"));
    $end_date = gmdate('Y-m-d');

    $counts = chatbot_supabase_get_interaction_counts($start_date, $end_date);

    $total = 0;
    foreach ($counts as $row) {
        $total += $row['count'];
    }

    return $total;
}

// =============================================================================
// GAP QUESTIONS (replaces wp_chatbot_gap_questions)
// =============================================================================

/**
 * Log a gap question (unanswered or low confidence)
 * Now includes vector embedding for semantic clustering
 */
function chatbot_supabase_log_gap_question($question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id = null) {
    // Try PDO first for proper vector handling
    $pdo = function_exists('chatbot_vector_get_pg_connection') ? chatbot_vector_get_pg_connection() : null;

    if ($pdo) {
        return chatbot_supabase_log_gap_question_pdo($pdo, $question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id);
    }

    // Fallback to REST API (without embedding)
    $data = [
        'question_text' => $question_text,
        'session_id' => $session_id,
        'user_id' => (int)$user_id,
        'page_id' => (int)$page_id,
        'faq_confidence' => $faq_confidence,
        'faq_match_id' => $faq_match_id,
        'asked_date' => gmdate('c'),
        'is_clustered' => false,
        'is_resolved' => false
    ];

    $result = chatbot_supabase_request('chatbot_gap_questions', 'POST', $data);

    if (!$result['success']) {
        error_log('[Chatbot Supabase] Failed to log gap question: ' . ($result['error'] ?? 'Unknown'));
    }

    return $result['success'];
}

/**
 * Log gap question using PDO (includes embedding for clustering)
 */
function chatbot_supabase_log_gap_question_pdo($pdo, $question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id = null) {
    try {
        // Generate embedding for the question
        $embedding = null;
        if (function_exists('chatbot_vector_generate_embedding')) {
            $embedding = chatbot_vector_generate_embedding($question_text);
        }

        if ($embedding) {
            // Insert with embedding
            $embedding_str = chatbot_vector_to_pg_format($embedding);
            $stmt = $pdo->prepare('
                INSERT INTO chatbot_gap_questions
                (question_text, session_id, user_id, page_id, faq_confidence, faq_match_id, asked_date, is_clustered, is_resolved, embedding)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), false, false, ?::vector)
            ');
            $stmt->execute([
                $question_text,
                $session_id,
                (int)$user_id,
                (int)$page_id,
                $faq_confidence,
                $faq_match_id,
                $embedding_str
            ]);
        } else {
            // Insert without embedding
            $stmt = $pdo->prepare('
                INSERT INTO chatbot_gap_questions
                (question_text, session_id, user_id, page_id, faq_confidence, faq_match_id, asked_date, is_clustered, is_resolved)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), false, false)
            ');
            $stmt->execute([
                $question_text,
                $session_id,
                (int)$user_id,
                (int)$page_id,
                $faq_confidence,
                $faq_match_id
            ]);
        }

        return true;
    } catch (PDOException $e) {
        error_log('[Chatbot Supabase] PDO failed to log gap question: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get gap questions (unresolved)
 */
function chatbot_supabase_get_gap_questions($limit = 100, $include_resolved = false) {
    $query_params = [
        'order' => 'asked_date.desc',
        'limit' => $limit
    ];

    if (!$include_resolved) {
        $query_params['is_resolved'] = 'eq.false';
    }

    $result = chatbot_supabase_request('chatbot_gap_questions', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

/**
 * Get gap questions count
 */
function chatbot_supabase_get_gap_questions_count($include_resolved = false) {
    $base_url = chatbot_supabase_get_url();
    $anon_key = chatbot_supabase_get_anon_key();
    $url = $base_url . '/chatbot_gap_questions?select=id';

    if (!$include_resolved) {
        $url .= '&is_resolved=eq.false';
    }

    $headers = [
        'apikey: ' . $anon_key,
        'Authorization: Bearer ' . $anon_key,
        'Prefer: count=exact'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers_str = substr($response, 0, $header_size);
    curl_close($ch);

    if (preg_match('/content-range: \d+-\d+\/(\d+)/i', $headers_str, $matches)) {
        return (int)$matches[1];
    }

    return 0;
}

/**
 * Mark gap question as resolved
 */
function chatbot_supabase_resolve_gap_question($id) {
    $query_params = ['id' => 'eq.' . $id];
    $data = ['is_resolved' => true];

    $result = chatbot_supabase_request('chatbot_gap_questions', 'PATCH', $data, $query_params);

    return $result['success'];
}

/**
 * Delete gap question
 */
function chatbot_supabase_delete_gap_question($id) {
    $query_params = ['id' => 'eq.' . $id];

    $result = chatbot_supabase_request('chatbot_gap_questions', 'DELETE', null, $query_params);

    return $result['success'];
}

/**
 * Get gap questions by confidence range (for analysis)
 */
function chatbot_supabase_get_gap_questions_by_confidence($min_confidence = 0, $max_confidence = 0.6) {
    $query_params = [
        'faq_confidence' => 'gte.' . $min_confidence,
        'and' => '(faq_confidence.lte.' . $max_confidence . ')',
        'is_resolved' => 'eq.false',
        'order' => 'asked_date.desc',
        'limit' => 100
    ];

    $result = chatbot_supabase_request('chatbot_gap_questions', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

// =============================================================================
// GAP QUESTION VECTOR CLUSTERING
// =============================================================================

/**
 * Find similar gap questions using vector similarity
 *
 * @param int $question_id The question ID to find similar questions for
 * @param float $threshold Minimum similarity (0-1), default 0.70
 * @param int $limit Maximum results
 * @return array Array of similar questions with similarity scores
 */
function chatbot_supabase_find_similar_gap_questions($question_id, $threshold = 0.70, $limit = 10) {
    $pdo = function_exists('chatbot_vector_get_pg_connection') ? chatbot_vector_get_pg_connection() : null;

    if (!$pdo) {
        return [];
    }

    try {
        // Find similar questions based on embedding
        $stmt = $pdo->prepare('
            SELECT
                g2.id,
                g2.question_text,
                g2.faq_confidence,
                g2.asked_date,
                g2.is_resolved,
                1 - (g1.embedding <=> g2.embedding) AS similarity
            FROM chatbot_gap_questions g1
            CROSS JOIN chatbot_gap_questions g2
            WHERE g1.id = ?
            AND g2.id != g1.id
            AND g1.embedding IS NOT NULL
            AND g2.embedding IS NOT NULL
            AND 1 - (g1.embedding <=> g2.embedding) >= ?
            ORDER BY similarity DESC
            LIMIT ?
        ');
        $stmt->execute([$question_id, $threshold, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('[Chatbot Supabase] Find similar gaps failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Cluster gap questions by semantic similarity
 * Returns groups of similar questions without manual clustering
 *
 * @param float $threshold Similarity threshold for grouping (0-1)
 * @param int $min_cluster_size Minimum questions per cluster
 * @return array Array of clusters, each containing similar questions
 */
function chatbot_supabase_cluster_gap_questions($threshold = 0.70, $min_cluster_size = 2) {
    $pdo = function_exists('chatbot_vector_get_pg_connection') ? chatbot_vector_get_pg_connection() : null;

    if (!$pdo) {
        return ['success' => false, 'error' => 'No database connection'];
    }

    try {
        // Get all unresolved gap questions with embeddings
        $stmt = $pdo->query('
            SELECT id, question_text, faq_confidence, asked_date, embedding
            FROM chatbot_gap_questions
            WHERE is_resolved = false
            AND embedding IS NOT NULL
            ORDER BY asked_date DESC
            LIMIT 200
        ');
        $questions = $stmt->fetchAll();

        if (count($questions) < $min_cluster_size) {
            return ['success' => true, 'clusters' => [], 'message' => 'Not enough questions to cluster'];
        }

        // Simple agglomerative clustering using SQL
        // Find pairs of similar questions
        $stmt = $pdo->prepare('
            SELECT
                g1.id as id1,
                g1.question_text as q1,
                g2.id as id2,
                g2.question_text as q2,
                1 - (g1.embedding <=> g2.embedding) AS similarity
            FROM chatbot_gap_questions g1
            INNER JOIN chatbot_gap_questions g2 ON g1.id < g2.id
            WHERE g1.is_resolved = false
            AND g2.is_resolved = false
            AND g1.embedding IS NOT NULL
            AND g2.embedding IS NOT NULL
            AND 1 - (g1.embedding <=> g2.embedding) >= ?
            ORDER BY similarity DESC
            LIMIT 500
        ');
        $stmt->execute([$threshold]);
        $pairs = $stmt->fetchAll();

        // Build clusters using union-find approach
        $clusters = [];
        $question_to_cluster = [];

        foreach ($pairs as $pair) {
            $id1 = $pair['id1'];
            $id2 = $pair['id2'];

            $cluster1 = $question_to_cluster[$id1] ?? null;
            $cluster2 = $question_to_cluster[$id2] ?? null;

            if ($cluster1 === null && $cluster2 === null) {
                // Both new - create new cluster
                $new_cluster_id = count($clusters);
                $clusters[$new_cluster_id] = [
                    'questions' => [
                        ['id' => $id1, 'text' => $pair['q1']],
                        ['id' => $id2, 'text' => $pair['q2']]
                    ],
                    'similarity' => $pair['similarity']
                ];
                $question_to_cluster[$id1] = $new_cluster_id;
                $question_to_cluster[$id2] = $new_cluster_id;
            } elseif ($cluster1 !== null && $cluster2 === null) {
                // Add id2 to cluster1
                $clusters[$cluster1]['questions'][] = ['id' => $id2, 'text' => $pair['q2']];
                $question_to_cluster[$id2] = $cluster1;
            } elseif ($cluster1 === null && $cluster2 !== null) {
                // Add id1 to cluster2
                $clusters[$cluster2]['questions'][] = ['id' => $id1, 'text' => $pair['q1']];
                $question_to_cluster[$id1] = $cluster2;
            } elseif ($cluster1 !== $cluster2) {
                // Merge clusters
                foreach ($clusters[$cluster2]['questions'] as $q) {
                    $clusters[$cluster1]['questions'][] = $q;
                    $question_to_cluster[$q['id']] = $cluster1;
                }
                unset($clusters[$cluster2]);
            }
        }

        // Filter by minimum cluster size and deduplicate
        $result_clusters = [];
        foreach ($clusters as $cluster) {
            // Deduplicate questions in cluster
            $seen_ids = [];
            $unique_questions = [];
            foreach ($cluster['questions'] as $q) {
                if (!isset($seen_ids[$q['id']])) {
                    $seen_ids[$q['id']] = true;
                    $unique_questions[] = $q;
                }
            }

            if (count($unique_questions) >= $min_cluster_size) {
                $result_clusters[] = [
                    'questions' => $unique_questions,
                    'count' => count($unique_questions),
                    'representative' => $unique_questions[0]['text']
                ];
            }
        }

        // Sort by cluster size (largest first)
        usort($result_clusters, fn($a, $b) => $b['count'] - $a['count']);

        return [
            'success' => true,
            'clusters' => $result_clusters,
            'total_clustered' => array_sum(array_column($result_clusters, 'count')),
            'cluster_count' => count($result_clusters)
        ];

    } catch (PDOException $e) {
        error_log('[Chatbot Supabase] Cluster gap questions failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get gap question clusters for admin display
 * Groups similar questions and suggests potential FAQ topics
 *
 * @return array Clusters with suggested topic summaries
 */
function chatbot_supabase_get_gap_clusters_for_admin() {
    $result = chatbot_supabase_cluster_gap_questions(0.70, 2);

    if (!$result['success'] || empty($result['clusters'])) {
        return $result;
    }

    // Enhance clusters with stats
    $enhanced = [];
    foreach ($result['clusters'] as $index => $cluster) {
        // Extract common words for topic suggestion
        $all_text = implode(' ', array_column($cluster['questions'], 'text'));
        $words = array_count_values(str_word_count(strtolower($all_text), 1));
        arsort($words);

        // Filter common stopwords
        $stopwords = ['what', 'how', 'why', 'when', 'where', 'is', 'are', 'the', 'a', 'an', 'to', 'for', 'of', 'and', 'in', 'on', 'do', 'does', 'can', 'i', 'my', 'you', 'your'];
        $keywords = array_diff_key($words, array_flip($stopwords));
        $top_keywords = array_slice(array_keys($keywords), 0, 5);

        $enhanced[] = [
            'cluster_id' => $index + 1,
            'question_count' => $cluster['count'],
            'questions' => $cluster['questions'],
            'representative_question' => $cluster['representative'],
            'suggested_keywords' => $top_keywords,
            'suggested_topic' => ucfirst(implode(' ', array_slice($top_keywords, 0, 3)))
        ];
    }

    return [
        'success' => true,
        'clusters' => $enhanced,
        'total_questions' => $result['total_clustered'],
        'cluster_count' => $result['cluster_count']
    ];
}

/**
 * Generate embeddings for existing gap questions that don't have them
 *
 * @param int $batch_size How many to process at once
 * @return array Results of the migration
 */
function chatbot_supabase_migrate_gap_embeddings($batch_size = 20) {
    $pdo = function_exists('chatbot_vector_get_pg_connection') ? chatbot_vector_get_pg_connection() : null;

    if (!$pdo) {
        return ['success' => false, 'error' => 'No database connection'];
    }

    try {
        // Get questions without embeddings
        $stmt = $pdo->prepare('
            SELECT id, question_text
            FROM chatbot_gap_questions
            WHERE embedding IS NULL
            AND question_text IS NOT NULL
            AND LENGTH(question_text) > 3
            LIMIT ?
        ');
        $stmt->execute([$batch_size]);
        $questions = $stmt->fetchAll();

        if (empty($questions)) {
            return ['success' => true, 'migrated' => 0, 'message' => 'All questions already have embeddings'];
        }

        $migrated = 0;
        $errors = 0;

        foreach ($questions as $q) {
            // Generate embedding
            $embedding = null;
            if (function_exists('chatbot_vector_generate_embedding')) {
                $embedding = chatbot_vector_generate_embedding($q['question_text']);
            }

            if ($embedding) {
                $embedding_str = chatbot_vector_to_pg_format($embedding);
                $stmt = $pdo->prepare('UPDATE chatbot_gap_questions SET embedding = ?::vector WHERE id = ?');
                $stmt->execute([$embedding_str, $q['id']]);
                $migrated++;
            } else {
                $errors++;
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }

        return [
            'success' => true,
            'migrated' => $migrated,
            'errors' => $errors,
            'remaining' => count($questions) - $migrated
        ];

    } catch (PDOException $e) {
        error_log('[Chatbot Supabase] Migrate gap embeddings failed: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// =============================================================================
// FAQ USAGE TRACKING
// =============================================================================

/**
 * Track FAQ usage in Supabase
 */
function chatbot_supabase_track_faq_usage($faq_id, $confidence_score) {
    if (empty($faq_id)) {
        return false;
    }

    // First, check if record exists
    $query_params = ['faq_id' => 'eq.' . $faq_id];
    $result = chatbot_supabase_request('chatbot_faq_usage', 'GET', null, $query_params);

    if ($result['success'] && !empty($result['data'])) {
        // Update existing record
        $existing = $result['data'][0];
        $new_hit_count = intval($existing['hit_count']) + 1;

        // Calculate new average confidence (running average)
        $old_avg = floatval($existing['avg_confidence'] ?? 0);
        $old_count = intval($existing['hit_count']);
        $new_avg = (($old_avg * $old_count) + floatval($confidence_score)) / $new_hit_count;

        $data = [
            'hit_count' => $new_hit_count,
            'last_asked' => gmdate('c'),
            'avg_confidence' => $new_avg
        ];

        $result = chatbot_supabase_request('chatbot_faq_usage', 'PATCH', $data, $query_params);
    } else {
        // Insert new record
        $data = [
            'faq_id' => $faq_id,
            'hit_count' => 1,
            'last_asked' => gmdate('c'),
            'avg_confidence' => floatval($confidence_score)
        ];

        $result = chatbot_supabase_request('chatbot_faq_usage', 'POST', $data);
    }

    return $result['success'] ?? false;
}

/**
 * Get FAQ usage stats
 */
function chatbot_supabase_get_faq_usage($limit = 100) {
    $query_params = [
        'order' => 'hit_count.desc',
        'limit' => $limit
    ];

    $result = chatbot_supabase_request('chatbot_faq_usage', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

// =============================================================================
// ASSISTANTS MANAGEMENT
// =============================================================================

/**
 * Get all assistants from Supabase
 */
function chatbot_supabase_get_assistants() {
    $query_params = [
        'order' => 'id.asc'
    ];

    $result = chatbot_supabase_request('chatbot_assistants', 'GET', null, $query_params);

    if ($result['success']) {
        return $result['data'];
    }

    return [];
}

/**
 * Get assistant by ID
 */
function chatbot_supabase_get_assistant($id) {
    $query_params = ['id' => 'eq.' . $id];
    $result = chatbot_supabase_request('chatbot_assistants', 'GET', null, $query_params);

    if ($result['success'] && !empty($result['data'])) {
        return $result['data'][0];
    }

    return null;
}

/**
 * Get assistant by assistant_id (OpenAI ID)
 */
function chatbot_supabase_get_assistant_by_assistant_id($assistant_id) {
    $query_params = ['assistant_id' => 'eq.' . $assistant_id];
    $result = chatbot_supabase_request('chatbot_assistants', 'GET', null, $query_params);

    if ($result['success'] && !empty($result['data'])) {
        return $result['data'][0];
    }

    return null;
}

/**
 * Add new assistant
 */
function chatbot_supabase_add_assistant($data) {
    $result = chatbot_supabase_request('chatbot_assistants', 'POST', $data);

    if ($result['success'] && !empty($result['data'])) {
        return ['success' => true, 'id' => $result['data'][0]['id']];
    }

    return ['success' => false, 'message' => $result['error'] ?? 'Failed to add assistant'];
}

/**
 * Update assistant
 */
function chatbot_supabase_update_assistant($id, $data) {
    $query_params = ['id' => 'eq.' . $id];
    $result = chatbot_supabase_request('chatbot_assistants', 'PATCH', $data, $query_params);

    return ['success' => $result['success'], 'message' => $result['error'] ?? ''];
}

/**
 * Delete assistant
 */
function chatbot_supabase_delete_assistant($id) {
    $query_params = ['id' => 'eq.' . $id];
    $result = chatbot_supabase_request('chatbot_assistants', 'DELETE', null, $query_params);

    return $result['success'];
}

/**
 * Get assistant count
 */
function chatbot_supabase_get_assistant_count() {
    $result = chatbot_supabase_get_assistants();
    return count($result);
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

// Note: chatbot_supabase_test_connection() is now defined in chatbot-settings-supabase.php

/**
 * Get all table counts for diagnostics
 */
function chatbot_supabase_get_diagnostics() {
    $tables = ['chatbot_faqs', 'chatbot_conversations', 'chatbot_interactions', 'chatbot_gap_questions', 'chatbot_faq_usage', 'chatbot_assistants'];
    $diagnostics = [];
    $anon_key = chatbot_supabase_get_anon_key();

    foreach ($tables as $table) {
        $base_url = chatbot_supabase_get_url();
        $url = $base_url . '/' . $table . '?select=id';

        $headers = [
            'apikey: ' . $anon_key,
            'Authorization: Bearer ' . $anon_key,
            'Prefer: count=exact'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers_str = substr($response, 0, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            if (preg_match('/content-range: \d+-\d+\/(\d+)/i', $headers_str, $matches)) {
                $diagnostics[$table] = (int)$matches[1];
            } else {
                $diagnostics[$table] = 0;
            }
        } else {
            $diagnostics[$table] = 'Error: ' . $http_code;
        }
    }

    return $diagnostics;
}
