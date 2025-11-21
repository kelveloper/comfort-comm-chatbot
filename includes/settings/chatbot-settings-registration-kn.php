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
        <p>Import FAQ entries from a CSV file. The chatbot will use these to answer customer questions naturally.</p>

        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>Current FAQ Entries:</strong> <?php echo esc_html($faq_count); ?>
            <?php if ($faq_count > 0): ?>
                <span style="color: #155724;"> ✓ Ready to use</span>
            <?php else: ?>
                <span style="color: #856404;"> - Upload a CSV to get started</span>
            <?php endif; ?>
        </div>

        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>How to import:</strong>
            <ol style="margin: 10px 0 0 20px;">
                <li>Download the template CSV below</li>
                <li>Fill in your questions and answers</li>
                <li>Choose your CSV file</li>
                <li>Click "Upload & Import FAQs"</li>
            </ol>
        </div>

        <h3>Step 1: Download Template</h3>
        <p>Get the sample CSV format:</p>
        <a href="<?php echo esc_url(admin_url('admin-post.php?action=chatbot_faq_download_template')); ?>" class="button button-secondary">
            Download Template CSV
        </a>

        <hr style="margin: 20px 0;">

        <h3>Step 2: Upload Your FAQs</h3>
        <p>CSV format: <code>question,answer,category</code> (category is optional)</p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="background: #f8f9fa; padding: 20px; border-radius: 5px; border: 1px solid #dee2e6;">
            <?php wp_nonce_field('chatbot_faq_import', 'chatbot_faq_import_nonce'); ?>
            <input type="hidden" name="action" value="chatbot_faq_import_csv">

            <p style="margin-bottom: 15px;">
                <label><strong>Choose CSV File:</strong></label><br>
                <input type="file" name="faq_csv_file" accept=".csv" required style="margin-top: 5px;">
            </p>

            <p style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" name="clear_existing" value="1">
                    Replace all existing FAQs (uncheck to add to existing)
                </label>
            </p>

            <button type="submit" class="button button-primary button-large" style="background: #0073aa; border-color: #0073aa;">
                Upload & Import FAQs
            </button>
        </form>

        <?php
        // Show existing FAQs if any
        if ($faq_count > 0 && function_exists('chatbot_faq_get_all')) {
            $faqs = chatbot_faq_get_all();
            if (!empty($faqs)) {
                ?>
                <hr style="margin: 20px 0;">
                <h3>Current FAQ Entries</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $faq) : ?>
                        <tr>
                            <td><?php echo esc_html(wp_trim_words($faq->question, 10)); ?></td>
                            <td><?php echo esc_html(wp_trim_words($faq->answer, 15)); ?></td>
                            <td><?php echo esc_html($faq->category); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            }
        }
        ?>
    </div>

    <!-- Reopen parent settings form -->
    <form method="post" action="options.php">
    <?php settings_fields('chatbot_chatgpt_knowledge_navigator'); ?>
    <?php
}
