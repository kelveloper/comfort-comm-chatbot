<?php
/**
 * Kognetiks Analytics - Sentiment Analysis - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Sentiment Analysis.
 * It handles the sentiment analysis of the conversation.
 * 
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Add status tracking option
function kognetiks_analytics_get_scoring_status() {
    
    return get_option('kognetiks_analytics_scoring_status', 'stopped');

}

function kognetiks_analytics_set_scoring_status($status) {

    update_option('kognetiks_analytics_scoring_status', $status);

}

// Scoring lock helpers
function kognetiks_analytics_is_scoring_locked() {

    return get_option('kognetiks_analytics_scoring_lock', false) === '1';

}

function kognetiks_analytics_set_scoring_lock($locked = true) {

    update_option('kognetiks_analytics_scoring_lock', $locked ? '1' : '0');

}

// Stop the scoring process
function kognetiks_analytics_stop_scoring() {

    kognetiks_analytics_set_scoring_status('stopped');
    kognetiks_analytics_set_scoring_lock(false); // Clear lock on stop
    // back_trace( 'NOTICE', 'Sentiment scoring process stopped');

}

// Reset all sentiment scores (Supabase only)
function kognetiks_analytics_reset_scoring() {

    // Sentiment scoring for Supabase is handled during conversation logging
    kognetiks_analytics_set_scoring_lock(false);

}

// Restart the scoring process
function kognetiks_analytics_restart_scoring() {

    kognetiks_analytics_set_scoring_status('running');
    // back_trace( 'NOTICE', 'Sentiment scoring process restarted');

}

// Score conversations without a sentiment score (Supabase handles this during logging)
function kognetiks_analytics_score_conversations_without_sentiment_score() {

    // Sentiment scoring for Supabase is handled during conversation logging
    // This function is no longer needed as sentiment is computed when messages are logged
    return;

}

// Score conversations without a sentiment score (AI-based)
// Updated Ver 2.4.8: Sentiment scoring is now handled during conversation logging in Supabase
function kognetiks_analytics_score_conversations_without_sentiment_score_ai_based() {

    // Sentiment scoring for Supabase is handled during conversation logging
    // This function is no longer needed as sentiment is computed when messages are logged
    return;

}

// Get the scoring control mode (Manual/Automated)
function kognetiks_analytics_get_scoring_control_mode() {
    
    return get_option('kognetiks_analytics_scoring_control', 'Manual');

}

// Schedule the automated scoring cron job
function kognetiks_analytics_schedule_scoring_cron() {

    if (!wp_next_scheduled('kognetiks_analytics_automated_scoring')) {
        wp_schedule_event(time(), 'hourly', 'kognetiks_analytics_automated_scoring');
        // back_trace( 'NOTICE', 'Automated scoring cron job scheduled');
    }

}

// Unschedule the automated scoring cron job
function kognetiks_analytics_unschedule_scoring_cron() {

    $timestamp = wp_next_scheduled('kognetiks_analytics_automated_scoring');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'kognetiks_analytics_automated_scoring');
        // back_trace( 'NOTICE', 'Automated scoring cron job unscheduled');
    }

}

// Cron job callback function
function kognetiks_analytics_automated_scoring_callback() {

    // Only run if scoring control is set to Automated
    if (kognetiks_analytics_get_scoring_control_mode() === 'Automated') {
        // back_trace( 'NOTICE', 'Running automated scoring cron job');
        kognetiks_analytics_score_conversations_without_sentiment_score();
    } else {
        // If somehow the cron job is still running but mode is Manual, unschedule it
        kognetiks_analytics_unschedule_scoring_cron();
    }

}
add_action('kognetiks_analytics_automated_scoring', 'kognetiks_analytics_automated_scoring_callback');

// Set the scoring control mode (Manual/Automated)
function kognetiks_analytics_set_scoring_control_mode($mode) {

    if (!in_array($mode, ['Manual', 'Automated'])) {
        return false;
    }

    $current_mode = kognetiks_analytics_get_scoring_control_mode();
    
    // Update the option in the database
    update_option('kognetiks_analytics_scoring_control', $mode);

    // Handle cron job based on mode change
    if ($mode === 'Automated' && $current_mode !== 'Automated') {
        // Switching to Automated - schedule the cron job
        kognetiks_analytics_schedule_scoring_cron();
    } elseif ($mode === 'Manual' && $current_mode !== 'Manual') {
        // Switching to Manual - unschedule the cron job
        kognetiks_analytics_unschedule_scoring_cron();
    }

    return true;

}

// Cleanup function to unschedule cron job on plugin deactivation
function kognetiks_analytics_deactivate() {

    kognetiks_analytics_unschedule_scoring_cron();

}
register_deactivation_hook(__FILE__, 'kognetiks_analytics_deactivate');

// Wrapper function to compute sentiment score using either simple, vector, or AI-based method
function kognetiks_analytics_compute_sentiment_score($message_text) {

    // Get the scoring method from options
    $scoring_method = get_option('kognetiks_analytics_scoring_method', 'simple');

    if ($scoring_method === 'vector') {
        // Vector embedding-based sentiment analysis
        $score = kognetiks_analytics_compute_sentiment_score_vector($message_text);
    } elseif ($scoring_method === 'ai_based') {
        // FIXME - AI-based scoring is future functionality
        // $score = kognetiks_analytics_compute_sentiment_score_ai_based($message_text);
        $score = kognetiks_analytics_compute_sentiment_score_simple($message_text);
    } else {
        $score = kognetiks_analytics_compute_sentiment_score_simple($message_text);
    }

    // Format score to one decimal place using standard rounding
    return round($score, 1);

}

// Compute the sentiment score for a message using a simple algorithm
function kognetiks_analytics_compute_sentiment_score_simple($message_text) {

    global $sentiment_words, $negator_words, $intensifier_words;
    
    // Convert to lowercase and remove punctuation
    $message_text = strtolower($message_text);
    $message_text = preg_replace('/[^\w\s]/', ' ', $message_text);
    
    // Split into words
    $tokens = preg_split('/\s+/', $message_text, -1, PREG_SPLIT_NO_EMPTY);
    
    $score = 0;
    $count = 0;
    $negator = false;
    
    foreach ($tokens as $word) {

        // Check for negators
        if (in_array($word, $negator_words)) {
            $negator = true;
            continue;
        }
        
        // Check for intensifiers
        $intensity = 1;
        if (isset($intensifier_words[$word])) {
            $intensity = $intensifier_words[$word];
            continue;
        }
        
        // Check for sentiment words
        if (isset($sentiment_words[$word])) {
            $word_score = $sentiment_words[$word];
            // Apply negator if present
            if ($negator) {
                $word_score = -$word_score;
                $negator = false;
            }
            // Apply intensity
            $word_score *= $intensity;
            $score += $word_score;
            $count++;
        }
    }
    
    if ($count === 0) return 0.0;

    // Normalize score to -1.0 to 1.0
    $normalized_score = $score / ($count * 5); // assuming max abs score = 5
    return max(min($normalized_score, 1.0), -1.0);

}

/**
 * Compute sentiment score using vector embeddings
 *
 * Uses cosine similarity between message embedding and sentiment reference embeddings.
 * More accurate than keyword matching, faster than full AI analysis.
 *
 * @param string $message_text The message to analyze
 * @return float Sentiment score from -1.0 (negative) to 1.0 (positive)
 */
function kognetiks_analytics_compute_sentiment_score_vector($message_text) {
    // Skip empty or very short messages
    if (empty($message_text) || strlen(trim($message_text)) < 3) {
        return 0.0;
    }

    // Check if vector functions are available
    if (!function_exists('chatbot_vector_generate_embedding')) {
        error_log('[Chatbot Sentiment] Vector embedding function not available');
        return 0.0;
    }

    // Generate embedding for the message
    $message_embedding = chatbot_vector_generate_embedding($message_text);
    if (!$message_embedding) {
        error_log('[Chatbot Sentiment] Failed to generate embedding for message');
        return 0.0;
    }

    // Get or generate sentiment reference embeddings (cached in transient)
    $references = kognetiks_analytics_get_sentiment_reference_embeddings();
    if (!$references) {
        error_log('[Chatbot Sentiment] Failed to get reference embeddings');
        return 0.0;
    }

    // Calculate cosine similarity to positive and negative references
    $positive_similarity = kognetiks_analytics_cosine_similarity($message_embedding, $references['positive']);
    $negative_similarity = kognetiks_analytics_cosine_similarity($message_embedding, $references['negative']);
    $neutral_similarity = kognetiks_analytics_cosine_similarity($message_embedding, $references['neutral']);

    // Calculate sentiment score based on relative similarities
    // If more similar to positive, score is positive; if more similar to negative, score is negative
    $pos_neg_diff = $positive_similarity - $negative_similarity;

    // Weight by how "emotional" the message is (distance from neutral)
    $emotional_intensity = max($positive_similarity, $negative_similarity) - $neutral_similarity;
    $emotional_intensity = max(0, $emotional_intensity); // Clamp to 0+

    // Combine: direction from pos/neg difference, magnitude from emotional intensity
    if ($emotional_intensity < 0.05) {
        // Very neutral message
        return 0.0;
    }

    // Scale the difference to -1 to 1 range
    // Typical similarity differences are small (0.0 to 0.3), so we amplify
    $score = $pos_neg_diff * 3;

    // Clamp to valid range
    return max(-1.0, min(1.0, $score));
}

/**
 * Get or generate sentiment reference embeddings
 * Caches embeddings in transient for performance
 *
 * @return array|null Array with 'positive', 'negative', 'neutral' embeddings or null on failure
 */
function kognetiks_analytics_get_sentiment_reference_embeddings() {
    // Check transient cache first (cache for 24 hours)
    $cached = get_transient('chatbot_sentiment_reference_embeddings');
    if ($cached !== false) {
        return $cached;
    }

    if (!function_exists('chatbot_vector_generate_embedding')) {
        return null;
    }

    // Generate reference embeddings for sentiment anchors
    // Using multiple phrases per sentiment for robustness
    $positive_phrases = [
        "I am very happy and satisfied with the service",
        "Thank you so much, this is excellent and helpful",
        "Great job, I really appreciate your help",
        "This is wonderful, exactly what I needed"
    ];

    $negative_phrases = [
        "I am frustrated and angry with this terrible service",
        "This is awful, I hate this and want to complain",
        "Very disappointed, nothing works properly",
        "This is unacceptable, I am very upset"
    ];

    $neutral_phrases = [
        "I have a question about the service",
        "Can you tell me more information",
        "What are the available options",
        "How does this work"
    ];

    // Generate and average embeddings for each sentiment
    $positive_embedding = kognetiks_analytics_average_embeddings($positive_phrases);
    $negative_embedding = kognetiks_analytics_average_embeddings($negative_phrases);
    $neutral_embedding = kognetiks_analytics_average_embeddings($neutral_phrases);

    if (!$positive_embedding || !$negative_embedding || !$neutral_embedding) {
        return null;
    }

    $references = [
        'positive' => $positive_embedding,
        'negative' => $negative_embedding,
        'neutral' => $neutral_embedding
    ];

    // Cache for 24 hours
    set_transient('chatbot_sentiment_reference_embeddings', $references, DAY_IN_SECONDS);

    return $references;
}

/**
 * Generate average embedding from multiple phrases
 *
 * @param array $phrases Array of text phrases
 * @return array|null Averaged embedding vector or null on failure
 */
function kognetiks_analytics_average_embeddings($phrases) {
    $embeddings = [];

    foreach ($phrases as $phrase) {
        $embedding = chatbot_vector_generate_embedding($phrase);
        if ($embedding) {
            $embeddings[] = $embedding;
        }
        // Small delay to avoid rate limiting
        usleep(50000); // 50ms
    }

    if (empty($embeddings)) {
        return null;
    }

    // Average the embeddings
    $dimensions = count($embeddings[0]);
    $averaged = array_fill(0, $dimensions, 0.0);

    foreach ($embeddings as $embedding) {
        for ($i = 0; $i < $dimensions; $i++) {
            $averaged[$i] += $embedding[$i];
        }
    }

    $count = count($embeddings);
    for ($i = 0; $i < $dimensions; $i++) {
        $averaged[$i] /= $count;
    }

    return $averaged;
}

/**
 * Calculate cosine similarity between two vectors
 *
 * @param array $vec1 First vector
 * @param array $vec2 Second vector
 * @return float Similarity score from 0 to 1
 */
function kognetiks_analytics_cosine_similarity($vec1, $vec2) {
    if (count($vec1) !== count($vec2)) {
        return 0.0;
    }

    $dot_product = 0.0;
    $norm1 = 0.0;
    $norm2 = 0.0;

    for ($i = 0; $i < count($vec1); $i++) {
        $dot_product += $vec1[$i] * $vec2[$i];
        $norm1 += $vec1[$i] * $vec1[$i];
        $norm2 += $vec2[$i] * $vec2[$i];
    }

    $norm1 = sqrt($norm1);
    $norm2 = sqrt($norm2);

    if ($norm1 == 0 || $norm2 == 0) {
        return 0.0;
    }

    return $dot_product / ($norm1 * $norm2);
}

// Compute the sentiment score for a message using an AI model
function kognetiks_analytics_compute_sentiment_score_ai_based($message_text) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Analyzing sentiment of message: ' . $message_text);

    // Get the AI Platform Choice from the options table
    $ai_platform_choice = get_option('chatbot_ai_platform_choice');

    // Get the AI Model from the options table
    switch ($ai_platform_choice) {  
        case 'OpenAI':
            $ai_model_choice = get_option('chatbot_chatgpt_model_choice');
            break;
        case 'Anthropic':
            $ai_model_choice = get_option('chatbot_anthropic_model_choice');
            break;
        case 'Azure OpenAI':
            $ai_model_choice = get_option('chatbot_azure_model_choice');
            break;
        case 'DeepSeek':
            $ai_model_choice = get_option('chatbot_deepseek_model_choice');
            break;
        case 'Mistral':
            $ai_model_choice = get_option('chatbot_mistral_model_choice');
            break;  
        case 'NVIDIA':
            $ai_model_choice = get_option('chatbot_nvidia_model_choice');
            break;
        case 'Local Server':
            $ai_model_choice = get_option('chatbot_local_model_choice');
            break;
        default:
            $ai_model_choice = 'gpt-3.5-turbo';
    }

    $sentiment_prompt = 'You are a sentiment analysis model. Your task is to analyze the sentiment of the message and return only the (no other text)score between -1.0 and 1.0. -1.0 is negative, 0 is neutral, and 1.0 is positive. Rate this message from -1.0 to 1.0: ';
    
    // Initialize sentiment score
    $sentiment_score = 0;
    
    // Get the API Key from the options table
    switch ($ai_platform_choice) {
        case 'OpenAI':
            $api_key = get_option('chatbot_chatgpt_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call OpenAI API
            $sentiment_score = kognetiks_analytics_openai_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Anthropic':
            $api_key = get_option('chatbot_anthropic_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call Anthropic API
            $sentiment_score = kognetiks_analytics_anthropic_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Azure OpenAI':
            $api_key = get_option('chatbot_azure_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call Azure OpenAI API
            $sentiment_score = kognetiks_analytics_azure_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'DeepSeek':
            $api_key = get_option('chatbot_deepseek_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call DeepSeek API
            $sentiment_score = kognetiks_analytics_deepseek_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Mistral':
            $api_key = get_option('chatbot_mistral_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call Mistral API
            $sentiment_score = kognetiks_analytics_mistral_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'NVIDIA':
            $api_key = get_option('chatbot_nvidia_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call NVIDIA API
            $sentiment_score = kognetiks_analytics_nvidia_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Local Server':
            $api_key = get_option('chatbot_local_server_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call the Local Server API
            $sentiment_score = kognetiks_analytics_local_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        default:
            // If no platform is selected, return neutral sentiment
            $sentiment_score = 0;
            break;
    }

    // Ensure the sentiment score is a valid number between -1 and 1
    $sentiment_score = floatval($sentiment_score);
    if ($sentiment_score < -1) $sentiment_score = -1;
    if ($sentiment_score > 1) $sentiment_score = 1;

    // Return the sentiment score
    return $sentiment_score;

}
