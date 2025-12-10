<?php
/**
 * Steven-Bot - Setup/Onboarding Settings
 *
 * This file handles the unified setup page with AI Platform selection,
 * API key configuration, and database connection - all with inline testing.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * Register Setup settings
 */
function chatbot_setup_settings_init() {
    // Register AI Platform settings with custom sanitize callback to detect changes
    register_setting('steven_bot_setup', 'chatbot_ai_platform_choice', [
        'sanitize_callback' => 'chatbot_setup_sanitize_platform_choice'
    ]);

    // Register Gemini API key - use custom callback to preserve existing if empty
    register_setting('steven_bot_setup', 'chatbot_gemini_api_key', [
        'sanitize_callback' => 'chatbot_setup_sanitize_gemini_key'
    ]);

    // Register OpenAI API key - use custom callback to preserve existing if empty
    register_setting('steven_bot_setup', 'steven_bot_api_key', [
        'sanitize_callback' => 'chatbot_setup_sanitize_openai_key'
    ]);

    // Register Supabase settings
    register_setting('steven_bot_setup', 'chatbot_supabase_project_url', 'steven_bot_sanitize_url');
    register_setting('steven_bot_setup', 'chatbot_supabase_anon_key', 'steven_bot_sanitize_text');
}
add_action('admin_init', 'chatbot_setup_settings_init');

/**
 * Sanitize AI Platform choice - detect platform changes for embedding regeneration
 */
function chatbot_setup_sanitize_platform_choice($input) {
    $input = sanitize_text_field($input);
    $old_platform = get_option('chatbot_ai_platform_choice', 'Gemini');
    $embedding_platform = get_option('chatbot_embedding_platform', '');

    // If the platform changed AND there was a previous embedding platform set, flag for regeneration
    if (!empty($embedding_platform) && $input !== $embedding_platform) {
        // Set a transient to show the regenerate embeddings prompt
        set_transient('steven_bot_platform_changed', [
            'old_platform' => $embedding_platform,
            'new_platform' => $input
        ], 60 * 5); // 5 minute expiry
    }

    return $input;
}

/**
 * Sanitize Gemini API key - preserve existing if empty
 */
function chatbot_setup_sanitize_gemini_key($input) {
    // Get the raw existing value directly from database to avoid recursion
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
        'chatbot_gemini_api_key'
    ));

    if (empty($input)) {
        // Keep existing value
        return $existing;
    }

    // Check if input is already encrypted JSON (don't double-encrypt)
    $decoded = json_decode($input, true);
    if (is_array($decoded) && isset($decoded['iv']) && isset($decoded['encrypted'])) {
        // Already encrypted, return as-is
        return $input;
    }

    // Encrypt the new key
    return steven_bot_encrypt_api_key($input);
}

/**
 * Sanitize OpenAI API key - preserve existing if empty
 */
function chatbot_setup_sanitize_openai_key($input) {
    // Get the raw existing value directly from database to avoid recursion
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
        'steven_bot_api_key'
    ));

    if (empty($input)) {
        // Keep existing value
        return $existing;
    }

    // Check if input is already encrypted JSON (don't double-encrypt)
    $decoded = json_decode($input, true);
    if (is_array($decoded) && isset($decoded['iv']) && isset($decoded['encrypted'])) {
        // Already encrypted, return as-is
        return $input;
    }

    // Encrypt the new key
    return steven_bot_encrypt_api_key($input);
}


/**
 * Render the Setup page content
 */
function chatbot_setup_page_content() {
    $ai_platform = esc_attr(get_option('chatbot_ai_platform_choice', 'Gemini'));

    // Check if platform was just changed (after save)
    $platform_changed = get_transient('steven_bot_platform_changed');
    $show_regen_prompt = false;
    $old_embed_platform = '';
    $new_embed_platform = '';
    if ($platform_changed) {
        $show_regen_prompt = true;
        $old_embed_platform = $platform_changed['old_platform'];
        $new_embed_platform = $platform_changed['new_platform'];
        // Don't delete transient yet - user needs to see and act on it
    }

    // Get current API keys (check if set and can be decrypted)
    $gemini_key_encrypted = get_option('chatbot_gemini_api_key', '');
    $openai_key_encrypted = get_option('steven_bot_api_key', '');

    // Try to decrypt and check if valid
    $gemini_key_decrypted = '';
    $openai_key_decrypted = '';

    if (!empty($gemini_key_encrypted) && function_exists('steven_bot_decrypt_api_key')) {
        // Decode HTML entities first
        $gemini_key_encrypted = html_entity_decode($gemini_key_encrypted);
        $decrypted = steven_bot_decrypt_api_key($gemini_key_encrypted);
        // Only use if decryption was successful (not the same as encrypted and doesn't contain JSON markers)
        if ($decrypted && strpos($decrypted, '{"iv"') === false && strpos($decrypted, 'encrypted') === false) {
            $gemini_key_decrypted = $decrypted;
        }
    }
    if (!empty($openai_key_encrypted) && function_exists('steven_bot_decrypt_api_key')) {
        // Decode HTML entities first
        $openai_key_encrypted = html_entity_decode($openai_key_encrypted);
        $decrypted = steven_bot_decrypt_api_key($openai_key_encrypted);
        // Only use if decryption was successful (not the same as encrypted and doesn't contain JSON markers)
        if ($decrypted && strpos($decrypted, '{"iv"') === false && strpos($decrypted, 'encrypted') === false) {
            $openai_key_decrypted = $decrypted;
        }
    }

    // Only consider key "saved" if decryption returns a non-empty string that looks like an API key
    $has_gemini_key = !empty($gemini_key_decrypted) && strlen($gemini_key_decrypted) > 10;
    $has_openai_key = !empty($openai_key_decrypted) && strlen($openai_key_decrypted) > 10;

    // Get Supabase config
    $supabase_url = get_option('chatbot_supabase_project_url', '');
    $supabase_key = get_option('chatbot_supabase_anon_key', '');
    $has_supabase = !empty($supabase_url) && !empty($supabase_key);

    // Check wp-config.php constants as fallback
    if (empty($supabase_url) && defined('CHATBOT_PG_HOST')) {
        // CHATBOT_PG_HOST is like "db.tlpvjrbmxxggubnjmdhe.supabase.co"
        // We need to convert to "https://tlpvjrbmxxggubnjmdhe.supabase.co"
        $pg_host = CHATBOT_PG_HOST;
        // Remove "db." prefix if present
        $pg_host = preg_replace('/^db\./', '', $pg_host);
        // Only add .supabase.co if it's not already there
        if (strpos($pg_host, '.supabase.co') === false) {
            $pg_host .= '.supabase.co';
        }
        $supabase_url = 'https://' . $pg_host;
    }
    if (empty($supabase_key) && defined('CHATBOT_SUPABASE_ANON_KEY')) {
        $supabase_key = CHATBOT_SUPABASE_ANON_KEY;
    }
    ?>

    <style>
        .setup-section {
            background-color: #fff;
            padding: 25px;
            margin-top: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .setup-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setup-section h3 .status-icon {
            font-size: 20px;
        }
        .setup-field {
            margin: 20px 0;
        }
        .setup-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .setup-field .field-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setup-field input[type="text"],
        .setup-field input[type="password"],
        .setup-field input[type="url"],
        .setup-field select {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .setup-field select {
            min-width: 200px;
        }
        .setup-field input[type="text"],
        .setup-field input[type="password"],
        .setup-field input[type="url"] {
            width: 400px;
        }
        .setup-field .description {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .test-btn {
            padding: 8px 16px;
            background: #0073aa;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .test-btn:hover {
            background: #005a87;
        }
        .test-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .password-wrapper {
            position: relative;
            display: inline-block;
        }
        .password-wrapper input {
            padding-right: 40px;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            padding: 0;
        }
        .toggle-password:hover {
            color: #0073aa;
        }
        .test-result {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            min-width: 150px;
        }
        .test-result.success {
            color: #46b450;
        }
        .test-result.error {
            color: #dc3232;
        }
        .test-result.testing {
            color: #666;
        }
        .status-icon.success { color: #46b450; }
        .status-icon.error { color: #dc3232; }
        .status-icon.pending { color: #f0b849; }

        .save-section {
            background: #f7f7f7;
            padding: 20px 25px;
            margin-top: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .save-section .validation-status {
            font-size: 14px;
        }
        #setup-save-btn:disabled {
            background: #ccc !important;
            border-color: #ccc !important;
            cursor: not-allowed;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            color: #0c5460;
        }
        .info-box.warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .info-box ol {
            margin: 10px 0 0 20px;
        }
    </style>

    <!-- AI Platform Section -->
    <div class="setup-section" id="ai-section">
        <h3>
            <span class="status-icon pending" id="ai-status-icon">●</span>
            AI Platform Configuration
        </h3>

        <div class="setup-field">
            <label for="chatbot_ai_platform_choice">AI Platform</label>
            <div class="field-row">
                <select id="chatbot_ai_platform_choice" name="chatbot_ai_platform_choice">
                    <option value="Gemini" <?php selected($ai_platform, 'Gemini'); ?>>Gemini (Google)</option>
                    <option value="OpenAI" <?php selected($ai_platform, 'OpenAI'); ?>>OpenAI</option>
                </select>
            </div>
            <p class="description">Select your preferred AI platform. Only Gemini and OpenAI support vector search for FAQ matching.</p>
        </div>

        <!-- Gemini API Key -->
        <div class="setup-field api-key-field" id="gemini-key-field" style="<?php echo $ai_platform !== 'Gemini' ? 'display:none;' : ''; ?>">
            <label for="chatbot_gemini_api_key">Gemini API Key</label>
            <div class="field-row">
                <div class="password-wrapper">
                    <input type="password"
                           id="chatbot_gemini_api_key"
                           name="chatbot_gemini_api_key"
                           value="<?php echo $has_gemini_key ? esc_attr($gemini_key_decrypted) : ''; ?>"
                           placeholder="<?php echo $has_gemini_key ? '' : 'Enter your Gemini API key'; ?>"
                           autocomplete="off">
                    <button type="button" class="toggle-password" data-target="chatbot_gemini_api_key" title="Show/Hide">👁</button>
                </div>
                <button type="button" class="test-btn" id="test-gemini-btn">Test</button>
                <span class="test-result" id="gemini-test-result"></span>
            </div>
            <p class="description">
                Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
                <?php if ($has_gemini_key): ?>
                    <span style="color: green; margin-left: 10px;">✓ Key saved</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- OpenAI API Key -->
        <div class="setup-field api-key-field" id="openai-key-field" style="<?php echo $ai_platform !== 'OpenAI' ? 'display:none;' : ''; ?>">
            <label for="steven_bot_api_key">OpenAI API Key</label>
            <div class="field-row">
                <div class="password-wrapper">
                    <input type="password"
                           id="steven_bot_api_key"
                           name="steven_bot_api_key"
                           value="<?php echo $has_openai_key ? esc_attr($openai_key_decrypted) : ''; ?>"
                           placeholder="<?php echo $has_openai_key ? '' : 'Enter your OpenAI API key'; ?>"
                           autocomplete="off">
                    <button type="button" class="toggle-password" data-target="steven_bot_api_key" title="Show/Hide">👁</button>
                </div>
                <button type="button" class="test-btn" id="test-openai-btn">Test</button>
                <span class="test-result" id="openai-test-result"></span>
            </div>
            <p class="description">
                Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                <?php if ($has_openai_key): ?>
                    <span style="color: green; margin-left: 10px;">✓ Key saved</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Ver 2.5.2: Regenerate Embeddings Section - Only shown after platform change -->
        <?php if ($show_regen_prompt): ?>
        <div class="setup-field" id="regen-embeddings-section" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb; background: #fff3cd; padding: 20px; border-radius: 5px;">
            <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px;">
                <span style="font-size: 24px;">⚠️</span>
                <div>
                    <strong style="font-size: 15px; color: #856404;">AI Platform Changed</strong>
                    <p style="margin: 5px 0 0; color: #856404;">
                        You switched from <strong><?php echo esc_html($old_embed_platform); ?></strong> to <strong><?php echo esc_html($new_embed_platform); ?></strong>.
                        Your existing FAQ and gap question embeddings need to be regenerated for vector search to work properly.
                    </p>
                </div>
            </div>
            <div class="field-row">
                <button type="button" class="button button-primary" id="regen-embeddings-btn" style="margin-right: 10px;">🔄 Regenerate All Embeddings</button>
                <span class="test-result" id="regen-result"></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Database Section -->
    <div class="setup-section" id="db-section">
        <h3>
            <span class="status-icon pending" id="db-status-icon">●</span>
            Database Connection (Supabase)
        </h3>

        <div class="info-box">
            <strong>How to get your Supabase credentials:</strong>
            <ol>
                <li>Go to your <a href="https://supabase.com/dashboard" target="_blank">Supabase Dashboard</a></li>
                <li><strong>Project URL:</strong> Settings → API → Project URL</li>
                <li><strong>Anon Key:</strong> Settings → API → anon public key</li>
            </ol>
        </div>

        <div class="setup-field">
            <label for="chatbot_supabase_project_url">Supabase Project URL</label>
            <div class="field-row">
                <input type="url"
                       id="chatbot_supabase_project_url"
                       name="chatbot_supabase_project_url"
                       value="<?php echo esc_attr($supabase_url); ?>"
                       placeholder="https://your-project.supabase.co">
            </div>
            <p class="description">Your Supabase project URL (e.g., https://abcdefgh.supabase.co)</p>
        </div>

        <div class="setup-field">
            <label for="chatbot_supabase_anon_key">Supabase Anon Key</label>
            <div class="field-row">
                <input type="text"
                       id="chatbot_supabase_anon_key"
                       name="chatbot_supabase_anon_key"
                       value="<?php echo esc_attr($supabase_key); ?>"
                       placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
                       style="width: 500px;">
            </div>
            <p class="description">Your Supabase anon/public key (starts with eyJ...)</p>
        </div>

        <div class="setup-field">
            <div class="field-row">
                <button type="button" class="test-btn" id="test-db-btn">Test Connection</button>
                <span class="test-result" id="db-test-result"></span>
            </div>
        </div>
    </div>

    <!-- Database Schema Setup Wizard -->
    <div class="setup-section" id="schema-section">
        <h3>
            <span class="status-icon pending" id="schema-status-icon">●</span>
            Database Schema Setup
        </h3>

        <p>After connecting to Supabase, you need to create the required database tables. Click the button below to check which tables exist.</p>

        <div class="setup-field">
            <div class="field-row">
                <button type="button" class="test-btn" id="check-tables-btn">Check Tables</button>
                <span class="test-result" id="tables-check-result"></span>
            </div>
        </div>

        <!-- Table status will be shown here -->
        <div id="table-status-container" style="display: none; margin-top: 20px;">
            <h4>Table Status:</h4>
            <div id="table-status-list" style="margin: 10px 0;"></div>
        </div>

        <!-- SQL Setup section (shown when tables are missing) -->
        <div id="sql-setup-container" style="display: none; margin-top: 20px;">
            <div class="info-box warning">
                <strong>⚠️ Missing Tables Detected</strong>
                <p>Some required tables don't exist in your Supabase database. Follow these steps:</p>
                <ol>
                    <li>Click "Copy SQL" below to copy the database schema</li>
                    <li>Open your <a href="#" id="supabase-sql-link" target="_blank">Supabase SQL Editor</a></li>
                    <li>Paste the SQL and click "Run"</li>
                    <li>Come back here and click "Check Tables" again to verify</li>
                </ol>
            </div>

            <div style="margin: 15px 0;">
                <button type="button" class="button button-primary" id="copy-sql-btn">📋 Copy SQL to Clipboard</button>
                <button type="button" class="button" id="show-sql-btn">👁 Show/Hide SQL</button>
                <span id="copy-result" style="margin-left: 10px; color: green; display: none;">✓ Copied!</span>
            </div>

            <div id="sql-preview" style="display: none; margin-top: 15px;">
                <textarea id="schema-sql" readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 12px; background: #f5f5f5; padding: 10px;"><?php echo esc_textarea(chatbot_get_schema_sql()); ?></textarea>
            </div>
        </div>

        <!-- Success message (shown when all tables exist) -->
        <div id="schema-success-container" style="display: none; margin-top: 20px;">
            <div class="info-box" style="background: #d4edda; border-color: #28a745;">
                <strong>✓ All Tables Ready</strong>
                <p>All required database tables exist in your Supabase project. Your chatbot is ready to use!</p>
            </div>
        </div>
    </div>

    <!-- Save Section -->
    <div class="save-section">
        <button type="submit" id="setup-save-btn" class="button button-primary">Save Settings</button>
        <span class="validation-status" id="validation-status">
            <span class="status-icon pending">●</span> Test your connections before saving
        </span>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var aiTestPassed = <?php echo ($has_gemini_key || $has_openai_key) ? 'true' : 'false'; ?>;
        var dbTestPassed = <?php echo $has_supabase ? 'true' : 'false'; ?>;

        // Update status icons on load if we have saved credentials
        if (aiTestPassed) {
            $('#ai-status-icon').removeClass('pending error').addClass('success').text('✓');
        }
        if (dbTestPassed) {
            $('#db-status-icon').removeClass('pending error').addClass('success').text('✓');
        }
        updateSaveButton();

        // Store embedding platform for comparison when user changes platform
        var embeddingPlatform = '<?php echo esc_js(get_option('chatbot_embedding_platform', '')); ?>';

        // Platform change handler - clears the OTHER platform's key
        $('#chatbot_ai_platform_choice').on('change', function() {
            var platform = $(this).val();
            $('.api-key-field').hide();
            if (platform === 'Gemini') {
                $('#gemini-key-field').show();
                // Clear OpenAI key when switching to Gemini
                $('#steven_bot_api_key').val('');
            } else if (platform === 'OpenAI') {
                $('#openai-key-field').show();
                // Clear Gemini key when switching to OpenAI
                $('#chatbot_gemini_api_key').val('');
            }
            // Reset AI test status when platform changes
            aiTestPassed = false;
            $('#ai-status-icon').removeClass('success error').addClass('pending').text('●');
            $('.test-result').text('');
            updateSaveButton();

            // Show simple info note if switching platforms with existing embeddings
            $('#platform-switch-info').remove();
            if (embeddingPlatform && platform !== embeddingPlatform) {
                var infoHtml = '<p id="platform-switch-info" style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;">' +
                    '<strong>Note:</strong> After saving, you\'ll be prompted to regenerate embeddings for the new platform.' +
                '</p>';
                $('#chatbot_ai_platform_choice').closest('.setup-field').append(infoHtml);
            }
        });

        // Regeneration Modal
        function showRegenerationModal() {
            var modalHtml = '<div id="regen-modal-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:100000;display:flex;align-items:center;justify-content:center;">' +
                '<div style="background:white;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);max-width:500px;width:90%;border-top:4px solid #0073aa;">' +
                    '<div style="padding:25px 25px 15px;">' +
                        '<h2 style="margin:0 0 8px;font-size:20px;font-weight:600;color:#1d2327;">Regenerating Embeddings</h2>' +
                        '<p style="margin:0 0 20px;color:#50575e;font-size:14px;">Converting your FAQs and gap questions to the new platform...</p>' +
                        '<div id="regen-progress-container" style="background:#f0f0f0;border-radius:4px;height:24px;overflow:hidden;margin-bottom:15px;">' +
                            '<div id="regen-progress-bar" style="background:linear-gradient(90deg, #0073aa 0%, #00a0d2 100%);height:100%;width:0%;transition:width 0.3s ease;"></div>' +
                        '</div>' +
                        '<div id="regen-status" style="font-size:14px;color:#666;margin-bottom:10px;">Initializing...</div>' +
                        '<div id="regen-details" style="font-size:12px;color:#999;"></div>' +
                    '</div>' +
                    '<div id="regen-actions" style="padding:15px 25px;display:none;border-top:1px solid #dcdcde;">' +
                        '<button id="regen-close-btn" class="button button-primary">Close</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
            $('body').append(modalHtml);

            // Start regeneration
            runEmbeddingRegeneration(0);

            // Close button
            $('#regen-close-btn').on('click', function() {
                $('#regen-modal-overlay').remove();
                location.reload();
            });
        }

        // Run embedding regeneration in batches
        function runEmbeddingRegeneration(offset) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'steven_bot_regenerate_embeddings',
                    type: 'all',
                    batch_size: 25,  // Ver 2.5.2: Increased from 10 for faster processing
                    offset: offset,
                    nonce: '<?php echo wp_create_nonce('steven_bot_regenerate_embeddings'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var percent = data.progress_percent || 0;

                        $('#regen-progress-bar').css('width', percent + '%');
                        $('#regen-status').html('Processing... <strong>' + Math.round(percent) + '%</strong> complete');
                        $('#regen-details').text(
                            'FAQs: ' + data.faqs_processed + ' | ' +
                            'Gap Questions: ' + data.gaps_processed
                        );

                        if (data.complete) {
                            $('#regen-progress-bar').css('width', '100%').css('background', 'linear-gradient(90deg, #46b450 0%, #7ad03a 100%)');
                            $('#regen-status').html('<span style="color:#46b450;font-weight:600;">✓ Regeneration Complete!</span>');
                            $('#regen-details').html(
                                'Successfully regenerated embeddings for <strong>' + data.faqs_total + '</strong> FAQs and <strong>' + data.gaps_total + '</strong> gap questions using <strong>' + data.platform + '</strong>.'
                            );
                            $('#regen-actions').show();
                        } else {
                            // Continue with next batch
                            setTimeout(function() {
                                runEmbeddingRegeneration(data.next_offset);
                            }, 500);
                        }
                    } else {
                        $('#regen-progress-bar').css('background', '#dc3232');
                        $('#regen-status').html('<span style="color:#dc3232;">✗ Error: ' + (response.data.message || 'Unknown error') + '</span>');
                        $('#regen-actions').show();
                    }
                },
                error: function() {
                    $('#regen-progress-bar').css('background', '#dc3232');
                    $('#regen-status').html('<span style="color:#dc3232;">✗ Connection failed. Please try again.</span>');
                    $('#regen-actions').show();
                }
            });
        }

        // Ver 2.5.2: Regenerate Embeddings button (shown only after platform change)
        $('#regen-embeddings-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true);
            showRegenerationModal();
        });

        // Test Gemini API
        $('#test-gemini-btn').on('click', function() {
            var $btn = $(this);
            var $result = $('#gemini-test-result');
            var apiKey = $('#chatbot_gemini_api_key').val();

            if (!apiKey) {
                $result.removeClass('success testing').addClass('error').text('✗ Enter API key first');
                return;
            }

            $btn.prop('disabled', true);
            $result.removeClass('success error').addClass('testing').text('Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'text',
                data: {
                    action: 'chatbot_test_api_key',
                    platform: 'gemini',
                    api_key: apiKey,
                    nonce: '<?php echo wp_create_nonce('chatbot_setup_test'); ?>'
                },
                success: function(responseText) {
                    console.log('Gemini raw response:', responseText);
                    $btn.prop('disabled', false);

                    // Strip any characters before the JSON
                    var jsonStart = responseText.indexOf('{');
                    if (jsonStart > 0) {
                        responseText = responseText.substring(jsonStart);
                    }

                    var response;
                    try {
                        response = JSON.parse(responseText);
                    } catch(e) {
                        console.log('JSON parse error:', e);
                        $result.removeClass('testing success').addClass('error').text('✗ Invalid response');
                        aiTestPassed = false;
                        updateSaveButton();
                        return;
                    }

                    var msg = 'Unknown response';
                    var warnings = [];
                    var info = '';
                    if (response) {
                        if (response.data && response.data.message) {
                            msg = response.data.message;
                        } else if (response.message) {
                            msg = response.message;
                        }
                        if (response.data && response.data.warnings) {
                            warnings = response.data.warnings;
                        }
                        if (response.data && response.data.info) {
                            info = response.data.info;
                        }
                    }
                    if (response && response.success) {
                        var html = '<span style="color: green;">' + msg + '</span>';
                        if (warnings.length > 0) {
                            html += '<br><span style="color: #b36b00;">' + warnings.join('<br>') + '</span>';
                        }
                        if (info) {
                            html += '<br><small style="color: #666;">' + info + '</small>';
                        }
                        $result.removeClass('testing error').addClass('success').html(html);
                        aiTestPassed = true;
                        $('#ai-status-icon').removeClass('pending error').addClass('success').text('✓');
                    } else {
                        $result.removeClass('testing success').addClass('error').text('✗ ' + msg);
                        aiTestPassed = false;
                        $('#ai-status-icon').removeClass('pending success').addClass('error').text('✗');
                    }
                    updateSaveButton();
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    console.log('AJAX Error:', status, error);
                    console.log('Response Text:', xhr.responseText);
                    $result.removeClass('testing success').addClass('error').text('✗ Connection failed');
                    aiTestPassed = false;
                    updateSaveButton();
                }
            });
        });

        // Test OpenAI API
        $('#test-openai-btn').on('click', function() {
            var $btn = $(this);
            var $result = $('#openai-test-result');
            var apiKey = $('#steven_bot_api_key').val();

            if (!apiKey) {
                $result.removeClass('success testing').addClass('error').text('✗ Enter API key first');
                return;
            }

            $btn.prop('disabled', true);
            $result.removeClass('success error').addClass('testing').text('Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'text',
                data: {
                    action: 'chatbot_test_api_key',
                    platform: 'openai',
                    api_key: apiKey,
                    nonce: '<?php echo wp_create_nonce('chatbot_setup_test'); ?>'
                },
                success: function(responseText) {
                    console.log('OpenAI raw response:', responseText);
                    $btn.prop('disabled', false);

                    // Strip any characters before the JSON
                    var jsonStart = responseText.indexOf('{');
                    if (jsonStart > 0) {
                        responseText = responseText.substring(jsonStart);
                    }

                    var response;
                    try {
                        response = JSON.parse(responseText);
                    } catch(e) {
                        console.log('JSON parse error:', e);
                        $result.removeClass('testing success').addClass('error').text('✗ Invalid response');
                        aiTestPassed = false;
                        updateSaveButton();
                        return;
                    }

                    var msg = 'Unknown response';
                    if (response) {
                        if (response.data && response.data.message) {
                            msg = response.data.message;
                        } else if (response.message) {
                            msg = response.message;
                        }
                    }
                    if (response && response.success) {
                        $result.removeClass('testing error').addClass('success').text('✓ ' + msg);
                        aiTestPassed = true;
                        $('#ai-status-icon').removeClass('pending error').addClass('success').text('✓');
                    } else {
                        $result.removeClass('testing success').addClass('error').text('✗ ' + msg);
                        aiTestPassed = false;
                        $('#ai-status-icon').removeClass('pending success').addClass('error').text('✗');
                    }
                    updateSaveButton();
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    console.log('AJAX Error:', status, error);
                    console.log('Response Text:', xhr.responseText);
                    $result.removeClass('testing success').addClass('error').text('✗ Connection failed');
                    aiTestPassed = false;
                    updateSaveButton();
                }
            });
        });

        // Test Database Connection
        $('#test-db-btn').on('click', function() {
            var $btn = $(this);
            var $result = $('#db-test-result');
            var projectUrl = $('#chatbot_supabase_project_url').val();
            var anonKey = $('#chatbot_supabase_anon_key').val();

            if (!projectUrl || !anonKey) {
                $result.removeClass('success testing').addClass('error').text('✗ Enter URL and Key first');
                return;
            }

            $btn.prop('disabled', true);
            $result.removeClass('success error').addClass('testing').text('Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'text',
                data: {
                    action: 'chatbot_test_db_setup',
                    project_url: projectUrl,
                    anon_key: anonKey,
                    nonce: '<?php echo wp_create_nonce('chatbot_setup_test'); ?>'
                },
                success: function(responseText) {
                    console.log('DB raw response:', responseText);
                    $btn.prop('disabled', false);

                    // Strip any characters before the JSON
                    var jsonStart = responseText.indexOf('{');
                    if (jsonStart > 0) {
                        responseText = responseText.substring(jsonStart);
                    }

                    var response;
                    try {
                        response = JSON.parse(responseText);
                    } catch(e) {
                        console.log('JSON parse error:', e);
                        $result.removeClass('testing success').addClass('error').text('✗ Invalid response');
                        dbTestPassed = false;
                        updateSaveButton();
                        return;
                    }

                    var msg = 'Unknown response';
                    if (response) {
                        if (response.data && response.data.message) {
                            msg = response.data.message;
                        } else if (response.message) {
                            msg = response.message;
                        }
                    }
                    if (response && response.success) {
                        $result.removeClass('testing error').addClass('success').text('✓ ' + msg);
                        dbTestPassed = true;
                        $('#db-status-icon').removeClass('pending error').addClass('success').text('✓');
                    } else {
                        $result.removeClass('testing success').addClass('error').text('✗ ' + msg);
                        dbTestPassed = false;
                        $('#db-status-icon').removeClass('pending success').addClass('error').text('✗');
                    }
                    updateSaveButton();
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    console.log('DB AJAX Error:', status, error);
                    console.log('Response Text:', xhr.responseText);
                    $result.removeClass('testing success').addClass('error').text('✗ Connection failed');
                    dbTestPassed = false;
                    updateSaveButton();
                }
            });
        });

        function updateSaveButton() {
            var $btn = $('#setup-save-btn');
            var $status = $('#validation-status');

            if (aiTestPassed && dbTestPassed) {
                $btn.prop('disabled', false);
                $status.html('<span class="status-icon success">✓</span> All connections verified - ready to save');
            } else if (aiTestPassed || dbTestPassed) {
                $btn.prop('disabled', false);
                var missing = [];
                if (!aiTestPassed) missing.push('AI API');
                if (!dbTestPassed) missing.push('Database');
                $status.html('<span class="status-icon pending">●</span> Test ' + missing.join(' and ') + ' connection');
            } else {
                $btn.prop('disabled', false);
                $status.html('<span class="status-icon pending">●</span> Test your connections before saving');
            }
        }

        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            var targetId = $(this).data('target');
            var $input = $('#' + targetId);
            var $btn = $(this);

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $btn.text('🙈');
                $btn.attr('title', 'Hide');
            } else {
                $input.attr('type', 'password');
                $btn.text('👁');
                $btn.attr('title', 'Show');
            }
        });

        // ===== Schema Setup Wizard =====

        // Check Tables button
        $('#check-tables-btn').on('click', function() {
            var $btn = $(this);
            var $result = $('#tables-check-result');
            var projectUrl = $('#chatbot_supabase_project_url').val();
            var anonKey = $('#chatbot_supabase_anon_key').val();

            if (!projectUrl || !anonKey) {
                $result.removeClass('success testing').addClass('error').text('✗ Enter Supabase credentials first');
                return;
            }

            $btn.prop('disabled', true);
            $result.removeClass('success error').addClass('testing').text('Checking tables...');

            // Update Supabase SQL Editor link
            var projectId = projectUrl.replace('https://', '').replace('.supabase.co', '');
            $('#supabase-sql-link').attr('href', 'https://supabase.com/dashboard/project/' + projectId + '/sql/new');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'chatbot_check_schema_tables',
                    project_url: projectUrl,
                    anon_key: anonKey,
                    nonce: '<?php echo wp_create_nonce('chatbot_setup_test'); ?>'
                },
                success: function(response) {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        var data = response.data;
                        var tableHtml = '';
                        var allExist = true;

                        // Build table status HTML
                        for (var table in data.tables) {
                            var exists = data.tables[table];
                            if (exists) {
                                tableHtml += '<div style="color: #28a745; margin: 5px 0;">✓ ' + table + '</div>';
                            } else {
                                tableHtml += '<div style="color: #dc3232; margin: 5px 0;">✗ ' + table + ' (missing)</div>';
                                allExist = false;
                            }
                        }

                        $('#table-status-list').html(tableHtml);
                        $('#table-status-container').show();

                        if (allExist) {
                            $result.removeClass('testing error').addClass('success').text('✓ All tables exist');
                            $('#schema-status-icon').removeClass('pending error').addClass('success').text('✓');
                            $('#sql-setup-container').hide();
                            $('#schema-success-container').show();
                        } else {
                            $result.removeClass('testing success').addClass('error').text('✗ Missing tables');
                            $('#schema-status-icon').removeClass('pending success').addClass('error').text('✗');
                            $('#sql-setup-container').show();
                            $('#schema-success-container').hide();
                        }
                    } else {
                        $result.removeClass('testing success').addClass('error').text('✗ ' + (response.data.message || 'Check failed'));
                        $('#schema-status-icon').removeClass('pending success').addClass('error').text('✗');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    $result.removeClass('testing success').addClass('error').text('✗ Connection failed');
                }
            });
        });

        // Copy SQL button
        $('#copy-sql-btn').on('click', function() {
            var $textarea = $('#schema-sql');
            $textarea.select();
            document.execCommand('copy');

            // Also try modern clipboard API
            if (navigator.clipboard) {
                navigator.clipboard.writeText($textarea.val());
            }

            $('#copy-result').fadeIn().delay(2000).fadeOut();
        });

        // Show/Hide SQL button
        $('#show-sql-btn').on('click', function() {
            $('#sql-preview').toggle();
            $(this).text($('#sql-preview').is(':visible') ? '🙈 Hide SQL' : '👁 Show SQL');
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for testing API keys
 */
function chatbot_test_api_key_ajax() {
    // Clean any output buffer that might have stray characters
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_setup_test')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }

    $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';
    // Don't use sanitize_text_field for API key as it may contain special characters
    $api_key = isset($_POST['api_key']) ? trim(wp_unslash($_POST['api_key'])) : '';

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key is required']);
        wp_die();
    }

    if ($platform === 'gemini') {
        // Test Gemini API - check models list first
        $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key, [
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
            wp_die();
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error = isset($body['error']['message']) ? $body['error']['message'] : 'Invalid API key (HTTP ' . $code . ')';
            wp_send_json_error(['message' => $error]);
            wp_die();
        }

        // API key is valid - now test embedding API (used for FAQ search)
        $embedding_test = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key=' . $api_key, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'model' => 'models/text-embedding-004',
                'content' => ['parts' => [['text' => 'test']]]
            ])
        ]);

        $embedding_ok = false;
        $embedding_error = '';
        if (!is_wp_error($embedding_test)) {
            $embed_code = wp_remote_retrieve_response_code($embedding_test);
            if ($embed_code === 200) {
                $embedding_ok = true;
            } else {
                $embed_body = json_decode(wp_remote_retrieve_body($embedding_test), true);
                $embedding_error = isset($embed_body['error']['message']) ? $embed_body['error']['message'] : 'HTTP ' . $embed_code;
            }
        } else {
            $embedding_error = $embedding_test->get_error_message();
        }

        // Test chat generation quota (used for AI fallback)
        $model = get_option('chatbot_gemini_model_choice', 'gemini-2.5-flash-lite');
        $chat_test = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [['parts' => [['text' => 'Say "test" and nothing else.']]]],
                'generationConfig' => ['maxOutputTokens' => 10]
            ])
        ]);

        $chat_ok = false;
        $chat_error = '';
        $quota_exhausted = false;
        if (!is_wp_error($chat_test)) {
            $chat_code = wp_remote_retrieve_response_code($chat_test);
            $chat_body_raw = wp_remote_retrieve_body($chat_test);
            error_log('[Chatbot Setup] Chat test response code: ' . $chat_code);
            error_log('[Chatbot Setup] Chat test response body: ' . $chat_body_raw);

            if ($chat_code === 200) {
                $chat_ok = true;
            } else {
                $chat_body = json_decode($chat_body_raw, true);
                $chat_error = isset($chat_body['error']['message']) ? $chat_body['error']['message'] : 'HTTP ' . $chat_code;
                // Check if it's a quota error
                if (stripos($chat_error, 'quota') !== false || stripos($chat_error, 'exceeded') !== false) {
                    $quota_exhausted = true;
                }
            }
        } else {
            $chat_error = $chat_test->get_error_message();
        }

        // Build response message
        $message = '✓ API Key Valid';
        $warnings = [];

        if ($embedding_ok) {
            $message .= ' | ✓ Embeddings OK';
        } else {
            $warnings[] = '⚠ Embeddings: ' . $embedding_error;
        }

        if ($chat_ok) {
            $message .= ' | ✓ Chat OK';
        } elseif ($quota_exhausted) {
            $warnings[] = '⚠ QUOTA EXHAUSTED - AI fallback will not work until quota resets (usually daily)';
        } else {
            $warnings[] = '⚠ Chat: ' . $chat_error;
        }

        // Add usage info
        $usage_info = 'Used for: FAQ vector search, Gap Analysis, Chatbot fallback';

        if (!empty($warnings)) {
            wp_send_json_success([
                'message' => $message,
                'warnings' => $warnings,
                'info' => $usage_info
            ]);
        } else {
            wp_send_json_success([
                'message' => $message,
                'info' => $usage_info
            ]);
        }
        wp_die();

    } elseif ($platform === 'openai') {
        // Test OpenAI API
        $response = wp_remote_get('https://api.openai.com/v1/models', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
            wp_die();
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            wp_send_json_success(['message' => 'Connected to OpenAI']);
            wp_die();
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error = isset($body['error']['message']) ? $body['error']['message'] : 'Invalid API key (HTTP ' . $code . ')';
            wp_send_json_error(['message' => $error]);
            wp_die();
        }
    } else {
        wp_send_json_error(['message' => 'Unknown platform: ' . $platform]);
        wp_die();
    }
}
add_action('wp_ajax_chatbot_test_api_key', 'chatbot_test_api_key_ajax');

/**
 * AJAX handler for testing Supabase connection from Setup page
 */
function chatbot_test_supabase_setup_ajax() {
    // Clean any output buffer that might have stray characters
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_setup_test')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }

    $project_url = isset($_POST['project_url']) ? esc_url_raw(trim(wp_unslash($_POST['project_url']))) : '';
    // Don't use sanitize_text_field for anon key as it contains special characters (JWT)
    $anon_key = isset($_POST['anon_key']) ? trim(wp_unslash($_POST['anon_key'])) : '';

    if (empty($project_url) || empty($anon_key)) {
        wp_send_json_error(['message' => 'Missing credentials']);
        wp_die();
    }

    // Test the connection
    $base_url = rtrim($project_url, '/') . '/rest/v1';
    $url = $base_url . '/chatbot_faqs?select=id&limit=1';

    $response = wp_remote_get($url, [
        'headers' => [
            'apikey' => $anon_key,
            'Authorization' => 'Bearer ' . $anon_key
        ],
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        wp_die();
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code === 200) {
        wp_send_json_success(['message' => 'Connected to Supabase']);
        wp_die();
    } elseif ($code === 401) {
        wp_send_json_error(['message' => 'Invalid API key']);
        wp_die();
    } elseif ($code === 404) {
        wp_send_json_error(['message' => 'Table not found - run database setup']);
        wp_die();
    } else {
        wp_send_json_error(['message' => 'Error: HTTP ' . $code]);
        wp_die();
    }
}
add_action('wp_ajax_chatbot_test_db_setup', 'chatbot_test_supabase_setup_ajax');

/**
 * Get the schema SQL for display
 * Ver 2.5.0: Uses the new comprehensive schema from chatbot-supabase-schema.php
 */
function chatbot_get_schema_sql() {
    // Load the comprehensive schema generator
    $schema_php = plugin_dir_path(dirname(__FILE__)) . 'supabase/chatbot-supabase-schema.php';

    if (file_exists($schema_php)) {
        if (!function_exists('chatbot_supabase_get_full_setup_sql')) {
            require_once $schema_php;
        }
        return chatbot_supabase_get_full_setup_sql();
    }

    // Fallback to static SQL file if PHP schema not found
    $schema_file = plugin_dir_path(dirname(__FILE__)) . 'supabase/supabase-schema.sql';
    if (file_exists($schema_file)) {
        return file_get_contents($schema_file);
    }

    // Final fallback
    return '-- Schema file not found. Please check: includes/supabase/chatbot-supabase-schema.php';
}

/**
 * AJAX handler for checking which tables exist
 */
function chatbot_check_schema_tables_ajax() {
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_setup_test')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }

    $project_url = isset($_POST['project_url']) ? esc_url_raw(trim(wp_unslash($_POST['project_url']))) : '';
    $anon_key = isset($_POST['anon_key']) ? trim(wp_unslash($_POST['anon_key'])) : '';

    if (empty($project_url) || empty($anon_key)) {
        wp_send_json_error(['message' => 'Missing credentials']);
        wp_die();
    }

    // Required tables for the chatbot - get from schema definition
    // Ver 2.5.0: Uses the comprehensive schema
    $schema_php = plugin_dir_path(dirname(__FILE__)) . 'supabase/chatbot-supabase-schema.php';
    if (file_exists($schema_php) && !function_exists('chatbot_supabase_get_schema')) {
        require_once $schema_php;
    }

    $required_tables = [];
    if (function_exists('chatbot_supabase_get_schema')) {
        $schema = chatbot_supabase_get_schema();
        foreach ($schema as $table) {
            $required_tables[] = $table['name'];
        }
    } else {
        // Fallback
        $required_tables = [
            'chatbot_faqs',
            'chatbot_conversations',
            'chatbot_interactions',
            'chatbot_gap_questions',
            'chatbot_gap_clusters',
            'chatbot_faq_usage',
            'chatbot_assistants'
        ];
    }

    $base_url = rtrim($project_url, '/') . '/rest/v1';
    $table_status = [];

    foreach ($required_tables as $table) {
        // Try to query the table (just check if it exists)
        $url = $base_url . '/' . $table . '?select=id&limit=1';

        $response = wp_remote_get($url, [
            'headers' => [
                'apikey' => $anon_key,
                'Authorization' => 'Bearer ' . $anon_key
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            $table_status[$table] = false;
            continue;
        }

        $code = wp_remote_retrieve_response_code($response);

        // 200 = table exists (even if empty)
        // 404 = table doesn't exist
        // Other codes might indicate permission issues
        $table_status[$table] = ($code === 200);
    }

    wp_send_json_success([
        'tables' => $table_status,
        'all_exist' => !in_array(false, $table_status, true)
    ]);
    wp_die();
}
add_action('wp_ajax_chatbot_check_schema_tables', 'chatbot_check_schema_tables_ajax');

/**
 * Check if platform change requires embedding regeneration
 * Ver 2.5.2
 */
function steven_bot_check_platform_change_ajax() {
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'chatbot_setup_test')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }

    $new_platform = isset($_POST['new_platform']) ? sanitize_text_field($_POST['new_platform']) : '';
    $embedding_platform = get_option('chatbot_embedding_platform', '');

    // Check if the platform supports embeddings
    $embedding_platforms = ['OpenAI', 'Gemini', 'Azure OpenAI', 'Mistral'];
    $new_supports_embeddings = in_array($new_platform, $embedding_platforms);

    // Get counts
    $faq_count = 0;
    $gap_count = 0;

    if (function_exists('chatbot_vector_get_pg_connection')) {
        $pdo = chatbot_vector_get_pg_connection();
        if ($pdo) {
            try {
                $stmt = $pdo->query('SELECT COUNT(*) FROM chatbot_faqs');
                $faq_count = intval($stmt->fetchColumn());

                $stmt = $pdo->query('SELECT COUNT(*) FROM chatbot_gap_questions WHERE question_text IS NOT NULL');
                $gap_count = intval($stmt->fetchColumn());
            } catch (Exception $e) {
                // Ignore
            }
        }
    }

    $total_items = $faq_count + $gap_count;
    $needs_regen = $new_supports_embeddings && !empty($embedding_platform) && $embedding_platform !== $new_platform && $total_items > 0;

    wp_send_json_success([
        'needs_regen' => $needs_regen,
        'new_platform' => $new_platform,
        'embedding_platform' => $embedding_platform,
        'new_supports_embeddings' => $new_supports_embeddings,
        'faq_count' => $faq_count,
        'gap_count' => $gap_count,
        'total_items' => $total_items,
        'message' => $needs_regen
            ? "You're switching from {$embedding_platform} to {$new_platform}. Your FAQ and gap question embeddings ({$total_items} items) need to be regenerated."
            : "No embedding regeneration needed."
    ]);
    wp_die();
}
add_action('wp_ajax_steven_bot_check_platform_change', 'steven_bot_check_platform_change_ajax');
