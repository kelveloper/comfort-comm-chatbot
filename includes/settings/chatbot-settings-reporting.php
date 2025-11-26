<?php
/**
 * Kognetiks Chatbot - Settings - Reporting
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the reporting settings and other parameters.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Reporting settings - Ver 2.0.7
function chatbot_chatgpt_reporting_settings_init() {

    // Register settings for Reporting
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_reporting_period');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_enable_conversation_logging');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_log_days_to_keep');

    // Reporting Overview Section
    add_settings_section(
        'chatbot_chatgpt_reporting_overview_section',
        'Reporting Overview',
        'chatbot_chatgpt_reporting_overview_section_callback',
        'chatbot_chatgpt_reporting_overview'
    );

    // Reporting Settings Section
    add_settings_section(
        'chatbot_chatgpt_reporting_section',
        'Reporting Settings',
        'chatbot_chatgpt_reporting_section_callback',
        'chatbot_chatgpt_reporting'
    );

    // Reporting Settings Field - Reporting Period
    add_settings_field(
        'chatbot_chatgpt_reporting_period',
        'Reporting Period',
        'chatbot_chatgpt_reporting_period_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Reporting Settings Field - Enable Conversation Logging
    add_settings_field(
        'chatbot_chatgpt_enable_conversation_logging',
        'Enable Conversation Logging',
        'chatbot_chatgpt_enable_conversation_logging_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Reporting Settings Field - Conversation Log Days to Keep
    add_settings_field(
        'chatbot_chatgpt_conversation_log_days_to_keep',
        'Conversation Log Days to Keep',
        'chatbot_chatgpt_conversation_log_days_to_keep_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Conversation Data Section
    add_settings_section(
        'chatbot_chatgpt_conversation_reporting_section',
        'Conversation Data',
        'chatbot_chatgpt_conversation_reporting_section_callback',
        'chatbot_chatgpt_conversation_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_conversation_reporting_field',
        'Conversation Data',
        'chatbot_chatgpt_conversation_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_conversation_reporting_section'
    );

    // Interaction Data Section
    add_settings_section(
        'chatbot_chatgpt_interaction_reporting_section',
        'Interaction Data',
        'chatbot_chatgpt_interaction_reporting_section_callback',
        'chatbot_chatgpt_interaction_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_interaction_reporting_field',
        'Interaction Data',
        'chatbot_chatgpt_interaction_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_interaction_reporting_section'
    );

    // // Token Data Section
    add_settings_section(
        'chatbot_chatgpt_token_reporting_section',
        'Token Data',
        'chatbot_chatgpt_token_reporting_section_callback',
        'chatbot_chatgpt_token_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_token_reporting_field',
        'Token Data',
        'chatbot_chatgpt_token_reporting_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_token_reporting_section'
    );

    // Gap Analysis Section - Ver 2.4.2
    add_settings_section(
        'chatbot_chatgpt_gap_analysis_section',
        'Gap Analysis',
        'chatbot_chatgpt_gap_analysis_section_callback',
        'chatbot_chatgpt_gap_analysis'
    );

    add_settings_field(
        'chatbot_chatgpt_gap_analysis_field',
        '',
        'chatbot_chatgpt_gap_analysis_callback',
        'chatbot_chatgpt_gap_analysis',
        'chatbot_chatgpt_gap_analysis_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_reporting_settings_init');

// Reporting section callback - Ver 1.6.3
function chatbot_chatgpt_reporting_overview_section_callback($args) {
    // Get CSAT stats
    $csat_stats = chatbot_chatgpt_get_csat_stats();
    $csat_score = $csat_stats['csat_score'];
    $total = $csat_stats['total_responses'];
    $helpful = $csat_stats['helpful_count'];
    $not_helpful = $csat_stats['not_helpful_count'];
    $target_met = $csat_stats['target_met'];

    $score_color = $target_met ? '#10b981' : '#ef4444'; // Green if >70%, red otherwise
    $status_text = $target_met ? '✓ Target Met (>70%)' : '⚠ Below Target (<70%)';
    $status_color = $target_met ? '#10b981' : '#f59e0b';
    ?>
    <div>
        <!-- CSAT Metrics Dashboard -->
        <div style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #1e293b;">📊 CSAT (Customer Satisfaction) Metrics</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <!-- CSAT Score -->
                <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid <?php echo $score_color; ?>;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">CSAT Score</div>
                    <div style="font-size: 32px; font-weight: bold; color: <?php echo $score_color; ?>;"><?php echo $csat_score; ?>%</div>
                </div>

                <!-- Total Responses -->
                <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #3b82f6;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Total Responses</div>
                    <div style="font-size: 32px; font-weight: bold; color: #1e293b;"><?php echo $total; ?></div>
                </div>

                <!-- Helpful -->
                <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #10b981;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">👍 Helpful</div>
                    <div style="font-size: 32px; font-weight: bold; color: #10b981;"><?php echo $helpful; ?></div>
                </div>

                <!-- Not Helpful -->
                <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #ef4444;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">👎 Not Helpful</div>
                    <div style="font-size: 32px; font-weight: bold; color: #ef4444;"><?php echo $not_helpful; ?></div>
                </div>
            </div>

            <!-- Status Badge -->
            <div style="background-color: <?php echo $status_color; ?>15; border: 1px solid <?php echo $status_color; ?>; border-radius: 4px; padding: 10px; text-align: center;">
                <span style="color: <?php echo $status_color; ?>; font-weight: 600;"><?php echo $status_text; ?></span>
            </div>

            <p style="margin-top: 15px; margin-bottom: 0; font-size: 12px; color: #64748b;">
                <b>P0 Success Metric:</b> CSAT Score >70% |
                <b>Calculation:</b> (Helpful / Total) × 100 = (<?php echo $helpful; ?> / <?php echo $total; ?>) × 100
            </p>
        </div>

        <?php
        // Display recent CSAT feedback with Q&A details
        $csat_data = get_option('chatbot_chatgpt_csat_data', array('responses' => array()));
        $responses = array_reverse($csat_data['responses']); // Most recent first
        $recent_responses = array_slice($responses, 0, 20); // Limit to 20 most recent

        if (!empty($recent_responses)) {
        ?>
        <!-- Recent CSAT Feedback Table -->
        <div style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #1e293b;">📋 Recent Feedback Details</h3>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">Showing the most recent <?php echo count($recent_responses); ?> CSAT responses with question and answer details</p>

            <table class="widefat striped" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f1f5f9;">
                        <th style="padding: 10px; width: 100px;">Date/Time</th>
                        <th style="padding: 10px; width: 60px;">Feedback</th>
                        <th style="padding: 10px; width: 90px;">Confidence</th>
                        <th style="padding: 10px;">Question Asked</th>
                        <th style="padding: 10px;">Answer Given</th>
                        <th style="padding: 10px; width: 200px;">User Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_responses as $response) :
                        $feedback_icon = $response['feedback'] === 'yes' ? '👍' : '👎';
                        $feedback_color = $response['feedback'] === 'yes' ? '#10b981' : '#ef4444';
                        $question = isset($response['question']) ? esc_html($response['question']) : 'N/A';
                        $answer = isset($response['answer']) ? esc_html($response['answer']) : 'N/A';
                        $comment = isset($response['comment']) && !empty($response['comment']) ? esc_html($response['comment']) : '';
                        $confidence = isset($response['confidence_score']) ? $response['confidence_score'] : 'unknown';

                        // Map confidence to display format and color
                        $confidence_map = [
                            'very_high' => ['label' => 'Very High', 'color' => '#10b981'],
                            'high' => ['label' => 'High', 'color' => '#3b82f6'],
                            'medium' => ['label' => 'Medium', 'color' => '#f59e0b'],
                            'low' => ['label' => 'Low', 'color' => '#ef4444'],
                            'unknown' => ['label' => '—', 'color' => '#94a3b8']
                        ];
                        $conf_display = $confidence_map[$confidence] ?? $confidence_map['unknown'];

                        // Truncate long text for display
                        $question_display = strlen($question) > 100 ? substr($question, 0, 100) . '...' : $question;
                        $answer_display = strlen($answer) > 150 ? substr($answer, 0, 150) . '...' : $answer;
                        $comment_display = strlen($comment) > 100 ? substr($comment, 0, 100) . '...' : $comment;
                    ?>
                    <tr>
                        <td style="padding: 8px; font-size: 11px;">
                            <?php echo date('m/d H:i', strtotime($response['timestamp'])); ?>
                        </td>
                        <td style="padding: 8px; text-align: center;">
                            <span style="font-size: 20px; color: <?php echo $feedback_color; ?>;"><?php echo $feedback_icon; ?></span>
                        </td>
                        <td style="padding: 8px; font-size: 11px; text-align: center;">
                            <span style="display: inline-block; padding: 4px 8px; background-color: <?php echo $conf_display['color']; ?>20; color: <?php echo $conf_display['color']; ?>; border-radius: 4px; font-weight: 600;">
                                <?php echo $conf_display['label']; ?>
                            </span>
                        </td>
                        <td style="padding: 8px; font-size: 12px; max-width: 250px;">
                            <?php echo $question_display; ?>
                        </td>
                        <td style="padding: 8px; font-size: 12px; max-width: 300px;">
                            <?php echo $answer_display; ?>
                        </td>
                        <td style="padding: 8px; font-size: 12px; max-width: 200px; font-style: italic; color: #64748b;">
                            <?php echo $comment ? $comment_display : '—'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php } ?>

        <!-- AI-Powered Feedback Analysis -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #111827;">🤖 AI-Powered Feedback Analysis</h3>
            <p style="margin: 0 0 15px 0; font-size: 13px; color: #6b7280;">
                Analyze thumbs-down feedback to automatically generate FAQ improvement suggestions based on selected time period.
            </p>

            <!-- Time Period Selector -->
            <div style="margin-bottom: 15px;">
                <label for="feedback-period" style="font-weight: 600; margin-right: 10px;">Analysis Period:</label>
                <select id="feedback-period" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <option value="weekly">Weekly (Last 7 days)</option>
                    <option value="monthly">Monthly (Last 30 days)</option>
                    <option value="quarterly">Quarterly (Last 90 days)</option>
                    <option value="yearly">Yearly (Last 365 days)</option>
                    <option value="all">All Time</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button onclick="chatbotAnalyzeFeedback()" class="button button-primary" style="font-size: 15px; padding: 10px 30px; height: auto;">
                    Analyze Feedback
                </button>
                <button onclick="chatbotClearFeedback()" class="button" style="font-size: 15px; padding: 10px 20px; height: auto; background: #ef4444; color: white; border-color: #dc2626;">
                    Clear Feedback Data
                </button>
            </div>

            <div id="chatbot-feedback-analysis-results" style="margin-top: 20px;"></div>
        </div>

        <script>
        function chatbotAnalyzeFeedback() {
            const btn = event.target;
            const originalText = btn.textContent;
            const resultsDiv = document.getElementById('chatbot-feedback-analysis-results');
            const period = document.getElementById('feedback-period').value;

            btn.disabled = true;
            btn.textContent = '⏳ Analyzing feedback...';
            resultsDiv.innerHTML = '';

            jQuery.post(ajaxurl, {
                action: 'chatbot_analyze_feedback',
                period: period,
                nonce: '<?php echo wp_create_nonce('chatbot_feedback_analysis'); ?>'
            }, function(response) {
                btn.disabled = false;
                btn.textContent = originalText;

                if (response.success) {
                    resultsDiv.innerHTML = response.data.html;
                } else {
                    resultsDiv.innerHTML = '<div style="background: #fee2e2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; color: #991b1b;">Error: ' + (response.data || 'Unknown error') + '</div>';
                }
            }).fail(function() {
                btn.disabled = false;
                btn.textContent = originalText;
                resultsDiv.innerHTML = '<div style="background: #fee2e2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; color: #991b1b;">Request failed. Please try again.</div>';
            });
        }

        function chatbotClearFeedback() {
            if (!confirm('Are you sure you want to clear ALL feedback data? This cannot be undone!')) {
                return;
            }

            const btn = event.target;
            const originalText = btn.textContent;

            btn.disabled = true;
            btn.textContent = 'Clearing...';

            jQuery.post(ajaxurl, {
                action: 'chatbot_clear_feedback',
                nonce: '<?php echo wp_create_nonce('chatbot_clear_feedback'); ?>'
            }, function(response) {
                btn.disabled = false;
                btn.textContent = originalText;

                if (response.success) {
                    alert('Feedback data cleared successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                btn.disabled = false;
                btn.textContent = originalText;
                alert('Request failed. Please try again.');
            });
        }

        function chatbotAddFAQ(suggestion) {
            if (!confirm('Add this new FAQ to the knowledge base?')) {
                return;
            }

            const faq = suggestion.suggested_faq;
            jQuery.post(ajaxurl, {
                action: 'chatbot_add_faq',
                faq_data: faq,
                nonce: '<?php echo wp_create_nonce('chatbot_faq_management'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('FAQ added successfully! ID: ' + response.data.faq_id);
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                alert('Request failed. Please try again.');
            });
        }

        function chatbotEditFAQ(suggestion) {
            const keywords = (suggestion.suggested_keywords || []).join(', ');
            const newKeywords = prompt('Add these keywords to FAQ ' + suggestion.existing_faq_id + ':\n\n' + keywords + '\n\nEdit keywords:', keywords);

            if (newKeywords === null) {
                return; // User cancelled
            }

            jQuery.post(ajaxurl, {
                action: 'chatbot_edit_faq',
                faq_id: suggestion.existing_faq_id,
                keywords: newKeywords,
                nonce: '<?php echo wp_create_nonce('chatbot_faq_management'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('FAQ updated successfully!');
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                alert('Request failed. Please try again.');
            });
        }
        </script>

        <p>Use these setting to select the reporting period for Visitor and User Interactions.</p>
        <p>Please review the section <b>Conversation Logging Overview</b> on the <a href="?page=chatbot-chatgpt&tab=support&dir=support&file=conversation-logging-and-history.md">Support</a> tab of this plugin for more details.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Reporting and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=reporting&file=reporting.md">here</a>.</b></p>
    </div>
    <?php
}

function chatbot_chatgpt_reporting_section_callback($args) {
    ?>
    <div>
        <p>Use these settings to select the reporting period for Visitor and User Interactions.</p>
        <p>You will need to Enable Conversation Logging if you want to record chatbot interactions. By default, conversation logging is initially turned <b>Off</b>.</p>
        <p>Conversation Log Days to Keep sets the number of days to keep the conversation log data in the database.</p>
    </div>
    <?php
}

function chatbot_chatgpt_conversation_reporting_section_callback($args) {
    ?>
    <div>
        <p>Conversation items stored in your DB total <b><?php echo chatbot_chatgpt_count_conversations(); ?></b> rows (includes both Visitor and User input and chatbot responses).</p>
        <p>Conversation items stored take up <b><?php echo chatbot_chatgpt_size_conversations(); ?> MB</b> in your database.</p>
        <p>Use the button (below) to retrieve the conversation data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_conversation_data')) . '">Download Conversation Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_interaction_reporting_section_callback($args) {
    ?>
    <div>
        <!-- TEMPORARILY REMOVED AS SOME USERS ARE EXPERIENCING ISSUES WITH THE CHARTS - Ver 1.7.8 -->
        <!-- <p><?php echo do_shortcode('[chatbot_simple_chart from_database="true"]'); ?></p> -->
        <p><?php echo chatbot_chatgpt_interactions_table() ?></p>
        <p>Use the button (below) to retrieve the interactions data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_interactions_data')) . '">Download Interaction Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_token_reporting_section_callback($args) {
    ?>
    <div>
        <p><?php echo chatbot_chatgpt_total_tokens() ?></p>
        <p>Use the button (below) to retrieve the interactions data and download as a CSV file.</p>
        <?php
            if (is_admin()) {
                $header = " ";
                $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_token_usage_data')) . '">Download Token Usage Data</a>';
                echo $header;
            }
        ?>
    </div>
    <?php
}

function chatbot_chatgpt_reporting_settings_callback($args){
    ?>
    <div>
        <h3>Reporting Settings</h3>
    </div>
    <?php
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_reporting_period_callback($args) {
    // Get the saved chatbot_chatgpt_reporting_period value or default to "Daily"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_reporting_period', 'Daily'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', 'chatbot_chatgpt_reporting_period' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_reporting_period" name="chatbot_chatgpt_reporting_period">
        <option value="<?php echo esc_attr( 'Daily' ); ?>" <?php selected( $output_choice, 'Daily' ); ?>><?php echo esc_html( 'Daily' ); ?></option>
        <!-- <option value="<?php echo esc_attr( 'Weekly' ); ?>" <?php selected( $output_choice, 'Weekly' ); ?>><?php echo esc_html( 'Weekly' ); ?></option> -->
        <option value="<?php echo esc_attr( 'Monthly' ); ?>" <?php selected( $output_choice, 'Monthly' ); ?>><?php echo esc_html( 'Monthly' ); ?></option>
        <option value="<?php echo esc_attr( 'Yearly' ); ?>" <?php selected( $output_choice, 'Yearly' ); ?>><?php echo esc_html( 'Yearly' ); ?></option>
    </select>
    <?php
}

// Conversation Logging - Ver 1.7.6
function  chatbot_chatgpt_enable_conversation_logging_callback($args) {
    // Get the saved chatbot_chatgpt_enable_conversation_logging value or default to "Off"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_enable_conversation_logging', 'Off'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', 'chatbot_chatgpt_enable_conversation_logging' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_enable_conversation_logging" name="chatbot_chatgpt_enable_conversation_logging">
        <option value="<?php echo esc_attr( 'On' ); ?>" <?php selected( $output_choice, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="<?php echo esc_attr( 'Off' ); ?>" <?php selected( $output_choice, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Conversation log retention period - Ver 1.7.6
function chatbot_chatgpt_conversation_log_days_to_keep_callback($args) {
    // Get the saved chatbot_chatgpt_conversation_log_days_to_keep value or default to "30"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_conversation_log_days_to_keep', '30'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', 'chatbot_chatgpt_conversation_log_days_to_keep' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_conversation_log_days_to_keep" name="chatbot_chatgpt_conversation_log_days_to_keep">
        <option value="<?php echo esc_attr( '1' ); ?>" <?php selected( $output_choice, '7' ); ?>><?php echo esc_html( '1' ); ?></option>
        <option value="<?php echo esc_attr( '7' ); ?>" <?php selected( $output_choice, '7' ); ?>><?php echo esc_html( '7' ); ?></option>
        <option value="<?php echo esc_attr( '30' ); ?>" <?php selected( $output_choice, '30' ); ?>><?php echo esc_html( '30' ); ?></option>
        <option value="<?php echo esc_attr( '60' ); ?>" <?php selected( $output_choice, '60' ); ?>><?php echo esc_html( '60' ); ?></option>
        <option value="<?php echo esc_attr( '90' ); ?>" <?php selected( $output_choice, '90' ); ?>><?php echo esc_html( '90' ); ?></option>
        <option value="<?php echo esc_attr( '180' ); ?>" <?php selected( $output_choice, '180' ); ?>><?php echo esc_html( '180' ); ?></option>
        <option value="<?php echo esc_attr( '365' ); ?>" <?php selected( $output_choice, '365' ); ?>><?php echo esc_html( '365' ); ?></option>
    </select>
    <?php
}

// Chatbot Simple Chart - Ver 1.6.3
function generate_gd_bar_chart($labels, $data, $colors, $name) {
    // Create an image
    $width = 500;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);

    // Allocate colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $light_blue = imagecolorallocate($image, 173, 216, 230); // Light Blue color

    // Fill the background
    imagefill($image, 0, 0, $white);

    // Add title
    $title = "Visitor Interactions";
    $font = 5;
    $title_x = ($width - imagefontwidth($font) * strlen($title)) / 2;
    $title_y = 5;
    imagestring($image, $font, $title_x, $title_y, $title, $black);

    // Calculate number of bars and bar width
    $bar_count = count($data);
    // $bar_width = (int)($width / ($bar_count * 2));
    $bar_width = round($width / ($bar_count * 2));

    // Offset for the chart
    $offset_x = 25;
    $offset_y = 25;
    $top_padding = 5;

    // Bottom line
    imageline($image, 0, $height - $offset_y, $width, $height - $offset_y, $black);

    // Font size for data and labels
    $font_size = 8;

    // Draw bars
    $chart_title_height = 30; // adjust this to the height of your chart title
    for ($i = 0; $i < $bar_count; $i++) {
        $bar_height = (int)(($data[$i] * ($height - $offset_y - $top_padding - $chart_title_height)) / max($data));
        $x1 = $i * $bar_width * 2 + $offset_x;
        $y1 = $height - $bar_height - $offset_y + $top_padding;
        $x2 = ($i * $bar_width * 2) + $bar_width + $offset_x;
        $y2 = $height - $offset_y;

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = $x1 + ($bar_width / 2);
        $data_value_x = $center_x - (imagefontwidth($font_size) * strlen($data[$i]) / 2);
        $data_value_y = $y1 - 15;
        $data_value_y = max($data_value_y, 0);

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = round($x1 + ($bar_width / 2));

        $data_value_x = $center_x - (imagefontwidth(round($font_size)) * strlen($data[$i]) / 2);
        $label_x = $center_x - (imagefontwidth(round($font_size)) * strlen($labels[$i]) / 2);

        $data_value_y = $y1 - 5; // Moves the counts up or down
        $data_value_y = max($data_value_y, 0);

        // Fix: Explicitly cast to int
        $data_value_x = (int)($data_value_x);
        $data_value_y = (int)($data_value_y);

        // https://fonts.google.com/specimen/Roboto - Ver 1.6.7
        $fontFile = plugin_dir_path(__FILE__) . 'assets/fonts/roboto/Roboto-Black.ttf';

        imagettftext($image, $font_size, 0, $data_value_x, $data_value_y, $black, $fontFile, $data[$i]);

        $label_x = $center_x - ($font_size * strlen($labels[$i]) / 2) + 7; // Moves the dates left or right
        $label_y = $height - $offset_y + 15; // Moves the dates up or down

        imagettftext($image, $font_size, 0, $label_x, $label_y, $black, $fontFile, $labels[$i]);

    }

    // Save the image
    $img_path = plugin_dir_path(__FILE__) . 'assets/images/' . $name . '.png';
    imagepng($image, $img_path);

    // Free memory
    imagedestroy($image);

    return $img_path;
}


// Chatbot Charts - Ver 1.6.3
function chatbot_chatgpt_simple_chart_shortcode_function( $atts ) {

    // Check is GD Library is installed - Ver 1.6.3
    if (!extension_loaded('gd')) {
        // GD Library is installed and loaded
        // DIAG - Log the output choice
        // back_trace( 'NOTICE', 'GD Library is installed and loaded.');
        chatbot_chatgpt_general_admin_notice('Chatbot requires the GD Library to function correctly, but it is not installed or enabled on your server. Please install or enable the GD Library.');
        // DIAG - Log the output choice
        // back_trace( 'NOTICE', 'GD Library is not installed! No chart will be displayed.');
        // Disable the shortcode functionality
        return;
    }

    // Retrieve the reporting period
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));

    // Parsing shortcode attributes
    $a = shortcode_atts( array(
        'name' => 'visitorsChart_' . rand(100, 999),
        'type' => 'bar',
        'labels' => 'label',
        ), $atts );

    if(isset($atts['from_database']) && $atts['from_database'] == 'true') {

        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';
        
        // Get the reporting period from the options
        $reporting_period = gesc_attr(et_option('chatbot_chatgpt_reporting_period'));
        
        // Calculate the start date and group by clause based on the reporting period
        if($reporting_period === 'Daily') {
            $start_date = date('Y-m-d', strtotime("-7 days"));
            // $group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
            $group_by = "DATE_FORMAT(date, '%m-%d')";
        } elseif($reporting_period === 'Monthly') {
            $start_date = date('Y-m-01', strtotime("-3 months"));
            $group_by = "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $start_date = date('Y-01-01', strtotime("-3 years"));
            $group_by = "DATE_FORMAT(date, '%Y')";
        }
        
        // Modify the SQL query to group the results based on the reporting period
        $results = $wpdb->get_results("SELECT $group_by AS date, SUM(count) AS count FROM $table_name WHERE date >= '$start_date' GROUP BY $group_by");

        if(!empty($wpdb->last_error)) {
            // DIAG - Handle the error
            // back_trace( 'ERROR', 'SQL query error ' . $wpdb->last_error);
            return;
        } else if(!empty($results)) {
            $labels = [];
            $data = [];
            foreach ($results as $result) {
                $labels[] = $result->date;
                $data[] = $result->count;
            }
            
            $a['labels'] = $labels;
            $atts['data'] = $data;
        }
    }

    if (empty( $a['labels']) || empty($atts['data'])) {
        // return '<p>You need to specify both the labels and data for the chart to work.</p>';
        return '<p>No data to chart at this time. Plesae visit again later.</p>';
    }

    // Generate the chart
    $img_path = generate_gd_bar_chart($a['labels'], $atts['data'], $atts['color'] ?? null, $a['name']);
    $img_url = plugin_dir_url(__FILE__) . 'assets/images/' . $a['name'] . '.png';

    wp_schedule_single_event(time() + 60, 'chatbot_chatgpt_delete_chart', array($img_path)); // 60 seconds delay

    return '<img src="' . $img_url . '" alt="Bar Chart">';
}
// TEMPORARILY REMOVED AS SOME USERS ARE EXPERIENCING ISSUES WITH THE CHARTS - Ver 1.7.8
// Add shortcode
// add_shortcode('chatbot_chatgpt_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');
// add_shortcode('chatbot_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');


// Clean up ../image subdirectory - Ver 1.6.3
function chatbot_chatgpt_delete_chart() {
    $img_dir_path = plugin_dir_path(__FILE__) . 'assets/images/'; // Replace with your actual directory path
    $png_files = glob($img_dir_path . '*.png'); // Search for .png files in the directory

    foreach ($png_files as $png_file) {
        unlink($png_file); // Delete each .png file
    }
}
add_action('chatbot_chatgpt_delete_chart', 'chatbot_chatgpt_delete_chart');

// Return Interactions data in a table - Ver 1.7.8
function chatbot_chatgpt_interactions_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';

    // Get the reporting period from the options
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));
    
        // Calculate the start date and group by clause based on the reporting period
        if($reporting_period === 'Daily') {
            $start_date = date('Y-m-d', strtotime("-7 days"));
            // $group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
            $group_by = "DATE_FORMAT(date, '%m-%d')";
        } elseif($reporting_period === 'Monthly') {
            $start_date = date('Y-m-01', strtotime("-3 months"));
            $group_by = "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $start_date = date('Y-01-01', strtotime("-3 years"));
            $group_by = "DATE_FORMAT(date, '%Y')";
        }
        
        // Modify the SQL query to group the results based on the reporting period
        $results = $wpdb->get_results("SELECT $group_by AS date, SUM(count) AS count FROM $table_name WHERE date >= '$start_date' GROUP BY $group_by");

        if(!empty($wpdb->last_error)) {
            // DIAG - Handle the error
            // back_trace( 'ERROR', 'SQL query error ' . $wpdb->last_error);
            return;
        } else if(!empty($results)) {
            $labels = [];
            $data = [];
            foreach ($results as $result) {
                $labels[] = $result->date;
                $data[] = $result->count;
            }
            
            $a['labels'] = $labels;
            $atts['data'] = $data;

            $output = '<table class="widefat striped" style="table-layout: fixed; width: auto;">';
            $output .= '<thead><tr><th style="width: 96px;">Date</th><th style="width: 96px;">Count</th></tr></thead>';
            $output .= '<tbody>';
            foreach ($results as $result) {
                $output .= '<tr>';
                $output .= '<td style="width: 96px;">' . $result->date . '</td>';
                $output .= '<td style="width: 96px;">' . $result->count . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody>';
            $output .= '</table>';            

        return $output;

    } else {
        return '<p>No data to report at this time. Plesae visit again later.</p>';
    }

}

// Count the number of conversations stored - Ver 1.7.6
function chatbot_chatgpt_count_conversations() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    $results = $wpdb->get_results("SELECT COUNT(id) AS count FROM $table_name");
    // TODO - Handle errors
    return $results[0]->count;

}

// Calculated size of the conversations stored - Ver 1.7.6
function chatbot_chatgpt_size_conversations() {

    global $wpdb;

    // Use the DB_NAME constant instead of directly accessing the protected property
    $database_name = DB_NAME;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Prepare the SQL query
    $query = $wpdb->prepare("
        SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `Size_in_MB`
        FROM information_schema.TABLES
        WHERE table_schema = %s
          AND table_name = %s
    ", $database_name, $table_name);

    // Execute the query
    $results = $wpdb->get_results($query);

    // Handle errors
    if (is_wp_error($results)) {
        return 'Error: ' . $results->get_error_message();
    }

    // Check if results are returned
    if (empty($results)) {
        return 'No results found';
    }

    // Return the size in MB
    return $results[0]->Size_in_MB;

}

// Total Prompt Tokens, Completion Tokens, and Total Tokens - Ver 1.8.5
function chatbot_chatgpt_total_tokens() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Get the reporting period from the options
    $reporting_period = esc_attr(get_option('chatbot_chatgpt_reporting_period'));
    
    // Calculate the start date and group by clause based on the reporting period
    if ($reporting_period === 'Daily') {
        $start_date = date('Y-m-d', strtotime("-7 days"));
        $group_by = "DATE_FORMAT(interaction_time, '%m-%d')";
    } elseif ($reporting_period === 'Monthly') {
        $start_date = date('Y-m-01', strtotime("-3 months"));
        $group_by = "DATE_FORMAT(interaction_time, '%Y-%m')";
    } else {
        $start_date = date('Y-01-01', strtotime("-3 years"));
        $group_by = "DATE_FORMAT(interaction_time, '%Y')";
    }
    
    $results = $wpdb->get_results("
        SELECT $group_by AS interaction_time, 
            SUM(CASE WHEN user_type = 'Total Tokens' THEN CAST(message_text AS UNSIGNED) ELSE 0 END) AS count 
        FROM $table_name 
        WHERE interaction_time >= '$start_date' 
        GROUP BY $group_by
        ");
    
    if (!empty($wpdb->last_error)) {
        // Handle the error
        return '<p>Error retrieving data: ' . esc_html($wpdb->last_error) . '</p>';
    } else if (!empty($results)) {
        $labels = [];
        $data = [];
        foreach ($results as $result) {
            $labels[] = $result->interaction_time; // Changed from result->date to result->interaction_time
            $data[] = $result->count;
        }
        
        $output = '<table class="widefat striped" style="table-layout: fixed; width: auto;">';
        $output .= '<thead><tr><th>Date</th><th>Total Tokens</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($results as $result) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($result->interaction_time) . '</td>'; // Corrected to use interaction_time
            $output .= '<td>' . number_format($result->count) . '</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';
    
        return $output;
    } else {
        return '<p>No data to report at this time. Please visit again later.</p>';
    }
    

}

function chatbot_chatgpt_download_interactions_data() {

    // Export data from the chatbot_chatgpt_interactions table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_interactions', 'Chatbot-ChatGPT-Interactions');

}

function chatbot_chatgpt_download_conversation_data() {

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_conversation_log', 'Chatbot-ChatGPT-Conversation Logs');
    
}

function chatbot_chatgpt_download_token_usage_data() {

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    chatbot_chatgpt_export_data('chatbot_chatgpt_conversation_log', 'Chatbot-ChatGPT-Token Usage');

}

// Download the conversation data - Ver 1.7.6
function chatbot_chatgpt_export_data( $t_table_name, $t_file_name ) {

    global $chatbot_chatgpt_plugin_dir_path;

    // Export data from the chatbot_chatgpt_conversation_log table to a csv file
    global $wpdb;
    $table_name = $wpdb->prefix . $t_table_name;

    if ( $t_file_name === 'Chatbot-ChatGPT-Token Usage' ) {
        $results = $wpdb->get_results("SELECT id, session_id, user_id, interaction_time, user_type, message_text FROM $table_name WHERE user_type IN ('Prompt Tokens', 'Completion Tokens', 'Total Tokens')", ARRAY_A);
    } else {
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }

    // Check for empty results
    if (empty($results)) {
        $message = __( 'No data in the file. Please enable conversation and interaction logging if currently off.', 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Check for errors
    if (!empty($wpdb->last_error)) {
        $message = __( 'Error reading table: ' . $wpdb->last_error, 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Ask user where to save the file
    $filename = $t_file_name . '-' . date('Y-m-d') . '.csv';
    // Replace spaces with - in the filename
    $filename = str_replace(' ', '-', $filename);
    $results_dir_path = $chatbot_chatgpt_plugin_dir_path . 'results/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($results_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace( 'ERROR', 'Failed to create directory.');
        return;
    }

    $results_csv_file = $results_dir_path . $filename;
    
    // Open file for writing
    $file = fopen($results_csv_file, 'w');

    // Check if file opened successfully
    if ($file === false) {
        $message = __( 'Error opening file for writing. Please try again.', 'chatbot-chatgpt' );
        set_transient('chatbot_chatgpt_admin_error', $message, 60); // Expires in 60 seconds
        wp_safe_redirect(admin_url('options-general.php?page=chatbot-chatgpt&tab=reporting')); // Redirect to your settings page
        exit;
    }

    // Write headers to file
    if (isset($results[0]) && is_array($results[0])) {
        $keys = array_keys($results[0]);
        fputcsv($file, $keys);
    } else {
        $class = 'notice notice-error';
        $message = __( 'Chatbot No data in the file. Please enable conversation logging if currently off.', 'chatbot-chatgpt' );
        // printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // Write results to file
    foreach ($results as $result) {
        $result = array_map(function($value) {
            return $value !== null ? mb_convert_encoding($value, 'UTF-8', 'auto') : '';
        }, $result);
        fputcsv($file, $result);
    }

    // Close the file
    fclose($file);

    // Exit early if the file doesn't exist
    if (!file_exists($results_csv_file)) {
        $class = 'notice notice-error';
        $message = __( 'File not found!' . $results_csv_file, 'chatbot-chatgpt' );
        // printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE', 'File path: ' . $results_csv_file);

    if (!file_exists($results_csv_file)) {
        // back_trace( 'ERROR', 'File does not exist: ' . $results_csv_file);
        return;
    }
    
    if (!is_readable($results_csv_file)) {
        // back_trace( 'ERROR', 'File is not readable ' . $results_csv_file);
        return;
    }
    
    $csv_data = file_get_contents(realpath($results_csv_file));
    if ($csv_data === false) {
        $class = 'notice notice-error';
        $message = __( 'Error reading file', 'chatbot-chatgpt' );
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }
    
    if (!is_writable($results_csv_file)) {
        // back_trace( 'ERROR', 'File is not writable: ' . $results_csv_file);
        return;
    }  
    
    // Deliver the file for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);
    echo $csv_data;

    // Delete the file
    unlink($results_csv_file);
    exit;

}
add_action('admin_post_chatbot_chatgpt_download_conversation_data', 'chatbot_chatgpt_download_conversation_data');
add_action('admin_post_chatbot_chatgpt_download_interactions_data', 'chatbot_chatgpt_download_interactions_data');
add_action('admin_post_chatbot_chatgpt_download_token_usage_data', 'chatbot_chatgpt_download_token_usage_data');

// Gap Analysis Section Callback - Ver 2.4.2
function chatbot_chatgpt_gap_analysis_section_callback($args) {
    ?>
    <p>Gap Analysis identifies questions that users ask but are not well-answered by the FAQ database. Use this data to improve your FAQ coverage.</p>
    <?php
}

// Gap Analysis Callback - Ver 2.4.2
function chatbot_chatgpt_gap_analysis_callback() {
    error_log('🔍 GAP ANALYSIS CALLBACK CALLED');

    // Get saved frequency setting (default: weekly)
    $analysis_frequency = get_option('chatbot_gap_analysis_frequency', 'weekly');
    $days_map = ['weekly' => 7, 'monthly' => 30, 'yearly' => 365];
    $days = $days_map[$analysis_frequency] ?? 7;

    // Get gap analysis data
    $data = chatbot_get_gap_analysis_data($days);

    error_log('Gap data received: ' . print_r($data, true));

    $total_gaps = $data['total_gaps'];
    $unresolved_gaps = $data['unresolved_gaps'];
    $active_clusters = $data['active_clusters'];
    $top_individual_gaps = $data['top_individual_gaps'];

    error_log("Total gaps: $total_gaps, Clusters: " . count($active_clusters));

    ?>
    <div>
        <!-- Header -->
        <h2 style="margin: 0 0 10px 0; color: #1e293b;">AI Gap Analysis Dashboard</h2>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 14px;">
            Identifies questions users ask that your FAQ database can't answer well (confidence < 60%)
        </p>

        <!-- AI Summary Preview -->
        <?php
        $frequency_descriptions = [
            'weekly' => [
                'title' => 'Weekly Analysis Mode',
                'interval' => '7 days',
                'description' => 'AI will automatically analyze gap questions every week. Best for new FAQ databases that need frequent updates.',
                'color' => '#3b82f6'
            ],
            'monthly' => [
                'title' => 'Monthly Analysis Mode',
                'interval' => '30 days',
                'description' => 'AI will automatically analyze gap questions every month. Good for mature FAQ databases with occasional updates.',
                'color' => '#8b5cf6'
            ],
            'yearly' => [
                'title' => 'Yearly Analysis Mode',
                'interval' => '365 days',
                'description' => 'AI will automatically analyze gap questions once per year. Best for very mature FAQ databases that rarely need updates.',
                'color' => '#059669'
            ]
        ];
        $current_freq = $frequency_descriptions[$analysis_frequency];
        ?>
        <div style="background: white; border-left: 4px solid <?php echo $current_freq['color']; ?>; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: start; gap: 15px;">
                <div style="font-size: 32px;">🤖</div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #111827;">
                        <span style="background-color: <?php echo $current_freq['color']; ?>; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-right: 8px;">
                            <?php echo strtoupper($analysis_frequency); ?>
                        </span>
                        <?php echo $current_freq['title']; ?>
                    </h3>
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280; line-height: 1.5;">
                        <?php echo $current_freq['description']; ?>
                    </p>
                    <div style="background-color: #f9fafb; padding: 10px; border-radius: 4px; font-size: 13px; color: #374151;">
                        <strong>How it works:</strong>
                        <ol style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li>Users ask questions that aren't in your FAQ database (confidence &lt; 60%)</li>
                            <li>Questions are automatically logged as "gap questions"</li>
                            <li>Every <strong><?php echo $current_freq['interval']; ?></strong>, AI analyzes all unresolved gap questions</li>
                            <li>AI groups similar questions into clusters and suggests new FAQs</li>
                            <li>You review suggestions and manually add approved FAQs to knowledge base</li>
                        </ol>
                    </div>
                    <div style="background-color: #fffbeb; border-left: 3px solid #f59e0b; padding: 12px; border-radius: 4px; margin-top: 12px; font-size: 13px; color: #78350f;">
                        <strong>💡 How to Write High-Confidence FAQs:</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px; line-height: 1.6;">
                            <li><strong>Use diverse keywords</strong> - Include synonyms and variations (e.g., "hours, open, close, time, schedule")</li>
                            <li><strong>Think like your customers</strong> - Add keywords matching how real people ask questions</li>
                            <li><strong>Be specific</strong> - Add keywords for common variations ("wifi" and "wi-fi", "internet" and "broadband")</li>
                            <li><strong>Review AI suggestions</strong> - The AI learns from actual customer questions to suggest better keywords</li>
                        </ul>
                        <p style="margin: 10px 0 0 0; font-style: italic; font-size: 12px;">
                            Better keywords = Higher confidence scores = More accurate answers
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Frequency -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151;">
                Analysis Frequency:
            </label>
            <select id="chatbot_analysis_frequency" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; width: 200px;">
                <option value="weekly" <?php selected($analysis_frequency, 'weekly'); ?>>Weekly</option>
                <option value="monthly" <?php selected($analysis_frequency, 'monthly'); ?>>Monthly</option>
                <option value="yearly" <?php selected($analysis_frequency, 'yearly'); ?>>Yearly</option>
            </select>
            <p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280;">
                This controls how often AI automatically analyzes gap questions
            </p>
        </div>

        <!-- Stats Overview -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #111827;">📊 Overview</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px; font-size: 13px; color: #6b7280;">Total Gap Questions (<?php echo $days; ?> days):</td>
                    <td style="padding: 12px; font-size: 18px; font-weight: bold; text-align: right; color: #111827;"><?php echo $total_gaps; ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px; font-size: 13px; color: #6b7280;">Unresolved:</td>
                    <td style="padding: 12px; font-size: 18px; font-weight: bold; text-align: right; color: #dc2626;"><?php echo $unresolved_gaps; ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-size: 13px; color: #6b7280;">AI Suggestions:</td>
                    <td style="padding: 12px; font-size: 18px; font-weight: bold; text-align: right; color: #2563eb;"><?php echo count($active_clusters); ?></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($active_clusters)) : ?>
        <!-- AI-Suggested FAQ Additions -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #111827;">✨ AI-Suggested FAQ Additions (<?php echo count($active_clusters); ?>)</h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #6b7280; padding: 12px; background-color: #f9fafb; border-left: 3px solid #3b82f6; border-radius: 4px;">
                <b>Human Review Required:</b> AI has analyzed similar questions and suggested FAQ entries below. Review and manually add to your knowledge base.
            </p>

            <?php foreach ($active_clusters as $cluster) :
                $suggested_faq = json_decode($cluster['suggested_faq'], true);
                $sample_questions = json_decode($cluster['sample_questions'], true);
                $priority_label = $cluster['priority_score'] >= 100 ? 'High' : ($cluster['priority_score'] >= 50 ? 'Medium' : 'Low');
                $action_type = $cluster['action_type'] ?? 'create';
                $is_improve = ($action_type === 'improve');
                $border_color = $is_improve ? '#f59e0b' : '#3b82f6';
                $action_label = $is_improve ? '🔧 Improve Existing FAQ' : '✨ Create New FAQ';
            ?>
            <div style="background: #f9fafb; border-left: 4px solid <?php echo $border_color; ?>; border: 1px solid #d1d5db; border-radius: 6px; padding: 16px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <div style="font-size: 10px; font-weight: 700; color: <?php echo $border_color; ?>; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php echo $action_label; ?>
                        </div>
                        <h4 style="margin: 0 0 4px 0; color: #111827; font-size: 15px;"><?php echo esc_html($cluster['cluster_name']); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: #6b7280;"><?php echo esc_html($cluster['cluster_description']); ?></p>
                    </div>
                    <div style="margin-left: 15px;">
                        <span style="background-color: #fff; border: 1px solid #d1d5db; padding: 4px 10px; border-radius: 4px; font-size: 11px; color: #374151;">
                            <?php echo $priority_label; ?> Priority • Asked <?php echo $cluster['question_count']; ?>x
                        </span>
                    </div>
                </div>

                <!-- Sample Questions -->
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 6px;">Sample Questions:</div>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #374151; line-height: 1.5;">
                        <?php foreach (array_slice($sample_questions, 0, 3) as $question) : ?>
                        <li style="margin-bottom: 3px;"><?php echo esc_html($question); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if ($is_improve) :
                    $suggested_keywords = json_decode($cluster['suggested_keywords'] ?? '[]', true);
                    if (!is_array($suggested_keywords)) {
                        $suggested_keywords = [];
                    }

                    // Get existing FAQ details (stored in suggested_faq field for improve actions)
                    $existing_faq = $suggested_faq; // Reuse the same variable
                    $current_keywords = !empty($existing_faq['keywords']) ? array_map('trim', explode(',', $existing_faq['keywords'])) : [];
                ?>
                <!-- Improve Existing FAQ -->
                <div style="background: #fffbeb; padding: 12px; border-radius: 4px; margin-bottom: 12px; border: 1px solid #f59e0b;">
                    <div style="font-size: 11px; font-weight: 600; color: #92400e; margin-bottom: 8px;">
                        🔧 Improve Existing FAQ
                    </div>

                    <!-- Existing FAQ Info -->
                    <div style="background: white; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                        <div style="margin-bottom: 6px;">
                            <strong style="font-size: 12px; color: #6b7280;">FAQ ID:</strong>
                            <span style="font-size: 12px; color: #374151; font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px;">
                                <?php echo esc_html($cluster['existing_faq_id'] ?? 'N/A'); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 6px;">
                            <strong style="font-size: 12px; color: #6b7280;">Question:</strong>
                            <span style="font-size: 12px; color: #111827;"><?php echo esc_html($existing_faq['question'] ?? 'N/A'); ?></span>
                        </div>
                    </div>

                    <!-- Current Keywords -->
                    <div style="margin-bottom: 10px;">
                        <strong style="font-size: 12px; color: #111827;">Current Keywords:</strong>
                        <div style="margin-top: 4px;">
                            <?php if (!empty($current_keywords)) : ?>
                                <?php foreach ($current_keywords as $keyword) : ?>
                                    <span style="display: inline-block; background: #e5e7eb; border: 1px solid #d1d5db; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin: 2px; color: #374151;">
                                        <?php echo esc_html($keyword); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span style="font-size: 11px; color: #9ca3af; font-style: italic;">No keywords yet</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Suggested New Keywords -->
                    <div>
                        <strong style="font-size: 12px; color: #111827;">✨ Suggested Keywords to Add:</strong>
                        <div style="margin-top: 4px;">
                            <?php foreach ($suggested_keywords as $keyword) : ?>
                                <span style="display: inline-block; background: #fef3c7; border: 1px solid #fbbf24; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin: 2px; color: #78350f; font-weight: 600;">
                                    <?php echo esc_html($keyword); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <p style="margin: 10px 0 0 0; font-size: 11px; color: #92400e; font-style: italic; background: #fef3c7; padding: 6px; border-radius: 3px;">
                        💡 Add these keywords to improve confidence score for similar questions
                    </p>
                </div>
                <?php else : ?>
                <!-- Create New FAQ -->
                <?php if (!empty($suggested_faq)) : ?>
                <div style="background: white; padding: 12px; border-radius: 4px; margin-bottom: 12px; border: 1px solid #e5e7eb;">
                    <div style="font-size: 11px; font-weight: 600; color: #059669; margin-bottom: 6px;">AI-Suggested FAQ:</div>
                    <div style="margin-bottom: 8px;">
                        <strong style="font-size: 13px; color: #111827;">Q:</strong>
                        <span style="font-size: 13px; color: #374151;"><?php echo esc_html($suggested_faq['question']); ?></span>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong style="font-size: 13px; color: #111827;">A:</strong>
                        <span style="font-size: 13px; color: #374151;"><?php echo esc_html($suggested_faq['answer']); ?></span>
                    </div>
                    <?php if (!empty($suggested_faq['keywords'])) : ?>
                    <div>
                        <strong style="font-size: 13px; color: #111827;">Keywords:</strong>
                        <span style="font-size: 13px; color: #6b7280; font-style: italic;"><?php echo esc_html($suggested_faq['keywords']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Actions -->
                <div style="text-align: right;">
                    <button onclick="chatbotResolveCluster(<?php echo $cluster['id']; ?>)" class="button button-primary" style="margin-right: 5px;">
                        ✓ Mark Resolved
                    </button>
                    <button onclick="chatbotDismissCluster(<?php echo $cluster['id']; ?>)" class="button">
                        Dismiss
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($top_individual_gaps)) : ?>
        <!-- Top Individual Gap Questions -->
        <div style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #1e293b;">📋 Top Unanswered Questions (Not Yet Clustered)</h3>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">These questions haven't been analyzed by AI yet. They will be processed in the next weekly analysis.</p>

            <table class="widefat striped" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f1f5f9;">
                        <th style="padding: 10px;">Question</th>
                        <th style="padding: 10px; width: 100px; text-align: center;">Times Asked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_individual_gaps as $gap) : ?>
                    <tr>
                        <td style="padding: 10px;"><?php echo esc_html($gap['question_text']); ?></td>
                        <td style="padding: 10px; text-align: center; font-weight: bold; color: #1e293b;"><?php echo $gap['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #111827;">🧪 Manual Testing</h3>
            <p style="margin: 0 0 15px 0; font-size: 13px; color: #6b7280;">
                Test the AI analysis by analyzing your last 4 chatbot conversations. The AI will review the questions, answers, and confidence scores to suggest FAQ improvements.
            </p>
            <div style="background: #f0f9ff; border-left: 3px solid #3b82f6; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
                <strong style="color: #1e40af; font-size: 13px;">How it works:</strong>
                <ol style="margin: 8px 0 0 0; padding-left: 20px; font-size: 12px; color: #1e3a8a; line-height: 1.6;">
                    <li>Have at least 4 conversations with the chatbot first</li>
                    <li>Click the button below to analyze those conversations</li>
                    <li>AI will show each question, answer, and confidence score</li>
                    <li>AI will suggest whether to improve existing FAQs or create new ones</li>
                </ol>
            </div>
            <button onclick="chatbotRunGapAnalysisLast10()" class="button button-primary" style="font-size: 16px; padding: 10px 30px; height: auto;">
                🤖 Analyze Last 4 Conversations
            </button>
        </div>
    </div>

    <script>
    function chatbotResolveCluster(clusterId) {
        if (!confirm('Mark this cluster as resolved (FAQ created)?')) return;

        jQuery.post(ajaxurl, {
            action: 'chatbot_resolve_cluster',
            cluster_id: clusterId,
            nonce: '<?php echo wp_create_nonce('chatbot_gap_analysis'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Cluster marked as resolved!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        });
    }

    function chatbotDismissCluster(clusterId) {
        if (!confirm('Dismiss this cluster?')) return;

        jQuery.post(ajaxurl, {
            action: 'chatbot_dismiss_cluster',
            cluster_id: clusterId,
            nonce: '<?php echo wp_create_nonce('chatbot_gap_analysis'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Cluster dismissed!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        });
    }

    function chatbotRunGapAnalysisLast10() {
        if (!confirm('Analyze your last 4 chatbot conversations with AI?\n\nThe AI will review:\n- Questions asked\n- Answers given\n- Confidence scores\n- Whether to improve existing FAQs or create new ones')) return;

        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '⏳ Analyzing conversations...';

        jQuery.post(ajaxurl, {
            action: 'chatbot_analyze_last_10_gaps',
            nonce: '<?php echo wp_create_nonce('chatbot_gap_analysis'); ?>'
        }, function(response) {
            btn.disabled = false;
            btn.textContent = originalText;

            if (response.success) {
                const convos = response.data.conversations || [];
                let message = '✓ Analysis Complete!\n\n';
                message += 'Analyzed ' + convos.length + ' conversations:\n\n';

                convos.forEach((conv, idx) => {
                    const confPercent = Math.round(conv.confidence * 100);
                    message += (idx + 1) + '. Q: ' + conv.question.substring(0, 60) + '...\n';
                    message += '   Confidence: ' + confPercent + '%\n';
                    if (conv.matched_faq_id) {
                        message += '   Matched: ' + conv.matched_faq_id + '\n';
                    }
                    message += '\n';
                });

                message += 'Found ' + (response.data.suggestions || 0) + ' AI suggestions.\n\nReloading dashboard...';
                alert(message);
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        }).fail(function() {
            btn.disabled = false;
            btn.textContent = originalText;
            alert('Request failed. Please try again.');
        });
    }

    function chatbotGenerateMockData() {
        if (!confirm('Generate mock gap analysis data? This will create sample questions and AI clusters for testing the dashboard.')) return;

        const btn = event.target;
        btn.disabled = true;
        btn.textContent = '⏳ Generating...';

        jQuery.post(ajaxurl, {
            action: 'chatbot_generate_mock_gap_data',
            nonce: '<?php echo wp_create_nonce('chatbot_gap_analysis'); ?>'
        }, function(response) {
            btn.disabled = false;
            btn.textContent = '✨ Generate Mock Data';

            if (response.success) {
                alert('Mock data created! Generated ' + response.data.questions + ' questions and ' + response.data.clusters + ' clusters.');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        });
    }

    // Handle frequency dropdown change
    jQuery(document).ready(function($) {
        $('#chatbot_analysis_frequency').on('change', function() {
            const frequency = $(this).val();
            const originalVal = '<?php echo $analysis_frequency; ?>';

            if (frequency === originalVal) return;

            $.post(ajaxurl, {
                action: 'chatbot_save_analysis_frequency',
                frequency: frequency,
                nonce: '<?php echo wp_create_nonce('chatbot_gap_analysis'); ?>'
            }, function(response) {
                if (response.success) {
                    // Show success message
                    const message = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0; padding: 10px;"><p><b>Saved!</b> Analysis frequency updated to ' + frequency + '.</p></div>');
                    $('#chatbot_analysis_frequency').parent().parent().after(message);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            });
        });
    });
    </script>
    <?php
}

// Function to display the reporting message - Ver 1.7.9
function chatbot_chatgpt_admin_notice() {
    $message = get_transient('chatbot_chatgpt_admin_error');
    if (!empty($message)) {
        printf('<div class="%1$s"><p><b>Chatbot: </b>%2$s</p></div>', 'notice notice-error is-dismissible', $message);
        delete_transient('chatbot_chatgpt_admin_error'); // Clear the transient after displaying the message
    }
}
add_action('admin_notices', 'chatbot_chatgpt_admin_notice');
