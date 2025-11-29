# Vector Search Setup Guide

## What This Does
Uses Supabase (PostgreSQL + pgvector) for semantic FAQ search. Users can ask questions in their own words and get matched to FAQs by meaning, not just keywords.

**All FAQs are stored in Supabase** - no local JSON files. Add/Edit/Delete FAQs directly from WordPress admin.

## Supabase Project Info
- **Project URL**: https://tlpvjrbmxxggubnjmdhe.supabase.co
- **Database Host**: db.tlpvjrbmxxggubnjmdhe.supabase.co
- **Database Password**: password1234

## Add to Client's wp-config.php

```php
/**
 * Vector Search Configuration (Supabase)
 */
define( 'CHATBOT_PG_HOST', 'db.tlpvjrbmxxggubnjmdhe.supabase.co' );
define( 'CHATBOT_SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRscHZqcmJteHhnZ3VibmptZGhlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQxOTQ1NjYsImV4cCI6MjA3OTc3MDU2Nn0.Ktgnc6lHiPbsl1NrreLU6hthxO2hX5dF0Ol9MOpslnI' );
```

## Managing FAQs

### From WordPress Admin
Go to **Chatbot Settings > Knowledge Navigator > FAQ Import** to:
- View all FAQs from the vector database
- Add new FAQs (auto-generates embedding)
- Edit existing FAQs (regenerates embedding)
- Delete FAQs

### How It Works
1. Admin adds/edits FAQ in WordPress
2. Gemini API generates embedding (768 dimensions, padded to 1536)
3. FAQ + embedding saved to Supabase via REST API
4. When user asks question, Supabase finds best match using cosine similarity

## Confidence Levels
- **very_high**: 85%+ match - Return FAQ directly
- **high**: 75-85% match - High confidence
- **medium**: 65-75% match - Medium confidence
- **low**: 50-65% match - Low confidence
- **min threshold**: 40%

## Database Table: chatbot_faqs
| Column | Type | Description |
|--------|------|-------------|
| id | serial | Auto-increment ID |
| faq_id | varchar(50) | Unique FAQ identifier |
| question | text | The FAQ question |
| answer | text | The FAQ answer |
| category | varchar(255) | Category for filtering |
| combined_embedding | vector(1536) | Embedding for search |
| created_at | timestamp | When created |
| updated_at | timestamp | When last updated |

## Files
- `includes/vector-search/chatbot-vector-schema.php` - Database connection (PDO + REST API)
- `includes/vector-search/chatbot-vector-search.php` - Search functions
- `includes/vector-search/chatbot-vector-faq-crud.php` - FAQ CRUD via Supabase REST API
- `includes/vector-search/chatbot-vector-migration.php` - Embedding generation
- `includes/vector-search/chatbot-vector-loader.php` - Main loader

## Notes
- Uses Gemini API for embeddings (same key as chatbot)
- Works on any hosting - uses REST API, no pdo_pgsql extension needed
- All CRUD operations go directly to Supabase (no local JSON backup)
- 66 FAQs currently in database
