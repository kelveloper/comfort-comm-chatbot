<?php
/**
 * Steve-Bot - Settings - Analytics & Feedback (NEW Merged Version)
 *
 * Merges Analytics and Reporting tabs with consistent styling from Analytics
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Render the new merged Analytics & Feedback page
 */
function chatbot_analytics_new_page() {

    // Handle period filter - check URL parameter first, then transient, then default
    if (isset($_GET['period']) && in_array($_GET['period'], array('Today', 'Week', 'Month', 'Quarter', 'Year'))) {
        $selected_period = sanitize_text_field($_GET['period']);
        set_transient('chatbot_analytics_selected_period', $selected_period, HOUR_IN_SECONDS);
    } else {
        $selected_period = get_transient('chatbot_analytics_selected_period');
        if (!$selected_period) {
            $selected_period = 'Week';
        }
    }

    // Get statistics - use Supabase functions if configured, otherwise fall back to WordPress DB
    $use_supabase = function_exists('chatbot_supabase_is_configured') && chatbot_supabase_is_configured();

    if ($use_supabase) {
        // Use Supabase analytics functions
        $time_based_counts = function_exists('chatbot_supabase_get_time_based_conversation_counts')
            ? chatbot_supabase_get_time_based_conversation_counts($selected_period)
            : array('current' => array('total' => 0, 'unique_visitors' => 0), 'previous' => array('total' => 0, 'unique_visitors' => 0), 'current_period_label' => 'This Period', 'previous_period_label' => 'Last Period');

        $message_stats = function_exists('chatbot_supabase_get_message_statistics')
            ? chatbot_supabase_get_message_statistics($selected_period)
            : array('current' => array('total_messages' => 0, 'visitor_messages' => 0), 'previous' => array('total_messages' => 0, 'visitor_messages' => 0), 'current_period_label' => 'This Period', 'previous_period_label' => 'Last Period');

        $sentiment_stats = function_exists('chatbot_supabase_get_sentiment_statistics')
            ? chatbot_supabase_get_sentiment_statistics($selected_period)
            : array('current' => array('avg_score' => 0, 'positive_percent' => 0), 'previous' => array('avg_score' => 0, 'positive_percent' => 0));
    } else {
        // Fall back to WordPress database functions
        $time_based_counts = function_exists('kognetiks_analytics_get_time_based_conversation_counts')
            ? kognetiks_analytics_get_time_based_conversation_counts($selected_period, 'All')
            : array('current' => array('total' => 0, 'unique_visitors' => 0), 'previous' => array('total' => 0, 'unique_visitors' => 0), 'current_period_label' => 'This Period', 'previous_period_label' => 'Last Period');

        $message_stats = function_exists('kognetiks_analytics_get_message_statistics')
            ? kognetiks_analytics_get_message_statistics($selected_period, 'All')
            : array('current' => array('total_messages' => 0, 'visitor_messages' => 0), 'previous' => array('total_messages' => 0, 'visitor_messages' => 0), 'current_period_label' => 'This Period', 'previous_period_label' => 'Last Period');

        $sentiment_stats = function_exists('kognetiks_analytics_get_sentiment_statistics')
            ? kognetiks_analytics_get_sentiment_statistics($selected_period, 'All')
            : array('current' => array('avg_score' => 0, 'positive_percent' => 0), 'previous' => array('avg_score' => 0, 'positive_percent' => 0));
    }

    // Get CSAT stats from Reporting
    $csat_stats = function_exists('chatbot_chatgpt_get_csat_stats')
        ? chatbot_chatgpt_get_csat_stats()
        : array('csat_score' => 0, 'total_responses' => 0, 'helpful_count' => 0, 'not_helpful_count' => 0, 'target_met' => false);

    // Get learning stats from Reporting
    $review_queue = function_exists('chatbot_get_learning_review_queue') ? chatbot_get_learning_review_queue() : array();
    $pending_count = count($review_queue);
    $learning_stats = function_exists('chatbot_get_learning_stats') ? chatbot_get_learning_stats() : array('approved' => 0, 'rejected' => 0, 'pending' => 0, 'rollbacks' => 0);

    // Get recent feedback
    $csat_data = get_option('chatbot_chatgpt_csat_data', array('responses' => array()));
    $responses = array_reverse($csat_data['responses']);
    $recent_responses = array_slice($responses, 0, 10);

    ?>
    <style>
        .analytics-container {
            max-width: 1400px;
            margin-top: 20px;
        }
        .analytics-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .analytics-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 14px;
            color: #1d2327;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .stats-grid-small {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .comparison-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
        }
        .current-period, .previous-period {
            flex: 1;
            text-align: center;
        }
        .period-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #2271b1;
        }
        .stat-value.success { color: #10b981; }
        .stat-value.danger { color: #ef4444; }
        .stat-value.warning { color: #f59e0b; }
        .trend-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            min-width: 40px;
            margin: 0 10px;
            flex-direction: column;
        }
        .trend-up {
            color: #28a745;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            margin-bottom: 4px;
        }
        .trend-down {
            color: #dc3545;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            margin-bottom: 4px;
        }
        .percent-change {
            font-size: 14px;
            font-weight: normal;
            margin-top: 2px;
        }
        .trend-up + .percent-change { color: #28a745; }
        .trend-down + .percent-change { color: #dc3545; }
        .section-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 10px;
        }
        .section-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            padding: 0;
            color: #1d2327;
            font-size: 1.3em;
        }
        .section-description {
            color: #646970;
            margin: 5px 0 0;
            font-size: 14px;
        }
        .period-filter-form {
            margin-bottom: 20px;
        }
        .period-filter-form select {
            padding: 8px 12px;
            font-size: 14px;
            min-width: 220px;
        }
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
        }
        .feedback-table th,
        .feedback-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }
        .feedback-table th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        .feedback-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
        }
        .status-success { background: #d1fae5; color: #065f46; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .info-box ol {
            margin: 0;
            padding-left: 20px;
            color: #1e3a8a;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .csat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            border-left: 4px solid #2271b1;
        }
        .csat-card.success { border-left-color: #10b981; }
        .csat-card.danger { border-left-color: #ef4444; }
        .csat-card.info { border-left-color: #3b82f6; }
        .csat-card h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .csat-value {
            font-size: 28px;
            font-weight: bold;
            color: #1d2327;
        }
        .csat-value.success { color: #10b981; }
        .csat-value.danger { color: #ef4444; }
    </style>

    <div class="analytics-container">

        <!-- Period Filter -->
        <div class="period-filter-form">
            <label for="analytics_period" style="font-weight: 600; margin-right: 10px;">Period:</label>
            <select name="analytics_period" id="analytics_period" onchange="window.location.href='<?php echo esc_url(admin_url('admin.php?page=chatbot-chatgpt&tab=analytics_feedback')); ?>&period=' + this.value;">
                <option value="Today" <?php selected($selected_period, 'Today'); ?>>Today vs Yesterday</option>
                <option value="Week" <?php selected($selected_period, 'Week'); ?>>This Week vs Last Week</option>
                <option value="Month" <?php selected($selected_period, 'Month'); ?>>This Month vs Last Month</option>
                <option value="Quarter" <?php selected($selected_period, 'Quarter'); ?>>This Quarter vs Last Quarter</option>
                <option value="Year" <?php selected($selected_period, 'Year'); ?>>This Year vs Last Year</option>
            </select>
        </div>

        <div style="margin-bottom: 30px;"></div>

        <!-- AI Gap Analysis Dashboard - MOVED TO TOP (Ver 2.5.0) -->
        <div class="section-header">
            <h2>AI Gap Analysis Dashboard</h2>
            <p class="section-description">Identifies questions users ask that your FAQ database can't answer — AI suggests new FAQs</p>
        </div>

        <div class="analytics-section">
            <?php
            // Call the gap analysis callback from reporting, passing the selected period
            if (function_exists('chatbot_chatgpt_gap_analysis_callback')) {
                chatbot_chatgpt_gap_analysis_callback($selected_period);
            } else {
                echo '<p style="color: #6b7280;">Gap analysis module not loaded.</p>';
            }
            ?>
        </div>

        <!-- Conversation Statistics -->
        <div class="section-header">
            <h2>Conversation Statistics</h2>
            <p class="section-description">Key metrics about your chatbot's conversations and user interactions</p>
        </div>

        <div class="analytics-section">
            <h3>Overview</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Total Conversations</h3>
                    <div class="comparison-row">
                        <div class="current-period">
                            <span class="period-label"><?php echo esc_html($time_based_counts['current_period_label'] ?? 'This Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($time_based_counts['current']['total'] ?? 0); ?></p>
                        </div>
                        <div class="trend-indicator">
                            <?php
                            $current = $time_based_counts['current']['total'] ?? 0;
                            $previous = $time_based_counts['previous']['total'] ?? 0;
                            if ($current > $previous) {
                                $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                            } elseif ($current < $previous) {
                                $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                            }
                            ?>
                        </div>
                        <div class="previous-period">
                            <span class="period-label"><?php echo esc_html($time_based_counts['previous_period_label'] ?? 'Last Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($time_based_counts['previous']['total'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-box">
                    <h3>Unique Visitors</h3>
                    <div class="comparison-row">
                        <div class="current-period">
                            <span class="period-label"><?php echo esc_html($time_based_counts['current_period_label'] ?? 'This Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($time_based_counts['current']['unique_visitors'] ?? 0); ?></p>
                        </div>
                        <div class="trend-indicator">
                            <?php
                            $current = $time_based_counts['current']['unique_visitors'] ?? 0;
                            $previous = $time_based_counts['previous']['unique_visitors'] ?? 0;
                            if ($current > $previous) {
                                $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                            } elseif ($current < $previous) {
                                $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                            }
                            ?>
                        </div>
                        <div class="previous-period">
                            <span class="period-label"><?php echo esc_html($time_based_counts['previous_period_label'] ?? 'Last Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($time_based_counts['previous']['unique_visitors'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Statistics -->
        <div class="section-header">
            <h2>Message Statistics</h2>
            <p class="section-description">Breakdown of messages between visitors and chatbot</p>
        </div>

        <div class="analytics-section">
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Total Messages</h3>
                    <div class="comparison-row">
                        <div class="current-period">
                            <span class="period-label"><?php echo esc_html($message_stats['current_period_label'] ?? 'This Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($message_stats['current']['total_messages'] ?? 0); ?></p>
                        </div>
                        <div class="trend-indicator">
                            <?php
                            $current = $message_stats['current']['total_messages'] ?? 0;
                            $previous = $message_stats['previous']['total_messages'] ?? 0;
                            if ($current > $previous) {
                                $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                            } elseif ($current < $previous) {
                                $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                            }
                            ?>
                        </div>
                        <div class="previous-period">
                            <span class="period-label"><?php echo esc_html($message_stats['previous_period_label'] ?? 'Last Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($message_stats['previous']['total_messages'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-box">
                    <h3>Visitor Messages</h3>
                    <div class="comparison-row">
                        <div class="current-period">
                            <span class="period-label"><?php echo esc_html($message_stats['current_period_label'] ?? 'This Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($message_stats['current']['visitor_messages'] ?? 0); ?></p>
                        </div>
                        <div class="trend-indicator">
                            <?php
                            $current = $message_stats['current']['visitor_messages'] ?? 0;
                            $previous = $message_stats['previous']['visitor_messages'] ?? 0;
                            if ($current > $previous) {
                                $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                            } elseif ($current < $previous) {
                                $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                            }
                            ?>
                        </div>
                        <div class="previous-period">
                            <span class="period-label"><?php echo esc_html($message_stats['previous_period_label'] ?? 'Last Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($message_stats['previous']['visitor_messages'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Satisfaction -->
        <div class="section-header">
            <h2>😊 Customer Satisfaction (CSAT)</h2>
            <p class="section-description">Direct user feedback on chatbot responses</p>
        </div>

        <div class="analytics-section">
            <div class="stats-grid stats-grid-small">
                <div class="csat-card <?php echo $csat_stats['target_met'] ? 'success' : 'danger'; ?>">
                    <h4>CSAT Score</h4>
                    <div class="csat-value <?php echo $csat_stats['target_met'] ? 'success' : 'danger'; ?>"><?php echo $csat_stats['csat_score']; ?>%</div>
                </div>
                <div class="csat-card info">
                    <h4>Total Responses</h4>
                    <div class="csat-value"><?php echo $csat_stats['total_responses']; ?></div>
                </div>
                <div class="csat-card success">
                    <h4>Helpful</h4>
                    <div class="csat-value success"><?php echo $csat_stats['helpful_count']; ?></div>
                </div>
                <div class="csat-card danger">
                    <h4>Not Helpful</h4>
                    <div class="csat-value danger"><?php echo $csat_stats['not_helpful_count']; ?></div>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <div class="status-indicator <?php echo $csat_stats['target_met'] ? 'status-success' : 'status-warning'; ?>">
                    <?php echo $csat_stats['target_met'] ? 'Target Met (>70%)' : 'Below Target (<70%)'; ?>
                </div>
            </div>
        </div>

        <!-- Sentiment Analysis -->
        <div class="section-header">
            <h2>🧠 Sentiment Analysis</h2>
            <p class="section-description">AI-powered analysis of conversation sentiment (Vector method enabled)</p>
        </div>

        <div class="analytics-section">
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Average Sentiment Score</h3>
                    <div class="comparison-row">
                        <div class="current-period">
                            <span class="period-label"><?php echo esc_html($sentiment_stats['current_period_label'] ?? 'This Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($sentiment_stats['current']['avg_score'] ?? 0, 2); ?></p>
                        </div>
                        <div class="trend-indicator">
                            <?php
                            $current = $sentiment_stats['current']['avg_score'] ?? 0;
                            $previous = $sentiment_stats['previous']['avg_score'] ?? 0;
                            if ($current > $previous) {
                                $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                            } elseif ($current < $previous) {
                                $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                            }
                            ?>
                        </div>
                        <div class="previous-period">
                            <span class="period-label"><?php echo esc_html($sentiment_stats['previous_period_label'] ?? 'Last Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($sentiment_stats['previous']['avg_score'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="stat-box">
                    <h3>Positive Conversations</h3>
                    <div class="comparison-row">
                        <div class="current-period">
                            <span class="period-label"><?php echo esc_html($sentiment_stats['current_period_label'] ?? 'This Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($sentiment_stats['current']['positive_percent'] ?? 0, 1); ?>%</p>
                        </div>
                        <div class="trend-indicator">
                            <?php
                            $current = $sentiment_stats['current']['positive_percent'] ?? 0;
                            $previous = $sentiment_stats['previous']['positive_percent'] ?? 0;
                            if ($current > $previous) {
                                $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                            } elseif ($current < $previous) {
                                $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                            }
                            ?>
                        </div>
                        <div class="previous-period">
                            <span class="period-label"><?php echo esc_html($sentiment_stats['previous_period_label'] ?? 'Last Period'); ?></span>
                            <p class="stat-value"><?php echo number_format($sentiment_stats['previous']['positive_percent'] ?? 0, 1); ?>%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Feedback -->
        <div class="section-header">
            <h2>Recent Feedback</h2>
            <p class="section-description">Latest user feedback on chatbot responses</p>
        </div>

        <div class="analytics-section">
            <?php if (empty($recent_responses)) : ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>No feedback received yet</p>
                    <p style="font-size: 12px;">Feedback will appear here when users rate responses</p>
                </div>
            <?php else : ?>
                <table class="feedback-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Feedback</th>
                            <th>Confidence</th>
                            <th>Question</th>
                            <th>Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_responses as $response) :
                            $feedback_icon = $response['feedback'] === 'yes' ? '+' : '-';
                            $question = isset($response['question']) ? esc_html($response['question']) : 'N/A';
                            $answer = isset($response['answer']) ? esc_html($response['answer']) : 'N/A';
                            $confidence = isset($response['confidence_score']) ? $response['confidence_score'] : 'unknown';

                            $confidence_map = [
                                'very_high' => ['label' => 'Very High', 'class' => 'badge-success'],
                                'high' => ['label' => 'High', 'class' => 'badge-info'],
                                'medium' => ['label' => 'Medium', 'class' => 'badge-warning'],
                                'low' => ['label' => 'Low', 'class' => 'badge-danger'],
                                'unknown' => ['label' => '—', 'class' => '']
                            ];
                            $conf_display = $confidence_map[$confidence] ?? $confidence_map['unknown'];

                            $question_display = strlen($question) > 60 ? substr($question, 0, 60) . '...' : $question;
                            $answer_display = strlen($answer) > 80 ? substr($answer, 0, 80) . '...' : $answer;
                        ?>
                        <tr>
                            <td style="font-size: 12px; white-space: nowrap;">
                                <?php echo date('M j, H:i', strtotime($response['timestamp'])); ?>
                            </td>
                            <td>
                                <span style="font-size: 20px;"><?php echo $feedback_icon; ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $conf_display['class']; ?>">
                                    <?php echo $conf_display['label']; ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; max-width: 200px;">
                                <?php echo $question_display; ?>
                            </td>
                            <td style="font-size: 13px; max-width: 250px; color: #666;">
                                <?php echo $answer_display; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Learning Dashboard -->
        <div class="section-header">
            <h2>Learning Dashboard</h2>
            <p class="section-description">FAQ improvements based on user feedback — all changes require human approval</p>
        </div>

        <div class="analytics-section">
            <div class="info-box">
                <h4>How It Works</h4>
                <ol>
                    <li><strong>User gives feedback</strong> — Thumbs up/down on chatbot responses</li>
                    <li><strong>Threshold reached</strong> — FAQ flagged after 5+ negative ratings</li>
                    <li><strong>Appears here</strong> — Flagged FAQs show in the Human Review Queue below</li>
                    <li><strong>You decide</strong> — Review, then Approve or Dismiss</li>
                </ol>
            </div>

            <div class="stats-grid stats-grid-small" style="margin-bottom: 20px;">
                <div class="csat-card success">
                    <h4>Learning Mode</h4>
                    <div style="font-size: 14px; font-weight: bold; color: #10b981;">Active</div>
                </div>
                <div class="csat-card <?php echo $pending_count > 0 ? '' : 'success'; ?>" style="<?php echo $pending_count > 0 ? 'border-left-color: #f59e0b;' : ''; ?>">
                    <h4>Pending Reviews</h4>
                    <div class="csat-value <?php echo $pending_count > 0 ? 'warning' : 'success'; ?>"><?php echo $pending_count; ?></div>
                </div>
                <div class="csat-card info">
                    <h4>Approved</h4>
                    <div class="csat-value"><?php echo $learning_stats['approved']; ?></div>
                </div>
                <div class="csat-card">
                    <h4>Rejected</h4>
                    <div class="csat-value"><?php echo $learning_stats['rejected']; ?></div>
                </div>
            </div>

            <h3 style="margin: 20px 0 15px 0;">Human Review Queue</h3>

            <?php if (empty($review_queue)) : ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <p>No FAQs pending review</p>
                    <p style="font-size: 12px;">FAQs will appear here when they reach the negative feedback threshold</p>
                </div>
            <?php else : ?>
                <table class="feedback-table">
                    <thead>
                        <tr>
                            <th>FAQ Question</th>
                            <th>Negative Count</th>
                            <th>Suggested Action</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($review_queue as $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item['question'] ?? 'Unknown'); ?></td>
                            <td>
                                <span class="badge badge-danger"><?php echo esc_html($item['negative_count'] ?? 0); ?></span>
                            </td>
                            <td style="font-size: 13px; color: #666;">
                                <?php echo esc_html($item['suggestion'] ?? 'Review and improve FAQ answer'); ?>
                            </td>
                            <td>
                                <button class="button button-small" style="background: #10b981; color: white; border-color: #10b981;">Approve</button>
                                <button class="button button-small">Dismiss</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
    <?php
}
