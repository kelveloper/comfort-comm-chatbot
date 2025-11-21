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
function chatbot_faq_search($query) {
    $faqs = chatbot_faq_load();

    if (empty($faqs)) {
        return null;
    }

    // Generate keywords from query
    $query_keywords = chatbot_faq_generate_keywords($query);
    $query_words = array_filter(explode(' ', $query_keywords));

    if (empty($query_words)) {
        return null;
    }

    $best_match = null;
    $best_score = 0;

    foreach ($faqs as $faq) {
        $faq_keywords = explode(' ', $faq['keywords']);

        // Count matching keywords
        $matches = 0;
        foreach ($query_words as $word) {
            foreach ($faq_keywords as $faq_word) {
                // Check for partial match
                if (strpos($faq_word, $word) !== false || strpos($word, $faq_word) !== false) {
                    $matches++;
                    break;
                }
            }
        }

        // Calculate score as percentage of query words matched
        $score = count($query_words) > 0 ? $matches / count($query_words) : 0;

        if ($score > $best_score && $score >= 0.3) { // At least 30% match
            $best_score = $score;
            $best_match = $faq;
        }
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
