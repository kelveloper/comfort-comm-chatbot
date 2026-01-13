# Steven-Bot - AI Chatbot Plugin

## Overview

This is a custom WordPress plugin designed for Comfort Communication Inc. It provides an AI-powered chatbot with semantic search to handle customer inquiries and improve engagement directly on the website.

## Tech Stack

- **Platform**: WordPress (PHP 7.4+)
- **Frontend**: JavaScript / CSS (Custom Chat Interface)
- **AI Engine**: Multi-provider support (OpenAI, Gemini, Anthropic, Azure, Mistral)
- **Database**: Supabase (PostgreSQL with pgvector for semantic search)

## How It Works

1. **Knowledge Base** - Add FAQs with questions and answers
2. **Vector Embeddings** - Each FAQ is converted to embeddings for semantic matching
3. **Customer Asks** - The chatbot finds the most similar FAQ using vector search
4. **Instant Answer** - Returns matching FAQ (no AI generation needed)
5. **AI Fallback** - If no match, falls back to AI for a response

## How to Install

1. Download the repository as a `.zip` file.
2. Log in to the WordPress Admin Dashboard.
3. Go to **Plugins > Add New > Upload Plugin**.
4. Select the zip file and click **Install Now**.
5. Click **Activate**.

## Configuration

1. Go to **Steven-Bot > Setup** in the WordPress admin.
2. Enter your AI API Key (Gemini or OpenAI recommended).
3. Enter your Supabase URL and Anon Key.
4. Go to **Steven-Bot > Knowledge Base** to add FAQs.
5. Add `[steven_bot]` shortcode to any page.

## Shortcode Options

```
[steven_bot]                    // Default floating chatbot
[steven_bot style="embedded"]   // Inline embedded chatbot
```

## Admin Pages

| Menu | Description |
|------|-------------|
| **Setup** | Configure API keys and Supabase connection |
| **Knowledge Base** | Add, edit, import FAQs |
| **Analytics & Feedback** | View conversations and gap analysis |

## Running Tests

```bash
php tests/run-tests.php
php tests/run-tests.php --test=vector-search --verbose
```

## Development

- **Main Branch**: `main` (Production ready)
- **Version**: 2.5.11

## License

Proprietary - Comfort Communication Inc.
