# Database Schema Documentation

## Overview

The chatbot plugin uses **Supabase** (PostgreSQL + pgvector) as its database.

---

## Vector Usage Summary

| Table | Uses Vectors? | Purpose |
|-------|---------------|---------|
| `chatbot_conversations` | No | Simple text storage |
| `chatbot_interactions` | No | Daily counts only |
| `chatbot_gap_questions` | **Yes** | Semantic clustering of similar questions |
| `chatbot_gap_clusters` | No | Cluster metadata |
| `chatbot_faq_usage` | No | Hit counts only |
| `chatbot_assistants` | No | Config storage |
| `chatbot_faqs` | **Yes** | Semantic FAQ search (main vector table) |

---

## Supabase Tables

### 1. `chatbot_conversations`
**Purpose:** Stores all chatbot conversation messages.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key, auto-increment |
| `session_id` | VARCHAR | Unique session identifier (e.g., `kognetiks_1234567890`) |
| `user_id` | VARCHAR | WordPress user ID (0 for anonymous) |
| `page_id` | VARCHAR | WordPress page ID where chat occurred |
| `interaction_time` | TIMESTAMP | When the message was sent |
| `user_type` | ENUM | `Visitor`, `Chatbot`, `Prompt Tokens`, `Completion Tokens`, `Total Tokens` |
| `thread_id` | VARCHAR | OpenAI thread ID for assistants |
| `assistant_id` | VARCHAR | OpenAI assistant ID used |
| `assistant_name` | VARCHAR | Display name of the assistant |
| `message_text` | TEXT | The actual message content |
| `sentiment_score` | FLOAT | Sentiment analysis score (-1 to 1) |
| `created_at` | TIMESTAMP | Record creation time |

**Used by:** Conversation logging, history display, analytics, reporting

---

### 2. `chatbot_interactions`
**Purpose:** Tracks daily interaction counts for reporting.

| Column | Type | Description |
|--------|------|-------------|
| `date` | DATE | Primary key - the date |
| `count` | INT | Number of interactions that day |

**Used by:** Dashboard widget, interaction reports, charts

---

### 3. `chatbot_gap_questions`
**Purpose:** Stores questions the chatbot couldn't answer well (low confidence).

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `question_text` | TEXT | The question asked |
| `session_id` | VARCHAR | Session where question was asked |
| `user_id` | BIGINT | User who asked |
| `page_id` | BIGINT | Page where asked |
| `faq_confidence` | FLOAT | Confidence score (0-1) |
| `faq_match_id` | VARCHAR | Matched FAQ ID (if any) |
| `asked_date` | DATETIME | When question was asked |
| `is_clustered` | BOOLEAN | Whether grouped into a cluster |
| `cluster_id` | INT | Associated cluster ID |
| `is_resolved` | BOOLEAN | Whether FAQ was created for this |
| `conversation_context` | TEXT | Previous Q&A context for follow-up questions (Ver 2.5.0) |
| `quality_score` | FLOAT | Question quality score (Ver 2.4.8) |
| `validation_flags` | JSONB | Validation flags (Ver 2.4.8) |
| `embedding` | vector(1536) | Question embedding for clustering |

**Used by:** Gap analysis, FAQ improvement suggestions

**Note:** The `conversation_context` column (added Ver 2.5.0) stores the previous Q&A when a follow-up question is logged. This helps you understand what the user was asking about. Example:
- Question: "Can you name them?"
- Context: "Previous Q: What carriers do you partner with? | Previous A: We partner with T-Mobile, AT&T..."

---

### 4. `chatbot_gap_clusters`
**Purpose:** Groups similar gap questions for FAQ suggestions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `cluster_name` | VARCHAR | Short name for the cluster |
| `cluster_description` | TEXT | What users are asking about |
| `question_count` | INT | Number of questions in cluster |
| `sample_questions` | JSON | Example questions |
| `suggested_faq` | JSON | AI-suggested FAQ content |
| `action_type` | ENUM | `create` or `improve` |
| `existing_faq_id` | VARCHAR | FAQ to improve (if action=improve) |
| `suggested_keywords` | JSON | Keywords to add |
| `priority_score` | FLOAT | Importance ranking |
| `status` | ENUM | `new`, `reviewed`, `faq_created`, `dismissed` |
| `created_at` | DATETIME | When cluster was created |
| `updated_at` | DATETIME | Last update time |

**Used by:** Gap analysis dashboard, FAQ improvement workflow

---

### 5. `chatbot_faq_usage`
**Purpose:** Tracks how often each FAQ is matched.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `faq_id` | VARCHAR | FAQ identifier (unique) |
| `hit_count` | INT | Times this FAQ was matched |
| `last_asked` | DATETIME | Last time FAQ was used |
| `avg_confidence` | FLOAT | Average match confidence |
| `created_at` | DATETIME | First usage time |
| `updated_at` | DATETIME | Last update time |

**Used by:** FAQ analytics, identifying popular/unused FAQs

---

### 6. `chatbot_assistants`
**Purpose:** Stores OpenAI assistant configurations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `assistant_id` | VARCHAR | OpenAI assistant ID |
| `assistant_name` | VARCHAR | Display name |
| `assistant_alias` | VARCHAR | Short alias for URLs |
| `settings` | JSON | Configuration settings |
| `created_at` | DATETIME | Creation time |

**Used by:** Assistant management, multi-assistant support

---

## Data Flow

```
User Message
    │
    ▼
chatbot_conversations (logged)
    │
    ├──► chatbot_interactions (daily count updated)
    │
    ├──► Sentiment Analysis (score saved)
    │
    └──► If low confidence:
            │
            ▼
         chatbot_gap_questions
            │
            ▼ (AI clustering)
         chatbot_gap_clusters
            │
            ▼ (FAQ created)
         chatbot_faq_usage (tracked)
```

---

## Key Functions

| Function | Table | Purpose |
|----------|-------|---------|
| `chatbot_supabase_log_conversation()` | conversations | Save new message |
| `chatbot_supabase_get_recent_conversations()` | conversations | Get messages by date |
| `chatbot_supabase_get_user_conversations()` | conversations | Get user's history |
| `chatbot_supabase_get_interaction_counts()` | interactions | Get daily counts |
| `chatbot_supabase_get_gap_questions()` | gap_questions | Get unanswered questions |
| `chatbot_supabase_delete_old_conversations()` | conversations | Cleanup old data |

---

### 7. `chatbot_faqs` (Vector Table)
**Purpose:** Stores FAQs with vector embeddings for semantic search.

| Column | Type | Description |
|--------|------|-------------|
| `id` | SERIAL | Primary key |
| `faq_id` | VARCHAR | Unique FAQ identifier |
| `question` | TEXT | The FAQ question |
| `answer` | TEXT | The FAQ answer |
| `category` | VARCHAR | FAQ category |
| `keywords` | TEXT | Search keywords |
| `question_embedding` | vector(1536) | **Vector** - Question embedding |
| `answer_embedding` | vector(1536) | **Vector** - Answer embedding |
| `combined_embedding` | vector(1536) | **Vector** - Combined Q+A embedding |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |

**Used by:** Semantic FAQ search, finding similar questions

---

## Vector Details

### What is pgvector?
PostgreSQL extension that enables storing and searching vector embeddings. Supabase includes pgvector by default.

### Embedding Model
- **Model:** OpenAI `text-embedding-3-small`
- **Dimensions:** 1536
- **Index Type:** IVFFlat (cosine similarity)

### Tables Using Vectors

**1. `chatbot_faqs`** - Main vector table
- 3 embedding columns: question, answer, combined
- Used for semantic FAQ matching
- Index: `idx_faqs_combined_embedding`

**2. `chatbot_gap_questions`** - Optional vectors
- 1 embedding column for clustering similar questions
- Index: `idx_gap_questions_embedding`

### How Vector Search Works

```
User Question: "How do I reset my password?"
         │
         ▼
    Generate Embedding (1536 floats)
         │
         ▼
    Cosine Similarity Search in chatbot_faqs
         │
         ▼
    Return top matches with confidence scores
```

---

## Notes

- All timestamps use UTC (via `gmdate('c')`)
- Session IDs format: `kognetiks_` + timestamp
- Sentiment scores: -1 (negative) to +1 (positive), 0 = neutral
- Confidence scores: 0 (no match) to 1 (perfect match)
- Vector dimensions: 1536 (OpenAI text-embedding-3-small)
