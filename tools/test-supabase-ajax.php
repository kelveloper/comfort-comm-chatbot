<?php
/**
 * Supabase Test AJAX Handler
 *
 * Add this to test Supabase tables from WordPress admin
 * Access via: /wp-admin/admin-ajax.php?action=test_supabase_tables
 */

// Register AJAX action
add_action('wp_ajax_test_supabase_tables', 'chatbot_test_supabase_tables_ajax');

function chatbot_test_supabase_tables_ajax() {
    // Check if user is admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    $results = [];
    $all_ok = true;

    // Test configuration
    $is_configured = chatbot_supabase_is_configured();
    $results['configuration'] = [
        'status' => $is_configured ? 'OK' : 'ERROR',
        'message' => $is_configured ? 'Supabase is configured' : 'Supabase not configured'
    ];

    if (!$is_configured) {
        wp_send_json_error([
            'message' => 'Supabase not configured',
            'results' => $results
        ]);
        return;
    }

    // Tables to test
    $tables = [
        'chatbot_conversations' => 'chatbot_supabase_get_recent_conversations',
        'chatbot_interactions' => 'chatbot_supabase_get_interaction_counts',
        'chatbot_gap_questions' => 'chatbot_supabase_get_gap_questions',
        'chatbot_gap_clusters' => 'chatbot_supabase_get_gap_clusters',
        'chatbot_faq_usage' => null,
        'chatbot_assistants' => 'chatbot_supabase_get_assistants'
    ];

    foreach ($tables as $table => $function) {
        // Direct API test
        $result = chatbot_supabase_request($table, 'GET', null, ['select' => 'id', 'limit' => '1']);

        if ($result === false || (is_array($result) && isset($result['error']))) {
            $results[$table] = [
                'status' => 'ERROR',
                'message' => 'Table not found or API error',
                'error' => is_array($result) && isset($result['error']) ? $result['error'] : 'Unknown error'
            ];
            $all_ok = false;
        } else {
            $count = is_array($result) ? count($result) : 0;
            $results[$table] = [
                'status' => 'OK',
                'message' => "Table exists",
                'row_sample' => $count
            ];

            // Test the helper function if available
            if ($function && function_exists($function)) {
                $results[$table]['function'] = $function;
                $results[$table]['function_exists'] = true;
            } elseif ($function) {
                $results[$table]['function'] = $function;
                $results[$table]['function_exists'] = false;
            }
        }
    }

    // Test CRUD operations on gap_clusters
    $crud_test = chatbot_test_gap_clusters_crud();
    $results['gap_clusters_crud_test'] = $crud_test;

    if ($all_ok) {
        wp_send_json_success([
            'message' => 'All tables exist in Supabase',
            'results' => $results
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Some tables are missing',
            'results' => $results
        ]);
    }
}

/**
 * Test CRUD operations on gap_clusters
 */
function chatbot_test_gap_clusters_crud() {
    $results = [];

    // 1. CREATE test
    $test_data = [
        'cluster_name' => 'TEST_CLUSTER_' . time(),
        'cluster_description' => 'Test cluster for verification',
        'question_count' => 5,
        'sample_questions' => json_encode(['Test question 1', 'Test question 2']),
        'suggested_faq' => json_encode(['question' => 'Test FAQ?', 'answer' => 'Test answer']),
        'action_type' => 'create',
        'priority_score' => 0.75,
        'status' => 'new'
    ];

    if (function_exists('chatbot_supabase_create_gap_cluster')) {
        $create_result = chatbot_supabase_create_gap_cluster($test_data);
        if ($create_result && isset($create_result['id'])) {
            $results['create'] = ['status' => 'OK', 'id' => $create_result['id']];
            $test_id = $create_result['id'];

            // 2. READ test
            if (function_exists('chatbot_supabase_get_gap_cluster')) {
                $read_result = chatbot_supabase_get_gap_cluster($test_id);
                if ($read_result && isset($read_result['id'])) {
                    $results['read'] = ['status' => 'OK', 'data' => $read_result['cluster_name']];
                } else {
                    $results['read'] = ['status' => 'ERROR', 'message' => 'Could not read created cluster'];
                }
            } else {
                $results['read'] = ['status' => 'SKIP', 'message' => 'Function not found'];
            }

            // 3. UPDATE test
            if (function_exists('chatbot_supabase_update_gap_cluster')) {
                $update_result = chatbot_supabase_update_gap_cluster($test_id, ['status' => 'reviewed']);
                if ($update_result) {
                    $results['update'] = ['status' => 'OK'];
                } else {
                    $results['update'] = ['status' => 'ERROR', 'message' => 'Could not update cluster'];
                }
            } else {
                $results['update'] = ['status' => 'SKIP', 'message' => 'Function not found'];
            }

            // 4. DELETE test
            if (function_exists('chatbot_supabase_delete_gap_cluster')) {
                $delete_result = chatbot_supabase_delete_gap_cluster($test_id);
                if ($delete_result !== false) {
                    $results['delete'] = ['status' => 'OK'];
                } else {
                    $results['delete'] = ['status' => 'ERROR', 'message' => 'Could not delete cluster'];
                }
            } else {
                $results['delete'] = ['status' => 'SKIP', 'message' => 'Function not found'];
            }
        } else {
            $results['create'] = ['status' => 'ERROR', 'message' => 'Failed to create test cluster', 'result' => $create_result];
        }
    } else {
        $results['create'] = ['status' => 'SKIP', 'message' => 'chatbot_supabase_create_gap_cluster function not found'];
    }

    return $results;
}
