=== Steven-Bot ===
Contributors: kelveloper
Tags: chatbot, faq, ai, gemini, openai, supabase, vector search
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

AI-powered FAQ chatbot with semantic search. Matches customer questions to your knowledge base using vector similarity, with AI fallback.

== Description ==

**Steven-Bot** is an AI-powered FAQ chatbot for WordPress that uses semantic vector search to provide accurate, instant answers to customer questions.

**How It Works:**

1. **You add FAQs** - Create a knowledge base of questions and answers about your business
2. **Vector embeddings** - Each FAQ is converted to a vector embedding for semantic matching
3. **Customer asks question** - The chatbot finds the most similar FAQ using Supabase pgvector
4. **Instant answer** - Returns your FAQ answer directly (no AI generation needed)
5. **AI fallback** - If no match found, falls back to Gemini/OpenAI for a response

**Key Features:**

* **Semantic Search** - Questions don't need to match exactly. "What time do you open?" matches "What are your hours?"
* **Cost Effective** - FAQ matches are nearly free; AI is only called when needed
* **Knowledge Base** - Manage FAQs with categories directly in WordPress
* **Gap Analysis** - See what questions aren't being answered and get AI-suggested FAQs
* **Floating or Embedded** - Choose chatbot style that fits your site
* **Analytics Dashboard** - Track conversations and identify gaps in your knowledge base

**Requires:**

* Google Gemini API key (for embeddings and fallback)
* Supabase project (for vector database)

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`
2. Activate the plugin
3. Go to **Steven-Bot > Setup**
4. Enter your Gemini API key
5. Enter your Supabase credentials
6. Go to **Knowledge Base** and add your FAQs
7. Add `[steven_bot]` shortcode to any page

== Frequently Asked Questions ==

= What is Supabase? =
Supabase is a cloud database service that provides PostgreSQL with pgvector extension for semantic search.

= Do I need both Gemini and OpenAI? =
No, you only need one. Gemini is recommended as it handles both embeddings and fallback responses.

= How much does it cost? =
Supabase has a free tier. Gemini has generous free limits. Most small business usage stays within free tiers.

= Can I import FAQs from a spreadsheet? =
Yes! Export as CSV with columns: question, answer, category and use the Import feature.

== Changelog ==

= 2.5.0 =
* Full rebrand to Steven-Bot with new plugin slug
* Automatic migration from previous chatbot_chatgpt settings
* New shortcode: [steven_bot] (legacy [chatbot_chatgpt] still works)
* All function prefixes updated to steven_bot_
* Database tables migrated automatically on upgrade
* Production-ready for WordPress Plugin Directory

= 2.4.6 =
* Rebranded to Steven-Bot
* Simplified admin UI (4 tabs: Setup, Knowledge Base, Analytics, Support)
* Improved API status display with quota warnings
* Better error handling when AI rate limit exceeded

== Screenshots ==

1. Floating chatbot on website
2. Embedded chatbot style
3. Knowledge Base management
4. Gap Analysis dashboard
5. Setup page with API testing

== Upgrade Notice ==

= 2.5.0 =
Major rebrand release. All settings will be automatically migrated. Legacy shortcodes still work.

= 2.4.6 =
Rebranded version with simplified UI and improved error handling.
