<?php
/**
 * Steven-Bot - Settings - Tools - Ver 2.0.6
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the support settings and other parameters.
 * 
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Tools settings - Ver 2.0.7
function steven_bot_tools_settings_init() {

    // Register tools settings
    register_setting('steven_bot_tools', 'steven_bot_options_exporter_extension', 'steven_bot_sanitize_text');

    // Tools Overview
    add_settings_section(
        'steven_bot_tools_overview_section',
        'Tools Overview',
        'steven_bot_tools_overview_section_callback',
        'steven_bot_tools_overview'
    );

    // options_exporter Check Overview
    add_settings_section(
        'steven_bot_options_exporter_tools_section',
        'Options Exporter Extension',
        'steven_bot_options_exporter_tools_section_callback',
        'steven_bot_tools'
    );

    // options_exporter Check Tool
    add_settings_field(
        'steven_bot_options_exporter_extension',
        'Options Exporter Extension',
        'steven_bot_options_exporter_tools_callback',
        'steven_bot_tools',
        'steven_bot_options_exporter_tools_section'
    );

    add_settings_section(
        'steven_bot_options_exporter_button_section',
        'Options Exporter',
        'steven_bot_options_exporter_button_callback',
        'steven_bot_tools_exporter_button'
    );

    // Manage Error Logs
    add_settings_section(
        'steven_bot_manage_error_logs_section',
        'Manage Error Logs',
        'steven_bot_manage_error_logs_section_callback',
        'steven_bot_manage_error_logs'
    );

    // Manage Widget Logs
    add_settings_section(
        'chatbot_manage_widget_logs_section',
        'Manage Widget Access Logs',
        'chatbot_manage_widget_logs_section_callback',
        'chatbot_manage_widget_logs'
    );
    
    // Shortcode Tester Overview
    add_settings_section(
        'steven_bot_shortcode_tools_section',
        'Shortcode Tester',
        'steven_bot_shortcode_tools_section_callback',
        'steven_bot_shortcode_tools'
    );

    // Capability Check Overview
    add_settings_section(
        'steven_bot_capability_tools_section',
        'Capability Check',
        'steven_bot_capability_tools_section_callback',
        'steven_bot_capability_tools'
    );
   
}
add_action('admin_init', 'steven_bot_tools_settings_init');

// Add the Tools section
function steven_bot_tools_overview_section_callback() {

    ?>
    <div>
        <p>This tab provides tools, tests and diagnostics that are enabled when the Chatbot Diagnostics are enabled on the Messages tab.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Tool settings and additional documentation please click <a href="?page=steven-bot&tab=support&dir=tools&file=tools.md">here</a>.</b></p>
    </div>
    <?php
    
}

// Options Exporter
function steven_bot_options_exporter_tools_section_callback() {

    ?>
    <div>
        <p>Export the Chatbot options to a file.</p>
        <p><b>NOTE:</b> If you change the format from CSV to JSON, or vice versa, you will need to scroll to the bottom of the page and <code>Save Changes</code> to update the format.</p>
    </div>
    <?php

}

// Export the chatbot options to a file
function steven_bot_options_exporter_tools_callback() {

    // Get the saved steven_bot_options_exporter_extension value or default to "CSV"
    $output_choice = esc_attr(get_option('steven_bot_options_exporter_extension', 'CSV'));
    ?>
    <div>
        <select id="steven_bot_options_exporter_extension" name="steven_bot_options_exporter_extension">
            <option value="<?php echo esc_attr( 'csv' ); ?>" <?php selected( $output_choice, 'csv' ); ?>><?php echo esc_html( 'CSV' ); ?></option>
            <option value="<?php echo esc_attr( 'json' ); ?>" <?php selected( $output_choice, 'json' ); ?>><?php echo esc_html( 'JSON' ); ?></option>
        </select>
    </div>
    <?php

}

function steven_bot_options_exporter_button_callback() {

    ?>
    <div>
        <p>Use the button (below) to retrieve the chatbot options and download the file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=steven_bot_download_options_data')) . '">Download Options Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php

}

// Manage Error Logs
function steven_bot_manage_error_logs_section_callback() {

    ?>
    <div>
        <p>Click the <code>Download</code> button to retrieve a log file, or the <code>Delete</code> button to remove a log file.</p>
        <p>Click the <code>Delete All</code> button to remove all log files.</p>
    </div>
    <?php

    // Call the capability tester
    steven_bot_manage_error_logs();

}

// Add the Shortcode Tester
function steven_bot_shortcode_tools_section_callback($args) {

    ?>
    <div>
        <p>This tool automatically tests the Chatbot Shortcode. There are three tests in all. Test 1 checks calling shortcodes without any parameters.  Test 2 checks calling a shortcode with a single parameter. And, Test 3 checks calling a shortcode with three parameters. The results are displayed below the tests.</p>
    </div>
    <?php

    // Call the shortcode tester
    chatbot_shortcode_tester();

}

// Capability Check Overview
function steven_bot_capability_tools_section_callback() {

    ?>
    <div>
        <p>This tool allows you to check the permissions for various features.</p>
    </div>
    <?php

    // Call the capability tester
    steven_bot_capability_tester();

}
