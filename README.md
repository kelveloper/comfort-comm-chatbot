# Steven-Bot

**Steven-Bot** is an AI-powered FAQ chatbot plugin for WordPress that uses semantic vector search to provide accurate, instant answers to customer questions.

## How It Works

1. **You add FAQs** - Create a knowledge base of questions and answers about your business
2. **Vector embeddings** - Each FAQ is converted to a vector embedding using Google Gemini
3. **Customer asks question** - The chatbot finds the most similar FAQ using Supabase pgvector
4. **Instant answer** - Returns your FAQ answer directly (no AI generation needed)
5. **AI fallback** - If no match found, falls back to Gemini/OpenAI for a response

## Key Features

* **Semantic Search** - Questions don't need to match exactly. "What time do you open?" matches "What are your hours?"
* **Cost Effective** - FAQ matches are nearly free; AI is only called when needed
* **Knowledge Base** - Manage FAQs with categories directly in WordPress admin
* **Gap Analysis** - See what questions aren't being answered and get AI-suggested FAQs
* **Floating or Embedded** - Choose chatbot style that fits your site
* **Analytics Dashboard** - Track conversations and identify gaps in your knowledge base

## Requirements

* WordPress 5.0+
* PHP 7.4+
* Google Gemini API key (for embeddings and AI fallback)
* Supabase project (for vector database with pgvector)

## Installation

1. Upload the plugin to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Go to **Steven-Bot > Setup**
4. Enter your Gemini API key
5. Enter your Supabase credentials (URL and anon key)
6. Go to **Knowledge Base** and add your FAQs
7. Add `[steven_bot]` shortcode to any page

## External Services

This plugin uses external AI services for embeddings and fallback responses:

- **Google Gemini**: [Terms of Service](https://ai.google.dev/terms) | [Privacy Policy](https://policies.google.com/privacy)
- **OpenAI** (optional): [Terms of Use](https://platform.openai.com/terms) | [Privacy Policy](https://openai.com/policies/privacy-policy/)
- **Supabase**: [Terms of Service](https://supabase.com/terms) | [Privacy Policy](https://supabase.com/privacy)

### Get API Keys

- [Google AI Studio (Gemini)](https://aistudio.google.com/app/apikey)
- [OpenAI API Keys](https://platform.openai.com/account/api-keys) (optional)
- [Supabase Dashboard](https://supabase.com/dashboard)

## Usage

### Shortcode

Add the chatbot to any page or post:

```
[steven_bot]
```

Options:
- `style="floating"` - Floating chatbot button (default)
- `style="embedded"` - Embedded inline chatbot

### Admin Pages

- **Setup** - Configure API keys and test connections
- **Knowledge Base** - Add, edit, and import FAQs
- **Analytics & Feedback** - View conversations and gap analysis
- **Support** - Documentation and help

## API Key Safety

Your API keys are stored encrypted in the WordPress database. Keep them secure:

- Never share API keys publicly
- Don't commit keys to Git repositories
- Monitor usage in provider dashboards
- Set spending limits to prevent unexpected charges

## Frequently Asked Questions

**What is Supabase?**
Supabase is a cloud database service that provides PostgreSQL with pgvector extension for semantic search.

**Do I need both Gemini and OpenAI?**
No, you only need one. Gemini is recommended as it handles both embeddings and fallback responses.

**How much does it cost?**
Supabase has a free tier. Gemini has generous free limits. Most small business usage stays within free tiers.

**Can I import FAQs from a spreadsheet?**
Yes! Export as CSV with columns: question, answer, category and use the Import feature.

## License

- License: GPLv3 or later
- License URI: https://www.gnu.org/licenses/gpl-3.0.html

## Author

Created by [kelveloper](https://github.com/kelveloper)

## Repository

[https://github.com/kelveloper/comfort-comm-chatbot](https://github.com/kelveloper/comfort-comm-chatbot)
