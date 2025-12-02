<?php
/**
 * Steve-Bot - Setup/Onboarding Settings
 *
 * This file handles the unified setup page with AI Platform selection,
 * API key configuration, and database connection - all with inline testing.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * Register Setup settings
 */
function chatbot_setup_settings_init() {
    // Register AI Platform settings
    register_setting('chatbot_chatgpt_setup', 'chatbot_ai_platform_choice');

    // Register Gemini API key - use custom callback to preserve existing if empty
    register_setting('chatbot_chatgpt_setup', 'chatbot_gemini_api_key', [
        'sanitize_callback' => 'chatbot_setup_sanitize_gemini_key'
    ]);

    // Register OpenAI API key - use custom callback to preserve existing if empty
    register_setting('chatbot_chatgpt_setup', 'chatbot_chatgpt_api_key', [
        'sanitize_callback' => 'chatbot_setup_sanitize_openai_key'
    ]);

    // Register Supabase settings
    register_setting('chatbot_chatgpt_setup', 'chatbot_supabase_project_url');
    register_setting('chatbot_chatgpt_setup', 'chatbot_supabase_anon_key');
}
add_action('admin_init', 'chatbot_setup_settings_init');

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
    return chatbot_chatgpt_encrypt_api_key($input);
}

/**
 * Sanitize OpenAI API key - preserve existing if empty
 */
function chatbot_setup_sanitize_openai_key($input) {
    // Get the raw existing value directly from database to avoid recursion
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
        'chatbot_chatgpt_api_key'
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
    return chatbot_chatgpt_encrypt_api_key($input);
}


/**
 * Render the Setup page content
 */
function chatbot_setup_page_content() {
    $ai_platform = esc_attr(get_option('chatbot_ai_platform_choice', 'Gemini'));

    // Get current API keys (check if set and can be decrypted)
    $gemini_key_encrypted = get_option('chatbot_gemini_api_key', '');
    $openai_key_encrypted = get_option('chatbot_chatgpt_api_key', '');

    // Try to decrypt and check if valid
    $gemini_key_decrypted = '';
    $openai_key_decrypted = '';

    if (!empty($gemini_key_encrypted) && function_exists('chatbot_chatgpt_decrypt_api_key')) {
        // Decode HTML entities first
        $gemini_key_encrypted = html_entity_decode($gemini_key_encrypted);
        $decrypted = chatbot_chatgpt_decrypt_api_key($gemini_key_encrypted);
        // Only use if decryption was successful (not the same as encrypted and doesn't contain JSON markers)
        if ($decrypted && strpos($decrypted, '{"iv"') === false && strpos($decrypted, 'encrypted') === false) {
            $gemini_key_decrypted = $decrypted;
        }
    }
    if (!empty($openai_key_encrypted) && function_exists('chatbot_chatgpt_decrypt_api_key')) {
        // Decode HTML entities first
        $openai_key_encrypted = html_entity_decode($openai_key_encrypted);
        $decrypted = chatbot_chatgpt_decrypt_api_key($openai_key_encrypted);
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
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
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
            <label for="chatbot_chatgpt_api_key">OpenAI API Key</label>
            <div class="field-row">
                <div class="password-wrapper">
                    <input type="password"
                           id="chatbot_chatgpt_api_key"
                           name="chatbot_chatgpt_api_key"
                           value="<?php echo $has_openai_key ? esc_attr($openai_key_decrypted) : ''; ?>"
                           placeholder="<?php echo $has_openai_key ? '' : 'Enter your OpenAI API key'; ?>"
                           autocomplete="off">
                    <button type="button" class="toggle-password" data-target="chatbot_chatgpt_api_key" title="Show/Hide">👁</button>
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

        // Platform change handler
        $('#chatbot_ai_platform_choice').on('change', function() {
            var platform = $(this).val();
            $('.api-key-field').hide();
            if (platform === 'Gemini') {
                $('#gemini-key-field').show();
            } else if (platform === 'OpenAI') {
                $('#openai-key-field').show();
            }
            // Reset AI test status when platform changes
            aiTestPassed = false;
            $('#ai-status-icon').removeClass('success error').addClass('pending').text('●');
            $('.test-result').text('');
            updateSaveButton();
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

        // Test OpenAI API
        $('#test-openai-btn').on('click', function() {
            var $btn = $(this);
            var $result = $('#openai-test-result');
            var apiKey = $('#chatbot_chatgpt_api_key').val();

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
        // Test Gemini API
        $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key, [
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
            wp_die();
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            wp_send_json_success(['message' => 'Connected to Gemini']);
            wp_die();
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error = isset($body['error']['message']) ? $body['error']['message'] : 'Invalid API key (HTTP ' . $code . ')';
            wp_send_json_error(['message' => $error]);
            wp_die();
        }

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
