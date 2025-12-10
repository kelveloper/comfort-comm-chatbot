<?php
/**
 * Chatbot Vector Search - Integration with Existing Chatbot
 *
 * This file integrates vector search into the existing chatbot logic.
 * Vector search is REQUIRED - no fallback to keyword search.
 *
 * @package comfort-comm-chatbot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Include vector search components
require_once plugin_dir_path(__FILE__) . 'chatbot-vector-search.php';

/**
 * Check if vector search is properly configured
 *
 * @return array Status with details
 */
function chatbot_vector_check_status() {
    $status = [
        'configured' => false,
        'connected' => false,
        'pgvector_installed' => false,
        'faqs_migrated' => false,
        'ready' => false,
        'errors' => []
    ];

    // Check if Supabase config exists (from admin settings or wp-config.php)
    $config = function_exists('chatbot_supabase_get_config') ? chatbot_supabase_get_config() : [];

    if (empty($config['project_url']) && empty($config['db_host'])) {
        $status['errors'][] = 'Supabase not configured. Go to Steven-Bot → Setup to configure.';
        return $status;
    }

    if (empty($config['anon_key'])) {
        $status['errors'][] = 'Supabase Anon Key not configured. Go to Steven-Bot → Setup to configure.';
        return $status;
    }
    $status['configured'] = true;

    // Test connection using REST API (doesn't need direct DB access)
    if (function_exists('chatbot_supabase_test_connection')) {
        $test = chatbot_supabase_test_connection($config);
        if (!$test['success']) {
            $status['errors'][] = $test['message'];
            return $status;
        }
    }
    $status['connected'] = true;

    // Check pgvector extension
    if (!chatbot_vector_is_available()) {
        $status['errors'][] = 'pgvector extension is not installed in PostgreSQL';
        return $status;
    }
    $status['pgvector_installed'] = true;

    // Check if FAQs are migrated
    $stats = chatbot_vector_get_stats();
    if (!isset($stats['faqs_with_embeddings']) || $stats['faqs_with_embeddings'] === 0) {
        $status['errors'][] = 'No FAQs with embeddings found. Run migration first.';
        return $status;
    }
    $status['faqs_migrated'] = true;
    $status['faq_count'] = $stats['faq_count'];
    $status['faqs_with_embeddings'] = $stats['faqs_with_embeddings'];

    // All checks passed
    $status['ready'] = true;
    return $status;
}

/**
 * Enhanced FAQ search - VECTOR ONLY
 *
 * This function replaces chatbot_faq_search() entirely.
 * No fallback to keyword search.
 *
 * @param string $query User's question
 * @param bool $return_score Whether to return score information
 * @param string|null $session_id Session ID for analytics
 * @param int|null $user_id User ID for analytics
 * @param int|null $page_id Page ID for analytics
 * @return array|null Search result
 */
function chatbot_enhanced_faq_search($query, $return_score = false, $session_id = null, $user_id = null, $page_id = null) {
    // Use vector search only - no fallback
    return chatbot_vector_faq_search($query, $return_score, $session_id, $user_id, $page_id);
}

/**
 * Get FAQ answer with confidence-based response strategy
 *
 * Returns the FAQ answer directly for high confidence matches,
 * or provides context to AI for lower confidence matches.
 *
 * @param string $query User's question
 * @param array $options Options array:
 *   - session_id: Session ID
 *   - user_id: User ID
 *   - page_id: Page ID
 *   - include_related: Include related questions
 * @return array Response with answer and metadata
 */
function chatbot_get_faq_response($query, $options = []) {
    $session_id = $options['session_id'] ?? null;
    $user_id = $options['user_id'] ?? null;
    $page_id = $options['page_id'] ?? null;
    $include_related = $options['include_related'] ?? false;

    // Search for matching FAQ using vector search only
    $result = chatbot_enhanced_faq_search($query, true, $session_id, $user_id, $page_id);

    // No match found
    if (!$result || !$result['match']) {
        return [
            'found' => false,
            'answer' => null,
            'confidence' => 'none',
            'score' => 0,
            'use_ai' => true,
            'context' => null
        ];
    }

    $response = [
        'found' => true,
        'faq_id' => $result['match']['id'] ?? null,
        'question' => $result['match']['question'],
        'answer' => $result['match']['answer'],
        'category' => $result['match']['category'] ?? '',
        'confidence' => $result['confidence'],
        'score' => $result['score'],
        'match_type' => $result['match_type'] ?? 'vector'
    ];

    // Determine response strategy based on confidence
    switch ($result['confidence']) {
        case 'very_high':
            // 85%+ - Return FAQ directly, no AI needed
            $response['use_ai'] = false;
            $response['strategy'] = 'direct';
            break;

        case 'high':
            // 75-85% - Return FAQ with minimal AI enhancement
            $response['use_ai'] = true;
            $response['strategy'] = 'enhance';
            $response['ai_instruction'] = 'Use this FAQ answer as the primary response. You may slightly rephrase for naturalness but keep the core information unchanged.';
            break;

        case 'medium':
            // 65-75% - Use FAQ as context, AI provides response
            $response['use_ai'] = true;
            $response['strategy'] = 'contextual';
            $response['ai_instruction'] = 'This FAQ may be relevant to the user\'s question. Use it as context but formulate your own response that addresses their specific query.';
            break;

        case 'low':
            // 50-65% - FAQ is a weak match, AI should handle
            $response['use_ai'] = true;
            $response['strategy'] = 'fallback';
            $response['ai_instruction'] = 'This FAQ has low relevance to the user\'s question. Consider it as background information only. Provide a helpful response based on your knowledge.';
            break;

        default:
            // Below threshold - No usable FAQ match
            $response['found'] = false;
            $response['use_ai'] = true;
            $response['strategy'] = 'ai_only';
            break;
    }

    // Get related questions if requested and we have a match
    if ($include_related && $response['found'] && isset($response['faq_id'])) {
        $related = chatbot_vector_get_similar_faqs($response['faq_id'], 3);
        $response['related_questions'] = array_map(function($faq) {
            return [
                'question' => $faq['question'],
                'category' => $faq['category']
            ];
        }, $related);
    }

    return $response;
}

/**
 * Build AI context with FAQ information
 *
 * Creates a context string for the AI model that includes relevant FAQ data.
 *
 * @param string $query User's question
 * @param int $max_faqs Maximum number of FAQs to include in context
 * @return string Context string for AI prompt
 */
function chatbot_build_faq_context($query, $max_faqs = 3) {
    $results = chatbot_vector_search($query, [
        'threshold' => CHATBOT_VECTOR_THRESHOLD_LOW,
        'limit' => $max_faqs,
        'return_scores' => true
    ]);

    if (!$results['success'] || empty($results['results'])) {
        return '';
    }

    $context_parts = ["Here are relevant FAQs that may help answer the user's question:\n"];

    foreach ($results['results'] as $i => $faq) {
        $num = $i + 1;
        $confidence_pct = round($faq['similarity'] * 100);
        $context_parts[] = "FAQ #{$num} (Relevance: {$confidence_pct}%):";
        $context_parts[] = "Q: {$faq['question']}";
        $context_parts[] = "A: {$faq['answer']}";
        $context_parts[] = "";
    }

    $context_parts[] = "Use this information to help answer the user's question. If the FAQs are highly relevant, base your answer on them. If not very relevant, use your general knowledge.";

    return implode("\n", $context_parts);
}

/**
 * Admin notice if vector search is not configured
 * Only shows on the Knowledge Base tab to avoid cluttering other pages
 */
function chatbot_vector_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Only show on Knowledge Base tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    if ($current_page !== 'steven-bot' || $current_tab !== 'kn_acquire') {
        return;
    }

    $status = chatbot_vector_check_status();

    if (!$status['ready']) {
        $errors = implode('<br>', $status['errors']);
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Knowledge Base Setup:</strong><br>';
        echo $errors;
        echo '</p></div>';
    }
}
add_action('admin_notices', 'chatbot_vector_admin_notice');

/**
 * Add sync/migrate button to admin - Shows when migration is needed
 */
function chatbot_vector_add_admin_actions() {
    $current_platform = get_option('chatbot_ai_platform_choice', 'OpenAI');
    $embedding_platform = get_option('chatbot_embedding_platform', '');

    // Get FAQ count to check if there are FAQs to migrate
    $faq_count = function_exists('chatbot_faq_get_count') ? chatbot_faq_get_count() : 0;

    // Determine if migration is needed:
    // 1. Never migrated (embedding_platform empty) AND have FAQs to migrate
    // 2. Platform changed since last migration
    $never_migrated = empty($embedding_platform) && $faq_count > 0;
    $platform_changed = !empty($embedding_platform) && $embedding_platform !== $current_platform;

    // If embeddings match current platform, don't show anything
    if (!$never_migrated && !$platform_changed) {
        return;
    }

    // Determine message
    if ($never_migrated) {
        $title = "⚠️ Migration Required";
        $message = "You have <strong>{$faq_count} FAQs</strong> that need embeddings generated with <strong>{$current_platform}</strong>.";
    } else {
        $title = "⚠️ Re-migration Required";
        $message = "Embeddings: <strong>{$embedding_platform}</strong> → Now using: <strong>{$current_platform}</strong>";
    }
    ?>
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px;">
        <strong><?php echo $title; ?></strong>
        <p style="margin: 8px 0;"><?php echo $message; ?></p>
        <?php if ($platform_changed): ?>
        <p style="margin: 0 0 10px 0;">
            <label style="cursor: pointer;">
                <input type="checkbox" id="chatbot-vector-clear-existing" value="1" checked>
                Clear existing entries
            </label>
        </p>
        <?php else: ?>
        <input type="hidden" id="chatbot-vector-clear-existing" value="0">
        <?php endif; ?>
        <button type="button" id="chatbot-vector-migrate-btn" class="button button-primary">
            Migrate FAQs Now
        </button>
        <span id="chatbot-vector-migrate-status" style="margin-left: 10px;"></span>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Branded modal function
        function showModal(title, message, options) {
            options = options || {};
            var borderColor = options.type === 'success' ? '#46b450' : options.type === 'error' ? '#dc3232' : '#0073aa';
            var showCancel = options.showCancel !== false;
            var confirmText = options.confirmText || 'OK';

            var overlay = document.createElement('div');
            overlay.id = 'chatbot-modal-overlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:100000;';

            var modal = document.createElement('div');
            modal.style.cssText = 'background:white;border-radius:4px;box-shadow:0 3px 6px rgba(0,0,0,0.3);max-width:400px;width:90%;border-top:4px solid ' + borderColor + ';';

            var buttonsHtml = showCancel
                ? '<button id="chatbot-modal-cancel" class="button" style="margin-right:10px;">Cancel</button><button id="chatbot-modal-confirm" class="button button-primary">' + confirmText + '</button>'
                : '<button id="chatbot-modal-confirm" class="button button-primary">' + confirmText + '</button>';

            modal.innerHTML =
                '<div style="padding:20px;">' +
                    '<h3 style="margin:0 0 10px 0;">' + title + '</h3>' +
                    '<p style="margin:0;color:#666;">' + message + '</p>' +
                '</div>' +
                '<div style="padding:15px 20px;background:#f7f7f7;text-align:right;border-radius:0 0 4px 4px;">' +
                    buttonsHtml +
                '</div>';

            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            document.getElementById('chatbot-modal-confirm').onclick = function() {
                document.body.removeChild(overlay);
                if (options.onConfirm) options.onConfirm();
            };

            var cancelBtn = document.getElementById('chatbot-modal-cancel');
            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    document.body.removeChild(overlay);
                    if (options.onCancel) options.onCancel();
                };
            }
        }

        $('#chatbot-vector-migrate-btn').on('click', function() {
            var btn = $(this);
            var status = $('#chatbot-vector-migrate-status');
            var clearExisting = $('#chatbot-vector-clear-existing').is(':checked') || $('#chatbot-vector-clear-existing').val() === '1' ? '1' : '0';

            showModal(
                'Generate Embeddings',
                'Generate embeddings with <?php echo esc_js($current_platform); ?>? This may take a few minutes.',
                {
                    confirmText: 'Migrate',
                    onConfirm: function() {
                        btn.prop('disabled', true);
                        status.html('<span style="color:#666;">Migrating...</span>');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'chatbot_vector_migrate',
                                nonce: '<?php echo wp_create_nonce('chatbot_vector_migrate'); ?>',
                                clear_existing: clearExisting
                            },
                            timeout: 300000,
                            success: function(response) {
                                btn.prop('disabled', false);
                                if (response.success) {
                                    showModal(
                                        'Migration Complete',
                                        response.data.message || 'All FAQs have been migrated successfully.',
                                        {
                                            type: 'success',
                                            showCancel: false,
                                            confirmText: 'Done',
                                            onConfirm: function() {
                                                location.reload();
                                            }
                                        }
                                    );
                                } else {
                                    showModal(
                                        'Migration Failed',
                                        response.data.message || 'An error occurred during migration.',
                                        {
                                            type: 'error',
                                            showCancel: false
                                        }
                                    );
                                }
                            },
                            error: function(xhr, textStatus, error) {
                                btn.prop('disabled', false);
                                showModal(
                                    'Migration Failed',
                                    'Request failed: ' + error,
                                    {
                                        type: 'error',
                                        showCancel: false
                                    }
                                );
                            }
                        });
                    }
                }
            );
        });
    });
    </script>
    <?php
}
