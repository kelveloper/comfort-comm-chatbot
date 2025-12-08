<?php
/**
 * Steven-Bot - Upgrade the Plugin
 *
 * This file contains the code for upgrading the plugin.
 * It should run with the plugin is activated or updated.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Activation Hook - Revised 1.7.6
function steven_bot_activate() {

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin activation started');

    // Logic to run during activation
    steven_bot_upgrade();

    // Handle unexpect output during activation - Ver 2.0.6 - 2024 07 10
    $unexpected_output = ob_get_clean();
    if (!empty($unexpected_output)) {
        // Log or handle unexpected output
        error_log('[Chatbot] [chatbot-upgrade.php] Unexpected output during plugin activation: ' . $unexpected_output);
    }

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin activation completed');

    return;

}

// Upgrade Hook for Plugin Update - Revised 1.7.6
function steven_bot_upgrade_completed($upgrader_object, $options) {

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin upgrade started');

    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        if (isset($options['plugins']) && is_array($options['plugins'])) {
            foreach($options['plugins'] as $plugin) {
                if (plugin_basename(__FILE__) === $plugin) {
                    // Logic to run during upgrade
                    steven_bot_upgrade();
                    break;
                }
            }
        } else {
            // DIAG - Log the warning
            // back_trace( 'WARNING', '"plugins" key is not set or not an array');
        }
    }

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin upgrade started');

    return;

}

// Upgrade Logic - Revised 1.7.6
function steven_bot_upgrade() {

    // DIAG - Log the upgrade
    // back_trace( 'NOTICE', 'Plugin upgrade started');

    // Ver 2.5.0 - Migrate from chatbot_chatgpt_ to steven_bot_
    steven_bot_migrate_from_legacy();

    // Ver 2.5.0: Update initial greeting to include Steven bot name
    if (!get_option('chatbot_greeting_updated_v25')) {
        update_option('steven_bot_initial_greeting', "Hi there! I'm Steven, Comfort Comm's virtual assistant. How can I help you with your internet, TV, or phone service today?");
        update_option('chatbot_greeting_updated_v25', true);
    }

    // Removed obsolete or replaced options
    if ( esc_attr(get_option( 'steven_bot_crawler_status' )) ) {
        delete_option( 'steven_bot_crawler_status' );
        // back_trace( 'NOTICE', 'steven_bot_crawler_status option deleted');
    }

    // Add new or replaced options - steven_bot_diagnostics
    if (esc_attr( get_option( 'steven_bot_diagnostics' )) ) {
        $diagnostics = esc_attr(get_option( 'steven_bot_diagnostics' ));
        if ( !$diagnostics || $diagnostics == '' || $diagnostics == ' ' ) {
            update_option( 'steven_bot_diagnostics', 'No' );
        }
        // back_trace( 'NOTICE', 'steven_bot_diagnostics option updated');
    }

    // Add new or replaced options - steven_bot_plugin_version
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_plugin_version') )) {
        delete_option( 'chatgpt_plugin_version' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_plugin_version option deleted');
    }

    // Replace option - steven_bot_width_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatbot_width_setting' ))) {
        $steven_bot_width_setting = esc_attr(get_option( 'chatbot_width_setting' ));
        delete_option( 'chatbot_width_setting' );
        update_option( 'steven_bot_width_setting', $steven_bot_width_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_width_setting option deleted');
    }

    // Replace option - steven_bot_api_key
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_api_key' ))) {
        $steven_bot_api_key = esc_attr(get_option( 'chatgpt_api_key' ));
        delete_option( 'chatgpt_api_key' );
        update_option( 'steven_bot_api_key', $steven_bot_api_key );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_api_key option deleted');
    }

    // Replace option - steven_bot_avatar_greeting_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_greeting_setting' ))) {
        $steven_bot_avatar_greeting_setting = esc_attr(get_option( 'chatgpt_avatar_greeting_setting' ));
        delete_option( 'chatgpt_avatar_greeting_setting' );
        update_option( 'steven_bot_avatar_greeting_setting', $steven_bot_avatar_greeting_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'steven_bot_avatar_greeting_setting option deleted');
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_icon_setting' ))) {
        $steven_bot_avatar_greeting_setting = esc_attr(get_option( 'chatgpt_avatar_icon_setting' ));
        delete_option( 'chatgpt_avatar_icon_setting' );
        update_option( 'steven_bot_avatar_icon_setting', $steven_bot_avatar_greeting_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_avatar_icon_setting option deleted');
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option ( 'steven_bot_avatar_icon' ))) {
        delete_option( 'steven_bot_avatar_icon' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'steven_bot_avatar_icon option replaced');
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_icon_url_setting' ))) {
        $steven_bot_avatar_icon_url_setting = esc_attr(get_option( 'chatgpt_avatar_icon_url_setting' ));
        delete_option( 'chatgpt_avatar_icon_url_setting' );
        update_option( 'steven_bot_avatar_icon_url_setting', $steven_bot_avatar_icon_url_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_avatar_icon_url_setting option deleted');
    }

    // Replace option - chatgpt_bot_name
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_bot_name' ))) {
        $steven_bot_bot_name = esc_attr(get_option( 'chatgpt_bot_name' ));
        delete_option( 'chatgpt_bot_name' );
        update_option( 'steven_bot_bot_name', $steven_bot_bot_name );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_bot_name option deleted');
    }

    // Replace option - chatgpt_custom_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_custom_avatar_icon_setting' ))) {
        $steven_bot_custom_avatar_icon_setting = esc_attr(get_option( 'chatgpt_custom_avatar_icon_setting' ));
        delete_option( 'chatgpt_custom_avatar_icon_setting' );
        update_option( 'steven_bot_custom_avatar_icon_setting', $steven_bot_custom_avatar_icon_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_custom_avatar_icon_setting option deleted');
    }

    // Replace option - chatgpt_diagnostics
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_diagnostics' ))) {
        $steven_bot_diagnostics = esc_attr(get_option( 'chatgpt_diagnostics' ));
        delete_option( 'chatgpt_diagnostics' );
        update_option( 'steven_bot_diagnostics', $steven_bot_diagnostics );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_diagnostics option deleted');
    }

    // Replace option - chatgpt_disclaimer_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_disclaimer_setting' ))) {
        $steven_bot_disclaimer_setting = esc_attr(get_option( 'chatgpt_disclaimer_setting' ));
        delete_option( 'chatgpt_disclaimer_setting' );
        update_option( 'steven_bot_disclaimer_setting', $steven_bot_disclaimer_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_disclaimer_setting option deleted');
    }

    // Replace option - chatgpt_initial_greeting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_initial_greeting' ))) {
        $steven_bot_initial_greeting = esc_attr(get_option( 'chatgpt_initial_greeting' ));
        delete_option( 'chatgpt_initial_greeting' );
        update_option( 'steven_bot_initial_greeting', $steven_bot_initial_greeting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_initial_greeting option deleted');
    }

    // Replace option - chatgpt_max_tokens_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_max_tokens_setting' ))) {
        $steven_bot_max_tokens_setting = esc_attr(get_option( 'chatgpt_max_tokens_setting' ));
        delete_option( 'chatgpt_max_tokens_setting' );
        update_option( 'steven_bot_max_tokens_setting', $steven_bot_max_tokens_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_max_tokens_setting option deleted');
    }

    // Replace option - chatgpt_model_choice
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_model_choice' ))) {
        $steven_bot_model_choice = esc_attr(get_option( 'chatgpt_model_choice' ));
        delete_option( 'chatgpt_model_choice' );
        update_option( 'steven_bot_model_choice', $steven_bot_model_choice );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_model_choice option deleted');
    }

    // Replace option - chatgptStartStatusNewVisitor
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgptStartStatusNewVisitor' ))) {
        $steven_bot_start_status_new_visitor = esc_attr(get_option( 'chatgptStartStatusNewVisitor' ));
        delete_option( 'chatgptStartStatusNewVisitor' );
        update_option( 'steven_bot_start_status_new_visitor', $steven_bot_start_status_new_visitor );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgptStartStatusNewVisitor option deleted');
    }
    if (esc_attr(get_option( 'chatgpt_start_status' ))) {
        delete_option( 'chatgpt_start_status' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_start_status option deleted');
    }

    // Replace option - chatgptstartstatus
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgptstartstatus' ))) {
        $steven_bot_start_status = esc_attr(get_option( 'chatgptstartstatus' ));
        delete_option( 'chatgptstartstatus' );
        update_option( 'steven_bot_start_status', $steven_bot_start_status );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgptstartstatus option deleted');
    }

    // Replace option - chatgpt_chatbot_bot_prompt
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_chatbot_bot_prompt' ))) {
        $steven_bot_bot_prompt = esc_attr(get_option( 'chatgpt_chatbot_bot_prompt' ));
        delete_option( 'chatgpt_chatbot_bot_prompt' );
        update_option( 'steven_bot_bot_prompts', $steven_bot_bot_prompt );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_chatbot_bot_prompt option deleted');
    }

    // Replace option - chatgpt_subsequent_greeting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_subsequent_greeting' ))) {
        $steven_bot_subsequent_greeting = esc_attr(get_option( 'chatgpt_subsequent_greeting' ));
        delete_option( 'chatgpt_subsequent_greeting' );
        update_option( 'steven_bot_subsequent_greeting', $steven_bot_subsequent_greeting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_subsequent_greeting option deleted');
    }

    // Replace option - chatGPTChatBotStatus
    if (esc_attr(get_option( 'chatGPTChatBotStatus' ))) {
        delete_option( 'chatGPTChatBotStatus' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatGPTChatBotStatus option deleted');
    }

    // Replace option - chatGPTChatBotStatusNewVisitor
    if (esc_attr(get_option( 'chatGPTChatBotStatusNewVisitor' ))) {
        delete_option( 'chatGPTChatBotStatusNewVisitor' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatGPTChatBotStatusNewVisitor option deleted');
    }

    // Replace option - chatbot_kn_items_per_batch
    if (esc_attr(get_option( 'chatbot_kn_items_per_batch' ))) {
        $steven_bot_kn_items_per_batch = esc_attr(get_option( 'chatbot_kn_items_per_batch' ));
        delete_option( 'chatbot_kn_items_per_batch' );
        update_option( 'steven_bot_kn_items_per_batch', $steven_bot_kn_items_per_batch );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_kn_items_per_batch option deleted');
    }

    // Replace option - no_of_items_analyzed
    if (esc_attr(get_option( 'no_of_items_analyzed' ))) {
        $steven_bot_no_of_items_analyzed = esc_attr(get_option( 'no_of_items_analyzed' ));
        delete_option( 'no_of_items_analyzed' );
        update_option( 'steven_bot_no_of_items_analyzed', $steven_bot_no_of_items_analyzed );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'no_of_items_analyzed option deleted');
    }

    // Reset the Knowledge Navigator reminder option
    if (esc_attr(get_option( 'steven_bot_kn_dismissed' ))) {
        delete_option( 'steven_bot_kn_dismissed' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'steven_bot_kn_dismissed option deleted');
    }

    // Replace option - steven_bot_enable_custom_buttons - Ver 2.0.5
    $steven_bot_enable_custom_buttons = esc_attr(get_option( 'steven_bot_enable_custom_buttons' ));
    if ($steven_bot_enable_custom_buttons == 'On') {
        $steven_bot_enable_custom_buttons = 'Floating Only';
        update_option('steven_bot_enable_custom_buttons', 'Floating Only');
    }

    // FIXME - DETERMINE WHAT OTHER 'OLD' OPTIONS SHOULD BE DELETED
    // FIXME - DETERMINE WHAT OPTION NAMES NEED TO BE CHANGED (DELETE, THEN REPLACE)

    // Add/update the option - steven_bot_plugin_version
    global $steven_bot_plugin_version;
    $plugin_version = $steven_bot_plugin_version;
    update_option('steven_bot_plugin_version', $plugin_version);
    // DIAG - Log the plugin version
    // back_trace( 'NOTICE', 'steven_bot_plugin_version option created');

    // Add new/replaced options - steven_bot_interactions
    create_steven_bot_interactions_table();
    // DIAG - Log the table creation
    // back_trace( 'NOTICE', 'steven_bot_interactions table created');

    // Add new/replaced options - create_conversation_logging_table
    create_conversation_logging_table();
    // DIAG - Log the table creation
    // back_trace( 'NOTICE', 'steven_bot_conversation_log table created');

    // Ensure sentiment_score column exists for existing installations
    if (function_exists('steven_bot_add_sentiment_score_column')) {
        steven_bot_add_sentiment_score_column();
    }
    // DIAG - Log the column addition
    // back_trace( 'NOTICE', 'sentiment_score column ensured');

    // DIAG - Log the upgrade complete
    // back_trace( 'NOTICE', 'Plugin upgrade completed');

    return;

}

/**
 * Migrate from legacy chatbot_chatgpt_ options to steven_bot_
 * This runs once on plugin activation/upgrade
 *
 * @since 2.5.0
 */
function steven_bot_migrate_from_legacy() {
    global $wpdb;

    // Check if migration has already been done
    if (get_option('steven_bot_migration_completed_v250')) {
        return;
    }

    // Log migration start
    error_log('[Steven-Bot] Starting migration from chatbot_chatgpt_ to steven_bot_');

    // Get all options that start with chatbot_chatgpt_
    $legacy_options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options}
         WHERE option_name LIKE 'chatbot_chatgpt_%'"
    );

    $migrated_count = 0;

    foreach ($legacy_options as $option) {
        // Convert old option name to new name
        $new_option_name = str_replace('chatbot_chatgpt_', 'steven_bot_', $option->option_name);

        // Only migrate if new option doesn't already exist
        if (get_option($new_option_name) === false) {
            // Copy the value to new option name
            update_option($new_option_name, maybe_unserialize($option->option_value));
            $migrated_count++;
        }
    }

    // Migrate database tables if they exist
    steven_bot_migrate_database_tables();

    // Mark migration as complete
    update_option('steven_bot_migration_completed_v250', true);
    update_option('steven_bot_migration_date', current_time('mysql'));
    update_option('steven_bot_migrated_options_count', $migrated_count);

    error_log('[Steven-Bot] Migration completed. Migrated ' . $migrated_count . ' options.');
}

/**
 * Migrate database tables from old names to new names
 * Creates new tables and copies data if old tables exist
 *
 * @since 2.5.0
 */
function steven_bot_migrate_database_tables() {
    global $wpdb;

    // Table name mappings: old => new
    $table_mappings = array(
        'chatbot_chatgpt_interactions' => 'steven_bot_interactions',
        'chatbot_chatgpt_conversation_log' => 'steven_bot_conversation_log',
        'chatbot_chatgpt_knowledge_base' => 'steven_bot_knowledge_base',
        'chatbot_chatgpt_knowledge_base_tfidf' => 'steven_bot_knowledge_base_tfidf',
        'chatbot_chatgpt_knowledge_base_word_count' => 'steven_bot_knowledge_base_word_count',
    );

    foreach ($table_mappings as $old_table => $new_table) {
        $old_table_name = $wpdb->prefix . $old_table;
        $new_table_name = $wpdb->prefix . $new_table;

        // Check if old table exists
        $old_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") === $old_table_name;

        if ($old_exists) {
            // Check if new table exists
            $new_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table_name'") === $new_table_name;

            if (!$new_exists) {
                // Create new table with same structure as old
                $wpdb->query("CREATE TABLE $new_table_name LIKE $old_table_name");

                // Copy all data from old table to new table
                $wpdb->query("INSERT INTO $new_table_name SELECT * FROM $old_table_name");

                error_log("[Steven-Bot] Migrated table $old_table_name to $new_table_name");
            }
        }
    }
}
