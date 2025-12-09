<?php
/**
 * Steven-Bot - Shared Guardrails & Pre-Processing
 *
 * This file contains shared pre-processing rules that apply to ALL AI platforms
 * (OpenAI, Gemini, etc.) to ensure consistent behavior.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * Pre-process user message before sending to any AI API
 * Handles: off-topic filtering, escalation patterns, greeting detection
 *
 * @param string $message The user's message
 * @param string $user_id User ID
 * @param string $page_id Page ID
 * @param string $session_id Session ID
 * @return array|null Returns response array if handled, null to continue to AI
 */
function chatbot_preprocess_message($message, $user_id = null, $page_id = null, $session_id = null) {
    $message_lower = strtolower(trim($message));

    // GREETING DETECTION - Mark for fresh start (no old conversation context)
    $cleaned_for_greeting = trim(preg_replace('/\s+/', ' ', $message_lower));
    $is_new_conversation_greeting = preg_match('/^(hi|hello|hey|good\s*(morning|afternoon|evening)|greetings)\s*[!.,]?\s*$/i', $cleaned_for_greeting);
    if ($is_new_conversation_greeting) {
        error_log('[Chatbot Guardrails] Greeting detected - clearing conversation history');
        delete_transient('steven_bot_context_history');
    }

    // OFF-TOPIC FILTER - Block questions unrelated to telecommunications
    $off_topic_result = chatbot_check_off_topic($message_lower);
    if ($off_topic_result) {
        return $off_topic_result;
    }

    // ESCALATION PATTERNS - Billing, account, login, cancel
    $escalation_result = chatbot_check_escalation($message_lower);
    if ($escalation_result) {
        return $escalation_result;
    }

    // Continue to AI processing
    return null;
}

/**
 * Check if message is off-topic
 *
 * @param string $message_lower Lowercase message
 * @return array|null Response if off-topic, null otherwise
 */
function chatbot_check_off_topic($message_lower) {
    $off_topic_keywords = [
        // Cryptocurrency
        'bitcoin', 'crypto', 'cryptocurrency', 'ethereum', 'blockchain', 'nft', 'dogecoin',
        // Finance (non-telecom)
        'stock', 'stocks', 'forex', 'trading', 'investment', 'invest in',
        // Weather
        'weather', 'forecast', 'temperature', 'rain', 'snow', 'sunny',
        // Sports
        'football', 'basketball', 'baseball', 'soccer', 'nfl', 'nba', 'super bowl', 'world cup',
        // Politics
        'president', 'election', 'vote', 'congress', 'senate', 'political',
        // Religion
        'god', 'jesus', 'allah', 'buddha', 'bible', 'quran', 'church', 'mosque', 'temple',
        // Entertainment
        'movie', 'actor', 'actress', 'celebrity', 'netflix', 'spotify',
        // General knowledge (that's clearly not telecom)
        'recipe', 'cooking', 'restaurant', 'hotel', 'flight', 'vacation',
        // Health
        'doctor', 'hospital', 'medicine', 'sick', 'disease', 'covid',
        // Education (non-telecom)
        'homework', 'essay', 'school assignment', 'university application',
    ];

    foreach ($off_topic_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            error_log('[Chatbot Guardrails] OFF-TOPIC blocked: keyword "' . $keyword . '"');
            return [
                'response' => "I'm here to help with internet, phone, and TV services from Comfort Comm. " .
                             "For questions about other topics, I'd recommend searching online. " .
                             "How can I assist you with your telecommunications needs today? " .
                             "Call us at (347) 519-9999 for personalized help!",
                'handled' => true,
                'reason' => 'off_topic',
                'keyword' => $keyword
            ];
        }
    }

    return null;
}

/**
 * Check if message requires escalation to human
 *
 * @param string $message_lower Lowercase message
 * @return array|null Response if escalation needed, null otherwise
 */
function chatbot_check_escalation($message_lower) {
    $escalation_patterns = [
        'billing' => ['billing', 'bill', 'payment', 'pay my', 'invoice', 'charge', 'refund', 'overcharge'],
        'account' => ['account balance', 'my account', 'account number', 'account info', 'account detail'],
        'login' => ['login', 'log in', 'password', 'username', 'forgot password', 'reset password', 'cant access'],
        'cancel' => ['cancel service', 'cancel my', 'terminate service', 'disconnect service'],
    ];

    foreach ($escalation_patterns as $category => $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($message_lower, $pattern) !== false) {
                error_log('[Chatbot Guardrails] ESCALATION triggered: ' . $category);
                return [
                    'response' => "For your account's security, I can't access personal billing or account details. " .
                                 "Please call our team at (347) 519-9999 or visit us at 13692 Roosevelt Ave, Flushing NY 11354. " .
                                 "We're here to help!",
                    'handled' => true,
                    'reason' => 'escalation',
                    'category' => $category
                ];
            }
        }
    }

    return null;
}

/**
 * Check if message is a generic/vague question that should skip FAQ search
 *
 * @param string $message The user's message
 * @return bool True if generic, false otherwise
 */
function chatbot_is_generic_question($message) {
    $message_lower = strtolower(trim($message));

    $generic_patterns = [
        // Greetings and intros
        '/^(hi|hello|hey|good\s*(morning|afternoon|evening)|greetings)\s*[!.,]?\s*$/i',
        '/^(hi|hello|hey)\s+(there|bot|chatbot|assistant)?\s*[!.,]?\s*$/i',
        // Generic help requests
        '/^(help|help me|i need help|can you help|can you help me)\s*[!?.,]?\s*$/i',
        '/^how can (you|u) help( me)?\s*[!?]?\s*$/i',
        '/^what (can|do) (you|u) do\s*[!?]?\s*$/i',
        '/^what (services|help) (do you|can you) (offer|provide)\s*[!?]?\s*$/i',
        // Conversational follow-ups (too short/vague)
        '/^(why|how|what|when|where|who)\s*[!?]?\s*$/i',
        '/^(why|how come)\s+(that|this|so)\s*[!?]?\s*$/i',
        '/^(tell me more|go on|continue|explain)\s*[!?]?\s*$/i',
        // Single word questions
        '/^(yes|no|ok|okay|sure|thanks|thank you|thx|ty)\s*[!?.,]?\s*$/i',
    ];

    foreach ($generic_patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return true;
        }
    }

    return false;
}

/**
 * Perform smart FAQ search and return context or direct response
 * Matches Gemini's tiered confidence system
 *
 * @param string $message The user's message
 * @param string|null $session_id Session ID for context-aware search
 * @param string|null $user_id User ID
 * @param string|null $page_id Page ID
 * @return array Result with faq_context, skip_ai, and direct_response
 */
function chatbot_smart_faq_search($message, $session_id = null, $user_id = null, $page_id = null) {
    $result = [
        'faq_context' => '',
        'skip_ai' => false,
        'direct_response' => ''
    ];

    $message_lower = strtolower(trim($message));

    // Skip FAQ search for generic questions
    if (chatbot_is_generic_question($message)) {
        error_log('[Chatbot Guardrails] Skipping FAQ search - generic question');
        return $result;
    }

    // Short vague message detection (less than 4 words without telecom keywords)
    $word_count = str_word_count($message_lower);
    if ($word_count <= 3 && !preg_match('/(spectrum|verizon|optimum|fios|internet|tv|phone|wifi|router|modem|bill|price|cost|speed|mbps|channel)/i', $message_lower)) {
        error_log('[Chatbot Guardrails] Skipping FAQ search - short vague message');
        return $result;
    }

    // Contextual follow-up detection - use conversation history instead
    $contextual_patterns = [
        '/^why\s+(that|this|them|it|those|him|her|they)\s*[!?]?\s*$/i',
        '/^why\s+\w+\s*[!?]?\s*$/i',
        '/^what about\s+/i',
        '/^and\s+(what|how|why|when)/i',
        '/^but\s+(what|how|why|when)/i',
        '/^(so|then)\s+(what|how|why)/i',
    ];

    foreach ($contextual_patterns as $pattern) {
        if (preg_match($pattern, $message_lower)) {
            error_log('[Chatbot Guardrails] Skipping FAQ search - contextual follow-up');
            return $result;
        }
    }

    // Use context-aware vector search if available (Ver 2.5.0)
    $faq_result = null;
    if (function_exists('chatbot_vector_context_aware_search')) {
        error_log('[Chatbot Guardrails] Using context-aware vector search');
        $faq_result = chatbot_vector_context_aware_search($message, true, $session_id, $user_id, $page_id);
    } elseif (function_exists('chatbot_vector_faq_search')) {
        error_log('[Chatbot Guardrails] Using regular vector FAQ search');
        $faq_result = chatbot_vector_faq_search($message, true, $session_id, $user_id, $page_id);
    } elseif (function_exists('chatbot_vector_search')) {
        error_log('[Chatbot Guardrails] Using basic vector search');
        $search_results = chatbot_vector_search($message, [
            'threshold' => 0.40,
            'limit' => 3,
            'return_scores' => true
        ]);

        if ($search_results['success'] && !empty($search_results['results'])) {
            $best = $search_results['results'][0];
            $score = $best['similarity'] ?? 0;
            $faq_result = [
                'match' => $best,
                'score' => $score,
                'confidence' => $score >= 0.80 ? 'very_high' : ($score >= 0.60 ? 'high' : ($score >= 0.40 ? 'medium' : 'low'))
            ];
        }
    }

    if (!$faq_result || !isset($faq_result['match']) || empty($faq_result['match']['answer'])) {
        error_log('[Chatbot Guardrails] No FAQ match found');
        return $result;
    }

    $confidence = $faq_result['confidence'] ?? 'low';
    $score = $faq_result['score'] ?? 0;
    $faq_match = $faq_result['match'];

    error_log('[Chatbot Guardrails] FAQ match: score=' . round($score * 100) . '% confidence=' . $confidence);

    // TIER 1: Very High Confidence (80%+) - Return FAQ directly, NO AI CALL
    if ($confidence === 'very_high') {
        error_log('[Chatbot Guardrails] VERY HIGH confidence - returning FAQ directly');
        $result['skip_ai'] = true;
        $result['direct_response'] = $faq_match['answer'];
        return $result;
    }

    // TIER 2: High Confidence (60-80%) - Minimal AI processing
    if ($confidence === 'high') {
        error_log('[Chatbot Guardrails] HIGH confidence - FAQ with minimal AI');
        $result['faq_context'] = "\n\nUSE THIS FAQ ANSWER: " . $faq_match['answer'] . "\n\n" .
                                "Rephrase it naturally in 1-2 sentences. Be concise.";
        return $result;
    }

    // TIER 3: Medium Confidence (40-60%) - AI with FAQ context
    if ($confidence === 'medium') {
        error_log('[Chatbot Guardrails] MEDIUM confidence - FAQ as reference');
        $result['faq_context'] = "\n\nRELEVANT FAQ:\nQ: " . $faq_match['question'] . "\nA: " . $faq_match['answer'] . "\n\n" .
                                "Use this FAQ as a reference, but ask clarifying questions if the user's intent is unclear.";
        return $result;
    }

    // TIER 4: Low Confidence - Don't use FAQ, let AI handle naturally
    error_log('[Chatbot Guardrails] LOW confidence - ignoring FAQ');
    return $result;
}
