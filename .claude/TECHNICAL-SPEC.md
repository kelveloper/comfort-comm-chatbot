# Comfort Comm Chatbot - Technical Specification (P0)

**Last Updated:** Nov 24, 2024
**Status:** P0 Implementation

---

## 1. System Architecture

### 1.1 AI Model
- **Provider:** Google Gemini API
- **Model:** `gemini-1.5-flash` (configurable in WordPress Admin)
- **API Endpoint:** `https://generativelanguage.googleapis.com/v1beta`
- **Configuration:**
  - Max Tokens: 2048
  - Temperature: 0.7
  - Top P: 0.95
- **API Key:** Stored in WordPress options (`chatbot_gemini_api_key`)

### 1.2 Knowledge Base (CSV)
- **Format:** CSV file with Question/Answer pairs
- **Location:** `/data/` folder in plugin directory
- **Search Function:** `chatbot_faq_search()` in `chatbot-call-gemini-api.php:48-62`
- **Priority:** FAQ matches override Gemini general knowledge
- **Behavior:** If FAQ match found, Gemini rephrases answer naturally

**CSV Structure:**
```csv
Question,Answer,Category
"What are your Spectrum prices?","[Answer text]","Internet Plans"
"How do I check my bill?","[Answer text]","Billing"
```

### 1.3 WordPress Integration
- **Base Plugin:** Kognetiks Chatbot v2.3.7
- **Type:** Custom WordPress plugin
- **Display Methods:**
  - Shortcode: `[chatbot]`
  - Widget injection
  - Floating chat window (default)
- **AJAX Endpoint:** `admin-ajax.php`
- **Action:** `chatbot_chatgpt_send_message`

---

## 2. Context System (P0 Features)

### 2.1 Page Context Injection ✅ IMPLEMENTED
**File:** `includes/chatbot-call-gemini-api.php:64-99`

**How it works:**
1. Captures WordPress page ID via `get_the_id()`
2. Fetches page title, URL, and content via `get_post($page_id)`
3. Strips HTML tags and shortcodes
4. Limits to first 800 words (prevents token overflow)
5. Injects into Gemini context

**Context sent to Gemini:**
```
CURRENT PAGE CONTEXT:
The user is currently viewing the page titled: "[Page Title]"
Page URL: [URL]

Page Content:
[First 800 words...]

When answering questions, you can reference information from this page...
```

**Capabilities:**
- Bot knows what page user is viewing
- Can answer "What page am I on?"
- References page content: "On this page, it says..."

### 2.2 Conversation History
**File:** `includes/chatbot-call-gemini-api.php:178-184`

**Setting:** `chatbot_chatgpt_conversation_continuation`
- **Default:** `Off`
- **WordPress Admin Path:** Settings → General → Additional Setup → "Conversation Continuation"
- **Recommendation:** Enable for P0 (better UX)

**When enabled:**
- Bot remembers previous questions in session
- Can answer follow-up questions
- Example:
  - User: "What are Spectrum prices?"
  - Bot: "[Answer]"
  - User: "What about T-Mobile?" ← Bot remembers context

### 2.3 FAQ Context Priority
**Order of context sent to Gemini:**
1. **FAQ CSV Match** (highest priority)
2. **Current Page Content**
3. **Conversation History**
4. **General Gemini Knowledge** (fallback)

---

## 3. Message Flow

### 3.1 User Message Processing

```
1. User types message
   ↓
2. JavaScript captures input (chatbot-chatgpt.js:1165)
   ↓
3. AJAX call to WordPress backend
   - Action: chatbot_chatgpt_send_message
   - Data: message, user_id, page_id, session_id, nonce
   ↓
4. Backend processing (chatbot-call-gemini-api.php):
   a. FAQ Search (CSV lookup)
   b. Page Context Injection (current page)
   c. Conversation History (if enabled)
   d. Build Gemini API request
   ↓
5. Call Gemini API
   ↓
6. Parse response
   ↓
7. Return to frontend
   ↓
8. Display in chat UI
```

### 3.2 Session Management
- **Logged-in users:** WordPress `user_id`
- **Anonymous users:** Cookie-based `session_id` (30-day expiry)
- **Cookie name:** `kognetiks_unique_id`
- **Conversation locking:** Prevents duplicate message processing (60s timeout)

---

## 4. UI/UX (P0 Implementation)

### 4.1 Chat Interface
- **Display Style:** Floating chat bubble (default)
- **Position:** Bottom-right corner
- **Header:** "Comfort Comm Chatbot"
- **Input:** Text area with "Enter your question..." placeholder
- **Buttons:** Submit, Erase conversation, Download transcript

### 4.2 Message Styling
**User Messages:**
- Background: `#fffbeb` (light yellow)
- Text color: `#1e3a8a` (dark blue)
- Position: Right-aligned
- Border: `1px solid #fde68a`

**Bot Messages:**
- Background: `#f1f5f9` (light gray) - controlled via CSS
- Text color: `#1e3a8a` (dark blue)
- Position: Left-aligned
- Border: `1px solid #e2e8f0`

**CSS File:** `assets/css/chatbot-chatgpt.css:322-354`

**Note:** Admin appearance overrides DISABLED for P0 (custom styling only)
- See `future_iteration.md` for P2 re-enablement

### 4.3 Greeting Message
**Setting:** `chatbot_chatgpt_initial_greeting`
**Current:** "Hey there! I'm doing great, thanks for asking!..."
**Location:** WordPress Admin → Settings → General → Initial Greeting

---

## 5. Performance & Infrastructure

### 5.1 Response Time
- **Target:** <3 seconds (user submit to bot response)
- **Timeout:** 240 seconds (configurable)
- **Actual:** Depends on Gemini API latency + FAQ search

### 5.2 Error Handling
- **API Failures:** Custom error message (configurable)
- **Network Timeout:** "Oops! Something went wrong" + retry
- **Duplicate Prevention:** 5-minute window via message UUID transients

### 5.3 Rate Limiting
- **Gemini API:** Per Google Cloud project limits
- **WordPress:** No custom rate limiting in P0
- **Conversation Lock:** 60-second timeout prevents stuck conversations

---

## 6. Security (P0)

### 6.1 Input Sanitization
- All user input sanitized: `sanitizeInput()` function
- AJAX nonce verification: `chatbot_message_nonce`
- XSS protection: `htmlspecialchars()`, `strip_tags()`

**File:** `assets/js/chatbot-chatgpt.js:1597-1612`

### 6.2 Output Sanitization
- Bot responses purified: DOMPurify (client-side)
- Markdown rendering (safe subset)
- Links force `target="_blank"`

### 6.3 API Key Security
- Stored in WordPress `wp_options` table
- Not exposed to frontend
- Backend-only API calls

---

## 7. Key Files Modified for P0

### PHP Backend
```
includes/chatbot-call-gemini-api.php
├── Lines 48-62:  FAQ search integration
├── Lines 64-99:  Page context injection (NEW - Nov 24)
├── Lines 178-184: Conversation continuation
└── Lines 216:     Context assembly for Gemini

includes/appearance/chatbot-settings-appearance-text.php
├── Lines 50-56:  Admin color overrides DISABLED
└── Lines 85-90, 175-182: Admin overrides DISABLED
```

### JavaScript Frontend
```
assets/js/chatbot-chatgpt.js
├── Lines 1165-1594: Submit message handler
├── Lines 1251: User message display
├── Lines 495-498: Subsequent greeting bug FIX (Nov 24)
└── Lines 830-930: appendMessage() function
```

### CSS Styling
```
assets/css/chatbot-chatgpt.css
├── Lines 322-338: User message styling (FIXED with !important)
└── Lines 340-354: Bot message styling
```

---

## 8. WordPress Admin Settings (P0 Configuration)

### Required Settings to Configure:

**API/Gemini Tab:**
- ✅ Gemini API Key (required)
- Model: `gemini-1.5-flash` (default)
- Max Tokens: 2048
- Temperature: 0.7

**General Tab:**
- Chatbot Name: "Comfort Comm Chatbot"
- Initial Greeting: [Custom greeting message]
- Conversation Continuation: **ENABLE** (recommended for P0)
- Bot Prompt: "Enter your question..."

**Appearance Tab:**
- Display Style: Floating (default)
- Width: Narrow
- **Note:** Color overrides disabled for P0 (CSS-controlled)

---

## 9. Testing Checklist (P0)

### Functional Tests
- [ ] FAQ questions return correct answers from CSV
- [ ] "What page am I on?" returns correct page name
- [ ] Page content references work ("On this page...")
- [ ] User messages visible (text appears in yellow box)
- [ ] Bot messages visible (text appears in gray box)
- [ ] Conversation history works (follow-up questions)
- [ ] Multiple messages don't duplicate (no 3x logging)

### Integration Tests
- [ ] Homepage page context loads
- [ ] FAQ CSV file accessible
- [ ] Gemini API key valid
- [ ] AJAX calls succeed
- [ ] Session management works

### Browser Tests
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile (responsive)

---

## 10. Known Issues & Bugs Fixed

### ✅ Fixed (Nov 24, 2024)
1. **User messages invisible** - CSS color override issue
   - Fix: Added `!important` to user text color
   - File: `chatbot-chatgpt.css:325, 335-338`

2. **JavaScript error: `subsequentGreeting` undefined**
   - Broke chatbot initialization
   - Fix: Commented out undefined variable check
   - File: `chatbot-chatgpt.js:495-498`

3. **Admin color overrides conflicting with custom CSS**
   - Green bot messages overriding gray
   - Fix: Disabled admin appearance functions
   - File: `chatbot-settings-appearance-text.php`

### ⚠️ Known Issues (Not Critical for P0)
1. **Debug message logs 3x** - Submit button triggered multiple times
   - File: `chatbot-chatgpt.js:1252`
   - Impact: Minor (just console spam)

---

## 11. Deployment Notes

### Pre-Launch
1. Populate FAQ CSV with Top 10-25 questions
2. Verify Gemini API key in Admin
3. Enable "Conversation Continuation" setting
4. Test on staging site
5. Clear browser cache before testing

### Post-Launch Monitoring
- Check chatbot logs: `/chatbot-logs/` directory
- Monitor Gemini API usage (Google Cloud Console)
- Review conversation transcripts weekly

---

## 12. Future Enhancements (P1/P2)

See `future_iteration.md` for:
- Admin appearance settings (P2)
- CSAT prompt implementation (P1)
- Sensitive question detection (P0 - TODO)
- Analytics dashboard (P1)
- Multilingual support (P2)

---

## Appendix: Technical Dependencies

### WordPress
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

### External APIs
- Google Gemini API (requires API key)

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
