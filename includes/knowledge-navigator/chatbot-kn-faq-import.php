<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - FAQ CSV Import - Ver 2.3.7
 *
 * This file contains the code for importing FAQ entries from CSV files.
 * FAQs are stored as JSON in the plugin folder (no database required).
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Get the FAQ data file path
function chatbot_faq_get_data_path() {
    $plugin_dir = plugin_dir_path(dirname(__FILE__, 2));
    $data_dir = $plugin_dir . 'data/';

    // Create data directory if it doesn't exist
    if (!file_exists($data_dir)) {
        wp_mkdir_p($data_dir);
    }

    return $data_dir . 'faqs.json';
}

// Load FAQs from JSON file
function chatbot_faq_load() {
    $file_path = chatbot_faq_get_data_path();

    if (!file_exists($file_path)) {
        return [];
    }

    $content = file_get_contents($file_path);
    if ($content === false) {
        return [];
    }

    $faqs = json_decode($content, true);
    return is_array($faqs) ? $faqs : [];
}

// Save FAQs to JSON file
function chatbot_faq_save($faqs) {
    $file_path = chatbot_faq_get_data_path();

    $json = json_encode($faqs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($file_path, $json) !== false;
}

// Handle CSV file upload
function chatbot_faq_handle_csv_upload() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Verify nonce
    if (!isset($_POST['chatbot_faq_import_nonce']) ||
        !wp_verify_nonce($_POST['chatbot_faq_import_nonce'], 'chatbot_faq_import')) {
        wp_die('Security check failed');
    }

    $redirect_url = admin_url('admin.php?page=chatbot-chatgpt&tab=kn_acquire');

    // Check if file was uploaded
    if (!isset($_FILES['faq_csv_file']) || $_FILES['faq_csv_file']['error'] !== UPLOAD_ERR_OK) {
        set_transient('chatbot_faq_import_message', [
            'type' => 'error',
            'message' => 'Error uploading file. Please try again.'
        ], 60);
        wp_redirect($redirect_url);
        exit;
    }

    $file = $_FILES['faq_csv_file'];

    // Validate file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        set_transient('chatbot_faq_import_message', [
            'type' => 'error',
            'message' => 'Please upload a CSV file.'
        ], 60);
        wp_redirect($redirect_url);
        exit;
    }

    // Parse and import CSV
    $result = chatbot_faq_import_csv($file['tmp_name']);

    if ($result['success']) {
        set_transient('chatbot_faq_import_message', [
            'type' => 'success',
            'message' => sprintf('Successfully imported %d FAQ entries!', $result['count'])
        ], 60);
    } else {
        set_transient('chatbot_faq_import_message', [
            'type' => 'error',
            'message' => 'Error importing CSV: ' . $result['message']
        ], 60);
    }

    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_chatbot_faq_import_csv', 'chatbot_faq_handle_csv_upload');

// Parse and import CSV file
function chatbot_faq_import_csv($file_path) {
    // Open file
    $handle = fopen($file_path, 'r');
    if ($handle === false) {
        return ['success' => false, 'message' => 'Could not open file'];
    }

    // Read header row
    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return ['success' => false, 'message' => 'Could not read CSV header'];
    }

    // Normalize header names
    $header = array_map('strtolower', array_map('trim', $header));

    // Find column indexes
    $question_idx = array_search('question', $header);
    $answer_idx = array_search('answer', $header);
    $category_idx = array_search('category', $header);

    if ($question_idx === false || $answer_idx === false) {
        fclose($handle);
        return ['success' => false, 'message' => 'CSV must have "question" and "answer" columns'];
    }

    // Check if we should clear existing entries
    $clear_existing = isset($_POST['clear_existing']) && $_POST['clear_existing'] === '1';

    // Load existing FAQs or start fresh
    $faqs = $clear_existing ? [] : chatbot_faq_load();

    // Import rows
    $count = 0;

    while (($row = fgetcsv($handle)) !== false) {
        // Skip empty rows
        if (empty($row) || (count($row) === 1 && empty($row[0]))) {
            continue;
        }

        $question = isset($row[$question_idx]) ? trim($row[$question_idx]) : '';
        $answer = isset($row[$answer_idx]) ? trim($row[$answer_idx]) : '';
        $category = ($category_idx !== false && isset($row[$category_idx])) ? trim($row[$category_idx]) : '';

        // Skip if question or answer is empty
        if (empty($question) || empty($answer)) {
            continue;
        }

        // Generate keywords from question
        $keywords = chatbot_faq_generate_keywords($question);

        // Add to FAQs array
        $faqs[] = [
            'id' => uniqid(),
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'keywords' => $keywords,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $count++;
    }

    fclose($handle);

    // Save to JSON file
    if (!chatbot_faq_save($faqs)) {
        return ['success' => false, 'message' => 'Failed to save FAQs to file'];
    }

    return [
        'success' => true,
        'count' => $count
    ];
}

// Generate keywords from question text
function chatbot_faq_generate_keywords($text) {
    // Convert to lowercase
    $text = strtolower($text);

    // Remove punctuation
    $text = preg_replace('/[^\w\s]/', '', $text);

    // Split into words
    $words = preg_split('/\s+/', $text);

    // Remove common stop words
    $stop_words = ['a', 'an', 'the', 'is', 'are', 'was', 'were', 'what', 'how', 'why',
                   'when', 'where', 'who', 'which', 'do', 'does', 'did', 'can', 'could',
                   'would', 'should', 'i', 'you', 'your', 'my', 'me', 'we', 'they', 'it',
                   'to', 'for', 'of', 'in', 'on', 'at', 'by', 'with', 'and', 'or', 'but'];

    $keywords = array_diff($words, $stop_words);
    $keywords = array_filter($keywords, function($word) {
        return strlen($word) > 2;
    });

    return implode(' ', $keywords);
}

// Search FAQs for matching answer
function chatbot_faq_search($query, $return_score = false) {
    $faqs = chatbot_faq_load();

    if (empty($faqs)) {
        return $return_score ? ['match' => null, 'score' => 0, 'confidence' => 'none'] : null;
    }

    // Normalize query
    $query_lower = strtolower($query);

    // Generate keywords from query
    $query_keywords = chatbot_faq_generate_keywords($query);
    $query_words = array_filter(explode(' ', $query_keywords));

    if (empty($query_words)) {
        return $return_score ? ['match' => null, 'score' => 0, 'confidence' => 'none'] : null;
    }

    $best_match = null;
    $best_score = 0;
    $best_match_type = 'keyword'; // keyword, phrase, or exact

    foreach ($faqs as $faq) {
        $score = 0;
        $match_type = 'keyword';

        // TIER 1: Exact question match (highest confidence) - 100% score
        $faq_question_lower = strtolower($faq['question']);
        if ($faq_question_lower === $query_lower) {
            $score = 1.0;
            $match_type = 'exact';
        }
        // TIER 2: Question contains query or vice versa (high confidence) - 90% score
        else if (strlen($query_lower) > 10 && strpos($faq_question_lower, $query_lower) !== false) {
            $score = 0.9;
            $match_type = 'phrase';
        }
        else if (strlen($query_lower) > 10 && strpos($query_lower, $faq_question_lower) !== false) {
            $score = 0.85;
            $match_type = 'phrase';
        }
        // TIER 3: Keyword matching with weighted scoring
        else {
            $faq_keywords = explode(' ', $faq['keywords']);
            $faq_question_keywords = chatbot_faq_generate_keywords($faq['question']);
            $faq_question_words = explode(' ', $faq_question_keywords);

            $keyword_matches = 0;
            $weighted_matches = 0;

            foreach ($query_words as $word) {
                $word_len = strlen($word);

                // Check question words first (higher weight)
                foreach ($faq_question_words as $q_word) {
                    if ($word === $q_word) {
                        $weighted_matches += 2.0; // Exact match in question = double weight
                        $keyword_matches++;
                        continue 2;
                    } else if ($word_len > 4 && (strpos($q_word, $word) !== false || strpos($word, $q_word) !== false)) {
                        $weighted_matches += 1.5; // Partial match in question
                        $keyword_matches++;
                        continue 2;
                    }
                }

                // Then check keywords
                foreach ($faq_keywords as $faq_word) {
                    if ($word === $faq_word) {
                        $weighted_matches += 1.0; // Exact match in keywords
                        $keyword_matches++;
                        break;
                    } else if ($word_len > 4 && (strpos($faq_word, $word) !== false || strpos($word, $faq_word) !== false)) {
                        $weighted_matches += 0.7; // Partial match in keywords
                        $keyword_matches++;
                        break;
                    }
                }
            }

            // Calculate weighted score
            $max_possible_score = count($query_words) * 2.0;
            $score = $max_possible_score > 0 ? min(0.8, $weighted_matches / $max_possible_score) : 0;

            // Boost score if most query words are matched
            if ($keyword_matches >= count($query_words) * 0.8) {
                $score *= 1.15; // 15% boost for comprehensive match
            }

            $match_type = 'keyword';
        }

        // Update best match
        if ($score > $best_score) {
            $best_score = $score;
            $best_match = $faq;
            $best_match_type = $match_type;
        }
    }

    // Only return matches above minimum threshold (20%)
    if ($best_score < 0.2) {
        return $return_score ? ['match' => null, 'score' => 0, 'confidence' => 'none'] : null;
    }

    // Determine confidence level
    $confidence = 'low';
    if ($best_score >= 0.8) {
        $confidence = 'very_high'; // 80%+ = return FAQ directly (no AI)
    } else if ($best_score >= 0.6) {
        $confidence = 'high'; // 60-80% = use AI minimally
    } else if ($best_score >= 0.4) {
        $confidence = 'medium'; // 40-60% = use AI with context
    } else {
        $confidence = 'low'; // 20-40% = mostly AI
    }

    if ($return_score) {
        return [
            'match' => $best_match,
            'score' => round($best_score, 3),
            'confidence' => $confidence,
            'match_type' => $best_match_type
        ];
    }

    return $best_match;
}

// Get all FAQ entries
function chatbot_faq_get_all() {
    $faqs = chatbot_faq_load();

    // Convert to objects for compatibility with existing UI
    return array_map(function($faq) {
        return (object) $faq;
    }, $faqs);
}

// Get FAQ count
function chatbot_faq_get_count() {
    $faqs = chatbot_faq_load();
    return count($faqs);
}

// Delete FAQ entry by ID
function chatbot_faq_delete($id) {
    $faqs = chatbot_faq_load();

    $faqs = array_filter($faqs, function($faq) use ($id) {
        return $faq['id'] !== $id;
    });

    return chatbot_faq_save(array_values($faqs));
}

// Get top N categories by FAQ count
function chatbot_faq_get_top_categories($limit = 4) {
    $faqs = chatbot_faq_load();

    if (empty($faqs)) {
        return [];
    }

    // Count FAQs per category
    $category_counts = [];
    foreach ($faqs as $faq) {
        $category = !empty($faq['category']) ? $faq['category'] : 'General';
        if (!isset($category_counts[$category])) {
            $category_counts[$category] = 0;
        }
        $category_counts[$category]++;
    }

    // Sort by count descending
    arsort($category_counts);

    // Get top N categories
    $top_categories = array_slice($category_counts, 0, $limit, true);

    // Format as array with name and count
    $result = [];
    foreach ($top_categories as $name => $count) {
        $result[] = [
            'name' => $name,
            'count' => $count
        ];
    }

    return $result;
}

// Get top N questions for a specific category
function chatbot_faq_get_category_questions($category, $limit = 3) {
    $faqs = chatbot_faq_load();

    if (empty($faqs)) {
        return [];
    }

    // Filter by category
    $category_faqs = array_filter($faqs, function($faq) use ($category) {
        $faq_category = !empty($faq['category']) ? $faq['category'] : 'General';
        return $faq_category === $category;
    });

    // Get top N questions
    $category_faqs = array_slice($category_faqs, 0, $limit);

    // Return just question and answer
    $result = [];
    foreach ($category_faqs as $faq) {
        $result[] = [
            'question' => $faq['question'],
            'answer' => $faq['answer']
        ];
    }

    return $result;
}

// Get category buttons data for frontend
function chatbot_faq_get_buttons_data() {
    $categories = chatbot_faq_get_top_categories(4);

    $buttons_data = [];
    foreach ($categories as $category) {
        $questions = chatbot_faq_get_category_questions($category['name'], 3);
        $buttons_data[] = [
            'name' => $category['name'],
            'count' => $category['count'],
            'questions' => $questions
        ];
    }

    return $buttons_data;
}

// Download sample CSV template
function chatbot_faq_download_template() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="faq-template.csv"');

    $output = fopen('php://output', 'w');

    // Header row
    fputcsv($output, ['question', 'answer', 'category']);

    // Sample rows
    fputcsv($output, ['What are your store hours?', 'We are open Monday-Friday 9am-6pm, Saturday 10am-4pm.', 'Store Info']);
    fputcsv($output, ['What are the Spectrum internet options?', 'Spectrum offers plans starting at $49.99/month with speeds up to 300 Mbps.', 'Internet Plans']);
    fputcsv($output, ['How do I check my bill?', 'You can check your bill by logging into your provider account or calling their customer service.', 'Billing']);
    fputcsv($output, ['How do I reboot my modem?', 'Unplug your modem from power, wait 30 seconds, then plug it back in. Wait 2-3 minutes for it to fully restart.', 'Troubleshooting']);

    fclose($output);
    exit;
}
add_action('admin_post_chatbot_faq_download_template', 'chatbot_faq_download_template');

// Add new FAQ entry - Ver 2.3.7
function chatbot_faq_add($question, $answer, $category = '') {
    if (empty($question) || empty($answer)) {
        return ['success' => false, 'message' => 'Question and answer are required'];
    }

    $faqs = chatbot_faq_load();

    // Generate keywords
    $keywords = chatbot_faq_generate_keywords($question);

    // Add new FAQ
    $faqs[] = [
        'id' => uniqid(),
        'question' => $question,
        'answer' => $answer,
        'category' => $category,
        'keywords' => $keywords,
        'created_at' => date('Y-m-d H:i:s')
    ];

    if (chatbot_faq_save($faqs)) {
        return ['success' => true, 'message' => 'FAQ added successfully'];
    }

    return ['success' => false, 'message' => 'Failed to save FAQ'];
}

// Update FAQ entry by ID - Ver 2.3.7
function chatbot_faq_update($id, $question, $answer, $category = '') {
    if (empty($question) || empty($answer)) {
        return ['success' => false, 'message' => 'Question and answer are required'];
    }

    $faqs = chatbot_faq_load();
    $updated = false;

    foreach ($faqs as $key => $faq) {
        if ($faq['id'] === $id) {
            // Regenerate keywords from new question
            $keywords = chatbot_faq_generate_keywords($question);

            $faqs[$key] = [
                'id' => $id,
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'keywords' => $keywords,
                'created_at' => $faq['created_at']
            ];
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        return ['success' => false, 'message' => 'FAQ not found'];
    }

    if (chatbot_faq_save($faqs)) {
        return ['success' => true, 'message' => 'FAQ updated successfully'];
    }

    return ['success' => false, 'message' => 'Failed to save FAQ'];
}

// Get single FAQ by ID - Ver 2.3.7
function chatbot_faq_get_by_id($id) {
    $faqs = chatbot_faq_load();

    foreach ($faqs as $faq) {
        if ($faq['id'] === $id) {
            return $faq;
        }
    }

    return null;
}

// AJAX: Add FAQ
function chatbot_faq_ajax_add() {
    check_ajax_referer('chatbot_faq_manage', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $question = sanitize_textarea_field($_POST['question'] ?? '');
    $answer = sanitize_textarea_field($_POST['answer'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');

    $result = chatbot_faq_add($question, $answer, $category);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_chatbot_faq_add', 'chatbot_faq_ajax_add');

// AJAX: Update FAQ
function chatbot_faq_ajax_update() {
    check_ajax_referer('chatbot_faq_manage', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $id = sanitize_text_field($_POST['id'] ?? '');
    $question = sanitize_textarea_field($_POST['question'] ?? '');
    $answer = sanitize_textarea_field($_POST['answer'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');

    $result = chatbot_faq_update($id, $question, $answer, $category);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_chatbot_faq_update', 'chatbot_faq_ajax_update');

// AJAX: Delete FAQ
function chatbot_faq_ajax_delete() {
    check_ajax_referer('chatbot_faq_manage', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $id = sanitize_text_field($_POST['id'] ?? '');

    if (chatbot_faq_delete($id)) {
        wp_send_json_success(['message' => 'FAQ deleted successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete FAQ']);
    }
}
add_action('wp_ajax_chatbot_faq_delete', 'chatbot_faq_ajax_delete');

// AJAX: Get FAQ by ID
function chatbot_faq_ajax_get() {
    check_ajax_referer('chatbot_faq_manage', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $id = sanitize_text_field($_POST['id'] ?? '');
    $faq = chatbot_faq_get_by_id($id);

    if ($faq) {
        wp_send_json_success(['faq' => $faq]);
    } else {
        wp_send_json_error(['message' => 'FAQ not found']);
    }
}
add_action('wp_ajax_chatbot_faq_get', 'chatbot_faq_ajax_get');
