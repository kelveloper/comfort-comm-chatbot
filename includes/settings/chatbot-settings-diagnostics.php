<?php
/**
 * Steven-Bot - Settings - Diagnostics
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the reporting and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Diagnostics settings - Ver 2.0.7
function steven_bot_diagnostics_settings_init() {

    register_setting('steven_bot_diagnostics', 'steven_bot_diagnostics', 'steven_bot_sanitize_checkbox');
    register_setting('steven_bot_diagnostics', 'steven_bot_custom_error_message', 'steven_bot_sanitize_text');
    register_setting('steven_bot_diagnostics', 'steven_bot_suppress_notices', 'steven_bot_sanitize_checkbox');
    register_setting('steven_bot_diagnostics', 'steven_bot_suppress_attribution', 'steven_bot_sanitize_checkbox');
    register_setting('steven_bot_diagnostics', 'steven_bot_custom_attribution', 'steven_bot_sanitize_text');
    register_setting('steven_bot_diagnostics', 'steven_bot_delete_data', 'steven_bot_sanitize_checkbox');
    register_setting('steven_bot_diagnostics', 'steven_bot_enable_beta_features', 'steven_bot_sanitize_checkbox');

    add_settings_section(
        'steven_bot_diagnostics_overview_section',
        'Messages and Diagnostics Overview',
        'steven_bot_diagnostics_overview_section_callback',
        'steven_bot_diagnostics_overview'
    );

    add_settings_section(
        'steven_bot_diagnostics_system_settings_section',
        'Platform Settings',
        'steven_bot_diagnostics_system_settings_section_callback',
        'steven_bot_diagnostics_system_settings'
    );

    // Diagnotics API Status
    add_settings_section(
        'steven_bot_diagnostics_api_status_section',
        'API Status and Results',
        'steven_bot_diagnostics_api_status_section_callback',
        'steven_bot_diagnostics_api_status'
    );

    add_settings_field(
        'steven_bot_api_test',
        'API Test Results',
        'steven_bot_api_test_callback',
        'steven_bot_diagnostics',
        'steven_bot_diagnostics_api_status_section'
    );

    // Simplified Settings Section - just the essential setting
    add_settings_section(
        'steven_bot_diagnostics_simple_section',
        'Plugin Settings',
        'steven_bot_diagnostics_simple_section_callback',
        'steven_bot_diagnostics_simple'
    );

    // Option to delete data on uninstall - Ver 1.9.9
    add_settings_field(
        'steven_bot_delete_data',
        'Delete Plugin Data on Uninstall',
        'steven_bot_delete_data_callback',
        'steven_bot_diagnostics_simple',
        'steven_bot_diagnostics_simple_section'
    );

    // Advanced Settings Section - Ver 2.3.6
    add_settings_section(
        'steven_bot_advanced_section',                 // ID
        'Advanced',                                          // Title
        'steven_bot_advanced_section_callback',        // Callback
        'steven_bot_advanced'                          // Page
    );

    // Reset Cache/Locks Field - Ver 2.3.6
    add_settings_field(
        'steven_bot_reset_cache_locks',                // ID
        'Reset Cache/Locks',                                // Title
        'steven_bot_reset_cache_locks_callback',       // Callback
        'steven_bot_advanced',                         // Page
        'steven_bot_advanced_section'                  // Section
    );

}
add_action('admin_init', 'steven_bot_diagnostics_settings_init');

// Simple diagnostics section callback
function steven_bot_diagnostics_simple_section_callback($args) {
    ?>
    <p>Configure basic plugin settings.</p>
    <?php
}

// Diagnostics overview section callback - Ver 2.0.7
function steven_bot_diagnostics_overview_section_callback($args) {
    ?>
        <p>The Diagnostics tab checks the API status and set options for diagnostics and notices.</p>
        <p>You can turn on/off console and error logging (as of Version 1.6.5 most are now commented out).</p>
        <p>You can also suppress attribution and notices by setting the value to 'On' (suppress) or 'Off' (no suppression).</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the diagnostics, messages, and additional documentation please click <a href="?page=steven-bot&tab=support&dir=messages&file=messages.md">here</a>.</b></p>
    <?php
}

function steven_bot_diagnostics_system_settings_section_callback($args) {

    // Get PHP version
    $php_version = phpversion();

    // Get WordPress version
    global $wp_version;
    global $steven_bot_plugin_version;

    echo '<p>Chatbot Version: <b>' . $steven_bot_plugin_version . '</b><br>';
    echo 'PHP Version: <b>' . $php_version . '</b><br>';
    echo 'PHP Memory Limit: <b>' . ini_get('memory_limit') . '</b><br>';
    echo 'WordPress Version: <b>' . $wp_version . '</b><br>';
    echo 'WordPress Language Code: <b>' . get_locale() . '</b></p>';

}

// Diagnostics settings section callback - Ver 1.6.5
function steven_bot_diagnostics_section_callback($args) {
    ?>
        <p>Choose your settings for Diagnostics, a Custom Error Message, Suppress Notices, Suppress Attribution, and Plugin Data retention settings.</p>
    <?php
}

// API Status and Results section callback - Ver 2.0.7
function steven_bot_diagnostics_api_status_section_callback($args) {
    // Check Supabase/Vector Search status (primary for FAQ responses)
    $supabase_status = chatbot_check_supabase_status();

    // Get the AI platform for fallback responses
    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    // Get cached LLM status based on platform
    switch ($chatbot_ai_platform_choice) {
        case 'Gemini':
            $cached_status = get_option('chatbot_gemini_api_status', 'Not tested yet');
            break;
        case 'OpenAI':
            $cached_status = get_option('steven_bot_api_status', 'Not tested yet');
            break;
        case 'Anthropic':
            $cached_status = get_option('chatbot_anthropic_api_status', 'Not tested yet');
            break;
        default:
            $cached_status = get_option('steven_bot_api_status', 'Not tested yet');
    }
    ?>
    <h4>Knowledge Base (Supabase Vector Search)</h4>
    <p><strong>Status:</strong>
        <?php if ($supabase_status['connected']): ?>
            <span style="color: green;">✓ Connected</span>
            <?php if ($supabase_status['faq_count'] > 0): ?>
                - <?php echo esc_html($supabase_status['faq_count']); ?> FAQs loaded
            <?php endif; ?>
        <?php else: ?>
            <span style="color: red;">✗ Not Connected</span>
            <?php if (!empty($supabase_status['error'])): ?>
                - <?php echo esc_html($supabase_status['error']); ?>
            <?php endif; ?>
        <?php endif; ?>
    </p>
    <p><em>This is your primary response source - FAQ matching via semantic search.</em></p>

    <hr style="margin: 20px 0;">

    <h4>AI Fallback (<?php echo esc_html($chatbot_ai_platform_choice); ?>)</h4>
    <p><strong>Last Status:</strong> <span id="api-status-result"><?php echo esc_html($cached_status); ?></span></p>
    <p>
        <button type="button" id="test-api-button" class="button button-secondary">Test AI API</button>
        <span id="api-test-loading" style="display:none; margin-left: 10px;">Testing...</span>
    </p>
    <p><em>Only used when no FAQ match is found. Each test uses API quota.</em></p>

    <script>
    jQuery(document).ready(function($) {
        $('#test-api-button').on('click', function() {
            var button = $(this);
            var loading = $('#api-test-loading');
            var result = $('#api-status-result');

            button.prop('disabled', true);
            loading.show();
            result.text('Testing...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'chatbot_test_api_status',
                    chatbot_nonce: '<?php echo wp_create_nonce('chatbot_test_api'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        result.text(response.data);
                    } else {
                        result.text('Error: ' + response.data);
                    }
                },
                error: function() {
                    result.text('Error: Failed to test API');
                },
                complete: function() {
                    button.prop('disabled', false);
                    loading.hide();
                }
            });
        });
    });
    </script>
    <?php
}

// Check Supabase connection status
function chatbot_check_supabase_status() {
    $result = [
        'connected' => false,
        'faq_count' => 0,
        'error' => ''
    ];

    // Check if Supabase is configured
    $pg_host = defined('CHATBOT_PG_HOST') ? CHATBOT_PG_HOST : '';
    $pg_password = defined('CHATBOT_PG_PASSWORD') ? CHATBOT_PG_PASSWORD : '';

    if (empty($pg_host) || strpos($pg_host, 'YOUR_SUPABASE') !== false) {
        $result['error'] = 'Supabase not configured in wp-config.php';
        return $result;
    }

    if (empty($pg_password) || strpos($pg_password, 'YOUR_SUPABASE') !== false) {
        $result['error'] = 'Supabase password not set';
        return $result;
    }

    // Try to connect
    if (function_exists('chatbot_vector_get_pg_connection')) {
        $pdo = chatbot_vector_get_pg_connection();
        if ($pdo) {
            $result['connected'] = true;

            // Count FAQs
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM chatbot_faqs WHERE is_active = true");
                $result['faq_count'] = (int) $stmt->fetchColumn();
            } catch (Exception $e) {
                // Table might not exist yet
                $result['faq_count'] = 0;
            }
        } else {
            $result['error'] = 'Could not connect to database';
        }
    } else {
        $result['error'] = 'Vector search module not loaded';
    }

    return $result;
}

// Call the api-test.php file to test the API
function steven_bot_api_test_callback($args) {

    $updated_status = kchat_test_api_status();
    ?>
    <p>API STATUS: <b><?php echo esc_html( $updated_status ); ?></b></p>
    <?php

}

// Diagnostics On/Off - Ver 1.6.5
function steven_bot_diagnostics_setting_callback($args) {
    $steven_bot_diagnostics = esc_attr(get_option('steven_bot_diagnostics', 'Off'));
    ?>
    <select id="steven_bot_diagnostics" name = "steven_bot_diagnostics">
        <option value="Off" <?php selected( $steven_bot_diagnostics, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
        <option value="Success" <?php selected( $steven_bot_diagnostics, 'Success' ); ?>><?php echo esc_html( 'Success' ); ?></option>
        <option value="Notice" <?php selected( $steven_bot_diagnostics, 'Notice' ); ?>><?php echo esc_html( 'Notice' ); ?></option>
        <option value="Failure" <?php selected( $steven_bot_diagnostics, 'Failure' ); ?>><?php echo esc_html( 'Failure' ); ?></option>
        <option value="Warning" <?php selected( $steven_bot_diagnostics, 'Warning' ); ?>><?php echo esc_html( 'Warning' ); ?></option>
        <option value="Error" <?php selected( $steven_bot_diagnostics, 'Error' ); ?>><?php echo esc_html( 'Error' ); ?></option>
     </select>
    <?php
}

// Custom Error Message - Ver 2.0.3
function steven_bot_custom_error_message_callback($args) {
    $steven_bot_custom_error_message = esc_attr(get_option('steven_bot_custom_error_message', 'Your custom error message goes here.'));
    if ( $steven_bot_custom_error_message === null || $steven_bot_custom_error_message === '' ) {
        $steven_bot_custom_error_message = 'Your custom error message goes here.';
    }
    ?>
    <input type="text" id="steven_bot_custom_error_message" name="steven_bot_custom_error_message" value="<?php echo esc_html( $steven_bot_custom_error_message ); ?>" size="50">
    <?php
}

// Suppress Notices On/Off - Ver 1.6.5
function steven_bot_suppress_notices_callback($args) {
    global $steven_bot_suppress_notices;
    $steven_bot_suppress_notices = esc_attr(get_option('steven_bot_suppress_notices', 'Off'));
    ?>
    <select id="chatgpt_suppress_notices_setting" name = "steven_bot_suppress_notices">
        <option value="On" <?php selected( $steven_bot_suppress_notices, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $steven_bot_suppress_notices, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Suppress Attribution On/Off - Ver 1.6.5
function steven_bot_suppress_attribution_callback($args) {
    global $steven_bot_suppress_attribution;
    $steven_bot_suppress_attribution = esc_attr(get_option('steven_bot_suppress_attribution', 'On'));
    ?>
    <select id="chatgpt_suppress_attribution_setting" name = "steven_bot_suppress_attribution">
        <option value="On" <?php selected( $steven_bot_suppress_attribution, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $steven_bot_suppress_attribution, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Alternate Attribution Text - Ver 2.0.9
function steven_bot_custom_attribution_callback($args) {
    $steven_bot_custom_attribution = esc_attr(get_option('steven_bot_custom_attribution', 'Your custom attribution message goes here.'));
    if ( $steven_bot_custom_attribution === null || $steven_bot_custom_attribution === '' ) {
        $steven_bot_custom_attribution = 'Your custom attribution message goes here.';
    }
    ?>
    <input type="text" id="steven_bot_custom_attribution" name="steven_bot_custom_attribution" value="<?php echo esc_html( $steven_bot_custom_attribution ); ?>" size="50">
    <?php
}

// Delete Plugin Data on Uninstall - Ver 1.9.9
function steven_bot_delete_data_callback($args) {
    global $steven_bot_delete_data;
    $steven_bot_delete_data = esc_attr(get_option('steven_bot_delete_data', 'no'));
    ?>
    <select id="chatgpt_delete_data_setting" name="steven_bot_delete_data">
    <option value="no" <?php selected( $steven_bot_delete_data, 'no' ); ?>><?php echo esc_html( 'DO NOT DELETE' ); ?></option>
    <option value="yes" <?php selected( $steven_bot_delete_data, 'yes' ); ?>><?php echo esc_html( 'DELETE ALL DATA' ); ?></option>
    </select>
    <?php
}

// Beta Feature Settings Section - Ver 2.2.1
function steven_bot_beta_features_section_callback($args) {
    ?>
        <div class="chatbot-beta-disclaimer">
            <h3>Caution: Beta Features Ahead 🚧</h3>
            <p>
                Enabling Beta Features in the Steven-Bot plugin is intended for testing and experimental purposes only. 
                <strong>These features are not fully tested or guaranteed to work as expected</strong> and may cause unexpected behavior, errors, or conflicts with your website.
            </p>
            <p><strong>Important Notices:</strong></p>
            <ol>
                <li><strong>Backup Your Site:</strong> Before enabling Beta Features, ensure you have a complete backup of your WordPress site and database.</li>
                <li><strong>Test Environment Recommended:</strong> Beta Features should only be enabled in a testing or staging environment. Avoid enabling them on live or production sites.</li>
                <li><strong>Use at Your Own Risk:</strong> We assume no liability for issues arising from the use of Beta Features. By enabling them, you accept full responsibility for any changes or damage to your site.</li>
            </ol>
            <p>
                If you're unsure about any of these steps, consult with a web professional or WordPress expert before proceeding.
            </p>
        </div>
    <?php
}

// Enable Beta Features - Ver 2.2.1
function steven_bot_enable_beta_features_callback($args) {
    global $steven_bot_enable_beta_features;
    $steven_bot_enable_beta_features = esc_attr(get_option('steven_bot_enable_beta_features', 'no'));
    ?>
    <select id="chatgpt_enable_beta_features_setting" name="steven_bot_enable_beta_features">
    <option value="no" <?php selected( $steven_bot_enable_beta_features, 'no' ); ?>><?php echo esc_html( 'NO' ); ?></option>
    <option value="yes" <?php selected( $steven_bot_enable_beta_features, 'yes' ); ?>><?php echo esc_html( 'YES' ); ?></option>
    </select>
    <?php
}

// Production Back Trace Function - Revised in Ver 2.1.5
function prod_trace($message_type = "NOTICE", $message = "No message") {

    // Trace production messages to the error log
    back_trace($message_type, $message);

}

// Back Trace Function - Revised in Ver 2.0.7
function back_trace($message_type = "NOTICE", $message = "No message") {

    // Usage Instructions
    // 
    // NOTE: Set WP_DEBUG and WP_DEBUG_LOG to true in wp-config.php to log messages to the debug.log file
    // 
    // Call the function back_trace() from any file to log messages to your server's error log
    // 
    // Uncomment the back_trace() function in the file(s) where you want to log messages
    // Or add new back_trace() calls to log messages at any point in the code
    //
    // Go to the Chatbot Settings, then the Messages tab
    // Set the Chatbot Diagnotics to one of Off, Success, Notice, Failure, Warning, or Error
    //
    // Each level will log messages based on the following criteria (Off will not log any messages)
    // [ERROR], [WARNING], [NOTICE], or [SUCCESS]
    // 
    // Call this function using // back_trace( 'NOTICE', $message);
    // back_trace( 'ERROR', 'Some message');
    // back_trace( 'WARNING', 'Some message');
    // back_trace( 'NOTICE', 'Some message');
    // back_trace( 'SUCCESS', 'Some message');

    // Check if diagnostics is On
    $steven_bot_diagnostics = esc_attr(get_option('steven_bot_diagnostics', 'Error'));
    if ('Off' === $steven_bot_diagnostics) {
        return;
    }

    // Belt and suspenders - make sure the value is either Off or Error
    if ('On' === $steven_bot_diagnostics) {
        $steven_bot_diagnostics = 'Error';
        update_option('steven_bot_diagnostics', $steven_bot_diagnostics);
    }

    $backtrace = debug_backtrace();
    // $caller = isset($backtrace[1]) ? $backtrace[1] : null; // Get the second element from the backtrace array
    $caller = isset($backtrace[0]) ? $backtrace[0] : null; // Get the first element from the backtrace array

    if ($caller) {
        $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $function = isset($caller['function']) ? $caller['function'] : 'unknown';
        $line = isset($caller['line']) ? $caller['line'] : 'unknown';
    } else {
        $file = 'unknown';
        $function = 'unknown';
        $line = 'unknown';
    }

    if ($message === null || $message === '') {
        $message = "No message";
    }
    if ($message_type === null || $message_type === '') {
        $message_type = "NOTICE";
    }

    // Convert array or object messages to JSON strings
    if (is_array($message) || is_object($message)) {
        $message = wp_json_encode($message, JSON_PRETTY_PRINT);
    }

    // Upper case the message type
    $message_type = strtoupper($message_type);

    $date_time = (new DateTime())->format('d-M-Y H:i:s \U\T\C');

    // Message Type: Indicating whether the log is an error, warning, notice, or success message.
    // Prefix the message with [ERROR], [WARNING], [NOTICE], or [SUCCESS].
    // Check for other levels and print messages accordingly
    if ('Error' === $steven_bot_diagnostics) {
        // Print all types of messages
        error_log("[Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]");
        chatbot_error_log( "[". $date_time ."] [Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]" );
    } elseif (in_array($steven_bot_diagnostics, ['Success', 'Failure'])) {
        // Print only SUCCESS and FAILURE messages
        if (in_array($message_type, ['SUCCESS', 'FAILURE'])) {
            error_log("[Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]");
            chatbot_error_log( "[". $date_time ."] [Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]" );
        }
    } elseif ('Warning' === $steven_bot_diagnostics) {
        // Print only ERROR and WARNING messages
        if (in_array($message_type, ['ERROR', 'WARNING'])) {
            error_log("[Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]");
            chatbot_error_log( "[". $date_time ."] [Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]" );
        }
    } elseif ('Notice' === $steven_bot_diagnostics) {
        // Print ERROR, WARNING, and NOTICE messages
        if (in_array($message_type, ['ERROR', 'WARNING', 'NOTICE'])) {
            error_log("[Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]");
            chatbot_error_log( "[". $date_time ."] [Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]" );
        }
    } elseif ('Debug' === $steven_bot_diagnostics) {
        // Print all types of messages
        error_log("[Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]");
        chatbot_error_log( "[". $date_time ."] [Chatbot] [". $file ."] [". $function ."] [". $line  ."] [". $message_type ."] [" .$message ."]" );
    } else {
        // Exit if none of the conditions are met
        return;
    }

}

// Log Chatbot Errors to the Server - Ver 2.0.9
function chatbot_error_log($message) {

    global $wp_filesystem;
    global $steven_bot_plugin_dir_path;

    $chatbot_logs_dir = $steven_bot_plugin_dir_path . 'chatbot-logs/';

    // Ensure the directory and index file exist
    create_directory_and_index_file($chatbot_logs_dir);

    // Get the current date to create a daily log file
    $current_date = date('Y-m-d');
    
    $log_file = $chatbot_logs_dir . 'chatbot-error-log-' . $current_date . '.log';

    // Debug: Log the file path and method being used
    // error_log('[Chatbot] [chatbot-settings-diagnotics.php] Writing to log file: ' . $log_file);

    // Check and fix file permissions if needed
    if (file_exists($log_file)) {
        $current_perms = fileperms($log_file);
        if (($current_perms & 0x0080) === 0) { // Check if writable by owner
            chmod($log_file, 0644);
            error_log('[Chatbot] [chatbot-settings-diagnotics.php] Fixed file permissions for: ' . $log_file);
        }
        
        // Check if file is currently being used by another process
        if (is_file_in_use($log_file)) {
            error_log('[Chatbot] [chatbot-settings-diagnotics.php] File appears to be in use by another process: ' . $log_file);
        }
    }

    // Initialize the WordPress filesystem if not already initialized
    if (!function_exists('WP_Filesystem')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    // Initialize the filesystem if not already done
    if (!$wp_filesystem) {
        $access_type = get_filesystem_method();
        if ($access_type === 'direct') {
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, array());
            if (WP_Filesystem($creds)) {
                // Filesystem initialized successfully
                error_log('[Chatbot] [chatbot-settings-diagnotics.php] WordPress filesystem initialized successfully');
            } else {
                // Fallback to file_put_contents if filesystem initialization fails
                error_log('[Chatbot] [chatbot-settings-diagnotics.php] Falling back to native file_put_contents');
                file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
                return;
            }
        } else {
            // Fallback to file_put_contents if direct access is not available
            error_log('[Chatbot] [chatbot-settings-diagnotics.php] Direct access not available, using native file_put_contents');
            file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }
    }

    // Append the error message to the log file
    if ($wp_filesystem) {

        // error_log('[Chatbot] [chatbot-settings-diagnotics.php] Using WordPress filesystem to write log');
        // WordPress filesystem doesn't support FILE_APPEND flag, so we need to read existing content first
        $existing_content = '';
        if ($wp_filesystem->exists($log_file)) {
            $existing_content = $wp_filesystem->get_contents($log_file);
            //error_log('[Chatbot] [chatbot-settings-diagnotics.php] Existing content length: ' . strlen($existing_content));
        } else {
            //error_log('[Chatbot] [chatbot-settings-diagnotics.php] Log file does not exist, creating new file');
        }
        
        // Append the new message to existing content
        $new_content = $existing_content . $message . PHP_EOL;
        
        // Write the combined content back to the file with proper locking
        $result = $wp_filesystem->put_contents($log_file, $new_content, 0644);
        if ($result === false) {
            // error_log('[Chatbot] [chatbot-settings-diagnotics.php] WordPress filesystem write failed, falling back to native method');
            // Try to fix file permissions before attempting to write
            if (file_exists($log_file)) {
                chmod($log_file, 0644);
            }
            // Use fopen with proper error handling
            $handle = @fopen($log_file, 'a');
            if ($handle) {
                if (flock($handle, LOCK_EX)) { // Exclusive lock
                    fwrite($handle, $message . PHP_EOL);
                    flock($handle, LOCK_UN); // Release lock
                    fclose($handle);
                    // error_log('[Chatbot] [chatbot-settings-diagnotics.php] Native fopen write successful');
                } else {
                    fclose($handle);
                    // error_log('[Chatbot] [chatbot-settings-diagnotics.php] Failed to acquire file lock');
                    // Try alternative method without locking
                    @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
                }
            } else {
                // error_log('[Chatbot] [chatbot-settings-diagnotics.php] Failed to open file for writing: ' . $log_file);
                // Try to create the file with proper permissions
                $dir = dirname($log_file);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (touch($log_file)) {
                    chmod($log_file, 0644);
                    @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
                } else {
                    // Last resort: try to write to a temporary file and then move it
                    $temp_file = $log_file . '.tmp';
                    if (@file_put_contents($temp_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX)) {
                        if (file_exists($log_file)) {
                            $existing_content = @file_get_contents($log_file);
                            if ($existing_content !== false) {
                                @file_put_contents($temp_file, $existing_content . $message . PHP_EOL, LOCK_EX);
                            }
                        }
                        @rename($temp_file, $log_file);
                    }
                }
            }
        
        } else {

            // error_log('[Chatbot] [chatbot-settings-diagnotics.php] WordPress filesystem write successful, bytes written: ' . $result);
        
        }

    } else {

        // Fallback to file_put_contents if $wp_filesystem is still not available
        // error_log('[Chatbot] [chatbot-settings-diagnotics.php] WordPress filesystem not available, using native file_put_contents');
        // Use exclusive lock to prevent race conditions
        $handle = @fopen($log_file, 'a');
        if ($handle) {
            if (flock($handle, LOCK_EX)) { // Exclusive lock
                fwrite($handle, $message . PHP_EOL);
                flock($handle, LOCK_UN); // Release lock
                fclose($handle);
            } else {
                fclose($handle);
                // If locking fails, try without lock
                file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
            }
        } else {
            // If fopen fails, try to create the file
            $dir = dirname($log_file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if (touch($log_file)) {
                chmod($log_file, 0644);
                @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }
    
    }
    
}

// Log Chatbot Errors to the Server - Ver 2.0.3
function log_chatbot_error() {

    // Security: Rate limiting for unauthenticated users to prevent log spam
    $user_id = get_current_user_id();
    $is_authenticated = $user_id > 0;
    
    if (!$is_authenticated) {
        // Get client IP for rate limiting
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rate_limit_key = 'chatbot_log_rate_limit_' . (function_exists('wp_fast_hash') ? wp_fast_hash($client_ip) : hash('sha256', $client_ip));
        
        // Check rate limit (max 5 log entries per minute for unauthenticated users)
        $current_count = get_transient($rate_limit_key) ?: 0;
        if ($current_count >= 5) {
            wp_die('Rate limit exceeded for logging.', 'Rate Limit Exceeded', array('response' => 429));
        }
        
        // Increment rate limit counter
        set_transient($rate_limit_key, $current_count + 1, 60); // 60 seconds
    }

    global $steven_bot_plugin_dir_path;
    
    if (isset($_POST['error_message'])) {

        $error_message = sanitize_text_field($_POST['error_message']);

        $chatbot_logs_dir = $steven_bot_plugin_dir_path . 'chatbot-logs/';

        // Ensure the directory and index file exist
        create_directory_and_index_file($chatbot_logs_dir);

        // Get the current date to create a daily log file
        $current_date = date('Y-m-d');

        $log_file = $chatbot_logs_dir . 'chatbot-error-log-' . $current_date . '.log';

        // Get additional info
        $session_id = session_id();
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $date_time = date('Y-m-d H:i:s');

        // Construct the log message
        $log_message = sprintf(
            "[Chatbot] [ERROR] [%s] [Session ID: %s] [User ID: %s] [IP Address: %s] [%s] [%s]",
            $date_time,
            $session_id ? $session_id : 'N/A',
            $user_id ? $user_id : 'N/A',
            $ip_address,
            $error_message,
            PHP_EOL
        );

        // Append the error message to the log file
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
    
    wp_die(); // this is required to terminate immediately and return a proper response
    
}
// Register AJAX actions
add_action('wp_ajax_log_chatbot_error', 'log_chatbot_error');
add_action('wp_ajax_nopriv_log_chatbot_error', 'log_chatbot_error');

// Test function to verify logging functionality
function test_chatbot_logging() {

    $test_message = '[' . date('Y-m-d H:i:s') . '] [Chatbot] [chatbot-settings-diagnotics.php] This is a test log message to verify logging functionality.';
    chatbot_error_log($test_message);
    return 'Test log message written. Check the log file to verify.';

}

// Function to check if a file is currently being used by another process
function is_file_in_use($file_path) {

    if (!file_exists($file_path)) {
        return false;
    }
    
    $handle = @fopen($file_path, 'r');
    if ($handle) {
        fclose($handle);
        return false; // File is not in use
    }
    
    return true; // File might be in use

}

// Add a simple way to test logging (for debugging purposes)
if (isset($_GET['test_logging']) && current_user_can('manage_options')) {

    add_action('admin_notices', function() {
        $result = test_chatbot_logging();
        echo '<div class="notice notice-info"><p>' . esc_html($result) . '</p></div>';
    });

}

// Advanced Settings Section Callback - Ver 2.3.6
function steven_bot_advanced_section_callback($args) {
    ?>
    <p><strong>⚠️ Caution: Advanced Features Ahead</strong></p>
    <p>Use these advanced features with caution. They can affect the performance and stability of your chatbot.</p>
    <p>These tools are designed for troubleshooting and maintenance purposes only.</p>
    <?php
}

// Reset Cache/Locks Callback - Ver 2.3.6
function steven_bot_reset_cache_locks_callback($args) {
    ?>
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px;">
        <p><strong>⚠️ Warning:</strong> This action will:</p>
        <ul style="margin-left: 20px;">
            <li>Clear all conversation locks</li>
            <li>Reset message queues</li>
            <li>Clean up expired transients</li>
            <li>Clear cached data</li>
        </ul>
        <p><strong>Use this only if you're experiencing issues with stuck conversations or performance problems.</strong></p>
        
        <button type="button" id="chatbot-reset-cache-locks" class="button button-secondary" style="background-color: #dc3545; color: white; border-color: #dc3545;">
            Reset Cache/Locks
        </button>
        
        <div id="chatbot-reset-status" style="margin-top: 10px; display: none;"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#chatbot-reset-cache-locks').on('click', function() {
            if (!confirm('Are you sure you want to reset all cache and locks? This action cannot be undone.')) {
                return;
            }
            
            var button = $(this);
            var statusDiv = $('#chatbot-reset-status');
            
            button.prop('disabled', true).text('Resetting...');
            statusDiv.show().html('<p style="color: #0073aa;">⏳ Resetting cache and locks...</p>');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'steven_bot_reset_cache_locks',
                    chatbot_nonce: '<?php echo wp_create_nonce('chatbot_reset_cache_locks'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<p style="color: #28a745;">✅ ' + response.data + '</p>');
                    } else {
                        statusDiv.html('<p style="color: #dc3545;">❌ Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    statusDiv.html('<p style="color: #dc3545;">❌ Error: Failed to reset cache and locks</p>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Reset Cache/Locks');
                    setTimeout(function() {
                        statusDiv.fadeOut();
                    }, 5000);
                }
            });
        });
    });
    </script>
    <?php
}
