# Implementation Roadmap

## Feature: FAQ CSV Import with Natural Language Responses

**Version:** 2.3.7
**Date:** 2024-11-21

---

## Non-Technical Summary

### What We Built
A system that lets you upload your FAQs from a spreadsheet (CSV), and the chatbot will answer customer questions using those FAQs in a natural, friendly way.

### How It Works
1. You create a spreadsheet with questions and answers
2. Upload it to the chatbot settings
3. When a customer asks a matching question, the bot finds your answer and rephrases it naturally

### Why This Matters
- **Faster responses** - Common questions are answered instantly
- **Consistent answers** - Everyone gets the same accurate info
- **Easy to update** - Just upload a new spreadsheet
- **Still sounds human** - AI rephrases answers conversationally

---

## Technical Summary

### Architecture
```
User Question → FAQ Search (keyword matching) →
  If match: Inject FAQ into Gemini prompt → Natural response
  If no match: Standard Gemini API call
```

### Components Built

| Component | File | Purpose |
|-----------|------|---------|
| FAQ Import Logic | `includes/knowledge-navigator/chatbot-kn-faq-import.php` | CSV parsing, JSON storage, keyword search |
| Admin UI | `includes/settings/chatbot-settings-registration-kn.php` | Upload form in Knowledge Navigator tab |
| API Integration | `includes/chatbot-call-gemini-api.php` | FAQ context injection into Gemini prompt |
| Data Storage | `data/faqs.json` | JSON file (no database required) |

### Key Functions

- `chatbot_faq_search($query)` - Keyword-based FAQ matching (30% threshold)
- `chatbot_faq_import_csv($file)` - Parse CSV and save to JSON
- `chatbot_faq_load()` / `chatbot_faq_save()` - JSON file I/O

### Data Flow

1. **Import:** CSV → Parse → Generate keywords → Save to `data/faqs.json`
2. **Query:** User message → Extract keywords → Match against FAQ keywords → Score matches
3. **Response:** FAQ match → Inject into Gemini context → AI rephrases naturally

### Storage Format
```json
{
  "id": "unique_id",
  "question": "What are your store hours?",
  "answer": "Mon-Fri 9am-6pm, Sat 10am-4pm",
  "category": "Store Info",
  "keywords": "store hours open",
  "created_at": "2024-11-21 10:30:00"
}
```

### Constraints
- No database tables (JSON file storage only)
- All code within `comfort-comm-chatbot/` plugin folder

---

## Common Questions

### Non-Tech Questions

**Q: How do I update the FAQs?**
A: Upload a new CSV file. Check "Clear existing FAQs" to replace all, or leave unchecked to add more.

**Q: What if the bot can't find an answer?**
A: It falls back to the normal AI response using its general knowledge.

**Q: How accurate is the matching?**
A: It uses keyword matching. Questions need ~30% word overlap to match.

### Technical Questions

**Q: Why JSON instead of database?**
A: Plugin-only access constraint. JSON file lives in plugin folder, no WordPress database access needed.

**Q: How does keyword matching work?**
A: Stop words removed, remaining words compared. Score = matched words / total query words. Threshold: 30%.

**Q: Can it handle typos?**
A: Basic partial matching (substring). For better fuzzy matching, would need Levenshtein distance or similar.

**Q: How does Gemini know to use the FAQ?**
A: FAQ is injected into the prompt with explicit instruction: "Answer using ONLY this information... Rephrase in a friendly tone."
