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
 * @param string $preferred_api 'gemini' or 'openai' - which API to use for AI classification
 * @return array|null Returns response array if handled, null to continue to AI
 */
function chatbot_preprocess_message($message, $user_id = null, $page_id = null, $session_id = null, $preferred_api = 'openai') {
    $message_lower = strtolower(trim($message));

    // GREETING DETECTION - Mark for fresh start (no old conversation context)
    $cleaned_for_greeting = trim(preg_replace('/\s+/', ' ', $message_lower));
    $is_new_conversation_greeting = preg_match('/^(hi|hello|hey|good\s*(morning|afternoon|evening)|greetings)\s*[!.,]?\s*$/i', $cleaned_for_greeting);
    if ($is_new_conversation_greeting) {
        error_log('[Chatbot Guardrails] Greeting detected - clearing conversation history');
        delete_transient('steven_bot_context_history');
    }

    // ENHANCED OFF-TOPIC FILTER - Keyword check + AI classification - Ver 2.5.0
    $off_topic_result = chatbot_enhanced_off_topic_check($message, $preferred_api);
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

/**
 * AI-powered topic classification using the user's configured platform
 * Ver 2.5.2: Uses steven_bot_get_api_config() for platform-aware API calls
 *
 * @param string $message The user's message
 * @return array ['is_on_topic' => bool, 'confidence' => float, 'reason' => string]
 */
function chatbot_gemini_topic_classification($message) {
    $result = [
        'is_on_topic' => true,  // Default to on-topic if classification fails
        'confidence' => 0.5,
        'reason' => 'default'
    ];

    // Get API config based on user's platform choice
    $api_config = steven_bot_get_api_config();

    if (empty($api_config['api_key'])) {
        error_log('[Chatbot Guardrails] Topic classification skipped - no API key for ' . $api_config['platform']);
        return $result;
    }

    $api_key = $api_config['api_key'];
    $platform = $api_config['platform'];

    $classification_prompt = 'You are a topic classifier for a telecommunications company chatbot (Comfort Communication Inc. - internet, TV, phone services).

ONLY these topics are ON-TOPIC:
- Internet, broadband, WiFi, fiber, cable services
- TV, streaming, cable packages, channels
- Phone services (landline, mobile plans, top-up, prepaid recharge)
- Telecom carriers: Spectrum, Verizon, Optimum, AT&T, T-Mobile, EarthLink, Frontier, Lycamobile, Ultra Mobile, H2O
- Equipment: routers, modems, set-top boxes
- ADT Home Security (cameras, alarms, monitoring)
- Billing, pricing, plans, installation, technical support
- Company info (store location, hours, contact)
- General questions about what services we offer or how we can help

EVERYTHING ELSE is OFF-TOPIC (math, science, recipes, crypto, sports, weather, general knowledge, etc.)

IMPORTANT: If the message mentions "Comfort Comm", "Comfort Communication", or asks what we can help with, it is ON-TOPIC.

User message: "' . addslashes($message) . '"

Respond ONLY with JSON (no markdown): {"on_topic": true/false, "confidence": 0.0-1.0, "reason": "2-3 words"}';

    $start_time = microtime(true);

    // Make API call based on platform
    if ($platform === 'Gemini') {
        $api_url = $api_config['base_url'] . '/models/' . $api_config['model'] . ':generateContent?key=' . $api_key;

        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'contents' => [['parts' => [['text' => $classification_prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 50,
                    'topP' => 0.1
                ]
            ]),
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            error_log('[Chatbot Guardrails] Gemini classification error: ' . $response->get_error_message());
            return $result;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('[Chatbot Guardrails] Gemini classification - unexpected response');
            return $result;
        }

        $ai_response = $response_body['candidates'][0]['content']['parts'][0]['text'];

    } else {
        // OpenAI and compatible APIs
        $api_url = $api_config['chat_url'];
        $headers = ['Content-Type' => 'application/json'];

        if ($platform === 'Azure OpenAI') {
            $headers['api-key'] = $api_key;
        } elseif ($platform === 'Anthropic') {
            $headers['x-api-key'] = $api_key;
            $headers['anthropic-version'] = '2023-06-01';
        } else {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        if ($platform === 'Anthropic') {
            $post_body = wp_json_encode([
                'model' => $api_config['model'],
                'max_tokens' => 50,
                'messages' => [['role' => 'user', 'content' => $classification_prompt]]
            ]);
        } else {
            $post_body = wp_json_encode([
                'model' => $api_config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $classification_prompt],
                    ['role' => 'user', 'content' => $message]
                ],
                'temperature' => 0.1,
                'max_tokens' => 50
            ]);
        }

        $response = wp_remote_post($api_url, [
            'headers' => $headers,
            'body' => $post_body,
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            error_log('[Chatbot Guardrails] ' . $platform . ' classification error: ' . $response->get_error_message());
            return $result;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($platform === 'Anthropic') {
            if (!isset($response_body['content'][0]['text'])) {
                error_log('[Chatbot Guardrails] Anthropic classification - unexpected response');
                return $result;
            }
            $ai_response = $response_body['content'][0]['text'];
        } else {
            if (!isset($response_body['choices'][0]['message']['content'])) {
                error_log('[Chatbot Guardrails] ' . $platform . ' classification - unexpected response');
                return $result;
            }
            $ai_response = $response_body['choices'][0]['message']['content'];
        }
    }

    $elapsed = round((microtime(true) - $start_time) * 1000);
    error_log("[Chatbot Guardrails] {$platform} classification took {$elapsed}ms");

    $ai_response = preg_replace('/```json\s*|```\s*/', '', trim($ai_response));
    $classification = json_decode($ai_response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($classification['on_topic'])) {
        $result['is_on_topic'] = (bool) $classification['on_topic'];
        $result['confidence'] = floatval($classification['confidence'] ?? 0.8);
        $result['reason'] = $classification['reason'] ?? $platform . '_classified';

        error_log('[Chatbot Guardrails] ' . $platform . ': ' .
            ($result['is_on_topic'] ? 'ON-TOPIC' : 'OFF-TOPIC') .
            ' (' . round($result['confidence'] * 100) . '% - ' . $result['reason'] . ')');
    }

    return $result;
}

/**
 * AI-powered topic classification using OpenAI
 * @deprecated Use chatbot_gemini_topic_classification() which now handles all platforms
 *
 * @param string $message The user's message
 * @return array ['is_on_topic' => bool, 'confidence' => float, 'reason' => string]
 */
function chatbot_openai_topic_classification($message) {
    // Redirect to the unified function that uses steven_bot_get_api_config()
    return chatbot_gemini_topic_classification($message);
}

/**
 * @deprecated Legacy function kept for backwards compatibility
 */
function chatbot_openai_topic_classification_legacy($message) {
    $result = [
        'is_on_topic' => true,
        'confidence' => 0.5,
        'reason' => 'default'
    ];

    // Get OpenAI API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', ''));
    if (empty($api_key)) {
        error_log('[Chatbot Guardrails] OpenAI classification skipped - no API key');
        return $result;
    }

    $api_url = 'https://api.openai.com/v1/chat/completions';

    $classification_prompt = 'You are a topic classifier for a telecommunications company chatbot (Comfort Communication Inc. - internet, TV, phone services).

ONLY these topics are ON-TOPIC:
- Internet, broadband, WiFi, fiber, cable services
- TV, streaming, cable packages, channels
- Phone services (landline, mobile plans, top-up, prepaid recharge)
- Telecom carriers: Spectrum, Verizon, Optimum, AT&T, T-Mobile, EarthLink, Frontier, Lycamobile, Ultra Mobile, H2O
- Equipment: routers, modems, set-top boxes
- ADT Home Security (cameras, alarms, monitoring)
- Billing, pricing, plans, installation, technical support
- Company info (store location, hours, contact)
- General questions about what services we offer or how we can help

EVERYTHING ELSE is OFF-TOPIC (math, science, recipes, crypto, sports, weather, general knowledge, etc.)

IMPORTANT: If the message mentions "Comfort Comm", "Comfort Communication", or asks what we can help with, it is ON-TOPIC.

Respond ONLY with JSON: {"on_topic": true/false, "confidence": 0.0-1.0, "reason": "2-3 words"}';

    $body = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $classification_prompt],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.1,
        'max_tokens' => 50
    ];

    $start_time = microtime(true);

    $response = wp_remote_post($api_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => wp_json_encode($body),
        'timeout' => 5,
    ]);

    $elapsed = round((microtime(true) - $start_time) * 1000);
    error_log("[Chatbot Guardrails] OpenAI classification took {$elapsed}ms");

    if (is_wp_error($response)) {
        error_log('[Chatbot Guardrails] OpenAI classification error: ' . $response->get_error_message());
        return $result;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($response_body['choices'][0]['message']['content'])) {
        error_log('[Chatbot Guardrails] OpenAI classification - unexpected response');
        return $result;
    }

    $ai_response = trim($response_body['choices'][0]['message']['content']);
    $ai_response = preg_replace('/```json\s*|```\s*/', '', $ai_response);

    $classification = json_decode($ai_response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($classification['on_topic'])) {
        $result['is_on_topic'] = (bool) $classification['on_topic'];
        $result['confidence'] = floatval($classification['confidence'] ?? 0.8);
        $result['reason'] = $classification['reason'] ?? 'openai_classified';

        error_log('[Chatbot Guardrails] OpenAI: ' .
            ($result['is_on_topic'] ? 'ON-TOPIC' : 'OFF-TOPIC') .
            ' (' . round($result['confidence'] * 100) . '% - ' . $result['reason'] . ')');
    }

    return $result;
}

/**
 * Enhanced off-topic check - keyword filter only
 * AI classification removed due to false positives on follow-up questions
 *
 * The main AI model's system prompt handles nuanced off-topic detection.
 * This function only blocks obvious off-topic keywords.
 *
 * @param string $message The user's message
 * @param string $preferred_api Unused, kept for backward compatibility
 * @return array|null Response if off-topic, null to continue
 */
function chatbot_enhanced_off_topic_check($message, $preferred_api = 'gemini') {
    $message_lower = strtolower(trim($message));

    // Only use keyword-based off-topic check
    // The AI's system prompt handles nuanced cases
    return chatbot_check_off_topic($message_lower);
}
