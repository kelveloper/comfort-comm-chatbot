<?php
/**
 * Chatbot Vector Search - Database Schema
 *
 * PostgreSQL + pgvector schema for semantic FAQ search.
 * Uses OpenAI text-embedding-3-small (1536 dimensions).
 *
 * @package comfort-comm-chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * SQL to create the FAQs table with vector embeddings.
 *
 * Run this in your PostgreSQL database:
 *
 * -- First, enable the pgvector extension
 * CREATE EXTENSION IF NOT EXISTS vector;
 *
 * -- Create the FAQs table with embeddings
 * CREATE TABLE IF NOT EXISTS chatbot_faqs (
 *     id SERIAL PRIMARY KEY,
 *     faq_id VARCHAR(50) UNIQUE NOT NULL,
 *     question TEXT NOT NULL,
 *     answer TEXT NOT NULL,
 *     category VARCHAR(255),
 *     keywords TEXT,
 *     question_embedding vector(1536),
 *     answer_embedding vector(1536),
 *     combined_embedding vector(1536),
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * );
 *
 * -- Create index for fast cosine similarity search
 * CREATE INDEX IF NOT EXISTS idx_faqs_combined_embedding
 * ON chatbot_faqs USING ivfflat (combined_embedding vector_cosine_ops)
 * WITH (lists = 100);
 *
 * -- Create index on faq_id for lookups
 * CREATE INDEX IF NOT EXISTS idx_faqs_faq_id ON chatbot_faqs(faq_id);
 *
 * -- Create index on category for filtering
 * CREATE INDEX IF NOT EXISTS idx_faqs_category ON chatbot_faqs(category);
 */

/**
 * PostgreSQL connection configuration.
 * Add these constants to wp-config.php:
 *
 * define('CHATBOT_PG_HOST', 'localhost');
 * define('CHATBOT_PG_PORT', '5432');
 * define('CHATBOT_PG_DATABASE', 'chatbot_vectors');
 * define('CHATBOT_PG_USER', 'your_username');
 * define('CHATBOT_PG_PASSWORD', 'your_password');
 */

/**
 * Get PostgreSQL connection status
 *
 * Note: Direct PDO connections are not used in this version.
 * All database operations use the Supabase REST API instead.
 *
 * @return null Always returns null - use REST API functions instead
 * @deprecated Use chatbot_vector_supabase_request() instead
 */
function chatbot_vector_get_pg_connection() {
    // Direct PDO connections are not supported in this version.
    // Use Supabase REST API via chatbot_vector_supabase_request() instead.
    return null;
}

/**
 * Get Supabase configuration for REST API
 *
 * @return array|null Supabase config or null if not configured
 */
function chatbot_vector_get_supabase_config() {
    if (!defined('CHATBOT_PG_HOST')) {
        return null;
    }

    // Extract project ref from host (e.g., db.xxxxx.supabase.co -> xxxxx)
    $host = CHATBOT_PG_HOST;
    if (preg_match('/db\.([a-z0-9]+)\.supabase\.co/', $host, $matches)) {
        $project_ref = $matches[1];
        return [
            'url' => 'https://' . $project_ref . '.supabase.co',
            'anon_key' => defined('CHATBOT_SUPABASE_ANON_KEY') ? CHATBOT_SUPABASE_ANON_KEY : null,
            'service_key' => defined('CHATBOT_SUPABASE_SERVICE_KEY') ? CHATBOT_SUPABASE_SERVICE_KEY : null,
        ];
    }

    return null;
}

/**
 * Make a Supabase REST API request
 *
 * @param string $endpoint The API endpoint (e.g., '/rest/v1/chatbot_faqs')
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array $params Query parameters or body data
 * @param bool $use_service_key Use service key instead of anon key
 * @return array|null Response data or null on failure
 */
function chatbot_vector_supabase_request($endpoint, $method = 'GET', $params = [], $use_service_key = false) {
    $config = chatbot_vector_get_supabase_config();

    if (!$config) {
        error_log('[Chatbot Vector] Supabase configuration not found');
        return null;
    }

    $api_key = $use_service_key ? $config['service_key'] : $config['anon_key'];
    if (!$api_key) {
        error_log('[Chatbot Vector] Supabase API key not configured');
        return null;
    }

    $url = $config['url'] . $endpoint;

    $headers = [
        'apikey' => $api_key,
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
        'Prefer' => 'return=representation',
    ];

    $args = [
        'method' => $method,
        'headers' => $headers,
        'timeout' => 30,
    ];

    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    } elseif (!empty($params)) {
        $args['body'] = json_encode($params);
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        error_log('[Chatbot Vector] Supabase request failed: ' . $response->get_error_message());
        return null;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status >= 400) {
        $error = isset($data['message']) ? $data['message'] : 'Unknown error';
        error_log('[Chatbot Vector] Supabase API error: ' . $error);
        return null;
    }

    return $data;
}

/**
 * Initialize the vector database schema
 *
 * Note: Schema must be created via Supabase SQL Editor.
 * This function returns instructions for manual setup.
 *
 * @return array Result with success status and message
 */
function chatbot_vector_init_schema() {
    return [
        'success' => false,
        'message' => 'Schema must be created via Supabase SQL Editor. Use the Setup Wizard in Steven-Bot > Setup to get the SQL.'
    ];
}

/**
 * Create the IVFFlat index for faster similarity search
 *
 * Note: Index must be created via Supabase SQL Editor.
 *
 * @param int $lists Number of lists for IVFFlat (default 100, use sqrt(n) as guideline)
 * @return array Result with success status and message
 */
function chatbot_vector_create_search_index($lists = 10) {
    return [
        'success' => false,
        'message' => 'Search index must be created via Supabase SQL Editor.'
    ];
}

/**
 * Check if vector search is available
 *
 * @return bool True if Supabase is configured and accessible
 */
function chatbot_vector_is_available() {
    // Check Supabase REST API config
    $config = chatbot_vector_get_supabase_config();
    if (!$config || empty($config['anon_key'])) {
        return false;
    }

    // Try to query the FAQs table via REST API
    $response = chatbot_vector_supabase_request('/rest/v1/chatbot_faqs?select=id&limit=1');
    return ($response !== null);
}

/**
 * Get vector database statistics via REST API
 *
 * @return array Stats including FAQ count, etc.
 */
function chatbot_vector_get_stats() {
    $config = chatbot_vector_get_supabase_config();

    if (!$config || empty($config['anon_key'])) {
        return [
            'available' => false,
            'error' => 'Supabase not configured'
        ];
    }

    $stats = [
        'available' => true,
        'connection' => 'ok'
    ];

    // Get FAQ count via REST API with count header
    $url = $config['url'] . '/rest/v1/chatbot_faqs?select=id';
    $response = wp_remote_get($url, [
        'headers' => [
            'apikey' => $config['anon_key'],
            'Authorization' => 'Bearer ' . $config['anon_key'],
            'Prefer' => 'count=exact'
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        return [
            'available' => false,
            'error' => $response->get_error_message()
        ];
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        // Get count from content-range header
        $range = wp_remote_retrieve_header($response, 'content-range');
        if (preg_match('/\/(\d+)$/', $range, $matches)) {
            $stats['faq_count'] = intval($matches[1]);
        } else {
            $stats['faq_count'] = 0;
        }
        $stats['faqs_with_embeddings'] = $stats['faq_count']; // Assume all have embeddings
    } elseif ($code === 404) {
        // Table doesn't exist yet
        $stats['faq_count'] = 0;
        $stats['faqs_with_embeddings'] = 0;
    } else {
        return [
            'available' => false,
            'error' => 'Failed to query database (HTTP ' . $code . ')'
        ];
    }

    // Note: Categories and index status not available via REST API
    $stats['categories'] = [];
    $stats['search_index_exists'] = true; // Assume it exists if setup was done properly

    return $stats;
}
