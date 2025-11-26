<?php
/**
 * Kognetiks Chatbot - Registration - Knowledge Navigator Settings - Ver 2.0.0
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the registration of settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Knowledge Navigator settings
function chatbot_chatgpt_kn_settings_init() {

    // Knowledge Navigator Tab
    
    // Knowledge Navigator Settings and Schedule - Ver 2.0.0
    add_settings_section(
        'chatbot_chatgpt_knowledge_navigator_settings_section',
        'Knowledge Navigator',
        'chatbot_chatgpt_knowledge_navigator_section_callback',
        'chatbot_chatgpt_knowledge_navigator'
    );

    // Knowledge Navigator Status
    add_settings_section(
        'chatbot_chatgpt_kn_status_section',
        'Knowledge Navigator Status',
        'chatbot_chatgpt_kn_status_section_callback',
        'chatbot_chatgpt_kn_status'
    );

    // Knowledge Navigator Settings and Schedule - Ver 2.0.0
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_schedule');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_maximum_top_words');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_tuning_percentage');

    add_settings_section(
        'chatbot_chatgpt_kn_scheduling_section',
        'Knowledge Navigator Scheduling',
        'chatbot_chatgpt_kn_settings_section_callback',
        'chatbot_chatgpt_kn_scheduling'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_schedule',
        'Select Run Schedule',
        'chatbot_chatgpt_kn_schedule_callback',
        'chatbot_chatgpt_kn_scheduling',
        'chatbot_chatgpt_kn_scheduling_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_maximum_top_words',
        'Maximum Top Words',
        'chatbot_chatgpt_kn_maximum_top_words_callback',
        'chatbot_chatgpt_kn_scheduling',
        'chatbot_chatgpt_kn_scheduling_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_tuning_percentage',
        'Tuning Percentage',
        'chatbot_chatgpt_kn_tuning_percentage_callback',
        'chatbot_chatgpt_kn_scheduling',
        'chatbot_chatgpt_kn_scheduling_section'
    );

    // Knowledge Navigator Inclusion/Exclusion Settings - Ver 2.0.0
    // Register settings for dynamic post types
    add_settings_section(
        'chatbot_chatgpt_kn_include_exclude_section',
        'Knowledge Navigator Include/Exclude Settings',
        'chatbot_chatgpt_kn_include_exclude_section_callback',
        'chatbot_chatgpt_kn_include_exclude'
    );

    // Register settings for comments separately since it's not a post type
    register_setting(
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_include_comments',
        [
            'type' => 'string',
            'default' => 'No',
            'sanitize_callback' => 'sanitize_text_field'
        ]
    );

    // Register dynamic post type settings and fields
    $published_types = chatbot_chatgpt_kn_get_published_post_types();
    foreach ($published_types as $post_type => $label) {

        // Register the setting
        $plural_type = $post_type === 'reference' ? 'references' : $post_type . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;

        register_setting(
            'chatbot_chatgpt_knowledge_navigator',
            $option_name
        );

        // Add the settings field
        add_settings_field(
            // 'chatbot_chatgpt_kn_include_' . $plural_type,
            $option_name,
            'Include ' . ucfirst($label),
            'chatbot_chatgpt_kn_include_post_type_callback',
            'chatbot_chatgpt_kn_include_exclude',
            'chatbot_chatgpt_kn_include_exclude_section',
            ['option_name' => $option_name]
        );

    }

    // Add comments field
    add_settings_field(
        'chatbot_chatgpt_kn_include_comments',
        'Include Approved Comments',
        'chatbot_chatgpt_kn_include_comments_callback',
        'chatbot_chatgpt_kn_include_exclude',
        'chatbot_chatgpt_kn_include_exclude_section'
    );

    // Knowledge Navigator Enhanced Responses - Ver 2.0.0
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_suppress_learnings');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_custom_learnings_message');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_enhanced_response_limit');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_enhanced_response_include_excerpts');

    add_settings_section(
        'chatbot_chatgpt_kn_enhanced_response_section',
        'Knowledge Navigator Enhanced Response Settings',
        'chatbot_chatgpt_kn_enhanced_response_section_callback',
        'chatbot_chatgpt_kn_enhanced_response'
    );

    add_settings_field(
        'chatbot_chatgpt_suppress_learnings',
        'Suppress Learnings Messages',
        'chatbot_chatgpt_suppress_learnings_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_learnings_message',
        'Custom Learnings Message',
        'chatbot_chatgpt_custom_learnings_message_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    add_settings_field(
        'chatbot_chatgpt_enhanced_response_limit',
        'Enhanced Response Limit',
        'chatbot_chatgpt_enhanced_response_limit_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    add_settings_field(
        'chatbot_chatgpt_enhanced_response_include_excerpts',
        'Include Post/Page Excerpts',
        'chatbot_chatgpt_enhanced_response_include_excerpts_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    // Analysis Tab

    // Knowledge Navigator Analysis settings tab - Ver 1.6.1
    register_setting('chatbot_chatgpt_kn_analysis', 'chatbot_chatgpt_kn_analysis_output');

    add_settings_section(
        'chatbot_chatgpt_kn_analysis_section',
        'Knowledge Navigator Analysis',
        'chatbot_chatgpt_kn_analysis_section_callback',
        'chatbot_chatgpt_kn_analysis'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_analysis_output',
        'Output Format',
        'chatbot_chatgpt_kn_analysis_output_callback',
        'chatbot_chatgpt_kn_analysis',
        'chatbot_chatgpt_kn_analysis_section'
    );

    // FAQ Import Section - Ver 2.3.7
    add_settings_section(
        'chatbot_chatgpt_faq_import_section',
        'FAQ Import',
        'chatbot_chatgpt_faq_import_section_callback',
        'chatbot_chatgpt_faq_import'
    );

}
add_action('admin_init', 'chatbot_chatgpt_kn_settings_init');

// FAQ Import Section Callback - Ver 2.3.7
function chatbot_chatgpt_faq_import_section_callback() {
    // Get FAQ count
    $faq_count = function_exists('chatbot_faq_get_count') ? chatbot_faq_get_count() : 0;

    // Display any import messages from transient
    $import_message = get_transient('chatbot_faq_import_message');
    if ($import_message) {
        delete_transient('chatbot_faq_import_message');
        $class = $import_message['type'] === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($import_message['message']) . '</p></div>';
    }

    ?>
    </form><!-- Close parent settings form to prevent nesting -->

    <div class="wrap">
        <p>Manage your FAQ entries. The chatbot will use these to answer customer questions naturally.</p>

        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>Current FAQ Entries:</strong> <?php echo esc_html($faq_count); ?>
            <?php if ($faq_count > 0): ?>
                <span style="color: #155724;"> ✓ Ready to use</span>
            <?php else: ?>
                <span style="color: #856404;"> - Add FAQs to get started</span>
            <?php endif; ?>
        </div>

        <button id="chatbot-faq-add-btn" class="button button-primary" style="margin-bottom: 20px;">
            Add New FAQ
        </button>

        <?php
        // Show existing FAQs if any
        if ($faq_count > 0 && function_exists('chatbot_faq_get_all')) {
            $faqs = chatbot_faq_get_all();
            if (!empty($faqs)) {
                ?>
                <table class="wp-list-table widefat fixed striped" id="chatbot-faq-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Question</th>
                            <th style="width: 40%;">Answer</th>
                            <th style="width: 15%;">Category</th>
                            <th style="width: 20%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $faq) : ?>
                        <tr data-faq-id="<?php echo esc_attr($faq->id); ?>">
                            <td><?php echo esc_html($faq->question); ?></td>
                            <td><?php echo esc_html($faq->answer); ?></td>
                            <td><?php echo esc_html($faq->category); ?></td>
                            <td>
                                <button class="button button-small chatbot-faq-edit-btn" data-faq-id="<?php echo esc_attr($faq->id); ?>">
                                    Edit
                                </button>
                                <button class="button button-small chatbot-faq-delete-btn" data-faq-id="<?php echo esc_attr($faq->id); ?>" style="color: #a00;">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            }
        }
        ?>

        <!-- FAQ Modal -->
        <div id="chatbot-faq-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 5px;">
                <span id="chatbot-faq-modal-close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                <h2 id="chatbot-faq-modal-title">Add FAQ</h2>
                <form id="chatbot-faq-form">
                    <input type="hidden" id="chatbot-faq-id" value="">
                    <p>
                        <label for="chatbot-faq-question"><strong>Question:</strong></label><br>
                        <textarea id="chatbot-faq-question" style="width: 100%; height: 80px;" required></textarea>
                    </p>
                    <p>
                        <label for="chatbot-faq-answer"><strong>Answer:</strong></label><br>
                        <textarea id="chatbot-faq-answer" style="width: 100%; height: 120px;" required></textarea>
                    </p>
                    <p>
                        <label for="chatbot-faq-category"><strong>Category:</strong></label><br>
                        <input type="text" id="chatbot-faq-category" style="width: 100%;">
                    </p>
                    <p>
                        <button type="submit" class="button button-primary">Save FAQ</button>
                        <button type="button" id="chatbot-faq-modal-cancel" class="button">Cancel</button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            const modal = $('#chatbot-faq-modal');
            const modalTitle = $('#chatbot-faq-modal-title');
            const form = $('#chatbot-faq-form');
            const faqId = $('#chatbot-faq-id');
            const question = $('#chatbot-faq-question');
            const answer = $('#chatbot-faq-answer');
            const category = $('#chatbot-faq-category');

            // Open modal for adding
            $('#chatbot-faq-add-btn').on('click', function() {
                modalTitle.text('Add New FAQ');
                faqId.val('');
                question.val('');
                answer.val('');
                category.val('');
                modal.show();
            });

            // Open modal for editing
            $(document).on('click', '.chatbot-faq-edit-btn', function() {
                const id = $(this).data('faq-id');
                modalTitle.text('Edit FAQ');

                // Get FAQ data via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'chatbot_faq_get',
                        nonce: '<?php echo wp_create_nonce('chatbot_faq_manage'); ?>',
                        id: id
                    },
                    success: function(response) {
                        if (response.success && response.data.faq) {
                            faqId.val(response.data.faq.id);
                            question.val(response.data.faq.question);
                            answer.val(response.data.faq.answer);
                            category.val(response.data.faq.category);
                            modal.show();
                        } else {
                            alert('Failed to load FAQ: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Failed to load FAQ');
                    }
                });
            });

            // Close modal
            $('#chatbot-faq-modal-close, #chatbot-faq-modal-cancel').on('click', function() {
                modal.hide();
            });

            // Submit form
            form.on('submit', function(e) {
                e.preventDefault();

                const id = faqId.val();
                const action = id ? 'chatbot_faq_update' : 'chatbot_faq_add';

                const data = {
                    action: action,
                    nonce: '<?php echo wp_create_nonce('chatbot_faq_manage'); ?>',
                    question: question.val(),
                    answer: answer.val(),
                    category: category.val()
                };

                if (id) {
                    data.id = id;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Failed to save FAQ');
                    }
                });
            });

            // Delete FAQ
            $(document).on('click', '.chatbot-faq-delete-btn', function() {
                const id = $(this).data('faq-id');

                if (!confirm('Are you sure you want to delete this FAQ?')) {
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'chatbot_faq_delete',
                        nonce: '<?php echo wp_create_nonce('chatbot_faq_manage'); ?>',
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Failed to delete FAQ');
                    }
                });
            });
        });
        </script>
    </div>

    <!-- Reopen parent settings form -->
    <form method="post" action="options.php">
    <?php settings_fields('chatbot_chatgpt_knowledge_navigator'); ?>
    <?php
}
