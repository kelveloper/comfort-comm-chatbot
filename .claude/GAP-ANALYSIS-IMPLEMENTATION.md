# Gap Question Analysis - Implementation Plan

**Feature:** Automatically detect questions users ask that aren't in the FAQ database
**Goal:** Show client exactly what FAQs they need to add
**Priority:** P0 (Highest Value)

---

## 🎯 How It Works

### User Flow:
1. User asks: "Can I use my own router with Spectrum?"
2. FAQ search returns low confidence match (< 40%)
3. System logs this as a "gap question"
4. AI clusters similar gap questions weekly
5. Dashboard shows: "8 people asked about router compatibility - Suggested FAQ"
6. Admin clicks "Generate FAQ" → AI creates draft FAQ
7. Admin reviews, edits, and adds to FAQ database

---

## 🗄️ Database Schema

### New Table: `wp_chatbot_gap_questions`

```sql
CREATE TABLE wp_chatbot_gap_questions (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    session_id VARCHAR(255),
    user_id BIGINT(20),
    page_id BIGINT(20),
    faq_confidence FLOAT,              -- The confidence score from FAQ search
    faq_match_id VARCHAR(50),          -- Which FAQ was the closest match (if any)
    asked_date DATETIME NOT NULL,
    user_agent TEXT,
    is_clustered BOOLEAN DEFAULT 0,    -- Has this been processed by AI clustering?
    cluster_id INT,                     -- Which cluster does this belong to?
    is_resolved BOOLEAN DEFAULT 0,     -- Has admin added an FAQ for this?
    resolved_faq_id VARCHAR(50),       -- Which FAQ was created to address this?
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_confidence (faq_confidence),
    INDEX idx_date (asked_date),
    INDEX idx_clustered (is_clustered),
    INDEX idx_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### New Table: `wp_chatbot_gap_clusters`

```sql
CREATE TABLE wp_chatbot_gap_clusters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cluster_name VARCHAR(255),         -- AI-generated name: "Router Compatibility"
    cluster_description TEXT,          -- AI summary of what users are asking
    question_count INT DEFAULT 0,      -- How many gap questions in this cluster
    sample_questions TEXT,             -- JSON array of example questions
    suggested_faq TEXT,                -- AI-generated FAQ draft
    suggested_keywords TEXT,           -- AI-suggested keywords
    suggested_category VARCHAR(100),   -- AI-suggested category
    priority_score FLOAT,              -- AI-calculated importance (based on frequency)
    created_date DATETIME NOT NULL,
    last_updated DATETIME,
    status ENUM('new', 'reviewed', 'faq_created', 'dismissed') DEFAULT 'new',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### New Table: `wp_chatbot_faq_usage` (Track FAQ hits)

```sql
CREATE TABLE wp_chatbot_faq_usage (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    faq_id VARCHAR(50) NOT NULL,
    question TEXT,
    category VARCHAR(100),
    hit_count INT DEFAULT 1,
    last_asked DATETIME NOT NULL,
    first_asked DATETIME NOT NULL,
    avg_confidence FLOAT,              -- Average confidence when this FAQ is matched
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_faq (faq_id),
    INDEX idx_hit_count (hit_count),
    INDEX idx_last_asked (last_asked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📝 Code Implementation

### Step 1: Log Gap Questions (Modify FAQ Search)

**File:** `includes/knowledge-navigator/chatbot-kn-faq-import.php`

Add this function:

```php
/**
 * Log gap questions - questions that don't have good FAQ matches
 * Ver 2.4.3
 */
function chatbot_log_gap_question($question, $faq_result, $session_id, $user_id, $page_id) {
    global $wpdb;

    $confidence = $faq_result['confidence'] ?? 'none';
    $score = $faq_result['score'] ?? 0;

    // Only log as gap if confidence is low/medium or no match
    if ($confidence === 'none' || $confidence === 'low' || ($confidence === 'medium' && $score < 0.5)) {

        $table_name = $wpdb->prefix . 'chatbot_gap_questions';

        $data = array(
            'question_text' => sanitize_text_field($question),
            'session_id' => sanitize_text_field($session_id),
            'user_id' => $user_id,
            'page_id' => $page_id,
            'faq_confidence' => floatval($score),
            'faq_match_id' => isset($faq_result['match']['id']) ? $faq_result['match']['id'] : null,
            'asked_date' => current_time('mysql'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
        );

        $wpdb->insert($table_name, $data);

        error_log("CHATBOT: Gap question logged - '{$question}' (confidence: {$confidence}, score: {$score})");
    }
}
```

Modify `chatbot_faq_search()` function (around line 345) to call this:

```php
// At the end of chatbot_faq_search() function, before returning
// Log gap question if confidence is low
if (function_exists('chatbot_log_gap_question')) {
    // Get session/user info from globals or current context
    $session_id = $_POST['session_id'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    $page_id = $_POST['page_id'] ?? 0;

    chatbot_log_gap_question($user_question, $result, $session_id, $user_id, $page_id);
}

return $result;
```

### Step 2: Track FAQ Usage

**File:** `includes/knowledge-navigator/chatbot-kn-faq-import.php`

Add this function:

```php
/**
 * Track FAQ usage - increment hit count when FAQ is used
 * Ver 2.4.3
 */
function chatbot_track_faq_usage($faq_match, $confidence_score) {
    global $wpdb;

    // Only track if there's a valid FAQ match
    if (empty($faq_match) || !isset($faq_match['id'])) {
        return;
    }

    $table_name = $wpdb->prefix . 'chatbot_faq_usage';
    $faq_id = sanitize_text_field($faq_match['id']);

    // Check if this FAQ already exists in usage table
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE faq_id = %s",
        $faq_id
    ));

    if ($existing) {
        // Update existing record
        $new_avg_confidence = (($existing->avg_confidence * $existing->hit_count) + $confidence_score) / ($existing->hit_count + 1);

        $wpdb->update(
            $table_name,
            array(
                'hit_count' => $existing->hit_count + 1,
                'last_asked' => current_time('mysql'),
                'avg_confidence' => $new_avg_confidence
            ),
            array('faq_id' => $faq_id)
        );
    } else {
        // Insert new record
        $wpdb->insert(
            $table_name,
            array(
                'faq_id' => $faq_id,
                'question' => isset($faq_match['question']) ? $faq_match['question'] : '',
                'category' => isset($faq_match['category']) ? $faq_match['category'] : '',
                'hit_count' => 1,
                'last_asked' => current_time('mysql'),
                'first_asked' => current_time('mysql'),
                'avg_confidence' => $confidence_score
            )
        );
    }
}
```

Call this in `chatbot_faq_search()` when a match is found:

```php
// After finding a match with confidence >= 0.2
if ($best_match && $best_score >= 0.2) {
    // Track this FAQ usage
    if (function_exists('chatbot_track_faq_usage')) {
        chatbot_track_faq_usage($best_match, $best_score);
    }

    // Return the match...
}
```

---

## 🤖 AI Analysis - Gap Question Clustering

**File:** `includes/analytics/chatbot-gap-analysis.php` (NEW FILE)

```php
<?php
/**
 * Gap Question Analysis - AI-powered clustering and FAQ suggestions
 * Ver 2.4.3
 */

if (!defined('WPINC')) {
    die();
}

/**
 * Run AI analysis on gap questions to cluster them and generate FAQ suggestions
 * This should be run via WordPress Cron nightly or on-demand
 */
function chatbot_analyze_gap_questions() {
    global $wpdb;

    $gap_table = $wpdb->prefix . 'chatbot_gap_questions';
    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    // Get all unclustered gap questions from the last 30 days
    $gap_questions = $wpdb->get_results(
        "SELECT * FROM $gap_table
         WHERE is_clustered = 0
         AND asked_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY asked_date DESC
         LIMIT 100"
    );

    if (empty($gap_questions)) {
        error_log("CHATBOT: No gap questions to analyze");
        return;
    }

    error_log("CHATBOT: Analyzing " . count($gap_questions) . " gap questions");

    // Prepare questions for AI analysis
    $questions_text = "";
    foreach ($gap_questions as $q) {
        $questions_text .= "- " . $q->question_text . "\n";
    }

    // Call Gemini to cluster questions
    $api_key = esc_attr(get_option('chatbot_gemini_api_key'));
    if (empty($api_key)) {
        error_log("CHATBOT: No Gemini API key configured for gap analysis");
        return;
    }

    $prompt = "Analyze these customer service questions that were NOT answered by our FAQ system.

Questions:
$questions_text

Task:
1. Group similar questions into clusters (max 10 clusters)
2. For each cluster:
   - Give it a clear name (e.g. 'Router Compatibility')
   - Provide a brief description
   - List 3 example questions from that cluster
   - Suggest a priority score (1-10, where 10 = very important)
   - Draft an FAQ entry with Question and Answer

Return JSON format:
{
  \"clusters\": [
    {
      \"name\": \"Cluster Name\",
      \"description\": \"What users are asking about\",
      \"sample_questions\": [\"Q1\", \"Q2\", \"Q3\"],
      \"priority\": 8,
      \"suggested_faq\": {
        \"question\": \"Suggested FAQ question\",
        \"answer\": \"Suggested FAQ answer\",
        \"keywords\": \"suggested keywords\",
        \"category\": \"suggested category\"
      }
    }
  ]
}

Be concise and actionable. Focus on the most common themes.";

    $response = chatbot_call_gemini_for_analysis($api_key, $prompt);

    if (!$response) {
        error_log("CHATBOT: Failed to get AI analysis response");
        return;
    }

    // Parse JSON response
    $analysis = json_decode($response, true);

    if (!$analysis || !isset($analysis['clusters'])) {
        error_log("CHATBOT: Failed to parse AI analysis: " . $response);
        return;
    }

    // Save clusters to database
    foreach ($analysis['clusters'] as $cluster) {
        $wpdb->insert(
            $cluster_table,
            array(
                'cluster_name' => $cluster['name'],
                'cluster_description' => $cluster['description'],
                'question_count' => 0, // Will update this when matching questions
                'sample_questions' => json_encode($cluster['sample_questions']),
                'suggested_faq' => json_encode($cluster['suggested_faq']),
                'suggested_keywords' => $cluster['suggested_faq']['keywords'] ?? '',
                'suggested_category' => $cluster['suggested_faq']['category'] ?? 'General',
                'priority_score' => floatval($cluster['priority']),
                'created_date' => current_time('mysql'),
                'status' => 'new'
            )
        );

        $cluster_id = $wpdb->insert_id;

        // Mark matching gap questions as clustered
        // Simple approach: match questions that contain key words from cluster name
        // TODO: Could use AI to match questions to clusters more accurately

        error_log("CHATBOT: Created cluster #{$cluster_id}: {$cluster['name']} (priority: {$cluster['priority']})");
    }

    // Mark all analyzed questions as clustered (simple approach)
    $question_ids = array_map(function($q) { return $q->id; }, $gap_questions);
    if (!empty($question_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($question_ids), '%d'));
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $gap_table SET is_clustered = 1 WHERE id IN ($ids_placeholder)",
                ...$question_ids
            )
        );
    }

    error_log("CHATBOT: Gap analysis complete - created " . count($analysis['clusters']) . " clusters");
}

/**
 * Call Gemini API for analysis (simplified)
 */
function chatbot_call_gemini_for_analysis($api_key, $prompt) {
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

    $body = json_encode(array(
        'contents' => array(
            array(
                'parts' => array(
                    array('text' => $prompt)
                )
            )
        ),
        'generationConfig' => array(
            'temperature' => 0.3,  // Lower temperature for more consistent JSON
            'topP' => 0.95,
            'maxOutputTokens' => 4096,
        )
    ));

    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => $body,
        'timeout' => 60,
    ));

    if (is_wp_error($response)) {
        error_log("CHATBOT: Gemini API error: " . $response->get_error_message());
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response));

    if (isset($response_body->candidates[0]->content->parts[0]->text)) {
        $text = $response_body->candidates[0]->content->parts[0]->text;

        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }

        return $text;
    }

    return false;
}

// Schedule nightly analysis via WordPress Cron
add_action('chatbot_gap_analysis_cron', 'chatbot_analyze_gap_questions');

// Register cron job on plugin activation (add to main plugin file)
register_activation_hook(__FILE__, 'chatbot_schedule_gap_analysis');
function chatbot_schedule_gap_analysis() {
    if (!wp_next_scheduled('chatbot_gap_analysis_cron')) {
        wp_schedule_event(time(), 'daily', 'chatbot_gap_analysis_cron');
    }
}
```

---

## 📊 Dashboard UI

**File:** `includes/settings/chatbot-settings-reporting.php`

Add new section to the reporting page (after line 179):

```php
// Gap Analysis Section
function chatbot_render_gap_analysis_section() {
    global $wpdb;

    $cluster_table = $wpdb->prefix . 'chatbot_gap_clusters';

    // Get clusters ordered by priority
    $clusters = $wpdb->get_results(
        "SELECT * FROM $cluster_table
         WHERE status = 'new'
         ORDER BY priority_score DESC
         LIMIT 10"
    );

    ?>
    <div class="chatbot-gap-analysis-section">
        <h2>💡 Suggested FAQ Additions</h2>
        <p>These are questions users frequently ask that don't have FAQ answers:</p>

        <?php if (empty($clusters)): ?>
            <p><em>No gap questions detected yet. Check back after more conversations.</em></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Sample Questions</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clusters as $cluster):
                        $sample_questions = json_decode($cluster->sample_questions, true);
                        $suggested_faq = json_decode($cluster->suggested_faq, true);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($cluster->cluster_name); ?></strong><br>
                            <small><?php echo esc_html($cluster->cluster_description); ?></small>
                        </td>
                        <td>
                            <ul style="margin: 0; padding-left: 20px;">
                                <?php foreach (array_slice($sample_questions, 0, 3) as $q): ?>
                                    <li><small><?php echo esc_html($q); ?></small></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <span class="priority-badge priority-<?php echo ($cluster->priority_score >= 7) ? 'high' : (($cluster->priority_score >= 4) ? 'medium' : 'low'); ?>">
                                <?php echo esc_html($cluster->priority_score); ?>/10
                            </span>
                        </td>
                        <td>
                            <button class="button button-primary" onclick="viewSuggestedFAQ(<?php echo $cluster->id; ?>)">
                                View Suggested FAQ
                            </button>
                            <button class="button" onclick="dismissCluster(<?php echo $cluster->id; ?>)">
                                Dismiss
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p>
            <button class="button button-secondary" onclick="runGapAnalysis()">
                🤖 Run AI Analysis Now
            </button>
            <small>Analyzes recent gap questions and generates suggestions</small>
        </p>
    </div>

    <style>
        .priority-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .priority-high {
            background-color: #dc3545;
            color: white;
        }
        .priority-medium {
            background-color: #ffc107;
            color: black;
        }
        .priority-low {
            background-color: #28a745;
            color: white;
        }
    </style>

    <script>
    function viewSuggestedFAQ(clusterId) {
        // TODO: Open modal with suggested FAQ details
        alert('Opening FAQ suggestion for cluster #' + clusterId);
    }

    function dismissCluster(clusterId) {
        if (confirm('Dismiss this suggestion?')) {
            // TODO: AJAX call to mark cluster as dismissed
            alert('Dismissed cluster #' + clusterId);
        }
    }

    function runGapAnalysis() {
        if (confirm('Run AI analysis now? This will cost approximately $0.05 in API calls.')) {
            // TODO: AJAX call to trigger gap analysis
            alert('Starting gap analysis...');
        }
    }
    </script>
    <?php
}
```

---

## 🔄 Next Steps

1. Create database tables (run SQL)
2. Implement gap question logging
3. Implement FAQ usage tracking
4. Build AI analysis function
5. Create dashboard UI
6. Test with real data
7. Refine AI prompts based on results

---

## 💰 Cost Estimate

**Per Analysis Run (100 questions):**
- Input tokens: ~500 tokens (questions list)
- Output tokens: ~2000 tokens (JSON clusters + FAQ suggestions)
- Total: ~2500 tokens = ~$0.01 per analysis

**Monthly Cost (running nightly):**
- ~30 analyses/month × $0.01 = **$0.30/month**

**Very affordable!** 🎉

---

## 🎯 Success Metrics

After implementing, track:
- Number of gap questions detected per week
- Number of FAQ suggestions generated
- Number of suggestions actually added to FAQ
- Reduction in gap questions after FAQ added
- Client satisfaction with auto-suggestions

---

Ready to start implementation? Let me know!
