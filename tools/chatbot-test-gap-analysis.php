<?php
/**
 * Gap Analysis Test Data Generator
 *
 * This script creates sample gap questions to test the Gap Analysis feature.
 * Run this from WordPress Admin → Kognetiks → Tools or via direct URL access.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

function chatbot_generate_test_gap_data() {
    global $wpdb;

    // Check if user has admin permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';

    // Sample gap questions (questions that might not be in the FAQ)
    $test_questions = [
        // Router/Equipment questions (Cluster 1)
        "Can I use my own router with your service?",
        "Do I need to buy a router or do you provide one?",
        "What routers are compatible with Spectrum?",
        "Can I use my own modem?",
        "Do you provide the equipment or do I buy it?",

        // Senior/Student Discount questions (Cluster 2)
        "Do you offer senior discounts?",
        "Are there any discounts for seniors?",
        "Is there a senior citizen discount available?",
        "Do you have student discounts?",
        "Any special pricing for elderly customers?",

        // Installation Time questions (Cluster 3)
        "How long does installation take?",
        "How many hours for installation?",
        "What's the installation time?",
        "How long will the technician be at my house?",

        // Contract/Commitment questions (Cluster 4)
        "Do I need to sign a contract?",
        "Is there a contract required?",
        "Are there any contracts or commitments?",
        "Can I cancel anytime?",
        "What's the contract length?",

        // Bundle Deal questions (Cluster 5)
        "Do you have internet and phone bundles?",
        "What bundle deals do you offer?",
        "Can I get internet and TV together?",
        "Are there package deals for internet and mobile?",

        // Credit Check questions (Cluster 6)
        "Do you do credit checks?",
        "Is a credit check required?",
        "Do I need good credit to sign up?",

        // Same-day Service questions (Cluster 7)
        "Can you install today?",
        "Do you offer same-day installation?",
        "How soon can you come out?",
    ];

    $session_base = 'test_session_';
    $inserted_count = 0;
    $failed_count = 0;

    foreach ($test_questions as $index => $question) {
        // Create realistic session IDs
        $session_id = $session_base . str_pad($index % 10, 3, '0', STR_PAD_LEFT);

        // Vary the dates over the last 7 days
        $days_ago = rand(0, 6);
        $asked_date = date('Y-m-d H:i:s', strtotime("-{$days_ago} days"));

        // Low confidence scores (gap questions have < 60% confidence)
        $confidence = rand(0, 55) / 100; // 0.00 to 0.55

        $result = $wpdb->insert(
            $gap_table,
            [
                'question_text' => $question,
                'session_id' => $session_id,
                'user_id' => rand(1, 5), // Random user IDs
                'page_id' => rand(1, 3), // Random page IDs
                'faq_confidence' => $confidence,
                'faq_match_id' => null, // No good match
                'asked_date' => $asked_date,
                'is_clustered' => 0,
                'is_resolved' => 0
            ],
            ['%s', '%s', '%d', '%d', '%f', '%s', '%s', '%d', '%d']
        );

        if ($result) {
            $inserted_count++;
        } else {
            $failed_count++;
            error_log("Failed to insert test gap question: " . $question);
        }
    }

    return [
        'success' => true,
        'inserted' => $inserted_count,
        'failed' => $failed_count,
        'total' => count($test_questions)
    ];
}

function chatbot_clear_test_gap_data() {
    global $wpdb;

    // Check if user has admin permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';
    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    // Delete test data (session_id starts with 'test_session_')
    $deleted_questions = $wpdb->query(
        "DELETE FROM $gap_table WHERE session_id LIKE 'test_session_%'"
    );

    // Clear all clusters (in case we ran analysis on test data)
    $deleted_clusters = $wpdb->query(
        "TRUNCATE TABLE $cluster_table"
    );

    return [
        'success' => true,
        'deleted_questions' => $deleted_questions,
        'deleted_clusters' => $deleted_clusters
    ];
}

// Handle AJAX request to generate test data
function chatbot_ajax_generate_test_gap_data() {
    check_ajax_referer('chatbot_test_gap_data', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $result = chatbot_generate_test_gap_data();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_chatbot_generate_test_gap_data', 'chatbot_ajax_generate_test_gap_data');

// Handle AJAX request to clear test data
function chatbot_ajax_clear_test_gap_data() {
    check_ajax_referer('chatbot_test_gap_data', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $result = chatbot_clear_test_gap_data();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_chatbot_clear_test_gap_data', 'chatbot_ajax_clear_test_gap_data');

// Add admin page UI for testing
function chatbot_gap_test_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    ?>
    <div class="wrap">
        <h1>🧪 Gap Analysis Test Tool</h1>

        <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 4px; max-width: 800px; margin-top: 20px;">
            <h2>Test the Gap Analysis Feature</h2>

            <p>This tool helps you test the Gap Analysis feature without waiting for real user questions.</p>

            <div style="background: #f0f9ff; border: 1px solid #3b82f6; border-radius: 4px; padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0;">📋 Step-by-Step Testing Instructions:</h3>
                <ol style="line-height: 1.8;">
                    <li><strong>Generate Test Data:</strong> Click the button below to create 30+ sample gap questions</li>
                    <li><strong>View the Data:</strong> Go to <strong>Reporting → Gap Analysis</strong> section</li>
                    <li><strong>Run AI Analysis:</strong> Click "Run Analysis" button on the Reporting page</li>
                    <li><strong>See the Results:</strong> AI will cluster similar questions and suggest FAQs</li>
                    <li><strong>Clean Up:</strong> When done testing, click "Clear Test Data" below</li>
                </ol>
            </div>

            <div style="margin: 20px 0; padding: 15px; background: #fffbeb; border: 1px solid #f59e0b; border-radius: 4px;">
                <strong>⚠️ Note:</strong> Running AI analysis will use ~$0.01-0.05 in Gemini API credits.
            </div>

            <div style="margin-top: 30px;">
                <button id="generate-test-data-btn" class="button button-primary button-hero" style="margin-right: 10px;">
                    ✨ Generate Test Data (30+ Questions)
                </button>

                <button id="clear-test-data-btn" class="button button-secondary button-hero">
                    🗑️ Clear Test Data
                </button>
            </div>

            <div id="test-result" style="margin-top: 20px; padding: 15px; border-radius: 4px; display: none;"></div>
        </div>

        <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 4px; max-width: 800px; margin-top: 20px;">
            <h3>Sample Test Questions Generated:</h3>
            <p>The test data includes realistic questions grouped into clusters:</p>
            <ul style="line-height: 1.8;">
                <li><strong>Router/Equipment:</strong> "Can I use my own router?", "Do you provide equipment?" (5 questions)</li>
                <li><strong>Senior/Student Discounts:</strong> "Do you offer senior discounts?" (5 questions)</li>
                <li><strong>Installation Time:</strong> "How long does installation take?" (4 questions)</li>
                <li><strong>Contracts:</strong> "Do I need to sign a contract?" (5 questions)</li>
                <li><strong>Bundle Deals:</strong> "What bundle deals do you offer?" (4 questions)</li>
                <li><strong>Credit Check:</strong> "Do you do credit checks?" (3 questions)</li>
                <li><strong>Same-day Service:</strong> "Can you install today?" (3 questions)</li>
            </ul>
            <p><em>Total: 30+ questions across 7 potential clusters</em></p>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#generate-test-data-btn').on('click', function() {
            const btn = $(this);
            const resultDiv = $('#test-result');

            btn.prop('disabled', true).text('⏳ Generating...');
            resultDiv.hide();

            $.post(ajaxurl, {
                action: 'chatbot_generate_test_gap_data',
                nonce: '<?php echo wp_create_nonce('chatbot_test_gap_data'); ?>'
            }, function(response) {
                btn.prop('disabled', false).html('✨ Generate Test Data (30+ Questions)');

                if (response.success) {
                    resultDiv
                        .css('background', '#d1fae5')
                        .css('border', '1px solid #10b981')
                        .html(`
                            <strong style="color: #065f46;">✅ Success!</strong><br>
                            Generated ${response.data.inserted} test gap questions.<br>
                            <br>
                            <strong>Next Steps:</strong><br>
                            1. Go to <strong>Kognetiks → Reporting</strong> tab<br>
                            2. Scroll to <strong>"Gap Analysis"</strong> section<br>
                            3. Click <strong>"🤖 Run Analysis"</strong> button<br>
                            4. Wait 10-30 seconds for AI to cluster the questions<br>
                            5. See AI-suggested FAQs!
                        `)
                        .show();
                } else {
                    resultDiv
                        .css('background', '#fee2e2')
                        .css('border', '1px solid #ef4444')
                        .html(`<strong style="color: #991b1b;">❌ Error:</strong> ${response.data || 'Unknown error'}`)
                        .show();
                }
            }).fail(function() {
                btn.prop('disabled', false).html('✨ Generate Test Data (30+ Questions)');
                resultDiv
                    .css('background', '#fee2e2')
                    .css('border', '1px solid #ef4444')
                    .html('<strong style="color: #991b1b;">❌ Error:</strong> AJAX request failed')
                    .show();
            });
        });

        $('#clear-test-data-btn').on('click', function() {
            if (!confirm('Clear all test gap data? This will remove test questions and clusters.')) {
                return;
            }

            const btn = $(this);
            const resultDiv = $('#test-result');

            btn.prop('disabled', true).text('⏳ Clearing...');
            resultDiv.hide();

            $.post(ajaxurl, {
                action: 'chatbot_clear_test_gap_data',
                nonce: '<?php echo wp_create_nonce('chatbot_test_gap_data'); ?>'
            }, function(response) {
                btn.prop('disabled', false).html('🗑️ Clear Test Data');

                if (response.success) {
                    resultDiv
                        .css('background', '#d1fae5')
                        .css('border', '1px solid #10b981')
                        .html(`
                            <strong style="color: #065f46;">✅ Cleared!</strong><br>
                            Deleted ${response.data.deleted_questions} test questions<br>
                            Deleted ${response.data.deleted_clusters} clusters
                        `)
                        .show();
                } else {
                    resultDiv
                        .css('background', '#fee2e2')
                        .css('border', '1px solid #ef4444')
                        .html(`<strong style="color: #991b1b;">❌ Error:</strong> ${response.data || 'Unknown error'}`)
                        .show();
                }
            }).fail(function() {
                btn.prop('disabled', false).html('🗑️ Clear Test Data');
                resultDiv
                    .css('background', '#fee2e2')
                    .css('border', '1px solid #ef4444')
                    .html('<strong style="color: #991b1b;">❌ Error:</strong> AJAX request failed')
                    .show();
            });
        });
    });
    </script>
    <?php
}

// Add menu item for the test page
function chatbot_gap_test_admin_menu() {
    add_submenu_page(
        'chatbot-chatgpt',
        'Gap Analysis Test',
        '🧪 Gap Test',
        'manage_options',
        'chatbot-gap-test',
        'chatbot_gap_test_admin_page'
    );
}
add_action('admin_menu', 'chatbot_gap_test_admin_menu', 100);
