<?php
/**
 * Kognetiks Chatbot - Gap Question Analysis - Ver 2.4.2
 *
 * This file contains AI-powered analysis of gap questions
 * to identify patterns and suggest FAQ additions.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Run AI clustering analysis on gap questions
 * This function is called by the weekly cron job
 */
function chatbot_run_gap_analysis() {
    global $wpdb;

    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';
    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    // Get unresolved, unclustered gap questions from the last 30 days
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $gap_table
         WHERE is_clustered = 0
         AND is_resolved = 0
         AND asked_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY asked_date DESC
         LIMIT 100"
    ), ARRAY_A);

    if (empty($questions)) {
        error_log('[Chatbot] No new gap questions to analyze');
        return false;
    }

    error_log('[Chatbot] Running gap analysis on ' . count($questions) . ' questions');

    // Group questions by similarity using AI
    $clusters = chatbot_cluster_questions_with_ai($questions);

    if (empty($clusters)) {
        error_log('[Chatbot] No clusters generated from gap questions');
        return false;
    }

    // Save clusters to database
    foreach ($clusters as $cluster) {
        // Calculate priority score based on question count and recency
        $priority = chatbot_calculate_cluster_priority($cluster);

        // Insert or update cluster
        $existing_cluster = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $cluster_table WHERE cluster_name = %s",
            $cluster['name']
        ), ARRAY_A);

        if ($existing_cluster) {
            // Update existing cluster
            $wpdb->update(
                $cluster_table,
                array(
                    'question_count' => $cluster['count'],
                    'sample_questions' => json_encode($cluster['sample_questions']),
                    'suggested_faq' => json_encode($cluster['suggested_faq']),
                    'priority_score' => $priority,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing_cluster['id']),
                array('%d', '%s', '%s', '%f', '%s'),
                array('%d')
            );

            $cluster_id = $existing_cluster['id'];
        } else {
            // Insert new cluster
            $action_type = $cluster['action_type'] ?? 'create';
            $insert_data = array(
                'cluster_name' => $cluster['name'],
                'cluster_description' => $cluster['description'],
                'question_count' => $cluster['count'],
                'sample_questions' => json_encode($cluster['sample_questions']),
                'action_type' => $action_type,
                'priority_score' => $priority,
                'status' => 'new',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );

            $insert_format = array('%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s');

            // Add action-specific fields
            if ($action_type === 'improve') {
                $existing_faq_id = $cluster['existing_faq_id'] ?? '';

                // Load FAQ details for display
                $faq_details = chatbot_get_faq_by_id($existing_faq_id);

                $insert_data['existing_faq_id'] = $existing_faq_id;
                $insert_data['suggested_keywords'] = json_encode($cluster['suggested_keywords'] ?? []);
                $insert_data['suggested_faq'] = json_encode($faq_details); // Store existing FAQ details for display
                $insert_format[] = '%s';
                $insert_format[] = '%s';
                $insert_format[] = '%s';
            } else {
                $insert_data['suggested_faq'] = json_encode($cluster['suggested_faq'] ?? []);
                $insert_data['existing_faq_id'] = '';
                $insert_data['suggested_keywords'] = '';
                $insert_format[] = '%s';
                $insert_format[] = '%s';
                $insert_format[] = '%s';
            }

            $wpdb->insert($cluster_table, $insert_data, $insert_format);
            $cluster_id = $wpdb->insert_id;
        }

        // Mark questions as clustered
        if ($cluster_id && !empty($cluster['question_ids'])) {
            $question_ids = implode(',', array_map('intval', $cluster['question_ids']));
            $wpdb->query("UPDATE $gap_table SET is_clustered = 1, cluster_id = $cluster_id WHERE id IN ($question_ids)");
        }
    }

    error_log('[Chatbot] Gap analysis complete. Created/updated ' . count($clusters) . ' clusters');
    return true;
}

/**
 * Analyze recent conversations with AI
 */
function chatbot_analyze_recent_conversations($conversations) {
    global $wpdb;
    $conv_table = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // For each conversation, get response and calculate confidence
    $analyzed = [];
    foreach ($conversations as $conv) {
        $question = $conv['message_text'];

        // Get chatbot response (next message in same session)
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT message_text FROM $conv_table
             WHERE user_type = 'Chatbot'
             AND session_id = %s
             AND interaction_time > %s
             ORDER BY interaction_time ASC
             LIMIT 1",
            $conv['session_id'],
            $conv['interaction_time']
        ), ARRAY_A);

        // Calculate FAQ confidence
        $faq_result = chatbot_find_best_faq_match($question);

        $analyzed[] = [
            'id' => $conv['id'],
            'question' => $question,
            'answer' => $response ? $response['message_text'] : 'No response',
            'confidence' => $faq_result['confidence'],
            'matched_faq_id' => $faq_result['faq_id'] ?? null,
            'time' => $conv['interaction_time']
        ];
    }

    // Send to AI for suggestions
    $ai_suggestions = chatbot_get_ai_suggestions_from_conversations($analyzed);

    return [
        'conversations' => $analyzed,
        'suggestions' => $ai_suggestions
    ];
}

/**
 * Get AI suggestions based on analyzed conversations
 */
function chatbot_get_ai_suggestions_from_conversations($conversations) {
    $api_key_encrypted = get_option('chatbot_gemini_api_key', '');

    if (empty($api_key_encrypted)) {
        error_log('[Chatbot] Gemini API key not set. Please add it in Settings > API/Model > Gemini');
        return [];
    }

    // Decrypt the API key
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_encrypted, 'chatbot_gemini_api_key');

    error_log('🔑 Gemini API key check:');
    error_log('🔑 Encrypted key length: ' . strlen($api_key_encrypted));
    error_log('🔑 Decrypted key length: ' . strlen($api_key));
    error_log('🔑 Decrypted key preview: ' . substr($api_key, 0, 10) . '...');

    if (empty($api_key)) {
        error_log('[Chatbot] Failed to decrypt Gemini API key');
        return [];
    }

    // Load existing FAQs (lightweight - ID, question, keywords only)
    $existing_faqs = chatbot_load_existing_faqs();
    $faq_summary = "Existing FAQs (" . count($existing_faqs) . " total):\n";
    foreach ($existing_faqs as $faq) {
        $faq_summary .= $faq['id'] . ': ' . $faq['question'] . ' [' . substr($faq['keywords'] ?? '', 0, 50) . "]\n";
    }

    // Format conversations for AI
    $conv_text = '';
    foreach ($conversations as $idx => $conv) {
        $conf_percent = round($conv['confidence'] * 100);
        $conv_text .= ($idx + 1) . ". Question: " . $conv['question'] . "\n";
        $conv_text .= "   Answer Given: " . substr($conv['answer'], 0, 200) . "...\n";
        $conv_text .= "   Confidence Score: {$conf_percent}%\n";
        if (!empty($conv['matched_faq_id'])) {
            $conv_text .= "   Matched FAQ: " . $conv['matched_faq_id'] . "\n";
        }
        $conv_text .= "\n";
    }

    $prompt = "You are analyzing recent chatbot conversations to improve the FAQ knowledge base.

EXISTING FAQ DATABASE:
$faq_summary

RECENT CONVERSATIONS:
$conv_text

For each conversation, analyze:
1. If confidence < 70%: Determine if you should IMPROVE an existing FAQ or CREATE a new one
2. If confidence >= 70%: Suggest if keywords could still be improved

Respond with a JSON array of suggestions:
[
  {
    \"conversation_number\": 1,
    \"action_type\": \"improve\" or \"create\",
    \"existing_faq_id\": \"cc002\" (only if improve),
    \"suggested_keywords\": [\"keyword1\", \"keyword2\"] (only if improve),
    \"suggested_faq\": {
      \"question\": \"...\",
      \"answer\": \"...\",
      \"keywords\": \"...\"
    } (only if create),
    \"reasoning\": \"Why this suggestion will help\"
  }
]

Only suggest improvements for conversations where the FAQ system could perform better.";

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 8192
            ]
        ]),
        'timeout' => 60
    ]);

    if (is_wp_error($response)) {
        error_log('[Chatbot] Gemini API error: ' . $response->get_error_message());
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
        error_log('[Chatbot] Unexpected Gemini API response');
        error_log('[Chatbot] Full API response: ' . print_r($body, true));
        return [];
    }

    $ai_response = $body['candidates'][0]['content']['parts'][0]['text'];
    $ai_response = preg_replace('/```json\n?/', '', $ai_response);
    $ai_response = preg_replace('/```\n?/', '', $ai_response);
    $ai_response = trim($ai_response);

    $suggestions = json_decode($ai_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('[Chatbot] Failed to parse AI suggestions: ' . json_last_error_msg());
        return [];
    }

    // Convert to cluster format
    $clusters = [];
    foreach ($suggestions as $idx => $sugg) {
        $conv = $conversations[$sugg['conversation_number'] - 1] ?? null;
        if (!$conv) continue;

        $cluster = [
            'cluster_name' => 'Conversation ' . $sugg['conversation_number'] . ': ' . substr($conv['question'], 0, 50),
            'cluster_description' => $sugg['reasoning'] ?? '',
            'question_count' => 1,
            'sample_questions' => [$conv['question']],
            'action_type' => $sugg['action_type'] ?? 'create',
            'priority_score' => (1 - $conv['confidence']) * 100
        ];

        if ($cluster['action_type'] === 'improve') {
            $existing_faq_id = $sugg['existing_faq_id'] ?? '';
            $faq_details = chatbot_get_faq_by_id($existing_faq_id);

            $cluster['existing_faq_id'] = $existing_faq_id;
            $cluster['suggested_keywords'] = $sugg['suggested_keywords'] ?? [];
            $cluster['suggested_faq'] = $faq_details;
        } else {
            $cluster['suggested_faq'] = $sugg['suggested_faq'] ?? [];
        }

        $clusters[] = $cluster;
    }

    return $clusters;
}

/**
 * Find best FAQ match for a question
 */
function chatbot_find_best_faq_match($question) {
    $faqs = chatbot_load_existing_faqs();
    $best_match = ['confidence' => 0, 'faq_id' => null, 'answer' => ''];

    $question_lower = strtolower($question);
    $question_words = array_filter(explode(' ', $question_lower));

    foreach ($faqs as $faq) {
        $keywords = strtolower($faq['keywords'] ?? '');
        $faq_question = strtolower($faq['question'] ?? '');

        $keyword_list = array_filter(explode(' ', str_replace(',', ' ', $keywords)));

        $matches = 0;
        foreach ($question_words as $word) {
            if (strlen($word) < 3) continue;

            if (strpos($faq_question, $word) !== false) {
                $matches += 2;
            }

            foreach ($keyword_list as $keyword) {
                if (strlen($keyword) < 3) continue;
                if (strpos($word, $keyword) !== false || strpos($keyword, $word) !== false) {
                    $matches++;
                }
            }
        }

        $confidence = min(1.0, $matches / max(3, count($question_words)));

        if ($confidence > $best_match['confidence']) {
            $best_match = [
                'confidence' => $confidence,
                'faq_id' => $faq['id'] ?? null,
                'answer' => $faq['answer'] ?? ''
            ];
        }
    }

    return $best_match;
}

/**
 * Get FAQ details by ID from JSON file
 */
function chatbot_get_faq_by_id($faq_id) {
    $faqs = chatbot_load_existing_faqs();

    foreach ($faqs as $faq) {
        if (isset($faq['id']) && $faq['id'] === $faq_id) {
            return [
                'id' => $faq['id'],
                'question' => $faq['question'] ?? '',
                'answer' => $faq['answer'] ?? '',
                'keywords' => $faq['keywords'] ?? ''
            ];
        }
    }

    return [
        'id' => $faq_id,
        'question' => 'FAQ not found',
        'answer' => '',
        'keywords' => ''
    ];
}

/**
 * Load existing FAQ database from JSON file
 */
function chatbot_load_existing_faqs() {
    $faq_path = chatbot_faq_get_data_path();

    if (!file_exists($faq_path)) {
        error_log('[Chatbot] FAQ file not found at: ' . $faq_path);
        return [];
    }

    $json_content = file_get_contents($faq_path);
    $faqs = json_decode($json_content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('[Chatbot] Failed to parse FAQ JSON: ' . json_last_error_msg());
        return [];
    }

    return $faqs;
}

/**
 * Format FAQs for AI context (concise format)
 */
function chatbot_format_faqs_for_ai($faqs) {
    if (empty($faqs)) {
        return "No existing FAQs found.";
    }

    $formatted = '';
    foreach ($faqs as $faq) {
        $id = $faq['id'] ?? 'unknown';
        $question = $faq['question'] ?? '';
        $keywords = $faq['keywords'] ?? '';
        $formatted .= "ID: $id | Q: $question | Keywords: $keywords\n";
    }

    return $formatted;
}

/**
 * Use Gemini AI to cluster similar questions and generate FAQ suggestions
 */
function chatbot_cluster_questions_with_ai($questions) {
    // Get Gemini API key
    $api_key_encrypted = get_option('chatbot_gemini_api_key', '');

    if (empty($api_key_encrypted)) {
        error_log('[Chatbot] Gemini API key not set. Cannot run gap analysis.');
        return [];
    }

    // Decrypt the API key
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_encrypted, 'chatbot_gemini_api_key');

    if (empty($api_key)) {
        error_log('[Chatbot] Failed to decrypt Gemini API key.');
        return [];
    }

    // Load existing FAQ database
    $existing_faqs = chatbot_load_existing_faqs();
    $faqs_context = chatbot_format_faqs_for_ai($existing_faqs);

    // Prepare questions list for AI
    $questions_text = '';
    foreach ($questions as $idx => $q) {
        $questions_text .= ($idx + 1) . ". " . $q['question_text'] . "\n";
    }

    // Build AI prompt
    $prompt = "You are analyzing customer questions that were not answered well by an FAQ system (confidence < 60%). Your job is to:

1. Review the EXISTING FAQ database
2. Group similar questions into clusters (themes)
3. For each cluster, determine if you should:
   - IMPROVE an existing FAQ by suggesting additional keywords
   - CREATE a new FAQ if no relevant FAQ exists

EXISTING FAQ DATABASE:
$faqs_context

CUSTOMER QUESTIONS (not answered well):
$questions_text

Please analyze these questions and respond with a JSON array of clusters. Each cluster should have:
- name: A short name for the cluster (e.g., 'Router Compatibility')
- description: Brief description of what customers are asking about
- question_numbers: Array of question numbers that belong to this cluster (e.g., [1, 5, 8])
- action_type: Either \"improve\" or \"create\"
- existing_faq_id: (only if action_type is \"improve\") The ID of the FAQ to improve
- suggested_keywords: (only if action_type is \"improve\") Additional keywords to add
- suggested_faq: (only if action_type is \"create\") Object with 'question', 'answer', and 'keywords' for a new FAQ entry

Important:
- Only create clusters that have at least 2 similar questions
- PREFER improving existing FAQs over creating new ones when possible
- Be specific with keyword suggestions (include synonyms, common misspellings, variations)
- Respond ONLY with valid JSON, no markdown formatting

Example response format:
[
  {
    \"name\": \"Closing Time Questions\",
    \"description\": \"Customers asking about closing time\",
    \"question_numbers\": [1, 3],
    \"action_type\": \"improve\",
    \"existing_faq_id\": \"cc002\",
    \"suggested_keywords\": [\"close\", \"closing\", \"what time\", \"end time\", \"closing time\"]
  },
  {
    \"name\": \"Router Compatibility\",
    \"description\": \"Questions about using own router\",
    \"question_numbers\": [2, 5],
    \"action_type\": \"create\",
    \"suggested_faq\": {
      \"question\": \"Can I use my own router?\",
      \"answer\": \"Yes, you can use your own router with our service...\",
      \"keywords\": \"router own personal equipment modem wifi\"
    }
  }
]";

    // Call Gemini API
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;

    $body = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 8192,
            'topP' => 0.95,
            'topK' => 40
        ]
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => $body,
        'timeout' => 60
    ]);

    if (is_wp_error($response)) {
        error_log('[Chatbot] Gemini API error: ' . $response->get_error_message());
        return [];
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
        error_log('[Chatbot] Unexpected Gemini API response format');
        return [];
    }

    $ai_response = $response_body['candidates'][0]['content']['parts'][0]['text'];

    // Parse JSON response (strip markdown code blocks if present)
    $ai_response = preg_replace('/```json\n?/', '', $ai_response);
    $ai_response = preg_replace('/```\n?/', '', $ai_response);
    $ai_response = trim($ai_response);

    $clusters_data = json_decode($ai_response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('[Chatbot] Failed to parse AI response as JSON: ' . json_last_error_msg());
        error_log('[Chatbot] AI Response: ' . $ai_response);
        return [];
    }

    // Convert to internal format
    $clusters = [];
    foreach ($clusters_data as $cluster_data) {
        $question_ids = [];
        $sample_questions = [];

        // Map question numbers back to question IDs
        foreach ($cluster_data['question_numbers'] as $num) {
            $idx = $num - 1;
            if (isset($questions[$idx])) {
                $question_ids[] = $questions[$idx]['id'];
                $sample_questions[] = $questions[$idx]['question_text'];
            }
        }

        if (count($question_ids) >= 2) {
            $cluster = [
                'name' => $cluster_data['name'],
                'description' => $cluster_data['description'],
                'count' => count($question_ids),
                'question_ids' => $question_ids,
                'sample_questions' => array_slice($sample_questions, 0, 5), // Keep up to 5 samples
                'action_type' => $cluster_data['action_type'] ?? 'create'
            ];

            // Add action-specific data
            if ($cluster['action_type'] === 'improve') {
                $cluster['existing_faq_id'] = $cluster_data['existing_faq_id'] ?? '';
                $cluster['suggested_keywords'] = $cluster_data['suggested_keywords'] ?? [];
            } else {
                $cluster['suggested_faq'] = $cluster_data['suggested_faq'] ?? [
                    'question' => '',
                    'answer' => '',
                    'keywords' => ''
                ];
            }

            $clusters[] = $cluster;
        }
    }

    return $clusters;
}

/**
 * Calculate priority score for a cluster
 * Higher score = more important to address
 */
function chatbot_calculate_cluster_priority($cluster) {
    $question_count = intval($cluster['count']);

    // Base score on question frequency
    $score = $question_count * 10;

    // Boost for high-frequency clusters
    if ($question_count >= 10) {
        $score *= 1.5;
    } else if ($question_count >= 5) {
        $score *= 1.2;
    }

    return round($score, 2);
}

/**
 * Get gap analysis dashboard data
 */
function chatbot_get_gap_analysis_data($days = 7) {
    global $wpdb;

    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';
    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    // Check if tables exist, create them if not
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $gap_table)) !== $gap_table) {
        create_chatbot_gap_questions_table();
        create_chatbot_gap_clusters_table();
        create_chatbot_faq_usage_table();
    }

    // Get total gap questions in the period
    $total_gaps = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $gap_table
         WHERE asked_date >= DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
    ));

    // Get unresolved gap questions
    $unresolved_gaps = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $gap_table
         WHERE is_resolved = 0
         AND asked_date >= DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
    ));

    // Get active clusters (status = 'new' or 'reviewed')
    $active_clusters = $wpdb->get_results(
        "SELECT * FROM $cluster_table
         WHERE status IN ('new', 'reviewed')
         ORDER BY priority_score DESC
         LIMIT 10",
        ARRAY_A
    );

    // Get top gap questions (not yet clustered)
    $top_gaps = $wpdb->get_results($wpdb->prepare(
        "SELECT question_text, COUNT(*) as count
         FROM $gap_table
         WHERE is_clustered = 0
         AND is_resolved = 0
         AND asked_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
         GROUP BY question_text
         ORDER BY count DESC
         LIMIT 10",
        $days
    ), ARRAY_A);

    return [
        'total_gaps' => intval($total_gaps),
        'unresolved_gaps' => intval($unresolved_gaps),
        'active_clusters' => $active_clusters,
        'top_individual_gaps' => $top_gaps
    ];
}

/**
 * Mark a cluster as resolved (FAQ created)
 */
function chatbot_resolve_gap_cluster($cluster_id) {
    global $wpdb;

    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';
    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';

    // Update cluster status
    $result = $wpdb->update(
        $cluster_table,
        array('status' => 'faq_created', 'updated_at' => current_time('mysql')),
        array('id' => intval($cluster_id)),
        array('%s', '%s'),
        array('%d')
    );

    if ($result === false) {
        return false;
    }

    // Mark all questions in this cluster as resolved
    $wpdb->update(
        $gap_table,
        array('is_resolved' => 1),
        array('cluster_id' => intval($cluster_id)),
        array('%d'),
        array('%d')
    );

    return true;
}

/**
 * Dismiss a cluster
 */
function chatbot_dismiss_gap_cluster($cluster_id) {
    global $wpdb;

    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    $result = $wpdb->update(
        $cluster_table,
        array('status' => 'dismissed', 'updated_at' => current_time('mysql')),
        array('id' => intval($cluster_id)),
        array('%s', '%s'),
        array('%d')
    );

    return $result !== false;
}

// Hook gap analysis to cron event
add_action('chatbot_gap_analysis_event', 'chatbot_run_gap_analysis');

// AJAX: Resolve cluster
function chatbot_ajax_resolve_cluster() {
    check_ajax_referer('chatbot_gap_analysis', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $cluster_id = intval($_POST['cluster_id'] ?? 0);

    if ($cluster_id <= 0) {
        wp_send_json_error('Invalid cluster ID');
    }

    $result = chatbot_resolve_gap_cluster($cluster_id);

    if ($result) {
        wp_send_json_success(['message' => 'Cluster resolved successfully']);
    } else {
        wp_send_json_error('Failed to resolve cluster');
    }
}
add_action('wp_ajax_chatbot_resolve_cluster', 'chatbot_ajax_resolve_cluster');

// AJAX: Dismiss cluster
function chatbot_ajax_dismiss_cluster() {
    check_ajax_referer('chatbot_gap_analysis', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $cluster_id = intval($_POST['cluster_id'] ?? 0);

    if ($cluster_id <= 0) {
        wp_send_json_error('Invalid cluster ID');
    }

    $result = chatbot_dismiss_gap_cluster($cluster_id);

    if ($result) {
        wp_send_json_success(['message' => 'Cluster dismissed successfully']);
    } else {
        wp_send_json_error('Failed to dismiss cluster');
    }
}
add_action('wp_ajax_chatbot_dismiss_cluster', 'chatbot_ajax_dismiss_cluster');

// AJAX: Run gap analysis manually
function chatbot_ajax_run_gap_analysis_manual() {
    check_ajax_referer('chatbot_gap_analysis', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $result = chatbot_run_gap_analysis();

    if ($result) {
        // Count how many clusters were created/updated
        global $wpdb;
        $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';
        $cluster_count = $wpdb->get_var("SELECT COUNT(*) FROM $cluster_table WHERE status IN ('new', 'reviewed')");

        wp_send_json_success([
            'message' => 'Gap analysis completed successfully',
            'clusters' => intval($cluster_count)
        ]);
    } else {
        wp_send_json_error('No gap questions to analyze or analysis failed');
    }
}
add_action('wp_ajax_chatbot_run_gap_analysis_manual', 'chatbot_ajax_run_gap_analysis_manual');

// AJAX: Generate mock data for testing
function chatbot_ajax_generate_mock_gap_data() {
    check_ajax_referer('chatbot_gap_analysis', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    global $wpdb;

    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';
    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    // Create mock gap questions
    $mock_questions = [
        ["Can I use my own router?", 0.25], ["Do you provide equipment?", 0.30],
        ["What routers work with your service?", 0.22], ["Can I bring my own modem?", 0.28],
        ["Do you offer senior discounts?", 0.15], ["Any discounts for students?", 0.18],
        ["How long does installation take?", 0.38], ["Installation time?", 0.35],
        ["Do I need a contract?", 0.28], ["Can I cancel anytime?", 0.32],
        ["Internet and phone bundle?", 0.42], ["Bundle deals available?", 0.45],
    ];

    $inserted = 0;
    foreach ($mock_questions as $index => $q) {
        $days_ago = rand(0, 6);
        $wpdb->insert($gap_table, [
            'question_text' => $q[0],
            'session_id' => 'mock_' . $index,
            'user_id' => rand(1, 5),
            'page_id' => rand(1, 3),
            'faq_confidence' => $q[1],
            'faq_match_id' => null,
            'asked_date' => date('Y-m-d H:i:s', strtotime("-{$days_ago} days")),
            'is_clustered' => 0,
            'is_resolved' => 0
        ], ['%s', '%s', '%d', '%d', '%f', '%s', '%s', '%d', '%d']);
        if ($wpdb->insert_id) $inserted++;
    }

    // Create mock clusters
    $mock_clusters = [
        [
            'name' => 'Router & Equipment',
            'description' => 'Questions about using own equipment',
            'count' => 4,
            'samples' => ["Can I use my own router?", "Do you provide equipment?"],
            'faq' => ['question' => 'Can I use my own router?', 'answer' => 'Yes! You can use your own equipment. We recommend DOCSIS 3.1 modems for best speeds.']
        ],
        [
            'name' => 'Senior Discounts',
            'description' => 'Questions about senior pricing',
            'count' => 2,
            'samples' => ["Do you offer senior discounts?", "Any discounts for students?"],
            'faq' => ['question' => 'Do you offer senior discounts?', 'answer' => 'Yes! We offer up to 15% off for seniors 65+ and students with valid ID.']
        ]
    ];

    $cluster_inserted = 0;
    foreach ($mock_clusters as $c) {
        $wpdb->insert($cluster_table, [
            'cluster_name' => $c['name'],
            'cluster_description' => $c['description'],
            'question_count' => $c['count'],
            'sample_questions' => json_encode($c['samples']),
            'suggested_faq' => json_encode($c['faq']),
            'priority_score' => $c['count'] * 20,
            'status' => 'new',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ], ['%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s']);
        if ($wpdb->insert_id) $cluster_inserted++;
    }

    wp_send_json_success([
        'message' => 'Mock data generated',
        'questions' => $inserted,
        'clusters' => $cluster_inserted
    ]);
}
add_action('wp_ajax_chatbot_generate_mock_gap_data', 'chatbot_ajax_generate_mock_gap_data');

// AJAX: Save analysis frequency - Ver 2.4.2
function chatbot_ajax_save_analysis_frequency() {
    check_ajax_referer('chatbot_gap_analysis', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : '';

    if (!in_array($frequency, ['weekly', 'monthly', 'yearly'])) {
        wp_send_json_error('Invalid frequency value');
    }

    update_option('chatbot_gap_analysis_frequency', $frequency);

    error_log("💾 Gap analysis frequency updated to: $frequency");

    wp_send_json_success([
        'message' => 'Frequency updated',
        'frequency' => $frequency
    ]);
}
add_action('wp_ajax_chatbot_save_analysis_frequency', 'chatbot_ajax_save_analysis_frequency');

// AJAX: Analyze Last 4 Conversations - Ver 2.4.2
function chatbot_ajax_analyze_last_10_gaps() {
    check_ajax_referer('chatbot_gap_analysis', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    global $wpdb;
    $conv_table = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$conv_table'");
    error_log("🔍 Conversation table exists: " . ($table_exists ? 'YES' : 'NO'));
    error_log("🔍 Table name: $conv_table");

    if (!$table_exists) {
        wp_send_json_error('Conversation logging table does not exist. Please enable conversation logging in Analytics settings first.');
    }

    // Count total rows
    $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM $conv_table");
    error_log("🔍 Total rows in conversation log: $total_rows");

    // Count visitor messages
    $visitor_count = $wpdb->get_var("SELECT COUNT(*) FROM $conv_table WHERE user_type = 'Visitor'");
    error_log("🔍 Visitor messages: $visitor_count");

    // Get last 4 visitor questions
    $conversations = $wpdb->get_results("
        SELECT id, message_text, session_id, interaction_time
        FROM $conv_table
        WHERE user_type = 'Visitor'
        ORDER BY interaction_time DESC
        LIMIT 4
    ", ARRAY_A);

    error_log("🔍 Found " . count($conversations) . " conversations");

    if (empty($conversations)) {
        wp_send_json_error('No recent conversations found. Conversation logging may not be enabled. Please check Analytics > Conversation Logging settings.');
    }

    error_log("📊 Analyzing last " . count($conversations) . " conversations with AI");

    // Analyze each conversation
    $analyzed_data = chatbot_analyze_recent_conversations($conversations);

    if (empty($analyzed_data['suggestions'])) {
        wp_send_json_error('AI analysis returned no suggestions');
    }

    $clusters = $analyzed_data['suggestions'];

    // Save clusters to database
    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';
    $saved_count = 0;

    foreach ($clusters as $cluster) {
        $result = $wpdb->insert($cluster_table, [
            'cluster_name' => $cluster['cluster_name'],
            'cluster_description' => $cluster['cluster_description'],
            'question_count' => $cluster['question_count'],
            'sample_questions' => json_encode($cluster['sample_questions']),
            'suggested_faq' => json_encode($cluster['suggested_faq']),
            'priority_score' => $cluster['priority_score'],
            'status' => 'new',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ], ['%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s']);

        if ($result) {
            $saved_count++;

            // Mark questions as clustered
            $cluster_id = $wpdb->insert_id;
            foreach ($cluster['question_ids'] as $q_id) {
                $wpdb->update($gap_table,
                    ['is_clustered' => 1, 'cluster_id' => $cluster_id],
                    ['id' => $q_id],
                    ['%d', '%d'],
                    ['%d']
                );
            }
        }
    }

    error_log("✓ Saved $saved_count clusters from AI analysis");

    // Track API usage
    $api_usage = get_option('chatbot_gap_analysis_api_usage', array(
        'total_calls' => 0,
        'this_week' => 0,
        'this_month' => 0,
        'last_reset_week' => date('W'),
        'last_reset_month' => date('m'),
        'last_analysis_date' => null
    ));

    $api_usage['total_calls']++;
    $api_usage['this_week']++;
    $api_usage['this_month']++;
    $api_usage['last_analysis_date'] = current_time('mysql');

    update_option('chatbot_gap_analysis_api_usage', $api_usage);

    error_log("📊 API Usage tracked: Total={$api_usage['total_calls']}, Week={$api_usage['this_week']}, Month={$api_usage['this_month']}");

    wp_send_json_success([
        'message' => 'Successfully analyzed ' . count($analyzed_data['conversations']) . ' conversations',
        'conversations' => $analyzed_data['conversations'],
        'suggestions' => $saved_count
    ]);
}
add_action('wp_ajax_chatbot_analyze_last_10_gaps', 'chatbot_ajax_analyze_last_10_gaps');
