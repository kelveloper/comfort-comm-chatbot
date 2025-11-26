<?php
/**
 * Kognetiks Chatbot - CSAT (Customer Satisfaction) Handler
 *
 * This file handles CSAT feedback collection and storage
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// AJAX handler for CSAT feedback submission
function chatbot_chatgpt_submit_csat() {
    error_log('========== CSAT AJAX HANDLER CALLED ==========');
    error_log('POST data: ' . print_r($_POST, true));

    // Verify nonce
    error_log('Nonce check - isset: ' . (isset($_POST['chatbot_nonce']) ? 'YES' : 'NO'));
    if (isset($_POST['chatbot_nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_message_nonce');
        error_log('Nonce validation result: ' . ($nonce_valid ? 'VALID' : 'INVALID'));
    }

    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_message_nonce')) {
        error_log('CSAT ERROR: Nonce verification failed!');
        wp_send_json_error('Invalid nonce');
        return;
    }

    error_log('Nonce verified successfully, processing feedback...');

    // Get feedback data
    $feedback = sanitize_text_field($_POST['feedback']); // 'yes' or 'no'
    $question = sanitize_textarea_field($_POST['question']);
    $answer = sanitize_textarea_field($_POST['answer']);
    $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
    $user_id = sanitize_text_field($_POST['user_id']);
    $session_id = sanitize_text_field($_POST['session_id']);
    $page_id = intval($_POST['page_id']);
    $timestamp = current_time('mysql');

    // Get confidence score by searching FAQ
    $confidence_score = 'unknown';
    if (function_exists('chatbot_faq_search')) {
        $faq_result = chatbot_faq_search($question, true, $session_id, $user_id, $page_id);
        if ($faq_result && isset($faq_result['confidence'])) {
            $confidence_score = $faq_result['confidence'];
        }
    }

    // Debug logging
    prod_trace('NOTICE', 'CSAT - Received question: ' . $question);
    prod_trace('NOTICE', 'CSAT - Received answer: ' . $answer);
    prod_trace('NOTICE', 'CSAT - Confidence score: ' . $confidence_score);

    // Store in WordPress options (simple approach for P0)
    $csat_data = get_option('chatbot_chatgpt_csat_data', array(
        'total' => 0,
        'helpful' => 0,
        'not_helpful' => 0,
        'responses' => array()
    ));

    // Update counts
    $csat_data['total']++;
    if ($feedback === 'yes') {
        $csat_data['helpful']++;
    } else {
        $csat_data['not_helpful']++;
    }

    // Store individual response
    $csat_data['responses'][] = array(
        'feedback' => $feedback,
        'question' => $question,
        'answer' => $answer,
        'comment' => $comment,
        'confidence_score' => $confidence_score,
        'user_id' => $user_id,
        'session_id' => $session_id,
        'page_id' => $page_id,
        'timestamp' => $timestamp
    );

    // Keep only last 1000 responses to prevent option size from growing too large
    if (count($csat_data['responses']) > 1000) {
        $csat_data['responses'] = array_slice($csat_data['responses'], -1000);
    }

    // Save updated data
    $saved = update_option('chatbot_chatgpt_csat_data', $csat_data);
    error_log('CSAT Data saved to database: ' . ($saved ? 'SUCCESS' : 'FAILED'));
    error_log('Total responses now: ' . $csat_data['total']);

    // Calculate CSAT score
    $csat_score = 0;
    if ($csat_data['total'] > 0) {
        $csat_score = round(($csat_data['helpful'] / $csat_data['total']) * 100, 1);
    }

    // Log the feedback
    prod_trace('NOTICE', 'CSAT feedback received: ' . $feedback . ' | Score: ' . $csat_score . '%');

    // Return success with current score
    wp_send_json_success(array(
        'message' => 'Feedback saved',
        'csat_score' => $csat_score,
        'total_responses' => $csat_data['total'],
        'helpful_count' => $csat_data['helpful']
    ));
}
add_action('wp_ajax_chatbot_chatgpt_submit_csat', 'chatbot_chatgpt_submit_csat');
add_action('wp_ajax_nopriv_chatbot_chatgpt_submit_csat', 'chatbot_chatgpt_submit_csat');

// Function to get CSAT statistics (for admin dashboard)
function chatbot_chatgpt_get_csat_stats() {
    $csat_data = get_option('chatbot_chatgpt_csat_data', array(
        'total' => 0,
        'helpful' => 0,
        'not_helpful' => 0,
        'responses' => array()
    ));

    $csat_score = 0;
    if ($csat_data['total'] > 0) {
        $csat_score = round(($csat_data['helpful'] / $csat_data['total']) * 100, 1);
    }

    return array(
        'total_responses' => $csat_data['total'],
        'helpful_count' => $csat_data['helpful'],
        'not_helpful_count' => $csat_data['not_helpful'],
        'csat_score' => $csat_score,
        'target_met' => $csat_score >= 70 // P0 Success Metric: >70%
    );
}

// Function to reset CSAT data (for admin use)
function chatbot_chatgpt_reset_csat_data() {
    delete_option('chatbot_chatgpt_csat_data');
    prod_trace('NOTICE', 'CSAT data has been reset');
}
