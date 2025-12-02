<?php
/**
 * Test Supabase Tables
 * Run this script to verify all tables exist in Supabase
 */

// Simulate WordPress environment
define('WPINC', 'wp-includes');
define('ABSPATH', dirname(__FILE__) . '/../../../../');

// Load Supabase functions
require_once dirname(__FILE__) . '/../includes/supabase/chatbot-supabase-db.php';

echo "=== Testing Supabase Tables ===\n\n";

// Get Supabase config
$url = get_option('chatbot_supabase_url');
$key = get_option('chatbot_supabase_anon_key');

// For testing, read from wp-config or use environment
if (empty($url) || empty($key)) {
    // Try to read from a config file if exists
    $config_file = dirname(__FILE__) . '/../.supabase-config.php';
    if (file_exists($config_file)) {
        include $config_file;
    }
}

echo "Supabase URL: " . ($url ? substr($url, 0, 50) . '...' : 'NOT SET') . "\n";
echo "Supabase Key: " . ($key ? 'SET (hidden)' : 'NOT SET') . "\n\n";

if (empty($url) || empty($key)) {
    echo "ERROR: Supabase credentials not configured.\n";
    echo "Please set chatbot_supabase_url and chatbot_supabase_anon_key in WordPress options.\n";
    exit(1);
}

$tables = [
    'chatbot_conversations',
    'chatbot_interactions',
    'chatbot_gap_questions',
    'chatbot_gap_clusters',
    'chatbot_faq_usage',
    'chatbot_assistants'
];

$all_ok = true;

foreach ($tables as $table) {
    $result = chatbot_supabase_request($table, 'GET', null, ['select' => 'id', 'limit' => '1']);

    if ($result === false) {
        echo "❌ Table $table: NOT FOUND or ERROR\n";
        $all_ok = false;
    } else {
        $count = is_array($result) ? count($result) : 0;
        echo "✅ Table $table: EXISTS (rows: $count)\n";
    }
}

echo "\n";

if ($all_ok) {
    echo "All tables exist in Supabase!\n";
} else {
    echo "Some tables are missing. Please run the SQL schema in Supabase SQL Editor.\n";
    echo "Schema file: includes/supabase/supabase-schema.sql\n";
}
