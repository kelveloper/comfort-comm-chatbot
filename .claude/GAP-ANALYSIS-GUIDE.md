# Gap Analysis System - Technical Guide

## Overview

The Gap Analysis system identifies questions that users ask but the chatbot can't answer well (confidence < 60%). It uses AI to analyze these "gap questions," group similar ones into clusters, and suggest FAQ improvements or new FAQs.

---

## How It Works (Flow Diagram)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           GAP ANALYSIS FLOW                                  │
└─────────────────────────────────────────────────────────────────────────────┘

  USER ASKS QUESTION
         │
         ▼
  ┌──────────────┐
  │   Chatbot    │
  │  Responds    │
  └──────────────┘
         │
         ▼
  ┌──────────────────────┐     YES     ┌─────────────────┐
  │  Confidence >= 60%?  │────────────▶│  Normal Response │
  └──────────────────────┘             └─────────────────┘
         │ NO
         ▼
  ┌──────────────────────┐
  │  Log as Gap Question │  ◀── Stored in Supabase: chatbot_gap_questions
  │  (is_clustered=false)│
  └──────────────────────┘
         │
         │ (When 30+ questions accumulate OR manual trigger)
         ▼
  ┌──────────────────────┐
  │   AI Clustering      │  ◀── Gemini 2.5 Flash analyzes questions
  │   (Batch of 30)      │      Groups similar questions together
  └──────────────────────┘
         │
         ▼
  ┌──────────────────────┐
  │  For Each Cluster:   │
  │  - "create" new FAQ  │  ◀── AI decides based on existing FAQ database
  │  - "improve" FAQ     │
  └──────────────────────┘
         │
         ▼
  ┌──────────────────────┐
  │  Save to Supabase    │  ◀── chatbot_gap_clusters table
  │  Mark questions as   │
  │  is_clustered=true   │
  └──────────────────────┘
         │
         ▼
  ┌──────────────────────┐
  │  Admin Dashboard     │  ◀── Human reviews AI suggestions
  │  - Review clusters   │
  │  - Edit if needed    │
  │  - Apply or Dismiss  │
  └──────────────────────┘
         │
         ▼
  ┌──────────────────────┐
  │  FAQ Updated/Created │  ◀── Knowledge base improved!
  └──────────────────────┘
```

---

## Database Tables (Supabase)

### `chatbot_gap_questions`
Stores individual questions that weren't answered confidently.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `question_text` | TEXT | The user's question |
| `confidence_score` | FLOAT | How confident the chatbot was (0-1) |
| `matched_faq_id` | VARCHAR | Which FAQ it tried to match (if any) |
| `conversation_context` | TEXT | Previous Q&A pairs for follow-up questions |
| `is_clustered` | BOOLEAN | Has this been processed by AI? |
| `is_resolved` | BOOLEAN | Has this been addressed? |
| `cluster_id` | INT | Which cluster it belongs to |
| `asked_date` | TIMESTAMP | When the question was asked |

### `chatbot_gap_clusters`
Stores AI-generated clusters of similar questions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `cluster_name` | VARCHAR | AI-generated name for the cluster |
| `cluster_description` | TEXT | What users are asking about |
| `question_count` | INT | How many questions in this cluster |
| `sample_questions` | JSONB | Array of example questions |
| `sample_contexts` | JSONB | Conversation context for follow-ups |
| `action_type` | ENUM | "create" or "improve" |
| `existing_faq_id` | VARCHAR | FAQ to improve (if action_type=improve) |
| `suggested_faq` | JSONB | For "create": new FAQ details |
| `suggested_answer` | TEXT | For "improve": better answer |
| `priority_score` | FLOAT | Higher = more important |
| `status` | ENUM | "new", "reviewed", "faq_created", "dismissed" |

---

## Trigger Methods

### 1. Manual Trigger (Admin Dashboard)
- Click "Run Analysis Now" button
- Processes **30 questions** (single batch)
- Good for testing and on-demand analysis
- No minimum question requirement

### 2. Automatic Trigger
- Toggle "Auto-analysis" ON in dashboard
- Triggers when **30+ questions** accumulate
- Runs on frontend page loads (`wp_footer` hook)
- **1-hour cooldown** between runs
- Processes **all questions** in batches (up to 300)

---

## Batch Processing (Scalability)

```
┌─────────────────────────────────────────────────────────────┐
│                    BATCH PROCESSING                          │
└─────────────────────────────────────────────────────────────┘

Configuration:
- batch_size = 30 questions per AI call
- max_batches = 10 (auto) or 1 (manual)
- delay_between_batches = 2 seconds
- max questions per run = 300 (auto) or 30 (manual)

Example with 98 questions (auto mode):
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│ Batch 1 │───▶│ Batch 2 │───▶│ Batch 3 │───▶│ Batch 4 │
│   30    │    │   30    │    │   30    │    │    8    │
└─────────┘    └─────────┘    └─────────┘    └─────────┘
     │              │              │              │
     └──────────────┴──────────────┴──────────────┘
                         │
                         ▼
              Total: 98 questions processed
              ~10-15 clusters created
```

---

## AI Prompt Structure

The AI (Gemini 2.5 Flash) receives:

1. **Existing FAQ Database** - All current FAQs with ID, question, answer, category
2. **Gap Questions** - List of questions that weren't answered well
3. **Instructions** - How to cluster and decide "create" vs "improve"

### AI Decision Logic:

```
For each cluster of similar questions:

IF an existing FAQ covers this topic BUT answer is incomplete:
   → action_type = "improve"
   → Provide suggested_answer (genuinely better, not a copy!)

ELSE IF no existing FAQ covers this topic:
   → action_type = "create"
   → Provide new question, answer, and category
```

---

## Admin Dashboard Actions

### For "Create New FAQ" Clusters:
1. Review the suggested question/answer/category
2. Edit if needed (fields are editable)
3. Click "Add to Knowledge Base"
4. FAQ is created, cluster marked as resolved

### For "Improve Existing FAQ" Clusters:
1. See current answer vs AI suggested answer side-by-side
2. Edit the suggested answer if needed
3. Click "Apply Improvement"
4. Existing FAQ is updated, cluster marked as resolved

### Dismiss:
- If suggestion isn't useful, click "Dismiss"
- Cluster is hidden from dashboard
- Questions remain for future analysis

---

## Key Files

| File | Purpose |
|------|---------|
| `includes/utilities/chatbot-gap-analysis.php` | Core analysis logic, AI prompts, batch processing |
| `includes/supabase/chatbot-supabase-db.php` | Database functions for gap questions/clusters |
| `includes/settings/chatbot-settings-reporting.php` | Admin dashboard UI and AJAX handlers |
| `includes/settings/chatbot-settings-analytics-new.php` | Analytics page with Gap Analysis section |
| `includes/vector-search/chatbot-vector-search.php` | Logs gap questions when confidence < 60% |

---

## Configuration Options

### In Code (`chatbot-gap-analysis.php`):

```php
$batch_size = 30;           // Questions per AI call
$max_batches = 10;          // Max batches per run (auto mode)
$delay_between_batches = 2; // Seconds between API calls
$min_questions_to_run = 30; // Minimum for auto-trigger
```

### In AI Prompt:
```
- Cluster minimum: 2+ questions (production)
- Cluster minimum: 1 question (testing mode)
```

### Admin Settings:
- Auto-analysis toggle (ON/OFF)
- Stored in: `chatbot_gap_auto_analysis_enabled` option

---

## Conversation Context (Follow-up Questions)

When a user asks a follow-up question that becomes a gap question, the system stores the previous conversation context:

```
Example:
Q1: "What internet plans do you offer?"
A1: "We offer plans from Spectrum, Verizon..."
Q2: "What about fiber?" ◀── This becomes a gap question

Stored context: "Q1: What internet plans... A1: We offer plans..."
```

This helps the admin understand WHY the question was asked and what context was missing from the chatbot's knowledge.

---

## Performance Optimizations (Ver 2.5.0)

1. **Count Queries** - Uses `COUNT` instead of loading all data for dashboard stats
2. **Limited Samples** - Only fetches 50 questions for "top gaps" display
3. **Cluster Limit** - Dashboard shows max 10 clusters at a time
4. **Batch Processing** - Processes in chunks to avoid API timeouts
5. **Rate Limiting** - 2-second delay between batches to avoid rate limits

---

## Troubleshooting

### "No clusters generated"
- Check if questions can be grouped (need 2+ similar questions)
- Check Gemini API key is valid
- Check debug.log for API errors

### "MAX_TOKENS error"
- Reduce batch_size from 30 to 20
- Or increase maxOutputTokens in API call (currently 16384)

### "Questions not being marked as clustered"
- Check if cluster was created successfully
- Verify `chatbot_supabase_mark_questions_clustered()` is called
- Check Supabase API permissions

### "Dashboard not showing clusters"
- Hard refresh browser (Cmd+Shift+R)
- Check cluster status is "new" or "reviewed"
- Verify `chatbot_supabase_get_gap_clusters()` returns data

---

## Debug Logging

All gap analysis operations are logged with prefix `[Chatbot Gap Analysis]`:

```
[Chatbot Gap Analysis] ========== STARTING ==========
[Chatbot Gap Analysis] Manual run - mode: single batch (30 max)
[Chatbot Gap Analysis] Batch 1: Fetched 30 questions in 0.45s
[Chatbot Gap Analysis] Batch 1: Sending to AI for clustering...
[Chatbot Gap Analysis] Batch 1: AI clustering completed in 37.33s
[Chatbot Gap Analysis] Batch 1: AI generated 3 clusters
[Chatbot Gap Analysis] ========== FINISHED in 41.2s ==========
[Chatbot Gap Analysis] Summary: 1 batches, 30 questions -> 3 clusters
```

View logs in: `wp-content/debug.log` (when WP_DEBUG_LOG is enabled)

---

## Data Retention & Automatic Cleanup (Ver 2.5.0)

The plugin automatically cleans up old data to prevent database bloat. Runs daily at 3 AM via WordPress cron.

### Retention Policies

| Table | Default Retention | What Gets Deleted |
|-------|-------------------|-------------------|
| `chatbot_conversations` | 90 days | All messages older than X days |
| `chatbot_interactions` | 365 days | Daily counts older than X days |
| `chatbot_gap_questions` | 30 days | Only **clustered** questions older than X days |
| `chatbot_gap_clusters` | 90 days | Only **resolved** clusters (faq_created/dismissed) |
| `chatbot_faqs` | **Never** | Knowledge base - never auto-deleted |
| `chatbot_faq_usage` | **Never** | Usage stats - never auto-deleted |
| `chatbot_assistants` | **Never** | Config - never auto-deleted |

### Admin Settings

Go to **Chatbot Settings > Reporting** → **Data Retention & Cleanup** section to:
- View current record counts for all tables
- Adjust retention periods per table
- Run manual cleanup immediately

### Key Files

| File | Purpose |
|------|---------|
| `includes/utilities/chatbot-db-management.php` | Core cleanup functions |
| `includes/settings/chatbot-settings-reporting.php` | Admin UI for retention settings |

### Manual Cleanup

```php
// Run all cleanup tasks
chatbot_run_all_data_cleanup();

// Or individual tables
chatbot_cleanup_old_conversations(90);    // Delete conversations > 90 days
chatbot_cleanup_old_gap_questions(30);    // Delete clustered gaps > 30 days
chatbot_cleanup_old_gap_clusters(90);     // Delete resolved clusters > 90 days
```

---

## Future Improvements

- [ ] Smart FAQ filtering (only send relevant FAQs to AI based on question topics)
- [ ] Email notifications when new clusters are ready for review
- [x] ~~Scheduled analysis (daily/weekly cron instead of on-demand)~~ ✅ Auto-analysis when 30+ questions
- [ ] Bulk actions (approve/dismiss multiple clusters at once)
- [ ] Analytics on FAQ improvement effectiveness
- [x] ~~Automatic data cleanup~~ ✅ Daily cron cleanup with configurable retention
