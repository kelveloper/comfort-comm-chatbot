<?php
/**
 * Chatbot Vector Search - Migration Script
 *
 * Converts JSON FAQ data to vector embeddings using OpenAI's
 * text-embedding-3-small model and populates the PostgreSQL database.
 *
 * @package comfort-comm-chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Include the schema file
require_once plugin_dir_path(__FILE__) . 'chatbot-vector-schema.php';

/**
 * Generate embedding using the user's configured AI platform
 * Ver 2.5.2: Uses steven_bot_get_api_config() for platform-aware API calls
 *
 * @param string $text Text to generate embedding for
 * @param string $model Model to use (ignored - uses config model)
 * @return array|null Embedding vector (1536 dimensions) or null on failure
 */
function chatbot_vector_generate_embedding($text, $model = null) {
    // Get API config based on user's platform choice
    $api_config = steven_bot_get_api_config();
    $platform = $api_config['platform'];
    $api_key = $api_config['api_key'];

    // Check if platform supports embeddings
    if (!steven_bot_platform_supports_embeddings()) {
        error_log('[Chatbot Vector] Platform ' . $platform . ' does not support embeddings');
        return null;
    }

    if (empty($api_key)) {
        error_log('[Chatbot Vector] No API key configured for platform: ' . $platform);
        return null;
    }

    // Clean and prepare text
    $text = trim($text);
    if (empty($text)) {
        return null;
    }

    // Truncate if too long
    if (strlen($text) > 30000) {
        $text = substr($text, 0, 30000);
    }

    if ($platform === 'Gemini') {
        return chatbot_vector_generate_embedding_gemini($text, $api_key, $api_config['embedding_model']);
    } elseif ($platform === 'Mistral') {
        return chatbot_vector_generate_embedding_mistral($text, $api_key, $api_config['embedding_model'], $api_config['embedding_url']);
    } else {
        // OpenAI, Azure OpenAI - use OpenAI format
        return chatbot_vector_generate_embedding_openai($text, $api_key, $api_config['embedding_model'], $api_config['embedding_url'], $platform);
    }
}

/**
 * Generate embedding using Google Gemini API
 */
function chatbot_vector_generate_embedding_gemini($text, $api_key, $model = 'text-embedding-004') {
    // Note: Gemini embedding model produces 768 dimensions by default
    // We'll need to update schema or pad to 1536 dimensions
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':embedContent?key=' . $api_key;

    $body = json_encode([
        'model' => 'models/' . $model,
        'content' => [
            'parts' => [
                ['text' => $text]
            ]
        ]
    ]);

    $response = wp_remote_post($url, [
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => $body
    ]);

    if (is_wp_error($response)) {
        error_log('[Chatbot Vector] Gemini API request failed: ' . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
        error_log('[Chatbot Vector] Gemini API error: ' . $error_msg);
        return null;
    }

    if (!isset($data['embedding']['values'])) {
        error_log('[Chatbot Vector] No embedding in Gemini response');
        return null;
    }

    $embedding = $data['embedding']['values'];

    // Gemini text-embedding-004 produces 768 dimensions
    // Pad to 1536 dimensions for consistency with our schema
    // (or we could resize the schema, but padding is simpler)
    while (count($embedding) < 1536) {
        $embedding[] = 0.0;
    }

    return $embedding;
}

/**
 * Generate embedding using OpenAI API (and Azure OpenAI)
 * Ver 2.5.2: Accepts URL and platform parameters for Azure support
 */
function chatbot_vector_generate_embedding_openai($text, $api_key, $model = 'text-embedding-3-small', $url = null, $platform = 'OpenAI') {
    if (empty($url)) {
        $url = 'https://api.openai.com/v1/embeddings';
    }

    $body = json_encode([
        'model' => $model,
        'input' => $text,
        'encoding_format' => 'float'
    ]);

    $headers = ['Content-Type' => 'application/json'];

    if ($platform === 'Azure OpenAI') {
        $headers['api-key'] = $api_key;
    } else {
        $headers['Authorization'] = 'Bearer ' . $api_key;
    }

    $response = wp_remote_post($url, [
        'timeout' => 30,
        'headers' => $headers,
        'body' => $body
    ]);

    if (is_wp_error($response)) {
        error_log('[Chatbot Vector] ' . $platform . ' API request failed: ' . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
        error_log('[Chatbot Vector] ' . $platform . ' API error: ' . $error_msg);
        return null;
    }

    if (!isset($data['data'][0]['embedding'])) {
        error_log('[Chatbot Vector] No embedding in response');
        return null;
    }

    return $data['data'][0]['embedding'];
}

/**
 * Generate embedding using Mistral API
 * Ver 2.5.2: Added Mistral embedding support
 */
function chatbot_vector_generate_embedding_mistral($text, $api_key, $model = 'mistral-embed', $url = null) {
    if (empty($url)) {
        $url = 'https://api.mistral.ai/v1/embeddings';
    }

    $body = json_encode([
        'model' => $model,
        'input' => [$text]
    ]);

    $response = wp_remote_post($url, [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => $body
    ]);

    if (is_wp_error($response)) {
        error_log('[Chatbot Vector] Mistral API request failed: ' . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code !== 200) {
        $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
        error_log('[Chatbot Vector] Mistral API error: ' . $error_msg);
        return null;
    }

    if (!isset($data['data'][0]['embedding'])) {
        error_log('[Chatbot Vector] No embedding in Mistral response');
        return null;
    }

    $embedding = $data['data'][0]['embedding'];

    // Mistral embeddings are 1024 dimensions, pad to 1536 for consistency
    while (count($embedding) < 1536) {
        $embedding[] = 0.0;
    }

    return $embedding;
}

/**
 * Convert embedding array to PostgreSQL vector format
 *
 * @param array $embedding Array of floats
 * @return string PostgreSQL vector string format
 */
function chatbot_vector_to_pg_format($embedding) {
    if (!is_array($embedding)) {
        return null;
    }

    return '[' . implode(',', $embedding) . ']';
}

/**
 * Insert or update a single FAQ with embeddings
 *
 * @param array $faq FAQ data array
 * @param bool $generate_embeddings Whether to generate new embeddings
 * @return array Result with success status
 */
function chatbot_vector_upsert_faq($faq, $generate_embeddings = true) {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }

    $faq_id = $faq['id'] ?? uniqid();
    $question = $faq['question'] ?? '';
    $answer = $faq['answer'] ?? '';
    $category = $faq['category'] ?? '';
    $keywords = $faq['keywords'] ?? '';

    if (empty($question) || empty($answer)) {
        return ['success' => false, 'message' => 'Question and answer are required'];
    }

    $question_embedding = null;
    $answer_embedding = null;
    $combined_embedding = null;

    if ($generate_embeddings) {
        // Generate embedding for question
        $question_embedding = chatbot_vector_generate_embedding($question);

        // Generate embedding for answer
        $answer_embedding = chatbot_vector_generate_embedding($answer);

        // Generate combined embedding (question + answer for better semantic matching)
        $combined_text = $question . ' ' . $answer;
        $combined_embedding = chatbot_vector_generate_embedding($combined_text);

        if (!$combined_embedding) {
            return ['success' => false, 'message' => 'Failed to generate embeddings'];
        }
    }

    try {
        // Check if FAQ exists
        $stmt = $pdo->prepare('SELECT id FROM chatbot_faqs WHERE faq_id = ?');
        $stmt->execute([$faq_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing FAQ
            if ($generate_embeddings) {
                $stmt = $pdo->prepare('
                    UPDATE chatbot_faqs SET
                        question = ?,
                        answer = ?,
                        category = ?,
                        keywords = ?,
                        question_embedding = ?::vector,
                        answer_embedding = ?::vector,
                        combined_embedding = ?::vector,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE faq_id = ?
                ');
                $stmt->execute([
                    $question,
                    $answer,
                    $category,
                    $keywords,
                    chatbot_vector_to_pg_format($question_embedding),
                    chatbot_vector_to_pg_format($answer_embedding),
                    chatbot_vector_to_pg_format($combined_embedding),
                    $faq_id
                ]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE chatbot_faqs SET
                        question = ?,
                        answer = ?,
                        category = ?,
                        keywords = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE faq_id = ?
                ');
                $stmt->execute([$question, $answer, $category, $keywords, $faq_id]);
            }
        } else {
            // Insert new FAQ
            if ($generate_embeddings) {
                $stmt = $pdo->prepare('
                    INSERT INTO chatbot_faqs
                    (faq_id, question, answer, category, keywords, question_embedding, answer_embedding, combined_embedding)
                    VALUES (?, ?, ?, ?, ?, ?::vector, ?::vector, ?::vector)
                ');
                $stmt->execute([
                    $faq_id,
                    $question,
                    $answer,
                    $category,
                    $keywords,
                    chatbot_vector_to_pg_format($question_embedding),
                    chatbot_vector_to_pg_format($answer_embedding),
                    chatbot_vector_to_pg_format($combined_embedding)
                ]);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO chatbot_faqs (faq_id, question, answer, category, keywords)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$faq_id, $question, $answer, $category, $keywords]);
            }
        }

        return ['success' => true, 'faq_id' => $faq_id];

    } catch (PDOException $e) {
        error_log('[Chatbot Vector] Upsert failed: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Check if embeddings need to be regenerated due to platform change
 * Ver 2.5.2
 *
 * @return array ['needs_regen' => bool, 'current_platform' => string, 'embedding_platform' => string]
 */
function steven_bot_check_embedding_platform_mismatch() {
    $api_config = steven_bot_get_api_config();
    $current_platform = $api_config['platform'];
    $embedding_platform = get_option('chatbot_embedding_platform', '');

    // If no embedding platform saved, embeddings haven't been generated yet
    if (empty($embedding_platform)) {
        return [
            'needs_regen' => false,
            'current_platform' => $current_platform,
            'embedding_platform' => 'none',
            'message' => 'No embeddings generated yet'
        ];
    }

    // Check if current platform supports embeddings
    if (!steven_bot_platform_supports_embeddings()) {
        return [
            'needs_regen' => false,
            'current_platform' => $current_platform,
            'embedding_platform' => $embedding_platform,
            'message' => $current_platform . ' does not support embeddings'
        ];
    }

    // Check if platform has changed
    $needs_regen = ($current_platform !== $embedding_platform);

    return [
        'needs_regen' => $needs_regen,
        'current_platform' => $current_platform,
        'embedding_platform' => $embedding_platform,
        'message' => $needs_regen
            ? "Platform changed from {$embedding_platform} to {$current_platform}. Embeddings need regeneration."
            : "Embeddings are using {$current_platform}"
    ];
}

/**
 * Migrate all FAQs - regenerate embeddings using Supabase REST API
 * Ver 2.5.2: Uses steven_bot_get_api_config() for platform-aware API calls
 *
 * @param bool $clear_existing Whether to clear existing embeddings first
 * @return array Migration result with stats
 */
function chatbot_vector_migrate_all_faqs($clear_existing = false) {
    $config = chatbot_vector_get_supabase_config();

    if (!$config || empty($config['anon_key'])) {
        return [
            'success' => false,
            'message' => 'Supabase not configured. Go to Setup tab.'
        ];
    }

    // Get API config using helper function
    $api_config = steven_bot_get_api_config();
    $ai_platform = $api_config['platform'];

    // Check if platform supports embeddings
    if (!steven_bot_platform_supports_embeddings()) {
        return [
            'success' => false,
            'message' => $ai_platform . ' does not support embeddings. Please use OpenAI, Gemini, Azure OpenAI, or Mistral.'
        ];
    }

    // Load existing FAQs from Supabase
    $faqs = chatbot_faq_load();

    if (empty($faqs)) {
        return [
            'success' => false,
            'message' => 'No FAQs found in database'
        ];
    }

    $total = count($faqs);
    $success_count = 0;
    $error_count = 0;
    $errors = [];

    error_log("[Chatbot Vector] Starting migration of {$total} FAQs using {$ai_platform}...");

    foreach ($faqs as $index => $faq) {
        $faq_id = $faq['id'] ?? $faq['faq_id'] ?? null;
        $question = $faq['question'] ?? '';
        $answer = $faq['answer'] ?? '';

        if (empty($faq_id) || empty($question) || empty($answer)) {
            $error_count++;
            continue;
        }

        // Generate new embeddings
        $question_embedding = chatbot_vector_generate_embedding($question);
        $combined_embedding = chatbot_vector_generate_embedding($question . ' ' . $answer);

        if (!$question_embedding || !$combined_embedding) {
            $error_count++;
            $errors[] = [
                'faq_id' => $faq_id,
                'error' => 'Failed to generate embedding'
            ];
            error_log("[Chatbot Vector] Failed embedding for FAQ {$faq_id}");
            continue;
        }

        // Update FAQ with new embeddings via REST API
        $url = $config['url'] . '/rest/v1/chatbot_faqs?faq_id=eq.' . urlencode($faq_id);

        $update_data = [
            'question_embedding' => $question_embedding,
            'combined_embedding' => $combined_embedding,
            'updated_at' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        $response = wp_remote_request($url, [
            'method' => 'PATCH',
            'headers' => [
                'apikey' => $config['anon_key'],
                'Authorization' => 'Bearer ' . $config['anon_key'],
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($update_data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $error_count++;
            $errors[] = [
                'faq_id' => $faq_id,
                'error' => $response->get_error_message()
            ];
            continue;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status >= 400) {
            $error_count++;
            $body = wp_remote_retrieve_body($response);
            $errors[] = [
                'faq_id' => $faq_id,
                'error' => $body
            ];
            continue;
        }

        $success_count++;
        error_log("[Chatbot Vector] Migrated FAQ " . ($index + 1) . "/{$total}");

        // Small delay to avoid rate limiting
        usleep(200000); // 200ms delay
    }

    $message = "Migration complete: {$success_count}/{$total} FAQs.";
    if ($error_count > 0) {
        $message .= " {$error_count} errors.";
    }

    error_log("[Chatbot Vector] " . $message);

    // Save which platform was used for embeddings
    if ($success_count > 0) {
        update_option('chatbot_embedding_platform', $ai_platform);
        update_option('chatbot_embedding_timestamp', current_time('mysql'));
    }

    return [
        'success' => $error_count === 0,
        'message' => $message,
        'total' => $total,
        'migrated' => $success_count,
        'errors' => $error_count,
        'error_details' => $errors
    ];
}

/**
 * Add a single FAQ with embedding (legacy function - use chatbot_faq_add instead)
 *
 * @param string $question The FAQ question
 * @param string $answer The FAQ answer
 * @param string $category The category
 * @return array Result with success status
 * @deprecated Use chatbot_faq_add() from chatbot-vector-faq-crud.php instead
 */
function chatbot_vector_add_faq($question, $answer, $category = '') {
    // This function is deprecated - use chatbot_faq_add() instead
    // Keeping for backwards compatibility only
    if (function_exists('chatbot_faq_add')) {
        return chatbot_faq_add($question, $answer, $category);
    }

    // Fallback to PDO method if CRUD functions not loaded
    $faq = [
        'id' => uniqid('cc'),
        'question' => $question,
        'answer' => $answer,
        'category' => $category,
    ];

    return chatbot_vector_upsert_faq($faq, true);
}

/**
 * Update embeddings for a single FAQ
 *
 * @param string $faq_id The FAQ ID to update
 * @return array Result with success status
 */
function chatbot_vector_update_embedding($faq_id) {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }

    try {
        // Get current FAQ data
        $stmt = $pdo->prepare('SELECT * FROM chatbot_faqs WHERE faq_id = ?');
        $stmt->execute([$faq_id]);
        $faq = $stmt->fetch();

        if (!$faq) {
            return ['success' => false, 'message' => 'FAQ not found'];
        }

        // Regenerate embeddings
        $question_embedding = chatbot_vector_generate_embedding($faq['question']);
        $answer_embedding = chatbot_vector_generate_embedding($faq['answer']);
        $combined_embedding = chatbot_vector_generate_embedding($faq['question'] . ' ' . $faq['answer']);

        if (!$combined_embedding) {
            return ['success' => false, 'message' => 'Failed to generate embeddings'];
        }

        // Update embeddings
        $stmt = $pdo->prepare('
            UPDATE chatbot_faqs SET
                question_embedding = ?::vector,
                answer_embedding = ?::vector,
                combined_embedding = ?::vector,
                updated_at = CURRENT_TIMESTAMP
            WHERE faq_id = ?
        ');
        $stmt->execute([
            chatbot_vector_to_pg_format($question_embedding),
            chatbot_vector_to_pg_format($answer_embedding),
            chatbot_vector_to_pg_format($combined_embedding),
            $faq_id
        ]);

        return ['success' => true, 'message' => 'Embedding updated successfully'];

    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * AJAX handler for running migration
 */
function chatbot_vector_ajax_migrate() {
    check_ajax_referer('chatbot_vector_migrate', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $clear_existing = isset($_POST['clear_existing']) && $_POST['clear_existing'] === '1';

    // This can take a while, increase time limit
    set_time_limit(300); // 5 minutes

    $result = chatbot_vector_migrate_all_faqs($clear_existing);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_chatbot_vector_migrate', 'chatbot_vector_ajax_migrate');

/**
 * AJAX handler for checking migration status
 */
function chatbot_vector_ajax_status() {
    check_ajax_referer('chatbot_vector_status', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $stats = chatbot_vector_get_stats();
    wp_send_json_success($stats);
}
add_action('wp_ajax_chatbot_vector_status', 'chatbot_vector_ajax_status');

/**
 * Regenerate all embeddings for FAQs and gap questions
 * Ver 2.5.2: Complete regeneration when platform changes
 *
 * @param string $type 'all', 'faqs', or 'gap_questions'
 * @param int $batch_size Items per batch
 * @param int $offset Starting offset
 * @return array Progress info
 */
function steven_bot_regenerate_embeddings($type = 'all', $batch_size = 10, $offset = 0) {
    $api_config = steven_bot_get_api_config();
    $platform = $api_config['platform'];

    if (!steven_bot_platform_supports_embeddings()) {
        return [
            'success' => false,
            'message' => $platform . ' does not support embeddings',
            'complete' => true
        ];
    }

    $results = [
        'success' => true,
        'platform' => $platform,
        'faqs_processed' => 0,
        'faqs_total' => 0,
        'gaps_processed' => 0,
        'gaps_total' => 0,
        'errors' => [],
        'complete' => false
    ];

    // Ver 2.5.2: Use Supabase REST API instead of PDO
    if (!function_exists('chatbot_supabase_request')) {
        $results['success'] = false;
        $results['message'] = 'Supabase functions not available';
        $results['complete'] = true;
        return $results;
    }

    try {
        // Get total counts first - NOTE: chatbot_supabase_request returns ['success' => bool, 'data' => array]
        $faq_count_response = chatbot_supabase_request('chatbot_faqs', 'GET', null, ['select' => 'faq_id']);
        $faq_count_data = isset($faq_count_response['data']) ? $faq_count_response['data'] : $faq_count_response;
        $results['faqs_total'] = is_array($faq_count_data) ? count($faq_count_data) : 0;

        $gap_count_response = chatbot_supabase_request('chatbot_gap_questions', 'GET', null, [
            'select' => 'id',
            'question_text' => 'not.is.null'
        ]);
        $gap_count_data = isset($gap_count_response['data']) ? $gap_count_response['data'] : $gap_count_response;
        $results['gaps_total'] = is_array($gap_count_data) ? count($gap_count_data) : 0;

        $total_items = $results['faqs_total'] + $results['gaps_total'];

        error_log("[Chatbot Regen] Totals: {$results['faqs_total']} FAQs, {$results['gaps_total']} gaps. Offset: {$offset}, Batch: {$batch_size}");

        // Ver 2.5.2: Process FAQs first, then gap questions (sequential offsets)
        $faq_offset = $offset;
        $gap_offset = max(0, $offset - $results['faqs_total']);
        $items_to_process = $batch_size;

        // Process FAQs if we haven't finished them yet
        if (($type === 'all' || $type === 'faqs') && $offset < $results['faqs_total']) {
            $faqs_response = chatbot_supabase_request('chatbot_faqs', 'GET', null, [
                'select' => 'faq_id,question,answer',
                'order' => 'faq_id.asc',
                'limit' => $items_to_process,
                'offset' => $faq_offset
            ]);
            $faqs_data = isset($faqs_response['data']) ? $faqs_response['data'] : $faqs_response;

            if (is_array($faqs_data)) {
                foreach ($faqs_data as $faq) {
                    $question_embedding = chatbot_vector_generate_embedding($faq['question']);
                    $combined_embedding = chatbot_vector_generate_embedding($faq['question'] . ' ' . $faq['answer']);

                    if ($question_embedding && $combined_embedding) {
                        chatbot_supabase_request('chatbot_faqs', 'PATCH', [
                            'question_embedding' => chatbot_vector_to_pg_format($question_embedding),
                            'combined_embedding' => chatbot_vector_to_pg_format($combined_embedding)
                        ], ['faq_id' => 'eq.' . $faq['faq_id']]);
                        $results['faqs_processed']++;
                    } else {
                        $results['errors'][] = 'Failed: FAQ ' . $faq['faq_id'];
                    }
                    $items_to_process--;
                    usleep(150000);
                }
            }
        }

        // Process gap questions if we've finished FAQs or if doing gaps only
        if (($type === 'all' || $type === 'gap_questions') && $items_to_process > 0 && $offset >= $results['faqs_total'] - $batch_size) {
            $gaps_response = chatbot_supabase_request('chatbot_gap_questions', 'GET', null, [
                'select' => 'id,question_text',
                'question_text' => 'not.is.null',
                'order' => 'id.asc',
                'limit' => $items_to_process,
                'offset' => $gap_offset
            ]);
            $gaps_data = isset($gaps_response['data']) ? $gaps_response['data'] : $gaps_response;

            if (is_array($gaps_data)) {
                foreach ($gaps_data as $gap) {
                    if (empty($gap['question_text']) || strlen($gap['question_text']) <= 3) {
                        continue;
                    }

                    $embedding = chatbot_vector_generate_embedding($gap['question_text']);

                    if ($embedding) {
                        chatbot_supabase_request('chatbot_gap_questions', 'PATCH', [
                            'embedding' => chatbot_vector_to_pg_format($embedding)
                        ], ['id' => 'eq.' . $gap['id']]);
                        $results['gaps_processed']++;
                    } else {
                        $results['errors'][] = 'Failed: Gap ' . $gap['id'];
                    }
                    usleep(150000);
                }
            }
        }

        error_log("[Chatbot Regen] Processed: {$results['faqs_processed']} FAQs, {$results['gaps_processed']} gaps");

        // Check if complete
        $total_processed = $results['faqs_processed'] + $results['gaps_processed'];
        $next_offset = $offset + $batch_size;
        $results['complete'] = ($next_offset >= $total_items) || ($total_processed === 0 && $offset > 0);

        // If complete, save the new embedding platform and clear the platform change prompt
        if ($results['complete'] && $results['success']) {
            update_option('chatbot_embedding_platform', $platform);
            update_option('chatbot_embedding_timestamp', current_time('mysql'));
            // Clear the platform change transient since embeddings are now regenerated
            delete_transient('steven_bot_platform_changed');
        }

        $results['next_offset'] = $next_offset;
        $results['progress_percent'] = $total_items > 0
            ? min(100, round(($next_offset / $total_items) * 100, 1))
            : 100;

    } catch (Exception $e) {
        $results['success'] = false;
        $results['message'] = $e->getMessage();
        $results['complete'] = true;
    }

    return $results;
}

/**
 * AJAX handler for regenerating embeddings with progress
 * Ver 2.5.2
 */
function steven_bot_ajax_regenerate_embeddings() {
    check_ajax_referer('steven_bot_regenerate_embeddings', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
    $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 10;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    set_time_limit(300); // 5 minutes max

    $result = steven_bot_regenerate_embeddings($type, $batch_size, $offset);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_steven_bot_regenerate_embeddings', 'steven_bot_ajax_regenerate_embeddings');

/**
 * AJAX handler for checking embedding platform status
 * Ver 2.5.2
 */
function steven_bot_ajax_check_embedding_status() {
    check_ajax_referer('steven_bot_embedding_status', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $status = steven_bot_check_embedding_platform_mismatch();

    // Add counts
    $pdo = function_exists('chatbot_vector_get_pg_connection') ? chatbot_vector_get_pg_connection() : null;
    if ($pdo) {
        try {
            $stmt = $pdo->query('SELECT COUNT(*) FROM chatbot_faqs');
            $status['faq_count'] = intval($stmt->fetchColumn());

            $stmt = $pdo->query('SELECT COUNT(*) FROM chatbot_gap_questions WHERE question_text IS NOT NULL');
            $status['gap_count'] = intval($stmt->fetchColumn());

            $status['total_items'] = $status['faq_count'] + $status['gap_count'];
        } catch (PDOException $e) {
            $status['faq_count'] = 0;
            $status['gap_count'] = 0;
        }
    }

    wp_send_json_success($status);
}
add_action('wp_ajax_steven_bot_check_embedding_status', 'steven_bot_ajax_check_embedding_status');
