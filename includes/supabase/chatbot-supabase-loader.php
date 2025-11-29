<?php
/**
 * Supabase Module Loader
 *
 * Loads all Supabase-related functionality and provides
 * a bridge between WordPress and Supabase for database operations.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Load Supabase database wrapper
require_once plugin_dir_path(__FILE__) . 'chatbot-supabase-db.php';

/**
 * Check if we should use Supabase for all database operations
 * Can be controlled via wp-config.php: define('CHATBOT_USE_SUPABASE_DB', true);
 */
function chatbot_should_use_supabase_db() {
    // Check if Supabase is configured
    if (!chatbot_supabase_is_configured()) {
        return false;
    }

    // Check if explicitly enabled (default: true if Supabase is configured)
    if (defined('CHATBOT_USE_SUPABASE_DB')) {
        return CHATBOT_USE_SUPABASE_DB;
    }

    // Default to true if Supabase is configured
    return true;
}

/**
 * Wrapper: Log conversation message
 * Uses Supabase if configured, falls back to WordPress $wpdb
 */
function chatbot_db_log_conversation($session_id, $user_id, $page_id, $user_type, $thread_id, $assistant_id, $assistant_name, $message, $sentiment_score = null) {
    if (chatbot_should_use_supabase_db()) {
        return chatbot_supabase_log_conversation($session_id, $user_id, $page_id, $user_type, $thread_id, $assistant_id, $assistant_name, $message, $sentiment_score);
    }

    // Fall back to WordPress (original function)
    if (function_exists('append_message_to_conversation_log')) {
        append_message_to_conversation_log($session_id, $user_id, $page_id, $user_type, $thread_id, $assistant_id, $assistant_name, $message);
        return true;
    }

    return false;
}

/**
 * Wrapper: Update interaction count
 * Uses Supabase if configured, falls back to WordPress $wpdb
 */
function chatbot_db_update_interaction() {
    if (chatbot_should_use_supabase_db()) {
        $result = chatbot_supabase_update_interaction_count();
        return $result['success'] ?? false;
    }

    // Fall back to WordPress (original function)
    if (function_exists('update_interaction_tracking')) {
        update_interaction_tracking();
        return true;
    }

    return false;
}

/**
 * Wrapper: Log gap question
 * Uses Supabase if configured, falls back to WordPress $wpdb
 */
function chatbot_db_log_gap_question($question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id = null) {
    if (chatbot_should_use_supabase_db()) {
        return chatbot_supabase_log_gap_question($question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id);
    }

    // Fall back to WordPress (original function)
    if (function_exists('chatbot_log_gap_question')) {
        chatbot_log_gap_question($question_text, $session_id, $user_id, $page_id, $faq_confidence, $faq_match_id);
        return true;
    }

    return false;
}

/**
 * Wrapper: Get gap questions
 */
function chatbot_db_get_gap_questions($limit = 100, $include_resolved = false) {
    if (chatbot_should_use_supabase_db()) {
        return chatbot_supabase_get_gap_questions($limit, $include_resolved);
    }

    // Fall back to WordPress
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_gap_questions';

    $where = $include_resolved ? '' : 'WHERE is_resolved = 0';
    $results = $wpdb->get_results(
        "SELECT * FROM $table_name $where ORDER BY asked_date DESC LIMIT $limit",
        ARRAY_A
    );

    return $results ?: [];
}

/**
 * Wrapper: Get conversations
 */
function chatbot_db_get_conversations($session_id, $limit = 100) {
    if (chatbot_should_use_supabase_db()) {
        return chatbot_supabase_get_conversations($session_id, $limit);
    }

    // Fall back to WordPress
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s ORDER BY interaction_time ASC LIMIT %d",
            $session_id,
            $limit
        ),
        ARRAY_A
    );

    return $results ?: [];
}

/**
 * Wrapper: Get interaction counts
 */
function chatbot_db_get_interaction_counts($start_date, $end_date) {
    if (chatbot_should_use_supabase_db()) {
        return chatbot_supabase_get_interaction_counts($start_date, $end_date);
    }

    // Fall back to WordPress
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE date >= %s AND date <= %s ORDER BY date ASC",
            $start_date,
            $end_date
        ),
        ARRAY_A
    );

    return $results ?: [];
}

/**
 * Get database status for admin display
 */
function chatbot_db_get_status() {
    $status = [
        'using_supabase' => chatbot_should_use_supabase_db(),
        'supabase_configured' => chatbot_supabase_is_configured(),
        'supabase_url' => chatbot_supabase_get_url()
    ];

    if ($status['supabase_configured']) {
        $status['supabase_connected'] = chatbot_supabase_test_connection();

        if ($status['supabase_connected']) {
            $status['table_counts'] = chatbot_supabase_get_diagnostics();
        }
    }

    return $status;
}

/**
 * AJAX handler: Get database diagnostics
 */
function chatbot_ajax_get_db_diagnostics() {
    check_ajax_referer('chatbot_db_diagnostics', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $status = chatbot_db_get_status();
    wp_send_json_success($status);
}
add_action('wp_ajax_chatbot_get_db_diagnostics', 'chatbot_ajax_get_db_diagnostics');
