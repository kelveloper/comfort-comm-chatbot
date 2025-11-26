# Quick Reference to Technical Spec

**Concise Overview | No Code | Last Updated: Nov 25, 2024**

---

## 🏗️ Architecture

**AI Model:** Google Gemini Flash 2.0
**Platform:** WordPress Plugin (Kognetiks Chatbot v2.3.7)
**Database:** SQLite (WordPress options table)
**Frontend:** JavaScript (jQuery) + AJAX
**Backend:** PHP

---

## 🛡️ Guardrails (3 Safety Layers)

1. **System Prompt** - Defines allowed/blocked topics, escalation rules
2. **FAQ Priority** - Searches curated knowledge base first, injects approved answers
3. **Escalation Rules** - Auto-refuses billing/SSN/passwords → provides phone number

---

## 🔍 FAQ Search Algorithm

**Type:** Keyword Matching with Stop Word Removal (NOT TF-IDF)

**Why NOT TF-IDF?**
- Small dataset (10-25 FAQs)
- Faster execution (<1ms)
- Simpler to debug
- Sufficient for exact phrase matches

**Process:**
1. Remove stop words ("the", "is", "my", etc.)
2. Compare query keywords to FAQ keywords
3. Score = matches / total words
4. Return match if score ≥ 30%

---

## 🎯 System Prompt Summary

**Blocks:** billing, payments, account balances, SSN, passwords
**Allows:** internet plans, TV packages, troubleshooting, hours/location
**Escalation:** "Call (347) 519-9999"

---

## 📊 Knowledge Base

**Format:** JSON file (`data/faqs.json`)
**Size:** 10 FAQs loaded
**Priority:** FAQ answers override Gemini general knowledge

---

## ✅ ALL 13 P0 FEATURES IMPLEMENTED

### **User Journey 1: New Customer**

1. ✅ **Natural Language Input** - Users can type plain English questions (e.g., "What are your Spectrum prices?")

2. ✅ **FAQ Knowledge Base Search** - Bot understands and provides direct answers from approved FAQ database

3. ✅ **Fallback Category Buttons** - Display preset "bubble" questions when user isn't sure what to ask

4. ✅ **CSAT Prompt After Answers** - "Was this helpful? Yes/No" shown after every bot response

---

### **User Journey 2: Recurring Customer**

5. ✅ **Support Question Understanding** - Users can type questions like "my internet is slow" or "how do I check my bill?"

6. ✅ **How-to Guide Responses** - Bot provides correct troubleshooting guides from Knowledge Base

7. ✅ **Support Category Fallback** - Display preset support bubbles for common issues

8. ✅ **CSAT Tracking** - Same thumbs up/down tracking for support questions

---

### **User Journey 3: Security & Escalation**

9. ✅ **Sensitive Question Blocking** - Bot refuses to answer questions about billing amounts, SSN, passwords

10. ✅ **Security Escalation Response** - Auto-responds: "For your account's security, I can't access personal billing details. Please call (347) 519-9999..."

11. ✅ **Cannot Understand Fallback** - When bot can't help: "I'm still learning... Please call (347) 519-9999"

---

### **Success Metrics**

12. ✅ **Automated Resolutions Tracking** - System tracks bot interactions (Target: >4 per week)

13. ✅ **CSAT Score Dashboard** - Real-time dashboard showing satisfaction score, helpful/not helpful counts, recent feedback (Target: >70%)

---

**TOTAL: 13/13 P0 Requirements Complete ✅**

---

## 📈 CSAT Tracking

**Storage:** WordPress options
**Dashboard:** Admin Reporting tab
**Shows:** Score %, helpful/not helpful counts, target status, recent feedback table
**UI:** 👍 👎 buttons after responses

---

## 🔧 Key Technologies

- Google Gemini Flash 2.0 API
- WordPress + PHP 7.4+
- SQLite database
- jQuery, AJAX
- Nonce verification, XSS protection

---

## 📂 Main Files

**Backend:** `chatbot-call-gemini-api.php`, `chatbot-kn-faq-import.php`, `chatbot-csat.php`
**Frontend:** `chatbot-chatgpt.js`, `chatbot-chatgpt.css`
**Data:** `data/faqs.json`

---

## 🚀 Performance

**Response Time:** <3 seconds target
**FAQ Search:** <1ms
**API Timeout:** 240 seconds

---

## 🔐 Security

- Input sanitization
- Output purification (DOMPurify)
- AJAX nonce verification
- API key backend-only
- Sensitive data always escalated

---

## 📊 Status

✅ **P0 Complete** - All 13 requirements
✅ **Production Ready**
✅ **CSAT Dashboard Live**
✅ **10 FAQs Loaded**

---

**For detailed technical specs, see:** `TECHNICAL-SPEC.md`
**For future plans, see:** `future_iteration.md`
**For product requirements, see:** `PRD.md`
