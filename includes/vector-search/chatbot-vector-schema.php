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
 * Get PostgreSQL PDO connection
 *
 * @return PDO|null PostgreSQL PDO instance or null on failure
 */
function chatbot_vector_get_pg_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Check if PostgreSQL config is defined
    if (!defined('CHATBOT_PG_HOST') || !defined('CHATBOT_PG_DATABASE')) {
        error_log('[Chatbot Vector] PostgreSQL configuration not found in wp-config.php');
        return null;
    }

    $host = CHATBOT_PG_HOST;
    $port = defined('CHATBOT_PG_PORT') ? CHATBOT_PG_PORT : '5432';
    $dbname = CHATBOT_PG_DATABASE;
    $user = CHATBOT_PG_USER;
    $password = CHATBOT_PG_PASSWORD;

    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;

    } catch (PDOException $e) {
        error_log('[Chatbot Vector] PostgreSQL connection failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Initialize the vector database schema
 *
 * @return array Result with success status and message
 */
function chatbot_vector_init_schema() {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        return [
            'success' => false,
            'message' => 'Could not connect to PostgreSQL database'
        ];
    }

    try {
        // Enable pgvector extension
        $pdo->exec('CREATE EXTENSION IF NOT EXISTS vector');

        // Create FAQs table with vector columns
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS chatbot_faqs (
                id SERIAL PRIMARY KEY,
                faq_id VARCHAR(50) UNIQUE NOT NULL,
                question TEXT NOT NULL,
                answer TEXT NOT NULL,
                category VARCHAR(255),
                keywords TEXT,
                question_embedding vector(1536),
                answer_embedding vector(1536),
                combined_embedding vector(1536),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create IVFFlat index for fast similarity search
        // Note: IVFFlat requires at least some data to be present before creating
        // For small datasets (<1000), you might skip this and use exact search
        $pdo->exec('
            CREATE INDEX IF NOT EXISTS idx_faqs_faq_id ON chatbot_faqs(faq_id)
        ');

        $pdo->exec('
            CREATE INDEX IF NOT EXISTS idx_faqs_category ON chatbot_faqs(category)
        ');

        return [
            'success' => true,
            'message' => 'Vector database schema initialized successfully'
        ];

    } catch (PDOException $e) {
        error_log('[Chatbot Vector] Schema initialization failed: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Schema initialization failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Create the IVFFlat index for faster similarity search
 * Call this AFTER populating the table with data
 *
 * @param int $lists Number of lists for IVFFlat (default 100, use sqrt(n) as guideline)
 * @return array Result with success status and message
 */
function chatbot_vector_create_search_index($lists = 10) {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        return [
            'success' => false,
            'message' => 'Could not connect to PostgreSQL database'
        ];
    }

    try {
        // Drop existing index if it exists
        $pdo->exec('DROP INDEX IF EXISTS idx_faqs_combined_embedding');

        // Create IVFFlat index for cosine similarity
        // For 66 FAQs, lists = 10 is appropriate (sqrt(66) ≈ 8)
        $pdo->exec("
            CREATE INDEX idx_faqs_combined_embedding
            ON chatbot_faqs USING ivfflat (combined_embedding vector_cosine_ops)
            WITH (lists = {$lists})
        ");

        return [
            'success' => true,
            'message' => "Search index created with {$lists} lists"
        ];

    } catch (PDOException $e) {
        error_log('[Chatbot Vector] Index creation failed: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Index creation failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Check if vector search is available
 *
 * @return bool True if PostgreSQL with pgvector is configured and accessible
 */
function chatbot_vector_is_available() {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        return false;
    }

    try {
        // Check if pgvector extension is installed
        $stmt = $pdo->query("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
        return $stmt->fetchColumn() !== false;

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get vector database statistics
 *
 * @return array Stats including FAQ count, index status, etc.
 */
function chatbot_vector_get_stats() {
    $pdo = chatbot_vector_get_pg_connection();

    if (!$pdo) {
        return [
            'available' => false,
            'error' => 'PostgreSQL connection not available'
        ];
    }

    try {
        $stats = [
            'available' => true,
            'connection' => 'ok'
        ];

        // Get FAQ count
        $stmt = $pdo->query('SELECT COUNT(*) FROM chatbot_faqs');
        $stats['faq_count'] = (int) $stmt->fetchColumn();

        // Get count of FAQs with embeddings
        $stmt = $pdo->query('SELECT COUNT(*) FROM chatbot_faqs WHERE combined_embedding IS NOT NULL');
        $stats['faqs_with_embeddings'] = (int) $stmt->fetchColumn();

        // Get category breakdown
        $stmt = $pdo->query('
            SELECT category, COUNT(*) as count
            FROM chatbot_faqs
            GROUP BY category
            ORDER BY count DESC
        ');
        $stats['categories'] = $stmt->fetchAll();

        // Check if index exists
        $stmt = $pdo->query("
            SELECT 1 FROM pg_indexes
            WHERE indexname = 'idx_faqs_combined_embedding'
        ");
        $stats['search_index_exists'] = $stmt->fetchColumn() !== false;

        return $stats;

    } catch (PDOException $e) {
        return [
            'available' => false,
            'error' => $e->getMessage()
        ];
    }
}
