<?php
/**
 * Steven-Bot - Knowledge Navigator - Scheduler - Ver 1.6.3
 *
 * This is the file that schedules the Knowledge Navigator.
 * Scheduled can be now, daily, weekly, etc.
 * 
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle long-running scripts with a scheduled event function - Ver 1.6.1
function knowledge_navigator_scan() {

    // DIAG - Diagnostics - Ver 1.6.3
    // back_trace( 'NOTICE', 'ENTERING knowledge_navigator_scan()');
    
    $run_scanner = esc_attr(get_option('steven_bot_knowledge_navigator', 'No'));
    
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$run_scanner: ' . $run_scanner );

    // The second parameter is the default value if the option is not set.
    update_option('steven_bot_kn_status', 'In Process');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // FIXME - Handle the case where the scanner is already running

    // FIXME - Handle the case where the user wants to stop the scanner
    // 'Cancel' the scheduled event

    // Reset the results message
    update_option('steven_bot_kn_results', '');

    // New process to acquire the content - Ver 1.9.6 - 2024 04 18
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'steven_bot_kn_action - schedule kicked off' );

    update_option( 'steven_bot_kn_action', 'initialize' );

    chatbot_kn_acquire_controller();

    // DIAG - Diagnostics - Ver 1.6.3
    // back_trace( 'NOTICE', 'EXITING knowledge_navigator_scan()');

}
add_action('knowledge_navigator_scan_hook', 'knowledge_navigator_scan');
