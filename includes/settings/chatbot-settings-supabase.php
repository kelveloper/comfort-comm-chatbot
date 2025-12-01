<?php
/**
 * Steve-Bot - Supabase Database Settings
 *
 * This file handles the Supabase/PostgreSQL connection settings UI.
 * Allows users to configure database connection from admin panel.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * Register Supabase settings
 */
function chatbot_supabase_settings_init() {
    // Register settings
    register_setting('chatbot_chatgpt_supabase', 'chatbot_supabase_project_url');
    register_setting('chatbot_chatgpt_supabase', 'chatbot_supabase_anon_key');
    register_setting('chatbot_chatgpt_supabase', 'chatbot_supabase_db_password', [
        'sanitize_callback' => 'chatbot_supabase_encrypt_password'
    ]);

    // Connection Settings Section
    add_settings_section(
        'chatbot_supabase_connection_section',
        'Supabase Connection',
        'chatbot_supabase_connection_section_callback',
        'chatbot_chatgpt_supabase'
    );

    add_settings_field(
        'chatbot_supabase_project_url',
        'Supabase Project URL',
        'chatbot_supabase_project_url_callback',
        'chatbot_chatgpt_supabase',
        'chatbot_supabase_connection_section'
    );

    add_settings_field(
        'chatbot_supabase_anon_key',
        'Supabase Anon Key',
        'chatbot_supabase_anon_key_callback',
        'chatbot_chatgpt_supabase',
        'chatbot_supabase_connection_section'
    );

    add_settings_field(
        'chatbot_supabase_db_password',
        'Database Password',
        'chatbot_supabase_db_password_callback',
        'chatbot_chatgpt_supabase',
        'chatbot_supabase_connection_section'
    );

    // Status Section
    add_settings_section(
        'chatbot_supabase_status_section',
        'Connection Status',
        'chatbot_supabase_status_section_callback',
        'chatbot_chatgpt_supabase_status'
    );
}
add_action('admin_init', 'chatbot_supabase_settings_init');

/**
 * Connection section description
 */
function chatbot_supabase_connection_section_callback() {
    ?>
    <div class="wrap">
        <p>Configure your Supabase database connection. You can find these values in your <a href="https://supabase.com/dashboard" target="_blank">Supabase Dashboard</a>.</p>
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <strong>How to get your credentials:</strong>
            <ol style="margin: 10px 0 0 20px;">
                <li>Go to your <a href="https://supabase.com/dashboard" target="_blank">Supabase Dashboard</a></li>
                <li><strong>Project URL:</strong> Settings → API → Project URL (e.g., https://xxxxx.supabase.co)</li>
                <li><strong>Anon Key:</strong> Settings → API → anon public key</li>
                <li><strong>Database Password:</strong> Settings → Database → Database Password (set during project creation)</li>
            </ol>
        </div>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> after entering your credentials.</i></b></p>
    </div>
    <?php
}

/**
 * Project URL field
 */
function chatbot_supabase_project_url_callback() {
    $value = esc_attr(get_option('chatbot_supabase_project_url', ''));
    // Also check wp-config.php constant
    if (empty($value) && defined('CHATBOT_PG_HOST')) {
        $value = 'https://' . CHATBOT_PG_HOST;
    }
    ?>
    <input type="url"
           id="chatbot_supabase_project_url"
           name="chatbot_supabase_project_url"
           value="<?php echo esc_attr($value); ?>"
           style="width: 400px;"
           placeholder="https://your-project.supabase.co">
    <p class="description">Your Supabase project URL (e.g., https://abcdefgh.supabase.co)</p>
    <?php
}

/**
 * Anon Key field
 */
function chatbot_supabase_anon_key_callback() {
    $value = esc_attr(get_option('chatbot_supabase_anon_key', ''));
    // Also check wp-config.php constant
    if (empty($value) && defined('CHATBOT_SUPABASE_ANON_KEY')) {
        $value = CHATBOT_SUPABASE_ANON_KEY;
    }
    ?>
    <input type="text"
           id="chatbot_supabase_anon_key"
           name="chatbot_supabase_anon_key"
           value="<?php echo esc_attr($value); ?>"
           style="width: 600px;"
           placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">
    <p class="description">Your Supabase anon/public key (starts with eyJ...)</p>
    <?php
}

/**
 * Database Password field
 */
function chatbot_supabase_db_password_callback() {
    $encrypted = get_option('chatbot_supabase_db_password', '');
    $has_password = !empty($encrypted) || defined('CHATBOT_PG_PASSWORD');
    ?>
    <input type="password"
           id="chatbot_supabase_db_password"
           name="chatbot_supabase_db_password"
           value=""
           style="width: 300px;"
           placeholder="<?php echo $has_password ? '••••••••••••••••' : 'Enter database password'; ?>">
    <p class="description">
        Your Supabase database password.
        <?php if ($has_password): ?>
            <span style="color: green;">✓ Password is saved</span>
        <?php endif; ?>
        <br>Leave blank to keep existing password.
    </p>
    <?php
}

/**
 * Encrypt password before saving
 */
function chatbot_supabase_encrypt_password($password) {
    // If empty, keep existing password
    if (empty($password)) {
        return get_option('chatbot_supabase_db_password', '');
    }

    // Simple encryption using WordPress auth key
    $key = defined('AUTH_KEY') ? AUTH_KEY : 'chatbot-default-key';
    $encrypted = base64_encode(openssl_encrypt($password, 'AES-256-CBC', $key, 0, substr(md5($key), 0, 16)));

    return $encrypted;
}

/**
 * Decrypt password for use
 */
function chatbot_supabase_decrypt_password($encrypted) {
    if (empty($encrypted)) {
        return '';
    }

    $key = defined('AUTH_KEY') ? AUTH_KEY : 'chatbot-default-key';
    $decrypted = openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $key, 0, substr(md5($key), 0, 16));

    return $decrypted;
}

/**
 * Status section - shows connection test results
 */
function chatbot_supabase_status_section_callback() {
    $config = chatbot_supabase_get_config();

    ?>
    <div class="wrap">
        <div id="supabase-connection-status">
            <?php chatbot_supabase_display_status($config); ?>
        </div>

        <p style="margin-top: 20px;">
            <button type="button" id="test-supabase-connection" class="button button-secondary">
                Test Connection
            </button>
            <span id="connection-test-spinner" class="spinner" style="float: none; margin-left: 10px;"></span>
            <span id="connection-test-result" style="margin-left: 10px;"></span>
        </p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test-supabase-connection').on('click', function() {
            var $btn = $(this);
            var $spinner = $('#connection-test-spinner');
            var $result = $('#connection-test-result');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'chatbot_test_supabase_connection',
                    nonce: '<?php echo wp_create_nonce('chatbot_supabase_test'); ?>'
                },
                success: function(response) {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);

                    if (response.success) {
                        $result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                        // Reload status section
                        location.reload();
                    } else {
                        $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);
                    $result.html('<span style="color: red;">✗ Connection test failed</span>');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Display connection status
 */
function chatbot_supabase_display_status($config) {
    $has_url = !empty($config['project_url']);
    $has_key = !empty($config['anon_key']);
    $has_password = !empty($config['db_password']);

    ?>
    <table class="widefat" style="max-width: 700px;">
        <tbody>
            <tr>
                <td><strong>Project URL</strong></td>
                <td>
                    <?php if ($has_url): ?>
                        <span style="color: green;">✓ Configured</span>
                        <code style="margin-left: 10px;"><?php echo esc_html(substr($config['project_url'], 0, 40)); ?>...</code>
                    <?php else: ?>
                        <span style="color: red;">✗ Not configured</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Anon Key</strong></td>
                <td>
                    <?php if ($has_key): ?>
                        <span style="color: green;">✓ Configured</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Not configured</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Database Password</strong></td>
                <td>
                    <?php if ($has_password): ?>
                        <span style="color: green;">✓ Configured</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠ Not configured (required for direct DB access)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($has_url && $has_key): ?>
            <tr>
                <td><strong>Connection</strong></td>
                <td>
                    <?php
                    $test = chatbot_supabase_test_connection($config);
                    if ($test['success']):
                    ?>
                        <span style="color: green;">✓ Connected</span>
                    <?php else: ?>
                        <span style="color: red;">✗ <?php echo esc_html($test['message']); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($test['success']):
                $faq_count = chatbot_supabase_get_faq_count($config);
            ?>
            <tr>
                <td><strong>FAQs</strong></td>
                <td>
                    <span style="color: <?php echo $faq_count > 0 ? 'green' : '#666'; ?>;">
                        <?php echo intval($faq_count); ?> <?php echo $faq_count == 1 ? 'FAQ found' : 'FAQs found'; ?>
                    </span>
                </td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Get FAQ count from Supabase
 */
function chatbot_supabase_get_faq_count($config) {
    $base_url = rtrim($config['project_url'], '/') . '/rest/v1';
    $url = $base_url . '/chatbot_faqs?select=id';

    $response = wp_remote_get($url, [
        'headers' => [
            'apikey' => $config['anon_key'],
            'Authorization' => 'Bearer ' . $config['anon_key'],
            'Prefer' => 'count=exact'
        ],
        'timeout' => 5
    ]);

    if (is_wp_error($response)) {
        return 0;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        $range = wp_remote_retrieve_header($response, 'content-range');
        if (preg_match('/\/(\d+)$/', $range, $matches)) {
            return intval($matches[1]);
        }
    }

    return 0;
}

/**
 * Get Supabase configuration from options or wp-config.php
 */
function chatbot_supabase_get_config() {
    $config = [
        'project_url' => '',
        'anon_key' => '',
        'db_password' => '',
        'db_host' => '',
        'db_port' => '5432',
        'db_name' => 'postgres',
        'db_user' => ''
    ];

    // Check WordPress options first
    $project_url = get_option('chatbot_supabase_project_url', '');
    $anon_key = get_option('chatbot_supabase_anon_key', '');
    $encrypted_password = get_option('chatbot_supabase_db_password', '');

    if (!empty($project_url)) {
        $config['project_url'] = $project_url;
        // Extract host from URL
        $parsed = parse_url($project_url);
        if (isset($parsed['host'])) {
            $config['db_host'] = str_replace('.supabase.co', '', $parsed['host']) . '.supabase.co';
            // For direct connection, use db. prefix
            $config['db_host'] = 'db.' . $parsed['host'];
            // Extract project ref for user
            $project_ref = str_replace('.supabase.co', '', $parsed['host']);
            $config['db_user'] = 'postgres.' . $project_ref;
        }
    }

    if (!empty($anon_key)) {
        $config['anon_key'] = $anon_key;
    }

    if (!empty($encrypted_password)) {
        $config['db_password'] = chatbot_supabase_decrypt_password($encrypted_password);
    }

    // Fall back to wp-config.php constants
    if (empty($config['db_host']) && defined('CHATBOT_PG_HOST')) {
        $config['db_host'] = CHATBOT_PG_HOST;
        $config['project_url'] = 'https://' . str_replace('db.', '', CHATBOT_PG_HOST);
    }

    if (empty($config['anon_key']) && defined('CHATBOT_SUPABASE_ANON_KEY')) {
        $config['anon_key'] = CHATBOT_SUPABASE_ANON_KEY;
    }

    if (empty($config['db_password']) && defined('CHATBOT_PG_PASSWORD')) {
        $config['db_password'] = CHATBOT_PG_PASSWORD;
    }

    if (defined('CHATBOT_PG_PORT')) {
        $config['db_port'] = CHATBOT_PG_PORT;
    }

    if (defined('CHATBOT_PG_DATABASE')) {
        $config['db_name'] = CHATBOT_PG_DATABASE;
    }

    if (defined('CHATBOT_PG_USER')) {
        $config['db_user'] = CHATBOT_PG_USER;
    }

    return $config;
}

/**
 * Test Supabase connection
 */
function chatbot_supabase_test_connection($config = null) {
    if ($config === null) {
        $config = chatbot_supabase_get_config();
    }

    $result = [
        'success' => false,
        'message' => ''
    ];

    if (empty($config['project_url']) || empty($config['anon_key'])) {
        $result['message'] = 'Missing configuration';
        return $result;
    }

    // Test REST API connection
    $url = rtrim($config['project_url'], '/') . '/rest/v1/faqs?select=count&limit=1';

    $response = wp_remote_get($url, [
        'headers' => [
            'apikey' => $config['anon_key'],
            'Authorization' => 'Bearer ' . $config['anon_key']
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        $result['message'] = 'Connection failed: ' . $response->get_error_message();
        return $result;
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code === 200) {
        $result['success'] = true;
        $result['message'] = 'Successfully connected to Supabase';

        // Try to get FAQ count
        $count_url = rtrim($config['project_url'], '/') . '/rest/v1/faqs?select=id';
        $count_response = wp_remote_get($count_url, [
            'headers' => [
                'apikey' => $config['anon_key'],
                'Authorization' => 'Bearer ' . $config['anon_key'],
                'Prefer' => 'count=exact'
            ],
            'timeout' => 10
        ]);

        if (!is_wp_error($count_response)) {
            $range = wp_remote_retrieve_header($count_response, 'content-range');
            if (preg_match('/\/(\d+)$/', $range, $matches)) {
                $result['faq_count'] = intval($matches[1]);
            }
        }
    } elseif ($code === 404) {
        // Table might not exist yet
        $result['success'] = true;
        $result['message'] = 'Connected (FAQs table may need to be created)';
        $result['faq_count'] = 0;
    } else {
        $body = wp_remote_retrieve_body($response);
        $result['message'] = "Connection failed (HTTP $code)";
    }

    return $result;
}

/**
 * AJAX handler for connection test
 */
function chatbot_test_supabase_connection_ajax() {
    check_ajax_referer('chatbot_supabase_test', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $result = chatbot_supabase_test_connection();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_chatbot_test_supabase_connection', 'chatbot_test_supabase_connection_ajax');
