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
    return defined('CHATBOT_SUPABASE_ANON_KEY') && !empty(CHATBOT_SUPABASE_ANON_KEY);
}

/**
 * Get Supabase REST API base URL
 */
function chatbot_supabase_get_url() {
    if (defined('CHATBOT_PG_HOST')) {
        // Extract project ref from host (db.xxxxx.supabase.co -> xxxxx)
        $host = CHATBOT_PG_HOST;
        if (preg_match('/db\.([^.]+)\.supabase\.co/', $host, $matches)) {
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
    if (!$base_url || !chatbot_supabase_is_configured()) {
        return ['success' => false, 'error' => 'Supabase not configured'];
    }

    $url = $base_url . '/' . $endpoint;

    // Add query parameters
    if (!empty($query_params)) {
        $url .= '?' . http_build_query($query_params);
    }

    $headers = [
        'apikey: ' . CHATBOT_SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . CHATBOT_SUPABASE_ANON_KEY,
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
    $url = $base_url . '/chatbot_conversations?' . http_build_query($query_params);

    $headers = [
        'apikey: ' . CHATBOT_SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . CHATBOT_SUPABASE_ANON_KEY,
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
 */
function chatbot_supabase_log_gap_question($question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id = null) {
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
    $url = $base_url . '/chatbot_gap_questions?select=id';

    if (!$include_resolved) {
        $url .= '&is_resolved=eq.false';
    }

    $headers = [
        'apikey: ' . CHATBOT_SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . CHATBOT_SUPABASE_ANON_KEY,
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
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Test Supabase connection
 */
function chatbot_supabase_test_connection() {
    $result = chatbot_supabase_request('chatbot_faqs', 'GET', null, ['limit' => 1]);
    return $result['success'];
}

/**
 * Get all table counts for diagnostics
 */
function chatbot_supabase_get_diagnostics() {
    $tables = ['chatbot_faqs', 'chatbot_conversations', 'chatbot_interactions', 'chatbot_gap_questions', 'chatbot_faq_usage'];
    $diagnostics = [];

    foreach ($tables as $table) {
        $base_url = chatbot_supabase_get_url();
        $url = $base_url . '/' . $table . '?select=id';

        $headers = [
            'apikey: ' . CHATBOT_SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . CHATBOT_SUPABASE_ANON_KEY,
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
